<?php

class JokulDb {

    function addData($datainsert) 
    {
        global $wpdb;
        $SQL = "";
                            
        foreach ( $datainsert as $field_name=>$field_data )
        {
            $SQL .= " $field_name = '$field_data',";
        }
        $SQL = substr( $SQL, 0, -1 );
                    
        $wpdb->query("INSERT INTO ".$wpdb->prefix."jokuldb SET $SQL");
    } 

    function checkTrx($order_id, $amount, $vaNumber)
    {
        global $wpdb;
        $db_prefix = $wpdb->prefix;

        $query="SELECT * FROM ".$db_prefix."jokuldb where invoice_number='".$order_id."' and amount='".$amount."' ORDER BY trx_id DESC LIMIT 1";
        $result = $wpdb->get_var($query);

        return $result;
    }

    function checkStatusTrx($order_id, $amount, $vaNumber, $processType)
    {
        global $wpdb;
        $db_prefix = $wpdb->prefix;
        $query="SELECT payment_code FROM ".$db_prefix."jokuldb where invoice_number='".$order_id."' and amount='".$amount."' and process_type = 'PAYMENT_COMPLETED' ORDER BY trx_id DESC LIMIT 1";
        $result = $wpdb->get_var($query);
        return $result;
    }
}
?>
