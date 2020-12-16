<?php
/**
 * Contains all equipment and package related functions
 *
 * @package MEM
 * @subpackage Venues
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether or not packages are enabled.
 *
 * @since 1.3
 * @return bool True if packages are enabled, false if they are not
 */
function mem_packages_enabled() {
	return mem_get_option( 'enable_packages', false );
} // mem_packages_enabled

/**
 * Get all packages.
 *
 * @since 1.4
 * @param arr $args Array of arguments. See @get_posts.
 * @return arr|bool Package details, or false if none.
 */
function mem_get_packages( $args = array() ) {

	$defaults = array(
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_type'      => 'mem-package',
		'post_status'    => 'publish',
	);

	$package_args = wp_parse_args( $args, $defaults );

	return apply_filters( 'mem_get_packages', get_posts( $package_args ) );

} // mem_get_packages

/**
 * Retrieve a single package.
 *
 * @since 1.4
 * @param int $package_id The ID of the package.
 * @return WP_Post object.
 */
function mem_get_package( $package_id ) {
	return mem_get_package_by( 'id', $package_id );
} // mem_get_package

/**
 * Get package by
 *
 * Retrieve a package by the given field.
 *
 * @since 1.4
 * @param str   $field The field by which to retrieve.
 * @param mixed $value The value of the field to match.
 * @return obj The WP Post object.
 */
function mem_get_package_by( $field, $value ) {

	if ( empty( $field ) || empty( $value ) ) {
		return false;
	}

	switch ( strtolower( $field ) ) {

		case 'id':
			$package = get_post( $value );

			if ( get_post_type( $package ) !== 'mem-package' ) {
				return false;
			}

			break;

		case 'slug':
		case 'name':
			$package = get_posts(
				array(
					'name' => $value,
				)
			);

			if ( $package ) {
				$package = $package[0];
			}

			break;

		case 'event':
			$event_package = get_post_meta( $value, '_mem_event_package', true );

			if ( ! $event_package ) {
				return false;
			}

			$package = get_post( $event_package );

			if ( get_post_type( $package ) !== 'mem-package' ) {
				return false;
			}

			break;

	}

	return $package;

} // mem_get_package_by

/**
 * Retrieve data for a package.
 *
 * @since 1.4
 * @param int|obj $package The package WP_Post object, or post ID.
 * @return arr
 */
function mem_get_package_data( $package ) {

	$package_id = is_object( $package ) ? $package->ID : $package;
	$events     = mem_get_package_event_types( $package_id );
	$users      = mem_get_employees_with_package( $package_id );
	$items      = mem_get_package_addons( $package_id );
	$cats       = get_the_terms( $package_id, 'package-category' );
	$employees  = array();
	$months     = array();
	$addons     = array();
	$categories = array();

	if ( ! mem_package_is_restricted_by_date( $package_id ) ) {
		$months[] = __( 'Always', 'mobile-events-manager' );
	} else {
		$availability = mem_get_package_months_available( $package_id );

		if ( ! $availability ) {
			$months[] = __( 'Always', 'mobile-events-manager' );
		} else {
			$i = 0;
			foreach ( $availability as $month ) {

				$months[] = mem_month_num_to_name( $availability[ $i ] );
				$i++;
			}
		}
	}

	if ( in_array( 'all', $users ) ) {
		$employees[] = __( 'All Employees', 'mobile-events-manager' );
	} else {
		foreach ( $users as $employee_id ) {
			if ( 'all' === $employee_id ) {
				continue;
			}
			$employees[] = array( $employee_id => mem_get_employee_display_name( $employee_id ) );
		}
	}

	if ( in_array( 'all', $events ) ) {
		$event_types = sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) );
	} else {
		foreach ( $events as $event ) {
			$term = get_term( $event, 'event-types' );

			if ( ! empty( $term ) ) {
				$event_types[] = $term->name;
			}
		}
	}

	if ( mem_package_has_variable_prices( $package_id ) ) {
		$range = mem_get_package_price_range( $package_id );

		$price = mem_get_currency() . ' ' . mem_format_amount( $range['low'] ) . ' &mdash; ' . mem_format_amount( $range['high'] );

	} else {
		$price = mem_get_currency() . ' ' . mem_format_amount( mem_get_package_price( $package_id ) );
	}

	if ( $items ) {
		foreach ( $items as $addon_id ) {
			$addons[] = array( $addon_id => mem_get_addon_name( $addon_id ) );
		}
	}

	if ( $cats ) {
		foreach ( $cats as $cat ) {
			$categories[] = $cat->name;
		}
	}

	$package_data = array(
		'name'         => mem_get_package_name( $package_id ),
		'categories'   => get_the_term_list( $package_id, 'package-category', '', ', ', '' ),
		'availability' => array(
			'months'      => $months,
			'employees'   => $employees,
			'event_types' => $event_types,
		),
		'price'        => $price,
		'items'        => $addons,
		'usage'        => array(
			'events' => mem_count_events_with_package( $package_id ),
		),
	);

	return apply_filters( 'mem_get_package_data', $package_data );

} // mem_get_package_data

/**
 * Retrieve all packages in the given category.
 *
 * @since 1.4
 * @param int|arr $terms The category IDs or names to search.
 * @return arr|bool Packages.
 */
function mem_get_packages_in_category( $term_id ) {

	$field = is_numeric( $term_id ) ? 'term_id' : 'name';

	$args = array(
		'tax_query' => array(
			array(
				'taxonomy'         => 'package-category',
				'field'            => $field,
				'terms'            => $term_id,
				'include_children' => false,
			),
		),
	);

	return mem_get_packages( $args );

} // mem_get_packages_in_category

/**
 * Retrieve a package name.
 *
 * @since 1.4
 * @param int $package_id ID of the package.
 * @return str The package title.
 */
function mem_get_package_name( $package_id ) {
	$title = get_the_title( $package_id );

	return apply_filters( 'mem_package_name', $title, $package_id );
} // mem_get_package_name

/**
 * Retrieve all employees with package.
 *
 * @since 1.4
 * @param int $addon_id ID of the package.
 * @return arr|false Array of employee ID's with the package or false if none.
 */
function mem_get_employees_with_package( $package_id ) {
	$employees = get_post_meta( $package_id, '_package_employees', true );

	return apply_filters( 'mem_employees_with_addon', $employees, $package_id );
} // mem_get_employees_with_package

/**
 * Retrieve all event types for which the package is available.
 *
 * @since 1.4
 * @param int $addon_id ID of the package.
 * @return arr Array of event types the package is available.
 */
