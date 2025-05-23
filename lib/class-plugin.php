<?php

namespace QuadLayers\LSFW;

final class Plugin {

	protected static $instance;

	private function __construct() {
		/**
		 * Load plugin textdomain.
		 */
		add_action( 'init', array( $this, 'load_textdomain' ) );

		/**
		 * Load plugin classes.
		 */
		add_action(
			'woocommerce_init',
			function () {
				Admin_Menu::instance();
				Admin_Product::instance();
				Hook_Product::instance();
				Hook_Switch::instance();
			}
		);
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'lifetime-subscriptions-for-woocommerce', false, LSFW_PLUGIN_DIR . '/languages/' );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

Plugin::instance();
