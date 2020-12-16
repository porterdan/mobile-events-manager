<?php

/**
 * Events by Type Reports Table Class
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
 * MEM_Types_Reports_Table Class
 *
 * Renders the Event Types Reports table
 *
 * @since 1.4
 */
class MEM_Types_Reports_Table extends WP_List_Table {

	private $label_single;
	private $label_plural;

	/**
	 * Get things started
	 *
	 * @since 1.4
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct(
			array(
				'singular' => esc_html( mem_get_label_singular() ), // Singular name of the listed records
				'plural'   => esc_html( mem_get_label_plural() ), // Plural name of the listed records
				'ajax'     => false, // Does this table support ajax?
			)
		);
		$this->label_single = esc_html( mem_get_label_singular() );
		$this->label_plural = esc_html( mem_get_label_plural() );
	}

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.4
	 * @access protected
	 *
	 * @return str Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'type';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.4
	 *
	 * @param arr $item Contains all the data of the downloads
	 * @param str $column_name The name of the column
	 *
	 * @return str Column Name
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	} // column_default

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'type'           => __( 'Type', 'mobile-events-manager' ),
			'total_events'   => __( 'Total Events', 'mobile-events-manager' ),
			'total_earnings' => __( 'Total Earnings', 'mobile-events-manager' ),
			'avg_earnings'   => __( 'Monthly Earnings Avg', 'mobile-events-manager' ),
		);

		return $columns;
	} // get_columns

	/**
	 * Retrieve the current page number
	 *
	 * @access public
	 * @since 1.4
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	} // get_paged

	/**
	 * Outputs the reporting views
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function extra_tablenav( $which = '' ) {
		if ( 'bottom' === $which ) {
			return;
		}

		mem_report_views();
		mem_reports_graph_controls();
	} // extra_tablenav

	/**
	 * Build all the reports data
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $reports_data All the data for customer reports
	 */
	public function reports_data() {
		$stats = new MEM_Stats();
		$dates = mem_get_report_dates();
		$stats->setup_dates( $dates['range'] );

		$cached_reports = false;
		if ( false !== $cached_reports ) {
			$reports_data = $cached_reports;
		} else {
			$reports_data = array();
			$term_args    = array(
				'parent'       => 0,
				'hierarchical' => 0,
			);

			$categories = get_terms( 'event-types', $term_args );

			foreach ( $categories as $category_id => $category ) {

				$event_count = 0;

				$category_slugs = array( $category->slug );

				$all_event_args = array(
					'post_status' => apply_filters(
						'mem_events_by_type_statuses',
						array( 'mem-contract', 'mem-approved', 'mem-completed' )
					),
					'fields'      => 'ids',
					'tax_query'   => array(
						array(
							'taxonomy' => 'event-types',
							'field'    => 'slug',
							'terms'    => $category_slugs,
						),
					),
					'meta_query'  => array(
						array(
							'key'     => '_mem_event_date',
							'value'   => array( gmdate( 'Y-m-d', $stats->start_date ), gmdate( 'Y-m-d', $stats->end_date ) ),
							'type'    => 'date',
							'compare' => 'BETWEEN',
						),
					),
				);

				$earnings     = 0.00;
				$avg_earnings = 0.00;

				$events = mem_get_events( $all_event_args );

				if ( $events ) {
					foreach ( $events as $event ) {
						$event_count++;

						$mem_event               = new MEM_Event( $event );
						$current_average_earnings = $current_earnings = $mem_event->get_total_profit();

						$received_date = get_post_field( 'post_date', $event );
						$diff          = abs( current_time( 'timestamp' ) - strtotime( $received_date ) );
						$months        = floor( $diff / ( 30 * 60 * 60 * 24 ) ); // Number of months since publication

						if ( $months > 0 ) {
							$current_average_earnings = ( $current_earnings / $months );
							// $current_average_events = ( $events / $months );
						}

						$earnings     += $current_earnings;
						$avg_earnings += $current_average_earnings;
					}
				} else {
					continue;
				}

				$avg_earnings = round( $avg_earnings / $event_count, mem_currency_decimal_filter() );

				$reports_data[] = array(
					'ID'                 => $category->term_id,
					'type'               => $category->name,
					'total_events'       => $event_count,
					'total_earnings'     => mem_currency_filter( mem_format_amount( $earnings ) ),
					'total_earnings_raw' => $earnings,
					'avg_earnings'       => mem_currency_filter( mem_format_amount( $avg_earnings ) ),
					'is_child'           => false,
				);

			}
		}

		return $reports_data;
	} // reports_data

	/**
	 * Output the Sources Events Mix Pie Chart
	 *
	 * @since 1.4
	 * @return str The HTML for the outputted graph
	 */
	public function output_source_graph() {
		if ( empty( $this->items ) ) {
			return;
		}

		$data         = array();
		$total_events = 0;

		foreach ( $this->items as $item ) {
			$total_events += $item['total_events'];

			$data[ $item['type'] ] = $item['total_events'];
		}

		if ( empty( $total_events ) ) {
			echo '<p><em>' . sprintf( esc_html__( 'No %s for dates provided.', 'mobile-events-manager' ), strtolower( $this->label_plural ) ) . '</em></p>';
		}

		// Sort High to Low, prior to filter so people can reorder if they please
		arsort( $data );
		$data = apply_filters( 'mem_types_graph_data', $data );

		$options = apply_filters(
			'mem_types_graph_options',
			array(
				'legend_formatter' => 'memLegendFormatterSources',
			),
			$data
		);

		$pie_graph = new MEM_Pie_Graph( $data, $options );
		$pie_graph->display();
	} // output_source_graph

	/**
	 * Output the Sources Earnings Mix Pie Chart
	 *
	 * @since 1.4
	 * @return str The HTML for the outputted graph
	 */
	public function output_earnings_graph() {
		if ( empty( $this->items ) ) {
			return;
		}

		$data           = array();
		$total_earnings = 0;

		foreach ( $this->items as $item ) {
			$total_earnings += $item['total_earnings_raw'];

			$data[ $item['type'] ] = $item['total_earnings_raw'];

		}

		if ( empty( $total_earnings ) ) {
			echo '<p><em>' . __( 'No earnings for dates provided.', 'mobile-events-manager' ) . '</em></p>';
		}

		// Sort High to Low, prior to filter so people can reorder if they please
		arsort( $data );
		$data = apply_filters( 'mem_types_earnings_graph_data', $data );

		$options = apply_filters(
			'mem_types_earnings_graph_options',
			array(
				'legend_formatter' => 'memLegendFormatterEarnings',
			),
			$data
		);

		$pie_graph = new MEM_Pie_Graph( $data, $options );
		$pie_graph->display();
	} // output_earnings_graph

	/**
	 * The output when no records are found.
	 *
	 * @since 1.4
	 */
	public function no_items() {
		esc_html_e( 'No data to display for this period.', 'mobile-events-manager' );
	} // no_items

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.4
	 * @uses MEM_Conversions_Reports_Table::get_columns()
	 * @uses MEM_Conversions_Reports_Table::get_sortable_columns()
	 * @uses MEM_Conversions_Reports_Table::reports_data()
	 * @return void
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->reports_data();
	} // prepare_items
} // MEM_Types_Reports_Table
