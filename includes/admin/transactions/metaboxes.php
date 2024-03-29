<?php

/**
 * Contains all metabox functions for the tmem-transaction post type
 *
 * @package TMEM
 * @subpackage Transactions
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove unwanted metaboxes to for the tmem-transaction post type.
 * Apply the `tmem_transaction_remove_metaboxes` filter to allow for filtering of metaboxes to be removed.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_remove_transaction_meta_boxes() {
	$metaboxes = apply_filters(
		'tmem_transaction_remove_metaboxes',
		array(
			array( 'submitdiv', 'tmem-transaction', 'side' ),
			array( 'transaction-typesdiv', 'tmem-transaction', 'side' ),
		)
	);

	foreach ( $metaboxes as $metabox ) {
		remove_meta_box( $metabox[0], $metabox[1], $metabox[2] );
	}
} // tmem_remove_transaction_meta_boxes
add_action( 'admin_head', 'tmem_remove_transaction_meta_boxes' );

/**
 * Define and add the metaboxes for the tmem-transaction post type.
 * Apply the `tmem_transaction_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_add_transaction_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'tmem_transaction_add_metaboxes',
		array(
			array(
				'id'         => 'tmem-txn-save',
				'title'      => __( 'Save Transaction', 'mobile-events-manager' ),
				'callback'   => 'tmem_transaction_metabox_save_txn',
				'context'    => 'side',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-txn-details',
				'title'      => __( 'Transaction Details', 'mobile-events-manager' ),
				'callback'   => 'tmem_transaction_metabox_txn_details',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'tmem_transaction_before_metaboxes' );

	// Begin metaboxes
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check
		if ( ! empty( $metabox['permission'] ) && ! tmem_employee_can( $metabox['permission'] ) ) {
			continue;
		}

		// Callback check
		if ( ! is_callable( $metabox['callback'] ) ) {
			continue;
		}

		add_meta_box(
			$metabox['id'],
			$metabox['title'],
			$metabox['callback'],
			'tmem-transaction',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'tmem_transaction_after_metaboxes' );
} // tmem_add_transaction_meta_boxes
add_action( 'add_meta_boxes_tmem-transaction', 'tmem_add_transaction_meta_boxes' );

/**
 * Output for the Client Details meta box.
 *
 * @since 1.3
 * @param obj $post Required: The post object (WP_Post).
 * @return
 */
