<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class JokulDb {

    function addData($datainsert) {
        global $wpdb;
    
        // Nama tabel dengan prefix
        $table = $wpdb->prefix . 'jokuldb';
    
        // Memastikan kolom adalah array dan valid
        if (empty($datainsert) || !is_array($datainsert)) {
            return false; // Menghindari jika data tidak valid
        }
    
        // Format data berdasarkan tipe (s = string, d = integer, f = float)
        $format = array_map(function ($value) {
            if (is_int($value)) {
                return '%d';  // Integer
            } elseif (is_float($value)) {
                return '%f';  // Float
            } else {
                return '%s';  // String
            }
        }, $datainsert);
    
        // Menggunakan $wpdb->insert() untuk keamanan dan efisiensi
        $result = $wpdb->insert($table, $datainsert, $format);
    
        return $result;
    }
    
    
    function updateData($invoice, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';
    
        // Pastikan bahwa invoice dan status adalah valid
        if (empty($invoice) || empty($status)) {
            return false;  // Menghindari update jika data tidak valid
        }
    
        // Data yang akan diupdate
        $data = [
            'process_type' => $status,  // Kolom yang akan diubah
        ];
    
        // Format data berdasarkan tipe
        $format = [
            '%s',  // Format untuk status (string)
        ];
    
        // Kondisi WHERE untuk pencarian berdasarkan invoice_number
        $where = [
            'invoice_number' => $invoice,
        ];
    
        // Format untuk kondisi WHERE
        $where_format = [
            '%s',  // Format untuk invoice_number (string)
        ];
    
        // Melakukan update dengan $wpdb->update()
        $result = $wpdb->update($table, $data, $where, $format, $where_format);
    
        return $result;
    }

    function checkTrx($order_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';
    
        // Pastikan input valid
        if (empty($order_id) || empty($amount)) {
            return false;
        }
    
        // Gunakan get_row() untuk mengambil satu baris data
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE invoice_number = %s AND amount = %d ORDER BY trx_id DESC LIMIT 1",
                $order_id, $amount
            )
        );
    }
    

    function checkStatusTrx($order_id, $amount, $processType) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';
    
        // Pastikan input valid
        if (empty($order_id) || empty($amount) || empty($processType)) {
            return false;
        }
    
        // Gunakan get_var() untuk mengambil satu nilai dari query
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT payment_code FROM $table WHERE invoice_number = %s AND amount = %d AND process_type = %s ORDER BY trx_id DESC LIMIT 1",
                $order_id, $amount, $processType
            )
        );
    }
    
}
