<?php
/**
 * AJAX Functions
 *
 * Process the AJAX actions. Frontend and backend
 *
 * @package MEM
 * @subpackage Functions/AJAX
 * @since 1.0
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
function mem_get_ajax_url() {
	$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';

	$current_url = mem_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'mem_ajax_url', $ajax_url );
} // mem_get_ajax_url

/**
 * Dismiss admin notices.
 *
 * @since 1.5
 * @return void
 */
function mem_ajax_dismiss_admin_notice() {

	$notice = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['notice'] ) ) );
	mem_dismiss_notice( $notice );

	wp_send_json_success();

} // mem_ajax_dismiss_admin_notice
add_action( 'wp_ajax_mem_dismiss_notice', 'mem_ajax_dismiss_admin_notice' );

/**
 * Retrieve employee availability data for the calendar view.
 *
 * @since 1.0
 * @return void
 */
function mem_calendar_activity_ajax() {
	$data  = array();
	$start = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['start'] ) ) );
	$end   = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['end'] ) ) );

	$activity = mem_get_calendar_entries( $start, $end );

	wp_send_json( $activity );
} // mem_calendar_activity_ajax
add_action( 'wp_ajax_mem_calendar_activity', 'mem_calendar_activity_ajax' );

/**
 * Adds an employee absence entry.
 *
 * @since 1.0
 * @return void
 */
