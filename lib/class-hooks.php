<?php

namespace QuadLayers\LSFW;

class Hooks {

	protected static $instance;
	protected static $lifetime = 'year';

	private function __construct() {
		// add_filter( 'woocommerce_before_calculate_totals', array( __CLASS__, 'maybe_set_free_trial' ), 100, 1 );
		// add_action( 'woocommerce_subscription_cart_before_grouping', array( __CLASS__, 'maybe_unset_free_trial' ) );
		// add_action( 'woocommerce_subscription_cart_after_grouping', array( __CLASS__, 'maybe_set_free_trial' ) );
		// add_action( 'wcs_recurring_cart_start_date', array( __CLASS__, 'maybe_unset_free_trial' ), 0, 1 );
		// add_action( 'wcs_recurring_cart_end_date', array( __CLASS__, 'maybe_set_free_trial' ), 100, 1 );
		// add_filter( 'woocommerce_subscriptions_calculated_total', array( __CLASS__, 'maybe_unset_free_trial' ), 10000, 1 );
		// add_action( 'woocommerce_cart_totals_before_shipping', array( __CLASS__, 'maybe_set_free_trial' ) );
		// add_action( 'woocommerce_cart_totals_after_shipping', array( __CLASS__, 'maybe_unset_free_trial' ) );
		// add_action( 'woocommerce_review_order_before_shipping', array( __CLASS__, 'maybe_set_free_trial' ) );
		// add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'maybe_unset_free_trial' ) );

		add_filter( 'wcs_switch_sign_up_fee', array( $this, 'lifetime_switch_signup_fee' ), 10, 2 );

		// Allow users to discount the full price of the subscription.
		add_filter( 'wcs_switch_proration_extra_to_pay', array( $this, 'lifetime_switch_recuring_fee' ), 10, 4 );

		// Make sure the proration is calculated. This is done by setting the days_in_new_cycle to the days_in_old_cycle.
		// add_filter( 'wcs_switch_proration_days_in_new_cycle', array( $this, 'lifetime_switch_proration_days_in_new_cycle' ), 10, 4 );

		// Removes next payment date from cart and checkout. This will remove recurrent payment.
		// add_filter( 'wcs_recurring_cart_next_payment_date', array( $this, 'lifetime_recurring_cart_next_payment_date' ), 10, 3 );

		// Change the price string to a one time payment by removing the period and the sign up fee.
		add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'lifetime_subscriptions_product_price_string' ), 10, 3 );

		// Removes the Upgrade subscription switch/button from subscription. No upgrade if subscription is lifetime.
		add_filter( 'woocommerce_subscriptions_switch_link', array( $this, 'lifetime_subscriptions_switch_link' ), 10, 4 );
	}

	/**
	 * Fix switch between lifetime subscriptions
	 */
	public static function maybe_set_free_trial( $total = '' ) {

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['subscription_switch']['subscription_id'] ) ) {
				$subscription = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );
				// $billing_period = $subscription->get_data()['billing_period'];
				// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
				$is_lifetime = $subscription->get_meta( '_is_lifetime', true );
				// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
				if ( $is_lifetime ) {
					wcs_set_objects_property( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'subscription_trial_length', 1, 'set_prop_only' );
				}
			}
		}

		return $total;
	}

	/**
	 * Fix switch between lifetime subscriptions
	 */
	public static function maybe_unset_free_trial( $total = '' ) {

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['subscription_switch']['subscription_id'] ) ) {
				$subscription = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );
				// $billing_period = $subscription->get_data()['billing_period'];
				// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
				$is_lifetime = $subscription->get_meta( '_is_lifetime', true );
				// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
				if ( $is_lifetime ) {
					wcs_set_objects_property( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'subscription_trial_length', 0, 'set_prop_only' );
				}
			}
		}
		return $total;
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

		// de periodica a periodica agregar extra_to_pay
		if ( ! $is_old_lifetime && ! $is_new_lifetime ) {
			return $extra_to_pay;
		}

		// Get sign-up fee for the old product from the subscription.
		$old_signup_fee = floatval( $old_product->get_meta( '_subscription_sign_up_fee' ) );
		$new_signup_fee = floatval( $new_product->get_meta( '_subscription_sign_up_fee' ) );

		// error_log( 'old_signup_fee: ' . json_encode( $old_signup_fee, JSON_PRETTY_PRINT ) );
		// error_log( 'new_signup_fee: ' . json_encode( $new_signup_fee, JSON_PRETTY_PRINT ) );

		return $new_signup_fee - $old_signup_fee;

	}

	public function lifetime_switch_recuring_fee( $extra_to_pay, $subscription, $cart_item, $get_days_in_old_cycle ) {

		// error_log( 'extra_to_pay: ' . json_encode( $extra_to_pay, JSON_PRETTY_PRINT ) );
		if ( ! isset( $cart_item['variation_id'], $cart_item['subscription_switch']['item_id'] ) ) {
			return $extra_to_pay;
		}

		$order_item = $subscription->get_items()[ $cart_item['subscription_switch']['item_id'] ];

		// Get the product associated with the subscription.
		$old_product = wc_get_product( $order_item->get_variation_id() );
		// Get the new product from the cart item.
		$new_product = wc_get_product( $cart_item['variation_id'] );
		// Check if the new product is a lifetime license.
		$is_new_lifetime = $new_product->get_meta( '_is_lifetime', true );
		// Check if the old product (from the subscription) is a lifetime license.
		$is_old_lifetime = $old_product->get_meta( '_is_lifetime', true );

		// de periodica a periodica agregar extra_to_pay
		if ( ! $is_old_lifetime && ! $is_new_lifetime ) {
			return $extra_to_pay;
		}

		// Get recurring price for the old product from the subscription.
		$old_recurring_fee = floatval( $old_product->get_meta( '_subscription_price' ) );
		//$new_recurring_fee = floatval( $new_product->get_meta( '_subscription_price' ) );

		// return 0;
		//error_log( 'old_recurring_fee: ' . json_encode( $old_recurring_fee, JSON_PRETTY_PRINT ) );
		// // error_log( 'new_recurring_fee: ' . json_encode( $new_recurring_fee, JSON_PRETTY_PRINT ) );

		// // de old lifetime a nueva periodica
		// if ( $is_old_lifetime && $is_new_lifetime ) {
		// return 0;
		// }

		return -$old_recurring_fee;
	}

	// public function lifetime_switch_proration_days_in_new_cycle( $days_in_new_cycle, $subscription, $cart_item, $days_in_old_cycle ) {

	// $product_id = $cart_item['variation_id'];

	// $product = wc_get_product( $product_id );

	// $billing_period = $product->get_meta( '_subscription_period', true );
	// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
	// $is_lifetime = $product->get_meta( '_is_lifetime', true );
	// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
	// if ( $is_lifetime ) {
	// $days_in_new_cycle = $days_in_old_cycle;
	// }

	// return $days_in_new_cycle;

	// }

	public function is_subscription_switch( $product_id, $switch_counter ) {
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

	public function lifetime_subscriptions_product_price_string( $subscription_string, $product, $include ) {

		if ( is_admin() ) {
			return $subscription_string;
		}

		static $switch_counter = -1;
		$switch_counter++;

		// $billing_period = $product->get_meta( '_subscription_period', true );
		// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
		$is_lifetime = $product->get_meta( '_is_lifetime', true );
		// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
		if ( ! $is_lifetime ) {
			return $subscription_string;
		}
		/**
		 * Check if the product is a switch.
		 */
		$is_switch = $this->is_subscription_switch( $product->get_id(), $switch_counter );

		if ( ! $is_switch ) {
			return $subscription_string;
		}

		$sign_up_fee = \WC_Subscriptions_Product::get_sign_up_fee( $product );

		if ( isset( $include['sign_up_fee'] ) && $sign_up_fee > 0 ) {
			$subscription_string = sprintf( __( '%s one time payment', 'lifetime-subscriptions-for-woocommerce' ), $include['sign_up_fee'] );
		}

		return $subscription_string;

	}

	// public function lifetime_subscriptions_product_length( $length, $product ) {
	// $billing_period = $product->get_meta( '_subscription_period', true );
	// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
	// $is_lifetime = $product->get_meta( '_is_lifetime', true );
	// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
	// if ( $is_lifetime ) {
	// $length = 1;
	// }
	// return $length;
	// }

	public function lifetime_subscriptions_switch_link( $switch_link, $item_id, $item, $subscription ) {
		// $billing_period = $subscription->get_data()['billing_period'];
		// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
		$is_lifetime = $subscription->get_meta( '_is_lifetime', true );
		// //error_log( 'lifetime_meta: ' . json_encode( $is_lifetime, JSON_PRETTY_PRINT ) );
		if ( ! $is_lifetime ) {
			return $switch_link;
		}
		$lifetime_subscriptions_switch_link = get_option( 'lifetime_subscriptions_switch_link', 'yes' );
		if ( 'yes' !== $lifetime_subscriptions_switch_link ) {
			return '';
		}
		return $switch_link;
	}

	// public function lifetime_recurring_cart_next_payment_date( $next_payment_date, $recurring_cart, $product ) {

	// //error_log( 'recurring_cart: ' . json_encode( $recurring_cart, JSON_PRETTY_PRINT ) );

	// return current_time( 'timestamp' );

	// $billing_period = $product->get_meta( '_subscription_period', true );
	// //error_log( 'billing_period: ' . json_encode( $billing_period, JSON_PRETTY_PRINT ) );
	// $is_lifetime = $product->get_meta( '_is_lifetime', true );
	// if ( ! $is_lifetime ) {
	// return $next_payment_date;
	// }
	// //error_log( 'next_payment_date: ' . json_encode( $next_payment_date, JSON_PRETTY_PRINT ) );

	// return 0;
	// }

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
