<?php
/**
 * Contains all Client Zone functions.
 *
 * @package TMEM
 * @subpackage Client Zone
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the application name.
 *
 * @since 1.4.8
 * @return str The application name. Default Client Zone
 */
function tmem_get_application_name() {
	return tmem_get_option( 'app_name', __( 'Client Zone', 'mobile-events-manager' ) );
} // tmem_get_application_name

/**
 * Print the TMEM footer text.
 *
 * @since 1.3
 */
function tmem_show_footer_in_client_zone() {

	if ( tmem_get_option( 'show_credits', false ) ) {
		echo '<div id="tmem-client-zone-footer">';
		echo '<p>';
		/* Translators: %1: Company Name %2: Version */
		printf( esc_html__( 'Powered by <a href="%1$s" target="_blank">TMEM Event Management</a>, version %2$s', 'mobile-events-manager' ), 'https://www.mobileeventsmanager.co.uk', TMEM_VERSION_NUM );

		echo '</p>';
		echo '</div>';
	}

} // tmem_show_footer_in_client_zone
add_action( 'wp_footer', 'tmem_show_footer_in_client_zone' );

/**
 * Remove comments and comments links from the front end for non-admins.
 *
 * @since 1.3
 */
function tmem_no_comments() {

	add_filter( 'get_comments_number', '__return_false' );

	if ( ! current_user_can( 'edit_posts' ) && ( tmem_is_employee() || current_user_can( 'client' ) ) ) {
		add_filter( 'get_edit_post_link', '__return_false' );
	}
}
add_action( 'init', 'tmem_no_comments' );

/**
 * Accept an enquiry.
 *
 * When a client clicks the Book Event button to accept an enquiry
 * transition the event to the awaiting contract status.
 *
 * @since 1.3
 * @param arr $data Data for the transition.
 * @return bool True on succes, otherwise false
 */
