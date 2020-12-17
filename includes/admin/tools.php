<?php
/**
 * Tools
 *
 * Functions used for displaying TMEM tools menu page.
 *
 * @package TMEM
 * @subpackage Admin/Tools
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools
 *
 * Display the tools page.
 *
 * @since 1.4
 * @return void
 */
function tmem_tools_page() {

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'api_keys';

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'TMEM Event Management Tools', 'mobile-events-manager' ); ?></h1>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( tmem_get_tools_page_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg(
					array(
						'tab' => $tab_id,
					)
				);

				$tab_url = remove_query_arg(
					array(
						'tmem-message',
					),
					$tab_url
				);

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';
			}
			?>
		</h2>
		<div class="metabox-holder">
			<?php do_action( 'tmem_tools_tab_' . $active_tab ); ?>
		</div>
	</div>
	<?php

} // tmem_tools_page

/**
 * Define the tabs for the tools page.
 *
 * @since 1.4
 * @return array
 */
function tmem_get_tools_page_tabs() {

	$tabs = array(
		'api_keys'      => __( 'API Keys', 'mobile-events-manager' ),
		'system_info'   => __( 'System Info', 'mobile-events-manager' ),
		'import_export' => __( 'Import/Export', 'mobile-events-manager' ),
	);

	return apply_filters( 'tmem_tools_page_tabs', $tabs );

} // tmem_get_tools_page_tabs

/**
 * Display the users API Keys
 *
 * @since 1.4
 * @return void
 */
function tmem_tools_api_keys_display() {

	if ( ! tmem_employee_can( 'manage_tmem' ) ) {
		return;
	}

	do_action( 'tmem_tools_api_keys_before' );

	require_once TMEM_PLUGIN_DIR . '/includes/admin/class-tmem-api-keys-table.php';

	$api_keys_table = new TMEM_API_Keys_Table();
	$api_keys_table->prepare_items();
	$api_keys_table->display();

	do_action( 'tmem_tools_api_keys_after' );

} // tmem_tools_api_keys_display
add_action( 'tmem_tools_tab_api_keys', 'tmem_tools_api_keys_display' );

/**
 * Display the System Info
 *
 * @since 1.4
 * @return void
 */
function tmem_tools_system_info_display() {

	if ( ! tmem_employee_can( 'manage_tmem' ) ) {
		return;
	}

	?>

	<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=tmem-event&page=tmem-tools&tab=system_info' ) ); ?>" method="post" dir="ltr">
		<textarea readonly onclick="this.focus(); this.select()" id="system-info-textarea" name="tmem-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo tmem_tools_sysinfo_get(); ?></textarea>
		<p class="submit">
			<input type="hidden" name="tmem-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'tmem-download-sysinfo', false ); ?>
		</p>
	</form>

	<?php

} // tmem_tools_api_keys_display
add_action( 'tmem_tools_tab_system_info', 'tmem_tools_system_info_display' );

/**
 * Get system info
 *
 * @since 1.4
 * @global obj $wpdb Used to query the database using the WordPress Database API
 * @return str $return A string containing the info to output
 */
