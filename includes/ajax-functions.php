<?php
/**
 * AJAX Functions
 *
 * Process the AJAX actions. Frontend and backend
 *
 * @package TMEM
 * @subpackage Functions/AJAX
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get AJAX URL
 *
 * @since 1.3
 * @return str
 */
function tmem_get_ajax_url() {
	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$current_url = tmem_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'tmem_ajax_url', $ajax_url );
} // tmem_get_ajax_url

/**
 * Dismiss admin notices.
 *
 * @since 1.5
 * @return void
 */
function tmem_ajax_dismiss_admin_notice() {

	$notice = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['notice'] ) ) );
	tmem_dismiss_notice( $notice );

	wp_send_json_success();

} // tmem_ajax_dismiss_admin_notice
add_action( 'wp_ajax_tmem_dismiss_notice', 'tmem_ajax_dismiss_admin_notice' );

/**
 * Retrieve employee availability data for the calendar view.
 *
 * @since 1.5.6
 * @return void
 */
function tmem_calendar_activity_ajax() {
	$data  = array();
	$start = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start'] ) ) );
	$end   = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end'] ) ) );

	$activity = tmem_get_calendar_entries( $start, $end );

	wp_send_json( $activity );
} // tmem_calendar_activity_ajax
add_action( 'wp_ajax_tmem_calendar_activity', 'tmem_calendar_activity_ajax' );

/**
 * Adds an employee absence entry.
 *
 * @since 1.5.6
 * @return void
 */
function tmem_add_employee_absence_ajax() {
	$employee_id = absint( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ) ) );
	$start_date  = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) );
	$end_date    = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) );
	$all_day     = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['all_day'] ) ) );
	$start_time  = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start_time_hr'] ) ) . ':' . wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start_time_min'] ) ) ) );
	$end_time    = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end_time_hr'] ) ) ) . ':' . wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end_time_min'] ) ) );
	$start_time .= ! empty( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start_time_period'] ) ) ) ) ? wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start_time_period'] ) ) ) : '';
	$end_time   .= ! empty( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end_time_period'] ) ) ) ) ? sanitize_text_field( wp_unslash( $_POST['end_time_period'] ) ) : '';
	$notes       = ! empty( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['notes'] ) ) ) ) ? wp_verify_nonce( sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) ) : '';

	$data = array(
		'employee_id' => $employee_id,
		'all_day'     => $all_day,
		'start'       => $start_date,
		'start_time'  => $start_time,
		'end'         => $end_date,
		'end_time'    => $end_time,
		'notes'       => $notes,
	);

	if ( tmem_add_employee_absence( $employee_id, $data ) ) {
		$success = true;
	} else {
		$success = false;
	}

	wp_send_json(
		array(
			'success' => $success,
			'date'    => $start_date,
		)
	);

} // tmem_add_employee_absence_ajax
add_action( 'wp_ajax_tmem_add_employee_absence', 'tmem_add_employee_absence_ajax' );

/**
 * Removes an employee absence entry.
 *
 * @since 1.5.6
 * return void
 */
function tmem_delete_employee_absence_ajax() {
	$id      = absint( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );
	$deleted = tmem_remove_employee_absence( $id );

	if ( $deleted > 0 ) {
		wp_send_json_success();
	}

	wp_send_json_error();
} // tmem_delete_employee_absence_ajax
add_action( 'wp_ajax_tmem_delete_employee_absence', 'tmem_delete_employee_absence_ajax' );

/**
 * Client profile update form validation
 *
 * @since 1.5
 * @return void
 */
function tmem_validate_client_profile_form_ajax() {

	if ( ! check_ajax_referer( 'update_client_profile', 'tmem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'tmem_nonce',
			)
		);
	}

	$client_id    = absint( sanitize_text_field( wp_unslash( $_POST['tmem_client_id'] ) ) );
	$client       = new TMEM_Client( $client_id );
	$fields       = $client->get_profile_fields();
	$new_password = ! empty( sanitize_text_field( wp_unslash( $_POST['tmem_new_password'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['tmem_new_password'] ) ) : false;
	$core_fields  = array( 'first_name', 'last_name', 'user_email' );
	$update_args  = array( 'ID' => $client_id );
	$update_meta  = array();
	$display_name = '';

	foreach ( $fields as $field ) {
		if ( ! empty( $field['required'] ) && empty( sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) ) ) {
			wp_send_json(
				array(
					'error' => sprintf( esc_html__( '%s is a required field', 'mobile-events-manager' ), esc_attr( $field['label'] ) ),
					'field' => $field['id'],
				)
			);
		}

		switch ( $field['id'] ) {
			case 'user_email':
				if ( ! is_email( wp_unslash( $_POST[ $field['id'] ] ) ) ) {
					wp_send_json(
						array(
							'error' => sprintf( sanitize_text_field( esc_html__( '%s is not a valid email address', 'mobile-events-manager' ) ), esc_attr( sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) ) ),
							'field' => $field['id'],
						)
					);
				}
		}

		switch ( $field['type'] ) {
			case 'text':
			case 'dropdown':
			default:
				$value = sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) );
				if ( 'first_name' == $field['id'] || 'last_name' == $field['id'] ) {
					$value = ucfirst( trim( $value ) );

					if ( 'first_name' == $field['id'] ) {
						$display_name = ! empty( $display_name ) ? $value . ' ' . $display_name : $value;
					}

					if ( 'last_name' == $field['id'] ) {
						$display_name = ! empty( $display_name ) ? $display_name . ' ' . $value : $value;
					}
				}
				break;

			case 'checkbox':
				$value = ! empty( sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) ) ? sanitize_text_field( wp_unslash( $_POST[ $field['id'] ] ) ) : 0;
				break;
		}

		if ( in_array( $field['id'], $core_fields ) ) {
			$update_args[ $field['id'] ] = $value;
		} else {
			$update_meta[ $field['id'] ] = $value;
		}
	}

	if ( $new_password ) {
		if ( empty( sanitize_text_field( wp_unslash( $_POST['tmem_confirm_password'] ) ) ) || sanitize_text_field( wp_unslash( $_POST['tmem_confirm_password'] ) ) != $new_password ) {
			wp_send_json(
				array(
					'error' => __( 'Passwords do not match', 'mobile-events-manager' ),
					'field' => 'tmem_confirm_password',
				)
			);
		}

		$update_args['user_pass'] = $new_password;
		$new_password             = true;
	}

	foreach ( $update_meta as $meta_key => $meta_value ) {
		update_user_meta( $client->ID, $meta_key, $meta_value );
	}

	$user_id = wp_update_user( $update_args );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'tmem_nonce',
			)
		);
	}

	if ( $new_password ) {
		wp_clear_auth_cookie();
		wp_logout();
	}
	wp_send_json( array( 'password' => $new_password ) );

} // tmem_validate_client_profile_form_ajax
add_action( 'wp_ajax_tmem_validate_client_profile', 'tmem_validate_client_profile_form_ajax' );
add_action( 'wp_ajax_nopriv_tmem_validate_client_profile', 'tmem_validate_client_profile_form_ajax' );

