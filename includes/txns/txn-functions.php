<?php
/**
 * Contains all transaction related functions
 *
 * @package MEM
 * @subpackage Transactions
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the label used for deposits.
 *
 * @since 1.3
 * @param
 * @return str The label set for deposits
 */
function mem_get_deposit_label() {

	$term = get_term_by( 'slug', 'mem-deposit-payments', 'transaction-types' );

	if ( empty( $term ) ) {
		return __( 'Deposit', 'mobile-events-manager' );
	}

	return esc_html( $term->name );

} // mem_get_deposit_label

/**
 * Get the label used for balances.
 *
 * @since 1.3
 * @param
 * @return str The label set for balances
 */
function mem_get_balance_label() {

	$term = get_term_by( 'slug', 'mem-balance-payments', 'transaction-types' );

	if ( empty( $term ) ) {
		return __( 'Balance', 'mobile-events-manager' );
	}

	return  esc_html( $term->name );

} // mem_get_balance_label

/**
 * Get the label used for merchant fees.
 *
 * @since 1.3
 * @param
 * @return str The label set for merchant fees
 */
function mem_get_merchant_fees_label() {

	$term = get_term_by( 'slug', 'mem-merchant-fees', 'transaction-types' );

	if ( empty( $term ) ) {
		return __( 'Term not found', 'mobile-events-manager' );
	}

	return esc_html( $term->name );

} // mem_get_balance_label

/**
 * Get the label used for custom payment amounts.
 *
 * @since 1.3
 * @param
 * @return str The label set for the other_amount_label option
 */
function mem_get_other_amount_label() {
	return mem_get_option( 'other_amount_label', __( 'Other Amount', 'mobile-events-manager' ) );
} // mem_get_other_amount_label

/**
 * Get the label used for employee wages.
 *
 * @since 1.3
 * @param
 * @return str The label set for merchant fees
 */
function mem_get_employee_wages_label() {

	$term = get_term_by( 'slug', 'mem-employee-wages', 'transaction-types' );

	if ( empty( $term ) ) {
		return __( 'Term not found', 'mobile-events-manager' );
	}

	return esc_html( $term->name );

} // mem_get_employee_wages_label

/**
 * Get the category ID for the term.
 *
 * @since 1.3
 * @param str $slug The slug of the term for which we want the ID.
 * @return int The term ID
 */
function mem_get_txn_cat_id( $field = 'slug', $slug ) {

	$term = get_term_by( $field, $slug, 'transaction-types' );

	if ( empty( $term ) ) {
		return __( 'Term not found', 'mobile-events-manager' );
	}

	$id = $term->term_id;
	(int) $id;

	return esc_html( $id );

} // mem_get_txn_cat_id

/**
 * Return all registered currencies.
 *
 * @since 1.3
 * @param
 * @return arr Array of MEM registered currencies
 */
