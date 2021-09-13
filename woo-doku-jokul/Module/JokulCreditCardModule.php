<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Service/JokulCreditCardService.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulDb.php');

class JokulCreditCardModule extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->init_form_fields();
        $this->id                   = 'jokul_creditcard';
        $this->has_fields           = true;
        $this->method_name          = 'Credit Card';
        $this->method_code          = 'CREDIT_CARD';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_name;
        $this->method_title         = __('Jokul', 'woocommerce-gateway-jokul');
        $this->method_description   = sprintf(__('Accept payment through various payment channels with Jokul. Make it easy for your customers to purchase on your store.', 'woocommerce'));
        $this->checkout_msg         = 'This your payment on Credit Card : ';

        $this->init_settings();
        $mainSettings = get_option('woocommerce_jokul_gateway_settings');
        $this->environmentPaymentJokul = $mainSettings['environment_payment_jokul'];
        $this->sandboxClientId = $mainSettings['sandbox_client_id'];
        $this->sandboxSharedKey = $mainSettings['sandbox_shared_key'];
        $this->prodClientId = $mainSettings['prod_client_id'];
        $this->prodSharedKey = $mainSettings['prod_shared_key'];
        $this->expiredTime = $mainSettings['expired_time'];
        $this->emailNotifications = $mainSettings['email_notifications'];

        $this->enabled = $this->get_option( 'enabled' );
        $this->channelName = $this->get_option('channel_name');
        $paymentDescription = $this->get_option('payment_description');

        $this->language = $this->get_option('language_payment_jokul');
        $this->backgroundColor = $this->get_option('payment_background_color');
        $this->fontColor = $this->get_option('payment_font_color');
        $this->buttonBackgroundColor = $this->get_option('payment_button_background_color');
        $this->buttonFontColor = $this->get_option('payment_button_font_color');

        if (empty($paymentDescription)){
            $this->paymentDescription   = 'Bayar Pesanan Dengan Credit Card';
        } else {
            $this->paymentDescription = $this->get_option('payment_description');
        }

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        if ($_SERVER['QUERY_STRING'] == 'cc=show') {
            add_filter( 'the_content', array($this, 'my_disruptive_filter'));
        }

        if ($_SERVER['QUERY_STRING'] == 'status=failed') {
            $chosen_payment_method = WC()->session->get('chosen_payment_method');
            if ($chosen_payment_method == 'jokul_creditcard') {
                wc_add_notice('Your payment with Credit Card is failed. Please Try again.', 'error');
            }
        }
    }

    function my_disruptive_filter($content) {
        ?>
        <script> 
            #align-center{ 
               display: block; 
               justify-content: center; 
             }
        </script>
        <?php
    
        $urlCC= get_post_meta('12', 'ccPageUrl', true);
    
        $url = "<div id='align_center'><iframe style='border:none' frameBorder='0' src=".$urlCC." title='Jokul Credit Card' height='350' width='100%'></iframe></div>";
    
        return $url;
    }

    public function process_payment($order_id)
    {
        global $woocommerce;

        $order  = wc_get_order($order_id);
        $amount = $order->order_total;
        $itemQty = array();
        
        foreach ($order->get_items() as $item_id => $item ) {
            $_product = wc_get_product($item->get_product_id());
            $Price = $_product->get_price();

            $itemQty[] = array('name' => $item->get_name(), 'price' => $Price, 'quantity' => $item->get_quantity());
        }

        $params = array(
            'customerId' => 0 !== $order->get_customer_id() ? $order->get_customer_id() : null,
            'customerEmail' => $order->get_billing_email(),
            'customerName' => $order->get_billing_first_name()." ".$order->get_billing_last_name(),
            'amount' => $amount,
            'invoiceNumber' => $order->get_order_number(),
            'expiryTime' => $this->expiredTime,
            'phone' => $order->billing_phone,
            'country' => $order->billing_country,
            'address' => $order->shipping_address_1,
            'itemQty' => $itemQty,
            'language' => $this->language,
            'backgroundColor' => $this->backgroundColor,
            'fontColor' => $this->fontColor,
            'buttonBackgroundColor' => $this->buttonBackgroundColor,
            'buttonFontColor' => $this->buttonFontColor,
            'info1' => '',
            'info2' => '',
            'info3' => '',
            'reusableStatus' => false,
            'urlSuccess' => $this->get_return_url($order),
            'urlFail' => wc_get_page_permalink('checkout')."?status=failed"
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

        $this->creditCardService = new JokulCreditCardService();
        $response = $this->creditCardService -> generated($config, $params);
        if( !is_wp_error( $response )) {
            if (!isset($response['error']['message']) && isset($response['credit_card_payment_page']['url'])) {
                update_post_meta('12', 'ccPageUrl', $response['credit_card_payment_page']['url']);
                JokulCreditCardModule::addDb($response, $amount);
                return array(
                    'result' => 'success',
                    'redirect' => wc_get_page_permalink('checkout')."?cc=show"
                );
            } else {
                wc_add_notice('There is something wrong. Please try again.', 'error');
            }
        } else {
            wc_add_notice('There is something wrong. Please try again.', 'error');
        }
    }

    public function init_form_fields() {
		$this->form_fields = require(DOKU_JOKUL_PLUGIN_PATH . '/Form/JokulCreditCardSetting.php' );
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

        return update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    public function admin_options()
    {
        ?>
        <script>
            jQuery(document).ready(function($) {
                $('.channel-name-format').text('<?=$this->title;?>');
                $('#woocommerce_<?=$this->id;?>_channel_name').change(
                    function() {
                        $('.channel-name-format').text($(this).val());
                    }
                );

                var isSubmitCheckDone = false;

                $("button[name='save']").on('click', function(e) {
                    if (isSubmitCheckDone) {
                        isSubmitCheckDone = false;
                        return;
                    }

                    e.preventDefault();

                    var paymentDescription = $('#woocommerce_<?=$this->id;?>_payment_description').val();
                    if (paymentDescription.length > 250) {
                        return swal({
                            text: 'Text is too long, please reduce the message and ensure that the length of the character is less than 250.',
                            buttons: {
                                cancel: 'Cancel'
                            }
                        });
                    } else {
                        isSubmitCheckDone = true;
                    }

                    $("button[name='save']").trigger('click');
                });
            });
        </script>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    public function payment_fields() 
    {
        if ($this->paymentDescription) {
            echo wpautop(wp_kses_post($this->paymentDescription));
        }
    }

    public function addDb($response, $amount) 
    {
        $this->jokulUtils = new JokulUtils();
        $getIp = $this->jokulUtils -> getIpaddress();

        $trx = array();
		$trx['invoice_number']          = $response['order']['invoice_number'];
		$trx['result_msg']              = null;
        $trx['process_type']            = 'PAYMENT_PENDING';  
        $trx['raw_post_data']           = file_get_contents('php://input');
        $trx['ip_address']              = $getIp;
		$trx['amount']                  = $amount;
		$trx['payment_channel']         = $this->method_code;
		$trx['payment_code']            = null;
		$trx['doku_payment_datetime']   = gmdate("Y-m-d H:i:s");
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");       
        $trx['message']                 = "Payment Pending message come from Jokul. Success : completed";   
                
        $this->jokulDb = new JokulDb();
        $this->jokulDb -> addData($trx);
    }
}
?>
