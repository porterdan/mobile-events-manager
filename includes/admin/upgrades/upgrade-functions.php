<?php
/**
 * Upgrade Functions
 *
 * @package MEM
 * @subpackage Admin/Upgrades
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 *
 * Taken from Easy Digital Downloads.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Perform automatic database upgrades when necessary
 *
 * @since 1.4
 * @return void
 */
function mem_do_automatic_upgrades() {

	$did_upgrade  = false;
	$mem_version = preg_replace( '/[^0-9.].*/', '', get_option( 'mem_version' ) );

	// Version 1.3.8.5 was the last version to use the old update procedures which were applied automatically.
	if ( version_compare( $mem_version, '1.3.8.1', '<' ) ) {
		add_option( 'mem_update_me', MEM_VERSION_NUM );
	}

	if ( version_compare( $mem_version, '1.4', '<' ) ) {
		mem_v14_upgrades();
	}

	if ( version_compare( $mem_version, '1.4.3', '<' ) ) {
		mem_v143_upgrades();
	}

	if ( version_compare( $mem_version, '1.4.7', '<' ) ) {
		mem_v147_upgrades();
	}

	if ( version_compare( $mem_version, '1,4,7,7', '<' ) ) {
		mem_update_option( 'playlist_limit', '0' );
	}

	if ( version_compare( $mem_version, '1.5', '<' ) ) {
		mem_v15_upgrades();
	}

	if ( version_compare( $mem_version, '1.5.4', '<' ) ) {
		mem_v154_upgrades();
	}

	if ( version_compare( $mem_version, '1.5.6', '<' ) ) {
		mem_v156_upgrades();
	}

	if ( version_compare( $mem_version, '1.5.7', '<' ) ) {
		mem_v157_upgrades();
	}

	if ( version_compare( $mem_version, MEM_VERSION_NUM, '<' ) ) {
		// Let us know that an upgrade has happened
		$did_upgrade = true;
	}

	if ( $did_upgrade ) {
		// Send to what's new page
		/*
		if ( substr_count( MEM_VERSION_NUM, '.' ) < 2 ) {
			set_transient( '_mem_activation_redirect', true, 30 );
		}*/

		update_option( 'mem_version_upgraded_from', get_option( 'mem_version' ) );
		update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
	}

} // mem_do_automatic_upgrades
add_action( 'admin_init', 'mem_do_automatic_upgrades' );

/**
 * Display a notice if an upgrade is required.
 *
 * @since 1.4
 */
function mem_show_upgrade_notice() {

	if ( isset( $_GET['page'] ) && 'mem-upgrades' === $_GET['page'] ) {
		return;
	}

	$mem_version = get_option( 'mem_version' );

	$mem_version = preg_replace( '/[^0-9.].*/', '', $mem_version );

	// Check if there is an incomplete upgrade routine.
	$resume_upgrade = mem_maybe_resume_upgrade();

	if ( ! empty( $resume_upgrade ) ) {

		$resume_url = add_query_arg( $resume_upgrade, admin_url( 'index.php' ) );
		printf(
			'<div class="notice notice-error"><p>' . __( 'Mobile Events Manager (MEM) needs to complete an upgrade that was previously started. Click <a href="%s">here</a> to resume the upgrade.', 'mobile-events-manager' ) . '</p></div>',
			esc_url( $resume_url )
		);

	} else {

		if ( version_compare( $mem_version, '1.4', '<' ) || ! mem_has_upgrade_completed( 'upgrade_event_packages' ) ) {
			printf(
				'<div class="notice notice-error"><p>' . __( 'Mobile Events Manager (MEM) needs to perform an upgrade to %1$s Packages and Add-ons. Click <a href="%2$s">here</a> to start the upgrade.', 'mobile-events-manager' ) . '</p></div>',
				esc_html( mem_get_label_singular( true ) ),
				esc_url( admin_url( 'index.php?page=mem-upgrades&mem-upgrade=upgrade_event_packages&message=1&redirect=' . mem_get_current_page_url() ) )
			);
		}

		if ( version_compare( $mem_version, '1.4.7', '<' ) || ! mem_has_upgrade_completed( 'upgrade_event_tasks' ) ) {
			printf(
				'<div class="notice notice-error"><p>' . __( 'Mobile Events Manager (MEM) needs to perform an upgrade to the %1$s database. Click <a href="%2$s">here</a> to start the upgrade.', 'mobile-events-manager' ) . '</p></div>',
				mem_get_label_plural( true ),
				esc_url( admin_url( 'index.php?page=mem-upgrades&mem-upgrade=upgrade_event_tasks&message=1&redirect=' . mem_get_current_page_url() ) )
			);
		}

		if ( version_compare( $mem_version, '1.5', '<' ) || ! mem_has_upgrade_completed( 'upgrade_event_pricing_15' ) ) {
			printf(
				'<div class="notice notice-error"><p>' . __( 'Mobile Events Manager (MEM) needs to perform an upgrade to the %1$s database. Click <a href="%2$s">here</a> to start the upgrade.', 'mobile-events-manager' ) . '</p></div>',
				mem_get_label_plural( true ),
				esc_url( admin_url( 'index.php?page=mem-upgrades&mem-upgrade=upgrade_event_pricing_15&message=1&redirect=' . mem_get_current_page_url() ) )
			);
		}

		if ( version_compare( $mem_version, '1.5.6', '<' ) || ! mem_has_upgrade_completed( 'upgrade_availability_db_156' ) ) {
			printf(
				'<div class="notice notice-error"><p>' . __( 'Mobile Events Manager (MEM) needs to perform an upgrade to the availability database. Click <a href="%s">here</a> to start the upgrade.', 'mobile-events-manager' ) . '</p></div>',
				esc_url( admin_url( 'index.php?page=mem-upgrades&mem-upgrade=upgrade_availability_db_156&message=1&redirect=' . mem_get_current_page_url() ) )
			);
		}

		/*
		 * NOTICE:
		 *
		 * When adding new upgrade notices, please be sure to put the action into the upgrades array during install:
		 * /includes/install.php @ Appox Line 783
		 *
		 */

	}

} // mem_show_upgrade_notice
add_action( 'admin_notices', 'mem_show_upgrade_notice' );

