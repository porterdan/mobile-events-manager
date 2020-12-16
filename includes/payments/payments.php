<?php
/**
 * Contains payment functions.
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
 * Whether or not a payment is in progress.
 *
 * @since 1.3.8
 * @param bool $ssl True if SSL required, otherwise false.
 * @return bool True if a payment is in progress, otherwise false.
 */
function mem_is_payment( $ssl = false ) {

	$is_payment = is_page( mem_get_option( 'payments_page' ) );

	if ( isset( $_GET['mem_action'] ) && 'process_payment' === sanitize_key( $_GET['mem_action'] ) ) {
		true === $is_payment;
	}

	if ( $ssl && ! is_ssl() ) {
		$is_payment = false;
	}

	return apply_filters( 'mem_is_payment', $is_payment, $ssl );

} // mem_is_payment

/**
 * Whether or not there is a gateway.
 *
 * @since 1.3.8
 * @param
 * @return bool True if there is a gateway, otherwise false.
 */
function mem_has_gateway() {

	$enabled_gateways = mem_get_enabled_payment_gateways();

	if ( ! empty( $enabled_gateways ) && count( $enabled_gateways ) >= 1 ) {
		return true;
	}

	return false;

} // mem_has_gateway

/**
 * Removes gateway receipt email setting if no gateways are enabled.
 *
 * @since 1.3.8
 * @param $mem_settings arr MEM Settings array.
 * @return $mem_settings arr MEM Settings array.
 */
function mem_filter_gateway_receipt_setting( $mem_settings ) {

	// Remove gateway receipt template if no gateway is enabled.
	$enabled_gateways = mem_get_enabled_payment_gateways();

	if ( empty( $enabled_gateways ) || count( $enabled_gateways ) < 1 ) {
		unset( $mem_settings['payments']['receipts']['payment_cfm_template'] );
	}

	return $mem_settings;

} // mem_filter_gateway_receipt_setting
add_filter( 'mem_registered_settings', 'mem_filter_gateway_receipt_setting' );

/**
 * Returns a list of all available gateways.
 *
 * @since 1.3.8
 * @return arr $gateways All the available gateways
 */
function mem_get_payment_gateways() {

	$gateways = array(
		'disabled' => array(
			'admin_label'   => __( 'Disabled', 'mobile-events-manager' ),
			'payment_label' => __( 'Disabled', 'mobile-events-manager' ),
		),
	);

	return apply_filters( 'mem_payment_gateways', $gateways );
} // mem_get_payment_gateways

/**
 * Returns a list of all enabled gateways.
 *
 * @since 1.3.8
 * @param bool $sort If true, the default gateway will be first.
 * @return arr $gateway_list All the available gateways
 */
function mem_get_enabled_payment_gateways( $sort = false ) {
	$gateways = mem_get_payment_gateways();
	$enabled  = (array) mem_get_option( 'gateways', false );

	$gateway_list = array();

	foreach ( $gateways as $key => $gateway ) {
		if ( isset( $enabled[ $key ] ) && 1 === $enabled[ $key ] ) {
			$gateway_list[ $key ] = $gateway;
		}
	}

	if ( true === $sort ) {
		// Reorder our gateways so the default is first.
		$default_gateway_id = mem_get_default_gateway();

		if ( mem_is_gateway_active( $default_gateway_id ) ) {

			$default_gateway = array( $default_gateway_id => $gateway_list[ $default_gateway_id ] );
			unset( $gateway_list[ $default_gateway_id ] );

			$gateway_list = array_merge( $default_gateway, $gateway_list );

		}
	}

	return apply_filters( 'mem_enabled_payment_gateways', $gateway_list );
} // mem_get_enabled_payment_gateways

/**
 * Checks whether a specified gateway is activated.
 *
 * @since 1.3.8
 * @param str $gateway Name of the gateway to check for.
 * @return bool true if enabled, false otherwise
 */
