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
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/contract/contract.php
 * @package is the package type
 */

global $tmem_event;
?>

<div id="tmem-contract-wrapper">
	<?php do_action( 'tmem_pre_contract', $tmem_event->ID ); ?>

	<div id="tmem-contract-header">

		<?php do_action( 'tmem_print_notices' ); ?>

		<p class="head-nav"><a href="{event_url}>"><?php printf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></a></p>

		<?php do_action( 'tmem_pre_contract_header', $tmem_event->ID ); ?>

		<p>
		<?php
		printf(
			esc_html__( __( 'The contract for your %1$s taking place on %2$s is displayed below.', 'mobile-events-manager' ) ),
			esc_html( tmem_get_label_singular( true ) ),
			'{event_date}'
		);
		?>
				</p>

		<?php if ( 'tmem-contract' === $tmem_event->post_status ) : ?>

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
			<p class="tmem-contract-notready">
			<?php
			printf(
				esc_html__( __( 'You cannot yet sign your contract as you have not indicated that you would like to proceed with your %1$s. Please return to the <a href="%2$s">event details</a> screen to confirm that you wish to proceed.', 'mobile-events-manager' ) ),
				esc_html( tmem_get_label_singular( true ) ),
				'{event_url}'
			);
			?>
				</p>

			<?php
		endif;
		'tmem-contract' === $tmem_event->post_status;
		?>

		<?php do_action( 'tmem_pre_contract_content', $tmem_event->ID ); ?>
	</div><!-- end tmem-contract-header -->

	<hr />
	<div id="tmem-contract-content">

		<?php do_action( 'tmem_pre_contract_content', $tmem_event->ID ); ?>

		<?php echo esc_attr( tmem_show_contract( $tmem_event->get_contract(), $tmem_event ) ); ?>

		<?php do_action( 'tmem_post_contract_footer', $tmem_event->ID ); ?>

	</div><!-- end tmem-contract-content -->
	<hr />

	<div id="tmem-contract-footer">
		<?php do_action( 'tmem_pre_contract_footer', $tmem_event->ID ); ?>
		<?php $disabled = ''; ?>

		<a id="signature_form"></a>
		<?php if ( 'tmem-contract' !== $tmem_event->post_status ) : ?>

			<?php $disabled = ' disabled="disabled"'; ?>
			<p class="tmem-contract-notready">
			<?php
			printf(
				esc_html__( __( 'You cannot yet sign your contract as you have not indicated that you would like to proceed with your %1$s. Please return to the <a href="%2$s">event details</a> screen to confirm that you wish to proceed.', 'mobile-events-manager' ) ),
				esc_html( tmem_get_label_singular( true ) ),
				'{event_url}'
			);
			?>
			</p>

			<?php
		endif;
		'tmem-contract' !== $tmem_event->post_status;
		?>

		<div id="tmem-contract-signature-form">
			<form name="tmem-signature-form" id="tmem-signature-form" method="post" action="<?php echo esc_html( tmem_get_current_page_url() ); ?>">
				<?php wp_nonce_field( 'sign_contract', 'tmem_nonce', true, true ); ?>
				<?php tmem_action_field( 'sign_event_contract' ); ?>
				<input type="hidden" id="event_id" name="event_id" value="<?php echo esc_attr( $tmem_event->ID ); ?>" />

				<div class="row tmem-contract-signatory-name">
					<div class="col first-name">
						<p><label for="tmem_first_name"><?php esc_html_e( 'First Name:', 'mobile-events-manager' ); ?></label><br />
							<input type="text" name="tmem_first_name" id="tmem_first_name" data-placeholder="<?php esc_html_e( 'First Name', 'mobile-events-manager' ); ?>" size="20"<?php echo esc_attr( $disabled ); ?> /></p>
					</div>

					<div class="last last-name">
						<p><label for="tmem_last_name"><?php esc_html_e( 'Last Name:', 'mobile-events-manager' ); ?></label><br />
							<input type="text" name="tmem_last_name" id="tmem_last_name" data-placeholder="<?php esc_html_e( 'Last Name', 'mobile-events-manager' ); ?>" size="20"<?php echo esc_attr( $disabled ); ?> /></p>
					</div>
				</div>

				<div class="row tmem-contract-signatory-terms">
					<p><input type="checkbox" name="tmem_accept_terms" id="tmem_accept_terms" value="accept"<?php echo esc_attr( $disabled ); ?> /> <label for="tmem_accept_terms"><?php esc_html_e( 'I hereby confirm that I have read and accept the contract and its terms', 'mobile-events-manager' ); ?></label></p>
				</div>

				<div class="row tmem-contract-signatory-client">
					<p><input type="checkbox" name="tmem_confirm_client" id="tmem_confirm_client" value="yes"<?php esc_attr( $disabled ); ?> /> <label for="tmem_confirm_client"><?php esc_html_e( 'I hereby confirm that the person named within the above contract is me and that all associated details are correct', 'mobile-events-manager' ); ?></label></p>
				</div>

				<div class="row tmem-contract-signatory-password">
					<p><label for="tmem_verify_password"><?php esc_html_e( 'Enter Your Password:', 'mobile-events-manager' ); ?></label><br />
						<input type="password" name="tmem_verify_password" id="tmem_verify_password" size="20"<?php esc_attr( $disabled ); ?> /></p>
				</div>

				<div class="row tmem-contract-sign">
					<p><input type="submit" name="tmem_submit_sign_contract" id="tmem_submit_sign_contract" value="<?php esc_html_e( 'Sign Contract', 'mobile-events-manager' ); ?>"<?php esc_attr( $disabled ); ?> /></p>
				</div>
			</form>
		</div><!-- end tmem-signature-form -->

		<?php do_action( 'tmem_post_contract_footer', $tmem_event->ID ); ?>
	</div><!-- end tmem-contract-footer -->

</div><!-- end tmem-contract-wrapper -->
