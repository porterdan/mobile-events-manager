<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Manage the quote posts
 */

/**
 * Define the columns to be displayed for quote posts
 *
 * @since 0.5
 * @param arr $columns Array of column names
 * @return arr $columns Filtered array of column names
 */
function tmem_quote_post_columns( $columns ) {

	$columns = array(
		'cb'               => '<input type="checkbox" />',
		'date'             => __( 'Generated', 'mobile-events-manager' ),
		'quote_event'      => __( 'Event ID', 'mobile-events-manager' ),
		'quote_client'     => __( 'Client', 'mobile-events-manager' ),
		'quote_value'      => __( 'Quote Value', 'mobile-events-manager' ),
		'quote_view_date'  => __( 'Date Viewed', 'mobile-events-manager' ),
		'quote_view_count' => __( 'View Count', 'mobile-events-manager' ),
	);

	if ( ! tmem_employee_can( 'list_own_quotes' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	if ( ! tmem_employee_can( 'edit_txns' ) ) {
		unset( $columns['quote_value'] );
	}

	return $columns;
} // tmem_quote_post_columns
add_filter( 'manage_tmem-quotes_posts_columns', 'tmem_quote_post_columns' );

/**
 * Define which columns are sortable for quote posts
 *
 * @since 0.7
 * @param arr $sortable_columns Array of transaction post sortable columns
 * @return arr $sortable_columns Filtered Array of transaction post sortable columns
 */
function tmem_quote_post_sortable_columns( $sortable_columns ) {

	$sortable_columns['quote_view_date'] = 'quote_view_date';
	// TO DO (Order by post parent total cost meta key value $sortable_columns['quote_value']	 = 'quote_value';

	return $sortable_columns;

} // tmem_quote_post_sortable_columns
add_filter( 'manage_edit-tmem-quotes_sortable_columns', 'tmem_quote_post_sortable_columns' );

/**
 * Order posts.
 *
 * @since 1.3
 * @param obj $query The WP_Query object
 * @return void
 */
function tmem_quote_post_order( $query ) {

	if ( ! is_admin() || 'tmem-quotes' != $query->get( 'post_type' ) ) {
		return;
	}

	switch ( $query->get( 'orderby' ) ) {

		case 'quote_view_date':
			$query->set( 'meta_key', '_tmem_quote_viewed_date' );
			$query->set( 'orderby', 'meta_value' );
			break;

		case 'quote_value':
			// TO DO
			break;

	}

} // tmem_quote_post_order
add_action( 'pre_get_posts', 'tmem_quote_post_order' );

/**
 * Hook into pre_get_posts and limit employees quotes to their own events
 * if their permissions are not full.
 *
 * @since 1.0
 * @param arr $query The WP_Query
 * @return void
 */
function tmem_limit_results_to_employee_quotes( $query ) {

	if ( ! is_admin() || 'tmem-quotes' != $query->get( 'post_type' ) || tmem_employee_can( 'list_all_quotes' ) ) {
		return;
	}

	global $user_ID;

	$events = tmem_get_employee_events( $user_ID );

	foreach ( $events as $event ) {
		$quote = tmem_get_event_quote_id( $event->ID );

		if ( ! empty( $quote ) ) {
			$quotes[] = $quote;
		}
	}

	if ( ! empty( $quotes ) ) {
		$query->set( 'post__in', $quotes );
	}

} // tmem_limit_results_to_employee_quotes
add_action( 'pre_get_posts', 'tmem_limit_results_to_employee_quotes' );

/**
 * Define the data to be displayed in each of the custom columns for the Quote post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display
 * @param int $post_id The current post ID
 * @return
 */
function tmem_quote_posts_custom_column( $column_name, $post_id ) {

	if ( 'wuote-event' ===  $column_name || 'wuote_value' === $column_name ) {
		$parent = wp_get_post_parent_id( $post_id );
	}

	switch ( $column_name ) {
		// Quote Date
		case 'date':
			echo get_the_date( 'd M Y H:i:s' );
			break;

		// Event
		case 'quote_event':
			if ( ! empty( $parent ) ) {

				printf(
					'<a href="%s">%s</a><br /><em>%s</em>',
					admin_url( '/post.php?post={$parent}&action=edit' ),
					tmem_get_event_contract_id( $parent ),
					tmem_get_event_date( $parent )
				);

			} else {
				esc_html_e( 'N/A', 'mobile-events-manager' );
			}

			break;

		// Client
		case 'quote_client':
			global $post;

			echo '<a href="' . admin_url( 'admin.php?page=tmem-clients&action=view_client&client_id=' . $post->post_author ) . '">' . get_the_author() . '</a>';
			break;

		// Cost
		case 'quote_value':
			echo tmem_currency_filter( tmem_get_event_price( $parent ) );
			break;

		// Date Viewed
		case 'quote_view_date':
			if ( 'tmem-quote-viewed' == get_post_status( $post_id ) ) {

				echo gmdate( 'd M Y H:i:s', strtotime( get_post_meta( $post_id, '_tmem_quote_viewed_date', true ) ) );

			} else {
				esc_html_e( 'N/A', 'mobile-events-manager' );
			}

			break;

		// View Count
		case 'quote_view_count':
			$count = get_post_meta( $post_id, '_tmem_quote_viewed_count', true );

			if ( empty( $count ) ) {
				$count = 0;
			}

			echo $count . _n( ' time', ' times', $count, 'mobile-events-manager' );

			break;
	} // switch

} // tmem_quote_posts_custom_column
add_action( 'manage_tmem-quotes_posts_custom_column', 'tmem_quote_posts_custom_column', 10, 2 );

/**
 * Customise the post row actions on the quote edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function tmem_quote_post_row_actions( $actions, $post ) {

	if ( 'tmem-quotes' !== $post->post_type ) {
		return $actions;
	}

	if ( isset( $actions['inline hide-if-no-js'] ) ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	if ( isset( $actions['edit'] ) ) {
		unset( $actions['edit'] );
	}

	return $actions;

} // tmem_quote_post_row_actions
add_filter( 'post_row_actions', 'tmem_quote_post_row_actions', 10, 2 );

/**
 * Remove the edit bulk action from the quote posts list
 *
 * @since 1.3
 * @param arr $actions Array of actions
 * @return arr $actions Filtered Array of actions
 */
function tmem_quote_bulk_action_list( $actions ) {

	unset( $actions['edit'] );

	return $actions;

} // tmem_quote_bulk_action_list
add_filter( 'bulk_actions-edit-tmem-quotes', 'tmem_quote_bulk_action_list' );

/**
 * Customise the view filter counts
 *
 * @since 1.0
 * @param arr $views Array of views
 * @return arr $views Filtered Array of views
 */
function tmem_quote_view_filters( $views ) {

	// We only run this filter if the user has restrictive caps and the post type is tmem-event
	if ( ! is_post_type_archive( 'tmem-quotes' ) || tmem_employee_can( 'list_all_quotes' ) ) {
		return $views;
	}

	global $user_ID;

	$events = tmem_get_employee_events( $user_ID );
	$all    = 0;

	if ( $events ) {

		foreach ( $events as $event ) {
			$quote        = tmem_get_event_quote_id( $event->ID );
			$quote_status = get_post_status( $quote );

			if ( ! isset( $status[ $quote_status ] ) ) {
				$status[ $quote_status ] = 1;
			} else {
				$status[ $quote_status ]++;
			}

			$all++;
		}
	}

	// The All filter
	$views['all'] = preg_replace( '/\(.+\)/U', '(' . tmem_count_employee_events() . ')', $views['all'] );

	$event_statuses = tmem_all_event_status();

	foreach ( $event_statuses as $status => $label ) {
		$events = tmem_get_employee_events( '', array( 'post_status' => $status ) );

		if ( empty( $events ) ) {

			if ( isset( $views[ $status ] ) ) {
				unset( $views[ $status ] );
			}

			continue;
		}

		$views[ $status ] = preg_replace( '/\(.+\)/U', '(' . count( $events ) . ')', $views[ $status ] );
	}

	// Only show the views we want
	foreach ( $views as $status => $link ) {

		if ( 'all' !== $status && ! array_key_exists( $status, $event_stati ) ) {
			unset( $views[ $status ] );
		}
	}

	return $views;
} // tmem_event_view_filters
add_filter( 'views_edit-tmem-quotes', 'tmem_quote_view_filters' );

/**
 * Remove the add new button edit post screen.
 *
 * @since 1.3
 * @param
 * @param
 */
function tmem_quotes_remove_add_new() {

	if ( ! isset( $_GET['post_type'] ) || 'tmem-quotes' !== $_GET['post_type'] ) {
		return;
	}

	?>
	<style type="text/css">
		.page-title-action	{
			display: none;
		}
	</style>
	<?php

} // tmem_quotes_remove_add_new
add_action( 'admin_head', 'tmem_quotes_remove_add_new' );

/**
 * Customise the messages associated with managing quote posts
 *
 * @since 1.3
 * @param arr $messages The current messages
 * @return arr $messages Filtered messages
 */
function tmem_quote_post_messages( $messages ) {

	global $post;

	if ( 'tmem-quotes' != $post->post_type ) {
		return $messages;
	}

	$messages['tmem-quotes'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( esc_html__( '%s updated.', 'mobile-events-manager' ), get_post_type_object( $post->post_type )->labels->singular_name ),
		4 => sprintf( esc_html__( '%s updated.', 'mobile-events-manager' ), get_post_type_object( $post->post_type )->labels->singular_name ),
		6 => sprintf( esc_html__( '%s generated.', 'mobile-events-manager' ), get_post_type_object( $post->post_type )->labels->singular_name ),
		7 => sprintf( esc_html__( '%s saved.', 'mobile-events-manager' ), get_post_type_object( $post->post_type )->labels->singular_name ),
	);

	return apply_filters( 'tmem_quote_post_messages', $messages );

} // tmem_quote_post_messages
add_filter( 'post_updated_messages', 'tmem_quote_post_messages' );