function mem_get_package_event_types( $package_id ) {
	$event_types = get_post_meta( $package_id, '_package_event_types', true );

	return apply_filters( 'mem_package_event_types', $event_types, $package_id );
} // mem_get_package_event_types

/**
 * Whether or not this package is restricted by date.
 *
 * @since 1.4
 * @param int $package_id ID of the package.
 * @return bool True if restricted, otherwise false.
 */
function mem_package_is_restricted_by_date( $package_id ) {
	$restricted = get_post_meta( $package_id, '_package_restrict_date', true );

	// If the package is restricted, there needs to be months set for availability.
	if ( $restricted ) {
		$months = mem_get_package_months_available( $package_id );

		if ( ! $months ) {
			$restricted = false;
		}
	}

	return apply_filters( 'mem_package_is_restricted_by_date', $restricted, $package_id );

} // mem_package_is_restricted_by_date

/**
 * Retrieve the months the package is available.
 *
 * @since 1.4
 * @param int $package_id ID of the package.
 * @return arr|false Array of month numbers this package is available, otherwise false.
 */
function mem_get_package_months_available( $package_id ) {
	$months = get_post_meta( $package_id, '_package_months', true );

	return apply_filters( 'mem_package_months_available', $months, $package_id );
} // mem_get_package_months_available

/**
 * Retrieve the price of the package.
 *
 * @since 1.4
 * @param int     $package_id ID of the package.
 * @param int|str $date Month number (1-12) or date (Y-m-d).
 * @return str The cost of the package.
 */
function mem_get_package_price( $package_id, $date = null ) {
	if ( isset( $date ) ) {
		$price = mem_get_package_price_for_month( $package_id, $date );
	} else {
		$price = get_post_meta( $package_id, '_package_price', true );
	}

	return apply_filters( 'mem_package_price', $price, $package_id );
} // mem_get_package_price

/**
 * Retrieves the price of a for a given month.
 *
 * @since 1.4
 * @param int     $package_id ID of the package.
 * @param int|str $date Either a numerical value for the month (1-12) or the date (Y-m-d).
 * @return str The cost of the package.
 */
function mem_get_package_price_for_month( $package_id, $date = null ) {

	if ( ! mem_package_has_variable_prices( $package_id ) ) {
		return mem_get_package_price( $package_id );
	}

	$price = mem_get_package_price( $package_id );

	if ( ! isset( $date ) ) {
		$date = gmdate( 'n' );
	}

	if ( ! is_numeric( $date ) ) {
		$date = gmdate( 'n', strtotime( $date ) );
	}

	$monthly_prices = mem_get_package_variable_prices( $package_id );

	foreach ( $monthly_prices as $data ) {
		if ( in_array( $date, $data['months'] ) ) {
			$price = $data['amount'];
		}
	}

	return $price;

} // mem_get_package_price_for_month

/**
 * Whether or not the package has monthly prices.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return bool True if variable pricing is enabled.
 */
function mem_package_has_variable_prices( $package_id ) {
	$variable_pricing = get_post_meta( $package_id, '_package_variable_pricing', true );

	return apply_filters( 'mem_package_has_variable_pricing', $variable_pricing, $package_id );
} // mem_package_has_variable_prices

/**
 * Retrieve a packages monthly prices.
 *
 * @since 1.4
 * @param int $addon_id ID of the package.
 * @return bool True if variable pricing is enabled.
 */
function mem_get_package_variable_prices( $package_id ) {
	$variable_pricing = get_post_meta( $package_id, '_package_variable_prices', true );

	return apply_filters( 'mem_package_monthly_pricing', $variable_pricing, $package_id );
} // mem_get_package_variable_prices

/**
 * Retrieve a packages price range.
 *
 * @since 1.4
 * @param int $package_id ID of the package.
 * @return arr Array of low and high prices.
 */
function mem_get_package_price_range( $package_id ) {

	if ( ! mem_package_has_variable_prices( $package_id ) ) {
		return;
	}

	$range = array();

	$prices = mem_get_package_variable_prices( $package_id );

	foreach ( $prices as $price ) {

		if ( empty( $range['low'] ) || $price['amount'] < $range['low'] ) {
			$range['low'] = $price['amount'];
		}

		if ( empty( $range['high'] ) || $price['amount'] > $range['high'] ) {
			$range['high'] = $price['amount'];
		}
	}

	return apply_filters( 'mem_package_price_range', $range, $package_id );

} // mem_get_package_price_range

/**
 * Retrieve the items in a package.
 *
 * @since 1.4
 * @param int $package_id The package ID.
 * @return arr|false Array of addon ID's in this package, or false if none.
 */
function mem_get_package_addons( $package_id ) {
	$items = get_post_meta( $package_id, '_package_items', true );

	return apply_filters( 'mem_package_items', $items, $package_id );
} // mem_get_package_addons

/**
 * Whether or not a package contains an addon.
 *
 * @since 1.4
 * @param $package_id The package ID.
 * @param $addon_id The addon ID to check if within the package.
 * @return bool True if the addon exists in the package, otherwise false.
 */
function mem_package_has_addon( $package_id, $addon_id ) {
	$addons = mem_get_package_addons( $addon_id );

	return in_array( $addon_id, $addons );
} // mem_package_has_addon

/**
 * Retrieve all packages with the given addon(s).
 *
 * @since 1.4
 * @param int|arr $addon_ids ID(s) of addons to look for.
 * @return mixed Array of WP_Post objects or false.
 */
function mem_get_packages_with_addons( $addon_ids ) {

	return mem_get_packages(
		array(
			'meta_query' => array(
				array(
					'key'     => '_package_items',
					'value'   => sprintf( ':"%s";', $addon_ids ),
					'compare' => 'LIKE',
				),
			),
		)
	);

} // mem_get_packages_with_addons

/**
 * Retrieve the count of packages containing the given addon.
 *
 * @since 1.4
 * @param int $addon_id list of addons.
 * @return int
 */
function mem_count_packages_with_addon( $addon_id ) {

	$count    = 0;
	$packages = mem_get_packages();

	if ( $packages ) {
		foreach ( $packages as $package ) {
			$addons = mem_get_package_addons( $package->ID );

			if ( $addons && in_array( $addon_id, $addons ) ) {
				$count++;
			}
		}
	}

	return $count;

} // mem_count_packages_with_addon

