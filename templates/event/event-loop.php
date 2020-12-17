<?php
/**
 * This template is used to display the current users (Client) list of events.
 *
 * @version 1.0
 * @author Mike Howard
 * @since 1.3
 * @content_tag client
 * @content_tag event
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/event/event-loop.php
 * @package is the package type
 */

global $tmem_event;
?>
<?php do_action( 'tmem_pre_event_loop' ); ?>
<div id="post-<?php echo esc_attr( $tmem_event->ID ); ?>" class="<?php echo esc_attr( $tmem_event->post_status ); ?>">
	<table class="tmem-event-overview">
		<tr>
			<th class="tmem-event-heading">{event_name}<br />
				{event_date}
			</th>
			<th class="tmem-event-heading right-align"><?php esc_html_e( 'ID:', 'mobile-events-manager' ); ?> {contract_id}<br />
				<?php esc_html_e( 'Status:', 'mobile-events-manager' ); ?> {event_status}<br />
				<span class="tmem-edit"><?php printf( esc_html__( '<a href="%1$s">Manage %2$s</a>', 'mobile-events-manager' ), '{event_url}', esc_html( tmem_get_label_singular() ) ); ?></span>
			</th>
		</tr>
		<tr>
			<td><span class="tmem-event-label"><?php esc_html_e( 'Time', 'mobile-events-manager' ); ?></span><br />
				{start_time} - {end_time}<br />
				<span class="tmem-event-label"><?php esc_html_e( 'Duration', 'mobile-events-manager' ); ?></span><br />
				{event_duration}
			</td>
			<td rowspan="3" class="top-align"><span class="tmem-event-label"><?php esc_html_e( 'Venue', 'mobile-events-manager' ); ?></span><br />
				{venue}
				<br />
				{venue_full_address}
			</td>
		</tr>
		<tr>
			<td><span class="tmem-event-label"><?php printf( esc_html__( '%s Type', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></span><br />
				{event_type}
			</td>
		</tr>
		<tr>
			<td><span class="tmem-event-label"><?php esc_html_e( 'Cost Summary', 'mobile-events-manager' ); ?></span><br />
				<?php esc_html_e( 'Total Cost:', 'mobile-events-manager' ); ?> {total_cost}<br />
				{deposit_label}: {deposit} ({deposit_status})<br />
				{balance_label} <?php esc_html_e( 'Remaining', 'mobile-events-manager' ); ?>: {balance}
			</td>
		</tr>

		<?php
		/**
		 * Display event action buttons
		 */
		?>

		<?php $buttons = tmem_get_event_action_buttons( $tmem_event->ID, true ); ?>
		<?php $cells = 2; // Number of cells. ?>
		<?php $i = 1; // Counter for the current cell. ?>

		<?php do_action( 'tmem_pre_event_loop_action_buttons' ); ?>

		<?php foreach ( $buttons as $button ) : ?>

			<?php if ( 1 === $i ) : ?>
				<tr>
			<?php endif; ?><!-- endif( $i == 1 ) -->

					<td class="action-button"><?php printf( '<a class="tmem-action-button tmem-action-button-%s" href="%s">' . esc_attr__( $button['label'] . '</a>', tmem_get_option( 'action_button_colour', 'blue' ) ), esc_attr( $button['url'] ) ); ?></td>

			<?php if ( $i === $cells ) : ?>
				</tr>
				<?php $i = 0; ?>
			<?php endif; ?><!-- endif( $i == $cells ) -->

			<?php $i++; ?>

		<?php endforeach; ?><!-- endforeach( $buttons as $button ) -->

		<?php // Write out empty cells to complete the table row. ?>
		<?php if ( 1 !== $i ) : ?>

			<?php while ( $i <= $cells ) : ?>
				<td>&nbsp;</td>
				<?php $i++; ?>
				<?php
				if ( $i === $cells ) :
					?>
					 </tr><?php endif; ?>
			<?php endwhile; ?><!-- endwhile( $i <= $cells ) -->
			</tr>
		<?php endif; ?><!-- endif( $i < $cells ) -->
		<?php do_action( 'tmem_post_event_loop_action_buttons' ); ?>
	</table>
</div>
<?php do_action( 'tmem_post_event_loop' ); ?>
