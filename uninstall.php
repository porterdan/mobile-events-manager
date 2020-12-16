<?php
/**
 * Uninstallation procedures for MEM.
 * What happens here is determined by the plugin uninstallation settings.
 *
 * @since 0.8
 * @package MEM
 */

// Do not run unless the uninstall procedure was called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Call the MEM main class file.
require_once 'mobile-events-manager.php';

global $wpdb;

ignore_user_abort( true );

if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
	@set_time_limit( 0 );
}

remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );
remove_action( 'save_post_mem-package', 'mem_save_package_post', 10, 2 );
remove_action( 'save_post_mem-addon', 'mem_save_addon_post', 10, 2 );
remove_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );
remove_action( 'save_post_mem-venue', 'mem_save_venue_post', 10, 3 );
remove_action( 'mem_delete_package', 'mem_remove_package_from_events' );
remove_action( 'mem_delete_addon', 'mem_remove_addons_from_packages', 10 );
remove_action( 'mem_delete_addon', 'mem_remove_addons_from_events', 15 );
remove_action( 'before_delete_post', 'mem_deleting_package' );
remove_action( 'wp_trash_post', 'mem_deleting_package' );
remove_action( 'before_delete_post', 'mem_deleting_addon' );
remove_action( 'wp_trash_post', 'mem_deleting_addon' );

