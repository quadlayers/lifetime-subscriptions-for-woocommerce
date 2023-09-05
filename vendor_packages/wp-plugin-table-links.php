<?php

if ( class_exists( 'QuadLayers\\WP_Plugin_Table_Links\\Load' ) ) {
	new \QuadLayers\WP_Plugin_Table_Links\Load(
		LSFW_PLUGIN_FILE,
		array(
			array(
				'text' => esc_html__( 'Support', 'lifetime-subscriptions-for-woocommerce' ),
				'url'  => LSFW_SUPPORT_URL,
			),
			array(
				'text' => esc_html__( 'Settings', 'lifetime-subscriptions-for-woocommerce' ),
				'url'  => admin_url('admin.php?page=wc-settings&tab=subscriptions'),
			),
		)
	);
}
