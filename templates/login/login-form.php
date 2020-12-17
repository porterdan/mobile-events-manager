<?php
/**
 * This template is used to generate the page for the shortcode [tmem-login].
 *
 * @version 1.0
 * @author Mike Howard
 * @since 1.3
 * @content_tag
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/login/login-form.php
 * @package TMEM
 */

global $tmem_login_redirect; ?>

<?php if ( ! is_user_logged_in() ) : ?>

	<?php do_action( 'tmem_print_notices' ); ?>

	<?php do_action( 'tmem_before_login_form' ); ?>

	<!-- TMEM login form content starts -->

	<form id="tmem-login-form" name="tmem-login-form" class="tmem_form" action="" method="post">
		<fieldset>
			<legend><?php printf( __( 'Login to %s', 'mobile-events-manager' ), '{company_name}' ); ?></legend>
			<?php do_action( 'tmem_login_form_top' ); ?>
			<p class="tmem-login-username">
				<label for="tmem-login-username"><?php esc_html_e( 'Email address:', 'mobile-events-manager' ); ?></label>
				<input type="text" name="tmem_user_login" id="tmem-login-username" class="tmem-input" value="" size="20" required />
			</p>

			<p class="tmem-login-password">
				<label for="tmem-login-password"><?php esc_html_e( 'Password:', 'mobile-events-manager' ); ?></label>
				<input type="password" name="tmem_user_pass" id="tmem-login-password" class="tmem-input" value="" size="20" required />
			</p>

			<?php do_action( 'tmem_login_form_middle' ); ?>

			<p class="tmem-login-submit">
				<input type="hidden" name="tmem_redirect" value="<?php echo esc_url( $tmem_login_redirect ); ?>"/>
				<input type="hidden" name="tmem_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tmem-login-nonce' ) ); ?>"/>
				<input type="hidden" name="tmem_action" value="user_login"/>
				<input id="tmem_login_submit" type="submit" class="tmem_submit" value="<?php printf( __( 'Login to %s', 'mobile-events-manager' ), '{application_name}' ); ?>" />
			</p>

			<p class="tmem-lost-password">
				<a href="<?php echo wp_lostpassword_url(); ?>" title="<?php echo esc_html_e( 'Lost Password', 'mobile-events-manager' ); ?>">
					<?php echo esc_html_e( 'Lost Password?', 'mobile-events-manager' ); ?>
				</a>
			</p>

			<?php do_action( 'tmem_login_form_bottom' ); ?>
		</fieldset>
	</form>

	<?php do_action( 'tmem_after_login_form' ); ?>

<?php else : ?>

	<?php echo esc_html_e( 'You are already logged in', 'mobile-events-manager' ); ?>

<?php endif; ?>
