<?php
/**
 * This template is used to display the contract page content.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag client
 * @content_tag event
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/contract/contract.php
 * @package is the package type
 */

global $mem_event;
?>

<div id="mem-contract-wrapper">
	<?php do_action( 'mem_pre_contract', $mem_event->ID ); ?>

	<div id="mem-contract-header">

		<?php do_action( 'mem_print_notices' ); ?>

		<p class="head-nav"><a href="{event_url}>"><?php printf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></a></p>

		<?php do_action( 'mem_pre_contract_header', $mem_event->ID ); ?>

		<p>
		<?php
		printf(
			esc_html__( __( 'The contract for your %1$s taking place on %2$s is displayed below.', 'mobile-events-manager' ) ),
			esc_html( mem_get_label_singular( true ) ),
			'{event_date}'
		);
		?>
				</p>

		<?php if ( 'mem-contract' === $mem_event->post_status ) : ?>

			<p>
			<?php
			printf(
				esc_html__( __( 'When ready, please <a href="%s">scroll to the bottom</a> of this page to confirm your acceptance of the contractual terms and digitally sign the contract.', 'mobile-events-manager' ) ),
				'#signature_form'
			);
			?>
				</p>

			<p><?php esc_html_e( 'Once the contract is signed, you will receive a confirmation email from us.', 'mobile-events-manager' ); ?></p>

		<?php else : ?>
			<p class="mem-contract-notready">
			<?php
			printf(
				esc_html__( __( 'You cannot yet sign your contract as you have not indicated that you would like to proceed with your %1$s. Please return to the <a href="%2$s">event details</a> screen to confirm that you wish to proceed.', 'mobile-events-manager' ) ),
				esc_html( mem_get_label_singular( true ) ),
				'{event_url}'
			);
			?>
				</p>

			<?php
		endif;
		'mem-contract' === $mem_event->post_status;
		?>

		<?php do_action( 'mem_pre_contract_content', $mem_event->ID ); ?>
	</div><!-- end mem-contract-header -->

	<hr />
	<div id="mem-contract-content">

		<?php do_action( 'mem_pre_contract_content', $mem_event->ID ); ?>

		<?php echo esc_attr( mem_show_contract( $mem_event->get_contract(), $mem_event ) ); ?>

		<?php do_action( 'mem_post_contract_footer', $mem_event->ID ); ?>

	</div><!-- end mem-contract-content -->
	<hr />

	<div id="mem-contract-footer">
		<?php do_action( 'mem_pre_contract_footer', $mem_event->ID ); ?>
		<?php $disabled = ''; ?>

		<a id="signature_form"></a>
		<?php if ( 'mem-contract' !== $mem_event->post_status ) : ?>

			<?php $disabled = ' disabled="disabled"'; ?>
			<p class="mem-contract-notready">
			<?php
			printf(
				esc_html__( __( 'You cannot yet sign your contract as you have not indicated that you would like to proceed with your %1$s. Please return to the <a href="%2$s">event details</a> screen to confirm that you wish to proceed.', 'mobile-events-manager' ) ),
				esc_html( mem_get_label_singular( true ) ),
				'{event_url}'
			);
			?>
			</p>

			<?php
		endif;
		'mem-contract' !== $mem_event->post_status;
		?>

		<div id="mem-contract-signature-form">
			<form name="mem-signature-form" id="mem-signature-form" method="post" action="<?php echo esc_html( mem_get_current_page_url() ); ?>">
				<?php wp_nonce_field( 'sign_contract', 'mem_nonce', true, true ); ?>
				<?php mem_action_field( 'sign_event_contract' ); ?>
				<input type="hidden" id="event_id" name="event_id" value="<?php echo esc_attr( $mem_event->ID ); ?>" />

				<div class="row mem-contract-signatory-name">
					<div class="col first-name">
						<p><label for="mem_first_name"><?php esc_html_e( 'First Name:', 'mobile-events-manager' ); ?></label><br />
							<input type="text" name="mem_first_name" id="mem_first_name" data-placeholder="<?php esc_html_e( 'First Name', 'mobile-events-manager' ); ?>" size="20"<?php echo esc_attr( $disabled ); ?> /></p>
					</div>

					<div class="last last-name">
						<p><label for="mem_last_name"><?php esc_html_e( 'Last Name:', 'mobile-events-manager' ); ?></label><br />
							<input type="text" name="mem_last_name" id="mem_last_name" data-placeholder="<?php esc_html_e( 'Last Name', 'mobile-events-manager' ); ?>" size="20"<?php echo esc_attr( $disabled ); ?> /></p>
					</div>
				</div>

				<div class="row mem-contract-signatory-terms">
					<p><input type="checkbox" name="mem_accept_terms" id="mem_accept_terms" value="accept"<?php echo esc_attr( $disabled ); ?> /> <label for="mem_accept_terms"><?php esc_html_e( 'I hereby confirm that I have read and accept the contract and its terms', 'mobile-events-manager' ); ?></label></p>
				</div>

				<div class="row mem-contract-signatory-client">
					<p><input type="checkbox" name="mem_confirm_client" id="mem_confirm_client" value="yes"<?php esc_attr( $disabled ); ?> /> <label for="mem_confirm_client"><?php esc_html_e( 'I hereby confirm that the person named within the above contract is me and that all associated details are correct', 'mobile-events-manager' ); ?></label></p>
				</div>

				<div class="row mem-contract-signatory-password">
					<p><label for="mem_verify_password"><?php esc_html_e( 'Enter Your Password:', 'mobile-events-manager' ); ?></label><br />
						<input type="password" name="mem_verify_password" id="mem_verify_password" size="20"<?php esc_attr( $disabled ); ?> /></p>
				</div>

				<div class="row mem-contract-sign">
					<p><input type="submit" name="mem_submit_sign_contract" id="mem_submit_sign_contract" value="<?php esc_html_e( 'Sign Contract', 'mobile-events-manager' ); ?>"<?php esc_attr( $disabled ); ?> /></p>
				</div>
			</form>
		</div><!-- end mem-signature-form -->

		<?php do_action( 'mem_post_contract_footer', $mem_event->ID ); ?>
	</div><!-- end mem-contract-footer -->

</div><!-- end mem-contract-wrapper -->
