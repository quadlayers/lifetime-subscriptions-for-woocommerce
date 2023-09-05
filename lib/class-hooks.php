<?php

namespace QuadLayers\LSFW;

class Hooks {

	protected static $instance;

	private function __construct() {
		add_filter( 'woocommerce_before_calculate_totals', array( __CLASS__, 'maybe_set_free_trial' ), 100, 1 );
		add_action( 'woocommerce_subscription_cart_before_grouping', array( __CLASS__, 'maybe_unset_free_trial' ) );
		add_action( 'woocommerce_subscription_cart_after_grouping', array( __CLASS__, 'maybe_set_free_trial' ) );
		add_action( 'wcs_recurring_cart_start_date', array( __CLASS__, 'maybe_unset_free_trial' ), 0, 1 );
		add_action( 'wcs_recurring_cart_end_date', array( __CLASS__, 'maybe_set_free_trial' ), 100, 1 );
		add_filter( 'woocommerce_subscriptions_calculated_total', array( __CLASS__, 'maybe_unset_free_trial' ), 10000, 1 );
		add_action( 'woocommerce_cart_totals_before_shipping', array( __CLASS__, 'maybe_set_free_trial' ) );
		add_action( 'woocommerce_cart_totals_after_shipping', array( __CLASS__, 'maybe_unset_free_trial' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( __CLASS__, 'maybe_set_free_trial' ) );
		add_action( 'woocommerce_review_order_after_shipping', array( __CLASS__, 'maybe_unset_free_trial' ) );

		// Allow users to discount the full price of the subscription.
		add_filter( 'wcs_switch_proration_extra_to_pay', array( $this, 'lifetime_switch_proration_extra_to_pay' ), 10, 4 );

		// Make sure the proration is calculated. This is done by setting the days_in_new_cycle to the days_in_old_cycle.
		add_filter( 'wcs_switch_proration_days_in_new_cycle', array( $this, 'lifetime_switch_proration_days_in_new_cycle' ), 10, 4 );

		// Removes next payment date from cart and checkout. This will remove recurrent payment.
		add_filter( 'wcs_recurring_cart_next_payment_date', array( $this, 'lifetime_recurring_cart_next_payment_date' ), 10, 3 );

		// Change the price string to a one time payment by removing the period and the sign up fee.
		add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'lifetime_subscriptions_product_price_string' ), 10, 3 );

		// Mame sure the lifetime subscription is not renewed. This is done by setting period to 1. This will the function is_switch_to_one_payment_subscription return true.
		add_filter( 'woocommerce_subscriptions_product_length', array( $this, 'lifetime_subscriptions_product_length' ), 10, 2 );

		// Removes the Upgrade subscription switch/button from subscription. No upgrade if subscription is lifetime.
		add_filter( 'woocommerce_subscriptions_switch_link', array( $this, 'lifetime_subscriptions_switch_link' ), 10, 4 );
	}

	/**
	 * Fix switch between lifetime subscriptions
	 */
	public static function maybe_set_free_trial( $total = '' ) {

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['subscription_switch']['subscription_id'] ) ) {
				$subscription   = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );
				$billing_period = $subscription->get_data()['billing_period'];
				if ( 'lifetime' === $billing_period ) {
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
				$subscription   = wcs_get_subscription( $cart_item['subscription_switch']['subscription_id'] );
				$billing_period = $subscription->get_data()['billing_period'];
				if ( 'lifetime' === $billing_period ) {
					wcs_set_objects_property( WC()->cart->cart_contents[ $cart_item_key ]['data'], 'subscription_trial_length', 0, 'set_prop_only' );
				}
			}
		}
		return $total;
	}

	public function lifetime_switch_proration_extra_to_pay( $extra_to_pay, $subscription, $cart_item, $get_days_in_old_cycle ) {

		$product_id = $cart_item['variation_id'];

		$product = wc_get_product( $product_id );

		$billing_period = $product->get_meta( '_subscription_period', true );

		if ( 'lifetime' !== $billing_period ) {
			return $extra_to_pay;
		}

		$new_price = \WC_Subscriptions_Product::get_price( $product ) * $cart_item['quantity'];

		$old_price = $subscription->get_total();

		$extra_to_pay = $new_price - $old_price;

		return $extra_to_pay;
	}

	public function lifetime_switch_proration_days_in_new_cycle( $days_in_new_cycle, $subscription, $cart_item, $days_in_old_cycle ) {

		$product_id = $cart_item['variation_id'];

		$product = wc_get_product( $product_id );

		$billing_period = $product->get_meta( '_subscription_period', true );

		if ( 'lifetime' === $billing_period ) {
			$days_in_new_cycle = $days_in_old_cycle;
		}

		return $days_in_new_cycle;

	}

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

		$billing_period = $product->get_meta( '_subscription_period', true );

		if ( 'lifetime' !== $billing_period ) {
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

	public function lifetime_subscriptions_product_length( $length, $product ) {
		$billing_period = $product->get_meta( '_subscription_period', true );
		if ( 'lifetime' === $billing_period ) {
			$length = 1;
		}
		return $length;
	}

	public function lifetime_subscriptions_switch_link( $switch_link, $item_id, $item, $subscription ) {
		$billing_period = $subscription->get_data()['billing_period'];
		if ( 'lifetime' !== $billing_period ) {
			return $switch_link;
		}
		$lifetime_subscriptions_switch_link = get_option( 'lifetime_subscriptions_switch_link', 'yes' );
		if ( 'yes' !== $lifetime_subscriptions_switch_link ) {
			return '';
		}
		return $switch_link;
	}

	public function lifetime_recurring_cart_next_payment_date( $next_payment_date, $recurring_cart, $product ) {
		$billing_period = $product->get_meta( '_subscription_period', true );
		if ( 'lifetime' !== $billing_period ) {
			return $next_payment_date;
		}
		return 0;
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
