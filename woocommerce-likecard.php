<?php
/**
 * Plugin Name: Space Woocommerce Like4Card Integration
 * Description:
 * Plugin URI: https://sz4h.com/
 * Author: Ahmed Safaa
 * Version: 1.0.7
 * Author URI: https://sz4h.com/
 *
 * Text Domain: space-woocommerce-likecard
 *
 */
use Sz4h\WoocommerceLikecard\Initializer;

/** @noinspection SpellCheckingInspection */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'vendor/autoload.php';

new Initializer();