<?php
/**
 * Clients Export Class
 *
 * This class handles customer export
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
 * MEM_Clients_Export Class
 *
 * @since 1.4
 */
class MEM_Clients_Export extends MEM_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'clients';

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

		$extra = '';

		if ( ! empty( $_POST['mem_export_event'] ) ) {
			$extra = sanitize_title( get_the_title( absint( $_POST['mem_export_event'] ) ) ) . '-';
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . apply_filters( 'mem_clients_export_filename', 'mem-export-' . $extra . $this->export_type . '-' . gmdate( 'd-m-Y' ) ) . '.csv' );
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
		if ( ! empty( $_POST['mem_export_event'] ) ) {
			$cols = array(
				'first_name' => __( 'First Name', 'mobile-events-manager' ),
				'last_name'  => __( 'Last Name', 'mobile-events-manager' ),
				'email'      => __( 'Email', 'mobile-events-manager' ),
				'date'       => sprintf( esc_html__( '%s Date', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
			);
		} else {

			$cols = array();

			if ( 'emails' != $_POST['mem_export_option'] ) {
				$cols['name'] = __( 'Name', 'mobile-events-manager' );
			}

			$cols['email'] = __( 'Email', 'mobile-events-manager' );

			if ( 'full' == $_POST['mem_export_option'] ) {
				$cols['events'] = sprintf( esc_html__( 'Total %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) );
				$cols['amount'] = __( 'Total Value', 'mobile-events-manager' ) . ' (' . html_entity_decode( mem_currency_filter( '' ) ) . ')';
			}
		}

		return $cols;
	} // csv_cols

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.4
	 * @global obj $wpdb Used to query the database using the WordPress Database API
	 * @return arr $data The data for the CSV file
	 */
	public function get_data() {
		global $wpdb;

		$data = array();

		// Export all clients
		$clients = mem_get_clients();

		$i = 0;

		foreach ( $clients as $client ) {

			if ( 'emails' != $_POST['mem_export_option'] ) {
				$data[ $i ]['name'] = $client->name;
			}

			$data[ $i ]['email'] = $client->email;

			if ( 'full' == $_POST['mem_export_option'] ) {
				$amount               = 0;
				$events               = mem_get_client_events( $client->ID );
				$data[ $i ]['events'] = $events ? count( $events ) : 0;

				if ( $events ) {
					foreach ( $events as $event ) {
						$amount += mem_get_event_price( $event->ID );
					}
				}

				$data[ $i ]['amount'] = mem_format_amount( $amount );

			}
			$i++;
		}

		$data = apply_filters( 'mem_export_get_data', $data );
		$data = apply_filters( 'mem_export_get_data_' . $this->export_type, $data );

		return $data;
	} // get_data

} // MEM_Clients_Export
