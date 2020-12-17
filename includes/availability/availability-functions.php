<?php
/**
 * Contains all availability checker related functions
 *
 * @package TMEM
 * @subpackage Availability
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve absence title
 *
 * @since 1.5.7
 * @return string Absence title
 */
function tmem_get_calendar_absence_title() {
	$absence_title = tmem_get_option( 'calendar_absence_title' );
	return stripslashes( esc_html( $absence_title ) );
} // tmem_get_calendar_absence_title

/**
 * Retrieve absence tip title
 *
 * @since 1.5.7
 * @return string Absence tip title
 */
function tmem_get_calendar_absence_tip_title() {
	$tip_title = tmem_get_option( 'calendar_absence_tip_title' );
	return stripslashes( esc_html( $tip_title ) );
} // tmem_get_calendar_absence_tip_title

/**
 * Retrieve absence content
 *
 * @since 1.5.7
 * @return string Absence tip content
 */
function tmem_get_calendar_absence_tip_content() {
	$tip_content = tmem_get_option( 'calendar_absence_tip_content' );
	return stripslashes( esc_html( $tip_content ) );
} // tmem_get_calendar_absence_tip_content

/**
 * Retrieve event title
 *
 * @since 1.5.7
 * @return string Event title
 */
function tmem_get_calendar_event_title() {
	$event_title = tmem_get_option( 'calendar_event_title' );
	return stripslashes( esc_html( $event_title ) );
} // tmem_get_calendar_event_title

/**
 * Retrieve absence tip title
 *
 * @since 1.5.7
 * @return string Event tip title
 */
function tmem_get_calendar_event_tip_title() {
	$tip_title = tmem_get_option( 'calendar_event_tip_title' );
	return stripslashes( esc_html( $tip_title ) );
} // tmem_get_calendar_event_tip_title

/**
 * Retrieve event content
 *
 * @since 1.5.7
 * @return string Event tip content
 */
function tmem_get_calendar_event_tip_content() {
	$tip_content = tmem_get_option( 'calendar_event_tip_content' );
	return stripslashes( esc_html( $tip_content ) );
} // tmem_get_calendar_event_tip_content

/**
 * Retrieve absence content tags
 *
 * @since 1.5.7
 * @return array Array of tags
 */
function tmem_get_absence_content_tags() {
	$absence_tags = array(
		'{employee_name}',
		'{start}',
		'{end}',
		'{notes}',
	);

	$absence_tags = apply_filters( 'tmem_absence_tags', $absence_tags );
	return $absence_tags;
} // tmem_get_absence_content_tags

/**
 * Display available absence content tags
 *
 * @since 1.5.7
 * @param string $seperator The string to use to seperate the tags.
 * @return string Absence tags
 */
function tmem_display_absence_content_tags( $seperator = ', ' ) {
	$absence_tags = array();

	foreach ( tmem_get_absence_content_tags() as $absence_tag ) {
		$absence_tags[] = sprintf( '<code>%s</code>', $absence_tag );
	}

	return implode( $seperator, $absence_tags );
} // tmem_display_absence_content_tags

/**
 * Perform the content tag replacements for absences.
 *
 * @since 1.5.7
 * @param string $content The content to search and replace.
 * @param object $absence Absence database object.
 * @return string Filtered content
 */
function tmem_do_absence_content_tags( $content = '', $absence = false ) {
	if ( ! empty( $content ) && ! empty( $absence ) ) {
		$employee_id = $absence->employee_id;
		$start       = strtotime( $absence->start );
		$end         = strtotime( $absence->end );
		$notes       = ! empty( $absence->notes ) ? stripslashes( $absence->notes ) : '';
		$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$search = tmem_get_absence_content_tags();

		$replace = array(
			tmem_get_employee_display_name( $employee_id ),
			gmdate( $date_format, $start ),
			gmdate( $date_format, $end ),
			$notes,
		);

		$content = str_replace( $search, $replace, $content );
	}

	return esc_html( $content );
} // tmem_do_absence_content_tags

/**
 * Retrieve the default event statuses that make an employee unavailable.
 *
 * @since 1.5.6
 * @return array
 */
function tmem_get_availability_statuses() {
	$statuses = tmem_get_option( 'availability_status', 'any' );
	return apply_filters( 'tmem_availability_statuses', $statuses );
} // tmem_get_availability_statuses

/**
 * Retrieve the default roles to check for availability.
 *
 * @since 1.5.6
 * @return array
 */
function tmem_get_availability_roles() {
	$roles = tmem_get_option( 'availability_roles', array() );
	return apply_filters( 'tmem_availability_roles', $roles );
} // tmem_get_availability_roles

/**
 * Set the correct time format for the calendar
 *
 * @since 1.5.6
 * @return arr $time_format.
 */
