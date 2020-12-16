<?php
/**
 * Post Type Functions
 *
 * @package MEM
 * @subpackage Functions
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and sets up the Mobile Events Manager (MEM) custom post types
 *
 * @since 1.3
 * @return void
 */
function mem_register_post_types() {

	// Event Post Type.
	$event_labels = apply_filters(
		'mem_event_labels',
		array(
			'name'               => _x( '%2$s', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( '%1$s', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'MEM %2$s', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( '%1$s', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Create %1$s', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Create New %1$s', 'mobile-events-manager' ),
			'new_item'           => __( 'New %1$s', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit %1$s', 'mobile-events-manager' ),
			'view_item'          => __( 'View %1$s', 'mobile-events-manager' ),
			'all_items'          => __( 'All %2$s', 'mobile-events-manager' ),
			'search_items'       => __( 'Search %2$s', 'mobile-events-manager' ),
			'not_found'          => __( 'No %3$s found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No %3$s found in Trash.', 'mobile-events-manager' ),
		)
	);

	foreach ( $event_labels as $key => $value ) {
		$event_labels[ $key ] = sprintf( $value, esc_html( mem_get_label_singular() ), esc_html( mem_get_label_plural() ), mem_get_label_plural( true ) );
	}

	$event_args = array(
		'labels'            => $event_labels,
		'description'       => __( 'MEM Events', 'mobile-events-manager' ),
		'show_ui'           => true,
		'show_in_menu'      => true,
		'menu_position'     => defined( 'MEM_MENU_POS' ) ? MEM_MENU_POS : 58.4,
		'show_in_admin_bar' => true,
		'capability_type'   => 'mem_event',
		'capabilities'      => apply_filters(
			'mem_event_caps',
			array(
				'publish_posts'       => 'publish_mem_events',
				'edit_posts'          => 'edit_mem_events',
				'edit_others_posts'   => 'edit_others_mem_events',
				'delete_posts'        => 'delete_mem_events',
				'delete_others_posts' => 'delete_others_mem_events',
				'read_private_posts'  => 'read_private_mem_events',
				'edit_post'           => 'edit_mem_event',
				'delete_post'         => 'delete_mem_event',
				'read_post'           => 'read_mem_event',
			)
		),
		'map_meta_cap'      => true,
		'has_archive'       => true,
		'supports'          => apply_filters( 'mem_event_supports', false ),
		'menu_icon'         => plugins_url( 'mobile-events-manager/assets/images/mem-menu-16x16.jpg' ),
		'taxonomies'        => array( 'mem-event' ),
	);
	register_post_type( 'mem-event', apply_filters( 'mem_event_post_type_args', $event_args ) );

	if ( mem_packages_enabled() ) {
		// Packages Post Type.
		$package_labels = apply_filters(
			'mem_package_labels',
			array(
				'name'               => _x( 'Packages', 'post type general name', 'mobile-events-manager' ),
				'singular_name'      => _x( 'Package', 'post type singular name', 'mobile-events-manager' ),
				'menu_name'          => _x( 'Packages', 'admin menu', 'mobile-events-manager' ),
				'name_admin_bar'     => _x( 'Package', 'add new on admin bar', 'mobile-events-manager' ),
				'add_new'            => __( 'Add Package', 'mobile-events-manager' ),
				'add_new_item'       => __( 'Add New Package', 'mobile-events-manager' ),
				'new_item'           => __( 'New Package', 'mobile-events-manager' ),
				'edit_item'          => __( 'Edit Package', 'mobile-events-manager' ),
				'view_item'          => __( 'View Package', 'mobile-events-manager' ),
				'all_items'          => __( 'All Packages', 'mobile-events-manager' ),
				'search_items'       => __( 'Search Packages', 'mobile-events-manager' ),
				'not_found'          => __( 'No packages found.', 'mobile-events-manager' ),
				'not_found_in_trash' => __( 'No packages found in Trash.', 'mobile-events-manager' ),
			)
		);

		$package_args = array(
			'labels'       => $package_labels,
			'description'  => __( 'Equipment Packages for the Mobile Events Manager (MEM) plugin', 'mobile-events-manager' ),
			'public'       => true,
			'show_in_menu' => 'edit.php?post_type=mem-package',
			// 'capability_type' => 'post',

			/*
			'capabilities' => apply_filters( 'mem_package_caps', array(
				'publish_posts' => 'publish_mem_packages',
				'edit_posts' => 'edit_mem_packages',
				'edit_others_posts' => 'edit_others_mem_packages',
				'delete_posts' => 'delete_mem_packages',
				'delete_others_posts' => 'delete_others_mem_packages',
				'read_private_posts' => 'read_private_mem_packages',
				'edit_post' => 'edit_mem_package',
				'delete_post' => 'delete_mem_package',
				'read_post' => 'read_mem_package',
			) ),
			'map_meta_cap' => true,
			*/
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'packages' ),
			'supports'     => apply_filters( 'mem_package_supports', array( 'title', 'editor', 'revisions', 'excerpt', 'thumbnail' ) ),
		);
		register_post_type( 'mem-package', apply_filters( 'mem_package_post_type_args', $package_args ) );

		// Addons Post Type.
		$addon_labels = apply_filters(
			'mem_addon_labels',
			array(
				'name'               => _x( 'Addons', 'post type general name', 'mobile-events-manager' ),
				'singular_name'      => _x( 'Addon', 'post type singular name', 'mobile-events-manager' ),
				'menu_name'          => _x( 'Addons', 'admin menu', 'mobile-events-manager' ),
				'name_admin_bar'     => _x( 'Addon', 'add new on admin bar', 'mobile-events-manager' ),
				'add_new'            => __( 'Add Addon', 'mobile-events-manager' ),
				'add_new_item'       => __( 'Add New Addon', 'mobile-events-manager' ),
				'new_item'           => __( 'New Addon', 'mobile-events-manager' ),
				'edit_item'          => __( 'Edit Addon', 'mobile-events-manager' ),
				'view_item'          => __( 'View Addon', 'mobile-events-manager' ),
				'all_items'          => __( 'All Addons', 'mobile-events-manager' ),
				'search_items'       => __( 'Search Addons', 'mobile-events-manager' ),
				'not_found'          => __( 'No addons found.', 'mobile-events-manager' ),
				'not_found_in_trash' => __( 'No addons found in Trash.', 'mobile-events-manager' ),
			)
		);

		$addon_args = array(
			'labels'       => $addon_labels,
			'description'  => __( 'Equipment Addons for the Mobile Events Manager (MEM) plugin', 'mobile-events-manager' ),
			'public'       => true,
			'show_in_menu' => 'edit.php?post_type=mem-addon',
			'has_archive'  => true,
			'rewrite'      => array( 'slug' => 'addons' ),
			'supports'     => apply_filters( 'mem_addon_supports', array( 'title', 'editor', 'revisions', 'excerpt', 'thumbnail' ) ),
		);
		register_post_type( 'mem-addon', apply_filters( 'mem_addon_post_type_args', $addon_args ) );

	}

	// Communication History Post Type.
	$email_history_labels = apply_filters(
		'mem_email_history_labels',
		array(
			'name'               => _x( 'Email History', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Email History', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Email History', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Email History', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Communication', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Communication', 'mobile-events-manager' ),
			'new_item'           => __( 'New Communication', 'mobile-events-manager' ),
			'edit_item'          => __( 'Review Email', 'mobile-events-manager' ),
			'view_item'          => __( 'View Email', 'mobile-events-manager' ),
			'all_items'          => __( 'All Emails', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Emails', 'mobile-events-manager' ),
			'not_found'          => __( 'No Emails found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No Emails found in Trash.', 'mobile-events-manager' ),
		)
	);

	$email_history_args = array(
		'labels'              => $email_history_labels,
		'description'         => __( 'Communication used by the Mobile Events Manager (MEM) for WordPress plugin', 'mobile-events-manager' ),
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=mem_communication',
		'show_in_admin_bar'   => false,
		'rewrite'             => array( 'slug' => 'mem-communications' ),
		'capability_type'     => 'mem_comm',
		'capabilities'        => apply_filters(
			'mem_communications_caps',
			array(
				'edit_post'          => 'edit_mem_comm',
				'read_post'          => 'read_mem_comm',
				'delete_post'        => 'delete_mem_comm',
				'edit_posts'         => 'edit_mem_comms',
				'edit_others_posts'  => 'edit_others_mem_comms',
				'publish_posts'      => 'publish_mem_comms',
				'read_private_posts' => 'read_private_mem_comms',
			)
		),
		'map_meta_cap'        => true,
		'has_archive'         => true,
		'supports'            => apply_filters( 'mem_email_history_supports', array( 'title' ) ),
	);
	register_post_type( 'mem_communication', apply_filters( 'mem_email_history_post_type_args', $email_history_args ) );

	// Contract Post Type.
	$contract_labels = apply_filters(
		'mem_contract_labels',
		array(
			'name'               => _x( 'Contract Templates', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Contract Template', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Contract Templates', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Contract Template', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Contract Template', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Contract Template', 'mobile-events-manager' ),
			'new_item'           => __( 'New Contract Template', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Contract Template', 'mobile-events-manager' ),
			'view_item'          => __( 'View Contract Template', 'mobile-events-manager' ),
			'all_items'          => __( 'All Contract Templates', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Contract Templates', 'mobile-events-manager' ),
			'not_found'          => __( 'No contract templates found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No contract templates found in Trash.', 'mobile-events-manager' ),
		)
	);

	$contract_args = array(
		'labels'              => $contract_labels,
		'description'         => __( 'Contracts used by the MEM plugin', 'mobile-events-manager' ),
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => 'edit.php?post_type=contract',
		'rewrite'             => array( 'slug' => 'contract-templates' ),
		'capability_type'     => array( 'mem_template', 'mem_templates' ),
		'capabilities'        => apply_filters(
			'mem_contract_caps',
			array(
				'edit_post'          => 'edit_mem_template',
				'read_post'          => 'read_mem_template',
				'delete_post'        => 'delete_mem_template',
				'edit_posts'         => 'edit_mem_templates',
				'edit_others_posts'  => 'edit_others_mem_templates',
				'publish_posts'      => 'publish_mem_templates',
				'read_private_posts' => 'read_private_mem_templates',
			)
		),
		'map_meta_cap'        => true,
		'has_archive'         => true,
		'supports'            => apply_filters( 'mem_contract_supports', array( 'title', 'editor', 'revisions' ) ),
	);
	register_post_type( 'contract', apply_filters( 'mem_contract_post_type_args', $contract_args ) );

	// Signed Contract Post Type.
	$signed_contract_labels = apply_filters(
		'mem_signed_contract_labels',
		array(
			'name'               => _x( 'Signed Contracts', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Signed Contract', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Signed Contracts', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Signed Contract', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Signed Contract', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Signed Contract', 'mobile-events-manager' ),
			'new_item'           => __( 'New Signed Contract', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Signed Contract', 'mobile-events-manager' ),
			'view_item'          => __( 'View Signed Contract', 'mobile-events-manager' ),
			'all_items'          => __( 'All Signed Contracts', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Signed Contracts', 'mobile-events-manager' ),
			'not_found'          => __( 'No signed contracts found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No signed contracts found in Trash.', 'mobile-events-manager' ),
		)
	);

	$signed_contract_args = array(
		'labels'          => $signed_contract_labels,
		'description'     => __( 'Signed Contracts used by the MEM plugin', 'mobile-events-manager' ),
		'rewrite'         => array( 'slug' => 'mem-signed-contract' ),
		'capability_type' => array( 'mem_signed_contract', 'mem_signed_contracts' ),
		'map_meta_cap'    => true,
		'has_archive'     => true,
		'supports'        => array( '' ),
	);
	register_post_type( 'mem-signed-contract', apply_filters( 'mem_signed_contract_post_type_args', $signed_contract_args ) );

	// Custom Field Post Type.
	$custom_field_labels = apply_filters(
		'mem_custom_field_contract_labels',
		array(
			'name'               => _x( 'Custom Event Fields', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Custom Event Field', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Custom Event Fields', 'admin menu', 'mobile-events-manager' ),
			'add_new'            => _x( 'Add Custom Event Field', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Custom Event Field', 'mobile-events-manager' ),
			'edit'               => __( 'Edit Custom Event Field', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Custom Event Field', 'mobile-events-manager' ),
			'new_item'           => __( 'New Hosted Plugin', 'mobile-events-manager' ),
			'view'               => __( 'View Custom Event Field', 'mobile-events-manager' ),
			'view_item'          => __( 'View Custom Event Field', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Custom Event Field', 'mobile-events-manager' ),
			'not_found'          => __( 'No Custom Event Fields found', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No Custom Event Fields found in trash', 'mobile-events-manager' ),
		)
	);

	$custom_field_args = array(
		'labels'      => $custom_field_labels,
		'description' => __( 'This is where you can add Custom Event Fields for use in the event screen.', 'mobile-events-manager' ),
		'rewrite'     => array( 'slug' => 'mem-custom-fields' ),
		'supports'    => array( 'title' ),
	);
	register_post_type( 'mem-custom-field', apply_filters( 'mem_custom_field_post_type_args', $custom_field_args ) );

	// Email Template Post Type.
	$email_template_labels = apply_filters(
		'mem_email_template_labels',
		array(
			'name'               => _x( 'Email Templates', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Email Template', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Email Templates', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Email Template', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Template', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Template', 'mobile-events-manager' ),
			'new_item'           => __( 'New Template', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Template', 'mobile-events-manager' ),
			'view_item'          => __( 'View Template', 'mobile-events-manager' ),
			'all_items'          => __( 'All Templates', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Templates', 'mobile-events-manager' ),
			'not_found'          => __( 'No templates found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No templates found in Trash.', 'mobile-events-manager' ),
		)
	);

	$email_template_args = array(
		'labels'            => $email_template_labels,
		'description'       => __( 'Email Templates for the Mobile Events Manager (MEM) plugin', 'mobile-events-manager' ),
		'show_ui'           => true,
		'show_in_menu'      => 'edit.php?post_type=email_template',
		'show_in_admin_bar' => true,
		'rewrite'           => array( 'slug' => 'email-template' ),
		'capability_type'   => 'mem_template',
		'capabilities'      => apply_filters(
			'mem_email_template_caps',
			array(
				'publish_posts'       => 'publish_mem_templates',
				'edit_posts'          => 'edit_mem_templates',
				'edit_others_posts'   => 'edit_others_mem_templates',
				'delete_posts'        => 'delete_mem_templates',
				'delete_others_posts' => 'delete_others_mem_templates',
				'read_private_posts'  => 'read_private_mem_templates',
				'edit_post'           => 'edit_mem_template',
				'delete_post'         => 'delete_mem_template',
				'read_post'           => 'read_mem_template',
			)
		),
		'map_meta_cap'      => true,
		'has_archive'       => true,
		'supports'          => apply_filters( 'mem_email_template_supports', array( 'title', 'editor', 'revisions' ) ),
	);
	register_post_type( 'email_template', apply_filters( 'mem_email_template_post_type_args', $email_template_args ) );

	// Playlist Post Type.
	$playlist_labels = apply_filters(
		'mem_playlist_labels',
		array(
			'name'               => _x( 'Playlist Entries', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Playlist Entry', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Playlist Entries', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Playlist Entry', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Playlist Entry', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Playlist Entry', 'mobile-events-manager' ),
			'new_item'           => __( 'New Entry', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Entry', 'mobile-events-manager' ),
			'view_item'          => __( 'View Entry', 'mobile-events-manager' ),
			'all_items'          => __( 'All Entries', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Entries', 'mobile-events-manager' ),
			'not_found'          => __( 'No entries found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No entries found in Trash.', 'mobile-events-manager' ),
		)
	);

	$playlist_args = array(
		'labels'          => $playlist_labels,
		'description'     => __( 'Mobile Events Manager (MEM) Playlist Entries', 'mobile-events-manager' ),
		'show_ui'         => true,
		'show_in_menu'    => false,
		'capability_type' => 'mem_playlist',
		'capabilities'    => apply_filters(
			'mem_playlist_caps',
			array(
				'edit_post'          => 'edit_mem_playlist',
				'read_post'          => 'read_mem_playlist',
				'delete_post'        => 'delete_mem_playlist',
				'edit_posts'         => 'edit_mem_playlists',
				'edit_others_posts'  => 'edit_others_mem_playlists',
				'publish_posts'      => 'publish_mem_playlists',
				'read_private_posts' => 'read_private_mem_playlists',
			)
		),
		'map_meta_cap'    => true,
		'supports'        => apply_filters( 'mem_playlist_supports', array( 'title' ) ),
		'taxonomies'      => array( 'mem-playlist' ),
	);
	register_post_type( 'mem-playlist', apply_filters( 'mem_playlist_post_type_args', $playlist_args ) );

	// Quote Post Type.
	$quote_labels = apply_filters(
		'mem_quote_labels',
		array(
			'name'               => _x( 'Quotes', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Quote', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Quotes', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Quote', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Create Quote', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Create New Quote', 'mobile-events-manager' ),
			'new_item'           => __( 'New Quote', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Quote', 'mobile-events-manager' ),
			'view_item'          => __( 'View Quote', 'mobile-events-manager' ),
			'all_items'          => __( 'All Quotes', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Quotes', 'mobile-events-manager' ),
			'not_found'          => __( 'No quotes found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No quotes found in Trash.', 'mobile-events-manager' ),
		)
	);

	$quote_args = array(
		'labels'            => $quote_labels,
		'description'       => __( 'Mobile Events Manager (MEM) Quotes', 'mobile-events-manager' ),
		'show_ui'           => true,
		'show_in_menu'      => 'edit.php?post_type=mem-quotes',
		'show_in_admin_bar' => false,
		'rewrite'           => array( 'slug' => 'mem-quotes' ),
		'capability_type'   => 'mem_quote',
		'capabilities'      => apply_filters(
			'mem_quote_caps',
			array(
				'edit_post'          => 'edit_mem_quote',
				'read_post'          => 'read_mem_quote',
				'delete_post'        => 'delete_mem_quote',
				'edit_posts'         => 'edit_mem_quotes',
				'edit_others_posts'  => 'edit_others_mem_quotes',
				'publish_posts'      => 'publish_mem_quotes',
				'read_private_posts' => 'read_private_mem_quotes',
			)
		),
		'map_meta_cap'      => true,
		'has_archive'       => true,
		'supports'          => apply_filters( 'mem_quote_supports', array( 'title' ) ),
	);
	register_post_type( 'mem-quotes', apply_filters( 'mem_quotes_post_type_args', $quote_args ) );

	// Transaction Post Type.
	$txn_labels = apply_filters(
		'mem_txn_labels',
		array(
			'name'               => _x( 'Transactions', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Transaction', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Transactions', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Transaction', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Transaction', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Transaction', 'mobile-events-manager' ),
			'new_item'           => __( 'New Transaction', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Transaction', 'mobile-events-manager' ),
			'view_item'          => __( 'View Transaction', 'mobile-events-manager' ),
			'all_items'          => __( 'All Transactions', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Transactions', 'mobile-events-manager' ),
			'not_found'          => __( 'No Transactions found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No Transactions found in Trash.', 'mobile-events-manager' ),
		)
	);

	$txn_args = array(
		'labels'            => $txn_labels,
		'description'       => __( 'Transactions for the Mobile Events Manager (MEM) plugin', 'mobile-events-manager' ),
		'show_ui'           => true,
		'show_in_menu'      => 'edit.php?post_type=mem-transaction',
		'show_in_admin_bar' => true,
		'rewrite'           => array( 'slug' => 'mem-transaction' ),
		'capability_type'   => 'mem_txn',
		'capabilities'      => apply_filters(
			'mem_transaction_caps',
			array(
				'edit_post'          => 'edit_mem_txn',
				'read_post'          => 'read_mem_txn',
				'delete_post'        => 'delete_mem_txn',
				'edit_posts'         => 'edit_mem_txns',
				'edit_others_posts'  => 'edit_others_mem_txns',
				'publish_posts'      => 'publish_mem_txns',
				'read_private_posts' => 'read_private_mem_txns',
			)
		),
		'map_meta_cap'      => true,
		'has_archive'       => true,
		'supports'          => apply_filters( 'mem_transaction_supports', array( 'title' ) ),
		'taxonomies'        => array( 'mem-transaction' ),
	);
	register_post_type( 'mem-transaction', apply_filters( 'mem_transaction_post_type_args', $txn_args ) );

	// Venue Post Type.
	$venue_labels = apply_filters(
		'mem_txn_labels',
		array(
			'name'               => _x( 'Venues', 'post type general name', 'mobile-events-manager' ),
			'singular_name'      => _x( 'Venue', 'post type singular name', 'mobile-events-manager' ),
			'menu_name'          => _x( 'Venues', 'admin menu', 'mobile-events-manager' ),
			'name_admin_bar'     => _x( 'Venue', 'add new on admin bar', 'mobile-events-manager' ),
			'add_new'            => __( 'Add Venue', 'mobile-events-manager' ),
			'add_new_item'       => __( 'Add New Venue', 'mobile-events-manager' ),
			'new_item'           => __( 'New Venue', 'mobile-events-manager' ),
			'edit_item'          => __( 'Edit Venue', 'mobile-events-manager' ),
			'view_item'          => __( 'View Venue', 'mobile-events-manager' ),
			'all_items'          => __( 'All Venues', 'mobile-events-manager' ),
			'search_items'       => __( 'Search Venues', 'mobile-events-manager' ),
			'not_found'          => __( 'No Venues found.', 'mobile-events-manager' ),
			'not_found_in_trash' => __( 'No Venues found in Trash.', 'mobile-events-manager' ),
		)
	);

	$venue_args = array(
		'labels'            => $venue_labels,
		'description'       => __( 'Venues stored for the Mobile Events Manager (MEM) plugin', 'mobile-events-manager' ),
		'show_ui'           => true,
		'show_in_menu'      => 'edit.php?post_type=mem-venue',
		'show_in_admin_bar' => true,
		'rewrite'           => array( 'slug' => 'mem-venue' ),
		'capability_type'   => 'mem_venue',
		'capabilities'      => apply_filters(
			'mem_venue_caps',
			array(
				'edit_post'          => 'edit_mem_venue',
				'read_post'          => 'read_mem_venue',
				'delete_post'        => 'delete_mem_venue',
				'edit_posts'         => 'edit_mem_venues',
				'edit_others_posts'  => 'edit_others_mem_venues',
				'publish_posts'      => 'publish_mem_venues',
				'read_private_posts' => 'read_private_mem_venues',
			)
		),
		'map_meta_cap'      => true,
		'has_archive'       => true,
		'supports'          => apply_filters( 'mem_venue_supports', array( 'title' ) ),
		'taxonomies'        => array( 'mem-venue' ),
	);
	register_post_type( 'mem-venue', apply_filters( 'mem_venue_post_type_args', $venue_args ) );
} // mem_register_post_types
add_action( 'init', 'mem_register_post_types', 1 );

/**
 * Get Default Labels
 *
 * @since 1.3
 * @return arr $defaults Default labels
 */
function mem_get_default_labels() {
	$defaults = array(
		'singular' => __( 'Event', 'mobile-events-manager' ),
		'plural'   => __( 'Events', 'mobile-events-manager' ),
	);
	return apply_filters( 'mem_default_events_name', $defaults );
} // mem_get_default_labels

/**
 * Get Post Status Label
 *
 * @since 1.3
 * @param str $status The post status.
 * @return arr $defaults Default labels
 */
function mem_get_post_status_label( $status ) {
	$object = get_post_status_object( $status );

	return apply_filters( 'mem_post_status_label_{$status}', $object->label );
} // mem_get_post_status_label

/**
 * Get Singular Label
 *
 * @since 1.3
 * @param bool $lowercase
 * @return str $defaults['singular'] Singular label
 */
function mem_get_label_singular( $lowercase = false ) {
	$defaults = mem_get_default_labels();
	return ( $lowercase ) ? strtolower( esc_html( $defaults['singular'] ) ) : esc_html( $defaults['singular'] );
} // mem_get_label_singular

/**
 * Get Plural Label
 *
 * @since 1.3
 * @param bool $lowercase
 * @return str $defaults['plural'] Plural label
 */
function mem_get_label_plural( $lowercase = false ) {
	$defaults = mem_get_default_labels();
	return ( $lowercase ) ? strtolower( esc_html( $defaults['plural'] ) ) : esc_html( $defaults['plural'] );
} // mem_get_label_plural

/**
 * Get the singular and plural labels for custom taxonomies.
 *
 * @since 0.1
 * @param str $taxonomy The Taxonomy to get labels for
 * @return arr Associative array of labels (name = plural)
 */
function mem_get_taxonomy_labels( $taxonomy = 'event-types' ) {

	$allowed_taxonomies = apply_filters(
		'mem_allowed_taxonomies',
		array(
			'addon-category',
			'event-types',
			'mem-playlist',
			'enquiry-source',
			'mem-transactions',
			'package-category',
			'venue-details',
		)
	);

	if ( ! in_array( $taxonomy, $allowed_taxonomies ) ) {
		return false;
	}

	$labels   = array();
	$taxonomy = get_taxonomy( $taxonomy );

	if ( false !== $taxonomy ) {
		$singular = $taxonomy->labels->singular_name;
		$name     = $taxonomy->labels->name;
		$column   = ! empty( $taxonomy->labels->post_column_name ) ? $taxonomy->labels->post_column_name : false;

		$labels = array(
			'name'          => $name,
			'singular_name' => $singular,
			'column_name'   => $column ? $column : '',
		);
	}

	return apply_filters( 'mem_get_taxonomy_labels', $labels, $taxonomy );

} // mem_get_taxonomy_labels

/**
 * Registers Custom Post Statuses which are used by the Communication,
 * Event, Transaction and Quote custom post types.
 *
 * @since 1.3
 * @return void
 */
function mem_register_post_statuses() {
	/** Communication Post Statuses */
	register_post_status(
		'ready to send',
		apply_filters(
			'mem_comm_ready_to_send_status',
			array(
				'label'                     => __( 'Ready to Send', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Ready to Send <span class="count">(%s)</span>', 'Ready to Send <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	register_post_status(
		'sent',
		apply_filters(
			'mem_comm_sent_status',
			array(
				'label'                     => __( 'Sent', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Sent <span class="count">(%s)</span>', 'Sent <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	register_post_status(
		'opened',
		apply_filters(
			'mem_comm_opened_status',
			array(
				'label'                     => __( 'Opened', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Opened <span class="count">(%s)</span>', 'Opened <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	register_post_status(
		'failed',
		apply_filters(
			'mem_comm_failed_status',
			array(
				'label'                     => __( 'Failed', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	/** Event Post Statuses */
	register_post_status(
		'mem-unattended',
		apply_filters(
			'mem_event_unattended_status',
			array(
				'label'                     => __( 'Unattended Enquiry', 'mobile-events-manager' ),
				'plural'                    => __( 'Unattended Enquiries', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Unattended Enquiry <span class="count">(%s)</span>', 'Unattended Enquiries <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-enquiry',
		apply_filters(
			'mem_event_enquiry_status',
			array(
				'label'                     => __( 'Enquiry', 'mobile-events-manager' ),
				'plural'                    => __( 'Enquiries', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Enquiry <span class="count">(%s)</span>', 'Enquiries <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-awaitingdeposit',
		apply_filters(
			'mem_event_awaitingdeposit_status',
			array(
				'label'                     => sprintf( esc_html__( 'Awaiting %s', 'mobile-events-manager' ), mem_get_deposit_label() ),
				'plural'                    => sprintf( esc_html__( 'Awaiting %s' . 's', 'mobile-events-manager' ), mem_get_deposit_label() ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					sprintf( 'Awaiting %s', mem_get_deposit_label() ) . ' <span class="count">(%s)</span>',
					sprintf( 'Awaiting %s', mem_get_deposit_label() ) . ' <span class="count">(%s)</span>',
					'mobile-events-manager'
				),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-contract',
		apply_filters(
			'mem_event_contract_status',
			array(
				'label'                     => __( 'Awaiting Contract', 'mobile-events-manager' ),
				'plural'                    => __( 'Awaiting Contracts', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Awaiting Contract <span class="count">(%s)</span>', 'Awaiting Contracts <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-approved',
		apply_filters(
			'mem_event_approved_status',
			array(
				'label'                     => __( 'Confirmed', 'mobile-events-manager' ),
				'plural'                    => __( 'Confirmed', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-completed',
		apply_filters(
			'mem_event_completed_status',
			array(
				'label'                     => __( 'Completed', 'mobile-events-manager' ),
				'plural'                    => __( 'Completed', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-cancelled',
		apply_filters(
			'mem_event_cancelled_status',
			array(
				'label'                     => __( 'Cancelled', 'mobile-events-manager' ),
				'plural'                    => __( 'Cancelled', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-rejected',
		apply_filters(
			'mem_event_rejected_status',
			array(
				'label'                     => __( 'Rejected Enquiry', 'mobile-events-manager' ),
				'plural'                    => __( 'Rejected Enquiries', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Rejected Enquiry <span class="count">(%s)</span>', 'Rejected Enquiries <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	register_post_status(
		'mem-failed',
		apply_filters(
			'mem_event_failed_status',
			array(
				'label'                     => __( 'Failed Enquiry', 'mobile-events-manager' ),
				'plural'                    => __( 'Failed Enquiries', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Failed Enquiry <span class="count">(%s)</span>', 'Failed Enquiries <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem-event'                => true,
			)
		)
	);

	/** Online Quote Post Statuses */
	register_post_status(
		'mem-quote-generated',
		apply_filters(
			'mem_quote_generated_status',
			array(
				'label'                     => __( 'Generated', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Generated Quote <span class="count">(%s)</span>', 'Generated Quotes <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	register_post_status(
		'mem-quote-viewed',
		apply_filters(
			'mem_quote_viewed_status',
			array(
				'label'                     => __( 'Viewed', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Viewed Quote <span class="count">(%s)</span>', 'Viewed Quotes <span class="count">(%s)</span>', 'mobile-events-manager' ),
			)
		)
	);

	/** Transaction Post Statuses */
	register_post_status(
		'mem-income',
		apply_filters(
			'mem_transaction_income_status',
			array(
				'label'                     => __( 'Income', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Received Payment <span class="count">(%s)</span>', 'Received Payments <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem'                      => true,
			)
		)
	);

	register_post_status(
		'mem-expenditure',
		apply_filters(
			'mem_transaction_expenditure_status',
			array(
				'label'                     => __( 'Expenditure', 'mobile-events-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Outgoing Payment <span class="count">(%s)</span>', 'Outgoing Payments <span class="count">(%s)</span>', 'mobile-events-manager' ),
				'mem'                      => true,
			)
		)
	);
} // mem_register_post_statuses
add_action( 'init', 'mem_register_post_statuses', 2 );

/**
 * Retrieve all MEM Event custom post statuses.
 *
 * @since 1.0
 * @uses get_post_stati()
 * @param str $output The type of output to return, either 'names' or 'objects'. Default 'names'.
 * @return arr|obj
 */
function mem_get_post_statuses( $output = 'names' ) {
	$args['mem-event'] = true;

	$mem_post_statuses = get_post_stati( $args, $output );

	return $mem_post_statuses;
} // mem_get_post_statuses

/**
 * Registers the custom taxonomies for the Event, Playlist.
 * Transaction and Venue custom post types.
 *
 * @since 1.3
 * @return void
 */
function mem_register_taxonomies() {

	/** Packages */
	$package_category_labels = array(
		'name'                       => _x( 'Package Category', 'taxonomy general name', 'mobile-events-manager' ),
		'post_column_name'           => __( 'Categories', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Package Category', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Package Categories', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Package Categories', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Package Category', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Package Category', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Package Category', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Package Category', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Event Package Categories', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Package Categories', 'mobile-events-manager' ),
		'not_found'                  => __( 'No package categories found', 'mobile-events-manager' ),
	);

	$package_category_args = apply_filters(
		'mem_package_category_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_package_category_labels', $package_category_labels ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'package-category' ),
			'capabilities'          => apply_filters(
				'mem_package_category_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'package-category', array( 'mem-package' ), $package_category_args );
	register_taxonomy_for_object_type( 'package-category', 'mem-package' );

	/** Addons */
	$addon_category_labels = array(
		'name'                       => _x( 'Add-on Category', 'taxonomy general name', 'mobile-events-manager' ),
		'post_column_name'           => __( 'Categories', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Add-on Category', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Add-on Categories', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Add-on Categories', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Add-on Category', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Add-on Category', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Add-on Category', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Add-on Category', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Event Add-on Categories', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Add-on Categories', 'mobile-events-manager' ),
		'not_found'                  => __( 'No add-ons categories found', 'mobile-events-manager' ),
	);

	$addon_category_args = apply_filters(
		'mem_addon_category_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_addon_category_labels', $addon_category_labels ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'addon-category' ),
			'capabilities'          => apply_filters(
				'mem_addon_category_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'addon-category', array( 'mem-addon' ), $addon_category_args );
	register_taxonomy_for_object_type( 'addon-category', 'mem-addon' );

	/** Event Types */
	$event_type_labels = array(
		'name'                       => _x( 'Event Type', 'taxonomy general name', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Event Type', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Event Types', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Event Types', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Event Type', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Event Type', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Event Type', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Event Type', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Event Types', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Event Types', 'mobile-events-manager' ),
		'not_found'                  => __( 'No event types found', 'mobile-events-manager' ),
	);

	$event_type_args = apply_filters(
		'mem_event_type_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_event_type_labels', $event_type_labels ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'event-types' ),
			'capabilities'          => apply_filters(
				'mem_event_type_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'event-types', array( 'mem-event' ), $event_type_args );
	register_taxonomy_for_object_type( 'event-types', 'mem-event' );

	/** Enquiry Sources */
	$enquiry_source_labels = array(
		'name'                       => _x( 'Enquiry Sources', 'taxonomy general name', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Enquiry Source', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Enquiry Sources', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Enquiry Sources', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Enquiry Source', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Enquiry Source', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Enquiry Source', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Enquiry Source', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Enquiry Sources', 'mobile-events-manager' ),
		'popular_items'              => __( 'Most Enquiries from', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Enquiry Sources', 'mobile-events-manager' ),
		'not_found'                  => __( 'No enquiry sources found', 'mobile-events-manager' ),
	);

	$enquiry_source_args = apply_filters(
		'mem_enquiry_source_args',
		array(
			'hierarchical'          => false,
			'labels'                => apply_filters( 'mem_enquiry_source_labels', $enquiry_source_labels ),
			'description'           => sprintf( esc_html__( 'Track how clients found %s', 'mobile-events-manager' ), mem_get_option( 'company_name', get_bloginfo( 'name' ) ) ),
			'public'                => false,
			'show_ui'               => true,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'enquiry-source' ),
			'capabilities'          => apply_filters(
				'mem_event_type_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'enquiry-source', array( 'mem-event' ), $enquiry_source_args );
	register_taxonomy_for_object_type( 'enquiry-source', 'mem-event' );

	/** Playlist Category */
	$playlist_category_labels = array(
		'name'                       => _x( 'Playlist Categories', 'taxonomy general name', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Playlist Category', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Playlist Categories', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Playlist Categories', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Playlist Category', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Playlist Category', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Playlist Category', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Playlist Category', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Event Playlist Categories', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Playlist Categories', 'mobile-events-manager' ),
		'not_found'                  => __( 'No playlist categories found', 'mobile-events-manager' ),
	);

	$playlist_category_args = apply_filters(
		'mem_playlist_category_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_playlist_category_labels', $playlist_category_labels ),
			'query_var'             => true,
			'capabilities'          => apply_filters(
				'mem_playlist_category_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'playlist-category', array( 'mem-playlist' ), $playlist_category_args );
	register_taxonomy_for_object_type( 'playlist-category', 'mem-playlist' );

	/** Transaction Types */
	$txn_type_labels = array(
		'name'                       => _x( 'Transaction Type', 'taxonomy general name', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Transaction Type', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Transaction Types', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Transaction Types', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Transaction Type', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Transaction Type', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Transaction Type', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Transaction Type', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Transaction Types', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Transaction Types', 'mobile-events-manager' ),
		'not_found'                  => __( 'No transaction types found', 'mobile-events-manager' ),
	);

	$txn_type_args = apply_filters(
		'mem_transaction_type_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_transaction_type_labels', $txn_type_labels ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'transaction-types' ),
			'capabilities'          => apply_filters(
				'mem_transaction_type_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'transaction-types', array( 'mem-transaction' ), $txn_type_args );
	register_taxonomy_for_object_type( 'transaction-types', 'mem-transaction' );

	/** Venue Details */
	$venue_details_labels = array(
		'name'                       => _x( 'Venue Details', 'taxonomy general name', 'mobile-events-manager' ),
		'singular_name'              => _x( 'Venue Detail', 'taxonomy singular name', 'mobile-events-manager' ),
		'search_items'               => __( 'Search Venue Details', 'mobile-events-manager' ),
		'all_items'                  => __( 'All Venue Details', 'mobile-events-manager' ),
		'edit_item'                  => __( 'Edit Venue Detail', 'mobile-events-manager' ),
		'update_item'                => __( 'Update Venue Detail', 'mobile-events-manager' ),
		'add_new_item'               => __( 'Add New Venue Detail', 'mobile-events-manager' ),
		'new_item_name'              => __( 'New Venue Detail', 'mobile-events-manager' ),
		'menu_name'                  => __( 'Venue Details', 'mobile-events-manager' ),
		'separate_items_with_commas' => null,
		'choose_from_most_used'      => __( 'Choose from the most popular Venue Details', 'mobile-events-manager' ),
		'not_found'                  => __( 'No details found', 'mobile-events-manager' ),
	);

	$venue_details_args = apply_filters(
		'mem_venue_details_args',
		array(
			'hierarchical'          => true,
			'labels'                => apply_filters( 'mem_venue_details_labels', $venue_details_labels ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'venue-details' ),
			'capabilities'          => apply_filters(
				'mem_venue_details_caps',
				array(
					'manage_terms' => 'manage_mem',
					'edit_terms'   => 'manage_mem',
					'delete_terms' => 'manage_mem',
					'assign_terms' => 'mem_employee',
				)
			),
			'update_count_callback' => '_update_generic_term_count',
		)
	);
	register_taxonomy( 'venue-details', array( 'mem-venue' ), $venue_details_args );
	register_taxonomy_for_object_type( 'venue-details', 'mem-venue' );
} // mem_register_taxonomies
add_action( 'init', 'mem_register_taxonomies', 0 );

/**
 * Retrieve all MEM Post Types.
 *
 * @since 1.3
 * @param
 * @return arr
 */
function mem_get_post_types() {
	$post_types = array(
		'mem_communication',
		'contract',
		'mem-custom-fields',
		'mem-signed-contract',
		'email_template',
		'mem-event',
		'mem-quotes',
		'mem-transaction',
		'mem-venue',
	);

	return apply_filters( 'mem_post_types', $post_types );
} // mem_get_post_types
