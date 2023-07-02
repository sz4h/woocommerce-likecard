<?php
use JetBrains\PhpStorm\NoReturn;

if (!function_exists( 'dd')) {
	/**
	 * @param mixed $value
	 *
	 * @return void
	 */
	#[NoReturn] function dd( mixed $value ): void {
		echo  '<pre>';
		echo var_dump( $value);
		echo '</pre>';
		die();
	}
}