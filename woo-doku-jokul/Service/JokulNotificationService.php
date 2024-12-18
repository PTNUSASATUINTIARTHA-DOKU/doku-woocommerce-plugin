<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulDb.php');

class JokulNotificationService
{

    private function sanitize_array($array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitize_array($value);
            } else {
                if (is_string($value)) {
                    $value = sanitize_text_field($value);
                } elseif (is_int($value)) {
                    $value = intval($value);
                } elseif (is_float($value)) {
                    $value = floatval($value);
                }
            }
        }
        return $array;
    }

    public function getNotification($path,$request)
    {
        $jokulUtils = new JokulUtils();
        $raw_input = $request->get_json_params();
        $jokulUtils->doku_log($jokulUtils, 'raw input notif : ' . json_encode($raw_input, JSON_PRETTY_PRINT));
        $raw_notification = $raw_input;
        $mainSettings = get_option('woocommerce_jokul_gateway_settings');
        $headerData = $request->get_headers();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jokulUtils->doku_log($jokulUtils, 'INVALID JSON INPUT: ' . json_last_error_msg(), null);
            http_response_code(400);
            echo esc_html(http_response_code());
            return new WP_REST_Response('Invalid JSON input', 400);
        }

        $raw_notification = $this->sanitize_array($raw_notification);

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
        $requestTarget =  '/wp-json/' . $path . '/notification';
        if ($serviceType == "ONLINE_TO_OFFLINE") {
            $paymentCode = $raw_notification['online_to_offline_info']['payment_code'];
            $paymentDate = $raw_notification['transaction']['date'];
        } else {
            $paymentCode = $raw_notification['virtual_account_info']['virtual_account_number'];
            $paymentDate = $raw_notification['virtual_account_payment']['date'];
        }

        $transaction = $jokulDb->checkTrx($invoiceNumber, $amount, $paymentCode);

        if (!empty($transaction)){

            $signature = $jokulUtils->generateSignatureNotification($headerData, $request->get_body(), $sharedKey, $requestTarget);
 			error_log("Signature From DOKU: " . $headerData['signature'][0]);
          	error_log("Signature From MERCHANT: " . $signature);
            if ($signature == $headerData['signature'][0]) {
                        $jokulUtils->doku_log($jokulUtils, 'TRANSACTION SIGNATURE VALID', $raw_notification['order']['invoice_number']);

                if (strtolower($raw_notification['transaction']['status']) == strtolower('SUCCESS')) {
                    $checkTrxStatus = $jokulDb->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus,$raw_notification);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('processing');
                    $order->payment_complete();
                } else if (strtolower($raw_notification['transaction']['status']) == strtolower('FAILED')) {
                    $checkTrxStatus = $jokulDb->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus,$raw_notification);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('failed');
                }
            } else {
                $jokulUtils->doku_log($jokulUtils, 'SIGNATURE NOT MATCH!', $raw_notification['order']['invoice_number']);
                http_response_code(400);
                echo esc_html(http_response_code());
                return new WP_REST_Response(null, 400);
            }
        } else {
            http_response_code(404);
            echo esc_html(http_response_code());
            return new WP_REST_Response(null, 404);
        }
    }

    function addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $channel, $transactionStatus,$raw_notification)
    {
        $jokulUtils = new JokulUtils();
        $getIp = $jokulUtils->getIpaddress();

        $trx = array();
        $trx['invoice_number']          = $invoiceNumber;
        $trx['result_msg']              = null;
        $trx['process_type']            = 'PAYMENT_COMPLETED';
        $trx['raw_post_data']           = sanitize_text_field(json_encode($raw_notification, JSON_PRETTY_PRINT));
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
