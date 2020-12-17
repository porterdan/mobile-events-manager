<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: Mobile Events Manager
 * Plugin URI: https://www.mobileeventsmanager.co.uk
 * Description: The most efficient and versatile event management solution for WordPress.
 * Version: 1.0.4
 * Date: 17 December 2020
 * Author: Jack Mawhinney, Dan Porter
 * Author URI: https://www.mobileeventsmanager.co.uk
 * Text Domain: mobile-events-manager
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tags: Event Management, Event Planning, Event Planner, Events, DJ Event Planner, Mobile DJ
 */
/**
TMEM Event Management is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

TMEM Event Management is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with TMEM Event Management; if not, see https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! class_exists( 'Mobile_Events_Manager' ) ) :
	/**
	 * Class: Mobile_Events_Manager
	 * Description: The main TMEM class
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
		 * Ensure we only have one instance of TMEM loaded into tmemory at any time.
		 *
		 * @since 1.3
		 * @param
		 * @return The one true Mobile_Events_Manager
		 */
		public static function instance() {
			global $tmem, $tmem_debug, $clientzone, $wp_version;

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Mobile_Events_Manager ) ) {
				self::$instance = new Mobile_Events_Manager();

				self::$instance->setup_constants();

				add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

				self::$instance->includes();
				$tmem                   = new TMEM();
				self::$instance->debug  = new TMEM_Debug();
				$tmem_debug             = self::$instance->debug; // REMOVE POST 1.3.
				self::$instance->events = new TMEM_Events();

				if ( version_compare( floatval( $wp_version ), '4.4', '>=' ) ) {
					self::$instance->api = new TMEM_API();
				}

				self::$instance->availability_db      = new TMEM_DB_Availability();
				self::$instance->availability_meta_db = new TMEM_DB_Availability_Meta();
				self::$instance->playlist_db          = new TMEM_DB_Playlists();
				self::$instance->playlist_meta_db     = new TMEM_DB_Playlist_Meta();

				self::$instance->content_tags = new TMEM_Content_Tags();
				self::$instance->cron         = new TMEM_Cron();
				self::$instance->emails       = new TMEM_Emails();
				self::$instance->html         = new TMEM_HTML_Elements();
				self::$instance->users        = new TMEM_Users();
				self::$instance->roles        = new TMEM_Roles();
				self::$instance->permissions  = new TMEM_Permissions();
				self::$instance->txns         = new TMEM_Transactions();

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
			define( 'TMEM_VERSION_NUM', '1.5.7' );
			define( 'TMEM_VERSION_KEY', 'tmem_version' );
			define( 'TMEM_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
			define( 'TMEM_PLUGIN_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
			define( 'TMEM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'TMEM_PLUGIN_FILE', __FILE__ );
			define( 'TMEM_NAME', 'TMEM Event Management' );

			define( 'TMEM_API_SETTINGS_KEY', 'tmem_api_data' );

			// Tables
			define( 'TMEM_HOLIDAY_TABLE', $wpdb->prefix . 'tmem_avail' );

		} // setup_constants

		/**
		 * Include required files.
		 *
		 * @access private
		 * @since 1.3
		 * @return void
		 */
		private function includes() {
			global $tmem_options;

			require_once TMEM_PLUGIN_DIR . '/includes/admin/settings/register-settings.php';
			$tmem_options = tmem_get_settings();

			require_once TMEM_PLUGIN_DIR . '/includes/actions.php';

			if ( file_exists( TMEM_PLUGIN_DIR . '/includes/deprecated-functions.php' ) ) {
				require_once TMEM_PLUGIN_DIR . '/includes/deprecated-functions.php';
			}

			require_once TMEM_PLUGIN_DIR . '/includes/ajax-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/api/class-tmem-api.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-db.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/tmem.php';
			require_once TMEM_PLUGIN_DIR . '/includes/template-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-cache-helper.php';
			require_once TMEM_PLUGIN_DIR . '/includes/payments/actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/payments/payments.php';
			require_once TMEM_PLUGIN_DIR . '/includes/payments/process-payments.php';
			require_once TMEM_PLUGIN_DIR . '/includes/payments/template.php';
			require_once TMEM_PLUGIN_DIR . '/includes/events/class-tmem-event.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-html-elements.php';
			require_once TMEM_PLUGIN_DIR . '/includes/events/class-events.php';
			require_once TMEM_PLUGIN_DIR . '/includes/events/event-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/events/event-actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/journal-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/emails/class-tmem-emails.php';
			require_once TMEM_PLUGIN_DIR . '/includes/emails/email-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/availability/class-tmem-db-availability.php';
			require_once TMEM_PLUGIN_DIR . '/includes/availability/class-tmem-db-availabulity-meta.php';
			require_once TMEM_PLUGIN_DIR . '/includes/availability/class-tmem-availability-checker.php';
			require_once TMEM_PLUGIN_DIR . '/includes/availability/availability-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/availability/availability-actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/contract/contract-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/contract/contract-actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-travel.php';
			require_once TMEM_PLUGIN_DIR . '/includes/travel-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/playlist/class-tmem-db-playlists.php';
			require_once TMEM_PLUGIN_DIR . '/includes/playlist/class-tmem-db-playlist-meta.php';
			require_once TMEM_PLUGIN_DIR . '/includes/playlist/playlist-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/playlist/playlist-actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/venue-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/txns/class-tmem-txn.php';
			require_once TMEM_PLUGIN_DIR . '/includes/txns/txn-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/txns/txn-actions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/equipment/equipment-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/misc-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/privacy-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/pages/event-fields.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/pages/client-fields.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/users/class-tmem-users.php';
			require_once TMEM_PLUGIN_DIR . '/includes/clients/client-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/clients/class-tmem-client.php';
			require_once TMEM_PLUGIN_DIR . '/includes/employee-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/roles/class-tmem-roles.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/roles/roles-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/roles/class-tmem-permissions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/settings/display-settings.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/menu.php';
			require_once TMEM_PLUGIN_DIR . '/includes/content/content-tags.php';
			require_once TMEM_PLUGIN_DIR . '/includes/tmem-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/clientzone-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/login.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-cron.php';
			require_once TMEM_PLUGIN_DIR . '/includes/scripts.php';
			require_once TMEM_PLUGIN_DIR . '/includes/post-types.php';
			require_once TMEM_PLUGIN_DIR . '/includes/formatting.php';
			require_once TMEM_PLUGIN_DIR . '/includes/widgets.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-stats.php';
			require_once TMEM_PLUGIN_DIR . '/includes/events/class-tmem-events-query.php';
			require_once TMEM_PLUGIN_DIR . '/includes/class-tmem-debug.php';
			require_once TMEM_PLUGIN_DIR . '/includes/admin/transactions/tmem-transactions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/shortcodes.php';
			require_once TMEM_PLUGIN_DIR . '/includes/tasks/task-functions.php';
			require_once TMEM_PLUGIN_DIR . '/includes/plugin-compatibility.php';

			if ( is_admin() ) {
				require_once TMEM_PLUGIN_DIR . '/includes/admin/admin-actions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/plugins.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/communications/comms.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/communications/comms-functions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/communications/contextual-help.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/communications/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/events.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/quotes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/taxonomies.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/contextual-help.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/playlist.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/equipment/equipment.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/equipment/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/templates/contracts.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/templates/emails.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/templates/contextual-help.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/templates/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/tools.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/transactions/txns.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/transactions/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/transactions/taxonomies.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/venues/venues.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/venues/metaboxes.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/dashboard-widgets.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/playlist-page.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/events/event-actions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/users/employee-actions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/availability/availability-actions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/availability/availability-page.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/tasks/task-actions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/tasks/tasks-page.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/admin-notices.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/settings/contextual-help.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/export/export-functions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/reporting-functions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/class-tmem-graph.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/class-tmem-pie-graph.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/graphing-functions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/extensions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/upgrades/upgrade-functions.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/upgrades/upgrades.php';
				require_once TMEM_PLUGIN_DIR . '/includes/admin/welcome.php';

			}

			require_once TMEM_PLUGIN_DIR . '/includes/install.php';

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

/** Mobile Events Manager */
function TMEM() {
	return Mobile_Events_Manager::instance();
}

TMEM();