/**
 * Triggers all upgrade functions.
 *
 * This function is usually triggered via AJAX.
 *
 * @since 1.4
 * @return void
 */
function mem_trigger_upgrades() {

	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	update_option( 'mem_version', MEM_VERSION_NUM );

	if ( DOING_AJAX ) {
		die( 'complete' ); // Let AJAX know that the upgrade is complete
	}

} // mem_trigger_upgrades
add_action( 'wp_ajax_mem_trigger_upgrades', 'mem_trigger_upgrades' );

/**
 * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle.
 *
 * @since 1.4
 * @return mixed When nothing to resume returns false, otherwise starts the upgrade where it left off.
 */
function mem_maybe_resume_upgrade() {

	$doing_upgrade = get_option( 'mem_doing_upgrade', false );

	if ( empty( $doing_upgrade ) ) {
		return false;
	}

	return $doing_upgrade;

} // mem_maybe_resume_upgrade

/**
 * Adds an upgrade action to the completed upgrades array.
 *
 * @since 1.4
 * @param str $upgrade_action The action to add to the copmleted upgrades array.
 * @return bool If the function was successfully added.
 */
function mem_set_upgrade_complete( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades   = mem_get_completed_upgrades();
	$completed_upgrades[] = $upgrade_action;

	// Remove any blanks, and only show uniques
	$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

	return update_option( 'mem_completed_upgrades', $completed_upgrades );
} // mem_set_upgrade_complete

/**
 * Migrates event packages and addons from the options table to posts.
 *
 * @since 1.4
 * @return void
 */