/**
 * Get all packages for the given employee.
 *
 * @since 1.3
 * @param int  $employee_id The employee ID whose packages we want.
 * @param bool $enabled True returns only enabled packages, false returns all.
 * @return obj The WP Post objects for the employee's packages.
 */
function mem_get_packages_by_employee( $employee_id = 0, $enabled = true ) {

	if ( empty( $employee_id ) && is_user_logged_in() ) {
		$employee_id = get_current_user_id();
	}

	if ( empty( $employee_id ) ) {
		return false;
	}

	$args = array(
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'DESC',
		'post_type'      => 'mem-package',
		'post_status'    => $enabled ? 'publish' : 'any',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_package_employees',
				'value'   => sprintf( ':"%s";', $employee_id ),
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_package_employees',
				'value'   => sprintf( ':"all";' ),
				'compare' => 'LIKE',
			),
		),
	);

	$packages = mem_get_packages( $args );

	return apply_filters( 'mem_get_packages_by_employee', $packages );

} // mem_get_packages_by_employee

/**
 * Whether or not an employee has the package.
 *
 * @since 1.4
 * @param int $package_id The package ID.
 * @param int $employee_id The employee ID to check.
 * @return bool True if the employee has the package, or false if not.
 */
function mem_employee_has_package( $package_id, $employee_id = 0 ) {

	$employees = mem_get_employees_with_package( $package_id );

	if ( empty( $employee_id ) ) {
		$employee_id = get_current_user_id();
	}

	if ( $employees ) {
		if ( ! is_array( $employees ) ) {
			$employees = array( 'all' );
		}

		if ( in_array( 'all', $employees ) || in_array( $employee_id, $employees ) ) {
			return true;
		}
	}

	return false;

} // mem_employee_has_package

/**
 * Whether or not a package is available for the event type.
 *
 * @since 1.4
 * @param int     $package_id The package ID.
 * @param int|str $event_type_term The event type term ID's to check.
 * @return bool True if the package is available for the event type, or false if not.
 */
function mem_package_is_available_for_event_type( $package_id, $event_type_term = '' ) {

	if ( empty( $event_type_term ) ) {
		return true;
	}

	$event_types = mem_get_package_event_types( $package_id );

	if ( $event_types ) {
		if ( in_array( 'all', $event_types ) || in_array( $event_type_term, $event_types ) ) {
			return true;
		}
	}

	return false;

} // mem_package_is_available_for_event_type

/**
 * Whether or not a package is available for the event date.
 *
 * @since 1.4
 * @param int     $package_id The package ID.
 * @param int|str $event_date The event date (YYYY-mm-dd) or the month as a numeric value (12).
 * @return bool True if the package is available for the event date, or false if not.
 */
function mem_package_is_available_for_event_date( $package_id, $event_date = '' ) {

	if ( empty( $event_date ) ) {
		return true;
	}

	if ( ! mem_package_is_restricted_by_date( $package_id ) ) {
		return true;
	}

	$event_months = mem_get_package_months_available( $package_id );

	if ( is_numeric( $event_date ) ) {
		$event_month = $event_date;
	} else {
		$event_month = gmdate( 'n', strtotime( $event_date ) );
	}

	if ( in_array( $event_month, $event_months ) ) {
		return true;
	}

	return false;

} // mem_package_is_available_for_event_date

/**
 * Retrieve an events package.
 *
 * @since 1.4
 * @param int $event_id The event ID.
 * @return int|false The event package or false if no package.
 */
function mem_get_event_package( $event_id ) {
	$package = get_post_meta( $event_id, '_mem_event_package', true );

	return apply_filters( 'mem_event_package', $package );
} // mem_get_event_package

/**
 * Retrieve all events with the given package.
 *
 * @since 1.4
 * @param int $package_id The package ID.
 * @return arr|false Array of WP_Post objects or false.
 */
function mem_get_events_with_package( $package_id ) {
	return mem_get_events(
		array(
			'meta_query' => array(
				'key'     => '_mem_event_package',
				'value'   => $package_id,
				'compare' => '=',
			),
		)
	);
} // mem_get_events_with_package

/**
 * Retrieve the count of events with the given package assigned.
 *
 * @since 1.4
 * @param int $package_id.
 * @return int
 */
function mem_count_events_with_package( $package_id ) {
	global $wpdb;

	$query = $wpdb->prepare(
		"
			SELECT COUNT(*)
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			AND
			meta_value = %d
		",
		'_mem_event_package',
		$package_id
	);

	$event_count = $wpdb->get_var( $query );

	if ( ! empty( $event_count ) ) {
		return $event_count;
	}

	return 0;

} // mem_count_events_with_package

/**
 * Retrieve the available packages.
 *
 * Availability can be dependant on an employee, month and event type.
 *
 * @param   arr $args   Arguments for package retrieval. See @defaults.
 * @return  arr|false   Array of WP_Post objects.
 */
function mem_mem_get_available_packages( $args = array() ) {

	if ( ! mem_packages_enabled() ) {
		return false;
	}

	$defaults = array(
		'employee'   => false,
		'date'       => false,
		'event_type' => false,
	);

	$args         = wp_parse_args( $args, $defaults );
	$package_args = array();

	if ( ! empty( $args['employee'] ) ) {
		$package_args['meta_query'] = array(
			array(
				'key'     => '_mem_employees',
				'value'   => sprintf( ':"%s";', $args['employee'] ),
				'compare' => 'LIKE',
			),
		);
	}

	$packages = mem_get_packages( $package_args );

	return apply_filters( 'mem_available_packages', $packages );

} // mem_mem_get_available_packages

/**
 * List all available packages. If an employee ID is provided, list what that
 * employee can provide only.
 *
 * @since 1.3
 * @param int  $employee_id An employee user ID, otherwise query current user.
 * @param bool $price True to display the formatted package price.
 * @return str HTML formatted string listing package information
 */
function mem_list_available_packages( $employee_id = 0, $price = false ) {

	if ( ! mem_packages_enabled() ) {
		return __( 'No packages available', 'mobile-events-manager' );
	}

	if ( ! empty( $employee_id ) ) {
		$packages = mem_get_packages_by_employee( $employee_id, false );
	} else {
		$packages = mem_get_packages();
	}

	if ( empty( $packages ) ) {
		return __( 'No packages available', 'mobile-events-manager' );
	}

	$return = array();

	foreach ( $packages as $package ) {
		$package_price = '';

		if ( $price ) {
			$package_price = ' ' . mem_currency_filter( mem_format_amount( mem_get_package_price( $package->ID ) ) );
		}

		$return[] = get_the_title( $package->ID ) . '' . $package_price;
	}

	$return = apply_filters( 'mem_list_packages', $return, $price );

	return implode( '<br />', $return );

} // mem_list_available_packages

