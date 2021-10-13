<?php

return apply_filters(
    'jokul_bri_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : Bank Rakyat Indonesia VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bank Rakyat Indonesia VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for Bank Rakyat Indonesia VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer Bank Rakyat Indonesia VA',
        )
    )
);

?>
