<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class JokulDb {

    function addData($datainsert) 
    {
        global $wpdb;
        $table = $wpdb->prefix . "jokuldb";

        $columns = array_keys($datainsert);
        $validated_columns = array_map('sanitize_key', $columns);
        $columns_str = implode(', ', $validated_columns);
        $placeholders = array_map(function ($value) {
            return is_numeric($value) ? '%d' : '%s';
        }, $datainsert);

        $placeholders_str = implode(', ', $placeholders);
        $query = $wpdb->prepare(
            "INSERT INTO `$table` ($columns_str) VALUES ($placeholders_str)",
            array_values($datainsert)
        );

        $result = $wpdb->query($query);
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
