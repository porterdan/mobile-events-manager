<?php
/**
 * Contains all admin availability related functions
 *
 * @package TMEM
 * @subpackage Availability
 * @since 1.5.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs an employee availability check.
 *
 * @since 1.5.6
 * @param array $data $_POST data.
 * @return void
 */
function tmem_employee_availability_check_action( $data ) {
	if ( ! isset( $data['tmem_nonce'] ) || ! wp_verify_nonce( $data['tmem_nonce'], 'employee_availability_check' ) ) {
		wp_die( wp_kses( 'Security failure', 'mobile-events-manager' ) );
	}

	if ( ! empty( $data['check_date'] ) ) {

		$return_url = add_query_arg(
			array(
				'post_type'    => 'tmem-event',
				'page'         => 'tmem-availability',
				'tmem-message' => $message,
			),
			admin_url( 'edit.php' )
		);
	}

	wp_safe_redirect( $return_url );
	die();
} // tmem_employee_availability_check_action
add_action( 'tmem-employee_availability_lookup', 'tmem_employee_availability_check_action' );
