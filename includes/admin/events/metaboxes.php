<?php

/**
 * Contains all metabox functions for the mem-event post type
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
 * Generate the client details action links.
 *
 * @since 1.5
 * @param int  $event_id The event ID
 * @param obj  $mem_event The event object
 * @param bool $mem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function mem_client_details_get_action_links( $event_id, $mem_event, $mem_event_update ) {

	$actions = array();

	if ( ! empty( $mem_event->client ) && mem_employee_can( 'view_clients_list' ) ) {
		$actions['view_client'] = '<a href="#" class="toggle-client-details-option-section">' . __( 'Show client details', 'mobile-events-manager' ) . '</a>';
	}

	$actions['add_client'] = '<a id="add-client-action" href="#" class="toggle-client-add-option-section">' . __( 'Show client form', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'mem_event_metabox_client_details_actions', $actions, $event_id, $mem_event, $mem_event_update );
	return $actions;
} // mem_client_details_get_action_links

/**
 * Generate the event details action links.
 *
 * @since 1.5
 * @param int  $event_id The event ID
 * @param obj  $mem_event The event object
 * @param bool $mem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function mem_event_details_get_action_links( $event_id, $mem_event, $mem_event_update ) {

	$venue_id = $mem_event->get_venue_id();
	$actions  = array(
		'event_options' => '<a href="#" class="toggle-event-options-section">' . sprintf( esc_html__( 'Show %s options', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) . '</a>',
	);

	// Event workers
	if ( mem_is_employer() ) {
		$actions['event_workers'] = '<a href="#" class="toggle-add-worker-section">' . sprintf( esc_html__( 'Show %s workers', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) . '</a>';
	}

	// New event type
	if ( mem_is_admin() ) {
		$actions['event_type'] = '<a href="#" class="toggle-event-type-option-section">' . sprintf( esc_html__( 'Add %s type', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) . '</a>';
	}

	// Venues
	$actions['view_venue'] = '<a href="#" class="toggle-event-view-venue-option-section">' . __( 'Show venue', 'mobile-events-manager' ) . '</a>';
	$actions['add_venue']  = '<a href="#" class="toggle-event-add-venue-option-section">' . __( 'Add venue', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'mem_event_metabox_event_details_actions', $actions, $event_id, $mem_event, $mem_event_update );
	return $actions;
} // mem_event_details_get_action_links

/**
 * Generate the event pricing action links.
 *
 * @since 1.5
 * @param int  $event_id The event ID
 * @param obj  $mem_event The event object
 * @param bool $mem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function mem_event_pricing_get_action_links( $event_id, $mem_event, $mem_event_update ) {

	$actions = array();

	$actions = apply_filters( 'mem_event_pricing_actions', $actions, $event_id, $mem_event, $mem_event_update );
	return $actions;
} // mem_event_pricing_get_action_links

/**
 * Remove unwanted metaboxes to for the mem-event post type.
 * Apply the `mem_event_remove_metaboxes` filter to allow for filtering of metaboxes to be removed.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_remove_event_meta_boxes() {
	$metaboxes = apply_filters(
		'mem_event_remove_metaboxes',
		array(
			array( 'submitdiv', 'mem-event', 'side' ),
			array( 'event-typesdiv', 'mem-event', 'side' ),
			array( 'tagsdiv-enquiry-source', 'mem-event', 'side' ),
			array( 'commentsdiv', 'mem-event', 'normal' ),
		)
	);

	foreach ( $metaboxes as $metabox ) {
		remove_meta_box( $metabox[0], $metabox[1], $metabox[2] );
	}
} // mem_remove_event_meta_boxes
add_action( 'admin_head', 'mem_remove_event_meta_boxes' );

/**
 * Define and add the metaboxes for the mem-event post type.
 * Apply the `mem_event_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_add_event_meta_boxes( $post ) {

	global $mem_event, $mem_event_update;

	$save              = __( 'Create', 'mobile-events-manager' );
	$mem_event_update = false;
	$mem_event        = new MEM_Event( $post->ID );
	$mem_event->get_event_data();

	if ( 'draft' != $post->post_status && 'auto-draft' != $post->post_status ) {
		$mem_event_update = true;
	}

	$metaboxes = apply_filters(
		'mem_event_add_metaboxes',
		array(
			array(
				'id'         => 'mem-event-save-mb',
				'title'      => sprintf( esc_html__( '%1$s #%2$s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ), $mem_event->data['contract_id'] ),
				'callback'   => 'mem_event_metabox_save_callback',
				'context'    => 'side',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'mem-event-tasks-mb',
				'title'      => __( 'Tasks', 'mobile-events-manager' ),
				'callback'   => 'mem_event_metabox_tasks_callback',
				'context'    => 'side',
				'priority'   => 'default',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'mem-event-overview-mb',
				'title'      => sprintf( esc_html__( '%s Overview', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'callback'   => 'mem_event_metabox_overview_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'mem-event-admin-mb',
				'title'      => __( 'Administration', 'mobile-events-manager' ),
				'callback'   => 'mem_event_metabox_admin_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'mem-event-transactions-mb',
				'title'      => __( 'Transactions', 'mobile-events-manager' ),
				'callback'   => 'mem_event_metabox_transactions_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'edit_txns',
			),
			array(
				'id'         => 'mem-event-history-mb',
				'title'      => sprintf( esc_html__( '%s History', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'callback'   => 'mem_event_metabox_history_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'manage_mem',
			),
		)
	);
	// Runs before metabox output
	do_action( 'mem_event_before_metaboxes' );

	// Begin metaboxes
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check
		if ( ! empty( $metabox['permission'] ) && ! mem_employee_can( $metabox['permission'] ) ) {
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
			'mem-event',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'mem_event_after_metaboxes' );
} // mem_add_event_meta_boxes
add_action( 'add_meta_boxes_mem-event', 'mem_add_event_meta_boxes' );

/**
 * Output for the Event Options meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_save_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	?>

	<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
			<div id="mem-event-actions">

				<?php
				/*
				 * Output the items for the options metabox
				 * These items go inside the mem-event-actions div
				 * @since	1.3.7
				 * @param	int	$post_id	The Event post ID
				 */
				do_action( 'mem_event_options_fields', $post->ID );
				?>
			</div><!-- #mem-event-actions -->
		</div><!-- #minor-publishing -->
	</div><!-- #submitpost -->
	<?php
	do_action( 'mem_event_options_fields_save', $post->ID );

} // mem_event_metabox_save_callback

/**
 * Output for the Event Tasks meta box.
 *
 * @since 1.5
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_tasks_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	/*
	 * Output the items for the options metabox
	 * These items go inside the mem-event-actions div
	 * @since 1.5
	 * @param int $post_id The Event post ID
	 */
	do_action( 'mem_event_tasks_fields', $post->ID );

} // mem_event_metabox_tasks_callback

