<?php
/**
 * Contains all event related functions
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
 * Retrieve an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return obj $event The event WP_Post object
 */
function mem_get_event( $event_id ) {
	return mem_get_event_by_id( $event_id );
} // mem_get_event

/**
 * Retrieve an event by ID.
 *
 * @param int $event_id The WP post ID for the event.
 * @return mixed $event WP_Query object or false.
 */
function mem_get_event_by_id( $event_id ) {
	$event = new MEM_Event( $event_id );

	return ( ! empty( $event->ID ) ? $event : false );
} // mem_get_event_by_id

/**
 * Retrieve an event by date.
 *
 * @since 1.4
 * @param str $date The date to query (Y-m-d).
 * @return array $events Array of event WP_Query objects or false.
 */
function mem_get_events_by_date( $date ) {
	$args['meta_key']   = '_mem_event_date';
	$args['meta_value'] = $date;

	$events = mem_get_events( $args );

	if ( $events ) {
		return $events;
	}

	return false;
} // mem_get_events_by_date

/**
 * Retrieve the events.
 *
 * @since 1.3
 * @param arr $args Array of possible arguments. See $defaults.
 * @return mixed $events False if no events, otherwise an object array of all events.
 */
function mem_get_events( $args = array() ) {

	$defaults = array(
		'post_type'      => 'mem-event',
		'post_status'    => 'any',
		'posts_per_page' => -1,
	);

	$args = wp_parse_args( $args, $defaults );

	$events = get_posts( $args );

	// Return the results.
	if ( $events ) {
		return $events;
	} else {
		return false;
	}

} // mem_get_events

/**
 * Count Events
 *
 * Returns the total number of events.
 *
 * @since 1.4
 * @param arr $args List of arguments to base the event count on.
 * @return arr $count Number of events sorted by event date
 */
function mem_count_events( $args = array() ) {

	global $wpdb;

	$defaults = array(
		'status'     => null, // array_keys( mem_all_event_status() ),.
		'employee'   => null,
		'client'     => null,
		's'          => null,
		'start-date' => null, // This is the post date aka Enquiry received date.
		'end-date'   => null,
		'date'       => null, // This is an event date or an array of dates if we want to search between.
		'type'       => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$select = 'SELECT p.post_status,count( * ) AS num_posts';
	$join   = '';
	$where  = "WHERE p.post_type = 'mem-event'";

	// Count events with a specific status or statuses.
	if ( ! empty( $args['status'] ) ) {
		if ( is_array( $args['status'] ) ) {
			$clause = 'IN ( ' . implode( ', ', $args['status'] ) . ' )';
		} else {
			$clause = "= '{$args['status']}'";
		}

		$where .= ' AND p.post_status ' . $clause;
	}

	// Count events for a specific employee.
	if ( ! empty( $args['employee'] ) ) {

		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";

		$where .= "
		 AND m.meta_key = '_mem_event_dj'
 AND m.meta_value = '{$args['employee']}'
 OR m.meta_key = '_mem_event_employees'
 AND m.meta_value LIKE '%:\"{$args['employee']}\";%'
 ";

		// Count events for a specific client.
	} elseif ( ! empty( $args['client'] ) ) {

		$join   = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
		$where .= "
		 AND m.meta_key = '_mem_event_client'
 AND m.meta_value = '{$args['client']}'
 ";

		// Count event for a search.
	} elseif ( ! empty( $args['s'] ) ) {

		if ( is_email( $args['s'] ) || strlen( $args['s'] ) === 32 ) {

			if ( is_email( $args['s'] ) ) {
				$field = '_mem_event_client';
			}

			$join   = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= $wpdb->prepare(
				'
				AND m.meta_key = %s
				AND m.meta_value = %s',
				$field,
				$args['s']
			);

		} elseif ( is_numeric( $args['s'] ) ) {

			$join   = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= $wpdb->prepare(
				"
				AND m.meta_key = '_mem_event_client'
				AND m.meta_value = %d",
				$args['s']
			);

		} else {
			$search = $wpdb->esc_like( $args['s'] );
			$search = '%' . $search . '%';

			$where .= $wpdb->prepare( 'AND ((p.post_title LIKE %s) OR (p.post_content LIKE %s))', $search, $search );
		}
	}

	// Limit event count by received date.
	if ( ! empty( $args['start-date'] ) && false !== strpos( $args['start-date'], '-' ) ) {

		$date_parts = explode( '-', $args['start-date'] );
		$year       = ! empty( $date_parts[0] ) && is_numeric( $date_parts[0] ) ? $date_parts[0] : 0;
		$month      = ! empty( $date_parts[1] ) && is_numeric( $date_parts[1] ) ? $date_parts[1] : 0;
		$day        = ! empty( $date_parts[2] ) && is_numeric( $date_parts[2] ) ? $date_parts[2] : 0;

		$is_date = checkdate( $month, $day, $year );
		if ( false !== $is_date ) {

			$date   = new DateTime( $args['start-date'] );
			$where .= $wpdb->prepare( " AND p.post_date >= '%s'", $date->format( 'Y-m-d' ) );

		}

		// Fixes an issue with the events list table counts when no end date is specified (partly with stats class).
		if ( empty( $args['end-date'] ) ) {
			$args['end-date'] = $args['start-date'];
		}
	}

	if ( ! empty( $args['end-date'] ) && false !== strpos( $args['end-date'], '-' ) ) {

		$date_parts = explode( '-', $args['end-date'] );
		$year       = ! empty( $date_parts[0] ) && is_numeric( $date_parts[0] ) ? $date_parts[0] : 0;
		$month      = ! empty( $date_parts[1] ) && is_numeric( $date_parts[1] ) ? $date_parts[1] : 0;
		$day        = ! empty( $date_parts[2] ) && is_numeric( $date_parts[2] ) ? $date_parts[2] : 0;

		$is_date = checkdate( $month, $day, $year );
		if ( false !== $is_date ) {

			$date   = new DateTime( $args['end-date'] );
			$where .= $wpdb->prepare( " AND p.post_date <= '%s'", $date->format( 'Y-m-d' ) );

		}
	}

	if ( ! empty( $args['date'] ) ) {

		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";

		if ( is_array( $args['date'] ) ) {
			$start_date = new DateTime( $args['date'][0] );
			$end_date   = new DateTime( $args['date'][1] );

			$where .= "
				AND m.meta_key = '_mem_event_date'
				AND STR_TO_DATE(m.meta_value, '%Y-%m-%d' )
					BETWEEN '" . $start_date->format( 'Y-m-d' ) . "'
					AND '" . $end_date->format( 'Y-m-d' ) . "'";

		} else {

			$date   = new DateTime( $args['date'] );
			$where .= $wpdb->prepare(
				"
				AND m.meta_key = '_mem_event_date'
				AND m.meta_value = '%s'",
				$date->format( 'Y-m-d' )
			);

		}
	}

	$where = apply_filters( 'mem_count_events_where', $where );
	$join  = apply_filters( 'mem_count_events_where', $join );

	$query = "$select
		FROM $wpdb->posts p
		$join
		$where
		GROUP BY p.post_status
	";

	$cache_key = md5( $query );

	$count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $count ) {
		return $count;
	}

	$count    = $wpdb->get_results( $query, ARRAY_A );
	$stats    = array();
	$total    = 0;
	$statuses = mem_all_event_status();

	foreach ( array_keys( $statuses ) as $state ) {
		$stats[ $state ] = 0;
	}

	foreach ( (array) $count as $row ) {
		if ( ! array_key_exists( $row['post_status'], mem_all_event_status() ) ) {
			continue;
		}
		$stats[ $row['post_status'] ] = $row['num_posts'];
	}

	$stats = (object) $stats;
	wp_cache_set( $cache_key, $stats, 'counts' );

	return $stats;
} // mem_count_events

/**
 * Retrieve the total event count.
 *
 * @since 1.4
 * @param string|arrary $status Post statuses.
 * @param array         $args Array of arguments to pass WP_Query.
 * @return int Event count
 */
function mem_event_count( $status = 'any', $args = array() ) {
	$defaults = array(
		'post_type'      => 'mem-event',
		'post_status'    => $status,
		'posts_per_page' => -1,
	);

	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'mem_event_count_args', $args );

	$events = new WP_Query( $args );

	return $events->found_posts;
} // mem_event_count