function mem_is_gateway_active( $gateway ) {
	$gateways = mem_get_enabled_payment_gateways();
	$ret      = array_key_exists( $gateway, $gateways );
	return apply_filters( 'mem_is_gateway_active', $ret, $gateway, $gateways );
} // mem_is_gateway_active

/**
 * Gets the default payment gateway selected from the MEM Settings
 *
 * @since 1.3.8
 * @return str Gateway ID
 */
function mem_get_default_gateway() {
	$default = mem_get_option( 'payment_gateway', 'disabled' );

	if ( ! mem_is_gateway_active( $default ) ) {
		$gateways = mem_get_enabled_payment_gateways();
		$gateways = array_keys( $gateways );
		$default  = reset( $gateways );
	}

	return apply_filters( 'mem_default_gateway', $default );
} // mem_get_default_gateway

/**
 * Returns the admin label for the specified gateway
 *
 * @since 1.3.8
 * @param str $gateway Name of the gateway to retrieve a label for.
 * @return str Gateway admin label
 */
function mem_get_gateway_admin_label( $gateway ) {
	$gateways = mem_get_payment_gateways();
	$label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['admin_label'] : $gateway;
	$payment  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : false;

	return apply_filters( 'mem_gateway_admin_label', $label, $gateway );
} // mem_get_gateway_admin_label

/**
 * Returns the payment label for the specified gateway
 *
 * @since 1.3.8
 * @param str $gateway Name of the gateway to retrieve a label for.
 * @return str Checkout label for the gateway
 */
function mem_get_gateway_payment_label( $gateway ) {
	$gateways = mem_get_payment_gateways();
	$label    = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['payment_label'] : $gateway;

	return apply_filters( 'mem_gateway_payment_label', $label, $gateway );
} // mem_get_gateway_payment_label

/**
 * Determines what the currently selected gateway is
 *
 * @since 1.3.8
 * @return str $enabled_gateway The slug of the gateway
 */
function mem_get_chosen_gateway() {
	$gateways = mem_get_enabled_payment_gateways();
	$chosen   = isset( $_REQUEST['payment-mode'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['payment-mode'] ) ) : false;

	if ( false !== $chosen ) {
		$chosen = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $chosen );
	}

	if ( ! empty( $chosen ) ) {
		$enabled_gateway = urldecode( $chosen );
	} elseif ( count( $gateways ) >= 1 && ! $chosen ) {
		foreach ( $gateways as $gateway_id => $gateway ) {
			$enabled_gateway = $gateway_id;
		}
	} else {
		$enabled_gateway = mem_get_default_gateway();
	}

	return apply_filters( 'mem_chosen_gateway', $enabled_gateway );
} // mem_get_chosen_gateway

/**
 * Sends all the payment data to the specified gateway
 *
 * @since 1.3.8
 * @param str $gateway Name of the gateway.
 * @param arr $payment_data All the payment data to be sent to the gateway.
 * @return void
 */
function mem_send_to_gateway( $gateway, $payment_data ) {

	$payment_data['gateway_nonce'] = wp_create_nonce( 'mem-gateway' );

	// $gateway must match the ID used when registering the gateway
	do_action( 'mem_gateway_' . $gateway, $payment_data );
} // mem_send_to_gateway

/**
 * Determines if the gateway menu should be shown
 *
 * @since 1.3.8
 * @return bool $show_gateways Whether or not to show the gateways
 */
function mem_show_gateways() {
	$gateways      = mem_get_enabled_payment_gateways();
	$show_gateways = false;

	$chosen_gateway = isset( $_GET['payment-mode'] ) ? preg_replace( '/[^a-zA-Z0-9-_]+/', '', sanitize_text_field( wp_unslash( $_GET['payment-mode'] ) ) ) : false;

	if ( count( $gateways ) > 1 && empty( $chosen_gateway ) ) {
		$show_gateways = true;
	}

	return apply_filters( 'mem_show_gateways', $show_gateways );
} // mem_show_gateways