/**
 * Retrieve a packages excerpt.
 *
 * @since 1.4
 * @param int $package_id The ID of the package.
 * @param int $length The length of the excerpt.
 * @return str
 */
function mem_get_package_excerpt( $package_id, $length = null ) {

	if ( ! isset( $length ) ) {
		$length = mem_get_option( 'package_excerpt_length', 55 );
	}

	if ( has_excerpt( $package_id ) ) {
		$description = get_post_field( 'post_excerpt', $package_id );
	} else {
		$description = get_post_field( 'post_content', $package_id );
	}

	if ( ! empty( $length ) ) {
		$description = wp_trim_words( $description, $length );
	}

	return apply_filters( 'mem_package_excerpt', $description );

} // mem_get_package_excerpt

/**
 * Renders HTML code for Package dropdown.
 *
 * @param arr  $settings See @$defaults.
 * @param bool $structure True to echo the select field structure, false just returns options.
 * @return HTML output for select field
 */
function mem_package_dropdown( $args = array(), $structure = true ) {

	global $current_user;

	$defaults = array(
		'name'            => '_mem_event_package',
		'id'              => '',
		'class'           => '',
		'selected'        => '',
		'first_entry'     => '',
		'first_entry_val' => '',
		'employee'        => ( is_user_logged_in() && ! current_user_can( 'client' ) ) ? $current_user->ID : '',
		'event_type'      => false,
		'event_date'      => false,
		'exclude_cats'    => null,
		'titles'          => true,
		'cost'            => true,
		'required'        => false,
	);

	$args = wp_parse_args( $args, $defaults );

	// For backwards compatibility.
	if ( isset( $args['dj'] ) ) {
		$args['employee'] = $args['dj'];
	}

	$args['required'] = ! empty( $args['required'] ) ? ' required' : '';
	$args['id']       = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

	$output = '';

	if ( $structure ) {
		$output = sprintf(
			'<select name="%s" id="%s" class="%s"%s>',
			esc_attr( $args['name'] ),
			esc_attr( mem_sanitize_key( $args['id'] ) ),
			sanitize_html_class( $args['class'] ),
			$args['required']
		) . "\n";
	}

	$args = array_merge(
		$args,
		array(
			'show_option_none' => ! empty( $args['first_entry'] ) ? $args['first_entry'] : false,
			'show_option_all'  => false,
			'options_only'     => true,
			'blank_first'      => false,
		)
	);

	$output .= MEM()->html->packages_dropdown( $args );

	if ( $structure ) {
		$output .= '</select>' . "\n";
	}

	return $output;

} // mem_package_dropdown

/**
 * Remove package from events.
 *
 * @since 1.4
 * @param int|arr $package_id ID of the package to remove.
 * @return void
 */
function mem_remove_package_from_events( $package_id ) {

	$events = mem_get_events_with_package( $package_id );

	if ( $events ) {
		foreach ( $events as $event ) {
			delete_post_meta( $event->ID, '_mem_event_package' );
		}
	}

} // mem_remove_addons_from_events
add_action( 'mem_delete_package', 'mem_remove_package_from_events' );

/***********************************************************
 * Addon Functions
 **********************************************************/

/**
 * Retrieve all addons.
 *
 * @since 1.4
 * @param arr $args Array of arguments. See @get_posts.
 * @return arr|bool Addons.
 */
function mem_get_addons( $args = array() ) {

	$defaults = array(
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'post_type'      => 'mem-addon',
		'post_status'    => 'publish',
	);

	$args = wp_parse_args( $args, $defaults );

	return apply_filters( 'mem_get_addons', get_posts( $args ) );

} // mem_get_addons

/**
 * Retrieve a single addon.
 *
 * @since 1.4
 * @param int $addon_id The ID of the addon.
 * @return WP_Post object.
 */
function mem_get_addon( $addon_id ) {
	return mem_get_addon_by( 'id', $addon_id );
} // mem_get_addon

/**
 * Get addon by
 *
 * Retrieve an addon by the given field.
 *
 * @since 1.4
 * @param str   $field The field by which to retrieve.
 * @param mixed $value The value of the field to match.
 * @return obj The WP Post object.
 */
function mem_get_addon_by( $field, $value ) {

	if ( empty( $field ) || empty( $value ) ) {
		return false;
	}

	switch ( strtolower( $field ) ) {

		case 'id':
			$addon = get_post( $value );

			if ( get_post_type( $addon ) !== 'mem-addon' ) {
				return false;
			}

			break;

		case 'slug':
		case 'name':
			$addon = mem_get_addons(
				array(
					'name' => $value,
				)
			);

			if ( $addon ) {
				$addon = $addon[0];
			}

			break;

	}

	return $addon;

} // mem_get_addon_by

/**
 * Retrieve data for an addon.
 *
 * @since 1.4
 * @param int|obj $addon The addon WP_Post object, or post ID.
 * @return arr
 */
