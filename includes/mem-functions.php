<?php
/**
 * mem-functions.php
 * 17/03/2015
 * Contains all main MEM functions used in front & back end
 */

/*
 * START GENERAL FUNCTIONS
 */

	/**
	 * Return the admin URL for the given page
	 *
	 * @params  STR     $mem_page  Required: The page for which we want the URL
	 *          str     $action     Optional: Whether to return as string (Default) or echo the URL.
	 * @returns $mem_page - str or echo
	 */
function mem_get_admin_page( $mem_page, $action = 'str' ) {
	if ( empty( $mem_page ) ) {
		return;
	}

	$mobile_events_manager = array( 'mobile_events_manager', 'user_guides', 'mem_support', 'mem_forums' );
	$mem_pages  = array(
		'wp_dashboard'        => 'index.php',
		'dashboard'           => 'admin.php?page=mem-dashboard',
		'settings'            => 'admin.php?page=mem-settings',
		'payment_settings'    => 'admin.php?page=mem-settings&tab=payments',
		'clientzone_settings' => 'admin.php?page=mem-settings&tab=client-zone',
		'clients'             => 'admin.php?page=mem-clients',
		'employees'           => 'admin.php?page=mem-employees',
		'permissions'         => 'admin.php?page=mem-employees&tab=permissions',
		'inactive_clients'    => 'admin.php?page=mem-clients&display=inactive_client',
		'add_client'          => 'user-new.php',
		'edit_client'         => 'user-edit.php?user_id=',
		'comms'               => 'admin.php?page=mem-comms',
		'email_history'       => 'edit.php?post_type=mem_communication',
		'contract'            => 'edit.php?post_type=contract',
		'signed_contract'     => 'edit.php?post_type=mem-signed-contract',
		'add_contract'        => 'post-new.php?post_type=contract',
		'djs'                 => 'admin.php?page=mem-djs',
		'inactive_djs'        => 'admin.php?page=mem-djs&display=inactive_dj',
		'email_template'      => 'edit.php?post_type=email_template',
		'add_email_template'  => 'post-new.php?post_type=email_template',
		'equipment'           => 'admin.php?page=mem-packages',
		'events'              => 'edit.php?post_type=mem-event',
		'add_event'           => 'post-new.php?post_type=mem-event',
		'enquiries'           => 'edit.php?post_status=mem-enquiry&post_type=mem-event',
		'unattended'          => 'edit.php?post_status=mem-unattended&post_type=mem-event',
		'awaitingdeposit'     => 'edit.php?post_status=mem0awaitingdeposit&post_type=mem-event',
		'playlists'           => 'admin.php?page=mem-playlists&event_id=',
		'custom_event_fields' => 'admin.php?page=mem-custom-event-fields',
		'venues'              => 'edit.php?post_type=mem-venue',
		'add_venue'           => 'post-new.php?post_type=mem-venue',
		'tasks'               => 'admin.php?page=mem-tasks',
		'client_text'         => 'admin.php?page=mem-settings&tab=client-zone&section=mem_app_text',
		'client_fields'       => 'admin.php?page=mem-custom-client-fields',
		'availability'        => 'admin.php?page=mem-availability',
		'debugging'           => 'admin.php?page=mem-settings&tab=general&section=mem_app_debugging',
		'contact_forms'       => 'admin.php?page=mem-contact-forms',
		'transactions'        => 'edit.php?post_type=mem-transaction',
		'updated'             => 'admin.php?page=mem-updated',
		'about'               => 'admin.php?page=mem-about',
		'mobile_events_manager'         => 'http://mobileeventsmanager.co.uk',
		'user_guides'         => 'http://mobileeventsmanager.co.uk/support/user-guides',
		'mem_support'        => 'http://mobileeventsmanager.co.uk/support',
		'mem_forums'         => 'http://mobileeventsmanager.co.uk/forums',
	);
	if ( in_array( $mem_page, $mobile_events_manager ) ) {
		$mem_page = $mem_pages[ $mem_page ];
	} else {
		$mem_page = admin_url( $mem_pages[ $mem_page ] );
	}
	if ( 'str' === $action ) {
		return $mem_page;
	} else {
		echo $mem_page;
		return;
	}
} // mem_get_admin_page

	/**
	 * Display update notice within Admin UI
	 *
	 * @param   str $class      Required: The admin notice class - updated | update-nag | error
	 *      str     $message    Required: Translated notice message
	 *      bool    $dismiss    Optional: true will make the notice dismissable. Default false.
	 */
function mem_update_notice( $class, $message, $dismiss = '' ) {
	$dismiss = ( ! empty( $dismiss ) ? ' notice is-dismissible' : '' );

	echo '<div id="message" class="' . $class . $dismiss . '">';
	echo '<p>' . __( $message, 'mobile-events-manager' ) . '</p>';
	echo '</div>';
} // mem_update_notice

/**
 * -- START CUSTOM FIELD FUNCTIONS
 */
	/**
	 * Retrieve all custom fields for the relevant section of the event
	 *
	 * @param str $section Optional: The section for which to retrieve the fields. If empty retrieve all.
	 * str $orderby Optional. Which field to order by. Default to menu order
	 * str $order Optional. ASC or DESC. Default ASC
	 * int $limit Optional: The number of results to return. Default -1 (all)
	 *
	 * @return arr $fields The custom event fields
	 */
function mem_get_custom_fields( $section = '', $orderby = 'menu_order', $order = 'ASC', $limit = -1 ) {
	// Retrieve fields for given $section and return as object.
	if ( ! empty( $section ) ) {

		$custom_fields = new WP_Query(
			array(
				'posts_per_page' => $limit,
				'post_type'      => 'mem-custom-fields',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'field_clause' => array(
						'key'   => '_mem_field_section',
						'value' => $section,
					),
				),
				'orderby'        => array(
					'field_clause' => $order,
					$orderby       => $order,
				),
				'order'          => $order,
			)
		);

	} else { // Retrieve fields for all custom event fields return as object.

		$custom_fields = new WP_Query(
			array(
				'posts_per_page' => $limit,
				'post_type'      => 'mem-custom-fields',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'field_clause' => array(
						'key' => '_mem_field_section',
					),
				),
				'orderby'        => array(
					'field_clause' => $order,
					$orderby       => $order,
				),
				'order'          => $order,
			)
		);

	}

	return $custom_fields;
} // mem_get_custom_fields

/**
 * -- END CUSTOM FIELD FUNCTIONS
 */
