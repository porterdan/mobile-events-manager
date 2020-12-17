<?php
/**
 * Formatting functions to taking care of properly formatted numbers, URLs and such
 *
 * @package TMEM
 * @subpackage Functions/Formatting
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize Amount
 *
 * Returns a sanitized amount by stripping out thousands separators.
 *
 * @since 1.3
 * @param str $amount Price amount to format.
 * @return str $amount Newly sanitized amount
 */
function tmem_sanitize_amount( $amount ) {
	$is_negative   = false;
	$thousands_sep = tmem_get_option( 'thousands_separator', ',' );
	$decimal_sep   = tmem_get_option( 'decimal', '.' );

	// Sanitize the amount.
	if ( ',' === $decimal_sep && false !== ( $found = strpos( $amount, $decimal_sep ) ) ) {
		if ( ( '.' === $thousands_sep || ' ' === $thousands_sep ) && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
			$amount = str_replace( $thousands_sep, '', $amount );
		} elseif ( empty( $thousands_sep ) && false !== ( $found = strpos( $amount, '.' ) ) ) {
			$amount = str_replace( '.', '', $amount );
		}

		$amount = str_replace( $decimal_sep, '.', $amount );
	} elseif ( ',' === $thousands_sep && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( $thousands_sep, '', $amount );
	}

	if ( $amount < 0 ) {
		$is_negative = true;
	}

	$amount   = preg_replace( '/[^0-9\.]/', '', $amount );
	$decimals = apply_filters( 'tmem_sanitize_amount_decimals', 2, $amount );
	$amount   = number_format( (float) $amount, $decimals, '.', '' );

	if ( $is_negative ) {
		$amount *= -1;
	}

	return apply_filters( 'tmem_sanitize_amount', $amount );
} // tmem_sanitize_amount

/**
 * Returns a nicely formatted amount.
 *
 * @since 1.3
 *
 * @param str $amount Price amount to format.
 * @param str $decimals Whether or not to use decimals. Useful when set to false for non-currency numbers.
 *
 * @return str $amount Newly formatted amount or Price Not Available
 */
function tmem_format_amount( $amount, $decimals = true, $thousands = true ) {
	$thousands_sep = tmem_get_option( 'thousands_seperator', ',' );
	$decimal_sep   = tmem_get_option( 'decimal', '.' );

	// Format the amount.
	if ( ',' === $decimal_sep && false !== ( $sep_found = strpos( $amount, $decimal_sep ) ) ) {
		$whole  = substr( $amount, 0, $sep_found );
		$part   = substr( $amount, $sep_found + 1, ( strlen( $amount ) - 1 ) );
		$amount = $whole . '.' . $part;
	}

	// Strip , from the amount (if set as the thousands separator).
	if ( ',' === $thousands_sep && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( ',', '', $amount );
	}

	// Strip ' ' from the amount (if set as the thousands separator).
	if ( ' ' === $thousands_sep && false !== ( $found = strpos( $amount, $thousands_sep ) ) ) {
		$amount = str_replace( ' ', '', $amount );
	}

	if ( empty( $amount ) ) {
		$amount = 0;
	}

	$decimals      = apply_filters( 'tmem_format_amount_decimals', $decimals ? 2 : 0, $amount );
	$thousands_sep = apply_filters( 'tmem_format_amount_thousands', $thousands ? $thousands_sep : '', $amount );

	$formatted = number_format( $amount, $decimals, $decimal_sep, $thousands_sep );

	return apply_filters( 'tmem_format_amount', $formatted, $amount, $decimals, $decimal_sep, $thousands_sep );
} // tmem_format_amount

/**
 * Formats the currency display
 *
 * @since 1.3
 * @param str $price Price.
 * @return arr $currency Currencies displayed correctly
 */
