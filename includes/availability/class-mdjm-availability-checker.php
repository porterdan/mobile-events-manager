<?php
/**
 * Availability
 *
 * @package MEM
 * @subpackage Classes/Availability Checker
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_Availability_Checker Class
 *
 * @since 1.3
 */
class MEM_Availability_Checker {

	/**
	 * The start date to check
	 *
	 * @since 1.3
	 * @var array $start start date of the check.
	 */
	public $start = 0;

	/**
	 * The end date of the check
	 *
	 * @since 1.3
	 * @var array $end end date of the check.
	 */
	public $end = 0;

	/**
	 * The employees to check
	 *
	 * @since 1.3
	 * @var array $employees employees to check
	 */
	public $employees = array();

	/**
	 * The employee roles to report on
	 *
	 * @since 1.3
	 * @var array $roles employee roles.
	 */
	public $roles = array();

	/**
	 * The event status' to report on
	 *
	 * @since 1.3
	 * @var array $status the statuses.
	 */
	public $status;

	/**
	 * The availability check result
	 *
	 * @since 1.3
	 * @var array $result availability check result.
	 */
	public $result = array();

	/**
	 * Employees that are available
	 *
	 * @since 1.0
	 * @var array $available available employees.
	 */
	public $available = array();

	/**
	 * Employees that are not available
	 *
	 * @since 1.0
	 * @var array $unavailable employees not available.
	 */
	public $unavailable = array();

	/**
	 * Array of absentees.
	 *
	 * @since   1.5.6
	 * @var array $absentees array of absentees.
	 */
	public $absentees = array();

	/**
	 * The full availability check result
	 *
	 * @since 1.0
	 * @var array $results availability results.
	 */
	public $results = array();

	/**
	 * Get things going
	 *
	 * All vars are optional.
	 *
	 * Dates can be parsed either as a unix timestamp
	 * or as an english formatted date.
	 * See http://php.net/manual/en/datetime.formats.php.
	 *
	 * If no dates are provided the current day will be assumed.
	 *
	 * $roles is only referenced if no $employees are provided.
	 *
	 * @since 1.3
	 * @param string       $start The start date for the checker.
	 * @param string       $end The end date for the checker.
	 * @param int|array    $employees Employee ID, or an array of employee IDs.
	 * @param string|array $roles Employee role, or array of roles.
	 * @param string|array $status Event status, or array of event statuses.
	 */
	public function __construct(
		$start = false,
		$end = false,
		$employees = array(),
		$roles = array(),
		$status = array()
	) {
		return $this->setup_check( $start, $end, $employees, $roles, $status ); }
	// __construct

	/**
	 * Setup the availability checker.
	 *
	 * @since 1.3
	 * @param str $start The start date of the check.
	 * @param str $end The end date of the check.
	 * @param arr $employees The employees available.
	 * @param arr $roles The roles.
	 * @param str $status the status.
	 * @return bool
	 */
	public function setup_check( $start, $end, $employees, $roles, $status ) {
		$this->setup_dates( $start, $end );
		$this->setup_roles( $roles );
		$this->setup_employees( $employees );
		$this->setup_status( $status );

		return true;
	} // setup_check

	/**
	 * Setup dates.
	 *
	 * @since 1.0
	 * @param mixed $start set up date start.
	 * @param mixed $end set up date end.
	 */
	public function setup_dates( $start, $end ) {

		$now = current_time( 'timestamp' );

		if ( ! empty( $start ) ) {
			if ( is_numeric( $start ) ) {
				$start = gmdate( 'Y-m-d', $start );
			}
		}

		if ( ! empty( $end ) ) {
			if ( is_numeric( $end ) ) {
				$end = gmdate( 'Y-m-d', $end );
			}
		}

		$start = ! empty( $start ) ? strtotime( $start ) : $now;
		$end   = ! empty( $end ) ? strtotime( $end ) : $start;

		$this->start = strtotime( gmdate( 'Y-m-d', $start ) . ' 00:00:00' );
		$this->end   = strtotime( gmdate( 'Y-m-d', $end ) . ' 23:59:59' );

	} // setup_dates

	/**
	 * Setup roles.
	 *
	 * @since 1.0
	 * @param mixed $roles Setup roles.
	 */
	public function setup_roles( $roles ) {
		$roles = ! empty( $roles ) ? $roles : mem_get_availability_roles();
		$roles = ! empty( $roles ) ? $roles : mem_get_roles( $roles );

		if ( ! is_array( $roles ) ) {
			$roles = array( $roles );
		}

		$this->roles = $roles;
	} // setup_roles

	/**
	 * Setup employees.
	 *
	 * @since 1.0
	 * @param mixed $employees Setup Employees.
	 */
	public function setup_employees( $employees ) {
		$employees = ! empty( $employees ) ? $employees : mem_get_employees( $this->roles );
		$employees = is_array( $employees ) ? $employees : array( $employees );

		foreach ( $employees as $employee ) {
			if ( is_object( $employee ) ) {
				$this->employees[] = $employee->ID;
			} else {
				$this->employees[] = $employee;
			}
		}

		$this->employees = array_map( 'intval', array_unique( $this->employees ) );
		$this->available = $this->employees;

	} // setup_employees

