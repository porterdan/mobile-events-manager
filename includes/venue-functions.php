<?php
/**
 * Contains all venue related functions
 *
 * @package TMEM
 * @subpackage Venues
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a new venue.
 *
 * @since 1.3
 * @param str $venue_name The name of the venue.
 * @param arr $venue_meta Meta data for the venue.
 * @return int|bool $venue_id Post ID of the new venue or false on failure.
 */
function tmem_add_venue( $venue_name = '', $venue_meta = array() ) {

	if ( ! tmem_employee_can( 'add_venues' ) ) {
		return false;
	}

	if ( empty( $venue_name ) && empty( $_POST['venue_name'] ) ) {
		return false;
	} elseif ( ! empty( $venue_name ) ) {
		$name = $venue_name;
	} else {
		$name = sanitize_text_field( wp_unslash( $_POST['venue_name'] ) );
	}

	$args = array(
		'post_title'   => $name,
		'post_content' => '',
		'post_type'    => 'tmem-venue',
		'post_author'  => get_current_user_id(),
		'post_status'  => 'publish',
	);

	// Remove the save post hook for venue posts to avoid loops.
	remove_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );

	/**
	 * Allow filtering of the venue post data
	 *
	 * @since 1.3
	 * @param arr $args Array of user data
	 */
	$args = apply_filters( 'tmem_add_venue', $args );

	/**
	 * Fire the `tmem_pre_add_venue` action.
	 *
	 * @since 1.3
	 * @param str $name Name of venue
	 * @param arr $venue_meta Array of venue meta data
	 */
	do_action( 'tmem_pre_add_venue', $name, $venue_meta );

	$venue_id = wp_insert_post( $args );

	if ( empty( $venue_id ) ) {
		return false;
	}

	if ( ! empty( $venue_meta ) ) {

		foreach ( $venue_meta as $key => $value ) {

			if ( 'venue_email' === $key && ! is_email( $value ) ) {
				continue;
			}

			if ( ! empty( $value ) && 'venue_name' === $key ) {
				add_post_meta( $venue_id, '_' . $key, $value );
			}
		}
	}

	/**
	 * Fire the `tmem_post_add_venue` action.
	 *
	 * @since 1.3
	 * @param str $venue_id Post ID of new venue
	 */
	do_action( 'tmem_post_add_venue', $venue_id );

	// Re-add the save post hook for venue posts.
	add_action( 'save_post_tmem-venue', 'tmem_save_venue_post', 10, 3 );

	return $venue_id;

} // tmem_add_venue

/**
 * Retrieve all venues
 *
 * @since 1.3
 * @param arr $args Array of options to pass to get_posts. See $defaults.
 * @return obj Post objects for all venues.
 */
function tmem_get_venues( $args = array() ) {

	$defaults = array(
		'post_type'   => 'tmem-venue',
		'post_status' => 'publish',
		'orderby'     => 'title',
		'order'       => 'ASC',
		'numberposts' => -1,
	);

	$args = wp_parse_args( $args, $defaults );

	$venues = get_posts( $args );

	return apply_filters( 'tmem_get_venues', $venues );

} // tmem_get_venues

/**
 * Retrieve all venue meta data for the given event
 *
 * @since 1.3
 * @param int $item_id Required: The post ID of the venue or the event.
 * @param str $field Optional: The meta field to retrieve. Default to all (empty).
 * @return arr Array of all venue data
 */
