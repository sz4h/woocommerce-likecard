<?php

namespace Sz4h\WoocommerceLikecard;

class Initializer {


	private Woocommerce $woocommerce;
	private ProductFields $product_fields;
	private AdminSettings $admin_settings;

	public function __construct() {

		define( 'SPWL_PATH', trailingslashit( plugin_dir_path( __DIR__ ) ) );
		define( 'SPWL_URL', plugin_dir_url( __DIR__ ) );
		define( "SPWL_TD", 'space-woocommerce-likecard' );
		add_action( 'plugins_loaded', [ $this, 'text_domain' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );

		$this->admin_settings = new AdminSettings();
		$this->product_fields = new ProductFields();
		$this->woocommerce    = new Woocommerce();

	}

	function text_domain(): void {
		load_plugin_textdomain( SPWL_TD, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function wp_enqueue_scripts() {
		if ( is_view_order_page() ) {
			wp_enqueue_style( 'woocommerce-likecard', SPWL_URL . '/assets/css/woocommerce-likecard.css' );
		}
	}

}