<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Replica extends MX_Controller
{
    private $cacheDir;
    private $cacheTtlSeconds = 86400 * 7; // 7 days

    // allow-list: URL prefix => upstream base
    private $allowMap = [
        'modelviewer/live' => 'https://wow.zamimg.com/modelviewer/live/',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->cacheDir = APPPATH . 'modules/charactertransfer/cache/replica';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        $this->logFile = dirname($this->cacheDir) . '/replica.log';
    }

    /**
     * Catch EVERYTHING after /replica/*
     * Example:
     * /charactertransfer/replica/modelviewer/live/viewer/viewer.min.js
     */
    public function _remap($firstSegment, $params = [])
    {
        // Log the request
        $logEntry = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            current_url()
        );
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // =======================
        // DEBUG: return resolved URL
        // =======================
        if ($this->input->get('debug') === 'url') {
            $prefixA = strtolower($firstSegment);
            $prefixB = strtolower($params[0] ?? '');

            if (!$prefixA || !$prefixB) {
                return $this->fail(400, 'Missing provider/path');
            }

            $allowKey = $prefixA . '/' . $prefixB;
            if (!isset($this->allowMap[$allowKey])) {
                return $this->fail(403, 'Upstream not allowed');
            }

            $relPath = $this->sanitizeRelativePath(
                implode('/', array_slice($params, 1))
            );

            $upstreamUrl = rtrim($this->allowMap[$allowKey], '/') . '/' . $relPath;

            $this->sendCorsHeaders();
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'requested' => current_url(),
                    'upstream'  => $upstreamUrl,
                ], JSON_PRETTY_PRINT));
            return;
        }
        // --- CORS preflight ---
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->sendCorsHeaders();
            $this->output
                ->set_status_header(204)
                ->set_output('');
            return;
        }

        // firstSegment = modelviewer
        // params[0]    = live
        if (!$firstSegment || empty($params)) {
            return $this->fail(400, 'Bad request: missing provider/path');
        }

        $prefixA = strtolower($firstSegment);
        $prefixB = strtolower(array_shift($params));

        $allowKey = $prefixA . '/' . $prefixB;
        if (!isset($this->allowMap[$allowKey])) {
            return $this->fail(403, 'Forbidden: upstream not allowed');
        }

        $relPath = $this->sanitizeRelativePath(implode('/', $params));
        if ($relPath === '') {
            return $this->fail(400, 'Bad request: empty path');
        }

        $baseUrl = rtrim($this->allowMap[$allowKey], '/');
        $upstreamUrl = $baseUrl . '/' . $relPath;
        $wasFallback = false;

        // Log the external URL being requested
        $logEntry = sprintf("[%s] External URL: %s\n", date('Y-m-d H:i:s'), $upstreamUrl);
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);

        $rangeHeader       = $this->getRequestHeader('Range');
        $ifNoneMatch       = $this->getRequestHeader('If-None-Match');
        $ifModifiedSince   = $this->getRequestHeader('If-Modified-Since');

        $cacheKey  = hash('sha256', $upstreamUrl);
        $cachePath = $this->cacheDir . '/' . $cacheKey . '.bin';
        $metaPath  = $this->cacheDir . '/' . $cacheKey . '.json';

        // --- Serve cached full file ---
        if (!$rangeHeader && is_file($cachePath)) {
            $meta = $this->readMeta($metaPath);
            if ($meta && (time() - $meta['stored_at']) < $this->cacheTtlSeconds) {
                return $this->serveFromCache($cachePath, $meta);
            }
        }

        // --- Fetch upstream ---
        $resp = $this->fetchUpstream($upstreamUrl, $rangeHeader, $ifNoneMatch, $ifModifiedSince);

        if ($resp['status'] === 304 && is_file($cachePath)) {
            $meta = $this->readMeta($metaPath);
            return $this->serveFromCache($cachePath, $meta ?: []);
        }

        // --- Cache full responses ---
        if ($resp['status'] === 200 && !$rangeHeader && $resp['body'] !== null && !$wasFallback) {
            file_put_contents($cachePath, $resp['body']);
            $meta = [
                'stored_at'     => time(),
                'etag'          => $resp['headers']['etag'] ?? null,
                'last_modified' => $resp['headers']['last-modified'] ?? null,
                'content_type'  => $this->detectContentType($resp['headers'], $cachePath),
            ];
            $this->writeMeta($metaPath, $meta);
        }

        return $this->sendResponse(
            $resp['status'],
            $resp['headers'],
            $resp['body'],
            $this->detectContentType($resp['headers'], $cachePath)
        );
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function fetchUpstream(string $url, ?string $range, ?string $etag, ?string $modified)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
        ]);

        $headers = ['User-Agent: FusionGEN-Replica/1.0'];
        if ($range)     $headers[] = "Range: $range";
        if ($etag)      $headers[] = "If-None-Match: $etag";
        if ($modified)  $headers[] = "If-Modified-Since: $modified";

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($ch);
        if ($resp === false) {
            $logEntry = sprintf("[%s] Fetch failed for URL: %s\n", date('Y-m-d H:i:s'), $url);
            file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
            curl_close($ch);
            return ['status' => 502, 'headers' => [], 'body' => null];
        }

        $status     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        return [
            'status'  => $status,
            'headers' => $this->parseHeaders(substr($resp, 0, $headerSize)),
            'body'    => substr($resp, $headerSize),
        ];
    }

    private function serveFromCache(string $path, array $meta)
    {
        $this->sendCorsHeaders();
        $this->output
            ->set_status_header(200)
            ->set_content_type($meta['content_type'] ?? 'application/octet-stream')
            ->set_output(file_get_contents($path));
    }

    private function sendResponse(int $status, array $headers, ?string $body, string $type)
    {
        $this->sendCorsHeaders();
        $this->output->set_status_header($status)->set_content_type($type);

        foreach (['etag','last-modified','content-range','accept-ranges','content-length'] as $h) {
            if (isset($headers[$h])) {
                $this->output->set_header(ucwords($h, '-') . ': ' . $headers[$h]);
            }
        }

        $this->output->set_output($body ?? '');
    }

    private function parseHeaders(string $raw)
    {
        $out = [];
        foreach (preg_split("/\r?\n/", $raw) as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $out[strtolower(trim($k))] = trim($v);
            }
        }
        return $out;
    }

    private function detectContentType(array $headers, string $path)
    {
        if (isset($headers['content-type'])) {
            return explode(';', $headers['content-type'])[0];
        }

        return [
            'js'   => 'application/javascript',
            'mjs'  => 'application/javascript',
            'wasm' => 'application/wasm',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'css'  => 'text/css',
            'json' => 'application/json',
            'glb'  => 'model/gltf-binary',
        ][strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    private function sendCorsHeaders()
    {
        $this->output
            ->set_header('Access-Control-Allow-Origin: *')
            ->set_header('Access-Control-Allow-Methods: GET, OPTIONS')
            ->set_header('Access-Control-Allow-Headers: Origin, Range, Content-Type, Accept');
    }

    private function sanitizeRelativePath(string $path)
    {
        if (preg_match('#^[a-z]+://#i', $path)) return '';
        $out = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') return '';
            $out[] = $seg;
        }
        return implode('/', $out);
    }

    private function readMeta(string $p)
    {
        return is_file($p) ? json_decode(file_get_contents($p), true) : null;
    }

    private function writeMeta(string $p, array $m)
    {
        file_put_contents($p, json_encode($m));
    }

    private function getRequestHeader(string $name)
    {
        return $this->input->get_request_header($name, true);
    }

    private function fail(int $status, string $msg)
    {
        $this->sendCorsHeaders();
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => $msg]));
    }
}
