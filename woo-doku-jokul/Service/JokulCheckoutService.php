<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulCheckoutService {

    public function generated($config, $params)
    {
        $header = array();
        $this->jokulUtils = new JokulUtils();

        $requestId = $this->jokulUtils->guidv4();
        $targetPath= "/checkout/v1/payment";
        $dateTime = gmdate("Y-m-d H:i:s");
        $dateTime = date(DATE_ISO8601, strtotime($dateTime));
        $dateTimeFinal = substr($dateTime,0,19)."Z";

        $data = $params['sac_check'] === 'yes' ? array(
            "order" => $params['auto_redirect'] === 'true' ? array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url" => $params['callback_url'],
                "currency" => "IDR",
                "auto_redirect" => true,
                "disable_retry_payment" => true
            ): array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url" => $params['callback_url'],
                "currency" => "IDR"
            ),
            "payment" => array(
                "payment_due_date" => $params['expiryTime']
            ),
            "customer" => array(
                "id" => $params['customerId'],
                "name" => trim($params['customerName']),
                "email" => $params['customerEmail'],
                "phone" => $params['phone'],
                "country" => $params['country'],
                "postcode" => $params['postcode'],
                "state" => $params['state'],
                "city" => $params['city'],
                "address" => $params['address']
            ),
            "shipping_address" => array(
                "first_name" => $params['first_name_shipping'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $params['phone'],
                "country_code" => "ID"
            ),
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.9",
                    "cms_version" => $params['woo_version']
                ),
                "account" => array(
                    "id" =>  $params['sac_textbox']
                ),
                "method" => "Jokul Checkout",
                "doku_wallet_notify_url" => ""
            )
        ) :  array(
            "order" => $params['auto_redirect'] === 'true' ? array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url" => $params['callback_url'],
                "currency" => "IDR",
                "auto_redirect" => true,
                "disable_retry_payment" => true
            ): array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url" => $params['callback_url'],
                "currency" => "IDR"
            ),
            "payment" => array(
                "payment_due_date" => $params['expiryTime']
            ),
            "customer" => array(
                "id" => $params['customerId'],
                "name" => trim($params['customerName']),
                "email" => $params['customerEmail'],
                "phone" => $params['phone'],
                "country" => $params['country'],
                "postcode" => $params['postcode'],
                "state" => $params['state'],
                "city" => $params['city'],
                "address" => $params['address']
            ),
            "shipping_address" => array(
                "first_name" => $params['first_name_shipping'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $params['phone'],
                "country_code" => "ID"
            ),
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.9",
                    "cms_version" => $params['woo_version']
                ),
                "method" => "Jokul Checkout",
                "doku_wallet_notify_url" => ""
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

        $this->jokulUtils->doku_log($this, 'Jokul Checkout REQUEST : ' . json_encode($data), $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout REQUEST URL : ' . $url, $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout RESPONSE : ' . json_encode($responseJson, JSON_PRETTY_PRINT), $params['invoiceNumber']);

        if (is_string($responseJson)) {
            return json_decode($responseJson, true);
        } else {
            print_r($responseJson);
        }
    }
}

?>
