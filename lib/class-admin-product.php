<?php

namespace QuadLayers\LSFW;

class Admin_Product {

	protected static $instance;

	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Add product lifetime meta.
		add_action( 'woocommerce_variation_options', array( $this, 'add_options' ), 10, 3 );
		// Fire hook before subscription is saved.
		add_action( 'woocommerce_save_product_variation', array( $this, 'save' ), 9999, 2 );
	}

	public function enqueue_scripts() {

		$screen = get_current_screen();

		$backend = include LSFW_PLUGIN_DIR . 'build/backend/js/index.asset.php';

		wp_register_style( 'lsfw-admin', plugins_url( '/build/backend/css/style.css', LSFW_PLUGIN_FILE ), false, LSFW_PLUGIN_VERSION );

		wp_register_script( 'lsfw-admin', plugins_url( '/build/backend/js/index.js', LSFW_PLUGIN_FILE ), $backend['dependencies'], $backend['version'], false );

		//  phpcs:ignore WordPress.PHP.StrictInArray.FoundNonStrictFalse
		if ( ! isset( $screen->id ) || ! in_array( $screen->id, array( 'product', 'edit-product', 'shop_order', 'edit-shop_order' ), false ) ) {
			return;
		}

		wp_enqueue_script( 'lsfw-admin' );
		wp_enqueue_style( 'lsfw-admin' );
	}

	public static function add_options( $loop, $variation_data, $variation ) {

		$product = wc_get_product( $variation );

		?>
			<label class="tips" data-tip="<?php esc_html_e( 'Enable this option if you want to make this subscription lifetime.', 'lifetime-subscriptions-for-woocommerce' ); ?>">
				<?php esc_html_e( 'Lifetime', 'lifetime-subscriptions-for-woocommerce' ); ?>
				<input id="_is_lifetime" type="checkbox" class="checkbox" name="_is_lifetime[<?php echo esc_attr( $loop ); ?>]" <?php checked( $product->get_meta( '_is_lifetime', true ), true ); ?> />
			</label>
		<?php
	}

	public static function save( $variation_id, $loop ) {

		$product = wc_get_product( $variation_id );

		if ( ! $product->get_id() ) {
			return;
		}

		if ( isset( $_POST['_is_lifetime'][ $loop ] ) ) {
			$value = wc_clean( wp_unslash( $_POST['_is_lifetime'][ $loop ] ) );

			$value = 'on' === $value ? true : false;

			$product->update_meta_data( '_is_lifetime', $value );
			$product->update_meta_data( '_subscription_period', 'year' );
			$product->update_meta_data( '_subscription_period_interval', 1 );
			$product->update_meta_data( '_subscription_length', 0 );
			$product->update_meta_data( '_subscription_price', 0 );
			$product->set_sale_price( 0 );
			$product->set_regular_price( 0 );
			$product->set_price( 0 );

		} else {
			$product->delete_meta_data( '_is_lifetime' );
		}

		$product->save();
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