function mem_get_addon_data( $addon ) {

	$addon_id    = is_object( $addon ) ? $addon->ID : $addon;
	$events      = mem_get_addon_event_types( $addon_id );
	$users       = mem_get_employees_with_addon( $addon_id );
	$packages    = mem_get_packages_with_addons( $addon_id );
	$cats        = get_the_terms( $addon_id, 'addon-category' );
	$employees   = array();
	$months      = array();
	$categories  = array();
	$in_packages = array();

	if ( ! mem_addon_is_restricted_by_date( $addon_id ) ) {
		$months[] = __( 'Always', 'mobile-events-manager' );
	} else {
		$availability = mem_get_addon_months_available( $addon_id );

		if ( ! $availability ) {
			$months[] = __( 'Always', 'mobile-events-manager' );
		} else {
			$i = 0;
			foreach ( $availability as $month ) {

				$months[] = mem_month_num_to_name( $availability[ $i ] );
				$i++;
			}
		}
	}

	if ( in_array( 'all', $users ) ) {
		$employees[] = __( 'All Employees', 'mobile-events-manager' );
	} else {
		foreach ( $users as $employee_id ) {
			if ( 'all' === $employee_id ) {
				continue;
			}
			$employees[] = array( $employee_id => mem_get_employee_display_name( $employee_id ) );
		}
	}

	if ( in_array( 'all', $events ) ) {
		$event_types = sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) );
	} else {
		foreach ( $events as $event ) {
			$term = get_term( $event, 'event-types' );

			if ( ! empty( $term ) ) {
				$event_types[] = $term->name;
			}
		}
	}

	if ( mem_addon_has_variable_prices( $addon_id ) ) {
		$range = mem_get_addon_price_range( $addon_id );

		$price = mem_get_currency() . ' ' . mem_format_amount( $range['low'] ) . ' &mdash; ' . mem_format_amount( $range['high'] );

	} else {
		$price = mem_get_currency() . ' ' . mem_format_amount( mem_get_addon_price( $addon_id ) );
	}

	if ( $packages ) {
		foreach ( $packages as $package ) {
			$in_packages[] = array( $package->ID => mem_get_package_name( $package->ID ) );
		}
	}

	if ( $cats ) {
		foreach ( $cats as $cat ) {
			$categories[] = $cat->name;
		}
	}

	$addon_data = array(
		'name'         => mem_get_addon_name( $addon_id ),
		'categories'   => $categories,
		'availability' => array(
			'months'      => $months,
			'employees'   => $employees,
			'event_types' => $event_types,
		),
		'price'        => $price,
		'packages'     => $in_packages,
		'usage'        => array(
			'packages' => mem_count_packages_with_addon( $addon_id ),
			'events'   => mem_count_events_with_addon( $addon_id ),
		),
	);

	return apply_filters( 'mem_get_addon_data', $addon_data );

} // mem_get_addon_data

/**
 * Retrieve all add-ons in the given category.
 *
 * @since 1.4
 * @param int|arr $term_id The category IDs or names to search.
 * @return arr|bool Addons.
 */
function mem_get_addons_in_category( $term_id ) {

	$field = is_numeric( $term_id ) ? 'term_id' : 'name';

	$args = array(
		'tax_query' => array(
			array(
				'taxonomy'         => 'addon-category',
				'field'            => $field,
				'terms'            => $term_id,
				'include_children' => false,
			),
		),
	);

	return mem_get_addons( $args );

} // mem_get_addons_in_category

/**
 * Retrieve an addons name.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return str The addon title.
 */
function mem_get_addon_name( $addon_id ) {
	$title = get_the_title( $addon_id );

	return apply_filters( 'mem_addon_name', $title, $addon_id );
} // mem_get_addon_name

/**
 * Retrieve all employees with addon.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return arr|false Array of employee ID's with the addon or false if none.
 */
function mem_get_employees_with_addon( $addon_id ) {
	$employees = get_post_meta( $addon_id, '_addon_employees', true );

	return apply_filters( 'mem_employees_with_addon', $employees, $addon_id );
} // mem_get_employees_with_addon

/**
 * Retrieve all event types for which the addon is available.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return arr Array of event types the addon is available.
 */
function mem_get_addon_event_types( $addon_id ) {
	$event_types = get_post_meta( $addon_id, '_addon_event_types', true );

	if ( ! $event_types || ! is_array( $event_types ) ) {
		$event_types = array( 'all' );
	}

	return apply_filters( 'mem_addon_event_types', $event_types, $addon_id );
} // mem_get_addon_event_types

/**
 * Whether or not this addon is restricted by date.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return bool True if restricted, otherwise false.
 */
function mem_addon_is_restricted_by_date( $addon_id ) {
	$restricted = get_post_meta( $addon_id, '_addon_restrict_date', true );

	// If the addon is restricted, there needs to be months set for availability.
	if ( $restricted ) {
		$months = mem_get_addon_months_available( $addon_id );

		if ( ! $months ) {
			$restricted = false;
		}
	}

	return apply_filters( 'mem_addon_is_restricted_by_date', $restricted, $addon_id );

} // mem_addon_is_restricted_by_date

/**
 * Retrieve the months the addon is available.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return arr|false Array of month numbers this addon is available, otherwise false.
 */
function mem_get_addon_months_available( $addon_id ) {
	$months = get_post_meta( $addon_id, '_addon_months', true );

	return apply_filters( 'mem_addon_months_available', $months, $addon_id );
} // mem_get_addon_months_available

/**
 * Retrieve the price of the addon.
 *
 * @since 1.4
 * @param int     $addon_id ID of the addon.
 * @param int|str $date Month number (1-12) or date (Y-m-d).
 * @return str The cost of the addon.
 */
function mem_get_addon_price( $addon_id, $date = null ) {
	if ( isset( $date ) ) {
		$price = mem_get_addon_price_for_month( $addon_id, $date );
	} else {
		$price = get_post_meta( $addon_id, '_addon_price', true );
	}

	return apply_filters( 'mem_addon_price', $price, $addon_id );
} // mem_get_addon_price

/**
 * Retrieves the price of an addon for a given month.
 *
 * @since 1.4
 * @param int     $addon_id ID of the addon.
 * @param int|str $date Either a numerical value for the month (1-12) or the date (Y-m-d).
 * @return str The cost of the addon.
 */
function mem_get_addon_price_for_month( $addon_id, $date = null ) {

	if ( ! mem_addon_has_variable_prices( $addon_id ) ) {
		return mem_get_addon_price( $addon_id );
	}

	$price = mem_get_addon_price( $addon_id );

	if ( ! isset( $date ) ) {
		$date = gmdate( 'n' );
	}

	if ( ! is_numeric( $date ) ) {
		$date = gmdate( 'n', strtotime( $date ) );
	}

	$monthly_prices = mem_get_addon_variable_prices( $addon_id );

	foreach ( $monthly_prices as $data ) {
		if ( in_array( $date, $data['months'] ) ) {
			$price = $data['amount'];
		}
	}

	return $price;

} // mem_get_addon_price_for_month

/**
 * Whether or not the addon has monthly prices.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return bool True if variable pricing is enabled.
 */
function mem_addon_has_variable_prices( $addon_id ) {
	$variable_pricing = get_post_meta( $addon_id, '_addon_variable_pricing', true );

	return apply_filters( 'mem_addon_has_variable_pricing', $variable_pricing, $addon_id );
} // mem_addon_has_variable_prices

/**
 * Retrieve an add-ons monthly prices.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return bool True if variable pricing is enabled.
 */
function mem_get_addon_variable_prices( $addon_id ) {
	$variable_pricing = get_post_meta( $addon_id, '_addon_variable_prices', true );

	return apply_filters( 'mem_addon_monthly_pricing', $variable_pricing, $addon_id );
} // mem_get_addon_variable_prices

