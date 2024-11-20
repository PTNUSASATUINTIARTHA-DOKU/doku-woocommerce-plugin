<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_credit_card_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: Credit Card', 'doku-payment'),
            'placeholder' => 'Credit Card',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'doku-payment'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Credit Card', 'doku-payment'),
            'placeholder' => 'Bayar pesanan dengan Credit Card',
        ),
        'language_payment_jokul' => array(
            'title' => __('Language :', 'jokul'),
            'type' => 'select',
            'default' => 'ID',
            'options' => array(
                'ID' => __('ID', 'jokul'),
                'EN' => __('EN', 'jokul'),
            ),
        ),
        'payment_background_color' => array(
            'title' => __('Background Color', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: F5F8FB', 'doku-payment'),
            'placeholder' => 'Background Color',
        ),
        'payment_font_color' => array(
            'title' => __('Font Color', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: 1A1A1A', 'doku-payment'),
            'placeholder' => 'Font Color',
        ),
        'payment_button_background_color' => array(
            'title' => __('Button Background Color', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: E1251B', 'doku-payment'),
            'placeholder' => 'Button Background Color',
        ),
        'payment_button_font_color' => array(
            'title' => __('Button Font Color', 'doku-payment'),
            'type' => 'text',
            'description' => __('Default: FFFFFF', 'doku-payment'),
            'placeholder' => 'Button Font Color',
        )
    )
);
