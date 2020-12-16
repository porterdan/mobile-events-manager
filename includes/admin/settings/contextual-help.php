<?php
/**
 * Contextual Help
 *
 * @package MEM
 * @subpackage Admin/Settings
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
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
function mem_settings_contextual_help() {
	$screen = get_current_screen();

	if ( 'mem-event_page_mem-settings' !== $screen->id ) {
		return;
	}

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'mobile-events-manager' ) . '</strong></p>' .
		'<p>' . sprintf(
			__( 'Visit the <a href="%s">documentation</a> on the Mobile Events Manager (MEM) website.', 'mobile-events-manager' ),
			esc_url( 'http://mem.co.uk/support/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( 'Join our <a href="%s">Facebook Group</a>.', 'mobile-events-manager' ),
			esc_url( 'https://www.facebook.com/groups/mobiledjmanager/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%1$s">Post an issue</a> on <a href="%2$s">GitHub</a>.', 'mobile-events-manager' ),
			esc_url( 'https://github.com/mem/mobile-events-manager/issues' ),
			esc_url( 'https://github.com/mem/mobile-events-manager/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( 'View <a href="%s">add-ons</a>.', 'mobile-events-manager' ),
			esc_url( 'http://mem.co.uk/add-ons/' )
		) . '</p>'
	);

	do_action( 'mem_pre_settings_contextual_help', $screen );

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-general',
			'title'   => __( 'General', 'mobile-events-manager' ),
			'content' => '<p>' . __( 'This screen provides the most basic settings for configuring MEM. Set your company name and preferred date and time format.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-events',
			'title'   => esc_html( mem_get_label_plural() ),
			'content' =>
			'<p>' . sprintf(
				__( 'This screen enables to you configure options %1$s and playlists. Select your %1$s default contract template, whether or not you are an employer and enable equipment packages.', 'mobile-events-manager' ) . '</p>' .
				 '<p>' . __( 'You can also toggle playlists on or off, select when a playlist should close choose whether or not to upload your playlists to the MEM servers.', 'mobile-events-manager' ),
				mem_get_label_plural( true )
			) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-emails-templates',
			'title'   => __( 'Emails &amp; Templates', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( 'This screen allows you to adjust options for emails, toggle on or off the email tracking feature and select which templates to use as content for emails.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-client-zone',
			'title'   => mem_get_application_name(),
			'content' => '<p>' . sprintf( esc_html__( 'This screen allows you to configure settings associated with the %s as well as set various pages and configure the Availability Checker.', 'mobile-events-manager' ), mem_get_application_name() ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-payments',
			'title'   => __( 'Payments', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( 'This screen allows you to configure the payment settings. Specify your currency, format currency display, set default deposits and select whether or not to apply tax.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-extensions',
			'title'   => __( 'Extensions', 'mobile-events-manager' ),
			'content' => '<p>' . __( 'This screen provides access to settings added by most Mobile Events Manager (MEM) extensions.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-licenses',
			'title'   => __( 'Licenses', 'mobile-events-manager' ),
			'content' =>
			'<p>' . sprintf(
				__( 'If you have any <a href="%s">Mobile Events Manager (MEM) paid add-ons</a> installed, this screen is where you should add the license to enable automatic updates whilst your license is valid.', 'mobile-events-manager' ),
				esc_url( 'http://mem.co.uk/add-ons/' )
			) . '</p>',
		)
	);

	do_action( 'mem_post_settings_contextual_help', $screen );
}
add_action( 'load-mem-event_page_mem-settings', 'mem_settings_contextual_help' );