/**
 * Retrieve an add-ons price range.
 *
 * @since 1.4
 * @param int $addon_id ID of the addon.
 * @return arr Array of low and high prices.
 */
function mem_get_addon_price_range( $addon_id ) {

	if ( ! mem_addon_has_variable_prices( $addon_id ) ) {
		return;
	}

	$range = array();

	$prices = mem_get_addon_variable_prices( $addon_id );

	foreach ( $prices as $price ) {

		if ( empty( $range['low'] ) || $price['amount'] < $range['low'] ) {
			$range['low'] = $price['amount'];
		}

		if ( empty( $range['high'] ) || $price['amount'] > $range['high'] ) {
			$range['high'] = $price['amount'];
		}
	}

	return apply_filters( 'mem_addon_price_range', $range, $addon_id );

} // mem_get_addon_price_range

/**
 * Get all packages for the given employee.
 *
 * @since 1.4
 * @param int  $employee_id The employee ID whose addons we want.
 * @param bool $enabled True returns only enabled addons, false returns all.
 * @return obj The WP Post objects for the employee's addons.
 */
function mem_get_addons_by_employee( $employee_id = 0, $enabled = true ) {

	if ( empty( $employee_id ) && is_user_logged_in() ) {
		$employee_id = get_current_user_id();
	}

	if ( empty( $employee_id ) ) {
		return false;
	}

	$args = array(
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'DESC',
		'post_type'      => 'mem-addon',
		'post_status'    => $enabled ? 'publish' : 'any',
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_addon_employees',
				'value'   => sprintf( ':"%s";', $employee_id ),
				'compare' => 'LIKE',
			),
			array(
				'key'     => '_addon_employees',
				'value'   => sprintf( ':"all";' ),
				'compare' => 'LIKE',
			),
		),
	);

	$addons = mem_get_addons( $args );

	return apply_filters( 'mem_get_addons_by_employee', $addons );

} // mem_get_addons_by_employee

/**
 * Whether or not an employee has the addon.
 *
 * @since 1.4
 * @param int $addon_id The addon ID.
 * @param int $employee_id The employee ID to check.
 * @return bool True if the employee has the addon, or false if not.
 */
function mem_employee_has_addon( $addon_id, $employee_id = 0 ) {

	$employees = mem_get_employees_with_addon( $addon_id );

	if ( empty( $employee_id ) ) {
		$employee_id = get_current_user_id();
	}

	if ( $employees ) {
		if ( in_array( 'all', $employees ) || in_array( $employee_id, $employees ) ) {
			return true;
		}
	}

	return false;

} // mem_employee_has_addon

/**
 * Whether or not an addon is available for the event type.
 *
 * @since 1.4
 * @param int     $addon_id The package ID.
 * @param int|str $event_type_terms The event type term ID's to check.
 * @return bool True if the addon is available for the event type, or false if not.
 */
function mem_addon_is_available_for_event_type( $addon_id, $event_type_term = '' ) {

	if ( empty( $event_type_term ) ) {
		return true;
	}

	$event_types = mem_get_addon_event_types( $addon_id );

	if ( $event_types ) {
		if ( in_array( 'all', $event_types ) || in_array( $event_type_term, $event_types ) ) {
			return true;
		}
	}

	return false;

} // mem_addon_is_available_for_event_type

/**
 * Whether or not an addon is available for the event date.
 *
 * @since 1.4
 * @param int $addon_id The addon ID.
 * @param str $event_date The event date.
 * @return bool True if the addon is available for the event date, or false if not.
 */
function mem_addon_is_available_for_event_date( $addon_id, $event_date = '' ) {

	if ( empty( $event_date ) ) {
		return true;
	}

	if ( ! mem_addon_is_restricted_by_date( $addon_id ) ) {
		return true;
	}

	$event_months = mem_get_addon_months_available( $addon_id );
	$event_month  = gmdate( 'n', strtotime( $event_date ) );

	if ( in_array( $event_month, $event_months ) ) {
		return true;
	}

	return false;

} // mem_addon_is_available_for_event_date

/**
 * Retrieve an events addons.
 *
 * @since 1.4
 * @param int $event_id The event ID.
 * @return int|false The event addons or false if no addons.
 */
function mem_get_event_addons( $event_id ) {
	$addons = get_post_meta( $event_id, '_mem_event_addons', true );

	return apply_filters( 'mem_event_addons', $addons );
} // mem_get_event_addons

/**
 * Retrieve all events with the given addon(s).
 *
 * @since 1.4
 * @param int|arr $addon_ids ID(s) of addons to look for.
 * @return mixed Array of WP_Post objects or false.
 */
function mem_get_events_with_addons( $addon_ids ) {

	if ( ! is_array( $addon_ids ) ) {
		$addon_ids = array( $addon_ids );
	}

	$meta_query = array();

	foreach ( $addon_ids as $addon_id ) {
		$meta_query[] = array(
			'key'     => '_mem_event_addons',
			'value'   => sprintf( ':"%s";', $addon_id ),
			'compare' => 'LIKE',
		);
	}

	$args['meta_query'] = array(
		'relation' => 'OR',
		$meta_query,
	);

	return mem_get_events( $args );

} // mem_get_events_with_addons

/**
 * Retrieve the count of events that have the given addon associated.
 *
 * @since 1.4
 * @param int $addon_id The addon ID.
 * @return int
 */
function mem_count_events_with_addon( $addon_id ) {

	global $wpdb;

	$count  = 0;
	$query  = "SELECT * FROM $wpdb->postmeta WHERE meta_value != '' AND meta_key = '_mem_event_addons'";
	$events = $wpdb->get_results( $query );

	if ( $events ) {
		foreach ( $events as $event ) {
			$addons = mem_get_event_addons( $event->post_id );

			// For backwards compatibility.
			if ( $addons && ! is_array( $addons ) ) {
				$addons = explode( ',', $addons );
			}

			if ( $addons && in_array( $addon_id, $addons ) ) {
				$count++;
			}
		}
	}

	return $count;

} // mem_count_events_with_addon

/**
 * Lists an events addons.
 *
 * @since 1.4
 * @param int  $event_id The event ID.
 * @param bool $price True to include the addon price.
 * @return int|false The event addons or false if no addons.
 */
