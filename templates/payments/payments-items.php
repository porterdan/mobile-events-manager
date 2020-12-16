<?php
/**
 * This template is used to display the items which can be paid for on the payment form.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3.8
 * @content_tag {client_*}
 * @content_tag {event_*}
 * @shortcodes Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/payments/payments-items.php
 * @package is the package type
 */

global $mem_event;
$deposit_disabled = '';
if ( 'Paid' === $mem_event->get_deposit_status() ) {
	$deposit_disabled = ' disabled = "true"';
}

$balance_disabled = '';
if ( 'Paid' === $mem_event->get_balance_status() ) {
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
<fieldset id="mem-payment-value">
	<legend><?php esc_html_e( 'Select Payment Amount', 'mobile-events-manager' ); ?></legend>
	<p class="mem-payment-amount">
		<input type="radio" name="mem_payment_amount" id="mem-payment-deposit" value="deposit"<?php echo esc_attr( $deposit_disabled ); ?><?php checked( $selected, 'deposit' ); ?> /> <?php echo esc_html( mem_get_deposit_label() ); ?> &ndash; <?php echo esc_html( mem_currency_filter( esc_attr( mem_format_amount( $mem_event->get_remaining_deposit() ) ) ) ); ?><br />

		<input type="radio" name="mem_payment_amount" id="mem-payment-balance" value="balance"<?php echo esc_attr( $balance_disabled ); ?><?php checked( $selected, 'balance' ); ?> /> <?php echo esc_html( mem_get_balance_label() ); ?> &ndash; <?php echo esc_attr( mem_currency_filter( mem_format_amount( $mem_event->get_balance() ) ) ); ?><br />

		<input type="radio" name="mem_payment_amount" id="mem-payment-part" value="part_payment"<?php checked( $selected, 'part_payment' ); ?> /> <?php echo esc_attr( mem_get_other_amount_label() ); ?> <span id="mem-payment-custom"<?php echo esc_attr( $other_amount_style ); ?>><?php echo esc_attr( mem_currency_symbol() ); ?><input type="text" class="mem_other_amount_input mem-input" name="part_payment" id="part-payment" placeholder="0.00" size="10" value="<?php echo esc_html( mem_sanitize_amount( mem_get_option( 'other_amount_default', true, false ) ) ); ?>" /></span>
		/* translators: %s is custom description for the pay button */
	<span class="mem-description"><?php esc_html_e( __( 'To pay a custom amount, select %s and enter the value into the text field.', 'mobile-events-manager' ), mem_get_other_amount_label() ); ?></span>
	</p>
</fieldset>
