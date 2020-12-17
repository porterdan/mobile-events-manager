<?php
/**
 * Payment Actions
 *
 * @package TMEM
 * @subpackage Payments
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads a payment gateway via AJAX
 *
 * @since 1.3.8
 * @return void
 */
function tmem_load_ajax_gateway() {
	if ( isset( $_POST['tmem_payment_mode'] ) ) {
		do_action( 'tmem_payment_form' );
		exit();
	}
} // tmem_load_ajax_gateway
add_action( 'wp_ajax_tmem_load_gateway', 'tmem_load_ajax_gateway' );
add_action( 'wp_ajax_nopriv_tmem_load_gateway', 'tmem_load_ajax_gateway' );
