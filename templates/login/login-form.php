<?php
/**
 * This template is used to generate the page for the shortcode [mem-login].
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/login/login-form.php
 * @package MEM
 */

global $mem_login_redirect; ?>

<?php if ( ! is_user_logged_in() ) : ?>

	<?php do_action( 'mem_print_notices' ); ?>

	<?php do_action( 'mem_before_login_form' ); ?>

	<!-- MEM login form content starts -->

	<form id="mem-login-form" name="mem-login-form" class="mem_form" action="" method="post">
		<fieldset>
			<legend><?php printf( __( 'Login to %s', 'mobile-events-manager' ), '{company_name}' ); ?></legend>
			<?php do_action( 'mem_login_form_top' ); ?>
			<p class="mem-login-username">
				<label for="mem-login-username"><?php _e( 'Email address:', 'mobile-events-manager' ); ?></label>
				<input type="text" name="mem_user_login" id="mem-login-username" class="mem-input" value="" size="20" required />
			</p>

			<p class="mem-login-password">
				<label for="mem-login-password"><?php _e( 'Password:', 'mobile-events-manager' ); ?></label>
				<input type="password" name="mem_user_pass" id="mem-login-password" class="mem-input" value="" size="20" required />
			</p>

			<?php do_action( 'mem_login_form_middle' ); ?>

			<p class="mem-login-submit">
				<input type="hidden" name="mem_redirect" value="<?php echo $mem_login_redirect; ?>"/>
				<input type="hidden" name="mem_login_nonce" value="<?php echo wp_create_nonce( 'mem-login-nonce' ) ); ?>"/>
				<input type="hidden" name="mem_action" value="user_login"/>
				<input id="mem_login_submit" type="submit" class="mem_submit" value="<?php printf( __( 'Login to %s', 'mobile-events-manager' ), '{application_name}' ); ?>" />
			</p>

			<p class="mem-lost-password">
				<a href="<?php echo wp_lostpassword_url(); ?>" title="<?php echo esc_html_e( 'Lost Password', 'mobile-events-manager' ); ?>">
					<?php echo esc_html_e( 'Lost Password?', 'mobile-events-manager' ); ?>
				</a>
			</p>

			<?php do_action( 'mem_login_form_bottom' ); ?>
		</fieldset>
	</form>

	<?php do_action( 'mem_after_login_form' ); ?>

<?php else : ?>

	<?php echo esc_html_e( 'You are already logged in', 'mobile-events-manager' ); ?>

<?php endif; ?>
