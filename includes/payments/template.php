<?php
/**
 * Payment Form.
 *
 * @package MEM
 * @subpackage Payments
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Payment Form
 *
 * @since 1.3.8
 * @return str
 */
function mem_payment_form() {
	global $mem_event;

	$payment_mode = mem_get_chosen_gateway();

	ob_start();
		echo '<div id="mem_payment_wrap">';
			do_action( 'mem_print_notices' );
			echo '<p class="head-nav"><a href="' . mem_get_event_uri( $mem_event->ID ) . '">' . __( 'Back to Event', 'mobile-events-manager' ) . '</a></p>';
	?>
			<div id="mem_payments_form_wrap" class="mem_clearfix">
				<?php do_action( 'mem_before_purchase_form' ); ?>
				<form id="mem_payment_form" class="mem_form" action="" method="POST" autocomplete="off">
					<input type="hidden" name="event_id" id="mem-event-id" value="<?php echo $mem_event->ID; ?>" />
					<?php
					mem_payment_items();
					/**
					 * Hooks in at the top of the payment form
					 *
					 * @since 1.3.8
					 */
					do_action( 'mem_payment_form_top' );

					if ( mem_show_gateways() ) {
						do_action( 'mem_payment_mode_select' );
					} else {
						do_action( 'mem_payment_form' );
					}

					/**
					 * Hooks in at the bottom of the checkout form
					 *
					 * @since 1.0
					 */
					do_action( 'mem_checkout_form_bottom' )
					?>
				</form>
				<?php do_action( 'mem_after_purchase_form' ); ?>
			</div><!--end #mem_payments_form_wrap-->
	<?php
		echo '</div><!--end #mem_payment_wrap-->';

	return ob_get_clean();

} // mem_payment_form

/**
 * Display the items that the client can pay for.
 *
 * @since 1.3.8
 * @return void
 */
function mem_payment_items() {
	do_action( 'mem_before_payment_items' );
		echo '<div id="mem_payment_items_wrap">';
			mem_get_template_part( 'payments', 'items' );
		echo '</div>';
	do_action( 'mem_after_payment_items' );
} // mem_payment_items

/**
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the MEM Settings, it will be
 * automatically selected.
 *
 * @since 1.3.8
 * @return void
 */
function mem_payment_mode_select() {
	$gateways = mem_get_enabled_payment_gateways( true );
	$page_URL = mem_get_current_page_url();
	do_action( 'mem_payment_mode_top' );
	?>
		<fieldset id="mem_payment_mode_select">
			<legend><?php esc_html_e( 'Select Payment Method', 'mobile-events-manager' ); ?></legend>
			<?php do_action( 'mem_payment_mode_before_gateways_wrap' ); ?>
			<div id="mem-payment-mode-wrap">
				<?php

				do_action( 'mem_payment_mode_before_gateways' );

				foreach ( $gateways as $gateway_id => $gateway ) {

					$checked       = checked( $gateway_id, mem_get_default_gateway(), false );
					$checked_class = $checked ? ' mem-gateway-option-selected' : '';
					echo '<label for="mem-gateway-' . esc_attr( $gateway_id ) . '" class="mem-gateway-option' . $checked_class . '" id="mem-gateway-option-' . esc_attr( $gateway_id ) . '">';
						echo '<input type="radio" name="payment-mode" class="mem-gateway" id="mem-gateway-' . esc_attr( $gateway_id ) . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . '>' . esc_html( $gateway['payment_label'] );
					echo '</label>';
				}

				do_action( 'mem_payment_mode_after_gateways' );

				?>
			</div>
			<?php do_action( 'mem_payment_mode_after_gateways_wrap' ); ?>
		</fieldset>
	<div id="mem_payment_form_wrap"></div><!-- the fields are loaded into this-->
	<?php
	do_action( 'mem_payment_mode_bottom' );
} // mem_payment_mode_select
add_action( 'mem_payment_mode_select', 'mem_payment_mode_select' );

/**
 * Renders the Payment Form, hooks are provided to add to the payment form.
 * The default Payment Form rendered displays a list of the enabled payment
 * gateways and a credit card info form if credit cards are enabled.
 *
 * @since 1.3.8
 * @return str
 */
function mem_show_payment_form() {

	$payment_mode = mem_get_chosen_gateway();

	/**
	 * Hooks in at the top of the purchase form
	 *
	 * @since 1.3.8
	 */
	do_action( 'mem_payment_form_top' );

	do_action( 'mem_payment_form_after_user_info' );

	/**
	 * Hooks in before Credit Card Form
	 *
	 * @since 1.3.8
	 */
	do_action( 'mem_payment_form_before_cc_form' );

	// Load the credit card form and allow gateways to load their own if they wish.
	if ( has_action( 'mem_' . $payment_mode . '_cc_form' ) ) {
		do_action( 'mem_' . $payment_mode . '_cc_form' );
	} else {
		do_action( 'mem_cc_form' );
	}

	/**
	 * Hooks in after Credit Card Form
	 *
	 * @since 1.3.8
	 */
	do_action( 'mem_payment_form_after_cc_form' );

	/**
	 * Hooks in at the bottom of the payment form
	 *
	 * @since 1.3.8
	 */
	do_action( 'mem_payment_form_bottom' );

} // mem_show_payment_form
add_action( 'mem_payment_form', 'mem_show_payment_form' );

/**
 * Renders the credit card info form.
 *
 * @since 1.3.8
 * @return void
 */
function mem_get_cc_form() {
	ob_start();
	?>

	<?php do_action( 'mem_before_cc_fields' ); ?>

		<?php mem_get_template_part( 'payments', 'cc' ); ?>

	<?php
	do_action( 'mem_after_cc_fields' );

	echo ob_get_clean();
} // mem_get_cc_form
add_action( 'mem_cc_form', 'mem_get_cc_form' );

/**
 * Renders the Payment Submit section
 *
 * @since 1.3.8
 * @return void
 */
function mem_payment_submit() {

	if ( ! mem_has_gateway() ) {
		return;
	}

	ob_start();
	?>

	<fieldset id="mem_payment_submit">
		<?php do_action( 'mem_payment_form_before_submit' ); ?>

		<?php mem_payment_hidden_fields(); ?>
		<div class="mem-alert mem-alert-error mem-hidden"></div>
		<input type="submit" name="mem_payment_submit" id="mem-payment-submit" value="<?php echo mem_get_payment_button_text(); ?>" />

		<?php do_action( 'mem_payment_form_after_submit' ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
} // mem_payment_submit
add_action( 'mem_payment_form_after_cc_form', 'mem_payment_submit', 9999 );

/**
 * Renders the hidden Payment fields
 *
 * @since 1.3.8
 * @return void
 */
function mem_payment_hidden_fields() {
	?>
	<?php mem_action_field( 'event_payment' ); ?>
	<input type="hidden" name="mem_gateway" id="mem_gateway" value="<?php echo mem_get_chosen_gateway(); ?>" />
	<?php
} // mem_payment_hidden_fields

/**
 * Renders an alert if no gateways are defined.
 *
 * @since 1.3.8
 * @return void
 */
function mem_no_gateway_notice() {

	if ( mem_has_gateway() ) {
		return;
	}

	ob_start();

	$notice = __( 'A gateway must be installed and enabled within Mobile Events Manager (MEM) before payments can be processed.', 'mobile-events-manager' );
	?>
	<div class="mem-alert mem-alert-error"><?php echo $notice; ?></div>

	<?php
	echo ob_get_clean();

} // mem_alert_no_gateway
add_action( 'mem_before_payment_items', 'mem_no_gateway_notice' );
