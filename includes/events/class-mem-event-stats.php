<?php
/**
 * Event Stats
 *
 * @package MEM
 * @subpackage Classes/Stats
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_Event_Stats Class
 *
 * This class is for retrieving stats for events
 *
 * Stats can be retrieved for date ranges and pre-defined periods
 *
 * @since 1.4
 */
class MEM_Event_Stats extends MEM_Stats {

	/**
	 * Retrieve event stats
	 *
	 * @access public
	 * @since 1.4
	 * @param str|bool $start_date The starting date for which we'd like to filter our sale stats. If false, we'll use the default start date of `this_month`.
	 * @param str|bool $end_date The end date for which we'd like to filter our sale stats. If false, we'll use the default end date of `this_month`.
	 * @param str|bool $event_date The event start date for which we'd like to filter our event stats. If false, we'll use the default start date of `this_month`.
	 * @param str|arr  $status The event status(es) to count. Only valid when retrieving global stats.
	 * @return float|int Total amount of events based on the passed arguments.
	 */
	public function get_events( $start_date = false, $end_date = false, $event_date = false, $status = 'publish' ) {

		$this->setup_dates( $start_date, $end_date );

		// Make sure start date is valid.
		if ( is_wp_error( $this->start_date ) ) {
			return $this->start_date;
		}

		// Make sure end date is valid.
		if ( is_wp_error( $this->end_date ) ) {
			return $this->end_date;
		}

		add_filter( 'mem_count_events_where', array( $this, 'count_events_where' ) );

		if ( is_array( $status ) ) {
			$count = 0;
			foreach ( $status as $event_status ) {
				$count += mem_count_events()->$event_status;
			}
		} else {
			$count = mem_count_events()->$status;
		}

		remove_filter( 'mem_count_events_where', array( $this, 'count_events_where' ) );

		return $count;

	} // get_events

} // MEM_Event_Stats
