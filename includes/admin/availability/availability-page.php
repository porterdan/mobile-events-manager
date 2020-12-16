<?php
/**
 * Availability Page
 *
 * @package MEM
 * @subpackage Availability/Functions
 * @copyright Copyright (c) 2020 Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the availability action links.
 *
 * @since 1.0
 * @return arr Array of action links
 */
function mem_get_availability_page_action_links() {

	$actions = array();

	$actions['add_absence'] = '<a id="mem-add-absence-action" href="#" class="toggle-add-absence-section">' . __( 'Show absence form', 'mobile-events-manager' ) . '</a>';

	// $actions['availabiility_check'] = '<a id="mem-check-availabilty-action" href="#" class="toggle-availability-checker-section">' . __( 'Show availability checker', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'mem_availability_page_actions', $actions );

	return $actions;
} // mem_get_availability_page_action_links

/**
 * Generate the availability page
 *
 * @since 1.0
 */
function mem_availability_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<div id="mem_availability_fields" class="mem_meta_table_wrap">

			<div class="widefat mem_repeatable_table">

				<div class="mem-availability-fields mem-repeatables-wrap">

					<div class="mem_availability_wrapper">

						<div class="mem-availability-row-header">
							<?php
							$actions = mem_get_availability_page_action_links();
							?>

							<span class="mem-repeatable-row-actions">
								<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
							</span>
						</div>

						<div class="mem-repeatable-row-standard-fields">
							<div id="mem-pending-notice" class="notice"></div>
							<?php do_action( 'mem_availability_page_standard_sections' ); ?>
						</div>
						<?php do_action( 'mem_availability_page_custom_sections' ); ?>
					</div>

				</div>

			</div>

		</div>
	</div><!--wrap-->
	<?php
} // mem_availability_page

/**
 * Displays the availability checker
 *
 * @since 1.0
 */
function mem_render_availability_page_checker() {
	$employee_id = get_current_user_id();
	$artist      = esc_attr( mem_get_option( 'artist' ) );

	mem_insert_datepicker(
		array(
			'id'       => 'display_date',
			'altfield' => 'check_date',
			'mindate'  => 'today',
		)
	);
	?>

	<div class="mem-availability-checker-fields">
		<div class="mem-availability-check-date">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Date', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'  => 'display_date',
					'id'    => 'display_date',
					'class' => 'mem_date',
				)
			);
			?>
			<?php
			echo MEM()->html->hidden(
				array(
					'name' => 'check_date',
					'id'   => 'check_date',
				)
			);
			?>
		</div>

		<?php if ( mem_employee_can( 'manage_employees' ) ) : ?>
			<div class="mem-event-primary-employee">
				<span class="mem-repeatable-row-setting-label">
					/* translators: %s: Employee */
					<?php printf( esc_attr( '%s', $artist ) ); ?>
				</span>

				<?php
				echo MEM()->html->employee_dropdown(
					array(
						'name'        => 'check_employee',
						'group'       => esc_html( mem_is_employer() ),
						'chosen'      => true,
						'placeholder' => __( 'Checking all employees', 'mobile-events-manager' ),
						'multiple'    => true,
						'selected'    => array(),
					)
				);
				?>
			</div>

			<div class="mem-event-primary-employee">
				<span class="mem-repeatable-row-setting-label">
					<?php esc_html_e( 'Roles', 'mobile-events-manager' ); ?>
				</span>

				<?php
				echo MEM()->html->roles_dropdown(
					array(
						'name'        => 'check_roles',
						'chosen'      => true,
						'placeholder' => __( 'Checking all roles', 'mobile-events-manager' ),
						'multiple'    => true,
						'selected'    => array(),
					)
				);
				?>
			</div>
		<?php else : ?>
			<?php
			echo MEM()->html->hidden(
				array(
					'name'  => 'check_employee',
					'value' => $employee_id,
				)
			);
			?>
		<?php endif; ?>
		<br>
		<p><span class="mem-event-worker-add">
			<a id="check-availability" class="button button-secondary">
				<?php esc_html_e( 'Check Availability', 'mobile-events-manager' ); ?>
			</a>
		</span></p>
	</div>
	<?php
} // mem_render_availability_page_checker
add_action( 'mem_availability_page_standard_sections', 'mem_render_availability_page_checker', 5 );

/**
 * Displays the availability calendar
 *
 * @since 1.0
 */
function mem_render_availability_page_calendar() {
	?>
	<div id="mem-calendar"></div>
	<?php
} // mem_render_availability_page_calendar
add_action( 'mem_availability_page_standard_sections', 'mem_render_availability_page_calendar' );

