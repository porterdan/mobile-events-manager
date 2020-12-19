<?php

/**
 * Contains all metabox functions for the tmem-event post type
 *
 * @package TMEM
 * @subpackage Events
 * @since 1.3
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
 * @param obj  $tmem_event The event object
 * @param bool $tmem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function tmem_client_details_get_action_links( $event_id, $tmem_event, $tmem_event_update ) {

	$actions = array();

	if ( ! empty( $tmem_event->client ) && tmem_employee_can( 'view_clients_list' ) ) {
		$actions['view_client'] = '<a href="#" class="toggle-client-details-option-section">' . __( 'Show client details', 'mobile-events-manager' ) . '</a>';
	}

	$actions['add_client'] = '<a id="add-client-action" href="#" class="toggle-client-add-option-section">' . __( 'Show client form', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'tmem_event_metabox_client_details_actions', $actions, $event_id, $tmem_event, $tmem_event_update );
	return $actions;
} // tmem_client_details_get_action_links

/**
 * Generate the event details action links.
 *
 * @since 1.5
 * @param int  $event_id The event ID
 * @param obj  $tmem_event The event object
 * @param bool $tmem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function tmem_event_details_get_action_links( $event_id, $tmem_event, $tmem_event_update ) {

	$venue_id = $tmem_event->get_venue_id();
	$actions  = array(
		'event_options' => '<a href="#" class="toggle-event-options-section">' . sprintf( esc_html__( 'Show %s options', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) . '</a>',
	);

	// Event workers
	if ( tmem_is_employer() ) {
		$actions['event_workers'] = '<a href="#" class="toggle-add-worker-section">' . sprintf( esc_html__( 'Show %s workers', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) . '</a>';
	}

	// New event type
	if ( tmem_is_admin() ) {
		$actions['event_type'] = '<a href="#" class="toggle-event-type-option-section">' . sprintf( esc_html__( 'Add %s type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) . '</a>';
	}

	// Venues
	$actions['view_venue'] = '<a href="#" class="toggle-event-view-venue-option-section">' . __( 'Show venue', 'mobile-events-manager' ) . '</a>';
	$actions['add_venue']  = '<a href="#" class="toggle-event-add-venue-option-section">' . __( 'Add venue', 'mobile-events-manager' ) . '</a>';

	$actions = apply_filters( 'tmem_event_metabox_event_details_actions', $actions, $event_id, $tmem_event, $tmem_event_update );
	return $actions;
} // tmem_event_details_get_action_links

/**
 * Generate the event pricing action links.
 *
 * @since 1.5
 * @param int  $event_id The event ID
 * @param obj  $tmem_event The event object
 * @param bool $tmem_event_update Whether an event is being updated
 * @return arr Array of action links
 */
function tmem_event_pricing_get_action_links( $event_id, $tmem_event, $tmem_event_update ) {

	$actions = array();

	$actions = apply_filters( 'tmem_event_pricing_actions', $actions, $event_id, $tmem_event, $tmem_event_update );
	return $actions;
} // tmem_event_pricing_get_action_links

/**
 * Remove unwanted metaboxes to for the tmem-event post type.
 * Apply the `tmem_event_remove_metaboxes` filter to allow for filtering of metaboxes to be removed.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_remove_event_meta_boxes() {
	$metaboxes = apply_filters(
		'tmem_event_remove_metaboxes',
		array(
			array( 'submitdiv', 'tmem-event', 'side' ),
			array( 'event-typesdiv', 'tmem-event', 'side' ),
			array( 'tagsdiv-enquiry-source', 'tmem-event', 'side' ),
			array( 'commentsdiv', 'tmem-event', 'normal' ),
		)
	);

	foreach ( $metaboxes as $metabox ) {
		remove_meta_box( $metabox[0], $metabox[1], $metabox[2] );
	}
} // tmem_remove_event_meta_boxes
add_action( 'admin_head', 'tmem_remove_event_meta_boxes' );

/**
 * Define and add the metaboxes for the tmem-event post type.
 * Apply the `tmem_event_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_add_event_meta_boxes( $post ) {

	global $tmem_event, $tmem_event_update;

	$save              = __( 'Create', 'mobile-events-manager' );
	$tmem_event_update = false;
	$tmem_event        = new TMEM_Event( $post->ID );
	$tmem_event->get_event_data();

	if ( 'draft' != $post->post_status && 'auto-draft' != $post->post_status ) {
		$tmem_event_update = true;
	}

	$metaboxes = apply_filters(
		'tmem_event_add_metaboxes',
		array(
			array(
				'id'         => 'tmem-event-save-mb',
				'title'      => sprintf( esc_html__( '%1$s #%2$s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), $tmem_event->data['contract_id'] ),
				'callback'   => 'tmem_event_metabox_save_callback',
				'context'    => 'side',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-event-tasks-mb',
				'title'      => __( 'Tasks', 'mobile-events-manager' ),
				'callback'   => 'tmem_event_metabox_tasks_callback',
				'context'    => 'side',
				'priority'   => 'default',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-event-overview-mb',
				'title'      => sprintf( esc_html__( '%s Overview', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'callback'   => 'tmem_event_metabox_overview_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-event-admin-mb',
				'title'      => __( 'Administration', 'mobile-events-manager' ),
				'callback'   => 'tmem_event_metabox_admin_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-event-transactions-mb',
				'title'      => __( 'Transactions', 'mobile-events-manager' ),
				'callback'   => 'tmem_event_metabox_transactions_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'edit_txns',
			),
			array(
				'id'         => 'tmem-event-history-mb',
				'title'      => sprintf( esc_html__( '%s History', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'callback'   => 'tmem_event_metabox_history_callback',
				'context'    => 'normal',
				'priority'   => 'low',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'manage_tmem',
			),
		)
	);
	// Runs before metabox output
	do_action( 'tmem_event_before_metaboxes' );

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
			'tmem-event',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'tmem_event_after_metaboxes' );
} // tmem_add_event_meta_boxes
add_action( 'add_meta_boxes_tmem-event', 'tmem_add_event_meta_boxes' );

/**
 * Output for the Event Options meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_save_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	?>

	<div class="submitbox" id="submitpost">
		<div id="minor-publishing">
			<div id="tmem-event-actions">

				<?php
				/*
				 * Output the items for the options metabox
				 * These items go inside the tmem-event-actions div
				 * @since	1.3.7
				 * @param	int	$post_id	The Event post ID
				 */
				do_action( 'tmem_event_options_fields', $post->ID );
				?>
			</div><!-- #tmem-event-actions -->
		</div><!-- #minor-publishing -->
	</div><!-- #submitpost -->
	<?php
	do_action( 'tmem_event_options_fields_save', $post->ID );

} // tmem_event_metabox_save_callback

/**
 * Output for the Event Tasks meta box.
 *
 * @since 1.5
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_tasks_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	/*
	 * Output the items for the options metabox
	 * These items go inside the tmem-event-actions div
	 * @since 1.5
	 * @param int $post_id The Event post ID
	 */
	do_action( 'tmem_event_tasks_fields', $post->ID );

} // tmem_event_metabox_tasks_callback

/**
 * Output for the Event Overview meta box.
 *
 * @since 1.5
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_overview_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	/*
	 * Output the items for the event overview metabox
	 * @since	1.5
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'tmem_event_overview_fields', $post->ID );

} // tmem_event_metabox_overview_callback

/**
 * Output for the Event Administration meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_admin_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	/*
	 * Output the items for the event admin metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'tmem_event_admin_fields', $post->ID );

} // tmem_event_metabox_admin_callback

/**
 * Output for the Event History meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_history_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	/*
	 * Output the items for the event history metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'tmem_event_history_fields', $post->ID );

} // tmem_event_metabox_history_callback

/**
 * Output for the Event Transactions meta box.
 *
 * @since 1.3
 * @global obj $post WP_Post object
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_event_metabox_transactions_callback( $post ) {

	global $post, $tmem_event, $tmem_event_update;

	/*
	 * Output the items for the event transactions metabox
	 * @since	1.3.7
	 * @param	int	$post_id	The Event post ID
	 */
	do_action( 'tmem_event_txn_fields', $post->ID );

} // tmem_event_metabox_transactions_callback