/**
 * Process playlist submission.
 *
 * @since 1.5
 * @return void
 */
function tmem_submit_playlist_ajax() {

	if ( ! check_ajax_referer( 'add_playlist_entry', 'tmem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'tmem_nonce',
			)
		);
	}

	$required_fields = array(
		'tmem_song' => __( 'Song', 'mobile-events-manager' ),
	);

	$required_fields = apply_filters( 'tmem_playlist_required_fields', $required_fields );

	foreach ( $required_fields as $required_field => $field_name ) {
		if ( empty( sanitize_text_field( wp_unslash( $_POST[ $required_field ] ) ) ) ) {
			wp_send_json(
				array(
					'error' => sprintf( esc_html__( '%s is a required field', 'mobile-events-manager' ), esc_attr( $field_name ) ),
					'field' => $required_field,
				)
			);
		}
	}

	$event    = absint( wp_unslash( $_POST['tmem_playlist_event'] ) );
	$song     = sanitize_text_field( wp_unslash( $_POST['tmem_song'] ) );
	$artist   = isset( $_POST['tmem_artist'] ) ? sanitize_text_field( wp_unslash( $_POST['tmem_artist'] ) ) : '';
	$category = isset( $_POST['tmem_category'] ) ? absint( wp_unslash( $_POST['tmem_category'] ) ) : null;
	$notes    = isset( $_POST['tmem_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tmem_notes'] ) ) : null;

	$playlist_data = array(
		'event_id' => $event,
		'song'     => $song,
		'artist'   => $artist,
		'category' => $category,
		'added_by' => get_current_user_id(),
		'notes'    => $notes,
	);

	$entry_id = tmem_store_playlist_entry( $playlist_data );

	if ( $entry_id ) {
		$category   = get_term( $category, 'playlist-category' );
		$entry_data = tmem_get_playlist_entry_data( $entry_id );

		ob_start(); ?>

		<div class="playlist-entry-row tmem-playlist-entry-<?php echo $entry_id; ?>">
			<div class="playlist-entry-column">
				<span class="playlist-entry"><?php echo esc_attr( $entry_data['artist'] ); ?></span>
			</div>
			<div class="playlist-entry-column">
				<span class="playlist-entry"><?php echo esc_attr( $entry_data['song'] ); ?></span>
			</div>
			<div class="playlist-entry-column">
				<span class="playlist-entry"><?php echo esc_attr( $category->name ); ?></span>
			</div>
			<div class="playlist-entry-column">
				<span class="playlist-entry">
					<?php if ( 'Guest' == $category->name ) : ?>
						<?php echo esc_attr( $entry_data['added_by'] ); ?>
					<?php elseif ( ! empty( $entry_data['djnotes'] ) ) : ?>
						<?php echo esc_attr( $entry_data['djnotes'] ); ?>
					<?php else : ?>
						<?php echo '&ndash;'; ?>
					<?php endif; ?>
				</span>
			</div>
			<div class="playlist-entry-column">
				<span class="playlist-entry">
					<a class="tmem-delete playlist-delete-entry" data-event="<?php echo $event; ?>" data-entry="<?php echo $entry_id; ?>"><?php esc_html_e( 'Remove', 'mobile-events-manager' ); ?></a>
				</span>
			</div>
		</div>

		<?php
		$row_data = ob_get_clean();

		$tmem_event     = new TMEM_Event( $event );
		$playlist_limit = tmem_get_event_playlist_limit( $tmem_event->ID );
		$total_entries  = tmem_count_playlist_entries( $tmem_event->ID );
		$songs          = sprintf( '%d %s', $total_entries, _n( 'song', 'songs', $total_entries, 'mobile-events-manager' ) );
		$length         = tmem_playlist_duration( $tmem_event->ID, $total_entries );

		if ( ! $tmem_event->playlist_is_open() ) {
			$closed = true;
		} elseif ( 0 !== $playlist_limit && $total_entries >= $playlist_limit ) {
			$closed = true;
		} else {
			$closed = false;
		}

		wp_send_json_success(
			array(
				'row_data' => $row_data,
				'closed'   => $closed,
				'songs'    => $songs,
				'length'   => $length,
				'total'    => $total_entries,
			)
		);
	}

	wp_send_json_error();
} // tmem_submit_playlist_ajax
add_action( 'wp_ajax_tmem_submit_playlist', 'tmem_submit_playlist_ajax' );
add_action( 'wp_ajax_nopriv_tmem_submit_playlist', 'tmem_submit_playlist_ajax' );

/**
 * Remove playlist entry.
 *
 * @since 1.5
 * @return void
 */

function tmem_remove_playlist_entry_ajax() {
	$event_id = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$song_id  = absint( sanitize_text_field( wp_unslash( $_POST['song_id'] ) ) );

	if ( tmem_remove_stored_playlist_entry( $song_id ) ) {
		$total  = tmem_count_playlist_entries( $event_id );
		$songs  = sprintf( '%d %s', $total, _n( 'song', 'songs', $total, 'mobile-events-manager' ) );
		$length = tmem_playlist_duration( $event_id, $total );
		wp_send_json_success(
			array(
				'count'  => $total,
				'songs'  => $songs,
				'length' => $length,
			)
		);
	}

	wp_send_json_error();
} // tmem_remove_playlist_entry_ajax
add_action( 'wp_ajax_tmem_remove_playlist_entry', 'tmem_remove_playlist_entry_ajax' );
add_action( 'wp_ajax_nopriv_tmem_remove_playlist_entry', 'tmem_remove_playlist_entry_ajax' );

/**
 * Process guest playlist submission.
 *
 * @since 1.5
 * @return void
 */