function tmem_get_event_venue_meta( $item_id, $field = '' ) {

	$prefix = '';

	if ( 'tmem-venue' === get_post_type( $item_id ) ) {
		$venue_id = $item_id;
	} else {
		$venue_id = get_post_meta( $item_id, '_tmem_event_venue_id', true );
		$prefix   = '_tmem_event';

		if ( 'Manual' === $venue_id ) {
			$venue_id = $item_id;
		}
	}

	if ( empty( $venue_id ) ) {
		return;
	}

	switch ( $field ) {
		case 'address':
			$return[] = get_post_meta( $venue_id, $prefix . '_venue_address1', true );
			$return[] = get_post_meta( $venue_id, $prefix . '_venue_address2', true );
			$return[] = get_post_meta( $venue_id, $prefix . '_venue_town', true );
			$return[] = get_post_meta( $venue_id, $prefix . '_venue_county', true );
			$return[] = get_post_meta( $venue_id, $prefix . '_venue_postcode', true );

			if ( ! empty( $return ) ) {
				$return = array_filter( $return );
			}
			break;

		case 'address1':
			$return = get_post_meta( $venue_id, $prefix . '_venue_address1', true );
			break;

		case 'address2':
			$return = get_post_meta( $venue_id, $prefix . '_venue_address2', true );
			break;

		case 'town':
			$return = get_post_meta( $venue_id, $prefix . '_venue_town', true );
			break;

		case 'county':
			$return = get_post_meta( $venue_id, $prefix . '_venue_county', true );
			break;

		case 'postcode':
			$return = get_post_meta( $venue_id, $prefix . '_venue_postcode', true );
			break;

		case 'contact':
			$return = get_post_meta( $venue_id, $prefix . '_venue_contact', true );
			break;

		case 'details':
			$return = tmem_get_venue_details( $venue_id );
			break;

		case 'email':
			$return = get_post_meta( $venue_id, $prefix . '_venue_email', true );
			break;

		case 'name':
			$return = empty( $prefix ) ? get_the_title( $venue_id ) : get_post_meta( $venue_id, $prefix . '_venue_name', true );
			break;

		case 'notes':
			$return = get_post_meta( $venue_id, $prefix . '_venue_information', true );
			break;

		case 'phone':
			$return = get_post_meta( $venue_id, $prefix . '_venue_phone', true );
			break;

		default:
			$return = '';
			break;
	}

	if ( ! empty( $return ) ) {
		if ( is_array( $return ) ) {
			$return = array_map( 'stripslashes', $return );
			$return = array_map( 'esc_html', $return );
		} else {
			$return = esc_html( stripslashes( trim( $return ) ) );
		}
	}

	return empty( $return ) ? '' : $return;
} // tmem_get_event_venue_meta

/**
 * Retrieve all details for the given venue.
 *
 * @since 1.3
 * @param int $venue_id Required: The post ID of the venue.
 * @return arr Array of all venue detail labels.
 */
function tmem_get_venue_details( $venue_id ) {
	$details = wp_get_object_terms( $venue_id, 'venue-details' );

	$venue_details = array();

	if ( $details ) {
		foreach ( $details as $detail ) {
			$venue_details[] = $detail->name;
		}
	}

	return $venue_details;
} // tmem_get_venue_details

/**
 * Display a select list for venues.
 *
 * @since 1.3
 * @param arr $args See $defaults.
 * @return str The select list.
 */
function tmem_venue_dropdown( $args = array() ) {

	$defaults = array(
		'name'              => '_tmem_event_venue',
		'id'                => '',
		'selected'          => '',
		'first_entry'       => '',
		'first_entry_value' => '',
		'class'             => '',
		'required'          => false,
		'echo'              => true,
	);

	$args = wp_parse_args( $args, $defaults );

	$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];
	$required   = ! empty( $args['required'] ) ? ' required' : '';

	$output = '';

	$venues = tmem_get_venues();

	$output .= '<select name="' . $args['name'] . '" id="' . $args['name'] . '" class="' . $args['class'] . '"' . $required . '>';

	if ( ! empty( $args['first_entry'] ) ) {
		$output .= '<option value="' . $args['first_entry_value'] . '">' . esc_attr( $args['first_entry'] ) . '</option>';
	}

	if ( ! empty( $venues ) ) {
		foreach ( $venues as $venue ) {
			$address  = tmem_get_event_venue_meta( $venue->ID, 'address' );
			$town     = tmem_get_event_venue_meta( $venue->ID, 'town' );
			$option   = esc_attr( $venue->post_title );
			$option  .= ! empty( $town ) ? ' (' . esc_attr( $town ) . ')' : '';
			$title    = ! empty( $address ) ? implode( "\n", $address ) : '';
			$selected = ! empty( $args['selected'] ) ? selected( $args['selected'], $venue->ID, false ) : '';

			$output .= '<option value="' . $venue->ID . '" title="' . $title . '"' . $selected . '>' . $option . '</option>';
		}
	} else {
		$output .= '<option value="" disabled="disabled">' . apply_filters( 'tmem_no_venues', __( 'No venues exist', 'mobile-events-manager' ) ) . '</option>';
	}

	$output .= '</select>';

	if ( ! empty( $args['echo'] ) ) {
		echo $output;
	} else {
		return $output;
	}

} // tmem_venue_dropdown