/**
 * Returns the text for the payment button.
 *
 * @since 1.3.8
 * @return str Button text
 */
function mem_get_payment_button_text() {
	$button_text = mem_get_option( 'payment_button', __( 'Pay Now', 'mobile-events-manager' ) );

	$button_text = esc_attr( apply_filters( 'mem_get_payment_button_text', $button_text ) );

	return $button_text;

} // mem_get_payment_button_text

/**
 * Get the required fields on a payment form.
 *
 * @since 1.3.8.1
 * @param
 * @return arr $required_fields Array of required fields.
 */
function mem_get_required_payment_fields() {

	// Array format should be (arr)$gateway => (str)$name => (bool)$php_ignore.
	$required_fields = array();

	// Allow filtering of the required fields.
	$required_fields = apply_filters( 'mem_required_payment_fields', $required_fields );

	ksort( $required_fields );

	return $required_fields;

} // mem_get_required_payment_fields

/**
 * Whether or not the payment field is required.
 *
 * @since 1.3.8.1
 * @param str  $gateway Payment gateway.
 * @param str  $name The ID or name of the payment field.
 * @param bool $php_ignore True to ignore the validation within PHP.
 * @return bool True if required.
 */
function mem_required_payment_field( $gateway, $name, $php_ignore = false ) {

	$required_fields = mem_get_required_payment_fields();
	$required        = false;

	if ( array_key_exists( $gateway, $required_fields ) ) {
		$gateway_fields = $required_fields[ $gateway ];
		$required       = array_key_exists( $name, $gateway_fields );

		if ( $php_ignore ) {
			$required = $required_fields[ $gateway ][ $name ];
		}
	}

	return (bool) $required;

} // mem_required_payment_field

/**
 * Generates a transaction for a new payment during processing.
 *
 * The transaction status will be set to Pending.
 * Payment gateways should update this txn once payment is verified.
 *
 * @since 1.3.8
 * @param arr $payment_data Array of data collected from payment form validation.
 * @return int Transaction ID ID of the newly created transaction.
 */
function mem_create_payment_txn( $payment_data ) {

	$gateway_label = mem_get_gateway_payment_label( $payment_data['gateway'] );
	$event_id      = $payment_data['event_id'];

	do_action( 'mem_create_payment_before_txn', $payment_data );

	$mem_txn = new MEM_Txn();

	$mem_txn->create(
		array(
			'post_title'  => sprintf( esc_html__( '%1$s payment for %2$s', 'mobile-events-manager' ), $gateway_label, $event_id ),
			'post_status' => 'mem-income',
			'post_author' => 1,
			'post_parent' => $event_id,
		),
		array(
			'_mem_txn_source'      => $gateway_label,
			'_mem_txn_gateway'     => $payment_data['gateway'],
			'_mem_txn_status'      => 'Pending',
			'_mem_payment_from'    => $payment_data['client_id'],
			'_mem_txn_total'       => $payment_data['total'],
			'_mem_payer_firstname' => $payment_data['client_data']['first_name'],
			'_mem_payer_lastname'  => $payment_data['client_data']['last_name'],
			'_mem_payer_email'     => $payment_data['client_data']['email'],
			'_mem_payer_ip'        => $payment_data['ip'],
			'_mem_payment_from'    => $payment_data['client_data']['display_name'],
		)
	);

	mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'name', $payment_data['type'] ) );

	do_action( 'mem_create_payment_after_txn', $mem_txn->ID, $payment_data );

	return $mem_txn->ID;

} // mem_create_payment_txn

/**
 * Completes the transaction record for the event payment
 * using data provided within the gateway response.
 *
 * @since 1.3.8
 * @param $gateway_data arr Transaction data from gateway.
 * @return void
 */