function mem_v14_upgrades() {
	global $wpdb;

	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	// Drop the deprecated Playlists table
	$results = $wpdb->get_results( "SHOW TABLES LIKE '" . $wpdb->prefix . 'mem_playlists' . "'" );
	if ( $results ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mem_playlists' );
	}

	$items             = array();
	$packages          = array();
	$existing_items    = get_option( 'mem_equipment', array() );
	$existing_cats     = get_option( 'mem_cats' );
	$existing_packages = get_option( 'mem_packages', array() );

	$convert_addons = get_option( 'mem_upgrade_v14_import_addons' );

	if ( ! $convert_addons ) {

		if ( $existing_items ) { // If addons exist we need to convert them

			foreach ( $existing_items as $slug => $existing_item ) {

				$employees = array( 'all' );

				if ( ! empty( $existing_item[8] ) ) {
					$employees = explode( ',', $existing_item[8] );
				}

				if ( $existing_cats && ! term_exists( $existing_cats[ $existing_item[5] ], 'addon-category' ) ) {
					wp_insert_term( $existing_cats[ $existing_item[5] ], 'addon-category', array( 'slug' => $existing_item[5] ) );

					$category = get_term_by( 'name', $existing_cats[ $existing_item[5] ], 'addon-category' );
				}

				$args = array(
					'post_type'     => 'mem-addon',
					'post_content'  => ! empty( $existing_item[4] ) ? stripslashes( $existing_item[4] ) : '',
					'post_title'    => $existing_item[0],
					'post_status'   => 'publish',
					'post_name'     => stripslashes( $slug ),
					'post_category' => isset( $category ) ? array( $category->term_id ) : '',
					'meta_input'    => array(
						'_addon_employees'        => $employees,
						'_addon_event_types'      => array( 'all' ),
						'_addon_restrict_date'    => false,
						'_addon_months'           => array(),
						'_addon_price'            => ! empty( $existing_item[7] ) ? mem_format_amount( $existing_item[7] ) : mem_format_amount( '0' ),
						'_addon_variable_pricing' => false,
						'_addon_variable_prices'  => false,
					),
				);

				$addon_id = wp_insert_post( $args );

				if ( $addon_id ) {
					if ( ! empty( $category ) ) {
						mem_set_addon_category( $addon_id, $category->term_id );
					}

					$items[ $slug ] = $addon_id; // Store each addon's slug and new post ID for use later
				}
			}
		}
	}

	// Log addon upgrade procedure as completed
	set_transient( 'mem_upgrade_v14_import_addons', $items, 30 * DAY_IN_SECONDS );

	$convert_packages = get_option( 'mem_upgrade_v14_import_packages' );

	if ( ! $convert_packages ) {

		foreach ( $existing_packages as $slug => $existing_package ) {

			$addons    = array();
			$equipment = array();
			$employees = array( 'all' );

			if ( ! empty( $existing_package['djs'] ) ) {
				$employees = explode( ',', $existing_package['djs'] );
			}

			if ( ! empty( $existing_package['equipment'] ) ) {
				$equipment = explode( ',', $existing_package['equipment'] );
			}

			if ( ! empty( $equipment ) ) {
				foreach ( $equipment as $item ) {
					if ( ! empty( $items[ $item ] ) ) {
						$addons[] = $items[ $item ];
					}
				}
			}

			$args = array(
				'post_type'    => 'mem-package',
				'post_content' => ! empty( $existing_package['desc'] ) ? stripslashes( $existing_package['desc'] ) : '',
				'post_title'   => ! empty( $existing_package['name'] ) ? stripslashes( $existing_package['name'] ) : '',
				'post_status'  => 'publish',
				'post_name'    => stripslashes( $slug ),
				'meta_input'   => array(
					'_package_employees'        => $employees,
					'_package_event_types'      => array( 'all' ),
					'_package_restrict_date'    => false,
					'_package_months'           => array(),
					'_package_items'            => array_unique( $addons ),
					'_package_price'            => ! empty( $existing_package['cost'] ) ? mem_format_amount( $existing_package['cost'] ) : mem_format_amount( '0' ),
					'_package_variable_pricing' => false,
					'_package_variable_prices'  => false,
				),
			);

			$package_id = wp_insert_post( $args );

			if ( $package_id ) {
				$packages[ $slug ] = $package_id; // Store each addon's slug and new post ID for use later
			}
		}
	}

	// Clear the permalinks
	flush_rewrite_rules( false );

	// Log package upgrade procedure as completed
	set_transient( 'mem_upgrade_v14_import_packages', $packages, 30 * DAY_IN_SECONDS );
	update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
} // mem_v14_upgrades

/**
 * Upgrade event packages from slugs to new post ID's.
 *
 * @since 1.4
 * @return void
 */
