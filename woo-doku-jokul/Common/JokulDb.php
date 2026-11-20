<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuDB {
    private $dokuUtils;

    public function __construct() {
        $this->dokuUtils = new DokuUtils();
    }

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
        
        try {
            $result = $wpdb->insert($table, $datainsert, $format);
            if ($result === false) {
                $this->dokuUtils->doku_log($this, 'Database Insertion Error occurred.', $datainsert['invoice_number'] ?? 'unknown');
            }
            return $result;
        } catch (Exception $e) {
            $this->dokuUtils->doku_log($this, 'Database Insertion Exception occurred.', $datainsert['invoice_number'] ?? 'unknown');
            return false;
        }
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
    
        // Log input updates for tracing
        $this->dokuUtils->doku_log($this, 'Database Update for Invoice: ' . (string) $invoice . ' - Status: ' . (string) $status, $invoice);

        // Melakukan update dengan $wpdb->update()
        try {
            $result = $wpdb->update($table, $data, $where, $format, $where_format);
            if ($result === false) {
                $this->dokuUtils->doku_log($this, 'Database Update Error occurred.', $invoice);
            }
            return $result;
        } catch (Exception $e) {
            $this->dokuUtils->doku_log($this, 'Database Update Exception occurred.', $invoice);
            return false;
        }
    }

    public function getLatestProcessType($invoice_number) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';

        if ($invoice_number === null) {
            return null;
        }

        $invoice_number = (string) $invoice_number;
        if ($invoice_number === '') {
            return null;
        }

        return $wpdb->get_var($wpdb->prepare(
            "SELECT process_type FROM $table WHERE invoice_number = %s ORDER BY trx_id DESC LIMIT 1",
            $invoice_number
        ));
    }

    function checkTrx($order_id, $amount) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';
    
        // Pastikan input valid
        if (empty($order_id) || empty($amount)) {
            return false;
        }
        
        try {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE invoice_number = %s AND amount = %d ORDER BY trx_id DESC LIMIT 1",
                    $order_id, $amount
                )
            );
        } catch (Exception $e) {
            $this->dokuUtils->doku_log($this, 'Database Query Error (checkTrx).', $order_id);
            return false;
        }
    }
    

    function checkStatusTrx($order_id, $amount, $processType) {
        global $wpdb;
        $table = $wpdb->prefix . 'jokuldb';
    
        // Pastikan input valid
        if (empty($order_id) || empty($amount) || empty($processType)) {
            return false;
        }
        try {
            // Gunakan process_type untuk deteksi status yang akurat
            return $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT process_type FROM $table WHERE invoice_number = %s AND amount = %d AND process_type = %s ORDER BY trx_id DESC LIMIT 1",
                    $order_id, $amount, $processType
                )
            );
        } catch (Exception $e) {
            $this->dokuUtils->doku_log($this, 'Database Query Error (checkStatusTrx).', $order_id);
            return false;
        }
    }
    
}
