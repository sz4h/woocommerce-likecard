<?php

namespace Sz4h\WoocommerceLikecard;

use Exception;
use Sz4h\WoocommerceLikecard\Exception\ApiException;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

class Woocommerce {

	private LikeCardApi $likecard_api;

	public function __construct() {
		$this->createApiInstance();

		add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );
//		add_action( 'woocommerce_new_order', [ $this, 'order_creation' ], 30, 1 );
		add_action( 'woocommerce_checkout_create_order_line_item', [
			$this,
			'woocommerce_checkout_create_order_line_item'
		], 30, 4 );

		/* Show in order details */
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'woocommerce_order_item_meta_end' ], 20, 4 );
		add_action( 'woocommerce_after_order_details', [ $this, 'woocommerce_after_order_details' ], 20, 1 );
//		add_action( 'woocommerce_order_details_after_order_table', [
//			$this,
//			'woocommerce_order_details_after_order_table'
//		] );
	}

	/**
	 * @throws ApiException
	 * @throws Exception
	 */
	function add_to_cart( $cart_id, $productId, $quantity, $variation_id, $variation, $cart_item_data ): void {
		$product = wc_get_product( $productId );

		if ( $variation_id ) {
			$likeCardId = (int) get_post_meta( $variation_id, 'sz4h_likecard_id', true );
		} else {
			$likeCardId = (int) $product->get_meta( 'sz4h_likecard_id' );
		}
		if ( $likeCardId === 0 ) {
			return;
		}
		$this->getProductsAvailability( [ $likeCardId ], $product );
	}

	public function woocommerce_checkout_create_order_line_item( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ): void {
		$productId  = $item->get_variation_id() ?? $item->get_product_id();
		$product    = $item->get_product();
		$likeCardId = (int) get_post_meta( $productId, 'sz4h_likecard_id', true );
		if ( $likeCardId === 0 ) {
			return;
		}
		try {
			$this->getProductsAvailability( [ $likeCardId ] );
		} catch ( Exception $e ) {
			$this->failed( [ $e->getMessage() ] );
		}
		$time = time();

		try {
			$response = $this->likecard_api->post( 'create_order', [
				'time'        => $time,
				'hash'        => $this->likecard_api->generateHash( $time ),
				'referenceId' => $order->get_id() . '_' . $productId,
				'productId'   => $likeCardId,
				'quantity'    => $item->get_quantity(),
			] );
		} catch ( ApiException $e ) {
			$this->failed( [ $e->getMessage() ] );
		}

		if ( ! isset( $response['serials'] ) || ! count( (array) $response['serials'] ) ) {
			return;
		}

		$serials = $item->get_meta( 'codes' ) ?: [];
		foreach ( $response['serials'] as $serial ) {
			$code      = $this->likecard_api->decryptSerial( @$serial['serialCode'] );
			$serials[] = [
				'serial' => $code,
				'valid'  => @$serial['validTo']
			];

			$order->add_order_note( sprintf( __( 'Code for %s is: %s and it\'s valid to %s', SPWL_TD ), $product->get_name(), $code, @$serial['validTo'] ) );
		}
		$item->update_meta_data( 'serials', $serials );
	}

	public function woocommerce_order_item_meta_end( $item_id, WC_Order_Item_Product $item, $order, $bool = false ): void {
		$serials = $item->get_meta( 'serials' ) ?: null;

		if ( ! $serials || count( $serials ) == 0 ) {
			return;
		}
		include SPWL_PATH . 'templates/like-card-serial.php';
	}

	public function woocommerce_after_order_details(): void {
		include SPWL_PATH . 'templates/like-card-serial-js.php';
	}
	/**
	 * @throws Exception
	 *//*
	public function order_creation( $order_id ): void {
		$likeCardIds = [];
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product                          = $cart_item['data'];
			$likeCardIds[ $cart_item['key'] ] = $product ? (int) $product->get_meta( 'sz4h_likecard_id' ) : null;
		}
		$likeCardIds = array_filter( $likeCardIds );
		if ( count( $likeCardIds ) ) {
			try {
				$this->getProductsAvailability( $likeCardIds );
			} catch ( Exception $e ) {
				$this->failed( [ $e->getMessage() ] );
			}
		}
		$time  = time();
		$order = wc_get_order( $order_id );


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

			$serials = [];
			foreach ( $response['serials'] as $serial ) {
				$code    = $this->likecard_api->decryptSerial( @$serial['serialCode'] );
				$product = $cartItem['data'];
				$name    = $product ? $product->get_name() : '';
				$serials = [
					'item_id' => $cartItem[''],
					'name'    => $name,
					'serial'  => $code,
					'valid'   => @$serial['validTo']
				];
				$order->add_order_note( sprintf( __( 'Code for %s is: %s and it\'s valid to %s', SPWL_TD ), $name, $code, @$serial['validTo'] ) );
			}
			add_post_meta( $order_id, 'serials', $serials );
		}

	}*/

	public function woocommerce_order_details_after_order_table( $order ): void {
		$serials = get_post_meta( $order->get_id(), 'serials' );
		if ( ! $serials ) {
			return;
		}
		foreach ( $serials as $serial ) {
			echo '<div class="box">' . sprintf( __( 'Code for %s is: <span>%s</span> valid to %s', SPWL_TD ), $serial['name'], $serial['serial'], $serial['valid'] ) . '</div>';
		}
	}

	protected function failed( array $errors, bool $refresh = false, bool $reload = false ): void {
		$errors     = '<li>' . implode( '</li><li>', $errors );
		$errorsHtml = "<ul class=\"woocommerce-error\" role=\"list\">\n\t\t\t$errors\n\t</ul>\n";
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
	 * @param bool|WC_Product|null $product
	 *
	 * @return void
	 * @throws Exception
	 */
	public function getProductsAvailability( array $ids, bool|null|WC_Product $product = null ): void {
		try {
			$response = $this->likecard_api->post( 'products', [
				'ids' => $ids
			] );


			if ( ! @$response['data'] ) {
				throw new Exception( __( 'Error in ordering', SPWL_TD ) . ' No data' );
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
			throw new Exception( __( 'Error in ordering', SPWL_TD ) . ':: ' . $product->get_name() );
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
			->set_hash_key( $options['hashKey'] )
			->set_secret_key( $options['secretKey'] )
			->set_secret_iv( $options['secretIv'] );
	}
}