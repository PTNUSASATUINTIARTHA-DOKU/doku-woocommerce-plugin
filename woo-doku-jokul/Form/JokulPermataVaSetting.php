<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_permata_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : Bank Permata VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bank Permata VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Bank Permata VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer dari Bank Permata',
        )
    )
);

?>
