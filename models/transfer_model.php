<?php
$mysqloutput = true;
class transfer_model extends CI_Model
{
 

    private $spelldata;
    private $talentspells;
    private $factions;

    public function __construct()
    {
        parent::__construct();
        $this->load->config('constants');
        $this->spelldata = $this->config->item('spelldata'); // laod spelldata array
        $this->talentspells = $this->config->item('talentspells'); // load talentspells
        $this->factions = $this->config->item('factions'); // load factions libary for each language codes
    }
  

public function getAllTransfers() {
    
    $query = $this->db->query("SELECT id, accountid, granted, status, charactername, realm, server, class, race , gender FROM character_transfer ORDER BY `id` ASC");
    if ($query->num_rows() > 0) {
        $data = array();
        $row = $query->result_array();
        return $this->ReturnTransferData($row);
    } else {
        return false;
    }
}

public function getTransfersByAccountID($userid) {
      if(is_numeric($userid)){
    $query = $this->db->query("SELECT id, accountid, granted, status, charactername, realm, server, class, race , gender FROM character_transfer where `accountid` = '{$userid}' ORDER BY `id` ASC");
    if ($query->num_rows() > 0) {
        $data = array();
        $row = $query->result_array();
         return $this->ReturnTransferData($row);
    } else {
        return false;
    }
}
}

public function getTransferByID($id) {
    if(is_numeric($id)){
    $query = $this->db->query("SELECT * FROM character_transfer where `id` = {$id}");
    if ($query->num_rows() > 0) {
        $data = array();
        $row = $query->result_array();
        foreach ($row as $character) {
            $data[] = array(
                "id" => $character["id"], 
                "chardump" => json_decode($character["chardump"],true), 
                "userid" => $character["accountid"], 
                "charactername" => $character["charactername"], 
                "race" => $this->getRaceString($character["race"]), 
                "gender" => $this->getGenderString($character["gender"]), 
                "class" => $this->getClassString($character["class"]),
                "realm" => $character["realm"],
                "status" => $this->getStatus($character["status"])
            );
           
        }
        return $data;
    } else {
        return false;
    }
}
}



public function ReturnTransferData($row) {
   
     foreach ($row as $character) {
            $data[] = array(
                "id" => $character["id"], 
                "userid" => $character["accountid"], 
                "charactername" => $character["charactername"], 
                "race" => $this->getRaceString($character["race"]), 
                "gender" => $this->getGenderString($character["gender"]), 
                "class" => $this->getClassString($character["class"]),
                "realm" => $character["realm"],
                "status" => $this->getStatus($character["status"])
            );
        }
      
        return $data;
}
    


    
    
           public function checkTransfer($userid,$charname, $realm, $serverip)
    {
        $query = $this->db->query("SELECT * FROM character_transfer  Where accountid = {$userid} AND `charactername` = '{$charname}' AND `realm` = '{$realm}' AND `server` = '{$serverip}' ORDER BY `id` ASC");
       
        if ($query->num_rows() > 0) {
           
            return true;
        } else {
            return false;
        }
    }
    
    public function getGenderString($gender)
{
    switch ($gender) {
        case 0:
            return 'Male';
            break;
        case 1:
            return 'Female';
            break;
        default:
        return 'uknown';
    }
}

