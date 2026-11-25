<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulDb.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckStatusService.php');

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

    public function getQrisNotification($path, $request)
    {
        $dokuUtils = new DokuUtils();
        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION START =====');
        $raw_input = $request->get_json_params();
        $mainSettings = get_option('woocommerce_doku_gateway_settings');
        $headerData = $request->get_headers();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $dokuUtils->doku_log($dokuUtils, 'INVALID JSON INPUT: ' . json_last_error_msg(), null);
            return new WP_REST_Response('Invalid JSON input', 400);
        }

        $raw_notification = is_array($raw_input) ? $this->sanitize_array($raw_input) : array();
        $invoiceNumber = isset($raw_notification['order']['invoice_number']) ? (string) $raw_notification['order']['invoice_number'] : '';
        $amount = isset($raw_notification['order']['amount']) ? $raw_notification['order']['amount'] : 0;
        $paymentChannel = !empty($raw_notification['payment']['channel'])
            ? (string) $raw_notification['payment']['channel']
            : (!empty($raw_notification['channel']['id']) ? (string) $raw_notification['channel']['id'] : '');
        $transactionStatus = isset($raw_notification['transaction']['status']) ? trim((string) $raw_notification['transaction']['status']) : '';

        // Gate 1: Load live gateway credentials from settings
        $environmentValue = isset($mainSettings['environment_payment_jokul']) ? (string) $mainSettings['environment_payment_jokul'] : 'false';
        $sharedKey = '';
        $configuredClientId = '';

        if ($environmentValue === 'false') {
            $sharedKey = isset($mainSettings['sandbox_shared_key']) ? trim($mainSettings['sandbox_shared_key']) : '';
            $configuredClientId = isset($mainSettings['sandbox_client_id']) ? trim($mainSettings['sandbox_client_id']) : '';
            $dokuUtils->doku_log($dokuUtils, 'Environment: SANDBOX', $invoiceNumber);
        } else if ($environmentValue === 'true') {
            $sharedKey = isset($mainSettings['prod_shared_key']) ? trim($mainSettings['prod_shared_key']) : '';
            $configuredClientId = isset($mainSettings['prod_client_id']) ? trim($mainSettings['prod_client_id']) : '';
            $dokuUtils->doku_log($dokuUtils, 'Environment: PRODUCTION', $invoiceNumber);
        }

        if (empty($sharedKey) || empty($configuredClientId)) {
            $dokuUtils->doku_log($dokuUtils, '[SECURITY REJECTED] Secret/Shared Key or Client ID is not configured or empty.', $invoiceNumber);
            return new WP_REST_Response('Gateway configuration error: Secret Key or Client ID is not configured.', 500);
        }

        $parsed_url = parse_url( get_site_url() );
        $endpoint_path = '';
        if ( isset( $parsed_url['path'] ) ) {
            $endpoint_path = $parsed_url['path'];
        }

        if ( isset( $parsed_url['query'] ) ) {
            $endpoint_path .= '?' . $parsed_url['query'];
        }

        $requestTarget = $endpoint_path . '/wp-json/' . $path . '/qrisnotification';
        $dokuUtils->doku_log($dokuUtils, 'Request Target: ' . $requestTarget, $invoiceNumber);

        $clientId = isset($headerData['client_id'][0]) ? trim((string) $headerData['client_id'][0]) : '';
        $requestId = isset($headerData['request_id'][0]) ? trim((string) $headerData['request_id'][0]) : '';
        $requestTimestamp = isset($headerData['request_timestamp'][0]) ? trim((string) $headerData['request_timestamp'][0]) : '';
        $incomingSignature = isset($headerData['signature'][0]) ? trim((string) $headerData['signature'][0]) : '';

        $signature = $dokuUtils->generateSignatureNotification($headerData, $request->get_body(), $sharedKey, $requestTarget);
        if ($clientId === '' || $requestId === '' || $requestTimestamp === '' || $incomingSignature === '' || !hash_equals($signature, $incomingSignature)) {
            $dokuUtils->doku_log($dokuUtils, 'SIGNATURE NOT MATCH!', $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Signature Mismatch) =====', $invoiceNumber);
            return new WP_REST_Response(null, 400);
        }

        $dokuUtils->doku_log($dokuUtils, 'TRANSACTION SIGNATURE VALID', $invoiceNumber);
        $dokuUtils->doku_log_masked_payload($dokuUtils, 'NOTIFICATION  : ', $raw_notification, $invoiceNumber);

        // Single trx_id & Order Lookup
        $dokuDB = new DokuDB();
        $transaction = $dokuDB->getLatestTrxByInvoice($invoiceNumber);

        if (empty($transaction) || !isset($transaction->trx_id) || (int) $transaction->trx_id <= 0) {
            $dokuUtils->doku_log($dokuUtils, 'Transaction NOT Found in DB: ' . $invoiceNumber, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 404 (Transaction Not Found) =====', $invoiceNumber);
            return new WP_REST_Response(null, 404);
        }

        $initialTrxId = (int) $transaction->trx_id;
        $initialProcessType = isset($transaction->process_type) ? strtoupper(trim((string) $transaction->process_type)) : '';

        $order = $dokuUtils->get_order_by_invoice($invoiceNumber);
        if (!$order) {
            $dokuUtils->doku_log($dokuUtils, 'Order NOT Found in WooCommerce: ' . $invoiceNumber, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 404 (Order Not Found) =====', $invoiceNumber);
            return new WP_REST_Response('Order not found', 404);
        }

        $orderId = $order->get_id();
        $dokuUtils->doku_log($dokuUtils, 'Transaction Found: ' . $invoiceNumber . ' | Trx ID: ' . $initialTrxId . ' | Status: ' . $initialProcessType, $invoiceNumber);

        // Fast-path guards: skip Check Status for already synced/final orders
        // 1. Final order status (completed, refunded)
        if ($order->has_status(array('completed', 'refunded'))) {
            $dokuUtils->doku_log($dokuUtils, 'Order is in final status (' . $order->get_status() . '), skipping notify', $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 (Order Final) =====', $invoiceNumber);
            return new WP_REST_Response('Order is in final status', 200);
        }

        // 2. Cancelled order already synchronized (PAYMENT_EXPIRED)
        if ($order->has_status('cancelled') && $initialProcessType === 'PAYMENT_EXPIRED') {
            $dokuUtils->doku_log($dokuUtils, 'Order already cancelled and PAYMENT_EXPIRED, skipping notify', $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 (Already Expired) =====', $invoiceNumber);
            return new WP_REST_Response('Order already cancelled and expired', 200);
        }

        // 3. Idempotent check: processing order with PAYMENT_COMPLETED
        if ($order->has_status('processing') && $initialProcessType === 'PAYMENT_COMPLETED') {
            $dokuUtils->doku_log($dokuUtils, 'PAYMENT_COMPLETED already exists, skipping duplicate notify', $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 (Duplicate Payment Completed) =====', $invoiceNumber);
            return new WP_REST_Response('Payment already completed', 200);
        }

        // Gate 2: Verify transaction status via Check Status API
        $checkStatusService = new DokuCheckStatusService();
        $checkStatusConfig = array(
            'client_id'   => $configuredClientId,
            'shared_key'  => $sharedKey,
            'environment' => $environmentValue,
        );
        $checkStatusParams = array(
            'invoiceNumber' => $invoiceNumber,
        );

        $checkStatusResponse = $checkStatusService->generated($checkStatusConfig, $checkStatusParams, array('redirection' => 0));
        $httpCode = $checkStatusService->getLastHttpCode();
        $apiError = $checkStatusService->getLastError();

        // Evaluate Check Status response (deterministic steps 1 to 5)
        // Step 1: Timeout, network error, HTTP 5xx, or unexpected HTTP status
        if ($apiError !== null || $httpCode >= 500 || $httpCode < 200 || ($httpCode >= 300 && $httpCode < 400)) {
            $dokuUtils->doku_log($dokuUtils, 'Check Status API error or 5xx/unexpected HTTP code: ' . $httpCode . ' (Error: ' . ($apiError !== null ? $apiError : 'none') . ')', $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 (Check Status API Error) =====', $invoiceNumber);
            return new WP_REST_Response('Check Status API error', 500);
        }

        // Step 2: HTTP 4xx client error (including 404)
        if ($httpCode >= 400 && $httpCode < 500) {
            $dokuUtils->doku_log($dokuUtils, 'Check Status API returned 4xx HTTP code: ' . $httpCode, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Check Status 4xx) =====', $invoiceNumber);
            return new WP_REST_Response('Check Status API 4xx', 400);
        }

        // Step 3: HTTP 2xx with non-JSON or unreadable body
        if (!is_array($checkStatusResponse)) {
            $dokuUtils->doku_log($dokuUtils, 'Check Status API returned non-JSON / unreadable body on HTTP ' . $httpCode, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 (Non-JSON Body) =====', $invoiceNumber);
            return new WP_REST_Response('Check Status unreadable body', 500);
        }

        // Step 4: Validate required fields and invoice match in response payload
        $resInvoice = isset($checkStatusResponse['order']['invoice_number']) ? (string) $checkStatusResponse['order']['invoice_number'] : '';
        $rawStatus = isset($checkStatusResponse['transaction']['status']) ? $checkStatusResponse['transaction']['status'] : null;

        if ($resInvoice !== (string) $invoiceNumber || $rawStatus === null || !is_scalar($rawStatus) || trim((string) $rawStatus) === '') {
            $dokuUtils->doku_log($dokuUtils, 'Check Status API response incomplete or invoice mismatch: invoice=' . $resInvoice, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Invalid Check Status Payload) =====', $invoiceNumber);
            return new WP_REST_Response('Invalid Check Status payload', 400);
        }

        // Step 5: Acquire per-invoice lock, re-fetch state, and apply status transition matrix
        $lockAcquired = $dokuDB->acquireInvoiceLock($invoiceNumber);
        if (!$lockAcquired) {
            $dokuUtils->doku_log($dokuUtils, 'Failed to acquire invoice lock: ' . $invoiceNumber, $invoiceNumber);
            $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 (Lock Failed) =====', $invoiceNumber);
            return new WP_REST_Response('Could not acquire lock', 500);
        }

        try {
            $latestTrx = $dokuDB->getLatestTrxByInvoice($invoiceNumber);
            if (empty($latestTrx) || !isset($latestTrx->trx_id) || (int) $latestTrx->trx_id !== $initialTrxId) {
                $dokuUtils->doku_log($dokuUtils, 'Stale transaction detected: initialTrxId=' . $initialTrxId . ', current=' . (isset($latestTrx->trx_id) ? $latestTrx->trx_id : 'none'), $invoiceNumber);
                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 (Stale Transaction) =====', $invoiceNumber);
                return new WP_REST_Response('Stale transaction', 500);
            }

            $freshOrder = function_exists('wc_get_order') ? wc_get_order($orderId) : $dokuUtils->get_order_by_invoice($invoiceNumber);
            if (!$freshOrder) {
                $freshOrder = $dokuUtils->get_order_by_invoice($invoiceNumber);
            }
            if (!$freshOrder) {
                $dokuUtils->doku_log($dokuUtils, 'Fresh order not found on re-fetch: ' . $invoiceNumber, $invoiceNumber);
                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 404 (Order Not Found on Re-fetch) =====', $invoiceNumber);
                return new WP_REST_Response('Order not found', 404);
            }

            $latestProcessType = isset($latestTrx->process_type) ? strtoupper(trim((string) $latestTrx->process_type)) : '';
            $webhookClaimedSuccess = (strtolower($transactionStatus) === 'success');
            $dokuStatus = strtolower(trim((string) $rawStatus));

            // Status transition matrix
            if ($dokuStatus === 'success') {
                if ($freshOrder->has_status(array('completed', 'refunded', 'cancelled'))) {
                    $dokuUtils->doku_log($dokuUtils, 'Order is in final/cancelled status (' . $freshOrder->get_status() . '), skipping success update', $invoiceNumber);
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                    return new WP_REST_Response('Order already final', 200);
                }

                if (!$freshOrder->has_status('processing')) {
                    $orderStatusBefore = $freshOrder->get_status();
                    $dokuUtils->doku_log($dokuUtils, 'Order Status Before Update: ' . $orderStatusBefore, $invoiceNumber);
                    $freshOrder->update_meta_data('_doku_payment_completed', 'pending');
                    $statusUpdated = $freshOrder->update_status('processing');
                    if ($statusUpdated === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED UPDATE ORDER STATUS TO PROCESSING', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to update order status', 500);
                    }
                    $dokuUtils->doku_log($dokuUtils, 'Order Updated to: processing', $invoiceNumber);
                }

                $needsPaymentComplete = ((string) $freshOrder->get_meta('_doku_payment_completed', true) === 'pending');
                if ($needsPaymentComplete) {
                    $paymentCompleted = $freshOrder->payment_complete();
                    if ($paymentCompleted === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED CALL PAYMENT_COMPLETE ON ORDER', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to complete payment', 500);
                    }
                    $freshOrder->update_meta_data('_doku_payment_completed', '1');
                    $freshOrder->save();
                    $dokuUtils->doku_log($dokuUtils, 'payment_complete() succeeded and _doku_payment_completed marker saved', $invoiceNumber);
                }

                if ($latestProcessType !== 'PAYMENT_COMPLETED') {
                    $resultDb = $dokuDB->updateDataById($initialTrxId, 'PAYMENT_COMPLETED', $invoiceNumber);
                    if ($resultDb === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED INSERT PAYMENT COMPLETED TO DB', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to insert payment_completed to DB', 500);
                    }
                }

                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                return new WP_REST_Response('Success', 200);

            } else if ($dokuStatus === 'expired') {
                if ($freshOrder->has_status(array('processing', 'completed', 'refunded'))) {
                    $dokuUtils->doku_log($dokuUtils, 'Order is in ' . $freshOrder->get_status() . ', preserving order and badge on EXPIRED', $invoiceNumber);
                    if ($webhookClaimedSuccess) {
                        $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=EXPIRED', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                        return new WP_REST_Response('Status mismatch', 400);
                    }
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                    return new WP_REST_Response('Order status preserved', 200);
                }

                if (!$freshOrder->has_status('cancelled')) {
                    $orderStatusBefore = $freshOrder->get_status();
                    $dokuUtils->doku_log($dokuUtils, 'Order Status Before Update: ' . $orderStatusBefore, $invoiceNumber);
                    $statusUpdated = $freshOrder->update_status('cancelled');
                    if ($statusUpdated === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED UPDATE ORDER STATUS TO CANCELLED', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to update order status', 500);
                    }
                    $dokuUtils->doku_log($dokuUtils, 'Order Updated to: cancelled', $invoiceNumber);
                }

                if ($latestProcessType !== 'PAYMENT_EXPIRED') {
                    $resultDb = $dokuDB->updateDataById($initialTrxId, 'PAYMENT_EXPIRED', $invoiceNumber);
                    if ($resultDb === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED INSERT PAYMENT EXPIRED TO DB', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to insert payment_expired to DB', 500);
                    }
                }

                if ($webhookClaimedSuccess) {
                    $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=EXPIRED', $invoiceNumber);
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                    return new WP_REST_Response('Status mismatch', 400);
                }

                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                return new WP_REST_Response('Expired', 200);

            } else if ($dokuStatus === 'pending') {
                if ($freshOrder->has_status(array('processing', 'completed', 'refunded', 'cancelled'))) {
                    $dokuUtils->doku_log($dokuUtils, 'Order is in ' . $freshOrder->get_status() . ', preserving order and badge on PENDING', $invoiceNumber);
                    if ($webhookClaimedSuccess) {
                        $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=PENDING', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                        return new WP_REST_Response('Status mismatch', 400);
                    }
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                    return new WP_REST_Response('Order status preserved', 200);
                }

                if ($freshOrder->has_status('failed')) {
                    $orderStatusBefore = $freshOrder->get_status();
                    $dokuUtils->doku_log($dokuUtils, 'Order Status Before Update: ' . $orderStatusBefore, $invoiceNumber);
                    $statusUpdated = $freshOrder->update_status('pending');
                    if ($statusUpdated === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED UPDATE ORDER STATUS TO PENDING', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to update order status', 500);
                    }
                    $dokuUtils->doku_log($dokuUtils, 'Order Updated to: pending', $invoiceNumber);
                }

                if ($latestProcessType !== 'PAYMENT_PENDING') {
                    $resultDb = $dokuDB->updateDataById($initialTrxId, 'PAYMENT_PENDING', $invoiceNumber);
                    if ($resultDb === false) {
                        $dokuUtils->doku_log($dokuUtils, 'FAILED INSERT PAYMENT PENDING TO DB', $invoiceNumber);
                        $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 500 =====', $invoiceNumber);
                        return new WP_REST_Response('Failed to insert payment_pending to DB', 500);
                    }
                }

                if ($webhookClaimedSuccess) {
                    $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=PENDING', $invoiceNumber);
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                    return new WP_REST_Response('Status mismatch', 400);
                }

                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                return new WP_REST_Response('Pending', 200);

            } else if ($dokuStatus === 'failed') {
                if ($webhookClaimedSuccess) {
                    $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=FAILED', $invoiceNumber);
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                    return new WP_REST_Response('Status mismatch', 400);
                }

                $dokuUtils->doku_log($dokuUtils, 'Transaction Status from DOKU: FAILED - Existing order status and badge preserved.', $invoiceNumber);
                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                return new WP_REST_Response('Failed status preserved', 200);

            } else {
                $statusClean = trim((string) $rawStatus);
                if ($webhookClaimedSuccess) {
                    $dokuUtils->doku_log($dokuUtils, 'Status mismatch: webhook=SUCCESS, check_status=' . $statusClean, $invoiceNumber);
                    $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 400 (Mismatch) =====', $invoiceNumber);
                    return new WP_REST_Response('Status mismatch', 400);
                }

                $dokuUtils->doku_log($dokuUtils, 'Ignored unknown transaction.status from DOKU: ' . $statusClean, $invoiceNumber);
                $dokuUtils->doku_log($dokuUtils, '===== QRIS NOTIFICATION END - HTTP 200 =====', $invoiceNumber);
                return new WP_REST_Response('Status preserved', 200);
            }

        } finally {
            $dokuDB->releaseInvoiceLock($invoiceNumber);
        }
    }

    function updateDb($invoiceNumber, $status) {
        $dokuDB = new DokuDB();
        return $dokuDB->updateData($invoiceNumber, $status);
    }
}
