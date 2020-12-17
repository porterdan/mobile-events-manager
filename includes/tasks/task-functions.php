<?php
/**
 * Task functions
 *
 * @package TMEM
 * @subpackage Tasks
 * @since 1.4.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the tasks
 *
 * @since 1.4.7
 * @return arr Array of tasks
 */
function tmem_get_tasks() {
	$tasks = get_option( 'tmem_schedules' );
	if ( $tasks ) {
		ksort( $tasks );
	}

	return apply_filters( 'tmem_tasks', $tasks );
} // tmem_get_tasks

/**
 * Update a single tasks
 *
 * @since 1.4.7
 * @param arr $data Array of task data to save.
 * @return bool True if update successful, or false
 */
function tmem_update_task( $data ) {
	if ( ! isset( $data['id'] ) ) {
		return false;
	}

	$id    = $data['id'];
	$tasks = tmem_get_tasks();

	foreach ( $data as $key => $value ) {
		if ( 'id' === $key ) {
			continue;
		}

		$tasks[ $id ][ $key ] = $value;

	}

	return update_option( 'tmem_schedules', $tasks );

} // tmem_update_task

/**
 * Retrieve a single tasks
 *
 * @since 1.4.7
 * @param str $id The ID of the task.
 * @return arr Array of tasks
 */
function tmem_get_task( $id ) {
	$tasks = tmem_get_tasks();

	if ( array_key_exists( $id, $tasks ) ) {
		return $tasks[ $id ];
	}

	return false;
} // tmem_get_task

/**
 * Retrieve a task name
 *
 * @since 1.5
 * @param string|array $task A task ID, or array.
 * @return string
 */
function tmem_get_task_name( $task ) {
	if ( ! is_array( $task ) ) {
		$task = tmem_get_task( $task );
	}

	return $task['name'];
} // tmem_get_task_name

/**
 * Retrieve a task description
 *
 * @since 1.5
 * @param string|array $task A task ID, or array.
 * @return string
 */
function tmem_get_task_description( $task ) {
	if ( ! is_array( $task ) ) {
		$task = tmem_get_task( $task );
	}

	return $task['desc'];
} // tmem_get_task_description

/**
 * Retrieve a tasks status
 *
 * @since 1.4.7
 * @param str|arr $task A task ID, or array.
 * @return bool True or false
 */
function tmem_is_task_active( $task ) {
	if ( ! is_array( $task ) ) {
		$task = tmem_get_task( $task );
	}

	if ( ! empty( $task['active'] ) ) {
		return true;
	}

	return false;
} // tmem_is_task_active

/**
 * Whether or not a task can be deleted
 *
 * @since 1.4.7
 * @param str|arr $task A task ID, or array.
 * @return arr Array of tasks
 */
function tmem_can_delete_task( $task ) {
	if ( ! is_array( $task ) ) {
		$task = tmem_get_task( $task );
	}

	if ( empty( $task['default'] ) ) {
		return true;
	}

	return false;
} // tmem_get_task

/**
 * Retrieve schedule options for tasks
 *
 * @since 1.4.7
 * @return arr Array of options
 */
function tmem_get_task_schedule_options() {
	$schedules = array(
		'Hourly'      => __( 'Hourly', 'mobile-events-manager' ),
		'Daily'       => __( 'Daily', 'mobile-events-manager' ),
		'Twice Daily' => __( 'Twice Daily', 'mobile-events-manager' ),
		'Weekly'      => __( 'Weekly', 'mobile-events-manager' ),
		'Monthly'     => __( 'Monthly', 'mobile-events-manager' ),
		'Yearly'      => __( 'Yearly', 'mobile-events-manager' ),
	);

	$schedules = apply_filters( 'tmem_task_schedule_options', $schedules );

	return $schedules;
} // tmem_get_task_schedule_options

/**
 * Retrieve task run time options
 *
 * @since 1.4.7
 * @param int $id The task ID.
 * @return arr Array of options
 */
