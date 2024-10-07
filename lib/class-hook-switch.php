<?php

namespace QuadLayers\LSFW;

class Hook_Switch {

	protected static $instance;

	private function __construct() {

		// Set the subscription switch type to upgrade if the new product is a lifetime license.
		add_filter( 'wcs_switch_proration_switch_type', array( $this, 'lifetime_switch_proration_type' ), 10, 5 );

		// Allow users to discount the full price of the subscription.
		add_filter( 'wcs_switch_sign_up_fee', array( $this, 'lifetime_switch_signup_fee' ), 10, 2 );

		// Allow users to discount the full price of the subscription.
		add_filter( 'wcs_switch_proration_extra_to_pay', array( $this, 'lifetime_switch_recuring_fee' ), 10, 4 );

		// Change the price string to a one time payment by removing the period and the sign up fee.
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'lifetime_switch_product_price_string' ), 10, 3 );

		add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'lifetime_subscriptions_product_price_string' ), 10, 3 );

		// Removes the Upgrade subscription switch/button from subscription. No upgrade if subscription is lifetime.
		add_filter( 'woocommerce_subscriptions_switch_link', array( $this, 'lifetime_subscriptions_switch_link' ), 10, 4 );

		// Allow users to switch between lifetime and recurrent products.
		add_filter( 'woocommerce_get_children', array( $this, 'customize_variations_options' ), 10, 1 );
	}

	public function lifetime_switch_proration_type( $switch_type, $subscription, $cart_item, $old_price_per_day, $new_price_per_day ) {

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
		// $old_signup_fee = floatval( $old_product->get_meta( '_subscription_sign_up_fee' ) );
		// $new_signup_fee = floatval( $new_product->get_meta( '_subscription_sign_up_fee' ) );

		// // Get recurring price for the old product from the subscription.
		// $old_recurring_fee = floatval( $old_product->get_meta( '_subscription_price' ) );
		// $new_recurring_fee = floatval( $new_product->get_meta( '_subscription_price' ) );

		// // Calculate total costs
		// $old_total_cost = $old_signup_fee + $old_recurring_fee;
		// $new_total_cost = $new_signup_fee + $new_recurring_fee;

		// // Treat all switches to or from lifetime as upgrades to fire wcs_switch_sign_up_fee and wcs_switch_proration_extra_to_pay hooks.
		// if ( $new_total_cost === $old_total_cost ) {
		// return 'crossgrade';
		// }

		return 'upgrade';
	}

	public function lifetime_switch_signup_fee( $extra_to_pay, $switch_item ) {

		if ( ! isset( $switch_item->cart_item['variation_id'], $switch_item->cart_item['subscription_switch']['item_id'] ) ) {
			return $extra_to_pay;
		}

		// Get the product associated with the subscription.
		$order_item = $switch_item->subscription->get_items()[ $switch_item->cart_item['subscription_switch']['item_id'] ];

		// Get the product associated with the subscription.
		$old_product = wc_get_product( $order_item->get_variation_id() );
		// Get the new product from the cart item.
		$new_product = wc_get_product( $switch_item->cart_item['variation_id'] );
		// Check if the new product is a lifetime license.
		$is_new_lifetime = $new_product->get_meta( '_is_lifetime', true );
		// Check if the old product (from the subscription) is a lifetime license.
		$is_old_lifetime = $old_product->get_meta( '_is_lifetime', true );

		if ( ! $is_old_lifetime && ! $is_new_lifetime ) {
			return $extra_to_pay;
		}

		// Get sign-up fee for the old product from the subscription.
		$old_signup_fee = floatval( $old_product->get_meta( '_subscription_sign_up_fee' ) );
		$new_signup_fee = floatval( $new_product->get_meta( '_subscription_sign_up_fee' ) );

		return $new_signup_fee - $old_signup_fee;
	}

	public function lifetime_switch_recuring_fee( $extra_to_pay, $subscription, $cart_item, $get_days_in_old_cycle ) {

		if ( ! isset( $cart_item['variation_id'], $cart_item['subscription_switch']['item_id'] ) ) {
			return $extra_to_pay;
		}

		// Get the product associated with the subscription.
		$order_item = $subscription->get_items()[ $cart_item['subscription_switch']['item_id'] ];

		// Get the product associated with the subscription.
		$old_product = wc_get_product( $order_item->get_variation_id() );
		// Get the new product from the cart item.
		$new_product = wc_get_product( $cart_item['variation_id'] );
		// Check if the new product is a lifetime license.
		$is_new_lifetime = $new_product->get_meta( '_is_lifetime', true );
		// Check if the old product (from the subscription) is a lifetime license.
		$is_old_lifetime = $old_product->get_meta( '_is_lifetime', true );

		if ( ! $is_old_lifetime && ! $is_new_lifetime ) {
			return $extra_to_pay;
		}

		// Get recurring price for the old product from the subscription.
		$old_recurring_fee = floatval( $old_product->get_meta( '_subscription_price' ) );

		return -$old_recurring_fee;
	}

	public function lifetime_switch_product_price_string( $product_subtotal, $cart_item, $cart_item_key ) {

		if ( empty( $cart_item['subscription_switch']['subscription_id'] ) ) {
			return $product_subtotal;
		}

		// Get the product associated with the subscription.
		$subscription = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );

		$old_product = wc_get_product( $subscription->get_items()[ $cart_item['subscription_switch']['item_id'] ]->get_variation_id() );
		// Get the new product from the cart item.
		$new_product = wc_get_product( $cart_item['variation_id'] );
		// Check if the new product is a lifetime license.
		$is_new_lifetime = $new_product->get_meta( '_is_lifetime', true );
		// Check if the old product (from the subscription) is a lifetime license.
		$is_old_lifetime = $old_product->get_meta( '_is_lifetime', true );

		if ( ! $is_new_lifetime && ! $is_old_lifetime ) {
			return $product_subtotal;
		}

		$pattern3         = '/<span class="subscription-switch-direction">[^<]+<\/span>/';
		$product_subtotal = preg_replace( $pattern3, esc_html__( '(Switch)', 'lifetime-subscriptions-for-woocommerce' ), $product_subtotal, 1 );

		return $product_subtotal;
	}

	public function lifetime_subscriptions_product_price_string( $subscription_string, $product, $include ) {

		if ( is_admin() ) {
			return $subscription_string;
		}

		static $switch_counter = -1;
		++$switch_counter;

		$is_lifetime = $product->get_meta( '_is_lifetime', true );

		if ( ! $is_lifetime ) {
			return $subscription_string;
		}

		/**
		 * Check if the product is a switch.
		 * $is_switch = $this->is_subscription_switch( $product->get_id(), $switch_counter );
		 */

		// Define a regular expression pattern to match the specific HTML structure.
		$pattern1            = '/<bdi><span class="woocommerce-Price-currencySymbol">[^<]+<\/span>[^<]+<\/bdi>/';
		$subscription_string = preg_replace( $pattern1, '', $subscription_string, 1 );

		$pattern2            = '/(<span class="subscription-details">).*?(<\/span>|<span class="woocommerce-Price-amount amount">)/s';
		$subscription_string = preg_replace( $pattern2, '$1', $subscription_string );

		return $subscription_string;
	}

	public function lifetime_subscriptions_switch_link( $switch_link, $item_id, $item, $subscription ) {

		$is_lifetime = $subscription->get_meta( '_is_lifetime', true );

		if ( ! $is_lifetime ) {
			return $switch_link;
		}

		$lifetime_subscriptions_switch_link = get_option( 'lifetime_subscriptions_switch_link', 'yes' );

		if ( 'yes' !== $lifetime_subscriptions_switch_link ) {
			return '';
		}

		return $switch_link;
	}

	private function is_subscription_switch( $product_id, $switch_counter ) {
		$position = floor( $switch_counter / 2 );

		$cart_items = array_values( WC()->cart->get_cart() );

		if ( ! isset( $cart_items[ $position ] ) ) {
			return false;
		}

		$cart_item = $cart_items[ $position ];

		if ( ! isset( $cart_item['subscription_switch'] ) ) {
			return false;
		}

		if ( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] === $product_id ) {
			return true;
		}

		if ( isset( $cart_item['product_id'] ) && $cart_item['product_id'] === $product_id ) {
			return true;
		}

		return false;
	}

	public function customize_variations_options( $variations ) {
		$option_value = get_option( 'lifetime_subscriptions_switch_between_types', 'no' );

		// Early return if the option is set to 'yes'.
		if ( $option_value === 'yes' ) {
			return $variations;
		}

		// Check if 'switch-subscription' is set and is a valid subscription.
		$id           = filter_input( INPUT_GET, 'switch-subscription', FILTER_VALIDATE_INT );
		$subscription = $id ? wcs_get_subscription( $id ) : null;

		if ( ! $subscription ) {
			return $variations;
		}

		// Determine if all products in the subscription are not lifetime.
		$has_recurrent_products = true;
		foreach ( $subscription->get_items() as $item ) {
			$product = wc_get_product( $item->get_variation_id() );
			if ( $product && $product->get_meta( '_is_lifetime', true ) ) {
				$has_recurrent_products = false;
				break; // Exit the loop early if we find a lifetime product.
			}
		}

		// Filter out the variations based on whether they match the lifetime status.
		return array_filter(
			$variations,
			function ( $variation ) use ( $has_recurrent_products ) {
			$variation_product = wc_get_product( $variation );
				if ( ! $variation_product ) {
					return false;
				}

			$variation_is_recurrent = ! (bool) $variation_product->get_meta( '_is_lifetime' );
			return $variation_is_recurrent === $has_recurrent_products;
			}
		);
	}


	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