function mem_list_event_addons( $event_id, $price = false ) {

	$output = __( 'No addons are assigned to this event', 'mobile-events-manager' );

	if ( ! mem_packages_enabled() ) {
		return $output;
	}

	$mem_event = new MEM_Event( $event_id );

	$event_addons = $mem_event->get_addons();
	$event_date   = $mem_event->date;

	if ( $event_addons ) {
		$addons = array();

		foreach ( $event_addons as $addon_id ) {
			$addon_price = '';

			if ( $price ) {
				$addon_price = ' ' . mem_currency_filter( mem_format_amount( mem_get_addon_price( $addon_id, $event_date ) ) );
			}

			$addons[] = mem_get_addon_name( $addon_id ) . '' . $addon_price;
		}

		$output = implode( '<br />', $addons );

	}

	return apply_filters( 'mem_list_event_addons', $output );

} // mem_list_event_addons

/**
 * Retrieve a list of available addons.
 *
 * @param arr $args Array of arguments. See @defaults.
 * @return arr Array of WP_Post objects for available addons.
 */
function mem_get_available_addons( $args = array() ) {

	if ( ! mem_packages_enabled() ) {
		return __( 'No addons are available', 'mobile-events-manager' );
	}

	$defaults = array(
		'employee' => 0,
		'event_id' => 0,
		'package'  => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$addon_args = array();

	if ( ! empty( $args['employee'] ) ) {
		$addon_args['meta_query'] = array(
			'key'     => '_mem_employees',
			'value'   => array( $args['employee'] ),
			'compare' => 'IN',
		);
	}

	if ( ! empty( $args['event_id'] ) ) {
		$event_addons = mem_get_event_addons( $args['event_id'] );

		if ( $event_addons ) {
			$addon_args['post__not_in'] = $event_addons;
		}
	}

	if ( ! empty( $args['package'] ) ) {
		if ( 'mem-package' === get_post_type( $args['package_id'] ) ) {
			$package_items = mem_get_package_addons( $args['package_id'] );

			if ( $package_items ) {
				if ( ! empty( $addon_args['post__not_in'] ) ) {
					$addon_args['post__not_in'] = array_merge( $addon_args['post__not_in'], $package_items );
				}
			}
		}
	}

	$addons = mem_get_addons( $addon_args );

	return apply_filters( 'mem_available_addons', $addons );

} // mem_get_available_addons

/**
 * List all available addons. If an employee ID is provided, list what that
 * employee can provide only.
 *
 * @since 1.4
 * @param int  $employee_id An employee user ID, otherwise query current user.
 * @param bool $price True to display the formatted package price.
 * @return str HTML formatted string listing package information
 */
function mem_list_available_addons( $employee_id = 0, $price = false ) {

	if ( ! mem_packages_enabled() ) {
		return __( 'No addons available', 'mobile-events-manager' );
	}

	if ( ! empty( $employee_id ) ) {
		$addons = mem_get_addons_by_employee( $employee_id, false );
	} else {
		$addons = mem_get_addons();
	}

	if ( ! $addons ) {
		return __( 'No addons available', 'mobile-events-manager' );
	}

	$return = array();

	foreach ( $addons as $addon ) {
		$addon_price = '';

		if ( $price ) {
			$addon_price = ' ' . mem_currency_filter( mem_format_amount( mem_get_package_price( $addon->ID ) ) );
		}

		$return[] = get_the_title( $addon->ID ) . '' . $addon_price;
	}

	$return = apply_filters( 'mem_list_available_addons', $return, $price );

	return implode( '<br />', $return );

} // mem_list_available_addons

/**
 * Retrieve an addons excerpt.
 *
 * @since 1.4
 * @param int $addon_id The ID of the addon.
 * @param int $length The length of the excerpt.
 * @return str
 */
function mem_get_addon_excerpt( $addon_id, $length = null ) {

	if ( ! isset( $length ) ) {
		$length = mem_get_option( 'package_excerpt_length', 55 );
	}

	if ( has_excerpt( $addon_id ) ) {
		$description = get_post_field( 'post_excerpt', $addon_id );
	} else {
		$description = get_post_field( 'post_content', $addon_id );
	}

	if ( ! empty( $length ) ) {
		$description = wp_trim_words( $description, $length );
	}

	return apply_filters( 'mem_addon_excerpt', $description );

} // mem_get_addon_excerpt

/**
 * Renders the HTML code for Addons multiple select dropdown.
 *
 * @param arr  $args Settings for the dropdown. See @$defaults.
 * @param bool $structure True to echo the select field structure, false just returns options.
 * @return HTML output for select field
 */
function mem_addons_dropdown( $args = array(), $structure = true ) {
	global $current_user;

	$defaults = array(
		'name'            => 'event_addons',
		'id'              => '',
		'class'           => '',
		'selected'        => '',
		'first_entry'     => '',
		'first_entry_val' => '',
		'employee'        => ( is_user_logged_in() && ! current_user_can( 'client' ) ) ? $current_user->ID : false,
		'event_type'      => false,
		'event_date'      => false,
		'exclude_cats'    => null,
		'package'         => '',
		'cost'            => true,
		'titles'          => true,
	);

	$args = wp_parse_args( $args, $defaults );

	// For backwards compatibility.
	if ( isset( $args['dj'] ) ) {
		$args['employee'] = $args['dj'];
	}

	if ( empty( $args['id'] ) ) {
		$args['id'] = $args['name'];
	}

	$output = '';

	if ( true === $structure ) {
		$output .= '<select name="' . esc_attr( $args['name'] ) . '[]" id="' . esc_attr( $args['id'] ) . '"';
		$output .= ! empty( $args['class'] ) ? ' class="' . sanitize_html_class( $args['class'] ) . '"' : '';
		$output .= ' MULTIPLE>' . "\n";
	}

	$args = array_merge(
		$args,
		array(
			'show_option_none' => ! empty( $args['first_entry'] ) ? $args['first_entry'] : false,
			'show_option_all'  => false,
			'options_only'     => true,
		)
	);

	$output .= MEM()->html->addons_dropdown( $args );

	if ( true !== $structure ) {
		$output .= '</select>' . "\n";
	}

	return $output;

} // mem_addons_dropdown

/**
 * Renders the HTML code for an Addons checkbox list
 *
 * @since 1.0
 * @param arr $args See @defaultsSettings for the dropdown.
 * @return HTML output for checkboxes
 */
function mem_addons_checkboxes( $args = array() ) {
	global $current_user;

	$defaults = array(
		'name'       => 'event_addons',
		'id'         => '',
		'class'      => '',
		'current'    => array(),
		'employee'   => is_user_logged_in() && ! current_user_can( 'client' ) ? $current_user->ID : '',
		'package'    => '',
		'event_type' => false,
		'event_date' => false,
		'title'      => true,
		'cost'       => false,
	);

	$args    = wp_parse_args( $args, $defaults );
	$output  = '';
	$options = array();
	$addons  = mem_get_addons();

	if ( empty( $args['id'] ) ) {
		$args['id'] = $args['name'];
	}

	// For backwards compatibility.
	if ( isset( $args['dj'] ) ) {
		$args['employee'] = $args['dj'];
	}

	if ( $addons ) {
		foreach ( $addons as $addon ) {
			if ( ! empty( $args['package'] ) ) {
				if ( is_numeric( $args['package'] ) ) {
					$package = mem_get_package( $args['package'] );
				} else {
					$package = mem_get_package_by( 'slug', $args['package'] );
				}

				if ( $package ) {
					$package_items = mem_get_package_addons( $package->ID );
				}

				if ( ! empty( $package_items ) && in_array( $addon->ID, $package_items ) ) {
					continue;
				}
			}

			if ( ! empty( $args['employee'] ) ) {
				if ( ! mem_employee_has_addon( $addon->ID, $args['employee'] ) ) {
					continue;
				}
			}

			if ( $args['event_type'] ) {
				if ( ! mem_addon_is_available_for_event_type( $addon->ID, $args['event_type'] ) ) {
					continue;
				}
			}

			if ( $args['event_date'] ) {
				if ( ! mem_addon_is_available_for_event_date( $addon->ID, $args['event_date'] ) ) {
					continue;
				}
			} else {
				$args['event_date'] = null;
			}

			$price = '';
			if ( true === $args['cost'] ) {
				$price .= ' - ' . mem_currency_filter( mem_format_amount( mem_get_addon_price( $addon->ID, $args['event_date'] ) ) );
			}

			$term  = '';
			$terms = get_the_terms( $addon->ID, 'addon-category' );

			if ( ! empty( $terms ) ) {
				$term = esc_html( $terms[0]->name );
			}

			$options[ $term ][] = array( $addon->ID => $addon->post_title . $price );

		}
	}

	if ( ! empty( $options ) ) {
		ksort( $options );

		$i = 0;
		foreach ( $options as $term => $addons ) {
			if ( 0 === $i ) {
				$output .= '<strong>' . $term . '</strong><br />' . "\n";
			}

			foreach ( $addons as $items ) {
				foreach ( $items as $item_id => $item ) {
					$output .= '<label for="' . esc_attr( $args['name'] ) . '-' . $item_id . '" title="' . mem_get_addon_excerpt( $item_id ) . '">';
					$output .= sprintf(
						'<input type="checkbox" name="%1$s[]" id="%1$s-%2$d" class="%3$s" value="%2$d" %4$s />',
						esc_attr( $args['name'] ),
						$item_id,
						sanitize_html_class( $args['class'] ),
						checked( in_array( $item_id, $args['current'] ), true, false )
					);
					$output .= '&nbsp;';
					$output .= esc_html( $item );
					$output .= '</label>';
					$output .= '<br />';

					$i++;
					if ( $i >= count( $items ) ) {
						$i = 0;
					}
				}
			}
		}
	} else {
		$output .= __( 'No add-ons are available', 'mobile-events-manager' );
	}

	return $output;

} // mem_addons_checkboxes

/**
 * Set the addon category.
 *
 * @since 1.4
 * @param int $addon_id The addon ID.
 * @param int $term_id The category term ID.
 * @return bool True on success, otherwise false.
 */
function mem_set_addon_category( $addon_id, $term_id ) {
	$set_entry_type = wp_set_post_terms( $addon_id, $term_id, 'addon-category' );

	if ( is_wp_error( $set_entry_type ) ) {
		return false;
	}

	return true;
} // mem_set_addon_category

/**
 * Remove addons from packages.
 *
 * @since 1.4
 * @param int|arr $addon_ids ID (or array of IDs) of the addon(s) to remove.
 * @return void
 */
function mem_remove_addons_from_packages( $addon_ids ) {

	if ( ! is_array( $addon_ids ) ) {
		$addon_ids = array( $addon_ids );
	}

	$packages = mem_get_packages_with_addons( $addon_ids );

	if ( $packages ) {
		foreach ( $packages as $package ) {
			foreach ( $addon_ids as $addon_id ) {
				mem_remove_addon_from_package( $package->ID, $addon_id );
			}
		}
	}

} // mem_remove_addons_from_packages
add_action( 'mem_delete_addon', 'mem_remove_addons_from_packages', 10 );

/**
 * Removes an addon from a package.
 *
 * @since 1.4
 * @param int $package_id The package ID from which to remove the addon.
 * @param int $addon_id The ID of the addon to remove.
 */
function mem_remove_addon_from_package( $package_id, $addon_id ) {

	$addons = mem_get_package_addons( $package_id );
	$items  = array();

	if ( $addons ) {
		foreach ( $addons as $addon ) {
			if ( $addon_id != $addon ) {
				$items[] = $addon;
			}
		}
		update_post_meta( $package_id, '_package_items', $items );
	}

} // mem_remove_addon_from_package

/**
 * Remove addons from events.
 *
 * @since 1.4
 * @param int|arr $addon_ids ID (or array of IDs) of the addon(s) to remove.
 * @return void
 */
function mem_remove_addons_from_events( $addon_ids ) {

	if ( ! is_array( $addon_ids ) ) {
		$addon_ids = array( $addon_ids );
	}

	$events = mem_get_events_with_addons( $addon_ids );

	if ( $events ) {
		foreach ( $events as $event ) {
			foreach ( $addon_ids as $addon_id ) {
				mem_remove_addon_from_event( $event->ID, $addon_id );
			}
		}
	}

} // mem_remove_addons_from_events
add_action( 'mem_delete_addon', 'mem_remove_addons_from_events', 15 );

/**
 * Removes an addon from an event.
 *
 * @since 1.4
 * @param int $event_id The event ID from which to remove the addon.
 * @param int $addon_id The ID of the addon to remove.
 */
function mem_remove_addon_from_event( $event_id, $addon_id ) {

	$addons = mem_get_event_addons( $event_id );
	$items  = array();

	if ( $addons ) {
		foreach ( $addons as $addon ) {
			if ( $addon_id != $addon ) {
				$items[] = $addon;
			}
		}
		update_post_meta( $event_id, '_mem_event_addons', $items );
	}

} // mem_remove_addon_from_event
