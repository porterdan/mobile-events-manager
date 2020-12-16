<?php
/**
 * Export Class
 *
 * This is the base class for all export methods. Each data export type (customers, events, transactions, etc) extend this class
 *
 * @package MEM
 * @subpackage Admin/Reports
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 * @taken from Easy Digital Downloads
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_Export Class
 *
 * @since 1.4
 */
class MEM_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'default';

	/**
	 * Event labels.
	 *
	 * @var str
	 * @since 1.4
	 */
	public $event_label_single;
	public $event_label_plural;

	/**
	 * Constructor.
	 *
	 * @since 1.4
	 */
	public function __construct() {
		$this->event_label_single = esc_html( mem_get_label_singular() );
		$this->event_label_plural = esc_html( mem_get_label_plural() );
	} // __construct

	/**
	 * Can we export?
	 *
	 * @access public
	 * @since 1.4
	 * @return bool Whether we can export or not
	 */
	public function can_export() {
		return (bool) apply_filters( 'mem_export_capability', mem_employee_can( 'run_reports' ) );
	} // can_export

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
		header( 'Content-Disposition: attachment; filename=mem-export-' . $this->export_type . '-' . gmdate( 'd-m-Y' ) . '.csv' );
		header( 'Expires: 0' );
	} // headers

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $cols All the columns
	 */
	public function csv_cols() {
		$cols = array(
			'id'   => __( 'ID', 'mobile-events-manager' ),
			'date' => __( 'Date', 'mobile-events-manager' ),
		);
		return $cols;
	} // csv_cols

	/**
	 * Retrieve the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $cols Array of the columns
	 */
	public function get_csv_cols() {
		$cols = $this->csv_cols();
		return apply_filters( 'mem_export_csv_cols_' . $this->export_type, $cols );
	} // get_csv_cols

	/**
	 * Output the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @uses MEM_Export::get_csv_cols()
	 * @return void
	 */
	public function csv_cols_out() {
		$cols = $this->get_csv_cols();
		$i    = 1;
		foreach ( $cols as $col_id => $column ) {
			echo '"' . esc_attr( addslashes( $column ) ) . '"';
			echo count( $cols ) === $i ? '' : ',';
			$i++;
		}
		echo "\r\n";
	} // csv_cols_out

	/**
	 * Get the data being exported
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $data Data for Export
	 */
	public function get_data() {
		// Just a sample data array
		$data = array(
			0 => array(
				'id'   => '',
				'data' => gmdate( 'F j, Y' ),
			),
			1 => array(
				'id'   => '',
				'data' => gmdate( 'F j, Y' ),
			),
		);

		$data = apply_filters( 'mem_export_get_data', $data );
		$data = apply_filters( 'mem_export_get_data_' . $this->export_type, $data );

		return $data;
	} // get_data

	/**
	 * Output the CSV rows
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function csv_rows_out() {
		$data = $this->get_data();

		$cols = $this->get_csv_cols();

		// Output each row
		foreach ( $data as $row ) {
			$i = 1;
			foreach ( $row as $col_id => $column ) {
				// Make sure the column is valid
				if ( array_key_exists( $col_id, $cols ) ) {
					echo '"' . esc_attr( addslashes( $column ) ) . '"';
					echo count( $cols ) === $i ? '' : ',';
					$i++;
				}
			}
			echo "\r\n";
		}
	} // csv_rows_out

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since 1.4
	 * @uses MEM_Export::can_export()
	 * @uses MEM_Export::headers()
	 * @uses MEM_Export::csv_cols_out()
	 * @uses MEM_Export::csv_rows_out()
	 * @return void
	 */
	public function export() {
		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
		}

		// Set headers
		$this->headers();

		// Output CSV columns (headers)
		$this->csv_cols_out();

		// Output CSV rows
		$this->csv_rows_out();

		die();
	} // export

} // MEM_Export
