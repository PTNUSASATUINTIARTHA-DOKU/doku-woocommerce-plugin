<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<table>
    <tr>
        <td>
            <p><b>Payment Instructions : </b></p>
            <ol>
                <?php foreach ($instructions as $paymentInstruction) : ?>
                    <li>
                        <p><b>Cara pembayaran via <?= $paymentInstruction['channel'] ?></b></p>
                        <ol>
                            <?php foreach ($paymentInstruction['step'] as $step) : ?>
                                <li>
                                    <?= $step ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <br />
                    </li>
                <?php endforeach; ?>
            </ol>
        </td>
    </tr>
</table>

<?php

/**
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );