<?php
/**
 * Availability Page
 *
 * @package TMEM
 * @subpackage Availability/Functions
 * @copyright Copyright (c) 2018, Mike Howard
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.5.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the availability action links.
 *
 * @since 1.5.6
 * @return arr Array of action links
 */
function tmem_get_availability_page_action_links() {

	$actions = array();

	$actions['add_absence'] = '<a id="tmem-add-absence-action" href="#" class="toggle-add-absence-section">' . __( 'Show absence form', 'mobile-events-manager' ) . '</a>';

	// $actions['availabiility_check'] = '<a id="tmem-check-availabilty-action" href="#" class="toggle-availability-checker-section">' . __( 'Show availability checker', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'tmem_availability_page_actions', $actions );

	return $actions;
} // tmem_get_availability_page_action_links

/**
 * Generate the availability page
 *
 * @since 1.5.6
 */
function tmem_availability_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<div id="tmem_availability_fields" class="tmem_meta_table_wrap">

			<div class="widefat tmem_repeatable_table">

				<div class="tmem-availability-fields tmem-repeatables-wrap">

					<div class="tmem_availability_wrapper">

						<div class="tmem-availability-row-header">
							<?php
							$actions = tmem_get_availability_page_action_links();
							?>

							<span class="tmem-repeatable-row-actions">
								<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
							</span>
						</div>

						<div class="tmem-repeatable-row-standard-fields">
							<div id="tmem-pending-notice" class="notice"></div>
							<?php do_action( 'tmem_availability_page_standard_sections' ); ?>
						</div>
						<?php do_action( 'tmem_availability_page_custom_sections' ); ?>
					</div>

				</div>

			</div>

		</div>
	</div><!--wrap-->
	<?php
} // tmem_availability_page

/**
 * Displays the availability checker
 *
 * @since 1.5.6
 */
function tmem_render_availability_page_checker() {
	$employee_id = get_current_user_id();
	$artist      = esc_attr( tmem_get_option( 'artist' ) );

	tmem_insert_datepicker(
		array(
			'id'       => 'display_date',
			'altfield' => 'check_date',
			'mindate'  => 'today',
		)
	);
	?>

	<div class="tmem-availability-checker-fields">
		<div class="tmem-availability-check-date">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Date', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'  => 'display_date',
					'id'    => 'display_date',
					'class' => 'tmem_date',
				)
			);
			?>
			<?php
			echo TMEM()->html->hidden(
				array(
					'name' => 'check_date',
					'id'   => 'check_date',
				)
			);
			?>
		</div>

		<?php if ( tmem_employee_can( 'manage_employees' ) ) : ?>
			<div class="tmem-event-primary-employee">
				<span class="tmem-repeatable-row-setting-label">
					/* translators: %s: Employee */
					<?php printf( esc_attr( '%s', $artist ) ); ?>
				</span>

				<?php
				echo TMEM()->html->employee_dropdown(
					array(
						'name'        => 'check_employee',
						'group'       => esc_html( tmem_is_employer() ),
						'chosen'      => true,
						'placeholder' => __( 'Checking all employees', 'mobile-events-manager' ),
						'multiple'    => true,
						'selected'    => array(),
					)
				);
				?>
			</div>

			<div class="tmem-event-primary-employee">
				<span class="tmem-repeatable-row-setting-label">
					<?php esc_html_e( 'Roles', 'mobile-events-manager' ); ?>
				</span>

				<?php
				echo TMEM()->html->roles_dropdown(
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
			echo TMEM()->html->hidden(
				array(
					'name'  => 'check_employee',
					'value' => $employee_id,
				)
			);
			?>
		<?php endif; ?>
		<br>
		<p><span class="tmem-event-worker-add">
			<a id="check-availability" class="button button-secondary">
				<?php esc_html_e( 'Check Availability', 'mobile-events-manager' ); ?>
			</a>
		</span></p>
	</div>
	<?php
} // tmem_render_availability_page_checker
add_action( 'tmem_availability_page_standard_sections', 'tmem_render_availability_page_checker', 5 );

/**
 * Displays the availability calendar
 *
 * @since 1.5.6
 */
function tmem_render_availability_page_calendar() {
	?>
	<div id="tmem-calendar"></div>
	<?php
} // tmem_render_availability_page_calendar
add_action( 'tmem_availability_page_standard_sections', 'tmem_render_availability_page_calendar' );

/**
 * Displays the absence entry form
 *
 * @since 1.5.6
 */
