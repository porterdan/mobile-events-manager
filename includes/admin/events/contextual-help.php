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
 * Events contextual help.
 *
 * @since 1.3
 * @return void
 */
function mem_events_contextual_help() {
	$screen = get_current_screen();

	if ( 'mem-event' !== $screen->id ) {
		return;
	}

	$singular = esc_html( mem_get_label_singular() );
	$plural   = esc_html( mem_get_label_plural() );

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'mobile-events-manager' ) . '</strong></p>' .
		'<p>' . sprintf(
			__( 'Visit the <a href="%s">documentation</a> on the Mobile Events Manager (MEM) website.', 'mobile-events-manager' ),
			esc_url( 'http://mobile-events-manager.co.uk/support/' )
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
			esc_url( 'http://mobile-events-manager.co.uk/add-ons/' )
		) . '</p>'
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-options',
			'title'   => sprintf( esc_html__( '%s Options', 'mobile-events-manager' ), $singular ),
			'content' =>
			'<p>' . sprintf(
				__( '<strong>%1$s Status</strong> - Set the status of this %2$s. An description of each status can be found <a href="%3$s" target="_blank">here</a>', 'mobile-events-manager' ),
				$singular,
				strtolower( $singular ),
				'http://mobile-events-manager.co.uk/docs/event-statuses/'
			) . '</p>' .
			 '<p>' . __( '<strong>Email Quote Template</strong> - During transition to <strong>Enquiry</strong> status, select which quote email template should be sent to the client.', 'mobile-events-manager' ) . '</p>' .
			 '<p>' . __( '<strong>Online Quote Template</strong> - During transition to <strong>Enquiry</strong> status, select which quote template should be used to generate the page that displays the online quote.', 'mobile-events-manager' ) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>%1$s Paid?</strong> - Select this option if the client has paid their %1$s.', 'mobile-events-manager' ),
				mem_get_deposit_label()
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>%1$s Paid?</strong> - Select this option if the client has paid their %1$s.', 'mobile-events-manager' ),
				mem_get_balance_label()
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Enable %1$s Playlist?</strong> - Toggle whether or not the client can manage the playlist for this %2$s.', 'mobile-events-manager' ),
				$singular,
				strtolower( $singular )
			) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-settings-client-details',
			'title'   => __( 'Client Details', 'mobile-events-manager' ),
			'content' =>
				'<p>' . sprintf( esc_html__( "Select a client for this %s. If the client does not exist, you can select <em>Add New Client</em>. In doing so, additional fields will be displayed enabling you to enter the new client's details.", 'mobile-events-manager' ), strtolower( $singular ) ) . '</p>' .
				'<p>' . __( '<strong>Disable Client Update Emails?</strong> - Selecting this option will stop any emails being sent to the client during update.', 'mobile-events-manager' ) . '</p>' .
				'<p>' . __( "<strong>Reset Client Password</strong> - If selected whilst transitioning to enquiry status, the client's password will be reset. If you insert the <code>{client_password}</code> content tag into your email template, the password will be inserted.", 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-details',
			'title'   => sprintf( esc_html__( '%s Details', 'mobile-events-manager' ), $singular ),
			'content' =>
			'<p>' . sprintf(
				__( '<strong>%1$s Type</strong> - Set the type of %2$s <em>i.e. Wedding or 40th Birthday</em>. You can define the types <a href="%3$s">here</a> or simply click the <i class="fa fa-plus"></i> icon to add a new %2$s type inline.', 'mobile-events-manager' ),
				$singular,
				strtolower( $singular ),
				admin_url( 'edit-tags.php?taxonomy=event-types&post_type=mem-event' )
			) . '</p>' .
				'<p>' . sprintf( esc_html__( '<strong>%1$s Contract</strong> - Select the contract associated with this %2$s.', 'mobile-events-manager' ), $singular, strtolower( $singular ) ) . '</p>' .
				'<p>' . sprintf( esc_html__( '<strong>%1$s Name</strong> - Assign a name for this %2$s. Can be viewed and adjusted by the client.', 'mobile-events-manager' ), $singular, strtolower( $singular ) ) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>%1$s Date</strong> - Use the datepicker to set the date for this %2$s.', 'mobile-events-manager' ),
				$singular,
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Start Time</strong> - Set the start time of the %s', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>End Time</strong> - Set the end time of the %s', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Total Cost</strong> - Enter the total cost of the %s. If using equipment packages and add-ons, selecting these will automatically set this cost.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>%1$s</strong> - Enter the %1$s that needs to be collected for this %2$s upon contract signing. This field can be auto populated depending on your settings and equipment packages and add-on selections', 'mobile-events-manager' ),
				mem_get_deposit_label(),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Select an %1$s Package</strong> - If packages are enabled and defined you can assign one to the %1$s here. Doing so will auto update the <em>Total Cost</em> and <em>%2$s</em> fields. Additionally, the <em>Select Add-ons</em> options will be updated to exclude add-ons included within the selected package.', 'mobile-events-manager' ),
				$singular,
				mem_get_deposit_label()
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Select Add-ons</strong> - If packages are enabled you can assign add-ons to your %1$s here. The <em>Total Cost</em> and <em>%2$s</em> fields will be updated automatically to reflect the new costs.', 'mobile-events-manager' ),
				strtolower( $singular ),
				mem_get_deposit_label()
			) . '</p>' .
				'<p>' . __( '<strong>Notes</strong> - Information entered here will be visible to the client and all event employees.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-employees',
			'title'   => sprintf( esc_html__( '%s Employees', 'mobile-events-manager' ), $singular ),
			'content' =>
			'<p>' . sprintf(
				__( '<strong>Select Primary Employee</strong> - Select the primary employee for this %s.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( 'Employees that are assigned to the %1$s are listed here together with their role and wage. You can add additional employees to the %1$s, select their %1$s role and allocate their wages.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-venue-details',
			'title'   => __( 'Venue Details', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( 'Select the venue from the drop down. If the venue does not exist, you can specify it manually by selecting <em>Enter Manually</em> and completing the additional fields that are then displayed.', 'mobile-events-manager' ) . '</p>' .
				'<p>' . __( 'Check the <em>Save this Venue</em> option to save the venue.', 'mobile-events-manager' ) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-transactions',
			'title'   => __( 'Transactions', 'mobile-events-manager' ),
			'content' =>
			'<p>' . sprintf(
				__( 'This section allows you to add transactions associated with the %1$s as well as listing existing associated transactions.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( 'If transactions already exist, the total amount of income and expenditure is displayed as well as the total overall earnings so far for the %1$s.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>',
		)
	);

	$screen->add_help_tab(
		array(
			'id'      => 'mem-event-administration',
			'title'   => __( 'Administration', 'mobile-events-manager' ),
			'content' =>
				'<p>' . __( '<strong>Enquiry Source</strong> - Select how the client heard about your business', 'mobile-events-manager' ) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Setup Date</strong> - Use the datepicker to select the date that you need to setup for this %s.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Setup Time</strong> - Select the time that you need to setup for this %s.', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>' .
				'<p>' . __( '<strong>Employee Notes</strong> - Enter notes that are only visible by employees. Clients will not see these notes', 'mobile-events-manager' ) . '</p>' .
			'<p>' . sprintf(
				__( '<strong>Admin Notes</strong> - Enter notes that are only visible by admins. Employees and clients will not see these notes', 'mobile-events-manager' ),
				strtolower( $singular )
			) . '</p>',
		)
	);

	do_action( 'mem_events_contextual_help', $screen );
}
add_action( 'load-post.php', 'mem_events_contextual_help' );
add_action( 'load-post-new.php', 'mem_events_contextual_help' );