    public function getStatus($statuscode)
{
    switch ($statuscode) {
        case 0:
            return 'Not Reviewd';
            break;
        case 1:
            return 'Approved';
            break;
        case 2: 
            return 'In Progress';
            break;
        case 3:
            return 'Denied';
            break;
          default:
        return 'uknown';
    }
}
    public function getClassString($class)
{
    switch ($class) {
        case 0:
            return 'None';
        case 1:
            return 'Warrior';
        case 2:
            return 'Paladin';
        case 3:
            return 'Hunter';
        case 4:
            return 'Rogue';
        case 5:
            return 'Priest';
        case 6:
            return 'DeathKnight';
        case 7:
            return 'Shaman';
        case 8:
            return 'Mage';
        case 9:
            return 'Warlock';
        case 10:
            return 'Monk';
        case 11:
            return 'Druid';
        case 12:
            return 'Demon Hunter';
        default:
            return 'Unknown';
    }
}
public function getRaceString($race)
{
    switch ($race) {
        case 1:
            return 'Human';
        case 2:
            return 'Orc';
        case 3:
            return 'Dwarf';
        case 4:
            return 'Night Elf';
        case 5:
            return 'Undead';
        case 6:
            return 'Tauren';
        case 7:
            return 'Gnome';
        case 8:
            return 'Troll';
        case 10:
            return 'Blood Elf';
        case 11:
            return 'Draenei';
        default:
            return 'Unknown';
    }
}
   
public function insertTransfer($chardump)
{
    $user_id = $this->user->getId();
    $characterdata = $this->ReadCharacterDump(json_decode($this->ReturnCharDumpAsJson($chardump), true));
    if (!$this->checkTransfer($this->user->getId(), $characterdata["main"]["name"], $characterdata["main"]["ServerRealm"], $characterdata["main"]["ServerIP"]))
    {
        $data = array(
            'accountid' => $user_id,
            'chardump' => $this->ReturnCharDumpAsJson($chardump),
            'granted' => 0,
            'status' => 0,
            'charactername' => $characterdata["main"]["name"],
            'realm' => $characterdata["main"]["ServerRealm"],
            'server' => $characterdata["main"]["ServerIP"],
            'class' => $characterdata["main"]["class"],
            'race' => $characterdata["main"]["race"],
            'gender' => $characterdata["main"]["gender"]
            
        );
        
        // Insert into database
        $this->db->insert('character_transfer', $data);
        
        // Get the insert ID
        $insert_id = $this->db->insert_id();
        
        return $insert_id;
    } else {
        return false;
    }
}

        
    public function decodecharacterdump($data)
        {
            $data = str_replace(array(
                "'",
                "\\"
            ) , "", strrev(base64_decode(base64_decode(strrev($data)))));

            return json_decode($data, true);

        }    
       

        
        
        public function ReturnCharDumpAsJson($data) {
            // Extract CHDMP_DATA and CHDMP_KEY from Lua file using regex
            preg_match('/CHDMP_DATA\s*=\s*"([^"]+)"/', $data, $dataMatch);
            preg_match('/CHDMP_KEY\s*=\s*"([^"]+)"/', $data, $keyMatch);

            if (!isset($dataMatch[1]) || !isset($keyMatch[1])) {
                return json_encode([]); // Return empty array if parsing fails
            }

            $CharacterData = $dataMatch[1] . $keyMatch[1];
            return json_encode($this->decodecharacterdump($CharacterData), true);
        }

