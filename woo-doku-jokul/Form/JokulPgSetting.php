<?php

return apply_filters(
    'jokul_payment_gateway_settings',
    array(
        'enabled' => array(
            'title' => __('Enable :', 'jokul'),
            'type' => 'checkbox',
            'label' => __('Enable Jokul', 'jokul'),
            'default' => 'no',
        ),

        'environment_payment_jokul' => array(
            'title' => __('Environment :', 'jokul'),
            'type' => 'select',
            'default' => 'false',
            'options' => array(
                'false' => __('Sandbox', 'jokul'),
                'true' => __('Production', 'jokul'),
            ),
        ),

        'sandbox_client_id' => array(
            'style' => '',
            'title' => __('Sandbox Client Id :', 'jokul'),
            'type' => 'text',
            'description' => __('Unique Sandbox Client Id you retrived from Jokul Back Office', 'jokul'),
            'default' => __('', 'jokul'),
        ),

        'sandbox_shared_key' => array(
            'style' => '',
            'title' => __('Sandbox Shared Key :', 'jokul'),
            'type' => 'text',
            'description' => __('Unique Sandbox Shared Key Id you retrived from Jokul Back Office', 'jokul'),
            'default' => __('', 'jokul'),
        ),

        'prod_client_id' => array(
            'style' => '',
            'title' => __('Production Client Id :', 'jokul'),
            'type' => 'text',
            'description' => __('Unique Production Client Id you retrived from Jokul Back Office', 'jokul'),
            'default' => __('', 'jokul'),
        ),

        'prod_shared_key' => array(
            'style' => '',
            'title' => __('Production Shared Key :', 'jokul'),
            'type' => 'text',
            'description' => __('Unique Production Shared Key Id you retrived from Jokul Back Office', 'jokul'),
            'default' => __('', 'jokul'),
        ),

        'expired_time' => array(
            'title' => __('Expired Time :', 'jokul'),
            'type' => 'number',
            'description' => __('Order will be cancelled if customer do not pay an invoice  (virtual account and Convenience Store) past the expiry time', 'jokul'),
            'default' => __('60', 'jokul'),
        ),

        'notif_url' => array(
            'style' => '',
            'title' => __('Url Notification :', 'jokul'),
            'type' => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'description' => __('Url notification for update status payment', 'jokul'),
            'default' => __(get_bloginfo('url').'/wp-json/jokul/notification', 'jokul'),
        ),

        'email_notifications' => array(
            'title' => __('Email Notifications :', 'jokul'),
            'type' => 'checkbox',
            'label' => __('Send email instruction to customer for virtual account and convenience store', 'jokul'),
            'default' => 'yes'
        ),

    )
);
?>