<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulCreditCardService {

    public function generated($config, $params)
    {
        $header = array();
        $this->jokulUtils = new JokulUtils();

        $requestId = $this->jokulUtils->guidv4();
        $targetPath= "/credit-card/v1/payment-page";
        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $dateTimeFinal = substr($dateTime,0,19)."Z";

        $data = $params['sac_check'] === 'yes' ? array(
            "customer" => array(
                "id" => $params['customerId'],
                "name" => trim($params['customerName']),
                "email" => $params['customerEmail'],
                "phone" => $params['phone'],
                "country" => $params['country'],
                "address" => $params['address']
            ),
            "order" => array(
                "invoice_number" => $params['invoiceNumber'],
                "amount" => $params['amount'],
                "failed_url" => $params['urlFail'],
                "callback_url" => $params['urlSuccess'],
                "auto_redirect" => true
            ),
            "override_configuration" => array(
                "themes" => array(
                    "language" => $params['language'] != "" ? $params['language'] : "" ,
                    "background_color" => $params['backgroundColor'] != "" ? $params['backgroundColor'] : "" ,
                    "font_color" => $params['fontColor'] != "" ? $params['fontColor'] : "" ,
                    "button_background_color" => $params['buttonBackgroundColor'] != "" ? $params['buttonBackgroundColor'] : "" ,
                    "button_font_color" => $params['buttonFontColor'] != "" ? $params['buttonFontColor'] : "" ,
                )
            ),
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.9",
                    "cms_version" => $params['woo_version']
                ),
                "account" => array(
                    "id" => $params['sac_textbox']
                ),
                "method" => "Jokul Direct"
            )
        ) : array(
            "customer" => array(
                "id" => $params['customerId'],
                "name" => trim($params['customerName']),
                "email" => $params['customerEmail'],
                "phone" => $params['phone'],
                "country" => $params['country'],
                "address" => $params['address']
            ),
            "order" => array(
                "invoice_number" => $params['invoiceNumber'],
                "amount" => $params['amount'],
                "failed_url" => $params['urlFail'],
                "callback_url" => $params['urlSuccess'],
                "auto_redirect" => true
            ),
            "override_configuration" => array(
                "themes" => array(
                    "language" => $params['language'] != "" ? $params['language'] : "" ,
                    "background_color" => $params['backgroundColor'] != "" ? $params['backgroundColor'] : "" ,
                    "font_color" => $params['fontColor'] != "" ? $params['fontColor'] : "" ,
                    "button_background_color" => $params['buttonBackgroundColor'] != "" ? $params['buttonBackgroundColor'] : "" ,
                    "button_font_color" => $params['buttonFontColor'] != "" ? $params['buttonFontColor'] : "" ,
                )
            ),
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.9",
                    "cms_version" => $params['woo_version']
                ),
                "method" => "Jokul Direct"
            )
        );

        $this->jokulConfig = new JokulConfig();
        $valueEnv = $config['environment'] === 'true'? true: false;
        $getUrl = $this->jokulConfig -> getBaseUrl($valueEnv);
        $url = $getUrl.$targetPath;

        $header['Client-Id'] = $config['client_id'];
        $header['Request-Id'] = $requestId;
        $header['Request-Timestamp'] = $dateTimeFinal;
        $header['Request-Target'] = $targetPath;

        $signature = $this->jokulUtils->generateSignature($header, json_encode($data), $config['shared_key']);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Signature:'.$signature,
            'Request-Id:'.$requestId,
            'Client-Id:'.$config['client_id'],
            'Request-Timestamp:'.$dateTimeFinal,
        
        ));

        $responseJson = curl_exec($ch);

        curl_close($ch);
        
        $this->jokulUtils->doku_log($this, 'DOKU CC REQUEST : ' . json_encode($data), $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'DOKU CC REQUEST URL : ' . $url, $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'DOKU CC RESPONSE : ' . json_encode($responseJson, JSON_PRETTY_PRINT), $params['invoiceNumber']);

        if (is_string($responseJson)) {
            return json_decode($responseJson, true);
        } else {
            print_r($responseJson);
        }
    }
}

?>