function mem_v14_upgrade_event_packages() {

	global $wpdb;

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	$items    = get_transient( 'mem_upgrade_v14_import_addons' );
	$packages = get_transient( 'mem_upgrade_v14_import_packages' );

	$number   = 50;
	$step     = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$offset   = 1 === $step ? 0 : ( $step - 1 ) * $number;
	$redirect = isset( $_GET['redirect'] ) ? sanitize_key( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=mem-event' );
	$message  = isset( $_GET['message'] ) ? 'upgrade-completed' : '';

	if ( $step < 2 ) {
		// Check if we have any events before moving on
		$sql        = "SELECT ID FROM $wpdb->posts WHERE post_type = 'mem-event' LIMIT 1";
		$has_events = $wpdb->get_col( $sql );

		if ( empty( $has_events ) || ( 'false' === $items && 'false' === $packages ) ) {
			// We had no events, addons, or packages, just complete
			update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
			mem_set_upgrade_complete( 'upgrade_event_packages' );
			delete_option( 'mem_doing_upgrade' );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(post_id) as total_events FROM $wpdb->postmeta WHERE meta_value != '' AND (meta_key = '_mem_event_package' OR meta_key = '_mem_event_addons')";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total = $results->total_events;
	}

	$event_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_value != ''
			AND (
				meta_key = %s
				OR
				meta_key = %s
			)
			ORDER BY post_id DESC LIMIT %d,%d;
		",
			'_mem_event_package',
			'_mem_event_addons',
			$offset,
			$number
		)
	);

	if ( $event_ids ) {
		foreach ( $event_ids as $event_id ) {
			$addons = array();

			$current_package = get_post_meta( $event_id, '_mem_event_package', true );
			$current_items   = get_post_meta( $event_id, '_mem_event_addons', true );

			if ( ! empty( $current_package ) ) {
				update_post_meta( $event_id, '_mem_event_package_pre_v14', $current_package );
				if ( array_key_exists( $current_package, $packages ) ) {
					update_post_meta( $event_id, '_mem_event_package', $packages[ $current_package ] );
				}
			}

			if ( ! empty( $current_items ) && is_array( $current_items ) ) {
				update_post_meta( $event_id, '_mem_event_addons_pre_v14', $current_items );
				$addons = array();

				foreach ( $current_items as $current_item ) {
					if ( array_key_exists( $current_item, $items ) ) {
						$addons[] = $items[ $current_item ];
					}
				}

				if ( ! empty( $addons ) ) {
					update_post_meta( $event_id, '_mem_event_addons', $addons );
				}
			}
		}

		// Events with packages/addons found so upgrade them
		$step++;
		$redirect = add_query_arg(
			array(
				'page'         => 'mem-upgrades',
				'mem-upgrade' => 'upgrade_event_packages',
				'step'         => $step,
				'number'       => $number,
				'total'        => $total,
				'redirect'     => $redirect,
				'message'      => $message,
			),
			admin_url( 'index.php' )
		);

		wp_safe_redirect( $redirect );
		exit;

	} else {
		// No more events found, finish up
		mem_set_upgrade_complete( 'upgrade_event_packages' );
		delete_option( 'mem_doing_upgrade' );

		$url = add_query_arg(
			array(
				'mem-message' => $message,
			),
			$redirect
		);

		wp_safe_redirect( $url );
		exit;
	}

} // mem_v14_upgrade_event_packages
add_action( 'mem-upgrade_event_packages', 'mem_v14_upgrade_event_packages' );

/**
 * Set all MEM Journal Entry comment_type columns to mem-journal.
 *
 * @since 1.4.3
 * @return void
 */
function mem_v143_upgrades() {
	global $wpdb;

	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	// Set comment type on journal entries
	$wpdb->update(
		$wpdb->comments,
		array( 'comment_type' => 'mem-journal' ),
		array( 'comment_type' => 'update-event' )
	);

	// Sanitize client field IDs
	$client_fields = get_option( 'mem_client_fields' );

	if ( $client_fields ) {
		foreach ( $client_fields as $field_id => $client_field ) {
			if ( ! empty( $client_field['default'] ) ) {
				continue;
			}

			$client_fields[ $field_id ]['id'] = sanitize_title_with_dashes( $client_field['label'], '', 'save' );
		}

		update_option( 'mem_client_fields', $client_fields );
	}

} // mem_v143_upgrades

/**
 * Update schedules.
 *
 * @since 1.4.7
 * @return void
 */
