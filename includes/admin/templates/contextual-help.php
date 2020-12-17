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
 * Contracts contextual help.
 *
 * @since 1.3
 * @return void
 */
function tmem_contract_contextual_help() {
	$screen = get_current_screen();

	if ( 'contract' !== $screen->id ) {
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

	do_action( 'tmem_pre_contract_contextual_help', $screen );

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-contract-add',
			'title'   => __( 'Add New Template', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your contract. A good title is short but descriptive of the type of contract.', 'mobile-events-manager' ) . '</p>' .
				'<p>' . __( '<strong>Content</strong> - Enter the content for your template. HTML, images, and TMEM content tags are supported. Use the TMEM button on the content editor toolbar for easy access to the content tags.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-contract-save',
			'title'   => __( 'Save Contract', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( "Save a draft if you've still got content to add, click preview to see what your contract looks like when formatted and click Save Contract when you are ready to publish.", 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-contract-details',
			'title'   => sprintf( esc_html__( '%s Details', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
			'content' =>
				'<p>' . sprintf( esc_html__( 'Displays general information regarding this contract such as Author, whether it is the default contract used for %1$s, and the number of %1$s it is assigned to. Enter a description if necessary to describe the type of contract and for which type of %1$s it should be used. The description will not be seen by clients.', 'mobile-events-manager' ), tmem_get_label_plural( true ) ) . '</p>',
		)
	);

	do_action( 'tmem_post_contract_contextual_help', $screen );
}
add_action( 'load-post.php', 'tmem_contract_contextual_help' );
add_action( 'load-post-new.php', 'tmem_contract_contextual_help' );

/**
 * Email Templates contextual help.
 *
 * @since 1.3
 * @return void
 */
function tmem_email_template_contextual_help() {
	$screen = get_current_screen();

	if ( 'email_template' !== $screen->id ) {
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

	do_action( 'tmem_pre_email_template_contextual_help', $screen );

	$screen->add_help_tab(
		array(
			'id'      => 'tmem-email-template-add',
			'title'   => __( 'Add New Template', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( '<strong>Title</strong> - Enter a title for your email template. A good title is short but descriptive. Retmember that the title is also used as the email subject.', 'mobile-events-manager' ) . '</p>' .
				'<p>' . __( '<strong>Content</strong> - Enter the content for your email template. HTML, images, and TMEM content tags are supported. Use the TMEM button on the content editor toolbar for easy access to the content tags.', 'mobile-events-manager' ) . '</p>' .
				'<p>' . __( "<strong>Publish</strong> - Save a draft if you've still got content to add, click preview to see what your email template looks like when formatted and click Publish when you are finished editing.", 'mobile-events-manager' ) . '</p>',
		)
	);

	do_action( 'tmem_post_email_template_contextual_help', $screen );
}
add_action( 'load-post.php', 'tmem_email_template_contextual_help' );
add_action( 'load-post-new.php', 'tmem_email_template_contextual_help' );
