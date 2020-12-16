<?php
/**
 * Admin Reports Page
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
 * Reports Page
 *
 * Renders the reports page contents.
 *
 * @since 1.4
 * @return void
 */
function mem_reports_page() {
	$current_page = admin_url( 'edit.php?post_type=mem-event&page=mem-reports' );
	$active_tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'reports';
	?>
	<div class="wrap">
		<h1 class="nav-tab-wrapper">
			<a href="
			<?php
			echo add_query_arg(
				array(
					'tab'              => 'reports',
					'settings-updated' => false,
				),
				$current_page
			);
			?>
			" class="nav-tab <?php echo 'reports' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reports', 'mobile-events-manager' ); ?></a>
			<a href="
			<?php
			echo add_query_arg(
				array(
					'tab'              => 'export',
					'settings-updated' => false,
				),
				$current_page
			);
			?>
			" class="nav-tab <?php echo 'export' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export', 'mobile-events-manager' ); ?></a>
			<?php do_action( 'mem_reports_tabs' ); ?>
		</h1>

		<?php
		do_action( 'mem_reports_page_top' );
		do_action( 'mem_reports_tab_' . $active_tab );
		do_action( 'mem_reports_page_bottom' );
		?>
	</div><!-- .wrap -->
	<?php
} // mem_reports_page

/**
 * Default Report Views
 *
 * @since 1.4
 * @return arr $views Report Views
 */
function mem_reports_default_views() {
	$event_label_single = esc_html( mem_get_label_singular() );
	$event_label_plural = esc_html( mem_get_label_plural() );

	$views = array(
		'earnings'     => __( 'Earnings', 'mobile-events-manager' ),
		'transactions' => __( 'Transactions', 'mobile-events-manager' ),
		'txn-types'    => __( 'Transactions by Type', 'mobile-events-manager' ),
		'conversions'  => __( 'Enquiries by Source', 'mobile-events-manager' ),
		'employees'    => sprintf( esc_html__( '%s by Employee', 'mobile-events-manager' ), $event_label_plural ),
		'types'        => sprintf( esc_html__( '%s by Type', 'mobile-events-manager' ), $event_label_plural ),
		'packages'     => sprintf( esc_html__( '%s by Package', 'mobile-events-manager' ), $event_label_plural ),
		'addons'       => sprintf( esc_html__( '%s by Addon', 'mobile-events-manager' ), $event_label_plural ),
	);

	if ( ! mem_is_employer() ) {
		unset( $views['employees'] );
	}

	if ( ! mem_packages_enabled() ) {
		unset( $views['packages'] );
		unset( $views['addons'] );
	}

	$views = apply_filters( 'mem_report_views', $views );

	return $views;
} // mem_reports_default_views

/**
 * Default Report Views
 *
 * Checks the $_GET['view'] parameter to ensure it exists within the default allowed views.
 *
 * @param string $default Default view to use.
 *
 * @since 1.4
 * @return str $view Report View
 */
function mem_get_reporting_view( $default = 'events' ) {

	if ( ! isset( $_GET['view'] ) || ! in_array( $_GET['view'], array_keys( mem_reports_default_views() ) ) ) {
		$view = $default;
	} else {
		$view = sanitize_key( wp_unslash( $_GET['view'] ) );
	}

	return apply_filters( 'mem_get_reporting_view', $view );
} // mem_get_reporting_view

/**
 * Renders the Reports page
 *
 * @since 1.4
 * @return void
 */
