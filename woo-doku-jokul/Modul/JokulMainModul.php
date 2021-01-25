<?php

class JokulMainModul extends WC_Payment_Gateway
{
    public function __construct()
    {

        $this->init_form_fields();
        $this->id                   = 'jokul_gateway';
        $this->has_fields           = true;
        $this->method_code          = 'Payment Gateway';
        $this->title                = !empty($this->get_option('channel_name')) ? $this->get_option('channel_name') : $this->method_code;
        $this->method_title         = __('Jokul', 'woocommerce-gateway-jokul');
        $this->method_description   = sprintf(__('Accept payment through various payment channels with Jokul. Make it easy for your customers to purchase on your store.', 'woocommerce'));

        $this->init_settings();
        $this->enabled = $this->get_option( 'enabled' );
        $this->environmentPaymentJokul = $this->get_option('environment_payment_jokul');
        $this->sandboxClientId = $this->get_option('sandbox_client_id');
        $this->sandboxSharedKey = $this->get_option('sandbox_shared_key');
        $this->prodClientId = $this->get_option('prod_client_id');
        $this->prodSharedKey = $this->get_option('prod_shared_key');
        $this->expiredTime = $this->get_option('expired_time');
        $this->notifUrl = $this->get_option('notif_url');
        $this->emailNotifications = $this->get_option('email_notifications');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_filter('woocommerce_available_payment_gateways', array(&$this, 'check_gateway_status'));
    }

    public function init_form_fields() {
		$this->form_fields = require( DOKU_JOKUL_PLUGIN_PATH . '/Form/JokulPgSetting.php' );
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

    public function check_gateway_status($gateways)
    {
        if ($this->id == 'jokul_gateway') {
            unset($gateways[$this->id]);
            return $gateways;
        }
    }
}

?>