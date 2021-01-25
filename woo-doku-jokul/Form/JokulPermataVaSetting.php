<?php

return apply_filters(
    'jokul_permata_va_settings',
    array(
        'channel_name' => array(
            'title' => __('Payment Channel Display Name', 'woocommerce-gateway-jokul'),
            'type' => 'text',
            'description' => __('Default : PERMATA VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'PERMATA VA',
        ),
        'payment_description' => array(
            'title' => __('Payment Description', 'woocommerce-gateway-jokul'),
            'type' => 'textarea',
            'css' => 'width: 400px;',
            'description' => __('Change your payment description for PERMATA VA', 'woocommerce-gateway-jokul'),
            'placeholder' => 'Bayar pesanan dengan transfer PERMATA VA',
        )
    )
);

?>
