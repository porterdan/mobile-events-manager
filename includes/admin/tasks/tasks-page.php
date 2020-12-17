<?php
/**
 * Tasks Page
 *
 * @package TMEM
 * @subpackage Tasks/Functions
 * @copyright Copyright (c) 2017, Mike Howard
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tasks Page
 *
 * Renders the task page contents.
 *
 * @since 1.0
 * @return void
 */
function tmem_tasks_page() {
	if ( isset( $_GET['view'], $_GET['id'] ) && 'task' == sanitize_text_field( wp_unslash( $_GET['view'] ) ) ) {
			tmem_render_single_task_view( sanitize_key( wp_unslash( $_GET['id'] ) ) );
	} else {
		tmem_tasks_list();
	}
} // tmem_tasks_page

/**
 * List table of customers
 *
 * @since 1.0
 * @return void
 */
function tmem_tasks_list() {
	include dirname( __FILE__ ) . '/class-tmem-tasks-table.php';

	$tasks_table = new TMEM_Tasks_Table();
	$tasks_table->prepare_items();
	?>
	<div class="wrap">
		<h1>
			<?php esc_html_e( 'Tasks', 'mobile-events-manager' ); ?>
		</h1>
		<?php do_action( 'tmem_tasks_table_top' ); ?>
		<form id="tmem-tasks-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=tmem-event&page=tmem-tasks' ); ?>">
			<?php
			$tasks_table->display();
			?>
			<input type="hidden" name="post_type" value="tmem-event" />
			<input type="hidden" name="page" value="tmem-tasks" />
			<input type="hidden" name="view" value="tasks" />
		</form>
		<?php do_action( 'tmem_tasks_table_bottom' ); ?>
	</div>
	<?php
} // tmem_tasks_list

/**
 * Renders the task view wrapper
 *
 * @since 1.0
 * @param str $view The View being requested
 * @param arr $callbacks The Registered views and their callback functions
 * @return void
 */
