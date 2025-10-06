<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'doku_checkout_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: Checkout', 'doku-payment'),
            'placeholder' => 'DOKU Checkout',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for DOKU Checkout', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan DOKU Checkout',
        ),
        'payment_client_id' => array(
            'title' => __('QRIS Client-ID', 'doku-payment'),
            'type' => 'text',
            'placeholder' => '0',
        ),
        'payment_shared_key' => array(
            'title' => __('QRIS Shared Key', 'doku-payment'),
            'type' => 'text',
            'placeholder' => '0',
        ),
        'payment_mpan' => array(
            'title' => __('QRIS Merchant PAN', 'doku-payment'),
            'type' => 'text',
            'placeholder' => '0',
        ),
        'auto_redirect_jokul' => array(
            'title' => __('Auto Redirect', 'doku-payment'),
            'type' => 'select',
            'default' => 'false',
            'options' => array(
                'false' => __('FALSE', 'doku-payment'),
                'true' => __('TRUE', 'doku-payment'),
            ),
        )
    )
);

