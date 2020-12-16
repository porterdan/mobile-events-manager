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
 * Activates the given task.
 *
 * @since 1.4.7
 * @return void
 */
function mem_activate_task_action( $data ) {
	if ( empty( $data['id'] ) ) {
		return;
	}

	if ( mem_task_set_active_status( $data['id'] ) ) {
		$message = 'task-status-updated';
	} else {
		$message = 'task-status-update-failed';
	}

	$redirect = add_query_arg(
		array(
			'post_type'    => 'mem-event',
			'page'         => 'mem-tasks',
			'mem-message' => $message,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect );
	die();

} // mem_activate_task_action
add_action( 'mem-activate_task', 'mem_activate_task_action' );

/**
 * Deactivates the given task.
 *
 * @since 1.4.7
 * @return void
 */
function mem_deactivate_task_action( $data ) {
	if ( empty( $data['id'] ) ) {
		return;
	}

	if ( mem_task_set_active_status( $data['id'], false ) ) {
		$message = 'task-status-updated';
	} else {
		$message = 'task-status-update-failed';
	}

	$redirect = add_query_arg(
		array(
			'post_type'    => 'mem-event',
			'page'         => 'mem-tasks',
			'mem-message' => $message,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect );
	die();

} // mem_deactivate_task_action
add_action( 'mem-deactivate_task', 'mem_deactivate_task_action' );

/**
 * Save an individual task.
 *
 * @since 1.4.7
 * @param arr $data Array of POST data
 * @return void
 */
function mem_save_task_action( $data ) {

	if ( ! isset( $_POST['mem_task_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mem_task_nonce'], 'mem_update_task_details_nonce' ) ) ) ) {
		return;
	}

	if ( ! isset( $_POST['mem_task_id'] ) ) {
		return;
	}

	$task_data = array(
		'id'        => sanitize_text_field( $data['mem_task_id'] ),
		'name'      => sanitize_text_field( $data['task_name'] ),
		'frequency' => sanitize_text_field( $data['task_frequency'] ),
		'desc'      => $data['task_description'],
		'options'   => array(
			'age'      => absint( $data['task_run_time'] ) . ' ' . sanitize_text_field( $data['task_run_period'] ),
			'run_when' => sanitize_text_field( $data['task_run_event_status'] ),
		),
	);

	if ( isset( $data['task_email_template'] ) ) {
		$task_data['options']['email_template'] = absint( $data['task_email_template'] );
		$task_data['options']['email_subject']  = sanitize_text_field( $data['task_email_subject'] );
		$task_data['options']['email_from']     = sanitize_text_field( $data['task_email_from'] );
	}

	if ( 'upload-playlists' != $task_data['id'] ) {
		$task_data['active'] = ! empty( $data['task_active'] ) ? true : false;
	}

	if ( mem_update_task( $task_data ) ) {
		$message = 'task-updated';
	} else {
		$message = 'task-update-failed';
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'    => 'mem-event',
				'page'         => 'mem-tasks',
				'view'         => 'task',
				'id'           => $task_data['id'],
				'mem-message' => $message,
			),
			admin_url( 'edit.php' )
		)
	);
	die();

} // mem_save_task_action
add_action( 'mem-update_task_details', 'mem_save_task_action' );

/**
 * Runs the given task.
 *
 * @since 1.4.7
 * @return void
 */
function mem_run_now_task_action( $data ) {
	if ( empty( $data['id'] ) ) {
		return;
	}

	if ( mem_task_run_now( $data['id'] ) ) {
		$message = 'task-run';
	} else {
		$message = 'task-run-failed';
	}

	$redirect = add_query_arg(
		array(
			'post_type'    => 'mem-event',
			'page'         => 'mem-tasks',
			'mem-message' => $message,
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect );
	die();

} // mem_run_now_task_action
add_action( 'mem-run_task', 'mem_run_now_task_action' );

/**
 * Schedule tasks to run after event approval.
 *
 * @since 1.4.7
 * @return void
 */
function mem_set_task_run_time_after_approval( $result, $event_id, $old_status ) {

	if ( empty( $result ) ) {
		return;
	}

	$tasks = mem_get_tasks();

	if ( ! $tasks ) {
		return;
	}

	foreach ( $tasks as $slug => $data ) {
		$meta_key = '_mem_event_task_after_approval_' . $slug;

		if ( 'after_approval' != $data['options']['run_when'] ) {
			continue;
		}

		$run_time = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $data['options']['age'] ) );

		update_post_meta( $event_id, $meta_key, $run_time );
	}

} // mem_set_task_run_time_after_approval
add_action( 'mem_post_update_event_status_mem-approval', 'mem_set_task_run_time_after_approval', 10, 3 );
