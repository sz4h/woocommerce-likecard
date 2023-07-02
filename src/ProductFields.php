<?php

namespace Sz4h\WoocommerceLikecard;

class ProductFields {

	public function __construct() {
		$cmbInitPath = SPWL_PATH . 'vendor/cmb2/cmb2/init.php';
		if ( file_exists( $cmbInitPath ) ) {
			require_once $cmbInitPath;
		}

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
}