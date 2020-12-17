<?php
/**
 * This template is used to display the items which can be paid for on the payment form.
 *
 * @version 1.0
 * @author Mike Howard
 * @since 1.3.8
 * @content_tag {client_*}
 * @content_tag {event_*}
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/payments/payments-items.php
 * @package is the package type
 */

global $tmem_event;
$deposit_disabled = '';
if ( 'Paid' === $tmem_event->get_deposit_status() ) {
	$deposit_disabled = ' disabled = "true"';
}

$balance_disabled = '';
if ( 'Paid' === $tmem_event->get_balance_status() ) {
	$balance_disabled = ' disabled = "true"';
}
$other_amount_style = ' style="display: none;"';
if ( ! empty( $balance_disabled ) && ! empty( $deposit_disabled ) ) {
	$other_amount_style = '';
}

if ( empty( $deposit_disabled ) ) {
	$selected = 'deposit';
} elseif ( empty( $balance_disabled ) ) {
	$selected = 'balance';
} else {
	$selected = 'part_payment';
}

?>
<fieldset id="tmem-payment-value">
	<legend><?php esc_html_e( 'Select Payment Amount', 'mobile-events-manager' ); ?></legend>
	<p class="tmem-payment-amount">
		<input type="radio" name="tmem_payment_amount" id="tmem-payment-deposit" value="deposit"<?php echo esc_attr( $deposit_disabled ); ?><?php checked( $selected, 'deposit' ); ?> /> <?php echo esc_html( tmem_get_deposit_label() ); ?> &ndash; <?php echo esc_html( tmem_currency_filter( esc_attr( tmem_format_amount( $tmem_event->get_remaining_deposit() ) ) ) ); ?><br />

		<input type="radio" name="tmem_payment_amount" id="tmem-payment-balance" value="balance"<?php echo esc_attr( $balance_disabled ); ?><?php checked( $selected, 'balance' ); ?> /> <?php echo esc_html( tmem_get_balance_label() ); ?> &ndash; <?php echo esc_attr( tmem_currency_filter( tmem_format_amount( $tmem_event->get_balance() ) ) ); ?><br />

		<input type="radio" name="tmem_payment_amount" id="tmem-payment-part" value="part_payment"<?php checked( $selected, 'part_payment' ); ?> /> <?php echo esc_attr( tmem_get_other_amount_label() ); ?> <span id="tmem-payment-custom"<?php echo esc_attr( $other_amount_style ); ?>><?php echo esc_attr( tmem_currency_symbol() ); ?><input type="text" class="tmem_other_amount_input tmem-input" name="part_payment" id="part-payment" placeholder="0.00" size="10" value="<?php echo esc_html( tmem_sanitize_amount( tmem_get_option( 'other_amount_default', true, false ) ) ); ?>" /></span>
		/* translators: %s is custom description for the pay button */
	<span class="tmem-description"><?php esc_html_e( __( 'To pay a custom amount, select %s and enter the value into the text field.', 'mobile-events-manager' ), tmem_get_other_amount_label() ); ?></span>
	</p>
</fieldset>