function mem_get_currencies() {
	return apply_filters(
		'mem_currencies',
		array(
			'GBP'  => __( 'Pounds Sterling (&pound;)', 'mobile-events-manager' ),
			'USD'  => __( 'US Dollars (&#36;)', 'mobile-events-manager' ),
			'EUR'  => __( 'Euros (&euro;)', 'mobile-events-manager' ),
			'AUD'  => __( 'Australian Dollars (&#36;)', 'mobile-events-manager' ),
			'BRL'  => __( 'Brazilian Real (R&#36;)', 'mobile-events-manager' ),
			'CAD'  => __( 'Canadian Dollars (&#36;)', 'mobile-events-manager' ),
			'CZK'  => __( 'Czech Koruna', 'mobile-events-manager' ),
			'DKK'  => __( 'Danish Krone', 'mobile-events-manager' ),
			'HKD'  => __( 'Hong Kong Dollar (&#36;)', 'mobile-events-manager' ),
			'HUF'  => __( 'Hungarian Forint', 'mobile-events-manager' ),
			'ILS'  => __( 'Israeli Shekel (&#8362;)', 'mobile-events-manager' ),
			'JPY'  => __( 'Japanese Yen (&yen;)', 'mobile-events-manager' ),
			'MYR'  => __( 'Malaysian Ringgits', 'mobile-events-manager' ),
			'MXN'  => __( 'Mexican Peso (&#36;)', 'mobile-events-manager' ),
			'NZD'  => __( 'New Zealand Dollar (&#36;)', 'mobile-events-manager' ),
			'NOK'  => __( 'Norwegian Krone', 'mobile-events-manager' ),
			'PHP'  => __( 'Philippine Pesos', 'mobile-events-manager' ),
			'PLN'  => __( 'Polish Zloty', 'mobile-events-manager' ),
			'SGD'  => __( 'Singapore Dollar (&#36;)', 'mobile-events-manager' ),
			'ZAR'  => __( 'South African Rand', 'mobile-events-manager' ),
			'SEK'  => __( 'Swedish Krona', 'mobile-events-manager' ),
			'CHF'  => __( 'Swiss Franc', 'mobile-events-manager' ),
			'TWD'  => __( 'Taiwan New Dollars', 'mobile-events-manager' ),
			'THB'  => __( 'Thai Baht (&#3647;)', 'mobile-events-manager' ),
			'INR'  => __( 'Indian Rupee (&#8377;)', 'mobile-events-manager' ),
			'TRY'  => __( 'Turkish Lira (&#8378;)', 'mobile-events-manager' ),
			'RIAL' => __( 'Iranian Rial (&#65020;)', 'mobile-events-manager' ),
			'RUB'  => __( 'Russian Rubles', 'mobile-events-manager' ),
		)
	);
} // mem_get_currencies

/**
 * Get the set currency
 *
 * @since 1.3
 * @return string The currency code
 */
function mem_get_currency() {
	$currency = mem_get_option( 'currency', 'GBP' );
	return apply_filters( 'mem_currency', esc_html( $currency ) );
} // mem_get_currency

/**
 * Given a currency determine the symbol to use. If no currency given, site default is used.
 * If no symbol is determine, the currency string is returned.
 *
 * @since 1.3
 * @param str $currency The currency string.
 * @return str The symbol to use for the currency
 */
function mem_currency_symbol( $currency = '' ) {
	if ( empty( $currency ) ) {
		$currency = mem_get_currency();
	}

	switch ( $currency ) :
		case 'GBP':
			$symbol = '&pound;';
			break;
		case 'BRL':
			$symbol = 'R&#36;';
			break;
		case 'EUR':
			$symbol = '&euro;';
			break;
		case 'USD':
		case 'AUD':
		case 'NZD':
		case 'CAD':
		case 'HKD':
		case 'MXN':
		case 'SGD':
			$symbol = '&#36;';
			break;
		case 'JPY':
			$symbol = '&yen;';
			break;
		default:
			$symbol = $currency;
			break;
	endswitch;

	return apply_filters( 'mem_currency_symbol', $symbol, $currency );
} // mem_currency_symbol

/**
 * Get the name of a currency
 *
 * @since 1.3
 * @param str $code The currency code.
 * @return str The currency's name
 */
function mem_get_currency_name( $code = 'GBP' ) {
	$currencies = mem_get_currencies();
	$name       = isset( $currencies[ $code ] ) ? $currencies[ $code ] : $code;

	return apply_filters( 'mem_currency_name', esc_html( $name ) );
} // mem_get_currency_name

/**
 * Retrieve a transaction.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @return obj $txn The transaction WP_Post object
 */
function mem_get_txn( $txn_id ) {
	return mem_get_txn_by_id( esc_html( $txn_id ) );
} // mem_get_txn

/**
 * Retrieve a transaction by ID.
 *
 * @param int $txn_id The WP post ID for the transaction.
 *
 * @return mixed $txn WP_Query object or false.
 */
function mem_get_txn_by_id( $txn_id ) {
	$txn = new MEM_Txn( $txn_id );

	return ( ! empty( $txn->ID ) ? esc_html( $txn ) : false );
} // mem_get_txn_by_id

