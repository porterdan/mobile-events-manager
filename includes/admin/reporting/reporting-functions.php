<?php
/**
 * Admin Reports Page
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
 * Reports Page
 *
 * Renders the reports page contents.
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_page() {
	$current_page = admin_url( 'edit.php?post_type=tmem-event&page=tmem-reports' );
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
			<?php do_action( 'tmem_reports_tabs' ); ?>
		</h1>

		<?php
		do_action( 'tmem_reports_page_top' );
		do_action( 'tmem_reports_tab_' . $active_tab );
		do_action( 'tmem_reports_page_bottom' );
		?>
	</div><!-- .wrap -->
	<?php
} // tmem_reports_page

/**
 * Default Report Views
 *
 * @since 1.4
 * @return arr $views Report Views
 */
function tmem_reports_default_views() {
	$event_label_single = esc_html( tmem_get_label_singular() );
	$event_label_plural = esc_html( tmem_get_label_plural() );

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

	if ( ! tmem_is_employer() ) {
		unset( $views['employees'] );
	}

	if ( ! tmem_packages_enabled() ) {
		unset( $views['packages'] );
		unset( $views['addons'] );
	}

	$views = apply_filters( 'tmem_report_views', $views );

	return $views;
} // tmem_reports_default_views

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
function tmem_get_reporting_view( $default = 'events' ) {

	if ( ! isset( $_GET['view'] ) || ! in_array( $_GET['view'], array_keys( tmem_reports_default_views() ) ) ) {
		$view = $default;
	} else {
		$view = sanitize_key( wp_unslash( $_GET['view'] ) );
	}

	return apply_filters( 'tmem_get_reporting_view', $view );
} // tmem_get_reporting_view

/**
 * Renders the Reports page
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_tab_reports() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		wp_die( __( 'You do not have permission to access this report', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	$current_view = 'earnings';
	$views        = tmem_reports_default_views();

	if ( isset( $_GET['view'] ) && array_key_exists( sanitize_key( wp_unslash( $_GET['view'] ) ), $views ) ) {
		$current_view = sanitize_key( wp_unslash( $_GET['view'] ) );
	}

	do_action( 'tmem_reports_view_' . $current_view );

} // tmem_reports_tab_reports
add_action( 'tmem_reports_tab_reports', 'tmem_reports_tab_reports' );

/**
 * Renders the Reports Page Views Drop Downs
 *
 * @since 1.3
 * @return void
 */
