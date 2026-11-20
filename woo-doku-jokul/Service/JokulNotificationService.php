<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulDb.php');

class DokuNotificationService
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
        $dokuUtils = new DokuUtils();
        $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION START =====');
        $raw_input = $request->get_json_params();
        $mainSettings = get_option('woocommerce_doku_gateway_settings');
        $headerData = $request->get_headers();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $dokuUtils->doku_log($dokuUtils, 'INVALID JSON INPUT: ' . json_last_error_msg(), null);
            http_response_code(400);
            echo esc_html(http_response_code());
            return new WP_REST_Response('Invalid JSON input', 400);
        }

        $raw_notification = is_array($raw_input) ? $this->sanitize_array($raw_input) : array();
        $invoiceNumber = isset($raw_notification['order']['invoice_number']) ? $raw_notification['order']['invoice_number'] : '';
        $amount = isset($raw_notification['order']['amount']) ? $raw_notification['order']['amount'] : 0;
        $paymentChannel = !empty($raw_notification['payment']['channel'])
            ? $raw_notification['payment']['channel']
            : (!empty($raw_notification['channel']['id']) ? $raw_notification['channel']['id'] : '');
        $transactionStatus = isset($raw_notification['transaction']['status']) ? trim((string) $raw_notification['transaction']['status']) : '';

        $sharedKey = '';
        if (isset($mainSettings['environment_payment_jokul']) && $mainSettings['environment_payment_jokul'] == 'false') {
            $sharedKey = isset($mainSettings['sandbox_shared_key']) ? trim($mainSettings['sandbox_shared_key']) : '';
            $dokuUtils->doku_log($dokuUtils, 'Environment: SANDBOX', $invoiceNumber);
        } else if (isset($mainSettings['environment_payment_jokul']) && $mainSettings['environment_payment_jokul'] == 'true') {
            $sharedKey = isset($mainSettings['prod_shared_key']) ? trim($mainSettings['prod_shared_key']) : '';
            $dokuUtils->doku_log($dokuUtils, 'Environment: PRODUCTION', $invoiceNumber);
        }

        if (empty($sharedKey)) {
            $dokuUtils->doku_log($dokuUtils, '[SECURITY REJECTED] Secret/Shared Key is not configured or empty.', $invoiceNumber);
            http_response_code(500);
            return new WP_REST_Response('Gateway configuration error: Secret Key is not configured.', 500);
        }

        $parsed_url = parse_url( get_site_url() );
        $endpoint_path = '';
        if ( isset( $parsed_url['path'] ) ) {
            $endpoint_path = $parsed_url['path'];
        }

        if ( isset( $parsed_url['query'] ) ) {
            $endpoint_path .= '?' . $parsed_url['query'];
        }

        $requestTarget = $endpoint_path . '/wp-json/' . $path . '/notification';
        $dokuUtils->doku_log($dokuUtils, 'Request Target: ' . $requestTarget, $invoiceNumber);

        $clientId = isset($headerData['client_id'][0]) ? trim((string) $headerData['client_id'][0]) : '';
        $requestId = isset($headerData['request_id'][0]) ? trim((string) $headerData['request_id'][0]) : '';
        $requestTimestamp = isset($headerData['request_timestamp'][0]) ? trim((string) $headerData['request_timestamp'][0]) : '';
        $incomingSignature = isset($headerData['signature'][0]) ? trim((string) $headerData['signature'][0]) : '';

        $signature = $dokuUtils->generateSignatureNotification($headerData, $request->get_body(), $sharedKey, $requestTarget);
        if ($clientId === '' || $requestId === '' || $requestTimestamp === '' || $incomingSignature === '' || !hash_equals($signature, $incomingSignature)) {
            $dokuUtils->doku_log($dokuUtils, 'SIGNATURE NOT MATCH!', $invoiceNumber);
            http_response_code(400);
            echo esc_html(http_response_code());
            $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION END - HTTP 400 (Signature Mismatch) =====', $invoiceNumber);
            return new WP_REST_Response(null, 400);
        }

        $dokuUtils->doku_log($dokuUtils, 'TRANSACTION SIGNATURE VALID', $invoiceNumber);
        $dokuUtils->doku_log_masked_payload($dokuUtils, 'NOTIFICATION  : ', $raw_notification, $invoiceNumber);

        $dokuDB = new DokuDB();
        $transaction = $dokuDB->checkTrx($invoiceNumber, $amount);

        if (!empty($transaction)) {
            $dokuUtils->doku_log($dokuUtils, 'Transaction Found: ' . $invoiceNumber . ' | Amount: ' . $amount . ' | Channel: ' . $paymentChannel, $invoiceNumber);

            $statusLower = strtolower($transactionStatus);

            if ($statusLower === 'success') {
                $dokuUtils->doku_log($dokuUtils, 'Transaction Status from DOKU: SUCCESS', $invoiceNumber);
                $checkTrxStatus = $dokuDB->checkStatusTrx($invoiceNumber, $amount, 'PAYMENT_COMPLETED');
                if ($checkTrxStatus == '') {
                    $order = $dokuUtils->get_order_by_invoice($invoiceNumber);

                    if (!$order) {
                        $dokuUtils->doku_log($dokuUtils, 'Order NOT Found in WooCommerce: ' . $invoiceNumber, $invoiceNumber);
                        http_response_code(404);
                        echo esc_html(http_response_code());
                        $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION END - HTTP 404 (Order Not Found) =====', $invoiceNumber);
                        return new WP_REST_Response('Order not found', 404);
                    }
                    $orderStatusBefore = $order->get_status();
                    $dokuUtils->doku_log($dokuUtils, 'Order Status Before Update: ' . $orderStatusBefore, $invoiceNumber);
                    $order->update_status('processing');
                    $order->payment_complete();
                    $dokuUtils->doku_log($dokuUtils, 'Order Updated to: processing | payment_complete() called', $invoiceNumber);
                    $resultDb = $this->updateDb($invoiceNumber, 'PAYMENT_COMPLETED');
                    if ($resultDb === false || $resultDb === 0) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED INSERT PAYMENT COMPLETED TO DB', $invoiceNumber);
                        http_response_code(500);
                        echo esc_html(http_response_code());
                        $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to insert payment_completed to DB', 500);
                    }
                } else {
                    $dokuUtils->doku_log($dokuUtils, 'PAYMENT_COMPLETED already exists, skipping duplicate notify', $invoiceNumber);
                }
            } else if ($statusLower === 'failed') {
                $dokuUtils->doku_log($dokuUtils, 'Transaction Status from DOKU: FAILED - Existing order status and badge preserved.', $invoiceNumber);
            } else {
                $dokuUtils->doku_log($dokuUtils, 'Ignored empty or unknown transaction.status: ' . ($transactionStatus === '' ? '(empty)' : $transactionStatus), $invoiceNumber);
            }
            $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
        } else {
            $dokuUtils->doku_log($dokuUtils, 'Transaction NOT Found in DB: ' . $invoiceNumber . ' | Amount: ' . $amount, $invoiceNumber);
            http_response_code(404);
            echo esc_html(http_response_code());
            $dokuUtils->doku_log($dokuUtils, '===== NOTIFICATION END - HTTP 404 (Transaction Not Found) =====', $invoiceNumber);
            return new WP_REST_Response(null, 404);
        }
    }

    function updateDb($invoiceNumber, $status) {
        $dokuDB = new DokuDB();
        return $dokuDB->updateData($invoiceNumber, $status);
    }
}
