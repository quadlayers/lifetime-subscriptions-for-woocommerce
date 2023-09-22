<?php

namespace QuadLayers\LSFW;

class Admin_Product {

	protected static $instance;

	private function __construct() {
		// Add product lifetime meta
		add_action( 'woocommerce_variation_options', array( $this, 'add_options' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save' ), 10, 2 );

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
