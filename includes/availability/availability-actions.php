<?php
/**
 * Contains all availability checker related functions called via actions executed on the front end
 *
 * @package TMEM
 * @subpackage Availability Checker
 * @since 1.3
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
function tmem_availability_check_action( $data ) {

	if ( ! isset( $data['availability_check_date'] ) ) {
		$message = 'missing_date';
	} else {
		$result = tmem_do_availability_check( $data['availability_check_date'] );

		if ( ! empty( $result['available'] ) ) {
			$message = 'available';
		} else {
			$message = 'not_available';
		}
	}

	$url = remove_query_arg( array( 'tmem_avail_date', 'tmem_message' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'tmem_avail_date' => $data['availability_check_date'],
				'tmem_message'    => sanitize_textarea_field( $message ),
			),
			$url
		)
	);

	die();

} // tmem_availability_check_action
add_action( 'tmem_do_availability_check', 'tmem_availability_check_action' );
