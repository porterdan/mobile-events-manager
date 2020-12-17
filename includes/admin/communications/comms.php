<?php
/**
 * Manage the communication history posts
 *
 * @package TMEM
 */

	defined( 'ABSPATH' ) || die( 'Direct access to this page is disabled!!!' );

/**
 * Define the columns to be displayed for communication posts
 *
 * @since 0.5
 * @param arr $columns Array of column names.
 * @return arr $columns Filtered array of column names
 */
function tmem_communication_post_columns( $columns ) {

	$columns = array(
		'cb'             => '<input type="checkbox" />',
		'date_sent'      => __( 'Date Sent', 'mobile-events-manager' ),
		'title'          => __( 'Email Subject', 'mobile-events-manager' ),
		'from'           => __( 'From', 'mobile-events-manager' ),
		'recipient'      => __( 'Recipient', 'mobile-events-manager' ),
		/* Translators: %s: URL */
		'event'          => sprintf( esc_html__( '%s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		'current_status' => __( 'Status', 'mobile-events-manager' ),
		'source'         => __( 'Source', 'mobile-events-manager' ),
	);

	if ( ! tmem_is_admin() && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // tmem_communication_post_columns
add_filter( 'manage_tmem_communication_posts_columns', 'tmem_communication_post_columns' );

/**
 * Define the data to be displayed in each of the custom columns for the Communication post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display.
 * @param int $post_id The current post ID.
 */
function tmem_communication_posts_custom_column( $column_name, $post_id ) {

	global $post;

	switch ( $column_name ) {
		// Date Sent.
		case 'date_sent':
			echo esc_html( gmdate( tmem_get_option( 'time_format', 'H:i' ) ) ) . ' ' . esc_html( tmem_get_option( 'short_date_format', 'd/m/Y' ) ), esc_attr( get_post_meta( $post_id, '_date_sent', true ) );

			break;

		// From.
		case 'from':
			$author = get_userdata( $post->post_author );

			if ( $author ) {
				printf( wp_kses_post( '<a href="%s">%s</a>', admin_url( "user-edit.php?user_id={$author->ID}" ), ucwords( $author->display_name ) ) );
			} else {
				echo esc_attr( get_post_meta( $post_id, '_recipient', true ) );
			}

			break;

		// Recipient.
		case 'recipient':
			$client = get_userdata( get_post_meta( $post_id, '_recipient', true ) );

			if ( $client ) {
				printf( wp_kses_post( '<a href="%s">%s</a>', admin_url( "user-edit.php?user_id={$client->ID}" ), ucwords( $client->display_name ) ) );
			} else {
				echo esc_html( __( 'Recipient no longer exists', 'mobile-events-manager' ) );
			}

			$copies = get_post_meta( $post_id, '_tmem_copy_to', true );

			if ( ! empty( $copies ) ) {
				if ( ! is_array( $copies ) ) {
					$copies = array( $copies );
				}
				foreach ( $copies as $copy ) {
					$user = get_user_by( 'email', $copy );
					if ( $user ) {
						echo wp_kses_post( "<br /><em>{$user->display_name} (copy)</em>" );
					}
				}
			}

			break;

		// Associated Event.
		case 'event':
			$event_id = get_post_meta( $post_id, '_event', true );

			if ( ! empty( $event_id ) ) {
				echo wp_kses_post( '<a href="' . get_edit_post_link( $event_id ) . '">' . tmem_get_event_contract_id( $event_id ) . '</a>' );
			} else {
				esc_html_e( 'N/A', 'mobile-events-manager' );
			}

			break;

		// Status.
		case 'current_status':
			echo esc_attr( get_post_status_object( $post->post_status )->label );

			if ( ! empty( $post->post_modified ) && 'opened' === $post->post_status ) {
				echo '<br />';
				echo wp_kses_post( '<em>' . gmdate( tmem_get_option( 'time_format', 'H:i' ) . ' ' . tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $post->post_modified ) ) . '</em>' );
			}

			break;

		// Source.
		case 'source':
			echo esc_attr( stripslashes( get_post_meta( $post_id, '_source', true ) ) );

			break;
	} // switch

} // tmem_communication_posts_custom_column
add_action( 'manage_tmem_communication_posts_custom_column', 'tmem_communication_posts_custom_column', 10, 2 );

/**
 * Remove the edit bulk action from the communication posts list
 *
 * @since 1.3
 * @param arr $actions Array of actions.
 * @return arr $actions Filtered Array of actions
 */
function tmem_communication_bulk_action_list( $actions ) {

	unset( $actions['edit'] );

	return $actions;

} // tmem_communication_bulk_action_list
add_filter( 'bulk_actions-edit-tmem_communication', 'tmem_communication_bulk_action_list' );

/**
 * Customise the post row actions on the communication edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions.
 * @param obj $post The WP_Post post object.
 */
function tmem_communication_post_row_actions( $actions, $post ) {

	if ( 'tmem_communication' !== $post->post_type ) {
		return $actions;
	}

	return $actions = array();

} // tmem_communication_post_row_actions.
add_filter( 'post_row_actions', 'tmem_communication_post_row_actions', 10, 2 );

/**
 * Remove the dropdown filters from the edit post screen.
 *
 * @since 1.3
 */
function tmem_communication_remove_add_new() {

	if ( ! isset( $_GET['post_type'] ) || 'tmem_communication' !== $_GET['post_type'] ) {
		return;
	}

	?>
	<style type="text/css">
		.page-title-action	{
			display: none;
		}
	</style>
	<?php

} // tmem_communication_remove_add_new
add_action( 'admin_head', 'tmem_communication_remove_add_new' );
