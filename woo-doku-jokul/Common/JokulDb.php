<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class JokulDb {

    function addData($datainsert) 
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";
        
        $columns = array_keys($datainsert);
        $placeholders = array_map(function ($value) {
            return is_numeric($value) ? '%d' : '%s';
        }, $datainsert);

        $columns_str = implode(', ', $columns);
        $placeholders_str = implode(', ', $placeholders);
        
        $query = $wpdb->prepare(
            "INSERT INTO $table ($columns_str) VALUES ($placeholders_str)",
            array_values($datainsert)
        );

        $result = $wpdb->query($query);
    }
    
    function updateData($invoice, $status) 
    {
        global $wpdb;
        $wpdb->query("UPDATE ".$wpdb->prefix."jokuldb SET process_type='".$status."' WHERE invoice_number='".$invoice."'");
    } 

    function checkTrx($order_id, $amount, $vaNumber)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE invoice_number = %s AND amount = %d ORDER BY trx_id DESC LIMIT 1",
            $order_id,
            $amount
        );

        $result = $wpdb->get_row($query);

        return $result;
    }

    function checkStatusTrx($order_id, $amount, $vaNumber, $processType)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $query = $wpdb->prepare(
            "SELECT payment_code FROM $table WHERE invoice_number = %s AND amount = %d AND process_type = %s ORDER BY trx_id DESC LIMIT 1",
            $order_id,
            $amount,
            $processType
        );

        $result = $wpdb->get_var($query);
        return $result;
    }
}
?>
