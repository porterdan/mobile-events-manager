<?php
/**
 * Uninstallation procedures for TMEM.
 * What happens here is determined by the plugin uninstallation settings.
 *
 * @since 0.8
 * @package TMEM
 */

// Do not run unless the uninstall procedure was called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Call the TMEM main class file.
require_once 'mobile-events-manager.php';

global $wpdb;

ignore_user_abort( true );

if ( ! tmem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
	@set_time_limit( 0 );
}

remove_action( 'save_post_tmem-event', 'tmem_save_event_post', 10, 3 );
remove_action( 'save_post_tmem-package', 'tmem_save_package_post', 10, 2 );
remove_action( 'save_post_tmem-addon', 'tmem_save_addon_post', 10, 2 );
remove_action( 'save_post_tmem-transaction', 'tmem_save_txn_post', 10, 3 );
remove_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );
remove_action( 'tmem_delete_package', 'tmem_remove_package_from_events' );
remove_action( 'tmem_delete_addon', 'tmem_remove_addons_from_packages', 10 );
remove_action( 'tmem_delete_addon', 'tmem_remove_addons_from_events', 15 );
remove_action( 'before_delete_post', 'tmem_deleting_package' );
remove_action( 'wp_trash_post', 'tmem_deleting_package' );
remove_action( 'before_delete_post', 'tmem_deleting_addon' );
remove_action( 'wp_trash_post', 'tmem_deleting_addon' );