/**
 * Retrieve the event data.
 *
 * @since 1.4
 * @param int|obj $event An event ID, or an MEM_Event object.
 * @return arr Event meta.
 */
function mem_get_event_data( $event ) {

	if ( is_numeric( $event ) ) {
		$mem_event = new MEM_Event( $event );
	} else {
		$mem_event = $event;
	}

	$contract_status = $mem_event->get_contract_status();
	$source          = mem_get_enquiry_source( $mem_event->ID );

	$event_data = array(
		'client'          => $mem_event->client,
		'contract'        => $mem_event->get_contract(),
		'contract_status' => $contract_status ? __( 'Signed', 'mobile-events-manager' ) : __( 'Unsigned', 'mobile-events-manager' ),
		'cost'            => array(
			'balance'           => $mem_event->get_balance(),
			'balance_status'    => $mem_event->get_balance_status(),
			'deposit'           => $mem_event->deposit,
			'deposit_status'    => $mem_event->get_deposit_status(),
			'remaining_deposit' => $mem_event->get_remaining_deposit(),
			'cost'              => $mem_event->price,
		),
		'date'            => $mem_event->date,
		'duration'        => mem_event_duration( $mem_event->ID ),
		'employees'       => array(
			'employees'        => $mem_event->get_all_employees(),
			'primary_employee' => $mem_event->employee_id,
		),
		'end_date'        => $mem_event->get_meta( '_mem_event_end_date' ),
		'end_time'        => $mem_event->get_finish_time(),
		'equipment'       => array(
			'package' => mem_get_package_name( mem_get_event_package( $mem_event->ID ) ),
			'addons'  => mem_get_event_addons( $mem_event->ID ),
		),
		'name'            => $mem_event->get_name(),
		'playlist'        => array(
			'playlist_enabled'    => $mem_event->playlist_is_enabled(),
			'playlist_guest_code' => $mem_event->get_playlist_code(),
			'playlist_status'     => $mem_event->playlist_is_open(),
			'playlist_limit'      => $mem_event->get_playlist_limit(),
		),
		'setup_date'      => $mem_event->get_setup_date(),
		'setup_time'      => $mem_event->get_setup_time(),
		'source'          => ! empty( $source ) ? $source->name : '',
		'status'          => $mem_event->get_status(),
		'start_time'      => $mem_event->get_start_time(),
		'type'            => $mem_event->get_type(),
		'venue'           => array(
			'id'      => $mem_event->get_meta( '_mem_event_venue_id' ),
			'name'    => mem_get_event_venue_meta( $mem_event->ID, 'name' ),
			'address' => mem_get_event_venue_meta( $mem_event->ID, 'address' ),
			'contact' => mem_get_event_venue_meta( $mem_event->ID, 'contact' ),
			'details' => mem_get_venue_details( $mem_event->get_venue_id() ),
			'email'   => mem_get_event_venue_meta( $mem_event->ID, 'email' ),
			'phone'   => mem_get_event_venue_meta( $mem_event->ID, 'phone' ),
			'notes'   => mem_get_event_venue_meta( $mem_event->ID, 'notes' ),
		),
	);

	$employees = $mem_event->get_all_employees();

	if ( ! empty( $employees ) ) {
		$event_data['employees']['employees'] = $employees;
	}

	$event_data = apply_filters( 'mem_get_event_data', $event_data, $mem_event->ID );

	return $event_data;

} // mem_get_event_data

/**
 * Whether or not the event is currently active.
 *
 * @since 1.3
 * @param int $event_id Event ID.
 * @return bool True if active, false if not.
 */
function mem_event_is_active( $event_id = '' ) {

	$event_statuses   = mem_active_event_statuses();
	$event_statuses[] = 'mem-unattended';
	$event_statuses[] = 'auto-draft';
	$event_statuses[] = 'draft';

	return in_array( get_post_status( $event_id ), $event_statuses );
} // mem_event_is_active

/**
 * Retrieve the next event.
 * If the current user is not an MEM admin, only list their own event.
 *
 * @since 1.3
 * @param int $employee_id User ID of employee. Leave empty to check for all employees.
 * @return obj Events WP_Post object.
 */
function mem_get_next_event( $employee_id = '' ) {

	if ( ! empty( $employee_id ) && ! mem_employee_can( 'manage_all_events' ) && get_current_user_id() !== $employee_id ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?', 'mobile-events-manager' ) . '</h1>' .
			'<p>' . sprintf( esc_html__( 'Your %1$s permissions do not permit you to search all %2$s!', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ), mem_get_label_plural( true ) ) . '</p>',
			403
		);
	}

	if ( ! empty( $employee_id ) || ! mem_employee_can( 'manage_all_events' ) ) {

		$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();
		$event       = mem_get_employees_next_event( $employee_id );

	} else {

		$args = array(
			'post_status'    => mem_active_event_statuses(),
			'posts_per_page' => 1,
			'meta_key'       => '_mem_event_date',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		$event = mem_get_events( $args );

		if ( ! empty( $event ) ) {
			$event = $event[0];
		}
	}

	if ( empty( $event ) ) {
		return false;
	}

	return $event;

} // mem_get_next_event

/**
 * Retrieve today's events.
 *
 * @since 1.3
 * @param int $employee_id User ID of employee. Leave empty to check for all employees.
 * @return obj Events WP_Post object.
 */
function mem_get_todays_events( $employee_id = '' ) {

	$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();

	$args = array(
		'post_status'    => mem_active_event_statuses(),
		'posts_per_page' => 1,
		'meta_key'       => '_mem_event_date',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	);

	$event = mem_get_employee_events( $employee_id, $args );

	if ( empty( $event ) ) {
		return false;
	}

	return $event[0];

} // mem_get_todays_events

/**
 * Retrieve an event by the guest playlist code.
 *
 * @since 1.3
 * @param int $access_code The access code for the event playlist.
 * @return obj $event_query WP_Query object.
 */
function mem_get_event_by_playlist_code( $access_code ) {
	global $wpdb;

	$query = "
 SELECT `post_id`
 AS `event_id`
 FROM `$wpdb->postmeta`
 WHERE `meta_value` = '$access_code'
 LIMIT 1
 ";

	$result = $wpdb->get_row( $query );

	return ( $result ? mem_get_event( $result->event_id ) : false );
} // mem_get_event_by_playlist_code

/**
 * Retrieve events by status.
 *
 * @since 1.3
 * @param str $status The event status.
 * @return obj|bool The WP_Query results object.
 */
function mem_get_events_by_status( $status ) {

	$events = mem_get_events( array( 'post_status' => $status ) );

	if ( ! $events ) {
		return false;
	}

	return $events;

} // mem_get_events_by_status

/**
 * Retrieve a count of events by status.
 *
 * @since 1.3
 * @param str $status The event status.
 * @return int The number of events with the status.
 */
function mem_count_events_by_status( $status ) {

	$count  = 0;
	$events = mem_get_events_by_status( $status );

	if ( $events ) {
		$count = count( $events );
	}

	return $count;

} // mem_count_events_by_status

/**
 * Determine if the event exists.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return obj|bool The WP_Post object for the event if it exists, otherwise false.
 */
function mem_event_exists( $event_id ) {
	return mem_get_event_by_id( $event_id );
} // mem_event_exists

/**
 * Returns an array of event post status keys.
 *
 * @since 1.4.6
 * @param
 * @return arr Array of event status keys
 */
function mem_all_event_status_keys() {
	$post_status = array(
		'mem-unattended',
		'mem-enquiry',
		'mem-awaitingdeposit',
		'mem-approved',
		'mem-contract',
		'mem-completed',
		'mem-cancelled',
		'mem-rejected',
		'mem-failed',
	);

	return apply_filters( 'mem_all_event_status', $post_status );
} // mem_all_event_status_keys

/**
 * Returns an array of event post status.
 *
 * @since 1.3
 * @param
 * @return arr Array of event status'. Key = post_status value = MEM Event status
 */
function mem_all_event_status() {
	$post_status = mem_all_event_status_keys();

	foreach ( $post_status as $status ) {
		$mem_status[ $status ] = get_post_status_object( $status )->label;
	}

	// Sort alphabetically.
	asort( $mem_status );

	return $mem_status;
} // mem_all_event_status

