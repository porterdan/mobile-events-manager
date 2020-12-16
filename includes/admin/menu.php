<?php

/**
 * Menu Pages
 *
 * @package MEM
 * @subpackage Admin/Pages
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
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
function mem_admin_menu() {

	if ( ! current_user_can( 'mem_employee' ) ) {
		return;
	}

	global $mem_settings_page, $mem_contract_template_page, $mem_email_template_page,
		 $mem_auto_tasks_page, $mem_clients_page, $mem_comms_page, $mem_comms_history_page,
		 $mem_availability_page, $mem_emp_page, $mem_packages_page, $mem_reports_page, $mem_tools_page,
		 $mem_transactions_page, $mem_venues_page, $mem_playlist_page, $mem_custom_event_fields_page,
		 $mem_custom_client_fields_page, $mem_extensions_page;

	$mem_settings_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Settings', 'mobile-events-manager' ), __( 'Settings', 'mobile-events-manager' ), 'manage_mem', 'mem-settings', 'mem_options_page' );

	if ( mem_employee_can( 'manage_templates' ) ) {
		$mem_contract_template_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Contract Templates', 'mobile-events-manager' ), __( 'Contract Templates', 'mobile-events-manager' ), 'mem_employee', 'edit.php?post_type=contract', '' );
		$mem_email_template_page    = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Email Templates', 'mobile-events-manager' ), __( 'Email Templates', 'mobile-events-manager' ), 'mem_employee', 'edit.php?post_type=email_template', '' );
	}

	$mem_auto_tasks_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Automated Tasks', 'mobile-events-manager' ), __( 'Automated Tasks', 'mobile-events-manager' ), 'manage_mem', 'mem-tasks', 'mem_tasks_page' );

	if ( mem_employee_can( 'view_clients_list' ) ) {
		$mem_clients_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Clients', 'mobile-events-manager' ), __( 'Clients', 'mobile-events-manager' ), 'mem_employee', 'mem-clients', array( MEM()->users, 'client_manager' ) );
	}

	if ( mem_employee_can( 'send_comms' ) ) {
		$mem_comms_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Communications', 'mobile-events-manager' ), __( 'Communications', 'mobile-events-manager' ), 'mem_employee', 'mem-comms', 'mem_comms_page' );

		$mem_comms_history_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Communication History', 'mobile-events-manager' ), '&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'History', 'mobile-events-manager' ), 'mem_employee', 'edit.php?post_type=mem_communication' );
	}

	if ( mem_is_employer() && mem_employee_can( 'manage_employees' ) ) {
		$mem_emp_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Employees', 'mobile-events-manager' ), __( 'Employees', 'mobile-events-manager' ), 'mem_employee', 'mem-employees', array( MEM()->users, 'employee_manager' ) );
	}

	$mem_availability_page = add_submenu_page(
		'edit.php?post_type=mem-event',
		__( 'Employee Availability', 'mobile-events-manager' ),
		'&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'Availability', 'mobile-events-manager' ),
		'manage_mem',
		'mem-availability',
		'mem_availability_page'
	);

	if ( ( mem_get_option( 'enable_packages' ) ) && mem_employee_can( 'manage_packages' ) ) {
		$mem_packages_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Packages', 'mobile-events-manager' ), __( 'Packages', 'mobile-events-manager' ), 'mem_package_edit_own', 'edit.php?post_type=mem-package', '' );
		$mem_addons_page   = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Addons', 'mobile-events-manager' ), '&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . __( 'Addons', 'mobile-events-manager' ), 'mem_package_edit_own', 'edit.php?post_type=mem-addon', '' );
	}

	if ( mem_employee_can( 'edit_txns' ) ) {
		$mem_transactions_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Transactions', 'mobile-events-manager' ), __( 'Transactions', 'mobile-events-manager' ), 'mem_employee', 'edit.php?post_type=mem-transaction', '' );
	}

	if ( mem_employee_can( 'list_venues' ) ) {
		$mem_venues_page = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Venues', 'mobile-events-manager' ), __( 'Venues', 'mobile-events-manager' ), 'mem_employee', 'edit.php?post_type=mem-venue', '' );
	}

	$mem_tools_page                = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Tools', 'mobile-events-manager' ), __( 'Tools', 'mobile-events-manager' ), 'mem_employee', 'mem-tools', 'mem_tools_page' );
	$mem_reports_page              = add_submenu_page( 'edit.php?post_type=mem-event', __( 'Reports', 'mobile-events-manager' ), __( 'Reports', 'mobile-events-manager' ), 'mem_employee', 'mem-reports', 'mem_reports_page' );
	$mem_extensions_page           = add_submenu_page( 'edit.php?post_type=mem-event', __( 'MEM Extensions', 'mobile-events-manager' ), __( 'Extensions', 'mobile-events-manager' ), 'mem_employee', 'mem-addons', 'mem_extensions_page' );
	$mem_playlist_page             = add_submenu_page( null, __( 'Playlists', 'mobile-events-manager' ), __( 'Playlists', 'mobile-events-manager' ), 'mem_employee', 'mem-playlists', 'mem_display_event_playlist_page' );
	$mem_custom_event_fields_page  = add_submenu_page( null, __( 'Custom Event Fields', 'mobile-events-manager' ), __( 'Custom Event Fields', 'mobile-events-manager' ), 'manage_mem', 'mem-custom-event-fields', array( 'MEM_Event_Fields', 'custom_event_field_settings' ) );
	$mem_custom_client_fields_page = add_submenu_page( null, __( 'Custom Client Fields', 'mobile-events-manager' ), __( 'Custom Client Fields', 'mobile-events-manager' ), 'manage_mem', 'mem-custom-client-fields', 'mem_custom_client_fields_page' );
	$mem_upgrades_screen           = add_submenu_page( null, __( 'MEM Upgrades', 'mobile-events-manager' ), __( 'MEM Upgrades', 'mobile-events-manager' ), 'mem_employee', 'mem-upgrades', 'mem_upgrades_screen' );

} // mem_admin_menu
add_action( 'admin_menu', 'mem_admin_menu', 9 );

/*
 * Builds the admin toolbar
 *
 * @since	1.3
 * @param
 * @return	void
 */
