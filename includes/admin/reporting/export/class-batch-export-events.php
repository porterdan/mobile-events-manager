<?php
/**
 * Batch Events Export Class
 *
 * This class handles events exports
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
 * TMEM_Batch_Export_Events Class
 *
 * @since 1.4
 */
class TMEM_Batch_Export_Events extends TMEM_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'events';

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $cols All the columns
	 */
	public function csv_cols() {

		$cols = array(
			'id'               => __( 'ID', 'mobile-events-manager' ),
			'event_id'         => sprintf( esc_html__( '%s ID', 'mobile-events-manager' ), $this->event_label_single ),
			'date'             => __( 'Date', 'mobile-events-manager' ),
			'status'           => __( 'Status', 'mobile-events-manager' ),
			'client'           => __( 'Client', 'mobile-events-manager' ),
			'primary_employee' => __( 'Primary Employee', 'mobile-events-manager' ),
			'employees'        => __( 'Employees', 'mobile-events-manager' ),
			'package'          => __( 'Package', 'mobile-events-manager' ),
			'addons'           => __( 'Addons', 'mobile-events-manager' ),
			'cost'             => __( 'Price', 'mobile-events-manager' ),
			'deposit'          => __( 'Deposit', 'mobile-events-manager' ),
			'deposit_status'   => __( 'Deposit Status', 'mobile-events-manager' ),
			'balance'          => __( 'Balance', 'mobile-events-manager' ),
			'balance_status'   => __( 'Balance Status', 'mobile-events-manager' ),
			'start_time'       => __( 'Start Time', 'mobile-events-manager' ),
			'end_time'         => __( 'End Time', 'mobile-events-manager' ),
			'end_date'         => __( 'End Date', 'mobile-events-manager' ),
			'setup_date'       => __( 'Setup Date', 'mobile-events-manager' ),
			'setup_time'       => __( 'Setup Time', 'mobile-events-manager' ),
			'duration'         => __( 'Duration', 'mobile-events-manager' ),
			'contract'         => __( 'Contract ID', 'mobile-events-manager' ),
			'contract_status'  => __( 'Contract Status', 'mobile-events-manager' ),
			'playlist_enabled' => __( 'Playlist Enabled', 'mobile-events-manager' ),
			'playlist_status'  => __( 'Playlist Status', 'mobile-events-manager' ),
			'source'           => __( 'Enquiry Source', 'mobile-events-manager' ),
			'converted'        => __( 'Converted', 'mobile-events-manager' ),
			'venue'            => __( 'Venue', 'mobile-events-manager' ),
			'address'          => __( 'Venue Address', 'mobile-events-manager' ),
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

		$data = array();

		// Export all events
		$offset = 30 * ( $this->step - 1 );

		$args = array(
			'post_type'      => 'tmem-event',
			'posts_per_page' => 30,
			'offset'         => $offset,
			'paged'          => $this->step,
			'post_status'    => $this->status,
			'order'          => 'ASC',
			'orderby'        => 'ID',
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {

			$args['meta_query'] = array(
				array(
					'key'     => '_tmem_event_date',
					'value'   => array( gmdate( 'Y-m-d', strtotime( $this->start ) ), gmdate( 'Y-m-d', strtotime( $this->end ) ) ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			);

		}

		$events = get_posts( $args );

		if ( $events ) {

			$i = 0;

			foreach ( $events as $event ) {

				$event_data = tmem_get_event_data( $event->ID );
				$employees  = array();
				$package    = '';
				$addons     = array();

				if ( ! empty( $event_data['client'] ) ) {
					$client = '(' . $event_data['client'] . ') ' . tmem_get_client_display_name( $event_data['client'] );
				}
				if ( ! empty( $event_data['employees']['primary_employee'] ) ) {
					$primary_employee = '(' . $event_data['employees']['primary_employee'] . ') ' . tmem_get_employee_display_name( $event_data['employees']['primary_employee'] );
				}
				if ( ! empty( $event_data['employees']['employees'] ) ) {
					foreach ( $event_data['employees']['employees'] as $employee_id => $employee_data ) {
						$employees[] = '(' . $employee_id . ') ' . tmem_get_employee_display_name( $employee_id );
					}
				}
				if ( ! empty( $event_data['equipment']['package'] ) ) {
					$package = $event_data['equipment']['package'];
				}
				if ( ! empty( $event_data['equipment']['addons'] ) ) {
					foreach ( $event_data['equipment']['addons'] as $addon_id ) {
						$addons[] = tmem_get_addon_name( $addon_id );
					}
				}

				$data[ $i ] = array(
					'id'               => $event->ID,
					'event_id'         => tmem_get_event_contract_id( $event->ID ),
					'date'             => tmem_format_short_date( $event_data['date'] ),
					'status'           => $event_data['status'],
					'client'           => $client,
					'primary_employee' => '(' . $event_data['employees']['primary_employee'] . ') ' . tmem_get_client_display_name( $event_data['employees']['primary_employee'] ),
					'employees'        => implode( ',', $employees ),
					'package'          => $package,
					'addons'           => implode( ', ', $addons ),
					'cost'             => tmem_format_amount( $event_data['cost']['cost'] ),
					'deposit'          => tmem_format_amount( $event_data['cost']['deposit'] ),
					'deposit_status'   => $event_data['cost']['deposit_status'],
					'balance'          => tmem_format_amount( $event_data['cost']['balance'] ),
					'balance_status'   => $event_data['cost']['balance_status'],
					'start_time'       => tmem_format_time( $event_data['start_time'] ),
					'end_time'         => tmem_format_time( $event_data['end_time'] ),
					'end_date'         => tmem_format_short_date( $event_data['end_date'] ),
					'setup_date'       => tmem_format_short_date( $event_data['setup_date'] ),
					'setup_time'       => tmem_format_time( $event_data['setup_time'] ),
					'duration'         => $event_data['duration'],
					'contract'         => $event_data['contract'],
					'contract_status'  => $event_data['contract_status'],
					'playlist_enabled' => $event_data['playlist']['playlist_enabled'],
					'playlist_status'  => $event_data['playlist']['playlist_status'],
					'source'           => $event_data['source'],
					'converted'        => $event_data['contract_status'],
					'venue'            => $event_data['venue']['name'],
					'address'          => ! empty( $event_data['venue']['address'] ) ? implode( ', ', $event_data['venue']['address'] ) : '',
				);

				$i++;

			}

			$data = apply_filters( 'tmem_export_get_data', $data );
			$data = apply_filters( 'tmem_export_get_data_' . $this->export_type, $data );

			return $data;

		}

		return false;
	} // get_data

	/**
	 * Set the count args.
	 *
	 * @since 1.4
	 * @param arr $args The args for the count query
	 * @return arr $args The args for the count query
	 */
	function filter_count_args( $args ) {

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {

			$args['meta_query'] = array(
				array(
					'key'     => '_tmem_event_date',
					'value'   => array( gmdate( 'Y-m-d', strtotime( $this->start ) ), gmdate( 'Y-m-d', strtotime( $this->end ) ) ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			);

		}

		if ( ! empty( $this->status ) ) {
			$args['post_status'] = $this->status;
		}

		return $args;

	} // filter_count_args

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.4
	 * @return int
	 */
	public function get_percentage_complete() {

		add_filter( 'tmem_event_count_args', array( $this, 'filter_count_args' ) );
		$total = tmem_event_count();
		remove_filter( 'tmem_event_count_args', array( $this, 'filter_count_args' ) );

		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	} // get_percentage_complete

	/**
	 * Set the properties specific to the Events export
	 *
	 * @since 1.4
	 * @param arr $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->start  = isset( $request['event_start'] ) ? sanitize_text_field( $request['event_start'] ) : '';
		$this->end    = isset( $request['event_end'] ) ? sanitize_text_field( $request['event_end'] ) : '';
		$this->status = isset( $request['event_status'] ) ? $request['event_status'] : 'any';
		$this->type   = isset( $request['event_type'] ) ? get_term( (int) $request['event_type'], 'event-type' ) : false;
	} // set_properties

} // TMEM_Batch_Export_Events
