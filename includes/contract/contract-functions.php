<?php
/**
 * Contains event contract functions.
 *
 * @package MEM
 * @subpackage Events
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the default event contract ID.
 *
 * @since 1.0
 * @param int $event_id the default event ID.
 * @return int The post ID of the default event contract.
 */
function mem_get_default_event_contract() {
	return mem_get_option( 'default_contract', false );
} // mem_get_default_event_contract

/**
 * Returns the event contract ID.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int The post ID of the event contract.
 */
function mem_get_event_contract( $event_id ) {
	$event = new MEM_Event( $event_id );

	return $event->get_contract();
} // mem_get_event_contract

/**
 * Retrieve the contract.
 *
 * @since 1.3
 * @param int $contract_id The contract ID.
 * @return obj|false Contract post object or false.
 */
function mem_get_contract( $contract_id ) {
	$contract = get_post( $contract_id );

	if ( ! $contract || ( 'contract' !== $contract->post_type && 'mem-signed-contract' !== $contract->post_type ) ) {
		$contract = false;
	}

	return apply_filters( 'mem_get_contract', $contract, $contract_id );
} // mem_get_contract

/**
 * Make sure the contract exists.
 *
 * @since 1.3
 * @param int $contract_id The contract ID.
 * @return bool true if it exists, otherwise false
 */
function mem_contract_exists( $contract_id ) {
	return mem_get_contract( $contract_id );
} // mem_contract_exists

/**
 * Determine if the event contract is signed.
 *
 * @since 1.3
 * @param $event_id The event ID.
 * @return int|bool The signed contracted post ID or false if not signed yet.
 */
function mem_contract_is_signed( $event_id ) {
	$event = new MEM_Event( $event_id );

	return $event->get_contract_status();
} // mem_contract_is_signed

/**
 * Output the contract to the screen.
 *
 * @since 1.3
 * @param $contract The contract ID.
 * @param $event An MEM_Event class object.
 * @return str The contract content.
 */
function mem_show_contract( $contract_id, $event ) {
	$contract = mem_get_contract( $contract_id );

	if ( $contract ) {
		// Retrieve the contract content.
		$content = $contract->post_content;
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		$output = mem_do_content_tags( $content, $event->ID, $event->client );
	} else {
		$output = __( 'The contract could not be displayed', 'mobile-events-manager' );
	}
	return apply_filters( 'mem_show_contract', $output, $contract_id, $event );
} // mem_show_contract


/**
 * Retrieve the contract signatory name.
 *
 * @since 1.3
 * @param $event_id The event ID.
 * @return str|bool The name of the person who signed the contract.
 */
function mem_get_contract_signatory_name( $event_id ) {
	$name = get_post_meta( $event_id, '_mem_event_contract_approver', true );

	if ( empty( $name ) ) {
		$name = __( 'Name not recorded', 'mobile-events-manager' );
	}

	return apply_filters( 'mem_get_contract_signatory_name', $name, $event_id );
} // mem_get_contract_signatory_name

/**
 * Retrieve the contract signatory IP address.
 *
 * @since 1.3
 * @param $event_id The event ID.
 * @return str|bool The IP address used by the person who signed the contract.
 */
function mem_get_contract_signatory_ip( $event_id ) {
	$ip = get_post_meta( $event_id, '_mem_event_contract_approver_ip', true );

	if ( empty( $ip ) ) {
		$ip = __( 'IP address not recorded', 'mobile-events-manager' );
	}

	return apply_filters( 'mem_get_contract_signatory_ip', $ip, $event_id );
} // mem_get_contract_signatory_ip

/**
 * Sign the event contract
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param arr $details Contract and event info.
 * @return bool Whether or not the contract was signed
 */
