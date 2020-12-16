<?php
/**
 * Payment Actions
 *
 * @package MEM
 * @subpackage Payments
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
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
function mem_load_ajax_gateway() {
	if ( isset( $_POST['mem_payment_mode'] ) ) {
		do_action( 'mem_payment_form' );
		exit();
	}
} // mem_load_ajax_gateway
add_action( 'wp_ajax_mem_load_gateway', 'mem_load_ajax_gateway' );
add_action( 'wp_ajax_nopriv_mem_load_gateway', 'mem_load_ajax_gateway' );
