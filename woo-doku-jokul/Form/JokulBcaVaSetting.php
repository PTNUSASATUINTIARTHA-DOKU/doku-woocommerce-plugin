<?php

return apply_filters(
    'jokul_bca_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : BCA VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'BCA VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for BCA VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer dari BCA',
        )
    )
);

?>
