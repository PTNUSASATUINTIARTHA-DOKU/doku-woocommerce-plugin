<?php

return apply_filters(
    'jokul_checkout_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default: Checkout', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Jokul Checkout',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Jokul Checkout', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan Jokul Checkout',
        ),
        'QRIS_Credential' => array(
            'title' => __('QRIS Credential', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'custom_attributes' => array('readonly' => 'readonly'),
            'placeholder' => 'Below field is QRIS credential section',
        ),
        'payment_client_id' => array(
            'title' => __('Client-ID', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default: Client ID', 'woocommerce-gateway-jokul'),
            'placeholder' => '0',
        ),
        'payment_shared_key' => array(
            'title' => __('Shared Key', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default: Shared Key', 'woocommerce-gateway-jokul'),
            'placeholder' => '0',
        ),
        'payment_mpan' => array(
            'title' => __('Merchant PAN', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default: Merchant PAN', 'woocommerce-gateway-jokul'),
            'placeholder' => '0',
        ),
        'auto_redirect_jokul' => array(
            'title' => __('Auto Redirect', 'woocommerce-gateway-jokul'),
            'type' => 'select',
            'default' => 'false',
            'options' => array(
                'false' => __('FALSE', 'woocommerce-gateway-jokul'),
                'true' => __('TRUE', 'woocommerce-gateway-jokul'),
            ),
        )
    )
);

?>