	/**
	 * Setup status.
	 *
	 * @since 1.0
	 * @param mixed $status Setup statuses.
	 */
	public function setup_status( $status ) {
		$status = ! empty( $status ) ? $status : mem_get_availability_statuses();

		$this->status = ! is_array( $status ) ? array( $status ) : $status;
	} // setup_status

	/**
	Whether or not an employee is absent on the given date
	 *
	 * @since 1.0
	 * @return bool True if the employee is absent, otherwise false
	 */
	public function is_employee_absent() {
		$this->check_absences();

		return empty( $this->available );
	} // is_employee_absent

	/**
	Whether or not an employee is working on the given date
	 *
	 * @since 1.0
	 * @return bool True if the employee is working, otherwise false
	 */
	public function is_employee_working() {
		$this->check_events();

		return empty( $this->available );
	} // is_employee_working

	/**
	 * Perform a detailed lookup.
	 *
	 * @since 1.0
	 */
	public function availability_check() {
		if ( ! empty( $this->available ) ) {
			$this->check_absences();
		}

		if ( ! empty( $this->available ) ) {
			$this->check_events();
		}

		$this->result['available']   = $this->available;
		$this->result['unavailable'] = $this->unavailable;
		$this->result['absentees']   = $this->absentees;
	} // availability_check

	/**
	 * Checks employee absences for the given gmdate(s).
	 *
	 * @since 1.0
	 */
	public function check_absences() {
		$absences = MEM()->availability_db->get_entries(
			array(
				'employee_id' => $this->available,
				'start'       => $this->start,
				'end'         => $this->end,
				'number'      => 100,
			)
		);

		foreach ( $absences as $absence ) {
			$this->unavailable[ $absence->employee_id ]['absence'][ $absence->id ] = array(
				'start' => $absence->start,
				'end'   => $absence->end,
				'notes' => stripslashes( $absence->notes ),
			);

			$this->absentees[] = $absence->employee_id;

			if ( false !== $key = array_search( $absence->employee_id, $this->available ) ) {
				unset( $this->available[ $key ] );
			}
		}
	} // check_absences