/**
 * Retrieve the transactions.
 *
 * @since 1.3
 * @param arr $args Array of possible arguments. See @get_posts.
 * @return mixed $txns False if no txns, otherwise an object array of all events.
 */
function mem_get_txns( $args = array() ) {

	$defaults = array(
		'post_type'      => 'mem-transaction',
		'post_status'    => 'any',
		'posts_per_page' => -1,
	);

	$args = wp_parse_args( $args, $defaults );

	$txns = get_posts( $args );

	// Return the results.
	if ( $txns ) {
		return $txns;
	} else {
		return false;
	}

} // mem_get_txns

/**
 * Retrieve the total transaction count.
 *
 * @since 1.4
 * @param str|arr $status Post statuses.
 * @param arr     $txn_statuses Txn statuses to count. 'any' to count all statuses.
 * @return int Transaction count
 */
function mem_txn_count( $status = array( 'mem-income', 'mem-expenditure' ), $txn_statuses = 'any' ) {
	$txn_args = array(
		'post_type'      => 'mem-transaction',
		'post_status'    => $status,
		'posts_per_page' => -1,
	);

	if ( is_array( $txn_statuses ) ) {
		$meta_query = array( 'relation' => 'OR' );

		foreach ( $txn_statuses as $txn_status ) {
			$meta_query[] = array(
				'key'   => '_mem_txn_status',
				'value' => $txn_status,
			);
		}
	}

	$txn_args = apply_filters( 'mem_txn_count_args', $txn_args );

	$txns = new WP_Query( $txn_args );

	return $txns->found_posts;
} // mem_txn_count

/**
 * Return the type of transaction.
 *
 * @since 1.3
 * @param int $txn_id ID of the current transaction.
 * @return str Transaction type.
 */
function mem_get_txn_type( $txn_id ) {
	$txn = new MEM_Txn( $txn_id );

	// Return the label for the status.
	return $txn->get_type();
} // mem_get_txn_type

/**
 * Return all possible types of transaction.
 *
 * @since 1.3
 * @param
 * @return obj Transaction type term objects.
 */
function mem_get_txn_types( $hide_empty = false ) {

	$txn_types = get_categories(
		array(
			'type'       => 'mem-transaction',
			'taxonomy'   => 'transaction-types',
			'order_by'   => 'name',
			'order'      => 'ASC',
			'hide_empty' => $hide_empty,
		)
	);

	return $txn_types;

} // mem_get_txn_types

/**
 * Set the transaction type for the transaction.
 *
 * @since 1.3
 * @param int     $txn_id Transaction ID.
 * @param int|arr $type The term ID of the category to set for the transaction.
 * @return bool True on success, or false.
 */
function mem_set_txn_type( $txn_id, $type ) {

	if ( ! is_array( $type ) ) {
		$type = array( $type );
	}

	$type = array_map( 'intval', $type );
	$type = array_unique( $type );

	(int) $txn_id;

	$set_txn_terms = wp_set_object_terms( $txn_id, $type, 'transaction-types', false );

	if ( is_wp_error( $set_txn_terms ) ) {
		MEM()->debug->log_it( sprintf( 'Unable to assign term ID %d to Transaction %d: %s', $type, $txn_id, $set_txn_terms->get_error_message() ), true );
	}

	return;

} // mem_set_event_type

/**
 * Retrieve all possible transaction sources
 *
 * @since   1.3
 * @param
 * @return  arr     $txn_src    Transaction sources
 */
function mem_get_txn_source() {

	$src = array();

	$src = mem_get_option( 'payment_sources' );

	$txn_src = explode( "\r\n", $src );

	asort( $txn_src );

	return $txn_src;

} // mem_get_txn_source

/**
 * Returns the date for a transaction in short format.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @return str The date of the transaction.
 */
