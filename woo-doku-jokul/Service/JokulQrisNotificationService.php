<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulConfig.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulUtils.php');

class JokulQrisNotificationService
{

    public function getQrisNotification()
    {
     $jokulUtils = new JokulUtils();
        $raw_notification = file_get_contents('php://input');

        $jokulUtils->doku_log($jokulUtils, 'Qris Notify  : ' .'Jokul - Notification Controller Notification Raw Request: '.$raw_notification);
        $jokulUtils->doku_log('Qris Notify','Jokul - Notification Controller Qris Notification Request : ' . $_POST['ACQUIRER']);

        $order = wc_get_order($_POST['TRANSACTIONID']);
        
        $mainSettings = get_option('woocommerce_jokul_checkout_settings');
        $sharedKey = $mainSettings['payment_shared_key'];

        $words = $_POST['ISSUERID'] . $_POST['TXNDATE'] . $_POST['MERCHANTPAN'] . $_POST['INVOICE'] . $sharedKey;
        $jokulUtils->doku_log('Qris Notify','Component Words Qris Current : ' . $words);

        $validateWord = sha1($words);
        $jokulUtils->doku_log('Qris Notify','Validated Words Qris Current : ' . $validateWord);
        $jokulUtils->doku_log('Qris Notify','Words Qris Expected : ' . $_POST['WORDS']);

        if ($validateWord == $_POST['WORDS']) {
            if (strtolower($_POST['TXNSTATUS']) == strtolower('S')) {
                $order = wc_get_order($_POST['TRANSACTIONID']);
                $order->update_status('processing');
                $order->payment_complete();
                $jokulUtils->doku_log('Qris Notify','Jokul - Update transaction to Processing '.$_POST['TRANSACTIONID']);
                echo "SUCCESS";
            } else {                
                $order = wc_get_order($_POST['TRANSACTIONID']);
                $order->update_status('failed');
                $jokulUtils->doku_log('Qris Notify','Jokul - Update transaction to FAILED '. $_POST['TRANSACTIONID']);
            }
        }  else {
            $jokulUtils->doku_log('Qris Notify','Words Not Match '. $_POST['TRANSACTIONID']);
        }  
    }
}
