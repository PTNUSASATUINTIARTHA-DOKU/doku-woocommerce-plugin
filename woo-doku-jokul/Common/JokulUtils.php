<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define("HTML_EMAIL_HEADERS", array('Content-Type: text/html; charset=UTF-8'));

class JokulUtils
{
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
        $digest = base64_encode(hash('sha256', $body, true));
        $url = get_site_url();
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'];

        if ($path != "/") {
            $path;
        }

        $rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $path . $requestTarget . "\n"
            . "Digest:" . $digest;
        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode($secret), true));
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
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipArray[0]);
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
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
    
        $log_filename = "doku_log";
        $log_header = gmdate(DATE_ATOM) . ' '  . '---> ' . $invoice_number . " : ";
        if (!file_exists($log_filename)) {
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . gmdate('d-M-Y') . '.log';
        file_put_contents($log_file_data, $log_header . $log_msg . "\n", FILE_APPEND);
    }

    public function send_email($order, $emailParams, $howToPayUrl)
    {
        $mailer = WC()->mailer();

        // Format the email
        $recipient = $emailParams['customerEmail'];
        $subject = sprintf(
            /* translators: %1$s: Customer name, %2$s: Order number */
            esc_html__(
                'Hi %1$s, here is your payment instructions for order number %2$s!', 
                'doku-payment'
            ),
            esc_html($emailParams['customerName']),
            esc_html($order->get_order_number())
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
        // Check if the phone number starts with '08'
        if (substr($phoneNumber, 0, 2) == '08') {
            // Replace '0' with '62'
            return '62' . substr($phoneNumber, 1);
        }
        return $phoneNumber;
    }
    
}
