<?php

namespace Sz4h\WoocommerceLikecard;

class ProductFields {

	public function __construct() {
		$cmbInitPath = SPWL_PATH . 'vendor/cmb2/cmb2/init.php';
		if ( file_exists( $cmbInitPath ) ) {
			require_once $cmbInitPath;
		}

		add_action( 'woocommerce_product_after_variable_attributes', [
			$this,
			'woocommerce_product_after_variable_attributes'
		], 10, 3 );

		add_action( 'woocommerce_save_product_variation', [ $this, 'woocommerce_save_product_variation' ], 10, 2 );

		add_action( 'cmb2_admin_init', [ $this, 'cmb2_admin_init' ] );
	}

	function cmb2_admin_init(): void {
		$box = new_cmb2_box( [
			'id'           => $this->_( 'like_metabox' ),
			'title'        => esc_html__( 'Like Metabox', SPWL_TD ),
			'object_types' => [ 'product' ],
		] );
		$box->add_field( [
			'name' => esc_html__( 'Like Card Ref. ID', SPWL_TD ),
			'id'   => $this->_( 'likecard_id' ),
			'type' => 'text',
		] );
	}


	public function _( string $string ): string {
		return "sz4h_$string";
	}

	public function woocommerce_product_after_variable_attributes( $loop, $variation_data, $variation ): void {
		echo '<div class="variation-custom-fields">';
		woocommerce_wp_text_input(
			array(
				'id'            => 'sz4h_likecard_id[' . $loop . ']',
				'label'         => __( 'Like Card Ref. ID', SPWL_TD ),
				'placeholder'   => '',
				//'desc_tip'    => true,
				'wrapper_class' => 'form-row form-row-first',
				//'description' => __( 'Enter the custom value here.', 'woocommerce' ),
				'value'         => get_post_meta( $variation->ID, 'sz4h_likecard_id', true )
			)
		);
		echo '</div>';
	}

	public function woocommerce_save_product_variation( $variation_id, $i ): void {
		$likeCardId = stripslashes( $_POST['sz4h_likecard_id'][ $i ] );
		if ( is_numeric( $likeCardId ) || empty( $likeCardId) ) {
			update_post_meta( $variation_id, 'sz4h_likecard_id', esc_attr( $likeCardId ) );
		}

	}
}