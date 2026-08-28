<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckoutService.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckStatusService.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulDb.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuCheckoutModule extends WC_Payment_Gateway
{
    public $method_name;
    public $method_code;
    public $checkout_msg;
    public $environmentPaymentJokul;
    public $sandboxClientId;
    public $sandboxSharedKey;
    public $prodClientId;
    public $prodSharedKey;
    public $expiredTime;
    public $emailNotifications;
    public $abandonedCart;
    public $timeRangeAbandonedCart;
    public $customExpireDate;
    public $channelName;
    public $payment_method;
    public $auto_redirect_jokul;
    public $sac_check;
    public $sac_textbox;
    public $paymentDescription;
    public $dokuUtils;
    public $dokuDB;
    public $dokuCheckStatusService;
    public $dokuCheckoutService;
    public $orderId;

    public function __construct()
    {
        $this->init_form_fields();
        $this->id                   = 'doku_checkout';
        $this->has_fields           = true;
        $this->method_name          = 'DOKU Checkout';
        $this->method_code          = 'JOKUL_CHECKOUT';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_name;
        $this->method_title         = __('DOKU Payment', 'doku-payment');
        $this->method_description   = sprintf(__('Customize how DOKU payment methods appear to your customers at checkout, including payment labels and QRIS configuration.', 'doku-payment'));
        $this->checkout_msg         = 'This your payment on DOKU Checkout : ';

        $this->init_settings();
        $mainSettings = get_option('woocommerce_doku_gateway_settings');
        $this->environmentPaymentJokul = $mainSettings['environment_payment_jokul'];
        $this->sandboxClientId = $mainSettings['sandbox_client_id'];
        $this->sandboxSharedKey = $mainSettings['sandbox_shared_key'];
        $this->prodClientId = $mainSettings['prod_client_id'];
        $this->prodSharedKey = $mainSettings['prod_shared_key'];
        $this->expiredTime = $mainSettings['expired_time'];
        $this->emailNotifications = $mainSettings['email_notifications'];
        $this->abandonedCart =  $mainSettings['abandoned_cart'];
        $this->timeRangeAbandonedCart =  $mainSettings['time_range_abandoned_cart'];
        $this->customExpireDate =  $mainSettings['custom_time_range_abandoned_cart'];

        $this->enabled = $this->get_option('enabled');
        $this->channelName = $this->get_option('channel_name');
        $paymentDescription = $this->get_option('payment_description');

        $this->payment_method = $this->get_option('payment_method');
        $this->auto_redirect_jokul = $this->get_option('auto_redirect_jokul');
        
        $this->sac_check = $mainSettings['sac_check' ];
        $this->sac_textbox = $mainSettings['sac_textbox'];

        if (empty($paymentDescription)) {
            $this->paymentDescription   = 'Bayar Pesanan Dengan DOKU Checkout';
        } else {
            $this->paymentDescription = $paymentDescription;
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        $queryArray = explode("&", sanitize_text_field($_SERVER['QUERY_STRING']));
        if (WC()->session != null) {
            $chosen_payment_method = WC()->session->get('chosen_payment_method');
            if ($this->id == 'doku_checkout') {
                if (in_array("jokul=show", $queryArray)) {
                    add_filter('the_title', array($this, 'woo_title_order_pending'));
                    add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page_pending'), 1, 10);
                } else {
                    add_filter('the_title', array($this, 'woo_title_order_received'));
                }
            }
        }
        if ( is_admin() ) {
            if ( ! has_action( 'woocommerce_admin_order_data_after_order_details', array( 'DokuCheckoutModule', 'doku_add_check_status_button' ) ) ) {
                add_action( 'woocommerce_admin_order_data_after_order_details', array( 'DokuCheckoutModule', 'doku_add_check_status_button' ) );
            }
            if ( ! has_action( 'wp_ajax_doku_check_status', array( 'DokuCheckoutModule', 'doku_handle_ajax_check_status' ) ) ) {
                add_action( 'wp_ajax_doku_check_status', array( 'DokuCheckoutModule', 'doku_handle_ajax_check_status' ) );
            }
            if ( ! has_action( 'woocommerce_admin_order_data_after_billing_address', array( 'DokuCheckoutModule', 'doku_display_payment_status_in_billing' ) ) ) {
                add_action( 'woocommerce_admin_order_data_after_billing_address', array( 'DokuCheckoutModule', 'doku_display_payment_status_in_billing' ) );
            }
        }

    }

    function calculateMinutes($abandonedCart, $timeRangeAbandonedCart, $customExpireDate) {  
        $minutes = 0; 
      
        if ($abandonedCart === 'yes') {  
            if ($timeRangeAbandonedCart !== 'Custom') {  
                switch ($timeRangeAbandonedCart) {  
                    case 'Tomorrow':  
                        $minutes = 1440; 
                        break;  
                    case '7':
                    case '7 day':  
                        $minutes = 10080;
                        break;  
                    case '14':
                    case '14 day':  
                        $minutes = 20160; 
                        break;  
                    case '30':
                    case '30 day':  
                        $minutes = 43200; 
                        break;  
                    default:  
                        $minutes = 0;  
                        break;  
                }  
            } else {  
                $customDays = max(1, min(30, intval($customExpireDate))); 
                $minutes = $customDays * 1440;   
            }  
        }  
      
        return $minutes;
    }

    public function get_order_data($order)
    {
        $pattern = "/[^A-Za-z0-9? .,_-]/";
        $order_id = $order->get_id();
        $dp = wc_get_price_decimals();
        $order_data = array();
        // add line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $term_names = wp_get_post_terms( $item->get_product_id(), 'product_cat', array('fields' => 'names') );
            $categories_string = implode(',', $term_names);
            $product_id = null;
            $product_sku = null;
            $image_url = null;
            $product_url = null;

            // Check if the product exists.
            if (is_object($product)) {
                $product_id = $product->get_id();
                $product_sku = $product->get_sku();
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            
            $order_data[] = array(
                'id' => $product_id,
                'price' => wc_format_decimal($order->get_item_total($item, false, false), $dp), 
                'quantity' => wc_stock_amount($item['qty']), 
                'name' => preg_replace($pattern, "", $item['name']), 
                'sku' => !empty($product_sku) ? $product_sku : $product_id, 
                'type' => 'produk',
                'category' => 'marketplace', 
                'image_url' =>  !empty($image_url) ? $image_url : '',
                'url' => $product_url
            );
        }
        // Add shipping.
        foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
            $product = isset($item) && is_object($item) ? $item->get_product() : null;
            $image_url = null;
            $product_url = null;

            // Check if the product exists.
            if (is_object($product)) {
                $product_id = $product->get_id();
                $product_sku = $product->get_sku();
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            if (wc_format_decimal($shipping_item['cost'], $dp) > 0) {
                $order_data[] = array(
                    'id' => 'shipping',
                    'name' => preg_replace($pattern, "", $shipping_item['name']), 
                    'price' => wc_format_decimal($shipping_item['cost'], $dp), 
                    'quantity' => 1,
                    'sku' => 'shipping', 
                    'type' => 'produk',
                    'category' => 'fee', 
                    'image_url' =>  !empty($image_url) ? $image_url : '',
                    'url' => $product_url
                );
            }
        }
        // Add taxes.
        foreach ($order->get_tax_totals() as $tax_code => $tax) {
            $product = isset($item) && is_object($item) ? $item->get_product() : null;
            $image_url = null;
            $product_url = null;

            if (is_object($product)) {
                $product_id = $product->get_id();
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            if (wc_format_decimal($tax->amount, $dp) > 0) {
                $order_data[] = array(
                    'id' => 'tax-' . $product_id . '-' . preg_replace($pattern, "", $tax->label), 
                    'name' => preg_replace($pattern, "", $tax->label), 
                    'price' => wc_format_decimal($tax->amount, $dp), 
                    'quantity' => 1, 
                    'type' => 'produk',
                    'sku' => 'tax-' . $product_id . '-' . preg_replace($pattern, "", $tax->label), 
                    'category' => 'fee',
                    'image_url' =>  !empty($image_url) ? $image_url : '',
                    'url' => $product_url 
                );
            }
        }
        // Add fees.
        foreach ($order->get_fees() as $fee_item_id => $fee_item) {
            $product = isset($item) && is_object($item) ? $item->get_product() : null;
            $image_url = null;
            $product_url = null;

            if (is_object($product)) {
                $product_id = $product->get_id();
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            $fee_name = isset($fee_item['name']) ? $fee_item['name'] : '';
            $order_data[] = array(
                    'id' => 'fee-' . $product_id . '-' . preg_replace($pattern, "", $fee_name),
                    'name' => preg_replace($pattern, "", $fee_name), 
                    'price' => wc_format_decimal($order->get_line_total($fee_item), $dp), 
                    'quantity' => 1, 
                    'type' => 'produk',
                    'sku' => 'fee-' . $product_id . '-' . preg_replace($pattern, "", $fee_name), 
                    'category' => 'fee',
                    'image_url' =>  !empty($image_url) ? $image_url : '',
                    'url' => $product_url 
                );
        }
        // woocommerce_cli_order_data is a WooCommerce core hook, used here to filter order data.
        // This hook name is not created or defined by this plugin and cant be modified.
        $order_data = apply_filters('woocommerce_cli_order_data', $order_data);
        return $order_data;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;
        $pattern = "/[^A-Za-z0-9? .-\/+,=_:@]/";
        
        $order  = wc_get_order($order_id);
        $amount = $order->get_total();
        $order_data = $order->get_data();
        
        $this->dokuUtils = new DokuUtils();
        $formattedPhoneNumber = $this->dokuUtils->formatPhoneNumber($order->get_billing_phone());

        $params = array(
            'customerId' => 0 !== $order->get_customer_id() ? $order->get_customer_id() : null,
            'customerEmail' => $order->get_billing_email(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' =>  $order->get_billing_last_name(),
            'customerName' => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
            'amount' => $amount,
            'invoiceNumber' => $order->get_order_number(),
            'expiryTime' => $this->expiredTime,
            'phone' => $formattedPhoneNumber,
            'country' => $order->get_billing_country(),
            'address' => preg_replace($pattern, "", $order->get_shipping_address_1()),
            'itemQty' => $this->get_order_data($order),
            'payment_method' => $this->payment_method,
            'postcode' => $order_data['billing']['postcode'],
            'state' => $order_data['billing']['state'],
            'city' => $order_data['billing']['city'],
            'info1' => '',
            'info2' => '',
            'info3' => '',
            'woo_version' => $woocommerce->version,
            'reusableStatus' => false,
            'callback_url_result' => $this->get_return_url($order) . '&' . $order_id,
            'sac_check' => $this->sac_check,
            'auto_redirect' => $this->auto_redirect_jokul,
            'sac_textbox' => $this->sac_textbox,
            'first_name_shipping' => $order->get_shipping_first_name(),
            'address_shipping' => preg_replace($pattern, "", $order->get_shipping_address_1()),
            'city_shipping' => $order->get_shipping_city(),
            'postal_code_shipping' => $order->get_shipping_postcode(),
            'recoverAbandonedCart' => ($this->abandonedCart === 'yes'),
            'expiredRecoveredCart' => $this->calculateMinutes($this->abandonedCart, $this->timeRangeAbandonedCart, $this->customExpireDate)
        );

        if ($this->environmentPaymentJokul == 'false') {
            $clientId = $this->sandboxClientId;
            $sharedKey = $this->sandboxSharedKey;
        } else if ($this->environmentPaymentJokul == 'true') {
            $clientId = $this->prodClientId;
            $sharedKey = $this->prodSharedKey;
        }

        $config = array(
            'client_id' => $clientId,
            'shared_key' => $sharedKey,
            'environment' => $this->environmentPaymentJokul
        );
        
        update_post_meta($order_id, 'checkoutParams', $params);
        update_post_meta($order_id, 'checkoutConfig', $config); 

        $this->dokuCheckoutService = new DokuCheckoutService();
        $response = $this->dokuCheckoutService->generated($config, $params);
        if (!is_wp_error($response)) {
            if (isset($response['message']) && is_array($response['message']) && isset($response['message'][0]) && $response['message'][0] == "SUCCESS" && isset($response['response']['payment']['url'])) {
                update_post_meta($order_id, 'checkoutUrl', $response['response']['payment']['url']);
                $resultDb = DokuCheckoutModule::addDb($response, $amount);
                if($resultDb === false || $resultDb === 0){
                    http_response_code(500);
                    echo esc_html(http_response_code());
                    wc_add_notice('Cant be proceed into checkout page. Please try again.', 'error');
                }
                $this->orderId = $order_id;
                return array(
                    'result' => 'success',
                    'redirect' => $response['response']['payment']['url']
                );
            } else {
                $error_msg = 'Unknown error';
                if (isset($response['error']['message']) && is_string($response['error']['message'])) {
                    $error_msg = $response['error']['message'];
                } elseif (isset($response['message'])) {
                    if (is_array($response['message']) && !empty($response['message'][0])) {
                        $error_msg = is_string($response['message'][0]) ? $response['message'][0] : json_encode($response['message'][0]);
                    } elseif (is_string($response['message'])) {
                        $error_msg = $response['message'];
                    }
                } elseif (isset($response['error']) && is_string($response['error'])) {
                    $error_msg = $response['error'];
                }
                wc_add_notice('There is something wrong. Please try again. ' . $error_msg, 'error');
                return array(
                    'result' => 'failure',
                    'redirect' => ''
                );
            }
        } else {
            wc_add_notice('There is something wrong. Please try again.', 'error');
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = require(DOKU_PAYMENT_PLUGIN_PATH . '/Form/JokulCheckoutSetting.php');
    }

    public function process_admin_options()
    {
        $this->init_settings();

        $post_data = $this->get_post_data();

        foreach ($this->get_form_fields() as $key => $field) {
            if ('title' !== $this->get_field_type($field)) {
                try {
                    $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
        }

        if (!isset($post_data['woocommerce_' . $this->id . '_enabled']) && $this->get_option_key() == 'woocommerce_' . $this->id . '_settings') {
            $this->settings['enabled'] = $this->enabled;
        }

        if (isset($post_data['woocommerce_' . $this->id . '_secret_key']) || isset($post_data['woocommerce_' . $this->id . '_secret_key_dev'])) {
            delete_transient('main_settings_jokul_pg');
        }
        // woocommerce_settings_api_sanitized_fields_ is a WooCommerce core hook, do not modify its name
        // This hook name is not created or defined by this plugin and cant be modified.
        return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    public function admin_options()
    {
        wp_enqueue_script(
            'admin-options-module',
            plugin_dir_url(__FILE__) . '../Js/admin-options-module.js',
            ['jquery'],
            '1.0.0',
            true
        );
    
        wp_localize_script('admin-options-module', 'woocommerceData', [
            'id' => $this->id,
            'title' => $this->title
        ]);
    
        ?>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }
    

    public function payment_fields()
    {
        if ($this->paymentDescription) {
            echo esc_html($this->paymentDescription);
        }
    }

    public function addDb($response, $amount)
    {
        $this->dokuUtils = new DokuUtils();
        $getIp = $this->dokuUtils->getIpaddress();
        $trx = array();
        $trx['invoice_number']          = $response['response']['order']['invoice_number'];
        $trx['result_msg']              = $response['message'][0];
        $trx['process_type']            = 'PAYMENT_PENDING';
        $trx['raw_post_data']           = json_encode($response);
        $trx['ip_address']              = $getIp;
        $trx['amount']                  = $amount;
        $trx['payment_channel']         = $this->method_code;
        $trx['payment_code']            = "";
        $trx['doku_payment_datetime']   = gmdate("Y-m-d H:i:s");
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");
        $trx['message']                 = "Payment Pending message come from Jokul. Success : completed";
        

        $this->dokuDB = new DokuDB();
        return $this->dokuDB->addData($trx);
    }

    public function thank_you_page_pending($order_id)
    {
        $jokulCheckoutURL = get_post_meta($order_id, 'checkoutUrl', true);
        if (!$jokulCheckoutURL) {
            return;
        }

        header('Location: ' . $jokulCheckoutURL);
        die(); 
    }

    function woo_title_order_pending($title)
    {
        if ($title === 'Order received') {
            return "Payment Pending";
        } else {
            return $title;
        }
    }
    
    function woo_title_order_received($title)
    {
        global $woocommerce;

        if (function_exists('is_order_received_page') && is_order_received_page() && $title === 'Order received') {
            global $wp;
            $order_id = absint($wp->query_vars['order-received']);
            $order  = wc_get_order($order_id);
            
            if (!$order || $order->get_payment_method() !== 'doku_checkout') {
                return $title;
            }

            $woocommerce->cart->empty_cart();
            wc_reduce_stock_levels($order->get_id());

            $paramsValue       = get_post_meta($order->get_id(), 'checkoutParams', true);
            $configValue       = get_post_meta($order->get_id(), 'checkoutConfig', true);

            if (is_array($paramsValue) && is_array($configValue)) {
                $this->dokuCheckStatusService = new DokuCheckStatusService();
                $response = $this->dokuCheckStatusService->generated($configValue, $paramsValue);

                if (!is_wp_error($response) && is_array($response)) {
                    if (isset($response['acquirer']['id']) && strtolower($response['acquirer']['id']) == strtolower('OVO')) {
                        $dokuUtils = new DokuUtils();
                        $dokuDB = new DokuDB();
                        $dokuUtils->doku_log($dokuUtils, 'Jokul Acquirer : ' . $response['acquirer']['id'], $paramsValue['invoiceNumber']);
                        if (isset($response['transaction']['status']) && strtolower($response['transaction']['status']) == strtolower('SUCCESS')) {
                            $dokuDB->updateData($paramsValue['invoiceNumber'], $response['transaction']['status']);
                            $order = wc_get_order($paramsValue['invoiceNumber']);
                            $order->update_status('processing');
                            $order->payment_complete();
                            $dokuUtils->doku_log($dokuUtils, 'DOKU Check Status Update Status : ' . 'processing', $paramsValue['invoiceNumber']);
                        } else {
                            $dokuDB->updateData($paramsValue['invoiceNumber'], $response['transaction']['status'] ?? 'FAILED');
                            $order = wc_get_order($paramsValue['invoiceNumber']);
                            $order->update_status('failed');
                            $dokuUtils->doku_log($dokuUtils, 'DOKU Check Status Update Status : ' . 'failed', $paramsValue['invoiceNumber']);
                        }
                    }
                }
            }

            return "Order Received";
        } else {
            return $title;
        }
    }

    public static function doku_add_check_status_button( $order ) {
        if ( $order->get_payment_method() === 'doku_checkout' && $order->has_status( array( 'pending', 'on-hold', 'cancelled' ) ) ) {
            wp_enqueue_script(
                'doku-admin-check-status', 
                plugin_dir_url(__FILE__) . '../Js/doku-admin-check-status.js', 
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('doku-admin-check-status', 'dokuAdminCheckStatusData', array(
                'ajaxurl'  => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce( "doku_check_status_nonce" ),
                'order_id' => $order->get_id()
            ));
            ?>
            <div class="form-field form-field-wide" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px; clear: both;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" id="doku-check-status-btn" class="button button-primary button-large">
                        <?php _e( 'Check Payment Status', 'doku-payment' ); ?>
                    </button>
                    <span id="doku-status-spinner" class="spinner" style="float: none; margin: 0; vertical-align: middle;"></span>
                </div>
                <div style="margin-top: 10px; min-height: 20px;">
                    <div id="doku-status-feedback" style="font-weight: bold; font-size: 13px; display: inline-block;"></div>
                </div>
            </div>
            <?php
        }
    }

    public static function doku_handle_ajax_check_status() {
        check_ajax_referer( 'doku_check_status_nonce', 'security' );

        if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
        
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => 'Invalid Order ID.' ) );
        }
        
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Order not found.' ) );
        }
        
        $paramsValue = get_post_meta( $order_id, 'checkoutParams', true );
        $configValue = get_post_meta( $order_id, 'checkoutConfig', true );
        
        if ( ! is_array( $paramsValue ) || ! is_array( $configValue ) ) {
            wp_send_json_error( array( 'message' => 'DOKU parameters/config not found for this order.' ) );
        }
        
        require_once( DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckStatusService.php' );
        $checkStatusService = new DokuCheckStatusService();
        $response = $checkStatusService->generated( $configValue, $paramsValue );
        
        $dokuUtils = new DokuUtils();
        $dokuDB = new DokuDB();
        
        // Log to debug.log and doku_log (Dual Logging)
        error_log('Manual Check Status Request for Invoice: ' . $paramsValue['invoiceNumber']);
        $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS START =====', $paramsValue['invoiceNumber']);
        $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status Request config: ' . json_encode( $configValue ), $paramsValue['invoiceNumber']);
        
        if ( is_wp_error( $response ) || ! is_array( $response ) ) {
            error_log('Manual Check Status Error for Invoice: ' . $paramsValue['invoiceNumber'] . ' - Connection failed');
            $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status Error: Connection failed or invalid response format', $paramsValue['invoiceNumber']);
            $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END - HTTP 500 =====', $paramsValue['invoiceNumber']);
            wp_send_json_error( array( 'message' => 'Failed to connect to DOKU API.' ) );
        }
        
        error_log('Manual Check Status Response for Invoice: ' . $paramsValue['invoiceNumber'] . ' - Status: ' . ($response['transaction']['status'] ?? 'UNKNOWN'));
        $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status Response: ' . json_encode( $response, JSON_PRETTY_PRINT ), $paramsValue['invoiceNumber']);
        
        if ( ! isset( $response['transaction'] ) ) {
            $error_detail = 'Invalid API Response';
            if ( isset( $response['error']['message'] ) ) {
                $error_detail = $response['error']['message'];
            } elseif ( isset( $response['message'] ) ) {
                $error_detail = $response['message'];
            }
            
            $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status API/Credential Error. Status remains unchanged. Response: ' . json_encode( $response ), $paramsValue['invoiceNumber']);
            $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END =====', $paramsValue['invoiceNumber']);
            
            wp_send_json_error( array( 
                'message' => 'API Error: ' . $error_detail,
                'should_reload' => false
            ) );
        }
        
        if ( isset( $response['transaction']['status'] ) && strtolower( $response['transaction']['status'] ) == strtolower( 'SUCCESS' ) ) {
            // Update database jokuldb
            $dokuDB->updateData( $paramsValue['invoiceNumber'], 'PAYMENT_COMPLETED' );
            
            // Complete order in WooCommerce
            $order->update_status( 'processing' );
            $order->payment_complete();
            $order->add_order_note( __( 'DOKU: Manual Check Status succeeded. Payment marked as completed.', 'doku-payment' ) );
            
            $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status SUCCESS. Order updated to processing.', $paramsValue['invoiceNumber']);
            $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END - HTTP 200 =====', $paramsValue['invoiceNumber']);
            wp_send_json_success( array( 'message' => 'Payment SUCCESS! Order status updated to Processing.' ) );
        } else {
            $status = isset( $response['transaction']['status'] ) ? $response['transaction']['status'] : 'UNKNOWN';
            
            if ( strtolower( $status ) == 'failed' ) {
                // Update database jokuldb to failed
                $dokuDB->updateData( $paramsValue['invoiceNumber'], 'PAYMENT_FAILED' );
                
                // Fail order in WooCommerce
                $order->update_status( 'failed', __( 'DOKU: Manual Check Status detected failed payment. Status updated to Failed.', 'doku-payment' ) );
                
                $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status FAILED. Order updated to failed. Response: ' . json_encode( $response ), $paramsValue['invoiceNumber']);
                $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END - HTTP 200 =====', $paramsValue['invoiceNumber']);
                wp_send_json_error( array( 
                    'message' => 'Payment FAILED! Order status updated to Failed.',
                    'should_reload' => true
                ) );
            } elseif ( strtolower( $status ) == 'expired' ) {
                // Update database jokuldb to expired
                $dokuDB->updateData( $paramsValue['invoiceNumber'], 'PAYMENT_EXPIRED' );
                
                // Cancel order in WooCommerce
                if ( ! $order->has_status( 'cancelled' ) ) {
                    $order->update_status( 'cancelled', __( 'DOKU: Manual Check Status detected expired payment. Status updated to Cancelled.', 'doku-payment' ) );
                }
                
                $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status EXPIRED. Order updated to cancelled. Response: ' . json_encode( $response ), $paramsValue['invoiceNumber']);
                $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END - HTTP 200 =====', $paramsValue['invoiceNumber']);
                wp_send_json_error( array( 
                    'message' => 'Payment EXPIRED! Order status updated to Cancelled.',
                    'should_reload' => true
                ) );
            } else {
                $order->add_order_note( sprintf( __( 'DOKU: Manual Check Status returned status: %s.', 'doku-payment' ), $status ) );
                
                $dokuUtils->doku_log('DokuCheckoutModule', 'Manual Check Status result: ' . $status . ' - Response: ' . json_encode( $response ), $paramsValue['invoiceNumber']);
                $dokuUtils->doku_log('DokuCheckoutModule', '===== MANUAL CHECK STATUS END =====', $paramsValue['invoiceNumber']);
                wp_send_json_error( array( 
                    'message' => 'Payment status is currently: ' . $status,
                    'should_reload' => false
                ) );
            }
        }
    }

    public static function doku_display_payment_status_in_billing( $order ) {
        if ( $order->get_payment_method() === 'doku_checkout' ) {
            global $wpdb;
            $table = $wpdb->prefix . 'jokuldb';
            $trx_status = $wpdb->get_var( $wpdb->prepare( "SELECT process_type FROM $table WHERE invoice_number = %s ORDER BY trx_id DESC LIMIT 1", $order->get_id() ) );
            
            // Format status label and color
            $status_label = 'PENDING';
            $status_color = '#ecc715'; // Yellow/Orange for pending
            
            if ( $trx_status ) {
                if ( strpos( strtoupper( $trx_status ), 'COMPLETED' ) !== false || strtoupper( $trx_status ) === 'SUCCESS' ) {
                    $status_label = 'SUCCESS';
                    $status_color = '#46b450'; // Green for success
                } elseif ( strpos( strtoupper( $trx_status ), 'FAILED' ) !== false ) {
                    $status_label = 'FAILED';
                    $status_color = '#dc3232'; // Red for failed
                } elseif ( strpos( strtoupper( $trx_status ), 'EXPIRED' ) !== false ) {
                    $status_label = 'EXPIRED';
                    $status_color = '#767676'; // Dark Gray for expired
                } else {
                    $status_label = strtoupper( $trx_status );
                }
            }
            
            echo '<div style="margin-top: 15px; border-top: 1px dashed #ccc; padding-top: 10px;">';
            echo '<p><strong>DOKU Payment Status:</strong><br />';
            echo '<span style="display: inline-block; margin-top: 5px; font-weight: bold; color: #fff; background-color: ' . esc_attr( $status_color ) . '; padding: 4px 10px; border-radius: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html( $status_label ) . '</span></p>';
            echo '</div>';
        }
    }
}
