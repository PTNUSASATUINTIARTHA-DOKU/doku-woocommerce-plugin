<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulDb.php');

class JokulNotificationService
{

    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function getNotification()
    {
        $raw_notification = json_decode(file_get_contents('php://input'), true);
        $mainSettings = get_option('woocommerce_jokul_gateway_settings');
        $headerData = JokulNotificationService::getallheaders();
        
        if ($mainSettings['environment_payment_jokul'] == 'false') {
            $sharedKey = $mainSettings['sandbox_shared_key'];
        } else if ($mainSettings['environment_payment_jokul'] == 'true') {
            $sharedKey = $mainSettings['prod_shared_key'];
        }

        $jokulDb = new JokulDb();
        $responseDb = $jokulDb::checkTrx($raw_notification['order']['invoice_number'], $raw_notification['order']['amount'], $raw_notification['virtual_account_info']['virtual_account_number']);

        if ($responseDb != '') {
            $jokulUtils = new JokulUtils();
            $signature = $jokulUtils::generateSignatureNotification($headerData, file_get_contents('php://input'), $sharedKey);

            if ($signature == $headerData['Signature']) {
                $request = $raw_notification;
                
                $checkTrxStatus = $jokulDb::checkStatusTrx($raw_notification['order']['invoice_number'], $raw_notification['order']['amount'], $raw_notification['virtual_account_info']['virtual_account_number'], 'PAYMENT_COMPLETED');

                if ($checkTrxStatus == '') {
                    JokulNotificationService::addDb($request);
                }

                $order = wc_get_order($raw_notification['order']['invoice_number']);
                $order->update_status('processing');
                $order->payment_complete();
            } else {
                http_response_code(400);
                echo http_response_code();
                return new WP_REST_Response(null, 400);
            }
        } else {
            http_response_code(404);
            echo http_response_code();
            return new WP_REST_Response(null, 404);
        }
    }

    function addDb($request) 
    {
        $jokulUtils = new JokulUtils();
        $getIp = $jokulUtils::getIpaddress();

        $trx = array();
		$trx['invoice_number']          = $request['order']['invoice_number'];
		$trx['result_msg']              = null;
        $trx['process_type']            = 'PAYMENT_COMPLETED';  
        $trx['raw_post_data']           = file_get_contents('php://input');
        $trx['ip_address']              = $getIp;
		$trx['amount']                  = $request['order']['amount'];
		$trx['payment_channel']         = 'DOKU VA';
		$trx['payment_code']            = $request['virtual_account_info']['virtual_account_number'];
		$trx['doku_payment_datetime']   = $request['virtual_account_payment']['date'];
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");       
        $trx['message']                 = "Payment process message come from Jokul. Success : completed";   
                                
        $jokulDb = new JokulDb();
        $jokulDb::addData($trx);
    }
}

