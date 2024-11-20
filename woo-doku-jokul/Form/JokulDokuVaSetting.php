<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_doku_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default : Other Banks (VA by DOKU)', 'doku-payment'),
            'placeholder' => 'Other Banks (VA by DOKU)',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for DOKU VA', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan transfer dari bank lain',
        )
    )
);

