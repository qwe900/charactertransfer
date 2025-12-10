<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wowhead / Charactertransfer Konfiguration
 */

// Default-Fallback-Locale
$config['wowhead_default_locale'] = 'en';

/**
 * Mapping Userlanguage (als ausgeschriebenes Wort)
 * → Wowhead-Locale
 *
 * $userLang wird z.B. "english", "german", "spanish" sein.
 */
$config['wowhead_locale_map'] = [
    'english'   => 'en',
    'german'    => 'de',
    'spanish'   => 'es',
    'french'    => 'fr',
    'russian'   => 'ru',
    'italian'   => 'it',
    'portuguese'=> 'pt',
    'korean'    => 'ko',
    'chinese'   => 'zh',

    // optional: Synonyme
    'espanol'   => 'es',
    'deutsch'   => 'de',
];

// Rest wie gehabt:
$config['wowhead_base_url']   = 'https://www.wowhead.com';
$config['wowhead_timeout']    = 10;
$config['wowhead_user_agent'] = 'FusionGen Character Transfer/1.0';
$config['charactertransfer_cache_ttl'] = 3600;