        public function convertSeconds($seconds) {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
          
        
            return "$days days, $hours hours, $minutes minutes";
        }
        public function ReadCharacterDump($data) { 
            $chardata = [];
            $newInventory = [];
            $characterequippment = [];
            $chardata["main"] = [];
        
            
            //realign data in new arrays
            $chardata["main"]["locale"] = $data["globalinfo"]["locale"];
            $chardata["main"]["name"] = $data["unitinfo"]["name"];
            $chardata["main"]["specs"] = $data["unitinfo"]["specs"];
            $chardata["main"]["honor"] = $data["unitinfo"]["honor"];
            $chardata["main"]["stats"] = $data["unitstats"];
            $chardata["main"]["playtime"] = $this->convertSeconds($data["unitinfo"]["playtime"]);
            $chardata["main"]["kills"] = $data["unitinfo"]["kills"];
            $chardata["main"]["arenapoints"] = $data["unitinfo"]["arenapoints"];
            $chardata["main"]["level"] = $data["unitinfo"]["level"];
            
        
           
            $class = 0;
            $spells = [];
            if (isset($data["unitinfo"]["class"])) {
                switch ($data["unitinfo"]["class"])
                {
                    case "WARRIOR":
                        $class = 1;
                        $spells = ["PLATE_MAIL", "MAIL", "BerserkerStance", "DefStance", "THROW_WAR", "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "STAVES", "POLEARMS", "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES", "GUNS", "FIST_WEAPONS", "DAGGERS", "CROSSBOWS", "BOWS", "BLOCK"];
                    break;
                    case "PALADIN":
                        $class = 2;
                        $spells = ["PLATE_MAIL", "MAIL", "Redemption", "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "POLEARMS", "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES", "BLOCK"];
                    break;
                    case "HUNTER":
                        $class = 3;
                        $spells = ["MAIL", "TameBeast", "FeedPet", "DismissPet", "CallPet", "RevivePet", "THROW_WAR", "TWO_H_SWORDS", "TWO_H_AXES", "STAVES", "POLEARMS", "ONE_H_SWORDS", "ONE_H_AXES", "GUNS", "FIST_WEAPONS", "DAGGERS", "CROSSBOWS", "BOWS"];
                    break;
                    case "ROGUE":
                        $class = 4;
                        $spells = ["ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES", "GUNS", "FIST_WEAPONS", "DAGGERS", "CROSSBOWS", "BOWS"];
                    break;
                    case "PRIEST":
                        $class = 5;
                        $spells = ["WANDS", "STAVES", "SHOOT", "ONE_H_MACES", "DAGGERS"];
                    break;
                    case "DEATH_KNIGHT":
                        $class = 6;
                        $spells = ["PLATE_MAIL", "MAIL", "DeathGate", "Runeforging", "TWO_H_SWORDS", "TWO_H_MACES", "TWO_H_AXES", "POLEARMS", "ONE_H_SWORDS", "ONE_H_MACES", "ONE_H_AXES"];
                    break;
                    case "SHAMAN":
                        $class = 7;
                        $spells = ["MAIL", "SearingTotem", "HealingStreamTotem", "StoneskinTotem", "TWO_H_MACES", "TWO_H_AXES", "STAVES", "ONE_H_MACES", "ONE_H_AXES", "FIST_WEAPONS", "DAGGERS", "BLOCK"];
                    break;
                    case "MAGE":
                        $class = 8;
                        $spells = ["PolyMorphPig", "TelePortDalaran", "WANDS", "STAVES", "SHOOT", "ONE_H_SWORDS", "DAGGERS"];
                    break;
                    case "WARLOCK":
                        $class = 9;
                        $spells = ["Imp", "Voidwalker", "Succubus", "Felhunter", "WANDS", "STAVES", "SHOOT", "ONE_H_SWORDS", "DAGGERS"];
                    break;
                    case "DRUID":
                        $class = 11;
                        $spells = ["BearForm", "AquaticForm", "SwiftFlightForm", "TWO_H_MACES", "STAVES", "POLEARMS", "ONE_H_MACES", "FIST_WEAPONS", "DAGGERS"];
                    break;
                    default:
                        $class = 0;
                        $spells = [];
                    break;
                }
            }
           
            $newspells = [];
            foreach ($spells as $spellName)
            {
                $newspells[] = $this->spelldata[$spellName];
            }
            $chardata["newspells"] = $newspells;
            $chardata["main"]["class"] = $class;
            
        
        
            switch ($data["unitinfo"]["race"])
            {
                case "Human":
                    $race = 1;
                break;
                case "Orc":
                    $race = 2;
                break;
                case "Dwarf":
                    $race = 3;
                break;
                case "Night Elf":
                    $race = 4;
                break;
                case "Undead":
                    $race = 5;
                break;
                case "Tauren":
                    $race = 6;
                break;
                case "Gnome":
                    $race = 7;
                break;
                case "Troll":
                    $race = 8;
                break;
                case "Blood Elf":
                    $race = 10;
                break;
                case "Draenei":
                    $race = 11;
                break;
                default:
                    $race = 0;
                break;
            }
            $chardata["main"]["race"] = $race;
        
        
            //genders are in wow lua from 1 to 2, and in azerothcore or similar 0 to 1 , its also possible to use $gender = $gender -1 or smth
            switch ($data["unitinfo"]["gender"])
            {
                case 1:
                    $gender = 0;
                break;
                case 2:
                    $gender = 1;
        
                default:
                    $gender = 0;
                break;
            }
            $chardata["main"]["gender"] = $gender;
            $chardata["main"]["ServerIP"] = $data["globalinfo"]["realmlist"] ?? '';
            $chardata["main"]["ServerRealm"] = $data["globalinfo"]["realm"] ?? '';
        
            $chardata["skills"] = $data["skills"] ?? [];
            $chardata["glyphs"] = $data["glyphs"] ?? [];
        
            if (isset($data["inventory"]) && is_array($data["inventory"])) {
                foreach ($data["inventory"] as $key => $item)
                {
                    // Extract the bag ID and slot from the key
                    list($bagId, $slot) = explode(':', $key);

                    // Check the range of bag IDs and add the item to the respective array
                    if ($bagId >= '0000' && $bagId <= '0135')
                    {
                        // Add the item to the character equipment array
                        $characterequippment[$slot] = ['ID' => $item['I'], 'Count' => $item['C'], 'G1' => $item['G1'], 'G2' => $item['G2'], 'G3' => $item['G3'], 'Q' => $item['Quality'], 'E' => $item['E']

                        ];
                    }
                    else
                    {
                        // Create the bag if it doesn't exist in the new inventory array
                        if (!isset($newInventory[$bagId]))
                        {
                            $newInventory[$bagId] = [];
                        }

                        // Add the item to the bag in the new inventory array
                        $newInventory[$bagId][$slot] = ['ID' => $item['I'], 'Count' => $item['C'], 'G1' => $item['G1'], 'G2' => $item['G2'], 'G3' => $item['G3'], 'Q' => $item['Quality'], 'E' => $item['E']];
                    }
                }
            }
             $mounts = array();
             $pets = array();
            foreach ($data["creature"] as $key => $value)
            {
                if (substr($key, 0, 1) === 'M')
                {
                    $mounts[] = $value;
                }
                elseif (substr($key, 0, 1) === 'C')
                {
                    $pets[] = $value;
                }
            }
            ksort($characterequippment);
            $chardata["Inventory"] = $newInventory;
            $chardata["Equippment"] = $characterequippment;
            $chardata["mounts"] = $mounts;
            $chardata["pets"] = $pets;
           
            
            
            $equipment = ['Head' => isset($chardata["Equippment"][1]["ID"]) ? $chardata["Equippment"][1]["ID"] : '0', 'Neck' => isset($chardata["Equippment"][2]["ID"]) ? $chardata["Equippment"][2]["ID"] : '0', 'Shoulder' => isset($chardata["Equippment"][3]["ID"]) ? $chardata["Equippment"][3]["ID"] : '0', 'Shirt' => isset($chardata["Equippment"][4]["ID"]) ? $chardata["Equippment"][4]["ID"] : '0', 'Chest' => isset($chardata["Equippment"][5]["ID"]) ? $chardata["Equippment"][5]["ID"] : '0', 'Waist' => isset($chardata["Equippment"][6]["ID"]) ? $chardata["Equippment"][6]["ID"] : '0', 'Legs' => isset($chardata["Equippment"][7]["ID"]) ? $chardata["Equippment"][7]["ID"] : '0', 'Feet' => isset($chardata["Equippment"][8]["ID"]) ? $chardata["Equippment"][8]["ID"] : '0', 'Wrist' => isset($chardata["Equippment"][9]["ID"]) ? $chardata["Equippment"][9]["ID"] : '0', 'Hands' => isset($chardata["Equippment"][10]["ID"]) ? $chardata["Equippment"][10]["ID"] : '0', 'Finger1' => isset($chardata["Equippment"][11]["ID"]) ? $chardata["Equippment"][11]["ID"] : '0', 'Finger2' => isset($chardata["Equippment"][12]["ID"]) ? $chardata["Equippment"][12]["ID"] : '0', 'Trinket1' => isset($chardata["Equippment"][13]["ID"]) ? $chardata["Equippment"][13]["ID"] : '0', 'Trinket2' => isset($chardata["Equippment"][14]["ID"]) ? $chardata["Equippment"][14]["ID"] : '0', 'Back' => isset($chardata["Equippment"][15]["ID"]) ? $chardata["Equippment"][15]["ID"] : '0', 'Main hand' => isset($chardata["Equippment"][16]["ID"]) ? $chardata["Equippment"][16]["ID"] : '0', 'Off hand' => isset($chardata["Equippment"][17]["ID"]) ? $chardata["Equippment"][17]["ID"] : '0', 'Ranged' => isset($chardata["Equippment"][18]["ID"]) ? $chardata["Equippment"][18]["ID"] : '0', 'Tabard' => isset($chardata["Equippment"][19]["ID"]) ? $chardata["Equippment"][19]["ID"] : '0', 'Bag1' => '0', 'Bag2' => '0', 'Bag3' => '0', 'Bag4' => '0', ];
        
            $equipmentCache = "";
            
            //generate the equipment cache so char is not naked on first sight on login screen not neccessary but work great
            foreach ($equipment as $item)
            {
                $equipmentCache .= $item . ' 0 '; // Adds Item ID and appends '0' for Appearance Mod ID and Item Enchant Aura ID.
                
            }
        
            $chardata["equipmentCache"] = trim($equipmentCache); // Removes trailing space
            
            $chardata["achievements"] = $data["achiev"] ?? [];
           
            $currency = array();

            if (isset($data["currency"]) && is_array($data["currency"])) {
                foreach ($data["currency"] as $item)
                {
                    if ($item['I'] != 0 && $item['C'] != 0)
                    {
                        $currency[] = ['I' => $item['I'], 'C' => $item['C'], ];
                    }
                }
            }

            $chardata["currency"] = $currency;
        
            $skills = array();
            $professions = array(
                "main" => array() ,
                "secondary" => array()
            );
        
            $profmain = array(
                2259 => "Alchemy",
                2018 => "Blacksmithing",
                7411 => "Enchanting",
                4036 => "Engineering",
                2366 => "Herb Gathering",
                45357 => "Inscription",
                25229 => "Jewelcrafting",
                2108 => "Leatherworking",
                8613 => "Skinning",
                3908 => "Tailoring",
            );
        
            $profsec = array(
                45542 => "First Aid",
                65293 => "Fishing",
                51296 => "Cooking",
            );
        
            $other = array(
                // English
                "Fishing" => 51294,
                "First Aid" => 7924,
                "Riding" => 34090,
                // German
                "Angeln" => 51294,
                "Erste Hilfe" => 7924,
                "Reiten" => 34090,
                // French
                "Pêche" => 51294,
                "Secourisme" => 7924,
                "Monte" => 34090,
                // Spanish
                "Pesca" => 51294,
                "Primeros auxilios" => 7924,
                "Equitación" => 34090,
                // Russian
                "Рыбная ловля" => 51294,
                "Первая помощь" => 7924,
                "Верховая езда" => 34090,
            );
        
            foreach ($data["skills"] as $skill)
            {
                if (isset($skill["S"]))
                {
                    // Check if the skill is in profmain or profsec and add them to professions
                    if (isset($profmain[$skill['S']]))
                    {
                        $professions['main'][] = array(
                            'Current' => $skill['C'],
                            'Max' => $skill['M'],
                            'Link' => '<a  data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $skill['S'] . '"></a>'
                        );
                    }
                    elseif (isset($profsec[$skill['S']]))
                    {
                        $professions['secondary'][] = array(
                            'Current' => $skill['C'],
                            'Max' => $skill['M'],
                            'Link' => '<a data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $skill['S'] . '"></a>'
                        );
        
                    }
                }
                else
                {
                    if (isset($other[$skill['N']]))
                    {
                        $professions['other'][] = array(
                            'Name' => $skill['N'],
                            'Current' => $skill['C'],
                            'Max' => $skill['M'],
                            'Link' => '<a data-wh-rename-link="true" data-wh-icon-size="tiny" href="https://www.wowhead.com/wotlk/de/spell=' . $other[$skill['N']] . '"></a>'
                        );
                    }
                }
        
            }
            
            $talentPoints = array(
                "highesttab" => "",
                1 => 0,
                2 => 0,
                3 => 0
            );
            $highestTab = 0;
            $highestPoints = 0;
            
           
            
            foreach ($data["talents"][0] as $talentTab)
            {
                foreach ($talentTab['talents'] as $talent)
                {
                    $talentPoints[$talent['tab']] += $talent['currentRank'];
                    // check if current tab has more points than highest so far
                    if ($talentPoints[$talent['tab']] > $highestPoints)
                    {
                        $highestPoints = $talentPoints[$talent['tab']];
                        $highestTab = $talent['tab'];
                        $talentPoints['highesttab'] = $talentTab['icon'];
                        $talentPoints['highestTabName'] = $talentTab['name'];
                    }
                }
            }
            $talentPointsTwo = array(
                "highestTabTwo" => "",
                1 => 0,
                2 => 0,
                3 => 0
            );
            $highestTabTwo = 0;
            $highestPointsTwo = 0;
        
            if (isset($data["talents"][1]) && is_array($data["talents"][1])) {
                foreach ($data["talents"][1] as $talentTab)
                {
                    if (isset($talentTab['talents']) && is_array($talentTab['talents'])) {
                        foreach ($talentTab['talents'] as $talent)
                        {
                            $talentPointsTwo[$talent['tab']] += $talent['currentRank'];
                            // check if current tab has more points than highest so far
                            if ($talentPointsTwo[$talent['tab']] > $highestPointsTwo)
                            {
                                $highestPointsTwo = $talentPointsTwo[$talent['tab']];
                                $highestTabTwo = $talent['tab'];
                                $talentPointsTwo['highestTabTwo'] = $talentTab['icon'];
                                $talentPointsTwo['highestTabName'] = $talentTab['name'];
                            }
                        }
                    }
                }
            }
        
            $newmoney = array();
            $money = $data["unitinfo"]["money"];
            $gold = floor($money / 10000);
            $remainder = $money % 10000;
        
            $silver = floor($remainder / 100);
            $copper = $remainder % 100;
        
            $newmoney = ['gold' => $gold, 'silver' => $silver, 'copper' => $copper];
        
            $chardata["main"]["money"] = $newmoney ?? ['gold' => 0, 'silver' => 0, 'copper' => 0];
        
            $chardata["talenttree"][1] = $talentPoints;
            $chardata["talenttree"][2] = $talentPointsTwo;
        
            $ranks = '';
            $specmask = array();
            if (isset($data['talents'][0]) && is_array($data['talents'][0])) {
                foreach ($data['talents'][0] as $index => $talent)
                {
                    if (is_numeric($index))
                    {
                        if (is_array($talent)) {
                            foreach ($talent as $talentfurther)
                            {
                                if (is_array($talentfurther))
                                {
                                    foreach ($talentfurther as $talentspell)
                                    {
                                        if (isset($talentspell['currentRank']))
                                        {
                                            $ranks .= $talentspell['currentRank'];
                                            if($talentspell['currentRank'] >= 1){
                                            $talendID = $talentspell['talentID'];
                                            $actualspell = $this->talentspells[$talendID]["ranks"][$talentspell['currentRank']];
                                            $specmask[0][] = $actualspell;

                                            }

                                        }
                                    }
                                }
                            }
                            $ranks .= "-";
                        }
                    }
                }
                $ranks = rtrim($ranks, '-');
            }
        
            $ranks2 = '';
            foreach ($data['talents'][1] as $index => $talent)
            {
                if (is_numeric($index))
                {
                    foreach ($talent as $talentfurther)
                    {
                        if (is_array($talentfurther))
                        {
                            foreach ($talentfurther as $talentspell)
                            {
                                if (isset($talentspell['currentRank']))
                                {
                                    $ranks2 .= $talentspell['currentRank'];
                                     if($talentspell['currentRank'] >= 1){
                                    $talendID = $talentspell['talentID'];
                                   $actualspell = $this->talentspells[$talendID]["ranks"][$talentspell['currentRank']];
                                    $specmask[1][] = $actualspell;
                                    }
                                }
                            }
                        }
                    }
                    $ranks2 .= "-";
                }
            }
            $ranks2 = rtrim($ranks2, '-');
        
            $chardata["specmask"] = $specmask ?? [];
        
            $chardata["skills"] = $skills;
            $chardata["professions"] = $professions;
        
            $chardata["talenttree"][1]["link"] = $ranks ?? '';
            $chardata["talenttree"][2]["link"] = $ranks2 ?? '';
        
        
            return $chardata;
        }

public function genCharDump($chardata) {
    
$array = [
'guid' => '999',
 'account' => '888',
 'name' => $chardata["main"]["name"], 
 'race' => $chardata["main"]["race"],
 'class' => $chardata["main"]["class"],
 'gender' => $chardata["main"]["gender"],
 'level' => $chardata["main"]["level"], 
 'xp' => '0',
 'money' => $chardata["main"]["money"]["gold"] * 100 * 100, // silver and copper left out
 'skin' => '0', 
 'face' => '0',
 'hairStyle' => '0',
 'hairColor' => '9', 
 'facialStyle' => '4',
 'bankSlots' => '7', 
 'restState' => '2',
 'playerFlags' => '0', 
 'position_x' => '5877.93', // dalaran
 'position_y' => '641.871', // dalaran
 'position_z' => '646.24', // dalaran
 'map' => '571', // dalaran
 'instance_id' => '0',
 'instance_mode_mask' => '0', 
 'orientation' => '0', 
 'taximask' => ' ',
 'online' => '1',
 'cinematic' => '1', 
 'totaltime' => $chardata["main"]["playtime"],
 'leveltime' => $chardata["main"]["playtime"],
 'logout_time' => '1685703040',
 'is_logout_resting' => '0',
 'rest_bonus' => '0.0289333',
 'resettalents_cost' => '0',
 'resettalents_time' => '0',
 'trans_x' => '0', 
 'trans_y' => '0', 
 'trans_z' => '0',
 'trans_o' => '0', 
 'transguid' => '0',
 'extra_flags' => '0', 
 'stable_slots' => '0',
 'at_login' => '0', 
 'zone' => '12',
 'death_expire_time' => '0', 
 'taxi_path' => 'NULL',
 'arenaPoints' => $chardata["main"]["arenapoints"],
 'totalHonorPoints' => $chardata["main"]["honor"],
 'todayHonorPoints' => '0', 
 'yesterdayHonorPoints' => '0',
 'totalKills' => $chardata["main"]["kills"],
 'todayKills' => '0',
 'yesterdayKills' => '0', 
 'chosenTitle' => '0', 
 'knownCurrencies' => '0', 
 'watchedFaction' => '0', 
 'drunk' => '100', 
 'health' => $chardata["main"]["stats"]["health"],
 'power1' => '0',
 'power2' => '0', 
 'power3' => '100', 
 'power4' => '0',
 'power5' => '0', 
 'power6' => '0', 
 'power7' => '8',
 'latency' => '1',
 'talentGroupsCount' => '2',
 'activeTalentGroup' => '0', 
 'exploredZones' => '0',
 'equipmentCache' => $chardata["equipmentCache"],
 'ammoId' => '0',
 'knownTitles' => '0',
 'actionBars' => '15', 
 'grantableLevels' => '0',
 'order' => '0',
 'creation_date' => date('Y-m-d H:i:s'), 
 'deleteInfos_Account' => 'NULL', 
 'deleteInfos_Name' => 'NULL',
 'deleteDate' => 'NULL',
 'innTriggerId' => '0'
 ];

$keys = '`' . implode('`, `', array_keys($array)) . '`';
$values = "'" . implode("', '", array_values($array)) . "'";

$query = "INSERT INTO `characters` ($keys) VALUES ($values);";

// Debug output removed for production
$itemnumber = 1337;

//every item needs it own iteminstanceid, this will be overwritten by the worldserver itself, but needs to be diffrent everytime
// sockets can be applied but then socketbonus will not be refreshed by the worldserver instead we need a workaround like just add them to te inventory
$bagOne = "";
$bagTwo = "";
$bagThree = "";
$bagfour = "";
$bagBankOne = "";
$bagBankTwo = "";
$bagBankThree = "";
$bagBankFour = "";
$bagBankFive = "";
$bagBankSix = "";
$bagBankSeven = "";

krsort($chardata["Equippment"]);

//items in bags are insereted in its iteminstanceid of the bag, except for predefined bags, like the fixed bag from char which is slot 0 or bank fixed bag which is somewhere 60 
// here the slots are stored to use them by filling the inventory
foreach ($chardata["Equippment"] as $slot => $item)
{
    $newslot = $slot - 1;
    switch ($newslot)
    {
        case 19:
            $bagOne = $itemnumber;

        break;
        case 20:
            $bagTwo = $itemnumber;
        break;
        case 21:
            $bagThree = $itemnumber;
        break;
        case 22:
            $bagfour = $itemnumber;
        break;
        case 67:
            $bagBankOne = $itemnumber;
        break;
        case 68:
            $bagBankTwo = $itemnumber;
        break;
        case 69:
            $bagBankThree = $itemnumber;
        break;
        case 70:
            $bagBankFour = $itemnumber;
        break;
        case 71:
            $bagBankFive = $itemnumber;
        break;
        case 72:
            $bagBankSix = $itemnumber;
        break;
        case 73:
            $bagBankSeven = $itemnumber;
        break;

    }
    if($mysqloutput) {
    //echo "Slot: " . $slot ."item : " . $item["ID"] . "<br>";

    echo "INSERT INTO `character_inventory` (`guid`, `bag`, `slot`, `item`) VALUES ('999', '0', '{$newslot}', '{$itemnumber}'); <br>";
   echo "INSERT INTO `item_instance` (`guid`, `itemEntry`, `owner_guid`, `creatorGuid`, `giftCreatorGuid`, `count`, `duration`, `charges`, `flags`, `enchantments`, `randomPropertyId`, `durability`, `playedTime`, `text`) VALUES ('{$itemnumber}', '{$item["ID"]}', '999', '0', '0', '1', '0', '0 0 0 0 0 ', '0', '{$item["E"]} 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 ', '0', '35', '0', 'NULL'); <br>";
    }
    $itemnumber++;

}
ksort($chardata["Inventory"]);


//filling the inventory
foreach ($chardata["Inventory"] as $bag => $itemarray)
{
    $bagnumber = $bag - 1000;
    ksort($itemarray);
    foreach ($itemarray as $slot => $item)
    {
        $newslot = $slot - 1;

        switch ($bagnumber)
        {
            case 0:
                $bag = 0;
                $newslot = $slot + 22;
            break;
            case 1:
                $bag = $bagOne;
            break;
            case 2:
                $bag = $bagTwo;
            break;
            case 3:
                $bag = $bagThree;
            break;
            case 4:
                $bag = $bagfour;
            break;
            case 5:
                $bag = $bagBankOne;
            break;
            case 6:
                $bag = $bagBankTwo;
            break;
            case 7:
                $bag = $bagBankThree;
            break;
            case 8:
                $bag = $bagBankFour;
            break;
            case 9:
                $bag = $bagBankFive;
            break;
            case 10:
                $bag = $bagBankSix;
            break;
            case 11:
                $bag = $bagBankSeven;
            break;

            default:
                $bag = $bagnumber;
            break;
        }
    // Debug output removed for production
        $itemnumber++;

    }
}


//normal achievements can be extracted easy 
ksort($chardata["achievements"]);
foreach ($chardata["achievements"] as $achievement)
{
    if ($achievement['completed'] == 1)
    {

        $time = $achievement["time"];
        $id = $achievement["id"];
         if($mysqloutput) {
             
       echo "INSERT INTO `character_achievement` (`guid`, `achievement`, `date`) VALUES ('999', '{$id}', '{$time}');<br>";
         }
    }

}

// weapon skills and profession skills are extracted , 
foreach ($chardata["newspells"] as $spellId)
{
    // Debug output removed for production
}

foreach ($original["skills"] as $skill)
{
    if (isset($skill["Skill"]))
    {
        $skillid = $skill["Skill"];
        $current = $skill["C"];
        $max = $skill["M"];
         if($mysqloutput) {
            echo "INSERT INTO `character_skills` (`guid`, `skill`, `value`, `max`) VALUES ('999', '{$skillid}', '{$current}', '{$max}');<br>";
         }
        
    }
    }


// statistic data is disabled as so much data is only serverside and addon is not finished yet there
$insertArray = array();
if(isset($original["statistic"] )){
foreach ($original["statistic"] as $value) {
    if (isset($value["criteria"])) {
        foreach ($value["criteria"] as $criteria) {
            if (isset($criteria["criteriaID"]) && isset($criteria["quantity"]) && isset($criteria["criteriaString"])) {
                $id = $criteria["criteriaID"];
                $statisticquantity = $criteria["quantity"];
                $time = time();

                if ($statisticquantity != 0) {
                    if (isset($insertArray[$id])) {
                        if ($statisticquantity < $insertArray[$id]["quantity"]) {
                            $insertArray[$id]["quantity"] = $statisticquantity;
                            $insertArray[$id]["time"] = $time;
                        }
                    } else {
                        $insertArray[$id] = array(
                            "quantity" => $statisticquantity,
                            "time" => $time
                        );
                    }
                }
            }
        }
    }
}

foreach ($insertArray as $id => $data) {
 //   echo "INSERT INTO `character_achievement_progress` (`guid`, `criteria`, `counter`, `date`) VALUES ('999', '{$id}', '{$data["quantity"]}', '{$data["time"]}');<br>";
}
}







foreach ($original["reputation"] as $reputation) {
    $name = $reputation["N"];
    $value = $reputation["V"];

    if (abs($value) > 0) {  // do not need to add reputation fields which hasn't received any updates
        $searchID = null;

        foreach ($factions as $key => $faction) {  // the wow lua api can't return the faction ids, so we have a list of factions within all locales to search through, to get the desired faction id
            $searchLocale = $chardata["main"]["locale"];
            $searchName = $name;
            
            if (isset($faction[$searchLocale]['Name']) && $faction[$searchLocale]['Name'] === $searchName) {
                $result = $key;
               
            } else {
                
             $result = null;
            }             
        

        if ($result !== null) {
            if ($mysqloutput) {
               ;
                echo "INSERT INTO `character_reputation` (`guid`, `faction`, `standing`, `flags`) VALUES ('999', '{$result}', '{$value}', '1');<br>";
            }
        } else {
            // echo "Faction ID not found for '$name'"; // only for debugging
        }
    }
}
}


//echo "INSERT INTO `character_talent` (`guid`, `spell`, `specMask`) VALUES ('999', '{$spellid}', '1');<br>";
// generate the correct specmask

//spellmask 1 is first talenttree, spellmask 2 is second talentree, spellmask 3 is both
$spellmaskcount = array();

if (count($chardata["specmask"][0]) >= 1) {
    foreach ($chardata["specmask"][0] as $spell) {
        
            $spellmaskcount[$spell] = 1;
        
    }
}

if (count($chardata["specmask"][1]) >= 1) {
    foreach ($chardata["specmask"][1] as $spell) {
     
            if (isset($spellmaskcount[$spell])) {
                $spellmaskcount[$spell] += 2;
            } else {
                $spellmaskcount[$spell] = 2;
            }
        
    }
}
ksort($spellmaskcount);
foreach($spellmaskcount as $spell => $spellmask){
    if($mysqloutput){
    echo "INSERT INTO `character_talent` (`guid`, `spell`, `specMask`) VALUES ('999', '{$spell}', '{$spellmask}');<br>";
    }
    
}

//adding glyphs, damn these are not item or spellids these are explicit glyph ids from dbc
foreach($chardata["glyphs"] as $key => $value){
    
    $glyphs = implode(', ', $value);
    if($mysqloutput){
    echo "INSERT INTO `character_glyphs` (`guid`, `talentGroup`, `glyph1`, `glyph2`, `glyph3`, `glyph4`, `glyph5`, `glyph6`) VALUES ('999', {$key}, {$glyphs});<br>";
    }
}
}

}