function mem_get_txn_date( $txn_id = '' ) {
	if ( empty( $txn_id ) ) {
		return false;
	}

	$txn = new MEM_Txn( $txn_id );

	return mem_format_short_date( $txn->get_date() );
} // mem_get_txn_date

/**
 * Retrieve the transaction price.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @return str The price of the transaction.
 */
function mem_get_txn_price( $txn_id ) {
	$mem_txn = new MEM_Txn( $txn_id );

	return esc_html( $mem_txn->price );
} // mem_get_txn_price

/**
 * Retrieve the transaction recipient ID.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @return int The recipient of the transaction.
 */
function mem_get_txn_recipient_id( $txn_id ) {
	$mem_txn = new MEM_Txn( $txn_id );

	return esc_html( $mem_txn->recipient_id );
} // mem_get_txn_recipient_id

/**
 * Retrieve the transaction recipient name.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @return str The recipient of the transaction.
 */
function mem_get_txn_recipient_name( $txn_id ) {
	$recipient_id = mem_get_txn_recipient_id( $txn_id );
	$recipient    = __( 'N/A', 'mobile-events-manager' );

	if ( ! empty( $recipient_id ) ) {

		if ( is_numeric( $recipient_id ) ) {

			$user = get_userdata( $recipient_id );

			$recipient = $user->display_name;

		} else {
			$recipient = $recipient_id;
		}
	}

	return esc_html( $recipient );
} // mem_get_txn_recipient_name

/**
 * Calculate the total wages payable for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str Total wages amount for the event.
 */
function mem_get_event_total_wages( $event_id ) {

	$event = mem_get_event( $event_id );

	return $event->get_wages_total();

} // mem_get_event_total_wages

/**
 * Registers a new transaction or updates an existing.
 *
 * @since 1.3
 * @param arr $data Array of transaction post data.
 * @return int|bool The new transaction ID or false on failure.
 */
function mem_add_txn( $data ) {

	$post_defaults = apply_filters(
		'mem_add_txn_defaults',
		array(
			'ID'            => isset( $data['invoice'] ) ? $data['invoice'] : '',
			'post_title'    => isset( $data['invoice'] ) ? mem_get_option( 'event_prefix' ) . $data['invoice'] : '',
			'post_status'   => 'mem-income',
			'post_date'     => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
			'edit_date'     => true,
			'post_author'   => isset( $data['item_number'] ) ? get_post_meta( $data['item_number'], '_mem_event_client', true ) : 1,
			'post_type'     => 'mem-transaction',
			'post_category' => ( ! empty( $data['txn_type'] ) ? array( $data['txn_type'] ) : '' ),
			'post_parent'   => isset( $data['event_id'] ) ? $data['event_id'] : '',
			'post_modified' => gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
		)
	);

	$txn_data = wp_parse_args( $data, $post_defaults );

	do_action( 'mem_pre_add_txn', $txn_data );

	$txn_id = wp_insert_post( $txn_data );

	// Failed.
	if ( 0 === $txn_id ) {
		return false;
	}

	// Set the transaction type (category).
	if ( ! empty( $txn_data['post_category'] ) ) {
		wp_set_post_terms( $txn_id, $txn_data['post_category'], 'transaction-types' );
	}

	do_action( 'mem_post_add_txn', $txn_id, $txn_data );

	return esc_html( $txn_id );

} // mem_add_txn

/**
 * Update the transaction status.
 *
 * @since 1.3.8
 * @param int $txn_id The transaction ID.
 * @param str $new_status The new transaction status.
 * @return void
 */
function mem_update_txn_status( $txn_id, $new_status ) {
	mem_update_txn_meta( $txn_id, array( '_mem_txn_status' => $new_status ) );
} // mem_update_txn_status

/**
 * Add or Update transaction meta data.
 *
 * We don't currently delete empty meta keys or values, instead we update with an empty value
 * if an empty value is passed to the function.
 *
 * @since 1.3
 * @param int $txn_id The transaction ID.
 * @param arr $data Array of transaction post meta data.
 * @return void
 */
