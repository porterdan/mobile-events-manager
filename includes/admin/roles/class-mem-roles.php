<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Class Name: MEM_Roles
 * Manage User roles within MEM
 */
if ( ! class_exists( 'MEM_Roles' ) ) :
	class MEM_Roles {
		/**
		 * Class constructor
		 */
		public function __construct() {
			// Capture form submissions
			add_action( 'init', array( &$this, 'init' ) );

			// Runs after Events settings are updated
			add_action( 'update_option_mem_settings', array( &$this, 'rename_dj_role' ), 10, 2 );

			// Ajax event for adding new roles
			add_action( 'wp_ajax_mem_add_role', array( &$this, 'add_role' ) );

			// Display admin notices
			add_action( 'admin_notices', array( &$this, 'messages' ) );
		}

		/**
		 * Hook into the init action to process form submissions
		 */
		public function init() {
			if ( isset( $_POST['delete-roles'] ) ) {
				$this->delete_roles();
			}
		} // init

		/**
		 * Display admin notices to the user
		 */
		public function messages() {
			if ( ! isset( $_GET['page'] ) || 'mem-employees' !== $_GET['page'] || empty( $_GET['role_action'] ) || empty( $_GET['message'] ) ) {
				return;
			}

			$messages = array(
				1 => array( 'updated', __( 'Roles deleted.', 'mobile-events-manager' ) ),
				2 => array( 'updated', __( '', 'mobile-events-manager' ) ),
				3 => array( 'updated', __( '', 'mobile-events-manager' ) ),
				4 => array( 'updated', __( 'Permissions updated.', 'mobile-events-manager' ) ),
				5 => array( 'error', __( 'No roles selected for deletion.', 'mobile-events-manager' ) ),
				6 => array( 'error', __( 'Not all roles could be deleted. Do you still have users assigned?', 'mobile-events-manager' ) ),
				7 => array( 'updated', __( '', 'mobile-events-manager' ) ),
			);

			mem_update_notice( sanitize_key( wp_unslash( $messages[ $_GET['message'] ][0] ) ), sanitize_key( wp_unslash( $messages[ $_GET['message'] ][1] ) ), true );
		} // messages

		/**
		 * Add the default roles to MEM
		 *
		 * @since 1.3
		 * @param
		 * @return void
		 */
		public function add_roles() {

			add_role(
				'inactive_client',
				__( 'Inactive Client', 'mobile-events-manager' ),
				array( 'read' => true )
			);

			add_role(
				'client',
				__( 'Client', 'mobile-events-manager' ),
				array( 'read' => true )
			);

			add_role(
				'inactive_dj',
				__( 'Inactive DJ', 'mobile-events-manager' ),
				array(
					'read'          => true,
					'create_users'  => false,
					'edit_users'    => false,
					'delete_users'  => false,
					'edit_posts'    => false,
					'delete_posts'  => false,
					'publish_posts' => false,
					'upload_files'  => false,
				)
			);

			add_role(
				'dj',
				__( 'DJ', 'mobile-events-manager' ),
				array(
					'read'         => true,
					'edit_posts'   => true,
					'delete_posts' => true,
				)
			);

		} // add_roles

		/**
		 * Retrieve all MEM user roles by filtering through the registered WP roles
		 *
		 * @params
		 *
		 * @return arr $mem_roles An array of all MEM roles
		 */
		public function get_roles() {
			global $wp_roles;

			// Retrieve all roles within this WP instance
			$raw_roles = $wp_roles->get_names();

			// Loop through the $raw_roles and filter for mem specific roles
			foreach ( $raw_roles as $role_id => $role_name ) {
				if ( 'dj' === $role_id || false !== strpos( $role_id, 'mem-' ) ) {
					$mem_roles[ $role_id ] = $role_name;
				}
			}

			// Filter the roles
			$mem_roles = apply_filters( 'mem_user_roles', $mem_roles );

			return $mem_roles;
		} // get_roles

		/**
		 * Retrieve all MEM user roles and display return them as <options>
		 *
		 * @params arr $args Arguments to pass to the select list. See $defaults.
		 *
		 * @return str $output HTML code for the <options>
		 */
		public function roles_dropdown( $args = '' ) {
			$defaults = array(
				'selected'        => '',
				'disable_default' => false,
				'first_entry'     => '',
				'first_entry_val' => 0,
			);

			$args = wp_parse_args( $args, $defaults );

			$mem_roles = $this->get_roles();

			// Filter the roles
			$mem_roles = apply_filters( 'mem_user_roles', $mem_roles );

			if ( empty( $mem_roles ) ) {
				$output = '<option value="0" disabled>' . __( 'No roles defined', 'mobile-events-manager' ) . '</option>' . "\r\n";

			} else {
				$output = '';

				if ( ! empty( $args['first_entry'] ) ) {
					$output .= '<option value="' . $args['first_entry_val'] . '">' . $args['first_entry'] . '</option>' . "\r\n";
				}

				foreach ( $mem_roles as $role_id => $role ) {
					$output .= '<option value="' . $role_id . '"';

					if ( ! empty( $args['selected'] ) ) {
						$output .= selected( $args['selected'], $role_id, false );
					}

					if ( ! empty( $args['disable_default'] ) && ( 'administrator' === $role_id || 'dj' === $role_id ) ) {
						$output .= ' disabled';
					}

					$output .= '>' . $role . '</option>' . "\r\n";
				}
			}

			return $output;
		} // roles_dropdown

		/**
		 * Add new MEM user role.
		 * Call from employee management form via Ajax
		 *
		 * @param str $role The requested name of the role
		 *
		 * @return arr $result JSON encoded array
		 */
		public function add_role() {
			if ( empty( $_POST['role_name'] ) ) {
				$return['type'] = 'error';
				$return['msg']  = __( 'Ooops! You need to add a role name', 'mobile-events-manager' );
			} else {
				$result = add_role(
					'mem-' . sanitize_title_with_dashes( strtolower( str_replace( '_', '-', sanitize_key( wp_unslash( $_POST['role_name'] ) ) ) ) ),
					ucwords( sanitize_key( wp_unslash( $_POST['role_name'] ) ) )
				);

				if ( null !== $result ) {
					$return['type'] = 'success';

					$updated_roles = $this->roles_dropdown();

					$return['options'] = $updated_roles;
					$result->add_cap( 'mem_employee' );
					$result->add_cap( 'read' );
				} else {
					$return['type'] = 'error';
					$return['msg']  = __( 'Ooops! An error occured. Does the role already exist?', 'mobile-events-manager' );
				}
			}

			echo json_encode( $return );

			die();
		} // add_role

		/**
		 * Delete MEM user role(s).
		 * If any users are still assigned to the role, do not delete but report an error
		 *
		 * @param str|arr $roles Required: The role name to delete. $_POST['all_roles'] takes priority if exists
		 *
		 * @return arr $result JSON encoded array
		 */
		public function delete_roles( $roles = '' ) {
			if ( isset( $_POST['delete-roles'] ) && ! empty( $_POST['all_roles'] ) ) {
				$roles = sanitize_key( wp_unslash( $_POST['all_roles'] ) );
			}

			// No roles
			if ( empty( $roles ) ) {
				wp_safe_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] . '&role_action=1&message=5' ) );
				exit;
			}

			// $roles must be an array so let's ensure it is
			if ( ! is_array( $roles ) ) {
				$roles = array( $roles );
			}

			// Make sure there are no employees assigned to the roles marked for deletion
			$employees = mem_get_employees( $roles );

			if ( ! empty( $employees ) ) {
				wp_safe_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] . '&role_action=1&message=6' ) );
				exit;
			}

			// Loop through the roles and remove after ensuring there are no users assigned
			foreach ( $roles as $role ) {
				remove_role( $role );
			}
			wp_safe_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] . '&role_action=1&message=1' ) );
			exit;
		} // delete_roles

		/**
		 * Rename the DJ role display name when admin saves the event settings from the settings page.
		 * Include both the standard and inactive role.
		 *
		 * Called by: update_option_mem_event_settings hook
		 *
		 * @param arr $old_value Old settings values
		 * arr $new_value New settings values
		 */
		public function rename_dj_role( $old_value, $new_value ) {
			global $wpdb;

			// If the artist setting has not been updated, we can return and do nothing
			if ( $new_value['artist'] == $old_value['artist'] ) {
				return;
			}

			$user_roles = get_option( $wpdb->prefix . 'user_roles' );

			if ( empty( $user_roles ) ) {
				if ( MEM_DEBUG == true ) {
					MEM()->debug->log_it( 'ERROR: Could not retrieve user roles from DB', true );
				}

				return;
			}

			$user_roles['inactive_dj']['name'] = __( 'Inactive', 'mobile-events-manager' ) . ' ' . $new_value['artist'];
			$user_roles['dj']['name']          = $new_value['artist'];

			if ( update_option( $wpdb->prefix . 'user_roles', $user_roles ) ) {
				if ( MEM_DEBUG == true ) {
					MEM()->debug->log_it( 'Updated DJ role name to ' . $new_value['artist'], true );
				}

				return;
			} else {
				if ( MEM_DEBUG == true ) {
					MEM()->debug->log_it( 'ERROR: Could not update DJ role name to ' . $new_value['artist'], true );
				}
			}
		} // rename_dj_role

		/**
		 * Runs against the mem_user_roles filter to modify the results removing the Administrator option.
		 *
		 * @params arr $mem_roles The list of MEM roles
		 *
		 * @return arr $mem_roles Filtered list of MEM roles
		 */
		public static function no_admin_role( $mem_roles ) {

			if ( isset( $mem_roles['administrator'] ) ) {
				unset( $mem_roles['administrator'] );
			}

			return $mem_roles;
		} // no_admin_role

	} // MEM_Roles
endif;
