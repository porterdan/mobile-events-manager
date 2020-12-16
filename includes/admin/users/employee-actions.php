<?php
/**
 * Process employee actions
 *
 * @package MEM
 * @subpackage Users
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add a new employee
 *
 * @since 1.3
 * @param arr $data $_POST super global
 * @return void
 */
function mem_add_employee_action( $data ) {

	if ( ! wp_verify_nonce( $data['mem_nonce'], 'add_employee' ) ) {
		$message = 'security_failed';
	} else {
		if ( empty( $data['first_name'] ) || empty( $data['last_name'] ) || empty( $data['user_email'] ) || ! is_email( $data['user_email'] ) || empty( $data['employee_role'] ) ) {
			$message = 'employee_info_missing';
		} elseif ( mem_add_employee( $data ) ) {
			$message = 'employee_added';
		} else {
			$message = 'employee_add_failed';
		}
	}

	$url = remove_query_arg( array( 'mem-action', 'mem_nonce' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem-message' => $message,
			),
			$url
		)
	);
	die();

} // mem_add_employee_action
add_action( 'mem-add_employee', 'mem_add_employee_action' );