function tmem_currency_filter( $price = '', $currency = '' ) {
	if ( empty( $currency ) ) {

		$currency = tmem_get_currency();

	}

	$position = tmem_get_option( 'currency_format', 'before' );

	$negative = $price < 0;

	if ( $negative ) {
		$price = substr( $price, 1 );
	}

	$symbol = tmem_currency_symbol( $currency );

	if ( 'before' === $position ) :
		switch ( $currency ) :
			case 'GBP':
			case 'BRL':
			case 'EUR':
			case 'USD':
			case 'AUD':
			case 'CAD':
			case 'HKD':
			case 'MXN':
			case 'NZD':
			case 'SGD':
			case 'JPY':
				$formatted = $symbol . $price;
				break;
			default:
				$formatted = $currency . ' ' . $price;
				break;
		endswitch;
		$formatted = apply_filters( 'tmem_' . strtolower( $currency ) . '_currency_filter_before', $formatted, $currency, $price );
	else :
		switch ( $currency ) :
			case 'GBP':
			case 'BRL':
			case 'EUR':
			case 'USD':
			case 'AUD':
			case 'CAD':
			case 'HKD':
			case 'MXN':
			case 'SGD':
			case 'JPY':
				$formatted = $price . $symbol;
				break;
			default:
				$formatted = $price . ' ' . $currency;
				break;
		endswitch;
		$formatted = apply_filters( 'tmem_' . strtolower( $currency ) . '_currency_filter_after', $formatted, $currency, $price );
	endif;

	if ( $negative ) {
		// Prepend the minus sign before the currency sign.
		$formatted = '-' . $formatted;
	}

	return $formatted;
} // tmem_currency_filter

/**
 * Set the number of decimal places per currency
 *
 * @since 1.3
 * @param int $decimals Number of decimal places.
 * @return int $decimals
 */
function tmem_currency_decimal_filter( $decimals = 2 ) {

	$currency = tmem_get_currency();

	switch ( $currency ) {
		case 'RIAL':
		case 'JPY':
		case 'TWD':
		case 'HUF':
			$decimals = 0;
			break;
	}

	return apply_filters( 'tmem_currency_decimal_count', $decimals, $currency );
} // tmem_currency_decimal_filter
add_filter( 'tmem_sanitize_amount_decimals', 'tmem_currency_decimal_filter' );
add_filter( 'tmem_format_amount_decimals', 'tmem_currency_decimal_filter' );

/**
 * Returns a nicely formatted distance.
 *
 * @since 1.3.8
 *
 * @param str  $distance The distance to format.
 * @param bool $singular Whether to return a singular value or plural.
 * @param bool $lowercase True to return a lowercase label, otherwise false.
 * @return str $distance Newly formatted distance
 */
function tmem_format_distance( $distance, $singular = false, $lowercase = false ) {

	$label   = tmem_travel_unit_label( $singular, $lowercase );
	$search  = array( 'km', 'mi', ' ' );
	$replace = '';

	$formatted = trim( str_replace( $search, $replace, $distance ) );
	$formatted = tmem_format_amount( $formatted, true );
	$formatted = $formatted . ' ' . $label;

	return apply_filters( 'tmem_format_distance', $formatted, $distance );

} // tmem_format_distance

/**
 * Returns a nicely formatted time from an input of seconds.
 *
 * @since 1.3.8
 *
 * @param str $seconds The number of seconds to format.
 * @return str $time Newly formatted time
 */
function tmem_seconds_to_time( $seconds ) {

	if ( ! is_numeric( $seconds ) ) {
		return;
	}

	$start = current_time( 'timestamp' );
	$end   = $start + $seconds;

	$time = str_replace( 'min', 'minute', human_time_diff( $start, $end ) );

	return apply_filters( 'tmem_seconds_to_time', $time, $seconds );

} // tmem_seconds_to_time

/**
 * Sanitizes a string key for TMEM Settings
 *
 * Keys are used as internal identifiers. Alphanumeric characters, dashes, underscores, stops, colons and slashes are allowed
 *
 * @since 1.3.7
 * @param str $key String key.
 * @return str Sanitized key
 */