/**
 * Returns an array of active event post statuses.
 *
 * @since 1.3
 * @param
 * @return arr Array of active event status'.
 */
function mem_active_event_statuses() {
	$statuses = mem_all_event_status_keys();
	$inactive = mem_inactive_event_status_keys();

	foreach ( $inactive as $status ) {
		if ( in_array( $status, $statuses ) ) {
			unset( $statuses[ $status ] );
		}
	}

	// Sort alphabetically.
	asort( $statuses );

	return $statuses;
} // mem_active_event_statuses

/**
 * Returns an array of inactive event post status keys.
 *
 * @since 1.4.6
 * @param
 * @return arr Array of event status keys
 */
function mem_inactive_event_status_keys() {
	$post_status = array(
		'mem-completed',
		'mem-cancelled',
		'mem-rejected',
		'mem-failed',
	);

	return apply_filters( 'mem_inactive_event_status', $post_status );
} // mem_inactive_event_status_keys

/**
 * Return the event status label for given event ID.
 *
 * @since 1.3
 * @param int $event_id Optional: ID of the current event. If not set, check for global $post and $post_id.
 * @return str Label for current event status.
 */
function mem_get_event_status( $event_id = '' ) {
	global $post, $post_id;

	if ( ! empty( $event_id ) ) {
		$id = $event_id;
	} elseif ( ! empty( $post_id ) ) {
		$id = $post_id;
	} elseif ( ! empty( $post ) ) {
		$id = $post->ID;
	} else {
		$id = '';
	}

	$event = new MEM_Event( $id );

	// Return the label for the status.
	return $event->get_status();
} // mem_get_event_status

/**
 * Returns the event name.
 *
 * @since 1.4.7.3
 * @param int $event_id ID of the event.
 * @return str Name for current event.
 */
function mem_get_event_name( $event_id = 0 ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$name = get_post_meta( $event_id, '_mem_event_name', true );

	/**
	 * Override the event name.
	 *
	 * @since 1.3
	 *
	 * @param str $name The event name.
	 */
	return apply_filters( 'mem_event_name', $name, $event_id );
} // mem_get_event_name

/**
 * Return a select list of possible event statuses
 *
 * @since 1.1.3
 * @param arr $args array of options. See $defaults.
 * @return str HTML for the select list
 */
function mem_event_status_dropdown( $args = '' ) {
	global $post;

	$defaults = array(
		'name'              => 'mem_event_status',
		'id'                => 'mem_event_status',
		'selected'          => ! empty( $post ) ? $post->post_status : 'mem-unattended',
		'first_entry'       => '',
		'first_entry_value' => '0',
		'small'             => false,
		'return_type'       => 'list',
	);

	$args = wp_parse_args( $args, $defaults );

	$event_status = mem_all_event_status();

	if ( empty( $event_status ) ) {
		return false;
	}

	if ( ! empty( $post->ID ) && array_key_exists( $post->post_status, $event_status ) ) {
		$current_status = $post->post_status;
	}

	$output  = '<select name="' . $args['name'] . '" id="' . $args['id'] . '"';
	$output .= ( ! empty( $args['small'] ) ? ' style="font-size: 11px;"' : '' );
	$output .= '>' . "\r\n";

	if ( ! empty( $first_entry ) ) {
		$output .= '<option value="' . $args['first_entry_value'] . '">' . $args['first_entry'] . '</option>' . "\r\n";
	}

	foreach ( $event_status as $slug => $label ) {
		$output .= '<option value="' . $slug . '"';
		$output .= $args['selected'] === $slug ? ' selected="selected"' : '';
		$output .= '>' . $label . '</option>' . "\r\n";
	}

	$output .= '</select>' . "\r\n";

	if ( 'list' === $args['return_type'] ) {
		echo $output;
	}

	return $output;
} // mem_event_status_dropdown

/**
 * Retrieve the enquiry source for the event.
 *
 * @since 1.3
 * @param int $event_id Event ID.
 * @return obj|bool The enquiry source for the event, or false if not set
 */
function mem_get_enquiry_source( $event_id ) {

	$enquiry_source = wp_get_object_terms( $event_id, 'enquiry-source' );
	$return         = (bool) false;

	if ( isset( $enquiry_source[0]->term_id ) ) {
		$return = $enquiry_source[0];
	}

	return $return;

} // mem_get_enquiry_source

/**
 * Return all enquiry sources.
 *
 * @since 1.3
 * @param arr $args See $defaults.
 * @return obj Object array of all enqury source categories.
 */
function mem_get_enquiry_sources( $args = array() ) {

	$defaults = array(
		'taxonomy'   => 'enquiry-source',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);

	$args = wp_parse_args( $args, $defaults );

	$enquiry_sources = get_categories( $args );

	return apply_filters( 'mem_get_enquiry_sources', $enquiry_sources, $args );

} // mem_get_enquiry_sources

/**
 * Set the enquiry source for the event.
 *
 * @since 1.3
 * @param int     $event_id Event ID.
 * @param int|arr $type The term ID of the category to set for the event.
 * @return bool True on success, or false.
 */
function mem_set_enquiry_source( $event_id, $type = '' ) {

	if ( empty( $type ) && mem_get_option( 'enquiry_source_default' ) ) {
		$type = mem_get_option( 'enquiry_source_default' );
	}

	if ( ! is_array( $type ) ) {
		$type = array( $type );
	}

	$type = array_map( 'intval', $type );
	$type = array_unique( $type );

	(int) $event_id;

	$set_enquiry_source = wp_set_object_terms( $event_id, $type, 'enquiry-source', false );

	if ( is_wp_error( $set_enquiry_source ) ) {
		MEM()->debug->log_it( sprintf( 'Unable to assign term ID %d to Event %d: %s', $type, $event_id, $set_enquiry_source->get_error_message() ), true );
	}

} // mem_set_enquiry_source

/**
 * Generate a dropdown list of enquiry sources.
 *
 * @since 1.3
 * @param arr $args See $defaults.
 * @return str HTML output for the dropdown list.
 */
