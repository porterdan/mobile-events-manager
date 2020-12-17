<?php
/**
 * Task Object
 *
 * @package TMEM
 * @subpackage Classes/Tasks
 * @copyright Copyright (c) 2017, Mike Howard
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMEM_Task_Runner Class
 *
 * @since 1.4.7
 */
class TMEM_Task_Runner {

	/**
	 * All tasks
	 *
	 * @since 1.4.7
	 * @arr
	 * @var arr $all_tasks All tasks
	 */
	public $all_tasks = array();

	/**
	 * The task slug
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $slug the task slug
	 */
	public $slug;

	/**
	 * The task name
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $name task name
	 */
	public $name;

	/**
	 * Active task
	 *
	 * @since 1.4.7
	 * @bool
	 * @var bool $active Active Task
	 */
	public $active;

	/**
	 * The task description
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $description Task Description
	 */
	public $description;

	/**
	 * The task frequency
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $frequency Task Frequency
	 */
	public $frequency;

	/**
	 * Last run
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $last_run Last Run
	 */
	public $last_run;

	/**
	 * Next run
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $next_run Next Run
	 */
	public $next_run;

	/**
	 * Total runs
	 *
	 * @since 1.4.7
	 * @int
	 * @var int $total
	 */
	public $total;

	/**
	 * The task options
	 *
	 * @since 1.4.7
	 * @var arr $options task options
	 */
	public $options;

	/**
	 * Default task
	 *
	 * @since 1.4.7
	 * @bool
	 * @var bool $default default talk
	 */
	public $default;

	/**
	 * The last result
	 *
	 * @since 1.4.7
	 * @str
	 * @var str $last_result last result
	 */
	public $last_result;

	/**
	 * Get things going
	 *
	 * @since 1.4.7
	 * @param arr $task task.
	 */
	public function __construct( $task = false ) {
		if ( empty( $task ) ) {
			return false;
		}

		if ( $this->setup_task( $task ) ) {
			return $this->execute();
		}

		return false;
	} // __construct

	/**
	 * Given the task slug, let's set the variables
	 *
	 * @since 1.4.7
	 * @param str $task The Task slug.
	 * @return bool If the setup was successful or not
	 */
	private function setup_task( $task ) {
		$this->all_tasks = get_option( 'tmem_schedules' );
		if ( empty( $this->all_tasks ) || ! array_key_exists( $task, $this->all_tasks ) ) {
			return false;
		}

		$this_task = $this->all_tasks[ $task ];

		$this->slug        = $task;
		$this->name        = $this_task['name'];
		$this->description = $this_task['desc'];
		$this->frequency   = $this_task['frequency'];
		$this->active      = ! empty( $this_task['active'] ) ? true : false;
		$this->next_run    = ! empty( $this_task['nextrun'] ) ? $this_task['nextrun'] : false;
		$this->last_run    = ! empty( $this_task['lastran'] ) ? $this_task['lastran'] : 'never';
		$this->total       = ! empty( $this_task['totalruns'] ) ? $this_task['totalruns'] : '0';
		$this->default     = ! empty( $this_task['default'] ) ? true : false;
		$this->last_result = ! empty( $this_task['last_result'] ) ? $this_task['last_result'] : false;
		$this->options     = $this_task['options'];

		if ( ! $this->ready_to_execute() ) {
			return false;
		}

		return true;

	} // setup_task

	/**
	 * Determine if the task should be execute
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	private function ready_to_execute() {
		if ( ! $this->active ) {
			return false;
		}

		$now = current_time( 'timestamp' );

		if ( empty( $this->next_run ) || $this->next_run <= $now ) {
			return true;
		}

		return false;
	} // ready_to_execute

	/**
	 * Execute the task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function execute() {
		$method = str_replace( '-', '_', $this->slug );

		if ( method_exists( $this, $method ) ) {
			if ( $this->$method() ) {
				return $this->complete_task();
			}
		}

		return false;
	} // execute

	/**
	 * Whether or not the task has run
	 *
	 * @since 1.4.7
	 * @param obj $event Post object.
	 * @return bool
	 */
	public function task_has_run( $event ) {
		$tasks = $event->get_tasks();

		if ( ! empty( $tasks ) && array_key_exists( $this->slug, $tasks ) ) {
			return true;
		}

		return false;
	} // task_has_run

