<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: Mobile Events Manager
 * Plugin URI: https://www.mobileeventsmanager.co.uk
 * Description: The event management system for WordPress.
 * Version: 1.0
 * Date: 15th December 2020
 * Author: Jack Mawhinney, Dan Porter
 * Author URI: http://mobileeventsmanager.co.uk
 * Text Domain: mobile-events-manager
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: Event Management, Event Planning, Event Planner, Events, DJ Event Planner, Mobile DJ
 */
/**
Mobile Events Manager (MEM) is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

Mobile Events Manager (MEM) is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Mobile Events Manager (MEM); if not, see https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! class_exists( 'Mobile_Events_Manager' ) ) :
	/**
	 * Class: Mobile_Events_Manager
	 * Description: The main MEM class
	 */
	class Mobile_Events_Manager {
		private static $instance;

		public $api;

		public $content_tags;

		public $cron;

		public $debug;

		public $emails;

		public $events;

		public $html;

		public $permissions;

		public $roles;

		public $txns;

		public $users;

		public $availability_db;

		public $availability_meta_db;

		/**
		 * Ensure we only have one instance of MEM loaded into memory at any time.
		 *
		 * @since 1.3
		 * @param
		 * @return The one true Mobile_Events_Manager
		 */
		public static function instance() {
			global $mem, $mem_debug, $clientzone, $wp_version;

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Mobile_Events_Manager ) ) {
				self::$instance = new Mobile_Events_Manager();

				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

				self::$instance->includes();
				$mem                   = new MEM();
				self::$instance->debug  = new MEM_Debug();
				$mem_debug             = self::$instance->debug; // REMOVE POST 1.3.
				self::$instance->events = new MEM_Events();

				if ( version_compare( floatval( $wp_version ), '4.4', '>=' ) ) {
					self::$instance->api = new MEM_API();
				}

				self::$instance->availability_db      = new MEM_DB_Availability();
				self::$instance->availability_meta_db = new MEM_DB_Availability_Meta();
				self::$instance->playlist_db          = new MEM_DB_Playlists();
				self::$instance->playlist_meta_db     = new MEM_DB_Playlist_Meta();

				self::$instance->content_tags = new MEM_Content_Tags();
				self::$instance->cron         = new MEM_Cron();
				self::$instance->emails       = new MEM_Emails();
				self::$instance->html         = new MEM_HTML_Elements();
				self::$instance->users        = new MEM_Users();
				self::$instance->roles        = new MEM_Roles();
				self::$instance->permissions  = new MEM_Permissions();
				self::$instance->txns         = new MEM_Transactions();

				// If we're on the front end, load the ClienZone class.
				if ( class_exists( 'ClientZone' ) ) {
					$clientzone = new ClientZone();
				}
			}

			return self::$instance;
		} // instance

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.3
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden.
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mobile-events-manager' ), '1.3' );
		}

		/**
		 * Setup plugin constants.
		 *
		 * @access private
		 * @since 1.3
		 * @return void
		 */
		private function setup_constants() {
			global $wpdb;
			define( 'MEM_VERSION_NUM', '1.5.7' );
			define( 'MEM_VERSION_KEY', 'mem_version' );
			define( 'MEM_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
			define( 'MEM_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
			define( 'MEM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'MEM_PLUGIN_FILE', __FILE__ );
			define( 'MEM_NAME', 'Mobile Events Manager' );

			define( 'MEM_API_SETTINGS_KEY', 'mem_api_data' );

			// Tables
			define( 'MEM_HOLIDAY_TABLE', $wpdb->prefix . 'mem_avail' );

		} // setup_constants

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 1.3
		 * @return void
		 */
		private function includes() {
			global $mem_options;

			require_once MEM_PLUGIN_DIR . '/includes/admin/settings/register-settings.php';
			$mem_options = mem_get_settings();

			require_once MEM_PLUGIN_DIR . '/includes/actions.php';

			if ( file_exists( MEM_PLUGIN_DIR . '/includes/deprecated-functions.php' ) ) {
				require_once MEM_PLUGIN_DIR . '/includes/deprecated-functions.php';
			}

			require_once MEM_PLUGIN_DIR . '/includes/ajax-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/api/class-mem-api.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-db.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/mem.php';
			require_once MEM_PLUGIN_DIR . '/includes/template-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-cache-helper.php';
			require_once MEM_PLUGIN_DIR . '/includes/payments/actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/payments/payments.php';
			require_once MEM_PLUGIN_DIR . '/includes/payments/process-payments.php';
			require_once MEM_PLUGIN_DIR . '/includes/payments/template.php';
			require_once MEM_PLUGIN_DIR . '/includes/events/class-mem-event.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-html-elements.php';
			require_once MEM_PLUGIN_DIR . '/includes/events/class-events.php';
			require_once MEM_PLUGIN_DIR . '/includes/events/event-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/events/event-actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/journal-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/emails/class-mem-emails.php';
			require_once MEM_PLUGIN_DIR . '/includes/emails/email-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/availability/class-mem-db-availability.php';
			require_once MEM_PLUGIN_DIR . '/includes/availability/class-mem-db-availabulity-meta.php';
			require_once MEM_PLUGIN_DIR . '/includes/availability/class-mem-availability-checker.php';
			require_once MEM_PLUGIN_DIR . '/includes/availability/availability-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/availability/availability-actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/contract/contract-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/contract/contract-actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-travel.php';
			require_once MEM_PLUGIN_DIR . '/includes/travel-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/playlist/class-mem-db-playlists.php';
			require_once MEM_PLUGIN_DIR . '/includes/playlist/class-mem-db-playlist-meta.php';
			require_once MEM_PLUGIN_DIR . '/includes/playlist/playlist-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/playlist/playlist-actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/venue-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/txns/class-mem-txn.php';
			require_once MEM_PLUGIN_DIR . '/includes/txns/txn-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/txns/txn-actions.php';
			require_once MEM_PLUGIN_DIR . '/includes/equipment/equipment-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/misc-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/privacy-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/pages/event-fields.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/pages/client-fields.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/users/class-mem-users.php';
			require_once MEM_PLUGIN_DIR . '/includes/clients/client-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/clients/class-mem-client.php';
			require_once MEM_PLUGIN_DIR . '/includes/employee-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/roles/class-mem-roles.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/roles/roles-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/roles/class-mem-permissions.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/settings/display-settings.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/menu.php';
			require_once MEM_PLUGIN_DIR . '/includes/content/content-tags.php';
			require_once MEM_PLUGIN_DIR . '/includes/mem-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/clientzone-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/login.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-cron.php';
			require_once MEM_PLUGIN_DIR . '/includes/scripts.php';
			require_once MEM_PLUGIN_DIR . '/includes/post-types.php';
			require_once MEM_PLUGIN_DIR . '/includes/formatting.php';
			require_once MEM_PLUGIN_DIR . '/includes/widgets.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-stats.php';
			require_once MEM_PLUGIN_DIR . '/includes/events/class-mem-events-query.php';
			require_once MEM_PLUGIN_DIR . '/includes/class-mem-debug.php';
			require_once MEM_PLUGIN_DIR . '/includes/admin/transactions/mem-transactions.php';
			require_once MEM_PLUGIN_DIR . '/includes/shortcodes.php';
			require_once MEM_PLUGIN_DIR . '/includes/tasks/task-functions.php';
			require_once MEM_PLUGIN_DIR . '/includes/plugin-compatibility.php';

			if ( is_admin() ) {
				require_once MEM_PLUGIN_DIR . '/includes/admin/admin-actions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/plugins.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/communications/comms.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/communications/comms-functions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/communications/contextual-help.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/communications/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/events.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/quotes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/taxonomies.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/contextual-help.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/playlist.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/equipment/equipment.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/equipment/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/templates/contracts.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/templates/emails.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/templates/contextual-help.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/templates/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/tools.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/transactions/txns.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/transactions/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/transactions/taxonomies.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/venues/venues.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/venues/metaboxes.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/dashboard-widgets.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/playlist-page.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/events/event-actions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/users/employee-actions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/availability/availability-actions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/availability/availability-page.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/tasks/task-actions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/tasks/tasks-page.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/admin-notices.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/settings/contextual-help.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/export/export-functions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/reporting-functions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/class-mem-graph.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/class-mem-pie-graph.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/graphing-functions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/extensions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/upgrades/upgrade-functions.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/upgrades/upgrades.php';
				require_once MEM_PLUGIN_DIR . '/includes/admin/welcome.php';

			}

			require_once MEM_PLUGIN_DIR . '/includes/install.php';

		} // includes

		/**
		 * Load the plugins text domain for translations.
		 *
		 * @since 1.3
		 * @param
		 * @return void
		 */
		public static function load_textdomain() {
			// Load the text domain for translations.
			load_plugin_textdomain(
				'mobile-events-manager',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages'
			);
		} // load_textdomain

	} // class Mobile_Events_Manager

endif;

/** Mobile DJ Manager */
function MEM() {
	return Mobile_Events_Manager::instance();
}

MEM();
