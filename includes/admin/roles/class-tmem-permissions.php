<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

/**
 * Class Name: TMEM_Permissions
 * Manage User permissions within TMEM
 */
class TMEM_Permissions {
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
		if ( isset( $_POST['set-permissions'], $_POST['tmem_set_permissions'] ) ) {
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
			'comm_permissions'     => 'tmem_comms',
			'client_permissions'   => 'tmem_client',
			'employee_permissions' => 'tmem_employee',
			'event_permissions'    => 'tmem_event',
			'package_permissions'  => 'tmem_package',
			'quote_permissions'    => 'tmem_quote',
			'report_permissions'   => 'tmem_reports',
			'template_permissions' => 'tmem_template',
			'txn_permissions'      => 'tmem_txn',
			'venue_permissions'    => 'tmem_venue',
		);

		foreach ( sanitize_key( wp_unslash( $_POST['employee_roles'] ) ) as $_role ) {
			$role = get_role( $_role );

			// If the role is to become admin
			if ( ! empty( $_POST[ 'manage_tmem_' . $_role ] ) ) {

				$role->add_cap( 'manage_tmem' );
				$this->make_admin( $_role );
				continue;

			} else {
				$role->remove_cap( 'manage_tmem' );
			}

			// Every role has the TMEM Employee capability
			$role->add_cap( 'tmem_employee' );

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
			case 'tmem_client_none':
				$caps = array(
					'tmem_client_edit'     => false,
					'tmem_client_edit_own' => false,
				);
				break;

			case 'tmem_client_edit_own':
				$caps = array(
					'tmem_client_edit'     => false,
					'tmem_client_edit_own' => true,
				);
				break;

			case 'tmem_client_edit':
				$caps = array(
					'tmem_client_edit'     => true,
					'tmem_client_edit_own' => true,
				);
				break;
			/**
			 * Communications
			 */
			case 'tmem_comms_none':
				$caps = array(
					'tmem_comms_send'             => false,
					'edit_tmem_comms'             => false,
					'edit_others_tmem_comms'      => false,
					'publish_tmem_comms'          => false,
					'read_private_tmem_comms'     => false,
					'edit_published_tmem_comms'   => false,
					'delete_tmem_comms'           => false,
					'delete_others_tmem_comms'    => false,
					'delete_private_tmem_comms'   => false,
					'delete_published_tmem_comms' => false,
					'edit_private_tmem_comms'     => false,
				);
				break;

			case 'tmem_comms_send':
				$caps = array(
					'tmem_comms_send'             => true,
					'edit_tmem_comms'             => true,
					'edit_others_tmem_comms'      => true,
					'publish_tmem_comms'          => true,
					'read_private_tmem_comms'     => true,
					'edit_published_tmem_comms'   => true,
					'delete_tmem_comms'           => true,
					'delete_others_tmem_comms'    => true,
					'delete_private_tmem_comms'   => true,
					'delete_published_tmem_comms' => true,
					'edit_private_tmem_comms'     => true,
				);
				break;

			/**
			 * Employees
			 */
			case 'tmem_employee_none':
				$caps = array( 'tmem_employee_edit' => false );
				break;

			case 'tmem_employee_edit':
				$caps = array( 'tmem_employee_edit' => true );
				break;
			/**
			 * Events
			 */
			case 'tmem_event_none':
				$caps = array(
					'tmem_event_read'           => false,
					'tmem_event_read_own'       => false,
					'tmem_event_edit'           => false,
					'tmem_event_edit_own'       => false,
					'publish_tmem_events'       => false,
					'edit_tmem_events'          => false,
					'edit_others_tmem_events'   => false,
					'delete_tmem_events'        => false,
					'delete_others_tmem_events' => false,
					'read_private_tmem_events'  => false,
				);
				break;

			case 'tmem_event_read_own':
				$caps = array(
					'tmem_event_read'           => false,
					'tmem_event_read_own'       => true,
					'tmem_event_edit'           => false,
					'tmem_event_edit_own'       => false,
					'publish_tmem_events'       => false,
					'edit_tmem_events'          => true,
					'edit_others_tmem_events'   => true,
					'delete_tmem_events'        => false,
					'delete_others_tmem_events' => false,
					'read_private_tmem_events'  => true,
				);
				break;

			case 'tmem_event_read':
				$caps = array(
					'tmem_event_read'           => true,
					'tmem_event_read_own'       => true,
					'tmem_event_edit'           => false,
					'tmem_event_edit_own'       => false,
					'publish_tmem_events'       => false,
					'edit_tmem_events'          => true,
					'edit_others_tmem_events'   => true,
					'delete_tmem_events'        => false,
					'delete_others_tmem_events' => false,
					'read_private_tmem_events'  => true,
				);
				break;

			case 'tmem_event_edit_own':
				$caps = array(
					'tmem_event_read'           => false,
					'tmem_event_read_own'       => true,
					'tmem_event_edit'           => false,
					'tmem_event_edit_own'       => true,
					'publish_tmem_events'       => true,
					'edit_tmem_events'          => true,
					'edit_others_tmem_events'   => true,
					'delete_tmem_events'        => false,
					'delete_others_tmem_events' => false,
					'read_private_tmem_events'  => true,
				);
				break;

			case 'tmem_event_edit':
				$caps = array(
					'tmem_event_read'           => true,
					'tmem_event_read_own'       => true,
					'tmem_event_edit'           => true,
					'tmem_event_edit_own'       => true,
					'publish_tmem_events'       => true,
					'edit_tmem_events'          => true,
					'edit_others_tmem_events'   => true,
					'delete_tmem_events'        => true,
					'delete_others_tmem_events' => true,
					'read_private_tmem_events'  => true,
				);
				break;
			/**
			 * Packages
			 */
			case 'tmem_package_none':
				$caps = array(
					'tmem_package_edit_own'       => true,
					'tmem_package_edit'           => false,
					'publish_tmem_packages'       => false,
					'edit_tmem_packages'          => false,
					'edit_others_tmem_packages'   => false,
					'delete_tmem_packages'        => false,
					'delete_others_tmem_packages' => false,
					'read_private_tmem_packages'  => false,
				);
				break;

			case 'tmem_package_edit_own':
				$caps = array(
					'tmem_package_edit_own'       => true,
					'tmem_package_edit'           => false,
					'publish_tmem_packages'       => true,
					'edit_tmem_packages'          => true,
					'edit_others_tmem_packages'   => false,
					'delete_tmem_packages'        => false,
					'delete_others_tmem_packages' => false,
					'read_private_tmem_packages'  => false,
				);
				break;

			case 'tmem_package_edit':
				$caps = array(
					'tmem_package_edit_own'       => true,
					'tmem_package_edit'           => true,
					'publish_tmem_packages'       => true,
					'edit_tmem_packages'          => true,
					'edit_others_tmem_packages'   => true,
					'delete_tmem_packages'        => true,
					'delete_others_tmem_packages' => true,
					'read_private_tmem_packages'  => true,
				);
				break;
			/**
			 * Quotes
			 */
			case 'tmem_quote_none':
				$caps = array(
					'tmem_quote_view_own'          => false,
					'tmem_quote_view'              => false,
					'edit_tmem_quotes'             => false,
					'edit_others_tmem_quotes'      => false,
					'publish_tmem_quotes'          => false,
					'read_private_tmem_quotes'     => false,
					'edit_published_tmem_quotes'   => false,
					'edit_private_tmem_quotes'     => false,
					'delete_tmem_quotes'           => false,
					'delete_others_tmem_quotes'    => false,
					'delete_private_tmem_quotes'   => false,
					'delete_published_tmem_quotes' => false,
				);
				break;

			case 'tmem_quote_view_own':
				$caps = array(
					'tmem_quote_view_own'          => true,
					'tmem_quote_view'              => false,
					'edit_tmem_quotes'             => true,
					'edit_others_tmem_quotes'      => false,
					'publish_tmem_quotes'          => false,
					'read_private_tmem_quotes'     => false,
					'edit_published_tmem_quotes'   => false,
					'edit_private_tmem_quotes'     => false,
					'delete_tmem_quotes'           => false,
					'delete_others_tmem_quotes'    => false,
					'delete_private_tmem_quotes'   => false,
					'delete_published_tmem_quotes' => false,
				);
				break;

			case 'tmem_quote_view':
				$caps = array(
					'tmem_quote_view_own'          => true,
					'tmem_quote_view'              => true,
					'edit_tmem_quotes'             => true,
					'edit_others_tmem_quotes'      => true,
					'publish_tmem_quotes'          => true,
					'read_private_tmem_quotes'     => true,
					'edit_published_tmem_quotes'   => true,
					'edit_private_tmem_quotes'     => true,
					'delete_tmem_quotes'           => true,
					'delete_others_tmem_quotes'    => true,
					'delete_private_tmem_quotes'   => true,
					'delete_published_tmem_quotes' => true,
				);
				break;
			/**
			 * Reports
			 */
			case 'tmem_reports_none':
				$caps = array(
					'view_event_reports' => false,
				);
				break;
			case 'tmem_reports_run':
				$caps = array(
					'view_event_reports' => true,
				);
				break;
			/**
			 * Templates
			 */
			case 'tmem_template_none':
				$caps = array(
					'tmem_template_edit'              => false,
					'edit_tmem_templates'             => false,
					'edit_others_tmem_templates'      => false,
					'publish_tmem_templates'          => false,
					'read_private_tmem_templates'     => false,
					'edit_published_tmem_templates'   => false,
					'edit_private_tmem_templates'     => false,
					'delete_tmem_templates'           => false,
					'delete_others_tmem_templates'    => false,
					'delete_private_tmem_templates'   => false,
					'delete_published_tmem_templates' => false,
				);
				break;

			case 'tmem_template_edit':
				$caps = array(
					'tmem_template_edit'              => true,
					'edit_tmem_templates'             => true,
					'edit_others_tmem_templates'      => true,
					'publish_tmem_templates'          => true,
					'read_private_tmem_templates'     => true,
					'edit_published_tmem_templates'   => true,
					'edit_private_tmem_templates'     => true,
					'delete_tmem_templates'           => true,
					'delete_others_tmem_templates'    => true,
					'delete_private_tmem_templates'   => true,
					'delete_published_tmem_templates' => true,
				);
				break;
			/**
			 * Transactions
			 */
			case 'tmem_txn_none':
				$caps = array(
					'tmem_txn_edit'              => false,
					'edit_tmem_txns'             => false,
					'edit_others_tmem_txns'      => false,
					'publish_tmem_txns'          => false,
					'read_private_tmem_txns'     => false,
					'edit_published_tmem_txns'   => false,
					'edit_private_tmem_txns'     => false,
					'delete_tmem_txns'           => false,
					'delete_others_tmem_txns'    => false,
					'delete_private_tmem_txns'   => false,
					'delete_published_tmem_txns' => false,
				);
				break;

			case 'tmem_txn_edit':
				$caps = array(
					'tmem_txn_edit'              => true,
					'edit_tmem_txns'             => true,
					'edit_others_tmem_txns'      => true,
					'publish_tmem_txns'          => true,
					'read_private_tmem_txns'     => true,
					'edit_published_tmem_txns'   => true,
					'edit_private_tmem_txns'     => true,
					'delete_tmem_txns'           => true,
					'delete_others_tmem_txns'    => true,
					'delete_private_tmem_txns'   => true,
					'delete_published_tmem_txns' => true,
				);
				break;
			/**
			 * Venues
			 */
			case 'tmem_venue_none':
				$caps = array(
					'tmem_venue_read'              => false,
					'tmem_venue_edit'              => false,
					'edit_tmem_venues'             => false,
					'edit_others_tmem_venues'      => false,
					'publish_tmem_venues'          => false,
					'read_private_tmem_venues'     => false,
					'edit_published_tmem_venues'   => false,
					'edit_private_tmem_venues'     => false,
					'delete_tmem_venues'           => false,
					'delete_others_tmem_venues'    => false,
					'delete_private_tmem_venues'   => false,
					'delete_published_tmem_venues' => false,
				);
				break;

			case 'tmem_venue_read':
				$caps = array(
					'tmem_venue_read'              => true,
					'tmem_venue_edit'              => false,
					'edit_tmem_venues'             => true,
					'edit_others_tmem_venues'      => true,
					'publish_tmem_venues'          => false,
					'read_private_tmem_venues'     => true,
					'edit_published_tmem_venues'   => true,
					'edit_private_tmem_venues'     => true,
					'delete_tmem_venues'           => false,
					'delete_others_tmem_venues'    => false,
					'delete_private_tmem_venues'   => false,
					'delete_published_tmem_venues' => false,
				);
				break;

			case 'tmem_venue_edit':
				$caps = array(
					'tmem_venue_read'              => true,
					'tmem_venue_edit'              => true,
					'edit_tmem_venues'             => true,
					'edit_others_tmem_venues'      => true,
					'publish_tmem_venues'          => true,
					'read_private_tmem_venues'     => true,
					'edit_published_tmem_venues'   => true,
					'edit_private_tmem_venues'     => true,
					'delete_tmem_venues'           => true,
					'delete_others_tmem_venues'    => true,
					'delete_private_tmem_venues'   => true,
					'delete_published_tmem_venues' => true,
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

		// TMEM Admins can do everything
		if ( tmem_is_admin( $user->ID ) ) {
			return true;
		}

		// Non employees can't do anything
		if ( ! tmem_is_employee( $user->ID ) ) {
			return false;
		}

		switch ( $action ) {

			case 'view_clients_list':
				$allowed_roles = array( 'tmem_client_edit', 'tmem_client_edit_own' );
				break;

			case 'list_all_clients':
				$allowed_roles = array( 'tmem_client_edit' );
				break;

			case 'manage_employees':
				$allowed_roles = array( 'tmem_employee_edit' );
				break;

			case 'read_events':
				$allowed_roles = array( 'tmem_event_read', 'tmem_event_read_own', 'tmem_event_edit', 'tmem_event_edit_own' );
				break;

			case 'read_events_all':
				$allowed_roles = array( 'tmem_event_read', 'tmem_event_edit' );
				break;

			case 'manage_events':
				$allowed_roles = array( 'tmem_event_edit', 'tmem_event_edit_own' );
				break;

			case 'manage_all_events':
				$allowed_roles = array( 'tmem_event_edit' );
				break;

			case 'manage_packages':
				$allowed_roles = array( 'tmem_package_edit', 'tmem_package_edit_own' );
				break;

			case 'manage_templates':
				$allowed_roles = array( 'tmem_template_edit' );
				break;

			case 'edit_txns':
				$allowed_roles = array( 'tmem_txn_edit' );
				break;

			case 'list_all_quotes':
				$allowed_roles = array( 'tmem_quote_view' );
				break;

			case 'list_own_quotes':
				$allowed_roles = array( 'tmem_quote_view_own', 'tmem_quote_view' );
				break;

			case 'list_venues':
				$allowed_roles = array( 'tmem_venue_read', 'tmem_venue_edit' );
				break;

			case 'add_venues':
				$allowed_roles = array( 'tmem_venue_edit' );
				break;

			case 'send_comms':
				$allowed_roles = array( 'tmem_comms_send' );
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
			// TMEM Admin
			'manage_tmem'                     => true,

			// Clients
			'tmem_client_edit'                => true,
			'tmem_client_edit_own'            => true,

			// Employees
			'tmem_employee_edit'              => true,

			// Packages
			'tmem_package_edit_own'           => true,
			'tmem_package_edit'               => true,
			'publish_tmem_packages'           => true,
			'edit_tmem_packages'              => true,
			'edit_others_tmem_packages'       => true,
			'delete_tmem_packages'            => true,
			'delete_others_tmem_packages'     => true,
			'read_private_tmem_packages'      => true,

			// Comm posts
			'tmem_comms_send'                 => true,
			'edit_tmem_comms'                 => true,
			'edit_others_tmem_comms'          => true,
			'publish_tmem_comms'              => true,
			'read_private_tmem_comms'         => true,
			'edit_published_tmem_comms'       => true,
			'delete_tmem_comms'               => true,
			'delete_others_tmem_comms'        => true,
			'delete_private_tmem_comms'       => true,
			'delete_published_tmem_comms'     => true,
			'edit_private_tmem_comms'         => true,

			// Event posts
			'tmem_event_read'                 => true,
			'tmem_event_read_own'             => true,
			'tmem_event_edit'                 => true,
			'tmem_event_edit_own'             => true,
			'publish_tmem_events'             => true,
			'edit_tmem_events'                => true,
			'edit_others_tmem_events'         => true,
			'delete_tmem_events'              => true,
			'delete_others_tmem_events'       => true,
			'read_private_tmem_events'        => true,

			// Quote posts
			'tmem_quote_view_own'             => true,
			'tmem_quote_view'                 => true,
			'edit_tmem_quotes'                => true,
			'edit_others_tmem_quotes'         => true,
			'publish_tmem_quotes'             => true,
			'read_private_tmem_quotes'        => true,
			'edit_published_tmem_quotes'      => true,
			'edit_private_tmem_quotes'        => true,
			'delete_tmem_quotes'              => true,
			'delete_others_tmem_quotes'       => true,
			'delete_private_tmem_quotes'      => true,
			'delete_published_tmem_quotes'    => true,

			// Reports
			'view_event_reports'              => true,

			// Templates
			'tmem_template_edit'              => true,
			'edit_tmem_templates'             => true,
			'edit_others_tmem_templates'      => true,
			'publish_tmem_templates'          => true,
			'read_private_tmem_templates'     => true,
			'edit_published_tmem_templates'   => true,
			'edit_private_tmem_templates'     => true,
			'delete_tmem_templates'           => true,
			'delete_others_tmem_templates'    => true,
			'delete_private_tmem_templates'   => true,
			'delete_published_tmem_templates' => true,

			// Transaction posts
			'tmem_txn_edit'                   => true,
			'edit_tmem_txns'                  => true,
			'edit_others_tmem_txns'           => true,
			'publish_tmem_txns'               => true,
			'read_private_tmem_txns'          => true,
			'edit_published_tmem_txns'        => true,
			'edit_private_tmem_txns'          => true,
			'delete_tmem_txns'                => true,
			'delete_others_tmem_txns'         => true,
			'delete_private_tmem_txns'        => true,
			'delete_published_tmem_txns'      => true,

			// Venue posts
			'tmem_venue_read'                 => true,
			'tmem_venue_edit'                 => true,
			'edit_tmem_venues'                => true,
			'edit_others_tmem_venues'         => true,
			'publish_tmem_venues'             => true,
			'read_private_tmem_venues'        => true,
			'edit_published_tmem_venues'      => true,
			'edit_private_tmem_venues'        => true,
			'delete_tmem_venues'              => true,
			'delete_others_tmem_venues'       => true,
			'delete_private_tmem_venues'      => true,
			'delete_published_tmem_venues'    => true,
		);

		$role = ( is_numeric( $where ) ? new WP_User( $where ) : get_role( $where ) );

		// Fire a filter to enable default capabilities to be manipulated
		$caps = apply_filters( 'tmem_all_caps', $caps );

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

} // TMEM_Permissions
