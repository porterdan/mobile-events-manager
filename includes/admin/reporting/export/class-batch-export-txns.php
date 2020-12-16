<?php
/**
 * Batch Transactions Export Class
 *
 * This class handles transaction exports
 *
 * @package MEM
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
 * MEM_Batch_Export_Txns Class
 *
 * @since 1.4
 */
class MEM_Batch_Export_Txns extends MEM_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var str
	 * @since 1.4
	 */
	public $export_type = 'transactions';

	/**
	 * Set the CSV columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $cols All the columns
	 */
	public function csv_cols() {

		$cols = array(
			'id'      => __( 'ID', 'mobile-events-manager' ),
			'date'    => __( 'Date', 'mobile-events-manager' ),
			'status'  => __( 'Status', 'mobile-events-manager' ),
			'income'  => __( 'Income', 'mobile-events-manager' ),
			'expense' => __( 'Expense', 'mobile-events-manager' ),
			'to_from' => __( 'To / From', 'mobile-events-manager' ),
			'type'    => __( 'Type', 'mobile-events-manager' ),
			'event'   => esc_html( mem_get_label_singular() ),
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

		// Export all transactions
		$offset = 30 * ( $this->step - 1 );

		$txn_args = array(
			'post_type'      => 'mem-transaction',
			'posts_per_page' => 30,
			'offset'         => $offset,
			'paged'          => $this->step,
			'post_status'    => array( 'mem-income', 'mem-expenditure' ),
			'order'          => 'ASC',
			'orderby'        => 'date',
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {

			$txn_args['date_query'] = array(
				array(
					'after'     => gmdate( 'Y-n-d 00:00:00', strtotime( $this->start ) ),
					'before'    => gmdate( 'Y-n-d 23:59:59', strtotime( $this->end ) ),
					'inclusive' => true,
				),
			);

		}

		if ( ! empty( $this->status ) && is_array( $this->status ) ) {
			$meta_query = array();

			foreach ( $this->status as $txn_status ) {
				$meta_query[] = array(
					'key'   => '_mem_txn_status',
					'value' => $txn_status,
				);
			}
			$txn_args['meta_query'] = array(
				'relation' => 'OR',
				$meta_query,
			);
		}

		$all_txns = get_posts( $txn_args );

		if ( $all_txns ) {

			$i       = 0;
			$income  = 0;
			$expense = 0;

			foreach ( $all_txns as $txn ) {

				$mem_txn = new MEM_Txn( $txn->ID );

				$data[ $i ]['id']      = $mem_txn->ID;
				$data[ $i ]['date']    = gmdate( 'd-M-Y', strtotime( $mem_txn->post_date ) );
				$data[ $i ]['status']  = $mem_txn->payment_status;
				$data[ $i ]['income']  = 'mem-income' == $mem_txn->post_status ? mem_format_amount( $mem_txn->price ) : '';
				$data[ $i ]['expense'] = 'mem-expenditure' == $mem_txn->post_status ? mem_format_amount( $mem_txn->price ) : '';
				$data[ $i ]['to_from'] = mem_get_txn_recipient_name( $mem_txn->ID );
				$data[ $i ]['type']    = $mem_txn->get_type();
				$data[ $i ]['source']  = $mem_txn->get_method();
				$data[ $i ]['gateway'] = $mem_txn->get_gateway();
				$data[ $i ]['event']   = ! empty( $mem_txn->post_parent ) ? mem_get_event_contract_id( $mem_txn->post_parent ) : '';

				if ( 'mem-income' == $mem_txn->post_status ) {
					$income += $mem_txn->price;
				} else {
					$expense += $mem_txn->price;
				}

				$i++;

			}

			$data = apply_filters( 'mem_export_get_data', $data );
			$data = apply_filters( 'mem_export_get_data_' . $this->export_type, $data );

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

			$args['date_query'] = array(
				array(
					'after'     => gmdate( 'Y-n-d 00:00:00', strtotime( $this->start ) ),
					'before'    => gmdate( 'Y-n-d 23:59:59', strtotime( $this->end ) ),
					'inclusive' => true,
				),
			);

		}

		if ( ! empty( $this->status ) && is_array( $this->status ) ) {
			$meta_query = array();

			foreach ( $this->status as $txn_status ) {
				$meta_query[] = array(
					'key'   => '_mem_txn_status',
					'value' => $txn_status,
				);
			}
			$args['meta_query'] = array(
				'relation' => 'OR',
				$meta_query,
			);
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

		add_filter( 'mem_txn_count_args', array( $this, 'filter_count_args' ) );
		$total = mem_txn_count();
		remove_filter( 'mem_txn_count_args', array( $this, 'filter_count_args' ) );

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
	 * Set the properties specific to the Clients export
	 *
	 * @since 1.4
	 * @param arr $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->start  = isset( $request['txn_start'] ) ? sanitize_text_field( $request['txn_start'] ) : '';
		$this->end    = isset( $request['txn_end'] ) ? sanitize_text_field( $request['txn_end'] ) : '';
		$this->status = ! empty( $request['txn_status'] ) ? array( sanitize_text_field( $request['txn_status'] ) ) : false;
	} // set_properties

} // MEM_Batch_Export_Txns
