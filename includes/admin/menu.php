<?php

/**
 * Menu Pages
 *
 * @package TMEM
 * @subpackage Admin/Pages
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Builds the admin menu
 *
 * @since	1.3
 * @param
 * @return	void
 */
function tmem_admin_menu() {

	if ( ! current_user_can( 'tmem_employee' ) ) {
		return;
	}

	global $tmem_settings_page, $tmem_contract_template_page, $tmem_email_template_page,
		 $tmem_auto_tasks_page, $tmem_clients_page, $tmem_comms_page, $tmem_comms_history_page,
		 $tmem_availability_page, $tmem_emp_page, $tmem_packages_page, $tmem_reports_page, $tmem_tools_page,
		 $tmem_transactions_page, $tmem_venues_page, $tmem_playlist_page, $tmem_custom_event_fields_page,
		 $tmem_custom_client_fields_page, $tmem_extensions_page;

	$tmem_settings_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Settings', 'mobile-events-manager' ), __( 'Settings', 'mobile-events-manager' ), 'manage_tmem', 'tmem-settings', 'tmem_options_page' );

	if ( tmem_employee_can( 'manage_templates' ) ) {
		$tmem_contract_template_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Contract Templates', 'mobile-events-manager' ), __( 'Contract Templates', 'mobile-events-manager' ), 'tmem_employee', 'edit.php?post_type=contract', '' );
		$tmem_email_template_page    = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Email Templates', 'mobile-events-manager' ), __( 'Email Templates', 'mobile-events-manager' ), 'tmem_employee', 'edit.php?post_type=email_template', '' );
	}

	$tmem_auto_tasks_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Automated Tasks', 'mobile-events-manager' ), __( 'Automated Tasks', 'mobile-events-manager' ), 'manage_tmem', 'tmem-tasks', 'tmem_tasks_page' );

	if ( tmem_employee_can( 'view_clients_list' ) ) {
		$tmem_clients_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Clients', 'mobile-events-manager' ), __( 'Clients', 'mobile-events-manager' ), 'tmem_employee', 'tmem-clients', array( TMEM()->users, 'client_manager' ) );
	}

	if ( tmem_employee_can( 'send_comms' ) ) {
		$tmem_comms_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Communications', 'mobile-events-manager' ), __( 'Communications', 'mobile-events-manager' ), 'tmem_employee', 'tmem-comms', 'tmem_comms_page' );

		$tmem_comms_history_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Communication History', 'mobile-events-manager' ), '&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'History', 'mobile-events-manager' ), 'tmem_employee', 'edit.php?post_type=tmem_communication' );
	}

	if ( tmem_is_employer() && tmem_employee_can( 'manage_employees' ) ) {
		$tmem_emp_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Employees', 'mobile-events-manager' ), __( 'Employees', 'mobile-events-manager' ), 'tmem_employee', 'tmem-employees', array( TMEM()->users, 'employee_manager' ) );
	}

	$tmem_availability_page = add_submenu_page(
		'edit.php?post_type=tmem-event',
		__( 'Employee Availability', 'mobile-events-manager' ),
		'&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'Availability', 'mobile-events-manager' ),
		'manage_tmem',
		'tmem-availability',
		'tmem_availability_page'
	);

	if ( ( tmem_get_option( 'enable_packages' ) ) && tmem_employee_can( 'manage_packages' ) ) {
		$tmem_packages_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Packages', 'mobile-events-manager' ), __( 'Packages', 'mobile-events-manager' ), 'tmem_package_edit_own', 'edit.php?post_type=tmem-package', '' );
		$tmem_addons_page   = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Addons', 'mobile-events-manager' ), '&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'Addons', 'mobile-events-manager' ), 'tmem_package_edit_own', 'edit.php?post_type=tmem-addon', '' );
	}

	if ( tmem_employee_can( 'edit_txns' ) ) {
		$tmem_transactions_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Transactions', 'mobile-events-manager' ), __( 'Transactions', 'mobile-events-manager' ), 'tmem_employee', 'edit.php?post_type=tmem-transaction', '' );
	}

	if ( tmem_employee_can( 'list_venues' ) ) {
		$tmem_venues_page = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Venues', 'mobile-events-manager' ), __( 'Venues', 'mobile-events-manager' ), 'tmem_employee', 'edit.php?post_type=tmem-venue', '' );
	}

	$tmem_tools_page                = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Tools', 'mobile-events-manager' ), __( 'Tools', 'mobile-events-manager' ), 'tmem_employee', 'tmem-tools', 'tmem_tools_page' );
	$tmem_reports_page              = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'Reports', 'mobile-events-manager' ), __( 'Reports', 'mobile-events-manager' ), 'tmem_employee', 'tmem-reports', 'tmem_reports_page' );
	$tmem_extensions_page           = add_submenu_page( 'edit.php?post_type=tmem-event', __( 'MEM Extensions', 'mobile-events-manager' ), __( 'Extensions', 'mobile-events-manager' ), 'tmem_employee', 'tmem-addons', 'tmem_extensions_page' );
	$tmem_playlist_page             = add_submenu_page( null, __( 'Playlists', 'mobile-events-manager' ), __( 'Playlists', 'mobile-events-manager' ), 'tmem_employee', 'tmem-playlists', 'tmem_display_event_playlist_page' );
	$tmem_custom_event_fields_page  = add_submenu_page( null, __( 'Custom Event Fields', 'mobile-events-manager' ), __( 'Custom Event Fields', 'mobile-events-manager' ), 'manage_tmem', 'tmem-custom-event-fields', array( 'TMEM_Event_Fields', 'custom_event_field_settings' ) );
	$tmem_custom_client_fields_page = add_submenu_page( null, __( 'Custom Client Fields', 'mobile-events-manager' ), __( 'Custom Client Fields', 'mobile-events-manager' ), 'manage_tmem', 'tmem-custom-client-fields', 'tmem_custom_client_fields_page' );
	$tmem_upgrades_screen           = add_submenu_page( null, __( 'MEM Upgrades', 'mobile-events-manager' ), __( 'MEM Upgrades', 'mobile-events-manager' ), 'tmem_employee', 'tmem-upgrades', 'tmem_upgrades_screen' );

} // tmem_admin_menu
add_action( 'admin_menu', 'tmem_admin_menu', 9 );

