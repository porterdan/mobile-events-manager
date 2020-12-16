<?php
/**
 * This template is used to generate the page for the shortcode [mem-profile] and is used by clients editing their profile.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.5
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/playlist/playlist-guest.php
 * @package MEM
 */

if ( ! is_user_logged_in() ) : ?>

	<?php echo esc_html( mem_display_notice( 'login_profile' ) ); ?>
	<?php echo esc_attr( mem_login_form( esc_url( mem_get_current_page_url() ) ) ); ?>

	<?php
else :

	$client_id     = get_current_user_id();
	$client        = new MEM_Client( esc_html( $client_id ) );
	$intro_text    = sprintf( esc_html__( 'Please keep your details up to date as incorrect information may cause problems with your %s.', 'mobile-events-manager' ), esc_html( esc_html( mem_get_label_singular( true ) ) ) );
	$form_title    = __( 'Your Details', 'mobile-events-manager' );
	$password_text = __( 'To change your password, type the new password and confirm it below. Leave this field empty to keep your current password', 'mobile-events-manager' );
	$submit_label  = __( 'Update Details', 'mobile-events-manager' );

	$client_fields = $client->get_profile_fields();

	?>
	<div id="mem_client_profile_wrap">
		<?php do_action( 'mem_print_notices' ); ?>
		<div id="mem_client_profile_form_wrap" class="mem_clearfix">
			<?php do_action( 'mem_before_client_profile_form' ); ?>

			<p><?php echo esc_attr( $intro_text ); ?></p>

					<form id="mem_client_profile_form" class="mem_form" method="post">
						<?php wp_nonce_field( 'update_client_profile', 'mem_nonce', true, true ); ?>
						<input type="hidden" id="mem_client_id" name="mem_client_id" value="<?php echo esc_html( $client->ID ); ?>" />
						<input type="hidden" id="action" name="action" value="mem_validate_client_profile" />

						<div class="mem-alert mem-alert-error mem-hidden"></div>
						<div class="mem-alert mem-alert-success mem-hidden"></div>

						<?php do_action( 'mem_client_profile_form_top' ); ?>

						<fieldset id="mem_client_profile_form_fields">
							<legend><?php echo esc_attr( $form_title ); ?></legend>

							<div id="mem-client-profile-input-fields">

								<?php foreach ( $client->get_profile_fields() as $field ) : ?>
									<?php
									if ( mem_display_client_field( $field ) ) :
										$mem_id = esc_attr( $field['id'] );
										$label   = esc_attr( $field['label'] );
										?>

										<p class="mem_<?php echo esc_html( $mem_id ); ?>_field">
											<label for="mem_<?php echo esc_html( $mem_id ); ?>">
												<?php echo esc_html( $label ); ?> <?php
												if ( ! empty( $field['required'] ) ) :
													?>
													<span class="mem-required-indicator">*</span><?php endif; ?>
											</label>

											<?php mem_display_client_input_field( $field, $client ); ?>
										</p>

									<?php endif; ?>
								<?php endforeach; ?>

								<p><span class="mem-description"><?php echo esc_html( $password_text ); ?></span></p>

								<p class="mem_new_password_field">
									<label for="mem_new_password">
										<?php esc_html_e( 'New Password', 'mobile-events-manager' ); ?>
									</label>

									<input name="mem_new_password" id="mem_new_password" type="password" autocomplete="off" />
								</p>

								<p class="mem_confirm_password_field">
									<label for="mem_confirm_password">
										<?php esc_html_e( 'Confirm New Password', 'mobile-events-manager' ); ?>
									</label>

									<input name="mem_confirm_password" id="mem_confirm_password" type="password" autocomplete="off" />
								</p>

								<?php do_action( 'mem_client_profile_form_after_fields' ); ?>

								<div id="mem_client_profile_submit_fields">
									<input class="button" name="update_profile_submit" id="update_profile_submit" type="submit" value="<?php echo esc_attr( $submit_label ); ?>" />
								</div>

								<?php do_action( 'mem_client_profile_form_after_submit' ); ?>
							</div>

						</fieldset>

						<?php do_action( 'mem_client_profile_form_bottom' ); ?>

					</form>

					<?php do_action( 'mem_after_client_profile_form' ); ?>

		</div><!--end #mem_guest_playlist_form_wrap-->
	</div><!-- end of #mem_guest_playlist_wrap -->
<?php endif; ?>
