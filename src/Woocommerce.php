<?php /** @noinspection PhpNoReturnAttributeCanBeAddedInspection */

/** @noinspection PhpUnusedParameterInspection */

namespace Sz4h\WoocommerceLikecard;

use Exception;
use Sz4h\WoocommerceLikecard\Exception\ApiException;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;

class Woocommerce {

	private LikeCardApi $likecard_api;

	public function __construct() {
		$this->createApiInstance();

		add_action( 'woocommerce_add_to_cart', [ $this, 'add_to_cart' ], 10, 6 );


		add_action( 'woocommerce_pre_payment_complete', [ $this, 'woocommerce_payment_complete' ], 10, 2 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'woocommerce_payment_complete' ], 10, 2 );
		/* Show in order details */
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'woocommerce_order_item_meta_end' ], 20, 4 );
		add_action( 'woocommerce_after_order_details', [ $this, 'woocommerce_after_order_details' ], 20 );
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

	public function woocommerce_payment_complete( $order_id, $transaction_id = null ): void {
		$order = wc_get_order( $order_id );
		if ( $order->get_meta( 'likecard_completed' ) ) {
			return;
		}
		$items        = $order->get_items();
		$cardProducts = $this->getCardProducts( $items );
		if ( count( $cardProducts ) === 0 ) {
			$this->completeOrderLikeCardProcess( $order );
			return;
		}

		$likeCardIds = array_map( fn( $item ) => $item['productId'], $cardProducts );

		/* Check Cards Availability */
		try {
			$this->getProductsAvailability( $likeCardIds );
		} catch ( Exception $e ) {
			$this->failed( [ $e->getMessage() ] );
		}
		$createOrderResponse      = $this->createBulkOrder( $order, $cardProducts );
		$bulkOrderDetailsResponse = $this->getBulkOrderDetails( $createOrderResponse );

		if ( ! isset( $bulkOrderDetailsResponse['orders'] ) || ! count( (array) $bulkOrderDetailsResponse['orders'] ) ) {
			return;
		}

		$mapping         = $this->getSerialCodesFromResponse( $bulkOrderDetailsResponse['orders'] );
		$storeProductIds = array_column( $cardProducts, 'storeProductId' );

		foreach ( $items as $item ) {
			$productId = $this->getOriginalItemId( $item );
			if ( ! in_array( $productId, $storeProductIds ) ) {
				continue;
			}

			$likeCardId = (int) get_post_meta( $productId, 'sz4h_likecard_id', true );
			$serials    = $item->get_meta( 'serials' ) ?: [];

			foreach ( $mapping[ $likeCardId ] as $codes ) {
				foreach ( $codes as $code ) {
					$serials[] = $code;
					$order->add_order_note( sprintf( __( 'Code for %s is: %s and it\'s valid to %s', SPWL_TD ), $item->get_name(), $code['serial'], @$code['validTo'] ) );
				}
			}
			$item->update_meta_data( 'serials', $serials );
			$item->save_meta_data();
		}
		$this->completeOrderLikeCardProcess( $order );
	}

	public function woocommerce_order_item_meta_end( $item_id, WC_Order_Item_Product $item, $order, $bool = false ): void {
		$serials = $item->get_meta( 'serials' ) ?: null;
//dd($serials);
		if ( ! $serials || count( $serials ) == 0 ) {
			return;
		}
		include SPWL_PATH . 'templates/like-card-serial.php';
	}

	public function woocommerce_after_order_details(): void {
		include SPWL_PATH . 'templates/like-card-serial-js.php';
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
			foreach ( $response['data'] as $p ) {
				if ( ! $p['available'] ) {
					$names[] = @$p['productName'];
				}
			}
			if ( count( $names ) ) {
				throw new Exception( __( 'Error in ordering', SPWL_TD ) . ' (' . implode( ',', $names ) . ').' );
			}
		} catch ( ApiException ) {
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


	function getCardProducts( array $items ): array {
		$products = [];

		foreach ( $items as $item ) {
			/** @var WC_Order_Item_Product $item */
			$productId = $this->getLikeCardId( $item );
			if ( ! $productId ) {
				continue;
			}
			$products[] = [
				'productId'      => $productId,
				'storeProductId' => $this->getOriginalItemId( $item ),
				'quantity'       => $item->get_quantity(),
			];
		}

		return $products;
	}

	private function getLikeCardId( WC_Order_Item_Product|WC_Order_Item $item ): ?int {
		$productId  = $this->getOriginalItemId( $item );
		$likeCardId = (int) get_post_meta( $productId, 'sz4h_likecard_id', true );

		return $likeCardId !== 0 ? $likeCardId : null;
	}

	/**
	 * @param WC_Order|bool $order
	 * @param array $cardProducts
	 *
	 * @return mixed
	 */
	public function createBulkOrder( WC_Order|bool $order, array $cardProducts ): mixed {
		$time        = time();
		$referenceId = "{$order->get_id()}_order_$time";
		$response    = null;
		try {
			$response = $this->likecard_api->post( 'create_order/bulk', [
				'time'        => $time,
				'hash'        => $this->likecard_api->generateHash( $time ),
				'referenceId' => $referenceId,
				'products'    => json_encode( $cardProducts ),
			] );
		} catch ( ApiException $e ) {
			$this->failed( [ $e->getMessage() ] );
		}

		return $response;
	}

	/**
	 * @param mixed $createOrderResponse
	 *
	 * @return mixed
	 */
	public function getBulkOrderDetails( mixed $createOrderResponse ): mixed {
		$response = null;
		try {
			$response = $this->likecard_api->post( 'get_bulk_order', [
				'bulkOrderId' => $createOrderResponse['bulkOrderId'],
			] );
		} catch ( ApiException $ee ) {
			$this->failed( [ $ee->getMessage() ] );
		}

		return $response;
	}

	/**
	 * @param $orders
	 *
	 * @return array
	 */
	public function getSerialCodesFromResponse( $orders ): array {
		$mapping = [];
		foreach ( $orders as $likeCardOrder ) {
			$serials = [];
			foreach ( $likeCardOrder['serials'] as $serial ) {
				$code      = $this->likecard_api->decryptSerial( @$serial['serialCode'] );
				$serials[] = [
					'serial' => $code,
					'valid'  => @$serial['validTo']
				];
			}
			$mapping[ $likeCardOrder['productId'] ][] = $serials;
		}

		return $mapping;
	}

	/**
	 * @param WC_Order_Item_Product|WC_Order_Item $item
	 *
	 * @return int
	 */
	public function getOriginalItemId( WC_Order_Item_Product|WC_Order_Item $item ): int {
		return $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
	}

	/**
	 * @param WC_Order|bool|\WC_Order_Refund $order
	 *
	 * @return void
	 */
	public function completeOrderLikeCardProcess( WC_Order|bool|\WC_Order_Refund $order ): void {
		$order->update_meta_data( 'likecard_completed', 1 );
		$order->save_meta_data();
	}
}