function tmem_format_calendar_time() {
	$time_format = get_option( 'time_format' );

	$search  = array( 'g', 'G', 'i', 'a', 'A' );
	$replace = array( 'h', 'H', 'mm', 't', 'T' );

	$time_format = str_replace( $search, $replace, $time_format );

	return apply_filters( 'tmem_format_calendar_time', $time_format );
} // tmem_format_calendar_time

/**
 * Retrieve the default view for the calendar.
 *
 * @since 1.5.6
 * @param bool $dashboard True if the view is for the dashboard page.
 * @return string The default view for the calendar
 */
function tmem_get_calendar_view( $dashboard = false ) {
	$option_name = ! $dashboard ? 'availability' : 'dashboard';
	$option_name = $option_name . '_view';

	$view = tmem_get_option( $option_name, 'month' );

	return apply_filters( 'tmem_calendar_view', $view, $dashboard );
} // tmem_get_calendar_view

/**
 * Retrieve view options for the calendar
 *
 * @since 1.5.6
 * @return array Array of view options
 */
function tmem_get_calendar_views() {
	$views = array(
		'agendaDay'  => __( 'Day', 'mobile-events-manager' ),
		'list'       => __( 'List', 'mobile-events-manager' ),
		'month'      => __( 'Month', 'mobile-events-manager' ),
		'agendaWeek' => __( 'Week', 'mobile-events-manager' ),
	);

	return apply_filters( 'tmem_calendar_views', $views );
} // tmem_get_calendar_views

/**
 * Retrieve color options for the calendar
 *
 * @since 1.5.6
 * @param str  $color string Which color to retrieve.
 * @param bool $event bool True if retrieving for an event or false for an asbence.
 * @return array Array of view options
 */
function tmem_get_calendar_color( $color = 'background', $event = false ) {
	$option_name = ! $event ? 'absence_' : 'event_';
	$option_name = $option_name . $color . '_color';
	$return      = tmem_get_option( $option_name );

	return apply_filters( 'tmem_calendar_color', $return, $color, $event, $option_name );
} // tmem_get_calendar_color

/**
 * Retrieve all dates within the given range
 *
 * @since 1.5.6
 * @param string $start The start date Y-m-d.
 * @param string $end The end date Y-m-d.
 * @return array Array of all dates between two given dates
 */
function tmem_get_all_dates_in_range( $start, $end ) {
	$start = \DateTime::createFromFormat( 'Y-m-d', $start );
	$end   = \DateTime::createFromFormat( 'Y-m-d', $end );

	$range = new \DatePeriod( $start, new \DateInterval( 'P1D' ), $end->modify( '+1 day' ) );

	return $range;
} // tmem_get_all_dates_in_range

/**
 * Add an employee absence entry.
 *
 * @since 1.5.6
 * @param int   $employee_id Employee user ID.
 * @param array $data Array of absence data.
 * @return bool True on success, otherwise false
 */
function tmem_add_employee_absence( $employee_id, $data ) {
	$employee_id = absint( $employee_id );

	if ( empty( $employee_id ) ) {
		return false;
	}

	$all_day    = isset( $data['all_day'] ) ? $data['all_day'] : 0;
	$start      = ! empty( $data['start'] ) ? $data['start'] : gmdate( 'Y-m-d' );
	$end        = ! empty( $data['end'] ) ? $data['end'] : gmdate( 'Y-m-d' );
	$start_time = ! empty( $data['start_time'] ) ? $data['start_time'] : '00:00:00';
	$end_time   = ! empty( $data['end_time'] ) ? $data['end_time'] : '00:00:00';
	$start      = $start . ' ' . $start_time;
	$end        = $end . ' ' . $end_time;
	$notes      = ! empty( $data['notes'] ) ? $data['notes'] : '';

	if ( $all_day ) {
		$end = gmdate( 'Y-m-d', strtotime( '+1 day', strtotime( $end ) ) ) . ' 00:00:00';
	}

	$args                = array();
	$args['employee_id'] = $employee_id;
	$args['all_day']     = $all_day;
	$args['start']       = $start;
	$args['end']         = $end;
	$args['notes']       = sanitize_textarea_field( $notes );

	$args = apply_filters( 'tmem_add_employee_absence_args', $args, $employee_id, $data );

	do_action( 'tmem_before_add_employee_absence', $args, $data );

	$return = TMEM()->availability_db->add( $args );

	do_action( 'tmem_add_employee_absence', $args, $data, $return );
	do_action( 'tmem_add_employee_absence_' . $employee_id, $args, $data, $return );

	return $return;
} // tmem_add_employee_absence

