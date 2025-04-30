<?php

if ( class_exists( 'QuadLayers\\WP_Plugin_Table_Links\\Load' ) ) {
	add_action('init', function() {
		new \QuadLayers\WP_Plugin_Table_Links\Load(
			LSFW_PLUGIN_FILE,
			array(

				array(
					'text' => esc_html__( 'Settings', 'lifetime-subscriptions-for-woocommerce' ),
					'url'  => admin_url('admin.php?page=wc-settings&tab=subscriptions'),
					'target' => '_self',
				),
				array(
					'text' => esc_html__( 'Premium', 'lifetime-subscriptions-for-woocommerce' ),
					'url'  => LSFW_PREMIUM_SELL_URL,
				),
				array(
					'place' => 'row_meta',
					'text'  => esc_html__( 'Support', 'lifetime-subscriptions-for-woocommerce' ),
					'url'   => LSFW_SUPPORT_URL,
				),
				array(
					'place' => 'row_meta',
					'text'  => esc_html__( 'Documentation', 'lifetime-subscriptions-for-woocommerce' ),
					'url'   => LSFW_DOCUMENTATION_URL,
				),
			)
		);
	});
}
