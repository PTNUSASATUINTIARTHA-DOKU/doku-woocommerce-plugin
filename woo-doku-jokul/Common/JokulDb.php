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

        $columns_str = implode(', ', array_map([$wpdb, 'escape'], $columns)); // Escape column names
        $placeholders_str = implode(', ', $placeholders);

        $query = "INSERT INTO {$table} ({$columns_str}) VALUES ({$placeholders_str})";
        $result = $wpdb->query($wpdb->prepare($query, array_values($datainsert))); // Use prepare properly
        return $result;
    }
    
    function updateData($invoice, $status) 
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $query = "UPDATE {$table} SET process_type = %s WHERE invoice_number = %s";
        $result = $wpdb->query($wpdb->prepare($query, $status, $invoice));
        return $result;
    } 

    function checkTrx($order_id, $amount)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $query = "SELECT * FROM {$table} WHERE invoice_number = %s AND amount = %d ORDER BY trx_id DESC LIMIT 1";
        $result = $wpdb->get_row($wpdb->prepare($query, $order_id, $amount));
        return $result;
    }

    function checkStatusTrx($order_id, $amount, $processType)
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $query = "SELECT payment_code FROM {$table} WHERE invoice_number = %s AND amount = %d AND process_type = %s ORDER BY trx_id DESC LIMIT 1";
        $result = $wpdb->get_var($wpdb->prepare($query, $order_id, $amount, $processType));
        return $result;
    }
}
