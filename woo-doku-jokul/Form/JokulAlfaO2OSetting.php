<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_alfa_o2o_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : Alfamart', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Alfamart',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for payment using Alfamart', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer melalui Alfamart',
        ),
        'footer_message' => array(
            'title' => __('Footer Message', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Change your footer message for payment using Alfamart', 'woocommerce-gateway-jokul'),
            'placeholder' => 'ex: Call Center 021 555-0525',
        )
    )
);
