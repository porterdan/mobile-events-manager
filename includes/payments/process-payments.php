<?php
/**
 * Process payments.
 *
 * @package MEM
 * @subpackage Functions
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Process Payment Form
 *
 * Handles the payment form process.
 *
 * @since 1.3.8
 * @return void
 */
function mem_process_payment_form() {

	do_action( 'mem_pre_process_payment' );

	// Validate the form $_POST data.
	$valid_data = mem_payment_form_validate_fields();

	// Allow themes and plugins to hook to errors.
	do_action( 'mem_payment_error_checks', $valid_data, $_POST );

	// Setup purchase information.
	$payment_data = array(
		'event_id'    => filter_var( $valid_data['event_id'], FILTER_SANITIZE_NUMBER_INT ),
		'client_id'   => filter_var( $valid_data['client_id'], FILTER_SANITIZE_NUMBER_INT ),
		'client_data' => sanitize_textarea_field( ( $valid_data['client_data'] ) ),
		'type'        => sanitize_text_field( $valid_data['type'] ),
		'total'       => esc_html( $valid_data['total'] ),
		'date'        => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
		'post_data'   => $_POST,
		'gateway'     => sanitize_text_field( $valid_data['gateway'] ),
		'card_info'   => sanitize_text_field( $valid_data['cc_info'] ),
		'ip'          => sanitize_text_field( $valid_data['client_ip'] ),
	);

	// Allow themes and plugins to hook before the gateway.
	do_action( 'mem_payment_before_gateway', $_POST, $payment_data );

	// Allow the payment data to be modified before a transaction is created.
	$payment_data = apply_filters(
		'mem_payment_data_before_txn',
		$payment_data,
		$valid_data
	);

	$txn_id = mem_create_payment_txn( $payment_data );

	if ( ! empty( $txn_id ) ) {
		$payment_data['txn_id'] = $txn_id;
	}

	// Allow the payment data to be modified before it is sent to gateway.
	$payment_data = apply_filters(
		'mem_payment_data_before_gateway',
		$payment_data,
		$valid_data
	);

	// Send info to the gateway for payment processing.
	mem_send_to_gateway( $payment_data['gateway'], $payment_data );
	die();
} // mem_process_payment_form
add_action( 'mem_event_payment', 'mem_process_payment_form' );
add_action( 'wp_ajax_mem_event_payment', 'mem_process_payment_form' );
add_action( 'wp_ajax_nopriv_mem_event_payment', 'mem_process_payment_form' );

/**
 * Payment Form Validate Fields
 *
 * @since 1.3.8
 * @return bool|arr
 */
function mem_payment_form_validate_fields() {
	// Check if there is $_POST.
	if ( empty( $_POST ) ) {
		return false;
	}

	$event_id = sanitize_text_field( wp_unslash( $_POST['event_id'] ) );

	if ( empty( $event_id ) ) {
		return false;
	}

	$client_data = mem_get_payment_form_client( mem_get_event_client_id( $event_id ) );

	// Start an array to collect valid data.
	$valid_data = array(
		'event_id'    => esc_html( $event_id ),
		'client_id'   => esc_html( $client_data['id'] ),
		'client_data' => esc_html( $client_data ),
		'client_ip'   => esc_html( mem_get_user_ip() ),
		'type'        => esc_html( mem_get_payment_type() ),
		'total'       => esc_html( mem_get_payment_total() ),
		'gateway'     => esc_html( mem_payment_form_validate_gateway() ),
		'cc_info'     => esc_html( mem_get_purchase_cc_info() ),
	);

	// Return collected data.
	return $valid_data;
} // mem_payment_form_validate_fields

function mem_get_payment_form_client( $client_id = 0 ) {
	if ( empty( $client_id ) ) {
		return false;
	}

	$client_data = array();

	$client = get_userdata( $client_id );

	if ( $client ) {

		$client_data['id']           = isset( $client->ID ) ? $client->ID : '';
		$client_data['first_name']   = isset( $client->first_name ) ? ucfirst( sanitize_text_field( $client->first_name ) ) : '';
		$client_data['last_name']    = isset( $client->last_name ) ? ucfirst( sanitize_text_field( $client->last_name ) ) : '';
		$client_data['display_name'] = isset( $client->display_name ) ? ucwords( sanitize_text_field( $client->display_name ) ) : '';
		$client_data['email']        = isset( $client->user_email ) ? strtolower( sanitize_email( $client->user_email ) ) : '';

	}

	/**
	 * Allow gateway extensions to filter the client data.
	 *
	 * @since 1.3.8
	 */

	$client_data = apply_filters( 'mem_get_payment_form_client', $client_data, $client_id );

	return $client_data;

} // mem_get_payment_form_client

