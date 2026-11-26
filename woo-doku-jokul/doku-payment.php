<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: DOKU Accept Payments
 * Plugin URI: https://github.com/PTNUSASATUINTIARTHA-DOKU/doku-woocommerce-plugin
 * Description: Accept payment through various payment channels with DOKU. Make it easy for your customers to purchase on your store.
 * Version: 1.3.34
 * Author: DOKU
 * Author URI: http://www.doku.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.9
 * Tested up to: 7.0.2
 * Requires PHP: 7.3
 * Requires Plugins: woocommerce
 * WC requires at least: 5.0
 * WC tested up to: 11.0.0
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
				$methods[] = 'DokuCheckoutModule';

				$is_block_editor = false;
				if ( is_admin() ) {
					$pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
					if ( in_array( $pagenow, array( 'post.php', 'post-new.php', 'site-editor.php' ), true ) ) {
						$is_block_editor = true;
					}
				}

				if ( ! $is_block_editor ) {
					$methods[] = 'DokuMainModule';
				}

				return $methods;
			}
		}
		$GLOBALS['doku_main_pg'] = DokuMainPg::get_instance();
	}
}

register_activation_hook(__FILE__, 'doku_payment_install_db');
add_action('plugins_loaded', 'doku_payment_check_install_db');
function doku_payment_check_install_db()
{
	if (get_option('doku_db_version') !== '1.0') {
		doku_payment_install_db();
	}
}
function doku_payment_install_db()
{
	global $wpdb;
	$doku_payment_db_version = "1.0";
	$table_name = $wpdb->prefix . "jokuldb";
	$sql = "
		CREATE TABLE $table_name (
		  trx_id int( 11 ) NOT NULL AUTO_INCREMENT,
		  ip_address VARCHAR( 64 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
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
            return doku_payment_order_update_status_qris('doku',$request);
        },
		'permission_callback' => '__return_true'
	));
}

function doku_payment_order_update_status_qris($path, $request)
{
	$qrisNotificationService = new DokuQrisNotificationService();
	$response = $qrisNotificationService->getQrisNotification($path,$request);
	return $response;
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

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'doku_payment_action_links');
function doku_payment_action_links($links)
{
	$plugin_links = array(
		'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=doku_gateway')) . '">' . __('Settings', 'doku-payment') . '</a>',
		'<a href="https://docs.doku.com/accept-payments/integration-tools/e-commerce-and-plugins/woocommerce-wordpress" target="_blank" rel="noopener noreferrer">' . __('Docs', 'doku-payment') . '</a>',
		'<a href="https://help.doku.com/en/support/home" target="_blank" rel="noopener noreferrer">' . __('Support', 'doku-payment') . '</a>',
	);

	return is_array($links) ? array_merge($plugin_links, $links) : $plugin_links;
}

add_action('admin_init', 'doku_payment_admin_init_security_check');
function doku_payment_admin_init_security_check()
{
	global $pagenow;
	if (
		(function_exists('wp_doing_ajax') && wp_doing_ajax())
		|| (function_exists('wp_doing_cron') && wp_doing_cron())
		|| (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		|| !is_admin()
		|| (isset($pagenow) && $pagenow === 'admin-post.php')
	) {
		return;
	}

	if (
		!current_user_can('manage_options')
		&& !current_user_can('manage_woocommerce')
	) {
		return;
	}

	$legacy_dir = defined('ABSPATH') ? ABSPATH . 'doku_log' : '';
	if ($legacy_dir === '' || !is_dir($legacy_dir)) {
		if (function_exists('delete_option')) {
			delete_option('doku_legacy_log_notice_status');
		}
		return;
	}

	// Shield legacy folder if not already shielded (DO NOT overwrite existing files)
	$htaccess_file = rtrim($legacy_dir, '/\\') . '/.htaccess';
	if (!file_exists($htaccess_file)) {
		$htaccess_content = "# Protect DOKU legacy logs\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n";
		@file_put_contents($htaccess_file, $htaccess_content);
	}

	$index_file = rtrim($legacy_dir, '/\\') . '/index.php';
	if (!file_exists($index_file)) {
		$index_content = "<?php\n// Silence is golden.\nexit;\n";
		@file_put_contents($index_file, $index_content);
	}

	// Scan for *.log files
	$scan_items = @scandir($legacy_dir);
	if ($scan_items === false) {
		if (function_exists('update_option')) {
			update_option('doku_legacy_log_notice_status', 'unreadable', false);
		}
		return;
	}

	$has_logs = false;
	foreach ($scan_items as $item) {
		if ($item === '.' || $item === '..') {
			continue;
		}
		if (substr(strtolower($item), -4) === '.log') {
			$has_logs = true;
			break;
		}
	}

	if ($has_logs) {
		if (function_exists('update_option')) {
			update_option('doku_legacy_log_notice_status', 'logs_found', false);
		}
	} else {
		// Verification succeeded and no *.log files found (including empty directory)
		if (function_exists('delete_option')) {
			delete_option('doku_legacy_log_notice_status');
		}
	}
}

add_action('admin_notices', 'doku_payment_security_admin_notices');
function doku_payment_security_admin_notices()
{
	if (
		!current_user_can('manage_options')
		&& !current_user_can('manage_woocommerce')
	) {
		return;
	}

	// Banner 1
	$log_error_reason = function_exists('get_option') ? get_option('doku_log_error_reason') : '';
	if (!empty($log_error_reason)) {
		$banner1_text = 'DOKU Accept Payment: Private log directory is unavailable or cannot be verified outside DOCUMENT_ROOT. File logging has been safely disabled. Define DOKU_LOG_DIR outside the public web root in wp-config.php to enable logging.';
		?>
		<div class="notice notice-warning">
			<p><?php echo esc_html($banner1_text); ?></p>
		</div>
		<?php
	}

	// Banner 2
	$legacy_status = function_exists('get_option') ? get_option('doku_legacy_log_notice_status') : '';
	if ($legacy_status === 'logs_found' || $legacy_status === 'unreadable') {
		if ($legacy_status === 'unreadable') {
			$banner2_text = 'DOKU Security Notice: Legacy log folder cannot be inspected (check directory permissions). Please locate the /doku_log folder in your WordPress root directory and move it to a private directory or delete the existing folder.';
		} else {
			$banner2_text = 'DOKU Security Notice: Please locate the /doku_log folder in your WordPress root directory and move it to a private directory or delete the existing folder.';
		}
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html($banner2_text); ?></p>
		</div>
		<?php
	}
}
