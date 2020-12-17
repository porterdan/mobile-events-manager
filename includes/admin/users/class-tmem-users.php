<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Class Name: TMEM_Users
 * Manage Users within TMEM
 */
if ( ! class_exists( 'TMEM_Users' ) ) :
	class TMEM_Users {
		/*
		 * Class constructor
		 */
		public function __construct() {
			// Capture form submissions
			add_action( 'init', array( &$this, 'remove_client_admin' ) );

			// Display custom user fields
			add_action( 'show_user_profile', array( &$this, 'profile_custom_fields' ) ); // User profile screen
			add_action( 'edit_user_profile', array( &$this, 'profile_custom_fields' ) ); // Edit user screen
			add_action( 'user_new_form', array( &$this, 'profile_custom_fields' ) ); // // New user screen

			// Save custom user fields
			add_action( 'user_register', array( &$this, 'save_custom_user_fields' ), 10, 1 );
			add_action( 'personal_options_update', array( &$this, 'save_custom_user_fields' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'save_custom_user_fields' ) );
			add_action( 'profile_update', array( &$this, 'admin_user_rights' ), 10, 2 );

			// When an employee is deleted
			add_action( 'deleted_user', array( $this, 'remove_employee_data' ) );

			// Capture the timestamp of a successful login
			add_action( 'wp_login', array( &$this, 'datestamp_login' ), 10, 2 );

			// Display admin notices
			add_action( 'admin_notices', array( &$this, 'messages' ) );
		}

		/**
		 * Display admin notices to the user
		 */
		public function messages() {
			if ( ! isset( $_GET['page'] ) || 'tmem-employees' !== $_GET['page'] || empty( $_GET['user_action'] ) || empty( $_GET['message'] ) ) {
				return;
			}

			$messages = array(
				2 => array( 'updated', __( 'Employees deleted.', 'mobile-events-manager' ) ),
			);

			tmem_update_notice( $messages[ sanitize_key( wp_unslash( $_GET['message'] ) ) ][0], $messages[ sanitize_key( wp_unslash( $_GET['message'] ) ) ][1], true );
		} // messages

		/**
		 * Present the employee management interface
		 *
		 * @called TMEM_Menu class
		 *
		 * @param
		 *
		 * @return
		 */
		public static function employee_manager() {
			wp_enqueue_script( 'tmem-users-js' );

			include 'class-tmem-employee-manager.php';
		} // employee_manager

		/**
		 * Present the client management interface
		 *
		 * @called: TMEM_Menu class
		 *
		 * @param
		 *
		 * @return
		 */
		public static function client_manager() {
			include 'class-tmem-client-manager.php';
		} // client_manager

		/**
		 * Adds a new employee and assigns the role
		 *
		 * @param arr $post_data
		 * 'first_name' Required: The first name of the employee.
		 * 'last_name' Required: The last name of the employee.
		 * 'user_email' Required: The email address of the employee.
		 * 'employee_role' Required: The role that the employee should be assigned.
		 *
		 * @return void
		 */
		public function add( $post_data ) {
			return tmem_add_employee( $post_data );
		} // add

		/**
		 * Retrieve a list of all employees
		 *
		 * @param str|arr $roles Optional: The roles for which we want to retrieve the employees from.
		 * str $orderby Optional: The field by which to order. Default display_name
		 * str $order Optional: ASC (default) | Desc
		 *
		 * @return $arr $employees or false if no employees for the specified roles
		 */
		public function get( $roles = '', $orderby = 'display_name', $order = 'ASC' ) {
			return tmem_get_employees( $roles = '', $orderby = 'display_name', $order = 'ASC' );
		} // get

		/**
		 * Set the role for the given employees
		 *
		 * @param int|arr $employees Required: Single user ID or array of user ID's to adjust
		 * str $role Required: The role ID to which the users will be moved
		 *
		 * @return
		 */
		public function set_role( $employees, $role ) {
			tmem_set_employee_role( $employees, $role );
		} // set_role

		/**
		 * Retrieve a list of all clients
		 *
		 * @param str|arr $roles Optional: The roles for which we want to retrieve the clients from.
		 * int $employee Optional: Only display clients of the given employee
		 * str $orderby Optional: The field by which to order. Default display_name
		 * str $order Optional: ASC (default) | Desc
		 *
		 * @return $arr $employees or false if no employees for the specified roles
		 */
		public function get_clients( $roles = '', $employee = '', $orderby = '', $order = '' ) {
			return tmem_get_clients( $roles = '', $employee = '', $orderby = '', $order = '' );
		} // get_clients

		/**
		 * Determine if the given client belongs to the given employee
		 * If no event is specified, true will be returned if the Employee has (or will) performed
		 * for the client at any time
		 *
		 * @params $client int Required: The user_ID of the client
		 * $employee int Optional: The user_ID of the employee. Uses current user if not specified.
		 * $event int Optional: The event ID to query
		 *
		 * @return bool True if client belongs to employee, otherwise false
		 */
		public function is_employee_client( $client, $employee = '', $event = '' ) {
			global $current_user;

			$args = array(
				'post_type'      => 'tmem-event',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_key'       => '_tmem_event_date',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_tmem_event_dj',
						'value'   => ! empty( $employee ) ? $employee : $current_user->ID,
						'compare' => '=',
					),
					array(
						'key'     => '_tmem_event_client',
						'value'   => $client,
						'compare' => '=',
					),
				),
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
			);

			if ( empty( $event ) ) {
				return ( count( get_posts( $args ) ) == 1 ? true : false );
			}

			$the_event = get_post( $event );

			// No events found return false
			if ( empty( $the_event ) ) {
				return false;
			}

			return ( get_post_meta( $the_event->ID, '_tmem_event_dj', true ) == $current_user->ID ) ? true : false;
		} // is_employee_client

		/**
		 * Add the TMEM Custom User Fields to the user profile page
		 *
		 * @param int $user The WP_User object
		 *
		 * @return
		 */
		public function profile_custom_fields( $user ) {

			global $current_screen, $user_ID, $pagenow;

			if ( 'user-new.php' !== $pagenow ) {
				$user_id = ( 'profile' === $current_screen->id ) ? $user_ID : sanitize_key( wp_unslash( $_REQUEST['user_id'] ) );
			}

			do_action( 'tmem_pre_profile_custom_fields', $user );

			echo '<h3>TMEM Event Management</h3>' . "\r\n";
			echo '<table class="form-table">' . "\r\n";

			// Is event staff checkbox for WP admins
			if ( isset( $user->ID ) && user_can( $user->ID, 'administrator' ) ) {

				$tmem_roles = tmem_get_roles();

				if ( get_current_user_id() !== $user->ID ) {

					echo '<tr>' . "\r\n";
					echo '<th><label for="_tmem_event_roles">' . sprintf( esc_html__( '%s Employee Role(s)', 'mobile-events-manager' ), tmem_get_option( 'company_name' ) ) . '</label></th>' . "\r\n";
					echo '<td>' . "\r\n";
					echo '<select name="_tmem_event_roles[]" id="_tmem_event_roles" multiple="multiple">';

					foreach ( $tmem_roles as $role_id => $role_name ) {
						echo '<option value="' . $role_id . '"';

						selected( in_array( $role_id, $user->roles ), true );

						echo '>' . $role_name . '</option>';
					}

					echo '</select>' . "\r\n";
					echo '</td>' . "\r\n";
					echo '</tr>' . "\r\n";

					echo '<tr>';

					echo '<th><label for="_tmem_event_admin">' . __( 'User is TMEM Admin?', 'mobile-events-manager' ) . '</label></th>' . "\r\n";
					echo '<td><input type="checkbox" name="_tmem_event_admin" id="_tmem_event_admin" value="1"';
					checked( $user->__get( '_tmem_event_admin' ), true );
					echo ' /></td>';

					echo '</tr>';

				} else {

					foreach ( $tmem_roles as $role_id => $role_name ) {
						if ( in_array( $role_id, $user->roles ) ) {
							echo '<input type="hidden" name="_tmem_event_roles[]" id="_tmem_event_roles_' . $role_id . '" value="' . $role_id . '" />' . "\r\n";
						}
					}
				}
			}

			do_action( 'tmem_pre_profile_custom_user_fields', $user );

			// Get the custom user fields
			$custom_fields = get_option( 'tmem_client_fields' );

			// Loop through the fields
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $custom_field ) {

					if ( 'user-new.php' !== $pagenow ) {
						$field_value = get_user_meta( $user_id, $custom_field['id'], true );
					}

					// Display if configured
					if ( true === $custom_field['display'] && 'first_name' !== $custom_field['id'] && 'last_name' !== $custom_field['id'] && 'user_email' !== $custom_field['id'] ) {

						echo '<tr>' . "\r\n" .
						'<th><label for="' . $custom_field['id'] . '">' . $custom_field['label'] . '</label></th>' . "\r\n" .
						'<td>' . "\r\n";

						// Checkbox Field
						if ( 'checkbox' === $custom_field['type'] ) {

							echo '<input type="' . $custom_field['type'] . '" name="' . $custom_field['id'] . '" id="' . $custom_field['id'] . '" value="' . $custom_field['value'] . '" ';

							if ( 'user-new.php' !== $pagenow ) {
								checked( $field_value, '1' );
							} else {
								checked( '', '' );
							}

							echo ' />' . "\r\n";
						}
						// Select List
						elseif ( 'dropdown' === $custom_field['type'] ) {

							echo '<select name="' . $custom_field['id'] . '" id="' . $custom_field['id'] . '">';

							$option_data = explode( "\r\n", $custom_field['value'] );

							echo '<option value="empty"';

							if ( 'user-new.php' === $pagenow || empty( $field_value ) || 'empty' === $field_value ) {
								echo ' selected';
							}

							echo '></option>' . "\r\n";

							foreach ( $option_data as $option ) {

								echo '<option value="' . $option . '"';

								if ( 'user-new.php' !== $pagenow ) {
									selected( $option, $field_value );
								}

								echo '>' . $option . '</option>' . "\r\n";
							}

							echo '<select/>';
						}
						// Everything else
						else {
							echo '<input type="' . $custom_field['type'] . '" name="' . $custom_field['id'] .
							'" id="' . $custom_field['id'] . '" value="' . ( 'user-new.php' !== $pagenow ? esc_attr( get_the_author_meta( $custom_field['id'], $user->ID ) ) : '' ) .
							'" class="regular-text" />' . "\r\n";
						}

						// Description if set
						if ( '' !== $custom_field['desc'] ) {
							echo '<br />' .
							'<span class="description">' . $custom_field['desc'] . '</span>' . "\r\n";
						}

						// End the table row
						echo '</td>' . "\r\n" .
						'</tr>' . "\r\n";

					}
				}
			}

			echo '</table>' . "\r\n";

			do_action( 'tmem_post_profile_custom_fields', $user );

		} // profile_custom_fields

		/**
		 * Save the TMEM Custom User Fields
		 *
		 * @since 1.0
		 * @param int $user_id The ID of the user
		 * @return void
		 */
		public function save_custom_user_fields( $user_id ) {

			do_action( 'tmem_pre_save_custom_user_fields', $user_id, $_POST );

			$custom_fields  = get_option( 'tmem_client_fields' );
			$default_fields = get_user_by( 'id', $user_id );

			if ( ! current_user_can( 'edit_user', $user_id ) ) {
				return;
			}

			// For administrators, determine if they should be an employee
			if ( get_current_user_id() !== $user_id && user_can( $user_id, 'administrator' ) && tmem_is_admin() ) {

				if ( ! empty( $_POST['_tmem_event_roles'] ) ) {
					update_user_meta( $user_id, '_tmem_event_staff', true );
					update_user_meta( $user_id, '_tmem_event_roles', sanitize_key( wp_unslash( $_POST['_tmem_event_roles'] ) ) );
				} else {
					update_user_meta( $user_id, '_tmem_event_staff', false );
					update_user_meta( $user_id, '_tmem_event_roles', false );
				}

				if ( ! empty( $_POST['_tmem_event_admin'] ) ) {
					update_user_meta( $user_id, '_tmem_event_admin', true );
				} else {
					update_user_meta( $user_id, '_tmem_event_admin', false );
				}
			}

			// Loop through the fields and update
			if ( ! empty( $custom_fields ) ) {

				foreach ( $custom_fields as $custom_field ) {

					$field = $custom_field['id'];

					// Checkbox unchecked = N
					if ( 'checkbox' === $custom_field['type'] && empty( $_POST[ $field ] ) ) {
						$_POST[ $field ] = 0;
					}

					// Update the users meta data
					if ( ! empty( $_POST[ $field ] ) ) {
						update_user_meta( $user_id, $field, sanitize_key( wp_unslash( $_POST[ $field ] ) ) );
					} else {
						delete_user_meta( $user_id, $field );
					}

					/**
					 * For new users, remove the admin bar
					 * and set the action to created
					 */
					if ( isset( $_POST['action'] ) && 'createuser' === $_POST['action'] ) {

						update_user_option( $user_id, 'show_admin_bar_front', false );

						if ( ! empty( $default_fields->first_name ) && ! empty( $default_fields->last_name ) ) {
							update_user_option( $user_id, 'display_name', $default_fields->first_name . ' ' . $default_fields->last_name );
						}

						$client_action = 'created';

					} else {
						$client_action = 'updated';
					}
				}
			}

			do_action( 'tmem_post_save_custom_user_fields', $user_id, $_POST );

		} // save_custom_user_fields

		/**
		 * Generate and Save API key
		 *
		 * Generates the key requested by user_key_field and stores it in the database
		 *
		 * @since 1.4
		 * @param int $user_id
		 * @return void
		 */
		function update_user_api_key( $user_id ) {

			if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['tmem_set_api_key'] ) ) {

				$user       = get_userdata( $user_id );
				$public_key = TMEM()->api->get_user_public_key( $user_id );

				if ( empty( $public_key ) ) {

					$new_public_key = TMEM()->api->generate_public_key( $user->user_email );
					$new_secret_key = TMEM()->api->generate_private_key( $user->ID );

					update_user_meta( $user_id, $new_public_key, 'tmem_user_public_key' );
					update_user_meta( $user_id, $new_secret_key, 'tmem_user_secret_key' );

				} else {
					TMEM()->api->revoke_api_key( $user_id );
				}
			}

		} // update_user_api_key

		/**
		 * Assign the 'DJ' role to an administrator
		 *
		 * @since 1.3
		 * @param int $user_id User ID.
		 * @param int $old_data Object containing user's data prior to update.
		 * @return
		 */
		public function admin_user_rights( $user_id, $old_data ) {

			if ( ! user_can( $user_id, 'administrator' ) ) {
				return;
			}

			// Retrieve the current user object after the profile update
			$user = new WP_User( $user_id );

			$is_staff       = $user->__get( '_tmem_event_staff' );
			$required_roles = $user->__get( '_tmem_event_roles' );
			$make_admin     = $user->__get( '_tmem_event_admin' );
			$tmem_roles     = tmem_get_roles();

			if ( ! empty( $is_staff ) && ! empty( $required_roles ) ) {

				// Reset roles and caps before applying updates due to some wierd bug
				foreach ( $tmem_roles as $role_id => $role_name ) {
					$user->remove_role( $role_id );
				}

				$user->remove_cap( 'tmem_employee' );

				foreach ( $required_roles as $role_id ) {
					$user->add_role( $role_id );
				}

				$user->add_cap( 'tmem_employee' );

				delete_user_meta( $user->ID, '_tmem_event_roles' );

			} else {

				foreach ( $tmem_roles as $role_id => $role_name ) {
					$user->remove_role( $role_id );
				}

				$user->remove_cap( 'tmem_employee' );

			}

			$permissions = new TMEM_Permissions();

			if ( ! empty( $make_admin ) ) {
				$permissions->make_admin( $user->ID );
				$user->add_cap( 'tmem_employee' );
			} else {
				$permissions->make_admin( $user->ID, true );
				$user->remove_cap( 'tmem_employee' );
			}

		} // admin_user_rights

		/**
		 * Remove employees absence entries when their user account is deleted.
		 *
		 * @since 1.5.7
		 * @param int $user_id The WP user ID
		 * @return void
		 */
		function remove_employee_data( $user_id ) {
			$remove_absences = tmem_get_option( 'remove_absences_on_delete', false );

			if ( $remove_absences ) {
				$absences = tmem_get_employee_absences( $user_id );

				foreach ( $absences as $absence ) {
					tmem_remove_employee_absence( $absence->id );
				}
			}

		} // remove_employee_data

		/**
		 * Remove admin bar & do not allow admin UI for Clients.
		 * Redirect to Client Zone.
		 *
		 * @called init
		 *
		 * @params
		 *
		 * @return void
		 */
		public function remove_client_admin() {

			if ( current_user_can( 'client' ) || current_user_can( 'inactive_client' ) ) {
				add_filter( 'show_admin_bar', '__return_false' );

				if ( is_admin() ) {

					if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
						wp_safe_redirect( tmem_get_formatted_url( tmem_get_option( 'app_home_page' ), false ) );
						exit;
					}
				}
			}

		} // remove_client_admin

		/**
		 * Prepare a user for password reset.
		 *
		 * @called
		 *
		 * @params int $user_id Required: xThe ID of the user who needs preparing.
		 *
		 * @return bool True on success, otherwise false.
		 */
		public function prepare_user_pass_reset( $user_id ) {

			TMEM()->debug->log_it( 'Preparing user ' . $user_id . ' for password reset' );

			$reset = update_user_meta(
				$user_id,
				'tmem_pass_action',
				wp_generate_password( tmem_get_option( 'pass_length' ) )
			);

			TMEM()->debug->log_it( 'Password preparation ' . ! empty( $reset ) ? 'success' : 'fail' );

			return $reset;

		} // prepare_user_pass_reset

		/**
		 * Log the users login time.
		 *
		 * @since 1.3
		 * @param str $user_login Username of the user
		 * @param obj $user WP_User object of the logged in user
		 * @return void
		 */
		public function datestamp_login( $user_login, $user ) {
			update_user_meta( $user->ID, 'last_login', current_time( 'mysql' ) );
		} // datestamp_login
	} // class TMEM_Users
endif;
