<?php
/**
 * Process event actions
 *
 * @package MEM
 * @subpackage Events
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds an entry to the playlist from the admin interface.
 *
 * @since 1.4
 * @param arr Array of form post data.
 */
function mem_add_event_playlist_entry_action( $data ) {
	if ( empty( $data['song'] ) || empty( $data['artist'] ) ) {
		$message = 'adding_song_failed';
	} elseif ( ! wp_verify_nonce( $data['mem_nonce'], 'add_playlist_entry' ) ) {
		$message = 'security_failed';
	} else {
		if ( mem_store_playlist_entry( $data ) ) {
			$message = 'song_added';
		} else {
			$message = 'adding_song_failed';
		}
	}

	$url = remove_query_arg( array( 'mem-action', 'mem_nonce', 'mem-message' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem-message' => $message,
			),
			$url
		)
	);
	die();
} // mem_add_event_playlist_entry_action
add_action( 'mem-add_playlist_entry', 'mem_add_event_playlist_entry_action' );

/**
 * Process song removals from bulk action
 *
 * @since 1.3
 * @param arr $_POST super global
 * @return void
 */
function mem_bulk_action_remove_playlist_entry_action() {

	if ( isset( $_POST['action'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
	} elseif ( isset( $_POST['action2'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_POST['action2'] ) );
	} else {
		return;
	}

	if ( ! isset( $action, $_POST['mem-playlist-bulk-delete'] ) ) {
		return;
	}

	foreach ( sanitize_text_field( wp_unslash( $_POST['mem-playlist-bulk-delete'] ) ) as $id ) {
		mem_remove_stored_playlist_entry( $id );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem-message' => 'song_removed',
			)
		)
	);
	die();

} // mem_bulk_action_remove_playlist_entry
add_action( 'load-admin_page_mem-playlists', 'mem_bulk_action_remove_playlist_entry_action' );

/**
 * Process song removals from delete link
 *
 * @since 1.3
 * @param int|arr $entry_ids Playlist entries to remove
 * @return void
 */
function mem_remove_playlist_song_action( $data ) {
	if ( ! wp_verify_nonce( $data['mem_nonce'], 'remove_playlist_entry' ) ) {
		$message = 'security_failed';
	} else {
		if ( mem_remove_stored_playlist_entry( $data['id'] ) ) {
			$message = 'song_removed';
		} else {
			$message = 'song_remove_failed';
		}
	}

	$url = remove_query_arg( array( 'mem-action', 'mem_nonce' ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem-message' => $message,
			),
			$url
		)
	);
	die();
} // mem_remove_playlist_entry_action
add_action( 'mem-delete_song', 'mem_remove_playlist_song_action' );

/**
 * Display the playlist for printing.
 *
 * @since 1.3
 * @param arr $data The super global $_POST
 * @return str Output for the print page.
 */
function mem_print_event_playlist_action( $data ) {
	if ( ! wp_verify_nonce( $data['mem_nonce'], 'print_playlist_entry' ) ) {
		$message = 'security_failed';
	} else {

		$mem_event = mem_get_event( $data['print_playlist_event_id'] );

		$content = mem_format_playlist_content( $mem_event->ID, $data['print_order_by'], 'ASC', true );

		$content = apply_filters( 'mem_print_playlist', $content, $data, $mem_event );

		?>
		<script type="text/javascript">
		window.onload = function() { window.print(); }
		</script>
		<style>
		@page	{
			size: landscape;
			margin: 2cm;
		}
		body { 
			background:white;
			color:black;
			margin:0;
			width:auto
		}
		#adminmenu {
			display: none !important
		}
		#adminmenumain {
			display: none !important
		}
		#adminmenuback {
			display: none !important
		}
		#adminmenuwrap {
			display: none !important
		}
		#wpadminbar {
			display: none !important
		}
		#wpheader {
			display: none !important;
		}
		#wpcontent {
			margin-left:0; 
			float:none; 
			width:auto }
		}
		#wpcomments {
			display: none !important;
		}
		#message {
			display: none !important;
		}
		#wpsidebar {
			display: none !important;
		}
		#wpfooter {
			display: none !important;
		}
		</style>
		<?php
		echo $content;
		echo '<p style="text-align: center" class="description">Powered by <a style="color:#F90" href="http://mem.co.uk" target="_blank">' . MEM_NAME . '></a>, version ' . MEM_VERSION_NUM . '</p>' . "\n";

	}

	die();
} // mem_print_event_playlist_action
add_action( 'mem-print_playlist', 'mem_print_event_playlist_action' );

/**
 * Send the playlist via email.
 *
 * @since 1.3
 * @param arr $data The super global $_POST
 * @return void
 */
function mem_email_event_playlist_action( $data ) {

	if ( ! wp_verify_nonce( $data['mem_nonce'], 'email_playlist_entry' ) ) {
		$message = 'security_failed';
	} else {
		global $current_user;

		$mem_event = mem_get_event( $data['email_playlist_event_id'] );

		$content = mem_format_playlist_content( $mem_event->ID, $data['email_order_by'], 'ASC', true );

		$content = apply_filters( 'mem_print_playlist', $content, $data, $mem_event );

		$html_content_start = '<html>' . "\n" . '<body>' . "\n";
		$html_content_end   = '<p>' . __( 'Regards', 'mobile-events-manager' ) . '</p>' . "\n" .
					'<p>{company_name}</p>' . "\n";
					'<p>&nbsp;</p>' . "\n";
					'<p align="center" style="font-size: 9px">Powered by <a style="color:#F90" href="https://mem.co.uk" target="_blank">' . MEM_NAME . '</a> version ' . MEM_VERSION_NUM . '</p>' . "\n" .
					'</body>' . "\n" . '</html>';

		$args = array(
			'to_email'   => $current_user->user_email,
			'from_name'  => mem_get_option( 'company_name' ),
			'from_email' => mem_get_option( 'system_email' ),
			'event_id'   => $mem_event->ID,
			'client_id'  => $mem_event->client,
			'subject'    => sprintf( esc_html__( 'Playlist for %s ID {contract_id}', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
			'message'    => $html_content_start . $content . $html_content_end,
			'copy_to'    => 'disable',
		);

		if ( mem_send_email_content( $args ) ) {
			$message = 'playlist_emailed';
		} else {
			$message = 'playlist_email_failed';
		}
	}

	wp_safe_redirect(
		add_query_arg( 'mem-message', $message )
	);
	die();
} // mem_email_event_playlist
add_action( 'mem-email_playlist', 'mem_email_event_playlist_action' );
