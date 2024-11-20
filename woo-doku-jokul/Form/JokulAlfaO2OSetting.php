<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_alfa_o2o_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default : Alfamart', 'doku-payment'),
            'placeholder' => 'Alfamart',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for payment using Alfamart', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan transfer melalui Alfamart',
        ),
        'footer_message' => array(
            'title' => __('Footer Message', 'doku-payment'),
            'type' => 'text',
            'description' => __('Change your footer message for payment using Alfamart', 'doku-payment'),
            'placeholder' => 'ex: Call Center 021 555-0525',
        )
    )
);