function mem_enquiry_sources_dropdown( $args ) {

	$defaults = array(
		'show_option_none'  => '',
		'option_none_value' => '',
		'orderby'           => 'name',
		'order'             => 'ASC',
		'hide_empty'        => false,
		'echo'              => true,
		'selected'          => 0,
		'name'              => 'mem_enquiry_source',
		'id'                => '',
		'class'             => 'postform',
		'taxonomy'          => 'event-types',
		'required'          => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$args['id']       = ! empty( $args['id'] ) ? $args['id'] : $args['name'];
	$args['required'] = ! empty( $args['required'] ) ? ' required' : '';
	$args['class']    = ! empty( $args['class'] ) ? $args['class'] : '';

	$enquiry_sources = mem_get_enquiry_sources();

	$output = sprintf( '<select name="%s" id="%s" class="%s"%s>', $args['name'], $args['id'], $args['class'], $args['required'] );

	if ( ! empty( $args['show_option_none'] ) ) {
		$output .= sprintf( '<option value="%s">%s</option>', $args['option_none_value'], $args['show_option_none'] );
	}

	if ( empty( $enquiry_sources ) ) {
		$output .= sprintf( '<option value="" disabled="disabled">%s</option>', apply_filters( 'mem_no_enquiry_source_options', __( 'No sources found', 'mobile-events-manager' ) ) );
	} else {

		foreach ( $enquiry_sources as $enquiry_source ) {
			$selected = selected( $enquiry_source->term_id, $args['selected'], false );

			$output .= sprintf( '<option value="%s"%s>%s</option>', $enquiry_source->term_id, $selected, esc_attr( $enquiry_source->name ) ) . "\n";

		}
	}

	$output .= '</select>';

	if ( ! empty( $args['echo'] ) ) {
		echo $output;
	} else {
		return $output;
	}

} // mem_enquiry_sources_dropdown

/**
 * Return all event types.
 *
 * @since 1.3
 * @param arr $args See $defaults.
 * @return obj Object array of all event type categories.
 */
function mem_get_event_types( $args = array() ) {

	$defaults = array(
		'taxonomy'   => 'event-types',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	);

	$args = wp_parse_args( $args, $defaults );

	$event_types = get_categories( $args );

	return apply_filters( 'mem_get_event_types', $event_types, $args );

} // mem_get_event_types

/**
 * Generate a dropdown list of event types.
 *
 * @since 1.3
 * @param arr $args See $defaults.
 * @return str HTML output for the dropdown list.
 */
function mem_event_types_dropdown( $args ) {

	$defaults = array(
		'show_option_none'  => '',
		'option_none_value' => '',
		'orderby'           => 'name',
		'order'             => 'ASC',
		'hide_empty'        => false,
		'echo'              => true,
		'selected'          => 0,
		'name'              => 'mem_event_type',
		'id'                => '',
		'class'             => 'postform',
		'taxonomy'          => 'event-types',
		'required'          => false,
	);

	$args = wp_parse_args( $args, $defaults );

	$args['id']       = ! empty( $args['id'] ) ? $args['id'] : $args['name'];
	$args['required'] = ! empty( $args['required'] ) ? ' required' : '';
	$args['class']    = ! empty( $args['class'] ) ? $args['class'] : '';

	$types = mem_get_event_types();

	$output = sprintf( '<select name="%s" id="%s" class="%s"%s>', $args['name'], $args['id'], $args['class'], $args['required'] );

	if ( ! empty( $args['show_option_none'] ) ) {
		$output .= sprintf( '<option value="%s">%s</option>', $args['option_none_value'], $args['show_option_none'] );
	}

	if ( empty( $types ) ) {
		$output .= sprintf( '<option value="" disabled="disabled">%s</option>', apply_filters( 'mem_no_event_type_options', __( 'No options found', 'mobile-events-manager' ) ) );
	} else {

		foreach ( $types as $type ) {
			$selected = selected( $type->term_id, $args['selected'], false );

			$output .= sprintf( '<option value="%s"%s>%s</option>', $type->term_id, $selected, esc_attr( $type->name ) ) . "\n";

		}
	}

	$output .= '</select>';

	if ( ! empty( $args['echo'] ) ) {
		echo $output;
	} else {
		return $output;
	}

} // mem_event_types_dropdown

/**
 * Set the event type for the event.
 *
 * @since 1.3
 * @param int     $event_id Event ID.
 * @param int|arr $type The term ID of the category to set for the event.
 * @return bool True on success, or false.
 */
function mem_set_event_type( $event_id, $type = '' ) {

	if ( empty( $type ) && mem_get_option( 'event_type_default' ) ) {
		$type = mem_get_option( 'event_type_default' );
	}

	if ( ! is_array( $type ) ) {
		$type = array( $type );
	}

	$type = array_map( 'intval', $type );
	$type = array_unique( $type );

	(int) $event_id;

	$set_event_terms = wp_set_object_terms( $event_id, $type, 'event-types', false );

	if ( is_wp_error( $set_event_terms ) ) {
		MEM()->debug->log_it( sprintf( 'Unable to assign term ID %d to Event %d: %s', $type, $event_id, $set_event_terms->get_error_message() ), true );
	}

} // mem_set_event_type

/**
 * Return the event type label for given event ID.
 *
 * @since 1.3
 * @param int  $event_id ID of the current event. If not set, check for global $post and $post_id.
 * @param bool $raw True to return the raw slug of the event type, false for the label.
 * @return str Label for current event type.
 */
function mem_get_event_type( $event_id = '', $raw = false ) {

	global $post, $post_id;

	if ( ! empty( $event_id ) ) {
		$id = $event_id;
	} elseif ( ! empty( $post_id ) ) {
		$id = $post_id;
	} elseif ( ! empty( $post ) ) {
		$id = $post->ID;
	} else {
		$id = '';
	}

	if ( $raw ) {
		return mem_get_event_type_raw( $id );
	}

	$event = new MEM_Event( $id );

	// Return the label for the status.
	return $event->get_type();

} // mem_get_event_type

/**
 * Return the event type slug for given event ID.
 *
 * @since 1.3
 * @param int $event_id ID of the current event. If not set, check for global $post and $post_id.
 * @return str Slug for current event type.
 */
function mem_get_event_type_raw( $event_id ) {
	$event_type = wp_get_object_terms( $event_id, 'event-types' );

	if ( $event_type ) {
		return absint( $event_type[0]->term_id );
	}

	return false;
} // mem_get_event_type_raw

/**
 * Returns the contract ID for the event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The contract ID for the event.
 */
function mem_get_event_contract_id( $event_id ) {

	if ( empty( $event_id ) ) {
		return false;
	}

	return esc_html( mem_get_option( 'event_prefix', '' ) . $event_id );

} // mem_get_event_contract_id

/**
 * Returns the date for an event in short format.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The date of the event.
 */
function mem_get_event_date( $event_id ) {

	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );

	return esc_html( mem_format_short_date( $event->get_date() ) );

} // mem_get_event_date

/**
 * Returns the date for an event in long format.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The date of the event.
 */
function mem_get_event_long_date( $event_id ) {

	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );

	return esc_html( $event->get_long_date() );

} // mem_get_event_long_date

/**
 * Returns the start time for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The date of the event.
 */
function mem_get_event_start( $event_id ) {

	$time = get_post_meta( $event_id, '_mem_event_start', true );

	return esc_html( gmdate( mem_get_option( 'time_format' ), strtotime( $time ) ) );

} // mem_get_event_start

/**
 * Returns the price for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int|str The price of the event.
 */
function mem_get_event_price( $event_id = 0 ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return esc_html( $event->get_price() );
} // mem_get_event_price

/**
 * Returns the deposit type.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int|str The deposit type.
 */
function mem_get_event_deposit_type() {
	return mem_get_option( 'deposit_type', 'fixed' );
} // mem_get_event_deposit_type

/**
 * Returns the deposit price for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int|str The deposit price of the event.
 */
function mem_get_event_deposit( $event_id = 0 ) {
	if ( empty( $event_id ) ) {

		return false;
	}

	$event = new MEM_Event( $event_id );
	return esc_html( $event->get_deposit() );
} // mem_get_event_deposit

/**
 * Returns the deposit status for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The deposit status of the event.
 */
function mem_get_event_deposit_status( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return esc_html( $event->get_deposit_status() );
} // mem_get_event_deposit_status

/**
 * Returns the remaining deposit due for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The remaining deposit value due for the event.
 */
function mem_get_event_remaining_deposit( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return mem_sanitize_amount( $event->get_remaining_deposit() );
} // mem_get_event_remaining_deposit

/**
 * Determine the event deposit value based upon event cost and
 * payment settings
 *
 * @param int|str $price Current price of event.
 */
function mem_calculate_deposit( $price = '' ) {

	$deposit_type = mem_get_event_deposit_type();

	if ( empty( $price ) && 'fixed' !== $deposit_type ) {
		$deposit = 0;
	}

	if ( empty( $deposit_type ) ) {
		$deposit = '0';
	} elseif ( 'fixed' === $deposit_type ) {
		$deposit = mem_get_option( 'deposit_amount' );
	} elseif ( 'percentage' === $deposit_type ) {
		$percentage = mem_get_option( 'deposit_amount' );

		$deposit = ( ! empty( $price ) && $price > 0 ? round( $percentage * ( $price / 100 ), 2 ) : 0 );
	}

	apply_filters( 'mem_calculate_deposit', $deposit, $price );

	return mem_sanitize_amount( $deposit );

} // mem_calculate_deposit

/**
 * Whether or not a deposit it required before an event can be confirmed.
 *
 * @since 1.5
 * @return bool
 */
function mem_require_deposit_before_confirming() {
	$require = mem_get_option( 'deposit_before_confirm' );
	$require = (bool) apply_filters( 'mem_require_deposit_before_confirming', $require );

	return $require;
} // mem_require_deposit_before_confirming

/**
 * Mark the event deposit as paid.
 *
 * Determines if any deposit remains and if so, assumes it has been paid and
 * creates an associted transaction.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return void
 */
