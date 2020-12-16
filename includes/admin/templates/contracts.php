<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Manage the contract template posts
 */

/**
 * Define the columns to be displayed for contract template posts
 *
 * @since 1.0
 * @param arr $columns Array of column names
 * @return arr $columns Filtered array of column names
 */
function mem_contract_post_columns( $columns ) {

	$columns = array(
		'cb'            => '<input type="checkbox" />',
		'title'         => __( 'Contract Name', 'mobile-events-manager' ),
		'event_default' => __( 'Is Default?', 'mobile-events-manager' ),
		'assigned'      => __( 'Assigned To', 'mobile-events-manager' ),
		'author'        => __( 'Created By', 'mobile-events-manager' ),
		'date'          => __( 'Date', 'mobile-events-manager' ),
	);

	if ( ! mem_employee_can( 'manage_templates' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // mem_contract_post_columns
add_filter( 'manage_contract_posts_columns', 'mem_contract_post_columns' );

/**
 * Define the data to be displayed in each of the custom columns for the Contract post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display
 * @param int $post_id The current post ID
 * @return
 */
function mem_contract_posts_custom_column( $column_name, $post_id ) {

	switch ( $column_name ) {
		// Is Default?
		case 'event_default':
			$event_default = mem_get_option( 'default_contract' );

			if ( $event_default == $post_id ) {
				echo '<span style="color: green; font-weight: bold;">' . __( 'Yes', 'mobile-events-manager' );
			} else {
				esc_html_e( 'No', 'mobile-events-manager' );
			}

			break;

		// Assigned To
		case 'assigned':
			$contract_events = mem_get_events(
				array(
					'meta_query' => array(
						array(
							'key'   => '_mem_event_contract',
							'value' => $post_id,
							'type'  => 'NUMERIC',
						),
					),
				)
			);

			$total = count( $contract_events );
			echo $total . sprintf( _n( ' %1$s', ' %2$s', $total, 'mobile-events-manager' ), esc_html( mem_get_label_singular() ), esc_html( mem_get_label_plural() ) );

			break;
	} // switch

} // mem_contract_posts_custom_column
add_action( 'manage_contract_posts_custom_column', 'mem_contract_posts_custom_column', 10, 2 );

/**
 * Customise the post row actions on the contract edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function mem_contract_post_row_actions( $actions, $post ) {

	if ( 'contract' !== $post->post_type ) {
		return $actions;
	}

	if ( isset( $actions['inline hide-if-no-js'] ) ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	return $actions = array();

} // mem_contract_post_row_actions
add_filter( 'post_row_actions', 'mem_contract_post_row_actions', 10, 2 );

/**
 * Set the post title placeholder for contracts
 *
 * @since 1.3
 * @param str $title The post title
 * @return str $title The filtered post title
 */
function mem_contract_title_placeholder( $title ) {
	global $post;

	if ( ! isset( $post ) || 'contract' != $post->post_type ) {
		return $title;
	}

	return __( 'Enter Contract name here...', 'mobile-events-manager' );

} // mem_contract_title_placeholder
add_filter( 'enter_title_here', 'mem_contract_title_placeholder' );

/**
 * Rename the Publish and Update post buttons for contracts
 *
 * @since 1.3
 * @param str $translation The current button text translation
 * @param str $text The text translation for the button
 * @return str $translation The filtererd text translation
 */
function mem_contract_rename_publish_button( $translation, $text ) {

	global $post;

	if ( ! isset( $post ) || 'contract' != $post->post_type ) {
		return $translation;
	}

	if ( 'Publish' === $text ) {
		return __( 'Save Contract', 'mobile-events-manager' );
	} elseif ( 'Update' === $text ) {
		return __( 'Update Contract', 'mobile-events-manager' );
	} else {
		return $translation;
	}

} // mem_contract_rename_publish_button
add_filter( 'gettext', 'mem_contract_rename_publish_button', 10, 2 );

/**
 * Save the meta data for the contract
 *
 * @since 1.3
 * @param int  $post_id The current post ID.
 * @param obj  $post The current post object (WP_Post).
 * @param bool $update Whether this is an existing post being updated or not.
 * @return void
 */
function mem_save_contract_post( $post_id, $post, $update ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( empty( $update ) ) {
		return;
	}

	// Permission Check
	if ( ! mem_employee_can( 'manage_templates' ) ) {

		if ( MEM_DEBUG == true ) {
			MEM()->debug->log_it( 'PERMISSION ERROR: User ' . get_current_user_id() . ' is not allowed to edit contracts' );
		}

		return;
	}

	// Remove the save post action to avoid loops
	remove_action( 'save_post_contract', 'mem_save_contract_post', 10, 3 );

	// Fire our pre-save hook
	do_action( 'mem_pre_contract_save', $post_id, $post, $update );

	// Current value of the contract description for comaprison.
	$current_desc = get_post_meta( $ID, '_contract_description', true );

	// If we have a value and the key did not exist previously, add it.
	if ( ! empty( $_POST['contract_description'] ) && empty( $current_desc ) ) {
		add_post_meta( $ID, '_contract_description', sanitize_key( wp_unslash( $_POST['contract_description'], true ) ) );
	}

	// If a value existed, but has changed, update it
	elseif ( ! empty( $_POST['contract_description'] ) && $current_desc != $_POST['contract_description'] ) {
		update_post_meta( $ID, '_contract_description', sanitize_key( wp_unslash( $_POST['contract_description'] ) ) );
	}

	// If there is no new meta value but an old value exists, delete it.
	elseif ( empty( $_POST['contract_description'] ) && ! empty( $current_desc ) ) {
		delete_post_meta( $ID, '_contract_description' );
	}

	// Fire our post save hook
	do_action( 'mem_post_contract_save', $post_id, $post, $update );

	// Re-add the save post action to avoid loops
	add_action( 'save_post_contract', 'mem_save_contract_post', 10, 3 );

}
add_action( 'save_post_contract', 'mem_save_contract_post', 10, 3 );

/**
 * Customise the messages associated with managing contract posts
 *
 * @since 1.3
 * @param arr $messages The current messages
 * @return arr $messages Filtered messages
 */
function mem_contract_post_messages( $messages ) {

	global $post;

	if ( 'contract' != $post->post_type ) {
		return $messages;
	}

	$url1 = '<a href="' . admin_url( 'edit.php?post_type=contract' ) . '">';
	$url2 = get_post_type_object( $post->post_type )->labels->singular_name;
	$url3 = '</a>';

	$messages['contract'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		4  => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( '%1$s restored to revision from %2$s.', 'mobile-events-manager' ), $url2, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => sprintf( esc_html__( '%2$s published. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		7  => sprintf( esc_html__( '%2$s saved. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		10 => sprintf( esc_html__( '%2$s draft updated. %1$s%2$s List%3$s..', 'mobile-events-manager' ), $url1, $url2, $url3 ),
	);

	return apply_filters( 'mem_contract_post_messages', $messages );

} // mem_contract_post_messages
add_filter( 'post_updated_messages', 'mem_contract_post_messages' );
