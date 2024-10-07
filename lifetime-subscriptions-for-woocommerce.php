<?php

/**
 * Plugin Name:             Lifetime Subscriptions for WooCommerce
 * Plugin URI:              https://quadlayers.com/
 * Description:             Lifetime Subscriptions for WooCommerce
 * Version:                 1.1.3
 * Text Domain:             lifetime-subscriptions-for-woocommerce
 * Author:                  QuadLayers
 * Author URI:              https://quadlayers.com
 * License:                 GPLv3
 * Domain Path:             /languages
 * Request at least:        4.7
 * Tested up to:            6.6
 * Requires PHP:            5.6
 * WC requires at least:    4.0
 * WC tested up to:         9.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
*   Definition globals variables
*/
define( 'LSFW_PLUGIN_NAME', 'Lifetime Subscriptions for WooCommerce' );
define( 'LSFW_PLUGIN_VERSION', '1.1.3' );
define( 'LSFW_PLUGIN_FILE', __FILE__ );
define( 'LSFW_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR );
define( 'LSFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LSFW_DOMAIN', 'lsfw' );
define( 'LSFW_PREFIX', LSFW_DOMAIN );
define( 'LSFW_PURCHASE_URL', 'https://quadlayers.com/?utm_source=lsfw_admin' );
define( 'LSFW_SUPPORT_URL', 'https://quadlayers.com/account/support/?utm_source=lsfw_admin' );
define( 'LSFW_DOCUMENTATION_URL', 'https://quadlayers.com/documentation/wp-menu-icons/?utm_source=lsfw_admin' );
define( 'LSFW_LICENSES_URL', 'https://quadlayers.com/account/licenses/?utm_source=lsfw_admin' );

/**
 * Load composer autoload
 */
require_once __DIR__ . '/vendor/autoload.php';
/**
 * Load vendor_packages packages
 */
require_once __DIR__ . '/vendor_packages/wp-i18n-map.php';
require_once __DIR__ . '/vendor_packages/wp-dashboard-widget-news.php';
require_once __DIR__ . '/vendor_packages/wp-plugin-table-links.php';
require_once __DIR__ . '/vendor_packages/wp-notice-plugin-required.php';
/**
 * Load plugin classes
 */
require_once __DIR__ . '/lib/class-plugin.php';
/**
 * On plugin activation
 */
register_activation_hook(
	__FILE__,
	function () {
		do_action( 'lsfw_activation' );
	}
);
/**
 * On plugin deactivation
 */
register_deactivation_hook(
	__FILE__,
	function () {
		do_action( 'lsfw_deactivation' );
	}
);

/**
 * Declarate compatibility with WooCommerce Custom Order Tables
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);