/**
 * Remove an employee absence entry.
 *
 * @since 1.5.6
 * @param int $id The absence ID to remove.
 * @return int The number of rows deleted or false
 */
function tmem_remove_employee_absence( $id ) {

	$id = absint( $id );

	if ( empty( $id ) ) {
		return false;
	}

	do_action( 'tmem_before_remove_employee_absence', $id );

	$deleted = TMEM()->availability_db->delete( $id );

	do_action( 'tmem_remove_employee_absence', $id, $deleted );

	return $deleted;
} // tmem_remove_employee_absence

/**
 * Retrieve all employee absences.
 *
 * @since 1.5.6
 * @return array Array of database result objects
 */
function tmem_get_all_absences() {
	$absences = TMEM()->availability_db->get_entries(
		array(
			'number'         => -1,
			'employees_only' => true,
		)
	);

	return $absences;
} // tmem_get_all_absences

/**
 * Retrieve all absences for an employee.
 *
 * @since 1.5.7
 * @param int $employee_id WP User ID.
 * @return array Array of employee absence objects
 */
function tmem_get_employee_absences( $employee_id ) {
	$employee_id = absint( $employee_id );

	if ( empty( $employee_id ) ) {
		return false;
	}

	$absences = TMEM()->availability_db->get_entries(
		array(
			'number'      => -1,
			'employee_id' => $employee_id,
		)
	);

	return $absences;
} // tmem_get_employee_absences

/**
 * Perform the availability lookup.
 *
 * @since 1.3
 * @param str       $date The requested date.
 * @param int|array $employees The employees to check.
 * @param str|array $roles The employee roles to check.
 * @param str|arr   $status The employee ID.
 * @return array|bool Array of available employees or roles, or false if not available
 */
function tmem_do_availability_check( $date, $employees = '', $roles = '', $status = '' ) {

	$check = new TMEM_Availability_Checker( $date, '', $employees, $roles, $status );

	$check->availability_check();

	return $check->result;

} // tmem_do_availability_check

/**
 * Retrieve employee and event activity for a given date range.
 *
 * @since 1.5.6
 * @param string $start Start date for which to retrieve activity.
 * @param string $end End date for which to retrieve activity.
 * @return array Array of data for the calendar
 */
function tmem_get_calendar_entries( $start, $end ) {
	$activity     = array();
	$roles        =
	$availability = new TMEM_Availability_Checker( $start, $end, '', tmem_get_roles() );

	$availability->get_calendar_entries();

	$activity = apply_filters( 'tmem_employee_availability_activity', $availability->results, $start, $end );

	return $activity;
} // tmem_get_calendar_entries

/**
 * Determine if an employee is absent on the given date.
 *
 * @since 1.3
 * @param str $date The date.
 * @param int $employee_id The employee ID.
 * @return bool True if the employee is working, otherwise false.
 */
function tmem_employee_is_absent( $date, $employee_id = '' ) {

	if ( empty( $employee_id ) && is_user_logged_in() ) {
		$employee_id = get_current_user_id();
	}

	$availability = new TMEM_Availability_Checker( $date, $employee_id );

	return $availability->is_employee_absent();
} // tmem_employee_is_absent

/**
 * Determine if an employee is working on the given date.
 *
 * @since 1.3
 * @param str     $date The date.
 * @param int     $employee_id The employee ID.
 * @param str|arr $status The employee ID.
 * @return bool True if the employee is working, otherwise false.
 */
function tmem_employee_is_working( $date, $employee_id = '' ) {

	if ( empty( $employee_id ) && is_user_logged_in() ) {
		$employee_id = get_current_user_id();
	}

	$availability = new TMEM_Availability_Checker( $date, $employee_id );

	return $availability->is_employee_working();
} // tmem_employee_is_working

/**
 * Retrieve the description text for the calendar popup
 *
 * @since 1.5.6
 * @return string
 */
function tmem_get_calendar_event_description_text() {
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Status: %s', 'mobile-events-manager' ), '{event_status}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Date: %s', 'mobile-events-manager' ), '{event_date}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Start: %s', 'mobile-events-manager' ), '{start_time}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Finish: %s', 'mobile-events-manager' ), '{end_time}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Setup: %s', 'mobile-events-manager' ), '{dj_setup_time}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Cost: %s', 'mobile-events-manager' ), '{total_cost}' ) . PHP_EOL;
	/* translators: %s: Company name */
	$default .= sprintf( esc_html__( 'Employees: %s', 'mobile-events-manager' ), '{event_employees}' ) . PHP_EOL;

	$text = tmem_get_event_tip_content();
	$text = ! empty( $text ) ? $text : $default;
	$text = utf8_encode( str_replace( PHP_EOL, '<br>', $text ) );

	return $text;
} // tmem_get_calendar_event_description_text
