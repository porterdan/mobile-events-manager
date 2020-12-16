<?php
/**
 * Admin Notices
 *
 * @package MEM
 * @subpackage Admin/Notices
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve all dismissed notices.
 *
 * @since 1.5
 * @return array Array of dismissed notices
 */
function mem_dismissed_notices() {

	global $current_user;

	$user_notices = (array) get_user_option( 'mem_dismissed_notices', $current_user->ID );

	return esc_html( $user_notices );

} // mem_dismissed_notices

/**
 * Check if a specific notice has been dismissed.
 *
 * @since 1.5
 * @param string $notice Notice to check.
 * @return bool Whether or not the notice has been dismissed
 */
function mem_is_notice_dismissed( $notice ) {

	$dismissed = mem_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed ) ) {
		return true;
	} else {
		return false;
	}

} // mem_is_notice_dismissed

/**
 * Dismiss a notice.
 *
 * @since 1.5
 * @param string $notice Notice to dismiss.
 * @return bool|int True on success, false on failure, meta ID if it didn't exist yet
 */
function mem_dismiss_notice( $notice ) {

	global $current_user;
	$dismissed_notices = (array) mem_dismissed_notices();
	$new               = $dismissed_notices;

	if ( ! array_key_exists( $notice, $dismissed_notices ) ) {
		$new[ $notice ] = 'true';
	}

	$update = update_user_option( $current_user->ID, 'mem_dismissed_notices', $new );

	return esc_html( $update );

} // mem_dismiss_notice

/**
 * Restore a dismissed notice.
 *
 * @since 1.5
 * @param string $notice Notice to restore.
 * @return bool|int True on success, false on failure, meta ID if it didn't exist yet
 */
function mem_restore_notice( $notice ) {

	global $current_user;

	$dismissed_notices = (array) mem_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed_notices ) ) {
		unset( $dismissed_notices[ $notice ] );
	}

	$update = update_user_option( $current_user->ID, 'mem_dismissed_notices', $dismissed_notices );

	return esc_html( $update );

} // mem_restore_notice

/**
 * Admin Messages
 *
 * @since 1.3
 * @global $mem_options Array of all the MEM Options
 * @return void
 */
