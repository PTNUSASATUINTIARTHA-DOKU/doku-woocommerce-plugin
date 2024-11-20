<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulCheckoutService {

    public function generated($config, $params)
    {
        $header = array();
        $this->jokulUtils = new JokulUtils();

        $requestId = $this->jokulUtils->guidv4();
        $formattedPhoneNumber = $this->jokulUtils->formatPhoneNumber($params['phone']);
        $targetPath= "/checkout/v1/payment";
        $dateTime = gmdate(DATE_ISO8601);
        $dateTimeFinal = substr($dateTime, 0, 19) . "Z";

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
                "name" => $params['first_name'],
                "last_name" => $params['last_name'],
                "email" => $params['customerEmail'],
                "phone" => $formattedPhoneNumber,
                "country" => $params['country'],
                "postcode" => $params['postcode'],
                "state" => $params['state'],
                "city" => $params['city'],
                "address" => $params['address']
            ),
            "shipping_address" => array(
                "first_name" => $params['first_name_shipping'],
                "last_name" => $params['last_name'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $formattedPhoneNumber,
                "country_code" => "IDN"
            ),
            "billing_address" => array(
                "first_name" => $params['first_name'],
                "last_name" => $params['last_name'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $formattedPhoneNumber,
                "country_code" => "IDN"
            ),                        
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.14",
                    "cms_version" => $params['woo_version']
                ),
                "account" => array(
                    "id" =>  $params['sac_textbox']
                ),
                "method" => "Jokul Checkout",
                "doku_wallet_notify_url" => get_site_url() .'/wp-json/doku/notification'
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
                "name" => $params['first_name'],
                "last_name" => $params['last_name'],
                "email" => $params['customerEmail'],
                "phone" => $formattedPhoneNumber,
                "country" => $params['country'],
                "postcode" => $params['postcode'],
                "state" => $params['state'],
                "city" => $params['city'],
                "address" => $params['address']
            ),
            "shipping_address" => array(
                "first_name" => $params['first_name_shipping'],
                "last_name" => $params['last_name'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $formattedPhoneNumber,
                "country_code" => "IDN"
            ),
            "billing_address" => array(
                "first_name" => $params['first_name'],
                "last_name" => $params['last_name'],
                "address" => trim($params['address_shipping']),
                "city" => $params['city_shipping'],
                "postal_code" => $params['postal_code_shipping'],
                "phone" => $formattedPhoneNumber,
                "country_code" => "IDN"
            ), 
            "additional_info" => array (
                "integration" => array (
                    "name" => "woocommerce-plugin",
                    "version" => "1.3.14",
                    "cms_version" => $params['woo_version']
                ),
                "method" => "Jokul Checkout",
                "doku_wallet_notify_url" => get_site_url() .'/wp-json/doku/notification'
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
        $header['Content-Type'] = "application/json";

        $signature = $this->jokulUtils->generateSignature($header, json_encode($data), $config['shared_key']);
        $header['Signature'] = $signature;

        $body = json_encode($data);

        $args = array(
            'body' => $body,
            'headers' => $header,
            'method' => 'POST',
            'timeout' => 45,
        );
        $response = wp_remote_post($url, $args);
        $response_body = wp_remote_retrieve_body($response);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout REQUEST : ' . json_encode($data), $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout REQUEST Header: ' . json_encode($header), $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout REQUEST URL : ' . $url, $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout RESPONSE : ' . json_encode($response, JSON_PRETTY_PRINT), $params['invoiceNumber']);
        $this->jokulUtils->doku_log($this, 'Jokul Checkout RESPONSE Body: ' . json_encode($response_body, JSON_PRETTY_PRINT), $params['invoiceNumber']);
        

        return json_decode($response_body, true);
    }
}

?>
