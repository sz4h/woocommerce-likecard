<?php /** @noinspection PhpNoReturnAttributeCanBeAddedInspection */


if ( ! function_exists( 'dd' ) ) {
	/**
	 * @param mixed $value
	 *
	 * @return void
	 */
	function dd( mixed ...$value ): void {
		dump(...func_get_args());
		die();
	}
}
if ( ! function_exists( 'dump' ) ) {
	/**
	 * @param mixed $value
	 *
	 * @return void
	 */
	function dump( mixed ...$value ): void {
		foreach ( func_get_args() as $item ) {
			echo '<pre>';
			var_dump( $item );
			echo '</pre>';
		}
	}
}