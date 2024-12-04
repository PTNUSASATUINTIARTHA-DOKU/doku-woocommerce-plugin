<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Doku_Checkout_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'doku_checkout';

    public function initialize() {
        $this->settings = get_option('woocommerce_doku_gateway_settings');
        $this->gateway = new DokuCheckoutModule();
    }

    public function is_active() {
        return true;
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'jokul_wc_blocks_integration',
            plugin_dir_url(__FILE__) . '../Js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            null,
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'jokul_wc_blocks_integration');
            
        }
        return [ 'jokul_wc_blocks_integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->paymentDescription,
        ];
    }

}