function mem_admin_notices() {
	global $mem_options;

	// Unattended events.
	if ( mem_employee_can( 'manage_all_events' ) && ( mem_get_option( 'warn_unattended' ) ) ) {
		$unattended = mem_event_count( 'mem-unattended' );

		if ( ! empty( $unattended ) && $unattended > 0 ) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' .
			sprintf(
					/* translators: %s: URL of site */
				wp_kses_post( 'You have unattended enquiries. <a href="%s">Click here</a> to manage.', 'mobile-events-manager' ),
				wp_kses_post( admin_url( 'edit.php?post_type=mem-event&post_status=mem-unattended' ) )
			) . '</p>';
			echo '</div>';
		}
	}

	// Settings.
	if ( isset( $_GET['mem-message'] ) && 'upgrade-completed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-upgraded',
			__( 'Mobile Events Manager (MEM) has been upgraded successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}

	// Availability.
	if ( isset( $_GET['mem-message'] ) && 'absence-added' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-absence-added',
			__( 'Absence added.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'absence-fail' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-absence-fail',
			__( 'Absence could not be added.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'absence-removed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-absence-deleted',
			__( 'Absence deleted.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'absence-delete-fail' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-absence-remove-fail',
			__( 'Absence could not be deleted.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'song_added' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-added-song',
			__( 'Entry added to playlist.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'adding_song_failed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-adding-song-failed',
			__( 'Could not add entry to playlist.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'song_removed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-removed-song',
			__( 'The selected songs were removed.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'song_remove_failed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-remove-faled',
			__( 'The songs count not be removed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'security_failed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-security-failed',
			__( 'Security verification failed. Action not completed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'playlist_emailed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-playlist-emailed',
			__( 'The playlist was emailed successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'playlist_email_failed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-playlist-email-failed',
			__( 'The playlist could not be emailed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['mem-message'] ) && 'employee_added' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-employee_added',
			__( 'Employee added.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'employee_add_failed' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-employee_add-failed',
			__( 'Could not add employee.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'employee_info_missing' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-employee_info-missing',
			__( 'Insufficient information to create employee.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'comm_missing_content' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-comm_content-missing',
			__( 'Not all required fields have been completed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'comm_sent' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-comm_sent',
			__( 'Email sent successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'comm_not_sent' !== wp_verify_nonce( sanitize_key( $_GET['mem-message'] ) ) ) {
		add_settings_error(
			'mem-notices',
			'mem-comm_not_sent',
			__( 'Email not sent.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-action'] ) && 'get_event_availability' !== wp_verify_nonce( sanitize_key( $_GET['mem-action'] ) ) ) {

		if ( ! isset( $_GET['mem_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['mem_nonce'], 'get_event_availability' ) ) ) {
			return;
		} elseif ( ! isset( $_GET['event_id'] ) ) {
			return;
		} else {

			$date = get_post_meta(
				sanitize_text_field( wp_unslash( $_GET['event_id'] ) ),
				'_mem_event_date',
				true
			);

			$result = mem_do_availability_check( $date );

			if ( ! empty( $result['available'] ) ) {

				echo '<ul>';

				foreach ( $result['available'] as $employee_id ) {
					echo '<li>';
					printf(
						wp_kses_post( '<a href="%1$s" title="Assign &amp; Respond to Enquiry">Assign %2$s &amp; respond to enquiry</a>', 'mobile-events-manager' ),
						esc_attr( add_query_arg( 'primary_employee', $employee_id ) ),
						get_edit_post_link( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						esc_attr( mem_get_employee_display_name( $employee_id ) )
					);
					echo '</li>';
				}

				echo '</ul>';

				echo '<div class="notice notice-info is-dismissible">';
				echo '<p>';
				printf(
					wp_kses(
						/* translators: %1: number of employees %2: Event Type %3: Event Type %4: Date of Event */
						__( 'You have %1$d employees available to work %2$s %3$s on %4$s.', 'mobile-events-manager' ),
						count( $result['available'] ),
						esc_html( mem_get_label_singular( true ) ),
						mem_get_event_contract_id( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						mem_get_event_long_date( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) )
					)
				);
				echo '</p>';
				echo '</div>';

			} else {

				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>';
				printf(
					wp_kses(
						/* translators: %1: Event Type %2: Event Type %3: Date of Event */
						__( 'There are no employees available to work %1$s %2$s on %3$s', 'mobile-events-manager' ),
						esc_html( mem_get_label_singular( true ) ),
						mem_get_event_contract_id( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						mem_get_event_long_date( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) )
					)
				);
				echo '</p>';
				echo '</div>';

			}
		}
	}
	if ( isset( $_GET['mem-message'] ) && 'payment_event_missing' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_event_missing',
			__( 'Event not identified.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'pay_employee_failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_employee_failed',
			__( 'Unable to make payment to employee.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'pay_all_employees_failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_employees_failed',
			__( 'Unable to make payment to employees.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'pay_all_employees_some_success' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_all_employees_some_success',
			__( 'Not all employees could be paid.', 'mobile-events-manager' ),
			'notice-info'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'pay_employee_success' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_employeee_success',
			__( 'Employee successfully paid.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'pay_all_employees_success' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-payment_all_employeees_success',
			__( 'Employees successfully paid.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'unattended_enquiries_rejected_success' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-unattended_enquiries_rejected_success',
			sprintf(
				/* translators: %1: Name of Employee %2: Name of Customer %3: Event Type */
				_n( '%1$s %2$s successfully rejected.', '%1$s %3$s successfully rejected.', sanitize_text_field( wp_unslash( $_GET['mem-count'] ) ), 'mobile-events-manager' ),
				sanitize_text_field( wp_unslash( $_GET['mem-count'] ) ),
				esc_html( mem_get_label_singular() ),
				esc_html( mem_get_label_plural() )
			),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'unattended_enquiries_rejected_failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-unattended_enquiries_rejected_failed',
			__( 'Errors were encountered.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'api-key-generated' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-api-key-generated',
			__( 'API keys generated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'api-key-regenerated' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-api-key-regenerated',
			__( 'API keys re-generated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'api-key-revoked' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-api-key-revoked',
			__( 'API keys revoked.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'api-key-failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-api-key-failed',
			__( 'Generating API keys failed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-status-updated' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-task-status-updated',
			__( 'Task status updated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-status-update-failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-task-status-update-failed',
			__( 'Task status could not be updated.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-run' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-task-run',
			__( 'Task executed successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-run-failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-run-failed',
			__( 'Task could not be executed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-updated' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-task-updated',
			__( 'Task updated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'task-update-failed' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-update-failed',
			__( 'Task update failed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['mem-message'] ) && 'settings-imported' !== sanitize_key( $_GET['mem-message'] ) ) {
		add_settings_error(
			'mem-notices',
			'mem-settings-imported',
			__( 'Settings sucessfully imported.', 'mobile-events-manager' ),
			'updated'
		);
	}

	settings_errors( 'mem-notices' );

} // mem_admin_messages
add_action( 'admin_notices', 'mem_admin_notices' );

/**
 * Admin WP Rating Request Notice
 *
 * @since 1.5
 * @return void
 */
function mem_admin_wp_5star_rating_notice() {
	ob_start(); ?>

	<div class="updated notice notice-mem-dismiss is-dismissible" data-notice="mem_request_wp_5star_rating">
		<p>
			<?php esc_html_e( '<strong>Awesome!</strong> It looks like you have using Mobile Events Manager (MEM) for a while now which is really fantastic!', 'mobile-events-manager' ); ?>
		</p>
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %1: MEM URL */
					__( 'Would you <strong>please</strong> do us a favour and leave a 5 star rating on WordPress.org? It only takes a minute and it <strong>really helps</strong> to motivate our developers and volunteers to continue to work on great new features and functionality. <a href="%1$s" target="_blank">Sure thing, you deserve it!</a>', 'mobile-events-manager' ),
					'https://wordpress.org/support/plugin/mobile-events-manager/reviews/'
				)
			);
			?>
		</p>
	</div>

	<?php
	echo esc_html( ob_get_clean() );
} // mem_admin_wp_5star_rating_notice

/**
 * Request 5 star rating after 5 events have completed.
 *
 * After 5 completed events we ask the admin for a 5 star rating on WordPress.org
 *
 * @since 1.5
 * @return void
 */
function mem_request_wp_5star_rating() {

	global $typenow, $pagenow;

	$allowed_types = array(
		'mem-event',
		'mem-package',
		'mem-addon',
		'mem_communication',
		'contract',
		'email_template',
		'mem-playlist',
		'mem-transaction',
		'mem-venue',
	);
	$allowed_pages = array( 'edit.php', 'post.php', 'post-new.php', 'index.php', 'plugins.php' );

	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	if ( ! in_array( $typenow, $allowed_types ) && ! in_array( $pagenow, $allowed_pages ) ) {
		return;
	}

	if ( mem_is_notice_dismissed( 'mem_request_wp_5star_rating' ) ) {
		return;
	}

	global $wpdb;

	$completed_events = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT COUNT(*)
			FROM $wpdb->posts
			WHERE `post_type` = %s
			AND `post_status` = %s
			",
			'mem-event',
			'mem-completed'
		)
	);

	if ( $completed_events >= 5 ) {
		add_action( 'admin_notices', 'mem_admin_wp_5star_rating_notice' );
	}

} // mem_request_wp_5star_rating
add_action( 'plugins_loaded', 'mem_request_wp_5star_rating' );