function tmem_get_task_run_times( $id = false ) {
	$event_label = esc_html( tmem_get_label_singular() );
	$run_times   = array(
		'event_created'  => sprintf( esc_html__( 'After the %s is Created', 'mobile-events-manager' ), $event_label ),
		'after_approval' => sprintf( esc_html__( 'After the %s is Confirmed', 'mobile-events-manager' ), $event_label ),
		'before_event'   => sprintf( esc_html__( 'Before the %s', 'mobile-events-manager' ), $event_label ),
		'after_event'    => sprintf( esc_html__( 'After the %s', 'mobile-events-manager' ), $event_label ),
	);

	$run_times = apply_filters( 'tmem_task_run_times', $run_times, $id );

	return $run_times;
} // tmem_get_task_run_times

/**
 * Task run times.
 *
 * @since 1.4.7
 * @param arr $run_times The run time schedules.
 * @param int $id The task ID.
 * @return arr The run time schedules
 */
function tmem_filter_task_run_times( $run_times, $id ) {
	if ( 'complete-events' === $id || 'upload-playlists' === $id ) {
		$unset = array( 'event_created', 'after_approval', 'before_event' );
	} elseif ( 'fail-enquiry' === $id ) {
		$unset = array( 'after_approval', 'before_event', 'after_event' );
	} elseif ( 'request-deposit' === $id || 'balance-reminder' === $id ) {
		$unset = array( 'event_created', 'after_event' );
	} elseif ( 'playlist-employee-notify' !== $id ) {
		$unset = array( 'event_created', 'after_event', 'after_approval' );
	}

	if ( isset( $unset ) ) {
		foreach ( $unset as $time ) {
			unset( $run_times[ $time ] );
		}
	}

	return $run_times;
} // tmem_filter_task_run_times
add_filter( 'tmem_task_run_times', 'tmem_filter_task_run_times', 10, 2 );

/**
 * Set the status for a given task
 *
 * @since 1.4.7
 * @param str  $id The slug ID of the task.
 * @param bool $activate True to activate task, false to deactivate.
 * @return bool True on success, otherwise false
 */
function tmem_task_set_active_status( $id, $activate = null ) {
	$tasks = tmem_get_tasks();

	if ( $tasks && array_key_exists( $id, $tasks ) ) {
		if ( ! isset( $activate ) ) {
			$activate = true;
		}

		$tasks[ $id ]['active'] = $activate;

		if ( $activate ) {
			$tasks[ $id ]['nextrun'] = current_time( 'timestamp' );
		} else {
			$tasks[ $id ]['nextrun'] = 'Never';
		}

		return update_option( 'tmem_schedules', $tasks );
	}

	return false;
} // tmem_task_set_active_status

/**
 * Runs given task
 *
 * @since 1.4.7
 * @param str $id The slug ID of the task.
 * @return bool True on success, otherwise false
 */
function tmem_task_run_now( $id ) {
	$tasks = tmem_get_tasks();

	if ( empty( $tasks ) || ! array_key_exists( $id, $tasks ) ) {
		return false;
	}

	$tasks[ $id ]['nextrun'] = current_time( 'timestamp' );
	update_option( 'tmem_schedules', $tasks );

	require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-task-runner.php';

	return new TMEM_Task_Runner( $id );
} // tmem_task_run_now

/**
 * Retrieve single available event tasks based on the event status.
 *
 * Does not consider whether or not a task has been previously executed.
 *
 * @since 1.5
 * @param int $event_id Event post ID
 * @return array Array of tasks that can be executed for the event
 */
