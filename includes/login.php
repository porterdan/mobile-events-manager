<?php
/**
 * Login / Register Functions
 *
 * @package TMEM
 * @subpackage Functions/Login
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
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
function tmem_login_form( $redirect = '' ) {
	global $tmem_login_redirect;

	if ( empty( $redirect ) ) {
		$redirect = tmem_do_content_tags( '{application_home}' );
	}

	$tmem_login_redirect = remove_query_arg( 'tmem_message', $redirect );

	ob_start();

	tmem_get_template_part( 'login', 'form' );

	$output = ob_get_clean();
	$output = tmem_do_content_tags( $output );

	return apply_filters( 'tmem_login_form', $output );
} // tmem_login_form

/**
 * Process Login Form
 *
 * @since 1.3
 * @param arr $data Data sent from the login form.
 * @return void
 */
function tmem_process_login_form( $data ) {
	if ( wp_verify_nonce( $data['tmem_login_nonce'], 'tmem-login-nonce' ) ) {
		$user_data = get_user_by( 'login', $data['tmem_user_login'] );

		if ( ! $user_data ) {
			$user_data = get_user_by( 'email', $data['tmem_user_login'] );
		}

		if ( $user_data ) {

			$user_ID    = $user_data->ID;
			$user_email = $user_data->user_email;
			if ( wp_check_password( $data['tmem_user_pass'], $user_data->user_pass, $user_data->ID ) ) {
				tmem_log_user_in( $user_data->ID, $data['tmem_user_login'], $data['tmem_user_pass'] );
			} else {
				$message = 'password_incorrect';
			}
		} else {

			$message = 'username_incorrect';

		}

		if ( ! empty( $message ) ) {
			$url = remove_query_arg( 'tmem_message' );
			wp_safe_redirect( add_query_arg( 'tmem_message', $message, $url ) );
			die();
		}

		$redirect = apply_filters( 'tmem_login_redirect', $data['tmem_redirect'], $user_ID );
		wp_safe_redirect( $redirect );
		die();

	}
} // tmem_process_login_form
add_action( 'tmem_user_login', 'tmem_process_login_form' );

/**
 * Log User In
 *
 * @since 1.3
 * @param int $user_id User ID.
 * @param str $user_login Username.
 * @param str $user_pass Password.
 * @return void
 */
function tmem_log_user_in( $user_id, $user_login, $user_pass ) {

	if ( $user_id < 1 ) {
		return;
	}

	wp_set_auth_cookie( $user_id );
	wp_set_current_user( $user_id, $user_login );
	do_action( 'wp_login', $user_login, get_userdata( $user_id ) );
	do_action( 'tmem_log_user_in', $user_id, $user_login, $user_pass );

} // tmem_log_user_in
