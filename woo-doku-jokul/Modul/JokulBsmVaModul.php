<?php

require_once(DOKU_JOKUL_PLUGIN_PATH . '/Service/JokulBsmVaService.php');
require_once(DOKU_JOKUL_PLUGIN_PATH . '/Common/JokulDb.php');

class JokulBsmVaModul extends WC_Payment_Gateway
{
    public function __construct()
    {

        $this->init_form_fields();
        $this->id                   = 'jokul_bsmva';
        $this->has_fields           = true;
        $this->method_code          = 'BSM VA';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->method_title         = __('Jokul BSM VA', 'woocommerce-gateway-jokul');
        $this->method_description   = sprintf(__('Accept payment through various payment channels with Jokul. Make it easy for your customers to purchase on your store.', 'woocommerce'));
        $this->checkout_msg         = 'Please transfer your payment to this payment code / VA Number : ';

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
        if (empty($paymentDescription)){
            $this->paymentDescription   = 'Bayar Pesanan Dengan BSM VA';
        } else {
            $this->paymentDescription = $this->get_option('payment_description');
        }
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_'. $this->id, array($this, 'thank_you_page_bsm_va'), 1, 10);
    }

    public function init_form_fields() {
		$this->form_fields = require(DOKU_JOKUL_PLUGIN_PATH . '/Form/JokulBsmVaSetting.php' );
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

    public function payment_fields() {
        if ( $this->paymentDescription ) {
            echo wpautop( wp_kses_post( $this->paymentDescription ) );
        }
    }

    public function process_payment($order_id) {
        global $woocommerce;
        
        $order  = wc_get_order( $order_id );
        $amount = $order->order_total;
        $params = array(
            'customerEmail' => ($a = get_userdata($order->get_user_id() )) ? $a->user_email : '',
            'customerName' => $order->get_billing_first_name()." ".$order->get_billing_last_name(),
            'amount' => $amount,
            'invoiceNumber' => $order->get_order_number(),
            'expiryTime' => $this->expiredTime,
            'info1' => '',
            'info2' => '',
            'info3' => '',
            'reusableStatus' => false
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

        $this->bsmVaService = new JokulBsmVaService();
        $response = $this->bsmVaService -> generated($config, $params);
        if( !is_wp_error( $response ) ) {
            if ( !isset($response['error']['message']) && isset($response['virtual_account_info']['virtual_account_number']) ) {
//			    $order->payment_complete();
                $order->reduce_order_stock();
                
			    $order->add_order_note($this->checkout_msg.$response['virtual_account_info']['virtual_account_number'], true );
                $woocommerce->cart->empty_cart();

                update_post_meta($order_id, 'jokul_va_amount', $amount);
                update_post_meta($order_id, 'jokul_va_number', $response['virtual_account_info']['virtual_account_number']);
                update_post_meta($order_id, 'jokul_va_expired',$response['virtual_account_info']['expired_date']);
                update_post_meta($order_id, 'jokul_va_how_to_page',$response['virtual_account_info']['how_to_pay_page']);

                $order = wc_get_order($response['order']['invoice_number']);
                $order->update_status('pending');

                JokulBsmVaModul::addDb($response, $amount);

			    return array(
				    'result' => 'success',
				    'redirect' => $this->get_return_url( $order )
			    );
            } else {
                JokulBsmVaModul::addDb($response, $amount);
			    wc_add_notice(  'Please try again.', 'error' );
			    return;
		    }
        } else {
            JokulBsmVaModul::addDb($response, $amount);
            wc_add_notice('Connection error.', 'error' );
            return;
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
		$trx['payment_channel']         = 'BSM VA';
		$trx['payment_code']            = $response['virtual_account_info']['virtual_account_number'];
		$trx['doku_payment_datetime']   = $response['virtual_account_info']['expired_date'];
        $trx['process_datetime']        = gmdate("Y-m-d H:i:s");       
        $trx['message']                 = "Payment Pending message come from Jokul. Success : completed";   
                
        $this->jokulDb = new JokulDb();
        $this->jokulDb -> addData($trx);
    }

    public function thank_you_page_bsm_va($order_id)
    {
        $vaNumber       = get_post_meta($order_id, 'jokul_va_number', true);
        $vaExpired      = get_post_meta($order_id, 'jokul_va_expired', true);
        $vaAmount       = get_post_meta($order_id, 'jokul_va_amount', true);
        $howToPage      = get_post_meta($order_id, 'jokul_va_how_to_page', true);

        $newDate = new DateTime($vaExpired);

        echo '<h2>Payment details</h2>';
        ?>
        <p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">Please transfer your payment to this payment code / VA number:</p>

            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

                <li class="woocommerce-order-overview__va va">
                    <?php _e( 'VA Number:', 'woocommerce' ); ?>
                    <strong><?php _e( $vaNumber, 'woocommerce' ); ?></strong>
                </li>

                <li class="woocommerce-order-overview__amount amount">
                    <?php _e( 'Payment Amount:', 'woocommerce' ); ?>
                    <strong><?php _e( wc_price($vaAmount), 'woocommerce' ); ?></strong>
                </li>

                <li class="woocommerce-order-overview__date date">
                    <?php _e( 'Make Your Payment Before:', 'woocommerce' ); ?>
                    <strong><?php _e( $newDate->format('d M Y H:i'), 'woocommerce' ); ?></strong>
                </li>
            </ul>
            <p>
                <a href=<?php _e($howToPage, 'woocommerce' ); ?>>Click here to see payment instructions</a>
            </p>
        <?php
    }
}
?>
