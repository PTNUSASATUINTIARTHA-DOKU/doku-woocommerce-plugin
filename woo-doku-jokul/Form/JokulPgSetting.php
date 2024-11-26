<?php

if ( ! defined( 'ABSPATH' ) ) exit;

return apply_filters(
    'jokul_payment_gateway_settings',
    array(
        'enabled' => array(
            'title' => __('Enable :', 'doku-payment'),
            'type' => 'checkbox',
            'label' => __('Enable DOKU', 'doku-payment'),
            'default' => 'no',
        ),

        'environment_payment_jokul' => array(
            'title' => __('Environment :', 'doku-payment'),
            'type' => 'select',
            'default' => 'false',
            'options' => array(
                'false' => __('Sandbox', 'doku-payment'),
                'true' => __('Production', 'doku-payment'),
            ),
        ),

        'sandbox_client_id' => array(
            'style' => '',
            'title' => __('Sandbox Client ID:', 'doku-payment'),
            'type' => 'text',
            'description' => __('Sandbox Client ID you retrieved from DOKU Back Office', 'doku-payment'),
            'default' => '',
        ),

        'sandbox_shared_key' => array(
            'style' => '',
            'title' => __('Sandbox Secret Key:', 'doku-payment'),
            'type' => 'text',
            'description' => __('Sandbox Secret Key you retrieved from DOKU Back Office', 'doku-payment'),
            'default' => '',
        ),

        'prod_client_id' => array(
            'style' => '',
            'title' => __('Production Client ID:', 'doku-payment'),
            'type' => 'text',
            'description' => __('Production Client ID you retrieved from DOKU Back Office', 'doku-payment'),
            'default' => '',
        ),

        'prod_shared_key' => array(
            'style' => '',
            'title' => __('Production Secret Key:', 'doku-payment'),
            'type' => 'text',
            'description' => __('Unique Production Secret Key Id you retrieved from DOKU Back Office', 'doku-payment'),
            'default' => '',
        ),

        'expired_time' => array(
            'title' => __('Expired Time:', 'doku-payment'),
            'type' => 'number',
            'description' => __('Order will be cancelled if customer do not pay an invoice  (Virtual Account and O2O) past the expiry time', 'doku-payment'),
            'default' => __('60', 'doku-payment'),
        ),

        'notif_url' => array(
            'style' => '',
            'title' => __('Notification URL:', 'doku-payment'),
            'type' => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'description' => __('Set this URL to your DOKU Back Office', 'doku-payment'),
            'default' => sprintf(
                /* translators: %s: The site URL. */
                __( '%s/wp-json/doku/notification', 'doku-payment' ),
                get_bloginfo('url')
            ),
        ),

        'notif_url_qris' => array(
            'style' => '',
            'title' => __('QRIS Notification URL:', 'doku-payment'),
            'type' => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'description' => __('Set this URL to your DOKU Back Office', 'doku-payment'),
            'default' => sprintf(
                /* translators: %s: The site URL. */
                __( '%s/wp-json/doku/qrisnotification', 'doku-payment' ),
                get_bloginfo('url')
            ),
        ),

        'email_notifications' => array(
            'title' => __('Email Notifications :', 'doku-payment'),
            'type' => 'checkbox',
            'label' => __('Send email instruction to customer for virtual account and convenience store', 'doku-payment'),
            'default' => 'yes'
        ),
        'sac_check' => array(
            'title' => __('Enabling Sub Account :', 'doku-payment'),
            'type' => 'checkbox',
            'label' => __('Enable Your Sub Account in Woocomerce', 'doku-payment'),
            'description' => __( 'Enable Your Sub Account in Woocomerce', 'doku-payment' ),
            'desc_tip'    => true,
            'default' => 'no'
        ),
        'sac_textbox' => array(
            'style' => '',
            'class'         => array('jokul_class'),
            'title' => __('On Behalf Of:', 'doku-payment'),
            'type' => 'text',
            'description' => __('Route to your DOKU Sub Account ID. All transactions will be linked to this account', 'doku-payment'),
            'placeholder' => _x('e.g. SAC-Xxxxxx', 'placeholder', 'doku-payment'),
            'default' => '',
            'required'    => true
        ),
    )
);
