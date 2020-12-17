<?php
/**
 * This template is used when no alternative is provided.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3.8
 * @content_tag {client_*}
 * @content_tag {event_*}
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/payments/payments-cc.php
 * @package TMEM
 */

global $tmem_event;
?>

	<?php do_action( 'tmem_pre_default_payments_form' ); ?>
	<div class="tmem-alert tmem-alert-error tmem-hidden"></div>
	<p class="tmem-default-form-text"><?php esc_html_e( 'Once you have selected your Payment Amount, click Pay Now to checkout', 'mobile-events-manager' ); ?></p>
	<?php do_action( 'tmem_after_default_payments_form' ); ?>