	/**
	 * Complete the task
	 *
	 * @since 1.4.7
	 */
	public function complete_task() {
		$this->all_tasks[ $this->slug ]['totalruns'] = $this->total + 1;
		$this->all_tasks[ $this->slug ]['nextrun']   = current_time( 'timestamp' ) + $this->set_next_run();
		$this->all_tasks[ $this->slug ]['lastran']   = current_time( 'timestamp' );

		return update_option( 'tmem_schedules', $this->all_tasks );
	} // complete_task

	/**
	 * Set the next run time
	 *
	 * @since 1.4.7
	 * @return str
	 */
	public function set_next_run() {
		switch ( $this->frequency ) {
			case 'Daily':
				$wait = DAY_IN_SECONDS;
				break;

			case 'Twice Daily':
				$wait = DAY_IN_SECONDS / 2;
				break;

			case 'Weekly':
				$wait = WEEK_IN_SECONDS;
				break;

			case 'Monthly':
				$wait = MONTH_IN_SECONDS;
				break;

			case 'Yearly':
				$wait = YEAR_IN_SECONDS;
				break;

			case 'Hourly':
			default:
				$wait = HOUR_IN_SECONDS;
				break;
		}

		return $wait;
	} // set_next_run

	/**
	 * Execute the Upload Playlist task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function upload_playlists() {
		TMEM()->debug->log_it( "*** Starting the $this->name task ***", true );

		tmem_process_playlist_upload();

		TMEM()->debug->log_it( "*** $this->name task Completed ***", true );

		return true;
	} // upload_playlists

	/**
	 * Execute the Complete Events task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function complete_events() {
		TMEM()->debug->log_it( "*** Starting the $this->name task ***", true );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'event', 'events', $events, 'mobile-events-manager' ) . ' to be marked as completed' );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );

				if ( ! $event ) {
					continue;
				}

				if ( $this->task_has_run( $event ) ) {
					continue;
				}

				$date_format = 'Y-m-d H:i:s';
				$time        = $event->get_finish_time();
				$end_date    = get_post_meta( $event->ID, '_tmem_event_end_date', true );

				if ( ! $end_date ) {
					$end_date = $event->date;
				}

				$end_time      = DateTime::createFromFormat( $date_format, $end_date . ' ' . $time );
				$mark_complete = strtotime( '+' . $this->options['age'] );

				if ( $mark_complete < strtotime( $end_time->format( $date_format ) ) ) {
					continue;
				}

				$update = tmem_update_event_status( $event->ID, 'tmem-completed', $event->post_status );
				if ( $update ) {

					if ( tmem_get_option( 'employee_auto_pay_complete' ) ) {
						tmem_pay_event_employees( $event->ID );
					}

					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => __( 'Event marked as completed via Scheduled Task', 'mobile-events-manager' ) . '<br /><br />' . time(),
						)
					);

					$event->complete_task( $this->slug );
					$completed++;

					TMEM()->debug->log_it( 'Event ' . $event->ID . ' marked as completed' );
				} else {
					TMEM()->debug->log_it( 'Event ' . $event->ID . ' could not be marked as completed' );
				}
			}
			add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
		}

		TMEM()->debug->log_it( "$completed event(s) marked as completed" );
		TMEM()->debug->log_it( "*** $this->name task Completed ***", true );

		return true;
	} // complete_events

	/**
	 * Execute the Fail Enquiry task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function fail_enquiry() {
		TMEM()->debug->log_it( "*** Starting the $this->name task ***", true );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'enquiry', 'enquiries', $events, 'mobile-events-manager' ) . ' to be marked as failed' );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );

				if ( ! $event ) {
					continue;
				}

				if ( $this->task_has_run( $event ) ) {
					continue;
				}

				$update = tmem_update_event_status( $event->ID, 'tmem-failed', $event->post_status );
				if ( $update ) {
					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => __( 'Enquiry marked as lost via Scheduled Task', 'mobile-events-manager' ) . '<br /><br />' . time(),
						)
					);

					$event->complete_task( $this->slug );
					$completed++;

					TMEM()->debug->log_it( 'Event ' . $event->ID . ' marked as failed' );
				} else {
					TMEM()->debug->log_it( 'Event ' . $event->ID . ' could not be marked as failed' );
				}
			}
			add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
		}

		TMEM()->debug->log_it( "*** $this->name task Completed ***", true );

		return true;
	} // fail_enquiry

	/**
	 * Execute the Balance Reminder task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function balance_reminder() {
		TMEM()->debug->log_it( "*** Starting the $this->name task ***", true );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'event', 'events', $events, 'mobile-events-manager' ) . ' due balance' );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );
				TMEM()->debug->log_it( 'Event: ' . $event->ID );
				if ( ! $event ) {
					continue;
				}

				if ( $this->task_has_run( $event ) ) {
					continue;
				}

				if ( empty( $this->options['email_template'] ) || empty( $event->client ) ) {
					continue;
				}

				$client = get_userdata( $event->client );

				$email_args = array(
					'to_email'  => $client->user_email,
					'event_id'  => $event->ID,
					'client_id' => $event->client,
					'subject'   => $this->options['email_subject'],
					'message'   => tmem_get_email_template_content( $this->options['email_template'] ),
					'track'     => true,
					/* translators: %s: Company name */
					'source'    => sprintf( esc_html__( 'Request %s Scheduled Task', 'mobile-events-manager' ), tmem_get_balance_label() ),
				);

				if ( 'employee' === $this->options['email_from'] && ! empty( $event->employee_id ) ) {
					$employee                 = get_userdata( $event->employee_id );
					$email_args['from_email'] = $employee->user_email;
					$email_args['from_name']  = $employee->display_name;
				}

				if ( tmem_send_email_content( $email_args ) ) {
					TMEM()->debug->log_it( 'Balance reminder sent to ' . $client->display_name );

					remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
					wp_update_post(
						array(
							'ID'            => $event->ID,
							'post_modified' => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

					update_post_meta( $event->ID, '_tmem_event_last_updated_by', 0 );
					$event->complete_task( $this->slug );

					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => $this->name . ' task executed<br /><br />' . time(),
						)
					);

				} else {
					TMEM()->debug->log_it( 'ERROR: Balance reminder was not sent. Event ID ' . $event->ID );
				}
			}
		} else {
			TMEM()->debug->log_it( 'No events requiring balance reminders' );
		}

		TMEM()->debug->log_it( "*** $this->name task Completed ***", true );

		return true;
	} // balance_reminder

	/**
	 * Execute the Deposit Reminder task
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function request_deposit() {
		TMEM()->debug->log_it( "*** Starting the $this->name task ***", true );

		$due_date = gmdate( 'Y-m-d', strtotime( '-' . $this->options['age'] ) );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'event', 'events', $events, 'mobile-events-manager' ) . ' due deposit' );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );

				if ( ! $event ) {
					continue;
				}

				if ( $this->task_has_run( $event ) ) {
					continue;
				}

				if ( empty( $this->options['email_template'] ) || empty( $event->client ) ) {
					continue;
				}

				$client = get_userdata( $event->client );

				$email_args = array(
					'to_email'  => $client->user_email,
					'event_id'  => $event->ID,
					'client_id' => $event->client,
					'subject'   => $this->options['email_subject'],
					'message'   => tmem_get_email_template_content( $this->options['email_template'] ),
					'track'     => true,
					/* translators: %s: Company name */
					'source'    => sprintf( esc_html__( 'Request %s Scheduled Task', 'mobile-events-manager' ), tmem_get_deposit_label() ),
				);

				if ( 'employee' === $this->options['email_from'] && ! empty( $event->employee_id ) ) {
					$employee                 = get_userdata( $event->employee_id );
					$email_args['from_email'] = $employee->user_email;
					$email_args['from_name']  = $employee->display_name;
				}

				if ( tmem_send_email_content( $email_args ) ) {
					TMEM()->debug->log_it( 'Deposit request sent to ' . $client->display_name );

					remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
					wp_update_post(
						array(
							'ID'            => $event->ID,
							'post_modified' => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					add_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );

					update_post_meta( $event->ID, '_tmem_event_last_updated_by', 0 );
					$event->complete_task( $this->slug );

					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => $this->name . ' task executed<br /><br />' . time(),
						)
					);

				} else {
					TMEM()->debug->log_it( 'ERROR: Deposit request was not sent. Event ID ' . $event->ID );
				}
			}
		} else {
			TMEM()->debug->log_it( 'No events requiring deposit requests' );
		}

		TMEM()->debug->log_it( "*** $this->name task Completed ***", true );

		return true;
	} // request_deposit

	/**
	 * Execute the Playlist Notification task
	 *
	 * @since 1.5
	 * @return bool
	 */
	public function playlist_notification() {
		TMEM()->debug->log_it( "*** Starting the {$this->name} task ***", true );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'event', 'events', $events, 'mobile-events-manager' ) . ' have new playlist entries within the past 24 hours' );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );

				if ( ! $event ) {
					continue;
				}

				$entries = tmem_get_playlist_entries(
					$event->ID,
					array(
						'posts_per_page' => 1,
						'date_query'     => array(
							array( 'after' => '24 hours ago' ),
						),
					)
				);

				if ( ! $entries ) {
					continue;
				}

				if ( empty( $this->options['email_template'] ) || empty( $event->client ) ) {
					continue;
				}

				$client = get_userdata( $event->client );

				$email_args = array(
					'to_email'  => $client->user_email,
					'event_id'  => $event->ID,
					'client_id' => $event->client,
					'subject'   => $this->options['email_subject'],
					'message'   => tmem_get_email_template_content( $this->options['email_template'] ),
					'track'     => true,
					/* translators: %s: Company name */
					'source'    => sprintf( esc_html__( '%s Scheduled Task', 'mobile-events-manager' ), $this->name ),
				);

				if ( 'employee' === $this->options['email_from'] && ! empty( $event->employee_id ) ) {
					$employee                 = get_userdata( $event->employee_id );
					$email_args['from_email'] = $employee->user_email;
					$email_args['from_name']  = $employee->display_name;
				}

				if ( tmem_send_email_content( $email_args ) ) {
					TMEM()->debug->log_it( $this->name . ' sent to ' . $client->display_name );

					delete_post_meta( $event->ID, '_tmem_playlist_client_notify' );

					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => "{$this->name} task executed<br /><br />" . time(),
						)
					);

				} else {
					TMEM()->debug->log_it( 'ERROR: Playlist notification was not sent. Event ID ' . $event->ID );
				}
			}
		} else {
			TMEM()->debug->log_it( 'No events have new playlist entries within the past 24 hours' );
		}

		TMEM()->debug->log_it( "*** {$this->name} task Completed ***", true );

		return true;
	} // playlist_notification

	/**
	 * Execute the Playlist Employee Notification task
	 *
	 * @since 1.5
	 * @return bool
	 */
	public function playlist_employee_notify() {
		TMEM()->debug->log_it( "*** Starting the {$this->name} task ***", true );

		$i         = 1;
		$completed = 0;
		$events    = tmem_get_events( $this->build_query() );

		if ( $events ) {
			$count = count( $events );
			TMEM()->debug->log_it( $count . ' ' . _n( 'event', 'events', $events, 'mobile-events-manager' ) . " are scheduled within the next {$this->options['age']}" );

			foreach ( $events as $_event ) {
				$event = new TMEM_Event( $_event->ID );

				if ( ! $event ) {
					continue;
				}

				if ( $this->task_has_run( $event ) ) {
					continue;
				}

				if ( empty( $this->options['email_template'] ) || empty( $event->employee_id ) ) {
					continue;
				}

				$entries = tmem_get_playlist_entries(
					$event->ID,
					array(
						'posts_per_page' => 1,
					)
				);

				if ( ! $entries ) {
					continue;
				}

				$employee = get_userdata( $event->employee_id );

				$email_args = array(
					'to_email'  => $employee->user_email,
					'event_id'  => $event->ID,
					'client_id' => $event->client,
					'subject'   => $this->options['email_subject'],
					'message'   => tmem_get_email_template_content( $this->options['email_template'] ),
					'track'     => true,
					/* translators: %s: Company name */
					'source'    => sprintf( esc_html__( '%s Scheduled Task', 'mobile-events-manager' ), $this->name ),
				);

				if ( 'employee' === $this->options['email_from'] && ! empty( $event->employee_id ) ) {
					$email_args['from_email'] = $employee->user_email;
					$email_args['from_name']  = $employee->display_name;
				}

				if ( tmem_send_email_content( $email_args ) ) {
					TMEM()->debug->log_it( $this->name . ' sent to ' . $employee->display_name );

					$event->complete_task( $this->slug );

					tmem_add_journal(
						array(
							'user_id'         => 1,
							'event_id'        => $event->ID,
							'comment_content' => "{$this->name} task executed<br /><br />" . time(),
						)
					);

				} else {
					TMEM()->debug->log_it( 'ERROR: Playlist notification was not sent. Event ID ' . $event->ID );
				}
			}
		} else {
			TMEM()->debug->log_it( "No events are scheduled within the next {$this->options['age']}" );
		}

		TMEM()->debug->log_it( "*** {$this->name} task Completed ***", true );

		return true;
	} // playlist_employee_notify

	/**
	 * Build the task query
	 *
	 * @since 1.4.7
	 * @return bool
	 */
	public function build_query() {
		if ( 'after_approval' !== $this->options['run_when'] ) {
			$run_date   = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $this->options['age'] ) );
			$date_query = array(
				'key'     => '_tmem_event_task_after_approval_' . $this->slug,
				'compare' => '<',
				'value'   => $run_date,
				'type'    => 'datetime',
			);
		} else {
			if ( 'before_event' === $this->options['run_when'] ) {
				$run_date   = gmdate( 'Y-m-d', strtotime( '+' . $this->options['age'] ) );
				$date_query = array(
					'key'     => '_tmem_event_date',
					'compare' => '<=',
					'value'   => $run_date,
					'type'    => 'date',
				);
			} else {
				$run_date   = gmdate( 'Y-m-d', strtotime( '-' . $this->options['age'] ) );
				$date_query = array(
					'key'     => '_tmem_event_date',
					'compare' => '>=',
					'value'   => $run_date,
					'type'    => 'date',
				);
			}
		}

		switch ( $this->slug ) {
			case 'complete-events':
				$query = array(
					'post_status' => 'tmem-approved',
					'meta_key'    => '_tmem_event_date',
					'orderby'     => 'meta_value',
					'order'       => 'ASC',
					'meta_query'  => array(
						'key'     => '_tmem_event_date',
						'value'   => gmdate( 'Y-m-d' ),
						'type'    => 'date',
						'compare' => '<=',
					),
				);
				break;

			case 'fail-enquiry':
				$expired = gmdate( 'Y-m-d', strtotime( '-' . $this->options['age'] ) );

				$query = array(
					'post_status' => array( 'tmem-unattended', 'tmem-enquiry' ),
					'date_query'  => array(
						'before' => $expired,
					),
				);
				break;

			case 'balance-reminder':
				$query = array(
					'post_status' => array( 'tmem-approved', 'tmem-awaitingdeposit' ),
					'meta_query'  => array(
						'relation' => 'AND',
						array(
							'key'     => '_tmem_event_date',
							'compare' => '>=',
							'value'   => gmdate( 'Y-m-d' ),
							'type'    => 'date',
						),
						$date_query,
						array(
							'key'   => '_tmem_event_balance_status',
							'value' => 'Due',
						),
						array(
							'key'     => '_tmem_event_cost',
							'value'   => '0.00',
							'compare' => '>',
						),
					),
				);
				break;

			case 'request-deposit':
				$query = array(
					'post_status' => array( 'tmem-approved', 'tmem-awaitingdeposit' ),
					'meta_query'  => array(
						'relation' => 'AND',
						$date_query,
						array(
							'key'     => '_tmem_event_date',
							'compare' => '>=',
							'value'   => gmdate( 'Y-m-d' ),
							'type'    => 'date',
						),
						array(
							'key'   => '_tmem_event_deposit_status',
							'value' => 'Due',
						),
						array(
							'key'     => '_tmem_event_deposit',
							'value'   => '0.00',
							'compare' => '>',
						),
					),
				);
				break;

			case 'playlist-notification':
				$query = array(
					'meta_key'   => '_tmem_playlist_client_notify',
					'meta_value' => '1',
				);
				break;

			case 'playlist-employee-notify':
				$start = gmdate( 'Y-m-d' );
				$end   = gmdate( 'Y-m-d', strtotime( '+' . $this->options['age'] ) );
				$query = array(
					'post_status' => 'tmem-approved',
					'meta_query'  => array(
						array(
							'key'     => '_tmem_event_date',
							'compare' => 'BETWEEN',
							'value'   => array( $start, $end ),
							'type'    => 'date',
						),
					),
				);
				break;

			default:
				$query = false;
		}

		return apply_filters( 'tmem_task_query', $query, $this->slug, $this );
	} // build_query

} // TMEM_Task_Runner
