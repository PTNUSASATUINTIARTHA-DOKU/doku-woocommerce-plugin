<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulDb.php');

class JokulNotificationService
{

    function getallheaders()
    {
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
        $jokulUtils = new JokulUtils();
        $raw_notification = json_decode(file_get_contents('php://input'), true);
        $mainSettings = get_option('woocommerce_jokul_gateway_settings');
        $headerData = $this->getallheaders();

        $jokulUtils->doku_log($jokulUtils, 'NOTIFICATION  : ' . json_encode($raw_notification, JSON_PRETTY_PRINT), $raw_notification['order']['invoice_number']);
        $jokulUtils->doku_log($jokulUtils, 'NOTIFICATION HEADER : ' . json_encode($headerData, JSON_PRETTY_PRINT), $raw_notification['order']['invoice_number']);

        if ($mainSettings['environment_payment_jokul'] == 'false') {
            $sharedKey = $mainSettings['sandbox_shared_key'];
        } else if ($mainSettings['environment_payment_jokul'] == 'true') {
            $sharedKey = $mainSettings['prod_shared_key'];
        }

        $jokulDb = new JokulDb();
        $serviceType = $raw_notification['service']['id'];
        $invoiceNumber = $raw_notification['order']['invoice_number'];
        $amount = $raw_notification['order']['amount'];
        $paymentChannel = $raw_notification['channel']['id'];
        $transactionStatus = $raw_notification['transaction']['status'];
        if ($serviceType == "ONLINE_TO_OFFLINE") {
            $paymentCode = $raw_notification['online_to_offline_info']['payment_code'];
            $paymentDate = $raw_notification['transaction']['date'];
        } else {
            $paymentCode = $raw_notification['virtual_account_info']['virtual_account_number'];
            $paymentDate = $raw_notification['virtual_account_payment']['date'];
        }

        $transaction = $jokulDb->checkTrx($invoiceNumber, $amount, $paymentCode);

        if ($transaction != '') {

            $signature = $jokulUtils->generateSignatureNotification($headerData, file_get_contents('php://input'), $sharedKey);

            if ($signature == $headerData['Signature']) {
                        $jokulUtils->doku_log($jokulUtils, 'TRANSACTION SIGNATURE VALID', $raw_notification['order']['invoice_number']);

                if (strtolower($raw_notification['transaction']['status']) == strtolower('SUCCESS')) {
                    $checkTrxStatus = $jokulDb->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('processing');
                    $order->payment_complete();
                } else if (strtolower($raw_notification['transaction']['status']) == strtolower('FAILED')) {
                    $checkTrxStatus = $jokulDb->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('failed');
                }
            } else {
                $jokulUtils->doku_log($jokulUtils, 'SIGNATURE NOT MATCH!', $raw_notification['order']['invoice_number']);
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

    function addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $channel, $transactionStatus)
    {
        $jokulUtils = new JokulUtils();
        $getIp = $jokulUtils->getIpaddress();

        $trx = array();
        $trx['invoice_number']          = $invoiceNumber;
        $trx['result_msg']              = null;
        $trx['process_type']            = 'PAYMENT_COMPLETED';
        $trx['raw_post_data']           = file_get_contents('php://input');
        $trx['ip_address']              = $getIp;
        $trx['amount']                  = $amount;
        $trx['payment_channel']         = $channel;
        $trx['payment_code']            = $paymentCode;
        $trx['doku_payment_datetime']   = $paymentDate;
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");
        $trx['message']                 = "Payment process message come from Jokul. " . $transactionStatus . " : completed";

        $jokulDb = new JokulDb();
        $jokulDb->addData($trx);
    }
}