function tmem_render_availability_absence_form() {
	$employee_id = get_current_user_id();
	$ampm        = 'H:i' !== tmem_get_option( 'time_format', 'H:i' ) ? true : false;

	tmem_insert_datepicker(
		array(
			'id'       => 'display_absence_start',
			'altfield' => 'absence_start',
			'mindate'  => 'today',
		)
	);
	tmem_insert_datepicker(
		array(
			'id'       => 'display_absence_end',
			'altfield' => 'absence_end',
		)
	);
	?>
	<div id="tmem-add-absence-fields" class="tmem-availability-add-absence-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php esc_html_e( 'Add Employee Absence', 'mobile-events-manager' ); ?></span>
				<?php if ( tmem_employee_can( 'manage_employees' ) ) : ?>
					<span class="tmem-employee-option">
						<label class="tmem-employee-id">
							<?php esc_html_e( 'Employee', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo TMEM()->html->employee_dropdown(
							array(
								'name'     => 'absence_employee_id',
								'selected' => $employee_id,
								'group'    => tmem_is_employer(),
								'chosen'   => true,
							)
						);
						?>
					</span>
				<?php else : ?>
					<?php
					echo TMEM()->html->hidden(
						array(
							'name'  => 'absence_employee_id',
							'value' => $employee_id,
						)
					);
					?>
				<?php endif; ?>

				<span class="tmem-absence-start-option">
					<label class="tmem-absence-start">
						<?php esc_html_e( 'From', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'display_absence_start',
							'id'    => 'display_absence_start',
							'class' => 'tmem_date',
						)
					);
					?>
					<?php
					echo TMEM()->html->hidden(
						array(
							'name' => 'absence_start',
							'id'   => 'absence_start',
						)
					);
					?>
				</span>

				<span class="tmem-absence-end-option">
					<label class="tmem-absence-end">
						<?php esc_html_e( 'To', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'display_absence_end',
							'id'    => 'display_absence_end',
							'class' => 'tmem_date',
						)
					);
					?>
					<?php
					echo TMEM()->html->hidden(
						array(
							'name' => 'absence_end',
							'id'   => 'absence_end',
						)
					);
					?>
				</span>

				<div class="tmem-repeatable-option">
					<span class="tmem-absence-allday-option">
						<label class="tmem-absence-all-day">
							<?php esc_html_e( 'All day?', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo TMEM()->html->checkbox(
							array(
								'name'    => 'absence_all_day',
								'current' => true,
							)
						);
						?>
					</span>
				</div>

				<span class="tmem-absence-start-time-option tmem-hidden">
					<label class="tmem-absence-start-hour">
						<?php esc_html_e( 'Start time', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->time_hour_select(
						array(
							'name' => 'absence_start_time_hr',
						)
					);
					?>

					<?php
					echo TMEM()->html->time_minute_select(
						array(
							'name' => 'absence_start_time_min',
						)
					);
					?>

					<?php if ( $ampm ) : ?>
						<?php
						echo TMEM()->html->time_period_select(
							array(
								'name' => 'absence_start_time_period',
							)
						);
						?>
					<?php endif; ?>
				</span>

				<span class="tmem-absence-end-time-option">
					<label class="tmem-absence-end-hour">
						<?php esc_html_e( 'End time', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->time_hour_select(
						array(
							'name' => 'absence_end_time_hr',
						)
					);
					?>

					<?php
					echo TMEM()->html->time_minute_select(
						array(
							'name' => 'absence_end_time_min',
						)
					);
					?>

					<?php if ( $ampm ) : ?>
						<?php
						echo TMEM()->html->time_period_select(
							array(
								'name' => 'absence_end_time_period',
							)
						);
						?>
					<?php endif; ?>
				</span>

				<br>
				<span class="tmem-absence-notes-option">
					<label class="tmem-absence-notes">
						<?php esc_html_e( 'Notes', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->textarea(
						array(
							'name'        => 'absence_notes',
							'placeholder' => __( 'i.e. On holiday', 'mobile-events-manager' ),
							'class'       => 'tmem_form_fields',
						)
					);
					?>
				</span>

				<br>
				<span class="tmem-absence-submit-option">
					<button id="add-absence" class="button button-secondary">
						<?php esc_html_e( 'Add Absence', 'mobile-events-manager' ); ?>
					</button>
				</span>

			</div>
		</div>
	</div>
	<?php
} // tmem_render_availability_absence_form
add_action( 'tmem_availability_page_custom_sections', 'tmem_render_availability_absence_form' );