function mem_update_payment_from_gateway( $gateway_data ) {

	$txn_data = apply_filters(
		'mem_update_gateway_payment_data',
		array(
			'ID'            => $gateway_data['txn_id'],
			'post_title'    => mem_get_option( 'event_prefix' ) . $gateway_data['txn_id'],
			'post_name'     => mem_get_option( 'event_prefix' ) . $gateway_data['txn_id'],
			'post_status'   => 'mem-income',
			'post_date'     => $gateway_data['date'],
			'edit_date'     => true,
			'post_author'   => mem_get_event_client_id( $gateway_data['event_id'] ),
			'post_type'     => 'mem-transaction',
			'post_parent'   => $gateway_data['event_id'],
			'post_modified' => current_time( 'mysql' ),
		)
	);

	$txn_meta = apply_filters(
		'mem_update_gateway_payment_meta',
		array(
			'_mem_txn_status'      => $gateway_data['status'],
			'_mem_txn_gw_id'       => $gateway_data['gw_id'],
			'_mem_txn_currency'    => $gateway_data['currency'],
			'_mem_txn_gw_response' => $gateway_data['data'],
			'_mem_txn_net'         => isset( $gateway_data['fee'] ) ? $gateway_data['total'] - $gateway_data['fee'] : '0.00',
			'_mem_txn_fee'         => isset( $gateway_data['fee'] ) ? $gateway_data['fee'] : '0.00',
			'_mem_txn_gw_message'  => isset( $gateway_data['message'] ) ? $gateway_data['message'] : '',
			'_mem_txn_card_type'   => isset( $gateway_data['card_type'] ) ? $gateway_data['card_type'] : '',
			'_mem_txn_env'         => isset( $gateway_data['live'] ) ? $gateway_data['live'] : '',
			'_mem_txn_gw_invoice'  => isset( $gateway_data['gw_invoice'] ) ? $gateway_data['gw_invoice'] : '',
			'_mem_txn_gw_billing'  => isset( $gateway_data['billing_address'] ) ? $gateway_data['billing_address'] : '',
		)
	);

	remove_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );

	do_action( 'mem_before_update_payment_from_gateway', $gateway_data, $txn_data, $txn_meta );

	wp_update_post( $txn_data );

	mem_update_txn_meta( $gateway_data['txn_id'], $txn_meta );

	do_action( 'mem_after_update_payment_from_gateway', $gateway_data, $txn_data, $txn_meta );

	add_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );

} // mem_complete_event_txn_payment
add_action( 'mem_complete_event_payment_txn', 'mem_update_payment_from_gateway' );

/**
 * Records the merchant fee transaction.
 *
 * @since 1.0
 * @param arr $gateway_data Transaction data received from the gateway.
 * @return void
 */
function mem_create_merchant_fee_txn( $gateway_data ) {

	if ( isset( $gateway_data['gateway'] ) ) {
		$gateway = mem_get_gateway_payment_label( $gateway_data['gateway'] );
	} else {
		$gateway = mem_get_gateway_payment_label( mem_get_default_gateway() );
	}

	if ( ! isset( $gateway_data['fee'] ) || $gateway_data['fee'] < '0.01' ) {
		return;
	}

	$txn_data = apply_filters(
		'mem_merchant_fee_transaction_data',
		array(
			'post_author' => mem_get_event_client_id( $gateway_data['event_id'] ),
			'post_type'   => 'mem-transaction',
			'post_title'  => sprintf(
				__( '%1$s Merchant Fee for Transaction %2$s', 'mobile-events-manager' ),
				$gateway,
				$gateway_data['txn_id']
			),
			'post_status' => 'mem-expenditure',
			'post_parent' => $gateway_data['event_id'],
		)
	);

	$txn_meta = apply_filters(
		'mem_merchant_fee_transaction_meta',
		array(
			'_mem_txn_status'   => 'Completed',
			'_mem_txn_source'   => $gateway,
			'_mem_txn_currency' => $gateway_data['currency'],
			'_mem_txn_total'    => $gateway_data['fee'],
			'_mem_payment_to'   => $gateway,
		)
	);

	do_action( 'mem_before_create_merchant_fee', $gateway_data, $txn_data, $txn_meta );

	$mem_txn = new MEM_Txn();

	$mem_txn->create(
		$txn_data,
		$txn_meta
	);

	$merchant_fee_id = $mem_txn->ID;

	if ( ! empty( $merchant_fee_id ) ) {
		mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'slug', 'mem-merchant-fees' ) );

		// Update the incoming transaction meta to include the merchant txn ID.
		mem_update_txn_meta( $gateway_data['txn_id'], array( '_mem_merchant_fee_txn_id' => $merchant_fee_id ) );
	}

	do_action( 'mem_after_create_merchant_fee', $merchant_fee_id, $gateway_data );

} // mem_create_merchant_fee_txn
add_action( 'mem_after_update_payment_from_gateway', 'mem_create_merchant_fee_txn' );