function mem_v147_upgrades() {
	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	// Update schedules
	$tasks       = get_option( 'mem_schedules' );
	$email_tasks = array(
		'request-deposit',
		'balance-reminder',
	);

	if ( $tasks ) {

		foreach ( $tasks as $slug => $task ) {
			$active  = false;
			$lastrun = false;
			$default = false;

			if ( ! empty( $task['active'] ) && 'Y' == $task['active'] ) {
				$active = true;
			}

			if ( ! empty( $task['lastran'] ) && 'Never' != $task['lastran'] ) {
				$lastrun = $task['lastran'];
			}

			if ( ! empty( $task['default'] ) && 'Y' == $task['default'] ) {
				$default = true;
			} else {
				$default = false;
			}

			$tasks[ $slug ]['active']      = $active;
			$tasks[ $slug ]['lastran']     = $lastrun;
			$tasks[ $slug ]['default']     = $default;
			$tasks[ $slug ]['last_result'] = false;

			unset( $tasks[ $slug ]['options']['email_client'] );
			unset( $tasks[ $slug ]['options']['notify_admin'] );
			unset( $tasks[ $slug ]['options']['notify_dj'] );

			if ( ! in_array( $slug, $email_tasks ) ) {
				unset( $tasks[ $slug ]['options']['email_template'] );
				unset( $tasks[ $slug ]['options']['email_subject'] );
				unset( $tasks[ $slug ]['options']['email_from'] );
			} else {
				if ( isset( $tasks[ $slug ]['options']['email_from'] ) && 'dj' == $tasks[ $slug ]['options']['email_from'] ) {
					$tasks[ $slug ]['options']['email_from'] = 'employee';
				} else {
					$tasks[ $slug ]['options']['email_from'] = 'admin';
				}
			}
		}

		update_option( 'mem_schedules', $tasks );

	}

	wp_clear_scheduled_hook( 'mem_hourly_schedule' );

	delete_option( 'mem_uninst' );

} // mem_v147_upgrades

/**
 * Ensure all events have the _mem_event_tasks meta key.
 *
 * @since 1.4.7
 * @return void
 */
function mem_v147_upgrade_event_tasks() {

	global $wpdb;

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	$number   = 20;
	$step     = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$offset   = 1 === $step ? 0 : ( $step - 1 ) * $number;
	$redirect = isset( $_GET['redirect'] ) ? sanitize_key( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=mem-event' );
	$message  = isset( $_GET['message'] ) ? 'upgrade-completed' : '';

	if ( $step < 2 ) {
		// Check if we have any events before moving on
		$sql        = "SELECT ID FROM $wpdb->posts WHERE post_type = 'mem-event' LIMIT 1";
		$has_events = $wpdb->get_col( $sql );

		if ( empty( $has_events ) ) {
			// We had no events, just complete
			update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
			mem_set_upgrade_complete( 'upgrade_event_tasks' );
			delete_option( 'mem_doing_upgrade' );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(ID) as total_events FROM $wpdb->posts WHERE post_type = 'mem-event'";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total = $results->total_events;
	}

	$event_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'mem-event'
			ORDER BY ID DESC LIMIT %d,%d;
		",
			$offset,
			$number
		)
	);

	if ( $event_ids ) {
		foreach ( $event_ids as $event_id ) {

			$tasks = array();
			$event = new MEM_Event( $event_id );

			if ( 'mem-completed' == $event->post_status ) {
				$tasks['complete-events'] = current_time( 'timestamp' );
			}

			if ( 'mem-failed' == $event->post_status ) {
				$tasks['fail-enquiry'] = current_time( 'timestamp' );
			}

			if ( 'Paid' == $event->mem_get_deposit_status() ) {
				$tasks['request-deposit'] = current_time( 'timestamp' );
			}

			if ( 'Paid' == $event->get_balance_status() ) {
				$tasks['balance-reminder'] = current_time( 'timestamp' );
			}

			add_post_meta( $event_id, '_mem_event_tasks', $tasks, true );
		}

		// Events found so upgrade them
		$step++;
		$redirect = add_query_arg(
			array(
				'page'         => 'mem-upgrades',
				'mem-upgrade' => 'upgrade_event_tasks',
				'step'         => $step,
				'number'       => $number,
				'total'        => $total,
				'redirect'     => $redirect,
				'message'      => $message,
			),
			admin_url( 'index.php' )
		);

		wp_safe_redirect( $redirect );
		exit;

	} else {
		// No more events found, finish up
		mem_set_upgrade_complete( 'upgrade_event_tasks' );
		delete_option( 'mem_doing_upgrade' );

		$url = add_query_arg(
			array(
				'mem-message' => 'upgrade-completed',
			),
			$redirect
		);

		wp_safe_redirect( $url );
		exit;
	}

} // mem_v147_upgrade_event_tasks
add_action( 'mem-upgrade_event_tasks', 'mem_v147_upgrade_event_tasks' );

/**
 * 1.5 Upgrade.
 *
 * @since 1.5
 * @return void
 */
