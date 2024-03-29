<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Manage the venue posts
 */

/**
 * Define the columns to be displayed for venue posts
 *
 * @since 0.5
 * @param arr $columns Array of column names
 * @return arr $columns Filtered array of column names
 */
function tmem_venue_post_columns( $columns ) {

	$columns = array(
		'cb'          => '<input type="checkbox" />',
		'title'       => __( 'Venue', 'mobile-events-manager' ),
		'contact'     => __( 'Contact', 'mobile-events-manager' ),
		'phone'       => __( 'Phone', 'mobile-events-manager' ),
		'town'        => __( 'Town', 'mobile-events-manager' ),
		'county'      => __( 'County', 'mobile-events-manager' ),
		'event_count' => sprintf( esc_html__( '%s', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ),
		'info'        => __( 'Information', 'mobile-events-manager' ),
		'details'     => __( 'Details', 'mobile-events-manager' ),
	);

	if ( ! tmem_employee_can( 'add_venues' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // tmem_venue_post_columns
add_filter( 'manage_tmem-venue_posts_columns', 'tmem_venue_post_columns' );

/**
 * Define which columns are sortable for venue posts
 *
 * @since 0.7
 * @param arr $sortable_columns Array of transaction post sortable columns
 * @return arr $sortable_columns Filtered Array of transaction post sortable columns
 */
function tmem_venue_post_sortable_columns( $sortable_columns ) {

	$sortable_columns['town']   = 'town';
	$sortable_columns['county'] = 'county';

	return $sortable_columns;

} // tmem_venue_post_sortable_columns
add_filter( 'manage_edit-tmem-venue_sortable_columns', 'tmem_venue_post_sortable_columns' );

/**
 * Order posts.
 *
 * @since 1.3
 * @param obj $query The WP_Query object
 * @return void
 */
function tmem_venue_post_order( $query ) {

	if ( ! is_admin() || 'tmem-venue' != $query->get( 'post_type' ) ) {
		return;
	}

	switch ( $query->get( 'orderby' ) ) {

		case 'town':
			$query->set( 'meta_key', '_venue_town' );
			$query->set( 'orderby', 'meta_value' );
			break;

		case 'county':
			$query->set( 'meta_key', '_venue_county' );
			$query->set( 'orderby', 'meta_value' );
			break;

	}

} // tmem_venue_post_order
add_action( 'pre_get_posts', 'tmem_venue_post_order' );

/**
 * Define the data to be displayed in each of the custom columns for the Venue post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display
 * @param int $post_id The current post ID
 * @return
 */
function tmem_venue_posts_custom_column( $column_name, $post_id ) {

	switch ( $column_name ) {
		case 'contact':
			echo sprintf(
				'<a href="mailto:%s">%s</a>',
				get_post_meta( $post_id, '_venue_email', true ),
				esc_attr(
					stripslashes( get_post_meta( $post_id, '_venue_contact', true ) )
				)
			);
			break;

		// Phone
		case 'phone':
			echo get_post_meta( $post_id, '_venue_phone', true );
			break;

		// Town
		case 'town':
			echo get_post_meta( $post_id, '_venue_town', true );
			break;

		// County
		case 'county':
			echo get_post_meta( $post_id, '_venue_county', true );
			break;

		// Event Count
		case 'event_count':
			$events_at_venue = get_posts(
				array(
					'post_type'   => 'tmem-event',
					'meta_query'  => array(
						'key'   => '_tmem_event_venue_id',
						'value' => $post_id,
						'type'  => 'NUMERIC',
					),
					'post_status' => array( 'tmem-approved', 'tmem-contract', 'tmem-completed', 'tmem-enquiry', 'tmem-unattended' ),
				)
			);

			$count = ! empty( $events_at_venue ) ? count( $events_at_venue ) : '0';

			if ( $count > 0 ) {
				$url = add_query_arg(
					array(
						'post_type'         => 'tmem-event',
						'post_status'       => 'all',
						'action'            => -1,
						'tmem_filter_date'  => 0,
						'tmem_filter_venue' => $post_id,
						'filter_action'     => 'Filter',
					),
					admin_url( 'edit.php' )
				);

				$count = sprintf( '<a href="%s">%s</a>', $url, $count );
			}

			echo $count;
			break;

		// Information
		case 'info':
			echo esc_attr( stripslashes( get_post_meta( $post_id, '_venue_information', true ) ) );
			break;

		// Details
		case 'details':
			$venue_terms = get_the_terms( $post_id, 'venue-details' );
			$venue_term  = '';

			if ( ! empty( $venue_terms ) ) {

				$venue_term .= '<ul class="details">' . "\r\n";

				foreach ( $venue_terms as $v_term ) {
					$venue_term .= '<li>' . $v_term->name . '</li>' . "\r\n";
				}

				$venue_term .= '</ul>' . "\r\n";
			}

			echo ! empty( $venue_term ) ? $venue_term : '';
			break;
	} // switch

} // tmem_venue_posts_custom_column
add_action( 'manage_tmem-venue_posts_custom_column', 'tmem_venue_posts_custom_column', 10, 2 );

/**
 * Customise the post row actions on the venue edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function tmem_venue_post_row_actions( $actions, $post ) {

	if ( 'tmem-venue' !== $post->post_type ) {
		return $actions;
	}

	if ( isset( $actions['view'] ) ) {
		unset( $actions['view'] );
	}

	if ( isset( $actions['inline hide-if-no-js'] ) ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	return $actions;

} // tmem_venue_post_row_actions
add_filter( 'post_row_actions', 'tmem_venue_post_row_actions', 10, 2 );

/**
 * Remove the edit bulk action from the venue posts list
 *
 * @since 1.3
 * @param arr $actions Array of actions
 * @return arr $actions Filtered Array of actions
 */
function tmem_venue_bulk_action_list( $actions ) {

	unset( $actions['edit'] );

	return $actions;

} // tmem_venue_bulk_action_list
add_filter( 'bulk_actions-edit-tmem-venue', 'tmem_venue_bulk_action_list' );

/**
 * Remove the dropdown filters from the edit post screen.
 *
 * @since 1.3
 * @param
 * @param
 */
function tmem_venue_remove_filters() {

	if ( ! isset( $_GET['post_type'] ) || 'tmem-venue' !== $_GET['post_type'] ) {
		return;
	}

	?>
	<style type="text/css">
		#posts-filter .tablenav select[name=m],
		#posts-filter .tablenav select[name=cat],
		#posts-filter .tablenav #post-query-submit{
			display:none;
		}
	</style>
	<?php

} // tmem_venue_remove_filters
add_action( 'admin_head', 'tmem_venue_remove_filters' );

/**
 * Set the post title placeholder for venues
 *
 * @since 1.3
 * @param str $title The post title
 * @return str $title The filtered post title
 */
function tmem_venue_title_placeholder( $title ) {
	global $post;

	if ( ! isset( $post ) || 'tmem-venue' != $post->post_type ) {
		return $title;
	}

	return __( 'Enter Venue name here...', 'mobile-events-manager' );

} // tmem_venue_title_placeholder
add_filter( 'enter_title_here', 'tmem_venue_title_placeholder' );

/**
 * Rename the Publish and Update post buttons for venues
 *
 * @since 1.3
 * @param str $translation The current button text translation
 * @param str $text The text translation for the button
 * @return str $translation The filtererd text translation
 */
function tmem_venue_rename_publish_button( $translation, $text ) {

	global $post;

	if ( ! isset( $post ) || 'tmem-venue' != $post->post_type ) {
		return $translation;
	}

	if ( 'Publish' === $text ) {
		return __( 'Save Venue', 'mobile-events-manager' );
	} elseif ( 'Update' === $text ) {
		return __( 'Update Venue', 'mobile-events-manager' );
	} else {
		return $translation;
	}

} // tmem_venue_rename_publish_button
add_filter( 'gettext', 'tmem_venue_rename_publish_button', 10, 2 );

/**
 * Save the meta data for the venue
 *
 * @since 1.3
 * @param int  $post_id The current post ID.
 * @param obj  $post The current post object (WP_Post).
 * @param bool $update Whether this is an existing post being updated or not.
 * @return void
 */
function tmem_save_venue_post( $post_id, $post, $update ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'trash' === $post->post_status ) {
		return;
	}

	if ( empty( $update ) ) {
		return;
	}

	// Permission Check
	if ( ! tmem_employee_can( 'add_venues' ) ) {

		if ( TMEM_DEBUG == true ) {
			TMEM()->debug->log_it( 'PERMISSION ERROR: User ' . get_current_user_id() . ' is not allowed to edit venues' );
		}

		return;
	}

	// Remove the save post action to avoid loops
	remove_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );

	// Fire our pre-save hook
	do_action( 'tmem_before_venue_save', $post_id, $post, $update );

	// Loop through all fields sanitizing and updating as required
	foreach ( $_POST as $meta_key => $new_meta_value ) {

		// We're only interested in 'venue_' fields
		if ( substr( $meta_key, 0, 6 ) == 'venue_' ) {

			$current_meta_value = get_post_meta( $post_id, '_' . $meta_key, true );

			if ( 'venue_postcode' === $meta_key && ! empty( $new_meta_value ) ) {
				$new_meta_value = strtoupper( $new_meta_value );
			} elseif ( 'venue_email' === $meta_key && ! empty( $new_meta_value ) ) {
				$new_meta_value = sanitize_email( $new_meta_value );
			} else {
				$new_meta_value = sanitize_text_field( ucwords( $new_meta_value ) );
			}

			// If we have a value and the key did not exist previously, add it
			if ( ! empty( $new_meta_value ) && empty( $current_meta_value ) ) {
				add_post_meta( $post_id, '_' . $meta_key, $new_meta_value, true );
			}

			/* -- If a value existed, but has changed, update it -- */
			elseif ( ! empty( $new_meta_value ) && $new_meta_value != $current_meta_value ) {
				update_post_meta( $post_id, '_' . $meta_key, $new_meta_value );
			}

			/* If there is no new meta value but an old value exists, delete it. */
			elseif ( empty( $new_meta_value ) && ! empty( $current_meta_value ) ) {
				delete_post_meta( $post_id, '_' . $meta_key, $meta_value );
			}
		}
	}

	// Fire our post save hook
	do_action( 'tmem_after_venue_save', $post_id, $post, $update );

	// Re-add the save post action to avoid loops
	add_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );

}
add_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );

/**
 * Customise the messages associated with managing venue posts
 *
 * @since 1.3
 * @param arr $messages The current messages
 * @return arr $messages Filtered messages
 */
function tmem_venue_post_messages( $messages ) {

	global $post;

	if ( 'tmem-venue' != $post->post_type ) {
		return $messages;
	}

	$url1 = '<a href="' . admin_url( 'edit.php?post_type=tmem-venue' ) . '">';
	$url2 = get_post_type_object( $post->post_type )->labels->singular_name;
	$url3 = '</a>';

	$messages['tmem-venue'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		4 => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		6 => sprintf( esc_html__( '%2$s added. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		7 => sprintf( esc_html__( '%2$s saved. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
	);

	return apply_filters( 'tmem_venue_post_messages', $messages );

} // tmem_venue_post_messages
add_filter( 'post_updated_messages', 'tmem_venue_post_messages' );