/**
 * Completes an event payment process.
 *
 * @since 1.3.8
 * @param arr $txn_data Transaction data.
 * @return void
 */
function mem_complete_event_payment( $txn_data ) {

	// Allow filtering of the transaction data.
	$txn_data = apply_filters( 'mem_complete_event_payment_data', $txn_data );

	$event_id = $txn_data['event_id'];

	// Allow actions before we update.
	do_action( 'mem_before_complete_event_payment', $txn_data );

	// The transaction updates are hooked into this.
	do_action( 'mem_complete_event_payment_txn', $txn_data );

	do_action( 'mem_before_send_gateway_receipt', $txn_data );

	if ( isset( $txn_data['gateway'] ) && has_action( 'mem_send_' . $txn_data['gateway'] . '_gateway_receipt' ) ) {
		do_action( 'mem_send_' . $txn_data['gateway'] . '_gateway_receipt', $txn_data['event_id'] );
	} else {
		do_action( 'mem_send_gateway_receipt', $txn_data['event_id'] );
	}

	do_action( 'mem_after_send_gateway_receipt', $txn_data );

	do_action( 'mem_after_complete_event_payment', $txn_data );

} // mem_complete_event_payment

/**
 * Register the {payment_for} content tag for use within receipt emails.
 *
 * @since 1.3.8
 * @param obj $mem_txn The transaction object.
 * @return void
 */
function mem_register_payment_for_content_tag( $txn_data ) {

	$txn_id = $txn_data['txn_id'];

	$type = mem_get_txn_type( $txn_id );

	if ( mem_get_deposit_label() === $type ) {
		$payment_for = 'mem_content_tag_deposit_label';
	} elseif ( mem_get_balance_label() === $type ) {
		$payment_for = 'mem_content_tag_balance_label';
	} else {
		$payment_for = 'mem_content_tag_part_payment_label';
	}

	mem_add_content_tag( 'payment_for', __( 'Reason for payment', 'mobile-events-manager' ), $payment_for );

} // mem_register_payment_for_content_tag
add_action( 'mem_before_send_gateway_receipt', 'mem_register_payment_for_content_tag' );

/**
 * Register the {payment_amount} content tag for use within receipt emails.
 *
 * @requires PHP version 5.4 due to use of anonymous functions.
 *
 * @since 1.3.8
 * @param obj $mem_txn The transaction object.
 * @return void
 */
function mem_register_payment_amount_content_tag( $txn_data ) {

	if ( version_compare( phpversion(), '5.4', '<' ) ) {
		return;
	}

	$txn_id = $txn_data['txn_id'];

	mem_add_content_tag(
		'payment_amount',
		__( 'Payment amount', 'mobile-events-manager' ),
		function() use ( $txn_id ) {
			return mem_currency_filter( mem_format_amount( mem_get_txn_price( $txn_id ) ) );
		}
	);

} // mem_register_payment_amount_content_tag
add_action( 'mem_before_send_gateway_receipt', 'mem_register_payment_amount_content_tag' );

