<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulQrisNotificationService
{

    public function getQrisNotification($request)
    {
        // Membuat instance untuk utilitas
        $jokulUtils = new JokulUtils();
        
        // Mendapatkan raw notification dari input
        $raw_notification = $request->get_json_params();
        $jokulUtils->doku_log($jokulUtils, 'Qris Notify  : ' .'Jokul - Notification Controller Notification Raw Request: '.$raw_notification);

        // Hanya mengambil nilai yang dibutuhkan dari $_POST
        $acquirer = isset($_POST['ACQUIRER']) ? sanitize_text_field($_POST['ACQUIRER']) : '';
        $transaction_id = isset($_POST['TRANSACTIONID']) ? sanitize_text_field($_POST['TRANSACTIONID']) : '';
        $issuer_id = isset($_POST['ISSUERID']) ? sanitize_text_field($_POST['ISSUERID']) : '';
        $txn_date = isset($_POST['TXNDATE']) ? sanitize_text_field($_POST['TXNDATE']) : '';
        $merchant_pan = isset($_POST['MERCHANTPAN']) ? sanitize_text_field($_POST['MERCHANTPAN']) : '';
        $invoice = isset($_POST['INVOICE']) ? sanitize_text_field($_POST['INVOICE']) : '';
        $words = isset($_POST['WORDS']) ? sanitize_text_field($_POST['WORDS']) : '';
        $txn_status = isset($_POST['TXNSTATUS']) ? sanitize_text_field($_POST['TXNSTATUS']) : '';

        // Logging acquirer
        $jokulUtils->doku_log('Qris Notify','Jokul - Notification Controller Qris Notification Request : ' . $acquirer);

        // Mendapatkan order berdasarkan TRANSACTIONID
        if (empty($transaction_id)) {
            $jokulUtils->doku_log('Qris Notify','TRANSACTIONID is missing');
            return; // Jika TRANSACTIONID tidak ada, keluar dari proses
        }
        $order = wc_get_order($transaction_id);

        if (!$order) {
            $jokulUtils->doku_log('Qris Notify','Order not found for TRANSACTIONID: ' . $transaction_id);
            return; // Jika order tidak ditemukan, keluar
        }

        // Mengambil sharedKey dari setting
        $mainSettings = get_option('woocommerce_jokul_checkout_settings');
        $sharedKey = isset($mainSettings['payment_shared_key']) ? $mainSettings['payment_shared_key'] : '';

        // Membuat kata untuk validasi
        $words_to_validate = $issuer_id . $txn_date . $merchant_pan . $invoice . $sharedKey;
        $jokulUtils->doku_log('Qris Notify','Component Words Qris Current : ' . $words_to_validate);

        // Melakukan hash dengan SHA1
        $validateWord = sha1($words_to_validate);
        $jokulUtils->doku_log('Qris Notify','Validated Words Qris Current : ' . $validateWord);
        $jokulUtils->doku_log('Qris Notify','Words Qris Expected : ' . $words);

        // Validasi kecocokan kata
        if ($validateWord == $words) {
            if (strtolower($txn_status) == 's') {
                // Update status order menjadi 'processing'
                $order->update_status('processing');
                $order->payment_complete();
                $jokulUtils->doku_log('Qris Notify','Jokul - Update transaction to Processing ' . $transaction_id);
                echo "SUCCESS";
            } else {                
                // Update status order menjadi 'failed'
                $order->update_status('failed');
                $jokulUtils->doku_log('Qris Notify','Jokul - Update transaction to FAILED ' . $transaction_id);
                echo "SUCCESS";
            }
        } else {
            $jokulUtils->doku_log('Qris Notify','Words Not Match ' . $transaction_id);
        }
    }
}
