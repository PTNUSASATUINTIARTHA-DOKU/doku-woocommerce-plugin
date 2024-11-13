<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulCheckStatusService {

    public function generated($config, $params)
    {
        $header = array();
        $this->jokulUtils = new JokulUtils();

        $requestId = $this->jokulUtils->guidv4();
        $targetPath = "/orders/v1/status/" . $params['invoiceNumber'];
        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $dateTimeFinal = substr($dateTime, 0, 19) . "Z";

        $this->jokulConfig = new JokulConfig();
        $valueEnv = $config['environment'] === 'true' ? true : false;
        $getUrl = $this->jokulConfig->getBaseUrl($valueEnv);
        $url = $getUrl . $targetPath;

        $header['Client-Id'] = $config['client_id'];
        $header['Request-Id'] = $requestId;
        $header['Request-Timestamp'] = $dateTimeFinal;
        $header['Request-Target'] = $targetPath;

        $signature = $this->jokulUtils->generateSignatureCheckStatus($header, $config['shared_key']);

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

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->jokulUtils->doku_log($this, 'Jokul Check Status ERROR: ' . $error_message, $params['invoiceNumber']);
            return null;
        }

        $responseBody = wp_remote_retrieve_body($response);

        $this->jokulUtils->doku_log($this, 'Jokul Check Status REQUEST URL: ' . $url, $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Check Status RESPONSE: ' . json_encode($responseBody, JSON_PRETTY_PRINT), $params['invoiceNumber']);

        return json_decode($responseBody, true);
    }
}

?>
