<?php
/**
 * This template is used to display the details of a single event to the client.
 *
 * @version 1.0.1
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag {client_*}
 * @content_tag {event_*}
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/event/event-single.php
 * @package is the package type
 */

global $mem_event;
?>
<?php do_action( 'mem_pre_event_detail', $mem_event->ID, $mem_event ); ?>
<div id="post-<?php echo esc_attr( $mem_event->ID ); ?>" class="mem-<?php echo esc_attr( $mem_event->post_status ); ?>">

	<?php do_action( 'mem_print_notices' ); ?>

	<p>
	<?php
	printf(
		esc_html( __( 'Details of your %1$s taking place on %2$s are shown below.', 'mobile-events-manager' ) ),
		esc_html( mem_get_label_singular( true ) ),
		'{event_date}'
	);
	?>
			</p>

	<p>
	<?php
	printf(
		esc_html( __( 'Please confirm the details displayed are correct or <a href="%s">contact us</a> with any adjustments.', 'mobile-events-manager' ) ),
		'{contact_page}'
	);
	?>
			</p>

	<?php
	/**
	 * Display event action buttons
	 */
	?>
	<div class="mem-action-btn-container">{event_action_buttons}</div>

	<?php
	/**
	 * Display event details
	 */
	?>
	<?php do_action( 'mem_pre_event_details', $mem_event->ID, $mem_event ); ?>

	<div id="mem-singleevent-details">
		<table class="mem-singleevent-overview">
			<tr>
				<th colspan="4" class="mem-event-heading">{event_name} - {event_date}</th>
			</tr>

			<tr>
				<th colspan="4"><?php esc_html_e( 'Status:', 'mobile-events-manager' ); ?> {event_status}</th>
			</tr>

			<tr>
				<th><?php printf( esc_html__( '%s Type:', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></th>
				<td>{event_type}</td>
				<th><?php printf( esc_html__( 'Your %s:', 'mobile-events-manager' ), esc_html__( mem_get_option( 'artist', __( 'DJ', 'mobile-events-manager' ) ) ) ); ?></th>
				<td>{employee_fullname}</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Starts:', 'mobile-events-manager' ); ?></th>
				<td>{start_time}</td>
				<th><?php esc_html_e( 'Completes:', 'mobile-events-manager' ); ?></th>
				<td>{end_time} ({end_date})</td>
			</tr>
			<?php if ( mem_get_option( 'enable_packages' ) ) : ?>
				<tr>
					<th colspan="4"><?php esc_html_e( 'Package Details:', 'mobile-events-manager' ); ?></th>
				</tr>

				<tr>
					<th><?php esc_html_e( 'Package:', 'mobile-events-manager' ); ?></th>
					<td>{event_package}</td>
					<th><?php esc_html_e( 'Add-ons:', 'mobile-events-manager' ); ?></th>
					<td>{event_addons}</td>
				</tr>
			<?php endif; ?>

			<tr>
				<th colspan="4"><?php esc_html_e( 'Pricing', 'mobile-events-manager' ); ?></th>
			</tr>

			<tr>
				<th colspan="4"><?php esc_html_e( 'Total Cost:', 'mobile-events-manager' ); ?> {total_cost}<br />
					{deposit_label}: {deposit} ({deposit_status})<br />
					{balance_label} <?php esc_html_e( 'Remaining', 'mobile-events-manager' ); ?>: {balance}
				</th>
			</tr>

			<tr>
				<th colspan="4"><?php esc_html_e( 'Your Details', 'mobile-events-manager' ); ?></th>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Name:', 'mobile-events-manager' ); ?></th>
				<td>{client_fullname}</td>
				<th><?php esc_html_e( 'Phone:', 'mobile-events-manager' ); ?></th>
				<td>{client_primary_phone}</td>
			</tr>

			<tr>
				<th><?php esc_html_e( 'Email:', 'mobile-events-manager' ); ?></th>
				<td>{client_email}</td>
				<th><?php esc_html_e( 'Address:', 'mobile-events-manager' ); ?></th>
				<td>{client_full_address}</td>
			</tr>

			<tr>
				<th colspan="4"><?php esc_html_e( 'Venue Details', 'mobile-events-manager' ); ?></th>
			</tr>

			 <tr>
				<th><?php esc_html_e( 'Venue:', 'mobile-events-manager' ); ?></th>
				<td>{venue}</td>
				<th><?php esc_html_e( 'Address:', 'mobile-events-manager' ); ?></th>
				<td>{venue_full_address}</td>
			</tr>

		</table>
	</div>
	<?php do_action( 'mem_post_event_details', $mem_event->ID, $mem_event ); ?>

</div>
<?php do_action( 'mem_post_event_detail', $mem_event->ID, $mem_event ); ?>