function tmem_accept_enquiry( $data ) {

	global $current_user;

	$tmem_event = tmem_get_event( $data['event_id'] );

	if ( ! $tmem_event ) {
		return false;
	}

	do_action( 'tmem_pre_event_accept_enquiry', $tmem_event->ID, $data );

	$data['meta'] = array(
		'_tmem_event_enquiry_accepted'    => current_time( 'mysql' ),
		'_tmem_event_enquiry_accepted_by' => $current_user->ID,
	);

	$data['client_notices'] = tmem_get_option( 'contract_to_client' );

	if ( ! tmem_update_event_status( $tmem_event->ID, 'tmem-contract', $tmem_event->post_status, $data ) ) {
		return false;
	}

	tmem_add_journal(
		array(
			'user'            => get_current_user_id(),
			'event'           => $tmem_event->ID,
			/* Translators: %s: Customer name */
			'comment_content' => sprintf( esc_html__( '%s has accepted their event enquiry', 'mobile-events-manager' ), $current_user->display_name . '<br>' ),
		),
		array(
			'type'       => 'update-event',
			'visibility' => '2',
		)
	);

	$content  = '<html>' . "\n" . '<body>' . "\n";
	$content .= '<p>' . sprintf(
		/* Translators: %1: Customer %2: Event Type %3: Source */
		__( 'Good news... %1$s has just accepted their %2$s quotation via %3$s', 'mobile-events-manager' ),
		'{client_fullname}',
		esc_html( tmem_get_label_singular( true ) ),
		'{application_name}'
	) . '</p>';

	$content .= '<hr />' . "\n";
	$content .= '<h4>' . sprintf(
		/* Translators: %1: Company Name %2: ID %3: Event ID */
		__( '<a href="%1$s">%2$s ID: %3$s</a>', 'mobile-events-manager' ),
		admin_url( 'post.php?post=' . $tmem_event->ID . '&action=edit' ),
		esc_html( tmem_get_label_singular() ),
		'{contract_id}'
	) . '</h4>' . "\n";

	$content .= '<p>' .
		/* Translators: %s: Date */
					sprintf( esc_html__( 'Date: %s', 'mobile-events-manager' ), '{event_date}' ) .
				'<br />' . "\n";
	$content .= __( 'Type', 'mobile-events-manager' ) . ': ' . tmem_get_event_type( $tmem_event->ID ) . '<br />' . "\n";

	$content .= __( 'Status', 'mobile-events-manager' ) . ': ' . tmem_get_event_status( $tmem_event->ID ) . '<br />' . "\n";
	$content .= __( 'Client', 'mobile-events-manager' ) . ': {client_fullname}<br />' . "\n";
	$content .= __( 'Value', 'mobile-events-manager' ) . ': {total_cost}<br />' . "\n";

	$content .= __( 'Deposit', 'mobile-events-manager' ) . ': {deposit} ({deposit_status})<br />' . "\n";

	$content .= __( 'Balance Due', 'mobile-events-manager' ) . ': {balance}</p>' . "\n";

	$content .= '<p>' . sprintf(
		/* Translators: %1: Company Name %2: Quote */
		__( '<a href="%1$s">View %2$s</a>', 'mobile-events-manager' ),
		admin_url( 'post.php?post=' . $tmem_event->ID . '&action=edit' ),
		esc_html( tmem_get_label_singular() )
	) . '</p>' . "\n";

	$content .= '</body>' . "\n" . '</html>' . "\n";

	$args = array(
		'to_email'  => tmem_get_option( 'system_email' ),
		'event_id'  => $tmem_event->ID,
		'client_id' => $tmem_event->client,
		/* Translators: %s: Company Name */
		'subject'   => sprintf( esc_html__( '%s Quotation Accepted', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		'message'   => $content,
		'copy_to'   => 'disable',
	);

	tmem_send_email_content( $args );

	do_action( 'tmem_post_event_accept_enquiry', $tmem_event->ID, $data );

	return true;

} // tmem_accept_enquiry

/**
 * Print out the relevant action buttons for the event.
 *
 * @since 1.3
 * @param int $event_id Event ID.
 * @return str Output the action buttons HTML
 */
function tmem_do_action_buttons( $event_id ) {

	$buttons = tmem_get_event_action_buttons( $event_id, false );
	$cells   = 4;
	$cells   = apply_filters( 'tmem_action_buttons_in_row', $cells );
	$i       = 0;
	$output  = '';

	do_action( 'tmem_pre_event_action_buttons', $event_id );

	if ( empty( $buttons ) ) {
		return false;
	}

	foreach ( $buttons as $button ) {
		if ( 0 === $i ) {
			$output .= '<div class="row">' . "\n";
		}

		$output .= '<div class="col three">' . "\n";

		$output .= sprintf(
			'<a href="%s" class="btn btn-%s"><i class="%s"></i> %s</a>',
			$button['url'],
			tmem_get_option( 'action_button_colour', 'blue' ),
			isset( $button['fa'] ) ? $button['fa'] : '',
			$button['label']
		);

		$i++;

		$output .= '</div>'; // <div class="tmem-action-btn-col three">

		if ( $i === $cells ) {
			$output .= '</div>'; // <div class="tmem-action_btn-row">
			$i       = 0;
		}
	}

	$output .= '</div>';

	do_action( 'tmem_post_event_action_buttons', $event_id );

	return apply_filters( 'tmem_do_action_buttons', $output, $event_id );

} // tmem_do_action_buttons

/**
 * Return all relevant action buttons for the event.
 *
 * Allow filtering of the buttons so they can be re-ordered, re-named etc.
 *
 * @since 1.3
 * @param int  $event_id The event ID.
 * @param bool $min True returns only minimal action buttons used within loop.
 * @return arr Array of event action buttons.
 */
function tmem_get_event_action_buttons( $event_id, $min = true ) {
	$event_status = get_post_status( $event_id );
	$buttons      = array();

	// Buttons for events in enquiry state.
	if ( 'tmem-enquiry' === $event_status ) {
		if ( ( tmem_get_option( 'online_enquiry', '0' ) ) ) {
			$buttons[5] = apply_filters(
				'tmem_quote_action_button',
				array(
					'label' => __( 'View Quote', 'mobile-events-manager' ),
					'id'    => 'tmem-quote-button',
					'fa'    => 'fa fa-file',
					'url'   => add_query_arg(
						'event_id',
						$event_id,
						tmem_get_formatted_url( tmem_get_option( 'quotes_page' ), true )
					),
				)
			);
		}

		$buttons[10] = apply_filters(
			'tmem_book_action_button',
			array(
				/* Translators: %s: Company name */
				'label' => sprintf( esc_html__( 'Book %s', 'mobile-events-manager' ), tmem_get_label_singular() ),
				'id'    => 'tmem-book-button',
				'fa'    => 'fa fa-check',
				'url'   => add_query_arg(
					array(
						'tmem_action' => 'accept_enquiry',
						'tmem_nonce'  => wp_create_nonce( 'accept_enquiry' ),
					),
					tmem_get_event_uri( $event_id )
				),
			)
		);
	}

	// Buttons for events in awaiting contract state.
	if ( 'tmem-contract' === $event_status ) {
		$buttons[15] = apply_filters(
			'tmem_sign_contract_action_button',
			array(
				'label' => __( 'Sign Contract', 'mobile-events-manager' ),
				'id'    => 'tmem-sign-contract-button',
				'fa'    => 'fa fa-pencil',
				'url'   => add_query_arg(
					'event_id',
					$event_id,
					tmem_get_formatted_url( tmem_get_option( 'contracts_page' ), true )
				),
			)
		);
	}

	// Buttons for events in approved state.
	if ( 'tmem-approved' === $event_status && tmem_contract_is_signed( $event_id ) ) {
		$buttons[20] = apply_filters(
			'tmem_view_contract_action_button',
			array(
				'label' => __( 'View Contract', 'mobile-events-manager' ),
				'id'    => 'tmem-view-contract-button',
				'fa'    => 'fa fa-file-text',
				'url'   => add_query_arg(
					'event_id',
					$event_id,
					tmem_get_formatted_url( tmem_get_option( 'contracts_page' ), true )
				),
			)
		);
	}

	// Playlist action button.
	if ( tmem_playlist_is_open( $event_id ) ) {
		if ( 'tmem-approved' === $event_status || 'tmem-contract' === $event_status ) {
			$buttons[25] = apply_filters(
				'tmem_manage_playlist_action_button',
				array(
					'label' => __( 'Manage Playlist', 'mobile-events-manager' ),
					'id'    => 'tmem-manage-playlist-button',
					'fa'    => 'fa fa-music',
					'url'   => add_query_arg(
						'event_id',
						$event_id,
						tmem_get_formatted_url( tmem_get_option( 'playlist_page' ), true )
					),
				)
			);
		}
	}

	// Payment button.
	if ( tmem_has_gateway() && tmem_get_event_balance( $event_id ) > 0 ) {
		$buttons[30] = apply_filters(
			'tmem_make_payment_button',
			array(
				'label' => __( 'Make a Payment', 'mobile-events-manager' ),
				'id'    => 'tmem-make-a-payment-button',
				'fa'    => 'fa fa-credit-card-alt',
				'url'   => add_query_arg(
					'event_id',
					$event_id,
					tmem_get_formatted_url( tmem_get_option( 'payments_page' ), true )
				),
			)
		);
	}

	if ( empty( $min ) ) {
		$buttons[50] = apply_filters(
			'tmem_update_profile_action_button',
			array(
				'label' => __( 'Update Profile', 'mobile-events-manager' ),
				'id'    => 'tmem-update-profile-button',
				'fa'    => 'fa fa-user',
				'url'   => tmem_get_formatted_url( tmem_get_option( 'profile_page' ), false ),
			)
		);

	}

	$buttons = apply_filters( 'tmem_event_action_buttons', $buttons, $event_id );
	ksort( $buttons );

	return $buttons;
} // tmem_get_event_action_buttons

/**
 * Output the book event button.
 *
 * If you are filtering the tmem_get_action_buttons function you may need to adjust the array key
 * within this function.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param arr $args Arguments for button display. See $defaults.
 * @return str The Book Event button
 */
function tmem_display_book_event_button( $event_id, $args = array() ) {

	if ( 'tmem-enquiry' !== tmem_get_event_status( $event_id ) ) {
		return;
	}

	$buttons = tmem_get_event_action_buttons( $event_id );

	if ( empty( $buttons ) || empty( $buttons[10] ) ) {
		return;
	}

	$book_button = $buttons[10];

	$defaults = array(
		'colour' => tmem_get_option( 'action_button_colour' ),
		'label'  => $book_button['label'],
		'fa'     => 'fa fa-thumbs-o-up',
		'url'    => $book_button['url'],
	);

	$args = wp_parse_args( $args, $defaults );

	$output = sprintf( '<a class="tmem-action-button tmem-action-button-%s" href="%s">%s</a>', $args['colour'], $args['url'], $args['label'] );

	return apply_filters( 'tmem_book_event_button', $output, $event_id, $args );

} // tmem_display_book_event_button
