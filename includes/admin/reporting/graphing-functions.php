<?php
/**
 * Graphing Functions
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
 * Show report graphs for earnings.
 *
 * @since 1.4
 * @return void
 */
function tmem_earnings_reports_graph() {
	// Retrieve the queried dates
	$dates = tmem_get_report_dates();

	$stats = new TMEM_Stats();

	// Determine graph options
	switch ( $dates['range'] ) :
		case 'today':
		case 'yesterday':
			$day_by_day = true;
			break;
		case 'last_year':
		case 'this_year':
			$day_by_day = false;
			break;
		case 'last_quarter':
		case 'this_quarter':
			$day_by_day = true;
			break;
		case 'other':
			if ( $dates['m_end'] - $dates['m_start'] >= 3 || ( $dates['year_end'] > $dates['year'] && ( $dates['m_start'] - $dates['m_end'] ) != 10 ) ) {
				$day_by_day = false;
			} else {
				$day_by_day = true;
			}
			break;
		default:
			$day_by_day = true;
			break;
	endswitch;

	$earnings_totals = 0.00; // Total earnings for time period shown
	$events_totals   = 0; // Total events for time period shown

	if ( 'today' === $dates['range'] || 'yesterday' === $dates['range'] ) {
		// Hour by hour
		$hour  = 1;
		$month = $dates['m_start'];
		while ( $hour <= 23 ) {
			$events   = $stats->get_events_by_date( $dates['day'], $month, $dates['year'], $hour );
			$earnings = $stats->get_earnings_by_date( $dates['day'], $month, $dates['year'], $hour );

			$events_totals   += $events;
			$earnings_totals += $earnings;

			$date = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;

			$events_data[]   = array( $date, $events );
			$earnings_data[] = array( $date, $earnings );

			$hour++;
		}
	} elseif ( 'this_week' === $dates['range'] || 'last_week' === $dates['range'] ) {

		$num_of_days = cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] );

		$report_dates = array();
		$i            = 0;
		while ( $i <= 6 ) {

			if ( ( $dates['day'] + $i ) <= $num_of_days ) {
				$report_dates[ $i ] = array(
					'day'   => (string) $dates['day'] + $i,
					'month' => $dates['m_start'],
					'year'  => $dates['year'],
				);
			} else {
				$report_dates[ $i ] = array(
					'day'   => (string) $i,
					'month' => $dates['m_end'],
					'year'  => $dates['year_end'],
				);
			}

			$i++;
		}

		foreach ( $report_dates as $report_date ) {
			$events         = $stats->get_events_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
			$events_totals += $events;

			$earnings         = $stats->get_earnings_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
			$earnings_totals += $earnings;

			$date            = mktime( 0, 0, 0, $report_date['month'], $report_date['day'], $report_date['year'] ) * 1000;
			$events_data[]   = array( $date, $events );
			$earnings_data[] = array( $date, $earnings );
		}
	} else {

		$y         = $dates['year'];
		$temp_data = array(
			'events'   => array(),
			'earnings' => array(),
		);

		while ( $y <= $dates['year_end'] ) {

			$last_year = false;

			if ( $dates['year'] == $dates['year_end'] ) {
				$month_start = $dates['m_start'];
				$month_end   = $dates['m_end'];
				$last_year   = true;
			} elseif ( $y == $dates['year'] ) {
				$month_start = $dates['m_start'];
				$month_end   = 12;
			} elseif ( $y == $dates['year_end'] ) {
				$month_start = 1;
				$month_end   = $dates['m_end'];
			} else {
				$month_start = 1;
				$month_end   = 12;
			}

			$i = $month_start;
			while ( $i <= $month_end ) {

				$d = $dates['day'];

				if ( $i == $month_end ) {

					$num_of_days = $dates['day_end'];

					if ( $month_start < $month_end ) {

						$d = 1;

					}
				} else {

					$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

				}

				while ( $d <= $num_of_days ) {

					$earnings         = $stats->get_earnings_by_date( $d, $i, $y );
					$earnings_totals += $earnings;

					$events         = $stats->get_events_by_date( $d, $i, $y );
					$events_totals += $events;

					$temp_data['earnings'][ $y ][ $i ][ $d ] = $earnings;
					$temp_data['events'][ $y ][ $i ][ $d ]   = $events;

					$d++;

				}

				$i++;

			}

			$y++;
		}

		$events_data   = array();
		$earnings_data = array();

		// When using 3 months or smaller as the custom range, show each day individually on the graph
		if ( $day_by_day ) {

			foreach ( $temp_data['events'] as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $events ) {
						$date          = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$events_data[] = array( $date, $events );
					}
				}
			}

			foreach ( $temp_data['earnings'] as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $earnings ) {
						$date            = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$earnings_data[] = array( $date, $earnings );
					}
				}
			}

			// When showing more than 3 months of results, group them by month, by the first (except for the last month, group on the last day of the month selected)
		} else {

			foreach ( $temp_data['events'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$events        = array_sum( $days );
					$date          = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
					$events_data[] = array( $date, $events );

				}
			}

			foreach ( $temp_data['earnings'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$earnings        = array_sum( $days );
					$date            = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
					$earnings_data[] = array( $date, $earnings );

				}
			}
		}
	}

	$data = array(
		__( 'Earnings', 'mobile-events-manager' ) => $earnings_data,
		esc_html( tmem_get_label_plural() )   => $events_data,
	);

	// start our own output buffer
	ob_start();
	?>
	<div id="tmem-dashboard-widgets-wrap">
		<div class="metabox-holder" style="padding-top: 0;">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Earnings Over Time', 'mobile-events-manager' ); ?></span></h3>

				<div class="inside">
					<?php
					tmem_reports_graph_controls();
					$graph = new TMEM_Graph( $data );
					$graph->set( 'x_mode', 'time' );
					$graph->set( 'multiple_y_axes', true );
					$graph->display();

					?>

					<p class="tmem_graph_totals">
						<strong>
							<?php
								esc_html_e( 'Total earnings for period shown: ', 'mobile-events-manager' );
								echo tmem_currency_filter( tmem_format_amount( $earnings_totals ) );
							?>
						</strong>
					</p>
					<p class="tmem_graph_totals">
						<strong>
							<?php
								printf(
									__( 'Total %s for period shown: ', 'mobile-events-manager' ),
									tmem_get_label_plural( true )
								);
								echo $events_totals;
							?>
						</strong>
					</p>

					<?php do_action( 'tmem_reports_earnings_graph_additional_stats' ); ?>

					<p class="tmem-graph-notes">
						<span>
							<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s taking place within the date period selected.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
						</span>
					</p>

				</div>
			</div>
		</div>
	</div>
	<?php
	// get output buffer contents and end our own buffer
	$output = ob_get_contents();
	ob_end_clean();

	echo $output;
} // tmem_earnings_reports_graph

