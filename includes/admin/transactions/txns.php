<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Manage the transaction posts
 */

/**
 * Define the columns to be displayed for transaction posts
 *
 * @since 0.5
 * @param arr $columns Array of column names
 * @return arr $columns Filtered array of column names
 */
function tmem_transaction_post_columns( $columns ) {

	$columns = array(
		'cb'         => '<input type="checkbox" />',
		'title'      => __( 'ID', 'mobile-events-manager' ),
		'txn_date'   => __( 'Date', 'mobile-events-manager' ),
		'direction'  => __( 'In/Out', 'mobile-events-manager' ),
		'payee'      => __( 'To/From', 'mobile-events-manager' ),
		'txn_status' => __( 'Status', 'mobile-events-manager' ),
		'detail'     => __( 'Details', 'mobile-events-manager' ),
		'event'      => sprintf( esc_html__( '%s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		'txn_value'  => __( 'Value', 'mobile-events-manager' ),
	);

	if ( ! tmem_employee_can( 'edit_txns' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // tmem_transaction_post_columns
add_filter( 'manage_tmem-transaction_posts_columns', 'tmem_transaction_post_columns' );

/**
 * Define which columns are sortable for transaction posts
 *
 * @since 0.7
 * @param arr $sortable_columns Array of transaction post sortable columns
 * @return arr $sortable_columns Filtered Array of transaction post sortable columns
 */
function tmem_transaction_post_sortable_columns( $sortable_columns ) {

	$sortable_columns['txn_date']   = 'txn_date';
	$sortable_columns['txn_status'] = 'txn_status';
	$sortable_columns['event']      = 'event';
	$sortable_columns['txn_value']  = 'txn_value';

	return $sortable_columns;

} // tmem_transaction_post_sortable_columns
add_filter( 'manage_edit-tmem-transaction_sortable_columns', 'tmem_transaction_post_sortable_columns' );

/**
 * Order posts.
 *
 * @since 1.3
 * @param obj $query The WP_Query object
 * @return void
 */
function tmem_transaction_post_order( $query ) {

	if ( ! is_admin() || 'tmem-transaction' != $query->get( 'post_type' ) ) {
		return;
	}

	switch ( $query->get( 'orderby' ) ) {

		case 'txn_date':
		default:
			$query->set( 'orderby', 'post_date' );
			break;

		case 'txn_status':
			$query->set( 'orderby', '_tmem_txn_status' );
			break;

		case 'event':
			$query->set( 'orderby', 'post_parent' );
			break;

		case 'txn_value':
			$query->set( 'meta_key', '_tmem_txn_total' );
			$query->set( 'orderby', 'meta_value_num' );
			break;

	}

} // tmem_transaction_post_order
add_action( 'pre_get_posts', 'tmem_transaction_post_order' );

/**
 * Adjust the query when the transactions are filtered.
 *
 * @since 1.3
 * @param arr $query The WP_Query
 * @return void
 */
function tmem_transaction_post_filtered( $query ) {

	global $pagenow;

	$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

	if ( 'edit.php' != $pagenow || 'tmem-transaction' != $post_type || ! is_admin() ) {
		return;
	}

	// Filter by transaction type
	if ( ! empty( $_GET['tmem_filter_type'] ) ) {

		$type = isset( $_GET['tmem_filter_type'] ) ? sanitize_key( wp_unslash( $_GET['tmem_filter_type'] ) ) : 0;

		if ( 0 !== $type ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'transaction-types',
						'field'    => 'term_id',
						'terms'    => $type,
					),
				)
			);
		}
	}

} // tmem_transaction_post_filtered
add_filter( 'parse_query', 'tmem_transaction_post_filtered' );

/**
 * Define the data to be displayed in each of the custom columns for the Transaction post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display
 * @param int $post_id The current post ID
 * @return
 */
function tmem_transaction_posts_custom_column( $column_name, $post_id ) {

	switch ( $column_name ) {
		// Details
		case 'detail':
			$trans_types = get_the_terms( $post_id, 'transaction-types' );

			if ( is_array( $trans_types ) ) {
				foreach ( $trans_types as $key => $trans_type ) {
					$trans_types[ $key ] = $trans_type->name;
				}
				echo implode( '<br/>', $trans_types );
			}
			break;

		// Date
		case 'txn_date':
			echo get_post_time( 'd M Y' );
			break;

		// Direction
		case 'direction':
			if ( 'tmem-income' == get_post_status( $post_id ) ) {

				echo '<span style="color:green">' . __( 'In', 'mobile-events-manager' ) . '</span>';

			} else {

				echo '<span style="color:red">&nbsp;&nbsp;&nbsp;&nbsp;' . __( 'Out', 'mobile-events-manager' ) . '</span>';

			}
			break;

		// Source
		case 'payee':
			echo tmem_get_txn_recipient_name( $post_id );

			break;

		// Event
		case 'event':
			$parent = wp_get_post_parent_id( $post_id );

			if ( ! empty( $parent ) ) {

				printf(
					'<a href="%s">%s</a>',
					admin_url( "/post.php?post={$parent}&action=edit" ),
					tmem_get_option( '' ) . $parent
				);

			} else {

				echo __( 'N/A', 'mobile-events-manager' );

			}
			break;

		// Value
		case 'txn_value':
			echo tmem_currency_filter( tmem_format_amount( get_post_meta( $post_id, '_tmem_txn_total', true ) ) );
			break;

		// Status
		case 'txn_status':
			echo get_post_meta( $post_id, '_tmem_txn_status', true );
			break;
	} // switch

} // tmem_transaction_posts_custom_column
add_action( 'manage_tmem-transaction_posts_custom_column', 'tmem_transaction_posts_custom_column', 10, 2 );

/**
 * Customise the post row actions on the transaction edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function tmem_transaction_post_row_actions( $actions, $post ) {

	if ( 'tmem-transaction' !== $post->post_type ) {
		return $actions;
	}

	if ( isset( $actions['inline hide-if-no-js'] ) ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	return $actions;

} // tmem_transaction_post_row_actions
add_filter( 'post_row_actions', 'tmem_transaction_post_row_actions', 10, 2 );

/**
 * Set the transaction post title and set as readonly.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function tmem_transaction_set_post_title( $post ) {

	if ( 'tmem-transaction' != $post->post_type ) {
		return;
	}

	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("#title").val("<?php echo tmem_get_event_contract_id( $post->ID ); ?>");
			$("#title").prop("readonly", true);
		});
	</script>
	<?php
} // tmem_transaction_set_post_title
add_action( 'edit_form_after_title', 'tmem_transaction_set_post_title' );

/**
 * Rename the Publish and Update post buttons for transaction
 *
 * @since 1.3
 * @param str $translation The current button text translation
 * @param str $text The text translation for the button
 * @return str $translation The filtererd text translation
 */
function tmem_transaction_rename_publish_button( $translation, $text ) {

	global $post;

	if ( ! isset( $post ) || 'tmem-transaction' != $post->post_type ) {
		return $translation;
	}

	$event_statuses = tmem_all_event_status();

	if ( 'Publish' === $text ) {
		return __( 'Save Transaction', 'mobile-events-manager' );
	} elseif ( 'Update' === $text ) {
		return __( 'Update Transaction', 'mobile-events-manager' );
	} else {
		return $translation;
	}

} // tmem_transaction_rename_publish_button
add_filter( 'gettext', 'tmem_transaction_rename_publish_button', 10, 2 );

/**
 * Add the dropdown filters for the transaction post categories.
 *
 * @since 1.0
 * @param
 * @return void
 */
function tmem_transaction_type_filter_dropdown() {

	if ( ! isset( $_GET['post_type'] ) || 'tmem-transaction' !== $_GET['post_type'] ) {
		return;
	}

	$transaction_types = get_categories(
		array(
			'type'       => 'tmem-transaction',
			'taxonomy'   => 'transaction-types',
			'pad_counts' => false,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	foreach ( $transaction_types as $transaction_type ) {
		$values[ $transaction_type->term_id ] = $transaction_type->name;
	}

	?>
	<select name="tmem_filter_type">
	<option value="0"><?php echo __( 'All Transaction Types', 'mobile-events-manager' ); ?></option>

	<?php
		$current_value = isset( $_GET['tmem_filter_type'] ) ? sanitize_key( wp_unslash( $_GET['tmem_filter_type'] ) ) : '';

	if ( ! empty( $values ) ) {

		foreach ( $values as $value => $label ) {

			printf( '<option value="%s"%s>%3$s (%3$s)</option>', $value, $value == $current_value ? ' selected="selected"' : '', $label );

		}
	}
	?>

	</select>
	<?php

} // tmem_transaction_type_filter_dropdown
add_action( 'restrict_manage_posts', 'tmem_transaction_type_filter_dropdown' );

/**
 * Save the meta data for the transaction
 *
 * @since 0.7
 * @param int  $post_id The current post ID.
 * @param obj  $post The current post object (WP_Post).
 * @param bool $update Whether this is an existing post being updated or not.
 * @return void
 */
function tmem_save_txn_post( $post_id, $post, $update ) {
	global $tmem_settings;

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
	if ( ! tmem_employee_can( 'edit_txns' ) ) {

		if ( TMEM_DEBUG == true ) {
			TMEM()->debug->log_it( 'PERMISSION ERROR: User ' . get_current_user_id() . ' is not allowed to edit transactions' );
		}

		return;

	}

	// Remove the save post action to avoid loops
	remove_action( 'save_post_tmem-transaction', 'tmem_save_txn_post', 10, 3 );

	// Fire our pre-save hook
	do_action( 'tmem_before_txn_save', $post_id, $post, $update );

	$trans_type = get_term( sanitize_key( wp_unslash( $_POST['tmem_transaction_type'] ) ), 'transaction-types' );

	// Set the post data
	$trans_data['ID']          = $post_id;
	$trans_data['post_status'] = 'Out' === $_POST['transaction_direction'] ? 'tmem-expenditure' : 'tmem-income';
	$trans_data['post_date']   = gmdate( 'Y-m-d H:i:s', strtotime( sanitize_key( wp_unslash( $_POST['transaction_date'] ) ) ) );
	$trans_data['edit_date']   = true;

	$trans_data['post_author']   = get_current_user_id();
	$trans_data['post_type']     = 'tmem-transaction';
	$trans_data['post_category'] = array( sanitize_key( wp_unslash( $_POST['tmem_transaction_type'] ) ) );

	// Set the post meta
	$trans_meta['_tmem_txn_status'] = sanitize_text_field( wp_unslash( $_POST['transaction_status'] ) );
	$trans_meta['_tmem_txn_source'] = sanitize_text_field( wp_unslash( $_POST['transaction_src'] ) );
	$trans_meta['_tmem_txn_total']  = sanitize_text_field( wp_unslash( $_POST['transaction_amount'] ) );
	$trans_meta['_tmem_txn_notes']  = sanitize_text_field( wp_unslash( $_POST['transaction_description'] ) );

	if ( 'In' === $_POST['transaction_direction'] ) {
		$trans_meta['_tmem_payment_from'] = sanitize_text_field( wp_unslash( $_POST['transaction_payee'] ) );
	} elseif ( 'Out' === $_POST['transaction_direction'] ) {
		$trans_meta['_tmem_payment_to'] = sanitize_text_field( wp_unslash( $_POST['transaction_payee'] ) );
	}

	$trans_meta['_tmem_txn_currency'] = tmem_get_currency();

	// Update the post
	if ( TMEM_DEBUG == true ) {
		 TMEM()->debug->log_it( 'Updating the transaction' );
	}

	wp_update_post( $trans_data );

	// Set the transaction Type
	if ( TMEM_DEBUG == true ) {
		 TMEM()->debug->log_it( 'Setting the transaction type' );
	}

	wp_set_post_terms( sanitize_key( wp_unslash( $post_id, $_POST['tmem_transaction_type'] ) ), 'transaction-types' );

	// Add the meta data
	if ( TMEM_DEBUG == true ) {
		 TMEM()->debug->log_it( 'Updating the transaction post meta' );
	}

	// Loop through the post meta and add/update/delete the meta keys.
	foreach ( $trans_meta as $meta_key => $new_meta_value ) {

		$current_meta_value = get_post_meta( $post_id, $meta_key, true );

		// If we have a value and the key did not exist previously, add it.
		if ( ! empty( $new_meta_value ) && empty( $current_meta_value ) ) {
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		}

		// If a value existed, but has changed, update it.
		elseif ( ! empty( $new_meta_value ) && $new_meta_value != $current_meta_value ) {
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		}

		// If there is no new meta value but an old value exists, delete it.
		elseif ( empty( $new_meta_value ) && ! empty( $current_meta_value ) ) {
			delete_post_meta( $post_id, $meta_key, $new_meta_value );
		}
	}

	// Fire our post txn save hook
	do_action( 'tmem_after_txn_save', $post_id, $post, $update );

	// Re-add the save post action to avoid loops
	add_action( 'save_post_tmem-transaction', 'tmem_save_txn_post', 10, 3 );

} // tmem_save_txn_post
add_action( 'save_post_tmem-transaction', 'tmem_save_txn_post', 10, 3 );


/**
 * Customise the messages associated with managing transaction posts
 *
 * @since 1.3
 * @param arr $messages The current messages
 * @return arr $messages Filtered messages
 */
function tmem_transaction_post_messages( $messages ) {

	global $post;

	if ( 'tmem-transaction' != $post->post_type ) {
		return $messages;
	}

	$url1 = '<a href="' . admin_url( 'edit.php?post_type=tmem-transaction' ) . '">';
	$url2 = get_post_type_object( $post->post_type )->labels->singular_name;
	$url3 = '</a>';

	$messages['tmem-transaction'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		4 => sprintf( esc_html__( '%2$s updated. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		6 => sprintf( esc_html__( '%2$s inserted. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
		7 => sprintf( esc_html__( '%2$s saved. %1$s%2$s List%3$s.', 'mobile-events-manager' ), $url1, $url2, $url3 ),
	);

	return apply_filters( 'tmem_transaction_post_messages', $messages );

} // tmem_transaction_post_messages
add_filter( 'post_updated_messages', 'tmem_transaction_post_messages' );
