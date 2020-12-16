<?php
/**
 * Contains all availability checker related functions called via actions executed on the front end
 *
 * @package MEM
 * @subpackage Availability Checker
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Process availability check from shortcode.
 *
 * @since 1.3
 * @param arr $data $_POST form data.
 * @return void
 */
function mem_availability_check_action( $data ) {

	if ( ! isset( $data['availability_check_date'] ) ) {
		$message = 'missing_date';
	} else {
		$result = mem_do_availability_check( $data['availability_check_date'] );

		if ( ! empty( $result['available'] ) ) {
			$message = 'available';
		} else {
			$message = 'not_available';
		}
	}

	$url = remove_query_arg( array( 'mem_avail_date', 'mem_message' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem_avail_date' => $data['availability_check_date'],
				'mem_message'    => sanitize_textarea_field( $message ),
			),
			$url
		)
	);

	die();

} // mem_availability_check_action
add_action( 'mem_do_availability_check', 'mem_availability_check_action' );
