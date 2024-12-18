<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class DokuConfig {

  const SANDBOX_BASE_URL    = 'https://api-sandbox.doku.com';
  const PRODUCTION_BASE_URL = 'https://api.doku.com';

  /**
   * @return string Doku API URL, depends on $state
   */
  public function getBaseUrl($state)
  {
    return $state ? DokuConfig::PRODUCTION_BASE_URL : DokuConfig::SANDBOX_BASE_URL;
  }
}

