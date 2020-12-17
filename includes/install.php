<?php
/**
 * Install Function
 *
 * @package TMEM
 * @subpackage Functions/Install
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * creates the plugin pages and populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the TMEM Welcome
 * screen.
 *
 * @since 1.3
 * @global $wpdb
 * @param bool $network_wide If the plugin is being network-activated
 * @return void
 */
function tmem_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {

		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {

			switch_to_blog( $blog_id );
			tmem_run_install();
			restore_current_blog();

		}
	} else {

		tmem_run_install();

	}

} // tmem_install
register_activation_hook( TMEM_PLUGIN_FILE, 'tmem_install' );

/**
 * Execute the install procedures
 *
 * @since 1.3
 * @param
 * @return void
 */
function tmem_run_install() {

	global $tmem_options, $wpdb;

	$current_version = get_option( 'tmem_version' );
	if ( $current_version ) {
		return;
	}

	// Setup custom post types.
	tmem_register_post_types();

	// Setup custom post statuses.
	tmem_register_post_statuses();

	// Setup custom taxonomies.
	tmem_register_taxonomies();

	// Clear the permalinks.
	flush_rewrite_rules( false );

	// Setup some default options.
	$options = array();

	// Pull options from WP, not TMEM's global.
	$current_options = get_option( 'tmem_settings', array() );

	// Checks if the Client Zone page option exists.
	if ( ! array_key_exists( 'app_home_page', $current_options ) ) {

		// Client Zone Home Page.
		$client_zone = wp_insert_post(
			array(
				'post_title'     => __( 'Client Zone', 'mobile-events-manager' ),
				'post_content'   => '[tmem-home]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);

		// User Profile Page.
		$profile = wp_insert_post(
			array(
				'post_title'     => __( 'Your Details', 'mobile-events-manager' ),
				'post_content'   => '[tmem-profile]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'post_parent'    => $client_zone,
				'comment_status' => 'closed',
			)
		);

		// Event Contract Page.
		$contract = wp_insert_post(
			array(
				'post_title'     => sprintf( esc_html__( '%s Contract', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'post_content'   => '[tmem-contract]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'post_parent'    => $client_zone,
				'comment_status' => 'closed',
			)
		);

		// Payments Page.
		$payments = wp_insert_post(
			array(
				'post_title'     => __( 'Payments', 'mobile-events-manager' ),
				'post_content'   => '[tmem-payments]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'post_parent'    => $client_zone,
				'comment_status' => 'closed',
			)
		);

		// Playlist Management Page.
		$playlist = wp_insert_post(
			array(
				'post_title'     => __( 'Playlist Management', 'mobile-events-manager' ),
				'post_content'   => '[tmem-playlist]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'post_parent'    => $client_zone,
				'comment_status' => 'closed',
			)
		);

		// Event Quotes Page.
		$quotes = wp_insert_post(
			array(
				'post_title'     => sprintf( esc_html__( '%s Quotes', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
				'post_content'   => '[tmem-quote]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'post_parent'    => $client_zone,
				'comment_status' => 'closed',
			)
		);

		// Store the page IDs in TMEM options.
		$options['app_home_page']  = $client_zone;
		$options['contracts_page'] = $contract;
		$options['payments_page']  = $payments;
		$options['playlist_page']  = $playlist;
		$options['profile_page']   = $profile;
		$options['quotes_page']    = $quotes;

	}

	// Create the default email and contract templates.
	// Checks if the enquiry template option exists.
	if ( ! array_key_exists( 'enquiry', $current_options ) ) {

		$enquiry = wp_insert_post(
			array(
				'post_title'     => __( 'Client Enquiry', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h1>' . __( 'Your DJ Enquiry from {company_name}', 'mobile-events-manager' ) . '</h1>' .
									__( 'Dear {client_firstname},', 'mobile-events-manager' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for contacting {company_name} regarding your up and coming %s on {event_date}.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) .
									'<br /><br />' .
									__( 'I am pleased to tell you that we are available and would love to provide the disco for you.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'To provide a disco from {start_time} to {end_time} our cost would be {total_cost}. There are no hidden charges.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'My standard package includes a vast music collection and great lighting. In addition I would stay in regular contact with you to ensure the night goes to plan. I can incorporate your own playlists, a few songs you want played, requests on the night, or remain in full control of the music - this is your decision, but I can be as flexible as required.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'Mobile DJs are required to have both PAT and PLI (Portable Appliance Testing and Public Liability Insurance). Confirmation of both can be provided.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'If you have any further questions, or would like to go ahead and book, please let me know by return.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'I hope to hear from you soon.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'Best Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{employee_fullname}' .
									'<br /><br />' .
									__( 'Email:', 'mobile-events-manager' ) . ' <a href="mailto:{employee_email}">{employee_email}</a>' .
									'<br />' .
									__( 'Tel:', 'mobile-events-manager' ) . ' {employee_primary_phone}' .
									'<br />' .
									'<a href="{website_url}">{website_url}</a>',
			)
		);

		$online_enquiry = wp_insert_post(
			array(
				'post_title'     => __( 'Default Online Quote', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => sprintf( '[caption id="" align="alignleft" width="128"]%2$s[/caption]', '{website_url}', '{company_name}' ) .
									'<h3>' . sprintf( esc_html__( '%1$s Quotation for %2$s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), '{client_fullname}' ) . '</h3>' .
									'<pre>' . sprintf( esc_html__( 'Prepared by: %s', 'mobile-events-manager' ), '{employee_fullname}' ) .
									'<br />' .
									__( 'Date:', 'mobile-events-manager' ) . ' {DDMMYYYY}' .
									'<br />' .
									__( 'Valid for: 2 weeks from date', 'mobile-events-manager' ) .
									'</pre><br />' .
									sprintf( esc_html__( 'Dear %s,', 'mobile-events-manager' ), '{client_firstname}' ) .
									'<br />' .
									sprintf( esc_html__( 'It is with pleasure that I am providing you with the following costs for your %1$s on %2$s.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ), '{event_date}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'I hope you find our quotation to your satisfaction. If there is anything you would like to discuss in further detail, please contact me on %1$s or at <a href="mailto: %2$s">%2$s</a>.', 'mobile-events-manager' ), '{employee_primary_phone}', '{employee_email}' ) .
									'<br />' .
									'<table style="font-size: 11px;">' .
									'<tbody>' .
									'<tr>' .
									'<td>' . sprintf( esc_html__( '%s Date:', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ) . '</td>' .
									'<td>{event_date}</td>' .
									'<td>' . sprintf( esc_html__( '%s Type:', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ) . '</td>' .
									'<td>{event_type}</td>' .
									'</tr>' .
									'<tr>' .
									'<td>' . __( 'Start Time:', 'mobile-events-manager' ) . '</td>' .
									'<td>{start_time}</td>' .
									'<td>' . __( 'End Time:', 'mobile-events-manager' ) . '</td>' .
									'<td>{end_time}</td>' .
									'</tr>' .
									'<tr>' .
									'<td>' . __( 'Selected Package:', 'mobile-events-manager' ) . '</td>' .
									'<td>{event_package}</td>' .
									'<td>' . __( 'Add-ons:', 'mobile-events-manager' ) . '</td>' .
									'<td>{event_addons}</td>' .
									'</tr>' .
									'<tr>' .
									'<td>' . __( 'Venue Details:', 'mobile-events-manager' ) . '</td>' .
									'<td colspan="3">{venue_full_address}</td>' .
									'</tr>' .
									'<tr>' .
									'<td colspan="4">' .
									'<hr />' .
									'</td>' .
									'</tr>' .
									'<tr style="font-weight: bold;">' .
									'<td colspan="2">' . sprintf( esc_html__( '%s Cost:', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ) . '</td>' .
									'<td colspan="2">{total_cost}</td>' .
									'</tr>' .
									'<tr style="font-weight: bold;">' .
									'<td colspan="2">' . __( 'Booking Fee:', 'mobile-events-manager' ) . '</td>' .
									'<td colspan="2">{DEPOSIT} <span style="font-size: 9px;">(' . __( 'due at time of booking', 'mobile-events-manager' ) . ')</span></td>' .
									'</tr>' .
									'</tbody>' .
									'</table>' .
									'<span style="color: #cccccc; font-size: 9px;"><a style="color: #cccccc;" href="#">' . __( 'Click here to view our list of terms and conditions', 'mobile-events-manager' ) . '</a></span>',
			)
		);

		$contract = wp_insert_post(
			array(
				'post_title'     => __( 'Client Contract Review', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h2>' . sprintf( esc_html__( 'Your Booking with %s', 'mobile-events-manager' ), '{company_name}' ) . '</h2>' .
									sprintf( esc_html__( 'Dear %s,', 'mobile-events-manager' ), '{client_firstname}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for indicating that you wish to proceed with booking %1$s for your up and coming %2$s on %3$s', 'mobile-events-manager' ), '{company_name}', esc_html( tmem_get_label_singular( true ) ), '{event_date}' ) .
									'<br /><br />' .
									__( 'There are two final tasks to complete before your booking can be confirmed...', 'mobile-events-manager' ) .
									'<br />' .
									'<ul>' .
									'<li><strong>' . __( 'Review and accept your contract', 'mobile-events-manager' ) . '</strong><br />' .
									sprintf( esc_html__( 'Your contract has now been produced. You can review it by <a href="%s">clicking here</a>. Please review the terms and accept the contract. If you would prefer the contract to be emailed to you, please let me know by return email.', 'mobile-events-manager' ), '{contract_url}' ) . '</li>' .
									'<li><strong>' . __( 'Pay your deposit', 'mobile-events-manager' ) . '</strong><br />' .
									sprintf( esc_html__( 'Your deposit of <strong>%1$s</strong> is now due. If you have not already done so please make this payment now. Details of how to make this payment are shown within the <a href="%2$s">contract</a>', 'mobile-events-manager' ), '{deposit}', '{contract_url}' ) . '</li>' .
									'</ul><br />' .
									__( 'Once these actions have been completed you will receive a further email confirming your booking.', 'mobile-events-manager' ) .
									'<br /><br />' .
									__( 'Meanwhile if you have any questions, please do not hesitate to get in touch.', 'mobile-events-manager' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for choosing %s', 'mobile-events-manager' ), '{company_name}' ) .
									'<br /><br />' .
									__( 'Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{company_name}' .
									'<br />' .
									'<a href="{website_url}">{website_url}</a>',
			)
		);

		$booking_conf_client = wp_insert_post(
			array(
				'post_title'     => __( 'Client Booking Confirmation', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h1>' . __( 'Booking Confirmation', 'mobile-events-manager' ) . '</h1>' .
									sprintf( esc_html__( 'Dear %s,', 'mobile-events-manager' ), '{client_firstname}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for booking your up and coming %1$s with %2$s. Your booking is now confirmed.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), '{company_name}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'My name is %1$s and I will be your DJ on %2$s. Should you wish to contact me at any stage to discuss your %3$s, my details are at the end of this email.', 'mobile-events-manager' ), '{employee_fullname}', '{event_date}', esc_html( tmem_get_label_singular( true ) ) ) .
									'<br />' .
									'<h2>' . __( 'What Now?', 'mobile-events-manager' ) . '</h2>' .
									'<br />' .
									'<strong>' . __( 'Music Selection & Playlists', 'mobile-events-manager' ) . '</strong>' .
									'<br /><br />' .
									sprintf( esc_html__( 'We have an online portal where you can add songs that you would like to ensure we play during your disco. To access this feature, head over to the %1$s <a href="%2$s">%2$s</a>. The playlist feature will close %3$s days before your %4$s.', 'mobile-events-manager' ), '{company_name}', '{application_home}', '{playlist_close}', esc_html( tmem_get_label_singular( true ) ) ) .
									'<br /><br />' .
									__( 'You will need to login. Your username and password have already been sent to you in a previous email but if you no longer have this information, click on the lost password link and enter your user name, which is your email address. Instructions on resetting your password will then be sent to you.', 'mobile-events-manager' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'You can also invite your guests to add songs to your playlist by providing them with your unique playlist URL - <a href="%1$s">%1$s</a>. We recommend creating a <a href="https://www.facebook.com/events/">Facebook Events Page</a> and sharing the link on there. Alternatively of course, you can email the URL to your guests.', 'mobile-events-manager' ), '{playlist_url}' ) .
									'<br /><br />' .
									__( "Don\'t worry though, you have full control over your playlist so you can remove songs added by your guests if you do not like their choices.", 'mobile-events-manager' ) .
									'<br /><br />' .
									'<strong>' . __( 'When will you next hear from me?', 'mobile-events-manager' ) . '</strong>' .
									'<br /><br />' .
									sprintf( esc_html__( 'I generally contact you again approximately 2 weeks before your %s to finalise details with you. However, if you have any questions, concerns, or just want a general chat about your disco, feel free to contact me at any time.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thanks again for choosing %1$s to provide the DJ & Disco for your %2$s. I look forward to partying with you on %3$s.', 'mobile-events-manager' ), '{company_name}', esc_html( tmem_get_label_singular( true ) ), '{event_date}' ) .
									'<br /><br />' .
									__( 'Best Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{employee_fullname}' .
									'<br /><br />' .
									__( 'Email:', 'mobile-events-manager' ) . ' <a href="mailto:{employee_email}">{employee_email}</a>' .
									'<br />' .
									__( 'Tel:', 'mobile-events-manager' ) . ' {employee_primary_phone}' .
									'<br />' .
									'<a href="{website_url}">{website_url}</a>',
			)
		);

		$email_dj_confirm = wp_insert_post(
			array(
				'post_title'     => __( 'DJ Booking Confirmation', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h1>' . __( 'Booking Confirmation', 'mobile-events-manager' ) . '</h1>' .
									sprintf( esc_html__( 'Dear %s,', 'mobile-events-manager' ), '{employee_firstname}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Your client %1$s has just confirmed their booking for you to DJ at their %2$s on %3$s.', 'mobile-events-manager' ), '{client_fullname}', esc_html( tmem_get_label_singular( true ) ), '{event_date}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'A booking confirmation email has been sent to them and they now have your contact details and access to the online %s tools to create playlist entries etc.', 'mobile-events-manager' ), '{application_name}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Make sure you login regularly to the <a href="%1$s">%2$s %3$s admin interface</a> to ensure you have all relevant information relating to their booking.', 'mobile-events-manager' ), admin_url(), '{company_name}', '{application_name}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Retmember it is your responsibility to remain in regular contact with your client regarding their %1$s as well as answer any queries or concerns they may have. Customer service is one of our key selling points and after the event, your client will be invited to provide feedback regarding the booking process, communication in the lead up to the %1$s, as well as the %1$s itself.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) .
									'<br />' .
									'<h2>' . sprintf( esc_html__( '%s Details', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ) . '</h2>' .
									'<br />' .
									__( 'Client Name: ', 'mobile-events-manager' ) . '{client_fullname}' . '<br />' .
									__( 'Event Date: ', 'mobile-events-manager' ) . '{event_date}' . '<br />' .
									__( 'Type: ', 'mobile-events-manager' ) . '{event_type}' . '<br />' .
									__( 'Start Time: ', 'mobile-events-manager' ) . '{start_time}' . '<br />' .
									__( 'Finish Time: ', 'mobile-events-manager' ) . '{end_time}' . '<br />' .
									__( 'Venue: ', 'mobile-events-manager' ) . '{venue}' . '<br />' .
									__( 'Balance Due: ', 'mobile-events-manager' ) . '{balance}' . '<br />' .
									'<br />' .
									sprintf( esc_html__( 'Further information is available on the <a href="%1$s">%2$s %3$s admin interface</a>.', 'mobile-events-manager' ), admin_url(), '{company_name}', '{application_home}' ) .
									'<br /><br />' .
									__( 'Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{company_name}',
			)
		);

		$unavailable = wp_insert_post(
			array(
				'post_title'     => sprintf( esc_html__( '%s is not Available', 'mobile-events-manager' ), '{company_name}' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h1>' . sprintf( esc_html__( 'Your DJ Enquiry with %s', 'mobile-events-manager' ), '{company_name}' ) . '</h1>' .
									sprintf( esc_html__( 'Dear %s', 'mobile-events-manager' ), '{client_firstname}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for contacting %1$s regarding your up and coming %2$s on %3$s.', 'mobile-events-manager' ), '{company_name}', esc_html( tmem_get_label_singular( true ) ), '{event_date}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Unfortunately however, we are not available on the date you have selected for your %s.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) .
									__( "If you have alternative dates you are looking at, we'd love to hear from you again.", 'mobile-events-manager' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Otherwise, we hope you have a great %s and hope to hear from you again next time.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ) .
									'<br /><br />' .
									__( 'Best Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{employee_fullname}' .
									'<br /><br />' .
									__( 'Email:', 'mobile-events-manager' ) . ' <a href="mailto:{employee_email}">{employee_email}</a>' .
									'<br />' .
									__( 'Tel:', 'mobile-events-manager' ) . ' {employee_primary_phone}' .
									'<br />' .
									'<a href="{website_url}">{website_url}</a>',
			)
		);

		$payment_cfm = wp_insert_post(
			array(
				'post_title'     => sprintf( esc_html__( '%1$s %2$s Payment Confirmation', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), '{payment_for}' ),
				'post_status'    => 'publish',
				'post_type'      => 'email_template',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h4><span style="color: #ff9900;">' . sprintf( esc_html__( 'Thank you for your %s payment', 'mobile-events-manager' ), '{payment_for}' ) . '</span></h4>' .
									sprintf( esc_html__( 'Dear %s,', 'mobile-events-manager' ), '{client_firstname}' ) .
									'<br /><br />' .
									sprintf( esc_html__( 'Thank you for your recent payment of <strong>%1$s</strong> towards the <strong>%2$s</strong> for you event on <strong>%3$s</strong>. Your payment has been received and your %4$s details have been updated.', 'mobile-events-manager' ), '{payment_amount}', '{payment_for}', '{event_date}', esc_html( tmem_get_label_singular( true ) ) ) .
									'<br /><br />' .
									sprintf( esc_html__( 'You can view your event details and manage your playlist by logging onto our <a title="%1$s %2$s" href="%3$s">%2$s</a> event management system.', 'mobile-events-manager' ), '{company_name}', '{application_name}', '{application_home}' ) .
									'<br /><br />' .
									sprintf( esc_html__( "Your username is %1\$s and if you can't recall your password, you can reset it by clicking the <a title='Reset your password for the %2\$s %3\$s' href='%4\$s'>Lost Password</a> link.", 'mobile-events-manager' ), '{client_username}', '{company_name}', '{application_name}', wp_lostpassword_url() ) .
									'<br /><br />' .
									__( 'Best Regards', 'mobile-events-manager' ) .
									'<br /><br />' .
									'{employee_fullname}' .
									'<br /><br />' .
									__( 'Email:', 'mobile-events-manager' ) . ' <a href="mailto:{employee_email}">{employee_email}</a>' .
									'<br />' .
									__( 'Tel:', 'mobile-events-manager' ) . ' {employee_primary_phone}' .
									'<br />' .
									'<a href="{website_url}">{website_url}</a>',
			)
		);

		$default_contract = wp_insert_post(
			array(
				'post_title'     => __( 'General', 'mobile-events-manager' ),
				'post_status'    => 'publish',
				'post_type'      => 'contract',
				'post_author'    => 1,
				'ping_status'    => 'closed',
				'comment_status' => 'closed',
				'post_content'   => '<h2 style="text-align: center;"><span style="text-decoration: underline;">Confirmation of Booking</span></h2><h3>Agreement Date: <span style="color: #ff0000;">{DDMMYYYY}</span></h3>This document sets out the terms and conditions verbally agreed by both parties and any non-fulfilment of the schedule below may render the defaulting party liable to damages.This agreement is between: <strong>{COMPANY_NAME}</strong> (hereinafter called the Artiste)and:<strong>{CLIENT_FULLNAME}</strong> (hereinafter called the Employer)<strong>of</strong><address><strong>{CLIENT_FULL_ADDRESS}{CLIENT_EMAIL}{CLIENT_PRIMARY_PHONE}</strong> </address><address> </address><address>in compliance with the schedule set out below.</address><h3 style="text-align: center;"><span style="text-decoration: underline;">Schedule</span></h3>It is agreed that the Artiste shall appear for the performance set out below for a total inclusive fee of <span style="color: #ff0000;"><strong>{TOTAL_COST}</strong></span>.Payment terms are: <strong><span style="color: #ff0000;">{DEPOSIT}</span> Deposit</strong> to be returned together with this form followed by <strong>CASH ON COMPLETION</strong> for the remaining balance of <strong><span style="color: #ff0000;">{BALANCE}</span>. </strong>Cheques will only be accepted by prior arrangement.Deposits can be made via bank transfer to the following account or via cheque made payable to <strong>XXXXXX</strong> and sent to the address at the top of this form.<strong>Bank Transfer Details: Name XXXXXX | Acct No. 10000000 | Sort Code | 30-00-00</strong><strong>The confirmation of this booking is secured upon receipt of the signed contract and any stated deposit amount</strong>.<h3 style="text-align: center;"><span style="text-decoration: underline;">Venue and Event</span></h3><table border="0" width="100%" cellspacing="0" cellpadding="0"><tbody><tr><td align="center"><table border="0" width="75%" cellspacing="0" cellpadding="0"><tbody><tr><td style="border-bottom-width: thin; border-bottom-style: solid; border-bottom-color: #000; border-right-width: thin; border-right-style: solid; border-right-color: #000;" width="33%"><strong>Address</strong></td><td style="border-bottom-width: thin; border-bottom-style: solid; border-bottom-color: #000; border-right-width: thin; border-right-style: solid; border-right-color: #000;" width="33%"><strong>Telephone Number</strong></td><td style="border-bottom-width: thin; border-bottom-style: solid; border-bottom-color: #000;" width="33%"><strong>Date</strong></td></tr><tr><td style="border-right-width: thin; border-right-style: solid; border-right-color: #000;" valign="top" width="33%"><span style="color: #ff0000;"><strong>{VENUE_FULL_ADDRESS}</strong></span></td><td style="border-right-width: thin; border-right-style: solid; border-right-color: #000;" valign="top" width="33%"><span style="color: #ff0000;"><strong>{VENUE_TELEPHONE}</strong></span></td><td valign="top" width="33%"><span style="color: #ff0000;"><strong>{EVENT_DATE}</strong></span></td></tr></tbody></table></td></tr></tbody></table>The Artiste will perform between the times of <span style="color: #ff0000;"><strong>{START_TIME}</strong></span> to <span style="color: #ff0000;"><strong>{END_TIME}</strong></span>. Any additional time will be charged at £50 per hour or part of.<hr /><h2 style="text-align: center;"> Terms &amp; Conditions</h2><ol>	<li>This contract may be cancelled by either party, giving the other not less than 28 days prior notice.</li>	<li>If the Employer cancels the contract in less than 28 days’ notice, the Employer is required to pay full contractual fee, unless a mutual written agreement has been made by the Artiste and Employer.</li>	<li>Deposits are non-refundable, unless cancellation notice is issued by the Artiste or by prior written agreement.</li>	<li>This contract is not transferable to any other persons/pub/club without written permission of the Artiste.</li>	<li>Provided the Employer pays the Artiste his full contractual fee, he may without giving any reason, prohibit the whole or any part of the Artiste performance.</li>	<li>Whilst all safeguards are assured the Artiste cannot be held responsible for any loss or damage, out of the Artiste’s control during any performance whilst on the Employers premises.</li>	<li>The Employer is under obligation to reprimand or if necessary remove any persons being repetitively destructive or abusive to the Artiste or their equipment.</li>	<li>It is the Employer’s obligation to ensure that the venue is available 90 minutes prior to the event start time and 90 minutes from event completion.</li>	<li>The venue must have adequate parking facilities and accessibility for the Artiste and his or her equipment.</li>	<li>The Artiste reserves the right to provide an alternative performer to the employer for the event. Any substitution will be advised in writing at least 7 days before the event date and the performer is guaranteed to be able to provide at least the same level of service as the Artiste.</li>	<li>Failing to acknowledge and confirm this contract 28 days prior to the performance date does not constitute a cancellation, however it may render the confirmation unsafe. If the employer does not acknowledge and confirm the contract within the 28 days, the Artiste is under no obligation to confirm this booking.</li>	<li>From time to time the Artiste, or a tmember of their crew, may take photographs of the performance. These photographs may include individuals attending the event. If you do not wish for photographs to be taken or used publicly such as on the Artiste’s websites or other advertising media, notify the Artiste in writing.</li></ol>',
			)
		);

		$options['enquiry']                     = $enquiry;
		$options['online_enquiry']              = $online_enquiry;
		$options['contract']                    = $contract;
		$options['booking_conf_client']         = $booking_conf_client;
		$options['email_dj_confirm']            = $email_dj_confirm;
		$options['unavailable']                 = $unavailable;
		$options['payment_cfm_template']        = $payment_cfm;
		$options['manual_payment_cfm_template'] = $payment_cfm;
		$options['default_contract']            = $default_contract;

	}

	// Setup default client fields.
	$client_fields = array(
		'first_name' => array(
			'label'    => __( 'First Name', 'mobile-events-manager' ),
			'id'       => 'first_name',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '0',
		),
		'last_name'  => array(
			'label'    => __( 'Last Name', 'mobile-events-manager' ),
			'id'       => 'last_name',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '1',
		),
		'user_email' => array(
			'label'    => __( 'Email Address', 'mobile-events-manager' ),
			'id'       => 'user_email',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '2',
		),
		'address1'   => array(
			'label'    => __( 'Address 1', 'mobile-events-manager' ),
			'id'       => 'address1',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '3',
		),
		'address2'   => array(
			'label'    => __( 'Address 2', 'mobile-events-manager' ),
			'id'       => 'address2',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '0',
			'desc'     => '',
			'default'  => '1',
			'position' => '4',
		),
		'town'       => array(
			'label'    => __( 'Town / City', 'mobile-events-manager' ),
			'id'       => 'town',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '5',
		),
		'county'     => array(
			'label'    => __( 'County', 'mobile-events-manager' ),
			'id'       => 'county',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',

			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '6',
		),
		'postcode'   => array(
			'label'    => __( 'Post Code', 'mobile-events-manager' ),
			'id'       => 'postcode',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',

			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '7',
		),
		'phone1'     => array(
			'label'    => __( 'Primary Phone', 'mobile-events-manager' ),
			'id'       => 'phone1',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'required' => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '8',
		),
		'phone2'     => array(
			'label'    => __( 'Alternative Phone', 'mobile-events-manager' ),
			'id'       => 'phone2',
			'type'     => 'text',
			'value'    => '',
			'checked'  => '0',
			'display'  => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '9',
		),
		'birthday'   => array(
			'label'    => __( 'Birthday', 'mobile-events-manager' ),
			'id'       => 'birthday',
			'type'     => 'dropdown',
			'value'    => __( 'January', 'mobile-events-manager' ) . "\r\n" .
					__( 'February', 'mobile-events-manager' ) . "\r\n" .
					__( 'March', 'mobile-events-manager' ) . "\r\n" .
					__( 'April', 'mobile-events-manager' ) . "\r\n" .
					__( 'May', 'mobile-events-manager' ) . "\r\n" .
					__( 'June', 'mobile-events-manager' ) . "\r\n" .
					__( 'July', 'mobile-events-manager' ) . "\r\n" .
					__( 'August', 'mobile-events-manager' ) . "\r\n" .
					__( 'September', 'mobile-events-manager' ) . "\r\n" .
					__( 'October', 'mobile-events-manager' ) . "\r\n" .
					__( 'November', 'mobile-events-manager' ) . "\r\n" .
					__( 'December', 'mobile-events-manager' ),
			'checked'  => '0',
			'display'  => '1',
			'desc'     => '',
			'default'  => '1',
			'position' => '10',
		),
		'marketing'  => array(
			'label'    => __( 'Marketing Info', 'mobile-events-manager' ) . '?',
			'id'       => 'marketing',
			'type'     => 'checkbox',
			'value'    => '1',
			'checked'  => ' checked',
			'display'  => '1',
			'desc'     => __( 'Do we add the user to the mailing list', 'mobile-events-manager' ) . '?',
			'default'  => '1',
			'position' => '11',
		),
	);

	// Populate some default values.
	foreach ( tmem_get_registered_settings() as $tab => $sections ) {
		foreach ( $sections as $section => $settings ) {

			// Check for backwards compatibility.
			$tab_sections = tmem_get_settings_tab_sections( $tab );

			if ( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
				$section  = 'main';
				$settings = $sections;
			}

			foreach ( $settings as $option ) {

				if ( 'checkbox' === $option['type'] && ! empty( $option['std'] ) ) {
					$options[ $option['id'] ] = '1';
				} elseif ( ! empty( $option['std'] ) ) {
					$options[ $option['id'] ] = $options[ $option['std'] ];
				}
			}
		}
	}

	$options['employee_pay_status'] = array( 'tmem-completed' );

	$merged_options = array_merge( $tmem_options, $options );
	$tmem_options   = $merged_options;

	update_option( 'tmem_settings', $merged_options );
	update_option( 'tmem_version', TMEM_VERSION_NUM );
	update_option( 'tmem_client_fields', $client_fields );

	// Setup scheduled tasks.
	TMEM()->cron->create_tasks();

	// Create taxonomy terms.
	// Event Types.
	wp_insert_term( __( '16th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '18th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '21st Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '30th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '40th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '50th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '60th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( '70th Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Anniversary Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Child Birthday Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Corporate Event', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Engagement Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Halloween Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'New Years Eve Party', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Other', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'School Disco', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'School Prom', 'mobile-events-manager' ), 'event-types' );
	wp_insert_term( __( 'Wedding', 'mobile-events-manager' ), 'event-types' );

	// Enquiry Sources.
	wp_insert_term( __( 'Business Card', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Email', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Facebook', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Flyer', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Google', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Other', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Telephone', 'mobile-events-manager' ), 'enquiry-source' );
	wp_insert_term( __( 'Website', 'mobile-events-manager' ), 'enquiry-source' );

	// Playlist Terms.
	wp_insert_term( __( 'General', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'First Dance', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Second Dance', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Last Song', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Father & Bride', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Mother & Son', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'DO NOT PLAY', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Other', 'mobile-events-manager' ), 'playlist-category' );
	wp_insert_term( __( 'Guest', 'mobile-events-manager' ), 'playlist-category' );

	// Transaction Terms.
	wp_insert_term(
		__( 'Deposit', 'mobile-events-manager' ),
		'transaction-types',
		array(
			'description' => __( 'Event deposit payments are assigned to this term', 'mobile-events-manager' ),
			'slug'        => 'tmem-deposit-payments',
		)
	);
	wp_insert_term(
		__( 'Balance', 'mobile-events-manager' ),
		'transaction-types',
		array(
			'description' => __( 'Event balance payments are assigned to this term', 'mobile-events-manager' ),
			'slug'        => 'tmem-balance-payments',
		)
	);
	wp_insert_term( __( 'Certifications', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term(
		__( 'Employee Wages', 'mobile-events-manager' ),
		'transaction-types',
		array(
			'description' => __( 'All employee wage payments are assigned to this term', 'mobile-events-manager' ),
			'slug'        => 'tmem-employee-wages',
		)
	);
	wp_insert_term( __( 'Hardware', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term( __( 'Insurance', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term( __( 'Maintenance', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term(
		__( 'Merchant Fees', 'mobile-events-manager' ),
		'transaction-types',
		array(
			'description' => __( 'Charges from payment gateways are assigned to this term', 'mobile-events-manager' ),
			'slug'        => 'tmem-merchant-fees',
		)
	);
	wp_insert_term( __( 'Music', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term(
		__( 'Other Amount', 'mobile-events-manager' ),
		'transaction-types',
		array(
			'description' => __( 'Term used for payments that are a contribution towards balance', 'mobile-events-manager' ),
			'slug'        => 'tmem-other-amount',
		)
	);
	wp_insert_term( __( 'Parking', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term( __( 'Petrol', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term( __( 'Software', 'mobile-events-manager' ), 'transaction-types' );
	wp_insert_term( __( 'Vehicle', 'mobile-events-manager' ), 'transaction-types' );

	// Venue Terms.
	wp_insert_term( __( 'Low Ceiling', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Venue has a low ceiling', 'mobile-events-manager' ) ) );
	wp_insert_term( __( 'PAT Required', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Venue requires a copy of the PAT certificate', 'mobile-events-manager' ) ) );
	wp_insert_term( __( 'PLI Required', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Venue requires proof of PLI', 'mobile-events-manager' ) ) );
	wp_insert_term( __( 'Smoke/Fog Allowed', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Venue allows the use of Smoke/Fog/Haze', 'mobile-events-manager' ) ) );
	wp_insert_term( __( 'Sound Limiter', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Venue has a sound limiter', 'mobile-events-manager' ) ) );
	wp_insert_term( __( 'Via Stairs', 'mobile-events-manager' ), 'venue-details', array( 'description' => __( 'Access to this Venue is via stairs', 'mobile-events-manager' ) ) );

	// Create the custom TMEM User Roles.
	$roles = new TMEM_Roles();
	$roles->add_roles();

	// Make all admins TMEM employees and admins by default by assigning caps to the user directly.
	$administrators = get_users( array( 'role' => 'administrator' ) );

	$permissions = new TMEM_Permissions();

	foreach ( $administrators as $user ) {
		update_user_meta( $user->ID, '_tmem_event_staff', true );
		update_user_meta( $user->ID, '_tmem_event_admin', true );
		$user->add_role( 'dj' );
		$user->add_cap( 'tmem_employee' );
		$permissions->make_admin( $user->ID );
	}

	// Assign the TMEM employee cap to the DJ role.
	$role = get_role( 'dj' );
	$role->add_cap( 'tmem_employee' );

	// Create the new database tables.
	$availability_db = TMEM()->availability_db;
	if ( ! $availability_db->table_exists( $availability_db->table_name ) ) {
		@$availability_db->create_table();
	}

	$availability_meta_db = TMEM()->availability_meta_db;
	if ( ! $availability_meta_db->table_exists( $availability_meta_db->table_name ) ) {
		@$availability_meta_db->create_table();
	}

	if ( ! $current_version ) {
		require_once TMEM_PLUGIN_DIR . '/includes/admin/upgrades/upgrade-functions.php';

		// When new upgrade routines are added, mark them as complete on fresh install.
		$upgrade_routines = array(
			'upgrade_event_packages',
			'upgrade_event_tasks',
			'upgrade_event_pricing_15',
			'upgrade_availability_db_156',
		);

		foreach ( $upgrade_routines as $upgrade ) {
			tmem_set_upgrade_complete( $upgrade );
		}
	}

	// Add the transient to redirect.
	set_transient( '_tmem_activation_redirect', true, 30 );

} // tmem_run_install

/**
 * Run during plugin deactivation.
 *
 * Clear the scheduled hook for hourly tasks.
 *
 * @since 1.3
 * @param
 * @return void
 */
function tmem_deactivate() {
	TMEM()->cron->unschedule_events();
} // tmem_deactivate.
register_deactivation_hook( TMEM_PLUGIN_FILE, 'tmem_deactivate' );
