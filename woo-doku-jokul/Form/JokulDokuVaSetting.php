<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_doku_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : Other Banks (VA by DOKU)', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Other Banks (VA by DOKU)',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for DOKU VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer dari bank lain',
        )
    )
);

?>
