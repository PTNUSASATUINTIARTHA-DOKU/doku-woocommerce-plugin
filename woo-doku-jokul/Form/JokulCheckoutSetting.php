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
        )
    )
);

?>
