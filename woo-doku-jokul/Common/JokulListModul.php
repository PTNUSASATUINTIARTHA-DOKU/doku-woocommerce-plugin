<?php

// Check PHP version.
if (version_compare(PHP_VERSION, '5.2.1', '<')) {
    throw new Exception('PHP version >= 5.2.1 required');
}

// Check PHP Curl & json decode capabilities.
if (!function_exists('curl_init') || !function_exists('curl_exec')) {
  throw new Exception('Jokul needs the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
  throw new Exception('Jokul needs the JSON PHP extension.');
}

// Moduls
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulMainModul.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulDokuVaModul.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulBsmVaModul.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulMandiriVaModul.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulBcaVaModul.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Modul/JokulPermataVaModul.php');

//API End Point
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Service/JokulNotificationService.php');

?>
