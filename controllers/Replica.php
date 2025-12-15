<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Replica extends MX_Controller
{
    private $cacheDir;
    private $cacheTtlSeconds = 86400 * 7; // 7 Tage

    private $allowMap = [
        'modelviewer/live' => 'https://wow.zamimg.com/modelviewer/live',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->cacheDir = APPPATH . 'modules/charactertransfer/cache/replica';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function index_options()
    {
        $this->sendCorsHeaders();
        $this->output->set_status_header(204)->set_output('');
    }

    public function index()
    {
        $this->sendCorsHeaders();

        $segments = $this->uri->segment_array();
        $replicaIdx = $this->findSegmentIndex($segments, 'replica');
        if ($replicaIdx === -1 || count($segments) <= $replicaIdx + 2) {
            return $this->fail(400, 'Bad request: missing provider/path');
        }

        $prefixA = $segments[$replicaIdx + 1];
        $prefixB = $segments[$replicaIdx + 2];
        $allowKey = strtolower($prefixA . '/' . $prefixB);
        if (!isset($this->allowMap[$allowKey])) {
            return $this->fail(403, 'Forbidden: upstream not allowed');
        }

        $remainder = array_slice($segments, $replicaIdx + 3);
        $relPath = $this->sanitizeRelativePath(implode('/', $remainder));
        if ($relPath === '') {
            return $this->fail(400, 'Bad request: empty path');
        }

        $base = rtrim($this->allowMap[$allowKey], '/');
        $upstreamUrl = $base . '/' . $relPath;

        $rangeHeader = $this->getRequestHeader('Range');
        $ifNoneMatch = $this->getRequestHeader('If-None-Match');
        $ifModifiedSince = $this->getRequestHeader('If-Modified-Since');

        $cacheKey = $this->buildCacheKey($upstreamUrl);
        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.bin';
        $metaPath  = $this->cacheDir . DIRECTORY_SEPARATOR . $cacheKey . '.json';

        if (empty($rangeHeader) && is_file($cachePath) && is_readable($cachePath)) {
            $meta = $this->readMeta($metaPath);
            if ($meta && isset($meta['stored_at']) && (time() - (int)$meta['stored_at'] < $this->cacheTtlSeconds)) {
                return $this->serveFromCache($cachePath, $meta);
            }
            $etag = $meta['etag'] ?? null;
            $lastMod = $meta['last_modified'] ?? null;
            $resp = $this->fetchUpstream($upstreamUrl, null, $etag, $lastMod);
            if ($resp['status'] === 304) {
                if ($meta) {
                    $meta['stored_at'] = time();
                    $this->writeMeta($metaPath, $meta);
                }
                return $this->serveFromCache($cachePath, $meta ?: []);
            }
            return $this->serveAndMaybeCache($resp, $cachePath, $metaPath, $rangeHeader);
        }

        if (!empty($rangeHeader) && is_file($cachePath) && is_readable($cachePath)) {
            $meta = $this->readMeta($metaPath) ?: [];
            return $this->serveRangeFromLocal($cachePath, $meta, $rangeHeader);
        }

        $resp = $this->fetchUpstream($upstreamUrl, $rangeHeader, $ifNoneMatch, $ifModifiedSince);
        return $this->serveAndMaybeCache($resp, $cachePath, $metaPath, $rangeHeader);
    }

    private function serveAndMaybeCache(array $resp, string $cachePath, string $metaPath, ?string $rangeHeader)
    {
        $status = $resp['status'];
        $headers = $resp['headers'];
        $body = $resp['body'];
        $contentType = $this->detectContentType($headers, $cachePath);

        if ($status === 200 && empty($rangeHeader) && $body !== null) {
            @file_put_contents($cachePath, $body);
            $meta = [
                'stored_at'     => time(),
                'etag'          => $headers['etag'] ?? null,
                'last_modified' => $headers['last-modified'] ?? null,
                'content_type'  => $contentType,
                'content_length'=> isset($headers['content-length']) ? (int)$headers['content-length'] : strlen($body),
                'accept_ranges' => $headers['accept-ranges'] ?? 'bytes',
            ];
            $this->writeMeta($metaPath, $meta);
        }

        return $this->sendResponse($status, $headers, $body, $contentType);
    }

    private function serveFromCache(string $cachePath, array $meta)
    {
        $size = filesize($cachePath);
        $contentType = $meta['content_type'] ?? $this->detectContentType([], $cachePath);
        $this->setStandardHeaders($contentType, $size, 200);
        $this->output->set_output(file_get_contents($cachePath));
        return;
    }

    private function serveRangeFromLocal(string $cachePath, array $meta, string $rangeHeader)
    {
        if (!preg_match('/bytes=([0-9]*)-([0-9]*)/', $rangeHeader, $m)) {
            return $this->fail(416, 'Invalid Range');
        }
        $size = filesize($cachePath);
        $start = ($m[1] !== '') ? (int)$m[1] : 0;
        $end = ($m[2] !== '') ? (int)$m[2] : ($size - 1);
        if ($start > $end || $end >= $size) {
            return $this->fail(416, 'Range Not Satisfiable');
        }
        $length = $end - $start + 1;
        $contentType = $meta['content_type'] ?? $this->detectContentType([], $cachePath);
        $this->output
            ->set_status_header(206)
            ->set_header('Accept-Ranges: bytes')
            ->set_header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size)
            ->set_header('Content-Length: ' . $length)
            ->set_content_type($contentType);
        $this->sendCorsHeaders();
        $fh = fopen($cachePath, 'rb');
        if ($fh) {
            fseek($fh, $start);
            $data = fread($fh, $length);
            fclose($fh);
            $this->output->set_output($data);
        } else {
            $this->fail(500, 'Failed to read cached file');
        }
        return;
    }

    private function fetchUpstream(string $url, ?string $rangeHeader, ?string $ifNoneMatch, ?string $ifModifiedSince)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $reqHeaders = [
            'User-Agent: FusionGEN-Replica/1.0',
        ];
        if (!empty($rangeHeader)) $reqHeaders[] = 'Range: ' . $rangeHeader;
        if (!empty($ifNoneMatch)) $reqHeaders[] = 'If-None-Match: ' . $ifNoneMatch;
        if (!empty($ifModifiedSince)) $reqHeaders[] = 'If-Modified-Since: ' . $ifModifiedSince;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['status' => 502, 'headers' => [], 'body' => null, 'error' => $err];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($resp, 0, $headerSize);
        $body = substr($resp, $headerSize);
        curl_close($ch);

        $headers = $this->parseHeaders($rawHeaders);
        return ['status' => $status, 'headers' => $headers, 'body' => $body];
    }

    private function parseHeaders(string $raw)
    {
        $headers = [];
        $lines = preg_split("/(\r?\n)+/", trim($raw));
        foreach ($lines as $line) {
            if (stripos($line, 'HTTP/') === 0) continue;
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $name = strtolower(trim($parts[0]));
                $value = trim($parts[1]);
                $headers[$name] = isset($headers[$name]) ? ($headers[$name] . ', ' . $value) : $value;
            }
        }
        return $headers;
    }

    private function detectContentType(array $headers, string $path)
    {
        if (isset($headers['content-type'])) {
            $ct = explode(';', $headers['content-type'])[0];
            return trim($ct);
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'js' => 'application/javascript', 'mjs'=> 'application/javascript', 'wasm' => 'application/wasm',
            'json' => 'application/json', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg'=> 'image/jpeg',
            'gif' => 'image/gif', 'webp'=> 'image/webp', 'svg' => 'image/svg+xml', 'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg', 'mp4' => 'video/mp4', 'glb' => 'model/gltf-binary', 'gltf'=> 'model/gltf+json',
            'bin' => 'application/octet-stream', 'txt' => 'text/plain', 'css' => 'text/css',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }

    private function setStandardHeaders(string $contentType, int $length, int $status)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type($contentType)
            ->set_header('Content-Length: ' . $length)
            ->set_header('Accept-Ranges: bytes');
        $this->sendCorsHeaders();
    }

    private function sendResponse(int $status, array $headers, ?string $body, ?string $contentType)
    {
        $passHeaders = ['etag', 'last-modified', 'content-length', 'content-range', 'accept-ranges', 'cache-control', 'expires'];
        $out = $this->output->set_status_header($status);
        if ($contentType) $out->set_content_type($contentType);
        foreach ($passHeaders as $name) {
            if (isset($headers[$name])) $this->output->set_header($this->canonicalHeaderName($name) . ': ' . $headers[$name]);
        }
        $this->sendCorsHeaders();
        $out->set_output($body !== null ? $body : '');
        return;
    }

    private function sendCorsHeaders()
    {
        $this->output
            ->set_header('Access-Control-Allow-Origin: *')
            ->set_header('Access-Control-Allow-Methods: GET, OPTIONS')
            ->set_header('Access-Control-Allow-Headers: Origin, Range, Content-Type, Accept')
            ->set_header('Vary: Origin');
    }

    private function buildCacheKey(string $url) { return hash('sha256', $url); }
    private function readMeta(string $metaPath)
    {
        if (!is_file($metaPath)) return null;
        $raw = @file_get_contents($metaPath);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
    private function writeMeta(string $metaPath, array $meta) { @file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_SLASHES)); }
    private function canonicalHeaderName(string $name)
    { $parts = explode('-', $name); $parts = array_map(function ($p) { return ucfirst($p); }, $parts); return implode('-', $parts); }
    private function getRequestHeader(string $name)
    { $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name)); if (isset($_SERVER[$key])) return $_SERVER[$key]; $val = $this->input->get_request_header($name, true); return $val ?: null; }

    private function sanitizeRelativePath(string $path)
    {
        $path = preg_replace('/[?#].*/', '', $path);
        if (preg_match('/^[a-zA-Z]+:\/\//i', $path)) { return ''; }
        $parts = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') return '';
            $parts[] = $seg;
        }
        return implode('/', $parts);
    }

    private function findSegmentIndex(array $segments, string $needle)
    { $i = 0; foreach ($segments as $seg) { if (strtolower($seg) === strtolower($needle)) return $i; $i++; } return -1; }

    private function fail(int $status, string $message)
    { $this->sendCorsHeaders(); $this->output->set_status_header($status)->set_content_type('application/json')->set_output(json_encode(['error' => $message])); return; }
}
