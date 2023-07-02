<?php

namespace Sz4h\WoocommerceLikecard;

class AdminSettings {


	public function __construct() {
		add_action( 'cmb2_admin_init', [$this,'cmb2_admin_init'] );
	}

	function cmb2_admin_init(  ) {
		/**
		 * Registers options page menu item and form.
		 */
		$cmb_options = new_cmb2_box( array(
			'id'           => 'sz4h_options',
			'title'        => esc_html__( 'Likecard', SPWL_TD ),
			'object_types' => array( 'options-page' ),

			/*
			 * The following parameters are specific to the options-page box
			 * Several of these parameters are passed along to add_menu_page()/add_submenu_page().
			 */

			'option_key'      => 'likecard_options', // The option key and admin menu page slug.
			// 'icon_url'        => 'dashicons-palmtree', // Menu icon. Only applicable if 'parent_slug' is left empty.
			// 'menu_title'      => esc_html__( 'Options', 'myprefix' ), // Falls back to 'title' (above).
			 'parent_slug'     => 'options-general.php', // Make options page a submenu item of the themes menu.
			 'capability'      => 'manage_options', // Cap required to view options-page.
			// 'position'        => 1, // Menu position. Only applicable if 'parent_slug' is left empty.
			// 'admin_menu_hook' => 'network_admin_menu', // 'network_admin_menu' to add network-level options page.
			// 'display_cb'      => false, // Override the options-page form output (CMB2_Hookup::options_page_output()).
			// 'save_button'     => esc_html__( 'Save Theme Options', 'myprefix' ), // The text for the options-page save button. Defaults to 'Save'.
		) );

		/*
		 * Options fields ids only need
		 * to be unique within this box.
		 * Prefix is not needed.
		 */

		$cmb_options->add_field( array(
			'name' => __( 'Email', SPWL_TD ),
			'id'   => 'email',
			'type' => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Device Id', SPWL_TD ),
			'id'      => 'deviceId',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Security Code', SPWL_TD ),
			'id'      => 'securityCode',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Password', SPWL_TD ),
			'id'      => 'password',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Phone', SPWL_TD ),
			'id'      => 'phone',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Hash Key', SPWL_TD ),
			'id'      => 'hashKey',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Secret Key', SPWL_TD ),
			'id'      => 'secretKey',
			'type'    => 'text',
			'default' => '',
		) );

		$cmb_options->add_field( array(
			'name'    => __( 'Secret IV', SPWL_TD ),
			'id'      => 'secretIv',
			'type'    => 'text',
			'default' => '',
		) );
	}
}