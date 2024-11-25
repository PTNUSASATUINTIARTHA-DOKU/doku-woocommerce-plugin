<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_checkout_settings',
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
        'QRIS_Credential' => array(
            'title' => __('QRIS Credential', 'doku-payment'),
            'type' => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'placeholder' => 'Below field is QRIS credential section',
        ),
        'payment_client_id' => array(
            'title' => __('Client-ID', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: Client ID', 'doku-payment'),
            'placeholder' => '0',
        ),
        'payment_shared_key' => array(
            'title' => __('Shared Key', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: Shared Key', 'doku-payment'),
            'placeholder' => '0',
        ),
        'payment_mpan' => array(
            'title' => __('Merchant PAN', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: Merchant PAN', 'doku-payment'),
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

