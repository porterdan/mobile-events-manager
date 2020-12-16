<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Class Name: MEM_Permissions
 * Manage User permissions within MEM
 */
class MEM_Permissions {
	/**
	 * Class constructor
	 */
	public function __construct() {
		// Capture form submissions
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Hook into the init action to process form submissions
	 */
	public function init() {
		if ( isset( $_POST['set-permissions'], $_POST['mem_set_permissions'] ) ) {
			$this->set_permissions();
		}
	} // init

	/**
	 * Set permissions for the given roles
	 *
	 * @since 1.3
	 * @param
	 * @return
	 */
	public function set_permissions() {

		if ( ! isset( $_POST['employee_roles'] ) ) {
			return;
		}

		$fields = array(
			'comm_permissions'     => 'mem_comms',
			'client_permissions'   => 'mem_client',
			'employee_permissions' => 'mem_employee',
			'event_permissions'    => 'mem_event',
			'package_permissions'  => 'mem_package',
			'quote_permissions'    => 'mem_quote',
			'report_permissions'   => 'mem_reports',
			'template_permissions' => 'mem_template',
			'txn_permissions'      => 'mem_txn',
			'venue_permissions'    => 'mem_venue',
		);

		foreach ( sanitize_key( wp_unslash( $_POST['employee_roles'] ) ) as $_role ) {
			$role = get_role( $_role );

			// If the role is to become admin
			if ( ! empty( $_POST[ 'manage_mem_' . $_role ] ) ) {

				$role->add_cap( 'manage_mem' );
				$this->make_admin( $_role );
				continue;

			} else {
				$role->remove_cap( 'manage_mem' );
			}

			// Every role has the MEM Employee capability
			$role->add_cap( 'mem_employee' );

			foreach ( $fields as $field => $prefix ) {

				$caps = empty( $_POST[ $field . '_' . $_role ] ) ?
					$this->get_capabilities( $prefix . '_none' ) :
					$this->get_capabilities(
						sanitize_key( wp_unslash( $_POST[ $field . '_' . $_role ] ) )
					);

				foreach ( $caps as $cap => $val ) {

					if ( empty( $val ) ) {
						$role->remove_cap( $cap );
					} else {
						$role->add_cap( $cap );
					}
				}
			}
		}

		wp_safe_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] . '&role_action=1&message=4' ) );
		exit;

	} // set_permissions

	/**
	 * Defines all of the required capabilities for the requested capability.
	 *
	 * @since 1.3
	 * @param str $cap The capability being requested
	 * @return arr $caps The required capabilities that the requested capability needs or false if they cannot be calculated
	 */
	public function get_capabilities( $cap ) {

		switch ( $cap ) {
			/**
			 * Clients
			 */
			case 'mem_client_none':
				$caps = array(
					'mem_client_edit'     => false,
					'mem_client_edit_own' => false,
				);
				break;

			case 'mem_client_edit_own':
				$caps = array(
					'mem_client_edit'     => false,
					'mem_client_edit_own' => true,
				);
				break;

			case 'mem_client_edit':
				$caps = array(
					'mem_client_edit'     => true,
					'mem_client_edit_own' => true,
				);
				break;
			/**
			 * Communications
			 */
			case 'mem_comms_none':
				$caps = array(
					'mem_comms_send'             => false,
					'edit_mem_comms'             => false,
					'edit_others_mem_comms'      => false,
					'publish_mem_comms'          => false,
					'read_private_mem_comms'     => false,
					'edit_published_mem_comms'   => false,
					'delete_mem_comms'           => false,
					'delete_others_mem_comms'    => false,
					'delete_private_mem_comms'   => false,
					'delete_published_mem_comms' => false,
					'edit_private_mem_comms'     => false,
				);
				break;

			case 'mem_comms_send':
				$caps = array(
					'mem_comms_send'             => true,
					'edit_mem_comms'             => true,
					'edit_others_mem_comms'      => true,
					'publish_mem_comms'          => true,
					'read_private_mem_comms'     => true,
					'edit_published_mem_comms'   => true,
					'delete_mem_comms'           => true,
					'delete_others_mem_comms'    => true,
					'delete_private_mem_comms'   => true,
					'delete_published_mem_comms' => true,
					'edit_private_mem_comms'     => true,
				);
				break;

			/**
			 * Employees
			 */
			case 'mem_employee_none':
				$caps = array( 'mem_employee_edit' => false );
				break;

			case 'mem_employee_edit':
				$caps = array( 'mem_employee_edit' => true );
				break;
			/**
			 * Events
			 */
			case 'mem_event_none':
				$caps = array(
					'mem_event_read'           => false,
					'mem_event_read_own'       => false,
					'mem_event_edit'           => false,
					'mem_event_edit_own'       => false,
					'publish_mem_events'       => false,
					'edit_mem_events'          => false,
					'edit_others_mem_events'   => false,
					'delete_mem_events'        => false,
					'delete_others_mem_events' => false,
					'read_private_mem_events'  => false,
				);
				break;

			case 'mem_event_read_own':
				$caps = array(
					'mem_event_read'           => false,
					'mem_event_read_own'       => true,
					'mem_event_edit'           => false,
					'mem_event_edit_own'       => false,
					'publish_mem_events'       => false,
					'edit_mem_events'          => true,
					'edit_others_mem_events'   => true,
					'delete_mem_events'        => false,
					'delete_others_mem_events' => false,
					'read_private_mem_events'  => true,
				);
				break;

			case 'mem_event_read':
				$caps = array(
					'mem_event_read'           => true,
					'mem_event_read_own'       => true,
					'mem_event_edit'           => false,
					'mem_event_edit_own'       => false,
					'publish_mem_events'       => false,
					'edit_mem_events'          => true,
					'edit_others_mem_events'   => true,
					'delete_mem_events'        => false,
					'delete_others_mem_events' => false,
					'read_private_mem_events'  => true,
				);
				break;

			case 'mem_event_edit_own':
				$caps = array(
					'mem_event_read'           => false,
					'mem_event_read_own'       => true,
					'mem_event_edit'           => false,
					'mem_event_edit_own'       => true,
					'publish_mem_events'       => true,
					'edit_mem_events'          => true,
					'edit_others_mem_events'   => true,
					'delete_mem_events'        => false,
					'delete_others_mem_events' => false,
					'read_private_mem_events'  => true,
				);
				break;

			case 'mem_event_edit':
				$caps = array(
					'mem_event_read'           => true,
					'mem_event_read_own'       => true,
					'mem_event_edit'           => true,
					'mem_event_edit_own'       => true,
					'publish_mem_events'       => true,
					'edit_mem_events'          => true,
					'edit_others_mem_events'   => true,
					'delete_mem_events'        => true,
					'delete_others_mem_events' => true,
					'read_private_mem_events'  => true,
				);
				break;
			/**
			 * Packages
			 */
			case 'mem_package_none':
				$caps = array(
					'mem_package_edit_own'       => true,
					'mem_package_edit'           => false,
					'publish_mem_packages'       => false,
					'edit_mem_packages'          => false,
					'edit_others_mem_packages'   => false,
					'delete_mem_packages'        => false,
					'delete_others_mem_packages' => false,
					'read_private_mem_packages'  => false,
				);
				break;

			case 'mem_package_edit_own':
				$caps = array(
					'mem_package_edit_own'       => true,
					'mem_package_edit'           => false,
					'publish_mem_packages'       => true,
					'edit_mem_packages'          => true,
					'edit_others_mem_packages'   => false,
					'delete_mem_packages'        => false,
					'delete_others_mem_packages' => false,
					'read_private_mem_packages'  => false,
				);
				break;

			case 'mem_package_edit':
				$caps = array(
					'mem_package_edit_own'       => true,
					'mem_package_edit'           => true,
					'publish_mem_packages'       => true,
					'edit_mem_packages'          => true,
					'edit_others_mem_packages'   => true,
					'delete_mem_packages'        => true,
					'delete_others_mem_packages' => true,
					'read_private_mem_packages'  => true,
				);
				break;
			/**
			 * Quotes
			 */
			case 'mem_quote_none':
				$caps = array(
					'mem_quote_view_own'          => false,
					'mem_quote_view'              => false,
					'edit_mem_quotes'             => false,
					'edit_others_mem_quotes'      => false,
					'publish_mem_quotes'          => false,
					'read_private_mem_quotes'     => false,
					'edit_published_mem_quotes'   => false,
					'edit_private_mem_quotes'     => false,
					'delete_mem_quotes'           => false,
					'delete_others_mem_quotes'    => false,
					'delete_private_mem_quotes'   => false,
					'delete_published_mem_quotes' => false,
				);
				break;

			case 'mem_quote_view_own':
				$caps = array(
					'mem_quote_view_own'          => true,
					'mem_quote_view'              => false,
					'edit_mem_quotes'             => true,
					'edit_others_mem_quotes'      => false,
					'publish_mem_quotes'          => false,
					'read_private_mem_quotes'     => false,
					'edit_published_mem_quotes'   => false,
					'edit_private_mem_quotes'     => false,
					'delete_mem_quotes'           => false,
					'delete_others_mem_quotes'    => false,
					'delete_private_mem_quotes'   => false,
					'delete_published_mem_quotes' => false,
				);
				break;

			case 'mem_quote_view':
				$caps = array(
					'mem_quote_view_own'          => true,
					'mem_quote_view'              => true,
					'edit_mem_quotes'             => true,
					'edit_others_mem_quotes'      => true,
					'publish_mem_quotes'          => true,
					'read_private_mem_quotes'     => true,
					'edit_published_mem_quotes'   => true,
					'edit_private_mem_quotes'     => true,
					'delete_mem_quotes'           => true,
					'delete_others_mem_quotes'    => true,
					'delete_private_mem_quotes'   => true,
					'delete_published_mem_quotes' => true,
				);
				break;
			/**
			 * Reports
			 */
			case 'mem_reports_none':
				$caps = array(
					'view_event_reports' => false,
				);
				break;
			case 'mem_reports_run':
				$caps = array(
					'view_event_reports' => true,
				);
				break;
			/**
			 * Templates
			 */
			case 'mem_template_none':
				$caps = array(
					'mem_template_edit'              => false,
					'edit_mem_templates'             => false,
					'edit_others_mem_templates'      => false,
					'publish_mem_templates'          => false,
					'read_private_mem_templates'     => false,
					'edit_published_mem_templates'   => false,
					'edit_private_mem_templates'     => false,
					'delete_mem_templates'           => false,
					'delete_others_mem_templates'    => false,
					'delete_private_mem_templates'   => false,
					'delete_published_mem_templates' => false,
				);
				break;

			case 'mem_template_edit':
				$caps = array(
					'mem_template_edit'              => true,
					'edit_mem_templates'             => true,
					'edit_others_mem_templates'      => true,
					'publish_mem_templates'          => true,
					'read_private_mem_templates'     => true,
					'edit_published_mem_templates'   => true,
					'edit_private_mem_templates'     => true,
					'delete_mem_templates'           => true,
					'delete_others_mem_templates'    => true,
					'delete_private_mem_templates'   => true,
					'delete_published_mem_templates' => true,
				);
				break;
			/**
			 * Transactions
			 */
			case 'mem_txn_none':
				$caps = array(
					'mem_txn_edit'              => false,
					'edit_mem_txns'             => false,
					'edit_others_mem_txns'      => false,
					'publish_mem_txns'          => false,
					'read_private_mem_txns'     => false,
					'edit_published_mem_txns'   => false,
					'edit_private_mem_txns'     => false,
					'delete_mem_txns'           => false,
					'delete_others_mem_txns'    => false,
					'delete_private_mem_txns'   => false,
					'delete_published_mem_txns' => false,
				);
				break;

			case 'mem_txn_edit':
				$caps = array(
					'mem_txn_edit'              => true,
					'edit_mem_txns'             => true,
					'edit_others_mem_txns'      => true,
					'publish_mem_txns'          => true,
					'read_private_mem_txns'     => true,
					'edit_published_mem_txns'   => true,
					'edit_private_mem_txns'     => true,
					'delete_mem_txns'           => true,
					'delete_others_mem_txns'    => true,
					'delete_private_mem_txns'   => true,
					'delete_published_mem_txns' => true,
				);
				break;
			/**
			 * Venues
			 */
			case 'mem_venue_none':
				$caps = array(
					'mem_venue_read'              => false,
					'mem_venue_edit'              => false,
					'edit_mem_venues'             => false,
					'edit_others_mem_venues'      => false,
					'publish_mem_venues'          => false,
					'read_private_mem_venues'     => false,
					'edit_published_mem_venues'   => false,
					'edit_private_mem_venues'     => false,
					'delete_mem_venues'           => false,
					'delete_others_mem_venues'    => false,
					'delete_private_mem_venues'   => false,
					'delete_published_mem_venues' => false,
				);
				break;

			case 'mem_venue_read':
				$caps = array(
					'mem_venue_read'              => true,
					'mem_venue_edit'              => false,
					'edit_mem_venues'             => true,
					'edit_others_mem_venues'      => true,
					'publish_mem_venues'          => false,
					'read_private_mem_venues'     => true,
					'edit_published_mem_venues'   => true,
					'edit_private_mem_venues'     => true,
					'delete_mem_venues'           => false,
					'delete_others_mem_venues'    => false,
					'delete_private_mem_venues'   => false,
					'delete_published_mem_venues' => false,
				);
				break;

			case 'mem_venue_edit':
				$caps = array(
					'mem_venue_read'              => true,
					'mem_venue_edit'              => true,
					'edit_mem_venues'             => true,
					'edit_others_mem_venues'      => true,
					'publish_mem_venues'          => true,
					'read_private_mem_venues'     => true,
					'edit_published_mem_venues'   => true,
					'edit_private_mem_venues'     => true,
					'delete_mem_venues'           => true,
					'delete_others_mem_venues'    => true,
					'delete_private_mem_venues'   => true,
					'delete_published_mem_venues' => true,
				);
				break;

			default:
				return false;
				break;

		}

		return ! empty( $caps ) ? $caps : false;
	} // get_capabilities

	/**
	 * Determine if the currently logged in employee user has the relevant permissions to perform the action/view the page
	 *
	 * @since 1.3
	 * @param str $action Required: The action being performed
	 * @param int $user_id Optional: The ID of the user to query. Default current user
	 * @return bool $granted true|false
	 */
	public function employee_can( $action, $user_id = '' ) {
		if ( empty( $user_id ) ) {
			$user = wp_get_current_user();
		} else {
			$user = get_user_by( 'id', $user_id );
		}

		// MEM Admins can do everything
		if ( mem_is_admin( $user->ID ) ) {
			return true;
		}

		// Non employees can't do anything
		if ( ! mem_is_employee( $user->ID ) ) {
			return false;
		}

		switch ( $action ) {

			case 'view_clients_list':
				$allowed_roles = array( 'mem_client_edit', 'mem_client_edit_own' );
				break;

			case 'list_all_clients':
				$allowed_roles = array( 'mem_client_edit' );
				break;

			case 'manage_employees':
				$allowed_roles = array( 'mem_employee_edit' );
				break;

			case 'read_events':
				$allowed_roles = array( 'mem_event_read', 'mem_event_read_own', 'mem_event_edit', 'mem_event_edit_own' );
				break;

			case 'read_events_all':
				$allowed_roles = array( 'mem_event_read', 'mem_event_edit' );
				break;

			case 'manage_events':
				$allowed_roles = array( 'mem_event_edit', 'mem_event_edit_own' );
				break;

			case 'manage_all_events':
				$allowed_roles = array( 'mem_event_edit' );
				break;

			case 'manage_packages':
				$allowed_roles = array( 'mem_package_edit', 'mem_package_edit_own' );
				break;

			case 'manage_templates':
				$allowed_roles = array( 'mem_template_edit' );
				break;

			case 'edit_txns':
				$allowed_roles = array( 'mem_txn_edit' );
				break;

			case 'list_all_quotes':
				$allowed_roles = array( 'mem_quote_view' );
				break;

			case 'list_own_quotes':
				$allowed_roles = array( 'mem_quote_view_own', 'mem_quote_view' );
				break;

			case 'list_venues':
				$allowed_roles = array( 'mem_venue_read', 'mem_venue_edit' );
				break;

			case 'add_venues':
				$allowed_roles = array( 'mem_venue_edit' );
				break;

			case 'send_comms':
				$allowed_roles = array( 'mem_comms_send' );
				break;

			case 'run_reports':
				$allowed_roles = array( 'view_event_reports' );
			default:
				return false;
				break;
		} // switch

		if ( empty( $allowed_roles ) ) {
			return false;
		}

		foreach ( $allowed_roles as $allowed ) {
			if ( user_can( $user->ID, $allowed ) ) {
				return true;
			}
		}

		return false;
	} // employee_can

	/**
	 * Make the current role a full admin
	 *
	 * @since 1.3
	 * @param int|str $where The user ID or role name to which the permission
	 *
	 * @return void
	 */
	public function make_admin( $where, $remove = false ) {

		$caps = array(
			// MEM Admin
			'manage_mem'                     => true,

			// Clients
			'mem_client_edit'                => true,
			'mem_client_edit_own'            => true,

			// Employees
			'mem_employee_edit'              => true,

			// Packages
			'mem_package_edit_own'           => true,
			'mem_package_edit'               => true,
			'publish_mem_packages'           => true,
			'edit_mem_packages'              => true,
			'edit_others_mem_packages'       => true,
			'delete_mem_packages'            => true,
			'delete_others_mem_packages'     => true,
			'read_private_mem_packages'      => true,

			// Comm posts
			'mem_comms_send'                 => true,
			'edit_mem_comms'                 => true,
			'edit_others_mem_comms'          => true,
			'publish_mem_comms'              => true,
			'read_private_mem_comms'         => true,
			'edit_published_mem_comms'       => true,
			'delete_mem_comms'               => true,
			'delete_others_mem_comms'        => true,
			'delete_private_mem_comms'       => true,
			'delete_published_mem_comms'     => true,
			'edit_private_mem_comms'         => true,

			// Event posts
			'mem_event_read'                 => true,
			'mem_event_read_own'             => true,
			'mem_event_edit'                 => true,
			'mem_event_edit_own'             => true,
			'publish_mem_events'             => true,
			'edit_mem_events'                => true,
			'edit_others_mem_events'         => true,
			'delete_mem_events'              => true,
			'delete_others_mem_events'       => true,
			'read_private_mem_events'        => true,

			// Quote posts
			'mem_quote_view_own'             => true,
			'mem_quote_view'                 => true,
			'edit_mem_quotes'                => true,
			'edit_others_mem_quotes'         => true,
			'publish_mem_quotes'             => true,
			'read_private_mem_quotes'        => true,
			'edit_published_mem_quotes'      => true,
			'edit_private_mem_quotes'        => true,
			'delete_mem_quotes'              => true,
			'delete_others_mem_quotes'       => true,
			'delete_private_mem_quotes'      => true,
			'delete_published_mem_quotes'    => true,

			// Reports
			'view_event_reports'              => true,

			// Templates
			'mem_template_edit'              => true,
			'edit_mem_templates'             => true,
			'edit_others_mem_templates'      => true,
			'publish_mem_templates'          => true,
			'read_private_mem_templates'     => true,
			'edit_published_mem_templates'   => true,
			'edit_private_mem_templates'     => true,
			'delete_mem_templates'           => true,
			'delete_others_mem_templates'    => true,
			'delete_private_mem_templates'   => true,
			'delete_published_mem_templates' => true,

			// Transaction posts
			'mem_txn_edit'                   => true,
			'edit_mem_txns'                  => true,
			'edit_others_mem_txns'           => true,
			'publish_mem_txns'               => true,
			'read_private_mem_txns'          => true,
			'edit_published_mem_txns'        => true,
			'edit_private_mem_txns'          => true,
			'delete_mem_txns'                => true,
			'delete_others_mem_txns'         => true,
			'delete_private_mem_txns'        => true,
			'delete_published_mem_txns'      => true,

			// Venue posts
			'mem_venue_read'                 => true,
			'mem_venue_edit'                 => true,
			'edit_mem_venues'                => true,
			'edit_others_mem_venues'         => true,
			'publish_mem_venues'             => true,
			'read_private_mem_venues'        => true,
			'edit_published_mem_venues'      => true,
			'edit_private_mem_venues'        => true,
			'delete_mem_venues'              => true,
			'delete_others_mem_venues'       => true,
			'delete_private_mem_venues'      => true,
			'delete_published_mem_venues'    => true,
		);

		$role = ( is_numeric( $where ) ? new WP_User( $where ) : get_role( $where ) );

		// Fire a filter to enable default capabilities to be manipulated
		$caps = apply_filters( 'mem_all_caps', $caps );

		foreach ( $caps as $cap => $set ) {

			if ( ! empty( $remove ) ) {
				$role->remove_cap( $cap );
			} elseif ( ! empty( $set ) ) {
				$role->add_cap( $cap );
			} else {
				$role->remove_cap( $cap );
			}
		}

	} // make_admin

} // MEM_Permissions