if ( tmem_get_option( 'remove_on_uninstall' ) ) {

	// Delete the Custom Post Types.
	$tmem_taxonomies = array(
		'package-category',
		'addon-category',
		'event-types',
		'enquiry-source',
		'playlist-category',
		'transaction-types',
		'venue-details',
	);

	$tmem_post_types = array(
		'tmem-event',
		'tmem-package',
		'tmem-addon',
		'tmem_communication',
		'contract',
		'tmem-signed-contract',
		'tmem-custom-field',
		'email_template',
		'tmem-playlist',
		'tmem-quotes',
		'tmem-transaction',
		'tmem-venue',
	);

	foreach ( $tmem_post_types as $tmem_post_type ) {

		$tmem_taxonomies = array_merge( $tmem_taxonomies, get_object_taxonomies( $tmem_post_type ) );
		$items           = get_posts(
			array(
				'post_type'   => $tmem_post_type,
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
	foreach ( array_unique( array_filter( $tmem_taxonomies ) ) as $tmem_taxonomy ) {

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
				$tmem_taxonomy
			)
		);

		// Delete Terms.
		if ( $terms ) {
			foreach ( $terms as $tmem_term ) {
				$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $tmem_term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $tmem_term->term_taxonomy_id ) );
				$wpdb->delete( $wpdb->terms, array( 'term_id' => $tmem_term->term_id ) );
			}
		}

		// Delete Taxonomies.
		$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $tmem_taxonomy ), array( '%s' ) );
	}

	// Delete Plugin Pages.
	$tmem_pages = array(
		'app_home_page',
		'contracts_page',
		'payments_page',
		'playlist_page',
		'profile_page',
		'quotes_page',
	);

	foreach ( $tmem_pages as $tmem_page ) {

		$rm_page = tmem_get_option( $tmem_page, false );

		if ( $rm_page ) {
			wp_delete_post( $rm_page, true );
		}
	}

	// Remove setting options.
	$all_options = array(
		'tmem_api_data',
		'tmem_availability_settings',
		'tmem_cats',
		'tmem_clientzone_settings',
		'tmem_client_fields',
		'tmem_completed_upgrades',
		'tmem_db_update_to_13',
		'tmem_db_version',
		'tmem_debug_settings',
		'tmem_email_settings',
		'tmem_enquiry_terms_created',
		'tmem_equipment',
		'tmem_event_settings',
		'tmem_frontend_text',
		'tmem_packages',
		'tmem_playlist_import',
		'tmem_playlist_settings',
		'tmem_plugin_pages',
		'tmem_plugin_permissions',
		'tmem_plugin_settings',
		'tmem_schedules',
		'tmem_settings',
		'tmem_templates_settings',
		'tmem_txn_terms_13',
		'tmem_updated',
		'tmem_update_me',
		'tmem_version',
		'tmem_version_upgraded_from',
	);

	foreach ( $all_options as $all_option ) {
		delete_option( $all_option );
	}

	$custom_tables = array(
		'tmem_avail',
		'tmem_availability',
		'tmem_availabilitymeta',
		'tmem_playlists',
		'tmem_playlistmeta',
	);

	// Remove all database tables.
	foreach ( $custom_tables as $custom_table ) {
		$tmem_table_name = $wpdb->prefix . $custom_table;
		$wpdb->query( "DROP TABLE IF EXISTS $tmem_table_name" );
		delete_option( $tmem_table_name . '_db_version' );
	}

	// Remove any transients and options we've left behind.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_tmem\_%'" );
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_tmem\_%'" );

	// Remove roles and capabilities.
	$all_caps = array(
		// TMEM Admin.
		'manage_tmem',

		// Clients.
		'tmem_client_edit',
		'tmem_client_edit_own',

		// Employees.
		'tmem_employee_edit',

		// Packages.
		'tmem_package_edit_own',
		'tmem_package_edit',
		'publish_tmem_packages',
		'edit_tmem_packages',
		'edit_others_tmem_packages',
		'delete_tmem_packages',
		'delete_others_tmem_packages',
		'read_private_tmem_packages',

		// Comm posts.
		'tmem_comms_send',
		'edit_tmem_comms',
		'edit_others_tmem_comms',
		'publish_tmem_comms',
		'read_private_tmem_comms',
		'edit_published_tmem_comms',
		'delete_tmem_comms',
		'delete_others_tmem_comms',
		'delete_private_tmem_comms',
		'delete_published_tmem_comms',
		'edit_private_tmem_comms',

		// Event posts.
		'tmem_event_read',
		'tmem_event_read_own',
		'tmem_event_edit',
		'tmem_event_edit_own',
		'publish_tmem_events',
		'edit_tmem_events',
		'edit_others_tmem_events',
		'delete_tmem_events',
		'delete_others_tmem_events',
		'read_private_tmem_events',

		// Quote posts.
		'tmem_quote_view_own',
		'tmem_quote_view',
		'edit_tmem_quotes',
		'edit_others_tmem_quotes',
		'publish_tmem_quotes',
		'read_private_tmem_quotes',
		'edit_published_tmem_quotes',
		'edit_private_tmem_quotes',
		'delete_tmem_quotes',
		'delete_others_tmem_quotes',
		'delete_private_tmem_quotes',
		'delete_published_tmem_quotes',

		// Reports.
		'view_event_reports',

		// Templates.
		'tmem_template_edit',
		'edit_tmem_templates',
		'edit_others_tmem_templates',
		'publish_tmem_templates',
		'read_private_tmem_templates',
		'edit_published_tmem_templates',
		'edit_private_tmem_templates',
		'delete_tmem_templates',
		'delete_others_tmem_templates',
		'delete_private_tmem_templates',
		'delete_published_tmem_templates',

		// Transaction posts.
		'tmem_txn_edit',
		'edit_tmem_txns',
		'edit_others_tmem_txns',
		'publish_tmem_txns',
		'read_private_tmem_txns',
		'edit_published_tmem_txns',
		'edit_private_tmem_txns',
		'delete_tmem_txns',
		'delete_others_tmem_txns',
		'delete_private_tmem_txns',
		'delete_published_tmem_txns',

		// Venue posts.
		'tmem_venue_read',
		'tmem_venue_edit',
		'edit_tmem_venues',
		'edit_others_tmem_venues',
		'publish_tmem_venues',
		'read_private_tmem_venues',
		'edit_published_tmem_venues',
		'edit_private_tmem_venues',
		'delete_tmem_venues',
		'delete_others_tmem_venues',
		'delete_private_tmem_venues',
		'delete_published_tmem_venues',
	);

	$roles = TMEM()->roles->get_roles();

	foreach ( $roles as $role_id => $role_name ) {
		$tmem_role = get_role( $role_id );

		if ( empty( $tmem_role ) ) {
			continue;
		}

		foreach ( $all_caps as $cap ) {
			$tmem_role->remove_cap( $cap );
		}

		if ( 'administrator' !== $role_id ) {
			remove_role( $role_id );
		}
	}

	// Remove users.
	$roles = array( 'client', 'inactive_client', 'dj', 'inactive_dj' );

	// Loop through roles array removing users.
	foreach ( $roles as $tmem_role ) {
		$args = array(
			'role'         => $tmem_role,
			'role__not_in' => 'Administrator',
			'orderby'      => 'display_name',
			'order'        => 'ASC',
		);

		$tmem_users = get_users( $args );

		foreach ( $tmem_users as $tmem_user ) {
			wp_delete_user( $tmem_user->ID );
		}
	}

	remove_role( 'inactive_dj' );
	remove_role( 'client' );
	remove_role( 'inactive_client' );
}
