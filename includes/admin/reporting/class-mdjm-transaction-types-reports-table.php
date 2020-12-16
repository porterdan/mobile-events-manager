<?php

/**
 * Conversions by Enquiry Source Reports Table Class
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
 * MEM_Conversions_Reports_Table Class
 *
 * Renders the Conversions Reports table
 *
 * @since 1.4
 */
class MEM_Transaction_Types_Reports_Table extends WP_List_Table {

	private $label_single;
	private $label_plural;
	private $total_txn_count   = 0;
	private $total_txn_income  = 0;
	private $total_txn_expense = 0;
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
				'singular' => __( 'Transaction', 'mobile-events-manager' ), // Singular name of the listed records
				'plural'   => __( 'Transactions', 'mobile-events-manager' ), // Plural name of the listed records
				'ajax'     => false, // Does this table support ajax?
			)
		);
		$this->label_single = esc_html( mem_get_label_singular() );
		$this->label_plural = esc_html( mem_get_label_plural() );

		add_action( 'mem_reports_txn_types_additional_stats', array( $this, 'graph_totals' ) );
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
			'type'               => __( 'Source', 'mobile-events-manager' ),
			'total_transactions' => __( 'Transactions', 'mobile-events-manager' ),
			'total_value'        => __( 'Total Value', 'mobile-events-manager' ),
			'total_income'       => __( 'Total Income', 'mobile-events-manager' ),
			'total_expense'      => __( 'Total Expenses', 'mobile-events-manager' ),
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

			$categories = get_terms( 'transaction-types', $term_args );

			foreach ( $categories as $category_id => $category ) {

				$category_slugs = array( $category->slug );

				$txn_args = array(
					'post_status' => array( 'mem-income', 'mem-expenditure' ),
					'fields'      => 'ids',
					'meta_key'    => '_mem_txn_status',
					'meta_value'  => 'Completed',
					'tax_query'   => array(
						array(
							'taxonomy' => 'transaction-types',
							'field'    => 'slug',
							'terms'    => $category_slugs,
						),
					),
					'date_query'  => array(
						array(
							'after'     => gmdate( 'Y-m-d', $stats->start_date ),
							'before'    => gmdate( 'Y-m-d', $stats->end_date ),
							'inclusive' => true,
						),
					),
				);

				$txn_count     = 0;
				$total_value   = 0;
				$total_income  = 0;
				$total_expense = 0;

				$txns = mem_get_txns( $txn_args );

				if ( $txns ) {
					foreach ( $txns as $txn ) {
						$txn_count++;

						$mem_txn = new MEM_Txn( $txn );

						if ( 'mem-income' == $mem_txn->post_status ) {
							$total_income += $mem_txn->price;
						} else {
							$total_expense += $mem_txn->price;
						}

						$total_value += $mem_txn->price;

					}
				} else {
					continue;
				}

				$reports_data[] = array(
					'ID'                 => $category->term_id,
					'type'               => $category->name,
					'total_transactions' => $txn_count,
					'total_income'       => mem_currency_filter( mem_format_amount( $total_income ) ),
					'total_income_raw'   => $total_income,
					'total_expense'      => mem_currency_filter( mem_format_amount( $total_expense ) ),
					'total_expense_raw'  => $total_expense,
					'total_value'        => mem_currency_filter( mem_format_amount( $total_value ) ),
					'total_value_raw'    => $total_value,
					'is_child'           => false,
				);

				$this->total_txn_count   += $txn_count;
				$this->total_txn_income  += $total_income;
				$this->total_txn_expense += $total_expense;

			}
		}

		return $reports_data;
	} // reports_data

	/**
	 * Output the Transaction Types Mix Pie Chart
	 *
	 * @since 1.4
	 * @return str The HTML for the outputted graph
	 */
	public function output_types_graph() {
		if ( empty( $this->items ) ) {
			return;
		}

		$data       = array();
		$total_txns = 0;

		foreach ( $this->items as $item ) {
			$total_txns += $item['total_transactions'];

			$data[ $item['type'] ] = $item['total_transactions'];
		}

		if ( empty( $total_txns ) ) {
			echo '<p><em>' . __( 'No transactions for dates provided.', 'mobile-events-manager' ) . '</em></p>';
		}

		// Sort High to Low, prior to filter so people can reorder if they please
		arsort( $data );
		$data = apply_filters( 'mem_txn_types_graph_data', $data );

		$options = apply_filters(
			'mem_txn_types_graph_options',
			array(
				'legend_formatter' => 'memLegendFormatterSources',
			),
			$data
		);

		$pie_graph = new MEM_Pie_Graph( $data, $options );
		$pie_graph->display();
	} // output_types_graph

	/**
	 * Output the Sources Earnings Mix Pie Chart
	 *
	 * @since 1.4
	 * @return str The HTML for the outputted graph
	 */
	public function output_values_graph() {
		if ( empty( $this->items ) ) {
			return;
		}

		$data        = array();
		$total_value = 0;

		foreach ( $this->items as $item ) {
			$total_value += $item['total_value_raw'];

			$data[ $item['type'] ] = $item['total_value_raw'];

		}

		if ( empty( $total_value ) ) {
			echo '<p><em>' . __( 'No transactions for dates provided.', 'mobile-events-manager' ) . '</em></p>';
		}

		// Sort High to Low, prior to filter so people can reorder if they please
		arsort( $data );
		$data = apply_filters( 'mem_txn_value_graph_data', $data );

		$options = apply_filters(
			'mem_txn_value_graph_options',
			array(
				'legend_formatter' => 'memLegendFormatterEarnings',
			),
			$data
		);

		$pie_graph = new MEM_Pie_Graph( $data, $options );
		$pie_graph->display();
	} // output_values_graph

	/**
	 * The output when no records are found.
	 *
	 * @since 1.4
	 */
	public function no_items() {
		esc_html_e( 'No data to display for this period.', 'mobile-events-manager' );
	} // no_items

	/**
	 * Display graph totals.
	 *
	 * @since 1.4
	 */
	public function graph_totals() {
		?>
		<p class="mem_graph_totals">
			<strong>
				<?php
					esc_html_e( 'Total transactions for period shown: ', 'mobile-events-manager' );
					echo $this->total_txn_count;
				?>
			</strong>
		</p>
		<p class="mem_graph_totals">
			<strong>
				<?php
					esc_html_e( 'Income for period shown: ', 'mobile-events-manager' );
					echo mem_currency_filter( mem_format_amount( $this->total_txn_income ) );
				?>
			</strong>
		</p>
		<p class="mem_graph_totals">
			<strong>
				<?php
					esc_html_e( 'Expenses for period shown: ', 'mobile-events-manager' );
					echo mem_currency_filter( mem_format_amount( $this->total_txn_expense ) );
				?>
			</strong>
		</p>
		<p class="mem_graph_totals">
			<strong>
				<?php
					esc_html_e( 'Earnings for period shown: ', 'mobile-events-manager' );
					echo mem_currency_filter( mem_format_amount( $this->total_txn_income - $this->total_txn_expense ) );
				?>
			</strong>
		</p>
		<p class="mem_graph_totals">
			<strong>
				<?php
					esc_html_e( 'Total turnover for period shown: ', 'mobile-events-manager' );
					echo mem_currency_filter( mem_format_amount( $this->total_txn_income + $this->total_txn_expense ) );
				?>
			</strong>
		</p>
		<?php
	} // graph_totals

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
} // MEM_Transaction_Types_Reports_Table
