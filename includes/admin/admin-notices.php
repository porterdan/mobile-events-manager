<?php
/**
 * Admin Notices
 *
 * @package TMEM
 * @subpackage Admin/Notices
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
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
function tmem_dismissed_notices() {

	global $current_user;

	$user_notices = (array) get_user_option( 'tmem_dismissed_notices', $current_user->ID );

	return esc_html( $user_notices );

} // tmem_dismissed_notices

/**
 * Check if a specific notice has been dismissed.
 *
 * @since 1.5
 * @param string $notice Notice to check.
 * @return bool Whether or not the notice has been dismissed
 */
function tmem_is_notice_dismissed( $notice ) {

	$dismissed = (array) tmem_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed ) ) {
		return true;
	} else {
		return false;
	}

} // tmem_is_notice_dismissed

/**
 * Dismiss a notice.
 *
 * @since 1.5
 * @param string $notice Notice to dismiss.
 * @return bool|int True on success, false on failure, meta ID if it didn't exist yet
 */
function tmem_dismiss_notice( $notice ) {

	global $current_user;
	$dismissed_notices = (array) tmem_dismissed_notices();
	$new               = $dismissed_notices;

	if ( ! array_key_exists( $notice, $dismissed_notices ) ) {
		$new[ $notice ] = 'true';
	}

	$update = update_user_option( $current_user->ID, 'tmem_dismissed_notices', $new );

	return esc_html( $update );

} // tmem_dismiss_notice

/**
 * Restore a dismissed notice.
 *
 * @since 1.5
 * @param string $notice Notice to restore.
 * @return bool|int True on success, false on failure, meta ID if it didn't exist yet
 */
function tmem_restore_notice( $notice ) {

	global $current_user;

	$dismissed_notices = (array) tmem_dismissed_notices();

	if ( array_key_exists( $notice, $dismissed_notices ) ) {
		unset( $dismissed_notices[ $notice ] );
	}

	$update = update_user_option( $current_user->ID, 'tmem_dismissed_notices', $dismissed_notices );

	return esc_html( $update );

} // tmem_restore_notice

/**
 * Admin Messages
 *
 * @since 1.3
 * @global $tmem_options Array of all the TMEM Options
 * @return void
 */