function mem_v15_upgrades() {
	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	// Add default values for new setting options
	$options = array(
		'setup_time'             => '0',
		'deposit_before_confirm' => false,
	);

	foreach ( $options as $key => $value ) {
		mem_update_option( $key, $value );
	}

	// Add new automated tasks
	$tasks = get_option( 'mem_schedules' );

	$tasks['playlist-notification'] = array(
		'slug'        => 'playlist-notification',
		'name'        => __( 'Client Playlist Notifications', 'mobile-events-manager' ),
		'active'      => false,
		'desc'        => __( 'Sends notifications to clients if a guest has added an entry to their playlist.', 'mobile-events-manager' ),
		'frequency'   => 'Daily',
		'nextrun'     => 'N/A',
		'lastran'     => 'Never',
		'options'     => array(
			'run_when'       => 'after_event',
			'age'            => '1 HOUR',
			'email_template' => '0',
			'email_subject'  => sprintf( esc_html__( 'Your %s playlist has been updated', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
			'email_from'     => 'admin',
		),
		'totalruns'   => '0',
		'default'     => true,
		'last_result' => false,
	);

	$tasks['playlist-employee-notify'] = array(
		'slug'        => 'playlist-employee-notify',
		'name'        => __( 'Employee Playlist Notification', 'mobile-events-manager' ),
		'active'      => false,
		'desc'        => sprintf( esc_html__( 'Sends notifications to an employee if an %s playlist has entries.', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'frequency'   => 'Daily',
		'nextrun'     => 'N/A',
		'lastran'     => 'Never',
		'options'     => array(
			'run_when'      => 'before_event',
			'age'           => '3 DAY',
			'email_subject' => sprintf( esc_html__( '%s playlist notification', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
			'email_from'    => 'admin',
		),
		'totalruns'   => '0',
		'default'     => true,
		'last_result' => false,
	);

	update_option( 'mem_schedules', $tasks );

} // mem_v15_upgrades

/**
 * Add the pricing breakdown meta keys to all existing events
 *
 * @since 1.5
 * @return void
 */
function mem_v15_upgrade_event_pricing() {

	global $wpdb;

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	$number   = 20;
	$step     = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$offset   = 1 === $step ? 0 : ( $step - 1 ) * $number;
	$redirect = isset( $_GET['redirect'] ) ? sanitize_key( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=mem-event' );
	$message  = isset( $_GET['message'] ) ? 'upgrade-completed' : '';

	if ( $step < 2 ) {
		// Check if we have any events before moving on
		$sql        = "SELECT ID FROM $wpdb->posts WHERE post_type = 'mem-event' AND post_status != 'draft' AND post_status != 'auto-draft' LIMIT 1";
		$has_events = $wpdb->get_col( $sql );

		if ( empty( $has_events ) ) {
			// We had no events, just complete
			update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
			mem_set_upgrade_complete( 'upgrade_event_pricing_15' );
			delete_option( 'mem_doing_upgrade' );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(ID) as total_events FROM $wpdb->posts WHERE post_type = 'mem-event' AND post_status != 'draft' AND post_status != 'auto-draft'";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total = $results->total_events;
	}

	$event_ids = $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'mem-event'
 AND post_status != 'draft'
 AND post_status != 'auto-draft'
			ORDER BY ID DESC LIMIT %d,%d;
		",
			$offset,
			$number
		)
	);

	if ( $event_ids ) {
		foreach ( $event_ids as $event_id ) {

			$mem_event = new MEM_Event( $event_id );

			if ( ! $mem_event ) {
				continue;
			}

			// Package price
			$package_price = $mem_event->get_package_price();
			update_post_meta( $event_id, '_mem_event_package_cost', mem_sanitize_amount( $package_price ) );

			// Addons cost
			$addons_price = $mem_event->get_addons_price();
			update_post_meta( $event_id, '_mem_event_addons_cost', mem_sanitize_amount( $addons_price ) );

			// Travel cost
			$travel_cost = mem_get_event_travel_data( $event_id );
			if ( empty( $travel_cost ) ) {
				$travel_cost = 0;
			}

			update_post_meta( $event_id, '_mem_event_travel_cost', mem_sanitize_amount( $travel_cost ) );

			// Additional costs
			update_post_meta( $event_id, '_mem_event_additional_cost', mem_sanitize_amount( 0 ) );

			// Discount
			update_post_meta( $event_id, '_mem_event_discount', mem_sanitize_amount( 0 ) );

			unset( $mem_event );
		}

		// Events found so upgrade them
		$step++;
		$redirect = add_query_arg(
			array(
				'page'         => 'mem-upgrades',
				'mem-upgrade' => 'upgrade_event_pricing_15',
				'step'         => $step,
				'number'       => $number,
				'total'        => $total,
				'redirect'     => $redirect,
				'message'      => $message,
			),
			admin_url( 'index.php' )
		);

		wp_safe_redirect( $redirect );
		exit;

	} else {
		// No more events found, finish up
		mem_set_upgrade_complete( 'upgrade_event_pricing_15' );
		delete_option( 'mem_doing_upgrade' );

		$url = add_query_arg(
			array(
				'mem-message' => 'upgrade-completed',
			),
			$redirect
		);

		wp_safe_redirect( $url );
		exit;
	}
} // mem_v15_upgrade_event_pricing
add_action( 'mem-upgrade_event_pricing_15', 'mem_v15_upgrade_event_pricing' );

/**
 * 1.5.4 Upgrade.
 *
 * @since 1.5.4
 * @return void
 */
function mem_v154_upgrades() {
	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	// Add default values for new setting options
	$options = array(
		'show_agree_to_privacy_policy' => false,
		'agree_privacy_label'          => '',
		'agree_privacy_descripton'     => '',
		'show_agree_policy_type'       => 'thickbox',
		'show_agree_to_terms'          => false,
		'agree_terms_label'            => __( 'I have read and agree to the terms and conditions', 'mobile-events-manager' ),
		'agree_terms_description'      => '',
		'agree_terms_heading'          => sprintf( esc_html__( 'Terms and Conditions for %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) ),
		'agree_terms_text'             => '',
	);

	foreach ( $options as $key => $value ) {
		mem_update_option( $key, $value );
	}

} // mem_v154_upgrades

/**
 * 1.5.6 Upgrade.
 *
 * @since 1.5.4
 * @return void
 */
function mem_v156_upgrades() {
	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	global $wpdb;

	// Create the new database tables
	$availability_db = MEM()->availability_db;
	if ( ! $availability_db->table_exists( $availability_db->table_name ) ) {
		@$availability_db->create_table();
	}

	$availability_meta_db = MEM()->availability_meta_db;
	if ( ! $availability_meta_db->table_exists( $availability_meta_db->table_name ) ) {
		@$availability_meta_db->create_table();
	}

	$playlist_db = MEM()->playlist_db;
	if ( ! $playlist_db->table_exists( $playlist_db->table_name ) ) {
		@$playlist_db->create_table();
	}

	$playlist_meta_db = MEM()->playlist_meta_db;
	if ( ! $playlist_meta_db->table_exists( $playlist_meta_db->table_name ) ) {
		@$playlist_meta_db->create_table();
	}

	$new_settings = array(
		'absence_background_color' => '#f7f7f7',
		'absence_border_color'     => '#cccccc',
		'absence_text_color'       => '#555555',
		'event_background_color'   => '#2ea2cc',
		'event_border_color'       => '#0074a2',
		'event_text_color'         => '#ffffff',
	);

	foreach ( $new_settings as $key => $value ) {
		mem_update_option( $key, $value );
	}
} // mem_v156_upgrades

/**
 * Migrate availability data to new database table.
 *
 * @since 1.0
 * @return void
 */
function mem_v156_upgrade_availability_db() {

	global $wpdb;

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	$number    = 10;
	$step      = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$offset    = 1 === $step ? 0 : ( $step - 1 ) * $number;
	$redirect  = isset( $_GET['redirect'] ) ? sanitize_key( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'edit.php?post_type=mem-event' );
	$message   = isset( $_GET['message'] ) ? 'upgrade-completed' : '';
	$old_table = $wpdb->prefix . 'mem_avail';

	if ( $step < 2 ) {
		// Check if we have any entries before moving on
		$sql         = "SELECT id FROM $old_table LIMIT 1";
		$has_entries = $wpdb->get_col( $sql );

		if ( empty( $has_entries ) ) {
			// We had no entries, just complete
			update_option( 'mem_version', preg_replace( '/[^0-9.].*/', '', MEM_VERSION_NUM ) );
			mem_set_upgrade_complete( 'upgrade_availability_db_156' );
			delete_option( 'mem_doing_upgrade' );
			delete_option( 'mem_db_version' );
			wp_safe_redirect( $redirect );
			exit;
		} else {
			$all_entries = $wpdb->get_results( "SELECT * FROM $old_table" );
			set_transient( 'mem_156_availability_entries', $all_entries, MONTH_IN_SECONDS );
		}
	}

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(*) as total_entries FROM $old_table GROUP BY entry_id ORDER BY id ASC LIMIT 1";
		$results   = $wpdb->get_row( $total_sql, 0 );

		$total = $results->total_entries;
	}

	$entries = $wpdb->get_results(
		$wpdb->prepare(
			"
			SELECT *
			FROM $old_table
 GROUP BY entry_id
			ORDER BY id ASC LIMIT %d,%d;
		",
			$offset,
			$number
		)
	);

	if ( $entries ) {
		foreach ( $entries as $entry ) {
			$start = strtotime( $entry->date_from . ' 00:00:00' );
			$end   = strtotime( '+1 day', strtotime( $entry->date_to . ' 00:00:00' ) );

			$data                = array();
			$data['event_id']    = 0;
			$data['employee_id'] = $entry->user_id;
			$data['all_day']     = 1;
			$data['start']       = gmdate( 'Y-m-d H:i:s', $start );
			$data['end']         = gmdate( 'Y-m-d H:i:s', $end );
			$data['notes']       = $entry->notes;

			MEM()->availability_db->add( $data );

			/*
			$wpdb->query( $wpdb->prepare(
				"
				DELETE FROM $old_table
				WHERE entry_id = '%s'
				", $entry->entry_id
			) );*/
		}

		// Entries found so upgrade them
		$step++;
		$redirect = add_query_arg(
			array(
				'page'         => 'mem-upgrades',
				'mem-upgrade' => 'upgrade_availability_db_156',
				'step'         => $step,
				'number'       => $number,
				'total'        => $total,
				'redirect'     => $redirect,
				'message'      => $message,
			),
			admin_url( 'index.php' )
		);

		wp_safe_redirect( $redirect );
		exit;

	} else {
		// No more events found, finish up
		mem_set_upgrade_complete( 'upgrade_availability_db_156' );
		delete_option( 'mem_availability_hashes' );
		delete_option( 'mem_doing_upgrade' );
		delete_option( 'mem_db_version' );

		$url = add_query_arg(
			array(
				'mem-message' => 'upgrade-completed',
			),
			$redirect
		);

		wp_safe_redirect( $url );
		exit;
	}
} // mem_v156_upgrade_event_pricing
add_action( 'mem-upgrade_availability_db_156', 'mem_v156_upgrade_availability_db' );

/**
 * 1.5.7 Upgrade.
 *
 * @since 1.5.7
 * @return void
 */
function mem_v157_upgrades() {
	if ( ! mem_employee_can( 'manage_mem' ) ) {
		wp_die( __( 'You do not have permission to do perform MEM upgrades', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! mem_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( 0 );
	}

	$absence_tip = sprintf( esc_html__( 'Absence: %s', 'mobile-events-manager' ), '{employee_name}' );

	$absence_content  = sprintf( esc_html__( 'From: %s', 'mobile-events-manager' ), '{start}' ) . PHP_EOL;
	$absence_content .= sprintf( esc_html__( 'To: %s', 'mobile-events-manager' ), '{end}' ) . PHP_EOL;
	$absence_content .= '{notes}';

	$event_title = '{event_type} ({event_status})';

	$event_tip_title = esc_html( mem_get_label_singular() ) . ' {contract_id} - {event_type}';

	$event_content  = sprintf( esc_html__( 'Status: %s', 'mobile-events-manager' ), '{event_status}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Date: %s', 'mobile-events-manager' ), '{event_date}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Start: %s', 'mobile-events-manager' ), '{start_time}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Finish: %s', 'mobile-events-manager' ), '{end_time}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Setup: %s', 'mobile-events-manager' ), '{dj_setup_time}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Cost: %s', 'mobile-events-manager' ), '{total_cost}' ) . PHP_EOL;
	$event_content .= sprintf( esc_html__( 'Employees: %s', 'mobile-events-manager' ), '{event_employees}' ) . PHP_EOL;

	$new_settings = array(
		'remove_absences_on_delete'    => '1',
		'calendar_absence_title'       => '{employee_name}',
		'calendar_absence_tip_title'   => $absence_tip,
		'calendar_absence_tip_content' => $absence_content,
		'calendar_event_title'         => $event_title,
		'calendar_event_tip_title'     => $event_tip_title,
		'calendar_event_tip_content'   => $event_content,
	);

	foreach ( $new_settings as $key => $value ) {
		mem_update_option( $key, $value );
	}
} // mem_v157_upgrades
