<?php

/**
 * Contains all metabox functions for the tmem-venue post type
 *
 * @package TMEM
 * @subpackage Templates
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define and add the metaboxes for the contract post type.
 * Apply the `tmem_contract_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param
 * @return
 */
function tmem_add_contract_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'tmem_contract_add_metaboxes',
		array(
			array(
				'id'         => 'tmem-contract-details',
				'title'      => sprintf( esc_html__( 'Contract Details', 'mobile-events-manager' ), get_post_type_object( 'contract' )->labels->singular_name ),
				'callback'   => 'tmem_contract_details_metabox',
				'context'    => 'side',
				'priority'   => 'default',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'tmem_contract_before_metaboxes' );

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
			'contract',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'tmem_contract_after_metaboxes' );

} // tmem_add_communication_meta_boxes
add_action( 'add_meta_boxes_contract', 'tmem_add_contract_meta_boxes' );

/**
 * Output for the Contract Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_contract_details_metabox( $post ) {

	do_action( 'tmem_pre_contract_details_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'tmem-contract' . '_nonce' );

	$contract_events = tmem_get_events(
		array(
			'meta_query' => array(
				array(
					'key'   => '_tmem_event_contract',
					'value' => $post->ID,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	$event_count = count( $contract_events );

	$total_events = sprintf(
		_n( ' %s', ' %s', $event_count, 'mobile-events-manager' ),
		esc_html( tmem_get_label_singular() ),
		esc_html( tmem_get_label_plural() )
	);

	$default_contract = tmem_get_option( 'default_contract' ) == $post->ID ? __( 'Yes', 'mobile-events-manager' ) : __( 'No', 'mobile-events-manager' );

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
					esc_html( tmem_get_label_singular() ),
					esc_html( tmem_get_label_plural() )
				);
	?>
	</p>

	<p><?php esc_html_e( '<strong>Description</strong>: <span class="description">(optional)</span>', 'mobile-events-manager' ); ?>
		<br />
		<input type="hidden" name="tmem_update_custom_post" id="tmem_update_custom_post" value="tmem_update" />
		<textarea name="contract_description" id="contract_description" class="widefat" rows="5" placeholder="<?php esc_html_e( 'i.e To be used for Pubs/Clubs', 'mobile-events-manager' ); ?>"><?php echo esc_attr( get_post_meta( $post->ID, '_contract_description', true ) ); ?></textarea>
	</p>

	<?php

	do_action( 'tmem_post_contract_details_metabox', $post );

} // tmem_contract_details_metabox
