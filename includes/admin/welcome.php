<?php
/**
 * Welcome Page Class
 *
 * @package TMEM
 * @subpackage Admin/Welcome
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMEM_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since 1.3
 */
class TMEM_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 1.3
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menus' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome' ) );
	}

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access public
	 * @since 1.3
	 * @return void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__( 'Welcome to TMEM Event Management', 'mobile-events-manager' ),
			__( 'Welcome to TMEM Event Management', 'mobile-events-manager' ),
			$this->minimum_capability,
			'tmem-about',
			array( $this, 'about_screen' )
		);

		// Changelog Page
		add_dashboard_page(
			__( 'ETMEM Event Management Changelog', 'mobile-events-manager' ),
			__( 'TMEM Event Management Changelog', 'mobile-events-manager' ),
			$this->minimum_capability,
			'tmem-changelog',
			array( $this, 'changelog_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with TMEM Event Management', 'mobile-events-manager' ),
			__( 'Getting started with TMEM Event Management', 'mobile-events-manager' ),
			$this->minimum_capability,
			'tmem-getting-started',
			array( $this, 'getting_started_screen' )
		);

		// Now remove them from the menus so plugins that allow customizing the admin menu don't show them
		remove_submenu_page( 'index.php', 'tmem-about' );
		remove_submenu_page( 'index.php', 'tmem-changelog' );
		remove_submenu_page( 'index.php', 'tmem-getting-started' );

	} // admin_menus

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access public
	 * @since 1.3
	 * @return void
	 */
	public function admin_head() {
		?>
<style type="text/css" media="screen">
/*<![CDATA[*/
.tmem-about-wrap .tmem-badge {
	float: right;
	border-radius: 4px;
	margin: 0 0 15px 15px;
	max-width: 200px;
}
.tmem-about-wrap #tmem-header {
	margin-bottom: 15px;
}
.tmem-about-wrap #tmem-header h1 {
	margin-bottom: 15px !important;
}
.tmem-about-wrap .about-text {
	margin: 0 0 15px;
	max-width: 670px;
}
.tmem-about-wrap .feature-section {
	margin-top: 20px;
}
.tmem-about-wrap .feature-section-content, .tmem-about-wrap .feature-section-media {
	width: 50%;
	box-sizing: border-box;
}
.tmem-about-wrap .feature-section-content {
	float: left;
	padding-right: 50px;
}
.tmem-about-wrap .feature-section-content h4 {
	margin: 0 0 1em;
}
.tmem-about-wrap .feature-section-media {
	float: right;
	text-align: right;
	margin-bottom: 20px;
}
.tmem-about-wrap .feature-section-media img {
	border: 1px solid #ddd;
}
.tmem-about-wrap .feature-section:not(.under-the-hood) .col {
	margin-top: 0;
}

/* responsive */
@media all and ( max-width: 782px ) {
.tmem-about-wrap .feature-section-content, .tmem-about-wrap .feature-section-media {
	float: none;
	padding-right: 0;
	width: 100%;
	text-align: left;
}
.tmem-about-wrap .feature-section-media img {
	float: none;
	margin: 0 0 20px;
}
}
			/*]]>*/
