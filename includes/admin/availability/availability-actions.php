<?php
/**
 * Contains all admin availability related functions
 *
 * @package MEM
 * @subpackage Availability
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs an employee availability check.
 *
 * @since 1.0
 * @param array $data $_POST data.
 * @return void
 */
function mem_employee_availability_check_action( $data ) {
	if ( ! isset( $data['mem_nonce'] ) || ! wp_verify_nonce( $data['mem_nonce'], 'employee_availability_check' ) ) {
		wp_die( wp_kses( 'Security failure', 'mobile-events-manager' ) );
	}

	if ( ! empty( $data['check_date'] ) ) {

		$return_url = add_query_arg(
			array(
				'post_type'    => 'mem-event',
				'page'         => 'mem-availability',
				'mem-message' => $message,
			),
			admin_url( 'edit.php' )
		);
	}

	wp_safe_redirect( $return_url );
	die();
} // mem_employee_availability_check_action
add_action( 'mem-employee_availability_lookup', 'mem_employee_availability_check_action' );
