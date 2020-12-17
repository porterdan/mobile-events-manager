<?php
/**
 * Payment Form.
 *
 * @package TMEM
 * @subpackage Payments
 * @since 1.3.8
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
function tmem_payment_form() {
	global $tmem_event;

	$payment_mode = tmem_get_chosen_gateway();

	ob_start();
		echo '<div id="tmem_payment_wrap">';
			do_action( 'tmem_print_notices' );
			echo '<p class="head-nav"><a href="' . tmem_get_event_uri( $tmem_event->ID ) . '">' . __( 'Back to Event', 'mobile-events-manager' ) . '</a></p>';
	?>
			<div id="tmem_payments_form_wrap" class="tmem_clearfix">
				<?php do_action( 'tmem_before_purchase_form' ); ?>
				<form id="tmem_payment_form" class="tmem_form" action="" method="POST" autocomplete="off">
					<input type="hidden" name="event_id" id="tmem-event-id" value="<?php echo $tmem_event->ID; ?>" />
					<?php
					tmem_payment_items();
					/**
					 * Hooks in at the top of the payment form
					 *
					 * @since 1.3.8
					 */
					do_action( 'tmem_payment_form_top' );

					if ( tmem_show_gateways() ) {
						do_action( 'tmem_payment_mode_select' );
					} else {
						do_action( 'tmem_payment_form' );
					}

					/**
					 * Hooks in at the bottom of the checkout form
					 *
					 * @since 1.0
					 */
					do_action( 'tmem_checkout_form_bottom' )
					?>
				</form>
				<?php do_action( 'tmem_after_purchase_form' ); ?>
			</div><!--end #tmem_payments_form_wrap-->
	<?php
		echo '</div><!--end #tmem_payment_wrap-->';

	return ob_get_clean();

} // tmem_payment_form

/**
 * Display the items that the client can pay for.
 *
 * @since 1.3.8
 * @return void
 */
function tmem_payment_items() {
	do_action( 'tmem_before_payment_items' );
		echo '<div id="tmem_payment_items_wrap">';
			tmem_get_template_part( 'payments', 'items' );
		echo '</div>';
	do_action( 'tmem_after_payment_items' );
} // tmem_payment_items

/**
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the TMEM Settings, it will be
 * automatically selected.
 *
 * @since 1.3.8
 * @return void
 */
function tmem_payment_mode_select() {
	$gateways = tmem_get_enabled_payment_gateways( true );
	$page_URL = tmem_get_current_page_url();
	do_action( 'tmem_payment_mode_top' );
	?>
		<fieldset id="tmem_payment_mode_select">
			<legend><?php esc_html_e( 'Select Payment Method', 'mobile-events-manager' ); ?></legend>
			<?php do_action( 'tmem_payment_mode_before_gateways_wrap' ); ?>
			<div id="tmem-payment-mode-wrap">
				<?php

				do_action( 'tmem_payment_mode_before_gateways' );

				foreach ( $gateways as $gateway_id => $gateway ) {

					$checked       = checked( $gateway_id, tmem_get_default_gateway(), false );
					$checked_class = $checked ? ' tmem-gateway-option-selected' : '';
					echo '<label for="tmem-gateway-' . esc_attr( $gateway_id ) . '" class="tmem-gateway-option' . $checked_class . '" id="tmem-gateway-option-' . esc_attr( $gateway_id ) . '">';
						echo '<input type="radio" name="payment-mode" class="tmem-gateway" id="tmem-gateway-' . esc_attr( $gateway_id ) . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . '>' . esc_html( $gateway['payment_label'] );
					echo '</label>';
				}

				do_action( 'tmem_payment_mode_after_gateways' );

				?>
			</div>
			<?php do_action( 'tmem_payment_mode_after_gateways_wrap' ); ?>
		</fieldset>
	<div id="tmem_payment_form_wrap"></div><!-- the fields are loaded into this-->
	<?php
	do_action( 'tmem_payment_mode_bottom' );
} // tmem_payment_mode_select
add_action( 'tmem_payment_mode_select', 'tmem_payment_mode_select' );

