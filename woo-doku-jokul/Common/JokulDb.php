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

        $query = $wpdb->prepare(
            "UPDATE `$table` SET process_type = %s WHERE invoice_number = %s",
            $status,
            $invoice
        );

        $result = $wpdb->query($query);
        return $result;
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