function tmem_sanitize_key( $key ) {
	$raw_key = $key;
	$key     = preg_replace( '/[^a-zA-Z0-9_\-\.\:\/]/', '', $key );

	/**
	 * Filter a sanitized key string.
	 *
	 * @since 1.3.7
	 * @param str $key Sanitized key.
	 * @param str $raw_key The key prior to sanitization.
	 */
	return apply_filters( 'tmem_sanitize_key', $key, $raw_key );
} // tmem_sanitize_key

/**
 * Set the shortdate format for the given date
 *
 * @since 1.3
 * @param int $date Date to format.
 * @return int $date
 */
function tmem_format_short_date( $date = '' ) {

	if ( empty( $date ) ) {
		$date = (string) current_time( 'timestamp' );
	}

	if ( ( (string) (int) $date === $date ) && ( $date <= PHP_INT_MAX ) && ( $date >= ~PHP_INT_MAX ) ) {
		$short_date = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ), $date );
	} else {
		$short_date = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $date ) );
	}

	return apply_filters( 'tmem_format_short_date', $short_date, $date );
} // tmem_format_short_date

/**
 * Set the long date format for the given date
 *
 * @since 1.3
 * @param int  $date Date to format.
 * @param bool $time True to include the time.
 * @return int $date
 */
function tmem_format_long_date( $date = '', $time = false ) {

	if ( empty( $date ) ) {
		$date = current_time( 'timestamp' );
	}

	$format = get_option( 'date_format', true ) . ! empty( $time ) ? ' \a\t ' . get_option( 'time_format', true ) : '';

	if ( ( (string) (int) $date === $date ) && ( $date <= PHP_INT_MAX ) && ( date >= ~PHP_INT_MAX ) ) {
		$long_date = gmdate( $format, $date );

	} else {
		$long_date = gmdate( $format, strtotime( $date ) );
	}

	return apply_filters( 'tmem_format_long_date', $long_date, $date );
} // tmem_format_long_date

/**
 * Set the time format for the given time
 *
 * @since 1.3
 * @param int $time Time to format.
 * @return int $date
 */
function tmem_format_time( $time = '' ) {

	if ( empty( $time ) ) {
		$time = current_time( 'timestamp' );
	}

	$format = tmem_get_option( 'time_format', 'H:i' );

	if ( ( (string) (int) $time === $time ) && ( $time <= PHP_INT_MAX ) && ( date >= ~PHP_INT_MAX ) ) {
		$time_format = gmdate( $format, $time );

	} else {
		$time_format = gmdate( $format, strtotime( $time ) );
	}

	return apply_filters( 'tmem_format_time', $time_format, $time );
} // tmem_format_time

/**
 * Generate an TMEM URL based upon the sites permalink settings.
 *
 * @since 1.3
 * @param int  $page_id Required: The WordPress page ID.
 * @param bool $permalink Optional: Whether or not to include the permalink structure.
 * @param bool $echo Optional: True to echo the URL or false (default) to return it.
 * @return str HTML formatted URL.
 */
function tmem_get_formatted_url( $page_id, $permalink = true, $echo = false ) {
	// The URL.
	$return = get_permalink( $page_id );

	if ( ! empty( $permalink ) ) {
		if ( get_option( 'permalink_structure', false ) ) {
			$return .= '?';
		} else {
			$return .= '&amp;';
		}
	}

	if ( ! empty( $echo ) ) {
		echo $return;
	} else {
		return $return;
	}
} // tmem_get_formatted_url

/**
 * Customise the WP admin footer text
 *
 * @since 1.3
 * @param str $text The footer text.
 * @return str Filtered footer text string
 */
function tmem_wpadmin_footer_text( $text ) {

	$text .= ' ';
	$text .= '<em>' . sprintf(
		__( 'Powered by <a class="tmem-admin-footer" href="%1$s" target="_blank">TMEM Event Management, version %2$s</a>.', 'mobile-events-manager' ),
		'https://www.mobileeventsmanager.co.uk',
		TMEM_VERSION_NUM
	) . '</em>';

	return $text;

} // tmem_wpadmin_footer_text.
add_filter( 'admin_footer_text', 'tmem_wpadmin_footer_text' );
