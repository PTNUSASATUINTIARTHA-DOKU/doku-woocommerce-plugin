<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckoutService.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Service/JokulCheckStatusService.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulDb.php');
require_once(DOKU_PAYMENT_PLUGIN_PATH . '/Common/JokulUtils.php');

class DokuCheckoutModule extends WC_Payment_Gateway
{
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

    }

    function calculateMinutes($abandonedCart, $timeRangeAbandonedCart, $customExpireDate) {  
        $minutes = 0; 
      
        if ($abandonedCart === 'yes') {  
            if ($timeRangeAbandonedCart !== 'Custom') {  
                switch ($timeRangeAbandonedCart) {  
                    case 'Tomorrow':  
                        $minutes = 1440; 
                        break;  
                    case '7 day':  
                        $minutes = 10080;
                        break;  
                    case '14 day':  
                        $minutes = 20160; 
                        break;  
                    case '30 day':  
                        $minutes = 43200; 
                        break;  
                    default:  
                        $minutes = 0;  
                        break;  
                }  
            } else {  
                $customDays = intval($customExpireDate); 
                $minutes = $customDays * 1440;   
            }  
        }  
      
        return $minutes;
    }

    public function get_order_data($order)
    {
        $pattern = "/[^A-Za-z0-9? .,_-]/";
        $order_post = get_post($order->id);
        $dp = wc_get_price_decimals();
        $order_data = array();
        // add line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $order->get_product_from_item($item);
            $term_names = wp_get_post_terms( $item->get_product_id(), 'product_cat', array('fields' => 'names') );
            $categories_string = implode(',', $term_names);
            $product_id = null;
            $product_sku = null;
            $image_url = null;
            $product_url = null;

            // Check if the product exists.
            if (is_object($product)) {
                $product_id = isset($product->variation_id) ? $product->variation_id : $product->id;
                $product_sku = $product->get_sku();
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            $meta = new WC_Order_Item_Meta($item, $product);
            $item_meta = array();
            foreach ($meta->get_formatted(null) as $meta_key => $formatted_meta) {
                $item_meta[] = array('key' => $meta_key, 'label' => $formatted_meta['label'], 'value' => $formatted_meta['value']);
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
            $product = $order->get_product_from_item($item);
            $image_url = null;
            $product_url = null;

            // Check if the product exists.
            if (is_object($product)) {
                $product_id = isset($product->variation_id) ? $product->variation_id : $product->id;
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
            $product = $order->get_product_from_item($item);
            $image_url = null;
            $product_url = null;

            if (is_object($product)) {
                $product_id = isset($product->variation_id) ? $product->variation_id : $product->id;
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
            $product = $order->get_product_from_item($item);
            $image_url = null;
            $product_url = null;

            if (is_object($product)) {
                $product_id = isset($product->variation_id) ? $product->variation_id : $product->id;
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                $product_url = $product->get_permalink();
            }
            $order_data[] = array(
                    'id' => 'fee-' . $product_id . '-' . preg_replace($pattern, "", $tax->label),
                    'name' => preg_replace($pattern, "", $fee_item['name']), 
                    'price' => wc_format_decimal($order->get_line_total($fee_item), $dp), 
                    'quantity' => 1, 
                    'type' => 'produk',
                    'sku' => 'fee-' . $product_id . '-' . preg_replace($pattern, "", $tax->label), 
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
        $amount = $order->order_total;
        $order_data = $order->get_data();
        
        $this->dokuUtils = new DokuUtils();
        $formattedPhoneNumber = $this->dokuUtils->formatPhoneNumber($order->billing_phone);

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
            'country' => $order->billing_country,
            'address' => preg_replace($pattern, "", $order->shipping_address_1),
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
            'callback_url' => $this->get_return_url($order) . '&' . $order_id,
            'sac_check' => $this->sac_check,
            'auto_redirect' => $this->auto_redirect_jokul,
            'sac_textbox' => $this->sac_textbox,
            'first_name_shipping' => $order->shipping_first_name,
            'address_shipping' => preg_replace($pattern, "",$order->shipping_address_1),
            'city_shipping' => $order->shipping_city,
            'postal_code_shipping' => $order->shipping_postcode,
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
            if ($response['message'][0] == "SUCCESS" && isset($response['response']['payment']['url'])) {
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
                    'redirect' => $this->get_return_url($order) . "&jokul=show&" . $order_id
                );
            } else {
                wc_add_notice('There is something wrong. Please try again. ' . $response['message'][0], 'error');
            }
        } else {
            wc_add_notice('There is something wrong. Please try again.', 'error');
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
            
            $woocommerce->cart->empty_cart();
            wc_reduce_stock_levels($order->get_id());

            $paramsValue       = get_post_meta($order->get_id(), 'checkoutParams', true);
            $configValue       = get_post_meta($order->get_id(), 'checkoutConfig', true);

            $this->dokuCheckStatusService = new DokuCheckStatusService();
            $response = $this->dokuCheckStatusService->generated($configValue, $paramsValue);

            if (!is_wp_error($response)) {
                if (strtolower($response['acquirer']['id']) == strtolower('OVO')) {
                    $dokuUtils = new DokuUtils();
                    $dokuDB = new DokuDB();
                    $dokuUtils->doku_log($dokuUtils, 'Jokul Acquirer : ' . $response['acquirer']['id'], $paramsValue['invoiceNumber']);
                    if (strtolower($response['transaction']['status']) == strtolower('SUCCESS')) {
                        $dokuDB->updateData($paramsValue['invoiceNumber'], $response['transaction']['status']);
                        $order = wc_get_order($paramsValue['invoiceNumber']);
                        $order->update_status('processing');
                        $order->payment_complete();
                        $dokuUtils->doku_log($dokuUtils, 'DOKU Check Status Update Status : ' . 'processing', $paramsValue['invoiceNumber']);
                    } else {
                        $dokuDB->updateData($paramsValue['invoiceNumber'], $response['transaction']['status']);
                        $order = wc_get_order($paramsValue['invoiceNumber']);
                        $order->update_status('failed');
                        $dokuUtils->doku_log($dokuUtils, 'DOKU Check Status Update Status : ' . 'failed', $paramsValue['invoiceNumber']);
                    }
                }
            }

            return "Order Received";
        } else {
            return $title;
        }
    }
}

