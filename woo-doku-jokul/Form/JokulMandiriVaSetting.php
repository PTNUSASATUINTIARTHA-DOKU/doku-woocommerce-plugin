<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_mandiri_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default : Bank Mandiri VA', 'doku-payment'),
            'placeholder' => 'Bank Mandiri VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Bank Mandiri VA', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan transfer dari Bank Mandiri',
        )
    )
);
