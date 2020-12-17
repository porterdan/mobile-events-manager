<?php
/**
 * WordPress Dashboard Widgets
 *
 * @package TMEM
 * @subpackage Admin/Widgets
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the dashboard widgets.
 *
 * @since 1.3
 */
function tmem_add_wp_dashboard_widgets() {
	/* translators: %s: Company Name */
	wp_add_dashboard_widget( 'tmem-widget-overview', sprintf( esc_html__( '%s Overview', 'mobile-events-manager' ), tmem_get_option( 'company_name', 'TMEM' ) ), 'tmem_widget_events_overview' );

} // tmem_add_wp_dashboard_widgets
add_action( 'wp_dashboard_setup', 'tmem_add_wp_dashboard_widgets' );

/**
 * Generate and display the content for the Events Overview dashboard widget.
 *
 * @since 1.3
 */
function tmem_widget_events_overview() {

	global $current_user;

	if ( tmem_employee_can( 'manage_tmem' ) ) {

		$stats = new TMEM_Stats();

		$enquiry_counts    = array(
			'month'     => 0,
			'this_year' => 0,
			'last_year' => 0,
		);
		$conversion_counts = array(
			'month'     => 0,
			'this_year' => 0,
			'last_year' => 0,
		);
		$enquiry_periods   = array(
			'month'     => gmdate( 'Y-m-01' ),
			'this_year' => gmdate( 'Y-01-01' ),
			'last_year' => gmdate( 'Y-01-01', strtotime( '-1 year' ) ),
		);

		foreach ( $enquiry_periods as $period => $date ) {
			$current_count = tmem_count_events(
				array(
					'start-date' => $date,
					'end-date'   => 'last_year' !== $period ? gmdate( 'Y-m-d' ) : gmdate( 'Y-12-31', strtotime( '-1 year' ) ),
				)
			);

			foreach ( $current_count as $status => $count ) {
				$enquiry_counts[ $period ] += $count;

				if ( in_array( $status, array( 'tmem-approved', 'tmem-contract', 'tmem-completed', 'tmem-cancelled' ) ) ) {
					$conversion_counts[ $period ] += $count;
				}
			}
		}

		$completed_counts = array(
			'month'     => 0,
			'this_year' => 0,
			'last_year' => 0,
		);
		$event_periods    = array(
			'month'     => array( gmdate( 'Y-m-01' ), gmdate( 'Y-m-d' ) ),
			'this_year' => array( gmdate( 'Y-01-01' ), gmdate( 'Y-m-d' ) ),
			'last_year' => array( gmdate( 'Y-m-01', strtotime( '-1 year' ) ), gmdate( 'Y-12-31', strtotime( '-1 year' ) ) ),
		);

		foreach ( $event_periods as $period => $date ) {
			$current_count = tmem_count_events(
				array(
					'date'   => $date,
					'status' => 'tmem-completed',
				)
			);

			foreach ( $current_count as $status => $count ) {
				$completed_counts[ $period ] += $count;
			}
		}

		$income_month  = $stats->get_income_by_date( null, gmdate( 'n' ), gmdate( 'Y' ) );
		$income_year   = $stats->get_income_by_date( null, '', gmdate( 'Y' ) );
		$income_last   = $stats->get_income_by_date( null, '', gmdate( 'Y' ) - 1 );
		$expense_month = $stats->get_expenses_by_date( null, gmdate( 'n' ), gmdate( 'Y' ) );
		$expense_year  = $stats->get_expenses_by_date( null, '', gmdate( 'Y' ) );
		$expense_last  = $stats->get_expenses_by_date( null, '', gmdate( 'Y' ) - 1 );

		$earnings_month = $income_month - $expense_month;
		$earnings_year  = $income_year - $expense_year;
		$earnings_last  = $income_last - $expense_last;

		?>
		<div class="tmem_stat_grid">
			<?php do_action( 'tmem_before_events_overview' ); ?>
			<table>
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th><?php esc_html_e( 'MTD', 'mobile-events-manager' ); ?></th>
						<th><?php esc_html_e( 'YTD', 'mobile-events-manager' ); ?></th>
						<th><?php echo esc_html_e( gmdate( 'Y', strtotime( '-1 year' ) ) ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th><?php /* translators: %s: Event Type */ printf( esc_html__( '%s Received', 'mobile-events-manager' ), esc_html( get_post_status_object( 'tmem-enquiry' )->plural ) ); ?></th>
						<td><?php echo esc_attr( $enquiry_counts['month'] ); ?></td>
						<td><?php echo esc_attr( $enquiry_counts['this_year'] ); ?></td>
						<td><?php echo esc_attr( $enquiry_counts['last_year'] ); ?></td>
					</tr>
					<tr>
						<th><?php /* translators: %s: Event Type */ printf( esc_html__( '%s Converted', 'mobile-events-manager' ), esc_html( get_post_status_object( 'tmem-enquiry' )->plural ) ); ?></th>
						<td><?php echo esc_attr( $conversion_counts['month'] ); ?></td>
						<td><?php echo esc_attr( $conversion_counts['this_year'] ); ?></td>
						<td><?php echo esc_attr( $conversion_counts['last_year'] ); ?></td>
					</tr>
					<tr>
						<th><?php /* translators: %s: Event Type */ printf( esc_html__( '%s Completed', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ); ?></th>
						<td><?php echo esc_attr( $completed_counts['month'] ); ?></td>
						<td><?php echo esc_attr( $completed_counts['this_year'] ); ?></td>
						<td><?php echo esc_attr( $completed_counts['last_year'] ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Income', 'mobile-events-manager' ); ?></th>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $income_month ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $income_year ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $income_last ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Outgoings', 'mobile-events-manager' ); ?></th>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $expense_month ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $expense_year ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $expense_last ) ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Earnings', 'mobile-events-manager' ); ?></th>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $earnings_month ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $earnings_year ) ) ); ?></td>
						<td><?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $earnings_last ) ) ); ?></td>
					</tr>
				</tbody>
			</table>

			<p>
				<?php
				printf( /* translators: %1: URL %2: Event type */
					wp_kses_post( __( '<a href="%1$s">Create %2$s</a>', 'mobile-events-manager' ) ),
					esc_url( admin_url( 'post-new.php?post_type=tmem-event' ) ),
					esc_html( tmem_get_label_singular() )
				);
				?>
				&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
				<?php
				printf( /* translators: %1: URL %2: Event type */
					wp_kses_post( __( '<a href="%1$s">Manage %2$s</a>', 'mobile-events-manager' ) ),
					esc_url( admin_url( 'edit.php?post_type=tmem-event' ) ),
					esc_html( tmem_get_label_plural() )
				);
				?>
				&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
				<?php
				printf( /* translators: %s: URL */
					wp_kses_post( __( '<a href="%s">Transactions</a>', 'mobile-events-manager' ) ),
					esc_url( admin_url( 'edit.php?post_type=tmem-transaction' ) )
				);
				?>
				&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
				<?php
				printf( /* translators: %s: URL */
					wp_kses_post( __( '<a href="%s">Settings</a>', 'mobile-events-manager' ) ),
					esc_url( admin_url( 'admin.php?page=tmem-settings' ) )
				);
				?>
			</p>

			<?php $sources = $stats->get_enquiry_sources_by_date( 'this_month' ); ?>

			<?php if ( ! empty( $sources ) ) : ?>

				<?php foreach ( $sources as $count => $source ) : ?>
					<p>
					<?php /* translators: %1: Source of Enquiry %2: Amount of events */ printf( wp_kses_post( '<p>Most enquiries have been received via <strong>%1$s (%2$d)</strong> so far this month.', 'mobile-events-manager' ), esc_attr( $source ), (int) $count ); ?>
					</p>
				<?php endforeach; ?>

			<?php else : ?>
				<p><?php esc_html_e( 'No enquiries yet this month.', 'mobile-events-manager' ); ?></p>
			<?php endif; ?>

			<?php do_action( 'tmem_after_events_overview' ); ?>

		</div>

		<?php
	}

} // tmem_widget_events_overview

/**
 * Add event count to At a glance widget
 *
 * @since 1.4
 * @param arr $items Event count at a glance.
 */
function tmem_dashboard_at_a_glance_widget( $items ) {
	$num_posts = tmem_count_events();
	$count     = 0;
	$statuses  = tmem_all_event_status();

	foreach ( $statuses as $status => $label ) {
		if ( ! empty( $num_posts->$status ) ) {
			$count += $num_posts->$status;
		}
	}

	if ( $num_posts && $count > 0 ) {
		/* translators: %s: Company Name */
		$text = wp_kses_post( _n( '%s' . tmem_get_label_singular(), '%s' . tmem_get_label_plural(), $count, 'mobile-events-manager' ) );

		$text = sprintf( $text, number_format_i18n( $count ) );

		if ( tmem_employee_can( 'read_events' ) ) {
			$text = sprintf( '<a class="event-count" href="edit.php?post_type=tmem-event">%1$s</a>', $text );
		} else {
			$text = sprintf( '<span class="event-count">%1$s</span>', $text );
		}

		$items[] = $text;
	}

	return $items;
} // tmem_dashboard_at_a_glance_widget
add_filter( 'dashboard_glance_items', 'tmem_dashboard_at_a_glance_widget', 1 );
