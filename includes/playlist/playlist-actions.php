<?php
/**
 * Contains all playlist related functions called via actions executed on the front end
 *
 * @package TMEM
 * @subpackage Playlists
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect to playlist.
 *
 * @since 1.3
 * @param
 * @return void
 */
function tmem_goto_playlist_action( $data ) {
	if ( ! isset( $data['event_id'] ) ) {
		return;
	}

	if ( ! tmem_event_exists( $data['event_id'] ) ) {
		wp_die( 'Sorry but no event exists', 'mobile-events-manager' );
	}

	wp_safe_redirect(
		add_query_arg(
			'event_id',
			$data['event_id'],
			tmem_get_formatted_url( tmem_get_option( 'playlist_page' ) )
		)
	);
	die();
} // tmem_goto_guest_playlist
add_action( 'tmem_goto_playlist', 'tmem_goto_playlist_action' );

/**
 * Redirect to guest playlist.
 *
 * @since 1.3
 * @param
 * @return void
 */
function tmem_goto_guest_playlist_action( $data ) {
	if ( ! isset( $data['playlist'] ) ) {
		return;
	}

	$event = tmem_get_event_by_playlist_code( $data['playlist'] );

	if ( ! $event ) {
		wp_die( 'Sorry but no event exists', 'mobile-events-manager' );
	}

	wp_safe_redirect(
		add_query_arg(
			'guest_playlist',
			$data['playlist'],
			tmem_get_formatted_url( tmem_get_option( 'playlist_page' ) )
		)
	);
	die();
} // tmem_goto_guest_playlist
add_action( 'tmem_goto_guest_playlist', 'tmem_goto_guest_playlist_action' );

/**
 * Redirect to playlist.
 *
 * Catches incorrect redirects and forwards to the playlist page.
 *
 * @see https://github.com/tmem/mobile-events-manager/issues/101
 *
 * @since 1.3.7
 * @param
 * @return void
 */
function tmem_correct_guest_playlist_url_action() {
	if ( ! isset( $_GET['guest_playlist'] ) ) {
		return;
	}

	if ( ! is_page( tmem_get_option( 'playlist_page' ) ) ) {
		wp_safe_redirect(
			add_query_arg(
				'guest_playlist',
				sanitize_text_field( wp_unslash( $_GET['guest_playlist'] ) ),
				tmem_get_formatted_url( tmem_get_option( 'playlist_page' ) )
			)
		);
		die();
	}
} // tmem_correct_guest_playlist_url_action
add_action( 'template_redirect', 'tmem_correct_guest_playlist_url_action' );

/**
 * Sets the flag to notify clients when a guest entry is added
 *
 * @since 1.5
 * @param int $entry_id The playlist entry ID.
 * @param int $event_id The event ID.
 * @return void
 */
function tmem_event_playlist_set_guest_notification_action( $entry_id, $event_id ) {
	if ( tmem_is_task_active( 'playlist-notification' ) ) {
		update_post_meta( $event_id, '_tmem_playlist_client_notify', '1', true );
	}
} // tmem_event_playlist_set_guest_notification_action
add_action( 'tmem_insert_guest_playlist_entry', 'tmem_event_playlist_set_guest_notification_action', 10, 2 );
