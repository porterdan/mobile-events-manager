<?php
/**
 * Batch Clients Export Class
 *
 * This class handles client export
 *
 * @package TMEM
 * @subpackage Admin/Reports
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMEM_Batch_Export_Clients Class
 *
 * @since 1.4
 */
class TMEM_Batch_Export_Clients extends TMEM_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'clients';

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $cols All the columns
	 */
	public function csv_cols() {

		$cols = array(
			'id'     => __( 'ID', 'mobile-events-manager' ),
			'name'   => __( 'Name', 'mobile-events-manager' ),
			'email'  => __( 'Email', 'mobile-events-manager' ),
			'events' => sprintf( esc_html__( 'Number of %s', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ),
			'amount' => __( 'Client Value', 'mobile-events-manager' ),
		);

		return $cols;
	} // csv_cols

	/**
	 * Filter the get_client args.
	 *
	 * @since 1.4
	 * @param arr $args Args passed to get_users query.
	 * @return arr $args Args passed to get_users query.
	 */
	public function filter_args( $args ) {
		$offset         = 30 * ( $this->step - 1 );
		$args['number'] = 30;
		$args['offset'] = $offset;
		$args['paged']  = $this->step;

		return $args;
	} // filter_args

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $data The data for the CSV file
	 */
	public function get_data() {

		$data = array();

		// Export all clients
		add_filter( 'tmem_get_clients_args', array( $this, 'filter_args' ) );
		$clients = tmem_get_clients();
		remove_filter( 'tmem_get_clients_args', array( $this, 'filter_args' ) );

		$i = 0;

		foreach ( $clients as $client ) {

			$events = tmem_get_client_events( $client->ID );
			$amount = 0;

			$data[ $i ]['id']     = $client->ID;
			$data[ $i ]['name']   = $client->display_name;
			$data[ $i ]['email']  = $client->user_email;
			$data[ $i ]['events'] = $events ? count( $events ) : 0;

			if ( $events ) {
				foreach ( $events as $event ) {
					$amount += tmem_get_event_price( $event->ID );
				}
			}

			$data[ $i ]['amount'] = tmem_format_amount( $amount );

			$i++;
		}

		$data = apply_filters( 'tmem_export_get_data', $data );
		$data = apply_filters( 'tmem_export_get_data_' . $this->export_type, $data );

		return $data;
	} // get_data

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.4
	 * @return int
	 */
	public function get_percentage_complete() {

		$percentage = 0;

		// We can't count the number when getting them for a specific download
		$total = tmem_client_count();

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	} // get_percentage_complete

	/**
	 * Set the properties specific to the Clients export
	 *
	 * @since 1.4
	 * @param arr $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->start = isset( $request['start'] ) ? sanitize_text_field( $request['start'] ) : '';
		$this->end   = isset( $request['end'] ) ? sanitize_text_field( $request['end'] ) : '';
	} // set_properties

} // TMEM_Batch_Export_Clients
