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
					'url'  => 'https://quadlayers.com/products/woocommerce-license-manager/?utm_source=lsfw_plugin&utm_medium=plugin_table&utm_campaign=cross_sell&utm_content=premium_link',
					'color' => 'green',
					'target' => '_blank',
				),
				array(
					'place' => 'row_meta',
					'text'  => esc_html__( 'Support', 'lifetime-subscriptions-for-woocommerce' ),
					'url'   => 'https://quadlayers.com/account/support/?utm_source=lsfw_plugin&utm_medium=plugin_table&utm_campaign=cross_sell&utm_content=support_link',
				),
				array(
					'place' => 'row_meta',
					'text'  => esc_html__( 'Documentation', 'lifetime-subscriptions-for-woocommerce' ),
					'url'   => 'https://quadlayers.com/documentation/woocommerce-license-manager/?utm_source=lsfw_plugin&utm_medium=plugin_table&utm_campaign=cross_sell&utm_content=documentation_link',
				),
			)
		);
	});
}