/**
 * Register the {payment_date} content tag for use within receipt emails.
 *
 * @requires PHP version 5.4 due to use of anonymous functions.
 *
 * @since 1.3.8
 * @param obj $mem_txn The transaction object.
 * @return void
 */
function mem_register_payment_date_content_tag( $txn_data ) {

	if ( version_compare( phpversion(), '5.4', '<' ) ) {
		return;
	}

	$txn_id = $txn_data['txn_id'];

	mem_add_content_tag(
		'payment_date',
		__( 'Date of payment', 'mobile-events-manager' ),
		function() use ( $txn_id ) {
			return mem_get_txn_date( $txn_id );
		}
	);

} // mem_register_payment_date_content_tag
add_action( 'mem_before_send_gateway_receipt', 'mem_register_payment_date_content_tag' );

/**
 * Send admin notice of payment.
 *
 * @since 1.3.8
 * @param
 * @return void
 */
function mem_admin_payment_notice( $txn_data ) {

	if ( isset( $txn_data['gateway'] ) ) {
		$gateway = mem_get_gateway_admin_label( $txn_data['gateway'] );
	} else {
		$gateway = mem_get_gateway_admin_label( mem_get_default_gateway() );
	}

	$subject = sprintf( esc_html__( '%1$s Payment received via %2$s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ), $gateway );
	$subject = apply_filters( 'mem_admin_payment_notice_subject', $subject );

	$content  = '<!DOCTYPE html>' . "\n";
	$content .= '<html>' . "\n" . '<body>' . "\n";
	$content .= '<p>' . __( 'Hi there', 'mobile-events-manager' ) . ',</p>' . "\n";
	$content .= '<p>' . __( 'A payment has just been received via Mobile Events Manager (MEM)', 'mobile-events-manager' ) . '</p>' . "\n";
	$content .= '<hr />' . "\n";
	$content .= '<h4>' . sprintf( esc_html__( '%s ID', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ) . ': ' . mem_get_event_contract_id( $txn_data['event_id'] ) . '</a></h4>' . "\n";
	$content .= '<p>' . "\n";
	$content .= __( 'Date', 'mobile-events-manager' ) . ': {event_date}<br />' . "\n";

	$content .= __( 'Status', 'mobile-events-manager' ) . ': {event_status}<br />' . "\n";
	$content .= __( 'Client', 'mobile-events-manager' ) . ': {client_fullname}<br />' . "\n";
	$content .= __( 'Payment Date', 'mobile-events-manager' ) . ': {payment_date}<br />' . "\n";

	$content .= __( 'For', 'mobile-events-manager' ) . ': {payment_for}<br />' . "\n";
	$content .= __( 'Amount', 'mobile-events-manager' ) . ': {payment_amount}<br />' . "\n";
	$content .= __( 'Merchant', 'mobile-events-manager' ) . ': ' . $gateway . '<br />' . "\n";

	if ( ! empty( $txn_data['fee'] ) ) {

		$content .= __( 'Transaction Fee', 'mobile-events-manager' ) . ': ' . mem_currency_filter( mem_format_amount( $txn_data['fee'] ) ) . '</span><br />' . "\n";

		$content .= '<strong>' . __( 'Total Received', 'mobile-events-manager' ) . ': ' .
			mem_currency_filter( mem_format_amount( $txn_data['total'] - $txn_data['fee'] ) ) . '</strong><br />' . "\n";

	}

	$content .= __( 'Outstanding Balance', 'mobile-events-manager' ) . ': {balance}</p>' . "\n";
	$content .= sprintf( esc_html__( '<a href="%1$s">View %2$s</a>', 'mobile-events-manager' ), admin_url( 'post.php?post=' . $txn_data['event_id'] . '&action=edit' ), esc_html( mem_get_label_singular() ) ) . '</p>' . "\n";

	$content .= '<hr />' . "\n";
	$content .= '<p>' . __( 'Regards', 'mobile-events-manager' ) . '<br />' . "\n";
	$content .= '{company_name}</p>' . "\n";
	$content .= '</body>' . "\n";
	$content .= '</html>' . "\n";

	$content = apply_filters( 'mem_admin_payment_notice_content', $content );

	mem_send_email_content(
		array(
			'to_email'   => mem_get_option( 'system_email' ),
			'from_name'  => mem_get_option( 'company_name' ),
			'from_email' => mem_get_option( 'system_email' ),
			'event_id'   => $txn_data['event_id'],
			'client_id'  => mem_get_event_client_id( $txn_data['event_id'] ),
			'subject'    => $subject,
			'message'    => $content,
			'copy_to'    => 'disable',
			'source'     => __( 'Automated Payment Received', 'mobile-events-manager' ),
		)
	);

} // mem_admin_payment_notice
add_action( 'mem_after_send_gateway_receipt', 'mem_admin_payment_notice' );

/**
 * Updates an event once a payment is completed.
 *
 * @since 1.3.8
 * @param arr $txn_data Transaction data from gateway.
 * @return void
 */
function mem_update_event_after_payment( $txn_data ) {

	$type = mem_get_txn_type( $txn_data['txn_id'] );
	$meta = array();

	if ( mem_get_deposit_label() === $type ) {
		$meta['_mem_event_deposit_status'] = 'Paid';
	} elseif ( mem_get_balance_label() === $type ) {
		$meta['_mem_event_deposit_status'] = 'Paid';
		$meta['_mem_event_balance_status'] = 'Paid';
	} else {
		if ( mem_get_event_remaining_deposit( $txn_data['event_id'] ) < 1 ) {
			$meta['_mem_event_deposit_status'] = 'Paid';
		}
		if ( mem_get_event_balance( $txn_data['event_id'] ) < 1 ) {
			$meta['_mem_event_deposit_status'] = 'Paid';
			$meta['_mem_event_balance_status'] = 'Paid';
		}
	}

	mem_update_event_meta( $txn_data['event_id'], $meta );

	// Update the journal
	mem_add_journal(
		array(
			'user_id'         => $txn_data['client_id'],
			'event_id'        => $txn_data['event_id'],
			'comment_content' => sprintf(
				__( '%1$s of %2$s received via %3$s', 'mobile-events-manager' ),
				$type,
				mem_currency_filter( mem_format_amount( $txn_data['total'] ) ),
				mem_get_gateway_admin_label( $txn_data['gateway'] )
			),
		)
	);

} // mem_update_event_after_payment
add_action( 'mem_after_update_payment_from_gateway', 'mem_update_event_after_payment', 11 );

/**
 * Write to the gateway log file.
 *
 * @since 1.3.8
 * @param str  $msg The message to be logged.
 * @param bool $stampit True to log with date/time.
 * @return void
 */
function mem_record_gateway_log( $msg, $stampit = false ) {

	$debug_log = true === $stampit ? gmdate( 'd/m/Y H:i:s', current_time( 'timestamp' ) ) . ' : ' . $msg : ' ' . $msg;

	error_log( $debug_log . "\r\n", 3, MEM_PLUGIN_DIR . '/includes/payments/gateway-logs.log' );

} // mem_record_gateway_log

/**
 * Register the log file for core MEM debugging class
 *
 * @since 1.0
 * @param arr $files Log files.
 * @return arr $files Filtered log files.
 */
function mem_payments_register_logs( $files ) {

	$files['MEM Payment Gateways'] = array( MEM_PLUGIN_DIR, '/includes/payments/gateway-logs.log' );

	return $files;
} // mem_payments_register_logs
add_filter( 'mem_log_files', 'mem_payments_register_logs' );
