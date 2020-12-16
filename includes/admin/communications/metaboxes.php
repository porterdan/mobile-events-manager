<?php
/**
 * Contains all metabox functions for the mem-venue post type
 *
 * @package MEM
 * @subpackage Communications
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove unwanted metaboxes to for the mem_communication post type.
 * Apply the `mem_communication_remove_metaboxes` filter to allow for filtering of metaboxes to be removed.
 *
 * @since 1.3
 * @param arr $metaboxes metaboxes.
 */
function mem_remove_communication_meta_boxes() {
	$metaboxes = apply_filters(
		'mem_communication_remove_metaboxes',
		array(
			array( 'submitdiv', 'mem_communication', 'side' ),
		)
	);

	foreach ( $metaboxes as $metabox ) {
		remove_meta_box( $metabox[0], $metabox[1], $metabox[2] );
	}
} // mem_remove_transaction_meta_boxes
add_action( 'admin_head', 'mem_remove_communication_meta_boxes' );

/**
 * Define and add the metaboxes for the mem_communication post type.
 * Apply the `mem_venue_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses function_exists to verify the callback function exists.
 *
 * @since 1.3
 * @param array $post Post Type.
 */
function mem_add_communication_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'mem_communication_add_metaboxes',
		array(
			array(
				'id'         => 'mem-email-details',
				'title'      => __( 'Details', 'mobile-events-manager' ),
				'callback'   => 'mem_communication_details_metabox',
				'context'    => 'side',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'mem_comms_send',
			),
			array(
				'id'         => 'mem-email-content',
				'title'      => __( 'Email Content', 'mobile-events-manager' ),
				'callback'   => 'mem_communication_content_metabox',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => 'mem_comms_send',
			),
		)
	);
	// Runs before metabox output.
	do_action( 'mem_communication_before_metaboxes' );

	// Begin metaboxes.
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check.
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check.
		if ( ! empty( $metabox['permission'] ) && ! mem_employee_can( $metabox['permission'] ) ) {
			continue;
		}

		// Callback check.
		if ( ! is_callable( $metabox['callback'] ) ) {
			continue;
		}

		add_meta_box(
			$metabox['id'],
			$metabox['title'],
			$metabox['callback'],
			'mem_communication',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output.
	do_action( 'mem_communication_after_metaboxes' );

} // mem_add_communication_meta_boxes.
add_action( 'add_meta_boxes_mem_communication', 'mem_add_communication_meta_boxes' );

/**
 * Output for the Communication Details meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 */
function mem_communication_details_metabox( $post ) {

	do_action( 'mem_pre_communication_details_metabox', $post );

	wp_nonce_field( basename( __FILE__ ), 'mem_communication' . '_nonce' );

	$from      = get_userdata( $post->post_author );
	$recipient = get_userdata( get_post_meta( $post->ID, '_recipient', true ) );

	$attachments = get_children(
		array(
			'post_parent'  => $post->ID,
			'post_type'    => 'attachment',
			'number_posts' => -1,
			'post_status'  => 'any',
		)
	);

	?>
	<p>
	<?php
	printf(
		wp_kses_post( /* translators: %s: Date sent */
			__( '<strong>Date Sent</strong>: %s', 'mobile-events-manager' ),
			gmdate( mem_get_option( 'time_format', 'H:i' ) . ' ' . mem_get_option( 'short_date_format', 'd/m/Y' ), get_post_meta( $post->ID, '_date_sent', true ) )
		)
	);
	?>
		</p>

	<p>
	<?php
	printf(
		wp_kses_post( /* translators: %1: URl 2%: Artiste name */
			__( '<strong>From</strong>: <a href="%1$s">%2$s</a>', 'mobile-events-manager' ),
			admin_url( "/user-edit.php?user_id={$from->ID}" ),
			$from->display_name
		)
	);
	?>
		</p>

	<p>
	<?php
	printf(
		wp_kses_post( /* translators: %1: URl 2%: Customer Name */
			__( '<strong>Recipient</strong>: <a href="%1$s">%2$s</a>', 'mobile-events-manager' ),
			admin_url( "/user-edit.php?user_id={$recipient->ID}" ),
			$recipient->display_name
		)
	);
	?>
		</p>

	<?php
	$copies = get_post_meta( $post->ID, '_mem_copy_to', true );

	if ( ! empty( $copies ) ) {

		?>
			<p>
			<?php

			esc_html_e( '<strong>Copied To</strong>: ', 'mobile-events-manager' );
			?>
				<?php

				$i = 1;
				foreach ( $copies as $copy ) {
					$user = get_user_by( 'email', $copy );
					if ( $user ) {
						echo wp_kses_post( "<em>{$user->display_name}</em>" );

						$i++;

						if ( $i < count( $copies ) ) {
							echo '<br />';
						}
					}
				}

				?>
			</p>
			<?php
	}
	?>

	<p><?php esc_html_e( '<strong>Status</strong>:', 'mobile-events-manager' ); ?>

		<?php
		echo esc_attr( get_post_status_object( $post->post_status )->label );

		if ( 'opened' === $post->post_status ) {
			echo ' ' . esc_attr( gmdate( mem_get_option( 'time_format', 'H:i' ) . ' ' . mem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $post->post_modified ) ) );
		}
		?>
		</p>

	<p><strong><?php echo esc_html( mem_get_label_singular() ); ?></strong>: <a href="<?php echo wp_kses_post( get_edit_post_link( get_post_meta( $post->ID, '_event', true ) ) ); ?>"><?php echo esc_attr( mem_get_event_contract_id( stripslashes( get_post_meta( $post->ID, '_event', true ) ) ) ); ?></a></p>

	<?php
	if ( ! empty( $attachments ) ) {

		$i = 1;
		?>
		<p><strong><?php esc_html_e( 'Attachments', 'mobile-events-manager' ); ?></strong>:<br />

			<?php
			foreach ( $attachments as $attachment ) {
				echo wp_kses_post( '<a style="font-size: 11px;" href="' . wp_get_attachment_url( $attachment->ID ) . '">' );
				echo esc_attr( basename( get_attached_file( $attachment->ID ) ) );
				echo '</a>';
				echo ( $i < count( $attachments ) ? '<br />' : '' );
				$i++;
			}
			?>
		</p>
		<?php
	}
	?>

	<a class="button-secondary" href="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ); ?>" title="<?php esc_html_e( 'Back to List', 'mobile-events-manager' ); ?>"><?php esc_html_e( 'Back', 'mobile-events-manager' ); ?></a>

	<?php

	do_action( 'mem_post_communication_details_metabox', $post );

} // mem_communication_details_metabox

/**
 * Output for the Communication Content meta box.
 *
 * @since 1.3
 * @param obj $post The post object (WP_Post).
 */
function mem_communication_content_metabox( $post ) {

	do_action( 'mem_pre_communication_content_metabox', $post );

	echo wp_kses_post( $post->post_content );

	do_action( 'mem_post_communication_content_metabox', $post );

} // mem_communication_content_metabox
