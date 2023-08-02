<?php /** @noinspection PhpNoReturnAttributeCanBeAddedInspection */


if ( ! function_exists( 'dd' ) ) {
	/**
	 * @param mixed $value
	 *
	 * @return void
	 */
	function dd( mixed $value ): void {
		echo '<pre>';
		var_dump( $value );
		echo '</pre>';
		die();
	}
}