/**
 * Show report graphs for earnings.
 *
 * @since 1.4
 * @return void
 */
function tmem_transactions_reports_graph() {
	// Retrieve the queried dates
	$dates = tmem_get_report_dates();

	$stats = new TMEM_Stats();

	// Determine graph options
	switch ( $dates['range'] ) :
		case 'today':
		case 'yesterday':
			$day_by_day = true;
			break;
		case 'last_year':
		case 'this_year':
			$day_by_day = false;
			break;
		case 'last_quarter':
		case 'this_quarter':
			$day_by_day = true;
			break;
		case 'other':
			if ( $dates['m_end'] - $dates['m_start'] >= 3 || ( $dates['year_end'] > $dates['year'] && ( $dates['m_start'] - $dates['m_end'] ) != 10 ) ) {
				$day_by_day = false;
			} else {
				$day_by_day = true;
			}
			break;
		default:
			$day_by_day = true;
			break;
	endswitch;

	$income_totals  = 0.00; // Total income for time period shown
	$expense_totals = 0.00; // Total expense for time period shown
	$events_totals  = 0; // Total events for the time period shown

	if ( 'today' === $dates['range'] || 'yesterday' === $dates['range'] ) {
		// Hour by hour
		$hour  = 1;
		$month = $dates['m_start'];
		while ( $hour <= 23 ) {
			$income  = $stats->get_income_by_date( $dates['day'], $month, $dates['year'], $hour );
			$expense = $stats->get_expenses_by_date( $dates['day'], $month, $dates['year'], $hour );
			$events  = $stats->get_events_by_date( $dates['day'], $month, $dates['year'], $hour );

			$income_totals  += $income;
			$expense_totals += $expense;
			$events_totals  += $events;

			$date = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;

			$income_data[]  = array( $date, $income );
			$expense_data[] = array( $date, $expense );
			$events_data[]  = array( $date, $events );

			$hour++;
		}
	} elseif ( 'this_week' === $dates['range'] || 'last_week' === $dates['range'] ) {

		$num_of_days = cal_days_in_month( CAL_GREGORIAN, $dates['m_start'], $dates['year'] );

		$report_dates = array();
		$i            = 0;
		while ( $i <= 6 ) {

			if ( ( $dates['day'] + $i ) <= $num_of_days ) {
				$report_dates[ $i ] = array(
					'day'   => (string) $dates['day'] + $i,
					'month' => $dates['m_start'],
					'year'  => $dates['year'],
				);
			} else {
				$report_dates[ $i ] = array(
					'day'   => (string) $i,
					'month' => $dates['m_end'],
					'year'  => $dates['year_end'],
				);
			}

			$i++;
		}

		foreach ( $report_dates as $report_date ) {
			$income         = $stats->get_income_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
			$income_totals += $income;

			$expense         = $stats->get_expenses_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
			$expense_totals += $expense;

			$events         = $stats->get_events_by_date( $report_date['day'], $report_date['month'], $report_date['year'] );
			$events_totals += $events;

			$date           = mktime( 0, 0, 0, $report_date['month'], $report_date['day'], $report_date['year'] ) * 1000;
			$income_data[]  = array( $date, $income );
			$expense_data[] = array( $date, $expense );
			$events_data[]  = array( $date, $events );
		}
	} else {

		$y         = $dates['year'];
		$temp_data = array(
			'income'  => array(),
			'expense' => array(),
		);

		while ( $y <= $dates['year_end'] ) {

			$last_year = false;

			if ( $dates['year'] == $dates['year_end'] ) {
				$month_start = $dates['m_start'];
				$month_end   = $dates['m_end'];
				$last_year   = true;
			} elseif ( $y == $dates['year'] ) {
				$month_start = $dates['m_start'];
				$month_end   = 12;
			} elseif ( $y == $dates['year_end'] ) {
				$month_start = 1;
				$month_end   = $dates['m_end'];
			} else {
				$month_start = 1;
				$month_end   = 12;
			}

			$i = $month_start;
			while ( $i <= $month_end ) {

				$d = $dates['day'];

				if ( $i == $month_end ) {

					$num_of_days = $dates['day_end'];

					if ( $month_start < $month_end ) {

						$d = 1;

					}
				} else {

					$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

				}

				while ( $d <= $num_of_days ) {

					$income         = $stats->get_income_by_date( $d, $i, $y );
					$income_totals += $income;

					$expense         = $stats->get_expenses_by_date( $d, $i, $y );
					$expense_totals += $expense;

					$events         = $stats->get_events_by_date( $d, $i, $y );
					$events_totals += $events;

					$temp_data['income'][ $y ][ $i ][ $d ]  = $income;
					$temp_data['expense'][ $y ][ $i ][ $d ] = $expense;
					$temp_data['events'][ $y ][ $i ][ $d ]  = $events;

					$d++;

				}

				$i++;

			}

			$y++;
		}

		$income_data  = array();
		$expense_data = array();

		// When using 3 months or smaller as the custom range, show each day individually on the graph
		if ( $day_by_day ) {

			foreach ( $temp_data['income'] as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $income ) {
						$date          = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$income_data[] = array( $date, $income );
					}
				}
			}

			foreach ( $temp_data['expense'] as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $expense ) {
						$date           = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$expense_data[] = array( $date, $expense );
					}
				}
			}

			foreach ( $temp_data['events'] as $year => $months ) {
				foreach ( $months as $month => $days ) {
					foreach ( $days as $day => $events ) {
						$date          = mktime( 0, 0, 0, $month, $day, $year ) * 1000;
						$events_data[] = array( $date, $events );
					}
				}
			}

			// When showing more than 3 months of results, group them by month, by the first (except for the last month, group on the last day of the month selected)
		} else {

			foreach ( $temp_data['income'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$income        = array_sum( $days );
					$date          = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
					$income_data[] = array( $date, $income );

				}
			}

			foreach ( $temp_data['expense'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$expense        = array_sum( $days );
					$date           = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
					$expense_data[] = array( $date, $expense );

				}
			}

			foreach ( $temp_data['events'] as $year => $months ) {

				$month_keys = array_keys( $months );
				$last_month = end( $month_keys );

				foreach ( $months as $month => $days ) {

					$day_keys = array_keys( $days );
					$last_day = end( $day_keys );

					$consolidated_date = $month === $last_month ? $last_day : 1;

					$events        = array_sum( $days );
					$date          = mktime( 0, 0, 0, $month, $consolidated_date, $year ) * 1000;
					$events_data[] = array( $date, $events );

				}
			}
		}
	}

	$data = array(
		__( 'Income', 'mobile-events-manager' )  => $income_data,
		__( 'Expense', 'mobile-events-manager' ) => $expense_data,
		esc_html( tmem_get_label_plural() )  => $events_data,
	);

	// start our own output buffer
	ob_start();
	?>
	<div id="tmem-dashboard-widgets-wrap">
		<div class="metabox-holder" style="padding-top: 0;">
			<div class="postbox">
				<h3><span><?php esc_html_e( 'Transactions Over Time', 'mobile-events-manager' ); ?></span></h3>

				<div class="inside">
					<?php
					tmem_reports_graph_controls();
					$graph = new TMEM_Graph( $data );
					$graph->set( 'x_mode', 'time' );
					$graph->set( 'multiple_y_axes', false );
					$graph->display();

					?>

					<p class="tmem_graph_totals">
						<strong>
							<?php
								esc_html_e( 'Total income for period shown: ', 'mobile-events-manager' );
								echo tmem_currency_filter( tmem_format_amount( $income_totals ) );
							?>
						</strong>
					</p>
					<p class="tmem_graph_totals">
						<strong>
							<?php
								esc_html_e( 'Total expense for period shown: ', 'mobile-events-manager' );
								echo tmem_currency_filter( tmem_format_amount( $expense_totals ) );
							?>
						</strong>
					</p>
					<p class="tmem_graph_totals">
						<strong>
							<?php
								esc_html_e( 'Total earnings for period shown: ', 'mobile-events-manager' );
								echo tmem_currency_filter( tmem_format_amount( $income_totals - $expense_totals ) );
							?>
						</strong>
					</p>
					<p class="tmem_graph_totals">
						<strong>
							<?php
								printf(
									__( 'Total %s for period shown: ', 'mobile-events-manager' ),
									esc_html( tmem_get_label_plural() )
								);
								echo $events_totals;
							?>
						</strong>
					</p>

					<?php do_action( 'tmem_reports_transactions_graph_additional_stats' ); ?>

					<p class="tmem-graph-notes">
						<span>
							<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s taking place within the date period selected.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
						</span>
					</p>

				</div>
			</div>
		</div>
	</div>
	<?php
	// get output buffer contents and end our own buffer
	$output = ob_get_contents();
	ob_end_clean();

	echo $output;
} // tmem_transactions_reports_graph