function mem_add_employee_absence_ajax() {
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

	if ( mem_add_employee_absence( $employee_id, $data ) ) {
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

} // mem_add_employee_absence_ajax
add_action( 'wp_ajax_mem_add_employee_absence', 'mem_add_employee_absence_ajax' );

/**
 * Removes an employee absence entry.
 *
 * @since 1.0
 * return void
 */
function mem_delete_employee_absence_ajax() {
	$id      = absint( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );
	$deleted = mem_remove_employee_absence( $id );

	if ( $deleted > 0 ) {
		wp_send_json_success();
	}

	wp_send_json_error();
} // mem_delete_employee_absence_ajax
add_action( 'wp_ajax_mem_delete_employee_absence', 'mem_delete_employee_absence_ajax' );

/**
 * Client profile update form validation
 *
 * @since 1.5
 * @return void
 */
function mem_validate_client_profile_form_ajax() {

	if ( ! check_ajax_referer( 'update_client_profile', 'mem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'mem_nonce',
			)
		);
	}

	$client_id    = absint( sanitize_text_field( wp_unslash( $_POST['mem_client_id'] ) ) );
	$client       = new MEM_Client( $client_id );
	$fields       = $client->get_profile_fields();
	$new_password = ! empty( sanitize_text_field( wp_unslash( $_POST['mem_new_password'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['mem_new_password'] ) ) : false;
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
		if ( empty( sanitize_text_field( wp_unslash( $_POST['mem_confirm_password'] ) ) ) || sanitize_text_field( wp_unslash( $_POST['mem_confirm_password'] ) ) != $new_password ) {
			wp_send_json(
				array(
					'error' => __( 'Passwords do not match', 'mobile-events-manager' ),
					'field' => 'mem_confirm_password',
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
				'field' => 'mem_nonce',
			)
		);
	}

	if ( $new_password ) {
		wp_clear_auth_cookie();
		wp_logout();
	}
	wp_send_json( array( 'password' => $new_password ) );

} // mem_validate_client_profile_form_ajax
add_action( 'wp_ajax_mem_validate_client_profile', 'mem_validate_client_profile_form_ajax' );
add_action( 'wp_ajax_nopriv_mem_validate_client_profile', 'mem_validate_client_profile_form_ajax' );

/**
 * Process playlist submission.
 *
 * @since 1.5
 * @return void
 */
function mem_submit_playlist_ajax() {

	if ( ! check_ajax_referer( 'add_playlist_entry', 'mem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'mem_nonce',
			)
		);
	}

	$required_fields = array(
		'mem_song' => __( 'Song', 'mobile-events-manager' ),
	);

	$required_fields = apply_filters( 'mem_playlist_required_fields', $required_fields );

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

	$event    = absint( wp_unslash( $_POST['mem_playlist_event'] ) );
	$song     = sanitize_text_field( wp_unslash( $_POST['mem_song'] ) );
	$artist   = isset( $_POST['mem_artist'] ) ? sanitize_text_field( wp_unslash( $_POST['mem_artist'] ) ) : '';
	$category = isset( $_POST['mem_category'] ) ? absint( wp_unslash( $_POST['mem_category'] ) ) : null;
	$notes    = isset( $_POST['mem_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mem_notes'] ) ) : null;

	$playlist_data = array(
		'event_id' => $event,
		'song'     => $song,
		'artist'   => $artist,
		'category' => $category,
		'added_by' => get_current_user_id(),
		'notes'    => $notes,
	);

	$entry_id = mem_store_playlist_entry( $playlist_data );

	if ( $entry_id ) {
		$category   = get_term( $category, 'playlist-category' );
		$entry_data = mem_get_playlist_entry_data( $entry_id );

		ob_start(); ?>

		<div class="playlist-entry-row mem-playlist-entry-<?php echo $entry_id; ?>">
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
					<a class="mem-delete playlist-delete-entry" data-event="<?php echo $event; ?>" data-entry="<?php echo $entry_id; ?>"><?php esc_html_e( 'Remove', 'mobile-events-manager' ); ?></a>
				</span>
			</div>
		</div>

		<?php
		$row_data = ob_get_clean();

		$mem_event     = new MEM_Event( $event );
		$playlist_limit = mem_get_event_playlist_limit( $mem_event->ID );
		$total_entries  = mem_count_playlist_entries( $mem_event->ID );
		$songs          = sprintf( '%d %s', $total_entries, _n( 'song', 'songs', $total_entries, 'mobile-events-manager' ) );
		$length         = mem_playlist_duration( $mem_event->ID, $total_entries );

		if ( ! $mem_event->playlist_is_open() ) {
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
} // mem_submit_playlist_ajax
add_action( 'wp_ajax_mem_submit_playlist', 'mem_submit_playlist_ajax' );
add_action( 'wp_ajax_nopriv_mem_submit_playlist', 'mem_submit_playlist_ajax' );

/**
 * Remove playlist entry.
 *
 * @since 1.5
 * @return void
 */

function mem_remove_playlist_entry_ajax() {
	$event_id = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$song_id  = absint( sanitize_text_field( wp_unslash( $_POST['song_id'] ) ) );

	if ( mem_remove_stored_playlist_entry( $song_id ) ) {
		$total  = mem_count_playlist_entries( $event_id );
		$songs  = sprintf( '%d %s', $total, _n( 'song', 'songs', $total, 'mobile-events-manager' ) );
		$length = mem_playlist_duration( $event_id, $total );
		wp_send_json_success(
			array(
				'count'  => $total,
				'songs'  => $songs,
				'length' => $length,
			)
		);
	}

	wp_send_json_error();
} // mem_remove_playlist_entry_ajax
add_action( 'wp_ajax_mem_remove_playlist_entry', 'mem_remove_playlist_entry_ajax' );
add_action( 'wp_ajax_nopriv_mem_remove_playlist_entry', 'mem_remove_playlist_entry_ajax' );

/**
 * Process guest playlist submission.
 *
 * @since 1.5
 * @return void
 */
function mem_submit_guest_playlist_ajax() {

	if ( ! check_ajax_referer( 'add_guest_playlist_entry', 'mem_nonce', false ) ) {
		wp_send_json(
			array(
				'error' => __( 'An error occured', 'mobile-events-manager' ),
				'field' => 'mem_nonce',
			)
		);
	}

	$required_fields = array(
		'mem_guest_name' => __( 'Name', 'mobile-events-manager' ),
		'mem_guest_song' => __( 'Song', 'mobile-events-manager' ),
	);

	$required_fields = apply_filters( 'mem_guest_playlist_required_fields', $required_fields );

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

	$song   = sanitize_text_field( wp_unslash( $_POST['mem_guest_song'] ) );
	$artist = isset( $_POST['mem_guest_artist'] ) ? sanitize_text_field( wp_unslash( $_POST['mem_guest_artist'] ) ) : '';
	$guest  = ucwords( sanitize_text_field( wp_unslash( $_POST['mem_guest_name'] ) ) );
	$event  = absint( wp_unslash( $_POST['mem_playlist_event'] ) );
	$closed = false;

	$playlist_data = array(
		'mem_guest_song'     => $song,
		'mem_guest_artist'   => $artist,
		'mem_guest_name'     => $guest,
		'mem_playlist_event' => $event,
	);

	$entry_id = mem_store_guest_playlist_entry( $playlist_data );

	if ( $entry_id ) {
		ob_start();
		?>
		<div class="guest-playlist-entry-row mem-playlist-entry-<?php echo $entry_id; ?>">
			<div class="guest-playlist-entry-column">
				<span class="guest-playlist-entry"><?php echo esc_attr( stripslashes( $artist ) ); ?></span>
			</div>
			<div class="guest-playlist-entry-column">
				<span class="guest-playlist-entry"><?php echo esc_attr( stripslashes( $song ) ); ?></span>
			</div>
			<div class="guest-playlist-entry-column">
				<span class="playlist-entry">
					<a class="mem-delete guest-playlist-delete-entry" data-event="<?php echo $event; ?>" data-entry="<?php echo $entry_id; ?>"><?php esc_html_e( 'Remove', 'mobile-events-manager' ); ?></a>
				</span>
			</div>
		</div>
		<?php
		$entry = ob_get_clean();

		$event_playlist_limit = mem_get_event_playlist_limit( $event );
		$entries_in_playlist  = mem_count_playlist_entries( $event );

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

} // mem_submit_guest_playlist_ajax
add_action( 'wp_ajax_mem_submit_guest_playlist', 'mem_submit_guest_playlist_ajax' );
add_action( 'wp_ajax_nopriv_mem_submit_guest_playlist', 'mem_submit_guest_playlist_ajax' );

/**
 * Remove guest playlist entry.
 *
 * @since 1.5
 * @return void
 */
function mem_remove_guest_playlist_entry_ajax() {
	$event_id = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$song_id  = absint( sanitize_text_field( wp_unslash( $_POST['song_id'] ) ) );

	if ( mem_remove_stored_playlist_entry( $song_id ) ) {
		wp_send_json_success();
	}

	wp_send_json_error();
} // mem_remove_guest_playlist_entry_ajax
add_action( 'wp_ajax_mem_remove_guest_playlist_entry', 'mem_remove_guest_playlist_entry_ajax' );
add_action( 'wp_ajax_nopriv_mem_remove_guest_playlist_entry', 'mem_remove_guest_playlist_entry_ajax' );

/**
 * Save the client fields order during drag and drop.
 */
function mem_save_client_field_order_ajax() {
	$client_fields = get_option( 'mem_client_fields' );

	foreach ( sanitize_text_field( wp_unslash( $_POST['fields'] ) ) as $order => $field ) {
		$i = $order + 1;

		$client_fields[ $field ]['position'] = $i;

	}
	update_option( 'mem_client_fields', $client_fields );

	die();
} // mem_save_client_field_order_ajax
add_action( 'wp_ajax_mem_update_client_field_order', 'mem_save_client_field_order_ajax' );

/**
 * Refresh the data within the client details table.
 *
 * @since 1.3.7
 */
function mem_refresh_client_details_ajax() {

	$result = array();

	ob_start();
	mem_do_client_details_table( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['client_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) ) );
	$result['client_details'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // mem_refresh_client_details_ajax
add_action( 'wp_ajax_mem_refresh_client_details', 'mem_refresh_client_details_ajax' );

/**
 * Adds a new client from the event field.
 *
 * @since 1.3.7
 * @global arr wp_unslash($_POST
 */
function mem_add_client_ajax() {

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

		$user_data = apply_filters( 'mem_event_new_client_data', $user_data );

		$client_id = mem_add_client( $user_data );

	}

	$clients = mem_get_clients( 'client' );

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
		do_action( 'mem_after_add_new_client', $user_data );
	}

	echo json_encode( $result );

	die();

} // mem_add_client_ajax
add_action( 'wp_ajax_mem_event_add_client', 'mem_add_client_ajax' );

/**
 * Refresh the data within the venue details table.
 *
 * @since 1.3.7
 */
function mem_refresh_venue_details_ajax() {

	wp_send_json_success(
		array( 'venue' => mem_do_venue_details_table( sanitize_text_field( wp_unslash( $_POST['venue_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) ) )
	);

} // mem_refresh_venue_details_ajax
add_action( 'wp_ajax_mem_refresh_venue_details', 'mem_refresh_venue_details_ajax' );

/**
 * Sets the venue address as the client address.
 *
 * @since 1.4
 */
function mem_set_client_venue_ajax() {

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

} // mem_use_client_address
add_action( 'wp_ajax_mem_set_client_venue', 'mem_set_client_venue_ajax' );

/**
 * Adds a new venue from the events screen.
 *
 * @since 1.3.7
 */
function mem_add_venue_ajax() {

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

	$venue_id = mem_add_venue( $venue_name, $venue_meta );

	$venues = mem_get_venues();

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

} // mem_add_venue_ajax
add_action( 'wp_ajax_mem_add_venue', 'mem_add_venue_ajax' );

/**
 * Refresh the travel data for an event.
 *
 * @since 1.4
 */
function mem_update_event_travel_data_ajax() {
	$employee_id = sanitize_text_field( wp_unslash( $_POST['employee_id'] ) );
	$dest        = sanitize_text_field( wp_unslash( $_POST['venue'] ) );
	$dest        = maybe_unserialize( $dest );

	$mem_travel = new MEM_Travel();

	if ( ! empty( $employee_id ) ) {
		$mem_travel->__set( 'start_address', $mem_travel->get_employee_address( $employee_id ) );
	}

	$mem_travel->set_destination( $dest );
	$mem_travel->get_travel_data();

	if ( ! empty( $mem_travel->data ) ) {
		$travel_cost = $mem_travel->get_cost();
		$response    = array(
			'type'           => 'success',
			'distance'       => mem_format_distance( $mem_travel->data['distance'], false, true ),
			'time'           => mem_seconds_to_time( $mem_travel->data['duration'] ),
			'cost'           => ! empty( $travel_cost ) ? mem_currency_filter( mem_format_amount( $travel_cost ) ) : mem_currency_filter( mem_format_amount( 0 ) ),
			'directions_url' => $mem_travel->get_directions_url(),
			'raw_cost'       => $travel_cost,
		);
	} else {
		$response = array( 'type' => 'error' );
	}

	wp_send_json( $response );
} // mem_update_event_travel_data_ajax
add_action( 'wp_ajax_mem_update_travel_data', 'mem_update_event_travel_data_ajax' );

/**
 * Save the custom event fields order
 *
 * @since 1.3.7
 */
function mem_order_custom_event_fields_ajax() {

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

} // mem_order_custom_event_field_ajax
add_action( 'wp_ajax_order_custom_event_fields', 'mem_order_custom_event_fields_ajax' );

/**
 * Save the event transaction
 */
function mem_save_event_transaction_ajax() {
	global $mem_event;

	$result = array();

	$mem_event = new MEM_Event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$mem_txn   = new MEM_Txn();

	$txn_data = array(
		'post_parent' => sanitize_text_field( wp_unslash( $_POST['event_id'] ) ),
		'post_author' => $mem_event->client,
		'post_status' => sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'Out' ? 'mem-expenditure' : 'mem-income',
		'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['date'] ) ) ) ),
	);

	$txn_meta = array(
		'_mem_txn_status'      => 'Completed',
		'_mem_payment_from'    => $mem_event->client,
		'_mem_txn_total'       => sanitize_text_field( wp_unslash( $_POST['amount'] ) ),
		'_mem_payer_firstname' => mem_get_client_firstname( $mem_event->client ),
		'_mem_payer_lastname'  => mem_get_client_lastname( $mem_event->client ),
		'_mem_payer_email'     => mem_get_client_email( $mem_event->client ),
		'_mem_payment_from'    => mem_get_client_display_name( $mem_event->client ),
		'_mem_txn_source'      => sanitize_text_field( wp_unslash( $_POST['src'] ) ),
	);

	if ( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'In' ) {
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['from'] ) ) ) ) {
			$txn_meta['_mem_payment_from'] = sanitize_text_field( wp_unslash( $_POST['from'] ) );
		} else {
			$txn_meta['_mem_payment_from'] = mem_get_client_display_name( $mem_event->client );
		}
	}

	if ( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'Out' ) {
		if ( ! empty( sanitize_text_field( wp_unslash( $_POST['to'] ) ) ) ) {
			$txn_meta['_mem_payment_to'] = sanitize_text_field( wp_unslash( $_POST['to'] ) );
		} else {
			$txn_meta['_mem_payment_to'] = mem_get_client_display_name( $mem_event->client );
		}
	}

	$mem_txn->create( $txn_data, $txn_meta );

	if ( $mem_txn->ID > 0 ) {
		$result['type'] = 'success';
		mem_set_txn_type( $mem_txn->ID, sanitize_text_field( wp_unslash( $_POST['for'] ) ) );

		$args = array(
			'user_id'         => get_current_user_id(),
			'event_id'        => sanitize_text_field( wp_unslash( $_POST['event_id'] ) ),
			'comment_content' => sprintf(
				__( '%1$s payment of %2$s received for %3$s %4$s.', 'mobile-events-manager' ),
				sanitize_text_field( wp_unslash( $_POST['direction'] ) ) == 'In' ? __( 'Incoming', 'mobile-events-manager' ) : __( 'Outgoing', 'mobile-events-manager' ),
				mem_currency_filter( mem_format_amount( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) ),
				esc_html( mem_get_label_singular( true ) ),
				mem_get_event_contract_id( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) )
			),
		);

		mem_add_journal( $args );

		// Email overide
		if ( empty( sanitize_text_field( wp_unslash( $_POST['send_notice'] ) ) ) && mem_get_option( 'manual_payment_cfm_template' ) ) {
			$manual_email_template = mem_get_option( 'manual_payment_cfm_template' );
			mem_update_option( 'manual_payment_cfm_template', 0 );
		}

		$payment_for = $mem_txn->get_type();
		$amount      = mem_currency_filter( mem_format_amount( sanitize_text_field( wp_unslash( $_POST['amount'] ) ) ) );

		mem_add_content_tag(
			'payment_for',
			__( 'Reason for payment', 'mobile-events-manager' ),
			function() use ( $payment_for ) {
				return $payment_for;
			}
		);

		mem_add_content_tag(
			'payment_amount',
			__( 'Payment amount', 'mobile-events-manager' ),
			function() use ( $amount ) {
				return $amount;
			}
		);

		mem_add_content_tag( 'payment_date', __( 'Date of payment', 'mobile-events-manager' ), 'mem_content_tag_ddmmyyyy' );

		/**
		 * Allow hooks into this payment. The hook is suffixed with 'in' or 'out' depending
		 * on the payment direction. i.e. mem_post_add_manual_txn_in and mem_post_add_manual_txn_out
		 *
		 * @since 1.3.7
		 * @param int $event_id
		 * @param obj $txn_id
		 */
		do_action( 'mem_post_add_manual_txn_' . strtolower( sanitize_text_field( wp_unslash( $_POST['direction'] ) ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ), $mem_txn->ID );

		// Email overide
		if ( empty( sanitize_text_field( wp_unslash( $_POST['send_notice'] ) ) ) && isset( $manual_email_template ) ) {
			mem_update_option( 'manual_payment_cfm_template', $manual_email_template );
		}

		$result['deposit_paid'] = 'N';
		$result['balance_paid'] = 'N';

		if ( $mem_event->get_remaining_deposit() < 1 ) {
			mem_update_event_meta( $mem_event->ID, array( '_mem_event_deposit_status' => 'Paid' ) );
			$result['deposit_paid'] = 'Y';

			// Update event status if contract signed & we wait for deposit paid before confirming booking
			if ( mem_require_deposit_before_confirming() && $mem_event->get_contract_status() ) {

				mem_update_event_status(
					$mem_event->ID,
					'mem-approved',
					$mem_event->post_status,
					array( 'client_notices' => mem_get_option( 'booking_conf_to_client' ) )
				);

			}
		}

		if ( $mem_event->get_balance() < 1 ) {
			mem_update_event_meta( $mem_event->ID, array( '_mem_event_balance_status' => 'Paid' ) );
			mem_update_event_meta( $mem_event->ID, array( '_mem_event_deposit_status' => 'Paid' ) );
			$result['balance_paid'] = 'Y';
			$result['deposit_paid'] = 'Y';
		}
	} else {
		$result['type'] = 'error';
		$result['msg']  = __( 'Unable to add transaction', 'mobile-events-manager' );
	}

	$result['event_status'] = get_post_status( $mem_event->ID );

	ob_start();
	mem_do_event_txn_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['transactions'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();
} // mem_save_event_transaction_ajax
add_action( 'wp_ajax_add_event_transaction', 'mem_save_event_transaction_ajax' );

/**
 * Add a new event type
 * Initiated from the Event Post screen
 */
function mem_add_event_type_ajax() {

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

} // mem_add_event_type_ajax
add_action( 'wp_ajax_add_event_type', 'mem_add_event_type_ajax' );

/**
 * Execute single event tasks.
 *
 * @since 1.5
 */
function mem_execute_event_task_ajax() {
	$task_id         = sanitize_text_field( wp_unslash( $_POST['task'] ) );
	$event_id        = absint( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$tasks           = mem_get_tasks_for_event( $event_id );
	$result          = mem_run_single_event_task( $event_id, $task_id );
	$mem_event      = new MEM_Event( $event_id );
	$completed_tasks = $mem_event->get_tasks();
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
			mem_get_task_name( $task_slug ),
			date( mem_get_option( 'short_date_format' ), $run_time )
		);
	}

	$task_history = '<span class="task-history-items">' . implode( '<br>', $tasks_history ) . '</span>';

	wp_send_json_success(
		array(
			'status'  => $mem_event->post_status,
			'history' => $task_history,
		)
	);
} // mem_execute_event_task_ajax
add_action( 'wp_ajax_mem_execute_event_task', 'mem_execute_event_task_ajax' );

/**
 * Add a new transaction type
 * Initiated from the Transaction Post screen
 */
function mem_add_transaction_type_ajax() {
	global $mem;

	MEM()->debug->log_it( 'Adding ' . sanitize_text_field( wp_unslash( $_POST['type'] ) ) . ' new Transaction Type from Transaction Post form', true );

	$args = array(
		'taxonomy'         => 'transaction-types',
		'hide_empty'       => 0,
		'name'             => 'mem_transaction_type',
		'id'               => 'mem_transaction_type',
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

	MEM()->debug->log_it( 'Completed adding ' . sanitize_text_field( wp_unslash( $_POST['type'] ) ) . ' new Transaction Type from Transaction Post form', true );

	$args['selected'] = 'success' === $result['type'] ? $term['term_id'] : sanitize_text_field( wp_unslash( $_POST['current'] ) );

	$result['transaction_types'] = wp_dropdown_categories( $args );

	$result = json_encode( $result );
	echo $result;

	die();
} // mem_add_transaction_type_ajax
add_action( 'wp_ajax_add_transaction_type', 'mem_add_transaction_type_ajax' );

/**
 * Determine the event setup time
 *
 * @since 1.5
 * @return void
 */
function mem_event_setup_time_ajax() {
	$time_format = mem_get_option( 'time_format' );
	$start_time  = sanitize_text_field( wp_unslash( $_POST['time'] ) );
	$event_date  = sanitize_text_field( wp_unslash( $_POST['date'] ) );
	$date        = new DateTime( $event_date . ' ' . $start_time );
	$timestamp   = $date->format( 'U' );

	$setup_time = $timestamp - ( (int) mem_get_option( 'setup_time' ) * 60 );

	$hour     = 'H:i' == $time_format ? gmdate( 'H', $setup_time ) : gmdate( 'g', $setup_time );
	$minute   = gmdate( 'i', $setup_time );
	$meridiem = 'H:i' == $time_format ? '' : gmdate( 'A', $setup_time );

	wp_send_json_success(
		array(
			'hour'       => $hour,
			'minute'     => $minute,
			'meridiem'   => $meridiem,
			'date'       => gmdate( mem_get_option( 'short_date_format' ), $setup_time ),
			'datepicker' => gmdate( 'Y-m-d', $setup_time ),
		)
	);
} // mem_event_setup_time_ajax
add_action( 'wp_ajax_mem_event_setup_time', 'mem_event_setup_time_ajax' );

/**
 * Calculate the event cost as event elements change
 *
 * @since 1.0
 * @return void
 */
function mem_update_event_cost_ajax() {

	$mem_event  = new MEM_Event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$mem_travel = new MEM_Travel();

	$event_cost    = $mem_event->price;
	$event_date    = $event_date = ! empty( sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : null;
	$base_cost     = '0.00';
	$package       = $mem_event->get_package();
	$package_price = $package ? mem_get_package_price( $package, $event_date ) : '0.00';
	$addons        = $mem_event->get_addons();
	$travel_data   = $mem_event->get_travel_data();
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
			$addon_price = mem_get_addon_price( $addon, $event_date );
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
		$package_cost = (float) mem_get_package_price( $new_package, $event_date );
	}

	if ( $new_addons ) {
		foreach ( $new_addons as $new_addon ) {
			$addons_cost += (float) mem_get_addon_price( $new_addon, $event_date );
		}
	}

	if ( $mem_travel->add_travel_cost ) {
		if ( ! empty( $employee_id ) ) {
			$mem_travel->__set( 'start_address', $mem_travel->get_employee_address( $employee_id ) );
		}

		$mem_travel->set_destination( $dest );
		$mem_travel->get_travel_data();

		$new_travel = ! empty( $mem_travel->data ) ? $mem_travel->get_cost() : false;

		if ( $new_travel && (float) preg_replace( '/[^0-9.]*/', '', $mem_travel->data['distance'] ) >= mem_get_option( 'travel_min_distance' ) ) {
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
		$result['cost'] = mem_sanitize_amount( (float) $cost );
	} else {
		$result['type'] = 'success';
		$result['cost'] = mem_sanitize_amount( 0 );
	}

	$result['package_cost']    = mem_sanitize_amount( $package_cost );
	$result['addons_cost']     = mem_sanitize_amount( $addons_cost );
	$result['travel_cost']     = mem_sanitize_amount( $travel_cost );
	$result['additional_cost'] = mem_sanitize_amount( $additional );
	$result['discount']        = mem_sanitize_amount( $discount );

	wp_send_json( $result );

} // mem_update_event_cost_ajax
add_action( 'wp_ajax_mem_update_event_cost', 'mem_update_event_cost_ajax' );

/**
 * Update the available list of packages and addons when the primary event employee
 * the event type, or the event date changes.
 *
 * @since 1.0
 * @return void
 */
function mem_refresh_event_package_options_ajax() {

	$employee        = ( ! empty( sanitize_text_field( wp_unslash( $_POST['employee'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['employee'] ) ) : '' );
	$current_package = ( ! empty( sanitize_text_field( wp_unslash( $_POST['package'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['package'] ) ) : '' );
	$current_addons  = ( ! empty( sanitize_text_field( wp_unslash( $_POST['addons'] ) ) ) ? sanitize_textarea_field( wp_unslash( $_POST['addons'] ) ) : '' );
	$event_type      = ( ! empty( sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '' );
	$event_date      = ( ! empty( sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '' );

	$packages = MEM()->html->packages_dropdown(
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

	$addons = MEM()->html->addons_dropdown(
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

} // mem_refresh_event_package_options_ajax
add_action( 'wp_ajax_refresh_event_package_options', 'mem_refresh_event_package_options_ajax' );

/**
 * Update the event deposit amount based upon the event cost
 * and the payment settings.
 *
 * @since 1.0
 * @return void
 */
function mem_update_event_deposit_ajax() {

	$event_cost = sanitize_text_field( wp_unslash( $_POST['current_cost'] ) );

	$deposit = mem_calculate_deposit( $event_cost );

	if ( ! empty( $deposit ) ) {
		$result['type']    = 'success';
		$result['deposit'] = mem_sanitize_amount( $deposit );
	} else {
		$result['type'] = 'error';
		$result['msg']  = 'Unable to calculate deposit';
	}

	$result = json_encode( $result );
	echo $result;

	die();

} // mem_update_event_deposit_ajax
add_action( 'wp_ajax_update_event_deposit', 'mem_update_event_deposit_ajax' );

/**
 * Add an employee to the event.
 *
 * @since 1.3
 * @return void
 */
function mem_add_employee_to_event_ajax() {

	$args = array(
		'id'             => isset( $_POST['employee_id'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ) : '',
		'role'           => isset( $_POST['employee_role'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_role'] ) ) : '',
		'wage'           => isset( $_POST['employee_wage'] ) ? sanitize_text_field( wp_unslash( $_POST['employee_wage'] ) ) : '',
		'payment_status' => 'unpaid',
	);

	if ( ! mem_add_employee_to_event( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ), $args ) ) {

		$result['type'] = 'error';
		$result['msg']  = __( 'Unable to add employee', 'mobile-events-manager' );

	} else {
		$result['type'] = 'success';
	}

	ob_start();
	mem_do_event_employees_list_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['employees'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // mem_add_employee_to_event_ajax
add_action( 'wp_ajax_add_employee_to_event', 'mem_add_employee_to_event_ajax' );

/**
 * Remove an employee from the event.
 *
 * @since 1.3
 * @return void
 */
function mem_remove_employee_from_event_ajax() {

	mem_remove_employee_from_event( sanitize_text_field( wp_unslash( $_POST['employee_id'] ) ), sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );

	$result['type'] = 'success';

	ob_start();
	mem_do_event_employees_list_table( sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
	$result['employees'] = ob_get_contents();
	ob_get_clean();

	echo json_encode( $result );

	die();

} // mem_remove_employee_from_event_ajax
add_action( 'wp_ajax_remove_employee_from_event', 'mem_remove_employee_from_event_ajax' );

/**
 * Retrieve the title of a template
 *
 * @since 1.4.7
 * @return str
 */
function mem_mem_get_template_title_ajax() {
	$title           = get_the_title( sanitize_text_field( wp_unslash( $_POST['template'] ) ) );
	$result['title'] = $title;
	echo json_encode( $result );

	die();
} // mem_mem_get_template_title_ajax
add_action( 'wp_ajax_mem_get_template_title', 'mem_mem_get_template_title_ajax' );

/**
 * Update the email content field with the selected template.
 *
 * @since 1.3
 * @return void
 */
function mem_set_email_content_ajax() {

	if ( empty( sanitize_text_field( wp_unslash( $_POST['template'] ) ) ) ) {
		$result['type']            = 'success';
		$result['updated_content'] = '';
	} else {
		$content = mem_get_email_template_content( sanitize_text_field( wp_unslash( $_POST['template'] ) ) );

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

} // mem_set_email_content_ajax
add_action( 'wp_ajax_mem_set_email_content', 'mem_set_email_content_ajax' );

/**
 * Update the email content field with the selected template.
 *
 * @since 1.3
 * @return void
 */
function mem_user_events_dropdown_ajax() {

	$result['event_list'] = '<option value="0">' . __( 'Select an Event', 'mobile-events-manager' ) . '</option>';

	if ( ! empty( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) ) ) {

		$statuses = 'any';

		if ( mem_is_employee( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ) ) ) {

			if ( mem_get_option( 'comms_show_active_events_only' ) ) {
				$statuses = array( 'post_status' => mem_active_event_statuses() );
			}

			$events = mem_get_employee_events( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ), $statuses );

		} else {

			if ( mem_get_option( 'comms_show_active_events_only' ) ) {
				$statuses = mem_active_event_statuses();
			}

			$events = mem_get_client_events( sanitize_text_field( wp_unslash( $_POST['recipient'] ) ), $statuses );
		}

		if ( $events ) {
			foreach ( $events as $event ) {
				$result['event_list'] .= '<option value="' . $event->ID . '">';
				$result['event_list'] .= mem_get_event_date( $event->ID ) . ' ';
				$result['event_list'] .= __( 'from', 'mobile-events-manager' ) . ' ';
				$result['event_list'] .= mem_get_event_start( $event->ID ) . ' ';
				$result['event_list'] .= '(' . mem_get_event_status( $event->ID ) . ')';
				$result['event_list'] .= '</option>';
			}
		}
	}

	$result['type'] = 'success';
	$result         = json_encode( $result );

	echo $result;

	die();

} // mem_user_events_dropdown_ajax
add_action( 'wp_ajax_mem_user_events_dropdown', 'mem_user_events_dropdown_ajax' );

/**
 * Refresh the addons options when the package selection is updated.
 *
 * @since 1.3.7
 * @return void
 */
function mem_refresh_event_addon_options_ajax() {

	$package    = sanitize_text_field( wp_unslash( $_POST['package'] ) );
	$employee   = ( isset( $_POST['employee'] ) ? sanitize_text_field( wp_unslash( $_POST['employee'] ) ) : false );
	$selected   = ( ! empty( $_POST['selected'] ) ? sanitize_text_field( wp_unslash( $_POST['selected'] ) ) : array() );
	$event_type = ( ! empty( $_POST['event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['event_type'] ) ) : '' );
	$event_date = ( ! empty( $_POST['event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['event_date'] ) ) : '' );

	$addons = MEM()->html->addons_dropdown(
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

} // mem_refresh_event_addon_options_ajax
add_action( 'wp_ajax_refresh_event_addon_options', 'mem_refresh_event_addon_options_ajax' );
add_action( 'wp_ajax_nopriv_refresh_event_addon_options', 'mem_refresh_event_addon_options_ajax' );

/**
 * Check the availability status for the given date
 *
 * @since 1.3
 * @param Global wp_unslash( $_POST
 * @return arr
 */
function mem_do_availability_check_ajax() {

	$date       = sanitize_text_field( wp_unslash( $_POST['date'] ) );
	$employees  = isset( $_POST['employees'] ) ? sanitize_text_field( wp_unslash( $_POST['employees'] ) ) : false;
	$roles      = isset( $_POST['roles'] ) ? sanitize_text_field( wp_unslash( $_POST['roles'] ) ) : false;
	$short_date = mem_format_short_date( $date );
	$result     = mem_do_availability_check( $date, $employees, $roles );

	if ( ! empty( $result['available'] ) ) {
		$result['result']       = 'available';
		$result['notice_class'] = 'updated';
	} else {
		$result['result']       = 'unavailable';
		$result['notice_class'] = 'error';
	}

	wp_send_json( $result );
} // mem_do_availability_check_ajax
add_action( 'wp_ajax_mem_do_availability_check', 'mem_do_availability_check_ajax' );
add_action( 'wp_ajax_nopriv_mem_do_availability_check', 'mem_do_availability_check_ajax' );