/**
 * Output the event options type row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_options_status_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$contract        = $tmem_event->get_contract();
	$contract_status = $tmem_event->get_contract_status();

	echo TMEM()->html->event_status_dropdown( 'tmem_event_status', $tmem_event->post_status );

} // tmem_event_metabox_options_status_row
add_action( 'tmem_event_options_fields', 'tmem_event_metabox_options_status_row', 10 );

/**
 * Output the event options payments row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_options_payments_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	if ( ! tmem_employee_can( 'edit_txns' ) ) {
		return;
	}

	$deposit_status = __( 'Due', 'mobile-events-manager' );
	$balance_status = __( 'Due', 'mobile-events-manager' );

	if ( $tmem_event_update && 'tmem-unattended' != $tmem_event->post_status ) {
		$deposit_status = $tmem_event->get_deposit_status();
		$balance_status = $tmem_event->get_balance_status();
	}

	?>

	<p><strong><?php esc_html_e( 'Payments', 'mobile-events-manager' ); ?></strong></p>

	<p>
	<?php
	echo TMEM()->html->checkbox(
		array(
			'name'    => 'deposit_paid',
			'value'   => 'Paid',
			'current' => $deposit_status,
		)
	);
	?>
		 <?php printf( esc_html__( '%s Paid?', 'mobile-events-manager' ), tmem_get_deposit_label() ); ?></p>

	<p>
	<?php
	echo TMEM()->html->checkbox(
		array(
			'name'    => 'balance_paid',
			'value'   => 'Paid',
			'current' => $balance_status,
		)
	);
	?>
		 <?php printf( esc_html__( '%s Paid?', 'mobile-events-manager' ), tmem_get_balance_label() ); ?></p>

	<?php

} // tmem_event_metabox_options_payments_row
add_action( 'tmem_event_options_fields', 'tmem_event_metabox_options_payments_row', 30 );

/**
 * Output the event options save row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_options_save_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	if ( ! tmem_employee_can( 'manage_events' ) ) {
		return;
	}

	$button_text = __( 'Add %s', 'mobile-events-manager' );

	if ( $tmem_event_update ) {
		$button_text = __( 'Update %s', 'mobile-events-manager' );
	}

	$class = '';
	$url   = add_query_arg( array( 'post_type' => 'tmem-event' ), admin_url( 'edit.php' ) );
	$a     = sprintf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) );

	if ( tmem_employee_can( 'manage_all_events' ) && ( ! $tmem_event_update || 'tmem-unattended' == $tmem_event->post_status ) ) {
		$class = 'tmem-delete';
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
		$a     = sprintf( esc_html__( 'Delete %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) );
	}

	?>
	<div id="major-publishing-actions">
		<div id="delete-action">
			<a class="<?php echo $class; ?>" href="<?php echo $url; ?>"><?php echo $a; ?></a>
		</div>

		<div id="publishing-action">
			<?php
			submit_button(
				sprintf( $button_text, esc_html( tmem_get_label_singular() ) ),
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

} // tmem_event_metabox_options_save_row
add_action( 'tmem_event_options_fields_save', 'tmem_event_metabox_options_save_row', 40 );

/**
 * Output the event tasks row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_tasks_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$completed_tasks = $tmem_event->get_tasks();
	$tasks_history   = array();
	$tasks           = tmem_get_tasks_for_event( $event_id );

	foreach ( $completed_tasks as $task_slug => $run_time ) {
		if ( ! array_key_exists( $task_slug, $tasks ) ) {
			continue;
		}

		$tasks_history[] = sprintf(
			'%s: %s',
			tmem_get_task_name( $task_slug ),
			date( tmem_get_option( 'short_date_format' ), $run_time )
		);
	}

	if ( ! empty( $tasks_history ) ) {
		$history_class = '';
		$history       = implode( '<br>', $tasks_history );
	} else {
		$history_class = ' description';
		$history       = sprintf( esc_html__( 'None of the available tasks have been executed for this %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) );
	}

	?>
	<div id="tmem-event-tasks">
		<?php if ( ! $tmem_event_update || empty( $tasks ) ) : ?>
			<span class="description">
				<?php printf( esc_html__( 'No tasks are available for this %s.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?>
			</span>
			<?php
		else :

			foreach ( $tasks as $id => $name ) :
				?>
				<?php $options[ $id ] = $name; ?>
			<?php endforeach; ?>

			<?php
			echo TMEM()->html->select(
				array(
					'options'          => $options,
					'name'             => 'tmem_event_task',
					'id'               => 'tmem-event-task',
					'chosen'           => true,
					'placeholder'      => __( 'Select a Task', 'mobile-events-manager' ),
					'show_option_none' => __( 'Select a Task', 'mobile-events-manager' ),
				)
			);
			?>

			<div id="tmem-event-task-run" class="tmem-hidden">
				<p class="tmem-execute-event-task">
				<?php
				submit_button(
					__( 'Run Task', 'mobile-events-manager' ),
					array( 'secondary', 'tmem-run-event-task' ),
					'tmem-run-task',
					false
				);
				?>
				</p>
				<span id="tmem-spinner" class="spinner tmem-execute-event-task"></span>
			</div>

			<p><strong><?php esc_html_e( 'Completed Tasks', 'mobile-events-manager' ); ?></strong></p>
			<span class="task-history-items<?php echo $history_class; ?>"><?php echo $history; ?></span>
		<?php endif; ?>
	</div>

	<?php

} // tmem_event_metabox_tasks_row
add_action( 'tmem_event_tasks_fields', 'tmem_event_metabox_tasks_row', 10 );

/**
 * Output the event client sections
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_client_sections( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>
	<div id="tmem_event_overview_client_fields" class="tmem_meta_table_wrap">

		<div class="widefat tmem_repeatable_table">

			<div class="tmem-client-option-fields tmem-repeatables-wrap">

				<div class="tmem_event_overview_wrapper">

					<div class="tmem-client-row-header">
						<span class="tmem-repeatable-row-title">
							<?php esc_html_e( 'Client Details', 'mobile-events-manager' ); ?>
						</span>

						<?php
						$actions = tmem_client_details_get_action_links( $event_id, $tmem_event, $tmem_event_update );
						?>

						<span class="tmem-repeatable-row-actions">
							<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
						</span>
					</div>

					<div class="tmem-repeatable-row-standard-fields">
						<?php do_action( 'tmem_event_overview_standard_client_sections', $event_id ); ?>
					</div>
					<?php do_action( 'tmem_event_overview_custom_client_sections', $event_id ); ?>
				</div>

			</div>

		</div>

	</div>
	<?php

} // tmem_event_overview_metabox_client_sections
add_action( 'tmem_event_overview_fields', 'tmem_event_overview_metabox_client_sections', 10 );

/**
 * Output the event sections
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_sections( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$singular = esc_html( tmem_get_label_singular() );

	?>
	<div id="tmem_event_overview_event_fields" class="tmem_meta_table_wrap">

		<div class="widefat tmem_repeatable_table">
			<div class="tmem-event-option-fields tmem-repeatables-wrap">
				<div class="tmem_event_overview_wrapper">
					<div class="tmem-event-row-header">
						<span class="tmem-repeatable-row-title">
							<?php printf( esc_html__( '%s Details', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?>
						</span>

						<?php
						$actions = tmem_event_details_get_action_links( $event_id, $tmem_event, $tmem_event_update );
						?>

						<span class="tmem-repeatable-row-actions">
							<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
						</span>
					</div>

					<div class="tmem-repeatable-row-standard-fields">
						<?php do_action( 'tmem_event_overview_standard_event_sections', $event_id ); ?>
					</div>
					<?php do_action( 'tmem_event_overview_custom_event_sections', $event_id ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

} // tmem_event_overview_metabox_event_sections
add_action( 'tmem_event_overview_fields', 'tmem_event_overview_metabox_event_sections', 20 );

/**
 * Output the event price sections
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_price_sections( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$singular = esc_html( tmem_get_label_singular() );

	if ( tmem_employee_can( 'edit_txns' ) ) :
		?>

		<div id="tmem_event_overview_event_price_fields" class="tmem_meta_table_wrap">

			<div class="widefat tmem_repeatable_table">
				<div class="tmem-event-option-fields tmem-repeatables-wrap">
					<div class="tmem_event_overview_wrapper">
						<div class="tmem-event-row-header">
							<span class="tmem-repeatable-row-title">
								<?php esc_html_e( 'Pricing', 'mobile-events-manager' ); ?>
							</span>

							<?php
							$actions = tmem_event_pricing_get_action_links( $event_id, $tmem_event, $tmem_event_update );
							?>

							<span class="tmem-repeatable-row-actions">
								<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
							</span>
						</div>

						<div id="tmem-event-pricing-detail" class="tmem-repeatable-row-standard-fields">
							<?php do_action( 'tmem_event_overview_standard_event_price_sections', $event_id ); ?>
						</div>
						<?php do_action( 'tmem_event_overview_custom_event_price_sections', $event_id ); ?>
					</div>
				</div>
			</div>
		</div>

	<?php else : ?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_package_cost',
				'value' => ! empty( $tmem_event->package_price ) ? tmem_sanitize_amount( $tmem_event->package_price ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_addons_cost',
				'value' => ! empty( $tmem_event->addons_price ) ? tmem_sanitize_amount( $tmem_event->addons_price ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_travel_cost',
				'value' => ! empty( $tmem_event->travel_cost ) ? tmem_sanitize_amount( $tmem_event->travel_cost ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_additional_cost',
				'value' => ! empty( $tmem_event->additional_cost ) ? tmem_sanitize_amount( $tmem_event->additional_cost ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_discount',
				'value' => ! empty( $tmem_event->discount ) ? tmem_sanitize_amount( $tmem_event->discount ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_deposit',
				'value' => $tmem_event_update ? tmem_sanitize_amount( $tmem_event->deposit ) : '',
			)
		);
		?>

		<?php
		echo TMEM()->html->hidden(
			array(
				'name'  => '_tmem_event_cost',
				'value' => ! empty( $tmem_event->price ) ? tmem_sanitize_amount( $tmem_event->price ) : '',
			)
		);
		?>

		<?php
	endif;

} // tmem_event_overview_metabox_event_price_sections
add_action( 'tmem_event_overview_fields', 'tmem_event_overview_metabox_event_price_sections', 30 );

/**
 * Output the client name row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_client_name_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$block_emails = false;

	if ( 'tmem-enquiry' == $tmem_event->post_status && ! tmem_get_option( 'contract_to_client' ) ) {
		$block_emails = true;
	}

	if ( 'tmem-enquiry' == $tmem_event->post_status && ! tmem_get_option( 'booking_conf_to_client' ) ) {
		$block_emails = true;
	}
	?>

 <div class="tmem-client-name">
		<span class="tmem-repeatable-row-setting-label"><?php esc_html_e( 'Client', 'mobile-events-manager' ); ?></span>

		<?php if ( tmem_event_is_active( $event_id ) ) : ?>

			<?php $clients = tmem_get_clients( 'client' ); ?>

			<?php
			echo TMEM()->html->client_dropdown(
				array(
					'selected'         => $tmem_event->client,
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
			echo TMEM()->html->text(
				array(
					'name'     => 'client_name_display',
					'value'    => tmem_get_client_display_name( $tmem_event->client ),
					'readonly' => true,
				)
			);
			?>

			<?php
			echo TMEM()->html->hidden(
				array(
					'name'  => 'client_name',
					'value' => $tmem_event->client,
				)
			);
			?>

		<?php endif; ?>
	</div>

	<?php if ( tmem_event_is_active( $tmem_event->ID ) && 'tmem-completed' != $tmem_event->post_status && 'tmem-approved' != $tmem_event->post_status ) : ?>
		<div class="tmem-repeatable-option tmem_repeatable_default_wrapper">

			<span class="tmem-repeatable-row-setting-label"><?php esc_html_e( 'Disable Emails?', 'mobile-events-manager' ); ?></span>
			<label class="tmem-block-email">
				<?php
				echo TMEM()->html->checkbox(
					array(
						'name'    => 'tmem_block_emails',
						'current' => $block_emails,
					)
				);
				?>
				<span class="screen-reader-text"><?php printf( esc_html__( 'Block update emails for this %s to the client', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?></span>
			</label>

		</div>
	<?php endif; ?>

	<?php if ( ! $tmem_event_update || 'tmem-unattended' == $tmem_event->post_status ) : ?>
		<div class="tmem-repeatable-option tmem_repeatable_default_wrapper">

			<span class="tmem-repeatable-row-setting-label"><?php esc_html_e( 'Reset Password?', 'mobile-events-manager' ); ?></span>
			<label class="tmem-reset-password">
				<?php
				echo TMEM()->html->checkbox(
					array(
						'name'  => 'tmem_reset_pw',
						'value' => 'Y',
					)
				);
				?>
				<span class="screen-reader-text"><?php printf( esc_html__( 'Click to reset the client password during %s update', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?></span>
			</label>

		</div>
		<?php
	endif;

} // tmem_event_overview_metabox_client_name_row
add_action( 'tmem_event_overview_standard_client_sections', 'tmem_event_overview_metabox_client_name_row', 10 );

/**
 * Output the client name row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_client_templates_row( $event_id ) {

	global $tmem_event, $tmem_event_update;
	?>

	<?php if ( ! $tmem_event_update || 'tmem-unattended' === $tmem_event->post_status ) : ?>
		<div class="tmem-client-template-fields">
			<div class="tmem-quote-template">
				<span class="tmem-repeatable-row-setting-label"><?php esc_html_e( 'Quote Template', 'mobile-events-manager' ); ?></span>
				<?php
				echo TMEM()->html->select(
					array(
						'name'     => 'tmem_email_template',
						'options'  => tmem_list_templates( 'email_template' ),
						'selected' => tmem_get_option( 'enquiry' ),
						'chosen'   => true,
						'data'     => array(
							'search-type'        => 'template',
							'search-placeholder' => __( 'Type to search all templates', 'mobile-events-manager' ),
						),
					)
				);
				?>
			</div>

			<?php if ( tmem_get_option( 'online_enquiry', false ) ) : ?>

				<div class="tmem-online-template">
					<span class="tmem-repeatable-row-setting-label"><?php esc_html_e( 'Online Quote', 'mobile-events-manager' ); ?></span>
					<?php
					echo TMEM()->html->select(
						array(
							'name'     => 'tmem_online_quote',
							'options'  => tmem_list_templates( 'email_template' ),
							'selected' => tmem_get_option( 'online_enquiry' ),
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

} // tmem_event_overview_metabox_client_templates_row
add_action( 'tmem_event_overview_standard_client_sections', 'tmem_event_overview_metabox_client_templates_row', 20 );

/**
 * Output the add client section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_add_client_section( $event_id ) {

	global $tmem_event, $tmem_event_update;
	?>

	<div id="tmem-add-client-fields" class="tmem-client-add-event-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php esc_html_e( 'Add a New Client', 'mobile-events-manager' ); ?></span>

				<span class="tmem-client-new-first">
					<label class="tmem-client-first">
						<?php esc_html_e( 'First Name', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'client_firstname',
							'class' => 'tmem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="tmem-client-new-last">
					<label class="tmem-client-last">
						<?php esc_html_e( 'Last Name', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_lastname',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-email">
					<label class="tmem-client-email">
						<?php esc_html_e( 'Email Address', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'client_email',
							'class' => 'tmem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="tmem-client-new-address1">
					<label class="tmem-client-address1">
						<?php esc_html_e( 'Address Line 1', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_address1',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-address2">
					<label class="tmem-client-address2">
						<?php esc_html_e( 'Address Line 2', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_address2',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-town">
					<label class="tmem-client-town">
						<?php esc_html_e( 'Town', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_town',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-county">
					<label class="tmem-client-county">
						<?php esc_html_e( 'County', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_county',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-postcode">
					<label class="tmem-client-postcode">
						<?php esc_html_e( 'Postal Code', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_postcode',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-phone">
					<label class="tmem-client-phone">
						<?php esc_html_e( 'Primary Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_phone',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-client-new-alt-phone">
					<label class="tmem-client-phone">
						<?php esc_html_e( 'Alternative Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'client_phone2',
							'class'       => 'tmem-name-field large-text',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-client-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						__( 'Add Client', 'mobile-events-manager' ),
						array( 'secondary', 'tmem-add-client' ),
						'tmem-add-client',
						false
					);
					?>
				</span>

			</div>
		</div>
	</div>
	<?php

} // tmem_event_overview_metabox_add_client_section
add_action( 'tmem_event_overview_custom_client_sections', 'tmem_event_overview_metabox_add_client_section', 10 );

/**
 * Output the client details section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_client_details_section( $event_id ) {

	global $tmem_event, $tmem_event_update;

	if ( empty( $tmem_event->client ) || ! tmem_employee_can( 'view_clients_list' ) ) {
		return;
	}

	$client = get_userdata( $tmem_event->data['client'] );

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
	 <div class="tmem-client-details-event-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php printf( esc_html__( 'Contact Details for %s', 'mobile-events-manager' ), $client->display_name ); ?></span>

				<?php if ( ! empty( $phone_numbers ) ) : ?>
					<span class="tmem-client-telephone">
						<i class="fa fa-phone" aria-hidden="true" title="<?php esc_html_e( 'Phone', 'mobile-events-manager' ); ?>"></i> <?php echo implode( '&nbsp;&#124;&nbsp;', $phone_numbers ); ?>
					</span>
				<?php endif; ?>

				<span class="tmem-client-email">
					<i class="fa fa-envelope-o" aria-hidden="true" title="<?php esc_html_e( 'Email', 'mobile-events-manager' ); ?>"></i>&nbsp;
					<a href="
					<?php
					echo add_query_arg(
						array(
							'recipient' => $client->ID,
							'event_id'  => $event_id,
						),
						admin_url( 'admin.php?page=tmem-comms' )
					);
					?>
								">
						<?php echo $client->user_email; ?>
					</a>
				</span>

				<span class="tmem-client-address">
					<?php echo tmem_get_client_full_address( $client->ID ); ?>
				</span>

				<span class="tmem-client-login">
					<i class="fa fa-sign-in" aria-hidden="true" title="<?php esc_html_e( 'Last Login', 'mobile-events-manager' ); ?>"></i> <?php echo tmem_get_client_last_login( $client->ID ); ?>
				</span>
			</div>
		</div>
	</div>
	<?php

} // tmem_event_overview_metabox_client_details_section
add_action( 'tmem_event_overview_custom_client_sections', 'tmem_event_overview_metabox_client_details_section', 20 );

/**
 * Output the primary employee row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_primary_employee_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$employee_id    = $tmem_event->data['employee_id'] ? $tmem_event->data['employee_id'] : get_current_user_id();
	$payment_status = $tmem_event->data['primary_employee_payment_status'];
	$artist         = esc_attr( tmem_get_option( 'artist' ) );

	if ( isset( $_GET['primary_employee'] ) ) {
		$employee_id = sanitize_key( wp_unslash( $_GET['primary_employee'] ) );
	}

	?>
	<div class="tmem-event-employee-fields">
		<div class="tmem-event-primary-employee">
			<span class="tmem-repeatable-row-setting-label">
				<?php printf( '%s', $artist ); ?>
			</span>

			<?php
			echo TMEM()->html->employee_dropdown(
				array(
					'selected'    => $employee_id,
					'group'       => tmem_is_employer() ? true : false,
					'chosen'      => true,
					'placeholder' => sprintf( esc_html__( 'Select %s', 'mobile-events-manager' ), $artist ),
				)
			);
			?>
		</div>

		<?php if ( tmem_get_option( 'enable_employee_payments' ) && tmem_employee_can( 'edit_txns' ) ) : ?>

			<?php $wage = tmem_get_employees_event_wage( $event_id, $employee_id ); ?>

			<div class="tmem-event-employee-wage">
				<span class="tmem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Wage', 'mobile-events-manager' );
					echo ' ' . tmem_currency_symbol();
					?>
				</span>

				<?php
				echo TMEM()->html->text(
					array(
						'name'        => '_tmem_event_dj_wage',
						'class'       => 'tmem-currency',
						'value'       => ! empty( $wage ) ? $wage : '',
						'placeholder' => esc_html( tmem_sanitize_amount( '0' ) ),
						'readonly'    => $payment_status ? true : false,
					)
				);
				?>

			</div>

		<?php endif; ?>
	</div>
	<?php

} // tmem_event_overview_metabox_primary_employee_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_primary_employee_row', 5 );

/**
 * Output the event type, contract and venue row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_type_contract_venue_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$event_type      = tmem_get_event_type( $event_id, true );
	$contract        = $tmem_event->data['contract'];
	$contract_status = $tmem_event->data['contract_status'];
	$venue_id        = $tmem_event->data['venue_id'];

	if ( ! $event_type ) {
		$event_type = tmem_get_option( 'event_type_default', '' );
	}

	if ( ! empty( $venue_id ) && $venue_id == $event_id ) {
		$venue_id = 'manual';
	}

	?>
	<div class="tmem-event-type-fields">
		<div class="tmem-event-type">
			<span class="tmem-repeatable-row-setting-label">
				<?php printf( esc_html__( '%s Type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?>
			</span>

			<?php
			echo TMEM()->html->event_type_dropdown(
				array(
					'name'     => 'tmem_event_type',
					'chosen'   => true,
					'selected' => $event_type,
				)
			);
			?>
		</div>

		<div class="tmem-event-contract">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Contract', 'mobile-events-manager' ); ?>
			</span>

			<?php if ( ! $contract_status ) : ?>

				<?php
				echo TMEM()->html->select(
					array(
						'name'     => '_tmem_event_contract',
						'options'  => tmem_list_templates( 'contract' ),
						'chosen'   => true,
						'selected' => ! empty( $contract ) ? $contract : tmem_get_option( 'default_contract' ),
						'data'     => array(
							'search-type'        => 'contract',
							'search-placeholder' => __( 'Type to search all contracts', 'mobile-events-manager' ),
						),
					)
				);
				?>

			<?php else : ?>

				<?php if ( tmem_employee_can( 'manage_events' ) ) : ?>
					<a id="view_contract" href="
					<?php
					echo esc_url(
						add_query_arg(
							array(
								'tmem_action' => 'review_contract',
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

		<div class="tmem-event-venue">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Venue', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->venue_dropdown(
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
} // tmem_event_overview_metabox_event_type_contract_venue_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_event_type_contract_venue_row', 15 );

/**
 * Output the event date rows
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_dates_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$finish_date = $tmem_event->data['finish_date'];
	$setup_date  = $tmem_event->data['setup_date'];

	tmem_insert_datepicker(
		array(
			'id' => 'display_event_date',
		)
	);

	tmem_insert_datepicker(
		array(
			'id'       => 'display_event_finish_date',
			'altfield' => '_tmem_event_end_date',
		)
	);

	tmem_insert_datepicker(
		array(
			'id'       => 'dj_setup_date',
			'altfield' => '_tmem_event_djsetup',
		)
	);

	?>
	<div class="tmem-event-date-fields">
		<div class="tmem-event-date">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Date', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'     => 'display_event_date',
					'class'    => 'tmem_date',
					'required' => true,
					'value'    => ! empty( $tmem_event->data['date'] ) ? tmem_format_short_date( $tmem_event->data['date'] ) : '',
				)
			);
			?>
			<?php
			echo TMEM()->html->hidden(
				array(
					'name'  => '_tmem_event_date',
					'value' => ! empty( $tmem_event->data['date'] ) ? $tmem_event->data['date'] : '',
				)
			);
			?>
		</div>

		<div class="tmem-event-finish-date">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'End', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'     => 'display_event_finish_date',
					'class'    => 'tmem_date',
					'required' => false,
					'value'    => ! empty( $finish_date ) ? tmem_format_short_date( $finish_date ) : '',
				)
			);
			?>
			<?php
			echo TMEM()->html->hidden(
				array(
					'name'  => '_tmem_event_end_date',
					'value' => ! empty( $finish_date ) ? $finish_date : '',
				)
			);
			?>
		</div>

		<div class="tmem-event-setup-date">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Setup', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'  => 'dj_setup_date',
					'class' => 'tmem_setup_date',
					'value' => $setup_date ? tmem_format_short_date( $setup_date ) : '',
				)
			);
			?>
			<?php
			echo TMEM()->html->hidden(
				array(
					'name'  => '_tmem_event_djsetup',
					'value' => $setup_date ? $setup_date : '',
				)
			);
			?>
		</div>
	</div>
	<?php

} // tmem_event_overview_metabox_event_dates_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_event_dates_row', 20 );

/**
 * Output the event times row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_times_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$start      = $tmem_event->data['start_time'];
	$finish     = $tmem_event->data['finish_time'];
	$setup_time = $tmem_event->data['setup_time'];
	$format     = tmem_get_option( 'time_format', 'H:i' );
	?>

	<div class="tmem-event-date-fields">
		<div class="tmem-event-start-time">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Start', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->time_hour_select(
				array(
					'selected' => ! empty( $start ) ? gmdate( $format[0], strtotime( $start ) ) : '',
				)
			);
			?>

			<?php
			echo TMEM()->html->time_minute_select(
				array(
					'selected' => ! empty( $start ) ? gmdate( $format[2], strtotime( $start ) ) : '',
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo TMEM()->html->time_period_select(
					array(
						'selected' => ! empty( $start ) ? gmdate( 'A', strtotime( $start ) ) : '',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="tmem-event-end-time">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'End', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->time_hour_select(
				array(
					'name'     => 'event_finish_hr',
					'selected' => ! empty( $finish ) ? gmdate( $format[0], strtotime( $finish ) ) : '',
				)
			);
			?>

			<?php
			echo TMEM()->html->time_minute_select(
				array(
					'name'     => 'event_finish_min',
					'selected' => ! empty( $finish ) ? gmdate( $format[2], strtotime( $finish ) ) : '',
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo TMEM()->html->time_period_select(
					array(
						'name'     => 'event_finish_period',
						'selected' => ! empty( $finish ) ? gmdate( 'A', strtotime( $finish ) ) : '',
					)
				);
				?>
			<?php endif; ?>
		</div>

		<div class="tmem-event-setup-time">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Setup', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->time_hour_select(
				array(
					'name'        => 'dj_setup_hr',
					'selected'    => ! empty( $setup_time ) ? gmdate( $format[0], strtotime( $setup_time ) ) : '',
					'blank_first' => true,
				)
			);
			?>

			<?php
			echo TMEM()->html->time_minute_select(
				array(
					'name'        => 'dj_setup_min',
					'selected'    => ! empty( $setup_time ) ? gmdate( $format[2], strtotime( $setup_time ) ) : '',
					'blank_first' => true,
				)
			);
			?>

			<?php if ( 'H:i' != $format ) : ?>
				<?php
				echo TMEM()->html->time_period_select(
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

} // tmem_event_overview_metabox_event_times_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_event_times_row', 20 );

/**
 * Output the event package row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_packages_row( $event_id ) {

	if ( ! tmem_packages_enabled() ) {
		return;
	}

	global $tmem_event, $tmem_event_update;

	$package    = $tmem_event->data['package'];
	$addons     = $tmem_event->data['addons'];
	$employee   = $tmem_event->data['employee_id'] ? $tmem_event->data['employee_id'] : get_current_user_id();
	$event_type = tmem_get_event_type( $event_id, true );
	$event_date = $tmem_event->data['date'] ? $tmem_event->data['date'] : false;

	if ( ! $event_type ) {
		$event_type = tmem_get_option( 'event_type_default', '' );
	}

	?>

	<div class="tmem-event-package-fields">
		<div class="tmem-event-package">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Package', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->packages_dropdown(
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

		<div class="tmem-event-addons">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Addons', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->addons_dropdown(
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

} // tmem_event_overview_metabox_event_packages_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_event_packages_row', 30 );

/**
 * Output the event client notes row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_client_notes_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>

	<div class="tmem-event-client-notes-fields">
		<div class="tmem-event-notes">
			<span class="tmem-repeatable-row-setting-label">
				<?php esc_html_e( 'Client Notes', 'mobile-events-manager' ); ?>
			</span>

			<?php
			echo TMEM()->html->textarea(
				array(
					'name'        => '_tmem_event_notes',
					'placeholder' => __( 'Information entered here is visible by both clients and employees', 'mobile-events-manager' ),
					'value'       => esc_attr( $tmem_event->data['notes'] ),
				)
			);
			?>
		</div>
	</div>
	<?php

} // tmem_event_overview_metabox_event_client_notes_row
add_action( 'tmem_event_overview_standard_event_sections', 'tmem_event_overview_metabox_event_client_notes_row', 50 );

/**
 * Output the playlist options section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_playlist_options_row( $event_id ) {
	global $tmem_event, $tmem_event_update;

	$enable_playlist = tmem_get_option( 'enable_playlists', true );
	$limit_class     = '';

	if ( ! $tmem_event_update || 'tmem-unattended' == $tmem_event->data['status'] ) {
		$playlist_limit = tmem_playlist_global_limit();
	} else {
		$playlist_limit  = $tmem_event->data['playlist_limit'];
		$enable_playlist = $tmem_event->data['playlist_enabled'];
	}

	if ( ! $enable_playlist ) {
		$limit_class = ' style="display: none;"';
	}

	if ( ! $playlist_limit ) {
		$playlist_limit = 0;
	}

	?>
	<div id="tmem-event-options-fields" class="tmem-event-options-sections-wrap">
		<div class="tmem-custom-event-sections">
			<?php do_action( 'tmem_event_overview_options_top', $event_id ); ?>
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php printf( esc_html__( '%s Options', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></span>

				<div class="tmem-repeatable-option">
					<span class="tmem-enable-playlist-option">
						<label class="tmem-enable-playlist">
							<?php esc_html_e( 'Enable Playlist?', 'mobile-events-manager' ); ?>
						</label>
						<?php
						echo TMEM()->html->checkbox(
							array(
								'name'    => '_tmem_event_playlist',
								'value'   => 'Y',
								'current' => $enable_playlist ? 'Y' : 0,
							)
						);
						?>
					</span>
				</div>

				<span id="tmem-playlist-limit" class="tmem-playlist-limit-option" <?php echo $limit_class; ?>>
					<label class="tmem-playlist-limit">
						<?php esc_html_e( 'Song Limit', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->number(
						array(
							'name'  => '_tmem_event_playlist_limit',
							'value' => $playlist_limit,
						)
					);
					?>
				</span>

				<?php if ( tmem_event_has_playlist( $event_id ) ) : ?>
					<?php $songs = tmem_count_playlist_entries( $event_id ); ?>
					<?php
					$url = add_query_arg(
						array(
							'page'     => 'tmem-playlists',
							'event_id' => $event_id,
						),
						admin_url( 'admin.php' )
					);
					?>

					<span id="tmem-playlist-view" class="tmem-playlist-view-option">
						<label class="tmem-playlist-view">
							<?php printf( _n( '%s Song in Playlist', '%s Songs in Playlist', $songs, 'mobile-events-manager' ), $songs ); ?>
						</label>
						<a href="<?php echo $url; ?>"><?php esc_html_e( 'View', 'mobile-events-manager' ); ?></a>
					</span>
				<?php endif; ?>

			</div>
			<?php do_action( 'tmem_event_overview_options', $event_id ); ?>
		</div>
	</div>

	<?php

} // tmem_event_overview_metabox_event_playlist_options_row
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_event_playlist_options_row', 5 );

/**
 * Output the event workers section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_workers_row( $event_id ) {
	global $tmem_event, $tmem_event_update;

	if ( ! tmem_is_employer() ) {
		return;
	}

	$exclude = false;

	if ( ! empty( $tmem_event->data['employees'] ) ) {
		foreach ( $tmem_event->data['employees'] as $employee_id => $employee_data ) {
			$exclude[] = $employee_id;
		}
	}

	?>
	<a id="tmem-event-workers"></a>
	<div id="tmem-event-workers-fields" class="tmem-event-workers-sections-wrap">
		<div class="tmem-custom-event-sections">
			<?php do_action( 'tmem_event_overview_workers_top', $event_id ); ?>
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php printf( esc_html__( '%s Workers', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></span>

				<span class="tmem-event-workers-role">
					<?php
					echo TMEM()->html->roles_dropdown(
						array(
							'name'   => 'event_new_employee_role',
							'chosen' => true,
						)
					);
					?>
				</span>

				<span class="tmem-event-workers-employee">
					<?php
					echo TMEM()->html->employee_dropdown(
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

				<?php if ( tmem_get_option( 'enable_employee_payments' ) && tmem_employee_can( 'manage_txns' ) ) : ?>
					<span class="tmem-event-workers-wage">
						<?php
						echo TMEM()->html->text(
							array(
								'name'        => 'event_new_employee_wage',
								'class'       => 'tmem-currency',
								'placeholder' => esc_html( tmem_sanitize_amount( '0' ) ),
							)
						);
						?>
					</span>
				<?php endif; ?>

				<br />
				<span class="tmem-event-worker-add">
					<a id="add_event_employee" class="button button-secondary button-small"><?php esc_html_e( 'Add', 'mobile-events-manager' ); ?></a>
				</span>

			</div>

			<?php do_action( 'tmem_event_overview_workers', $event_id ); ?>
		</div>

		<div id="tmem-event-employee-list">
			<?php tmem_do_event_employees_list_table( $event_id ); ?>
		</div>

		<?php if ( tmem_get_option( 'enable_employee_payments' ) && in_array( $tmem_event->post_status, tmem_get_option( 'employee_pay_status' ) ) && tmem_employee_can( 'manage_txns' ) && ! tmem_event_employees_paid( $event_id ) ) : ?>

			<div class="tmem_field_wrap tmem_form_fields">
				<p><a href="
				<?php
				echo wp_nonce_url(
					add_query_arg(
						array(
							'tmem-action' => 'pay_event_employees',
							'event_id'    => $event_id,
						),
						admin_url( 'admin.php' )
					),
					'pay_event_employees',
					'tmem_nonce'
				);
				?>
							" id="pay_event_employees" class="button button-primary button-small"><?php printf( esc_html__( 'Pay %s Employees', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></a></p>
			</div>

		<?php endif; ?>

	</div>

	<?php

} // tmem_event_overview_metabox_event_workers_row
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_event_workers_row', 10 );

/**
 * Output the add event type section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_add_event_type_section( $event_id ) {

	global $tmem_event, $tmem_event_update;
	?>

	<div id="tmem-add-event-type-fields" class="tmem-add-event-type-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php printf( esc_html__( 'New %s Type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></span>

				<span class="tmem-new-event-type">
					<label class="tmem-event-type">
						<?php printf( esc_html__( '%s Type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'event_type_name',
							'class' => 'tmem-name-field large-text',
						)
					);
					?>

				</span>

				<span class="tmem-add-event-type-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						sprintf( esc_html__( 'Add %s Type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
						array( 'secondary', 'tmem-add-event-type' ),
						'tmem-add-event-type',
						false
					);
					?>
				</span>
			</div>
		</div>
	</div>
	<?php
} // tmem_event_overview_metabox_add_event_type_section
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_add_event_type_section', 15 );

/**
 * Output the venue details section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_venue_details_section( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$venue_id = $tmem_event->get_venue_id();

	echo tmem_do_venue_details_table( $venue_id, $event_id );

} // tmem_event_overview_metabox_venue_details_section
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_venue_details_section', 20 );

/**
 * Output the add venue section
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_add_venue_section( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$venue_id = $tmem_event->data['venue_id'];

	if ( 'Manual' == $venue_id ) {
		$venue_id = $event_id;
	}

	$venue_name     = tmem_get_event_venue_meta( $venue_id, 'name' );
	$venue_contact  = tmem_get_event_venue_meta( $venue_id, 'contact' );
	$venue_email    = tmem_get_event_venue_meta( $venue_id, 'email' );
	$venue_address1 = tmem_get_event_venue_meta( $venue_id, 'address1' );
	$venue_address2 = tmem_get_event_venue_meta( $venue_id, 'address2' );
	$venue_town     = tmem_get_event_venue_meta( $venue_id, 'town' );
	$venue_county   = tmem_get_event_venue_meta( $venue_id, 'county' );
	$venue_postcode = tmem_get_event_venue_meta( $venue_id, 'postcode' );
	$venue_phone    = tmem_get_event_venue_meta( $venue_id, 'phone' );
	$venue_address  = array( $venue_address1, $venue_address2, $venue_town, $venue_county, $venue_postcode );
	$section_title  = __( 'Add a New Venue', 'mobile-events-manager' );
	$add_save_label = __( 'Add', 'mobile-events-manager' );
	$employee_id    = $tmem_event->data['employee_id'] ? $tmem_event->data['employee_id'] : get_current_user_id();

	if ( $tmem_event->ID == $venue_id ) {
		$section_title  = __( 'Manual Venue', 'mobile-events-manager' );
		$add_save_label = __( 'Save', 'mobile-events-manager' );
	}

	?>

	<div id="tmem-add-venue-fields" class="tmem-add-event-venue-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title"><?php echo $section_title; ?></span>

				<span class="tmem-add-venue-name">
					<label class="tmem-venue-name">
						<?php esc_html_e( 'Venue', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'  => 'venue_name',
							'value' => $venue_name,
							'class' => 'tmem-name-field large-text',
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-contact">
					<label class="tmem-venue-contact">
						<?php esc_html_e( 'Contact', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_contact',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_contact ) ? esc_attr( $venue_contact ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-email">
					<label class="tmem-venue-email">
						<?php esc_html_e( 'Email Address', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_email',
							'class'       => 'tmem-name-field large-text',
							'type'        => 'email',
							'value'       => ! empty( $venue_email ) ? esc_attr( $venue_email ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-phone">
					<label class="tmem-venue-phone">
						<?php esc_html_e( 'Phone', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_phone',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_phone ) ? esc_attr( $venue_phone ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-address1">
					<label class="tmem-venue-address1">
						<?php esc_html_e( 'Address Line 1', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_address1',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_address1 ) ? esc_attr( $venue_address1 ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-address2">
					<label class="tmem-venue-address2">
						<?php esc_html_e( 'Address Line 2', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_address2',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_address2 ) ? esc_attr( $venue_address2 ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-town">
					<label class="tmem-venue-town">
						<?php esc_html_e( 'Town', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_town',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_town ) ? esc_attr( $venue_town ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-county">
					<label class="tmem-venue-county">
						<?php esc_html_e( 'County', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_county',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_county ) ? esc_attr( $venue_county ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-postcode">
					<label class="tmem-venue-postcode">
						<?php esc_html_e( 'Post Code', 'mobile-events-manager' ); ?>
					</label>
					<?php
					echo TMEM()->html->text(
						array(
							'name'        => 'venue_postcode',
							'class'       => 'tmem-name-field large-text',
							'value'       => ! empty( $venue_postcode ) ? esc_attr( $venue_postcode ) : '',
							'placeholder' => __( '(optional)', 'mobile-events-manager' ),
						)
					);
					?>
				</span>

				<span class="tmem-add-venue-action">
					<label>&nbsp;</label>
					<?php
					submit_button(
						sprintf( esc_html__( '%s Venue', 'mobile-events-manager' ), $add_save_label ),
						array( 'secondary', 'tmem-add-venue' ),
						'tmem-add-venue',
						false
					);
					?>
				</span>

				<?php do_action( 'tmem_venue_details_table_after_save', $event_id ); ?>
				<?php do_action( 'tmem_venue_details_travel_data', $venue_address, $employee_id ); ?>

			</div>
		</div>
	</div>
	<?php
} // tmem_event_overview_metabox_add_venue_section
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_add_venue_section', 25 );

/**
 * Output the event travel costs hidden fields
 *
 * @since 1.4
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_venue_travel_section( $event_id ) {
	global $tmem_event, $tmem_event_update;

	$travel_fields = tmem_get_event_travel_fields();

	foreach ( $travel_fields as $field ) :
		?>
		<?php $travel_data = tmem_get_event_travel_data( $event_id, $field ); ?>
		<?php $value = ! empty( $travel_data ) ? $travel_data : ''; ?>
		<input type="hidden" name="travel_<?php echo $field; ?>" id="tmem_travel_<?php echo $field; ?>" value="<?php echo $value; ?>" />
		<?php
	endforeach;

} // tmem_event_overview_metabox_venue_travel_section
add_action( 'tmem_event_overview_custom_event_sections', 'tmem_event_overview_metabox_venue_travel_section', 30 );

/**
 * Output the event equipment and travel costs row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_equipment_travel_costs_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>

	<div class="tmem-event-equipment-price-fields">

		<?php if ( tmem_packages_enabled() ) : ?>

			<div class="tmem-event-package-cost">
				<span class="tmem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Package Costs', 'mobile-events-manager' );
					echo ' ' . tmem_currency_symbol();
					?>
				</span>

				<?php
				echo TMEM()->html->text(
					array(
						'name'        => '_tmem_event_package_cost',
						'class'       => 'tmem-currency',
						'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( tmem_sanitize_amount( $tmem_event->data['package_price'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

			<div class="tmem-event-addons-cost">
				<span class="tmem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Addons Cost', 'mobile-events-manager' );
					echo ' ' . tmem_currency_symbol();
					?>
				</span>

				<?php
				echo TMEM()->html->text(
					array(
						'name'        => '_tmem_event_addons_cost',
						'class'       => 'tmem-currency',
						'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( tmem_sanitize_amount( $tmem_event->data['addons_price'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

		<?php endif; ?>

		<?php if ( tmem_get_option( 'travel_add_cost', false ) ) : ?>

			<div class="tmem-event-travel-cost">
				<span class="tmem-repeatable-row-setting-label">
					<?php
					esc_html_e( 'Travel Cost', 'mobile-events-manager' );
					echo ' ' . tmem_currency_symbol();
					?>
				</span>

				<?php
				echo TMEM()->html->text(
					array(
						'name'        => '_tmem_event_travel_cost',
						'class'       => 'tmem-currency',
						'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
						'value'       => esc_attr( tmem_sanitize_amount( $tmem_event->data['travel_cost'] ) ),
						'readonly'    => true,
					)
				);
				?>
			</div>

		<?php endif; ?>

	</div>
	<?php

} // tmem_event_overview_metabox_event_equipment_travel_costs_row
add_action( 'tmem_event_overview_standard_event_price_sections', 'tmem_event_overview_metabox_event_equipment_travel_costs_row', 10 );

/**
 * Output the event discount and deposit row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_discount_deposit_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>

	<div class="tmem-event-price-fields">
		<div class="tmem-event-additional-cost">
			<span class="tmem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Additional Costs', 'mobile-events-manager' );
				echo ' ' . tmem_currency_symbol();
				?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'        => '_tmem_event_additional_cost',
					'class'       => 'tmem-currency',
					'placeholder' => esc_html( '0.00' ),
					'value'       => esc_attr( $tmem_event->data['additional_cost'] ),
				)
			);
			?>
		</div>

		<div class="tmem-event-discount">
			<span class="tmem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Discount', 'mobile-events-manager' );
				echo ' ' . tmem_currency_symbol();
				?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'        => '_tmem_event_discount',
					'class'       => 'tmem-currency',
					'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
					'value'       => sanitize_key( tmem_sanitize_amount( $tmem_event->data['discount'] ) ),
				)
			);
			?>
		</div>

		<div class="tmem-event-deposit">
			<span class="tmem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Deposit', 'mobile-events-manager' );
				echo ' ' . tmem_currency_symbol();
				?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'        => '_tmem_event_deposit',
					'class'       => 'tmem-currency',
					'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
					'value'       => $tmem_event_update ? esc_attr( $tmem_event->deposit ) : tmem_calculate_deposit( $tmem_event->price ),
				)
			);
			?>
		</div>

	</div>
	<?php

} // tmem_event_overview_metabox_event_discount_deposit_row
add_action( 'tmem_event_overview_standard_event_price_sections', 'tmem_event_overview_metabox_event_discount_deposit_row', 20 );

/**
 * Output the event price row
 *
 * @since 1.5
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_overview_metabox_event_price_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>

	<div class="tmem-event-price-fields">
		<div class="tmem-event-cost">
			<span class="tmem-repeatable-row-setting-label">
				<?php
				esc_html_e( 'Total Cost', 'mobile-events-manager' );
				echo ' ' . tmem_currency_symbol();
				?>
			</span>

			<?php
			echo TMEM()->html->text(
				array(
					'name'        => '_tmem_event_cost',
					'class'       => 'tmem-currency',
					'placeholder' => esc_html( tmem_sanitize_amount( '0.00' ) ),
					'value'       => ! empty( $tmem_event->price ) ? tmem_sanitize_amount( $tmem_event->price ) : '',
					'readonly'    => true,
				)
			);
			?>
		</div>

	</div>
	<?php

} // tmem_event_overview_metabox_event_price_row
add_action( 'tmem_event_overview_standard_event_price_sections', 'tmem_event_overview_metabox_event_price_row', 30 );

/**
 * Output the event enquiry source row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_admin_enquiry_source_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$enquiry_source = tmem_get_enquiry_source( $event_id );

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<div class="tmem_col">
			<label for="tmem_enquiry_source"><?php esc_html_e( 'Enquiry Source:', 'mobile-events-manager' ); ?></label>
			<?php
			echo TMEM()->html->enquiry_source_dropdown(
				'tmem_enquiry_source',
				$enquiry_source ? $enquiry_source->term_id : ''
			);
			?>
		</div>
	</div>
	<?php
} // tmem_event_metabox_admin_enquiry_source_row
add_action( 'tmem_event_admin_fields', 'tmem_event_metabox_admin_enquiry_source_row', 10 );

/**
 * Output the employee notes row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_admin_employee_notes_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<?php
		echo TMEM()->html->textarea(
			array(
				'label'       => sprintf( esc_html__( '%s Notes:', 'mobile-events-manager' ), tmem_get_option( 'artist' ) ),
				'name'        => '_tmem_event_dj_notes',
				'placeholder' => __( 'This information is not visible to clients', 'mobile-events-manager' ),
				'value'       => get_post_meta( $event_id, '_tmem_event_dj_notes', true ),
			)
		);
		?>
	</div>

	<?php
} // tmem_event_metabox_admin_employee_notes_row
add_action( 'tmem_event_admin_fields', 'tmem_event_metabox_admin_employee_notes_row', 30 );

/**
 * Output the admin notes row
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_admin_notes_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	if ( ! tmem_is_admin() ) {
		return;
	}

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<?php
		echo TMEM()->html->textarea(
			array(
				'label'       => __( 'Admin Notes:', 'mobile-events-manager' ),
				'name'        => '_tmem_event_admin_notes',
				'placeholder' => __( 'This information is only visible to admins', 'mobile-events-manager' ),
				'value'       => get_post_meta( $event_id, '_tmem_event_admin_notes', true ),
			)
		);
		?>
	</div>

	<?php
} // tmem_event_metabox_admin_notes_row
add_action( 'tmem_event_admin_fields', 'tmem_event_metabox_admin_notes_row', 40 );

/**
 * Output the event transaction list table
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_txn_list_table( $event_id ) {

	global $tmem_event, $tmem_event_update;

	?>
	<p><strong><?php esc_html_e( 'All Transactions', 'mobile-events-manager' ); ?></strong> <span class="tmem-small">(<a id="tmem_txn_toggle" class="tmem-fake"><?php esc_html_e( 'toggle', 'mobile-events-manager' ); ?></a>)</span></p>
	<div id="tmem_event_txn_table" class="tmem_meta_table_wrap">
		<?php tmem_do_event_txn_table( $event_id ); ?>
	</div>
	<?php
} // tmem_event_metabox_txn_list_table
add_action( 'tmem_event_txn_fields', 'tmem_event_metabox_txn_list_table', 10 );

/**
 * Output the event transaction list table
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_txn_add_new_row( $event_id ) {

	global $tmem_event, $tmem_event_update;

	tmem_insert_datepicker(
		array(
			'id'       => 'tmem_txn_display_date',
			'altfield' => 'tmem_txn_date',
			'maxdate'  => 'today',
		)
	);

	?>

	<div id="tmem-event-add-txn-table">
		<table id="tmem_event_add_txn_table" class="widefat tmem_event_add_txn_table tmem_form_fields">
			<thead>
				<tr>
					<th colspan="3"><?php esc_html_e( 'Add Transaction', 'mobile-events-manager' ); ?> <a id="toggle_add_txn_fields" class="tmem-small tmem-fake"><?php esc_html_e( 'show form', 'mobile-events-manager' ); ?></a></th>
				</tr>
			</thead>

			<tbody class="tmem-hidden">
				<tr>
					<td><label for="tmem_txn_amount"><?php esc_html_e( 'Amount:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo tmem_currency_symbol() .
						TMEM()->html->text(
							array(
								'name'        => 'tmem_txn_amount',
								'class'       => 'tmem-input-currency',
								'placeholder' => esc_html( tmem_sanitize_amount( '10' ) ),
							)
						);
						?>
						</td>

					<td><label for="tmem_txn_display_date"><?php esc_html_e( 'Date:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo TMEM()->html->text(
							array(
								'name'  => 'tmem_txn_display_date',
								'class' => '',
							)
						) .
						TMEM()->html->hidden(
							array(
								'name' => 'tmem_txn_date',
							)
						);
						?>
						</td>

					<td><label for="tmem_txn_amount"><?php esc_html_e( 'Direction:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo TMEM()->html->select(
							array(
								'name'             => 'tmem_txn_direction',
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
					<td><span id="tmem_txn_from_container"><label for="tmem_txn_from"><?php esc_html_e( 'From:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo TMEM()->html->text(
							array(
								'name'        => 'tmem_txn_from',
								'class'       => '',
								'placeholder' => __( 'Leave empty if client', 'mobile-events-manager' ),
							)
						);
						?>
						</span>
						<span id="tmem_txn_to_container" class="tmem-hidden"><label for="tmem_txn_to"><?php esc_html_e( 'To:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo TMEM()->html->text(
							array(
								'name'        => 'tmem_txn_to',
								'class'       => '',
								'placeholder' => __( 'Leave empty if client', 'mobile-events-manager' ),
							)
						);
						?>
						</span></td>

					<td><label for="tmem_txn_for"><?php esc_html_e( 'For:', 'mobile-events-manager' ); ?></label><br />
						<?php echo TMEM()->html->txn_type_dropdown(); ?></td>

					<td><label for="tmem_txn_src"><?php esc_html_e( 'Paid via:', 'mobile-events-manager' ); ?></label><br />
						<?php
						echo TMEM()->html->select(
							array(
								'name'             => 'tmem_txn_src',
								'options'          => tmem_get_txn_source(),
								'selected'         => tmem_get_option( 'default_type', 'Cash' ),
								'show_option_all'  => false,
								'show_option_none' => false,
							)
						);
						?>
						</td>
				</tr>

				<?php if ( tmem_get_option( 'manual_payment_cfm_template' ) ) : ?>

					<tr id="tmem-txn-email">
						<td colspan="3">
						<?php
						echo TMEM()->html->checkbox(
							array(
								'name'    => 'tmem_manual_txn_email',
								'current' => tmem_get_option( 'manual_payment_cfm_template' ) ? true : false,
								'class'   => 'tmem-checkbox',
							)
						);
						?>
							<?php esc_html_e( 'Send manual payment confirmation email?', 'mobile-events-manager' ); ?></td>
					</tr>

				<?php endif; ?>

			</tbody>
		</table>

	</div>

	<p id="save-event-txn" class="tmem-hidden"><a id="save_transaction" class="button button-primary button-small"><?php esc_html_e( 'Add Transaction', 'mobile-events-manager' ); ?></a></p>
	<?php
} // tmem_event_metabox_txn_add_new_row
add_action( 'tmem_event_txn_fields', 'tmem_event_metabox_txn_add_new_row', 20 );

/**
 * Output the event journal table
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_history_journal_table( $event_id ) {

	global $tmem_event, $tmem_event_update;

	$journals = tmem_get_journal_entries( $event_id );

	$count = count( $journals );
	$i     = 0;

	?>
	<div id="tmem-event-journal-table">
		<strong><?php esc_html_e( 'Recent Journal Entries', 'mobile-events-manager' ); ?></strong>
		<table class="widefat tmem_event_journal_table tmem_form_fields">
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
							<td><a href="<?php echo get_edit_comment_link( $journal->comment_ID ); ?>"><?php echo gmdate( tmem_get_option( 'time_format' ) . ' ' . tmem_get_option( 'short_date_format' ), strtotime( $journal->comment_date ) ); ?></a></td>
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
					<td colspan="2"><?php printf( esc_html__( 'There are no journal entries associated with this %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?></td>
				</tr>
				<?php endif; ?>

			</tbody>

			<?php if ( $journals ) : ?>
				<tfoot>
					<tr>
						<td colspan="2"><span class="description"><?php printf( __('Displaying the most recent %1$d entries of <a href="%2$s">%3$d total', 'mobile-events-manager' ), ( $count >= 3 ) ? 3 : $count, add_query_arg( array( 'p' => $event_id ), admin_url( 'edit-comments.php?p=5636' ) ), $count ); ?></span></td>
					</tr>
				</tfoot>
			<?php endif; ?>

		</table>
	</div>
	<?php
} // tmem_event_metabox_history_journal_table
add_action( 'tmem_event_history_fields', 'tmem_event_metabox_history_journal_table', 10 );

/**
 * Output the event emails table
 *
 * @since 1.3.7
 * @global obj $tmem_event TMEM_Event class object
 * @global bool $tmem_event_update True if this event is being updated, false if new.
 * @param int $event_id The event ID.
 * @return str
 */
