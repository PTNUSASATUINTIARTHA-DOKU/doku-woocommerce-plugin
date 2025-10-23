<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuQrisNotificationService
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
    public function getQrisNotification($path, $request){

        $dokuUtils = new DokuUtils();

        $raw_input = $request->get_json_params();
        $dokuUtils->doku_log('Qris Notify', 'Jokul - Notification Controller Notification Raw Request: ' . json_encode($raw_input, JSON_PRETTY_PRINT));

        $dokuUtils->doku_log($dokuUtils, 'raw input notif : ' . json_encode($raw_input, JSON_PRETTY_PRINT));
        $raw_notification = $raw_input;
        $mainSettings = get_option('woocommerce_doku_gateway_settings');
        $headerData = $request->get_headers();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $dokuUtils->doku_log($dokuUtils, 'INVALID JSON INPUT: ' . json_last_error_msg(), null);
            http_response_code(400);
            echo esc_html(http_response_code());
            return new WP_REST_Response('Invalid JSON input', 400);
        }

        $raw_notification = $this->sanitize_array($raw_notification);

        $dokuUtils->doku_log($dokuUtils, 'NOTIFICATION  : ' . json_encode($raw_notification, JSON_PRETTY_PRINT), $raw_notification['order']['invoice_number']);
        $dokuUtils->doku_log($dokuUtils, 'NOTIFICATION HEADER : ' . json_encode($headerData, JSON_PRETTY_PRINT), $raw_notification['order']['invoice_number']);

         if ($mainSettings['environment_payment_jokul'] == 'false') {
            $sharedKey = $mainSettings['sandbox_shared_key'];
        } else if ($mainSettings['environment_payment_jokul'] == 'true') {
            $sharedKey = $mainSettings['prod_shared_key'];
        }

        $parsed_url = parse_url( get_site_url() );
        $endpoint_path = '';
		if ( isset( $parsed_url['path'] ) ) {
    		$endpoint_path = $parsed_url['path'];
		}

		if ( isset( $parsed_url['query'] ) ) {
    		$endpoint_path .= '?' . $parsed_url['query'];
		}

        $dokuDB = new DokuDB();
        $serviceType = $raw_notification['service']['id'];
        $invoiceNumber = $raw_notification['order']['invoice_number'];
        $amount = $raw_notification['order']['amount'];
        $paymentChannel = $raw_notification['channel']['id'];
        $transactionStatus = $raw_notification['transaction']['status'];
        $requestTarget =  $endpoint_path . '/wp-json/' . $path . '/qrisnotification';
        if ($serviceType == "ONLINE_TO_OFFLINE") {
            $paymentCode = $raw_notification['online_to_offline_info']['payment_code'];
            $paymentDate = $raw_notification['transaction']['date'];
        } else {
            $paymentCode = $raw_notification['virtual_account_info']['virtual_account_number'] ?? '';
            $paymentDate = $raw_notification['virtual_account_payment']['date'] ?? '';
        }


        $transaction = $dokuDB->checkTrx($invoiceNumber, $amount, $paymentCode);

        if (!empty($transaction)){

            $signature = $dokuUtils->generateSignatureNotification($headerData, $request->get_body(), $sharedKey, $requestTarget);
 			error_log("Signature From DOKU: " . $headerData['signature'][0]);
          	error_log("Signature From MERCHANT: " . $signature);
            if ($signature == $headerData['signature'][0]) {
                        $dokuUtils->doku_log($dokuUtils, 'TRANSACTION SIGNATURE VALID', $raw_notification['order']['invoice_number']);

                if (strtolower($raw_notification['transaction']['status']) == strtolower('SUCCESS')) {
                    $checkTrxStatus = $dokuDB->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus,$raw_notification);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('processing');
                    $order->payment_complete();
                } else if (strtolower($raw_notification['transaction']['status']) == strtolower('FAILED')) {
                    $checkTrxStatus = $dokuDB->checkStatusTrx($invoiceNumber, $amount, $paymentCode == "" ? "" : $paymentCode, 'PAYMENT_COMPLETED');

                    if ($checkTrxStatus == '') {
                        $this->addDb($invoiceNumber, $amount, $paymentCode, $paymentDate, $paymentChannel, $transactionStatus,$raw_notification);
                    }

                    $order = wc_get_order($invoiceNumber);
                    $order->update_status('failed');
                }
            } else {
                $dokuUtils->doku_log($dokuUtils, 'SIGNATURE NOT MATCH!', $raw_notification['order']['invoice_number']);
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
        $dokuUtils = new DokuUtils();
        $getIp = $dokuUtils->getIpaddress();

        $trx = array();
        $trx['invoice_number']          = $invoiceNumber;
        $trx['result_msg']              = "SUCCESS";
        $trx['process_type']            = 'PAYMENT_COMPLETED';
        $trx['raw_post_data']           = sanitize_text_field(json_encode($raw_notification, JSON_PRETTY_PRINT));
        $trx['ip_address']              = $getIp;
        $trx['amount']                  = $amount;
        $trx['payment_channel']         = $channel;
        $trx['payment_code']            = $paymentCode;
        $trx['doku_payment_datetime']   = $paymentDate;
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");
        $trx['message']                 = "Payment process message come from Jokul. " . $transactionStatus . " : completed";

        $dokuDB = new DokuDB();
        $dokuDB->addData($trx);
    }
}