/*
 * Builds the admin toolbar
 *
 * @since	1.3
 * @param
 * @return	void
 */
function tmem_admin_toolbar( $admin_bar ) {

	if ( ! current_user_can( 'tmem_employee' ) ) {
		return;
	}

	// Build out the toolbar menu structure
	$admin_bar->add_menu(
		array(
			'id'    => 'tmem',
			'title' => sprintf( esc_html__( 'MEM %s', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ),
			'href'  => tmem_employee_can( 'read_events' ) ? admin_url( 'edit.php?post_type=tmem-event' ) : '#',
			'meta'  => array(
				'title' => __( 'TMEM Event Management', 'mobile-events-manager' ),
			),
		)
	);
	if ( tmem_employee_can( 'read_events' ) ) {
		// Events
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-events',
				'parent' => 'tmem',
				'title'  => esc_html( tmem_get_label_plural() ),
				'href'   => admin_url( 'edit.php?post_type=tmem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'TMEM %s', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ),
				),
			)
		);
	}
	if ( tmem_employee_can( 'manage_all_events' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-add-events',
				'parent' => 'tmem-events',
				'title'  => sprintf( esc_html__( 'Create %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'href'   => admin_url( 'post-new.php?post_type=tmem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Create New %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				),
			)
		);
		// Enquiries
		$event_status = array(
			'tmem-unattended' => __( 'Unattended Enquiries', 'mobile-events-manager' ),
			'tmem-enquiry'    => __( 'View Enquiries', 'mobile-events-manager' ),
		);

		foreach ( $event_status as $current_status => $display ) {
			$status_count = tmem_event_count( $current_status );
			if ( ! $status_count ) {
				continue;
			}

			$admin_bar->add_menu(
				array(
					'id'     => 'tmem-' . str_replace( ' ', '-', strtolower( $display ) ),
					'parent' => 'tmem-events',
					'title'  => $display . ' (' . $status_count . ')',
					'href'   => admin_url( 'edit.php?post_status=' . $current_status . '&post_type=tmem-event' ),
					'meta'   => array(
						'title' => $display,
					),
				)
			);
		}
		// Event Types
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-event-types',
				'parent' => 'tmem-events',
				'title'  => sprintf( esc_html__( '%s Types', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=event-types&post_type=tmem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Manage %s Types', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				),
			)
		);

		// Playlist Categories
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-playlist-cats',
				'parent' => 'tmem-events',
				'title'  => __( 'Playlist Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=playlist-category&post_type=tmem-playlist' ),
				'meta'   => array(
					'title' => __( 'Manage Playlist Categories', 'mobile-events-manager' ),
				),
			)
		);

		// Enquiry Sources
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-enquiry-sources',
				'parent' => 'tmem-events',
				'title'  => __( 'Enquiry Sources', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=enquiry-source&post_type=tmem-event' ),
				'meta'   => array(
					'title' => __( 'Manage Enquiry Sources', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Dashboard
	/*
	$admin_bar->add_menu( array(
		'id'		=> 'tmem-dashboard',
		'parent'	=> 'tmem',
		'title'	 => __( 'Dashboard', 'mobile-events-manager' ),
		'href'	 => admin_url( 'admin.php?page=tmem-dashboard' ),
		'meta'	 => array(
			'title' => __( 'TMEM Dashboard', 'mobile-events-manager' ),
		),
	) ); */
	// Settings
	if ( tmem_is_admin() ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings',
				'parent' => 'tmem',
				'title'  => __( 'Settings', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-settings' ),
				'meta'   => array(
					'title' => __( 'TMEM Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-general',
				'parent' => 'tmem-settings',
				'title'  => __( 'General', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=general' ),
				'meta'   => array(
					'title' => __( 'TMEM General Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-events',
				'parent' => 'tmem-settings',
				'title'  => esc_html( tmem_get_label_plural() ),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=events' ),
				'meta'   => array(
					'title' => __( 'MEM Event Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-permissions',
				'parent' => 'tmem-settings',
				'title'  => __( 'Permissions', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=general&section=tmem_app_permissions' ),
				'meta'   => array(
					'title' => __( 'TMEM Permission Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-emails',
				'parent' => 'tmem-settings',
				'title'  => sprintf( esc_html__( 'Email %s Template Settings', 'mobile-events-manager' ), '&amp;' ),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=emails' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'TMEM Email %s Template Settings', 'mobile-events-manager' ), '&amp;' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-client-zone',
				'parent' => 'tmem-settings',
				'title'  => sprintf(
					__( '%s Settings', 'mobile-events-manager' ),
					tmem_get_application_name()
				),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=client_zone' ),
				'meta'   => array(
					'title' => sprintf(
						__( '%s Settings', 'mobile-events-manager' ),
						tmem_get_application_name()
					),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-settings-payments',
				'parent' => 'tmem-settings',
				'title'  => __( 'Payment Settings', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-settings&tab=payments' ),
				'meta'   => array(
					'title' => __( 'TMEM Payment Settings', 'mobile-events-manager' ),
				),
			)
		);
	}
	do_action( 'tmem_admin_bar_settings_items', $admin_bar );
	if ( tmem_is_employer() && tmem_employee_can( 'manage_employees' ) ) {
		// Employees
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-employees',
				'parent' => 'tmem',
				'title'  => __( 'Employees', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-employees' ),
				'meta'   => array(
					'title' => __( 'Employees', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( tmem_is_admin() ) {
		// Employee Availability
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-availability',
				'parent' => tmem_is_employer() ? 'tmem-employees' : 'tmem',
				'title'  => __( 'Employee Availability', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-availability' ),
				'meta'   => array(
					'title' => __( 'Employee Availability', 'mobile-events-manager' ),
				),
			)
		);
		// Automated Tasks
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-tasks',
				'parent' => 'tmem',
				'title'  => __( 'Automated Tasks', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-tasks' ),
				'meta'   => array(
					'title' => __( 'Automated Tasks', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( tmem_employee_can( 'view_clients_list' ) ) {
		// Clients
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-clients',
				'parent' => 'tmem',
				'title'  => __( 'Clients', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-clients' ),
				'meta'   => array(
					'title' => __( 'Clients', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( tmem_employee_can( 'list_all_clients' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-add-client',
				'parent' => 'tmem-clients',
				'title'  => __( 'Add Client', 'mobile-events-manager' ),
				'href'   => admin_url( 'user-new.php' ),
				'meta'   => array(
					'title' => __( 'Add New Client', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-custom-client-fields',
				'parent' => 'tmem-clients',
				'title'  => __( 'Custom Client Fields', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-custom-client-fields' ),
				'meta'   => array(
					'title' => __( 'Custom Client Field', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Communications
	if ( tmem_employee_can( 'send_comms' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-comms',
				'parent' => 'tmem',
				'title'  => __( 'Communications', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=tmem-comms' ),
				'meta'   => array(
					'title' => __( 'Communications', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'edit.php?post_type=tmem_communication',
				'parent' => 'tmem-comms',
				'title'  => __( 'Communication History', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=tmem_communication' ),
				'meta'   => array(
					'title' => __( 'Communication History', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Filter for TMEM DCF Admin Bar Items
	do_action( 'tmem_dcf_admin_bar_items', $admin_bar );
	if ( tmem_employee_can( 'manage_templates' ) ) {
		// Contract Templates
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-contracts',
				'parent' => 'tmem',
				'title'  => __( 'Contract Templates', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=contract' ),
				'meta'   => array(
					'title' => __( 'Contract Templates', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-new-contract',
				'parent' => 'tmem-contracts',
				'title'  => __( 'Add Contract Template', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=contract' ),
				'meta'   => array(
					'title' => __( 'New Contract Template', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( tmem_employee_can( 'manage_templates' ) ) {
		// Email Templates
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-email-templates',
				'parent' => 'tmem',
				'title'  => __( 'Email Templates', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=email_template' ),
				'meta'   => array(
					'title' => __( 'Email Templates', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-new-email-template',
				'parent' => 'tmem-email-templates',
				'title'  => __( 'Add Template', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=email_template' ),
				'meta'   => array(
					'title' => __( 'New Email Template', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Equipment Packages & Add-ons
	if ( tmem_packages_enabled() && tmem_employee_can( 'manage_packages' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-packages',
				'parent' => 'tmem',
				'title'  => __( 'Packages', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=tmem-package' ),
				'meta'   => array(
					'title' => __( 'Packages', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-package-cats',
				'parent' => 'tmem-packages',
				'title'  => __( 'Package Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=package-category&post_type=tmem-package' ),
				'meta'   => array(
					'title' => __( 'Package Categories', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-addons',
				'parent' => 'tmem-packages',
				'title'  => __( 'Add-ons', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=tmem-addon' ),
				'meta'   => array(
					'title' => __( 'Add-ons', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-addon-cats',
				'parent' => 'tmem-packages',
				'title'  => __( 'Addon Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=addon-category&post_type=tmem-addon' ),
				'meta'   => array(
					'title' => __( 'Addon Categories', 'mobile-events-manager' ),
				),
			)
		);
	}

	// Custom Event Fields
	if ( tmem_is_admin() ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-event-fields',
				'parent' => 'tmem-events',
				'title'  => sprintf( esc_html__( 'Custom %s Fields', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'href'   => admin_url( 'admin.php?page=tmem-custom-event-fields' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Manage Custom %s Fields', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				),
			)
		);
	}
	// Event Quotes
	if ( tmem_get_option( 'online_enquiry', false ) && tmem_employee_can( 'list_own_quotes' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-event-quotes',
				'parent' => 'tmem-events',
				'title'  => sprintf( esc_html__( '%s Quotes', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'href'   => admin_url( 'edit.php?post_type=tmem-quotes' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'View %s Quotes', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				),
			)
		);
	}
	// Reporting
	/*
	if( current_user_can( 'manage_options' ) ) {
		$admin_bar->add_menu( array(
			'id' => 'tmem-reports',
			'parent' => 'tmem',
			'title' => __( 'Reports', 'mobile-events-manager' ),
			'href' => admin_url( 'admin.php?page=tmem-reports' ),
			'meta' => array(
				'title' => __( 'TMEM Reports', 'mobile-events-manager' ),
			),
		) );
	}*/
	if ( tmem_employee_can( 'edit_txns' ) ) {
		// Transactions
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-transactions',
				'parent' => 'tmem',
				'title'  => __( 'Transactions', 'mobile-events-manager' ),
				'href'   => 'edit.php?post_type=tmem-transaction',
				'meta'   => array(
					'title' => __( 'MEM Transactions', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-add-transaction',
				'parent' => 'tmem-transactions',
				'title'  => __( 'Add Transaction', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=tmem-transaction' ),
				'meta'   => array(
					'title' => __( 'Add Transaction', 'mobile-events-manager' ),
				),
			)
		);
		// Transaction Types
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-transaction-types',
				'parent' => 'tmem-transactions',
				'title'  => __( 'Transaction Types', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=transaction-types&post_type=tmem-transaction' ),
				'meta'   => array(
					'title' => __( 'View / Edit Transaction Types', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( tmem_employee_can( 'list_venues' ) ) {
		// Venues
		$admin_bar->add_menu(
			array(
				'id'     => 'tmem-venues',
				'parent' => 'tmem',
				'title'  => __( 'Venues', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=tmem-venue' ),
				'meta'   => array(
					'title' => __( 'Venues', 'mobile-events-manager' ),
				),
			)
		);
		if ( tmem_employee_can( 'add_venues' ) ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'tmem-add-venue',
					'parent' => 'tmem-venues',
					'title'  => __( 'Add Venue', 'mobile-events-manager' ),
					'href'   => admin_url( 'post-new.php?post_type=tmem-venue' ),
					'meta'   => array(
						'title' => __( 'Add New Venue', 'mobile-events-manager' ),
					),
				)
			);
			$admin_bar->add_menu(
				array(
					'id'     => 'tmem-venue-details',
					'parent' => 'tmem-venues',
					'title'  => __( 'Venue Details', 'mobile-events-manager' ),
					'href'   => admin_url( 'edit-tags.php?taxonomy=venue-details&post_type=tmem-venue' ),
					'meta'   => array(
						'title' => __( 'View / Edit Venue Details', 'mobile-events-manager' ),
					),
				)
			);
		}
	}
	// TMEM Links
	$admin_bar->add_menu(
		array(
			'id'     => 'tmem-user-guides',
			'parent' => 'tmem',
			'title'  => sprintf( esc_html__( '%1$sDocumentation%2$s', 'mobile-events-manager' ), '<span style="color:#F90">', '</span>' ),
			'href'   => 'https://www.mobileeventsmanager.co.uk/support/',
			'meta'   => array(
				'title'  => __( 'Documentation', 'mobile-events-manager' ),
				'target' => '_blank',
			),
		)
	);
	$admin_bar->add_menu(
		array(
			'id'     => 'tmem-support',
			'parent' => 'tmem',
			'title'  => sprintf( esc_html__( '%1$sSupport%2$s', 'mobile-events-manager' ), '<span style="color:#F90">', '</span>' ),
			'href'   => 'http://www.mydjplanner.co.uk/forums/',
			'meta'   => array(
				'title'  => __( 'MEM Support Forums', 'mobile-events-manager' ),
				'target' => '_blank',
			),
		)
	);
} // tmem_admin_toolbar
add_action( 'admin_bar_menu', 'tmem_admin_toolbar', 99 );

function tmem_clients_page() {
	include_once TMEM_PLUGIN_DIR . '/includes/admin/pages/clients.php';
} // tmem_clients_page

function tmem_employee_availability_page() {
	include_once TMEM_PLUGIN_DIR . '/includes/admin/pages/availability.php';
} // tmem_employee_availability_page

function tmem_custom_client_fields_page() {
	new TMEM_ClientFields();
} // tmem_custom_client_fields_page
