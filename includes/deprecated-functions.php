<?php
/**
 * Contains deprecated functions.
 *
 * @package MEM
 * @subpackage Functions
 * @since 1.0
 *
 * All functions should call _deprecated_function( $function, $version, $replacement = null ).
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catch incoming API calls
 *
 * @since 1.3
 * @remove 1.5
 * @replacement mem_get_actions
 */
function mem_api_listener() {
	$listener = isset( $_GET['mem-api'] ) ? sanitize_text_field( wp_unslash( $_GET['mem-api'] ) ) : '';

	if ( empty( $listener ) ) {
		return;
	}

	switch ( $listener ) {
		case 'MEM_EMAIL_RCPT':
			_deprecated_function( __FUNCTION__, '1.3', 'mem_api_listener()' );

			$data['tracker_id'] = ! empty( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : '';

			do_action( 'mem_track_open_email', $data );

			break;

		default:
			return;
	} // switch
} // mem_api_listener
add_action( 'wp_loaded', 'mem_api_listener' );

/**
 * Format the date for the datepicker script
 *
 * @since 1.3
 * @remove 1.5
 * @replacement mem_format_datepicker_date
 */
function mem_jquery_short_date() {
	_deprecated_function( __FUNCTION__, '1.3', 'mem_format_datepicker_date()' );

	return mem_format_datepicker_date();
} // mem_jquery_short_date

/**
 * Insert the datepicker jQuery code
 *
 * @since: 1.1.3
 * @called:
 * @params $args =>array
 * [0] = class name
 * [1] = alternative field name (hidden)
 * [2] = maximum # days from today which can be selected
 * [3] = minimum # days past today which can be selected
 *
 * @defaults [0] = mem_date
 * [1] = _mem_event_date
 * [2] none
 *
 * @since 1.3
 * @remove 1.5
 * @replacement mem_insert_datepicker
 */
function mem_jquery_datepicker_script( $args = '' ) {
	_deprecated_function( __FUNCTION__, '1.3', 'mem_insert_datepicker()' );

	$class    = ! empty( $args[0] ) ? $args[0] : 'mem_date';
	$altfield = ! empty( $args[1] ) ? $args[1] : '_mem_event_date';
	$maxdate  = ! empty( $args[2] ) ? $args[2] : '';
	$mindate  = ! empty( $args[3] ) ? $args[3] : '';

	return mem_insert_datepicker(
		array(
			'class'    => $class,
			'altfield' => $altfield,
			'mindate'  => $mindate,
			'maxdate'  => $maxdate,
		)
	);
} // mem_jquery_datepicker_script

/**
 * Displays the price in the selected format per settings
 * basically determining where the currency symbol is displayed
 *
 * @param   str $amount     The price to to display.
 *      bool    $symbol     true to display currency symbol (default)
 * @return  str                 The formatted price with currency symbol
 * @since   1.3
 * @remove  1.5
 */
function display_price( $amount, $symbol = true ) {
	_deprecated_function( __FUNCTION__, '1.3', 'display_price()' );

	global $mem_settings;

	if ( empty( $amount ) || ! is_numeric( $amount ) ) {
		$amount = '0.00';
	}

	$symbol = ( isset( $symbol ) ? $symbol : true );

	$dec = $mem_settings['payments']['decimal'];
	$tho = $mem_settings['payments']['thousands_seperator'];

	// Currency before price.
	if ( 'before' === $mem_settings['payments']['currency_format'] ) {
		return ( ! empty( $symbol ) ? mem_currency_symbol() : '' ) . number_format( $amount, 2, $dec, $tho );
	}

	// Currency before price with space.
	elseif ( 'before with space' === $mem_settings['payments']['currency_format'] ) {
		return ( ! empty( $symbol ) ? mem_currency_symbol() . ' ' : '' ) . number_format( $amount, 2, $dec, $tho );
	}

	// Currency after price.
	elseif ( 'after' === $mem_settings['payments']['currency_format'] ) {
		return number_format( $amount, 2, $dec, $tho ) . ( ! empty( $symbol ) ? mem_currency_symbol() : '' );
	}

	// Currency after price with space.
	elseif ( 'after with space' === $mem_settings['payments']['currency_format'] ) {
		return number_format( $amount, 2, $dec, $tho ) . ' ' . ( ! empty( $symbol ) ? mem_currency_symbol() : '' );
	}

	// Default.
	return ( ! empty( $symbol ) ? mem_currency_symbol() : '' ) . number_format( $amount, 2, $dec, $tho );
} // display_price

/**
 * Determine the event deposit value based upon event cost and
 * payment settings
 *
 * @param   str $cost   Current cost of event
 * @return  str     The amount of deposit to apply.
 * @since   1.3
 * @remove  1.5
 */
function mem_get_deposit( $cost = '' ) {

	_deprecated_function( __FUNCTION__, '1.3', 'mem_calculate_deposit()' );

	// If no event cost is provided then we return 0.
	if ( empty( $cost ) ) {
		$deposit = '0.00';
	}

	// If we don't need a deposit per settings, return 0.
	if ( ! mem_get_option( 'deposit_type' ) ) {
		$deposit = '0.00';
	}

	// Set fixed deposit amount.
	elseif ( mem_get_option( 'deposit_type' ) === 'fixed' ) {
		$deposit = number_format( mem_get_option( 'deposit_amount' ), 2 );
	}

	// Set deposit based on % of total cost.
	elseif ( mem_get_option( 'deposit_type' ) !== 'percentage' ) {
		$percentage = mem_get_option( 'deposit_amount' ); // The % to apply.

		$deposit = ( ! empty( $cost ) && $cost > 0 ? round( $percentage * ( $cost / 100 ), 2 ) : '0.00' );
	}

	return $deposit;
} // mem_get_deposit

/**
 * Write to the gateway log file.
 *
 * @since 1.3.8
 * @param str  $msg The message to be logged.
 * @param bool $stampit True to log with date/time.
 * @remove 1.6
 */
function mem_payments_write( $msg, $stampit = false ) {
	_deprecated_function( __FUNCTION__, '1.3.8', 'mem_record_gateway_log()' );

	return mem_record_gateway_log( $msg, $stampit = false );
} // mem_payments_write

/**
 * Get the addons available
 *
 * @since   1.4
 * @param   int $employee   The user ID of the Employee.
 * @param   str $package    The slug of a package where the package contents need to be excluded.
 * @param   int $event_id   Event ID to check if the add-on is already assigned.
 * @return  arr     Array of available addons and their details.
 */
function get_available_addons( $employee = '', $package = '', $event_id = '' ) {

	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_available_addons()' );

	$addons  = array();
	$_addons = mem_get_available_addons(
		array(
			'employee' => $employee,
			'event_id' => $event_id,
			'package'  => $package,
		)
	);

	if ( $_addons ) {
		foreach ( $_addons as $addon ) {
			$terms                               = get_the_terms( $addon->ID, 'addon-category' );
			$addons[ $addon->post_name ]['cat']  = $terms[0];
			$addons[ $addon->post_name ]['slug'] = $addon->post_name;
			$addons[ $addon->post_name ]['name'] = $addon->post_title;
			$addons[ $addon->post_name ]['cost'] = mem_get_addon_price( $addon->ID );
			$addons[ $addon->post_name ]['desc'] = mem_get_addon_excerpt( $addon->ID );
		}
	}

	return $addons;
} // get_available_addons

/**
 * Get the package information
 *
 * @since 1.4
 * @param int $dj Optional: The user ID of the DJ.
 * @return
 */
function get_available_packages( $dj = '', $price = false ) {

	_deprecated_function( __FUNCTION__, '1.3.8', 'mem_get_available_packages()' );

	// All packages.
	$packages  = array();
	$_packages = mem_get_available_packages(
		array(
			'employee' => $dj,
		)
	);

	if ( $_packages ) {
		foreach ( $_packages as $package ) {
			$terms                                   = get_the_terms( $package->ID, 'package-category' );
			$packages[ $package->post_name ]['cat']  = $terms[0];
			$packages[ $package->post_name ]['slug'] = $package->post_name;
			$packages[ $package->post_name ]['name'] = $package->post_title;
			$packages[ $package->post_name ]['cost'] = mem_get_package_price( $package->ID );
			$packages[ $package->post_name ]['desc'] = mem_get_package_excerpt( $package->ID );
		}
	}

	return $packages;
} // get_available_packages

/**
 * Get the package information for the given event
 *
 * @param int  $event_id The event ID.
 * @param bool $price True to include the package price.
 * @return str
 */
function get_event_package( $event_id, $price = false ) {

	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_event_package()' );

	$return        = __( 'No package is assigned to this event', 'mobile-events-manager' );
	$package_price = '';

	$event_package = mem_get_event_package( $event_id );

	if ( ! empty( $event_package ) ) {
		$return = mem_get_package_name( $event_id );

		if ( ! empty( $price ) ) {
			$return .= ' ' . mem_currency_filter( mem_format_amount( mem_get_package_price( $event_package ) ) );
		}
	}

	return $return;
} // get_event_package

/**
 * Get the description of the package for the event.
 *
 * @param int $event_id The event ID.
 * @return str
 */
function get_event_package_description( $event_id ) {

	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_package_excerpt()' );

	$return = '';

	$package_id = mem_get_event_package( $event_id );

	if ( ! empty( $package_id ) ) {
		$return = mem_get_package_excerpt( $package_id );
	}

	// Event package.
	$event_package = get_post_meta( $event_id, '_mem_event_package', true );

	return $return;
} // get_event_package_description

/**
 * Retrieve the package from the given slug
 *
 * @since 1.4
 * @param str $slug The slug to search for.
 * @return obj|bool $packages The package details
 */
function mem_get_package_by_slug( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4', "mem_get_package_by('field', 'value')" );
	return mem_get_package_by( 'slug', $slug );
} // mem_get_package_by_slug

/**
 * Retrieve the package by name
 *
 * @since 1.4
 * @param str $name The name to search for.
 * @return obj|bool $packages The package details
 */
function mem_get_package_by_name( $name ) {
	_deprecated_function( __FUNCTION__, '1.4', "mem_get_package_by( 'field', 'value' )" );
	return mem_get_package_by( 'name', $name );
} // mem_get_package_by_name

/**
 * Retrieve the cost of a package.
 *
 * @since 1.4
 * @param str $slug The slug identifier for the package.
 * @return int The cost of the package.
 */
function mem_get_package_cost( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_package_price()' );
	$package = mem_get_package_by( 'slug', $slug );

	if ( $package ) {
		return mem_format_amount( mem_get_package_price( $package->ID ) );
	}
} // mem_get_package_cost

/**
 * Retrieve the package name by it's slug.
 *
 * @since 1.4
 * @param str $slug Slug name of the package.
 * @return str $package The display name of the package
 */
function get_package_name( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_package_name()' );
	$return = false;

	$package = mem_get_package_by( 'slug', $slug );

	if ( $package ) {
		$return = $package->post_title;
	}

	return $package;
} // get_package_name

/**
 * Get the add-on information for the given event
 *
 * @since 1.4
 * @param int  $event_id The event ID.
 * @param bool $price True to include the add-on price.
 * @return str $addons Array with add-ons details, or false if no add-ons assigned
 */
function get_event_addons( $event_id, $price = false ) {
	_deprecated_function( __FUNCTION__, '1.4', 'mem_list_event_addons()' );
	return mem_list_event_addons( $event_id, $price );
} // get_event_addons

/**
 * Retrieve the cost of an addon.
 *
 * @since 1.4
 * @param str $slug The slug identifier for the addon.
 * @return int The cost of the addon.
 */
function mem_get_addon_cost( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4', "mem_get_addon_by( 'field', 'value' )" );
	$addon = mem_get_addon_by( 'slug', $slug );

	if ( $addon ) {
		return mem_format_amount( mem_get_addon_price( $addon->ID ) );
	}
} // mem_get_addon_cost

/**
 * Retrieve all addons within the given package slug
 *
 * @since 1.4
 * @param str $slug Required: Slug of the package for which to search.
 * @return arr $addons Array of all addons
 */
function mem_addons_by_package_slug( $slug ) {

	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_addons_by_package()' );
	$package = mem_get_package_by( 'slug', strtolower( $slug ) );

	// No package returns false.
	if ( empty( $package ) ) {
		return false;
	}

	return mem_get_package_addons( $package->ID );
} // mem_addons_by_package_slug

/**
 * Retrieve the package name, description, cost
 *
 * @since   1.4
 * @param   str $slug       Slug name of the package.
 */
function get_package_details( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4' );
	if ( empty( $slug ) ) {
		return false;
	}

	$packages = mem_get_packages();

	if ( empty( $packages[ $slug ] ) ) {
		return false;
	}

	$package['slug']      = $slug;
	$package['name']      = stripslashes( esc_attr( $packages[ $slug ]['name'] ) );
	$package['desc']      = stripslashes( esc_textarea( $packages[ $slug ]['desc'] ) );
	$package['equipment'] = $packages[ $slug ]['equipment'];
	$package['cost']      = $packages[ $slug ]['cost'];

	return $package;
} // get_package_details

/**
 * Retrieve all addons by dj
 *
 * @since 1.4
 * @param int|arr $user_id Required: User ID of DJ, or array of DJ User ID's.
 * @return arr $addons Array of all addons
 */
function mem_addons_by_dj( $user_id ) {
	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_addons_by_employee()' );
	// We work with an array.
	if ( ! is_array( $user_id ) ) {
		$users = array( $user_id );
	}

	$equipment = mem_get_addons();

	// No addons, return false.
	if ( empty( $equipment ) ) {
		return false;
	}

	asort( $equipment );

	// Loop through the addons and filter for the given user(s).
	foreach ( $equipment as $addon ) {
		$users_have = explode( ',', $addon[8] );

		foreach ( $users as $user ) {
			if ( ! in_array( $user, $users_have ) ) {
				continue 2; // Continue from the foreach( $equipment as $addon ) loop.
			}
		}

		$addons[] = $addon;
	}
	// Return the results, or false if none.
	return ! empty( $addons ) ? $addons : false;
} // mem_addons_by_dj

/**
 * Retrieve all addons within the given category
 *
 * @param str $cat Required: Slug of the category for which to search.
 *
 * @return arr $addons Array of all addons
 */
function mem_addons_by_cat( $cat ) {
	_deprecated_function( __FUNCTION__, '1.4' );
	$equipment = mem_get_addons();

	// No addons, return false.
	if ( empty( $equipment ) ) {
		return false;
	}

	asort( $equipment );

	// Loop through the addons and filter for the given category.
	foreach ( $equipment as $addon ) {
		if ( $addon[5] !== $cat ) {
			continue;
		}

		$addons[] = $addon;
	}
	// Return the results, or false if none.
	return ! empty( $addons ) ? $addons : false;
} // mem_addons_by_cat

/**
 * Retrieve all addons within the given package
 *
 * @since 1.4
 * @param str $name Required: Name of the package for which to search.
 * @return arr $addons Array of all addons
 */
function mem_addons_by_package_name( $name ) {
	_deprecated_function( __FUNCTION__, '1.4' );
	$package = mem_get_package_by_name( $name );

	// No package or the package has no addons, return false.
	if ( empty( $package ) || empty( $package['equipment'] ) ) {
		return false;
	}

	$package_items = explode( ',', $package['equipment'] );
	$equipment     = mem_get_addons();

	// No addons, return false.
	if ( empty( $equipment ) ) {
		return false;
	}

	foreach ( $equipment as $addon ) {
		if ( ! in_array( $addon[1], $package_items ) ) {
			continue;
		}

		$addons[] = $addon;
	}

	// Return the results, or false if none.
	return ! empty( $addons ) ? $addons : false;
} // mem_addons_by_package_name

/**
 * Retrieve the addon name
 *
 * @since   1.4
 * @param   str $slug   The slug name of the addon.
 * @return  str     $addon  The display name of the addon
 */
function get_addon_name( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4', 'mem_get_addon_name()' );
	if ( empty( $slug ) ) {
		return false;
	}

	$equipment = mem_get_addons();

	if ( empty( $equipment[ $slug ] ) || empty( $equipment[ $slug ][0] ) ) {
		return false;
	}

	$addon = stripslashes( esc_attr( $equipment[ $slug ][0] ) );

	return $addon;
} // get_addon_name

/**
 * Retrieve the addon category, name, decription & cost
 *
 * @since   1.4
 */
function mem_get_addon_details( $slug ) {
	_deprecated_function( __FUNCTION__, '1.4' );
	if ( empty( $slug ) ) {
		return false;
	}

	$cats = get_option( 'mem_cats' );

	$equipment = mem_get_addons();

	if ( empty( $equipment[ $slug ] ) ) {
		return false;
	}

	$addon['slug'] = $slug;
	$addon['cat']  = stripslashes( esc_attr( $cats[ $equipment[ $slug ][5] ] ) );
	$addon['name'] = stripslashes( esc_attr( $equipment[ $slug ][0] ) );
	$addon['desc'] = stripslashes( esc_textarea( $equipment[ $slug ][4] ) );
	$addon['cost'] = $equipment[ $slug ][7];

	return $addon;
} // mem_get_addon_details

/**
 * Calculate the event cost as the package changes
 *
 * @since 1.0
 * @return void
 */
function mem_update_event_cost_from_package_ajax() {
	_deprecated_function( __FUNCTION__, '1.4' );
	$mem_event = new MEM_Event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );

	$package    = $mem_event->get_package();
	$addons     = $mem_event->get_addons();
	$event_cost = $mem_event->price;
	$event_date = ! empty( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : null;
	$base_cost  = '0.00';

	$package_price = ( $package ) ? (float) mem_get_package_price( $package->ID, $event_date ) : false;

	if ( $event_cost ) {
		$event_cost = (float) $event_cost;
		$base_cost  = ( $package_price ) ? $event_cost - $package_price : $event_cost;
	}

	if ( $addons ) {
		foreach ( $addons as $addon ) {
			$addon_cost = mem_get_package_price( $addon->ID, $event_date );
			$base_cost  = $base_cost - (float) $addon_cost;
		}
	}

	$cost = $base_cost;

	$new_package       = sanitize_text_field( wp_unslash( $_POST['package'] ) );
	$new_package_price = ( ! empty( $new_package ) ) ? mem_get_package_price( $new_package, $event_date ) : false;

	if ( $new_package_price ) {
		$cost = $base_cost + (float) $new_package_price;
	}

	if ( ! empty( $cost ) ) {
		$result['type'] = 'success';
		$result['cost'] = mem_sanitize_amount( (float) $cost );
	} else {
		$result['type'] = 'success';
		$result['cost'] = mem_sanitize_amount( 0 );
	}

	$result = json_encode( $result );

	echo $result;

	die();
} // mem_update_event_cost_from_package_ajax
add_action( 'wp_ajax_update_event_cost_from_package', 'mem_update_event_cost_from_package_ajax' );

/**
 * Return all dates within the given range
 *
 * @param str $from_date The start date Y-m-d.
 * $str $to_date The end date Y-m-d
 *
 * @return all dates between 2 given dates as an array
 */
function mem_all_dates_in_range( $from_date, $to_date ) {

	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_get_all_dates_in_range' );

	return mem_get_all_dates_in_range( $from_date, $to_date );
} // mem_all_dates_in_range

/**
 * Insert an employee holiday into the database
 *
 * @param arr $args an array of information regarding the holiday.
 * 'from_date' Y-m-d
 * 'to_date' Y-m-d
 * 'employee' UserID
 * 'notes' String with information re holiday
 */
function mem_add_holiday( $args ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_add_employee_absence()' );

	$employee_id = $args['employee'];

	do_action( 'mem_before_added_holiday', $args, $date_range );

	mem_add_employee_absence( $employee_id, $args );

	do_action( 'mem_added_holiday', $args, $date_range );

	mem_update_notice( 'updated', __( 'The entry was added successfully', 'mobile-events-manager' ) );
} // mem_add_holiday

/**
 * Remove an employee holiday entry from the database
 *
 * @param int $entry The database ID for the entry.
 */
function mem_remove_holiday( $entry_id ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_remove_employee_absence()' );

	do_action( 'mem_before_remove_holiday', $entry_id );

	mem_remove_employee_absence( $entry_id );
} // mem_remove_holiday

/**
 * Determine if the current user is a DJ
 *
 * @since:   1.1.3
 * @params:
 * @returns: bool    true : false
 */
function mem_is_dj( $user = '' ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_is_employee()' );
	if ( ! empty( $user ) && user_can( $user, 'dj' ) ) {
		return true;
	}

	if ( current_user_can( 'dj' ) ) {
		return true;
	}

	return false;
} // mem_is_dj

/**
 * mem_mem_dj_can
 * 19/03/2015
 * Determine if the DJ is allowed to carry out the current action
 *
 *  @since: 1.1.3
 *  @params: $task
 *  @returns: true : false
 */
function mem_mem_dj_can( $task ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_employee_can()' );
	global $mem_settings;

	return isset( $mem_settings['permissions'][ 'dj_' . $task ] ) ? true : false;
}

/**
 * Mdjm_get_djs
 * 19/03/2015
 * Retrieve a list of all DJ's
 *
 *   @since: 1.1.3
 *   @params:
 *   @returns: $djs => object
 */
function mem_get_djs( $role = 'dj' ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_get_employees()' );
	return mem_get_employees(
		'dj' === $role ? array( 'administrator', $role ) : $role
	);
} // mem_get_djs

/**
 * Check the availability of the Employee('s) on the given date (Y-m-d)
 *
 * @param   int          $employees  Optional: The user ID of the employee, if empty we'll check all.
 * @param   arr          $roles  Optional: If no $dj is set, we can check an array of role names.
 * @param   string|array $date   The date (Y-m-d) to check.
 * @return  array           $status array of user id's (['available'] | ['unavailable']
 */
function mem_dj_available( $employees = '', $roles = '', $date = '' ) {
	_deprecated_function( __FUNCTION__, '1.5.6', 'mem_do_availability_check()' );
	return mem_do_availability_check( $date, $employees, $roles );
} // mem_dj_available