function mem_admin_toolbar( $admin_bar ) {

	if ( ! current_user_can( 'mem_employee' ) ) {
		return;
	}

	// Build out the toolbar menu structure
	$admin_bar->add_menu(
		array(
			'id'    => 'mem',
			'title' => sprintf( esc_html__( 'MEM %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) ),
			'href'  => mem_employee_can( 'read_events' ) ? admin_url( 'edit.php?post_type=mem-event' ) : '#',
			'meta'  => array(
				'title' => __( 'Mobile Events Manager (MEM)', 'mobile-events-manager' ),
			),
		)
	);
	if ( mem_employee_can( 'read_events' ) ) {
		// Events
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-events',
				'parent' => 'mem',
				'title'  => esc_html( mem_get_label_plural() ),
				'href'   => admin_url( 'edit.php?post_type=mem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'MEM %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) ),
				),
			)
		);
	}
	if ( mem_employee_can( 'manage_all_events' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-add-events',
				'parent' => 'mem-events',
				'title'  => sprintf( esc_html__( 'Create %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'href'   => admin_url( 'post-new.php?post_type=mem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Create New %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				),
			)
		);
		// Enquiries
		$event_status = array(
			'mem-unattended' => __( 'Unattended Enquiries', 'mobile-events-manager' ),
			'mem-enquiry'    => __( 'View Enquiries', 'mobile-events-manager' ),
		);

		foreach ( $event_status as $current_status => $display ) {
			$status_count = mem_event_count( $current_status );
			if ( ! $status_count ) {
				continue;
			}

			$admin_bar->add_menu(
				array(
					'id'     => 'mem-' . str_replace( ' ', '-', strtolower( $display ) ),
					'parent' => 'mem-events',
					'title'  => $display . ' (' . $status_count . ')',
					'href'   => admin_url( 'edit.php?post_status=' . $current_status . '&post_type=mem-event' ),
					'meta'   => array(
						'title' => $display,
					),
				)
			);
		}
		// Event Types
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-event-types',
				'parent' => 'mem-events',
				'title'  => sprintf( esc_html__( '%s Types', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=event-types&post_type=mem-event' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Manage %s Types', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				),
			)
		);

		// Playlist Categories
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-playlist-cats',
				'parent' => 'mem-events',
				'title'  => __( 'Playlist Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=playlist-category&post_type=mem-playlist' ),
				'meta'   => array(
					'title' => __( 'Manage Playlist Categories', 'mobile-events-manager' ),
				),
			)
		);

		// Enquiry Sources
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-enquiry-sources',
				'parent' => 'mem-events',
				'title'  => __( 'Enquiry Sources', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=enquiry-source&post_type=mem-event' ),
				'meta'   => array(
					'title' => __( 'Manage Enquiry Sources', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Dashboard
	/*
	$admin_bar->add_menu( array(
		'id'		=> 'mem-dashboard',
		'parent'	=> 'mem',
		'title'	 => __( 'Dashboard', 'mobile-events-manager' ),
		'href'	 => admin_url( 'admin.php?page=mem-dashboard' ),
		'meta'	 => array(
			'title' => __( 'MEM Dashboard', 'mobile-events-manager' ),
		),
	) ); */
	// Settings
	if ( mem_is_admin() ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings',
				'parent' => 'mem',
				'title'  => __( 'Settings', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-settings' ),
				'meta'   => array(
					'title' => __( 'MEM Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-general',
				'parent' => 'mem-settings',
				'title'  => __( 'General', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=general' ),
				'meta'   => array(
					'title' => __( 'MEM General Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-events',
				'parent' => 'mem-settings',
				'title'  => esc_html( mem_get_label_plural() ),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=events' ),
				'meta'   => array(
					'title' => __( 'MEM Event Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-permissions',
				'parent' => 'mem-settings',
				'title'  => __( 'Permissions', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=general&section=mem_app_permissions' ),
				'meta'   => array(
					'title' => __( 'MEM Permission Settings', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-emails',
				'parent' => 'mem-settings',
				'title'  => sprintf( esc_html__( 'Email %s Template Settings', 'mobile-events-manager' ), '&amp;' ),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=emails' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'MEM Email %s Template Settings', 'mobile-events-manager' ), '&amp;' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-client-zone',
				'parent' => 'mem-settings',
				'title'  => sprintf(
					__( '%s Settings', 'mobile-events-manager' ),
					mem_get_application_name()
				),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=client_zone' ),
				'meta'   => array(
					'title' => sprintf(
						__( '%s Settings', 'mobile-events-manager' ),
						mem_get_application_name()
					),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-settings-payments',
				'parent' => 'mem-settings',
				'title'  => __( 'Payment Settings', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-settings&tab=payments' ),
				'meta'   => array(
					'title' => __( 'MEM Payment Settings', 'mobile-events-manager' ),
				),
			)
		);
	}
	do_action( 'mem_admin_bar_settings_items', $admin_bar );
	if ( mem_is_employer() && mem_employee_can( 'manage_employees' ) ) {
		// Employees
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-employees',
				'parent' => 'mem',
				'title'  => __( 'Employees', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-employees' ),
				'meta'   => array(
					'title' => __( 'Employees', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( mem_is_admin() ) {
		// Employee Availability
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-availability',
				'parent' => mem_is_employer() ? 'mem-employees' : 'mem',
				'title'  => __( 'Employee Availability', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-availability' ),
				'meta'   => array(
					'title' => __( 'Employee Availability', 'mobile-events-manager' ),
				),
			)
		);
		// Automated Tasks
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-tasks',
				'parent' => 'mem',
				'title'  => __( 'Automated Tasks', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-tasks' ),
				'meta'   => array(
					'title' => __( 'Automated Tasks', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( mem_employee_can( 'view_clients_list' ) ) {
		// Clients
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-clients',
				'parent' => 'mem',
				'title'  => __( 'Clients', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-clients' ),
				'meta'   => array(
					'title' => __( 'Clients', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( mem_employee_can( 'list_all_clients' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-add-client',
				'parent' => 'mem-clients',
				'title'  => __( 'Add Client', 'mobile-events-manager' ),
				'href'   => admin_url( 'user-new.php' ),
				'meta'   => array(
					'title' => __( 'Add New Client', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-custom-client-fields',
				'parent' => 'mem-clients',
				'title'  => __( 'Custom Client Fields', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-custom-client-fields' ),
				'meta'   => array(
					'title' => __( 'Custom Client Field', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Communications
	if ( mem_employee_can( 'send_comms' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-comms',
				'parent' => 'mem',
				'title'  => __( 'Communications', 'mobile-events-manager' ),
				'href'   => admin_url( 'admin.php?page=mem-comms' ),
				'meta'   => array(
					'title' => __( 'Communications', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'edit.php?post_type=mem_communication',
				'parent' => 'mem-comms',
				'title'  => __( 'Communication History', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=mem_communication' ),
				'meta'   => array(
					'title' => __( 'Communication History', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Filter for MEM DCF Admin Bar Items
	do_action( 'mem_dcf_admin_bar_items', $admin_bar );
	if ( mem_employee_can( 'manage_templates' ) ) {
		// Contract Templates
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-contracts',
				'parent' => 'mem',
				'title'  => __( 'Contract Templates', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=contract' ),
				'meta'   => array(
					'title' => __( 'Contract Templates', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-new-contract',
				'parent' => 'mem-contracts',
				'title'  => __( 'Add Contract Template', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=contract' ),
				'meta'   => array(
					'title' => __( 'New Contract Template', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( mem_employee_can( 'manage_templates' ) ) {
		// Email Templates
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-email-templates',
				'parent' => 'mem',
				'title'  => __( 'Email Templates', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=email_template' ),
				'meta'   => array(
					'title' => __( 'Email Templates', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-new-email-template',
				'parent' => 'mem-email-templates',
				'title'  => __( 'Add Template', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=email_template' ),
				'meta'   => array(
					'title' => __( 'New Email Template', 'mobile-events-manager' ),
				),
			)
		);
	}
	// Equipment Packages & Add-ons
	if ( mem_packages_enabled() && mem_employee_can( 'manage_packages' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-packages',
				'parent' => 'mem',
				'title'  => __( 'Packages', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=mem-package' ),
				'meta'   => array(
					'title' => __( 'Packages', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-package-cats',
				'parent' => 'mem-packages',
				'title'  => __( 'Package Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=package-category&post_type=mem-package' ),
				'meta'   => array(
					'title' => __( 'Package Categories', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-addons',
				'parent' => 'mem-packages',
				'title'  => __( 'Add-ons', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=mem-addon' ),
				'meta'   => array(
					'title' => __( 'Add-ons', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-addon-cats',
				'parent' => 'mem-packages',
				'title'  => __( 'Addon Categories', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=addon-category&post_type=mem-addon' ),
				'meta'   => array(
					'title' => __( 'Addon Categories', 'mobile-events-manager' ),
				),
			)
		);
	}

	// Custom Event Fields
	if ( mem_is_admin() ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-event-fields',
				'parent' => 'mem-events',
				'title'  => sprintf( esc_html__( 'Custom %s Fields', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'href'   => admin_url( 'admin.php?page=mem-custom-event-fields' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'Manage Custom %s Fields', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				),
			)
		);
	}
	// Event Quotes
	if ( mem_get_option( 'online_enquiry', false ) && mem_employee_can( 'list_own_quotes' ) ) {
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-event-quotes',
				'parent' => 'mem-events',
				'title'  => sprintf( esc_html__( '%s Quotes', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				'href'   => admin_url( 'edit.php?post_type=mem-quotes' ),
				'meta'   => array(
					'title' => sprintf( esc_html__( 'View %s Quotes', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
				),
			)
		);
	}
	// Reporting
	/*
	if( current_user_can( 'manage_options' ) ) {
		$admin_bar->add_menu( array(
			'id' => 'mem-reports',
			'parent' => 'mem',
			'title' => __( 'Reports', 'mobile-events-manager' ),
			'href' => admin_url( 'admin.php?page=mem-reports' ),
			'meta' => array(
				'title' => __( 'MEM Reports', 'mobile-events-manager' ),
			),
		) );
	}*/
	if ( mem_employee_can( 'edit_txns' ) ) {
		// Transactions
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-transactions',
				'parent' => 'mem',
				'title'  => __( 'Transactions', 'mobile-events-manager' ),
				'href'   => 'edit.php?post_type=mem-transaction',
				'meta'   => array(
					'title' => __( 'MEM Transactions', 'mobile-events-manager' ),
				),
			)
		);
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-add-transaction',
				'parent' => 'mem-transactions',
				'title'  => __( 'Add Transaction', 'mobile-events-manager' ),
				'href'   => admin_url( 'post-new.php?post_type=mem-transaction' ),
				'meta'   => array(
					'title' => __( 'Add Transaction', 'mobile-events-manager' ),
				),
			)
		);
		// Transaction Types
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-transaction-types',
				'parent' => 'mem-transactions',
				'title'  => __( 'Transaction Types', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit-tags.php?taxonomy=transaction-types&post_type=mem-transaction' ),
				'meta'   => array(
					'title' => __( 'View / Edit Transaction Types', 'mobile-events-manager' ),
				),
			)
		);
	}
	if ( mem_employee_can( 'list_venues' ) ) {
		// Venues
		$admin_bar->add_menu(
			array(
				'id'     => 'mem-venues',
				'parent' => 'mem',
				'title'  => __( 'Venues', 'mobile-events-manager' ),
				'href'   => admin_url( 'edit.php?post_type=mem-venue' ),
				'meta'   => array(
					'title' => __( 'Venues', 'mobile-events-manager' ),
				),
			)
		);
		if ( mem_employee_can( 'add_venues' ) ) {
			$admin_bar->add_menu(
				array(
					'id'     => 'mem-add-venue',
					'parent' => 'mem-venues',
					'title'  => __( 'Add Venue', 'mobile-events-manager' ),
					'href'   => admin_url( 'post-new.php?post_type=mem-venue' ),
					'meta'   => array(
						'title' => __( 'Add New Venue', 'mobile-events-manager' ),
					),
				)
			);
			$admin_bar->add_menu(
				array(
					'id'     => 'mem-venue-details',
					'parent' => 'mem-venues',
					'title'  => __( 'Venue Details', 'mobile-events-manager' ),
					'href'   => admin_url( 'edit-tags.php?taxonomy=venue-details&post_type=mem-venue' ),
					'meta'   => array(
						'title' => __( 'View / Edit Venue Details', 'mobile-events-manager' ),
					),
				)
			);
		}
	}
	// MEM Links
	$admin_bar->add_menu(
		array(
			'id'     => 'mem-user-guides',
			'parent' => 'mem',
			'title'  => sprintf( esc_html__( '%1$sDocumentation%2$s', 'mobile-events-manager' ), '<span style="color:#F90">', '</span>' ),
			'href'   => 'http://mobile-events-manager.co.uk/support/',
			'meta'   => array(
				'title'  => __( 'Documentation', 'mobile-events-manager' ),
				'target' => '_blank',
			),
		)
	);
	$admin_bar->add_menu(
		array(
			'id'     => 'mem-support',
			'parent' => 'mem',
			'title'  => sprintf( esc_html__( '%1$sSupport%2$s', 'mobile-events-manager' ), '<span style="color:#F90">', '</span>' ),
			'href'   => 'http://www.mobile_events_manager.co.uk/forums/',
			'meta'   => array(
				'title'  => __( 'MEM Support Forums', 'mobile-events-manager' ),
				'target' => '_blank',
			),
		)
	);
} // mem_admin_toolbar
add_action( 'admin_bar_menu', 'mem_admin_toolbar', 99 );

function mem_clients_page() {
	include_once MEM_PLUGIN_DIR . '/includes/admin/pages/clients.php';
} // mem_clients_page

function mem_employee_availability_page() {
	include_once MEM_PLUGIN_DIR . '/includes/admin/pages/availability.php';
} // mem_employee_availability_page

function mem_custom_client_fields_page() {
	new MEM_ClientFields();
} // mem_custom_client_fields_page