function tmem_get_tasks_for_event( $event_id ) {
	$event           = new TMEM_Event( $event_id );
	$tasks           = array();
	$completed_tasks = $event->get_tasks();
	$playlist        = tmem_get_playlist_entries( $event_id, array( 'posts_per_page' => 1 ) );
	$guest_args      = array(
		'posts_per_page' => 1,
		'tax_query'      => array(
			array(
				'taxonomy' => 'playlist-category',
				'field'    => 'slug',
				'terms'    => 'guest',
			),
		),
	);

	$guest_playlist = tmem_get_playlist_entries( $event_id, $guest_args );

	if ( in_array( $event->post_status, array( 'tmem-awaitingdeposit', 'tmem-approved', 'tmem-contract' ) ) ) {
		if ( 'Due' === $event->get_deposit_status() ) {
			$tasks['request-deposit'] = tmem_get_task_name( 'request-deposit' );
		}
		if ( 'Due' === $event->get_balance_status() ) {
			$tasks['balance-reminder'] = tmem_get_task_name( 'balance-reminder' );
		}
	}

	/*
	If ( $playlist ) {
		$tasks['playlist-employee-notify'] = tmem_get_task_name( 'playlist-employee-notify' );
	}
	*/

	/*
	If ( $guest_playlist ) {
		$tasks['playlist-notification'] = tmem_get_task_name( 'playlist-notification' );
	}
	*/

	if ( 'tmem-unattended' === $event->post_status ) {
		$tasks['reject-enquiry'] = __( 'Reject Enquiry', 'mobile-events-manager' );
	}

	if ( 'tmem-enquiry' === $event->post_status ) {
		$tasks['fail-enquiry'] = tmem_get_task_name( 'fail-enquiry' );
	}

	$tasks = apply_filters( 'tmem_tasks_for_event', $tasks, $event_id );

	if ( ! empty( $tasks ) ) {
		ksort( $tasks );
	}

	return $tasks;
} // tmem_get_tasks_for_event

/**
 * Executes a single event task.
 *
 * @since 1.5
 * @param int    $event_id The event post ID.
 * @param string $task_id The slug (id) of the task to be executed.
 * @return bool True if the task ran successfully, otherwise false
 */
function tmem_run_single_event_task( $event_id, $task_id ) {
	$task = tmem_get_task( $task_id );

	switch ( $task_id ) {
		case 'fail-enquiry':
			return tmem_fail_enquiry_single_task( $event_id );
			break;

		case 'request-deposit':
			return tmem_request_deposit_single_task( $event_id );
			break;

		case 'balance-reminder':
			return tmem_balance_reminder_single_task( $event_id );
			break;

		case 'playlist-employee-notify':
			return tmem_employee_playlist_notify_single_task( $event_id );
			break;

		default:
			break;
	}
} // tmem_run_single_event_task

/**
 * Executes the fail enquiry task for a single event.
 *
 * @since 1.5
 * @param $event_id
 * @return bool True if task ran successfully
 */
function tmem_fail_enquiry_single_task( $event_id ) {
	$event = new TMEM_Event( $event_id );

	if ( tmem_update_event_status( $event->ID, 'tmem-failed', $event->post_status ) ) {
		tmem_add_journal(
			array(
				'user_id'         => 1,
				'event_id'        => $event->ID,
				'comment_content' => __( 'Enquiry marked as lost via manually executed Scheduled Task', 'mobile-events-manager' ) . '<br /><br />' . time(),
			)
		);

		$event->complete_task( 'fail-enquiry' );

		return true;
	}

	return false;
} // tmem_fail_enquiry_single_task

/**
 * Executes the request deposit task for a single event.
 *
 * @since 1.5
 * @param $event_id
 * @return bool True if task ran successfully
 */