function mem_update_txn_meta( $txn_id, $data ) {

	$meta = get_post_meta( $txn_id, '_mem_txn_data', true );

	if ( ! $meta ) {
		$meta = array();
	} elseif ( ! is_array( $meta ) ) {
		$meta = array( $meta );
	}

	foreach ( $data as $key => $value ) {

		if ( 'mem_nonce' === $key || 'mem_action' === $key ) {
			continue;
		}

		if ( array_key_exists( $key, $meta ) ) {
		}

		// For backwards comaptibility.
		update_post_meta( $txn_id, $key, $value );

		$meta[ $key ] = $value;

	}

	$update = update_post_meta( $txn_id, '_mem_txn_data', $meta );

	return $update;

} // mem_update_txn_meta

/**
 * Mark event employees salaries as paid.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $_employee_id User ID of employee to pay.
 * @param str $amount Amount to pay.
 * @return mixed Array of 'success' and 'failed' payments or if individual employee, true or false.
 */
function mem_pay_event_employees( $event_id, $_employee_id = 0, $amount = 0 ) {

	if ( ! mem_get_option( 'enable_employee_payments' ) ) {
		return;
	}

	$mem_event = mem_get_event( $event_id );

	if ( ! $mem_event ) {
		return false;
	}

	$employees = $mem_event->get_all_employees();

	if ( ! $employees ) {
		return false;
	}

	do_action( 'mem_pre_pay_event_employees', $event_id, $_employee_id, $mem_event );

	foreach ( $employees as $employee_id => $employee_data ) {

		if ( 'paid' === $employee_data['payment_status'] ) {
			MEM()->debug->log_it( sprintf( 'Skipping payment to %s. Employee already paid.', mem_get_employee_display_name( $employee_id ) ) );
		}

		$mem_txn = new MEM_Txn( $employee_data['txn_id'] );

		if ( ! $mem_txn ) {
			return false;
		}

		MEM()->debug->log_it(
			sprintf(
				'Starting payment to %s for %s',
				mem_get_employee_display_name( $employee_id ),
				mem_currency_filter( mem_format_amount( $mem_txn->price ) )
			),
			true
		);

		if ( ! mem_set_employee_paid( $employee_id, $event_id, $mem_txn->ID ) ) {
			MEM()->debug->log_it( sprintf( 'Payment to %s failed', mem_get_employee_display_name( $employee_id ) ) );

			if ( ! empty( $_employee_id ) ) {
				$return = false;
			} else {
				$return['failed'] = $employee_id;
			}
		} else {
			MEM()->debug->log_it(
				sprintf(
					'%s successfully paid %s',
					mem_get_employee_display_name( $employee_id ),
					mem_currency_filter( mem_format_amount( $mem_txn->price ) )
				)
			);

			mem_update_txn_meta( $mem_txn->ID, array( '_mem_txn_status' => 'Completed' ) );

			if ( ! empty( $_employee_id ) ) {
				$return = true;
			} else {
				$return['success'] = $employee_id;
			}
		}
	}

	do_action( 'mem_post_pay_event_employees', $event_id, $_employee_id, $mem_event, $mem_txn->ID );

	return $return;

} // mem_pay_event_employees

/**
 * Remove the post save action whilst adding or updating transactions.
 *
 * @since 1.3
 * @param
 * @return void
 */
function mem_remove_txn_save_post_action() {
	remove_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );
} // mem_remove_txn_save_post_action
add_action( 'mem_pre_add_txn', 'mem_remove_txn_save_post_action' );
add_action( 'mem_pre_update_txn', 'mem_remove_txn_save_post_action' );

/**
 * Add the post save action after adding or updating transactions.
 *
 * @since 1.3
 * @param
 * @return void
 */
function mem_add_txn_save_post_action() {
	add_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );
} // mem_add_txn_save_post_action
add_action( 'mem_post_add_txn', 'mem_add_txn_save_post_action' );
add_action( 'mem_post_update_txn', 'mem_add_txn_save_post_action' );
