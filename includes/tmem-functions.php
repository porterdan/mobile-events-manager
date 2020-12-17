<?php
/**
 * tmem-functions.php
 * 17/03/2015
 * Contains all main TMEM functions used in front & back end
 */

/*
 * START GENERAL FUNCTIONS
 */

	/**
	 * Return the admin URL for the given page
	 *
	 * @params  STR     $tmem_page  Required: The page for which we want the URL
	 *          str     $action     Optional: Whether to return as string (Default) or echo the URL.
	 * @returns $tmem_page - str or echo
	 */
function tmem_get_admin_page( $tmem_page, $action = 'str' ) {
	if ( empty( $tmem_page ) ) {
		return;
	}

	$mydjplanner = array( 'mydjplanner', 'user_guides', 'tmem_support', 'tmem_forums' );
	$tmem_pages  = array(
		'wp_dashboard'        => 'index.php',
		'dashboard'           => 'admin.php?page=tmem-dashboard',
		'settings'            => 'admin.php?page=tmem-settings',
		'payment_settings'    => 'admin.php?page=tmem-settings&tab=payments',
		'clientzone_settings' => 'admin.php?page=tmem-settings&tab=client-zone',
		'clients'             => 'admin.php?page=tmem-clients',
		'employees'           => 'admin.php?page=tmem-employees',
		'permissions'         => 'admin.php?page=tmem-employees&tab=permissions',
		'inactive_clients'    => 'admin.php?page=tmem-clients&display=inactive_client',
		'add_client'          => 'user-new.php',
		'edit_client'         => 'user-edit.php?user_id=',
		'comms'               => 'admin.php?page=tmem-comms',
		'email_history'       => 'edit.php?post_type=tmem_communication',
		'contract'            => 'edit.php?post_type=contract',
		'signed_contract'     => 'edit.php?post_type=tmem-signed-contract',
		'add_contract'        => 'post-new.php?post_type=contract',
		'djs'                 => 'admin.php?page=tmem-djs',
		'inactive_djs'        => 'admin.php?page=tmem-djs&display=inactive_dj',
		'email_template'      => 'edit.php?post_type=email_template',
		'add_email_template'  => 'post-new.php?post_type=email_template',
		'equipment'           => 'admin.php?page=tmem-packages',
		'events'              => 'edit.php?post_type=tmem-event',
		'add_event'           => 'post-new.php?post_type=tmem-event',
		'enquiries'           => 'edit.php?post_status=tmem-enquiry&post_type=tmem-event',
		'unattended'          => 'edit.php?post_status=tmem-unattended&post_type=tmem-event',
		'awaitingdeposit'     => 'edit.php?post_status=tmem0awaitingdeposit&post_type=tmem-event',
		'playlists'           => 'admin.php?page=tmem-playlists&event_id=',
		'custom_event_fields' => 'admin.php?page=tmem-custom-event-fields',
		'venues'              => 'edit.php?post_type=tmem-venue',
		'add_venue'           => 'post-new.php?post_type=tmem-venue',
		'tasks'               => 'admin.php?page=tmem-tasks',
		'client_text'         => 'admin.php?page=tmem-settings&tab=client-zone&section=tmem_app_text',
		'client_fields'       => 'admin.php?page=tmem-custom-client-fields',
		'availability'        => 'admin.php?page=tmem-availability',
		'debugging'           => 'admin.php?page=tmem-settings&tab=general&section=tmem_app_debugging',
		'contact_forms'       => 'admin.php?page=tmem-contact-forms',
		'transactions'        => 'edit.php?post_type=tmem-transaction',
		'updated'             => 'admin.php?page=tmem-updated',
		'about'               => 'admin.php?page=tmem-about',
		'mydjplanner'         => 'https://www.mobileeventsmanager.co.uk',
		'user_guides'         => 'https://www.mobileeventsmanager.co.uk/support/user-guides',
		'tmem_support'        => 'https://www.mobileeventsmanager.co.uk/support',
		'tmem_forums'         => 'https://www.mobileeventsmanager.co.uk/forums',
	);
	if ( in_array( $tmem_page, $mydjplanner ) ) {
		$tmem_page = $tmem_pages[ $tmem_page ];
	} else {
		$tmem_page = admin_url( $tmem_pages[ $tmem_page ] );
	}
	if ( 'str' === $action ) {
		return $tmem_page;
	} else {
		echo $tmem_page;
		return;
	}
} // tmem_get_admin_page

	/**
	 * Display update notice within Admin UI
	 *
	 * @param   str $class      Required: The admin notice class - updated | update-nag | error
	 *      str     $message    Required: Translated notice message
	 *      bool    $dismiss    Optional: true will make the notice dismissable. Default false.
	 */
function tmem_update_notice( $class, $message, $dismiss = '' ) {
	$dismiss = ( ! empty( $dismiss ) ? ' notice is-dismissible' : '' );

	echo '<div id="message" class="' . $class . $dismiss . '">';
	echo '<p>' . __( $message, 'mobile-events-manager' ) . '</p>';
	echo '</div>';
} // tmem_update_notice

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
function tmem_get_custom_fields( $section = '', $orderby = 'menu_order', $order = 'ASC', $limit = -1 ) {
	// Retrieve fields for given $section and return as object.
	if ( ! empty( $section ) ) {

		$custom_fields = new WP_Query(
			array(
				'posts_per_page' => $limit,
				'post_type'      => 'tmem-custom-fields',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'field_clause' => array(
						'key'   => '_tmem_field_section',
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
				'post_type'      => 'tmem-custom-fields',
				'post_status'    => 'publish',
				'meta_query'     => array(
					'field_clause' => array(
						'key' => '_tmem_field_section',
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
} // tmem_get_custom_fields

/**
 * -- END CUSTOM FIELD FUNCTIONS
 */
