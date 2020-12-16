<?php

/**
 * Contains all metabox functions for the mem-venue post type
 *
 * @package MEM
 * @subpackage Venues
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define and add the metaboxes for the mem-venue post type.
 * Apply the `mem_venue_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_add_venue_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'mem_venue_add_metaboxes',
		array(
			array(
				'id'         => 'mem-venue-details',
				'title'      => __( 'Venue Details', 'mobile-events-manager' ),
				'callback'   => 'mem_venue_details_metabox',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'mem_venue_before_metaboxes' );

	// Begin metaboxes
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check
		if ( ! empty( $metabox['permission'] ) && ! mem_employee_can( $metabox['permission'] ) ) {
			continue;
		}

		// Callback check
		if ( ! is_callable( $metabox['callback'] ) ) {
			continue;
		}

		add_meta_box(
			$metabox['id'],
			$metabox['title'],
			$metabox['callback'],
			'mem-venue',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'mem_venue_after_metaboxes' );
} // mem_add_venue_meta_boxes
add_action( 'add_meta_boxes_mem-venue', 'mem_add_venue_meta_boxes' );

/**
 * Output for the Venue Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_venue_details_metabox( $post ) {

	do_action( 'mem_pre_venue_details_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'mem-venue' . '_nonce' );

	?>
	<script type="text/javascript">
	document.getElementById("title").className += " required";
	</script>
	<input type="hidden" name="mem_update_custom_post" id="mem_update_custom_post" value="mem_update" />
	<!-- Start first row -->
	<div class="mem-post-row-single">
		<div class="mem-post-1column">
			<label for="venue_contact" class="mem-label"><strong><?php esc_html_e( 'Contact Name: ', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_contact" id="venue_contact" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_contact', true ) ); ?>">
		</div>
	</div>
	<!-- End first row -->
	<!-- Start second row -->
	<div class="mem-post-row-single">
		<div class="mem-post-1column">
			<label for="venue_phone" class="mem-label"><strong><?php esc_html_e( 'Contact Phone:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_phone" id="venue_phone" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_phone', true ) ); ?>" />
		</div>
	</div>
	<!-- End second row -->
	<!-- Start third row -->
	<div class="mem-post-row-single">
		<div class="mem-post-1column">
			<label for="venue_email" class="mem-label"><strong><?php esc_html_e( 'Contact Email: ', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_email" id="venue_email" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_email', true ) ); ?>">
		</div>
	</div>
	<!-- End third row -->
	<!-- Start fourth row -->
	<div class="mem-post-row">
		<!-- Start first coloumn -->
		<div class="mem-post-2column">
			<label for="venue_address1" class="mem-label"><strong><?php esc_html_e( 'Address Line 1:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_address1" id="venue_address1" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_address1', true ) ); ?>" />
		</div>
		<!-- End first coloumn -->
		<!-- Start second coloumn -->
		<div class="mem-post-last-2column">
			<label for="venue_address2" class="mem-label"><strong><?php esc_html_e( 'Address Line 2:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_address2" id="venue_address2" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_address2', true ) ); ?>" />
		</div>
		<!-- End second coloumn -->
	</div>
	<!-- End fourth row -->
	<!-- Start fifth row -->
	<div class="mem-post-row">
		<!-- Start first coloumn -->
		<div class="mem-post-2column">
			<label for="venue_town" class="mem-label"><strong><?php esc_html_e( 'Town:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_town" id="venue_town" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_town', true ) ); ?>" />
		</div>
		<!-- End first coloumn -->
		<!-- Start second coloumn -->
		<div class="mem-post-last-2column">
			<label for="venue_county" class="mem-label"><strong><?php esc_html_e( 'County:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_county" id="venue_county" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_county', true ) ); ?>" />
		</div>
		<!-- End second coloumn -->
	</div>
	<!-- End fifth row -->
	<!-- Start sixth row -->
	<div class="mem-post-row-single">
		<div class="mem-post-1column">
			<label for="venue_postcode" class="mem-label"><strong><?php esc_html_e( 'Post Code:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_postcode" id="venue_postcode" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_postcode', true ) ); ?>" />
		</div>
	</div>
	<!-- End sixth row -->
	<!-- Start seventh row -->
	<div class="mem-post-row-single-textarea">
		<div class="mem-post-1column">
			<label for="venue_information" class="mem-label"><strong><?php esc_html_e( 'General Information:', 'mobile-events-manager' ); ?></strong></label><br />
			<textarea name="venue_information" id="venue_information" class="widefat" cols="30" rows="3" placeholder="Enter any information you feel relevant for the venue here. Consider adding or selecting venue details where possible via the 'Venue Details' side box"><?php echo esc_attr( get_post_meta( $post->ID, '_venue_information', true ) ); ?></textarea>
		</div>
	</div>
	<!-- End seventh row -->
	<?php

	do_action( 'mem_post_venue_details_metabox', $post );

} // mem_venue_details_metabox
