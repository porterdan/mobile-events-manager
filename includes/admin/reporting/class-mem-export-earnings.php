<?php
/**
 * Earnings Export Class
 *
 * This class handles earnings export
 *
 * @package MEM
 * @subpackage Admin/Reports
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_Earnings_Export Class
 *
 * @since 1.4
 */
class MEM_Earnings_Export extends MEM_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'earnings';

	/**
	 * Set the export headers
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function headers() {

		ignore_user_abort( true );

		if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
			set_time_limit( 0 );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . apply_filters( 'mem_earnings_export_filename', 'mem-export-' . $this->export_type . '-' . gmdate( 'n' ) . '-' . gmdate( 'Y' ) ) . '.csv' );
		header( 'Expires: 0' );

	} // headers

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return srr $cols All the columns
	 */
	public function csv_cols() {

		$cols = array(
			'date'     => __( 'Date', 'mobile-events-manager' ),
			// 'events' => esc_html( mem_get_label_plural() ),
			'earnings' => __( 'Earnings', 'mobile-events-manager' ) . ' (' . html_entity_decode( mem_currency_filter( '' ) ) . ')',
		);

		return $cols;

	} // csv_cols

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $data The data for the CSV file
	 */
	public function get_data() {

		$start_year  = isset( $_POST['start_year'] ) ? absint( $_POST['start_year'] ) : gmdate( 'Y' );
		$end_year    = isset( $_POST['end_year'] ) ? absint( $_POST['end_year'] ) : gmdate( 'Y' );
		$start_month = isset( $_POST['start_month'] ) ? absint( $_POST['start_month'] ) : gmdate( 'n' );
		$end_month   = isset( $_POST['end_month'] ) ? absint( $_POST['end_month'] ) : gmdate( 'n' );

		$data  = array();
		$year  = $start_year;
		$stats = new MEM_Stats();

		while ( $year <= $end_year ) {

			if ( $year == $start_year && $year == $end_year ) {

				$m1 = $start_month;
				$m2 = $end_month;

			} elseif ( $year == $start_year ) {

				$m1 = $start_month;
				$m2 = 12;

			} elseif ( $year == $end_year ) {

				$m1 = 1;
				$m2 = $end_month;

			} else {

				$m1 = 1;
				$m2 = 12;

			}

			while ( $m1 <= $m2 ) {

				$date1 = mktime( 0, 0, 0, $m1, 1, $year );
				$date2 = mktime( 0, 0, 0, $m1, cal_days_in_month( CAL_GREGORIAN, $m1, $year ), $year );

				$event_status = array_keys( mem_all_event_status() );

				$data[] = array(
					'date'     => date_i18n( 'F Y', $date1 ),
					// 'events' => $stats->get_events_by_date( null, $m1, $year ),
					'earnings' => mem_format_amount( $stats->get_earnings( $m1, $year ) ),
				);

				$m1++;

			}

			$year++;

		}

		$data = apply_filters( 'mem_export_get_data', $data );
		$data = apply_filters( 'mem_export_get_data_' . $this->export_type, $data );

		return $data;

	} // get_data

} // MEM_Earnings_Export
