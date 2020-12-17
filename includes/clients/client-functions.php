<?php
/**
 * Contains all client related functions
 *
 * @package TMEM
 * @subpackage Users/Clients
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve a list of all clients
 *
 * @param str|arr $roles Optional: The roles for which we want to retrieve the clients from.
 * @param int     $employee Optional: Only display clients of the given employee.
 * @param str     $orderby Optional: The field by which to order. Default display_name.
 * @param str     $order Optional: ASC (default) | Desc.
 * @return $arr $clients or false if no clients for the specified roles
 */
function tmem_get_clients( $roles = array( 'client', 'inactive_client' ), $employee = false, $orderby = 'display_name', $order = 'ASC' ) {

	// We'll work with an array of roles.
	if ( ! empty( $roles ) && ! is_array( $roles ) ) {
		$roles = array( $roles );
	}

	$client_args = apply_filters(
		'tmem_get_clients_args',
		array(
			'role__in' => $roles,
			'orderby'  => $orderby,
			'order'    => $order,
		)
	);

	$all_clients = get_users( $client_args );

	// If we are only quering an employee's client, we need to filter.
	if ( $employee ) {
		foreach ( $all_clients as $client ) {

			if ( ! TMEM()->users->is_employee_client( $client->ID, $employee ) ) {
				continue;
			}

			$clients[] = $client;
		}

		if ( empty( $clients ) ) {
			return false;
		}

		$all_clients = $clients;
	}

	$clients = $all_clients;

	return $clients;
} // tmem_get_clients

/**
 * Returns a count of clients.
 *
 * @since 1.4
 * @param bool $inactive True to include inactive clients, false to ignore.
 * @return int Client count.
 */
function tmem_client_count( $inactive = true ) {
	$roles = array( 'client' );

	if ( $inactive ) {
		$roles[] = 'inactive_client';
	}

	$args = array(
		'role__in'    => $roles,
		'count_total' => true,
	);

	$clients = new WP_User_Query( $args );

	return $clients->get_total();

} // tmem_client_count

/**
 * Retrieve the client ID from the event
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @return $arr $employees or false if no employees for the specified roles
 */
function tmem_get_client_id( $event_id ) {
	return tmem_get_event_client_id( $event_id );
} // tmem_get_client_id

/**
 * Adds a new client.
 *
 * We assume that $data is passed from the $_POST super global but $user_data can be passed.
 *
 * @since 1.3
 * @param arr $user_data Array of client data. See $defaults.
 * @return int|false $user_id User ID of the new client or false on failure.
 */
