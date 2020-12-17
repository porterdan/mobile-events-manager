<?php
/**
 * Perform actions related to contracts as received by $_GET and $_POST super globals.
 *
 * @package TMEM
 * @subpackage Contracts
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect to contract.
 *
 * @since 1.3
 * @param arr
 * @return void
 */
function tmem_goto_contract_action() {
	if ( ! isset( $_GET['event_id'] ) ) {
		return;
	}

	if ( ! tmem_event_exists( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ) {
		wp_die( 'Sorry but we could not locate your event.', 'mobile-events-manager' );
	}

	wp_safe_redirect(
		add_query_arg(
			'event_id',
			sanitize_text_field( wp_unslash( $_GET['event_id'] ) ),
			tmem_get_formatted_url( tmem_get_option( 'contracts_page' ) )
		)
	);
	die();
} // tmem_goto_contract_action
add_action( 'tmem_goto_contract', 'tmem_goto_contract_action' );

/**
 * Sign the contract.
 *
 * @since 1.3
 * @param arr $data sign contract.
 * @return
 */
function tmem_sign_event_contract_action( $data ) {
	// Check the password is correct.
	$user = wp_get_current_user();

	$password_confirmation = wp_authenticate( $user->user_login, $data['tmem_verify_password'] );

	$data['tmem_accept_terms']   = ! empty( $data['tmem_accept_terms'] ) ? sanitize_text_field( $data['tmem_accept_terms'] ) : false;
	$data['tmem_confirm_client'] = ! empty( $data['tmem_confirm_client'] ) ? sanitize_text_field( $data['tmem_confirm_client'] ) : false;

	if ( is_wp_error( $password_confirmation ) ) {
		$message = 'password_error';
	} elseif ( ! wp_verify_nonce( $data['tmem_nonce'], 'sign_contract' ) ) {
		$message = 'nonce_fail';
	} else {
		// Setup the signed contract details.
		$posted = array();

		foreach ( $data as $key => $value ) {
			if ( 'tmem_nonce' !== $key && 'tmem_action' !== $key && 'tmem_redirect' !== $key && 'tmem_submit_sign_contract' !== $key ) {
				// All fields are required.
				if ( empty( $value ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'event_id'     => sanitize_text_field( $data['event_id'] ),
								'tmem_message' => 'contract_data_missing',
							),
							tmem_get_formatted_url( tmem_get_option( 'contracts_page' ) )
						)
					);
					die();
				} elseif ( is_string( $value ) || is_int( $value ) ) {
					$posted[ $key ] = strip_tags( addslashes( $value ) );
				} elseif ( is_array( $value ) ) {
					$posted[ $key ] = array_map( 'absint', $value );
				}
			}
		}

		if ( tmem_sign_event_contract( $data['event_id'], $posted ) ) {
			$message = 'contract_signed';
		} else {
			$message = 'contract_not_signed';
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'event_id'     => sanitize_text_field( $data['event_id'] ),
				'tmem_message' => $message,
			),
			tmem_get_formatted_url( tmem_get_option( 'contracts_page' ) )
		)
	);
	die();

}
add_action( 'tmem_sign_event_contract', 'tmem_sign_event_contract_action' );

/**
 * Displays the signed contract for review.
 *
 * @since 1.3.6
 * @param int $event_id The event ID.
 * @return void
 */
function tmem_review_signed_contract() {

	if ( empty( $_GET['tmem_action'] ) ) {
		return;
	}

	if ( 'review_contract' !== sanitize_key( $_GET['tmem_action'] ) ) {
		return;
	}

	if ( ! tmem_employee_can( 'manage_events' ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

	if ( ! tmem_is_admin() ) {
		if ( ! array_key_exists( get_current_user_id(), $tmem_event->get_all_employees() ) ) {
			return;
		}
	}

	if ( ! $tmem_event->get_contract_status() ) {
		printf( esc_html__( 'The contract for this %s is not signed', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) );
		exit;
	}

	$contract_id = $tmem_event->get_contract();

	if ( empty( $contract_id ) ) {
		return;
	}

	echo tmem_show_contract( $contract_id, $tmem_event );

	exit;

} // tmem_review_signed_contract
add_action( 'template_redirect', 'tmem_review_signed_contract' );