function tmem_request_deposit_single_task( $event_id ) {
	$event = new TMEM_Event( $event_id );
	$task  = tmem_get_task( 'request-deposit' );

	if ( ! empty( $task['options']['email_template'] ) && ! empty( $event->client ) ) {

		$client = get_userdata( $event->client );

		$email_args = array(
			'to_email'  => $client->user_email,
			'event_id'  => $event->ID,
			'client_id' => $event->client,
			'subject'   => $task['options']['email_subject'],
			'message'   => tmem_get_email_template_content( $task['options']['email_template'] ),
			'track'     => true,
			'source'    => sprintf( esc_html__( 'Request %s Scheduled Task', 'mobile-events-manager' ), tmem_get_deposit_label() ),
		);

		if ( 'employee' === $task['options']['email_from'] && ! empty( $event->employee_id ) ) {
			$employee                 = get_userdata( $event->employee_id );
			$email_args['from_email'] = $employee->user_email;
			$email_args['from_name']  = $employee->display_name;
		}

		if ( tmem_send_email_content( $email_args ) ) {
			remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
			wp_update_post(
				array(
					'ID'            => $event->ID,
					'post_modified' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
			add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

			update_post_meta( $event->ID, '_tmem_event_last_updated_by', 0 );
			$event->complete_task( $task['slug'] );

			tmem_add_journal(
				array(
					'user_id'         => 1,
					'event_id'        => $event->ID,
					'comment_content' => sprintf( esc_html__( '%s task manually executed', 'mobile-events-manager' ), esc_attr( $task['name'] ) ) . '<br /><br />' . time(),
				)
			);

			return true;
		}
	}

	return false;
} // tmem_request_deposit_single_task

/**
 * Executes the balance reminder task for a single event.
 *
 * @since 1.5
 * @param $event_id
 * @return bool True if task ran successfully
 */
function tmem_balance_reminder_single_task( $event_id ) {
	$event = new TMEM_Event( $event_id );
	$task  = tmem_get_task( 'balance-reminder' );

	if ( ! empty( $task['options']['email_template'] ) && ! empty( $event->client ) ) {

		$client = get_userdata( $event->client );

		$email_args = array(
			'to_email'  => $client->user_email,
			'event_id'  => $event->ID,
			'client_id' => $event->client,
			'subject'   => $task['options']['email_subject'],
			'message'   => tmem_get_email_template_content( $task['options']['email_template'] ),
			'track'     => true,
			'source'    => sprintf( esc_html__( 'Request %s Scheduled Task', 'mobile-events-manager' ), tmem_get_balance_label() ),
		);

		if ( 'employee' === $task['options']['email_from'] && ! empty( $event->employee_id ) ) {
			$employee                 = get_userdata( $event->employee_id );
			$email_args['from_email'] = $employee->user_email;
			$email_args['from_name']  = $employee->display_name;
		}

		if ( tmem_send_email_content( $email_args ) ) {

			remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
			wp_update_post(
				array(
					'ID'            => $event->ID,
					'post_modified' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
			add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

			update_post_meta( $event->ID, '_tmem_event_last_updated_by', 0 );
			$event->complete_task( $task['slug'] );

			tmem_add_journal(
				array(
					'user_id'         => 1,
					'event_id'        => $event->ID,
					'comment_content' => sprintf( esc_html__( '%s task manually executed', 'mobile-events-manager' ), $task['name'] ) . '<br /><br />' . time(),
				)
			);

			return true;
		}
	}

	return false;
} // tmem_balance_reminder_single_task

/**
 * Executes the employee playlist notification task for a single event.
 *
 * @since 1.5
 * @param $event_id
 * @return bool True if task ran successfully
 */
function tmem_employee_playlist_notify_single_task( $event_id ) {
	$event = new TMEM_Event( $event_id );

	$content = tmem_format_playlist_content( $event_id, '', 'ASC', '', true );
	$content = apply_filters( 'tmem_print_playlist', $content, $event );

	$html_content_start = '<html>' . "\n" . '<body>' . "\n";
	$html_content_end   = '<p>' . __( 'Regards', 'mobile-events-manager' ) . '</p>' . "\n" .
		'<p>{company_name}</p>' . "\n";
		'<p>&nbsp;</p>' . "\n";
		'<p align="center" style="font-size: 9px">Powered by <a style="color:#F90" href="https://tmem.co.uk" target="_blank">' . TMEM_NAME . '</a> version ' . TMEM_VERSION_NUM . '</p>' . "\n" .
		'</body>' . "\n" . '</html>';

	$args = array(
		'to_email'   => tmem_get_employee_email( $event->employee_id ),
		'from_name'  => tmem_get_option( 'company_name' ),
		'from_email' => tmem_get_option( 'system_email' ),
		'event_id'   => $event_id,
		'client_id'  => $event->client,
		'subject'    => sprintf( esc_html__( 'Playlist for %1$s ID %2$s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), '{contract_id}' ),
		'message'    => $html_content_start . $content . $html_content_end,
		'copy_to'    => 'disable',
	);

	$event->complete_task( 'employee-playlist-notify' );
	return tmem_send_email_content( $args );
} // tmem_employee_playlist_notify_single_task