function tmem_add_client( $user_data = array() ) {

	$first_name = ( ! empty( $_POST['client_firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['client_firstname'] ) ) : '' );
	$last_name  = ( ! empty( $_POST['client_lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['client_lastname'] ) ) : '' );
	$email      = ( ! empty( $_POST['client_email'] ) ? sanitize_email( wp_unslash( $_POST['client_email'] ) ) : '' );
	$phone      = ( ! empty( $_POST['client_phone'] ) ? preg_replace( '/[^0-9]/', '', sanitize_text_field( wp_unslash( $_POST['client_phone'] ) ) ) : '' );

	$defaults = array(
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'user_email'   => $email,
		'user_pass'    => wp_generate_password( tmem_get_option( 'pass_length' ) ),
		'role'         => 'client',
		'client_phone' => $phone,
	);

	$defaults['display_name'] = sanitize_text_field( $defaults['first_name'] ) . ' ' . sanitize_text_field( $defaults['last_name'] );
	$defaults['nickname']     = $defaults['display_name'];
	$defaults['user_login']   = is_email( $defaults['user_email'] );

	$user_data = wp_parse_args( $user_data, $defaults );

	do_action( 'tmem_pre_add_client' );

	$user_id = wp_insert_user( $user_data );

	if ( is_wp_error( $user_id ) ) {

		if ( TMEM_DEBUG === true ) {
			TMEM()->debug->log_it( 'Error creating user: ' . $user_id->get_error_message(), true );
		}

		return false;
	}

	$user_meta = array(
		'first_name'           => sanitize_text_field( $user_data['first_name'] ),
		'last_name'            => sanitize_text_field( $user_data['last_name'] ),
		'show_admin_bar_front' => false,
		'marketing'            => 'Y',
		'phone1'               => isset( $user_data['client_address1'] ) ? sanitize_text_field( $user_data['client_phone'] ) : '',
		'phone2'               => isset( $user_data['client_phone2'] ) ? preg_replace( '/[^0-9]/', '', $user_data['client_phone2'] ) : '',
		'address1'             => isset( $user_data['client_address1'] ) ? sanitize_textarea_field( $user_data['client_address1'] ) : '',
		'address2'             => isset( $user_data['client_address2'] ) ? sanitize_textarea_field( $user_data['client_address2'] ) : '',
		'town'                 => isset( $user_data['client_town'] ) ? sanitize_text_field( $user_data['client_town'] ) : '',
		'county'               => isset( $user_data['client_county'] ) ? sanitize_text_field( $user_data['client_county'] ) : '',
		'postcode'             => isset( $user_data['client_postcode'] ) ? sanitize_text_field( $user_data['client_postcode'] ) : '',
	);

	$user_meta = apply_filters( 'tmem_add_client_meta_data', $user_meta );

	foreach ( $user_meta as $key => $value ) {
		update_user_meta( $user_id, $key, $value );
	}

	do_action( 'tmem_post_add_client', $user_id );

	return $user_id;

} // tmem_add_client

/**
 * Retrieve all of this clients events.
 *
 * @param int $client_id Optional The WP userID of the client. Default to current user.
 * @param arr $status Event Status.
 * @param arr $orderby Event Date.
 * @param arr $order order.
 * str|arr $status Optional: Status of events that should be returned. Default any.
 * str $orderby Optional: The field by which to order. Default event date.
 * str $order Optional: DESC (default) | ASC
 *
 * @return mixed $events WP_Post objects or false.
 */
function tmem_get_client_events( $client_id = '', $status = 'any', $orderby = 'event_date', $order = 'ASC' ) {
	$args = apply_filters(
		'tmem_get_client_events_args',
		array(
			'post_type'      => 'tmem-event',
			'post_status'    => $status,
			'posts_per_page' => -1,
			'meta_key'       => '_tmem_' . $orderby,
			'orderby'        => 'meta_value_num',
			'order'          => $order,
			'meta_query'     => array(
				array(
					'key'     => '_tmem_event_client',
					'value'   => ! empty( $client_id ) ? $client_id : get_current_user_id(),
					'compare' => 'IN',
				),
			),
		)
	);

	$events = tmem_get_events( $args );

	return $events;
} // tmem_get_client_events

/**
 * Retrieve the client's next event.
 *
 * @since 1.3
 * @param int $client_id The user ID for the client.
 * @return WP_Post object for clients next event, or false
 */
function tmem_get_clients_next_event( $client_id = '' ) {

	$client_id = ! empty( $client_id ) ? $client_id : get_current_user_id();

	$args = array(
		'post_status'    => array( 'tmem-approved', 'tmem-contract', 'tmem-enquiry', 'tmem-unattended' ),
		'posts_per_page' => 1,
		'meta_key'       => '_tmem_event_date',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'   => '_tmem_event_client',
				'value' => $client_id,
			),
			array(
				'key'     => '_tmem_event_date',
				'value'   => gmdate( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'date',
			),
		),
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	);

	$next_event = tmem_get_events( $args );

	return apply_filters( 'tmem_get_clients_next_event', $next_event );

} // tmem_get_clients_next_event

/**
 * Check whether the user is a client.
 *
 * @since 1.3
 * @param int $client_id The ID of the user to check.
 * @return bool True if user has the client role, or false.
 */
function tmem_user_is_client( $client_id ) {
	if ( tmem_get_client_events( $client_id ) ) {
		return true;
	}

	return false;
} // tmem_user_is_client

/**
 * Determine if a client is active.
 *
 * @since 1.3.7
 * @param int $client_id The client user ID.
 * @return bool True if active, otherwise false.
 */
function tmem_is_client_active( $client_id ) {

	$return = false;
	$user   = get_userdata( $client_id );

	if ( $user ) {
		if ( ! in_array( 'inactive_client', $user->roles ) ) {
			return true;
		}
	}

	return apply_filters( 'tmem_is_client_active', $return, $client_id );

} // tmem_is_client_active

/**
 * Activate a client a client if needed.
 *
 * @since 1.3.7
 * @param int $event_id The Event ID.
 * @return void
 */
function tmem_maybe_activate_client( $event_id ) {

	$client_id = tmem_get_event_client_id( $event_id );

	if ( ! empty( $client_id ) ) {
		if ( ! tmem_is_client_active( $client_id ) ) {
			tmem_update_client_status( $client_id );
		}
	}

} // tmem_maybe_activate_client
add_action( 'tmem_pre_update_event_status_tmem-unattended', 'tmem_maybe_activate_client' );
add_action( 'tmem_pre_update_event_status_tmem-enquiry', 'tmem_maybe_activate_client' );
add_action( 'tmem_pre_update_event_status_tmem-contract', 'tmem_maybe_activate_client' );
add_action( 'tmem_pre_update_event_status_tmem-approved', 'tmem_maybe_activate_client' );
add_action( 'tmem_pre_update_event_status_tmem-completed', 'tmem_maybe_activate_client' );

/**
 * Updates a clients status.
 *
 * @since 1.3.7
 * @param int $client_id The client user ID.
 * @param str $status 'active' or 'inactive'.
 * @return void
 */
function tmem_update_client_status( $client_id, $status = 'active' ) {

	if ( 'inactive' === $status ) {
		$role = 'inactive_client';
	} else {
		$role = 'client';
	}

	$user = new WP_User( $client_id );

	$user->set_role( $role );

} // tmem_update_client_status

/**
 * Listen for event status changes and update the client status.
 *
 * @since 1.3.7
 * @param bool $result True if the event status change was successful, false if not.
 * @param int  $event_id Event ID.
 * @return void
 */
function tmem_set_client_status_inactive( $result, $event_id ) {

	if ( ! tmem_get_option( 'set_client_inactive' ) ) {
		return;
	}

	if ( ! $result ) {
		return;
	}

	$client_id = tmem_get_event_client_id( $event_id );

	if ( empty( $client_id ) ) {
		return;
	}

	$next_event = tmem_get_clients_next_event( $client_id );

	if ( ! $next_event ) {
		tmem_update_client_status( $client_id, 'inactive' );
	}

} // tmem_set_client_status_inactive
add_action( 'tmem_post_update_event_status_tmem-cancelled', 'tmem_set_client_status_inactive', 10, 2 );
add_action( 'tmem_post_update_event_status_tmem-failed', 'tmem_set_client_status_inactive', 10, 2 );
add_action( 'tmem_post_update_event_status_tmem-rejected', 'tmem_set_client_status_inactive', 10, 2 );

/**
 * Retrieve a clients login.
 *
 * @since 1.3.8.4
 * @param int $user_id The ID of the user to check.
 * @return str The login ID of the client.
 */
function tmem_get_client_login( $user_id ) {
	$login  = '';
	$client = get_userdata( $user_id );

	if ( $client && ! empty( $client->user_login ) ) {
		$login = sanitize_text_field( $client->user_login );
	}

	return apply_filters( 'tmem_client_login', $login, $user_id );
} // tmem_get_client_login

/**
 * Retrieve a clients first name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The first name of the client.
 */
function tmem_get_client_firstname( $user_id ) {
	$first_name = '';
	$client     = get_userdata( $user_id );

	if ( $client && ! empty( $client->first_name ) ) {
		$first_name = sanitize_text_field( $client->first_name );
	}

	return apply_filters( 'tmem_client_firstname', $first_name, $user_id );
} // tmem_get_client_firstname

/**
 * Retrieve a clients last name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The last name of the client.
 */
function tmem_get_client_lastname( $user_id ) {
	$last_name = '';
	$client    = get_userdata( $user_id );

	if ( $client && ! empty( $client->last_name ) ) {
		$last_name = sanitize_text_field( $client->last_name );
	}

	return apply_filters( 'tmem_client_lastname', $last_name, $user_id );
} // tmem_get_client_lastname

/**
 * Retrieve a clients display name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The display name of the client.
 */
function tmem_get_client_display_name( $user_id ) {
	$display_name = '';
	$client       = get_userdata( $user_id );

	if ( $client && ! empty( $client->display_name ) ) {
		$display_name = sanitize_text_field( $client->display_name );
	}

	return apply_filters( 'tmem_client_display_name', $display_name, $user_id );
} // tmem_get_client_display_name

/**
 * Retrieve a clients email address.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The email address of the client.
 */
function tmem_get_client_email( $user_id ) {
	$client = get_userdata( $user_id );

	if ( $client && ! empty( $client->user_email ) ) {
		$email = strtolower( sanitize_email( $client->user_email ) );
	} else {
		$email = '';
	}

	return apply_filters( 'tmem_client_email', $email, $user_id );
} // tmem_get_client_email

/**
 * Retrieve a clients address.
 *
 * @since 1.4
 * @param int $client_id The ID of the client to check.
 * @return arr The address of the client.
 */
function tmem_get_client_address( $client_id ) {

	$client  = get_userdata( $client_id );
	$address = array();

	if ( ! empty( $client->address1 ) ) {
		$address[] = stripslashes( sanitize_textarea_field( $client->address1 ) );
	}
	if ( ! empty( $client->address2 ) ) {
		$address[] = stripslashes( sanitize_textarea_field( $client->address2 ) );
	}
	if ( ! empty( $client->town ) ) {
		$address[] = stripslashes( sanitize_text_field( $client->town ) );
	}
	if ( ! empty( $client->county ) ) {
		$address[] = stripslashes( sanitize_text_field( $client->county ) );
	}
	if ( ! empty( $client->postcode ) ) {
		$address[] = stripslashes( sanitize_text_field( $client->postcode ) );
	}

	return apply_filters( 'tmem_get_client_address', $address, $client_id );
} // tmem_get_client_address

/**
 * Retrieve the full address of the client.
 *
 * @since 1.3.7
 * @param int $client_id The client ID.
 * @return str The address of the client.
 */
function tmem_get_client_full_address( $client_id ) {

	$address = sanitize_textarea_field( tmem_get_client_address( $client_id ) );

	$address = apply_filters( 'tmem_client_full_address', $address );

	return is_array( $address ) ? implode( '<br />', $address ) : '';
} // tmem_get_client_full_address

/**
 * Retrieve a clients phone number.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The phone number of the client.
 */
function tmem_get_client_phone( $user_id ) {
	$phone  = '';
	$client = get_userdata( $user_id );

	if ( $client && ! empty( $client->phone1 ) ) {
		$phone = preg_replace( '/[^0-9]/', '', $client->phone1 );
	}

	return apply_filters( 'tmem_client_phone', $phone, $user_id );
} // tmem_get_client_phone

/**
 * Retrieve a clients alternative phone number.
 *
 * @since 1.3.8.4
 * @param int $user_id The ID of the user to check.
 * @return str The alternative phone number of the client.
 */
function tmem_get_client_alt_phone( $user_id ) {
	$alt_phone = get_user_meta( $user_id, 'phone2', true );

	return apply_filters( 'tmem_client_alt_phone', $alt_phone, $user_id );
} // tmem_get_client_alt_phone

/**
 * Retrieve a clients last login timestamp.
 *
 * @since 1.3
 * @param int $client_id The ID of the user to check.
 * @return str The phone number of the client.
 */
function tmem_get_client_last_login( $client_id ) {

	$client = get_userdata( $client_id );

	if ( $client && ! empty( $client->last_login ) ) {
		$login = $client->last_login;
	} else {
		$login = __( 'Never', 'mobile-events-manager' );
	}

	return apply_filters( 'tmem_client_last_login', $login, $client_id );
} // tmem_get_client_last_login

/**
 * Retrieve the client fields.
 *
 * @since 1.3
 * @param arr $client_fields Client Fields.
 * @return arr|bool Array of client fields or false.
 */
function tmem_get_client_fields() {

	$client_fields = get_option( 'tmem_client_fields' );
	$client_fields = apply_filters( 'tmem_client_fields', $client_fields );

	return $client_fields;

} // tmem_get_client_fields

/**
 * Whether or not a client field should be displayed.
 *
 * @since 1.5
 * @param array $field The field to query.
 * @return bool True if we should display
 */
function tmem_display_client_field( $field ) {
	return ! empty( $field['display'] ) ? true : false;
} // tmem_display_client_field

/**
 * Outputs a client input field
 *
 * @since 1.5
 * @param array  $field The field to display.
 * @param object $client TMEM_Client object.
 */
function tmem_display_client_input_field( $field, $client ) {

	$id       = $field['id'];
	$label    = esc_attr( $field['label'] );
	$type     = esc_attr( $field['type'] );
	$value    = esc_attr( $client->$id );
	$required = ! empty( $field['required'] ) ? ' required="required"' : '';

	if ( 'user_email' === $id ) {
		$type = 'email';
	}

	switch ( $type ) {
		case 'text':
		default:
			printf(
				'<input name="%1$s" id="%1$s" type="%2$s" value="%3$s"%4$s />',
				$id,
				$type,
				$value,
				$required
			);
			break;

		case 'dropdown':
			$options = explode( "\r\n", $field['value'] );
			printf(
				'<select name="%1$s" id="%1$s">',
				$id
			);

			foreach ( $options as $option ) {
				printf(
					'<option value="%1$s"%2$s>%1$s</option>',
					$option,
					selected( $option, $client->$id, false )
				);
			}

			echo '</select>';
			break;

		case 'checkbox':
			printf(
				'<input name="%1$s" id="%1$s" type="%2$s" value="%3$s"%4$s />',
				$id,
				$type,
				esc_attr( $field['value'] ),
				checked( $field['value'], $client->$id, false )
			);
			break;

	}
} // tmem_display_client_input_field

/**
 * Output the clients details.
 *
 * @since 1.3.7
 * @param int $client_id Client user ID.
 * @param int $event_id Event ID.
 * @return str
 */
function tmem_do_client_details_table( $client_id, $event_id = 0 ) {

	$client = get_userdata( $client_id );

	if ( ! $client ) {
		return;
	}

	?>
	<div id="tmem-event-client-details" class="tmem-hidden">
		<table class="widefat tmem_event_client_details tmem_form_fields">
			<thead>
				<tr>
					/* Translators: %s: Artiste name */
					<th colspan="3"><?php printf( esc_html__( 'Contact Details for %s', 'mobile-events-manager' ), esc_html( $client->display_name ) ); ?>
						<span class="description">(<a href="<?php echo add_query_arg( array( 'user_id' => $client_id ), admin_url( 'user-edit.php' ) ); ?>"><?php esc_html_e( 'edit', 'mobile-events-manager' ); ?></a>)</span></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><i class="fa fa-phone" aria-hidden="true" title="<?php esc_html_e( 'Phone', 'mobile-events-manager' ); ?>"></i>
					<?php
					echo $client->phone1;
					echo '' !== $client->phone2 ? ' / ' . esc_html( $client->phone2 ) : '';
					?>
					</td>

					<td rowspan="3"><?php echo esc_html( tmem_get_client_full_address( $client->ID ) ); ?></td>
				</tr>

				<tr>
					<td><i class="fa fa-envelope-o" aria-hidden="true" title="<?php esc_html_e( 'Email', 'mobile-events-manager' ); ?>"></i>
					<a href="
					<?php
					echo add_query_arg(
						array(
							'recipient' => $client->ID,
							'event_id'  => $event_id,
						),
						admin_url( 'admin.php?page=tmem-comms' )
					);
					?>
								"><?php echo esc_html( $client->user_email ); ?></a></td>
				</tr>

				<tr>
					<td><i class="fa fa-sign-in" aria-hidden="true" title="<?php esc_html_e( 'Last Login', 'mobile-events-manager' ); ?>"></i>
					<?php echo tmem_get_client_last_login( $client_id ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php

} // tmem_do_client_details_table
