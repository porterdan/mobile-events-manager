<?php
/**
 * Login / Register Functions
 *
 * @package MEM
 * @subpackage Functions/Login
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Login Form
 *
 * @since 1.3
 * @global $post
 * @param str $redirect Redirect page URL.
 * @return str Login form
 */
function mem_login_form( $redirect = '' ) {
	global $mem_login_redirect;

	if ( empty( $redirect ) ) {
		$redirect = mem_do_content_tags( '{application_home}' );
	}

	$mem_login_redirect = remove_query_arg( 'mem_message', $redirect );

	ob_start();

	mem_get_template_part( 'login', 'form' );

	$output = ob_get_clean();
	$output = mem_do_content_tags( $output );

	return apply_filters( 'mem_login_form', $output );
} // mem_login_form

/**
 * Process Login Form
 *
 * @since 1.3
 * @param arr $data Data sent from the login form.
 * @return void
 */
function mem_process_login_form( $data ) {
echo "TEST";
	if ( wp_verify_nonce( $data['mem_login_nonce'], 'mem-login-nonce' ) ) {
		$user_data = get_user_by( 'login', $data['mem_user_login'] );

		if ( ! $user_data ) {
			$user_data = get_user_by( 'email', $data['mem_user_login'] );
		}

		if ( $user_data ) {

			$user_ID    = $user_data->ID;
			$user_email = $user_data->user_email;
			if ( wp_check_password( $data['mem_user_pass'], $user_data->user_pass, $user_data->ID ) ) {
				mem_log_user_in( $user_data->ID, $data['mem_user_login'], $data['mem_user_pass'] );
			} else {
				$message = 'password_incorrect';
			}
		} else {

			$message = 'username_incorrect';

		}

		if ( ! empty( $message ) ) {
			$url = remove_query_arg( 'mem_message' );
			wp_safe_redirect( add_query_arg( 'mem_message', $message, $url ) );
			die();
		}

		$redirect = apply_filters( 'mem_login_redirect', $data['mem_redirect'], $user_ID );
		wp_safe_redirect( $redirect );
		die();

	}
} // mem_process_login_form
add_action( 'mem_user_login', 'mem_process_login_form' );

/**
 * Log User In
 *
 * @since 1.3
 * @param int $user_id User ID.
 * @param str $user_login Username.
 * @param str $user_pass Password.
 * @return void
 */
function mem_log_user_in( $user_id, $user_login, $user_pass ) {

	if ( $user_id < 1 ) {
		return;
	}

	wp_set_auth_cookie( $user_id );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, get_userdata( $user_id ) );
	do_action( 'mem_log_user_in', $user_id, $user_login, $user_pass );

} // mem_log_user_in
