<?php
/**
 * Contains all travel related functions
 *
 * @package MEM
 * @subpackage Venues
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculate the travel distance
 *
 * @since 1.3.8
 * @param int|obj $event The event ID or the event MEM_Event class object.
 * @param int     $venue_id The venue ID.
 * @return str The distance to the event venue or an empty string
 */
function mem_travel_get_distance( $event = '', $venue_id = '' ) {

	if ( ! empty( $event ) ) {
		if ( ! is_object( $event ) ) {
			$mem_event = new MEM_Event( $event );
		} else {
			$mem_event = $event;
		}
	}

	$start       = mem_travel_get_start( $mem_event );
	$destination = mem_travel_get_destination( $mem_event, $venue_id );

	if ( empty( $start ) || empty( $destination ) ) {
		return false;
	}

	$query = mem_travel_build_url( $start, $destination );

	$response = wp_remote_get( $query );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$travel_data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( empty( $travel_data ) || 'OK' === $travel_data->status ) {
		return false;
	}

	if ( empty( $travel_data->rows ) ) {
		return false;
	}

	if ( empty( $travel_data->origin_addresses[0] ) || empty( $travel_data->destination_addresses[0] ) ) {
		return false;
	}

	if ( empty( $travel_data->rows[0]->elements[0]->distance->value ) || empty( $travel_data->rows[0]->elements[0]->duration->value ) ) {
		return false;
	}

	$return = array(
		'origin'      => $travel_data->origin_addresses[0],
		'destination' => $travel_data->destination_addresses[0],
		'duration'    => $travel_data->rows[0]->elements[0]->duration->value,
		'distance'    => str_replace(
			array( 'km', 'mi' ),
			array( '', '' ),
			$travel_data->rows[0]->elements[0]->distance->text
		),
	);

	return apply_filters( 'mem_travel_get_distance', $return, $event );

} // mem_travel_get_distance

/**
 * Calculate the travel cost.
 *
 * @since 1.3.8
 * @param str $distance The distance of travel.
 * @return str|int The cost of travel.
 */
function mem_get_travel_cost( $distance ) {
	$mem_travel = new MEM_Travel();
	$mem_travel->__set( 'distance', $distance );

	return $mem_travel->get_cost();
} // mem_get_travel_cost

/**
 * Build the URL to retrieve the distance.
 *
 * @since 1.3.8
 * @param str $start The travel start address.
 * @return str $destination The travel destination address.
 */
function mem_travel_build_url( $start, $destination ) {

	$api_key = mem_travel_get_api_key();
	$prefix  = 'https://maps.googleapis.com/maps/api/distancematrix/json';
	$mode    = 'driving';
	$units   = mem_get_option( 'travel_units' );

	$url = add_query_arg(
		array(
			'units'        => $units,
			'origins'      => str_replace( '%2C', ',', rawurlencode( $start ) ),
			'destinations' => str_replace( '%2C', ',', rawurlencode( $destination ) ),
			'mode'         => $mode,
			'key'          => $api_key,
		),
		$prefix
	);

	return apply_filters( 'mem_travel_build_url', $url );

} // mem_travel_build_url

/**
 * Retrieve the Google API key.
 *
 * @since 1.3.8
 * @param
 * @return str The API key.
 */
function mem_travel_get_api_key() {
	return '617372114575-g846rsgcm715pkmhkokrho9c75ii3cne.apps.googleusercontent.com';
} // mem_travel_get_api_key

/**
 * Retrieves the travel starting point.
 *
 * @since 1.3.8
 * @param int|obj $event The event ID or the event MEM_Event class object.
 * @return str
 */
function mem_travel_get_start( $event = '' ) {

	if ( ! empty( $event ) ) {
		if ( ! is_object( $event ) ) {
			$mem_event = new MEM_Event( $event );
		} else {
			$mem_event = $event;
		}
	}

	$employee = $mem_event->get_employee();

	if ( $employee ) {
		$address = mem_get_employee_address( $employee );
	} else {
		$address = mem_get_option( 'travel_primary' );
	}

	$start = $address;

	if ( empty( $start ) ) {
		return;
	}

	if ( is_array( $start ) ) {
		$start = implode( ',', $start );
	}

	return apply_filters( 'mem_travel_get_start', $start );

} // mem_travel_get_start

/**
 * Retrieves the travel destination address.
 *
 * @since 1.3.8
 * @param int|obj $event The event ID or the event MEM_Event class object.
 * @return str
 */
function mem_travel_get_destination( $event, $venue_id = '' ) {

	if ( ! is_object( $event ) ) {
		$mem_event = new MEM_Event( $event );
	} else {
		$mem_event = $event;
	}

	$venue = ! empty( $venue_id ) ? $venue_id : $mem_event->get_venue_id();

	$destination = mem_get_event_venue_meta( $venue, 'address' );

	if ( ! $destination ) {
		return;
	}

	if ( is_array( $destination ) ) {
		$destination = implode( ',', $destination );
	}

	return apply_filters( 'mem_travel_get_destination', $destination );

} // mem_travel_get_destination

/**
 * Returns the label for the selected measurement unit.
 *
 * @since 1.3.8
 * @param bool $singular Whether to return a singular (true) or plural (false) value.
 * @param bool $lowercase True to return a lowercase label, otherwise false.
 * @return str
 */
