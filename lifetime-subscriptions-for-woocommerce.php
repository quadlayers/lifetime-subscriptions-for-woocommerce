<?php

/**
 * Plugin Name:             Lifetime Subscriptions for WooCommerce
 * Plugin URI:              https://quadlayers.com/
 * Description:             Lifetime Subscriptions for WooCommerce
 * Version:                 1.0.0
 * Text Domain:             lifetime-subscriptions-for-woocommerce
 * Author:                  QuadLayers
 * Author URI:              https://quadlayers.com
 * License:                 GPLv3
 * Domain Path:             /languages
 * Request at least:        4.7.0
 * Tested up to:            6.3
 * Requires PHP:            5.6
 * WC requires at least:    4.0
 * WC tested up to:         8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
*   Definition globals variables
*/

define( 'LSFW_PLUGIN_NAME', 'Lifetime Subscriptions for WooCommerce' );
define( 'LSFW_PLUGIN_VERSION', '1.0.0' );
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
	function() {
		do_action( 'lsfw_activation' );
	}
);
/**
 * On plugin deactivation
 */
register_deactivation_hook(
	__FILE__,
	function() {
		do_action( 'lsfw_deactivation' );
	}
);

add_filter(
	'woocommerce_product_variation_get__subscription_period',
	function( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 'year';
	},
	10,
	2
);
add_filter(
	'woocommerce_product_variation_get__subscription_period_interval',
	function( $value, $data ) {
		// //error_log( 'value: ' . json_encode( $value, JSON_PRETTY_PRINT ) );
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 1;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_variation_get__subscription_length',
	function( $value, $data ) {
		// //error_log( 'value: ' . json_encode( $value, JSON_PRETTY_PRINT ) );
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_variation_get_sale_price',
	function( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_variation_get_regular_price',
	function( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	},
	10,
	2
);
add_filter(
	'woocommerce_product_variation_get_price',
	function( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	},
	10,
	2
);

/**
 * Adjust the switch type based on the old and new price per day, including the sign-up fee.
 *
 * @param string          $switch_type The original switch type.
 * @param WC_Subscription $subscription The subscription object.
 * @param array           $cart_item Cart item data.
 * @param float           $old_price_per_day Old price per day.
 * @param float           $new_price_per_day New price per day.
 * @return string Modified switch type.
 */
function custom_wcs_switch_proration_type( $switch_type, $subscription, $cart_item, $old_price_per_day, $new_price_per_day ) {

	if ( ! isset( $cart_item['variation_id'], $cart_item['subscription_switch']['item_id'] ) ) {
		return $switch_type;
	}

	// Get the product associated with the subscription.
	$old_product = wc_get_product( $subscription->get_items()[ $cart_item['subscription_switch']['item_id'] ]->get_variation_id() );
	// Get the new product from the cart item.
	$new_product = wc_get_product( $cart_item['variation_id'] );
	// Check if the new product is a lifetime license.
	$is_new_lifetime = $new_product->get_meta( '_is_lifetime', true );
	// Check if the old product (from the subscription) is a lifetime license.
	$is_old_lifetime = $old_product->get_meta( '_is_lifetime', true );

	if ( ! $is_new_lifetime && ! $is_old_lifetime ) {
		return $switch_type;
	}

	// // Get sign-up fee for the old product from the subscription.
	$old_signup_fee = floatval( $old_product->get_meta( '_subscription_sign_up_fee' ) );
	$new_signup_fee = floatval( $new_product->get_meta( '_subscription_sign_up_fee' ) );

	// Get recurring price for the old product from the subscription.
	$old_recurring_fee = floatval( $old_product->get_meta( '_subscription_price' ) );
	$new_recurring_fee = floatval( $new_product->get_meta( '_subscription_price' ) );

	// Calculate total costs
	$old_total_cost = $old_signup_fee + $old_recurring_fee;
	$new_total_cost = $new_signup_fee + $new_recurring_fee;

	// error_log( 'new_total_cost: ' . json_encode( $new_total_cost, JSON_PRETTY_PRINT ) );
	// error_log( 'old_total_cost: ' . json_encode( $old_total_cost, JSON_PRETTY_PRINT ) );

	// if ( $old_total_cost === $new_total_cost ) {
	// 	return 'crossgrade';
	// }

	// Treat all switches to or from lifetime as upgrades to fire wcs_switch_sign_up_fee and wcs_switch_proration_extra_to_pay hooks.
	if ( $new_total_cost > $old_total_cost ) {
		return 'upgrade';
	} elseif ( $new_total_cost < $old_total_cost ) {
		return 'upgrade';
	}

	return 'crossgrade';
}
add_filter( 'wcs_switch_proration_switch_type', 'custom_wcs_switch_proration_type', 10, 5 );


add_filter(
	'woocommerce_subscription_calculated_next_payment_date',
	function( $date, $subscription ) {

		/**
		 * Check if the subscription has a recurrent product and return the date if it does.
		 * There is no issue with the renewal of lifetime subscriptions because the recurring price is 0.
		 */
		$has_recurrent_products = array_map(
			function( $item ) {
				$product     = wc_get_product( $item->get_variation_id() );
				$is_lifetime = $product->get_meta( '_is_lifetime', true );
				return ! $is_lifetime;
			},
			$subscription->get_items()
		);

		if ( in_array( true, $has_recurrent_products, true ) ) {
			return $date;
		}

		return 0;
	},
	10,
	2
);

/**
 * Remove the next payment date from the subscription in the cart.
 * If we don't remove it, the hook 'woocommerce_subscription_calculated_next_payment_date' will not be triggered.
 * I can't believe this level of hack is necessary. I haven't encountered worse code in my life.
 */
add_filter(
	'woocommerce_subscriptions_product_first_renewal_payment_time',
	function( $first_renewal_timestamp, $product, $from_date_param, $timezone ) {

		$is_lifetime = $product->get_meta( '_is_lifetime', true );

		if ( ! $is_lifetime ) {
			return $first_renewal_timestamp;
		}
		return 0;
	},
	10,
	4
);

add_action(
	'woocommerce_subscriptions_switched_item',
	function ( $subscription, $new_order_item, $old_order_item ) {
		/**
		 * Check if the subscription has a recurrent product and return the date if it does.
		 * There is no issue with the renewal of lifetime subscriptions because the recurring price is 0.
		 */
		$has_recurrent_products = array_map(
			function( $item ) {
				$product     = wc_get_product( $item->get_variation_id() );
				$is_lifetime = $product->get_meta( '_is_lifetime', true );
				return ! $is_lifetime;
			},
			$subscription->get_items()
		);

		if ( in_array( true, $has_recurrent_products, true ) ) {
			return;
		}

		$subscription->update_dates( array( 'next_payment' => 0 ) );
		$subscription->save();

	},
	10,
	3
);