/**
 * Output the venues details.
 *
 * @since 1.3.7
 * @param int $venue_id Venue ID.
 * @param int $event_id Event ID.
 * @return str
 */
function tmem_do_venue_details_table( $venue_id = '', $event_id = '' ) {

	if ( empty( $venue_id ) && empty( $event_id ) ) {
		return;
	} else {
		if ( empty( $venue_id ) ) {
			$venue_id = $event_id;
		}
	}

	$venue_name     = tmem_get_event_venue_meta( $venue_id, 'name' );
	$venue_contact  = tmem_get_event_venue_meta( $venue_id, 'contact' );
	$venue_email    = tmem_get_event_venue_meta( $venue_id, 'email' );
	$venue_address  = tmem_get_event_venue_meta( $venue_id, 'address' );
	$venue_town     = tmem_get_event_venue_meta( $venue_id, 'town' );
	$venue_county   = tmem_get_event_venue_meta( $venue_id, 'county' );
	$venue_postcode = tmem_get_event_venue_meta( $venue_id, 'postcode' );
	$venue_phone    = tmem_get_event_venue_meta( $venue_id, 'phone' );
	$venue_notes    = tmem_get_event_venue_meta( $venue_id, 'notes' );
	$venue_details  = tmem_get_venue_details( $venue_id );
	$employee_id    = ! empty( $event_id ) ? tmem_get_event_primary_employee_id( $event_id ) : '';
	$output         = array();

	if ( ! empty( $venue_contact ) ) {
		$output['contact'] = sprintf(
			'<label>%s</label>%s',
			__( 'Contact', 'mobile-events-manager' ),
			esc_attr( $venue_contact )
		);
	}

	if ( ! empty( $venue_email ) ) {
		$output['email'] = sprintf(
			'<label>%1$s</label><a href="mailto:%2$s">%2$s</a>',
			__( 'Email', 'mobile-events-manager' ),
			esc_attr( $venue_email )
		);
	}

	if ( ! empty( $venue_phone ) ) {
		$output['phone'] = sprintf(
			'<label>%s</label>%s',
			__( 'Phone', 'mobile-events-manager' ),
			esc_attr( $venue_phone )
		);
	}

	if ( ! empty( $venue_address ) ) {
		$output['address'] = sprintf(
			'<label>%s</label>%s',
			__( 'Address', 'mobile-events-manager' ),
			implode( '<br>', $venue_address )
		);
	}

	if ( ! empty( $venue_notes ) ) {
		$output['notes'] = sprintf(
			'<label>%s</label>%s',
			__( 'Information', 'mobile-events-manager' ),
			esc_attr( $venue_notes )
		);
	}

	if ( ! empty( $venue_details ) ) {
		$output['details'] = sprintf(
			'<label>%s</label>%s',
			__( 'Details', 'mobile-events-manager' ),
			implode( '<br>', $venue_details )
		);
	}

	ob_start(); ?>
	<div id="tmem-venue-details-fields" class="tmem-event-venue-details-sections-wrap">
		<div class="tmem-custom-event-sections">
			<div class="tmem-custom-event-section">
				<span class="tmem-custom-event-section-title">
					<?php if ( ! empty( $venue_name ) ) : ?>
						<?php printf( esc_html__( 'Venue Details for %s', 'mobile-events-manager' ), esc_attr( $venue_name ) ); ?>
					<?php else : ?>
						<?php printf( esc_html__( 'No venue has been selected for this %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ); ?>
					<?php endif; ?>
				</span>

				<?php foreach ( $output as $key => $value ) : ?>
					<span class="tmem-view-venue-<?php echo $key; ?>">
						<?php echo $value; ?>
					</span>
				<?php endforeach; ?>
				<?php do_action( 'tmem_after_venue_notes', $venue_address, $employee_id ); ?>
			</div>
		</div>
	</div>

	<?php
	return ob_get_clean();

} // tmem_do_venue_details_table
