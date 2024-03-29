<?php
/**
 * Batch Export Class
 *
 * This is the base class for all batch export methods. Each data export type (clients, txnss, etc) extend this class
 *
 * @package TMEM
 * @subpackage Admin/Export
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMEM_Batch_Export Class
 *
 * @since 1.4
 */
class TMEM_Batch_Export extends TMEM_Export {

	/**
	 * The file the data is stored in
	 *
	 * @since 1.4
	 */
	private $file;

	/**
	 * The name of the file the data is stored in
	 *
	 * @since 1.4
	 */
	public $filename;

	/**
	 * The file type, typically .csv
	 *
	 * @since 1.4
	 */
	public $filetype;

	/**
	 * The current step being processed
	 *
	 * @since 1.4
	 */
	public $step;

	/**
	 * Start date, Y-m-d H:i:s
	 *
	 * @since 1.4
	 */
	public $start;

	/**
	 * End date, Y-m-d H:i:s
	 *
	 * @since 1.4
	 */
	public $end;

	/**
	 * Status to export
	 *
	 * @since 1.4
	 */
	public $status;

	/**
	 * Type to export
	 *
	 * @since 1.4
	 */
	public $type;

	/**
	 * Event to export data for
	 *
	 * @since 1.4
	 */
	public $event = null;

	/**
	 * Event Transaction ID to export data for
	 *
	 * @since 1.4
	 */
	public $txn_id = null;

	/**
	 * Is the export file writable
	 *
	 * @since 1.4
	 */
	public $is_writable = true;

	/**
	 * Is the export file empty
	 *
	 * @since 1.4
	 */
	public $is_empty = false;

	/**
	 * Get things started
	 *
	 * @param $_step int The step to process
	 * @since 1.4
	 */
	public function __construct( $_step = 1 ) {

		$upload_dir     = wp_upload_dir();
		$this->filetype = '.csv';
		$this->filename = 'tmem-' . $this->export_type . $this->filetype;
		$this->file     = trailingslashit( $upload_dir['basedir'] ) . $this->filename;

		if ( ! is_writeable( $upload_dir['basedir'] ) ) {
			$this->is_writable = false;
		}

		$this->step = $_step;
		$this->done = false;
	} // __construct

	/**
	 * Process a step
	 *
	 * @since 1.4
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
		}

		if ( $this->step < 2 ) {

			// Make sure we start with a fresh file on step 1
			@unlink( $this->file );
			$this->print_csv_cols();
		}

		$rows = $this->print_csv_rows();

		if ( $rows ) {
			return true;
		} else {
			return false;
		}
	} // process_step

	/**
	 * Output the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @uses TMEM_Export::get_csv_cols()
	 * @return str
	 */
	public function print_csv_cols() {

		$col_data = '';
		$cols     = $this->get_csv_cols();
		$i        = 1;
		foreach ( $cols as $col_id => $column ) {
			$col_data .= '"' . addslashes( $column ) . '"';
			$col_data .= count( $cols ) === $i ? '' : ',';
			$i++;
		}
		$col_data .= "\r\n";

		$this->stash_step_data( $col_data );

		return $col_data;

	} // print_csv_cols

	/**
	 * Print the CSV rows for the current step
	 *
	 * @access public
	 * @since 1.4
	 * @return str|false
	 */
	public function print_csv_rows() {

		$row_data = '';
		$data     = $this->get_data();
		$cols     = $this->get_csv_cols();

		if ( $data ) {

			// Output each row
			foreach ( $data as $row ) {
				$i = 1;
				foreach ( $row as $col_id => $column ) {
					if ( is_array( $column ) ) {
						error_log( $col_id . ' - ' . var_export( $column, true ), 0 );
					}
					// Make sure the column is valid
					if ( array_key_exists( $col_id, $cols ) ) {
						$row_data .= '"' . addslashes( preg_replace( '/"/', "'", $column ) ) . '"';
						$row_data .= count( $cols ) === $i ? '' : ',';
						$i++;
					}
				}
				$row_data .= "\r\n";
			}

			$this->stash_step_data( $row_data );

			return $row_data;
		}

		return false;
	} // print_csv_rows

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.4
	 * @return int
	 */
	public function get_percentage_complete() {
		return 100;
	} // get_percentage_complete

	/**
	 * Retrieve the file data is written to
	 *
	 * @since 1.4
	 * @return str
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	} // get_file

	/**
	 * Append data to export file
	 *
	 * @since 1.4
	 * @param str $data The data to add to the file
	 * @return void
	 */
	protected function stash_step_data( $data = '' ) {

		$file  = $this->get_file();
		$file .= $data;
		@file_put_contents( $this->file, $file );

		// If we have no rows after this step, mark it as an empty export
		$file_rows    = file( $this->file, FILE_SKIP_EMPTY_LINES );
		$default_cols = $this->get_csv_cols();
		$default_cols = empty( $default_cols ) ? 0 : 1;

		$this->is_empty = count( $file_rows ) == $default_cols ? true : false;

	} // stash_step_data

	/**
	 * Perform the export
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		$file = $this->get_file();

		@unlink( $this->file );

		echo $file;

		die();
	} // export

	/*
	 * Set the properties specific to the export
	 *
	 * @since	1.4
	 * @param	arr		$request	The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {}

	/**
	 * Allow for prefetching of data for the remainder of the exporter
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function pre_fetch() {}

} // TMEM_Batch_Export