function mem_travel_unit_label( $singular = false, $lowercase = true ) {
	$units = array(
		'singular' => array(
			'imperial' => 'Mile',
			'metric'   => 'Kilometer',
		),
		'plural'   => array(
			'imperial' => 'Miles',
			'metric'   => 'Kilometers',
		),
	);

	$type = 'singular';

	if ( ! $singular ) {
		$type = 'plural';
	}

	$return = $units[ $type ][ mem_get_option( 'travel_units', 'imperial' ) ];

	if ( $lowercase ) {
		$return = strtolower( $return );
	}

	return apply_filters( 'mem_travel_unit_label', $return );

} // mem_travel_unit_label

/**
 * Retrieve the event travel fields.
 *
 * These fields are used to generate hidden fields on the event admin page
 * and store data relating to event travel.
 *
 * @since 1.4
 * @return arr
 */
function mem_get_event_travel_fields() {
	$travel_fields = array( 'cost', 'distance', 'time', 'directions_url' );

	/**
	 * Allow filtering of the travel fields for developers.
	 *
	 * @since 1.4
	 */
	return apply_filters( 'mem_event_travel_fields', $travel_fields );
} // mem_get_event_travel_fields

/**
 * Retrieve event travel data.
 *
 * @since 1.4
 * @param int $event_id Event ID.
 * @param str $field The travel field to retrieve.
 * @return str
 */
function mem_get_event_travel_data( $event_id, $field = 'cost' ) {
	$travel_data = get_post_meta( $event_id, '_mem_event_travel_data', true );

	if ( $travel_data ) {
		if ( ! empty( $travel_data[ $field ] ) ) {
			return apply_filters( 'mem_event_travel_' . $field, $travel_data[ $field ], $event_id );
		}
	}

	return false;
} // mem_get_event_travel_fields

/**
 * Adds the travel data row to the venue details metabox on the event screen.
 *
 * @since 1.4
 * @param int|arr|obj $dest An address array, event ID, event object or venue ID.
 * @param int         $employee_id An employee user ID.
 * @return void
 */
function mem_show_travel_data_row( $dest, $employee_id = '' ) {

	$mem_travel = new MEM_Travel();

	if ( ! empty( $employee_id ) ) {
		$mem_travel->__set( 'start_address', $mem_travel->get_employee_address( $employee_id ) );
	}

	$mem_travel->set_destination( $dest );

	if ( empty( $employee_id ) ) {
		if ( is_object( $dest ) ) {
			$mem_travel->__set( 'start_address', $mem_travel->get_employee_address( $dest->employee_id ) );
		} elseif ( is_numeric( $dest ) ) {
			if ( 'mem-event' === get_post_type( $dest ) ) {
				$mem_travel->__set( 'start_address', $mem_travel->get_employee_address( mem_get_event_primary_employee_id( $dest ) ) );
			}
		}
	}

	$mem_travel->get_travel_data();
	$distance       = '';
	$duration       = '';
	$cost           = '';
	$directions_url = '';
	$directions     = $mem_travel->get_directions_url();
	$class          = 'mem-hidden';

	if ( ! empty( $mem_travel->data ) ) {
		$distance       = mem_format_distance( $mem_travel->data['distance'], false, true );
		$duration       = mem_seconds_to_time( $mem_travel->data['duration'] );
		$cost           = mem_currency_filter( mem_format_amount( $mem_travel->get_cost() ) );
		$directions_url = $directions ? $directions : '';
		$class          = '';
	}

	ob_start(); ?>

	<div id="mem-travel-data" class="<?php echo $class; ?>">
		<span class="mem-travel-unit">
			<i class="fa fa-car" aria-hidden="true" title="<?php esc_html_e( 'Distance', 'mobile-events-manager' ); ?>"></i>
			<span class="mem-travel-distance"><?php echo $distance; ?></span>
		</span>
		<span class="mem-travel-unit">
			<i class="fa fa-clock-o" aria-hidden="true" title="<?php _e( 'Travel Time', 'mobile-events-manager' ); ?>"></i>
			<span class="mem-travel-time"><?php echo $duration; ?></span>
		</span>
		<span class="mem-travel-unit">
			<i class="fa fa-money" aria-hidden="true" title="<?php esc_html_e( 'Cost', 'mobile-events-manager' ); ?>"></i>
			<span class="mem-travel-cost"><?php echo $cost; ?></span>
		</span>
		<span class="mem-travel-unit">
			<i class="fa fa-map-signs" aria-hidden="true" title="<?php esc_html_e( 'Directions', 'mobile-events-manager' ); ?>"></i>
			<span class="mem-travel-directions"><a id="travel_directions" href="<?php echo $directions_url; ?>" target="_blank"><?php esc_html_e( 'Directions', 'mobile-events-manager' ); ?></a></span>
		</span>
		</div>

	<?php
	$travel_data_row = ob_get_contents();
	ob_end_clean();

	echo $travel_data_row;

} // mem_show_travel_data_row
add_action( 'mem_after_venue_notes', 'mem_show_travel_data_row', 10, 2 );
add_action( 'mem_venue_details_travel_data', 'mem_show_travel_data_row', 10, 2 );
