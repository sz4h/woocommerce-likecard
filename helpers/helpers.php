<?php
use JetBrains\PhpStorm\NoReturn;

if (!function_exists( 'dd')) {
	/**
	 * @param mixed $value
	 *
	 * @return void
	 */
	#[NoReturn] function dd( mixed $value ): void {
		echo  '<pre>' . var_dump( $value) .'</pre>';
		die();
	}
}