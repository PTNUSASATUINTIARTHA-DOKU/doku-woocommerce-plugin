<?php
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

    public function generateSignatureNotification($headers, $body, $secret)
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
            . "Request-Target:" . $path . "/wp-json/doku/notification" . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode($secret), true));
        return 'HMACSHA256=' . $signature;
    }

    public function getIpaddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
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
        $log_header = date(DATE_ATOM, time()) . ' '  . '---> ' . $invoice_number . " : ";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_header . $log_msg . "\n", FILE_APPEND);
    }

    public function send_email($order, $emailParams, $howToPayUrl)
    {

        $mailer = WC()->mailer();

        //format the email
        $recipient = $emailParams['customerEmail'];
        $subject = __("Hi " . $emailParams['customerName']. ", here is your payment instructions for order number " . $order->get_order_number() . "!", 'theme_name');
        $content = $this->get_custom_email_html($order, $this->getEmailMessage($howToPayUrl), $mailer, $subject);
        $headers = "Content-Type: text/html\r\n";

        //send the email through wordpress
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

    function getEmailMessage($url)
    {
        $ch = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',

        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $responseJson = json_decode($response, true);
        return $responseJson['payment_instruction'];
    }
}