function tmem_submit_guest_playlist_ajax() {

	if ( ! check_ajax_referer( 'add_guest_playlist_entry', 'tmem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'tmem_nonce',
			)
		);
	}

	$required_fields = array(
		'tmem_guest_name' => __( 'Name', 'mobile-events-manager' ),
		'tmem_guest_song' => __( 'Song', 'mobile-events-manager' ),
	);

	$required_fields = apply_filters( 'tmem_guest_playlist_required_fields', $required_fields );

	foreach ( $required_fields as $required_field => $field_name ) {
		if ( empty( sanitize_text_field( wp_unslash( $_POST[ $required_field ] ) ) ) ) {
			wp_send_json(
				array(
					'error' => sprintf( esc_html__( '%s is a required field', 'mobile-events-manager' ), esc_attr( $field_name ) ),
					'field' => $required_field,
				)
			);
		}
	}

	$song   = sanitize_text_field( wp_unslash( $_POST['tmem_guest_song'] ) );
	$artist = isset( $_POST['tmem_guest_artist'] ) ? sanitize_text_field( wp_unslash( $_POST['tmem_guest_artist'] ) ) : '';
	$guest  = ucwords( sanitize_text_field( wp_unslash( $_POST['tmem_guest_name'] ) ) );
	$event  = absint( wp_unslash( $_POST['tmem_playlist_event'] ) );
	$closed = false;

	$playlist_data = array(
		'tmem_guest_song'     => $song,
		'tmem_guest_artist'   => $artist,
		'tmem_guest_name'     => $guest,
		'tmem_playlist_event' => $event,
	);

	$entry_id = tmem_store_guest_playlist_entry( $playlist_data );

	if ( $entry_id ) {
		ob_start();
		?>
		<div class="guest-playlist-entry-row tmem-playlist-entry-<?php echo $entry_id; ?>">
			<div class="guest-playlist-entry-column">
				<span class="guest-playlist-entry"><?php echo esc_attr( stripslashes( $artist ) ); ?></span>
			</div>
			<div class="guest-playlist-entry-column">
				<span class="guest-playlist-entry"><?php echo esc_attr( stripslashes( $song ) ); ?></span>
			</div>
			<div class="guest-playlist-entry-column">
				<span class="playlist-entry">
					<a class="tmem-delete guest-playlist-delete-entry" data-event="<?php echo $event; ?>" data-entry="<?php echo $entry_id; ?>"><?php esc_html_e( 'Remove', 'mobile-events-manager' ); ?></a>
				</span>
			</div>
		</div>
		<?php
		$entry = ob_get_clean();

		$event_playlist_limit = tmem_get_event_playlist_limit( $event );
		$entries_in_playlist  = tmem_count_playlist_entries( $event );

		if ( 0 !== $event_playlist_limit && $entries_in_playlist >= $event_playlist_limit ) {
			$closed = true;
		}

		wp_send_json(
			array(
				'entry'  => $entry,
				'closed' => $closed,
			)
		);
	}

} // tmem_submit_guest_playlist_ajax
add_action( 'wp_ajax_tmem_submit_guest_playlist', 'tmem_submit_guest_playlist_ajax' );
add_action( 'wp_ajax_nopriv_tmem_submit_guest_playlist', 'tmem_submit_guest_playlist_ajax' );

/**
 * Remove guest playlist entry.
 *
 * @since 1.5
 * @return void
 */
function tmem_remove_guest_playlist_entry_ajax() {
	$event_id = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$song_id  = absint( sanitize_text_field( wp_unslash( $_POST['song_id'] ) ) );

	if ( tmem_remove_stored_playlist_entry( $song_id ) ) {
		wp_send_json_success();
	}

	wp_send_json_error();
} // tmem_remove_guest_playlist_entry_ajax
add_action( 'wp_ajax_tmem_remove_guest_playlist_entry', 'tmem_remove_guest_playlist_entry_ajax' );
add_action( 'wp_ajax_nopriv_tmem_remove_guest_playlist_entry', 'tmem_remove_guest_playlist_entry_ajax' );

/**
 * Save the client fields order during drag and drop.
 */
function tmem_save_client_field_order_ajax() {
	$client_fields = get_option( 'tmem_client_fields' );

	foreach ( sanitize_text_field( wp_unslash( $_POST['fields'] ) ) as $order => $field ) {
		$i = $order + 1;

		$client_fields[ $field ]['position'] = $i;

	}
	update_option( 'tmem_client_fields', $client_fields );

	die();
} // tmem_save_client_field_order_ajax
add_action( 'wp_ajax_tmem_update_client_field_order', 'tmem_save_client_field_order_ajax' );

/**
 * Refresh the data within the client details table.
 *
 * @since 1.3.7
 */
