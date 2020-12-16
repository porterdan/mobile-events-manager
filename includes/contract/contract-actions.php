<?php
/**
 * Perform actions related to contracts as received by $_GET and $_POST super globals.
 *
 * @package MEM
 * @subpackage Contracts
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect to contract.
 *
 * @since 1.0
 * @param arr
 * @return void
 */
function mem_goto_contract_action() {
	if ( ! isset( $_GET['event_id'] ) ) {
		return;
	}

	if ( ! mem_event_exists( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ) {
		wp_die( 'Sorry but we could not locate your event.', 'mobile-events-manager' );
	}

	wp_safe_redirect(
		add_query_arg(
			'event_id',
			sanitize_text_field( wp_unslash( $_GET['event_id'] ) ),
			mem_get_formatted_url( mem_get_option( 'contracts_page' ) )
		)
	);
	die();
} // mem_goto_contract_action
add_action( 'mem_goto_contract', 'mem_goto_contract_action' );

/**
 * Sign the contract.
 *
 * @since 1.3
 * @param arr $data sign contract.
 * @return
 */
function mem_sign_event_contract_action( $data ) {
	// Check the password is correct.
	$user = wp_get_current_user();

	$password_confirmation = wp_authenticate( $user->user_login, $data['mem_verify_password'] );

	$data['mem_accept_terms']   = ! empty( $data['mem_accept_terms'] ) ? sanitize_text_field( $data['mem_accept_terms'] ) : false;
	$data['mem_confirm_client'] = ! empty( $data['mem_confirm_client'] ) ? sanitize_text_field( $data['mem_confirm_client'] ) : false;

	if ( is_wp_error( $password_confirmation ) ) {
		$message = 'password_error';
	} elseif ( ! wp_verify_nonce( $data['mem_nonce'], 'sign_contract' ) ) {
		$message = 'nonce_fail';
	} else {
		// Setup the signed contract details.
		$posted = array();

		foreach ( $data as $key => $value ) {
			if ( 'mem_nonce' !== $key && 'mem_action' !== $key && 'mem_redirect' !== $key && 'mem_submit_sign_contract' !== $key ) {
				// All fields are required.
				if ( empty( $value ) ) {
					wp_safe_redirect(
						add_query_arg(
							array(
								'event_id'     => sanitize_text_field( $data['event_id'] ),
								'mem_message' => 'contract_data_missing',
							),
							mem_get_formatted_url( mem_get_option( 'contracts_page' ) )
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

		if ( mem_sign_event_contract( $data['event_id'], $posted ) ) {
			$message = 'contract_signed';
		} else {
			$message = 'contract_not_signed';
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'event_id'     => sanitize_text_field( $data['event_id'] ),
				'mem_message' => $message,
			),
			mem_get_formatted_url( mem_get_option( 'contracts_page' ) )
		)
	);
	die();

}
add_action( 'mem_sign_event_contract', 'mem_sign_event_contract_action' );

/**
 * Displays the signed contract for review.
 *
 * @since 1.3.6
 * @param int $event_id The event ID.
 * @return void
 */
function mem_review_signed_contract() {

	if ( empty( $_GET['mem_action'] ) ) {
		return;
	}

	if ( 'review_contract' !== sanitize_key( $_GET['mem_action'] ) ) {
		return;
	}

	if ( ! mem_employee_can( 'manage_events' ) ) {
		return;
	}

	$mem_event = new MEM_Event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

	if ( ! mem_is_admin() ) {
		if ( ! array_key_exists( get_current_user_id(), $mem_event->get_all_employees() ) ) {
			return;
		}
	}

	if ( ! $mem_event->get_contract_status() ) {
		printf( esc_html__( 'The contract for this %s is not signed', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) );
		exit;
	}

	$contract_id = $mem_event->get_contract();

	if ( empty( $contract_id ) ) {
		return;
	}

	echo mem_show_contract( $contract_id, $mem_event );

	exit;

} // mem_review_signed_contract
add_action( 'template_redirect', 'mem_review_signed_contract' );
