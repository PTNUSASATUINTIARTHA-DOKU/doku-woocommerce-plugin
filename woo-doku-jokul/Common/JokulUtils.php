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

    public function doku_log($class, $log_msg, $invoice_number = '')
    {
        $dir = $this->resolve_private_log_dir();
        if ($dir === false) {
            return false;
        }

        $log_file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'log_' . gmdate('d-M-Y') . '.log';

        clearstatcache(true, $log_file);
        if (is_link($log_file)) {
            $this->record_log_error('FILE_PERMISSION_FAILED');
            return false;
        }

        if (!file_exists($log_file)) {
            $handle = @fopen($log_file, 'x');
            if ($handle !== false) {
                fclose($handle);
            }
        }

        clearstatcache(true, $log_file);
        if (!is_file($log_file) || is_link($log_file)) {
            $this->record_log_error('FILE_PERMISSION_FAILED');
            return false;
        }

        $real_dir = realpath($dir);
        $real_file = realpath($log_file);
        if ($real_file === false || $real_dir === false || dirname($real_file) !== $real_dir) {
            $this->record_log_error('FILE_PERMISSION_FAILED');
            return false;
        }

        @chmod($log_file, 0600);
        clearstatcache(true, $log_file);

        if (!$this->is_secure_file_permission($log_file)) {
            $this->record_log_error('FILE_PERMISSION_FAILED');
            return false;
        }

        $log_header = gmdate(DATE_ATOM) . ' ' . '---> ' . $invoice_number . ' : ';
        $log_data = $log_header . $log_msg . "\n";

        $result = @file_put_contents($log_file, $log_data, FILE_APPEND | LOCK_EX);
        if ($result === false || $result !== strlen($log_data)) {
            $this->record_log_error('LOG_WRITE_FAILED');
            return false;
        }

        $this->clear_log_error();
        return true;
    }

    public function resolve_private_log_dir()
    {
        $is_cli_or_cron = (php_sapi_name() === 'cli' || defined('STDIN') || (function_exists('wp_doing_cron') && wp_doing_cron()));
        $doc_root = isset($_SERVER['DOCUMENT_ROOT']) && is_string($_SERVER['DOCUMENT_ROOT']) ? trim($_SERVER['DOCUMENT_ROOT']) : '';

        if ($is_cli_or_cron && $doc_root === '') {
            return false;
        }

        if ($doc_root === '') {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        $real_doc_root = realpath($doc_root);
        $real_abspath = defined('ABSPATH') ? realpath(ABSPATH) : false;

        if ($real_doc_root === false || $real_abspath === false) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        $target_dir = '';
        if (defined('DOKU_LOG_DIR') && is_string(DOKU_LOG_DIR) && trim(DOKU_LOG_DIR) !== '') {
            $target_dir = trim(DOKU_LOG_DIR);
        } else {
            $target_dir = dirname(rtrim($real_abspath, '/\\')) . DIRECTORY_SEPARATOR . 'doku-logs';
        }

        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $target_dir)) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        $is_absolute = false;
        if (substr($target_dir, 0, 1) === '/' || substr($target_dir, 0, 1) === '\\') {
            $is_absolute = true;
        } elseif (preg_match('/^[a-zA-Z]:[\\\\\/]/', $target_dir)) {
            $is_absolute = true;
        }

        if (!$is_absolute) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        $trimmed_target = rtrim($target_dir, '/\\');
        if ($trimmed_target === '' || preg_match('/^[a-zA-Z]:$/', $trimmed_target)) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        if (!file_exists($target_dir)) {
            $parent = dirname($target_dir);
            $real_parent = realpath($parent);
            if ($real_parent === false) {
                $this->record_log_error('PRIVATE_PATH_INVALID');
                return false;
            }

            if ($this->is_path_inside_or_same($real_parent, $real_doc_root) || $this->is_path_inside_or_same($real_parent, $real_abspath)) {
                $this->record_log_error('PRIVATE_PATH_INVALID');
                return false;
            }

            $created = @mkdir($target_dir, 0750, true);
            if (!$created && !is_dir($target_dir)) {
                $this->record_log_error('DIRECTORY_CREATE_FAILED');
                return false;
            }
        }

        $real_target = realpath($target_dir);
        if ($real_target === false) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        $trimmed_real_target = rtrim($real_target, '/\\');
        if ($trimmed_real_target === '' || preg_match('/^[a-zA-Z]:$/', $trimmed_real_target)) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        if ($this->is_path_inside_or_same($real_target, $real_doc_root) || $this->is_path_inside_or_same($real_target, $real_abspath)) {
            $this->record_log_error('PRIVATE_PATH_INVALID');
            return false;
        }

        if (!is_writable($real_target)) {
            $this->record_log_error('DIRECTORY_NOT_WRITABLE');
            return false;
        }

        return $real_target;
    }

    public function is_path_inside_or_same($path, $boundary)
    {
        if (!$path || !$boundary) {
            return true;
        }
        $p = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        $b = rtrim($boundary, '/\\') . DIRECTORY_SEPARATOR;

        if ($p === $b || strpos($p, $b) === 0) {
            return true;
        }

        return false;
    }

    public function is_secure_file_permission($file)
    {
        clearstatcache(true, $file);
        if (is_link($file) || !is_file($file)) {
            return false;
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return is_readable($file) && is_writable($file);
        }
        $perms = @fileperms($file);
        if ($perms === false) {
            return false;
        }
        return (($perms & 0077) === 0) && (($perms & 0600) === 0600);
    }

    public function record_log_error($reason)
    {
        $allowed_reasons = array(
            'PRIVATE_PATH_INVALID',
            'DIRECTORY_CREATE_FAILED',
            'DIRECTORY_NOT_WRITABLE',
            'FILE_PERMISSION_FAILED',
            'LOG_WRITE_FAILED',
        );

        if (!in_array($reason, $allowed_reasons, true)) {
            return;
        }

        $last_reason = function_exists('get_option') ? get_option('doku_log_error_reason', '') : '';
        if ($last_reason !== $reason) {
            @error_log('[DOKU Accept Payments] File logging disabled: reason=' . $reason);
            if (function_exists('update_option')) {
                update_option('doku_log_error_reason', $reason, false);
            }
        }
    }

    public function clear_log_error()
    {
        if (function_exists('get_option') && get_option('doku_log_error_reason')) {
            if (function_exists('delete_option')) {
                delete_option('doku_log_error_reason');
            }
        }
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