if ( mem_get_option( 'remove_on_uninstall' ) ) {

	// Delete the Custom Post Types.
	$mem_taxonomies = array(
		'package-category',
		'addon-category',
		'event-types',
		'enquiry-source',
		'playlist-category',
		'transaction-types',
		'venue-details',
	);

	$mem_post_types = array(
		'mem-event',
		'mem-package',
		'mem-addon',
		'mem_communication',
		'contract',
		'mem-signed-contract',
		'mem-custom-field',
		'email_template',
		'mem-playlist',
		'mem-quotes',
		'mem-transaction',
		'mem-venue',
	);

	foreach ( $mem_post_types as $mem_post_type ) {

		$mem_taxonomies = array_merge( $mem_taxonomies, get_object_taxonomies( $mem_post_type ) );
		$items           = get_posts(
			array(
				'post_type'   => $mem_post_type,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);

		if ( $items ) {
			foreach ( $items as $item ) {
				wp_delete_post( $item, true );
			}
		}
	}


	// Delete Terms & Taxonomies.
	foreach ( array_unique( array_filter( $mem_taxonomies ) ) as $mem_taxonomy ) {

		$terms = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.*, tt.*
			FROM $wpdb->terms
			AS t
			INNER JOIN $wpdb->term_taxonomy
			AS tt
			ON t.term_id = tt.term_id
			WHERE tt.taxonomy IN ('%s')
			ORDER BY t.name ASC",
				$mem_taxonomy
			)
		);

		// Delete Terms.
		if ( $terms ) {
			foreach ( $terms as $mem_term ) {
				$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $mem_term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $mem_term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $mem_term->term_id ) );
			}
		}

		// Delete Taxonomies.
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $mem_taxonomy ), array( '%s' ) );
	}

	// Delete Plugin Pages.
	$mem_pages = array(
		'app_home_page',
		'contracts_page',
		'payments_page',
		'playlist_page',
		'profile_page',
		'quotes_page',
	);

	foreach ( $mem_pages as $mem_page ) {

		$rm_page = mem_get_option( $mem_page, false );

		if ( $rm_page ) {
			wp_delete_post( $rm_page, true );
		}
	}

	// Remove setting options.
	$all_options = array(
		'mem_api_data',
		'mem_availability_settings',
		'mem_cats',
		'mem_clientzone_settings',
		'mem_client_fields',
		'mem_completed_upgrades',
		'mem_db_update_to_13',
		'mem_db_version',
		'mem_debug_settings',
		'mem_email_settings',
		'mem_enquiry_terms_created',
		'mem_equipment',
		'mem_event_settings',
		'mem_frontend_text',
		'mem_packages',
		'mem_playlist_import',
		'mem_playlist_settings',
		'mem_plugin_pages',
		'mem_plugin_permissions',
		'mem_plugin_settings',
		'mem_schedules',
		'mem_settings',
		'mem_templates_settings',
		'mem_txn_terms_13',
		'mem_updated',
		'mem_update_me',
		'mem_version',
		'mem_version_upgraded_from',
	);

	foreach ( $all_options as $all_option ) {
		delete_option( $all_option );
	}

	$custom_tables = array(
		'mem_avail',
		'mem_availability',
		'mem_availabilitymeta',
		'mem_playlists',
		'mem_playlistmeta',
	);

	// Remove all database tables.
	foreach ( $custom_tables as $custom_table ) {
		$mem_table_name = $wpdb->prefix . $custom_table;
		$wpdb->query( "DROP TABLE IF EXISTS $mem_table_name" );
		delete_option( $mem_table_name . '_db_version' );
	}

	// Remove any transients and options we've left behind.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_mem\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_mem\_%'" );

	// Remove roles and capabilities.
	$all_caps = array(
		// MEM Admin.
		'manage_mem',

		// Clients.
		'mem_client_edit',
		'mem_client_edit_own',

		// Employees.
		'mem_employee_edit',

		// Packages.
		'mem_package_edit_own',
		'mem_package_edit',
		'publish_mem_packages',
		'edit_mem_packages',
		'edit_others_mem_packages',
		'delete_mem_packages',
		'delete_others_mem_packages',
		'read_private_mem_packages',

		// Comm posts.
		'mem_comms_send',
		'edit_mem_comms',
		'edit_others_mem_comms',
		'publish_mem_comms',
		'read_private_mem_comms',
		'edit_published_mem_comms',
		'delete_mem_comms',
		'delete_others_mem_comms',
		'delete_private_mem_comms',
		'delete_published_mem_comms',
		'edit_private_mem_comms',

		// Event posts.
		'mem_event_read',
		'mem_event_read_own',
		'mem_event_edit',
		'mem_event_edit_own',
		'publish_mem_events',
		'edit_mem_events',
		'edit_others_mem_events',
		'delete_mem_events',
		'delete_others_mem_events',
		'read_private_mem_events',

		// Quote posts.
		'mem_quote_view_own',
		'mem_quote_view',
		'edit_mem_quotes',
		'edit_others_mem_quotes',
		'publish_mem_quotes',
		'read_private_mem_quotes',
		'edit_published_mem_quotes',
		'edit_private_mem_quotes',
		'delete_mem_quotes',
		'delete_others_mem_quotes',
		'delete_private_mem_quotes',
		'delete_published_mem_quotes',

		// Reports.
		'view_event_reports',

		// Templates.
		'mem_template_edit',
		'edit_mem_templates',
		'edit_others_mem_templates',
		'publish_mem_templates',
		'read_private_mem_templates',
		'edit_published_mem_templates',
		'edit_private_mem_templates',
		'delete_mem_templates',
		'delete_others_mem_templates',
		'delete_private_mem_templates',
		'delete_published_mem_templates',

		// Transaction posts.
		'mem_txn_edit',
		'edit_mem_txns',
		'edit_others_mem_txns',
		'publish_mem_txns',
		'read_private_mem_txns',
		'edit_published_mem_txns',
		'edit_private_mem_txns',
		'delete_mem_txns',
		'delete_others_mem_txns',
		'delete_private_mem_txns',
		'delete_published_mem_txns',

		// Venue posts.
		'mem_venue_read',
		'mem_venue_edit',
		'edit_mem_venues',
		'edit_others_mem_venues',
		'publish_mem_venues',
		'read_private_mem_venues',
		'edit_published_mem_venues',
		'edit_private_mem_venues',
		'delete_mem_venues',
		'delete_others_mem_venues',
		'delete_private_mem_venues',
		'delete_published_mem_venues',
	);

	$roles = MEM()->roles->get_roles();

	foreach ( $roles as $role_id => $role_name ) {
		$mem_role = get_role( $role_id );

		if ( empty( $mem_role ) ) {
			continue;
		}

		foreach ( $all_caps as $cap ) {
			$mem_role->remove_cap( $cap );
		}

		if ( 'administrator' !== $role_id ) {
			remove_role( $role_id );
		}
	}

	// Remove users.
	$roles = array( 'client', 'inactive_client', 'dj', 'inactive_dj' );

	// Loop through roles array removing users.
	foreach ( $roles as $mem_role ) {
		$args = array(
			'role'         => $mem_role,
			'role__not_in' => 'Administrator',
			'orderby'      => 'display_name',
			'order'        => 'ASC',
		);

		$mem_users = get_users( $args );

		foreach ( $mem_users as $mem_user ) {
			wp_delete_user( $mem_user->ID );
		}
	}

	remove_role( 'inactive_dj' );
	remove_role( 'client' );
	remove_role( 'inactive_client' );
}