</style>
		<?php
	} // admin_head

	/**
	 * Welcome message
	 *
	 * @access public
	 * @since 2.5
	 * @return void
	 */
	public function welcome_message() {
		list( $display_version ) = explode( '-', TMEM_VERSION_NUM );
		?>
<div id="tmem-header"> <img class="tmem-badge" src="<?php echo TMEM_PLUGIN_URL . '/assets/images/tmem_logo_300.png'; ?>" alt="<?php esc_html_e( 'TMEM Event Management', 'mobile-events-manager' ); ?>" / >
  <h1>
		<?php esc_html_e( __( 'Welcome to TMEM Event Management %s', 'mobile-events-manager' ), esc_html( $display_version ) ); ?>
  </h1>
  <p class="about-text">
		<?php esc_html_e( 'Thank you for updating to the latest version!', 'mobile-events-manager' ); ?>
	<br />
		<?php
		esc_html_e(
			__( 'TMEM Event Management %1$s is ready to make your %2$s business even more efficient!', 'mobile-events-manager' ),
			esc_html( $display_version ),
			esc_html( tmem_get_label_plural( true ) )
		);
		?>
  </p>
</div>
		<?php
	} // welcome_message

	/**
	 * Navigation tabs
	 *
	 * @access public
	 * @since 1.3
	 * @return void
	 */
	public function tabs() {
		$selected = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'tmem-about';
		?>
<h1 class="nav-tab-wrapper"> <a class="nav-tab <?php echo 'tmem-about' === $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'tmem-about' ), 'index.php' ) ) ); ?>">
		<?php esc_html_e( "What's New", 'mobile-events-manager' ); ?>
  </a> <a class="nav-tab <?php echo 'tmem-getting-started' === $selected ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'tmem-getting-started' ), 'index.php' ) ) ); ?>">
		<?php esc_html_e( 'Getting Started', 'mobile-events-manager' ); ?>
  </a> </h1>
		<?php
	} // tabs

	/**
	 * Render About Screen
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function about_screen() {
		?>
<div class="wrap about-wrap tmem-about-wrap">
		<?php
		// Load welcome message and content tabs
		$this->welcome_message();
		$this->tabs();
		?>
  <div class="changelog">
	<h3>
		<?php esc_html_e( 'Showcase your Business Products', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/14-package-list.png'; ?>"/> </div>
	  <div class="feature-section-content">
		<p>
		  <?php esc_html_e( 'With TMEM Event Management version 1.4, you now have the ability to showcase your business and products.', 'mobile-events-manager' ); ?>
		</p>
		<p>
		  <?php esc_html_e( 'Packages &amp; Addons are now created as custom post types so you can enjoy all the functionality of normal WordPress posts such as a featured image, including multiple images within the description, a detailed description, an excerpt and a full archive of your products.', 'mobile-events-manager' ); ?>
		</p>
		<p>
		  <?php
			printf(
				__( 'Each package and add-on has its own URL to be showcased on your website, or alternatively you can display the archives by creating menu links to <a href="%1$s" target="_blank">%1$s</a> and <a href="%2$s" target="_blank">%2$s</a> respectively.', 'mobile-events-manager' ),
				site_url( '/packages/' ),
				site_url( '/addons/' )
			);
			?>
		</p>
		<p>
			  <?php esc_html_e( 'In addition you can utilise a variety of plugins to show off your business products effectively and entice more clients to get in touch.', 'mobile-events-manager' ); ?>
		</p>
		<h4>
			  <?php esc_html_e( 'Variable Pricing', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php esc_html_e( "Assign variable prices to your packages and addons depending on month's of the year.", 'mobile-events-manager' ); ?>
		  <br />
			  <?php esc_html_e( 'Perhaps you have a full wedding package that is cheaper during winter months than in the summer.', 'mobile-events-manager' ); ?>
		</p>
		<h4>
			  <?php esc_html_e( 'Set Availability Options', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php esc_html_e( 'You can now specify the conditions under which individual packages and addons are available for use. Options include availability during certain months of the year, for specific event types, and for individual employees.', 'mobile-events-manager' ); ?>
		</p>
	  </div>
	</div>
  </div>
  <div class="changelog">
	<h3>
		  <?php esc_html_e( 'Reports &amp; Export', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/14-reports.png'; ?>"/> </div>
	  <div class="feature-section-content">
		<p>
			  <?php esc_html_e( "Knowing how your business is performing is key to its long term success. With TMEM Event Management version 1.4 we've provided easy access to a number of reports so you have this information at your fingertips at all times.", 'mobile-events-manager' ); ?>
		</p>
		<p><?php printf( esc_html__( 'Reports include income and expenditure, most popular %s types, most successful enquiry sources and more.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?></p>
		<p>
			  <?php
				printf(
					esc_html__( 'Export %s, transaction, client and employee data to CSV files enabling you to subsequently import into other systems, such as accounting tools.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) )
				);
				?>
		</p>
	  </div>
	</div>
  </div>
  <div class="changelog">
	<h3>
		  <?php esc_html_e( 'Travel Data', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/14-travel-costs.png'; ?>"/> </div>
	  <div class="feature-section-content">
		<p>
			  <?php
				printf(
					esc_html__( 'From version 1.4 you can configure settings to automatically add the cost of %1$s travel to the overall %1$s cost.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) )
				);
				?>
		</p>
		<p>
			  <?php esc_html_e( "Travel costs are determined by connecting to Google's distance matrix API and calculating the distance from the primary employees address (or the default address per settings) to the venue address. You define the per cost per mile/kilometer and a few other settings to match your preferences and TMEM will do the rest for you.", 'mobile-events-manager' ); ?>
		</p>
		<p>
			  <?php
				printf(
					esc_html__( 'Handy shortcodes (see below) are also available to provide directions to a venue which you can include within automated emails received by employees ahead of an %s.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) )
				);
				?>
		</p>
	  </div>
	</div>
  </div>
  <div class="changelog">
	<h3>
		  <?php esc_html_e( 'REST API', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/14-rest-api.png'; ?>"/> </div>
	  <div class="feature-section-content">
		<p>
			  <?php esc_html_e( 'TMEM Event Management version 1.4 extends the WordPress REST API enabling easy, yet secure, access to a multitude of data via third party tools and integrations.', 'mobile-events-manager' ); ?>
		</p>
		<p>
			  <?php
				printf(
					esc_html__( 'Endpoints are available to retrieve data for %1$s, clients, employees, packages and add-ons, and availability. For more information visit the <a href="%2$s" target="_blank">Support Documentation</a>', 'mobile-events-manager' ),
					esc_html( tmem_get_label_plural() ),
					'https://www.mobileeventsmanager.co.uk/docs/api/tmem-rest-api-introduction/'
				);
				?>
		</p>
	  </div>
	</div>
  </div>
  <div class="changelog">
	<h3>
		  <?php esc_html_e( 'Additional Updates', 'mobile-events-manager' ); ?>
	</h3>
	<hr />
	<div class="feature-section three-col">
	  <div class="col">
		<h4>
			  <?php esc_html_e( 'Travel Content Tags', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php esc_html_e( '<code>{travel_cost}</code>, <code>{travel_directions}</code>, <code>{travel_distance}</code>, and <code>{travel_time}</code> content tags added.', 'mobile-events-manager' ); ?>
		</p>
	  </div>
	  <div class="col">
		<h4>
			  <?php esc_html_e( 'Playlist Entries', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php
				printf(
					esc_html__( 'Employees can now add entries to a playlist via admin. Navigate to the %s screen and click on the playlist entries column.', 'mobile-events-manager' ),
					tmem_get_label_plural( true )
				);
				?>
		</p>
	  </div>
	  <div class="col">
		<h4>
			  <?php esc_html_e( 'Improved Upgrade Procedures', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php esc_html_e( 'Re-designed plugin update procedures providing a cleaner and more reliable upgrade procedure.', 'mobile-events-manager' ); ?>
		</p>
	  </div>
	</div>
  </div>
  <div class="return-to-dashboard"> <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'tmem-settings' ), 'admin.php' ) ) ); ?>">
		<?php esc_html_e( 'Go to TMEM Event Management Settings', 'mobile-events-manager' ); ?>
	</a> &middot; <a href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'tmem-changelog' ), 'index.php' ) ) ); ?>">
		<?php esc_html_e( 'View the Full Changelog', 'mobile-events-manager' ); ?>
	</a> </div>
</div>
		<?php
	} // about_screen

	/**
	 * Render Changelog Screen
	 *
	 * @access public
	 * @since 2.0.3
	 * @return void
	 */
	public function changelog_screen() {
		?>
<div class="wrap about-wrap tmem-about-wrap">
		<?php
		// load welcome message and content tabs
		$this->welcome_message();
		$this->tabs();
		?>
  <div class="changelog">
	<h3>
		<?php esc_html_e( 'Full Changelog', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section"> <?php echo $this->parse_readme(); ?> </div>
  </div>
  <div class="return-to-dashboard"> <a href="
				<?php
				echo esc_url(
					admin_url(
						add_query_arg(
							array(
								'post_type' => 'tmem-event',
								'page'      => 'tmem-settings',
							),
							'edit.php'
						)
					)
				);
				?>
							">
		<?php esc_html_e( 'Go to TMEM Event Management Settings', 'mobile-events-manager' ); ?>
	</a> </div>
</div>
		<?php
	} // changelog_screen

	/**
	 * Render Getting Started Screen
	 *
	 * @access public
	 * @since 1.9
	 * @return void
	 */
	public function getting_started_screen() {
		?>
<div class="wrap about-wrap tmem-about-wrap">
		<?php
		// load welcome message and content tabs
		$this->welcome_message();
		$this->tabs();
		?>
  <p class="about-description">
		<?php esc_html_e( "Now that TMEM Event Management is installed, you're ready to get started. It works out of the box, but there are some customisations you can configure to match your business needs.", 'mobile-events-manager' ); ?>
  </p>
  <div class="changelog">
	<h3>
		<?php
		printf(
			esc_html__( 'Creating Your First %s', 'mobile-events-manager' ),
			esc_html( tmem_get_label_singular() )
		);
		?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/tmem-first-event.png'; ?>" class="tmem-welcome-screenshots"/> </div>
	  <div class="feature-section-content">
		<h4><a href="<?php echo admin_url( 'post-new.php?post_type=tmem-event' ); ?>">
		  <?php
			printf(
				esc_html__( 'TMEM %1$s &rarr; Create %2$s', 'mobile-events-manager' ),
				esc_html( tmem_get_label_plural() ),
				esc_html( tmem_get_label_singular() )
			);
			?>
		  </a></h4>
		<p>
			  <?php
				printf(
					esc_html__( 'The TMEM %1$s menu is your access point to all aspects of your %2$s creation and setup. To create your first %2$s, simply click Add New and then fill out the %2$s details.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_plural() ),
					esc_html( tmem_get_label_singular( true ) )
				);
				?>
		</p>
		<h4>
			  <?php esc_html_e( 'Create a Client', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php
				printf(
					esc_html__( 'Create clients directly from the %s screen by selected <strong>Add New Client</strong> from the <em>Select Client</em> dropdown to reveal a few additional fields', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular() )
				);
				?>
		<h4>
			  <?php
				printf(
					esc_html__( 'Add %s Types', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular() )
				);
				?>
		</h4>
		<p>
			  <?php
				printf(
					esc_html__( 'If the %1$s type does not exist for the %1$s you are creating, click the <em>Add New</em> link next to the %2$s Type dropdown, enter the %2$s Type name in the text box that is revealed and click <em>Add</em>.<br />To manage all %2$s Types, go to <a href="%4$s">TMEM %3$s &rarr; %2$s Types</a>.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					esc_html( tmem_get_label_singular() ),
					esc_html( tmem_get_label_plural() ),
					admin_url( 'edit-tags.php?taxonomy=event-types&post_type=tmem-event' )
				);
				?>
		</p>
	  </div>
	</div>
  </div>
  <div class="changelog">
	<h3>
		  <?php esc_html_e( 'Setup Templates for Complete Automation', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section">
	  <div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/tmem-edit-template.png'; ?>"/> </div>
	  <div class="feature-section-content">
		<h4>
			  <?php esc_html_e( 'Email Templates', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			  <?php
				printf(
					esc_html__( 'Email templates can be configured to be sent automatically during an %1$s status change. Supporting our vast collection of <a href="%2$s" target="_blank">content tags</a> each email can be completley customised and tailored to the %1$s and client details.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					'https://www.mobileeventsmanager.co.uk/docs/content-tags/'
				);
				?>
		</p>
		<p>
			  <?php esc_html_e( 'With email tracking enabled, you can even be sure that your client received your email and know when they have read it.', 'mobile-events-manager' ); ?>
		</p>
		<h4>
			  <?php esc_html_e( 'Contract Templates', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %1: Event Type %2: Client zone name */
					esc_html__( 'Create contract templates that be assigned to your %1. Clients will be able to review and <strong>Digitally Sign</strong> the contract via the %2$s.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular() ),
					esc_html( tmem_get_application_name() )
				);
			?>
		</p>
	</div>
	</div>
</div>
<div class="changelog">
	<h3>
		<?php
			printf(
				/* translators: %s: Event Type */
				esc_html__( 'Create %s Packages &amp; Add-ons', 'mobile-events-manager' ),
				esc_html( tmem_get_label_singular() )
			);
		?>
	</h3>
	<div class="feature-section">
	<div class="feature-section-media"> <img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/screenshots/14-package-options.png'; ?>"/> </div>
	<div class="feature-section-content">
		<h4>
		<?php
			printf(
				/* translators: %1: Company Name */
				esc_html__( '%1$s Packages', 'mobile-events-manager' ),
				esc_html( tmem_get_label_singular() )
			);
		?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %1: Event Type %2: travel %3 costs */
					esc_html__( 'Packages are a pre-defined collection of add-ons that you can offer to your clients for their %1$s. Define a price for the package and upon selection, the %1$s %2$s and %3$s will be automatically and dynamically re-calculated. Add-ons included within the package, will no longer be available for selection within the add-ons list for this %2$s.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					tmem_get_balance_label(),
					tmem_get_deposit_label()
				);
			?>
		</p>
		<h4>
			<?php esc_html_e( 'Add-ons', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %1: Event Type %2: Travel %3: Times */
					esc_html__( 'Add-ons are additional equipment items that can be selected for an %1$s. Each add-on is assigned an individual price and when selected the %1$s %2$s and %3$s are automatically and dynamically re-calculated.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					tmem_get_balance_label(),
					tmem_get_deposit_label()
				);
			?>
		</p>
		<p>
			<?php
				printf(
					/* translators: %1: Company Name %2: Artiste %3: URL %4: URL %5: URL */
					esc_html__( 'Once you have enabled %1$s Packages &amp; Add-ons within the <a href="%3$s">TMEM %2$s Settings page</a>, manage them within <a href="%4$s">TMEM %2$s &rarr; Equipment Packages</a> and a href="%5$s">TMEM %2$s &rarr; Equipment Add-ons</a>.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					esc_html( tmem_get_label_plural() ),
					admin_url( 'admin.php?page=tmem-settings&tab=events' ),
					admin_url( 'edit.php?post_type=tmem-package' ),
					admin_url( 'edit.php?post_type=tmem-addon' )
				);
			?>
		</p>
	</div>
	</div>
</div>
<div class="changelog">
	<h3>
		<?php esc_html_e( 'Even More Features', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section two-col">
	<div class="col">
		<h4>
			<?php esc_html_e( 'Integrated Client Portal', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %1: Event %2: Company Name */
					esc_html__( 'Known as the <em>Client Zone</em> by default, a password protected portal is available to your clients where they can review their %1$s, view and accept your quote, digitally sign their contract, and manage their %1$s playlist. All %2$s pages use a template system and are fully customisable.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) ),
					esc_html( tmem_get_application_name() )
				);
			?>
		</p>
	  </div>
	<div class="col">
		<h4>
			<?php esc_html_e( 'Digitally Sign Contracts', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %1: Client Zone name %2: Event Type */
					esc_html__( 'Via the %1$s, clients are able to review and digitally sign their %2$s contract. Signing requires confirmation of their name and password for verification to maintain security.', 'mobile-events-manager' ),
					esc_html( tmem_get_application_name() ),
					esc_html( tmem_get_label_singular() )
				);
			?>
		</p>
	</div>
	</div>
	<div class="feature-section two-col">
	<div class="col">
		<h4>
			<?php esc_html_e( 'Transaction Logging', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %s: Event Type */
					esc_html__( 'Log all payments your business receives and all expenses you have with the TMEM Event Management Transactions system. Instantly know how profitable your %s are as well as how much money your company has made over differing periods of time.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_plural() )
				);
			?>
		</p>
	</div>
	<div class="col">
		<h4>
			<?php esc_html_e( 'Multi Employee Aware', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %s: Event */
					esc_html__( 'TMEM Event Management supports as many employees as you need at no additional cost. Easily create new employees, set permissions for them to ensure they only have access to what they need, and then assign as many employees to an %s as you need.', 'mobile-events-manager' ),
					esc_html( tmem_get_label_singular( true ) )
				);
			?>
		</p>
	</div>
	</div>
</div>
<div class="changelog">
	<h3>
		<?php esc_html_e( 'Need Help?', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section two-col">
	<div class="col">
		<h4>
			<?php esc_html_e( 'Excellent Support', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php esc_html_e( 'We pride ourselves on our level of support and excellent response times. If you are experiencing an issue, submit a support ticket and we will respond quickly.', 'mobile-events-manager' ); ?>
		</p>
	</div>
	<div class="col">
		<h4>
			<?php esc_html_e( 'Join our Facebook User Group', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php esc_html_e( 'Our <a href="https://www.facebook.com/groups/mobile-events-manager/" target="_blank">TMEM Facebook User Group</a> is a great way to exchange knowledge with other users and gain tips for use.', 'mobile-events-manager' ); ?>
		</p>
	</div>
	</div>
</div>
<div class="changelog">
	<h3>
		<?php esc_html_e( 'Stay Up to Date', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section two-col">
	<div class="col">
		<h4>
			<?php esc_html_e( 'Get Notified of Add-on Releases', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php esc_html_e( 'New add-ons make TMEM Event Management even more powerful. Subscribe to the newsletter to stay up to date with our latest releases. <a href="http://eepurl.com/bTRkZj" target="_blank">Sign up now</a> to ensure you do not miss a release!', 'mobile-events-manager' ); ?>
		</p>
	</div>
	<div class="col">
		<h4>
			<?php esc_html_e( 'Get Alerted About New Tutorials', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php esc_html_e( '<a href="http://eepurl.com/bTRkZj" target="_blank">Sign up now</a> to hear about the latest tutorial releases that explain how to take TMEM Event Management further.', 'mobile-events-manager' ); ?>
		</p>
	</div>
	</div>
</div>
<div class="changelog">
	<h3>
		<?php esc_html_e( 'Extensions', 'mobile-events-manager' ); ?>
	</h3>
	<div class="feature-section two-col">
	<div class="col">
		<h4>
			<?php esc_html_e( 'A Growing List of Add-ons', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php esc_html_e( 'Add-on plugins are available that greatly extend the default functionality of TMEM Event Management. There are extensions to further automate TMEM Event Management, payment processing and calendar syncronisation.', 'mobile-events-manager' ); ?>
		</p>
	</div>
	<div class="col">
		<h4>
			<?php esc_html_e( 'Visit the Add-ons Store', 'mobile-events-manager' ); ?>
		</h4>
		<p>
			<?php
				printf(
					/* translators: %s: site name */
					esc_html__( '<a href="%s" target="_blank">The Add-ons store</a> has a list of all available extensions, including convenient category filters so you can find exactly what you are looking for.', 'mobile-events-manager' ),
					'https://www.mobileeventsmanager.co.uk/add-ons'
				);
			?>
		</p>
	</div>
</div>
</div>
</div>
		<?php
	} // getting_started_screen

	/**
	 * Parse the TMEM readme.txt file
	 *
	 * @since 2.0.3
	 * @return string $readme HTML formatted readme file
	 */
	public function parse_readme() {
		$file = file_exists( TMEM_PLUGIN_DIR . '/readme.txt' ) ? TMEM_PLUGIN_DIR . '/readme.txt' : null;

		if ( ! $file ) {
			$readme = '<p>' . __( 'No valid changelog was found.', 'mobile-events-manager' ) . '</p>';
		} else {
			$readme = file_get_contents( $file );
			$readme = nl2br( esc_html( $readme ) );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );

			$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
			$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
			$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
			$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
			$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
		}

		return $readme;
	} // parse_readme

	/**
	 * Sends user to the Welcome page on first activation of TMEM as well as each
	 * time TMEM is upgraded to a new version
	 *
	 * @access public
	 * @since 1.3
	 * @return void
	 */
	public function welcome() {
		// Bail if no activation redirect.
		if ( ! get_transient( '_tmem_activation_redirect' ) ) {
			return;
		}

		// Delete the redirect transient.
		delete_transient( '_tmem_activation_redirect' );

		// Bail if activating from network, or bulk.
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$upgrade = get_option( 'tmem_version_upgraded_from' );

		if ( ! $upgrade ) { // First time install.
			wp_safe_redirect( admin_url( 'index.php?page=tmem-getting-started' ) );
			exit;
		} else { // Update.
			wp_safe_redirect( admin_url( 'index.php?page=tmem-about' ) );
			exit;
		}
	} // welcome
}
new TMEM_Welcome();
