<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuCheckStatusService {
    private $dokuUtils;
    private $dokuConfig;
    private $lastHttpCode = 0;
    private $lastError = null;

    public function getLastHttpCode()
    {
        return (int) $this->lastHttpCode;
    }

    public function getLastError()
    {
        return $this->lastError !== null ? (string) $this->lastError : null;
    }

    public function generated($config, $params, $options = array())
    {
        // Reset state for each call
        $this->lastHttpCode = 0;
        $this->lastError = null;

        // Guard clause: validate params and config
        $hasValidInvoice = is_array($params)
            && isset($params['invoiceNumber'])
            && is_scalar($params['invoiceNumber'])
            && trim((string) $params['invoiceNumber']) !== '';
        $hasValidClientId = is_array($config)
            && isset($config['client_id'])
            && is_scalar($config['client_id'])
            && trim((string) $config['client_id']) !== '';

        if (!$hasValidInvoice || !$hasValidClientId) {
            $this->lastError = 'Invalid params or config';
            $this->lastHttpCode = 0;
            $invoiceLog = $hasValidInvoice ? trim((string) $params['invoiceNumber']) : 'unknown';
            $this->dokuUtils = new DokuUtils();
            $this->dokuUtils->doku_log($this, 'Jokul Check Status ERROR: Invalid params or config passed to generated()', $invoiceLog);
            return null;
        }

        $invoiceNumberStr = trim((string) $params['invoiceNumber']);

        // Build request headers and signature
        $header = array();
        $this->dokuUtils = new DokuUtils();

        $requestId = $this->dokuUtils->guidv4();
        $targetPath = "/orders/v1/status/" . $invoiceNumberStr;
        $dateTimeFinal = gmdate("Y-m-d\TH:i:s\Z");

        $this->dokuConfig = new DokuConfig();
        $valueEnv = isset($config['environment']) && $config['environment'] === 'true' ? true : false;
        $getUrl = $this->dokuConfig->getBaseUrl($valueEnv);
        $url = $getUrl . $targetPath;

        $header['Client-Id'] = $config['client_id'];
        $header['Request-Id'] = $requestId;
        $header['Request-Timestamp'] = $dateTimeFinal;
        $header['Request-Target'] = $targetPath;

        $signature = $this->dokuUtils->generateSignatureCheckStatus($header, $config['shared_key']);

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Signature' => $signature,
                'Request-Id' => $requestId,
                'Client-Id' => $config['client_id'],
                'Request-Timestamp' => $dateTimeFinal,
                'Request-Target' => $targetPath,
            ),
            'timeout' => 45
        );

        // Disable redirects if requested (e.g. SSRF defense)
        if (is_array($options) && array_key_exists('redirection', $options)) {
            $args['redirection'] = (int) $options['redirection'];
        }

        // Execute remote GET request
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->lastError = $response->get_error_message();
            $this->lastHttpCode = 0;
            $this->dokuUtils->doku_log($this, 'Jokul Check Status ERROR: ' . $this->lastError, $invoiceNumberStr);
            return null;
        }

        // Parse and record response
        $this->lastHttpCode = (int) wp_remote_retrieve_response_code($response);
        $this->lastError = null;

        $http_code = $this->lastHttpCode;
        $responseBody = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($responseBody, true);

        $this->dokuUtils->doku_log($this, 'Jokul Check Status REQUEST URL: ' . $url . ' (HTTP ' . $http_code . ')', $invoiceNumberStr);
        $this->dokuUtils->doku_log_masked_payload($this, 'Jokul Check Status RESPONSE: ', $decoded_body, $invoiceNumberStr);

        return $decoded_body;
    }
}
