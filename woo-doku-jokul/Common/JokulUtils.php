<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define("DOKU_PAYMENT_HTML_EMAIL_HEADERS", array('Content-Type: text/html; charset=UTF-8'));

class DokuUtils
{
    
    public function get_order_by_invoice($invoiceNumber)
    {
        $invoiceNumber = (string) $invoiceNumber;
        if ($invoiceNumber === '') {
            return false;
        }

        $order = false;
        if (ctype_digit($invoiceNumber)) {
            $candidateOrder = wc_get_order((int) $invoiceNumber);
            if ($candidateOrder && ((string) $candidateOrder->get_order_number() === $invoiceNumber || (string) get_post_meta($candidateOrder->get_id(), '_doku_invoice_number', true) === $invoiceNumber)) {
                $order = $candidateOrder;
            }
        }

        if (!$order && function_exists('wc_get_order_id_by_order_number')) {
            $resolvedOrderId = wc_get_order_id_by_order_number($invoiceNumber);
            if ($resolvedOrderId) {
                $candidateOrder = wc_get_order($resolvedOrderId);
                if ($candidateOrder && ((string) $candidateOrder->get_order_number() === $invoiceNumber || (string) get_post_meta($candidateOrder->get_id(), '_doku_invoice_number', true) === $invoiceNumber)) {
                    $this->doku_log($this, 'RESOLVEORDERID VIA WC FUNCTION > ' . $resolvedOrderId, $invoiceNumber);
                    $order = $candidateOrder;
                }
            }
        }

        if (!$order) {
            foreach (array('_order_number', '_doku_invoice_number') as $metaKey) {
                $matchingOrders = wc_get_orders(array(
                    'limit'      => 1,
                    'meta_key'   => $metaKey,
                    'meta_value' => $invoiceNumber,
                ));
                if (!empty($matchingOrders)) {
                    $order = reset($matchingOrders);
                    $this->doku_log($this, 'RESOLVEORDERID VIA WC_GET_ORDERS (' . $metaKey . ') > ' . $order->get_id(), $invoiceNumber);
                    break;
                }
            }
        }

        return $order ? $order : false;
    }
    public function generateSignatureCheckStatus($headers, $secret)
    {
        $rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $headers['Request-Target'];

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode($secret), true));
        return 'HMACSHA256=' . $signature;
    }
    
    public function generateSignature($headers, $body, $secret)
    {
        $digest = base64_encode(hash('sha256', $body, true));
        $rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $headers['Request-Target'] . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode($secret), true));
        return 'HMACSHA256=' . $signature;
    }

    public function generateSignatureNotification($headers, $body, $secret, $requestTarget)
    {
        $clientId = isset($headers['client_id'][0]) ? (string) $headers['client_id'][0] : '';
        $requestId = isset($headers['request_id'][0]) ? (string) $headers['request_id'][0] : '';
        $requestTimestamp = isset($headers['request_timestamp'][0]) ? (string) $headers['request_timestamp'][0] : '';
        $digest = base64_encode(hash('sha256', (string) $body, true));

        $rawSignature = "Client-Id:" . $clientId . "\n"
            . "Request-Id:" . $requestId . "\n"
            . "Request-Timestamp:" . $requestTimestamp . "\n"
            . "Request-Target:" . $requestTarget . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode((string) $secret), true));

        return 'HMACSHA256=' . $signature;
    }

    // public function getIpaddress()
    // {
    //     if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    //         $ip = $_SERVER['HTTP_CLIENT_IP'];
    //     } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    //         $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    //     } else {
    //         $ip = $_SERVER['REMOTE_ADDR'];
    //     }
    //     return $ip;
    // }

    public function getIpaddress()
    {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipArray =  map_deep(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']),'sanitize_text_field');
            $ip = trim($ipArray[0]);
        } else {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }


    public function guidv4($data = null)
    {
        $data = $data ?? random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    function doku_log($class, $log_msg, $invoice_number = '')
    {
    
        $log_filename = ABSPATH . "doku_log";
        $log_header = gmdate(DATE_ATOM) . ' '  . '---> ' . $invoice_number . " : ";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . gmdate('d-M-Y') . '.log';
        file_put_contents($log_file_data, $log_header . $log_msg . "\n", FILE_APPEND);
    }

    public function doku_log_masked_payload($class, $prefix, $payload, $invoice_number = '')
    {
        try {
            if (is_object($payload)) {
                $payload = get_object_vars($payload);
            }

            if (!is_array($payload)) {
                $this->doku_log($class, $prefix . '[Response body omitted: unsupported format]', $invoice_number);
                return;
            }

            $masked = $this->mask_sensitive_data($payload);
            $encoded = json_encode($masked, JSON_PRETTY_PRINT);
            if ($encoded === false) {
                $this->doku_log($class, $prefix . '[Log payload omitted: sanitization or encoding failed]', $invoice_number);
                return;
            }

            $this->doku_log($class, $prefix . $encoded, $invoice_number);
        } catch (\Throwable $e) {
            $this->doku_log($class, $prefix . '[Log payload omitted: sanitization or encoding failed]', $invoice_number);
        }
    }

    public function mask_sensitive_data($data, $parentKey = '')
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (!is_array($data)) {
            return $data;
        }

        $customerContexts = array('customer', 'billing_address', 'shipping_address', 'payer');
        $parentLower = strtolower((string) $parentKey);
        $masked = array();

        foreach ($data as $key => $value) {
            $keyLower = strtolower((string) $key);

            if (is_array($value)) {
                $nextParent = is_string($key) ? $key : $parentKey;
                $masked[$key] = $this->mask_sensitive_data($value, $nextParent);
                continue;
            }

            if (is_object($value)) {
                $nextParent = is_string($key) ? $key : $parentKey;
                $masked[$key] = $this->mask_sensitive_data(get_object_vars($value), $nextParent);
                continue;
            }

            if ($value === null || $value === '') {
                $masked[$key] = $value;
                continue;
            }

            if (in_array($keyLower, array('shared_key', 'secret_key', 'signature'), true)) {
                $masked[$key] = '***';
                continue;
            }

            if ($keyLower === 'url' && $parentLower === 'payment') {
                $masked[$key] = '***';
                continue;
            }

            if (in_array($parentLower, $customerContexts, true) && in_array($keyLower, array('id', 'city', 'state'), true)) {
                $masked[$key] = '***';
                continue;
            }

            if (in_array($keyLower, array('email', 'customeremail'), true)) {
                $masked[$key] = $this->mask_email($value);
                continue;
            }

            if ($keyLower === 'phone') {
                $masked[$key] = $this->mask_phone($value);
                continue;
            }

            if ($keyLower === 'callback_url_result' || strpos($keyLower, 'callback_url') !== false) {
                $masked[$key] = $this->mask_callback_url($value);
                continue;
            }

            if (
                in_array($keyLower, array('first_name', 'last_name', 'customername', 'address', 'address_shipping', 'postcode', 'postal_code', 'postal_code_shipping'), true)
                || ($keyLower === 'name' && in_array($parentLower, $customerContexts, true))
            ) {
                $masked[$key] = $this->mask_partial_string($value);
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    private function mask_partial_string($value)
    {
        if (!is_scalar($value)) {
            return '***';
        }
        $str = (string) $value;
        if ($str === '') {
            return '';
        }
        if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
            return '***';
        }
        if (function_exists('mb_check_encoding') && !mb_check_encoding($str, 'UTF-8')) {
            return '***';
        }
        $len = mb_strlen($str, 'UTF-8');
        if ($len <= 2) {
            return '***';
        }
        return mb_substr($str, 0, 1, 'UTF-8') . '***' . mb_substr($str, -1, 1, 'UTF-8');
    }

    private function mask_email($value)
    {
        if (!is_scalar($value)) {
            return '***';
        }
        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }
        $atPos = strpos($str, '@');
        if ($atPos === false) {
            return $this->mask_partial_string($str);
        }
        $local = substr($str, 0, $atPos);
        $domain = substr($str, $atPos + 1);
        if ($domain === '') {
            return '***';
        }
        if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
            return '***@' . $domain;
        }
        if (function_exists('mb_check_encoding') && (!mb_check_encoding($local, 'UTF-8') || !mb_check_encoding($domain, 'UTF-8'))) {
            return '***';
        }
        $localLen = mb_strlen($local, 'UTF-8');
        $maskedLocal = ($localLen <= 2)
            ? '***'
            : mb_substr($local, 0, 1, 'UTF-8') . '***' . mb_substr($local, -1, 1, 'UTF-8');
        return $maskedLocal . '@' . $domain;
    }

    private function mask_phone($value)
    {
        if (!is_scalar($value)) {
            return '***';
        }
        $str = trim((string) $value);
        if ($str === '') {
            return '';
        }
        $digits = preg_replace('/\D+/', '', $str);
        if ($digits === null || strlen($digits) <= 3) {
            return '***';
        }
        return '***' . substr($digits, -3);
    }

    private function mask_callback_url($value)
    {
        if (!is_scalar($value)) {
            return '***';
        }
        $url = trim((string) $value);
        if ($url === '') {
            return '';
        }
        $parsed = parse_url($url);
        if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
            return '***';
        }

        $rebuilt = $parsed['scheme'] . '://';
        if (isset($parsed['user'])) {
            $rebuilt .= '***';
            if (isset($parsed['pass'])) {
                $rebuilt .= ':***';
            }
            $rebuilt .= '@';
        }
        $rebuilt .= $parsed['host'];
        if (isset($parsed['port'])) {
            $rebuilt .= ':' . $parsed['port'];
        }
        if (isset($parsed['path'])) {
            $rebuilt .= $parsed['path'];
        }
        if (isset($parsed['query']) && $parsed['query'] !== '') {
            $maskedQuery = preg_replace('/(^|&)(key=)([^&]*)/i', '$1$2***', $parsed['query']);
            if ($maskedQuery === null) {
                return '***';
            }
            $rebuilt .= '?' . $maskedQuery;
        }
        if (isset($parsed['fragment']) && $parsed['fragment'] !== '') {
            $rebuilt .= '#' . $parsed['fragment'];
        }

        return $rebuilt;
    }

    public function send_email($order, $emailParams, $howToPayUrl)
    {
        $mailer = WC()->mailer();

        // Format the email
        $recipient = $emailParams['customerEmail'];
        $customer_name = $emailParams['customerName'] ?? '-';
        $order_number = $order->get_order_number() ?? '-';
        $subject = sprintf(
            /* translators: %1$s: Customer name, %2$s: Order number */
            esc_html__(
                'Hi %1$s, here is your payment instructions for order number %2$s!', 
                'doku-payment'
            ),
            esc_html($customer_name),
            esc_html($order_number)
        );

        $content = $this->get_custom_email_html($order, $this->getEmailMessage($howToPayUrl), $mailer, $subject);
        $headers = "Content-Type: text/html\r\n";

        // Send the email through WordPress
        $mailer->send($recipient, $subject, $content, $headers);
    }

    function get_custom_email_html($order, $instructions, $mailer, $heading = false)
    {
        $template = 'how-to-pay.php';
        return wc_get_template_html($template, array(
            'order'         => $order,
            'instructions'  => $instructions,
            'email_heading' => false,
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $mailer
        ));
    }

    // function getEmailMessage($url)
    // {
    //     $ch = curl_init();
    //     $headers = array(
    //         'Accept: application/json',
    //         'Content-Type: application/json',

    //     );
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_HEADER, 0);

    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     // Timeout in seconds
    //     curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    //     $response = curl_exec($ch);
    //     $responseJson = json_decode($response, true);
    //     return $responseJson['payment_instruction'];
    // }
    function getEmailMessage($url)
    {
        $headers = array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        );

        $args = array(
            'headers' => $headers,
            'timeout' => 30,
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return "Error fetching payment instructions: $error_message";
        }

        // Ambil isi body dari respons
        $response_body = wp_remote_retrieve_body($response);
        $responseJson = json_decode($response_body, true);

        return $responseJson['payment_instruction'] ?? null;
    }

    function formatPhoneNumber($phoneNumber) {
        if (empty($phoneNumber)) {
            return null; 
        }

        // Check if the phone number starts with '08'
        if (substr($phoneNumber, 0, 2) == '08') {
            // Replace '0' with '62'
            return '62' . substr($phoneNumber, 1);
        }
        return $phoneNumber;
    }

    public function removeNullValues($array) {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->removeNullValues($value);
            }
            if (is_null($value)) {
                unset($array[$key]);
            }
        }
        return $array;
    }
    
}
