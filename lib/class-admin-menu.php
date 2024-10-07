<?php

namespace QuadLayers\LSFW;

class Admin_Menu {

	protected static $instance;

	private function __construct() {

		// Add an option for lifetime/onetime purchase In the subscription intervals. (day, week, month, year, lifetime).
		// add_filter( 'woocommerce_subscription_periods', array( $this, 'lifetime_subscription_period' ), 10, 1 );

		// // Add subscription Lifetime length to subscription array.
		// add_filter( 'woocommerce_subscription_lengths', array( $this, 'lifetime_subscription_length' ), 10, 2 );

		// Add settings in Woocommerce -> Settings -> Subscriptions
		add_filter( 'woocommerce_subscription_settings', array( $this, 'lifetime_subscription_label' ), 10, 1 );
	}

	public function lifetime_subscription_period( $period ) {
		$label              = get_option( 'lifetime_subscription_label', esc_html__( 'Lifetime', 'lifetime-subscriptions-for-woocommerce' ) );
		$label              = strtolower( $label );
		$period['lifetime'] = sprintf( _nx( 'lifetime', $label, 0, 'Subscription billing period.', 'lifetime-subscriptions-for-woocommerce' ), 0 );
		return $period;
	}

	public function lifetime_subscription_length( $subscription_ranges, $subscription_period ) {
		$subscription_ranges['lifetime'] = array( 'Never expire' );
		return $subscription_ranges;
	}

	public function lifetime_subscription_label( $settings ) {
		$settings[] =
		array(
			'name' => esc_html__( 'Lifetime Subscription', 'lifetime-subscriptions-for-woocommerce' ),
			'type' => 'title',
			'desc' => '',
			'id'   => 'lifetime_label',
		);
		$settings[] = array(
			'name'        => esc_html__( 'Label', 'lifetime-subscriptions-for-woocommerce' ),
			'desc'        => esc_html__( 'Define a custom label to denote a lifetime subscription. This label will appear on the product page replacing the "/Lifetime" text next to the product price. E.g., if you enter "Forever", it will display as "$x.xx /Forever".', 'lifetime-subscriptions-for-woocommerce' ),
			'tip'         => esc_html__( 'Use a descriptive term that best communicates a one-time payment for indefinite access.', 'lifetime-subscriptions-for-woocommerce' ), // Added a tip to guide the user in setting up a meaningful label
			'id'          => 'lifetime_subscription_label',
			'css'         => 'min-width:150px;',
			'default'     => esc_html__( 'Lifetime', 'lifetime-subscriptions-for-woocommerce' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'placeholder' => esc_html__( 'Enter custom label', 'lifetime-subscriptions-for-woocommerce' ),
		);
		$settings[] = array(
			'name'     => esc_html__( 'Switch Link', 'lifetime-subscriptions-for-woocommerce' ),
			'desc'     => esc_html__( 'Enable this option to allow users upgrading or downgrading their lifetime subscriptions.', 'lifetime-subscriptions-for-woocommerce' ),
			'tip'      => esc_html__( 'If enabled, users will be able to change their lifetime subscription level once it has been set.', 'lifetime-subscriptions-for-woocommerce' ), // Tip to help the admin understand the functionality
			'id'       => 'lifetime_subscriptions_switch_link',
			'css'      => 'min-width:150px;',
			'default'  => 'yes', // Set to 'no' to make the functionality disabled by default
			'type'     => 'checkbox',
			'desc_tip' => true,
		);
		$settings[] = array(
			'name'     => esc_html__( 'Switch Between Types', 'lifetime-subscriptions-for-woocommerce' ),
			'desc'     => esc_html__( 'Allow switch between lifetime and recurrent subscriptions.', 'lifetime-subscriptions-for-woocommerce' ),
			'tip'      => esc_html__( 'If enabled, users will be able to switch their lifetime subscriptions to recurrent subscriptions and vice versa.', 'lifetime-subscriptions-for-woocommerce' ), // Tip to help the admin understand the functionality
			'id'       => 'lifetime_subscriptions_switch_between_types',
			'css'      => 'min-width:150px;',
			'default'  => 'no', // Set to 'yes'/'no' to make the functionality enabled/disabled by default
			'type'     => 'checkbox',
			'desc_tip' => true,
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'lifetime_label',
		);

		return $settings;
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