function mem_reports_tab_reports() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		wp_die( __( 'You do not have permission to access this report', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	$current_view = 'earnings';
	$views        = mem_reports_default_views();

	if ( isset( $_GET['view'] ) && array_key_exists( sanitize_key( wp_unslash( $_GET['view'] ) ), $views ) ) {
		$current_view = sanitize_key( wp_unslash( $_GET['view'] ) );
	}

	do_action( 'mem_reports_view_' . $current_view );

} // mem_reports_tab_reports
add_action( 'mem_reports_tab_reports', 'mem_reports_tab_reports' );

/**
 * Renders the Reports Page Views Drop Downs
 *
 * @since 1.3
 * @return void
 */
function mem_report_views() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	$views        = mem_reports_default_views();
	$current_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'earnings';
	?>
	<form id="mem-reports-filter" method="get">
		<select id="mem-reports-view" name="view">
			<option value="-1"><?php esc_html_e( 'Report Type', 'mobile-events-manager' ); ?></option>
			<?php foreach ( $views as $view_id => $label ) : ?>
				<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>

		<?php do_action( 'mem_report_view_actions' ); ?>

		<input type="hidden" name="post_type" value="mem-event"/>
		<input type="hidden" name="page" value="mem-reports"/>
		<?php submit_button( __( 'Show', 'mobile-events-manager' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
	do_action( 'mem_report_view_actions_after' );
} // mem_report_views

/**
 * Renders the Reports Earnings Graphs
 *
 * @since 1.4
 * @return void
 */
function mem_reports_earnings() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}
	?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php mem_report_views(); ?></div>
	</div>
	<?php
	mem_earnings_reports_graph();
} // mem_reports_earnings
add_action( 'mem_reports_view_earnings', 'mem_reports_earnings' );

/**
 * Renders the Reports Transactions Graphs
 *
 * @since 1.4
 * @return void
 */
function mem_reports_transactions() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}
	?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php mem_report_views(); ?></div>
	</div>
	<?php
	mem_transactions_reports_graph();
} // mem_reports_transactions
add_action( 'mem_reports_view_transactions', 'mem_reports_transactions' );

/**
 * Renders the Reports Event Employees Table
 *
 * @since 1.4
 * @uses MEM_Employees_Reports_Table::prepare_items()
 * @uses MEM_Employees_Reports_Table::display()
 * @return void
 */
