<?php
/**
 * Functions for privacy
 *
 * @package MEM
 * @subpackage Functions/Privacy
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the privacy page.
 *
 * @since 1.5.3
 * @return int The page ID for the privacy policy
 */
function mem_get_privacy_page() {
	$privacy_page = get_option( 'wp_page_for_privacy_policy' );
	$privacy_page = apply_filters( 'mem_privacy_page', $privacy_page );

	return $privacy_page;
} // mem_get_privacy_page

/**
 * Register the MEM template for a privacy policy.
 *
 * Note, this is just a suggestion and should be customized to meet your businesses needs.
 *
 * @since 1.5.3
 * @return string The MEM suggested privacy policy
 */
function mem_register_privacy_policy_template() {

	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = sprintf(
		__( 'We collect and store information about you during the %1$s creation process on our website. This information may include, but is not limited to, your name, your email address, your address, and any additional details that may be requested from you for the purpose of handling your %1$s.', 'mobile-events-manager' ),
		esc_html( mem_get_label_singular( true ) )
	);

	$content .= "\n\n";

	$content .= __( 'Handling this information also allows us to:', 'mobile-events-manager' );
	$content .= '<ul>';
	$content .= '<li>' . sprintf( esc_html__( 'Plan, manage and deliver your %s efficiently', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) . '</li>';
	$content .= '<li>' . sprintf( esc_html__( 'Send you important information regarding your %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ) . '</li>';
	$content .= '<li>' . __( 'Set up and administer your user account and verify your identity', 'mobile-events-manager' ) . '</li>';
	$content .= '</ul>';

	$content .= "\n\n";

	$additional_collection = array();

	$additional_collection[] = __( 'Your comments and rating reviews if you choose to leave them on our website', 'mobile-events-manager' );

	$additional_collection[] = __( 'Your account email and password to allow you to access your account, if you have one', 'mobile-events-manager' );

	$additional_collection[] = __( 'Important dates, such as your birth, engagement or wedding date due to the nature of our business', 'mobile-events-manager' );

	$additional_collection = apply_filters( 'mem_privacy_policy_additional_collection', $additional_collection );

	if ( ! empty( $additional_collection ) ) {
		$content .= __( 'Additionally we may also collect the following information:', 'mobile-events-manager' );
		$content .= '<ul>';

		foreach ( $additional_collection as $item ) {
			$content .= sprintf( '<li>%s</li>', $item );
		}

		$content .= '</ul>';
	}

	$content = apply_filters( 'mem_privacy_policy_content', $content );
	$content = wp_kses_post( $content );

	wp_add_privacy_policy_content( 'Mobile Events Manager (MEM)', wpautop( $content ) );
} // mem_register_privacy_policy_template
add_action( 'admin_init', 'mem_register_privacy_policy_template' );

/**
 * Given a string, mask it with the * character.
 *
 * First and last character will remain with the filling characters being changed to *. One Character will
 * be left in tact as is. Two character strings will have the first character remain and the second be a *.
 *
 * @since 1.5.3
 * @param string $string
 * @return string Masked string
 */
function mem_mask_string( $string = '' ) {

	if ( empty( $string ) ) {
		return '';
	}

	$first_char = substr( $string, 0, 1 );
	$last_char  = substr( $string, -1, 1 );

	$masked_string = $string;

	if ( strlen( $string ) > 2 ) {

		$total_stars   = strlen( $string ) - 2;
		$masked_string = $first_char . str_repeat( '*', $total_stars ) . $last_char;

	} elseif ( strlen( $string ) === 2 ) {
		$masked_string = $first_char . '*';
	}

	return $masked_string;

} // mem_mask_string

/**
 * Given a domain, mask it with the * character.
 *
 * TLD parts will remain intact (.com, .co.uk, etc). All subdomains will be masked t**t.e*****e.co.uk.
 *
 * @since 1.5.3
 * @param string $domain
 * @return string Masked domain
 */
function mem_mask_domain( $domain = '' ) {

	if ( empty( $domain ) ) {
		return '';
	}

	$domain_parts = explode( '.', $domain );

	if ( count( $domain_parts ) === 2 ) {

		// We have a single entry tld like .org or .com.
		$domain_parts[0] = kbs_mask_string( $domain_parts[0] );

	} else {

		$part_count     = count( $domain_parts );
		$possible_cctld = strlen( $domain_parts[ $part_count - 2 ] ) <= 3 ? true : false;

		$mask_parts = $possible_cctld ? array_slice( $domain_parts, 0, $part_count - 2 ) : array_slice( $domain_parts, 0, $part_count - 1 );

		$i = 0;
		while ( $i < count( $mask_parts ) ) {
			$domain_parts[ $i ] = kbs_mask_string( $domain_parts[ $i ] );
			$i++;
		}
	}

	return implode( '.', $domain_parts );
} // mem_mask_domain

/**
 * Given an email address, mask the name and domain according to domain and string masking functions.
 *
 * Will result in an email address like a***n@e*****e.org for admin@example.org.
 *
 * @since 1.5.3
 * @param string $email_address
 * @return string Masked email address
 */
function mem_pseudo_mask_email( $email_address ) {
	if ( ! is_email( $email_address ) ) {
		return $email_address;
	}

	$email_parts = explode( '@', $email_address );
	$name        = mem_mask_string( $email_parts[0] );
	$domain      = mem_mask_domain( $email_parts[1] );

	$email_address = $name . '@' . $domain;

	return $email_address;
} // mem_pseudo_mask_email

/**
 * Log the privacy and terms timestamp for the last submitted contact from the website for a client.
 *
 * Stores the timestamp of the last time the user submits a form via Dynamic Contact Forms for the
 * Agree to Terms and/or Privacy Policy checkboxes during the submission process.
 *
 * @since 1.5.3
 * @param $event_id The event post ID
 * @param $form_data Array of data submitted with contact form or payment form
 * @return void
 */
function mem_log_terms_and_privacy_times( $event_id, $form_data ) {
	$event = mem_get_event( $event_id );

	if ( empty( $event->client ) ) {
		return;
	}

	$client = get_userdata( $event->client );

	if ( empty( $client->ID ) ) {
		return;
	}

	if ( ! empty( $form_data['form_data']['data']['privacy_accepted'] ) ) {
		update_user_meta( $client->ID, 'agree_to_privacy_time', $form_data['form_data']['data']['privacy_accepted'] );
	}

	if ( ! empty( $form_data['form_data']['data']['terms_agreed'] ) ) {
		update_user_meta( $client->ID, 'agree_to_terms_time', $form_data['form_data']['data']['terms_agreed'] );
	}
} // mem_log_terms_and_privacy_times
add_action( 'mem_dcf_after_create_event', 'mem_log_terms_and_privacy_times', 10, 2 );

/**
 * Capture payment transactions prior to them being sent to the gateway.
 *
 * @since 1.5.3
 * @param array $data Array of $_POST data.
 * @param array $payment_data Array of payment data.
 * @return void
 */
function mem_payment_terms_privacy_log_action( $data, $payment_data ) {
	$form_data = array();
	$event_id  = absint( $payment_data['event_id'] );
	$timestamp = current_time( 'timestamp' );

	if ( isset( $data['mem_agree_privacy_policy'] ) ) {
		$form_data['form_data']['data']['privacy_accepted'] = $timestamp;
	}

	if ( isset( $data['mem_agree_terms'] ) ) {
		$form_data['form_data']['data']['terms_agreed'] = $timestamp;
	}

	mem_log_terms_and_privacy_times( $event_id, $form_data );
} // mem_payment_terms_privacy_log_action
add_action( 'mem_payment_before_gateway', 'mem_payment_terms_privacy_log_action', 10, 2 );

/**
 * Returns an anonymized email address.
 *
 * While WP Core supports anonymizing email addresses with the wp_privacy_anonymize_data function,
 * it turns every email address into deleted@site.invalid, which does not work when some event/client
 * records are still needed for legal and regulatory reasons.
 *
 * This function will anonymize the email with an MD5 that is salted and given a randomized uniqid prefixed
 * with the site URL in order to prevent connecting a single client across multiple sites,
 * as well as the timestamp at the time of anonymization (so it trying the same email again will not be
 * repeatable and therefore connected), and return the email address as <hash>@site.invalid.
 *
 * @since   1.5.3
 * @param   string $email_address  Email address to be anonymized
 * @return  string  Anonymized email address
 */
function mem_anonymize_email( $email_address ) {

	if ( empty( $email_address ) ) {
		return $email_address;
	}

	$email_address    = strtolower( $email_address );
	$email_parts      = explode( '@', $email_address );
	$anonymized_email = wp_hash( uniqid( get_option( 'site_url' ), true ) . $email_parts[0] . current_time( 'timestamp' ), 'nonce' );

	return $anonymized_email . '@site.invalid';
} // mem_anonymize_email

/**
 * Given a user ID, anonymize the data related to that client.
 *
 * Only the client record is affected in this function. The data that is changed:
 * - The name is changed to 'Anonymized Client'
 * - The email address is anonymized, but kept in a format that passes is_email checks
 * - The date created is set to the timestamp of 0 (January 1, 1970)
 * - Notes are fully cleared
 * - Any additional email addresses are removed
 *
 * Once completed, a note is left stating when the client was anonymized.
 *
 * @since 1.5.3
 * @param int $client_id
 * @return array
 */
function _mem_anonymize_client( $client_id = 0 ) {

	$client = get_userdata( $client_id );
	if ( empty( $client->ID ) ) {
		return array(
			'success' => false,
			'message' => sprintf( esc_html__( 'No client with ID %d', 'mobile-events-manager' ), $client_id ),
		);
	}

	/**
	 * Determines if this client should be allowed to be anonymized.
	 *
	 * Developers and extensions can use this filter to make it possible to not anonymize a client.
	 *
	 * @since 1.5.3
	 * @param array {
	 * Contains data related to if the anonymization should take place
	 *
	 * @type bool $should_anonymize If the client should be anonymized.
	 * @type string $message A message to display if the client could not be anonymized.
	 * }
	 */
	$should_anonymize_client = apply_filters(
		'mem_should_anonymize_client',
		array(
			'should_anonymize' => true,
			'message'          => '',
		),
		$client
	);

	if ( empty( $should_anonymize_client['should_anonymize'] ) ) {
		return array(
			'success' => false,
			'message' => $should_anonymize_client['message'],
		);
	}

	delete_user_meta( $client->ID, 'address1' );
	delete_user_meta( $client->ID, 'address2' );
	delete_user_meta( $client->ID, 'town' );
	delete_user_meta( $client->ID, 'county' );
	delete_user_meta( $client->ID, 'postcode' );
	delete_user_meta( $client->ID, 'phone1' );
	delete_user_meta( $client->ID, 'phone2' );

	$anonymized_email = mem_anonymize_email( $client->user_email );

	wp_update_user(
		array(
			'ID'         => $client_id,
			'first_name' => __( 'Anonymized', 'mobile-events-manager' ),
			'last_name'  => __( 'Client', 'mobile-events-manager' ),
			'email'      => $anonymized_email,
		)
	);

	/**
	 * Run further anonymization on a client
	 *
	 * Developers and extensions can use the KBS_Customer object passed into the kbs_anonymize_customer action
	 * to complete further anonymization.
	 *
	 * @since 1.5.3
	 * @param WP_User $client The WP_User object that was found.
	 */
	do_action( 'mem_anonymize_client', $client, $anonymized_email );

	return array(
		'success' => true,
		'message' => sprintf( esc_html__( 'Client ID %d successfully anonymized.', 'mobile-events-manager' ), $customer_id ),
	);

} // _mem_anonymize_client


/**
 * Since our eraser callbacks need to look up a stored client ID by hashed email address,
 * developers can use this to retrieve the customer ID associated with an email address that's being
 * requested to be deleted even after the customer has been anonymized.
 *
 * @since 1.5.3
 * @param $email_address
 * @return KBS_Ticket
 */
function _mem_privacy_get_client_id_for_email( $email_address ) {
	$client_id = get_option( 'mem_priv_' . md5( $email_address ), true );
	$client    = get_userdata( $client_id );

	return $client;
} // _mem_privacy_get_client_id_for_email

/**
 * Register any of our Privacy Data Exporters
 *
 * @since 1.5.3
 * @param $exporters
 * @return array
 */
function mem_register_privacy_exporters( $exporters ) {

	$exporters[] = array(
		'exporter_friendly_name' => __( 'MEM Client Record', 'mobile-events-manager' ),
		'callback'               => 'mem_privacy_client_record_exporter',
	);

	return $exporters;

} // mem_register_privacy_exporters
add_filter( 'wp_privacy_personal_data_exporters', 'mem_register_privacy_exporters' );

/**
 * Retrieves the client record for the Privacy Data Exporter
 *
 * @since 1.5.3
 * @param string $email_address
 * @param int    $page
 * @return array
 */
function mem_privacy_client_record_exporter( $email_address = '', $page = 1 ) {

	$client = get_user_by( 'email', $email_address );

	if ( empty( $client->ID ) ) {
		return array(
			'data' => array(),
			'done' => true,
		);
	}

	$custom_fields = get_option( 'mem_client_fields' );
	$address       = mem_get_client_address( $client->ID );

	// We can exclude some fields as WordPress has these covered for us.
	$exclude = array(
		'ID',
		'first_name',
		'last_name',
		'user_email',
		'marketing',
	);

	$export_data = array(
		'group_id'    => 'mem-client-record',
		'group_label' => __( 'MEM Client Record', 'mobile-events-manager' ),
		'item_id'     => "mem-client-record-{$client->ID}",
		'data'        => array(),
	);

	if ( ! empty( $custom_fields ) ) {
		foreach ( $custom_fields as $custom_field ) {
			if ( in_array( $custom_field['id'], $exclude ) ) {
				continue;
			}

			$value = get_user_meta( $client->ID, $custom_field['id'], true );

			if ( empty( $value ) ) {
				continue;
			}

			$export_data['data'][] = array(
				'name'  => esc_attr( $custom_field['label'] ),
				'value' => esc_attr( $value ),
			);
		}
	}

	$agree_to_privacy_time = get_user_meta( $client->ID, 'agree_to_privacy_time', false );
	if ( ! empty( $agree_to_privacy_time ) ) {
		foreach ( $agree_to_privacy_time as $timestamp ) {
			$export_data['data'][] = array(
				'name'  => __( 'Agreed to Privacy Policy', 'mobile-events-manager' ),
				'value' => date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ),
			);
		}
	}

	$agree_to_terms_time = get_user_meta( $client->ID, 'agree_to_terms_time', false );
	if ( ! empty( $agree_to_terms_time ) ) {
		foreach ( $agree_to_terms_time as $timestamp ) {
			$export_data['data'][] = array(
				'name'  => __( 'Agreed to Terms', 'mobile-events-manager' ),
				'value' => date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ),
			);
		}
	}

	$export_data = apply_filters( 'mem_privacy_client_record', $export_data, $client );

	return array(
		'data' => array( $export_data ),
		'done' => true,
	);
} // mem_privacy_client_record_exporter

/**
 * Render the agree to privacy policy checkbox.
 *
 * @since 1.5.3
 * @return string
 */
function mem_render_agree_to_privacy_policy_field() {
	$agree_to_policy = mem_get_option( 'show_agree_to_privacy_policy', false );
	$privacy_page    = mem_get_privacy_page();
	$label           = mem_get_option( 'agree_privacy_label', false );
	$description     = mem_get_option( 'agree_privacy_descripton', false );

	if ( empty( $agree_to_policy ) || empty( $privacy_page ) || empty( $label ) ) {
		return;
	}

	$privacy_text = get_post_field( 'post_content', $privacy_page );

	if ( empty( $privacy_text ) ) {
		return;
	}

	$label_class = '';
	$input_class = '';

	$args = apply_filters(
		'mem_agree_to_privacy_policy_args',
		array(
			'label_class' => '',
			'input_class' => '',
		)
	);

	if ( ! empty( $args['label_class'] ) ) {
		$label_class = ' ' . sanitize_html_class( $args['label_class'] );
	}

	if ( ! empty( $args['input_class'] ) ) {
		$input_class = ' class="' . sanitize_html_class( $args['input_class'] ) . '"';
	}

	if ( 'thickbox' === mem_get_option( 'show_agree_policy_type' ) ) {
		$privacy_url = sprintf(
			'<a href="#TB_inline?width=600&height=550&inlineId=mem-privacy-policy" class="thickbox"%s title="%s">%s</a>',
			$label_class,
			esc_html( get_the_title( $privacy_page ) ),
			esc_attr( $label )
		);
	} else {
		$privacy_url = sprintf(
			'<a href="%s" target="_blank" title="%s" class="%s">%s</a>',
			get_permalink( $privacy_page ),
			esc_html( get_the_title( $privacy_page ) ),
			$label_class,
			esc_attr( $label )
		);
	}

	ob_start(); ?>

	<p><input type="checkbox" name="mem_agree_privacy_policy" id="mem-agree-privacy-policy"<?php echo $input_class; ?> value="1" /> <?php echo $privacy_url; ?></p>

	<?php if ( ! empty( $description ) ) : ?>
		<span class="mem-description"><?php echo esc_html( $description ); ?></span>
	<?php endif; ?>

	<?php if ( 'thickbox' === mem_get_option( 'show_agree_policy_type' ) ) : ?>
		<div id="mem-privacy-policy" class="mem-hidden">
			<?php do_action( 'mem_before_privacy_policy' ); ?>
			<?php echo wpautop( do_shortcode( stripslashes( $privacy_text ) ) ); ?>
			<?php do_action( 'mem_after_privacy_policy' ); ?>
		</div>
	<?php endif; ?>

	<?php
	echo ob_get_clean();

} // mem_render_agree_to_privacy_policy_field
add_action( 'mem_payment_form_after_cc_form', 'mem_render_agree_to_privacy_policy_field', 950 );

/**
 * Render the agree to terms checkbox.
 *
 * @since 1.5.3
 * @return string
 */
function mem_render_agree_to_terms_field() {
	$agree_to_terms = mem_get_option( 'show_agree_to_terms', false );
	$agree_text     = mem_get_option( 'agree_terms_text', false );
	$label          = mem_get_option( 'agree_terms_label', false );
	$terms_heading  = mem_get_option(
		'agree_terms_heading',
		__( 'Terms and Conditions', 'mobile-events-manager' )
	);

	if ( ! $agree_to_terms || ! $agree_text || ! $label ) {
		return;
	}

	$label_class = '';
	$input_class = '';

	$args = apply_filters(
		'mem_agree_to_terms_args',
		array(
			'label_class' => '',
			'input_class' => '',
		)
	);

	if ( ! empty( $args['label_class'] ) ) {
		$label_class = ' ' . sanitize_html_class( $args['label_class'] );
	}

	if ( ! empty( $args['input_class'] ) ) {
		$input_class = ' class="' . sanitize_html_class( $args['input_class'] ) . '"';
	}

	ob_start();
	?>

	<p><input type="checkbox" name="mem_agree_terms" id="mem-agree-terms"<?php echo $input_class; ?> value="1" /> <a href="#TB_inline?width=600&height=550&inlineId=mem-terms-conditions" title="<?php esc_html_e( $terms_heading, 'mobile-events-manager' ); ?>" class="thickbox"<?php echo $label_class; ?>><?php esc_html_e( $label, 'mobile-events-manager' ); ?></a></p>

	<div id="mem-terms-conditions" class="mem-hidden">
		<?php do_action( 'mem_before_terms' ); ?>
		<?php echo wpautop( stripslashes( $agree_text ) ); ?>
		<?php do_action( 'mem_after_terms' ); ?>
	</div>

	<?php
	echo ob_get_clean();

} // mem_render_agree_to_terms_field
add_action( 'mem_payment_form_after_cc_form', 'mem_render_agree_to_terms_field', 999 );
