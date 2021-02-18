<?php

class JokulUtils
{
    public function generateSignature($headers, $body, $secret)
    {
        $digest = base64_encode(hash('sha256', $body, true));
        $rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $headers['Request-Target'] . "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode ($secret), true));
        return 'HMACSHA256=' . $signature;
    }

    public function generateSignatureNotification($headers, $body, $secret)
    {
        $digest = base64_encode(hash('sha256', $body, true));
		$url = get_site_url();
		$parsedUrl = parse_url($url);
		$path = $parsedUrl['path'];
		
		if ( $path != "/" ) {
			$path;
		}
		
        $rawSignature = "Client-Id:" . $headers['Client-Id'] . "\n"
            . "Request-Id:" . $headers['Request-Id'] . "\n"
            . "Request-Timestamp:" . $headers['Request-Timestamp'] . "\n"
            . "Request-Target:" . $path . "/wp-json/jokul/notification". "\n"
            . "Digest:" . $digest;

        $signature = base64_encode(hash_hmac('sha256', $rawSignature, htmlspecialchars_decode($secret), true));
        return 'HMACSHA256=' . $signature;
    }

    public function getIpaddress()    
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
            {
                $ip=$_SERVER['HTTP_CLIENT_IP'];
            }
            elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }
            else
            {
                $ip=$_SERVER['REMOTE_ADDR'];
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
}

?>
