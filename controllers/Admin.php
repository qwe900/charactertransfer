<?php

class Admin extends MX_Controller
{
    // Allgemeine Properties
    private $canCache = false;

    private $js;
    private $css;
    private $id;
    private $cache_dir = 'application/cache/charactertransfer/';

    // Character-/Realm-bezogene Properties
    private $realm;
    private $realmName;
    private $charData = [];
    private $class;
    private $className;
    private $race;
    private $raceName;
    private $level;
    private $accountId;
    private $account;
    private $gender;

    private $stats;
    private $items = [];
    private $model = [];

    public function __construct()
    {
        parent::__construct();

        $this->load->library('administrator');
        $this->load->model('transfer_model');
        $this->load->model('admin_transfer_model');

        $this->css = "modules/charactertransfer/css/character.css";

        // Initialisierungen
        $this->model = [];
        $this->canCache = false;
        $this->items = [];

        // Cache-Verzeichnis anlegen, falls nicht vorhanden
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }

        // Falls du später wieder Berechtigungen willst:
        // requirePermission("canViewAdmin");
        // requirePermission("viewAdmin");
    }

    /**
     * Simple file-based caching for API calls
     */
    private function getCache($key)
    {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (file_exists($file) && (time() - filemtime($file) < 3600)) { // 1 hour cache
            return unserialize(file_get_contents($file));
        }
        return false;
    }

    private function setCache($key, $data)
    {
        $file = $this->cache_dir . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }

    public function getItemHTML($item)
    {
        if (is_array($item)) {
            $itemId = $item["ID"];
            $url = "https://www.wowhead.com/wotlk/de/item=" . $itemId;
            $gems = $item["G1"] . ":" . $item["G2"] . ":" . $item["G3"];
            $entchandID = $item["E"];

            $html = '<a data-wh-icon-size="large" href="' . $url . '" rel="item=' . $itemId . '&gems=' . $gems . '&ench=' . $entchandID . '"></a>';
        } else {
            $itemId = $item;
            $url = "https://www.wowhead.com/wotlk/de/item=" . $itemId;
            $html = '<a data-wh-icon-size="large" href="' . $url . '" rel="item=' . $itemId . '"></a>';
        }

        return $html;
    }

    public function getItemDisplayID($itemId)
    {
        try {
            $url = "https://www.wowhead.com/wotlk/de/item=" . $itemId . "&xml";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'FusionGen Character Transfer/1.0'
                ]
            ]);
            $xmlString = file_get_contents($url, false, $context);

            if ($xmlString === false) {
                throw new Exception("Failed to fetch XML from Wowhead");
            }

            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                throw new Exception("Failed to parse XML");
            }

            $displayId = (string)$xml->item->icon['displayId'];

            return $displayId ?: '0';
        } catch (Exception $e) {
            error_log("getItemDisplayID error for item {$itemId}: " . $e->getMessage());
            return '0';
        }
    }

    public function getreplacementItem($itemid)
    {
        // Besser vorbereitetes Statement benutzen, aber ich lasse deine Logik drin
        $query = $this->db->query("SELECT replacementitemid FROM character_transfer_item_replacements WHERE itemid = ?", [$itemid]);

        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            return $row["replacementitemid"];
        } else {
            $cacheKey = 'replacement_' . $itemid;
            $cached = $this->getCache($cacheKey);
            if ($cached !== false) {
                return $cached;
            }

            try {
                $url = 'https://www.wowhead.com/wotlk/item=' . $itemid;

                $context = stream_context_create([
                    'http' => [
                        'timeout' => 15,
                        'user_agent' => 'FusionGen Character Transfer/1.0'
                    ]
                ]);

                $html = file_get_contents($url, false, $context);

                if ($html === false) {
                    throw new Exception("Failed to fetch HTML from Wowhead");
                }

                $doc = new DOMDocument();
                @$doc->loadHTML($html);
                $xpath = new DOMXPath($doc);

                $scriptTags = $xpath->query('//script');
                $items = array();
                $lowestLevelItem = null;

                foreach ($scriptTags as $tag) {
                    $jsCode = $tag->nodeValue;
                    if (preg_match("/new Listview\(\{\s*template: 'item',\s*id: 'see-also',.*?\}\);/s", $jsCode, $matches)) {
                        if (preg_match('/data: (\[.*?\]),\s*\}\);/s', $matches[0], $dataMatches)) {
                            $jsonData = $dataMatches[1];
                            $dataArray = json_decode($jsonData, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                $items = $dataArray;
                            }
                        }
                        break;
                    }
                }

                if (count($items) > 0) {
                    foreach ($items as $item) {
                        if ($lowestLevelItem === null || $item['level'] < $lowestLevelItem['level']) {
                            $lowestLevelItem = $item;
                        }
                    }

                    $newitemid = $lowestLevelItem["id"];
                    $this->db->query(
                        "INSERT INTO `character_transfer_item_replacements` (`itemid`, `replacementitemid`) VALUES (?, ?)",
                        array($itemid, $newitemid)
                    );
                    $this->setCache($cacheKey, $newitemid);
                    return $newitemid;
                } else {
                    $this->db->query(
                        "INSERT INTO `character_transfer_item_replacements` (`itemid`, `replacementitemid`) VALUES (?, ?)",
                        array($itemid, $itemid)
                    );
                    $this->setCache($cacheKey, $itemid);
                    return $itemid;
                }
            } catch (Exception $e) {
                error_log("getreplacementItem error for item {$itemid}: " . $e->getMessage());
                $this->db->query(
                    "INSERT INTO `character_transfer_item_replacements` (`itemid`, `replacementitemid`) VALUES (?, ?)",
                    array($itemid, $itemid)
                );
                $this->setCache($cacheKey, $itemid);
                return $itemid;
            }
        }
    }

    /**
     * Get character info
     */
    private function getInfo($id = false)
    {
        $data = $this->transfer_model->getTransferByID($id);
        if ($data == false) {
            $this->getError();
            return;
        }

        $this->charData = $this->transfer_model->ReadCharacterDump($data[0]["chardump"]);

        // Load the items
        $items = $this->charData["Equippment"];

        // Item slots
        $slots = array(
            0  => "none",
            1  => "head",
            2  => "neck",
            3  => "shoulders",
            4  => "body",
            5  => "chest",
            6  => "waist",
            7  => "legs",
            8  => "feet",
            9  => "wrists",
            10 => "hands",
            11 => "finger1",
            12 => "finger2",
            13 => "trinket1",
            14 => "trinket2",
            15 => "back",
            16 => "mainhand",
            17 => "offhand",
            18 => "ranged",
            19 => "tabard",
            20 => "bag1",
            21 => "bag2",
            22 => "bag3",
            23 => "bag4"
        );

        $allowedmodels = array(
            1, 2, 3, 4, 5, 6, 7, 8,
            9, 10, 15, 16, 17, 18, 19
        );

        if (is_array($items)) {
            foreach ($slots as $slot => $slotname) {
                if (isset($items[$slot])) {
                    $this->items[$slotname]["equipped"] = $this->getItemHTML($items[$slot]);
                    $this->items[$slotname]["replacement"] = $this->getItemHTML(
                        $this->getreplacementItem($items[$slot]["ID"])
                    );

                    if (in_array($slot, $allowedmodels)) {
                        $this->model[] = array(
                            "item" => array(
                                "entry"     => (int)$items[$slot]["ID"],
                                "displayid" => (int)$this->getItemDisplayID($items[$slot]["ID"])
                            ),
                            "transmog" => (object)array(),
                            "slot"     => $slot
                        );
                    }
                } else {
                    $this->items[$slotname]["equipped"] =
                        "<img style='width:68px;height:68px' src='" .
                        $this->template->page_url .
                        "application/modules/charactertransfer/img/" . $slotname . ".png' />";
                }
            }
        }

        $this->charData['items'] = $this->items;
        $this->charData['model'] = $this->model;
    }

    public function index()
    {
        $data["transferdata"] = $this->transfer_model->getAllTransfers();
        $output = $this->template->loadPage("admin.tpl", $data);
        $this->template->view($output);
    }

    public function view($id)
    {
        $this->getInfo($id);
        $this->template->setTitle("Validate: " . $this->charData["main"]["name"]);

        $data = array(
            "module"   => "default",
            "headline" => "Charactervalidation",
            "content"  => $this->template->loadPage("view.tpl", $this->charData),
        );

        $page = $this->template->loadPage("page.tpl", $data);
        $this->template->view($page, $this->css);
    }

    /**
     * Show "character doesn't exist" error
     */
    private function getError($get = false)
    {
        $this->template->setTitle("Error");

        $data = array(
            "module"   => "default",
            "headline" => "Character Does not Exists",
            "content"  => "<center style='margin:10px;font-weight:bold;'>nope</center>"
        );

        $page = $this->template->loadPage("page.tpl", $data);

        if ($get) {
            return $page;
        } else {
            $this->template->view($page);
        }
    }
}
