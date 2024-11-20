<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_bri_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default : Bank Rakyat Indonesia VA', 'doku-payment'),
            'placeholder' => 'Bank Rakyat Indonesia VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Bank Rakyat Indonesia VA', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan transfer Bank Rakyat Indonesia VA',
        )
    )
);