function tmem_admin_notices() {
	global $tmem_options;

	// Unattended events.
	if ( tmem_employee_can( 'manage_all_events' ) && ( tmem_get_option( 'warn_unattended' ) ) ) {
		$unattended = tmem_event_count( 'tmem-unattended' );

		if ( ! empty( $unattended ) && $unattended > 0 ) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>' .
			sprintf(
					/* translators: %s: URL of site */
				wp_kses_post( 'You have unattended enquiries. <a href="%s">Click here</a> to manage.', 'mobile-events-manager' ),
				wp_kses_post( admin_url( 'edit.php?post_type=tmem-event&post_status=tmem-unattended' ) )
			) . '</p>';
			echo '</div>';
		}
	}

	// Settings.
	if ( isset( $_GET['tmem-message'] ) && 'upgrade-completed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-upgraded',
			__( 'TMEM Event Management has been upgraded successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}

	// Availability.
	if ( isset( $_GET['tmem-message'] ) && 'absence-added' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-absence-added',
			__( 'Absence added.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'absence-fail' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-absence-fail',
			__( 'Absence could not be added.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'absence-removed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-absence-deleted',
			__( 'Absence deleted.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'absence-delete-fail' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-absence-remove-fail',
			__( 'Absence could not be deleted.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'song_added' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-added-song',
			__( 'Entry added to playlist.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'adding_song_failed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-adding-song-failed',
			__( 'Could not add entry to playlist.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'song_removed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-removed-song',
			__( 'The selected songs were removed.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'song_remove_failed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-remove-faled',
			__( 'The songs count not be removed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'security_failed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-security-failed',
			__( 'Security verification failed. Action not completed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'playlist_emailed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-playlist-emailed',
			__( 'The playlist was emailed successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'playlist_email_failed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-playlist-email-failed',
			__( 'The playlist could not be emailed.', 'mobile-events-manager' ),
			'error'
		);
	}

	if ( isset( $_GET['tmem-message'] ) && 'employee_added' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-employee_added',
			__( 'Employee added.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'employee_add_failed' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-employee_add-failed',
			__( 'Could not add employee.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'employee_info_missing' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-employee_info-missing',
			__( 'Insufficient information to create employee.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'comm_missing_content' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-comm_content-missing',
			__( 'Not all required fields have been completed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'comm_sent' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-comm_sent',
			__( 'Email sent successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'comm_not_sent' !== wp_verify_nonce( sanitize_key( $_GET['tmem-message'] ) ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-comm_not_sent',
			__( 'Email not sent.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-action'] ) && 'get_event_availability' !== wp_verify_nonce( sanitize_key( $_GET['tmem-action'] ) ) ) {

		if ( ! isset( $_GET['tmem_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['tmem_nonce'], 'get_event_availability' ) ) ) {
			return;
		} elseif ( ! isset( $_GET['event_id'] ) ) {
			return;
		} else {

			$date = get_post_meta(
				sanitize_text_field( wp_unslash( $_GET['event_id'] ) ),
				'_tmem_event_date',
				true
			);

			$result = tmem_do_availability_check( $date );

			if ( ! empty( $result['available'] ) ) {

				echo '<ul>';

				foreach ( $result['available'] as $employee_id ) {
					echo '<li>';
					printf(
						wp_kses_post( '<a href="%1$s" title="Assign &amp; Respond to Enquiry">Assign %2$s &amp; respond to enquiry</a>', 'mobile-events-manager' ),
						esc_attr( add_query_arg( 'primary_employee', $employee_id ) ),
						get_edit_post_link( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						esc_attr( tmem_get_employee_display_name( $employee_id ) )
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
						esc_html( tmem_get_label_singular( true ) ),
						tmem_get_event_contract_id( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						tmem_get_event_long_date( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) )
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
						esc_html( tmem_get_label_singular( true ) ),
						tmem_get_event_contract_id( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ),
						tmem_get_event_long_date( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) )
					)
				);
				echo '</p>';
				echo '</div>';

			}
		}
	}
	if ( isset( $_GET['tmem-message'] ) && 'payment_event_missing' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_event_missing',
			__( 'Event not identified.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'pay_employee_failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_employee_failed',
			__( 'Unable to make payment to employee.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'pay_all_employees_failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_employees_failed',
			__( 'Unable to make payment to employees.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'pay_all_employees_some_success' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_all_employees_some_success',
			__( 'Not all employees could be paid.', 'mobile-events-manager' ),
			'notice-info'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'pay_employee_success' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_employeee_success',
			__( 'Employee successfully paid.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'pay_all_employees_success' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-payment_all_employeees_success',
			__( 'Employees successfully paid.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'unattended_enquiries_rejected_success' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-unattended_enquiries_rejected_success',
			sprintf(
				/* translators: %1: Name of Employee %2: Name of Customer %3: Event Type */
				_n( '%1$s %2$s successfully rejected.', '%1$s %3$s successfully rejected.', sanitize_text_field( wp_unslash( $_GET['tmem-count'] ) ), 'mobile-events-manager' ),
				sanitize_text_field( wp_unslash( $_GET['tmem-count'] ) ),
				esc_html( tmem_get_label_singular() ),
				esc_html( tmem_get_label_plural() )
			),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'unattended_enquiries_rejected_failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-unattended_enquiries_rejected_failed',
			__( 'Errors were encountered.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'api-key-generated' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-api-key-generated',
			__( 'API keys generated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'api-key-regenerated' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-api-key-regenerated',
			__( 'API keys re-generated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'api-key-revoked' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-api-key-revoked',
			__( 'API keys revoked.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'api-key-failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-api-key-failed',
			__( 'Generating API keys failed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-status-updated' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-task-status-updated',
			__( 'Task status updated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-status-update-failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-task-status-update-failed',
			__( 'Task status could not be updated.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-run' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-task-run',
			__( 'Task executed successfully.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-run-failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-run-failed',
			__( 'Task could not be executed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-updated' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-task-updated',
			__( 'Task updated.', 'mobile-events-manager' ),
			'updated'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'task-update-failed' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-update-failed',
			__( 'Task update failed.', 'mobile-events-manager' ),
			'error'
		);
	}
	if ( isset( $_GET['tmem-message'] ) && 'settings-imported' !== sanitize_key( $_GET['tmem-message'] ) ) {
		add_settings_error(
			'tmem-notices',
			'tmem-settings-imported',
			__( 'Settings sucessfully imported.', 'mobile-events-manager' ),
			'updated'
		);
	}

	settings_errors( 'tmem-notices' );

} // tmem_admin_messages
add_action( 'admin_notices', 'tmem_admin_notices' );

/**
 * Admin WP Rating Request Notice
 *
 * @since 1.5
 * @return void
 */
function tmem_admin_wp_5star_rating_notice() {
	ob_start(); ?>

	<div class="updated notice notice-tmem-dismiss is-dismissible" data-notice="tmem_request_wp_5star_rating">
		<p>
			<?php esc_html_e( '<strong>Awesome!</strong> It looks like you have using TMEM Event Management for a while now which is really fantastic!', 'mobile-events-manager' ); ?>
		</p>
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %1: TMEM URL */
					__( 'Would you <strong>please</strong> do us a favour and leave a 5 star rating on WordPress.org? It only takes a minute and it <strong>really helps</strong> to motivate our developers and volunteers to continue to work on great new features and functionality. <a href="%1$s" target="_blank">Sure thing, you deserve it!</a>', 'mobile-events-manager' ),
					'https://wordpress.org/support/plugin/mobile-events-manager/reviews/'
				)
			);
			?>
		</p>
	</div>

	<?php
	echo esc_html( ob_get_clean() );
} // tmem_admin_wp_5star_rating_notice

/**
 * Request 5 star rating after 5 events have completed.
 *
 * After 5 completed events we ask the admin for a 5 star rating on WordPress.org
 *
 * @since 1.5
 * @return void
 */
function tmem_request_wp_5star_rating() {

	global $typenow, $pagenow;

	$allowed_types = array(
		'tmem-event',
		'tmem-package',
		'tmem-addon',
		'tmem_communication',
		'contract',
		'email_template',
		'tmem-playlist',
		'tmem-transaction',
		'tmem-venue',
	);
	$allowed_pages = array( 'edit.php', 'post.php', 'post-new.php', 'index.php', 'plugins.php' );

	if ( ! current_user_can( 'administrator' ) ) {
		return;
	}

	if ( ! in_array( $typenow, $allowed_types ) && ! in_array( $pagenow, $allowed_pages ) ) {
		return;
	}

	if ( tmem_is_notice_dismissed( 'tmem_request_wp_5star_rating' ) ) {
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
			'tmem-event',
			'tmem-completed'
		)
	);

	if ( $completed_events >= 5 ) {
		add_action( 'admin_notices', 'tmem_admin_wp_5star_rating_notice' );
	}

} // tmem_request_wp_5star_rating
add_action( 'plugins_loaded', 'tmem_request_wp_5star_rating' );
