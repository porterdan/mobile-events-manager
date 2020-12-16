<?php

/**
 * Contains all metabox functions for the mem-venue post type
 *
 * @package MEM
 * @subpackage Templates
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define and add the metaboxes for the contract post type.
 * Apply the `mem_contract_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_add_contract_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'mem_contract_add_metaboxes',
		array(
			array(
				'id'         => 'mem-contract-details',
				'title'      => sprintf( esc_html__( 'Contract Details', 'mobile-events-manager' ), get_post_type_object( 'contract' )->labels->singular_name ),
				'callback'   => 'mem_contract_details_metabox',
				'context'    => 'side',
				'priority'   => 'default',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'mem_contract_before_metaboxes' );

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
			'contract',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'mem_contract_after_metaboxes' );

} // mem_add_communication_meta_boxes
add_action( 'add_meta_boxes_contract', 'mem_add_contract_meta_boxes' );

/**
 * Output for the Contract Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function mem_contract_details_metabox( $post ) {

	do_action( 'mem_pre_contract_details_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'mem-contract' . '_nonce' );

	$contract_events = mem_get_events(
		array(
			'meta_query' => array(
				array(
					'key'   => '_mem_event_contract',
					'value' => $post->ID,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	$event_count = count( $contract_events );

	$total_events = sprintf(
		_n( ' %s', ' %s', $event_count, 'mobile-events-manager' ),
		esc_html( mem_get_label_singular() ),
		esc_html( mem_get_label_plural() )
	);

	$default_contract = mem_get_option( 'default_contract' ) == $post->ID ? __( 'Yes', 'mobile-events-manager' ) : __( 'No', 'mobile-events-manager' );

	?>
	<script type="text/javascript">
	document.getElementById("title").className += " required";
	document.getElementById("content").className += " required";
	</script>

	<p>
	<?php
	printf(
		__( '<strong>Author</strong>: <a href="%1$s">%2$s</a>', 'mobile-events-manager' ),
		admin_url( "user-edit.php?user_id={$post->post_author}" ),
		get_the_author_meta( 'display_name', $post->post_author )
	);
	?>
	</p>

	<p>
	<?php
	esc_html_e( '<strong>Default</strong>?', 'mobile-events-manager' );
		echo ' ' . $default_contract;
	?>
	</p>

	<p>
	<?php
	esc_html_e( '<strong>Assigned To</strong>: ', 'mobile-events-manager' );
				printf(
					_n( $event_count . ' %1$s', $event_count . ' %2$s', $event_count, 'mobile-events-manager' ),
					esc_html( mem_get_label_singular() ),
					esc_html( mem_get_label_plural() )
				);
	?>
	</p>

	<p><?php esc_html_e( '<strong>Description</strong>: <span class="description">(optional)</span>', 'mobile-events-manager' ); ?>
		<br />
		<input type="hidden" name="mem_update_custom_post" id="mem_update_custom_post" value="mem_update" />
		<textarea name="contract_description" id="contract_description" class="widefat" rows="5" placeholder="<?php esc_html_e( 'i.e To be used for Pubs/Clubs', 'mobile-events-manager' ); ?>"><?php echo esc_attr( get_post_meta( $post->ID, '_contract_description', true ) ); ?></textarea>
	</p>

	<?php

	do_action( 'mem_post_contract_details_metabox', $post );

} // mem_contract_details_metabox
