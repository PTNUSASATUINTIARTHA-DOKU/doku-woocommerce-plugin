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

// Modules
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulMainModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulDokuVaModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulBsmVaModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulMandiriVaModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulBcaVaModule.php');
// require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulPermataVaModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulAlfaO2OModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulCreditCardModule.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Module/JokulBriVaModule.php');


//API End Point
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Service/JokulNotificationService.php');

?>