function mem_sign_event_contract( $event_id, $details ) {
	$event = new MEM_Event( $event_id );

	if ( ! $event ) {
		return false;
	}

	$contract_template = mem_get_contract( mem_get_event_contract( $event->ID ) );

	if ( ! $contract_template ) {
		return false;
	}

	do_action( 'mem_pre_sign_event_contract', $event_id, $details );

	// Prepare the content for the contract.
	$contract_content = $contract_template->post_content;
	$contract_content = apply_filters( 'the_content', $contract_content );
	$contract_content = str_replace( ']]>', ']]&gt;', $contract_content );
	$contract_content = mem_do_content_tags( $contract_content, $event->ID, $event->client );

	// The signatory information displayed at the foot of the contract.
	$contract_signatory_content  = '<hr>' . "\r\n";
	$contract_signatory_content .= '<p style="font-weight: bold">' . __( 'Signatory', 'mobile-events-manager' ) . ': <span style="text-decoration: underline;">' .
		ucfirst( $details['mem_first_name'] ) . ' ' . ucfirst( $details['mem_last_name'] ) . '</span></p>' . "\r\n";

	$contract_signatory_content .= '<p style="font-weight: bold">' . __( 'Date of Signature', 'mobile-events-manager' ) . ': <span style="text-decoration: underline;">' . gmdate( 'jS F Y' ) . '</span></p>' . "\r\n";
	$contract_signatory_content .= '<p style="font-weight: bold">' . __( 'Verification Method', 'mobile-events-manager' ) . ': ' . __( 'User Password Confirmation', 'mobile-events-manager' ) . '</p>' . "\r\n";
	$contract_signatory_content .= '<p style="font-weight: bold">' . __( 'IP Address Used', 'mobile-events-manager' ) . ': ' . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) . '</p>' . "\r\n";

	$contract_signatory_content = apply_filters( 'mem_contract_signatory', $contract_signatory_content );

	$contract_content .= $contract_signatory_content;

	// Filter the signed contract post data.
	$signed_contract = apply_filters(
		'mem_signed_contract_data',
		array(
			'post_title'     => sprintf( esc_html__( 'Event Contract: %s', 'mobile-events-manager' ), mem_get_option( 'event_prefix' ) . $event->ID ),
			'post_author'    => get_current_user_id(),
			'post_type'      => 'mem-signed-contract',
			'post_status'    => 'publish',
			'post_content'   => $contract_content,
			'post_parent'    => $event->ID,
			'ping_status'    => 'closed',
			'comment_status' => 'closed',
		),
		$event->ID,
		$event
	);

	$signed_contract_id = wp_insert_post( $signed_contract, true );

	if ( is_wp_error( $signed_contract_id ) ) {
		return false;
	}

	add_post_meta( $signed_contract, '_mem_contract_signed_name', ucfirst( $details['mem_first_name'] ) . ' ' . ucfirst( $details['mem_last_name'] ), true );

	$event_meta = array(
		'_mem_event_signed_contract'      => $signed_contract_id,
		'_mem_event_contract_approved'    => current_time( 'mysql' ),
		'_mem_event_contract_approver'    => strip_tags( addslashes( ucfirst( $details['mem_first_name'] ) . ' ' . ucfirst( $details['mem_last_name'] ) ) ),
		'_mem_event_contract_approver_ip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ),
		'_mem_event_last_updated_by'      => get_current_user_id(),
	);

	// Update the event status with awaitingdeposit if configured to wait for deposit before issuing confirmation.
	if ( mem_require_deposit_before_confirming() && ( 'Paid' !== $event->get_deposit_status() ) ) {
		mem_update_event_status(
			$event->ID,
			'mem-awaitingdeposit',
			$event->post_status,
			array(
				'meta'           => $event_meta,
				'client_notices' => mem_get_option( 'awaitingdeposit_to_client' ),
			)
		);

		mem_add_journal(
			array(
				'user'            => get_current_user_id(),
				'event'           => $event->ID,
				'comment_content' => __( 'Contract Approval completed by ', 'mobile-events-manager' ) . ucfirst( $details['mem_first_name'] ) . ' ' . ucfirst( $details['mem_last_name'] . '<br>' ),
			),
			array(
				'type'       => 'update-event',
				'visibility' => '2',
			)
		);

		do_action( 'mem_post_sign_event_contract', $event_id, $details );

		return true;

	} else {
		// Update the event status.
		mem_update_event_status(
			$event->ID,
			'mem-approved',
			$event->post_status,
			array(
				'meta'           => $event_meta,
				'client_notices' => mem_get_option( 'booking_conf_to_client' ),
			)
		);

		mem_add_journal(
			array(
				'user'            => get_current_user_id(),
				'event'           => $event->ID,
				'comment_content' => __( 'Contract Approval completed by ', 'mobile-events-manager' ) . ucfirst( $details['mem_first_name'] ) . ' ' . ucfirst( $details['mem_last_name'] . '<br>' ),
			),
			array(
				'type'       => 'update-event',
				'visibility' => '2',
			)
		);

		do_action( 'mem_post_sign_event_contract', $event_id, $details );

		return true;
	}

} // mem_sign_event_contract