function mem_mark_event_deposit_paid( $event_id ) {

	$mem_event = new MEM_Event( $event_id );
	$txn_id     = 0;

	if ( 'Paid' === $mem_event->get_deposit_status() ) {
		return;
	}

	$remaining = $mem_event->get_remaining_deposit();

	do_action( 'mem_pre_mark_event_deposit_paid', $event_id, $remaining );

	if ( ! empty( $remaining ) && $remaining > 0 ) {
		$mem_txn = new MEM_Txn();

		$txn_meta = array(
			'_mem_txn_source'      => mem_get_option( 'default_type', __( 'Cash', 'mobile-events-manager' ) ),
			'_mem_txn_currency'    => mem_get_currency(),
			'_mem_txn_status'      => 'Completed',
			'_mem_txn_total'       => $remaining,
			'_mem_payer_firstname' => mem_get_client_firstname( $mem_event->client ),
			'_mem_payer_lastname'  => mem_get_client_lastname( $mem_event->client ),
			'_mem_payer_email'     => mem_get_client_email( $mem_event->client ),
			'_mem_payment_from'    => mem_get_client_display_name( $mem_event->client ),
		);

		$mem_txn->create( array( 'post_parent' => $event_id ), $txn_meta );

		if ( $mem_txn->ID > 0 ) {

			mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'slug', 'mem-deposit-payments' ) );

			$args = array(
				'user_id'         => get_current_user_id(),
				'event_id'        => $event_id,
				'comment_content' => sprintf(
					__( '%1$s payment of %2$s received and %1$s marked as paid.', 'mobile-events-manager' ),
					mem_get_deposit_label(),
					mem_currency_filter( mem_format_amount( $remaining ) )
				),
			);

			mem_add_journal( $args );

			mem_add_content_tag( 'payment_for', __( 'Reason for payment', 'mobile-events-manager' ), 'mem_content_tag_deposit_label' );
			mem_add_content_tag(
				'payment_amount',
				__( 'Payment amount', 'mobile-events-manager' ),
				function() use ( $remaining ) {
					return mem_currency_filter( mem_format_amount( $remaining ) );
				}
			);
			mem_add_content_tag( 'payment_date', __( 'Date of payment', 'mobile-events-manager' ), 'mem_content_tag_ddmmyyyy' );

			do_action( 'mem_post_add_manual_txn_in', $event_id, $mem_txn->ID );

		}
	}

	// if we've been waiting for the deposit & the contract is signed, mark the event status as confirmed.
	if ( mem_require_deposit_before_confirming() && $mem_event->get_contract_status() ) {
		mem_update_event_status(
			$mem_event->ID,
			'mem-approved',
			$mem_event->post_status,
			array( 'client_notices' => mem_get_option( 'booking_conf_to_client' ) )
		);
	}

	mem_update_event_meta( $mem_event->ID, array( '_mem_event_deposit_status' => 'Paid' ) );

	do_action( 'mem_post_mark_event_deposit_paid', $event_id );

} // mem_mark_event_deposit_paid

/**
 * Mark the event balance as paid.
 *
 * Determines if any balance remains and if so, assumes it has been paid and
 * creates an associted transaction.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return void
 */
function mem_mark_event_balance_paid( $event_id ) {

	$mem_event = new MEM_Event( $event_id );
	$txn_id     = 0;

	if ( 'Paid' === $mem_event->get_balance_status() ) {
		return;
	}

	$remaining = $mem_event->get_balance();

	do_action( 'mem_pre_mark_event_balance_paid', $event_id, $remaining );

	if ( ! empty( $remaining ) && $remaining > 0 ) {
		$mem_txn = new MEM_Txn();

		$txn_meta = array(
			'_mem_txn_source'      => mem_get_option( 'default_type', __( 'Cash', 'mobile-events-manager' ) ),
			'_mem_txn_currency'    => mem_get_currency(),
			'_mem_txn_status'      => 'Completed',
			'_mem_txn_total'       => $remaining,
			'_mem_payer_firstname' => mem_get_client_firstname( $mem_event->client ),
			'_mem_payer_lastname'  => mem_get_client_lastname( $mem_event->client ),
			'_mem_payer_email'     => mem_get_client_email( $mem_event->client ),
			'_mem_payment_from'    => mem_get_client_display_name( $mem_event->client ),
		);

		$mem_txn->create( array( 'post_parent' => $event_id ), $txn_meta );

		if ( $mem_txn->ID > 0 ) {

			mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'slug', 'mem-balance-payments' ) );

			$args = array(
				'user_id'         => get_current_user_id(),
				'event_id'        => $event_id,
				'comment_content' => sprintf(
					__( '%1$s payment of %2$s received and %1$s marked as paid.', 'mobile-events-manager' ),
					mem_get_balance_label(),
					mem_currency_filter( mem_format_amount( $remaining ) )
				),
			);

			mem_add_journal( $args );

			mem_add_content_tag( 'payment_for', __( 'Reason for payment', 'mobile-events-manager' ), 'mem_content_tag_balance_label' );
			mem_add_content_tag(
				'payment_amount',
				__( 'Payment amount', 'mobile-events-manager' ),
				function() use ( $remaining ) {
					return mem_currency_filter( mem_format_amount( $remaining ) );
				}
			);
			mem_add_content_tag( 'payment_date', __( 'Date of payment', 'mobile-events-manager' ), 'mem_content_tag_ddmmyyyy' );

			do_action( 'mem_post_add_manual_txn_in', $event_id, $mem_txn->ID );

		}
	}

	mem_update_event_meta(
		$mem_event->ID,
		array(
			'_mem_event_deposit_status' => 'Paid',
			'_mem_event_balance_status' => 'Paid',
		)
	);

	do_action( 'mem_post_mark_event_balance_paid', $event_id );

} // mem_mark_event_balance_paid

/**
 * Returns the balance status for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The balance status of the event.
 */
function mem_get_event_balance_status( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return $event->get_balance_status();
} // mem_get_event_balance_status

/**
 * Returns the balance owed for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int|str The balance owed for the event.
 */
function mem_get_event_balance( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return mem_sanitize_amount( $event->get_balance() );
} // mem_get_event_balance

/**
 * Returns the total income for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int|str The income for the event.
 */
function mem_get_event_income( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return mem_sanitize_amount( $event->get_total_income() );
} // mem_get_event_income

/**
 * Retrieve event transactions.
 *
 * @since 1.3.8
 * @param int $event_id Event ID.
 * @param arr $args @see get_posts.
 * @return obj Array of event transactions.
 */
function mem_get_event_txns( $event_id, $args = array() ) {

	$defaults = array(
		'post_parent' => $event_id,
		'post_status' => 'any',
		'meta_key'    => '_mem_txn_status',
		'meta_query'  => array(
			'key'     => '_mem_txn_status',
			'value'   => 'Completed',
			'compare' => '=',
		),
	);

	$args = wp_parse_args( $args, $defaults );

	return mem_get_txns( $args );

} // mem_get_event_txns

/**
 * Generate a list of event transactions.
 *
 * @since 1.3.8
 * @param int $event_id Event ID.
 * @return arr $event_txns Array of event transactions.
 */
function mem_list_event_txns( $event_id ) {

	$args = array( 'post_status' => 'mem-income' );

	$event_txns = mem_get_event_txns( $event_id, $args );

	$txns = array();

	if ( $event_txns ) {
		foreach ( $event_txns as $txn ) {
			$mem_txn = new MEM_Txn( $txn->ID );

			$txns[] = mem_currency_filter( mem_format_amount( $mem_txn->price ) ) .
						' on ' .
						mem_format_short_date( $mem_txn->post_date ) .
						' (' . $mem_txn->get_type() . ')';

		}
	}

	return implode( '<br />', $txns );

} // mem_list_event_txns

/**
 * Displays all event transactions within a table.
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @param int $event_id
 * @return str
 */
