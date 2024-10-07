<?php

namespace QuadLayers\LSFW;

class Hook_Product {

	protected static $instance;

	private function __construct() {
		/**
		 * Set the subscription period to 1 year.
		 * The lifetime subscription should never expire.
		 */
		// add_filter( 'woocommerce_product_variation_get__subscription_period', array( $this, 'lifetime_subscription_period' ), 10, 2 );
		// add_filter( 'woocommerce_product_variation_get__subscription_period_interval', array( $this, 'lifetime_subscription_period_interval' ), 10, 2 );
		// add_filter( 'woocommerce_product_variation_get__subscription_length', array( $this, 'lifetime_subscription_length' ), 10, 2 );
		// /**
		// * Set the price of the lifetime subscription to 0.
		// * The lifetime subscription should be set via sign up fee.
		// */
		// add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'lifetime_price' ), 10, 2 );
		// add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'lifetime_price' ), 10, 2 );
		// add_filter( 'woocommerce_product_variation_get_price', array( $this, 'lifetime_price' ), 10, 2 );
		/**
		 * Remove next payment date in purchase.
		 */
		add_filter( 'woocommerce_subscription_calculated_next_payment_date', array( $this, 'lifetime_calculated_next_payment_date' ), 10, 2 );
		add_filter( 'woocommerce_subscriptions_product_first_renewal_payment_time', array( $this, 'lifetime_first_renewal_payment_time' ), 10, 4 );
		/**
		 * Remove the next payment date in subscription switch.
		 */
		add_action( 'woocommerce_subscriptions_switched_item', array( $this, 'lifetime_switch_next_payment_date' ), 10, 3 );
	}

	public function lifetime_subscription_period( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 'year';
	}

	public function lifetime_subscription_period_interval( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 1;
	}

	public function lifetime_subscription_length( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	}

	public function lifetime_price( $value, $data ) {
		$is_lifetime = $data->get_meta( '_is_lifetime', true );
		if ( ! $is_lifetime ) {
			return $value;
		}
		return 0;
	}

	/**
	 * Check if the subscription has a recurrent product and return the date if it does.
	 * There is no issue with the renewal of lifetime subscriptions because the recurring price is 0.
	 */
	public function lifetime_calculated_next_payment_date( $date, $subscription ) {

		$has_recurrent_products = array_map(
			function ( $item ) {
				$product = wc_get_product( $item->get_variation_id() );
				if ( ! $product ) {
					return true;
				}
				$is_lifetime = $product->get_meta( '_is_lifetime', true );
				return ! $is_lifetime;
			},
			$subscription->get_items()
		);

		if ( is_array( $has_recurrent_products ) && in_array( true, $has_recurrent_products, true ) ) {
			return $date;
		}

		return 0;
	}

	/**
	 * Remove the next payment date from the subscription in the cart.
	 * If we don't remove it, the hook 'woocommerce_subscription_calculated_next_payment_date' will not be triggered.
	 * I can't believe this level of hack is necessary. I haven't encountered worse code in my life.
	 */
	public function lifetime_first_renewal_payment_time( $first_renewal_timestamp, $product, $from_date_param, $timezone ) {

		$is_lifetime = $product->get_meta( '_is_lifetime', true );

		if ( ! $is_lifetime ) {
			return $first_renewal_timestamp;
		}
		return 0;
	}

	public function lifetime_switch_next_payment_date( $subscription, $new_order_item, $old_order_item ) {
		/**
		 * Check if the subscription has a recurrent product and return the date if it does.
		 * There is no issue with the renewal of lifetime subscriptions because the recurring price is 0.
		 */
		$has_recurrent_products = array_map(
			function ( $item ) {
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
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
