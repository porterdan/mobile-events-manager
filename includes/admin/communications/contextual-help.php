<?php
/**
 * Contextual Help
 *
 * @package MEM
 * @subpackage Admin/Settings
 * @copyright Copyright (c) 2020 Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Communications contextual help.
 *
 * @since 1.3
 * @return void
 */
function mem_comms_email_contextual_help() {

	$screen = get_current_screen();

	if ( 'mem-event_page_mem-comms' !== $screen->id ) {
		return;
	}

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'mobile-events-manager' ) . '</strong></p>' .
		'<p>' . sprintf(
			/* translators: %s: MEM website */
			__( 'Visit the <a href="%s">documentation</a> on the Mobile Events Manager (MEM) website.', 'mobile-events-manager' ),
			esc_url( 'http://mem.co.uk/support/' )
		) . '</p>' .
		'<p>' . sprintf(
			/* translators: %s: MEM website */
			__( 'Join our <a href="%s">Facebook Group</a>.', 'mobile-events-manager' ),
			esc_url( 'https://www.facebook.com/groups/mobiledjmanager/' )
		) . '</p>' .
		'<p>' . sprintf(
			/* translators: %1: MEM website %2: GitHub Website */
			__( '<a href="%1$s">Post an issue</a> on <a href="%2$s">GitHub</a>.', 'mobile-events-manager' ),
			esc_url( 'https://github.com/mem/mobile-events-manager/issues' ),
			esc_url( 'https://github.com/mem/mobile-events-manager/' )
		) . '</p>' .
		'<p>' . sprintf(
			/* translators: %s: MEM website */
			__( 'View <a href="%s">add-ons</a>.', 'mobile-events-manager' ),
			esc_url( 'http://mem.co.uk/add-ons/' )
		) . '</p>'
	);

	do_action( 'mem_pre_comms_email_contextual_help', $screen );

	$screen->add_help_tab(
		array(
			'id'      => 'mem-comm-email',
			'title'   => __( 'Communications', 'mobile-events-manager' ),
			'content' =>
			'<p>' . sprintf(
				/* translators: %1: Artiste type */
				__( '<strong>Select a Recipient</strong> - Choose from the dropdown list who your email is to. Users are grouped into Clients and Employees. Once you have selected a recipient the Associated %1$s list will be updated with their active %2$s. This is a required field.', 'mobile-events-manager' ),
				esc_html( mem_get_label_plural() ),
				mem_get_label_plural( true )
			) . '</p>' .
			'<p>' . __( '<strong>Subject</strong> - Enter the subject of your email. If you select a template the subject will be updated to the title of the template. This is a required field.', 'mobile-events-manager' ) . '</p>' .
			'<p>' . __( '<strong>Copy Yourself?</strong> - Select this option if you wish to receive a copy of the email. If the settings options have been enabled to copy Admin and/or Employee into Client emails, you may receive a copy regardless of whether or not this option is selected.', 'mobile-events-manager' ) . '</p>' .
			'<p>' . __( '<strong>Select a Template</strong> - Choose a pre-defined email or contract template to populate the content field. Anything you have already entered into the content field will be overwritten. If you do not select a template, you will need to manually enter content into the content field.', 'mobile-events-manager' ) . '</p>' .
			'<p>' . sprintf(
				/* translators: %1: Event type %2: Date */
				__( '<strong>Associated %1</strong> - If the Client or Employee you have selected within the <strong>Select a Recipient</strong> field has active %1 it is displayed here. Select it to tell MEM that the email you are sending is associated to this %1 and %2$s content tags can be used within the email content.', 'mobile-events-manager' ),
				esc_html( mem_get_label_singular() ),
				mem_get_label_plural( true ),
				esc_html( mem_get_label_singular( true ) )
			) . '</p>' .
			'<p>' . __( '<strong>Attach a File</strong> - Enables you to select a file from your computer to the email.', 'mobile-events-manager' ) . '</p>' .
			'<p>' . __( '<strong>Content</strong> - If you have selected a template within the <strong>Select a Template</strong> field, this field will be populated with that templates content. You can adjust this content as required. Alternatively, if no template is selected, use this as a free text field for your email content. Content tags are supported and can be entered via the <strong>MEM</strong> button on the text editor toolbar. Remember this field is resizeable. Drag from the bottom right hand corner to make bigger if necessary. This is a required field.', 'mobile-events-manager' ) . '</p>',
		)
	);

	do_action( 'mem_post_comms_email_contextual_help', $screen );

} // mem_comms_email_contextual_help
add_action( 'load-mem-event_page_mem-comms', 'mem_comms_email_contextual_help' );