function tmem_render_single_task_view( $id ) {

	$task_view_role = apply_filters( 'tmem_view_tasks_role', 'manage_tmem' );
	$url            = remove_query_arg( array( 'tmem-message', 'render' ) );
	$task           = tmem_get_task( $id );
	$run_when       = explode( ' ', $task['options']['age'] );
	$run_times      = tmem_get_task_run_times( $id );
	$hide_runtimes  = 'playlist-notification' == $id ? ' tmem-hidden' : '';
	$return_url     = add_query_arg(
		array(
			'post_type' => 'tmem-event',
			'page'      => 'tmem-tasks',
		),
		admin_url( 'edit.php' )
	);

	if ( empty( $task ) ) {
		wp_die( __( 'Invalid task', 'mobile-events-manager' ) );
	}

	$run_task_url = add_query_arg(
		array(
			'post_type'   => 'tmem-event',
			'page'        => 'tmem-tasks',
			'id'          => $id,
			'tmem-action' => 'run_task',
		),
		admin_url( 'edit.php' )
	);

	$delete_url = add_query_arg(
		array(
			'post_type'   => 'tmem-event',
			'page'        => 'tmem-tasks',
			'tmem-action' => 'delete_task',
			'task_id'     => $id,
		),
		admin_url( 'edit.php?' )
	);

	?>

	<div class="wrap tmem-wrap">
		<h1>
			<?php printf( esc_html__( 'Task: %s', 'mobile-events-manager' ), esc_html( $task['name'] ) ); ?>
			<a href="<?php echo $return_url; ?>" class="page-title-action">
				<?php esc_html_e( 'Back to Task List', 'mobile-events-manager' ); ?>
			</a>
		</h1>
		<?php do_action( 'tmem_view_task_details_before', $id ); ?>
		<form id="tmem-edit-task-form" method="post">
		<?php do_action( 'tmem_view_task_details_form_top', $id ); ?>
		<div id="poststuff">
			<div id="tmem-dashboard-widgets-wrap">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables ui-sortable">

							<?php do_action( 'tmem_view_task_details_sidebar_before', $id ); ?>

							<div id="tmem-task-update" class="postbox tmem-task-data">

								<h3 class="hndle">
									<span><?php esc_html_e( 'Update Task', 'mobile-events-manager' ); ?></span>
								</h3>
								<div class="inside">
									<div class="tmem-admin-box">

										<?php do_action( 'tmem_task_details_stats_before', $id ); ?>

										<div class="tmem-admin-box-inside tmem-task-stats">
											<p>
												<span class="label"><?php esc_html_e( 'Last Ran:', 'mobile-events-manager' ); ?>&nbsp;</span>
												<?php if ( ! empty( $task['lastran'] ) && 'Never' != $task['lastran'] ) : ?>
													<?php echo date_i18n( get_option( 'time_format' ) . ' ' . get_option( 'date_format' ), $task['lastran'] ); ?>
												<?php else : ?>
													<?php echo __( 'Never', 'mobile-events-manager' ); ?>
												<?php endif; ?>
											</p>

											<p>
												<span class="label"><?php esc_html_e( 'Next Due:', 'mobile-events-manager' ); ?>&nbsp;</span>
												<?php if ( ! empty( $task['nextrun'] ) && 'N/A' != $task['nextrun'] ) : ?>
													 <?php echo date_i18n( get_option( 'time_format' ) . ' ' . get_option( 'date_format' ), $task['nextrun'] ); ?>
												<?php else : ?>
													<?php echo __( 'N/A', 'mobile-events-manager' ); ?>
												<?php endif; ?>
											</p>

											<p>
												<span class="label"><?php esc_html_e( 'Total Runs:', 'mobile-events-manager' ); ?>&nbsp;</span>
												<?php echo $task['totalruns']; ?>
											</p>

											<?php if ( 'upload-playlists' == $id ) : ?>
												<p>
													<span class="label"><?php esc_html_e( 'Entries Uploaded:', 'mobile-events-manager' ); ?>&nbsp;</span>
													<?php echo tmem_get_uploaded_playlist_entry_count(); ?>
												</p>
											<?php else : ?>
												<p>
													<?php
													echo TMEM()->html->checkbox(
														array(
															'name' => 'task_active',
															'current' => ! empty( $task['active'] ) ? true : false,
														)
													);
													?>
													&nbsp;
													<span class="label"><?php esc_html_e( 'Task Active', 'mobile-events-manager' ); ?></span>
												</p>
											<?php endif; ?>

											<?php if ( ! empty( $task['active'] ) ) : ?>
												<p>
													<a href="<?php echo $run_task_url; ?>" class="button button-secondary">
														<?php esc_html_e( 'Run Task', 'mobile-events-manager' ); ?>
													</a>
												</p>
											<?php endif; ?>

										</div><!-- /.tmem-admin-box-inside -->

									</div><!-- /.tmem-admin-box -->
								</div><!-- /.inside -->

								<div class="tmem-task-update-box tmem-admin-box">
									<?php do_action( 'tmem_view_task_details_update_before', $id ); ?>
									<div id="major-publishing-actions">
										<?php if ( tmem_can_delete_task( $task ) ) : ?>
											<div id="delete-action">
												<a href="<?php echo wp_nonce_url( $delete_url, 'tmem_task_nonce' ); ?>" class="tmem-delete-task tmem-delete">
													<?php esc_html_e( 'Delete Task', 'mobile-events-manager' ); ?>
												</a>
											</div>
										<?php endif; ?>
										<input type="submit" class="button button-primary right" value="<?php esc_html_e( 'Save Task', 'mobile-events-manager' ); ?>"/>
										<div class="clear"></div>
									</div>
									<?php do_action( 'tmem_view_task_details_update_after', $id ); ?>
								</div><!-- /.tmem-order-update-box -->

							</div><!-- /#tmem-task-data -->

							<?php do_action( 'tmem_view_task_details_sidebar_after', $id ); ?>

						</div><!-- /#side-sortables -->
					</div><!-- /#postbox-container-1 -->

					<div id="postbox-container-2" class="postbox-container">
						<div id="normal-sortables" class="meta-box-sortables ui-sortable">

							<?php do_action( 'tmem_view_task_details_main_before', $id ); ?>

							<div id="tmem-task-details" class="postbox">
								<h3 class="hndle">
									<span><?php esc_html_e( 'Task Details', 'mobile-events-manager' ); ?></span>
								</h3>
								<div class="inside tmem-clearfix">

									<div class="column-container task-info">
										<div class="column">
											<strong><?php esc_html_e( 'Name:', 'mobile-events-manager' ); ?></strong>
											<br />
											<?php
											echo TMEM()->html->text(
												array(
													'id'   => 'tmem-task-name',
													'name' => 'task_name',
													'value' => esc_html( $task['name'] ),
												)
											);
											?>
										</div>
										<div class="column column-2">
											<strong><?php esc_html_e( 'Frequency:', 'mobile-events-manager' ); ?></strong>
											<br />
											<?php
											echo TMEM()->html->select(
												array(
													'options' => tmem_get_task_schedule_options(),
													'name' => 'task_frequency',
													'id'   => 'tmem-task-frequency',
													'selected' => $task['frequency'],
												)
											);
											?>
										</div>
									</div>

									<?php do_action( 'tmem_task_view_details_after_info', $id ); ?>

									<div class="column-container task-info">
										<p><strong><?php esc_html_e( 'Description:', 'mobile-events-manager' ); ?></strong>
										<br />
										<?php
										echo TMEM()->html->textarea(
											array(
												'name'  => 'task_description',
												'value' => esc_html( $task['desc'] ),
												'class' => 'large-text description',
											)
										);
										?>
										</p>
									</div>

									<?php do_action( 'tmem_task_view_details_after_description', $id ); ?>

									<div class="column-container task-info<?php echo $hide_runtimes; ?>">
										<p><strong><?php esc_html_e( 'Run this task:', 'mobile-events-manager' ); ?></strong>
										<br />
										<?php
											$run_intervals = array();
										for ( $i = 1; $i <= 12; $i++ ) {
											$run_intervals[ $i ] = $i;
										}

										?>
										<?php
										echo TMEM()->html->select(
											array(
												'name'     => 'task_run_time',
												'id'       => 'task-run-time',
												'selected' => $run_when[0],
												'options'  => $run_intervals,
											)
										);
										?>
										&nbsp;&nbsp;
										<?php
										echo TMEM()->html->select(
											array(
												'name'     => 'task_run_period',
												'id'       => 'task-run-period',
												'selected' => $run_when[1],
												'options'  => array(
													'HOUR' => __( 'Hour(s)', 'mobile-events-manager' ),
													'DAY'  => __( 'Day(s)', 'mobile-events-manager' ),
													'WEEK' => __( 'Week(s)', 'mobile-events-manager' ),
													'MONTH' => __( 'Month(s)', 'mobile-events-manager' ),
													'YEAR' => __( 'Year(s)', 'mobile-events-manager' ),
												),
											)
										);
										?>
										&nbsp;&nbsp;
										<?php
										echo TMEM()->html->select(
											array(
												'name'     => 'task_run_event_status',
												'id'       => 'task-run-event-status',
												'selected' => $task['options']['run_when'],
												'options'  => $run_times,
											)
										);
										?>
										</p>
									</div>

									<?php do_action( 'tmem_task_view_details', $id ); ?>

								</div><!-- /.inside -->
							</div><!-- /#tmem-task-details -->

							<?php do_action( 'tmem_view_task_details_main_after', $id ); ?>

							<?php if ( isset( $task['options']['email_template'] ) ) : ?>

								<?php do_action( 'tmem_view_task_details_email_options_before', $id ); ?>

								<div id="tmem-task-email-options" class="postbox">
									<h3 class="hndle">
										<span><?php esc_html_e( 'Email Options', 'mobile-events-manager' ); ?></span>
									</h3>
									<div class="inside tmem-clearfix">
										<div class="column-container email-options">
											<div class="column">
												<strong><?php esc_html_e( 'Email Template:', 'mobile-events-manager' ); ?></strong>
												<br />
												<?php
												echo TMEM()->html->select(
													array(
														'options' => tmem_list_templates( 'email_template' ),
														'name' => 'task_email_template',
														'id' => 'tmem-task-email-template',
														'selected' => $task['options']['email_template'],
													)
												);
												?>
											</div>
											<div class="column column-2">
												<strong><?php esc_html_e( 'Subject:', 'mobile-events-manager' ); ?></strong>
												<br />
												<?php
												echo TMEM()->html->text(
													array(
														'id' => 'tmem-task-email-subject',
														'name' => 'task_email_subject',
														'value' => esc_html( $task['options']['email_subject'] ),
													)
												);
												?>
											</div>
										</div>
										<div class="column-container email-options">
											<div class="column">
												<p><strong><?php esc_html_e( 'Email From:', 'mobile-events-manager' ); ?></strong>
												<br />
												<?php
												echo TMEM()->html->select(
													array(
														'options' => array(
															'admin' => __( 'System Administrator', 'mobile-events-manager' ),
															'employee' => __( 'Primary Employee', 'mobile-events-manager' ),
														),
														'name' => 'task_email_from',
														'id' => 'tmem-task-email-from',
														'selected' => $task['options']['email_from'],
													)
												);
												?>
												</p>
											</div>
										</div>
									</div>
								<?php endif; ?>
							</div><!-- #tmem-task-email-options -->

						</div><!-- /#normal-sortables -->
					</div><!-- #postbox-container-2 -->
				</div><!-- /#post-body -->
			</div><!-- #tmem-dashboard-widgets-wrap -->
		</div><!-- /#post-stuff -->
		<?php do_action( 'tmem_view_task_details_form_bottom', $id ); ?>
		<?php wp_nonce_field( 'tmem_update_task_details_nonce', 'tmem_task_nonce' ); ?>
		<input type="hidden" name="tmem_task_id" value="<?php echo esc_attr( $id ); ?>"/>
		<input type="hidden" name="tmem-action" value="update_task_details"/>
	</form>
	<?php do_action( 'tmem_view_task_details_after', $id ); ?>
</div><!-- /.wrap -->

	<?php

} // tmem_render_single_task_view
