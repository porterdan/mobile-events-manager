<?php
/**
 * Contextual Help
 *
 * @package TMEM
 * @subpackage Admin/Settings
 * @copyright Copyright (c) 2015, Mike Howard
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings contextual help.
 *
 * @since 1.3
 * @return void
 */
function tmem_settings_contextual_help() {
	$screen = get_current_screen();

	if ( 'tmem-event_page_tmem-settings' !== $screen->id ) {
		return;
	}

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'mobile-events-manager' ) . '</strong></p>' .
		'<p>' . sprintf(
			__( 'Visit the <a href="%s">documentation</a> on the TMEM Event Management website.', 'mobile-events-manager' ),
			esc_url( 'https://www.mobileeventsmanager.co.uk/support/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( 'Join our <a href="%s">Facebook Group</a>.', 'mobile-events-manager' ),
			esc_url( 'https://www.facebook.com/groups/mobiledjmanager/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%1$s">Post an issue</a> on <a href="%2$s">GitHub</a>.', 'mobile-events-manager' ),
			esc_url( 'https://github.com/tmem/mobile-events-manager/issues' ),
			esc_url( 'https://github.com/tmem/mobile-events-manager/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( 'View <a href="%s">add-ons</a>.', 'mobile-events-manager' ),
			esc_url( 'https://www.mobileeventsmanager.co.uk/add-ons/' )
		) . '</p>'
	);

	do_action( 'tmem_pre_settings_contextual_help', $screen );

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-general',
			'title'   => __( 'General', 'mobile-events-manager' ),
			'content' => '<p>' . __( 'This screen provides the most basic settings for configuring TMEM. Set your company name and preferred date and time format.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-events',
			'title'   => esc_html( tmem_get_label_plural() ),
			'content' =>
			'<p>' . sprintf(
				__( 'This screen enables to you configure options %1$s and playlists. Select your %1$s default contract template, whether or not you are an employer and enable equipment packages.', 'mobile-events-manager' ) . '</p>' .
				 '<p>' . __( 'You can also toggle playlists on or off, select when a playlist should close choose whether or not to upload your playlists to the TMEM servers.', 'mobile-events-manager' ),
				tmem_get_label_plural( true )
			) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-emails-templates',
			'title'   => __( 'Emails &amp; Templates', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( 'This screen allows you to adjust options for emails, toggle on or off the email tracking feature and select which templates to use as content for emails.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-client-zone',
			'title'   => tmem_get_application_name(),
			'content' => '<p>' . sprintf( esc_html__( 'This screen allows you to configure settings associated with the %s as well as set various pages and configure the Availability Checker.', 'mobile-events-manager' ), tmem_get_application_name() ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-payments',
			'title'   => __( 'Payments', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( 'This screen allows you to configure the payment settings. Specify your currency, format currency display, set default deposits and select whether or not to apply tax.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-extensions',
			'title'   => __( 'Extensions', 'mobile-events-manager' ),
			'content' => '<p>' . __( 'This screen provides access to settings added by most TMEM Event Management extensions.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-settings-licenses',
			'title'   => __( 'Licenses', 'mobile-events-manager' ),
			'content' =>
			'<p>' . sprintf(
				__( 'If you have any <a href="%s">TMEM Event Management paid add-ons</a> installed, this screen is where you should add the license to enable automatic updates whilst your license is valid.', 'mobile-events-manager' ),
				esc_url( 'https://www.mobileeventsmanager.co.uk/add-ons/' )
			) . '</p>',
		)
	);

	do_action( 'tmem_post_settings_contextual_help', $screen );
}
add_action( 'load-tmem-event_page_tmem-settings', 'tmem_settings_contextual_help' );