/**
 * Payment Form Validate Gateway
 *
 * @since 1.3.8
 * @return string
 */
function mem_payment_form_validate_gateway() {
	$gateway = mem_get_default_gateway();

	// Check if a gateway value is present.
	if ( ! empty( $_REQUEST['mem_gateway'] ) ) {
		$gateway = sanitize_text_field( wp_unslash( $_REQUEST['mem_gateway'] ) );
	}

	return $gateway;
} // mem_payment_form_validate_gateway

/**
 * Payment form payment type.
 *
 * @since 1.3.8
 * @return str
 */
function mem_get_payment_type() {
	$type = sanitize_text_field( wp_unslash( $_POST['mem_payment_amount'] ) );

	if ( 'deposit' === $type ) {
		$type = mem_get_deposit_label();
	} elseif ( 'balance' === $type ) {
		$type = mem_get_balance_label();
	} else {
		$type = mem_get_other_amount_label();
	}

	return $type;

} // mem_get_payment_type

/**
 * Payment form total price.
 *
 * @since 1.3.8
 * @return str
 */
function mem_get_payment_total() {
	$event_id = sanitize_text_field( wp_unslash( $_POST['event_id'] ) );
	$type     = sanitize_text_field( wp_unslash( $_POST['mem_payment_amount'] ) );
	$total    = false;

	$mem_event = new MEM_Event( $event_id );

	if ( 'deposit' === $type ) {
		$type  = mem_get_deposit_label();
		$total = $mem_event->get_remaining_deposit();
	} elseif ( 'balance' === $type ) {
		$type  = mem_get_balance_label();
		$total = $mem_event->get_balance();
	} else {
		$type  = mem_get_other_amount_label();
		$total = ! empty( $_POST['part_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['part_payment'] ) ) : false;
	}

	return $total;

} // mem_get_payment_total

/**
 * Get Credit Card Info
 *
 * @since 1.3.8
 * @return arr
 */
function mem_get_purchase_cc_info() {
	$cc_info                   = array();
	$cc_info['card_name']      = isset( $_POST['card_name'] ) ? sanitize_text_field( wp_unslash( $_POST['card_name'] ) ) : '';
	$cc_info['card_number']    = isset( $_POST['card_number'] ) ? sanitize_text_field( wp_unslash( $_POST['card_number'] ) ) : '';
	$cc_info['card_cvc']       = isset( $_POST['card_cvc'] ) ? sanitize_text_field( wp_unslash( $_POST['card_cvc'] ) ) : '';
	$cc_info['card_exp_month'] = isset( $_POST['card_exp_month'] ) ? sanitize_text_field( wp_unslash( $_POST['card_exp_month'] ) ) : '';
	$cc_info['card_exp_year']  = isset( $_POST['card_exp_year'] ) ? sanitize_text_field( wp_unslash( $_POST['card_exp_year'] ) ) : '';
	$cc_info['card_address']   = isset( $_POST['card_address'] ) ? sanitize_text_field( wp_unslash( $_POST['card_address'] ) ) : '';
	$cc_info['card_address_2'] = isset( $_POST['card_address_2'] ) ? sanitize_text_field( wp_unslash( $_POST['card_address_2'] ) ) : '';
	$cc_info['card_city']      = isset( $_POST['card_city'] ) ? sanitize_text_field( wp_unslash( $_POST['card_city'] ) ) : '';
	$cc_info['card_state']     = isset( $_POST['card_state'] ) ? sanitize_text_field( wp_unslash( $_POST['card_state'] ) ) : '';
	$cc_info['card_country']   = isset( $_POST['billing_country'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) : '';
	$cc_info['card_zip']       = isset( $_POST['card_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['card_zip'] ) ) : '';

	return $cc_info;
} // mem_get_purchase_cc_info