/**
 * Show report graph date filters
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_graph_controls() {
	$date_options = apply_filters(
		'tmem_report_date_options',
		array(
			// 'today' => __( 'Today', 'mobile-events-manager' ),
			// 'yesterday' => __( 'Yesterday', 'mobile-events-manager' ),
			'this_week'    => __( 'This Week', 'mobile-events-manager' ),
			'last_week'    => __( 'Last Week', 'mobile-events-manager' ),
			'this_month'   => __( 'This Month', 'mobile-events-manager' ),
			'last_month'   => __( 'Last Month', 'mobile-events-manager' ),
			'this_quarter' => __( 'This Quarter', 'mobile-events-manager' ),
			'last_quarter' => __( 'Last Quarter', 'mobile-events-manager' ),
			'this_year'    => __( 'This Year', 'mobile-events-manager' ),
			'last_year'    => __( 'Last Year', 'mobile-events-manager' ),
			'other'        => __( 'Custom', 'mobile-events-manager' ),
		)
	);

	$dates   = tmem_get_report_dates();
	$display = 'other' === $dates['range'] ? '' : 'style="display:none;"';
	$view    = tmem_get_reporting_view();
	$taxes   = ! empty( $_GET['exclude_taxes'] ) ? false : true;

	if ( empty( $dates['day_end'] ) ) {
		$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, gmdate( 'n' ), gmdate( 'Y' ) );
	}

	?>
	<form id="tmem-graphs-filter" method="get">
		<div class="tablenav top">
			<div class="alignleft actions">

				<input type="hidden" name="post_type" value="tmem-event"/>
				<input type="hidden" name="page" value="tmem-reports"/>
				<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>"/>

				<?php if ( isset( $_GET['event-id'] ) ) : ?>
					<input type="hidden" name="event-id" value="<?php echo absint( $_GET['event-id'] ); ?>"/>
				<?php endif; ?>

				<select id="tmem-graphs-date-options" name="range">
				<?php foreach ( $date_options as $key => $option ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, $dates['range'] ); ?>><?php echo esc_html( $option ); ?></option>
					<?php endforeach; ?>
				</select>

				<div id="tmem-date-range-options" <?php echo $display; ?>>
					<span><?php esc_html_e( 'From', 'mobile-events-manager' ); ?>&nbsp;</span>
					<select id="tmem-graphs-month-start" name="m_start">
						<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_start'] ); ?>><?php echo tmem_month_num_to_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="tmem-graphs-day-start" name="day">
						<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<select id="tmem-graphs-year-start" name="year">
						<?php for ( $i = 2007; $i <= gmdate( 'Y' ); $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<span><?php esc_html_e( 'To', 'mobile-events-manager' ); ?>&nbsp;</span>
					<select id="tmem-graphs-month-end" name="m_end">
						<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_end'] ); ?>><?php echo tmem_month_num_to_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="tmem-graphs-day-end" name="day_end">
						<?php for ( $i = 1; $i <= 31; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['day_end'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<select id="tmem-graphs-year-end" name="year_end">
						<?php for ( $i = 2007; $i <= gmdate( 'Y' ); $i++ ) : ?>
						<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year_end'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
				</div>

				<div class="tmem-graph-filter-submit graph-option-section">
					<input type="hidden" name="tmem-action" value="filter_reports" />
					<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Filter', 'mobile-events-manager' ); ?>"/>
				</div>
			</div>
		</div>
	</form>
	<?php
} // tmem_reports_graph_controls

/**
 * Sets up the dates used to filter graph data
 *
 * Date sent via $_GET is read first and then modified (if needed) to match the
 * selected date-range (if any)
 *
 * @since 1.4
 * @return arr
 */
