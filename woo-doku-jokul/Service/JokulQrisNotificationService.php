<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuQrisNotificationService
{
    public function getQrisNotification($request){

        $dokuUtils = new DokuUtils();

        $raw_notification = $request->get_json_params();
        $dokuUtils->doku_log('Qris Notify', 'Jokul - Notification Controller Notification Raw Request: ' . json_encode($raw_notification, JSON_PRETTY_PRINT));

        $transaction_id = isset($raw_notification['order']['invoice_number']) ? sanitize_text_field($raw_notification['order']['invoice_number']) : '';
        $acquirer = isset($raw_notification['acquirer']['id']) ? sanitize_text_field($raw_notification['acquirer']['id']) : '';
        $txn_status = isset($raw_notification['transaction']['status']) ? sanitize_text_field($raw_notification['transaction']['status']) : '';

        $dokuUtils->doku_log('Qris Notify', 'Jokul - Notification Controller Qris Notification Request : ' . $acquirer);

        if (empty($transaction_id)) {
            $dokuUtils->doku_log('Qris Notify', 'TRANSACTIONID is missing');
            return;
        }

        $order = wc_get_order($transaction_id);
        if (!$order) {
            $dokuUtils->doku_log('Qris Notify', 'Order not found for TRANSACTIONID: ' . $transaction_id);
            return;
        }

        $dokuUtils->doku_log('Qris Notify', '$txn_status : ' . $txn_status);

        if ($txn_status == 'SUCCESS') {
            $order->update_status('processing');
            $order->payment_complete();
            $dokuUtils->doku_log('Qris Notify', 'Jokul - Update transaction to Processing ' . $transaction_id);
            echo "SUCCESS";
        } else {
            $order->update_status('failed');
            $dokuUtils->doku_log('Qris Notify', 'Jokul - Update transaction to FAILED ' . $transaction_id);
            echo "SUCCESS";
        }
    }
}