function tmem_report_views() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	$views        = tmem_reports_default_views();
	$current_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'earnings';
	?>
	<form id="tmem-reports-filter" method="get">
		<select id="tmem-reports-view" name="view">
			<option value="-1"><?php esc_html_e( 'Report Type', 'mobile-events-manager' ); ?></option>
			<?php foreach ( $views as $view_id => $label ) : ?>
				<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>

		<?php do_action( 'tmem_report_view_actions' ); ?>

		<input type="hidden" name="post_type" value="tmem-event"/>
		<input type="hidden" name="page" value="tmem-reports"/>
		<?php submit_button( __( 'Show', 'mobile-events-manager' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
	do_action( 'tmem_report_view_actions_after' );
} // tmem_report_views

/**
 * Renders the Reports Earnings Graphs
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_earnings() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}
	?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php tmem_report_views(); ?></div>
	</div>
	<?php
	tmem_earnings_reports_graph();
} // tmem_reports_earnings
add_action( 'tmem_reports_view_earnings', 'tmem_reports_earnings' );

/**
 * Renders the Reports Transactions Graphs
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_transactions() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}
	?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php tmem_report_views(); ?></div>
	</div>
	<?php
	tmem_transactions_reports_graph();
} // tmem_reports_transactions
add_action( 'tmem_reports_view_transactions', 'tmem_reports_transactions' );

/**
 * Renders the Reports Event Employees Table
 *
 * @since 1.4
 * @uses TMEM_Employees_Reports_Table::prepare_items()
 * @uses TMEM_Employees_Reports_Table::display()
 * @return void
 */
function tmem_reports_employees_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-employees-reports-table.php';

	?>

	<div class="inside">
		<?php

		$employees_table = new TMEM_Employees_Reports_Table();
		$employees_table->prepare_items();
		$employees_table->display();
		?>

		<?php echo $employees_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php printf( esc_html__( 'Employee %s Mix: ', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ); ?></strong>
				<?php $employees_table->output_employee_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Employee Wages Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $employees_table->output_wages_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_employees_graph_additional_stats' ); ?>

		<p class="tmem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'All employee %s are included whether they are the primary employee or not.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
			<span>
				<em><?php printf( esc_html__( 'Stats include all %s that take place within the date period selected.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // tmem_reports_employees_table
add_action( 'tmem_reports_view_employees', 'tmem_reports_employees_table' );

/**
 * Renders the Reports Event Types Table
 *
 * @since 1.4
 * @uses TMEM_Types_Reports_Table::prepare_items()
 * @uses TMEM_Types_Reports_Table::display()
 * @return void
 */
function tmem_reports_types_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-types-reports-table.php';

	?>

	<div class="inside">
		<?php

		$types_table = new TMEM_Types_Reports_Table();
		$types_table->prepare_items();
		$types_table->display();
		?>

		<?php echo $types_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php printf( esc_html__( '%s Types Mix: ', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></strong>
				<?php $types_table->output_source_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Type Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $types_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_types_graph_additional_stats' ); ?>

		<p class="tmem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s taking place within the date period selected.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // tmem_reports_types_table
add_action( 'tmem_reports_view_types', 'tmem_reports_types_table' );

/**
 * Renders the Reports Event Conversions Table
 *
 * @since 1.4
 * @uses TMEM_Conversions_Reports_Table::prepare_items()
 * @uses TMEM_Conversions_Reports_Table::display()
 * @return void
 */
function tmem_reports_conversions_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-conversions-reports-table.php';

	?>

	<div class="inside">
		<?php

		$conversions_table = new TMEM_Conversions_Reports_Table();
		$conversions_table->prepare_items();
		$conversions_table->display();
		?>

		<?php echo $conversions_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Enquiry Source Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $conversions_table->output_source_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Source Values Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $conversions_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_conversions_graph_additional_stats' ); ?>

		<p class="tmem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all enquiries that were received within the date period selected.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // tmem_reports_conversions_table
add_action( 'tmem_reports_view_conversions', 'tmem_reports_conversions_table' );

/**
 * Renders the Reports Event Packages Table
 *
 * @since 1.4
 * @uses TMEM_Conversions_Reports_Table::prepare_items()
 * @uses TMEM_Conversions_Reports_Table::display()
 * @return void
 */
function tmem_reports_packages_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-packages-reports-table.php';

	?>

	<div class="inside">
		<?php

		$packages_table = new TMEM_Packages_Reports_Table();
		$packages_table->prepare_items();
		$packages_table->display();
		?>

		<?php echo $packages_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php printf( esc_html__( '%s Package Mix: ', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></strong>
				<?php $packages_table->output_source_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Package Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $packages_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_packages_graph_additional_stats' ); ?>

		<p class="tmem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s with a date within the period selected regardless of their status.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // tmem_reports_packages_table
add_action( 'tmem_reports_view_packages', 'tmem_reports_packages_table' );

/**
 * Renders the Reports Event Packages Table
 *
 * @since 1.4
 * @uses TMEM_Conversions_Reports_Table::prepare_items()
 * @uses TMEM_Conversions_Reports_Table::display()
 * @return void
 */
function tmem_reports_addons_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-addons-reports-table.php';

	?>

	<div class="inside">
		<?php

		$addons_table = new TMEM_Addons_Reports_Table();
		$addons_table->prepare_items();
		$addons_table->display();
		?>

		<?php echo $addons_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php printf( esc_html__( '%s Addons Mix: ', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ); ?></strong>
				<?php $addons_table->output_source_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Addon Earnings Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $addons_table->output_earnings_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_addons_graph_additional_stats' ); ?>

		<p class="tmem-graph-notes">
			<span>
				<em><sup>&dagger;</sup> <?php printf( esc_html__( 'Stats include all %s with a date within the period selected regardless of their status.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ); ?></em>
			</span>
		</p>

	</div>
	<?php
} // tmem_reports_addons_table
add_action( 'tmem_reports_view_addons', 'tmem_reports_addons_table' );

/**
 * Renders the Reports Transactions Graphs
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_txn_types_table() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		return;
	}

	include dirname( __FILE__ ) . '/class-tmem-transaction-types-reports-table.php';

	?>

	<div class="inside">
		<?php

		$txn_types_table = new TMEM_Transaction_Types_Reports_Table();
		$txn_types_table->prepare_items();
		$txn_types_table->display();
		?>

		<?php echo $txn_types_table->load_scripts(); ?>

		<div class="tmem-mix-totals">
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Transaction Types Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $txn_types_table->output_types_graph(); ?>
			</div>
			<div class="tmem-mix-chart">
				<strong><?php esc_html_e( 'Transaction Values Mix: ', 'mobile-events-manager' ); ?></strong>
				<?php $txn_types_table->output_values_graph(); ?>
			</div>
		</div>

		<?php do_action( 'tmem_reports_txn_types_additional_stats' ); ?>

	</div>
	<?php
} // tmem_reports_txn_types_table
add_action( 'tmem_reports_view_txn-types', 'tmem_reports_txn_types_table' );

/**
 * Renders the 'Export' tab on the Reports Page
 *
 * @since 1.4
 * @return void
 */
function tmem_reports_tab_export() {

	if ( ! tmem_employee_can( 'run_reports' ) ) {
		wp_die( __( 'You do not have permission to export reports', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	$label_single = esc_html( tmem_get_label_singular() );
	$label_plural = esc_html( tmem_get_label_plural() );

	?>
	<div id="tmem-dashboard-widgets-wrap">
		<div class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content">

					<?php do_action( 'tmem_reports_tab_export_content_top' ); ?>

					<div class="postbox tmem-export-events-earnings">
						<h3><span><?php esc_html_e( 'Export Transaction History', 'mobile-events-manager' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Download a CSV of all transactions recorded.', 'mobile-events-manager' ); ?></p>
							<form id="tmem-export-txns" class="tmem-export-form tmem-import-export-form" method="post">
								<?php
								tmem_insert_datepicker(
									array(
										'id'       => 'tmem-txn-export-start',
										'altfield' => 'txn_start',
									)
								);
								?>
								<?php
								echo TMEM()->html->date_field(
									array(
										'id'          => 'tmem-txn-export-start',
										'name'        => 'display_start_date',
										'placeholder' => __( 'Select Start Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo TMEM()->html->hidden(
									array(
										'name' => 'txn_start',
									)
								);
								?>
								<?php
								tmem_insert_datepicker(
									array(
										'id'       => 'tmem-txn-export-end',
										'altfield' => 'txn_end',
									)
								);
								?>
								<?php
								echo TMEM()->html->date_field(
									array(
										'id'          => 'tmem-txn-export-end',
										'name'        => 'display_end_date',
										'placeholder' => __( 'Select End Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo TMEM()->html->hidden(
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
								<?php wp_nonce_field( 'tmem_ajax_export', 'tmem_ajax_export' ); ?>
								<input type="hidden" name="tmem-export-class" value="TMEM_Batch_Export_Txns"/>
								<span>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
									<span class="spinner"></span>
								</span>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox tmem-export-events">
						<h3><span><?php printf( esc_html__( 'Export %s', 'mobile-events-manager' ), $label_plural ); ?></span></h3>
						<div class="inside">
							<p><?php printf( esc_html__( 'Download a CSV of %s data.', 'mobile-events-manager' ), $label_plural ); ?></p>
							<form id="tmem-export-events" class="tmem-export-form tmem-import-export-form" method="post">
								<?php
								tmem_insert_datepicker(
									array(
										'id'       => 'tmem-event-export-start',
										'altfield' => 'event_start',
									)
								);
								?>
								<?php
								echo TMEM()->html->date_field(
									array(
										'id'          => 'tmem-event-export-start',
										'name'        => 'display_start_date',
										'placeholder' => __( 'Select Start Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo TMEM()->html->hidden(
									array(
										'name' => 'event_start',
									)
								);
								?>
								<?php
								tmem_insert_datepicker(
									array(
										'id'       => 'tmem-event-export-end',
										'altfield' => 'event_end',
									)
								);
								?>
								<?php
								echo TMEM()->html->date_field(
									array(
										'id'          => 'tmem-event-export-end',
										'name'        => 'display_end_date',
										'placeholder' => __( 'Select End Date', 'mobile-events-manager' ),
									)
								);
								?>
								<?php
								echo TMEM()->html->hidden(
									array(
										'name' => 'event_end',
									)
								);
								?>
								<select name="event_status">
									<option value="any"><?php esc_html_e( 'All Statuses', 'mobile-events-manager' ); ?></option>
									<?php foreach ( tmem_all_event_status() as $status => $label ) : ?>
										<option value="<?php echo $status; ?>"><?php echo $label; ?></option>
									<?php endforeach; ?>
								</select>
								<?php wp_nonce_field( 'tmem_ajax_export', 'tmem_ajax_export' ); ?>
								<input type="hidden" name="tmem-export-class" value="TMEM_Batch_Export_Events"/>
								<span>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
									<span class="spinner"></span>
								</span>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<div class="postbox tmem-export-clients">
						<h3><span><?php esc_html_e( 'Export Clients', 'mobile-events-manager' ); ?></span></h3>
						<div class="inside">
							<p><?php esc_html_e( 'Download a CSV of clients.', 'mobile-events-manager' ); ?></p>
							<form id="tmem-export-clients" class="tmem-export-form tmem-import-export-form" method="post">
								<?php wp_nonce_field( 'tmem_ajax_export', 'tmem_ajax_export' ); ?>
								<input type="hidden" name="tmem-export-class" value="TMEM_Batch_Export_Clients"/>
								<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
							</form>
						</div><!-- .inside -->
					</div><!-- .postbox -->

					<?php if ( tmem_is_employer() ) : ?>
						<div class="postbox tmem-export-employees">
							<h3><span><?php esc_html_e( 'Export Employees', 'mobile-events-manager' ); ?></span></h3>
							<div class="inside">
								<p><?php esc_html_e( 'Download a CSV of employees.', 'mobile-events-manager' ); ?></p>
								<form id="tmem-export-employees" class="tmem-export-form tmem-import-export-form" method="post">
									<?php wp_nonce_field( 'tmem_ajax_export', 'tmem_ajax_export' ); ?>
									<input type="hidden" name="tmem-export-class" value="TMEM_Batch_Export_Employees"/>
									<input type="submit" value="<?php esc_html_e( 'Generate CSV', 'mobile-events-manager' ); ?>" class="button-secondary"/>
								</form>
							</div><!-- .inside -->
						</div><!-- .postbox -->
					<?php endif; ?>

				</div><!-- .post-body-content -->
			</div><!-- .post-body -->
		</div><!-- .metabox-holder -->
	</div><!-- #tmem-dashboard-widgets-wrap -->

	<?php
} // tmem_reports_tab_export
add_action( 'tmem_reports_tab_export', 'tmem_reports_tab_export' );