function tmem_tools_sysinfo_get() {

	global $wpdb;

	// Get theme info
	$theme_data = wp_get_theme();
	$theme      = $theme_data->Name . ' ' . $theme_data->Version;

	$return = '### Begin System Info ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL: ' . site_url() . "\n";
	$return .= 'Home URL: ' . home_url() . "\n";
	$return .= 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_site_info', $return );

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version: ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language: ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
	$return .= 'Permalink Structure: ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme: ' . $theme . "\n";
	$return .= 'Show On Front: ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if ( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front: ' . ( 0 !== $front_page_id ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts: ' . ( 0 !== $blog_page_id ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH: ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'  => false,
		'timeout'    => 60,
		'user-agent' => 'TMEM/' . TMEM_VERSION_NUM,
		'body'       => $request,
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$WP_REMOTE_POST = 'wp_remote_post() works';
	} else {
		$WP_REMOTE_POST = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post: ' . $WP_REMOTE_POST . "\n";
	$return .= 'Table Prefix: ' . 'Length: ' . strlen( $wpdb->prefix ) . ' Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	$return .= 'WP_DEBUG: ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit: ' . WP_TMEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati: ' . implode( ', ', get_post_stati() ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_wordpress_config', $return );

	// TMEM configuration
	$employer = tmem_is_employer();
	$packages = tmem_packages_enabled();
	$debug    = TMEM_DEBUG;

	$return .= "\n" . '-- TMEM Configuration' . "\n\n";
	$return .= 'Version: ' . TMEM_VERSION_NUM . "\n";
	$return .= 'Upgraded From: ' . get_option( 'tmem_version_upgraded_from', 'None' ) . "\n";
	$return .= 'Debugging Status: ' . ( ! empty( $debug ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Multiple Employees: ' . ( ! empty( $employer ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Packages Enabled: ' . ( ! empty( $packages ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Currency Code: ' . tmem_get_currency() . "\n";
	$return .= 'Currency Position: ' . tmem_get_option( 'currency_format', 'before' ) . "\n";
	$return .= 'Decimal Separator: ' . tmem_get_option( 'decimal', '.' ) . "\n";
	$return .= 'Thousands Separator: ' . tmem_get_option( 'thousands_separator', ',' ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_tmem_config', $return );

	// TMEM pages
	$clientzone_page = tmem_get_option( 'app_home_page', '' );
	$contact_page    = tmem_get_option( 'contact_page', '' );
	$contracts_page  = tmem_get_option( 'contracts_page', '' );
	$payments_page   = tmem_get_option( 'payments_page', '' );
	$playlist_page   = tmem_get_option( 'playlist_page', '' );
	$profile_page    = tmem_get_option( 'profile_page', '' );
	$quotes_page     = tmem_get_option( 'quotes_page', '' );

	$return .= "\n" . '-- TMEM Page Configuration' . "\n\n";
	$return .= 'Client Zone Page: ' . ( ! empty( $clientzone_page ) ? get_permalink( $clientzone_page ) . "\n" : "Unset\n" );
	$return .= 'Contact Page: ' . ( ! empty( $contact_page ) ? get_permalink( $contact_page ) . "\n" : "Unset\n" );
	$return .= 'Contracts Page: ' . ( ! empty( $contracts_page ) ? get_permalink( $contracts_page ) . "\n" : "Unset\n" );
	$return .= 'Payments Page: ' . ( ! empty( $payments_page ) ? get_permalink( $payments_page ) . "\n" : "Unset\n" );
	$return .= 'Playlist Page: ' . ( ! empty( $playlist_page ) ? get_permalink( $playlist_page ) . "\n" : "Unset\n" );
	$return .= 'Profile Page: ' . ( ! empty( $profile_page ) ? get_permalink( $profile_page ) . "\n" : "Unset\n" );
	$return .= 'Quotes Page: ' . ( ! empty( $quotes_page ) ? get_permalink( $quotes_page ) . "\n" : "Unset\n" );

	$return = apply_filters( 'tmem_sysinfo_after_tmem_pages', $return );

	// TMEM page templates
	$clientzone_templates = '';
	$page_templates       = tmem_get_template_files();

	foreach ( $page_templates as $template_area => $page_template ) {

		foreach ( $page_template as $single_template ) {
			$file_names = explode( '-', $single_template, 2 );

			$slug = $file_names[0];
			$name = null;

			if ( false !== strpos( $file_names[0], '.php' ) ) {
				$slug = substr( $file_names[0], 0, -4 );
			}

			if ( ! empty( $file_names[1] ) && false !== strpos( $file_names[1], '.php' ) ) {
				$name = substr( $file_names[1], 0, -4 );
			}

			$clientzone_templates .= ucfirst( $template_area ) . ': ' . tmem_get_template_part( $slug, $name, false ) . "\n";
		}
	}

	$return .= "\n" . '-- TMEM Template Files' . "\n\n";
	$return .= $clientzone_templates;

	// TMEM email templates
	$quote_template          = tmem_get_option( 'enquiry', '' );
	$online_quote            = tmem_get_option( 'online_enquiry', '' );
	$unavailable_template    = tmem_get_option( 'unavailable', '' );
	$contract_template       = tmem_get_option( 'contract', '' );
	$booking_conf_template   = tmem_get_option( 'booking_conf_client', '' );
	$auto_payment_template   = tmem_get_option( 'payment_cfm_template', '' );
	$manual_payment_template = tmem_get_option( 'manual_payment_cfm_template', '' );

	$return .= "\n" . '-- TMEM Email Templates' . "\n\n";
	$return .= 'Quote: ' . ( ! empty( $quote_template ) ? get_the_title( $quote_template ) . ' (' . $quote_template . ')' . "\n" : "Unset\n" );
	$return .= 'Online Quote: ' . ( ! empty( $online_quote ) ? get_the_title( $online_quote ) . ' (' . $online_quote . ')' . "\n" : "Unset\n" );
	$return .= 'Unavailable: ' . ( ! empty( $unavailable_template ) ? get_the_title( $unavailable_template ) . ' (' . $unavailable_template . ')' . "\n" : "Unset\n" );
	$return .= 'Awaiting Contract: ' . ( ! empty( $contract_template ) ? get_the_title( $quote_template ) . ' (' . $quote_template . ')' . "\n" : "Unset\n" );
	$return .= 'Booking Confirmation: ' . ( ! empty( $booking_conf_template ) ? get_the_title( $booking_conf_template ) . ' (' . $booking_conf_template . ')' . "\n" : "Unset\n" );
	$return .= 'Gateway Payment: ' . ( ! empty( $auto_payment_template ) ? get_the_title( $auto_payment_template ) . ' (' . $auto_payment_template . ')' . "\n" : "Unset\n" );
	$return .= 'Manual Payment: ' . ( ! empty( $manual_payment_template ) ? get_the_title( $manual_payment_template ) . ' (' . $manual_payment_template . ')' . "\n" : "Unset\n" );

	$return = apply_filters( 'tmem_sysinfo_after_tmem_pages', $return );

	// TMEM Payment Gateways
	$return .= "\n" . '-- TMEM Gateway Configuration' . "\n\n";

	$active_gateways = tmem_get_enabled_payment_gateways();

	if ( $active_gateways ) {

		$default_gateway_is_active = tmem_is_gateway_active( tmem_get_default_gateway() );

		if ( $default_gateway_is_active ) {
			$default_gateway = tmem_get_default_gateway();
			$default_gateway = $active_gateways[ $default_gateway ]['admin_label'];
		} else {
			$default_gateway = 'Test Payment';
		}

		$gateways = array();

		foreach ( $active_gateways as $gateway ) {
			$gateways[] = $gateway['admin_label'];
		}

		$return .= 'Enabled Gateways: ' . implode( ', ', $gateways ) . "\n";
		$return .= 'Default Gateway: ' . $default_gateway . "\n";

	} else {
		$return .= 'Enabled Gateways: None' . "\n";
	}

	$return = apply_filters( 'tmem_sysinfo_after_tmem_gateways', $return );

	// TMEM Templates
	$dir = get_stylesheet_directory() . '/tmem-templates/*';
	if ( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- TMEM Template Overrides' . "\n\n";

		foreach ( glob( $dir ) as $file ) {
			$return .= 'Filename: ' . basename( $file ) . "\n";
		}

		$return = apply_filters( 'tmem_sysinfo_after_tmem_templates', $return );
	}

	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if ( count( $muplugins > 0 ) ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'tmem_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'tmem_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
	}

	$return = apply_filters( 'tmem_sysinfo_after_wordpress_plugins_inactive', $return );

	if ( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update  = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		$return = apply_filters( 'tmem_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version: ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version: ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info: ' . sanitize_key( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_webserver_config', $return );

	// PHP configs... now we're getting to the important stuff
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Safe Mode: ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
	$return .= 'Memory Limit: ' . ini_get( 'tmemory_limit' ) . "\n";
	$return .= 'Upload Max Size: ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size: ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize: ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit: ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars: ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors: ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL: ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen: ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client: ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin: ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'tmem_sysinfo_after_php_ext', $return );

	$return .= "\n" . '### End System Info ###';

	return $return;

} // tmem_tools_sysinfo_get

/**
 * Generates a System Info download file
 *
 * @since 1.4
 * @return void
 */
function tmem_tools_sysinfo_download() {

	if ( ! tmem_employee_can( 'manage_tmem' ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="tmem-system-info.txt"' );

	echo wp_strip_all_tags( wp_unslash( $_POST['tmem-sysinfo'] ) );
	die();
} // tmem_tools_sysinfo_download
add_action( 'tmem-download_sysinfo', 'tmem_tools_sysinfo_download' );

/**
 * Display the tools import/export tab
 *
 * @since 1.5
 * @return void
 */
function tmem_tools_import_export_display() {

	if ( ! current_user_can( 'manage_tmem' ) ) {
		return;
	}

	do_action( 'tmem_tools_import_export_before' );
	?>

	<div class="postbox">
		<h3><span><?php esc_html_e( 'Export Settings', 'mobile-events-manager' ); ?></span></h3>
		<div class="inside">
			<p><?php esc_html_e( 'Export the TMEM Event Management settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'mobile-events-manager' ); ?></p>
			<p>
			<?php
			printf(
				__( 'To export %1$s data (%2$s, clients, etc), visit the <a href="%3$s">Reports</a> page.', 'mobile-events-manager' ),
				esc_html( tmem_get_label_singular( true ) ),
				tmem_get_label_plural( true ),
				admin_url( 'edit.php?post_type=tmem-event&page=tmem-reports&tab=export' )
			);
			?>
			</p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=tmem-event&page=tmem-tools&tab=import_export' ); ?>">
				<p><input type="hidden" name="tmem_action" value="export_settings" /></p>
				<p>
					<?php wp_nonce_field( 'tmem_export_nonce', 'tmem_export_nonce' ); ?>
					<?php submit_button( __( 'Export', 'mobile-events-manager' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->

	<div class="postbox">
		<h3><span><?php esc_html_e( 'Import Settings', 'mobile-events-manager' ); ?></span></h3>
		<div class="inside">
			<p><?php esc_html_e( 'Import the TMEM Event Management settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'mobile-events-manager' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'edit.php?post_type=tmem-event&page=tmem-tools&tab=import_export' ); ?>">
				<p>
					<input type="file" name="import_file"/>
				</p>
				<p>
					<input type="hidden" name="tmem_action" value="import_settings" />
					<?php wp_nonce_field( 'tmem_import_nonce', 'tmem_import_nonce' ); ?>
					<?php submit_button( __( 'Import', 'mobile-events-manager' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->
	<?php
	do_action( 'tmem_tools_import_export_after' );
} // tmem_tools_import_export_display
add_action( 'tmem_tools_tab_import_export', 'tmem_tools_import_export_display' );


/**
 * Process a settings export that generates a .json file of the shop settings
 *
 * @since 1.5
 * @return void
 */
function tmem_tools_import_export_process_export() {

	if ( empty( $_POST['tmem_export_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tmem_export_nonce'], 'tmem_export_nonce' ) ) ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_tmem' ) ) {
		return;
	}

	$settings = array();
	$settings = get_option( 'tmem_settings' );

	ignore_user_abort( true );

	if ( ! tmem_is_func_disabled( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . apply_filters( 'tmem_settings_export_filename', 'tmem-settings-export-' . gmdate( 'd-m-Y' ) ) . '.json' );
	header( 'Expires: 0' );

	echo json_encode( $settings );
	exit;
} // tmem_tools_import_export_process_export
add_action( 'tmem_export_settings', 'tmem_tools_import_export_process_export' );

/**
 * Process a settings import from a json file
 *
 * @since 1.5
 * @return void
 */
function tmem_tools_import_export_process_import() {

	if ( empty( $_POST['tmem_import_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tmem_import_nonce'], 'tmem_import_nonce' ) ) ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_tmem' ) ) {
		return;
	}

	if ( tmem_get_file_extension( sanitize_key( wp_unslash( $_FILES['import_file']['name'] ) ) ) != 'json' ) {
		wp_die( __( 'Please upload a valid .json file', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 400 ) );
	}

	$import_file = sanitize_key( wp_unslash( $_FILES['import_file']['tmp_name'] ) );

	if ( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 400 ) );
	}

	// Retrieve the settings from the file and convert the json object to an array
	$settings = tmem_object_to_array( json_decode( file_get_contents( $import_file ) ) );

	update_option( 'tmem_settings', $settings );

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'    => 'tmem-event',
				'page'         => 'tmem-tools',
				'tab'          => 'import_export',
				'tmem-message' => 'settings-imported',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;

} // tmem_tools_import_export_process_import
add_action( 'tmem_import_settings', 'tmem_tools_import_export_process_import' );
