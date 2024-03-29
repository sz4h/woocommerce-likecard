<?php

namespace Sz4h\WoocommerceLikecard;

use Sz4h\WoocommerceLikecard\Exception\ApiException;

class LikeCardApi {

	private string $base = 'https://taxes.like4app.com/online/';

	private string $deviceId = '';
	private string $email = '';
	private string $password = '';
	private string $securityCode = '';
	private int $timeout = 30;
	private int $langId = 1;
	private string $phone = '';
	private string $hashKey = '';
	private string $secretKey;
	private string $secretIv;


	/**
	 * @throws ApiException
	 */
	public function post( string $url, array $additionalParameters = [], array $headers = [ 'accept' => 'application/json' ], array $cookies = [], string $method = 'POST' ) {
		$response = wp_remote_post( $this->get_base() . $url, array(
				'method'      => $method,
				'timeout'     => $this->get_timeout(),
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => $headers,
				'body'        => [
					'deviceId'     => $this->get_device_id(),
					'email'        => $this->get_email(),
					'password'     => $this->get_password(),
					'securityCode' => $this->get_security_code(),
					'langId'       => $this->get_lang_id(),
					...$additionalParameters
				],
				'cookies'     => $cookies
			)
		);

		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_null( $body ) || ! isset( $body['response'] ) || $body['response'] !== 1 ) {
				throw new ApiException( __( 'Error in ordering', SPWL_TD ) . ' No response' . json_encode( $body) );
			}


			return $body;
		} else {
			$error_message = $response->get_error_message();
			throw new ApiException( $error_message );
		}
	}


	function generateHash( $time ): string {
		$email = strtolower( $this->get_email() );
		$phone = $this->get_phone();
		$key   = $this->get_hash_key();

		return hash( 'sha256', $time . $email . $phone . $key );
	}


	function decryptSerial( string $encrypted_txt ): string {
		$encrypt_method = 'AES-256-CBC';
		$key            = hash( 'sha256', $this->get_secret_key() );

		//iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr( hash( 'sha256', $this->get_secret_iv() ), 0, 16 );

		return openssl_decrypt( base64_decode( $encrypted_txt ), $encrypt_method, $key, 0, $iv );
	}

	/**
	 * @return string
	 */
	public function get_base(): string {
		return $this->base;
	}


	/**
	 * @return string
	 */
	public function get_device_id(): string {
		return $this->deviceId;
	}

	/**
	 * @param string $deviceId
	 *
	 * @return LikeCardApi
	 */
	public function set_device_id( string $deviceId ): LikeCardApi {
		$this->deviceId = $deviceId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_email(): string {
		return $this->email;
	}

	/**
	 * @param string $email
	 *
	 * @return LikeCardApi
	 */
	public function set_email( string $email ): LikeCardApi {
		$this->email = $email;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_password(): string {
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * @return LikeCardApi
	 */
	public function set_password( string $password ): LikeCardApi {
		$this->password = $password;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_security_code(): string {
		return $this->securityCode;
	}

	/**
	 * @param string $securityCode
	 *
	 * @return LikeCardApi
	 */
	public function set_security_code( string $securityCode ): LikeCardApi {
		$this->securityCode = $securityCode;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_timeout(): int {
		return $this->timeout;
	}
	/**
	 * @return int
	 */
	public function get_lang_id(): int {
		return $this->langId;
	}


	/**
	 * @return string
	 */
	public function get_phone(): string {
		return $this->phone;
	}

	/**
	 * @param string $phone
	 *
	 * @return LikeCardApi
	 */
	public function set_phone( string $phone ): LikeCardApi {
		$this->phone = $phone;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_hash_key(): string {
		return $this->hashKey;
	}

	/**
	 * @param string $hashKey
	 *
	 * @return LikeCardApi
	 */
	public function set_hash_key( string $hashKey ): LikeCardApi {
		$this->hashKey = $hashKey;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_secret_key(): string {
		return $this->secretKey;
	}

	/**
	 * @param string $secretKey
	 *
	 * @return LikeCardApi
	 */
	public function set_secret_key( string $secretKey ): LikeCardApi {
		$this->secretKey = $secretKey;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_secret_iv(): string {
		return $this->secretIv;
	}

	/**
	 * @param string $secretIv
	 *
	 * @return LikeCardApi
	 */
	public function set_secret_iv( string $secretIv ): LikeCardApi {
		$this->secretIv = $secretIv;

		return $this;
	}
}