/**
 * Output for the Event Overview meta box.
 *
 * @since 1.5
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_overview_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	/*
	 * Output the items for the event overview metabox
	 * @since	1.5
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'mem_event_overview_fields', $post->ID );

} // mem_event_metabox_overview_callback

/**
 * Output for the Event Administration meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_admin_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	/*
	 * Output the items for the event admin metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'mem_event_admin_fields', $post->ID );

} // mem_event_metabox_admin_callback

/**
 * Output for the Event History meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_history_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	/*
	 * Output the items for the event history metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'mem_event_history_fields', $post->ID );

} // mem_event_metabox_history_callback

/**
 * Output for the Event Transactions meta box.
 *
 * @since 1.3
 * @global obj $post WP_Post object
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_event_metabox_transactions_callback( $post ) {

	global $post, $mem_event, $mem_event_update;

	/*
	 * Output the items for the event transactions metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'mem_event_txn_fields', $post->ID );

} // mem_event_metabox_transactions_callback

/**
 * Output the event options type row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_options_status_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$contract        = $mem_event->get_contract();
	$contract_status = $mem_event->get_contract_status();

	echo MEM()->html->event_status_dropdown( 'mem_event_status', $mem_event->post_status );

} // mem_event_metabox_options_status_row
add_action( 'mem_event_options_fields', 'mem_event_metabox_options_status_row', 10 );

/**
 * Output the event options payments row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_options_payments_row( $event_id ) {

	global $mem_event, $mem_event_update;

	if ( ! mem_employee_can( 'edit_txns' ) ) {
		return;
	}

	$deposit_status = __( 'Due', 'mobile-events-manager' );
	$balance_status = __( 'Due', 'mobile-events-manager' );

	if ( $mem_event_update && 'mem-unattended' != $mem_event->post_status ) {
		$deposit_status = $mem_event->mem_get_deposit_status();
		$balance_status = $mem_event->get_balance_status();
	}

	?>

	<p><strong><?php esc_html_e( 'Payments', 'mobile-events-manager' ); ?></strong></p>

	<p>
	<?php
	echo MEM()->html->checkbox(
		array(
			'name'    => 'deposit_paid',
			'value'   => 'Paid',
			'current' => $deposit_status,
		)
	);
	?>
		 <?php printf( esc_html__( '%s Paid?', 'mobile-events-manager' ), mem_get_deposit_label() ); ?></p>

	<p>
	<?php
	echo MEM()->html->checkbox(
		array(
			'name'    => 'balance_paid',
			'value'   => 'Paid',
			'current' => $balance_status,
		)
	);
	?>
		 <?php printf( esc_html__( '%s Paid?', 'mobile-events-manager' ), mem_get_balance_label() ); ?></p>

	<?php

} // mem_event_metabox_options_payments_row
add_action( 'mem_event_options_fields', 'mem_event_metabox_options_payments_row', 30 );

/**
 * Output the event options save row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_options_save_row( $event_id ) {

	global $mem_event, $mem_event_update;

	if ( ! mem_employee_can( 'manage_events' ) ) {
		return;
	}

	$button_text = __( 'Add %s', 'mobile-events-manager' );

	if ( $mem_event_update ) {
		$button_text = __( 'Update %s', 'mobile-events-manager' );
	}

	$class = '';
	$url   = add_query_arg( array( 'post_type' => 'mem-event' ), admin_url( 'edit.php' ) );
	$a     = sprintf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) );

	if ( mem_employee_can( 'manage_all_events' ) && ( ! $mem_event_update || 'mem-unattended' == $mem_event->post_status ) ) {
		$class = 'mem-delete';
		$url   = wp_nonce_url(
			add_query_arg(
				array(
					'post'   => $event_id,
					'action' => 'trash',
				),
				admin_url( 'post.php' )
			),
			'trash-post_' . $event_id
		);
		$a     = sprintf( esc_html__( 'Delete %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) );
	}

	?>
	<div id="major-publishing-actions">
		<div id="delete-action">
			<a class="<?php echo $class; ?>" href="<?php echo $url; ?>"><?php echo $a; ?></a>
		</div>

		<div id="publishing-action">
			<?php
			submit_button(
				sprintf( $button_text, esc_html( mem_get_label_singular() ) ),
				'primary',
				'save',
				false,
				array( 'id' => 'save-post' )
			);
			?>
		</div>
		<div class="clear"></div>
	</div>

	<?php

} // mem_event_metabox_options_save_row
add_action( 'mem_event_options_fields_save', 'mem_event_metabox_options_save_row', 40 );

/**
 * Output the event tasks row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_tasks_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$completed_tasks = $mem_event->get_tasks();
	$tasks_history   = array();
	$tasks           = mem_get_tasks_for_event( $event_id );

	foreach ( $completed_tasks as $task_slug => $run_time ) {
		if ( ! array_key_exists( $task_slug, $tasks ) ) {
			continue;
		}

		$tasks_history[] = sprintf(
			'%s: %s',
			mem_get_task_name( $task_slug ),
			date( mem_get_option( 'short_date_format' ), $run_time )
		);
	}

	if ( ! empty( $tasks_history ) ) {
		$history_class = '';
		$history       = implode( '<br>', $tasks_history );
	} else {
		$history_class = ' description';
		$history       = sprintf( esc_html__( 'None of the available tasks have been executed for this %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) );
	}

	?>
	<div id="mem-event-tasks">
		<?php if ( ! $mem_event_update || empty( $tasks ) ) : ?>
			<span class="description">
				<?php printf( esc_html__( 'No tasks are available for this %s.', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?>
			</span>
			<?php
		else :

			foreach ( $tasks as $id => $name ) :
				?>
				<?php $options[ $id ] = $name; ?>
			<?php endforeach; ?>

			<?php
			echo MEM()->html->select(
				array(
					'options'          => $options,
					'name'             => 'mem_event_task',
					'id'               => 'mem-event-task',
					'chosen'           => true,
					'placeholder'      => __( 'Select a Task', 'mobile-events-manager' ),
					'show_option_none' => __( 'Select a Task', 'mobile-events-manager' ),
				)
			);
			?>

			<div id="mem-event-task-run" class="mem-hidden">
				<p class="mem-execute-event-task">
				<?php
				submit_button(
					__( 'Run Task', 'mobile-events-manager' ),
					array( 'secondary', 'mem-run-event-task' ),
					'mem-run-task',
					false
				);
				?>
				</p>
				<span id="mem-spinner" class="spinner mem-execute-event-task"></span>
			</div>

			<p><strong><?php esc_html_e( 'Completed Tasks', 'mobile-events-manager' ); ?></strong></p>
			<span class="task-history-items<?php echo $history_class; ?>"><?php echo $history; ?></span>
		<?php endif; ?>
	</div>

	<?php

} // mem_event_metabox_tasks_row
add_action( 'mem_event_tasks_fields', 'mem_event_metabox_tasks_row', 10 );

/**
 * Output the event client sections
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_client_sections( $event_id ) {

	global $mem_event, $mem_event_update;

	?>
	<div id="mem_event_overview_client_fields" class="mem_meta_table_wrap">

		<div class="widefat mem_repeatable_table">

			<div class="mem-client-option-fields mem-repeatables-wrap">

				<div class="mem_event_overview_wrapper">

					<div class="mem-client-row-header">
						<span class="mem-repeatable-row-title">
							<?php esc_html_e( 'Client Details', 'mobile-events-manager' ); ?>
						</span>

						<?php
						$actions = mem_client_details_get_action_links( $event_id, $mem_event, $mem_event_update );
						?>

						<span class="mem-repeatable-row-actions">
							<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
						</span>
					</div>

					<div class="mem-repeatable-row-standard-fields">
						<?php do_action( 'mem_event_overview_standard_client_sections', $event_id ); ?>
					</div>
					<?php do_action( 'mem_event_overview_custom_client_sections', $event_id ); ?>
				</div>

			</div>

		</div>

	</div>
	<?php

} // mem_event_overview_metabox_client_sections
add_action( 'mem_event_overview_fields', 'mem_event_overview_metabox_client_sections', 10 );

/**
 * Output the event sections
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_sections( $event_id ) {

	global $mem_event, $mem_event_update;

	$singular = esc_html( mem_get_label_singular() );

	?>
	<div id="mem_event_overview_event_fields" class="mem_meta_table_wrap">

		<div class="widefat mem_repeatable_table">
			<div class="mem-event-option-fields mem-repeatables-wrap">
				<div class="mem_event_overview_wrapper">
					<div class="mem-event-row-header">
						<span class="mem-repeatable-row-title">
							<?php printf( esc_html__( '%s Details', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?>
						</span>

						<?php
						$actions = mem_event_details_get_action_links( $event_id, $mem_event, $mem_event_update );
						?>

						<span class="mem-repeatable-row-actions">
							<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
						</span>
					</div>

					<div class="mem-repeatable-row-standard-fields">
						<?php do_action( 'mem_event_overview_standard_event_sections', $event_id ); ?>
					</div>
					<?php do_action( 'mem_event_overview_custom_event_sections', $event_id ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_event_sections
add_action( 'mem_event_overview_fields', 'mem_event_overview_metabox_event_sections', 20 );

/**
 * Output the event price sections
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_price_sections( $event_id ) {

	global $mem_event, $mem_event_update;

	$singular = esc_html( mem_get_label_singular() );

	if ( mem_employee_can( 'edit_txns' ) ) :
		?>

		<div id="mem_event_overview_event_price_fields" class="mem_meta_table_wrap">

			<div class="widefat mem_repeatable_table">
				<div class="mem-event-option-fields mem-repeatables-wrap">
					<div class="mem_event_overview_wrapper">
						<div class="mem-event-row-header">
							<span class="mem-repeatable-row-title">
								<?php esc_html_e( 'Pricing', 'mobile-events-manager' ); ?>
							</span>

							<?php
							$actions = mem_event_pricing_get_action_links( $event_id, $mem_event, $mem_event_update );
							?>

							<span class="mem-repeatable-row-actions">
								<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
							</span>
						</div>

						<div id="mem-event-pricing-detail" class="mem-repeatable-row-standard-fields">
							<?php do_action( 'mem_event_overview_standard_event_price_sections', $event_id ); ?>
						</div>
						<?php do_action( 'mem_event_overview_custom_event_price_sections', $event_id ); ?>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_package_cost',
				'value' => ! empty( $mem_event->package_price ) ? mem_sanitize_amount( $mem_event->package_price ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_addons_cost',
				'value' => ! empty( $mem_event->addons_price ) ? mem_sanitize_amount( $mem_event->addons_price ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_travel_cost',
				'value' => ! empty( $mem_event->travel_cost ) ? mem_sanitize_amount( $mem_event->travel_cost ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_additional_cost',
				'value' => ! empty( $mem_event->additional_cost ) ? mem_sanitize_amount( $mem_event->additional_cost ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_discount',
				'value' => ! empty( $mem_event->discount ) ? mem_sanitize_amount( $mem_event->discount ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_deposit',
				'value' => $mem_event_update ? mem_sanitize_amount( $mem_event->deposit ) : '',
			)
		);
		?>

		<?php
		echo MEM()->html->hidden(
			array(
				'name'  => '_mem_event_cost',
				'value' => ! empty( $mem_event->price ) ? mem_sanitize_amount( $mem_event->price ) : '',
			)
		);
		?>

		<?php
	endif;

} // mem_event_overview_metabox_event_price_sections
add_action( 'mem_event_overview_fields', 'mem_event_overview_metabox_event_price_sections', 30 );

/**
 * Output the client name row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_client_name_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$block_emails = false;

	if ( 'mem-enquiry' == $mem_event->post_status && ! mem_get_option( 'contract_to_client' ) ) {
		$block_emails = true;
	}

	if ( 'mem-enquiry' == $mem_event->post_status && ! mem_get_option( 'booking_conf_to_client' ) ) {
		$block_emails = true;
	}
	?>

 <div class="mem-client-name">
		<span class="mem-repeatable-row-setting-label"><?php esc_html_e( 'Client', 'mobile-events-manager' ); ?></span>

		<?php if ( mem_event_is_active( $event_id ) ) : ?>

			<?php $clients = mem_get_clients( 'client' ); ?>

			<?php
			echo MEM()->html->client_dropdown(
				array(
					'selected'         => $mem_event->client,
					'roles'            => array( 'client' ),
					'chosen'           => true,
					'placeholder'      => __( 'Select a Client', 'mobile-events-manager' ),
					'null_value'       => array( '' => __( 'Select a Client', 'mobile-events-manager' ) ),
					'show_option_all'  => false,
					'show_option_none' => false,
				)
			);
			?>

		<?php else : ?>

			<?php
			echo MEM()->html->text(
				array(
					'name'     => 'client_name_display',
					'value'    => mem_get_client_display_name( $mem_event->client ),
					'readonly' => true,
				)
			);
			?>

			<?php
			echo MEM()->html->hidden(
				array(
					'name'  => 'client_name',
					'value' => $mem_event->client,
				)
			);
			?>

		<?php endif; ?>
	</div>

	<?php if ( mem_event_is_active( $mem_event->ID ) && 'mem-completed' != $mem_event->post_status && 'mem-approved' != $mem_event->post_status ) : ?>
		<div class="mem-repeatable-option mem_repeatable_default_wrapper">

			<span class="mem-repeatable-row-setting-label"><?php esc_html_e( 'Disable Emails?', 'mobile-events-manager' ); ?></span>
			<label class="mem-block-email">
				<?php
				echo MEM()->html->checkbox(
					array(
						'name'    => 'mem_block_emails',
						'current' => $block_emails,
					)
				);
				?>
				<span class="screen-reader-text"><?php printf( esc_html__( 'Block update emails for this %s to the client', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></span>
			</label>

		</div>
	<?php endif; ?>

	<?php if ( ! $mem_event_update || 'mem-unattended' == $mem_event->post_status ) : ?>
		<div class="mem-repeatable-option mem_repeatable_default_wrapper">

			<span class="mem-repeatable-row-setting-label"><?php esc_html_e( 'Reset Password?', 'mobile-events-manager' ); ?></span>
			<label class="mem-reset-password">
				<?php
				echo MEM()->html->checkbox(
					array(
						'name'  => 'mem_reset_pw',
						'value' => 'Y',
					)
				);
				?>
				<span class="screen-reader-text"><?php printf( esc_html__( 'Click to reset the client password during %s update', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></span>
			</label>

		</div>
		<?php
	endif;

} // mem_event_overview_metabox_client_name_row
add_action( 'mem_event_overview_standard_client_sections', 'mem_event_overview_metabox_client_name_row', 10 );

/**
 * Output the client name row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_client_templates_row( $event_id ) {

	global $mem_event, $mem_event_update;
	?>

	<?php if ( ! $mem_event_update || 'mem-unattended' === $mem_event->post_status ) : ?>
		<div class="mem-client-template-fields">
			<div class="mem-quote-template">
				<span class="mem-repeatable-row-setting-label"><?php esc_html_e( 'Quote Template', 'mobile-events-manager' ); ?></span>
				<?php
				echo MEM()->html->select(
					array(
						'name'     => 'mem_email_template',
						'options'  => mem_list_templates( 'email_template' ),
						'selected' => mem_get_option( 'enquiry' ),
						'chosen'   => true,
						'data'     => array(
							'search-type'        => 'template',
							'search-placeholder' => __( 'Type to search all templates', 'mobile-events-manager' ),
						),
					)
				);
				?>
			</div>

			<?php if ( mem_get_option( 'online_enquiry', false ) ) : ?>

				<div class="mem-online-template">
					<span class="mem-repeatable-row-setting-label"><?php esc_html_e( 'Online Quote', 'mobile-events-manager' ); ?></span>
					<?php
					echo MEM()->html->select(
						array(
							'name'     => 'mem_online_quote',
							'options'  => mem_list_templates( 'email_template' ),
							'selected' => mem_get_option( 'online_enquiry' ),
							'chosen'   => true,
							'data'     => array(
								'search-type'        => 'template',
								'search-placeholder' => __( 'Type to search all templates', 'mobile-events-manager' ),
							),
						)
					);
					?>
				</div>

			<?php endif; ?>
		</div>

		<?php
	endif;

} // mem_event_overview_metabox_client_templates_row
add_action( 'mem_event_overview_standard_client_sections', 'mem_event_overview_metabox_client_templates_row', 20 );

/**
 * Output the add client section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_add_client_section( $event_id ) {

	global $mem_event, $mem_event_update;
	?>

	<div id="mem-add-client-fields" class="mem-client-add-event-sections-wrap">
		<div class="mem-custom-event-sections">
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php esc_html_e( 'Add a New Client', 'mobile-events-manager' ); ?></span>

				<span class="mem-client-new-first">
					<label class="mem-client-first">
						<?php esc_html_e( 'First Name', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'client_firstname',
							'class' => 'mem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="mem-client-new-last">
					<label class="mem-client-last">
						<?php esc_html_e( 'Last Name', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_lastname',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-email">
					<label class="mem-client-email">
						<?php esc_html_e( 'Email Address', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'client_email',
							'class' => 'mem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="mem-client-new-address1">
					<label class="mem-client-address1">
						<?php esc_html_e( 'Address Line 1', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_address1',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-address2">
					<label class="mem-client-address2">
						<?php esc_html_e( 'Address Line 2', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_address2',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-town">
					<label class="mem-client-town">
						<?php esc_html_e( 'Town', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_town',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-county">
					<label class="mem-client-county">
						<?php esc_html_e( 'County', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_county',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-postcode">
					<label class="mem-client-postcode">
						<?php esc_html_e( 'Postal Code', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_postcode',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-phone">
					<label class="mem-client-phone">
						<?php esc_html_e( 'Primary Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_phone',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-client-new-alt-phone">
					<label class="mem-client-phone">
						<?php esc_html_e( 'Alternative Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'client_phone2',
							'class'       => 'mem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-client-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						__( 'Add Client', 'mobile-events-manager' ),
						array( 'secondary', 'mem-add-client' ),
						'mem-add-client',
						false
					);
					?>
				</span>

			</div>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_add_client_section
add_action( 'mem_event_overview_custom_client_sections', 'mem_event_overview_metabox_add_client_section', 10 );

/**
 * Output the client details section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_client_details_section( $event_id ) {

	global $mem_event, $mem_event_update;

	if ( empty( $mem_event->client ) || ! mem_employee_can( 'view_clients_list' ) ) {
		return;
	}

	$client = get_userdata( $mem_event->data['client'] );

	if ( ! $client ) {
		return;
	}

	$phone_numbers = array();
	if ( ! empty( $client->phone1 ) ) {
		$phone_numbers[] = $client->phone1;
	}
	if ( ! empty( $client->phone2 ) ) {
		$phone_numbers[] = $client->phone2;
	}

	?>
	 <div class="mem-client-details-event-sections-wrap">
		<div class="mem-custom-event-sections">
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php printf( esc_html__( 'Contact Details for %s', 'mobile-events-manager' ), $client->display_name ); ?></span>

				<?php if ( ! empty( $phone_numbers ) ) : ?>
					<span class="mem-client-telephone">
						<i class="fa fa-phone" aria-hidden="true" title="<?php esc_html_e( 'Phone', 'mobile-events-manager' ); ?>"></i> <?php echo implode( '&nbsp;&#124;&nbsp;', $phone_numbers ); ?>
					</span>
				<?php endif; ?>

				<span class="mem-client-email">
					<i class="fa fa-envelope-o" aria-hidden="true" title="<?php esc_html_e( 'Email', 'mobile-events-manager' ); ?>"></i>&nbsp;
					<a href="
					<?php
					echo add_query_arg(
						array(
							'recipient' => $client->ID,
							'event_id'  => $event_id,
						),
						admin_url( 'admin.php?page=mem-comms' )
					);
					?>
								">
						<?php echo $client->user_email; ?>
					</a>
				</span>

				<span class="mem-client-address">
					<?php echo mem_get_client_full_address( $client->ID ); ?>
				</span>

				<span class="mem-client-login">
					<i class="fa fa-sign-in" aria-hidden="true" title="<?php esc_html_e( 'Last Login', 'mobile-events-manager' ); ?>"></i> <?php echo mem_get_client_last_login( $client->ID ); ?>
				</span>
			</div>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_client_details_section
add_action( 'mem_event_overview_custom_client_sections', 'mem_event_overview_metabox_client_details_section', 20 );

/**
 * Output the primary employee row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_primary_employee_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$employee_id    = $mem_event->data['employee_id'] ? $mem_event->data['employee_id'] : get_current_user_id();
	$payment_status = $mem_event->data['primary_employee_payment_status'];
	$artist         = esc_attr( mem_get_option( 'artist' ) );

	if ( isset( $_GET['primary_employee'] ) ) {
		$employee_id = sanitize_key( wp_unslash( $_GET['primary_employee'] ) );
	}

	?>
   <div class="mem-event-employee-fields">
        <div class="mem-event-primary-employee">
            <span class="mem-repeatable-row-setting-label">
                <?php printf( '%s', $artist ); ?>
            </span>
            
            <?php echo MEM()->html->employee_dropdown( array(
                'selected'    => $employee_id,
                'group'       => mem_is_employer() ? true : false,
                'chosen'      => true,
                'placeholder' => sprintf( __( 'Select %s', 'mobile-events-manager' ), $artist )
            ) ); ?>
        </div>

		<?php if ( mem_get_option( 'enable_employee_payments' ) && mem_employee_can( 'edit_txns' ) ) : ?>

			<?php $wage = mem_get_employees_event_wage( $event_id, $employee_id ); ?>

			<div class="mem-event-employee-wage">
				<span class="mem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Wage', 'mobile-events-manager' );
					echo ' ' . mem_currency_symbol();
					?>
				</span>

				<?php
				echo MEM()->html->text(
					array(
						'name'        => '_mem_event_dj_wage',
						'class'       => 'mem-currency',
						'value'       => ! empty( $wage ) ? $wage : '',
						'placeholder' => esc_html( mem_sanitize_amount( '0' ) ),
						'readonly'    => $payment_status ? true : false,
					)
				);
				?>

			</div>

		<?php endif; ?>
	</div>
	<?php

} // mem_event_overview_metabox_primary_employee_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_primary_employee_row', 5 );

/**
 * Output the event type, contract and venue row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_type_contract_venue_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$event_type      = mem_get_event_type( $event_id, true );
	$contract        = $mem_event->data['contract'];
	$contract_status = $mem_event->data['contract_status'];
	$venue_id        = $mem_event->data['venue_id'];

	if ( ! $event_type ) {
		$event_type = mem_get_option( 'event_type_default', '' );
	}

	if ( ! empty( $venue_id ) && $venue_id == $event_id ) {
		$venue_id = 'manual';
	}

	?>
	<div class="mem-event-type-fields">
		<div class="mem-event-type">
			<span class="mem-repeatable-row-setting-label">
				<?php printf( esc_html__( '%s Type', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?>
			</span>

			<?php
			echo MEM()->html->event_type_dropdown(
				array(
					'name'     => 'mem_event_type',
					'chosen'   => true,
					'selected' => $event_type,
				)
			);
			?>
		</div>

		<div class="mem-event-contract">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Contract', 'mobile-events-manager' ); ?>
			</span>

			<?php if ( ! $contract_status ) : ?>

				<?php
				echo MEM()->html->select(
					array(
						'name'     => '_mem_event_contract',
						'options'  => mem_list_templates( 'contract' ),
						'chosen'   => true,
						'selected' => ! empty( $contract ) ? $contract : mem_get_option( 'default_contract' ),
						'data'     => array(
							'search-type'        => 'contract',
							'search-placeholder' => __( 'Type to search all contracts', 'mobile-events-manager' ),
						),
					)
				);
				?>

			<?php else : ?>

				<?php if ( mem_employee_can( 'manage_events' ) ) : ?>
					<a id="view_contract" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'mem_action' => 'review_contract',
								'event_id'    => $event_id,
							),
							home_url()
						)
					);
					?>
												" target="_blank"><?php esc_html_e( 'View signed contract', 'mobile-events-manager' ); ?></a>
				<?php else : ?>
					<?php esc_html_e( 'Contract is Signed', 'mobile-events-manager' ); ?>
				<?php endif; ?>

			<?php endif; ?>
		</div>

		<div class="mem-event-venue">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Venue', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->venue_dropdown(
				array(
					'name'        => 'venue_id',
					'selected'    => $venue_id,
					'placeholder' => __( 'Select a Venue', 'mobile-events-manager' ),
					'chosen'      => true,
				)
			);
			?>

		</div>
	</div>

	<?php
} // mem_event_overview_metabox_event_type_contract_venue_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_event_type_contract_venue_row', 15 );

/**
 * Output the event date rows
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_dates_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$finish_date = $mem_event->data['finish_date'];
	$setup_date  = $mem_event->data['setup_date'];

	mem_insert_datepicker(
		array(
			'id' => 'display_event_date',
		)
	);

	mem_insert_datepicker(
		array(
			'id'       => 'display_event_finish_date',
			'altfield' => '_mem_event_end_date',
		)
	);

	mem_insert_datepicker(
		array(
			'id'       => 'dj_setup_date',
			'altfield' => '_mem_event_djsetup',
		)
	);

	?>
	<div class="mem-event-date-fields">
		<div class="mem-event-date">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Date', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'     => 'display_event_date',
					'class'    => 'mem_date',
					'required' => true,
					'value'    => ! empty( $mem_event->data['date'] ) ? mem_format_short_date( $mem_event->data['date'] ) : '',
				)
			);
			?>
			<?php
			echo MEM()->html->hidden(
				array(
					'name'  => '_mem_event_date',
					'value' => ! empty( $mem_event->data['date'] ) ? $mem_event->data['date'] : '',
				)
			);
			?>
		</div>

		<div class="mem-event-finish-date">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'End', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'     => 'display_event_finish_date',
					'class'    => 'mem_date',
					'required' => false,
					'value'    => ! empty( $finish_date ) ? mem_format_short_date( $finish_date ) : '',
				)
			);
			?>
			<?php
			echo MEM()->html->hidden(
				array(
					'name'  => '_mem_event_end_date',
					'value' => ! empty( $finish_date ) ? $finish_date : '',
				)
			);
			?>
		</div>

		<div class="mem-event-setup-date">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Setup', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'  => 'dj_setup_date',
					'class' => 'mem_setup_date',
					'value' => $setup_date ? mem_format_short_date( $setup_date ) : '',
				)
			);
			?>
			<?php
			echo MEM()->html->hidden(
				array(
					'name'  => '_mem_event_djsetup',
					'value' => $setup_date ? $setup_date : '',
				)
			);
			?>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_event_dates_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_event_dates_row', 20 );

/**
 * Output the event times row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_times_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$start      = $mem_event->data['start_time'];
	$finish     = $mem_event->data['finish_time'];
	$setup_time = $mem_event->data['setup_time'];
	$format     = mem_get_option( 'time_format', 'H:i' );
	?>

	<div class="mem-event-date-fields">
		<div class="mem-event-start-time">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Start', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->time_hour_select(
				array(
					'selected' => ! empty( $start ) ? gmdate( $format[0], strtotime( $start ) ) : '',
				)
			);
			?>

			<?php
			echo MEM()->html->time_minute_select(
				array(
					'selected' => ! empty( $start ) ? gmdate( $format[2], strtotime( $start ) ) : '',
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo MEM()->html->time_period_select(
					array(
						'selected' => ! empty( $start ) ? gmdate( 'A', strtotime( $start ) ) : '',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="mem-event-end-time">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'End', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->time_hour_select(
				array(
					'name'     => 'event_finish_hr',
					'selected' => ! empty( $finish ) ? gmdate( $format[0], strtotime( $finish ) ) : '',
				)
			);
			?>

			<?php
			echo MEM()->html->time_minute_select(
				array(
					'name'     => 'event_finish_min',
					'selected' => ! empty( $finish ) ? gmdate( $format[2], strtotime( $finish ) ) : '',
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo MEM()->html->time_period_select(
					array(
						'name'     => 'event_finish_period',
						'selected' => ! empty( $finish ) ? gmdate( 'A', strtotime( $finish ) ) : '',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="mem-event-setup-time">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Setup', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->time_hour_select(
				array(
					'name'        => 'dj_setup_hr',
					'selected'    => ! empty( $setup_time ) ? gmdate( $format[0], strtotime( $setup_time ) ) : '',
					'blank_first' => true,
				)
			);
			?>

			<?php
			echo MEM()->html->time_minute_select(
				array(
					'name'        => 'dj_setup_min',
					'selected'    => ! empty( $setup_time ) ? gmdate( $format[2], strtotime( $setup_time ) ) : '',
					'blank_first' => true,
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo MEM()->html->time_period_select(
					array(
						'name'        => 'dj_setup_period',
						'selected'    => ! empty( $setup_time ) ? gmdate( 'A', strtotime( $setup_time ) ) : '',
						'blank_first' => true,
					)
				);
				?>
			<?php endif; ?>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_event_times_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_event_times_row', 20 );

/**
 * Output the event package row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_packages_row( $event_id ) {

	if ( ! mem_packages_enabled() ) {
		return;
	}

	global $mem_event, $mem_event_update;

	$package    = $mem_event->data['package'];
	$addons     = $mem_event->data['addons'];
	$employee   = $mem_event->data['employee_id'] ? $mem_event->data['employee_id'] : get_current_user_id();
	$event_type = mem_get_event_type( $event_id, true );
	$event_date = $mem_event->data['date'] ? $mem_event->data['date'] : false;

	if ( ! $event_type ) {
		$event_type = mem_get_option( 'event_type_default', '' );
	}

	?>

	<div class="mem-event-package-fields">
		<div class="mem-event-package">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Package', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->packages_dropdown(
				array(
					'employee'   => $employee,
					'event_type' => $event_type,
					'event_date' => $event_date,
					'selected'   => $package,
					'chosen'     => true,
				)
			);
			?>
		</div>

		<div class="mem-event-addons">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Addons', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->addons_dropdown(
				array(
					'selected'         => $addons,
					'show_option_none' => false,
					'employee'         => $employee,
					'event_type'       => $event_type,
					'event_date'       => $event_date,
					'package'          => $package,
					'placeholder'      => __( 'Select Add-ons', 'mobile-events-manager' ),
					'chosen'           => true,
				)
			);
			?>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_event_packages_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_event_packages_row', 30 );

/**
 * Output the event client notes row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_client_notes_row( $event_id ) {

	global $mem_event, $mem_event_update;

	?>

	<div class="mem-event-client-notes-fields">
		<div class="mem-event-notes">
			<span class="mem-repeatable-row-setting-label">
				<?php esc_html_e( 'Client Notes', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo MEM()->html->textarea(
				array(
					'name'        => '_mem_event_notes',
					'placeholder' => __( 'Information entered here is visible by both clients and employees', 'mobile-events-manager' ),
					'value'       => $mem_event->data['notes'],
				)
			);
			?>
		</div>
	</div>
	<?php

} // mem_event_overview_metabox_event_client_notes_row
add_action( 'mem_event_overview_standard_event_sections', 'mem_event_overview_metabox_event_client_notes_row', 50 );

/**
 * Output the playlist options section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_playlist_options_row( $event_id ) {
	global $mem_event, $mem_event_update;

	$enable_playlist = mem_get_option( 'enable_playlists', true );
	$limit_class     = '';

	if ( ! $mem_event_update || 'mem-unattended' == $mem_event->data['status'] ) {
		$playlist_limit = mem_playlist_global_limit();
	} else {
		$playlist_limit  = $mem_event->data['playlist_limit'];
		$enable_playlist = $mem_event->data['playlist_enabled'];
	}

	if ( ! $enable_playlist ) {
		$limit_class = ' style="display: none;"';
	}

	if ( ! $playlist_limit ) {
		$playlist_limit = 0;
	}

	?>
	<div id="mem-event-options-fields" class="mem-event-options-sections-wrap">
		<div class="mem-custom-event-sections">
			<?php do_action( 'mem_event_overview_options_top', $event_id ); ?>
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php printf( esc_html__( '%s Options', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></span>

				<div class="mem-repeatable-option">
					<span class="mem-enable-playlist-option">
						<label class="mem-enable-playlist">
							<?php esc_html_e( 'Enable Playlist?', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo MEM()->html->checkbox(
							array(
								'name'    => '_mem_event_playlist',
								'value'   => 'Y',
								'current' => $enable_playlist ? 'Y' : 0,
							)
						);
						?>
					</span>
				</div>

				<span id="mem-playlist-limit" class="mem-playlist-limit-option" <?php echo $limit_class; ?>>
					<label class="mem-playlist-limit">
						<?php esc_html_e( 'Song Limit', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->number(
						array(
							'name'  => '_mem_event_playlist_limit',
							'value' => $playlist_limit,
						)
					);
					?>
				</span>

				<?php if ( mem_event_has_playlist( $event_id ) ) : ?>
					<?php $songs = mem_count_playlist_entries( $event_id ); ?>
					<?php
					$url = add_query_arg(
						array(
							'page'     => 'mem-playlists',
							'event_id' => $event_id,
						),
						admin_url( 'admin.php' )
					);
					?>

					<span id="mem-playlist-view" class="mem-playlist-view-option">
						<label class="mem-playlist-view">
							<?php printf( _n( '%s Song in Playlist', '%s Songs in Playlist', $songs, 'mobile-events-manager' ), $songs ); ?>
						</label>
						<a href="<?php echo $url; ?>"><?php esc_html_e( 'View', 'mobile-events-manager' ); ?></a>
					</span>
				<?php endif; ?>

			</div>
			<?php do_action( 'mem_event_overview_options', $event_id ); ?>
		</div>
	</div>

	<?php

} // mem_event_overview_metabox_event_playlist_options_row
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_event_playlist_options_row', 5 );

/**
 * Output the event workers section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_workers_row( $event_id ) {
	global $mem_event, $mem_event_update;

	if ( ! mem_is_employer() ) {
		return;
	}

	$exclude = false;

	if ( ! empty( $mem_event->data['employees'] ) ) {
		foreach ( $mem_event->data['employees'] as $employee_id => $employee_data ) {
			$exclude[] = $employee_id;
		}
	}

	?>
	<a id="mem-event-workers"></a>
	<div id="mem-event-workers-fields" class="mem-event-workers-sections-wrap">
		<div class="mem-custom-event-sections">
			<?php do_action( 'mem_event_overview_workers_top', $event_id ); ?>
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php printf( esc_html__( '%s Workers', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></span>

				<span class="mem-event-workers-role">
					<?php
					echo MEM()->html->roles_dropdown(
						array(
							'name'   => 'event_new_employee_role',
							'chosen' => true,
						)
					);
					?>
				</span>

				<span class="mem-event-workers-employee">
					<?php
					echo MEM()->html->employee_dropdown(
						array(
							'name'        => 'event_new_employee',
							'exclude'     => $exclude,
							'group'       => true,
							'chosen'      => true,
							'placeholder' => __( 'Select an Employee', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<?php if ( mem_get_option( 'enable_employee_payments' ) && mem_employee_can( 'manage_txns' ) ) : ?>
					<span class="mem-event-workers-wage">
						<?php
						echo MEM()->html->text(
							array(
								'name'        => 'event_new_employee_wage',
								'class'       => 'mem-currency',
								'placeholder' => esc_html( mem_sanitize_amount( '0' ) ),
							)
						);
						?>
					</span>
				<?php endif; ?>

				<br />
				<span class="mem-event-worker-add">
					<a id="add_event_employee" class="button button-secondary button-small"><?php esc_html_e( 'Add', 'mobile-events-manager' ); ?></a>
				</span>

			</div>

			<?php do_action( 'mem_event_overview_workers', $event_id ); ?>
		</div>

		<div id="mem-event-employee-list">
			<?php mem_do_event_employees_list_table( $event_id ); ?>
		</div>

		<?php if ( mem_get_option( 'enable_employee_payments' ) && in_array( $mem_event->post_status, mem_get_option( 'employee_pay_status' ) ) && mem_employee_can( 'manage_txns' ) && ! mem_event_employees_paid( $event_id ) ) : ?>

			<div class="mem_field_wrap mem_form_fields">
				<p><a href="
				<?php
				echo wp_nonce_url(
					add_query_arg(
						array(
							'mem-action' => 'pay_event_employees',
							'event_id'    => $event_id,
						),
						admin_url( 'admin.php' )
					),
					'pay_event_employees',
					'mem_nonce'
				);
				?>
							" id="pay_event_employees" class="button button-primary button-small"><?php printf( esc_html__( 'Pay %s Employees', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></a></p>
			</div>

		<?php endif; ?>

	</div>

	<?php

} // mem_event_overview_metabox_event_workers_row
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_event_workers_row', 10 );

/**
 * Output the add event type section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_add_event_type_section( $event_id ) {

	global $mem_event, $mem_event_update;
	?>

	<div id="mem-add-event-type-fields" class="mem-add-event-type-sections-wrap">
		<div class="mem-custom-event-sections">
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php printf( esc_html__( 'New %s Type', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></span>

				<span class="mem-new-event-type">
					<label class="mem-event-type">
						<?php printf( esc_html__( '%s Type', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'event_type_name',
							'class' => 'mem-name-field large-text',
						)
					);
					?>

				</span>

				<span class="mem-add-event-type-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						sprintf( esc_html__( 'Add %s Type', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
						array( 'secondary', 'mem-add-event-type' ),
						'mem-add-event-type',
						false
					);
					?>
				</span>
			</div>
		</div>
	</div>
	<?php
} // mem_event_overview_metabox_add_event_type_section
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_add_event_type_section', 15 );

/**
 * Output the venue details section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_venue_details_section( $event_id ) {

	global $mem_event, $mem_event_update;

	$venue_id = $mem_event->get_venue_id();

	echo mem_do_venue_details_table( $venue_id, $event_id );

} // mem_event_overview_metabox_venue_details_section
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_venue_details_section', 20 );

/**
 * Output the add venue section
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_add_venue_section( $event_id ) {

	global $mem_event, $mem_event_update;

	$venue_id = $mem_event->data['venue_id'];

	if ( 'Manual' == $venue_id ) {
		$venue_id = $event_id;
	}

	$venue_name     = mem_get_event_venue_meta( $venue_id, 'name' );
	$venue_contact  = mem_get_event_venue_meta( $venue_id, 'contact' );
	$venue_email    = mem_get_event_venue_meta( $venue_id, 'email' );
	$venue_address1 = mem_get_event_venue_meta( $venue_id, 'address1' );
	$venue_address2 = mem_get_event_venue_meta( $venue_id, 'address2' );
	$venue_town     = mem_get_event_venue_meta( $venue_id, 'town' );
	$venue_county   = mem_get_event_venue_meta( $venue_id, 'county' );
	$venue_postcode = mem_get_event_venue_meta( $venue_id, 'postcode' );
	$venue_phone    = mem_get_event_venue_meta( $venue_id, 'phone' );
	$venue_address  = array( $venue_address1, $venue_address2, $venue_town, $venue_county, $venue_postcode );
	$section_title  = __( 'Add a New Venue', 'mobile-events-manager' );
	$add_save_label = __( 'Add', 'mobile-events-manager' );
	$employee_id    = $mem_event->data['employee_id'] ? $mem_event->data['employee_id'] : get_current_user_id();

	if ( $mem_event->ID == $venue_id ) {
		$section_title  = __( 'Manual Venue', 'mobile-events-manager' );
		$add_save_label = __( 'Save', 'mobile-events-manager' );
	}

	?>

	<div id="mem-add-venue-fields" class="mem-add-event-venue-sections-wrap">
		<div class="mem-custom-event-sections">
			<div class="mem-custom-event-section">
				<span class="mem-custom-event-section-title"><?php echo $section_title; ?></span>

				<span class="mem-add-venue-name">
					<label class="mem-venue-name">
						<?php esc_html_e( 'Venue', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'  => 'venue_name',
							'value' => $venue_name,
							'class' => 'mem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="mem-add-venue-contact">
					<label class="mem-venue-contact">
						<?php esc_html_e( 'Contact', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_contact',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_contact ) ? esc_attr( $venue_contact ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-email">
					<label class="mem-venue-email">
						<?php esc_html_e( 'Email Address', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_email',
							'class'       => 'mem-name-field large-text',
							'type'        => 'email',
							'value'       => ! empty( $venue_email ) ? esc_attr( $venue_email ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-phone">
					<label class="mem-venue-phone">
						<?php esc_html_e( 'Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_phone',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_phone ) ? esc_attr( $venue_phone ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-address1">
					<label class="mem-venue-address1">
						<?php esc_html_e( 'Address Line 1', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_address1',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_address1 ) ? esc_attr( $venue_address1 ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-address2">
					<label class="mem-venue-address2">
						<?php esc_html_e( 'Address Line 2', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_address2',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_address2 ) ? esc_attr( $venue_address2 ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-town">
					<label class="mem-venue-town">
						<?php esc_html_e( 'Town', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_town',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_town ) ? esc_attr( $venue_town ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-county">
					<label class="mem-venue-county">
						<?php esc_html_e( 'County', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_county',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_county ) ? esc_attr( $venue_county ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-postcode">
					<label class="mem-venue-postcode">
						<?php esc_html_e( 'Post Code', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo MEM()->html->text(
						array(
							'name'        => 'venue_postcode',
							'class'       => 'mem-name-field large-text',
							'value'       => ! empty( $venue_postcode ) ? esc_attr( $venue_postcode ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="mem-add-venue-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						sprintf( esc_html__( '%s Venue', 'mobile-events-manager' ), $add_save_label ),
						array( 'secondary', 'mem-add-venue' ),
						'mem-add-venue',
						false
					);
					?>
				</span>

				<?php do_action( 'mem_venue_details_table_after_save', $event_id ); ?>
				<?php do_action( 'mem_venue_details_travel_data', $venue_address, $employee_id ); ?>

			</div>
		</div>
	</div>
	<?php
} // mem_event_overview_metabox_add_venue_section
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_add_venue_section', 25 );

/**
 * Output the event travel costs hidden fields
 *
 * @since 1.4
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_venue_travel_section( $event_id ) {
	global $mem_event, $mem_event_update;

	$travel_fields = mem_get_event_travel_fields();

	foreach ( $travel_fields as $field ) :
		?>
		<?php $travel_data = mem_get_event_travel_data( $event_id, $field ); ?>
		<?php $value = ! empty( $travel_data ) ? $travel_data : ''; ?>
		<input type="hidden" name="travel_<?php echo $field; ?>" id="mem_travel_<?php echo $field; ?>" value="<?php echo $value; ?>" />
		<?php
	endforeach;

} // mem_event_overview_metabox_venue_travel_section
add_action( 'mem_event_overview_custom_event_sections', 'mem_event_overview_metabox_venue_travel_section', 30 );

/**
 * Output the event equipment and travel costs row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_equipment_travel_costs_row( $event_id ) {

	global $mem_event, $mem_event_update;

	?>

	<div class="mem-event-equipment-price-fields">

		<?php if ( mem_packages_enabled() ) : ?>

			<div class="mem-event-package-cost">
				<span class="mem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Package Costs', 'mobile-events-manager' );
					echo ' ' . mem_currency_symbol();
					?>
				</span>

				<?php
				echo MEM()->html->text(
					array(
						'name'        => '_mem_event_package_cost',
						'class'       => 'mem-currency',
						'placeholder' => esc_html( mem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( mem_sanitize_amount( $mem_event->data['package_price'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

			<div class="mem-event-addons-cost">
				<span class="mem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Addons Cost', 'mobile-events-manager' );
					echo ' ' . mem_currency_symbol();
					?>
				</span>

				<?php
				echo MEM()->html->text(
					array(
						'name'        => '_mem_event_addons_cost',
						'class'       => 'mem-currency',
						'placeholder' => esc_html( mem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( mem_sanitize_amount( $mem_event->data['addons_price'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

		<?php endif; ?>

		<?php if ( mem_get_option( 'travel_add_cost', false ) ) : ?>

			<div class="mem-event-travel-cost">
				<span class="mem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Travel Cost', 'mobile-events-manager' );
					echo ' ' . mem_currency_symbol();
					?>
				</span>

				<?php
				echo MEM()->html->text(
					array(
						'name'        => '_mem_event_travel_cost',
						'class'       => 'mem-currency',
						'placeholder' => esc_html( mem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( mem_sanitize_amount( $mem_event->data['travel_cost'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

		<?php endif; ?>

	</div>
	<?php

} // mem_event_overview_metabox_event_equipment_travel_costs_row
add_action( 'mem_event_overview_standard_event_price_sections', 'mem_event_overview_metabox_event_equipment_travel_costs_row', 10 );

/**
 * Output the event discount and deposit row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_discount_deposit_row( $event_id ) {

	global $mem_event, $mem_event_update;

	?>

	<div class="mem-event-price-fields">
		<div class="mem-event-additional-cost">
			<span class="mem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Additional Costs', 'mobile-events-manager' );
				echo ' ' . mem_currency_symbol();
				?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'        => '_mem_event_additional_cost',
					'class'       => 'mem-currency',
					'placeholder' => esc_html( '0.00' ),
					'value'       => esc_attr( $mem_event->data['additional_cost'] ),
				)
			);
			?>
		</div>

		<div class="mem-event-discount">
			<span class="mem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Discount', 'mobile-events-manager' );
				echo ' ' . mem_currency_symbol();
				?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'        => '_mem_event_discount',
					'class'       => 'mem-currency',
					'placeholder' => sanitize_text_field( '0.00' ),
					'value'       => sanitize_text_field( $mem_event->data['discount'] ),
				)
			);
			?>
		</div>

		<div class="mem-event-deposit">
			<span class="mem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Deposit', 'mobile-events-manager' );
				echo ' ' . mem_currency_symbol();
				?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'        => '_mem_event_deposit',
					'class'       => 'mem-currency',
					'placeholder' => esc_html( mem_sanitize_amount( '0.00' ) ),
					'value'       => $mem_event_update ? esc_attr( $mem_event->deposit ) : mem_calculate_deposit( $mem_event->price ),
				)
			);
			?>
		</div>

	</div>
	<?php

} // mem_event_overview_metabox_event_discount_deposit_row
add_action( 'mem_event_overview_standard_event_price_sections', 'mem_event_overview_metabox_event_discount_deposit_row', 20 );

/**
 * Output the event price row
 *
 * @since 1.5
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_overview_metabox_event_price_row( $event_id ) {

	global $mem_event, $mem_event_update;

	?>

	<div class="mem-event-price-fields">
		<div class="mem-event-cost">
			<span class="mem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Total Cost', 'mobile-events-manager' );
				echo ' ' . mem_currency_symbol();
				?>
			</span>

			<?php
			echo MEM()->html->text(
				array(
					'name'        => '_mem_event_cost',
					'class'       => 'mem-currency',
					'placeholder' => esc_html( mem_sanitize_amount( '0.00' ) ),
					'value'       => ! empty( $mem_event->price ) ? mem_sanitize_amount( $mem_event->price ) : '',
					'readonly'    => true,
				)
			);
			?>
		</div>

	</div>
	<?php

} // mem_event_overview_metabox_event_price_row
add_action( 'mem_event_overview_standard_event_price_sections', 'mem_event_overview_metabox_event_price_row', 30 );

/**
 * Output the event enquiry source row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_admin_enquiry_source_row( $event_id ) {

	global $mem_event, $mem_event_update;

	$enquiry_source = mem_get_enquiry_source( $event_id );

	?>
	<div class="mem_field_wrap mem_form_fields">
		<div class="mem_col">
			<label for="mem_enquiry_source"><?php esc_html_e( 'Enquiry Source:', 'mobile-events-manager' ); ?></label>
			<?php
			echo MEM()->html->enquiry_source_dropdown(
				'mem_enquiry_source',
				$enquiry_source ? $enquiry_source->term_id : ''
			);
			?>
		</div>
	</div>
	<?php
} // mem_event_metabox_admin_enquiry_source_row
add_action( 'mem_event_admin_fields', 'mem_event_metabox_admin_enquiry_source_row', 10 );

/**
 * Output the employee notes row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_admin_employee_notes_row( $event_id ) {

	global $mem_event, $mem_event_update;

	?>
	<div class="mem_field_wrap mem_form_fields">
		<?php
		echo MEM()->html->textarea(
			array(
				'label'       => sprintf( esc_html__( '%s Notes:', 'mobile-events-manager' ), mem_get_option( 'artist' ) ),
				'name'        => '_mem_event_dj_notes',
				'placeholder' => __( 'This information is not visible to clients', 'mobile-events-manager' ),
				'value'       => get_post_meta( $event_id, '_mem_event_dj_notes', true ),
			)
		);
		?>
	</div>

	<?php
} // mem_event_metabox_admin_employee_notes_row
add_action( 'mem_event_admin_fields', 'mem_event_metabox_admin_employee_notes_row', 30 );

/**
 * Output the admin notes row
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_admin_notes_row( $event_id ) {

	global $mem_event, $mem_event_update;

	if ( ! mem_is_admin() ) {
		return;
	}

	?>
	<div class="mem_field_wrap mem_form_fields">
		<?php
		echo MEM()->html->textarea(
			array(
				'label'       => __( 'Admin Notes:', 'mobile-events-manager' ),
				'name'        => '_mem_event_admin_notes',
				'placeholder' => __( 'This information is only visible to admins', 'mobile-events-manager' ),
				'value'       => get_post_meta( $event_id, '_mem_event_admin_notes', true ),
			)
		);
		?>
	</div>

	<?php
} // mem_event_metabox_admin_notes_row
add_action( 'mem_event_admin_fields', 'mem_event_metabox_admin_notes_row', 40 );

/**
 * Output the event transaction list table
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_txn_list_table( $event_id ) {

	global $mem_event, $mem_event_update;

	?>
	<p><strong><?php esc_html_e( 'All Transactions', 'mobile-events-manager' ); ?></strong> <span class="mem-small">(<a id="mem_txn_toggle" class="mem-fake"><?php esc_html_e( 'toggle', 'mobile-events-manager' ); ?></a>)</span></p>
	<div id="mem_event_txn_table" class="mem_meta_table_wrap">
		<?php mem_do_event_txn_table( $event_id ); ?>
	</div>
	<?php
} // mem_event_metabox_txn_list_table
add_action( 'mem_event_txn_fields', 'mem_event_metabox_txn_list_table', 10 );

/**
 * Output the event transaction list table
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_txn_add_new_row( $event_id ) {

	global $mem_event, $mem_event_update;

	mem_insert_datepicker(
		array(
			'id'       => 'mem_txn_display_date',
			'altfield' => 'mem_txn_date',
			'maxdate'  => 'today',
		)
	);

	?>

	<div id="mem-event-add-txn-table">
		<table id="mem_event_add_txn_table" class="widefat mem_event_add_txn_table mem_form_fields">
			<thead>
				<tr>
					<th colspan="3"><?php esc_html_e( 'Add Transaction', 'mobile-events-manager' ); ?> <a id="toggle_add_txn_fields" class="mem-small mem-fake"><?php esc_html_e( 'show form', 'mobile-events-manager' ); ?></a></th>
				</tr>
			</thead>

			<tbody class="mem-hidden">
				<tr>
					<td><label for="mem_txn_amount"><?php esc_html_e( 'Amount:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo mem_currency_symbol() .
						MEM()->html->text(
							array(
								'name'        => 'mem_txn_amount',
								'class'       => 'mem-input-currency',
								'placeholder' => esc_html( mem_sanitize_amount( '10' ) ),
							)
						);
						?>
						</td>

					<td><label for="mem_txn_display_date"><?php esc_html_e( 'Date:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo MEM()->html->text(
							array(
								'name'  => 'mem_txn_display_date',
								'class' => '',
							)
						) .
						MEM()->html->hidden(
							array(
								'name' => 'mem_txn_date',
							)
						);
						?>
						</td>

					<td><label for="mem_txn_amount"><?php esc_html_e( 'Direction:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo MEM()->html->select(
							array(
								'name'             => 'mem_txn_direction',
								'options'          => array(
									'In'  => __( 'Incoming', 'mobile-events-manager' ),
									'Out' => __( 'Outgoing', 'mobile-events-manager' ),
								),
								'show_option_all'  => false,
								'show_option_none' => false,
							)
						);
						?>
						</td>
				</tr>

				<tr>
					<td><span id="mem_txn_from_container"><label for="mem_txn_from"><?php esc_html_e( 'From:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo MEM()->html->text(
							array(
								'name'        => 'mem_txn_from',
								'class'       => '',
								'placeholder' => __( 'Leave empty if client', 'mobile-events-manager' ),
							)
						);
						?>
						</span>
						<span id="mem_txn_to_container" class="mem-hidden"><label for="mem_txn_to"><?php esc_html_e( 'To:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo MEM()->html->text(
							array(
								'name'        => 'mem_txn_to',
								'class'       => '',
								'placeholder' => __( 'Leave empty if client', 'mobile-events-manager' ),
							)
						);
						?>
						</span></td>

					<td><label for="mem_txn_for"><?php esc_html_e( 'For:', 'mobile-events-manager' ); ?></label><br />
						<?php echo MEM()->html->txn_type_dropdown(); ?></td>

					<td><label for="mem_txn_src"><?php esc_html_e( 'Paid via:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo MEM()->html->select(
							array(
								'name'             => 'mem_txn_src',
								'options'          => mem_get_txn_source(),
								'selected'         => mem_get_option( 'default_type', 'Cash' ),
								'show_option_all'  => false,
								'show_option_none' => false,
							)
						);
						?>
						</td>
				</tr>

				<?php if ( mem_get_option( 'manual_payment_cfm_template' ) ) : ?>

					<tr id="mem-txn-email">
						<td colspan="3">
						<?php
						echo MEM()->html->checkbox(
							array(
								'name'    => 'mem_manual_txn_email',
								'current' => mem_get_option( 'manual_payment_cfm_template' ) ? true : false,
								'class'   => 'mem-checkbox',
							)
						);
						?>
							<?php esc_html_e( 'Send manual payment confirmation email?', 'mobile-events-manager' ); ?></td>
					</tr>

				<?php endif; ?>

			</tbody>
		</table>

	</div>

	<p id="save-event-txn" class="mem-hidden"><a id="save_transaction" class="button button-primary button-small"><?php esc_html_e( 'Add Transaction', 'mobile-events-manager' ); ?></a></p>
	<?php
} // mem_event_metabox_txn_add_new_row
add_action( 'mem_event_txn_fields', 'mem_event_metabox_txn_add_new_row', 20 );

/**
 * Output the event journal table
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_history_journal_table( $event_id ) {

	global $mem_event, $mem_event_update;

	$journals = mem_get_journal_entries( $event_id );

	$count = count( $journals );
	$i     = 0;

	?>
	<div id="mem-event-journal-table">
		<strong><?php esc_html_e( 'Recent Journal Entries', 'mobile-events-manager' ); ?></strong>
		<table class="widefat mem_event_journal_table mem_form_fields">
			<thead>
				<tr>
					<th style="width: 20%"><?php esc_html_e( 'Date', 'mobile-events-manager' ); ?></th>
					<th><?php esc_html_e( 'Excerpt', 'mobile-events-manager' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php if ( $journals ) : ?>
					<?php foreach ( $journals as $journal ) : ?>
						<tr>
							<td><a href="<?php echo get_edit_comment_link( $journal->comment_ID ); ?>"><?php echo gmdate( mem_get_option( 'time_format' ) . ' ' . mem_get_option( 'short_date_format' ), strtotime( $journal->comment_date ) ); ?></a></td>
							<td><?php echo substr( $journal->comment_content, 0, 250 ); ?></td>
						</tr>
						<?php $i++; ?>

						<?php
						if ( $i >= 3 ) {
							break;}
						?>

					<?php endforeach; ?>
				<?php else : ?>
				<tr>
					<td colspan="2"><?php printf( esc_html__( 'There are no journal entries associated with this %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></td>
				</tr>
				<?php endif; ?>

			</tbody>

			<?php if ( $journals ) : ?>
				<tfoot>
					<tr>
						<td colspan="2"><span class="description">(<?php printf( wp_kses_post( 'Displaying the most recent %1$d entries of <a href="%2$s">%3$d total', 'mobile-events-manager' ), ( $count >= 3 ) ? 3 : $count, add_query_arg( array( 'p' => $event_id ), admin_url( 'edit-comments.php?p=5636' ) ), $count ); ?>)</span></td>
					</tr>
				</tfoot>
			<?php endif; ?>

		</table>
	</div>
	<?php
} // mem_event_metabox_history_journal_table
add_action( 'mem_event_history_fields', 'mem_event_metabox_history_journal_table', 10 );

/**
 * Output the event emails table
 *
 * @since 1.3.7
 * @global obj $mem_event MEM_Event class object
 * @global bool $mem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function mem_event_metabox_history_emails_table( $event_id ) {

	global $mem_event, $mem_event_update;

	if ( ! mem_get_option( 'track_client_emails' ) ) {
		return;
	}

	$emails = mem_event_get_emails( $event_id );
	$count  = count( $emails );
	$i      = 0;

	?>
	<div id="mem-event-emails-table">
		<strong><?php esc_html_e( 'Associated Emails', 'mobile-events-manager' ); ?></strong>
		<table class="widefat mem_event_emails_table mem_form_fields">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'mobile-events-manager' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'mobile-events-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'mobile-events-manager' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php if ( $emails ) : ?>
					<?php foreach ( $emails as $email ) : ?>
						<tr>
							<td><?php echo gmdate( mem_get_option( 'time_format' ) . ' ' . mem_get_option( 'short_date_format' ), strtotime( $email->post_date ) ); ?></td>
							<td><a href="<?php echo get_edit_post_link( $email->ID ); ?>"><?php echo get_the_title( $email->ID ); ?></a></td>
							<td>
							<?php
							echo get_post_status_object( $email->post_status )->label;

							if ( ! empty( $email->post_modified ) && 'opened' == $email->post_status ) :
								?>
								<?php echo '<br />'; ?>
								<span class="description"><?php echo gmdate( mem_get_option( 'time_format', 'H:i' ) . ' ' . mem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $email->post_modified ) ); ?></span>
							<?php endif; ?></td>
						</tr>
						<?php $i++; ?>

						<?php
						if ( $i >= 3 ) {
							break;}
						?>

					<?php endforeach; ?>
				<?php else : ?>
				<tr>
					<td colspan="3"><?php printf( esc_html__( 'There are no emails associated with this %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></td>
				</tr>
				<?php endif; ?>

			</tbody>

			<?php if ( $emails ) : ?>
				<tfoot>
					<tr>
						<td colspan="3"><span class="description">(<?php printf( esc_html__( 'Displaying the most recent %1$d emails of %2$d total', 'mobile-events-manager' ), ( $count >= 3 ) ? 3 : $count, $count ); ?>)</span></td>
					</tr>
				</tfoot>
			<?php endif; ?>

		</table>
	</div>
	<?php
} // mem_event_metabox_emails_table
add_action( 'mem_event_history_fields', 'mem_event_metabox_history_emails_table', 20 );