/**
 * Displays the absence entry form
 *
 * @since 1.0
 */
function mem_render_availability_absence_form() {
	$employee_id = get_current_user_id();
	$ampm        = 'H:i' !== mem_get_option( 'time_format', 'H:i' ) ? true : false;

	mem_insert_datepicker(
		array(
			'id'       => 'display_absence_start',
			'altfield' => 'absence_start',
			'mindate'  => 'today',
		)
	);
	mem_insert_datepicker(
		array(
			'id'       => 'display_absence_end',
			'altfield' => 'absence_end',
		)
	);
	?>
	<div id="mem-add-absence-fields" class="mem-availability-add-absence-sections-wrap">
		<div class="mem-custom-event-sections">
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php esc_html_e( 'Add Employee Absence', 'mobile-events-manager' ); ?></span>
				<?php if ( mem_employee_can( 'manage_employees' ) ) : ?>
					<span class="mem-employee-option">
						<label class="mem-employee-id">
							<?php esc_html_e( 'Employee', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo MEM()->html->employee_dropdown(
							array(
								'name'     => 'absence_employee_id',
								'selected' => $employee_id,
								'group'    => mem_is_employer(),
								'chosen'   => true,
							)
						);
						?>
					</span>
				<?php else : ?>
					<?php
					echo MEM()->html->hidden(
						array(
							'name'  => 'absence_employee_id',
							'value' => $employee_id,
						)
					);
					?>
				<?php endif; ?>

				<span class="mem-absence-start-option">
					<label class="mem-absence-start">
						<?php esc_html_e( 'From', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'display_absence_start',
							'id'    => 'display_absence_start',
							'class' => 'mem_date',
						)
					);
					?>
					<?php
					echo MEM()->html->hidden(
						array(
							'name' => 'absence_start',
							'id'   => 'absence_start',
						)
					);
					?>
				</span>

				<span class="mem-absence-end-option">
					<label class="mem-absence-end">
						<?php esc_html_e( 'To', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'display_absence_end',
							'id'    => 'display_absence_end',
							'class' => 'mem_date',
						)
					);
					?>
					<?php
					echo MEM()->html->hidden(
						array(
							'name' => 'absence_end',
							'id'   => 'absence_end',
						)
					);
					?>
				</span>

				<div class="mem-repeatable-option">
					<span class="mem-absence-allday-option">
						<label class="mem-absence-all-day">
							<?php esc_html_e( 'All day?', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo MEM()->html->checkbox(
							array(
								'name'    => 'absence_all_day',
								'current' => true,
							)
						);
						?>
					</span>
				</div>

				<span class="mem-absence-start-time-option mem-hidden">
					<label class="mem-absence-start-hour">
						<?php esc_html_e( 'Start time', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->time_hour_select(
						array(
							'name' => 'absence_start_time_hr',
						)
					);
					?>

					<?php
					echo MEM()->html->time_minute_select(
						array(
							'name' => 'absence_start_time_min',
						)
					);
					?>

					<?php if ( $ampm ) : ?>
						<?php
						echo MEM()->html->time_period_select(
							array(
								'name' => 'absence_start_time_period',
							)
						);
						?>
					<?php endif; ?>
				</span>

				<span class="mem-absence-end-time-option">
					<label class="mem-absence-end-hour">
						<?php esc_html_e( 'End time', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->time_hour_select(
						array(
							'name' => 'absence_end_time_hr',
						)
					);
					?>

					<?php
					echo MEM()->html->time_minute_select(
						array(
							'name' => 'absence_end_time_min',
						)
					);
					?>

					<?php if ( $ampm ) : ?>
						<?php
						echo MEM()->html->time_period_select(
							array(
								'name' => 'absence_end_time_period',
							)
						);
						?>
					<?php endif; ?>
				</span>

				<br>
				<span class="mem-absence-notes-option">
					<label class="mem-absence-notes">
						<?php esc_html_e( 'Notes', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->textarea(
						array(
							'name'        => 'absence_notes',
							'placeholder' => __( 'i.e. On holiday', 'mobile-events-manager' ),
							'class'       => 'mem_form_fields',
						)
					);
					?>
				</span>

				<br>
				<span class="mem-absence-submit-option">
					<button id="add-absence" class="button button-secondary">
						<?php esc_html_e( 'Add Absence', 'mobile-events-manager' ); ?>
					</button>
				</span>

			</div>
		</div>
	</div>
	<?php
} // mem_render_availability_absence_form
add_action( 'mem_availability_page_custom_sections', 'mem_render_availability_absence_form' );
