<?php
/**
 * This template is used when the current logged in client has no events.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag {client_*}
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/event/event-none.php
 * @package is the name of the package
 */

?>
<div id="client-no-events">
	<p><?php ( esc_html_e( 'Hey,', 'mobile-events-manager' ) ); ?> {client_firstname}
				<?php
					printf(
						esc_html( __( 'welcome to the %1$s %2$s.', 'mobile-events-manager' ) ),
						'{company_name}',
						'{application_name}'
					);
					?>
		</p>

	<p><?php printf( esc_html__( 'You do not currently have any active %s booked with us.', 'mobile-events-manager' ), esc_html( tmem_get_label_plural() ) ); ?></p>

	<p>
	<?php
	printf(
		__( 'If you are ready to plan your next %1$s, contact us <a href="%2$s">here</a>.', 'mobile-events-manager' ),
		esc_html( tmem_get_label_singular( true ) ),
		'{contact_page}'
	);
	?>
		</p>
</div>