function tmem_event_metabox_history_emails_table( $event_id ) {

	global $tmem_event, $tmem_event_update;

	if ( ! tmem_get_option( 'track_client_emails' ) ) {
		return;
	}

	$emails = tmem_event_get_emails( $event_id );
	$count  = count( $emails );
	$i      = 0;

	?>
	<div id="tmem-event-emails-table">
		<strong><?php esc_html_e( 'Associated Emails', 'mobile-events-manager' ); ?></strong>
		<table class="widefat tmem_event_emails_table tmem_form_fields">
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
							<td><?php echo gmdate( tmem_get_option( 'time_format' ) . ' ' . tmem_get_option( 'short_date_format' ), strtotime( $email->post_date ) ); ?></td>
							<td><a href="<?php echo get_edit_post_link( $email->ID ); ?>"><?php echo get_the_title( $email->ID ); ?></a></td>
							<td>
							<?php
							echo get_post_status_object( $email->post_status )->label;

							if ( ! empty( $email->post_modified ) && 'opened' == $email->post_status ) :
								?>
								<?php echo '<br />'; ?>
								<span class="description"><?php echo gmdate( tmem_get_option( 'time_format', 'H:i' ) . ' ' . tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $email->post_modified ) ); ?></span>
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
					<td colspan="3"><?php printf( esc_html__( 'There are no emails associated with this %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?></td>
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
} // tmem_event_metabox_emails_table
add_action( 'tmem_event_history_fields', 'tmem_event_metabox_history_emails_table', 20 );
