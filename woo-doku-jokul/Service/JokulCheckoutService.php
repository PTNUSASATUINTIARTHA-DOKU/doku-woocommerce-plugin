<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuCheckoutService {
    public $dokuUtils;
    public $dokuConfig;

    public function generated($config, $params)
    {
        $header = array();
        $this->dokuUtils = new DokuUtils();

        $requestId = $this->dokuUtils->guidv4();
        $formattedPhoneNumber = $this->dokuUtils->formatPhoneNumber($params['phone']);
        $targetPath= "/checkout/v1/payment";
        $dateTime = gmdate(DATE_ISO8601);
        $dateTimeFinal = substr($dateTime, 0, 19) . "Z";

        $data = $params['sac_check'] === 'yes' ? array(
            "order" => $params['auto_redirect'] === 'true' ? array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url_result" => $params['callback_url_result'],
                "callback_url_cancel" => "https://www.doku.com/en-US",
                "currency" => "IDR",
                "auto_redirect" => true,
                "disable_retry_payment" => true,
                "recover_abandoned_cart" => $params['recoverAbandonedCart'],
              	"expired_recovered_cart" => $params['expiredRecoveredCart'],
            ): array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url_result" => $params['callback_url_result'],
                "callback_url_cancel" => "https://www.doku.com/en-US",
                "currency" => "IDR",
              	"recover_abandoned_cart" => $params['recoverAbandonedCart'],
              	"expired_recovered_cart" => $params['expiredRecoveredCart'],
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
                    "version" => "1.3.33",
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
                "callback_url_result" => $params['callback_url_result'],
                "callback_url_cancel" => "https://www.doku.com/en-US",
                "currency" => "IDR",
                "auto_redirect" => true,
                "disable_retry_payment" => true,
              	"recover_abandoned_cart" => $params['recoverAbandonedCart'],
              	"expired_recovered_cart" => $params['expiredRecoveredCart'],
            ): array(
                "invoice_number" => $params['invoiceNumber'],
                "line_items" => $params['itemQty'],
                "amount" => $params['amount'],
                "callback_url_result" => $params['callback_url_result'],
                "callback_url_cancel" => "https://www.doku.com/en-US",
                "currency" => "IDR",
              	"recover_abandoned_cart" => $params['recoverAbandonedCart'],
              	"expired_recovered_cart" => $params['expiredRecoveredCart'],
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
                    "version" => "1.3.33",
                    "cms_version" => $params['woo_version']
                ),
                "method" => "Jokul Checkout",
                "doku_wallet_notify_url" => get_site_url() .'/wp-json/doku/notification'
            )
        );

        $data = $this->dokuUtils->removeNullValues($data);

        $this->dokuConfig = new DokuConfig();
        $valueEnv = $config['environment'] === 'true'? true: false;
        $getUrl = $this->dokuConfig -> getBaseUrl($valueEnv);
        $url = $getUrl.$targetPath;

        $header['Client-Id'] = $config['client_id'];
        $header['Request-Id'] = $requestId;
        $header['Request-Timestamp'] = $dateTimeFinal;
        $header['Request-Target'] = $targetPath;
        $header['Content-Type'] = "application/json";

        $signature = $this->dokuUtils->generateSignature($header, json_encode($data), $config['shared_key']);
        $header['Signature'] = $signature;

        $body = json_encode($data);

        $args = array(
            'body' => $body,
            'headers' => $header,
            'method' => 'POST',
            'timeout' => 45,
        );
        $response = wp_remote_post($url, $args);
        $http_code = is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response);
        $response_body = is_wp_error($response) ? '' : wp_remote_retrieve_body($response);
        $decoded_response = json_decode($response_body, true);

        $this->dokuUtils->doku_log_masked_payload($this, 'Jokul Checkout REQUEST : ', $data, $params['invoiceNumber']);
        $this->dokuUtils->doku_log($this, 'Jokul Checkout REQUEST URL : ' . $url, $params['invoiceNumber']);
        $this->dokuUtils->doku_log($this, 'Jokul Checkout RESPONSE HTTP Code: ' . $http_code, $params['invoiceNumber']);
        $this->dokuUtils->doku_log_masked_payload($this, 'Jokul Checkout RESPONSE Body: ', $decoded_response, $params['invoiceNumber']);

        return $decoded_response;
    }
}

?>