	/**
	 * Checks active events for the given gmdate(s).
	 *
	 * @since 1.0
	 */
	function check_events() {
		$employees_query = array();

		foreach ( $this->available as $employee_id ) {
			$employees_query[] = array(
				'key'     => '_mem_event_employees',
				'value'   => sprintf( ':"%s";', $employee_id ),
				'compare' => 'LIKE',
			);
		}

		$events = mem_get_events(
			array(
				'post_status'    => $this->status,
				'posts_per_page' => -1,
				'meta_key'       => '_mem_event_date',
				'meta_value'     => gmdate( 'Y-m-d', $this->start ),
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_mem_event_dj',
						'value'   => implode( ',', $this->available ),
						'compare' => 'IN',
						'type'    => 'NUMERIC',
					),
					$employees_query,
				),
			)
		);

		if ( $events ) {
			foreach ( $events as $event ) {
				$mem_event = new MEM_Event( $event->ID );
				$employees  = $mem_event->get_all_employees();

				foreach ( $employees as $employee_id => $data ) {

					$this->unavailable[ $employee_id ]['event'][ $event->ID ] = array(
						'date'   => $mem_event->date,
						'end'    => $mem_event->get_finish_date(),
						'finish' => $mem_event->get_finish_time(),
						'start'  => $mem_event->get_start_time(),
						'status' => $mem_event->get_status(),
						'role'   => $data['role'],
					);

					if ( ! in_array( $employee_id, $this->absentees ) ) {
						$this->absentees[] = $employee_id;
					}

					if ( false !== $key = array_search( $employee_id, $this->available ) ) {
						unset( $this->available[ $key ] );
					}
				}
			}
		}
	} // check_events

	/**
	 * Retrieve entries for the calendar.
	 *
	 * @since 1.0
	 */
	public function get_calendar_entries() {
		$this->get_absences_in_range();
		$this->get_events_in_range();

		return $this->results;
	} // get_calendar_entries

	/**
	 * Retrieve employee absences within the given date range.
	 *
	 * @since 1.0
	 */
	public function get_absences_in_range() {
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$absences = MEM()->availability_db->get_entries(
			array(
				'employee_id' => $this->available,
				'start'       => $this->start,
				'end'         => $this->end,
				'calendar'    => true,
				'number'      => 100,
			)
		);

		foreach ( $absences as $absence ) {

			$short_date_start = gmdate( 'Y-m-d', strtotime( $absence->start ) );
			$short_date_end   = gmdate( 'Y-m-d', strtotime( $absence->end ) );
			$description      = array();
			$all_day          = ! empty( $absence->all_day ) ? true : false;
			$actions          = array();

			$from = gmdate( $time_format . ' \o\n ' . $date_format, strtotime( $absence->start ) );
			$to   = gmdate( $time_format . ' \o\n ' . $date_format, strtotime( $absence->end ) );

			$title = mem_do_absence_content_tags(
				mem_get_calendar_absence_title(),
				$absence
			);

			$tip_title = mem_do_absence_content_tags(
				mem_get_calendar_absence_tip_title(),
				$absence
			);

			$notes = '<p>' . str_replace( PHP_EOL, '<br>', mem_get_calendar_absence_tip_content() ) . '<p>';
			$notes = mem_do_absence_content_tags(
				$notes,
				$absence
			);

			$actions = apply_filters( 'mem_calendar_absence_actions', $actions );

			if ( mem_employee_can( 'manage_employees' ) ) {
				$actions[] = sprintf(
					'<a class="mem-delete availability-link delete-absence" data-entry="%d" href="#" >%s</a>',
					$absence->id,
					__( 'Delete entry', 'mobile-events-manager' )
				);
			}

			if ( ! empty( $actions ) ) {
				$notes .= '<p>' . implode( '&nbsp;&#124;&nbsp;', $actions ) . '</p>';
			}

			$this->results[] = array(
				'allDay'          => $all_day,
				'backgroundColor' => mem_get_calendar_color(),
				'borderColor'     => mem_get_calendar_color( 'border' ),
				'className'       => 'mem_calendar_absence',
				'end'             => $absence->end,
				'id'              => $absence->id,
				'notes'           => $notes,
				'start'           => $absence->start,
				'textColor'       => mem_get_calendar_color( 'text' ),
				'tipTitle'        => $tip_title,
				'title'           => $title,
			);

			$array_key = $key = array_search( $absence->employee_id, $this->available );
			if ( false !== $key ) {
				unset( $this->available[ $key ] );
			}
		}
	} // get_absences_in_range

	/**
	 * Retrieve events within the given date range.
	 *
	 * We only need to search for events where an employee is available.
	 * i.e. not absent as a result of the get_absences_in_range() method
	 *
	 * @since 1.0
	 */
	public function get_events_in_range() {
		$event_statuses   = mem_active_event_statuses();
		$event_statuses[] = 'mem-completed';

		$query_args = array(
			'post_status' => $event_statuses,
			'meta_query'  => array(
				'key'     => '_mem_event_date',
				'value'   => array( gmdate( 'Y-m-d', $this->start ), gmdate( 'Y-m-d', $this->end ) ),
				'compare' => 'BETWEEN',
				'type'    => 'DATE',
			),
		);

		$query_args = apply_filters( 'mem_events_in_range_args', $query_args );
		$events     = mem_get_events( $query_args );

		if ( $events ) {
			foreach ( $events as $event ) {
				$popover      = 'top';
				$mem_event   = new MEM_Event( $event->ID );
				$event_type   = $mem_event->get_type();
				$event_status = $mem_event->get_status();
				$employee     = mem_get_employee_display_name( $mem_event->employee_id );
				$event_id     = mem_get_event_contract_id( $mem_event->ID );
				$actions      = array();
				$event_url    = add_query_arg(
					array(
						'post'   => $mem_event->ID,
						'action' => 'edit',
					),
					admin_url( 'post.php' )
				);

				$title = mem_do_content_tags(
					mem_get_calendar_event_title(),
					$mem_event->ID,
					$mem_event->client
				);

				$tip_title = mem_do_content_tags(
					mem_get_calendar_event_tip_title(),
					$mem_event->ID,
					$mem_event->client
				);

				$notes = '<p>' . str_replace( PHP_EOL, '<br>', mem_get_calendar_event_tip_content() ) . '<p>';
				$notes = mem_do_content_tags(
					$notes,
					$mem_event->ID,
					$mem_event->client
				);

				$actions[] = sprintf(
					'<a class="availability-link" href="%s" >%s</a>',
					$event_url,
					sprintf( esc_html__( 'View %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) )
				);

				$actions = apply_filters( 'mem_calendar_event_actions', $actions );

				if ( ! empty( $actions ) ) {
					$notes .= '<p>' . implode( '&nbsp;&#124;&nbsp;', $actions ) . '</p>';
				}

				$this->results[] = array(
					'allDay'          => false,
					'backgroundColor' => mem_get_calendar_color( 'background', true ),
					'borderColor'     => mem_get_calendar_color( 'border', true ),
					'className'       => 'mem_calendar_event',
					'end'             => $mem_event->get_finish_date() . ' ' . $mem_event->get_finish_time(),
					'id'              => $mem_event->ID,
					'notes'           => $notes,
					'start'           => $mem_event->date . ' ' . $mem_event->get_start_time(),
					'textColor'       => mem_get_calendar_color( 'text', true ),
					'tipTitle'        => $tip_title,
					'title'           => $title,
				);
			}
		}
	} // get_events_in_range

} // class MEM_Availability_Checker