function tmem_transaction_metabox_save_txn( $post ) {

	do_action( 'tmem_pre_txn_save_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'tmem-transaction' . '_nonce' );

	?>
	<div id="new_transaction_type_div">
		<div class="tmem-meta-row" style="height: 60px !important">
			<div class="tmem-left-col">
					<label class="tmem-label" for="transaction_type_name">New Transaction Type:</label><br />
					<input type="text" name="transaction_type_name" id="transaction_type_name" class="tmem-meta" placeholder="Transaction Type Name" />&nbsp;
						<a href="#" id="add_transaction_type" class="button button-primary button-small">Add</a>
			</div>
		</div>
	</div>

	<div class="tmem-meta-row">
		<div class="tmem-left-col">
		 <?php
			submit_button(
				( 'auto-draft' === $post->post_status ? 'Add Transaction' : 'Update Transaction' ),
				'primary',
				'save',
				false,
				array( 'id' => 'save-post' )
			);
			?>
		</div>
	</div>
	<?php

	do_action( 'tmem_post_txn_save_metabox', $post );

} // tmem_transaction_metabox_save_txn

/**
 * Output for the Transaction Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_transaction_metabox_txn_details( $post ) {

	$event_singular = esc_html( tmem_get_label_singular() );

	$message = sprintf(
		__( 'Go to the <a href="%1$s">%2$s Management Interface</a> to add a transaction associated to an %3$s.', 'mobile-events-manager' ),
		admin_url( 'edit.php?post_type=tmem-event' ),
		$event_singular,
		strtolower( $event_singular )
	);

	if ( ! empty( $post->post_parent ) && 'tmem-event' == get_post_type( $post->post_parent ) ) {
		$event_url = add_query_arg(
			array(
				'post'   => $post->post_parent,
				'action' => 'edit',
			),
			admin_url( 'post.php' )
		);
		$message   = sprintf(
			__( '<a class="page-title-action" href="%1$s">Edit %2$s</a>', 'mobile-events-manager' ),
			$event_url,
			$event_singular
		);
	}

	do_action( 'tmem_pre_txn_details_metabox', $post );

	?>

	<input type="hidden" name="tmem_update_custom_post" id="tmem_update_custom_post" value="tmem_update" />

	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<p><?php echo $message; ?></p>
		</div>
	</div>

	<?php
	tmem_insert_datepicker(
		array(
			'class'    => 'trans_date',
			'altfield' => 'transaction_date',
			'maxdate'  => 'today',
		)
	);

	echo '<div class="tmem-post-row">' . "\r\n";
		echo '<div class="tmem-post-3column">' . "\r\n";
			echo '<label class="tmem-label" for="transaction_amount">Amount:</label><br />' .
				tmem_currency_symbol() . '<input type="text" name="transaction_amount" id="transaction_amount" class="small-text required" placeholder="' .
				esc_html( tmem_sanitize_amount( '10' ) ) . '" value="' . esc_attr( tmem_sanitize_amount( get_post_meta( $post->ID, '_tmem_txn_total', true ) ) ) . '" />' . "\r\n";
		echo '</div>' . "\r\n";

		echo '<div class="tmem-post-3column">' . "\r\n";
			echo '<label class="tmem-label" for="transaction_display_date">Date:</label><br />' .
			'<input type="text" name="transaction_display_date" id="transaction_display_date" class="trans_date required" value="' . tmem_format_short_date( $post->post_date ) . '" />' .
			'<input type="hidden" name="transaction_date" id="transaction_date" value="' . gmdate( 'Y-m-d', strtotime( $post->post_date ) ) . '" />' . "\r\n";
		echo '</div>' . "\r\n";

		echo '<div class="tmem-post-last-3column">' . "\r\n";
			echo '<label class="tmem-label" for="transaction_direction">Direction:</label><br />' .
			'<select name="transaction_direction" id="transaction_direction" onChange="displayPaid();">' . "\r\n" .
			'<option value="In"' . selected( 'tmem-income', $post->post_status, false ) . '>Incoming</option>' . "\r\n" .
			'<option value="Out"' . selected( 'tmem-expenditure', $post->post_status, false ) . '>Outgoing</option>' . "\r\n" .
			'</select>' . "\r\n";
		echo '</div>' . "\r\n";
	echo '</div>' . "\r\n";
	?>
	<style>
	#paid_from_field	{
		display: <?php echo ( 'tmem-expenditure' !== $post->post_status ? 'block' : 'none' ); ?>;
	}
	#paid_to_field	{
		display: <?php echo ( 'tmem-expenditure' === $post->post_status ? 'block' : 'none' ); ?>;
	}
	</style>
	<script type="text/javascript">
	function displayPaid() {
		var direction = document.getElementById("transaction_direction");
		var direction_val = direction.options[direction.selectedIndex].value;
		var paid_from_div = document.getElementById("paid_from_field");
		var paid_to_div = document.getElementById("paid_to_field");

	 if (direction_val == 'Out') {
		 paid_from_div.style.display = "none";
		 paid_to_div.style.display = "block";
	 }
	 else {
		 paid_from_div.style.display = "block";
		 paid_to_div.style.display = "none";
	 }
	}
	</script>
	<?php
	echo '<div class="tmem-post-row">' . "\r\n";
		echo '<div class="tmem-post-3column">' . "\r\n";
			echo '<div id="paid_from_field">' . "\r\n";
				echo '<label class="tmem-label" for="transaction_from">Paid From:</label><br />';
			echo '</div>' . "\r\n";

			echo '<div id="paid_to_field">' . "\r\n";
				echo '<label class="tmem-label" for="transaction_to">Paid To:</label><br />';
			echo '</div>' . "\r\n";
			echo '<input type="text" name="transaction_payee" id="transaction_payee" class="regular_text" value="'
				. ( 'tmem-income' === $post->post_status ?
				get_post_meta( $post->ID, '_tmem_payment_from', true ) :
				get_post_meta( $post->ID, '_tmem_payment_to', true ) )
				. '" />';
		echo '</div>' . "\r\n";

		/* -- The current transaction type -- */
		$existing_transaction_type = wp_get_object_terms( $post->ID, 'transaction-types' );
		echo '<div class="tmem-post-3column">' . "\r\n";
			echo '<label class="tmem-label" for="transaction_for">Type:</label><br />';
				echo '<div id="transaction_types">' . "\r\n";
					/* -- Display the drop down selection -- */
					wp_dropdown_categories(
						array(
							'taxonomy'         => 'transaction-types',
							'hide_empty'       => 0,
							'name'             => 'tmem_transaction_type',
							'id'               => 'tmem_transaction_type',
							'selected'         => ( isset( $existing_transaction_type[0]->term_id ) ? $existing_transaction_type[0]->term_id : '' ),
							'orderby'          => 'name',
							'hierarchical'     => 0,
							'show_option_none' => __( 'Select Transaction Type', 'mobile-events-manager' ),
							'class'            => 'required',
						)
					);
						echo '<a id="new_transaction_type" class="side-meta" href="#">Add New</a>' . "\r\n";
				echo '</div>' . "\r\n";
		echo '</div>' . "\r\n";
		echo '<script type="text/javascript">' . "\r\n" .
		'jQuery("#tmem_transaction_type option:first").val(null);' . "\r\n" .
		'</script>' . "\r\n";
		$sources = tmem_get_txn_source();
		echo '<div class="tmem-post-last-3column">' . "\r\n";
			echo '<label class="tmem-label" for="transaction_src">Source:</label><br />' . "\r\n" .
				'<select name="transaction_src" id="transaction_src" class="required">' . "\r\n" .
				'<option value="">--- Select ---</option>' . "\r\n";
	foreach ( $sources as $source ) {
		echo '<option value="' . $source . '"' . selected( $source, get_post_meta( $post->ID, '_tmem_txn_source', true ) ) . '>' . $source . '</option>' . "\r\n";
	}
				echo '</select>' . "\r\n";
		echo '</div>' . "\r\n";

	echo '</div>' . "\r\n";
	?>
	<script type="text/javascript">
	jQuery("#tmem_event_type option:first").val(null);
	</script>
	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<label for="transaction_status" class="tmem-label">Status:</label><br />
			<select name="transaction_status" id="transaction_status" class="required">
			<option value="">--- Select ---</option>
			<option value="Completed"<?php selected( 'Completed', get_post_meta( $post->ID, '_tmem_txn_status', true ) ); ?>>Completed</option>
			<option value="Pending"<?php selected( 'Pending', get_post_meta( $post->ID, '_tmem_txn_status', true ) ); ?>>Pending</option>
			<option value="Refunded"<?php selected( 'Refunded', get_post_meta( $post->ID, '_tmem_txn_status', true ) ); ?>>Refunded</option>
			<option value="Cancelled"<?php selected( 'Cancelled', get_post_meta( $post->ID, '_tmem_txn_status', true ) ); ?>>Cancelled</option>
			<option value="Failed"<?php selected( 'Failed', get_post_meta( $post->ID, '_tmem_txn_status', true ) ); ?>>Completed</option>
			</select>
		</div>
	</div>
	<div class="tmem-post-row-single-textarea">
		<div class="tmem-post-1column">
			<label for="transaction_description" class="tmem-label"><?php esc_html_e( 'Description', 'mobile-events-manager' ); ?>:</label><br />
			<textarea name="transaction_description" id="transaction_description" class="widefat" cols="30" rows="3" placeholder="Enter any optional information here..."><?php echo esc_attr( get_post_meta( $post->ID, '_tmem_txn_notes', true ) ); ?></textarea>
		</div>
	</div>
	<?php

	do_action( 'tmem_post_txn_details_metabox', $post );

} // tmem_transaction_metabox_txn_details