function tmem_get_report_dates() {
	$dates = array();

	$current_time = current_time( 'timestamp' );

	$dates['range'] = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'this_month';

	if ( 'custom' !== $dates['range'] ) {
		$dates['year']     = isset( $_GET['year'] ) ? sanitize_text_field( wp_unslash( $_GET['year'] ) ) : gmdate( 'Y' );
		$dates['year_end'] = isset( $_GET['year_end'] ) ? sanitize_text_field( wp_unslash( $_GET['year_end'] ) ) : gmdate( 'Y' );
		$dates['m_start']  = isset( $_GET['m_start'] ) ? sanitize_text_field( wp_unslash( $_GET['m_start'] ) ) : 1;
		$dates['m_end']    = isset( $_GET['m_end'] ) ? sanitize_text_field( wp_unslash( $_GET['m_end'] ) ) : 12;
		$dates['day']      = isset( $_GET['day'] ) ? sanitize_text_field( wp_unslash( $_GET['day'] ) ) : 1;
		$dates['day_end']  = isset( $_GET['day_end'] ) ? sanitize_text_field( wp_unslash( $_GET['day_end'] ) ) : cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
	}

	// Modify dates based on predefined ranges
	switch ( $dates['range'] ) :

		case 'this_month':
			$dates['m_start']  = gmdate( 'n', $current_time );
			$dates['m_end']    = gmdate( 'n', $current_time );
			$dates['day']      = 1;
			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year']     = gmdate( 'Y' );
			$dates['year_end'] = gmdate( 'Y' );
			break;

		case 'last_month':
			if ( gmdate( 'n' ) == 1 ) {
				$dates['m_start']  = 12;
				$dates['m_end']    = 12;
				$dates['year']     = gmdate( 'Y', $current_time ) - 1;
				$dates['year_end'] = gmdate( 'Y', $current_time ) - 1;
			} else {
				$dates['m_start']  = gmdate( 'n' ) - 1;
				$dates['m_end']    = gmdate( 'n' ) - 1;
				$dates['year_end'] = $dates['year'];
			}
			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			break;

		case 'today':
			$dates['day']     = gmdate( 'd', $current_time );
			$dates['m_start'] = gmdate( 'n', $current_time );
			$dates['m_end']   = gmdate( 'n', $current_time );
			$dates['year']    = gmdate( 'Y', $current_time );
			break;

		case 'yesterday':
			$year  = gmdate( 'Y', $current_time );
			$month = gmdate( 'n', $current_time );
			$day   = gmdate( 'd', $current_time );

			if ( 1 === $month && 1 === $day ) {

				$year -= 1;
				$month = 12;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} elseif ( $month > 1 && 1 === $day ) {

				$month -= 1;
				$day    = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} else {

				$day -= 1;

			}

			$dates['day']      = $day;
			$dates['m_start']  = $month;
			$dates['m_end']    = $month;
			$dates['year']     = $year;
			$dates['year_end'] = $year;
			break;

		case 'this_week':
		case 'last_week':
			$base_time = 'this_week' === $dates['range'] ? current_time( 'mysql' ) : gmdate( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
			$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

			$dates['day']     = gmdate( 'd', esc_html( $start_end['start'] ) );
			$dates['m_start'] = gmdate( 'n', esc_html( $start_end['start'] ) );
			$dates['year']    = gmdate( 'Y', esc_html( $start_end['start'] ) );

			$dates['day_end']  = gmdate( 'd', esc_html( $start_end['end'] ) );
			$dates['m_end']    = gmdate( 'n', esc_html( $start_end['end'] ) );
			$dates['year_end'] = gmdate( 'Y', esc_html( $start_end['end'] ) );
			break;

		case 'this_quarter':
			$month_now         = gmdate( 'n', $current_time );
			$dates['year']     = gmdate( 'Y', $current_time );
			$dates['year_end'] = $dates['year'];

			if ( $month_now <= 3 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 3;

			} elseif ( $month_now <= 6 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 6;

			} elseif ( $month_now <= 9 ) {

				$dates['m_start'] = 7;
				$dates['m_end']   = 9;

			} else {

				$dates['m_start'] = 10;
				$dates['m_end']   = 12;

			}

			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			break;

		case 'last_quarter':
			$month_now = gmdate( 'n' );

			if ( $month_now <= 3 ) {

				$dates['m_start'] = 10;
				$dates['m_end']   = 12;
				$dates['year']    = gmdate( 'Y', $current_time ) - 1; // Previous year

			} elseif ( $month_now <= 6 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 3;
				$dates['year']    = gmdate( 'Y', $current_time );

			} elseif ( $month_now <= 9 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 6;
				$dates['year']    = gmdate( 'Y', $current_time );

			} else {

				$dates['m_start'] = 7;
				$dates['m_end']   = 9;
				$dates['year']    = gmdate( 'Y', $current_time );

			}

			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year_end'] = $dates['year'];
			break;

		case 'this_year':
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = gmdate( 'Y', $current_time );
			$dates['year_end'] = $dates['year'];
			break;

		case 'last_year':
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = gmdate( 'Y', $current_time ) - 1;
			$dates['year_end'] = gmdate( 'Y', $current_time ) - 1;
			break;

	endswitch;

	return apply_filters( 'tmem_report_dates', $dates );
} // tmem_get_report_dates

/**
 * Grabs all of the selected date info and then redirects appropriately
 *
 * @since 1.4
 * @param $data
 */
function tmem_parse_report_dates( $data ) {
	$dates = tmem_get_report_dates();

	$view = tmem_get_reporting_view();
	$id   = isset( $_GET['event-id'] ) ? sanitize_text_field( wp_unslash( $_GET['event-id'] ) ) : null;

	wp_safe_redirect( add_query_arg( $dates, admin_url( 'edit.php?post_type=tmem-event&page=tmem-reports&view=' . esc_attr( $view ) . '&event-id=' . absint( $id ) ) ) );
	wp_die();
} // tmem_parse_report_dates
add_action( 'tmem_filter_reports', 'tmem_parse_report_dates' );