function mem_do_event_txn_table( $event_id ) {

	global $mem_event;

	$event_txns = apply_filters(
		'mem_event_txns',
		mem_get_event_txns(
			$event_id,
			array( 'orderby' => 'post_status' )
		)
	);

	$in  = 0;
	$out = 0;

	?>

	<table class="widefat mem_event_txn_list">
		<thead>
			<tr>
				<th style="width: 20%"><?php esc_html_e( 'Date', 'mobile-events-manager' ); ?></th>
				<th style="width: 20%"><?php esc_html_e( 'To/From', 'mobile-events-manager' ); ?></th>
				<th style="width: 15%"><?php esc_html_e( 'In', 'mobile-events-manager' ); ?></th>
				<th style="width: 15%"><?php esc_html_e( 'Out', 'mobile-events-manager' ); ?></th>
				<th><?php esc_html_e( 'Details', 'mobile-events-manager' ); ?></th>
				<?php do_action( 'mem_event_txn_table_head', $event_id ); ?>
			</tr>
		</thead>
		<tbody>
		<?php if ( $event_txns ) : ?>
			<?php foreach ( $event_txns as $event_txn ) : ?>

				<?php $txn = new MEM_Txn( $event_txn->ID ); ?>

				<tr class="mem_field_wrapper">
					<td><a href="<?php echo get_edit_post_link( $txn->ID ); ?>"><?php echo mem_format_short_date( $txn->post_date ); ?></a></td>
					<td><?php echo esc_attr( mem_get_txn_recipient_name( $txn->ID ) ); ?></td>
					<td>
						<?php if ( 'mem-income' === $txn->post_status ) : ?>
							<?php $in += mem_sanitize_amount( $txn->price ); ?>
							<?php echo mem_currency_filter( mem_format_amount( $txn->price ) ); ?>
						<?php else : ?>
							<?php echo '&ndash;'; ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( 'mem-expenditure' === $txn->post_status ) : ?>
							<?php $out += mem_sanitize_amount( $txn->price ); ?>
							<?php echo mem_currency_filter( mem_format_amount( $txn->price ) ); ?>
						<?php else : ?>
							<?php echo '&ndash;'; ?>
						<?php endif; ?>
					</td>
					<td><?php echo $txn->get_type(); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
		<tr>
			<td colspan="5"><?php printf( esc_html__( 'There are currently no transactions for this %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></td>
		</tr>
		<?php endif; ?>
		</tbody>
		<tfoot>
		<tr>
			<th style="width: 20%">&nbsp;</th>
			<th style="width: 20%">&nbsp;</th>
			<th style="width: 15%"><strong><?php echo mem_currency_filter( mem_format_amount( $in ) ); ?></strong></th>
			<th style="width: 15%"><strong><?php echo mem_currency_filter( mem_format_amount( $out ) ); ?></strong></th>
			<th><strong><?php printf( esc_html__( '%s Earnings:', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?> <?php echo mem_currency_filter( mem_format_amount( ( $in - $out ) ) ); ?></strong></th>
		</tr>
		<?php do_action( 'mem_event_txn_table_foot', $event_id ); ?>
		</tfoot>
	</table>

	<?php

} // mem_do_event_txn_table

/**
 * Returns the client ID.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int The user ID of the event client.
 */
function mem_get_event_client_id( $event_id ) {
	$event = new MEM_Event( $event_id );
	return $event->client;
} // mem_get_event_client_id

/**
 * Retrieve the event employees.
 *
 * @since 1.3
 * @param int $event_id
 * @return arr Array of all event employees and data.
 */
function mem_get_all_event_employees( $event_id ) {
	$mem_event = new MEM_Event( $event_id );

	return $mem_event->get_all_employees();
} // mem_get_event_employees

/**
 * Returns the primary employee ID.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int The user ID of the events primary employee.
 */
function mem_get_event_primary_employee_id( $event_id ) {
	if ( empty( $event_id ) ) {
		return false;
	}

	$event = new MEM_Event( $event_id );
	return esc_html( $event->get_employee() );
} // mem_get_event_primary_employee_id

/**
 * Returns the URL for an event.
 *
 * @since 1.3
 * @param int  $event_id The event ID.
 * @param bool $admin True to retrieve the admin URL to the event.
 * @return str URL to Client Zone page for the event.
 */
function mem_get_event_uri( $event_id, $admin = false ) {
	if ( $admin ) {
		return add_query_arg(
			array(
				'post'   => $event_id,
				'action' => 'edit',
			),
			admin_url( 'post.php' )
		);
	} else {
		return add_query_arg( 'event_id', $event_id, mem_get_formatted_url( mem_get_option( 'app_home_page' ) ) );
	}
} // mem_get_event_uri

/**
 * Retrieve the length of the event in hours.
 *
 * @since 1.5
 * @param int $event_id The event ID.
 * @return int The event duration in hours
 */
function mem_get_event_duration_in_hours( $event_id ) {
	$event = new MEM_Event( $event_id );

	if ( 0 === $event->ID ) {
		return 0;
	}

	return esc_html( $event->get_duration() );
} // mem_get_event_duration_in_hours

/**
 * Retrieve the duration of the event.
 *
 * Calculate the duration of the event and return in human readable format.
 *
 * @since 1.3
 * @uses human_time_diff
 * @param int $event_id The event ID.
 * @return str The length of the event.
 */
function mem_event_duration( $event_id ) {
	$start_time = get_post_meta( $event_id, '_mem_event_start', true );
	$start_date = get_post_meta( $event_id, '_mem_event_date', true );
	$end_time   = get_post_meta( $event_id, '_mem_event_finish', true );
	$end_date   = get_post_meta( $event_id, '_mem_event_end_date', true );

	if ( ! empty( $start_time ) && ! empty( $start_date ) && ! empty( $end_time ) && ! empty( $end_time ) ) {
		$start = strtotime( $start_time . ' ' . $start_date );
		$end   = strtotime( $end_time . ' ' . $end_date );

		$duration = str_replace( 'min', 'minute', human_time_diff( $start, $end ) );

		return apply_filters( 'mem_event_duration', $duration );
	}
} // mem_event_duration

/**
 * Calculate time to event.
 *
 * Calculate the length of time until the event starts.
 *
 * @since 1.3
 * @uses human_time_diff
 * @param int $event_id The event ID.
 * @return str The length of the event.
 */
function mem_time_until_event( $event_id ) {

	$start_time = get_post_meta( $event_id, '_mem_event_start', true );
	$start_date = get_post_meta( $event_id, '_mem_event_date', true );

	if ( ! empty( $start_time ) && ! empty( $start_date ) ) {

		$start = strtotime( $start_time . ' ' . $start_date );
		$end   = strtotime( $end_time . ' ' . $end_date );

		$length = str_replace( 'min', 'minute', human_time_diff( $start, $end ) );

		return apply_filters( 'mem_time_until_event', $length );

	}

} // mem_time_until_event

/**
 * Update event meta.
 *
 * We don't currently delete empty meta keys or values, instead we update with an empty value
 * if an empty value is passed to the function.
 *
 * We may soon move to a configuration where all meta key => value pairs are stored in a single
 * meta key (_mem_event_data). As a result there is some duplication here, but performance
 * impact is minimal.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param arr $data The appropriately formatted meta data values.
 * @return mixed See get_post_meta()
 */
function mem_update_event_meta( $event_id, $data ) {

	do_action( 'mem_pre_update_event_meta', $event_id, $data );

	// For backwards compatibility.
	$current_meta = get_post_meta( $event_id );

	$debug = array();
	$meta  = get_post_meta( $event_id, '_mem_event_data', true );

	foreach ( $data as $key => $value ) {

		if ( 'mem_nonce' === $key || 'mem_action' === $key || substr( $key, 0, 12 ) != '_mem_event_' ) {
			continue;
		}

		$price_keys = array(
			'_mem_event_dj_wage',
			'_mem_event_package_cost',
			'_mem_event_addons_cost',
			'_mem_event_travel_cost',
			'_mem_event_additional_cost',
			'_mem_event_discount',
			'_mem_event_deposit',
			'_mem_event_cost',

		);

		if ( in_array( $key, $price_keys ) ) {
			$value = $value;
		} elseif ( '_mem_event_venue_postcode' === $key && ! empty( $value ) ) { // Postcodes are uppercase.
			$value = strtoupper( $value );
		} elseif ( '_mem_event_venue_email' === $key && ! empty( $value ) ) { // Emails are lowercase.
			$value = strtolower( $value );
		} elseif ( '_mem_event_package' === $key && ! empty( $value ) ) {
			$value = sanitize_text_field( strtolower( $value ) );
		} elseif ( '_mem_event_addons' === $key && ! empty( $value ) ) {
			$value = $value;
		} elseif ( '_mem_event_travel_data' === $key ) {
			$value = $value;
		} elseif ( ! strpos( $key, 'notes' ) && ! empty( $value ) ) {
			$value = sanitize_text_field( ucwords( $value ) );
		} elseif ( ! empty( $value ) ) {
			$value = $value;
		} else {
			$value = '';
		}

		// If we have a value and the key did not exist previously, add it.
		if ( ! empty( $value ) && ( empty( $current_meta[ $key ] ) || empty( $current_meta[ $key ][0] ) ) ) {

			$debug[] = sprintf( esc_html__( 'Adding %1$s value as %2$s', 'mobile-events-manager' ), mem_event_get_meta_label( $key ), is_array( $value ) ? var_export( $value, true ) : $value );
			add_post_meta( $event_id, $key, $value );

		} elseif ( ! empty( $value ) && $value != $current_meta[ $key ][0] ) { // If a value existed, but has changed, update it.

			$debug[] = sprintf( esc_html__( 'Updating %1$s with %2$s', 'mobile-events-manager' ), mem_event_get_meta_label( $key ), is_array( $value ) ? var_export( $value, true ) : $value );
			update_post_meta( $event_id, $key, $value );

		} elseif ( empty( $value ) && ! empty( $current_meta[ $key ][0] ) ) { // If there is no new meta value but an old value exists, delete it.

			$debug[] = sprintf( esc_html__( 'Removing %1$s from %2$s', 'mobile-events-manager' ), $current_meta[ $key ][0], mem_event_get_meta_label( $key ) );
			delete_post_meta( $event_id, $key, $value );

		}
	}

	$journal_args = array(
		'user_id'         => is_user_logged_in() ? get_current_user_id() : 1,
		'event_id'        => $event_id,
		'comment_content' => sprintf(
			__( '%s Updated', 'mobile-events-manager' ) . ':<br /> %s',
			esc_html( mem_get_label_singular() ),
			implode( '<br />', $debug )
		),
	);

	mem_add_journal( $journal_args );

	do_action( 'mem_primary_employee_payment_status', $event_id, $current_meta, $data );
	do_action( 'mem_post_update_event_meta', $event_id, $current_meta, $data );

	if ( ! empty( $debug ) ) {

		foreach ( $debug as $log ) {
			MEM()->debug->log_it( $log, false );
		}
	}

	return true;

} // mem_update_event_meta

/**
 * Retrieve a readable name for the meta key.
 *
 * @since 1.3
 * @param str $key The meta key.
 * @return str The readable label
 */
function mem_event_get_meta_label( $key ) {

	$keys = array(
		'_mem_event_addons'            => __( 'Add-ons', 'mobile-events-manager' ),
		'_mem_event_admin_notes'       => __( 'Admin Notes', 'mobile-events-manager' ),
		'_mem_event_balance_status'    => sprintf( esc_html__( '%s Status', 'mobile-events-manager' ), mem_get_balance_label() ),
		'_mem_event_client'            => __( 'Client', 'mobile-events-manager' ),
		'_mem_event_contract'          => sprintf( esc_html__( '%s Contract', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_contract_approved' => __( 'Contract Approved Date', 'mobile-events-manager' ),
		'_mem_event_contract_approver' => __( 'Contract Approved By', 'mobile-events-manager' ),
		'_mem_event_cost'              => __( 'Total Cost', 'mobile-events-manager' ),
		'_mem_event_date'              => sprintf( esc_html__( '%s Date', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_deposit'           => mem_get_deposit_label(),
		'_mem_event_deposit_status'    => sprintf( esc_html__( '%s Status', 'mobile-events-manager' ), mem_get_deposit_label() ),
		'_mem_event_dj'                => sprintf( esc_html__( '%s Contract', 'mobile-events-manager' ), mem_get_option( 'artist' ) ),
		'_mem_event_dj_notes'          => __( 'Employee Notes', 'mobile-events-manager' ),
		'_mem_event_dj_payment_status' => sprintf( esc_html__( 'Primary Employee %s Payment Details', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_djsetup_date'      => sprintf( esc_html__( '%s Setup Date', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_djsetup_time'      => sprintf( esc_html__( '%s Setup Time', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_dj_wage'           => sprintf( esc_html__( 'Primary Employee %s Wage', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_employees'         => __( 'Employees', 'mobile-events-manager' ),
		'_mem_event_employees_data'    => __( 'Employees Payment Data', 'mobile-events-manager' ),
		'_mem_event_enquiry_source'    => __( 'Enquiry Source', 'mobile-events-manager' ),
		'_mem_event_finish'            => __( 'End Time', 'mobile-events-manager' ),
		'_mem_event_last_updated_by'   => __( 'Last Updated By', 'mobile-events-manager' ),
		'_mem_event_name'              => sprintf( esc_html__( '%s Name', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'_mem_event_notes'             => __( 'Description', 'mobile-events-manager' ),
		'_mem_event_package'           => __( 'Package', 'mobile-events-manager' ),
		'_mem_event_playlist'          => __( 'Playlist Enabled', 'mobile-events-manager' ),
		'_mem_event_playlist_access'   => __( 'Playlist Guest Access Code', 'mobile-events-manager' ),
		'_mem_event_playlist_limit'    => __( 'Playlist Limit', 'mobile-events-manager' ),
		'_mem_event_start'             => __( 'Start Time', 'mobile-events-manager' ),
		'_mem_event_travel_data'       => __( 'Travel Data', 'mobile-events-manager' ),
		'_mem_event_venue_address1'    => __( 'Venue Address Line 1', 'mobile-events-manager' ),
		'_mem_event_venue_address2'    => __( 'Venue Address Line 2', 'mobile-events-manager' ),
		'_mem_event_venue_contact'     => __( 'Venue Contact', 'mobile-events-manager' ),
		'_mem_event_venue_county'      => __( 'Venue County', 'mobile-events-manager' ),
		'_mem_event_venue_email'       => __( 'Venue Email Address', 'mobile-events-manager' ),
		'_mem_event_venue_id'          => __( 'Venue ID', 'mobile-events-manager' ),
		'_mem_event_venue_name'        => __( 'Venue Name', 'mobile-events-manager' ),
		'_mem_event_venue_phone'       => __( 'Venue Phone Number', 'mobile-events-manager' ),
		'_mem_event_venue_postcode'    => __( 'Venue Post Code', 'mobile-events-manager' ),
		'_mem_event_venue_town'        => __( 'Venue Post Town', 'mobile-events-manager' ),
	);

	$keys = apply_filters( 'mem_event_meta_labels', $keys );

	if ( array_key_exists( $key, $keys ) ) {
		return $keys[ $key ];
	} else {
		return $key;
	}

} // mem_event_get_meta_label

/**
 * Update the event status.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $new_status The new event status.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_update_event_status( $event_id, $new_status, $old_status, $args = array() ) {

	if ( $new_status === $old_status ) {
		return false;
	}

	do_action( 'mem_pre_event_status_change', $event_id, $new_status, $old_status, $args );

	do_action( "mem_pre_update_event_status_{$new_status}", $event_id, $old_status, $args );

	$func = 'mem_set_event_status_' . str_replace( '-', '_', $new_status );

	if ( function_exists( $func ) ) {
		$result = $func( $event_id, $old_status, $args );
	} else {
		$result = true;
	}

	do_action( "mem_post_update_event_status_{$new_status}", $result, $event_id, $old_status, $args );

	do_action( 'mem_post_event_status_change', $result, $event_id, $new_status, $old_status, $args );

	return $result;

} // mem_update_event_status

/**
 * Update event status to Unattended Enquiry.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_unattended( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-unattended',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	return $update;

} // mem_set_event_status_mem_unattended

/**
 * Update event status to Enquiry.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_enquiry( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-enquiry',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	// Generate an online quote that is visible via the Client Zone.
	if ( mem_get_option( 'online_enquiry', false ) ) {
		$quote_template = isset( $args['quote_template'] ) ? $args['quote_template'] : mem_get_option( 'online_enquiry' );
		$quote_id       = mem_create_online_quote( $event_id, $quote_template );
	}

	// Email the client.
	if ( ! empty( $args['client_notices'] ) ) {
		$email_template = isset( $args['email_template'] ) ? $args['email_template'] : mem_get_option( 'enquiry' );
		mem_email_quote( $event_id, $email_template );
	}

	return $update;

} // mem_set_event_status_mem_enquiry

/**
Update event status to Avaiting Deposit
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.5
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_awaitingdeposit( $event_id, $old_status, $args = array() ) {
		remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

		$update = wp_update_post(
			array(
				'ID'          => $event_id,
				'post_status' => 'mem-awaitingdeposit',
			)
		);

		// Meta updates.
		$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

		mem_update_event_meta( $event_id, $args['meta'] );

		// Email the client.
	if ( ! empty( $args['client_notices'] ) ) {
		mem_email_awaitingdeposit( $event_id );
	}

		add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

		return $update;
} // mem_set_event_status_mem_awaitingdeposit

/**
 * Update event status to Awaiting Contract.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_contract( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-contract',
		)
	);

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	// Email the client.
	if ( ! empty( $args['client_notices'] ) ) {
		mem_email_enquiry_accepted( $event_id );
	}

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	return $update;

} // mem_set_event_status_mem_contract

/**
 * Update event status to Approved.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_approved( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-approved',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	// Email the client.
	if ( ! empty( $args['client_notices'] ) ) {
		mem_email_booking_confirmation( $event_id );
	}

	return $update;

} // mem_set_event_status_mem_approved

/**
 * Update event status to Completed.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_completed( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-completed',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	return $update;

} // mem_set_event_status_mem_completed

/**
 * Update event status to Cancelled.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_cancelled( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-cancelled',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	return $update;

} // mem_set_event_status_mem_cancelled

/**
 * Update event status to Failed Enquiry.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_failed( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-failed',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	return $update;

} // mem_set_event_status_mem_failed

/**
 * Update event status to Rejected Enquiry.
 *
 * If you're looking for hooks, see the mem_update_event_status() function.
 * Do not call this function directly, instead call mem_update_event_status() to ensure
 * all hooks are processed.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param str $old_status The old event status.
 * @param arr $args Array of data required for transition.
 * @return int The ID of the event if it is successfully updated. Otherwise returns 0.
 */
function mem_set_event_status_mem_rejected( $event_id, $old_status, $args = array() ) {

	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$update = wp_update_post(
		array(
			'ID'          => $event_id,
			'post_status' => 'mem-rejected',
		)
	);

	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Meta updates.
	$args['meta']['_mem_event_last_updated_by'] = is_user_logged_in() ? get_current_user_id() : 1;

	mem_update_event_meta( $event_id, $args['meta'] );

	return $update;

} // mem_set_event_status_mem_rejected

/**
 * Retrieve the quote for the event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return obj Quote post object or false if no quote exists
 */
function mem_get_event_quote( $event_id ) {

	$quote = get_posts(
		array(
			'posts_per_page' => 1,
			'post_parent'    => $event_id,
			'post_type'      => 'mem-quotes',
			'post_status'    => 'any',
		)
	);

	if ( $quote ) {
		return $quote[0];
	} else {
		return false;
	}

} // mem_get_event_quote

/**
 * Retrieve the Quote ID for the event
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return int Quote post ID false if no quote exists
 */
function mem_get_event_quote_id( $event_id ) {

	$quote = mem_get_event_quote( $event_id );

	if ( $quote ) {
		return $quote->ID;
	} else {
		return false;
	}

} // mem_get_event_quote_id

/**
 * Generates a new online quote for the event.
 *
 * Uses the quote template defined within settings unless $template_id is provided.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $template_id The template ID from which to create the quote.
 * @return int $quote_id The ID of the newly created post or false on fail.
 */
function mem_create_online_quote( $event_id, $template_id = '' ) {

	$existing_id = mem_get_event_quote_id( $event_id );
	$template_id = ! empty( $template_id ) ? $template_id : mem_get_option( 'online_enquiry' );

	if ( empty( $template_id ) ) {
		return false;
	}

	/**
	 * Allow filtering of the quote template.
	 *
	 * @since 1.3
	 * @param $template_id
	 */
	$template_id = apply_filters( 'mem_online_quote_template', $template_id );
	$template    = get_post( $template_id );

	if ( ! $template ) {
		return false;
	}

	/**
	 * Fire the `mem_pre_create_online_quote` hook.
	 *
	 * @since 1.3
	 * @param int $event_id The Event ID
	 * @param int $template_id The quote template ID
	 * @param obj $template The quote template WP_Post object
	 */
	do_action( 'mem_pre_create_online_quote', $event_id, $template_id, $template );

	$client_id = mem_get_event_client_id( $event_id );

	$content = $template->post_content;
	$content = apply_filters( 'the_content', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );
	$content = mem_do_content_tags( $content, $event_id, $client_id );

	$args = array(
		'ID'            => $existing_id,
		'post_date'     => current_time( 'mysql' ),
		'post_modified' => current_time( 'mysql' ),
		'post_title'    => sprintf( esc_html__( 'Quote %s', 'mobile-events-manager' ), mem_get_event_contract_id( $event_id ) ),
		'post_content'  => $content,
		'post_type'     => 'mem-quotes',
		'post_status'   => 'mem-quote-generated',
		'post_author'   => ! empty( $client_id ) ? $client_id : 1,
		'post_parent'   => $event_id,
		'meta_input'    => array(
			'_mem_quote_viewed_date'  => 0,
			'_mem_quote_viewed_count' => 0,
		),
	);

	/**
	 * Allow filtering of the quote template args.
	 *
	 * @since 1.3
	 * @param $args
	 */
	$args     = apply_filters( 'mem_create_online_quote_args', $args );
	$quote_id = wp_insert_post( $args );

	if ( ! $quote_id ) {
		return false;
	}

	// Reset view date and count for existing quotes.
	if ( ! empty( $existing_id ) ) {
		delete_post_meta( $quote_id, '_mem_quote_viewed_date' );
		delete_post_meta( $quote_id, '_mem_quote_viewed_count' );
	}

	/**
	 * Fire the `mem_post_create_online_quote` hook.
	 *
	 * @since 1.3
	 * @param int $quote_id The new quote ID
	 */
	do_action( 'mem_pre_create_online_quote', $quote_id );

	return $quote_id;

} // mem_create_online_quote

/**
 * Display the online quote.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return str The content of the quote.
 */
function mem_display_quote( $event_id ) {

	$quote = mem_get_event_quote( $event_id );

	if ( ! $quote ) {
		return apply_filters( 'mem_quote_not_found_msg', sprintf( esc_html__( 'Sorry but the quote for your %s could not be displayed.', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) );
	}

	$quote_content = $quote->post_content;
	$quote_content = apply_filters( 'the_content', $quote_content );
	$quote_content = str_replace( ']]>', ']]&gt;', $quote_content );

	mem_viewed_quote( $quote->ID, $event_id );

	return apply_filters( 'mem_display_quote', $quote_content, $event_id );

} // mem_display_quote

/**
 * Registers the online quote as viewed.
 *
 * Increase the view count.
 *
 * @since 1.3
 * @param int $quote_id The quote ID.
 * @param int $event_id The event ID.
 * @return void
 */
function mem_viewed_quote( $quote_id, $event_id ) {

	// Only counts if the current user is the event client.
	if ( get_current_user_id() !== get_post_meta( $event_id, '_mem_event_client', true ) ) {
		return;
	}

	if ( wp_update_post(
		array(
			'ID'          => $quote_id,
			'post_status' => 'mem-quote-viewed',
		)
	) ) {

		$view_count = get_post_meta( $quote_id, '_mem_quote_viewed_count', true );

		if ( ! empty( $view_count ) ) {
			$view_count++;
		} else {
			$view_count = 1;
		}

		MEM()->debug->log_it( 'Updating quote view count for Quote ID: ' . $quote_id . ' and event ID: ' . $event_id, true );
		update_post_meta( $quote_id, '_mem_quote_viewed_count', $view_count );

		// Only update the view date if this is the first viewing.
		if ( 1 === $view_count ) {

			MEM()->debug->log_it( 'Updating quote viewed time', false );

			update_post_meta( $quote_id, '_mem_quote_viewed_date', current_time( 'mysql' ) );
		}
	}

} // mem_viewed_quote

/**
 * Retrieve the emails associated with the event.
 *
 * @since 1.3.7
 * @param int $event_id Event ID.
 * @return obj The email post objects.
 */
function mem_event_get_emails( $event_id ) {

	if ( ! mem_employee_can( 'read_events' ) ) {
		return false;
	}

	$args = array(
		'post_type'      => 'mem_communication',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'post_parent'    => $event_id,
		'order'          => 'DESC',
	);

	if ( ! mem_employee_can( 'read_events_all' ) ) {
		$args['post_author'] = get_current_user_id();
	}

	$emails = get_posts( $args );

	return apply_filters( 'mem_event_get_emails', $emails, $event_id );

} // mem_event_get_emails