/**
 * Renders the Payment Form, hooks are provided to add to the payment form.
 * The default Payment Form rendered displays a list of the enabled payment
 * gateways and a credit card info form if credit cards are enabled.
 *
 * @since 1.3.8
 * @return str
 */
function tmem_show_payment_form() {

	$payment_mode = tmem_get_chosen_gateway();

	/**
	 * Hooks in at the top of the purchase form
	 *
	 * @since 1.3.8
	 */
	do_action( 'tmem_payment_form_top' );

	do_action( 'tmem_payment_form_after_user_info' );

	/**
	 * Hooks in before Credit Card Form
	 *
	 * @since 1.3.8
	 */
	do_action( 'tmem_payment_form_before_cc_form' );

	// Load the credit card form and allow gateways to load their own if they wish.
	if ( has_action( 'tmem_' . $payment_mode . '_cc_form' ) ) {
		do_action( 'tmem_' . $payment_mode . '_cc_form' );
	} else {
		do_action( 'tmem_cc_form' );
	}

	/**
	 * Hooks in after Credit Card Form
	 *
	 * @since 1.3.8
	 */
	do_action( 'tmem_payment_form_after_cc_form' );

	/**
	 * Hooks in at the bottom of the payment form
	 *
	 * @since 1.3.8
	 */
	do_action( 'tmem_payment_form_bottom' );

} // tmem_show_payment_form
add_action( 'tmem_payment_form', 'tmem_show_payment_form' );

/**
 * Renders the credit card info form.
 *
 * @since 1.3.8
 * @return void
 */
function tmem_get_cc_form() {
	ob_start();
	?>

	<?php do_action( 'tmem_before_cc_fields' ); ?>

		<?php tmem_get_template_part( 'payments', 'cc' ); ?>

	<?php
	do_action( 'tmem_after_cc_fields' );

	echo ob_get_clean();
} // tmem_get_cc_form
add_action( 'tmem_cc_form', 'tmem_get_cc_form' );

/**
 * Renders the Payment Submit section
 *
 * @since 1.3.8
 * @return void
 */
function tmem_payment_submit() {

	if ( ! tmem_has_gateway() ) {
		return;
	}

	ob_start();
	?>

	<fieldset id="tmem_payment_submit">
		<?php do_action( 'tmem_payment_form_before_submit' ); ?>

		<?php tmem_payment_hidden_fields(); ?>
		<div class="tmem-alert tmem-alert-error tmem-hidden"></div>
		<input type="submit" name="tmem_payment_submit" id="tmem-payment-submit" value="<?php echo tmem_get_payment_button_text(); ?>" />

		<?php do_action( 'tmem_payment_form_after_submit' ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
} // tmem_payment_submit
add_action( 'tmem_payment_form_after_cc_form', 'tmem_payment_submit', 9999 );

/**
 * Renders the hidden Payment fields
 *
 * @since 1.3.8
 * @return void
 */
function tmem_payment_hidden_fields() {
	?>
	<?php tmem_action_field( 'event_payment' ); ?>
	<input type="hidden" name="tmem_gateway" id="tmem_gateway" value="<?php echo tmem_get_chosen_gateway(); ?>" />
	<?php
} // tmem_payment_hidden_fields

/**
 * Renders an alert if no gateways are defined.
 *
 * @since 1.3.8
 * @return void
 */
function tmem_no_gateway_notice() {

	if ( tmem_has_gateway() ) {
		return;
	}

	ob_start();

	$notice = __( 'A gateway must be installed and enabled within TMEM Event Management before payments can be processed.', 'mobile-events-manager' );
	?>
	<div class="tmem-alert tmem-alert-error"><?php echo $notice; ?></div>

	<?php
	echo ob_get_clean();

} // tmem_alert_no_gateway
add_action( 'tmem_before_payment_items', 'tmem_no_gateway_notice' );
