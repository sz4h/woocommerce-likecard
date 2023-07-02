<?php

namespace Sz4h\WoocommerceLikecard;

use Exception;
use Sz4h\WoocommerceLikecard\Exception\ApiException;

class Woocommerce {

	private LikeCardApi $likecard_api;

	public function __construct() {
		$this->createApiInstance();
		add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
		add_action( 'woocommerce_new_order', [ $this, 'order_creation' ], 10, 1 );

	}

	/**
	 * @throws ApiException
	 * @throws Exception
	 */
	function add_to_cart( $cart_id, $productId, $quantity, $variation_id, $variation, $cart_item_data ): void {
		$product    = wc_get_product( $productId );
		$likeCardId = (int) $product->get_meta( 'sz4h_likecard_id' );
		if ( $likeCardId === 0 ) {
			return;
		}
		$this->getPorductsAvailablity( [ $likeCardId ], $product );
	}

	/**
	 * @throws Exception
	 */
	public function order_creation( $order_id ): void {
		$likeCardIds = [];
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product                          = $cart_item['data'];
			$likeCardIds[ $cart_item['key'] ] = $product ? (int) $product->get_meta( 'sz4h_likecard_id' ) : null;
		}
		$likeCardIds = array_filter( $likeCardIds );
		if ( count( $likeCardIds ) ) {
			try {
				$this->getPorductsAvailablity( $likeCardIds );
			} catch ( Exception $e ) {
				$this->failed( [ $e->getMessage() ] );
			}
		}
		$time = time();
		foreach ( $likeCardIds as $cart_item_key => $like_card_id ) {
			$cartItem = WC()->cart->get_cart()[ $cart_item_key ];

			$response = $this->likecard_api->post( 'create_order', [
				'time'        => $time,
				'hash'        => $this->likecard_api->generateHash( $time ),
				'referenceId' => $order_id . '_' . $cartItem['product_id'],
				'productId'   => $like_card_id,
				'quantity'    => $cartItem['quantity'],
			] );
			if ( ! isset( $response['serials'] ) || ! count( (array) $response['serials'] ) ) {
				return;
			}
			$order = wc_get_order( $order_id );
			foreach ( $response['serials'] as $serial ) {
				$product = $cartItem['data'];
				$name    = $product ? $product->get_name() : '';
				$order->add_order_note( sprintf( __( 'Serial for %s is: %s' ), $name, @$serial['serialCode'] ) );
			}
		}

	}

	protected function failed( array $errors, bool $refresh = false, bool $reload = false ): void {
		$errors     = '<li>' . implode( '</li><li>', $errors );
		$errorsHtml = "<ul class=\"woocommerce-error\" role=\"alert\">\n\t\t\t$errors\n\t</ul>\n";
		echo json_encode( [
			'result'   => 'failure',
			'messages' => $errorsHtml,
			'refresh'  => $refresh,
			'reload'   => $reload,
		] );
		die();
	}

	/**
	 * @param array $ids
	 * @param bool|\WC_Product|null $product
	 *
	 * @return void
	 * @throws Exception
	 */
	public function getPorductsAvailablity( array $ids, bool|\WC_Product|null $product = null ): void {
		try {
			$response = $this->likecard_api->post( 'products', [
				'ids' => $ids
			] );


			if ( ! @$response['data'] ) {
				throw new Exception( __( 'Error in ordering', SPWL_TD ) );
			}
			$names = [];
			foreach ( $response['data'] as $product ) {
				if ( ! $product['available'] ) {
					$names[] = @$product['productName'];
				}
			}
			if ( count( $names ) ) {
				throw new Exception( __( 'Error in ordering', SPWL_TD ) . ' (' . implode( ',', $names ) . ').' );
			}
		} catch ( ApiException $e ) {
			throw new Exception( __( 'Error in ordering', SPWL_TD ) . ':: ' . $product?->get_name() );
		}
	}

	/**
	 * @return void
	 */
	public function createApiInstance(): void {
		$options            = get_option( 'likecard_options' );
		$this->likecard_api = new LikeCardApi();
		if ( ! @$options['email'] ) {
			return;
		}
		$this->likecard_api
			->set_email( $options['email'] )
			->set_password( $options['password'] )
			->set_device_id( $options['deviceId'] )
			->set_phone( $options['phone'] )
			->set_security_code( $options['securityCode'] )
			->set_hash_key( $options['hashKey'] );
	}
}