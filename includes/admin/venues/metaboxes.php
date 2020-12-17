<?php

/**
 * Contains all metabox functions for the tmem-venue post type
 *
 * @package TMEM
 * @subpackage Venues
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define and add the metaboxes for the tmem-venue post type.
 * Apply the `tmem_venue_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_add_venue_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'tmem_venue_add_metaboxes',
		array(
			array(
				'id'         => 'tmem-venue-details',
				'title'      => __( 'Venue Details', 'mobile-events-manager' ),
				'callback'   => 'tmem_venue_details_metabox',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'tmem_venue_before_metaboxes' );

	// Begin metaboxes
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check
		if ( ! empty( $metabox['permission'] ) && ! tmem_employee_can( $metabox['permission'] ) ) {
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
			'tmem-venue',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'tmem_venue_after_metaboxes' );
} // tmem_add_venue_meta_boxes
add_action( 'add_meta_boxes_tmem-venue', 'tmem_add_venue_meta_boxes' );

/**
 * Output for the Venue Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_venue_details_metabox( $post ) {

	do_action( 'tmem_pre_venue_details_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'tmem-venue' . '_nonce' );

	?>
	<script type="text/javascript">
	document.getElementById("title").className += " required";
	</script>
	<input type="hidden" name="tmem_update_custom_post" id="tmem_update_custom_post" value="tmem_update" />
	<!-- Start first row -->
	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<label for="venue_contact" class="tmem-label"><strong><?php esc_html_e( 'Contact Name: ', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_contact" id="venue_contact" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_contact', true ) ); ?>">
		</div>
	</div>
	<!-- End first row -->
	<!-- Start second row -->
	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<label for="venue_phone" class="tmem-label"><strong><?php esc_html_e( 'Contact Phone:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_phone" id="venue_phone" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_phone', true ) ); ?>" />
		</div>
	</div>
	<!-- End second row -->
	<!-- Start third row -->
	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<label for="venue_email" class="tmem-label"><strong><?php esc_html_e( 'Contact Email: ', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_email" id="venue_email" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_email', true ) ); ?>">
		</div>
	</div>
	<!-- End third row -->
	<!-- Start fourth row -->
	<div class="tmem-post-row">
		<!-- Start first coloumn -->
		<div class="tmem-post-2column">
			<label for="venue_address1" class="tmem-label"><strong><?php esc_html_e( 'Address Line 1:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_address1" id="venue_address1" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_address1', true ) ); ?>" />
		</div>
		<!-- End first coloumn -->
		<!-- Start second coloumn -->
		<div class="tmem-post-last-2column">
			<label for="venue_address2" class="tmem-label"><strong><?php esc_html_e( 'Address Line 2:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_address2" id="venue_address2" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_address2', true ) ); ?>" />
		</div>
		<!-- End second coloumn -->
	</div>
	<!-- End fourth row -->
	<!-- Start fifth row -->
	<div class="tmem-post-row">
		<!-- Start first coloumn -->
		<div class="tmem-post-2column">
			<label for="venue_town" class="tmem-label"><strong><?php esc_html_e( 'Town:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_town" id="venue_town" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_town', true ) ); ?>" />
		</div>
		<!-- End first coloumn -->
		<!-- Start second coloumn -->
		<div class="tmem-post-last-2column">
			<label for="venue_county" class="tmem-label"><strong><?php esc_html_e( 'County:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_county" id="venue_county" class="regular-text required" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_county', true ) ); ?>" />
		</div>
		<!-- End second coloumn -->
	</div>
	<!-- End fifth row -->
	<!-- Start sixth row -->
	<div class="tmem-post-row-single">
		<div class="tmem-post-1column">
			<label for="venue_postcode" class="tmem-label"><strong><?php esc_html_e( 'Post Code:', 'mobile-events-manager' ); ?></strong></label><br />
			<input type="text" name="venue_postcode" id="venue_postcode" class="regular-text" value="<?php echo esc_attr( get_post_meta( $post->ID, '_venue_postcode', true ) ); ?>" />
		</div>
	</div>
	<!-- End sixth row -->
	<!-- Start seventh row -->
	<div class="tmem-post-row-single-textarea">
		<div class="tmem-post-1column">
			<label for="venue_information" class="tmem-label"><strong><?php esc_html_e( 'General Information:', 'mobile-events-manager' ); ?></strong></label><br />
			<textarea name="venue_information" id="venue_information" class="widefat" cols="30" rows="3" placeholder="Enter any information you feel relevant for the venue here. Consider adding or selecting venue details where possible via the 'Venue Details' side box"><?php echo esc_attr( get_post_meta( $post->ID, '_venue_information', true ) ); ?></textarea>
		</div>
	</div>
	<!-- End seventh row -->
	<?php

	do_action( 'tmem_post_venue_details_metabox', $post );

} // tmem_venue_details_metabox