function mem_reports_employees_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-employees-reports-table.php';

	?>

	<div class="inside">
		<?php

		$employees_table = new MEM_Employees_Reports_Table();
		$employees_table->prepare_items();
		$employees_table->display();
		?>

		<?php echo $employees_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php printf( esc_html__( 'Employee %s Mix: ', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) ); ?></strong>
				<?php $employees_table->output_employee_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Employee Wages Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $employees_table->output_wages_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_employees_graph_additional_stats' ); ?>

		<p class="mem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'All employee %s are included whether they are the primary employee or not.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
			<span>
				<em><?php printf( esc_html__( 'Stats include all %s that take place within the date period selected.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // mem_reports_employees_table
add_action( 'mem_reports_view_employees', 'mem_reports_employees_table' );

/**
 * Renders the Reports Event Types Table
 *
 * @since 1.4
 * @uses MEM_Types_Reports_Table::prepare_items()
 * @uses MEM_Types_Reports_Table::display()
 * @return void
 */
function mem_reports_types_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-types-reports-table.php';

	?>

	<div class="inside">
		<?php

		$types_table = new MEM_Types_Reports_Table();
		$types_table->prepare_items();
		$types_table->display();
		?>

		<?php echo $types_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php printf( esc_html__( '%s Types Mix: ', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></strong>
				<?php $types_table->output_source_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Type Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $types_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_types_graph_additional_stats' ); ?>

		<p class="mem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s taking place within the date period selected.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // mem_reports_types_table
add_action( 'mem_reports_view_types', 'mem_reports_types_table' );

/**
 * Renders the Reports Event Conversions Table
 *
 * @since 1.4
 * @uses MEM_Conversions_Reports_Table::prepare_items()
 * @uses MEM_Conversions_Reports_Table::display()
 * @return void
 */
function mem_reports_conversions_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-conversions-reports-table.php';

	?>

	<div class="inside">
		<?php

		$conversions_table = new MEM_Conversions_Reports_Table();
		$conversions_table->prepare_items();
		$conversions_table->display();
		?>

		<?php echo $conversions_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Enquiry Source Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $conversions_table->output_source_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Source Values Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $conversions_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_conversions_graph_additional_stats' ); ?>

		<p class="mem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all enquiries that were received within the date period selected.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // mem_reports_conversions_table
add_action( 'mem_reports_view_conversions', 'mem_reports_conversions_table' );

/**
 * Renders the Reports Event Packages Table
 *
 * @since 1.4
 * @uses MEM_Conversions_Reports_Table::prepare_items()
 * @uses MEM_Conversions_Reports_Table::display()
 * @return void
 */
function mem_reports_packages_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-packages-reports-table.php';

	?>

	<div class="inside">
		<?php

		$packages_table = new MEM_Packages_Reports_Table();
		$packages_table->prepare_items();
		$packages_table->display();
		?>

		<?php echo $packages_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php printf( esc_html__( '%s Package Mix: ', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></strong>
				<?php $packages_table->output_source_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Package Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $packages_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_packages_graph_additional_stats' ); ?>

		<p class="mem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s with a date within the period selected regardless of their status.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // mem_reports_packages_table
add_action( 'mem_reports_view_packages', 'mem_reports_packages_table' );

/**
 * Renders the Reports Event Packages Table
 *
 * @since 1.4
 * @uses MEM_Conversions_Reports_Table::prepare_items()
 * @uses MEM_Conversions_Reports_Table::display()
 * @return void
 */
function mem_reports_addons_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-addons-reports-table.php';

	?>

	<div class="inside">
		<?php

		$addons_table = new MEM_Addons_Reports_Table();
		$addons_table->prepare_items();
		$addons_table->display();
		?>

		<?php echo $addons_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php printf( esc_html__( '%s Addons Mix: ', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) ); ?></strong>
				<?php $addons_table->output_source_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Addon Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $addons_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_addons_graph_additional_stats' ); ?>

		<p class="mem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s with a date within the period selected regardless of their status.', 'mobile-events-manager' ), mem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // mem_reports_addons_table
add_action( 'mem_reports_view_addons', 'mem_reports_addons_table' );

/**
 * Renders the Reports Transactions Graphs
 *
 * @since 1.4
 * @return void
 */
function mem_reports_txn_types_table() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-mem-transaction-types-reports-table.php';

	?>

	<div class="inside">
		<?php

		$txn_types_table = new MEM_Transaction_Types_Reports_Table();
		$txn_types_table->prepare_items();
		$txn_types_table->display();
		?>

		<?php echo $txn_types_table->load_scripts(); ?>

		<div class="mem-mix-totals">
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Transaction Types Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $txn_types_table->output_types_graph(); ?>
			</div>
			<div class="mem-mix-chart">
				<strong><?php esc_html_e( 'Transaction Values Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $txn_types_table->output_values_graph(); ?>
			</div>
		</div>

		<?php do_action( 'mem_reports_txn_types_additional_stats' ); ?>

	</div>
	<?php
} // mem_reports_txn_types_table
add_action( 'mem_reports_view_txn-types', 'mem_reports_txn_types_table' );

/**
 * Renders the 'Export' tab on the Reports Page
 *
 * @since 1.4
 * @return void
 */
function mem_reports_tab_export() {

	if ( ! mem_employee_can( 'run_reports' ) ) {
		wp_die( __( 'You do not have permission to export reports', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	$label_single = esc_html( mem_get_label_singular() );
	$label_plural = esc_html( mem_get_label_plural() );

	?>
	<div id="mem-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content">

					<?php do_action( 'mem_reports_tab_export_content_top' ); ?>

					<div class="postbox mem-export-events-earnings">
						<h3><span><?php esc_html_e( 'Export Transaction History', 'mobile-events-manager' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Download a CSV of all transactions recorded.', 'mobile-events-manager' ); ?></p>
							<form id="mem-export-txns" class="mem-export-form mem-import-export-form" method="post">
								<?php
								mem_insert_datepicker(
									array(
										'id'       => 'mem-txn-export-start',
										'altfield' => 'txn_start',
									)
								);
								?>
								<?php
								echo MEM()->html->date_field(
									array(
										'id'          => 'mem-txn-export-start',
										'name'        => 'display_start_date',
										'placeholder' => __( 'Select Start Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo MEM()->html->hidden(
									array(
										'name' => 'txn_start',
									)
								);
								?>
								<?php
								mem_insert_datepicker(
									array(
										'id'       => 'mem-txn-export-end',
										'altfield' => 'txn_end',
									)
								);
								?>
								<?php
								echo MEM()->html->date_field(
									array(
										'id'          => 'mem-txn-export-end',
										'name'        => 'display_end_date',
										'placeholder' => __( 'Select End Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo MEM()->html->hidden(
									array(
										'name' => 'txn_end',
									)
								);
								?>
								<select name="txn_status">
									<option value=""><?php esc_html_e( 'All Statuses', 'mobile-events-manager' ); ?></option>
									<option value="Completed"><?php esc_html_e( 'Completed', 'mobile-events-manager' ); ?></option>
									<option value="Pending"><?php esc_html_e( 'Pending', 'mobile-events-manager' ); ?></option>
									<option value="Cancelled"><?php esc_html_e( 'Cancelled', 'mobile-events-manager' ); ?></option>
									<option value="Failed"><?php esc_html_e( 'Failed', 'mobile-events-manager' ); ?></option>
								</select>
								<?php wp_nonce_field( 'mem_ajax_export', 'mem_ajax_export' ); ?>
								<input type="hidden" name="mem-export-class" value="MEM_Batch_Export_Txns"/>
								<span>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
									<span class="spinner"></span>
								</span>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox mem-export-events">
						<h3><span><?php printf( esc_html__( 'Export %s', 'mobile-events-manager' ), $label_plural ); ?></span></h3>
						<div class="inside">
							<p><?php printf( esc_html__( 'Download a CSV of %s data.', 'mobile-events-manager' ), $label_plural ); ?></p>
							<form id="mem-export-events" class="mem-export-form mem-import-export-form" method="post">
								<?php
								mem_insert_datepicker(
									array(
										'id'       => 'mem-event-export-start',
										'altfield' => 'event_start',
									)
								);
								?>
								<?php
								echo MEM()->html->date_field(
									array(
										'id'          => 'mem-event-export-start',
										'name'        => 'display_start_date',
										'placeholder' => __( 'Select Start Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo MEM()->html->hidden(
									array(
										'name' => 'event_start',
									)
								);
								?>
								<?php
								mem_insert_datepicker(
									array(
										'id'       => 'mem-event-export-end',
										'altfield' => 'event_end',
									)
								);
								?>
								<?php
								echo MEM()->html->date_field(
									array(
										'id'          => 'mem-event-export-end',
										'name'        => 'display_end_date',
										'placeholder' => __( 'Select End Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo MEM()->html->hidden(
									array(
										'name' => 'event_end',
									)
								);
								?>
								<select name="event_status">
									<option value="any"><?php esc_html_e( 'All Statuses', 'mobile-events-manager' ); ?></option>
									<?php foreach ( mem_all_event_status() as $status => $label ) : ?>
										<option value="<?php echo $status; ?>"><?php echo $label; ?></option>
									<?php endforeach; ?>
								</select>
								<?php wp_nonce_field( 'mem_ajax_export', 'mem_ajax_export' ); ?>
								<input type="hidden" name="mem-export-class" value="MEM_Batch_Export_Events"/>
								<span>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
									<span class="spinner"></span>
								</span>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox mem-export-clients">
						<h3><span><?php esc_html_e( 'Export Clients', 'mobile-events-manager' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Download a CSV of clients.', 'mobile-events-manager' ); ?></p>
							<form id="mem-export-clients" class="mem-export-form mem-import-export-form" method="post">
								<?php wp_nonce_field( 'mem_ajax_export', 'mem_ajax_export' ); ?>
								<input type="hidden" name="mem-export-class" value="MEM_Batch_Export_Clients"/>
								<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<?php if ( mem_is_employer() ) : ?>
						<div class="postbox mem-export-employees">
							<h3><span><?php esc_html_e( 'Export Employees', 'mobile-events-manager' ); ?></span></h3>
							<div class="inside">
								<p><?php esc_html_e( 'Download a CSV of employees.', 'mobile-events-manager' ); ?></p>
								<form id="mem-export-employees" class="mem-export-form mem-import-export-form" method="post">
									<?php wp_nonce_field( 'mem_ajax_export', 'mem_ajax_export' ); ?>
									<input type="hidden" name="mem-export-class" value="MEM_Batch_Export_Employees"/>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
								</form>
							</div><!-- .inside -->
						</div><!-- .postbox -->
					<?php endif; ?>

				</div><!-- .post-body-content -->
			</div><!-- .post-body -->
		</div><!-- .metabox-holder -->
	</div><!-- #mem-dashboard-widgets-wrap -->

	<?php
} // mem_reports_tab_export
add_action( 'mem_reports_tab_export', 'mem_reports_tab_export' );