function tmem_refresh_client_details_ajax() {

	$result = array();

	ob_start();
	tmem_do_client_details_table( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['client_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) ) );
	$result['client_details'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // tmem_refresh_client_details_ajax
add_action( 'wp_ajax_tmem_refresh_client_details', 'tmem_refresh_client_details_ajax' );

/**
 * Adds a new client from the event field.
 *
 * @since 1.3.7
 * @global arr wp_unslash($_POST
 */
function tmem_add_client_ajax() {

	$client_id   = false;
	$client_list = '';
	$result      = array();
	$message     = array();

	if ( ! is_email( wp_unslash( $_POST['client_email'] ) ) ) {
		$message = __( 'Email address is invalid', 'mobile-events-manager' );
	} elseif ( email_exists( sanitize_text_field( wp_unslash( $_POST['client_email'] ) ) ) ) {
		$message = __( 'Email address is already in use', 'mobile-events-manager' );
	} else {

		$user_data = array(
			'first_name'      => ucwords( sanitize_text_field( wp_unslash( $_POST['client_firstname'] ) ) ),
			'last_name'       => ! empty( sanitize_text_field( wp_unslash( $_POST['client_lastname'] ) ) ) ? ucwords( sanitize_text_field( wp_unslash( $_POST['client_lastname'] ) ) ) : '',
			'user_email'      => strtolower( sanitize_text_field( wp_unslash( $_POST['client_email'] ) ) ),
			'client_phone'    => ! empty( sanitize_text_field( wp_unslash( $_POST['client_phone'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_phone'] ) ) : '',
			'client_phone2'   => ! empty( sanitize_text_field( wp_unslash( $_POST['client_phone2'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_phone2'] ) ) : '',
			'client_address1' => ! empty( sanitize_text_field( wp_unslash( $_POST['client_address1'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_address1'] ) ) : '',
			'client_address2' => ! empty( sanitize_text_field( wp_unslash( $_POST['client_address2'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_address2'] ) ) : '',
			'client_town'     => ! empty( sanitize_text_field( wp_unslash( $_POST['client_town'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_town'] ) ) : '',
			'client_county'   => ! empty( sanitize_text_field( wp_unslash( $_POST['client_county'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_county'] ) ) : '',
			'client_postcode' => ! empty( sanitize_text_field( wp_unslash( $_POST['client_postcode'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['client_postcode'] ) ) : '',
		);

		$user_data = apply_filters( 'tmem_event_new_client_data', $user_data );

		$client_id = tmem_add_client( $user_data );

	}

	$clients = tmem_get_clients( 'client' );

	if ( ! empty( $clients ) ) {
		foreach ( $clients as $client ) {
			$client_list .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				$client->ID,
				$client->ID == $client_id ? ' selected="selected"' : '',
				$client->display_name
			);
		}
	}

	if ( empty( $client_id ) ) {
		$result = array(
			'type'    => 'error',
			'message' => explode( "\n", $message ),
		);
	} else {
		$result = array(
			'type'        => 'success',
			'client_id'   => $client_id,
			'client_list' => $client_list,
		);
		do_action( 'tmem_after_add_new_client', $user_data );
	}

	echo json_encode( $result );

	die();

} // tmem_add_client_ajax
add_action( 'wp_ajax_tmem_event_add_client', 'tmem_add_client_ajax' );

/**
 * Refresh the data within the venue details table.
 *
 * @since 1.3.7
 */
function tmem_refresh_venue_details_ajax() {

	wp_send_json_success(
		array( 'venue' => tmem_do_venue_details_table( sanitize_text_field( wp_unslash( $_POST['venue_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) ) )
	);

} // tmem_refresh_venue_details_ajax
add_action( 'wp_ajax_tmem_refresh_venue_details', 'tmem_refresh_venue_details_ajax' );

/**
 * Sets the venue address as the client address.
 *
 * @since 1.4
 */
function tmem_set_client_venue_ajax() {

	$client_id = sanitize_text_field( wp_unslash( $_POST['client_id'] ) );
	$response  = array();

	$client = get_userdata( $client_id );

	if ( $client ) {
		if ( ! empty( $client->address1 ) ) {
			$response['address1'] = stripslashes( $client->address1 );
		}
		if ( ! empty( $client->address2 ) ) {
			$response['address2'] = stripslashes( $client->address2 );
		}
		if ( ! empty( $client->town ) ) {
			$response['town'] = stripslashes( $client->town );
		}
		if ( ! empty( $client->county ) ) {
			$response['county'] = stripslashes( $client->county );
		}
		if ( ! empty( $client->postcode ) ) {
			$response['postcode'] = stripslashes( $client->postcode );
		}
	}

	$response['type'] = 'success';

	wp_send_json( $response );

} // tmem_use_client_address
add_action( 'wp_ajax_tmem_set_client_venue', 'tmem_set_client_venue_ajax' );

/**
 * Adds a new venue from the events screen.
 *
 * @since 1.3.7
 */
function tmem_add_venue_ajax() {

	$venue_id   = false;
	$venue_list = '';
	$result     = array();
	$venue_name = '';
	$venue_meta = array();

	foreach ( $_POST as $key => $value ) {
		if (  'action' === $key ) {
			continue;
		} elseif ( 'venue_name' === $key ) {
			$venue_name = $value;
		} else {
			$venue_meta[ $key ] = strip_tags( addslashes( $value ) );
		}
	}

	$venue_id = tmem_add_venue( $venue_name, $venue_meta );

	$venues = tmem_get_venues();

	$venue_list .= '<option value="manual">' . __( ' - Enter Manually - ', 'mobile-events-manager' ) . '</option>' . "\r\n";
	$venue_list .= '<option value="client">' . __( ' - Use Client Address - ', 'mobile-events-manager' ) . '</option>' . "\r\n";

	if ( ! empty( $venues ) ) {
		foreach ( $venues as $venue ) {
			$venue_list .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				$venue->ID,
				$venue->ID == $venue_id ? ' selected="selected"' : '',
				$venue->post_title
			);
		}
	}

	if ( empty( $venue_id ) ) {
		$result = array(
			'type'    => 'error',
			'message' => __( 'Unable to add venue', 'mobile-events-manager' ),
		);
	} else {
		$result = array(
			'type'       => 'success',
			'venue_id'   => $venue_id,
			'venue_list' => $venue_list,
		);

	}

	echo json_encode( $result );

	die();

} // tmem_add_venue_ajax
add_action( 'wp_ajax_tmem_add_venue', 'tmem_add_venue_ajax' );

/**
 * Refresh the travel data for an event.
 *
 * @since 1.4
 */
function tmem_update_event_travel_data_ajax() {
	$employee_id = sanitize_text_field( wp_unslash( $_POST['employee_id'] ) );
	$dest        = sanitize_text_field( wp_unslash( $_POST['venue'] ) );
	$dest        = maybe_unserialize( $dest );

	$tmem_travel = new TMEM_Travel();

	if ( ! empty( $employee_id ) ) {
		$tmem_travel->__set( 'start_address', $tmem_travel->get_employee_address( $employee_id ) );
	}

	$tmem_travel->set_destination( $dest );
	$tmem_travel->get_travel_data();

	if ( ! empty( $tmem_travel->data ) ) {
		$travel_cost = $tmem_travel->get_cost();
		$response    = array(
			'type'           => 'success',
			'distance'       => tmem_format_distance( $tmem_travel->data['distance'], false, true ),
			'time'           => tmem_seconds_to_time( $tmem_travel->data['duration'] ),
			'cost'           => ! empty( $travel_cost ) ? tmem_currency_filter( tmem_format_amount( $travel_cost ) ) : tmem_currency_filter( tmem_format_amount( 0 ) ),
			'directions_url' => $tmem_travel->get_directions_url(),
			'raw_cost'       => $travel_cost,
		);
	} else {
		$response = array( 'type' => 'error' );
	}

	wp_send_json( $response );
} // tmem_update_event_travel_data_ajax
add_action( 'wp_ajax_tmem_update_travel_data', 'tmem_update_event_travel_data_ajax' );

/**
 * Save the custom event fields order
 *
 * @since 1.3.7
 */
function tmem_order_custom_event_fields_ajax() {

	foreach ( sanitize_text_field( wp_unslash( $_POST['customfields'] ) ) as $order => $id ) {
		$order++;

		wp_update_post(
			array(
				'ID'         => $id,
				'menu_order' => $order,
			)
		);
	}

	die();

} // tmem_order_custom_event_field_ajax
add_action( 'wp_ajax_order_custom_event_fields', 'tmem_order_custom_event_fields_ajax' );

/**
 * Save the event transaction
 */
function tmem_save_event_transaction_ajax() {
	global $tmem_event;

	$result = array();

	$tmem_event = new TMEM_Event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$tmem_txn   = new TMEM_Txn();

	$txn_data = array(
		'post_parent' => sanitize_text_field( wp_unslash( $_POST['event_id'] ) ),
		'post_author' => $tmem_event->client,
		'post_status' => sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'Out' ? 'tmem-expenditure' : 'tmem-income',
		'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) ),
	);

	$txn_meta = array(
		'_tmem_txn_status'      => 'Completed',
		'_tmem_payment_from'    => $tmem_event->client,
		'_tmem_txn_total'       => sanitize_text_field( wp_unslash( $_POST['amount'] ) ),
		'_tmem_payer_firstname' => tmem_get_client_firstname( $tmem_event->client ),
		'_tmem_payer_lastname'  => tmem_get_client_lastname( $tmem_event->client ),
		'_tmem_payer_email'     => tmem_get_client_email( $tmem_event->client ),
		'_tmem_payment_from'    => tmem_get_client_display_name( $tmem_event->client ),
		'_tmem_txn_source'      => sanitize_text_field( wp_unslash( $_POST['src'] ) ),
	);

	if ( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'In' ) {
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['from'] ) ) ) ) {
			$txn_meta['_tmem_payment_from'] = sanitize_text_field( wp_unslash( $_POST['from'] ) );
		} else {
			$txn_meta['_tmem_payment_from'] = tmem_get_client_display_name( $tmem_event->client );
		}
	}

	if ( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'Out' ) {
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['to'] ) ) ) ) {
			$txn_meta['_tmem_payment_to'] = sanitize_text_field( wp_unslash( $_POST['to'] ) );
		} else {
			$txn_meta['_tmem_payment_to'] = tmem_get_client_display_name( $tmem_event->client );
		}
	}

	$tmem_txn->create( $txn_data, $txn_meta );

	if ( $tmem_txn->ID > 0 ) {
		$result['type'] = 'success';
		tmem_set_txn_type( $tmem_txn->ID, sanitize_text_field( wp_unslash( $_POST['for'] ) ) );

		$args = array(
			'user_id'         => get_current_user_id(),
			'event_id'        => sanitize_text_field( wp_unslash( $_POST['event_id'] ) ),
			'comment_content' => sprintf(
				__( '%1$s payment of %2$s received for %3$s %4$s.', 'mobile-events-manager' ),
				sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'In' ? __( 'Incoming', 'mobile-events-manager' ) : __( 'Outgoing', 'mobile-events-manager' ),
				tmem_currency_filter( tmem_format_amount( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) ),
				esc_html( tmem_get_label_singular( true ) ),
				tmem_get_event_contract_id( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) )
			),
		);

		tmem_add_journal( $args );

		// Email overide
		if ( empty( sanitize_text_field( wp_unslash( $_POST['send_notice'] ) ) ) && tmem_get_option( 'manual_payment_cfm_template' ) ) {
			$manual_email_template = tmem_get_option( 'manual_payment_cfm_template' );
			tmem_update_option( 'manual_payment_cfm_template', 0 );
		}

		$payment_for = $tmem_txn->get_type();
		$amount      = tmem_currency_filter( tmem_format_amount( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) );

		tmem_add_content_tag(
			'payment_for',
			__( 'Reason for payment', 'mobile-events-manager' ),
			function() use ( $payment_for ) {
				return $payment_for;
			}
		);

		tmem_add_content_tag(
			'payment_amount',
			__( 'Payment amount', 'mobile-events-manager' ),
			function() use ( $amount ) {
				return $amount;
			}
		);

		tmem_add_content_tag( 'payment_date', __( 'Date of payment', 'mobile-events-manager' ), 'tmem_content_tag_ddmmyyyy' );

		/**
		 * Allow hooks into this payment. The hook is suffixed with 'in' or 'out' depending
		 * on the payment direction. i.e. tmem_post_add_manual_txn_in and tmem_post_add_manual_txn_out
		 *
		 * @since 1.3.7
		 * @param int $event_id
		 * @param obj $txn_id
		 */
		do_action( 'tmem_post_add_manual_txn_' . strtolower( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ), $tmem_txn->ID );

		// Email overide
		if ( empty( sanitize_text_field( wp_unslash( $_POST['send_notice'] ) ) ) && isset( $manual_email_template ) ) {
			tmem_update_option( 'manual_payment_cfm_template', $manual_email_template );
		}

		$result['deposit_paid'] = 'N';
		$result['balance_paid'] = 'N';

		if ( $tmem_event->get_remaining_deposit() < 1 ) {
			tmem_update_event_meta( $tmem_event->ID, array( '_tmem_event_deposit_status' => 'Paid' ) );
			$result['deposit_paid'] = 'Y';

			// Update event status if contract signed & we wait for deposit paid before confirming booking
			if ( tmem_require_deposit_before_confirming() && $tmem_event->get_contract_status() ) {

				tmem_update_event_status(
					$tmem_event->ID,
					'tmem-approved',
					$tmem_event->post_status,
					array( 'client_notices' => tmem_get_option( 'booking_conf_to_client' ) )
				);

			}
		}

		if ( $tmem_event->get_balance() < 1 ) {
			tmem_update_event_meta( $tmem_event->ID, array( '_tmem_event_balance_status' => 'Paid' ) );
			tmem_update_event_meta( $tmem_event->ID, array( '_tmem_event_deposit_status' => 'Paid' ) );
			$result['balance_paid'] = 'Y';
			$result['deposit_paid'] = 'Y';
		}
	} else {
		$result['type'] = 'error';
		$result['msg']  = __( 'Unable to add transaction', 'mobile-events-manager' );
	}

	$result['event_status'] = get_post_status( $tmem_event->ID );

	ob_start();
	tmem_do_event_txn_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['transactions'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();
} // tmem_save_event_transaction_ajax
add_action( 'wp_ajax_add_event_transaction', 'tmem_save_event_transaction_ajax' );

/**
 * Add a new event type
 * Initiated from the Event Post screen
 */
function tmem_add_event_type_ajax() {

	if ( empty( sanitize_text_field( wp_unslash( $_POST['type'] ) ) ) ) {
		$msg = __( 'Enter a name for the new Event Type', 'mobile-events-manager' );
		wp_send_json_error(
			array(
				'selected' => sanitize_text_field( wp_unslash( $_POST['current'] ) ),
				'msg'      => $msg,
			)
		);
	} else {
		$term = wp_insert_term( sanitize_text_field( wp_unslash( $_POST['type'] ) ), 'event-types' );

		if ( ! is_wp_error( $term ) ) {
			$msg = 'success';
		} else {
			error_log( $term->get_error_message() );
		}
	}

	$selected   = 'success' === $msg ? $term['term_id'] : sanitize_text_field( wp_unslash( $_POST['current'] ) );
	$categories = get_terms( 'event-types', array( 'hide_empty' => false ) );
	$options    = array();
	$output     = '';

	foreach ( $categories as $category ) {
		$options[ absint( $category->term_id ) ] = esc_html( $category->name );
	}

	foreach ( $options as $key => $option ) {
		$selected = selected( $term['term_id'], $key, false );

		$output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option ) . '</option>' . "\r\n";
	}
	wp_send_json_success(
		array(
			'event_types' => $output,
			'msg'         => 'success',
		)
	);

	die();

} // tmem_add_event_type_ajax
add_action( 'wp_ajax_add_event_type', 'tmem_add_event_type_ajax' );

/**
 * Execute single event tasks.
 *
 * @since 1.5
 */
function tmem_execute_event_task_ajax() {
	$task_id         = sanitize_text_field( wp_unslash( $_POST['task'] ) );
	$event_id        = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$tasks           = tmem_get_tasks_for_event( $event_id );
	$result          = tmem_run_single_event_task( $event_id, $task_id );
	$tmem_event      = new TMEM_Event( $event_id );
	$completed_tasks = $tmem_event->get_tasks();
	$tasks_history   = array();

	if ( ! $result ) {
		wp_send_json_error();
	}

	foreach ( $completed_tasks as $task_slug => $run_time ) {
		if ( ! array_key_exists( $task_slug, $tasks ) ) {
			continue;
		}

		$tasks_history[] = sprintf(
			'%s: %s',
			tmem_get_task_name( $task_slug ),
			date( tmem_get_option( 'short_date_format' ), $run_time )
		);
	}

	$task_history = '<span class="task-history-items">' . implode( '<br>', $tasks_history ) . '</span>';

	wp_send_json_success(
		array(
			'status'  => $tmem_event->post_status,
			'history' => $task_history,
		)
	);
} // tmem_execute_event_task_ajax
add_action( 'wp_ajax_tmem_execute_event_task', 'tmem_execute_event_task_ajax' );

/**
 * Add a new transaction type
 * Initiated from the Transaction Post screen
 */
function tmem_add_transaction_type_ajax() {
	global $tmem;

	TMEM()->debug->log_it( 'Adding ' . sanitize_text_field( wp_unslash( $_POST['type'] ) ) . ' new Transaction Type from Transaction Post form', true );

	$args = array(
		'taxonomy'         => 'transaction-types',
		'hide_empty'       => 0,
		'name'             => 'tmem_transaction_type',
		'id'               => 'tmem_transaction_type',
		'orderby'          => 'name',
		'hierarchical'     => 0,
		'show_option_none' => __( 'Select Transaction Type', 'mobile-events-manager' ),
		'class'            => ' required',
		'echo'             => 0,
	);

	/* -- Validate that we have a Transaction Type to add -- */
	if ( empty( sanitize_text_field( wp_unslash( $_POST['type'] ) ) ) ) {
		$result['type'] = 'Error';
		$result['msg']  = 'Please enter a name for the new Transaction Type';
	} else {
		$term = wp_insert_term( sanitize_text_field( wp_unslash( $_POST['type'] ) ), 'transaction-types' );
		if ( is_array( $term ) ) {
			$result['type'] = 'success';
		} else {
			$result['type'] = 'error';
		}
	}

	TMEM()->debug->log_it( 'Completed adding ' . sanitize_text_field( wp_unslash( $_POST['type'] ) ) . ' new Transaction Type from Transaction Post form', true );

	$args['selected'] = 'success' === $result['type'] ? $term['term_id'] : sanitize_text_field( wp_unslash( $_POST['current'] ) );

	$result['transaction_types'] = wp_dropdown_categories( $args );

	$result = json_encode( $result );
	echo $result;

	die();
} // tmem_add_transaction_type_ajax
add_action( 'wp_ajax_add_transaction_type', 'tmem_add_transaction_type_ajax' );

/**
 * Determine the event setup time
 *
 * @since 1.5
 * @return void
 */
function tmem_event_setup_time_ajax() {
	$time_format = tmem_get_option( 'time_format' );
	$start_time  = sanitize_text_field( wp_unslash( $_POST['time'] ) );
	$event_date  = sanitize_text_field( wp_unslash( $_POST['date'] ) );
	$date        = new DateTime( $event_date . ' ' . $start_time );
	$timestamp   = $date->format( 'U' );

	$setup_time = $timestamp - ( (int) tmem_get_option( 'setup_time' ) * 60 );

	$hour     = 'H:i' == $time_format ? gmdate( 'H', $setup_time ) : gmdate( 'g', $setup_time );
	$minute   = gmdate( 'i', $setup_time );
	$meridiem = 'H:i' == $time_format ? '' : gmdate( 'A', $setup_time );

	wp_send_json_success(
		array(
			'hour'       => $hour,
			'minute'     => $minute,
			'meridiem'   => $meridiem,
			'date'       => gmdate( tmem_get_option( 'short_date_format' ), $setup_time ),
			'datepicker' => gmdate( 'Y-m-d', $setup_time ),
		)
	);
} // tmem_event_setup_time_ajax
add_action( 'wp_ajax_tmem_event_setup_time', 'tmem_event_setup_time_ajax' );

/**
 * Calculate the event cost as event elements change
 *
 * @since 1.0
 * @return void
 */
function tmem_update_event_cost_ajax() {

	$tmem_event  = new TMEM_Event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$tmem_travel = new TMEM_Travel();

	$event_cost    = $tmem_event->price;
	$event_date    = $event_date = ! empty( sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : null;
	$base_cost     = '0.00';
	$package       = $tmem_event->get_package();
	$package_price = $package ? tmem_get_package_price( $package, $event_date ) : '0.00';
	$addons        = $tmem_event->get_addons();
	$travel_data   = $tmem_event->get_travel_data();
	$employee_id   = sanitize_text_field( wp_unslash( $_POST['employee_id'] ) );
	$dest          = sanitize_text_field( wp_unslash( $_POST['venue'] ) );
	$dest          = maybe_unserialize( $dest );
	$package_cost  = 0;
	$addons_cost   = 0;
	$travel_cost   = 0;
	$additional    = ! empty( sanitize_text_field( wp_unslash( $_POST['additional'] ) ) ) ? (float) sanitize_text_field( wp_unslash( $_POST['additional'] ) ) : 0;
	$discount      = ! empty( sanitize_text_field( wp_unslash( $_POST['discount'] ) ) ) ? (float) sanitize_text_field( wp_unslash( $_POST['discount'] ) ) : 0;

	if ( $event_cost ) {
		$event_cost = (float) $event_cost;
		$base_cost  = ( $package_price ) ? $event_cost - $package_price : $event_cost;
	}

	if ( $package ) {
		$base_cost = $event_cost - $package_price;
	}

	if ( $addons ) {
		foreach ( $addons as $addon ) {
			$addon_price = tmem_get_addon_price( $addon, $event_date );
			$base_cost   = $base_cost - (float) $addon_price;
		}
	}

	if ( $travel_data && ! empty( $travel_data['cost'] ) ) {
		$base_cost = $base_cost - (float) $travel_data['cost'];
	}

	$base_cost = $base_cost - $additional;
	$base_cost = $base_cost + $discount;

	$new_package = ! empty( sanitize_text_field( wp_unslash( $_POST['package'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : false;
	$new_addons  = ! empty( sanitize_text_field( wp_unslash( $_POST['addons'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['addons'] ) ) : false;

	$cost = $base_cost;

	if ( $new_package ) {
		$package_cost = (float) tmem_get_package_price( $new_package, $event_date );
	}

	if ( $new_addons ) {
		foreach ( $new_addons as $new_addon ) {
			$addons_cost += (float) tmem_get_addon_price( $new_addon, $event_date );
		}
	}

	if ( $tmem_travel->add_travel_cost ) {
		if ( ! empty( $employee_id ) ) {
			$tmem_travel->__set( 'start_address', $tmem_travel->get_employee_address( $employee_id ) );
		}

		$tmem_travel->set_destination( $dest );
		$tmem_travel->get_travel_data();

		$new_travel = ! empty( $tmem_travel->data ) ? $tmem_travel->get_cost() : false;

		if ( $new_travel && (float) preg_replace( '/[^0-9.]*/', '', $tmem_travel->data['distance'] ) >= tmem_get_option( 'travel_min_distance' ) ) {
			$travel_cost = (float) $new_travel;
		}
	}

	$cost += $package_cost;
	$cost += $addons_cost;
	$cost += $travel_cost;
	$cost += $additional;
	// $cost -= $discount;

	if ( ! empty( $cost ) ) {
		$result['type'] = 'success';
		$result['cost'] = tmem_sanitize_amount( (float) $cost );
	} else {
		$result['type'] = 'success';
		$result['cost'] = tmem_sanitize_amount( 0 );
	}

	$result['package_cost']    = tmem_sanitize_amount( $package_cost );
	$result['addons_cost']     = tmem_sanitize_amount( $addons_cost );
	$result['travel_cost']     = tmem_sanitize_amount( $travel_cost );
	$result['additional_cost'] = tmem_sanitize_amount( $additional );
	$result['discount']        = tmem_sanitize_amount( $discount );

	wp_send_json( $result );

} // tmem_update_event_cost_ajax
add_action( 'wp_ajax_tmem_update_event_cost', 'tmem_update_event_cost_ajax' );

/**
 * Update the available list of packages and addons when the primary event employee
 * the event type, or the event date changes.
 *
 * @since 1.0
 * @return void
 */
function tmem_refresh_event_package_options_ajax() {

	$employee        = ( ! empty( sanitize_text_field( wp_unslash( $_POST['employee'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['employee'] ) ) : '' );
	$current_package = ( ! empty( sanitize_text_field( wp_unslash( $_POST['package'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '' );
	$current_addons  = ( ! empty( sanitize_text_field( wp_unslash( $_POST['addons'] ) ) ) ? sanitize_textarea_field( wp_unslash( $_POST['addons'] ) ) : '' );
	$event_type      = ( ! empty( sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '' );
	$event_date      = ( ! empty( sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '' );

	$packages = TMEM()->html->packages_dropdown(
		array(
			'selected'     => $current_package,
			'chosen'       => true,
			'employee'     => $employee,
			'event_type'   => $event_type,
			'event_date'   => $event_date,
			'options_only' => true,
			'blank_first'  => true,
			'data'         => array(),
		)
	);

	$selected_addons = ! empty( sanitize_text_field( wp_unslash( $_POST['addons'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['addons'] ) ) : array();

	$addons = TMEM()->html->addons_dropdown(
		array(
			'selected'         => $selected_addons,
			'show_option_none' => false,
			'show_option_all'  => false,
			'employee'         => $employee,
			'package'          => $current_package,
			'event_type'       => $event_type,
			'event_date'       => $event_date,
			'cost'             => true,
			'placeholder'      => __( 'Select Add-ons', 'mobile-events-manager' ),
			'chosen'           => true,
			'options_only'     => true,
			'blank_first'      => true,
			'data'             => array(),
		)
	);

	if ( ! empty( $addons ) || ! empty( $packages ) ) {
		$result['type'] = 'success';
	} else {
		$result['type'] = 'error';
		$result['msg']  = __( 'No packages or addons available', 'mobile-events-manager' );
	}

	if ( ! empty( $packages ) ) {
		$result['packages'] = $packages;
	} else {
		$result['packages'] = __( 'No Packages Available', 'mobile-events-manager' );
	}

	if ( ! empty( $addons ) && '<option value="0">' . __( 'No Packages Available', 'mobile-events-manager' ) . '</option>' !== $packages ) {
		$result['addons'] = $addons;
	} else {
		$result['addons'] = __( 'No Addons Available', 'mobile-events-manager' );
	}

	echo json_encode( $result );

	die();

} // tmem_refresh_event_package_options_ajax
add_action( 'wp_ajax_refresh_event_package_options', 'tmem_refresh_event_package_options_ajax' );

/**
 * Update the event deposit amount based upon the event cost
 * and the payment settings.
 *
 * @since 1.0
 * @return void
 */
function tmem_update_event_deposit_ajax() {

	$event_cost = sanitize_text_field( wp_unslash( $_POST['current_cost'] ) );

	$deposit = tmem_calculate_deposit( $event_cost );

	if ( ! empty( $deposit ) ) {
		$result['type']    = 'success';
		$result['deposit'] = tmem_sanitize_amount( $deposit );
	} else {
		$result['type'] = 'error';
		$result['msg']  = 'Unable to calculate deposit';
	}

	$result = json_encode( $result );
	echo $result;

	die();

} // tmem_update_event_deposit_ajax
add_action( 'wp_ajax_update_event_deposit', 'tmem_update_event_deposit_ajax' );

/**
 * Add an employee to the event.
 *
 * @since 1.3
 * @return void
 */
function tmem_add_employee_to_event_ajax() {

	$args = array(
		'id'             => isset( $_POST['employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ) : '',
		'role'           => isset( $_POST['employee_role'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_role'] ) ) : '',
		'wage'           => isset( $_POST['employee_wage'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_wage'] ) ) : '',
		'payment_status' => 'unpaid',
	);

	if ( ! tmem_add_employee_to_event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ), $args ) ) {

		$result['type'] = 'error';
		$result['msg']  = __( 'Unable to add employee', 'mobile-events-manager' );

	} else {
		$result['type'] = 'success';
	}

	ob_start();
	tmem_do_event_employees_list_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['employees'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // tmem_add_employee_to_event_ajax
add_action( 'wp_ajax_add_employee_to_event', 'tmem_add_employee_to_event_ajax' );

/**
 * Remove an employee from the event.
 *
 * @since 1.3
 * @return void
 */
function tmem_remove_employee_from_event_ajax() {

	tmem_remove_employee_from_event( sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );

	$result['type'] = 'success';

	ob_start();
	tmem_do_event_employees_list_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['employees'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // tmem_remove_employee_from_event_ajax
add_action( 'wp_ajax_remove_employee_from_event', 'tmem_remove_employee_from_event_ajax' );

/**
 * Retrieve the title of a template
 *
 * @since 1.4.7
 * @return str
 */
function tmem_tmem_get_template_title_ajax() {
	$title           = get_the_title( sanitize_text_field( wp_unslash( $_POST['template'] ) ) );
	$result['title'] = $title;
	echo json_encode( $result );

	die();
} // tmem_tmem_get_template_title_ajax
add_action( 'wp_ajax_tmem_get_template_title', 'tmem_tmem_get_template_title_ajax' );

/**
 * Update the email content field with the selected template.
 *
 * @since 1.3
 * @return void
 */
function tmem_set_email_content_ajax() {

	if ( empty( sanitize_text_field( wp_unslash( $_POST['template'] ) ) ) ) {
		$result['type']            = 'success';
		$result['updated_content'] = '';
	} else {
		$content = tmem_get_email_template_content( sanitize_text_field( wp_unslash( $_POST['template'] ) ) );

		if ( ! $content ) {
			$result['type'] = 'error';
			$result['msg']  = __( 'Unable to retrieve template content', 'mobile-events-manager' );
		} else {
			$result['type']            = 'success';
			$result['updated_content'] = $content;
			$result['updated_subject'] = html_entity_decode( get_the_title( sanitize_text_field( wp_unslash( $_POST['template'] ) ) ) );
		}
	}

	$result = json_encode( $result );

	echo $result;

	die();

} // tmem_set_email_content_ajax
add_action( 'wp_ajax_tmem_set_email_content', 'tmem_set_email_content_ajax' );

/**
 * Update the email content field with the selected template.
 *
 * @since 1.3
 * @return void
 */
function tmem_user_events_dropdown_ajax() {

	$result['event_list'] = '<option value="0">' . __( 'Select an Event', 'mobile-events-manager' ) . '</option>';

	if ( ! empty( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) ) ) {

		$statuses = 'any';

		if ( tmem_is_employee( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) ) ) {

			if ( tmem_get_option( 'comms_show_active_events_only' ) ) {
				$statuses = array( 'post_status' => tmem_active_event_statuses() );
			}

			$events = tmem_get_employee_events( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ), $statuses );

		} else {

			if ( tmem_get_option( 'comms_show_active_events_only' ) ) {
				$statuses = tmem_active_event_statuses();
			}

			$events = tmem_get_client_events( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ), $statuses );
		}

		if ( $events ) {
			foreach ( $events as $event ) {
				$result['event_list'] .= '<option value="' . $event->ID . '">';
				$result['event_list'] .= tmem_get_event_date( $event->ID ) . ' ';
				$result['event_list'] .= __( 'from', 'mobile-events-manager' ) . ' ';
				$result['event_list'] .= tmem_get_event_start( $event->ID ) . ' ';
				$result['event_list'] .= '(' . tmem_get_event_status( $event->ID ) . ')';
				$result['event_list'] .= '</option>';
			}
		}
	}

	$result['type'] = 'success';
	$result         = json_encode( $result );

	echo $result;

	die();

} // tmem_user_events_dropdown_ajax
add_action( 'wp_ajax_tmem_user_events_dropdown', 'tmem_user_events_dropdown_ajax' );

/**
 * Refresh the addons options when the package selection is updated.
 *
 * @since 1.3.7
 * @return void
 */
function tmem_refresh_event_addon_options_ajax() {

	$package    = sanitize_text_field( wp_unslash( $_POST['package'] ) );
	$employee   = ( isset( $_POST['employee'] ) ? sanitize_text_field( wp_unslash( $_POST['employee'] ) ) : false );
	$selected   = ( ! empty( $_POST['selected'] ) ? sanitize_text_field( wp_unslash( $_POST['selected'] ) ) : array() );
	$event_type = ( ! empty( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '' );
	$event_date = ( ! empty( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '' );

	$addons = TMEM()->html->addons_dropdown(
		array(
			'selected'         => $selected,
			'show_option_none' => false,
			'show_option_all'  => false,
			'employee'         => $employee,
			'event_type'       => $event_type,
			'event_date'       => $event_date,
			'package'          => $package,
			'cost'             => true,
			'placeholder'      => __( 'Select Add-ons', 'mobile-events-manager' ),
			'chosen'           => true,
			'options_only'     => true,
			'data'             => array(),
		)
	);

	$result['type'] = 'success';

	if ( ! empty( $addons ) ) {
		$result['addons'] = $addons;
	} else {
		$result['addons'] = '<option value="0" disabled="disabled">' . __( 'No addons available', 'mobile-events-manager' ) . '</option>';
	}

	echo json_encode( $result );

	die();

} // tmem_refresh_event_addon_options_ajax
add_action( 'wp_ajax_refresh_event_addon_options', 'tmem_refresh_event_addon_options_ajax' );
add_action( 'wp_ajax_nopriv_refresh_event_addon_options', 'tmem_refresh_event_addon_options_ajax' );

/**
 * Check the availability status for the given date
 *
 * @since 1.3
 * @param Global wp_unslash( $_POST
 * @return arr
 */
function tmem_do_availability_check_ajax() {

	$date       = sanitize_text_field( wp_unslash( $_POST['date'] ) );
	$employees  = isset( $_POST['employees'] ) ? sanitize_text_field( wp_unslash( $_POST['employees'] ) ) : false;
	$roles      = isset( $_POST['roles'] ) ? sanitize_text_field( wp_unslash( $_POST['roles'] ) ) : false;
	$short_date = tmem_format_short_date( $date );
	$result     = tmem_do_availability_check( $date, $employees, $roles );

	if ( ! empty( $result['available'] ) ) {
		$result['result']       = 'available';
		$result['notice_class'] = 'updated';
	} else {
		$result['result']       = 'unavailable';
		$result['notice_class'] = 'error';
	}

	wp_send_json( $result );
} // tmem_do_availability_check_ajax
add_action( 'wp_ajax_tmem_do_availability_check', 'tmem_do_availability_check_ajax' );
add_action( 'wp_ajax_nopriv_tmem_do_availability_check', 'tmem_do_availability_check_ajax' );
