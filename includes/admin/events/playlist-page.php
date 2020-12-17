<?php
/**
 * Contains all admin playlist functions
 *
 * @package TMEM
 * @subpackage Admin/Events
 * @since 1.3
 */

/**
 * Display the event playlist page.
 *
 * @since 1.3
 * @param
 * @return str The event playlist page content.
 */
function tmem_display_event_playlist_page() {

	if ( ! tmem_employee_can( 'read_events' ) && ! tmem_employee_working_event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ) {
		wp_die(
			'<h1>' . __( 'Cheatin&#8217; uh?', 'mobile-events-manager' ) . '</h1>' .
			'<p>' . __( 'You do not have permission to view this playlist.', 'mobile-events-manager' ) . '</p>',
			403
		);
	}

	if ( ! class_exists( 'TMEM_PlayList_Table' ) ) {
		require_once TMEM_PLUGIN_DIR . '/includes/admin/events/class-tmem-playlist-table.php';
	}

	$playlist_obj = new TMEM_PlayList_Table();

	?>
	<div class="wrap">
		<h1><?php printf( wp_kses_post( 'Playlist for %1$s %2$s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), tmem_get_event_contract_id( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ); ?></h1>

		<form method="post">
			<?php
			$playlist_obj->prepare_items();
			$playlist_obj->display_header();

			if ( count( $playlist_obj->items ) > 0 ) {
				$playlist_obj->views();
			}

			$playlist_obj->display();
			$playlist_obj->entry_form();
			?>
		</form>
		<br class="clear">
	</div>
	<?php
} // tmem_display_event_playlist_page

/**
 * Format the playlist results for emailing/printing.
 *
 * @since 1.3
 * @param int  $event_id The event ID to retrieve the playlist for.
 * @param str  $orderby Which field to order the playlist entries by.
 * @param str  $order Order ASC or DESC.
 * @param int  $repeat_headers Repeat the table headers after this many rows.
 * @param bool $hide_empty If displaying by category do we hide empty categories?
 * @return str $results Output of playlist entries.
 */
function tmem_format_playlist_content( $event_id, $orderby = 'category', $order = 'ASC', $hide_empty = true, $repeat_headers = 0 ) {
	global $current_user;

	$tmem_event = tmem_get_event( $event_id );

	// Obtain results ordered by category
	if ( 'category' === $orderby ) {

		$playlist = tmem_get_playlist_by_category( $event_id, array( 'hide_empty' => $hide_empty ) );

		if ( $playlist ) {

			foreach ( $playlist as $cat => $entries ) {

				foreach ( $entries as $entry ) {

					$entry_data = tmem_get_playlist_entry_data( $entry->ID );

					$results[] = array(
						'ID'       => $entry->ID,
						'event'    => $event_id,
						'artist'   => stripslashes( $entry_data['artist'] ),
						'song'     => stripslashes( $entry_data['song'] ),
						'added_by' => stripslashes( $entry_data['added_by'] ),
						'category' => $cat,
						'notes'    => stripslashes( $entry_data['djnotes'] ),
						'date'     => tmem_format_short_date( $entry->post_date ),
					);

				}
			}
		}
	}
	// Obtain results ordered by another field.
	else {

		$args = array(
			'orderby'  => 'date' === $orderby ? 'post_date' : 'meta_value',
			'order'    => $order,
			'meta_key' => 'date' === $orderby ? '' : '_tmem_playlist_entry_' . $orderby,
		);

		$entries = tmem_get_playlist_entries( $event_id, $args );

		if ( $entries ) {
			foreach ( $entries as $entry ) {
				$entry_data = tmem_get_playlist_entry_data( $entry->ID );

				$categories = wp_get_object_terms( $entry->ID, 'playlist-category' );

				if ( ! empty( $categories ) ) {
					$category = $categories[0]->name;
				}

				$results[] = array(
					'ID'       => $entry->ID,
					'event'    => $event_id,
					'artist'   => stripslashes( $entry_data['artist'] ),
					'song'     => stripslashes( $entry_data['song'] ),
					'added_by' => stripslashes( $entry_data['added_by'] ),
					'category' => ! empty( $category ) ? $category : '',
					'notes'    => stripslashes( $entry_data['djnotes'] ),
					'date'     => tmem_format_short_date( $entry->post_date ),
				);
			}
		}
	}

	// Build out the formatted display
	if ( ! empty( $results ) ) {

		$i = 0;

		$output  = '<p>' . sprintf( esc_html__( 'Hey %s', 'mobile-events-manager' ), $current_user->first_name ) . '</p>' . "\n";
		$output .= '<p>' . __( 'Here is the playlist you requested...', 'mobile-events-manager' ) . '</p>' . "\n";

		$output .= '<p>' .
					 __( 'Client Name', 'mobile-events-manager' ) . ': ' . tmem_get_client_display_name( $tmem_event->client ) . '<br />' . "\n" .
					 __( 'Event Date', 'mobile-events-manager' ) . ': ' . tmem_get_event_long_date( $tmem_event->ID ) . '<br />' . "\n" .
					 __( 'Event Type', 'mobile-events-manager' ) . ': ' . tmem_get_event_type( $tmem_event->ID ) . '<br />' . "\n" .
					 __( 'Songs in Playlist', 'mobile-events-manager' ) . ': ' . count( $results ) . '<br />' . "\n" .
					 '</p>';

		$output .= '<hr />' . "\n";

		$headers = '<tr style="height: 30px">' . "\n" .
						'<td style="width: 15%"><strong>' . __( 'Song', 'mobile-events-manager' ) . '</strong></td>' . "\n" .
						'<td style="width: 15%"><strong>' . __( 'Artist', 'mobile-events-manager' ) . '</strong></td>' . "\n" .
						'<td style="width: 15%"><strong>' . __( 'Category', 'mobile-events-manager' ) . '</strong></td>' . "\n" .
						'<td style="width: 40%"><strong>' . __( 'Notes', 'mobile-events-manager' ) . '</strong></td>' . "\n" .
						'<td style="width: 15%"><strong>' . __( 'Added By', 'mobile-events-manager' ) . '</strong></td>' . "\n" .
					'</tr>' . "\n";

		$output .= '<table width="90%" border="0" cellpadding="0" cellspacing="0">' . "\n";

		$output .= $headers;

		foreach ( $results as $result ) {
			if ( $repeat_headers > 0 && $i == $repeat_headers ) {
				$output .= '<tr>' . "\n" .
								'<td colspan="5">&nbsp;</td>' . "\n" .
							'</tr>' . "\n" .
							$headers;
				$i       = 0;
			}

			if ( is_numeric( $result['added_by'] ) ) {
				$user = get_userdata( $result['added_by'] );

				$name = $user->display_name;
			} else {
				$name = $result['added_by'];
			}

			$output .= '<tr>' . "\n" .
							'<td>' . stripslashes( $result['song'] ) . '</td>' . "\n" .
							'<td>' . stripslashes( $result['artist'] ) . '</td>' . "\n" .
							'<td>' . stripslashes( $result['category'] ) . '</td>' . "\n" .
							'<td>' . stripslashes( $result['notes'] ) . '</td>' . "\n" .
							'<td>' . stripslashes( $name ) . '</td>' . "\n" .
						'</tr>' . "\n";

			$i++;
		}

		$output .= '</table>' . "\n";

	} else {
		$output = '<p>' . __( 'The playlist for this event does not contain any entries!', 'mobile-events-manager' ) . '</p>' . "\n";
	}

	return $output;
} // tmem_format_playlist_content
