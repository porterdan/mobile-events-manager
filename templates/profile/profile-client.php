<?php
/**
 * This template is used to generate the page for the shortcode [tmem-profile] and is used by clients editing their profile.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.5
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/playlist/playlist-guest.php
 * @package TMEM
 */

if ( ! is_user_logged_in() ) : ?>

	<?php echo esc_html( tmem_display_notice( 'login_profile' ) ); ?>
	<?php echo esc_attr( tmem_login_form( esc_url( tmem_get_current_page_url() ) ) ); ?>

	<?php
else :

	$client_id     = get_current_user_id();
	$client        = new TMEM_Client( esc_html( $client_id ) );
	$intro_text    = sprintf( esc_html__( 'Please keep your details up to date as incorrect information may cause problems with your %s.', 'mobile-events-manager' ), esc_html( esc_html( tmem_get_label_singular( true ) ) ) );
	$form_title    = __( 'Your Details', 'mobile-events-manager' );
	$password_text = __( 'To change your password, type the new password and confirm it below. Leave this field empty to keep your current password', 'mobile-events-manager' );
	$submit_label  = __( 'Update Details', 'mobile-events-manager' );

	$client_fields = $client->get_profile_fields();

	?>
	<div id="tmem_client_profile_wrap">
		<?php do_action( 'tmem_print_notices' ); ?>
		<div id="tmem_client_profile_form_wrap" class="tmem_clearfix">
			<?php do_action( 'tmem_before_client_profile_form' ); ?>

			<p><?php echo esc_attr( $intro_text ); ?></p>

					<form id="tmem_client_profile_form" class="tmem_form" method="post">
						<?php wp_nonce_field( 'update_client_profile', 'tmem_nonce', true, true ); ?>
						<input type="hidden" id="tmem_client_id" name="tmem_client_id" value="<?php echo esc_html( $client->ID ); ?>" />
						<input type="hidden" id="action" name="action" value="tmem_validate_client_profile" />

						<div class="tmem-alert tmem-alert-error tmem-hidden"></div>
						<div class="tmem-alert tmem-alert-success tmem-hidden"></div>

						<?php do_action( 'tmem_client_profile_form_top' ); ?>

						<fieldset id="tmem_client_profile_form_fields">
							<legend><?php echo esc_attr( $form_title ); ?></legend>

							<div id="tmem-client-profile-input-fields">

								<?php foreach ( $client->get_profile_fields() as $field ) : ?>
									<?php
									if ( tmem_display_client_field( $field ) ) :
										$tmem_id = esc_attr( $field['id'] );
										$label   = esc_attr( $field['label'] );
										?>

										<p class="tmem_<?php echo esc_html( $tmem_id ); ?>_field">
											<label for="tmem_<?php echo esc_html( $tmem_id ); ?>">
												<?php echo esc_html( $label ); ?> <?php
												if ( ! empty( $field['required'] ) ) :
													?>
													<span class="tmem-required-indicator">*</span><?php endif; ?>
											</label>

											<?php tmem_display_client_input_field( $field, $client ); ?>
										</p>

									<?php endif; ?>
								<?php endforeach; ?>

								<p><span class="tmem-description"><?php echo esc_html( $password_text ); ?></span></p>

								<p class="tmem_new_password_field">
									<label for="tmem_new_password">
										<?php esc_html_e( 'New Password', 'mobile-events-manager' ); ?>
									</label>

									<input name="tmem_new_password" id="tmem_new_password" type="password" autocomplete="off" />
								</p>

								<p class="tmem_confirm_password_field">
									<label for="tmem_confirm_password">
										<?php esc_html_e( 'Confirm New Password', 'mobile-events-manager' ); ?>
									</label>

									<input name="tmem_confirm_password" id="tmem_confirm_password" type="password" autocomplete="off" />
								</p>

								<?php do_action( 'tmem_client_profile_form_after_fields' ); ?>

								<div id="tmem_client_profile_submit_fields">
									<input class="button" name="update_profile_submit" id="update_profile_submit" type="submit" value="<?php echo esc_attr( $submit_label ); ?>" />
								</div>

								<?php do_action( 'tmem_client_profile_form_after_submit' ); ?>
							</div>

						</fieldset>

						<?php do_action( 'tmem_client_profile_form_bottom' ); ?>

					</form>

					<?php do_action( 'tmem_after_client_profile_form' ); ?>

		</div><!--end #tmem_guest_playlist_form_wrap-->
	</div><!-- end of #tmem_guest_playlist_wrap -->
<?php endif; ?>
