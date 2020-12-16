<?php
/**
 * Process event actions
 *
 * @package MEM
 * @subpackage Events
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect to event.
 *
 * @since 1.3
 * @param
 * @return void
 */
function mem_goto_event_action( $data ) {

	if ( ! isset( $data['event_id'] ) ) {
		return;
	}

	if ( ! mem_event_exists( $data['event_id'] ) ) {
		wp_die( 'Sorry but no event exists', 'mobile-events-manager' );
	}

	wp_safe_redirect( mem_get_event_uri( $data['event_id'] ) );
	die();

} // mem_goto_event
add_action( 'mem_goto_event', 'mem_goto_event_action' );

/**
 * Accept an enquiry from the Client Zone.
 *
 * @since 1.3
 * @param arr $data Passed from the super global $_POST.
 * @return void
 */
function mem_event_accept_enquiry_action( $data ) {

	if ( ! wp_verify_nonce( $data['mem_nonce'], 'accept_enquiry' ) ) {

		$message = 'nonce_fail';

	} elseif ( ! isset( $data['event_id'] ) ) {

		$message = 'missing_event';

	} else {

		if ( mem_accept_enquiry( $data ) ) {
			$message = 'enquiry_accepted';
		} else {
			$message = 'enquiry_accept_fail';
		}
	}

	wp_safe_redirect( add_query_arg( 'mem_message', $message, mem_get_event_uri( $data['event_id'] ) ) );

	die();

} // mem_event_accept_enquiry_action
add_action( 'mem_accept_enquiry', 'mem_event_accept_enquiry_action' );

/**
 * Fires after an event's status is updated.
 *
 * Add journal entry.
 *
 * @since 1.3
 * @param int|bool $result The result of the status change function. False is an unsuccessful status update.
 * @param int      $event_id The event ID.
 * @param str      $new_status The new event status.
 * @param str      $old_status The old event status.
 * @param arr      $args Arguments passed to the mem_update_event_status function.
 * @return void
 */
function mem_event_add_journal_after_status_change( $result, $event_id, $new_status, $old_status, $args ) {

	if ( empty( $result ) ) {
		return;
	}

	$reject_reason = ( 'mem-rejected' === $new_status && ! empty( $args['reject_reason'] ) ) ? '<br />' . $args['reject_reason'] : '';

	$comment_args = array(
		'user_id'         => is_user_logged_in() ? get_current_user_id() : 1,
		'event_id'        => $event_id,
		'comment_content' => sprintf(
			__( '%1$s status updated to %2$s.%3$s', 'mobile-events-manager' ),
			esc_html( mem_get_label_singular() ),
			mem_get_event_status( $event_id ),
			$reject_reason
		),
	);

	mem_add_journal( $comment_args );

} // mem_event_add_journal_after_status_change
add_action( 'mem_post_event_status_change', 'mem_event_add_journal_after_status_change', 10, 5 );
