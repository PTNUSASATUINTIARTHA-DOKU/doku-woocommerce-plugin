<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: DOKU Payment
 * Plugin URI: https://github.com/PTNUSASATUINTIARTHA-DOKU/doku-woocommerce-plugin
 * Description: Accept payment through various payment channels with DOKU. Make it easy for your customers to purchase on your store.
 * Version: 1.3.22
 * Author: DOKU
 * Author URI: http://www.doku.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 2.2
 **/

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
define('DOKU_PAYMENT_MAIN_FILE', __FILE__);
define('DOKU_PAYMENT_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));

add_action('plugins_loaded', 'doku_payment_init_gateway_class');
function doku_payment_init_gateway_class()
{

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	if (!class_exists('DokuMainPg')) {
		class DokuMainPg
		{
			private static $instance;

			public static function get_instance()
			{
				if (self::$instance === null) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			private function __construct()
			{
				$this->init();
			}

			public function init()
			{
				require_once dirname(__FILE__) . '/Common/JokulListModule.php';
				add_filter('woocommerce_payment_gateways', array($this, 'addJokulGateway'));
			}

			/**
			 * Add jokul payment methods
			 *
			 * @param array $methods
			 * @return array $methods
			 */
			function addJokulGateway($methods)
			{
				$mainSettings = get_option('woocommerce_doku_gateway_settings');
				$methods[] = 'DokuMainModule';
				$methods[] = 'DokuCheckoutModule';

				return $methods;
			}
		}
		$GLOBALS['doku_main_pg'] = DokuMainPg::get_instance();
	}
}

register_activation_hook(__FILE__, 'doku_payment_install_db');
function doku_payment_install_db()
{
	global $wpdb;
	$doku_payment_db_version = "1.0";
	$table_name = $wpdb->prefix . "jokuldb";
	$sql = "
		CREATE TABLE $table_name (
		  trx_id int( 11 ) NOT NULL AUTO_INCREMENT,
		  ip_address VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  process_type VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  process_datetime DATETIME NULL, 
		  doku_payment_datetime DATETIME NULL,   
		  invoice_number VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  amount DECIMAL( 20,2 ) NOT NULL DEFAULT '0',
		  notify_type VARCHAR( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  response_code VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  status_code VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  result_msg VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  reversal INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  approval_code CHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  payment_channel VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  payment_code VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  bank_issuer VARCHAR( 100 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  creditcard VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  words VARCHAR( 200 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',  
		  session_id VARCHAR( 48 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  verify_id VARCHAR( 30 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  verify_score INT( 3 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  verify_status VARCHAR( 10 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
		  check_status INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			count_check_status INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
		  raw_post_data TEXT COLLATE utf8_unicode_ci,  
		  message TEXT COLLATE utf8_unicode_ci,  
		  PRIMARY KEY (trx_id)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1		
		
	";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('doku_db_version', $doku_payment_db_version);
}

add_action('rest_api_init', function () {
    // Register route for Doku
    register_rest_route('doku', 'notification', array(
        'methods' => 'POST',
        'callback' => function ($request) {
            return doku_payment_order_update_status('doku',$request);
        },
        'permission_callback' => '__return_true'
    ));
});

function doku_payment_order_update_status($path,$request)
{
	$notificationService = new DokuNotificationService();
	$response = $notificationService->getNotification($path,$request);
	return $response;
}

add_action('rest_api_init', 'doku_payment_qris_register_route');
function doku_payment_qris_register_route()
{
	register_rest_route('doku', 'qrisnotification', array(
		'methods' => 'POST',
		'callback' => function ($request) {
            return doku_payment_order_update_status_qris($request);
        },
		'permission_callback' => '__return_true'
	));
}

function doku_payment_order_update_status_qris($request)
{
	$qrisNotificationService = new DokuQrisNotificationService();
	$response = $qrisNotificationService->getQrisNotification($request);
}

add_action('woocommerce_thankyou', 'doku_payment_thank_you_page_credit_card', 1, 10);
function doku_payment_thank_you_page_credit_card($order_id)
{
	$chosen_payment_method     = WC()->session->get('chosen_payment_method');
	if ($chosen_payment_method == 'jokul_creditcard') {
		global $woocommerce;
		$woocommerce->cart->empty_cart();
		wc_reduce_stock_levels($order_id);
		update_post_meta(1000, 'jokul_cc_order_id', '');
	?>
		<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">Your payment with Credit Card is success!</p>
		<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
			<li class="woocommerce-order-overview__cc cc">
				<?php esc_html_e('Payment Method', 'doku-payment'); ?>
				<strong><?php esc_html_e("Credit Card", 'doku-payment'); ?></strong>
			</li>
		</ul>
<?php
	}
}


add_action('before_woocommerce_init', 'doku_payment_declare_cart_checkout_blocks_compatibility');
function doku_payment_declare_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

add_action('woocommerce_blocks_loaded', 'doku_payment_register_payment_method_type');
function doku_payment_register_payment_method_type() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'Block/DokuCheckoutBlock.php';
	
    add_action('woocommerce_blocks_payment_method_type_registration',
    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
        $payment_method_registry->register(new Doku_Checkout_Blocks);
    });
}

add_filter('woocommerce_locate_template', 'doku_payment_plugin_template', 1, 3);
function doku_payment_plugin_template($template, $template_name, $template_path)
{
	global $woocommerce;
	$_template = $template;
	if (!$template_path)
		$template_path = $woocommerce->template_url;

	$plugin_path  = untrailingslashit(plugin_dir_path(__FILE__))  . '/templates/';

	// Look within passed path within the theme - this is priority
	$template = locate_template(
		array(
			$template_path . $template_name,
			$template_name
		)
	);

	if (!$template && file_exists($plugin_path . $template_name)) {
		$template = $plugin_path . $template_name;
	}

	if (!$template) {
		$template = $_template;
	}

	return $template;
}
