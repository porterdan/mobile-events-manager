<?php
/**
 * Upgrade Screen
 *
 * @package TMEM
 * @subpackage Admin/Upgrades
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 *
 * Taken from Easy Digital Downloads.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Upgrades Screen
 *
 * @since 1.4
 * @return void
 */
function tmem_upgrades_screen() {
	$action = isset( $_GET['tmem-upgrade'] ) ? sanitize_text_field( wp_unslash( $_GET['tmem-upgrade'] ) ) : '';
	$step   = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	$custom = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 0;
	$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 100;
	$steps  = round( ( $total / $number ), 0 );

	$doing_upgrade_args = array(
		'page'         => 'tmem-upgrades',
		'tmem-upgrade' => $action,
		'step'         => $step,
		'total'        => $total,
		'custom'       => $custom,
		'steps'        => $steps,
	);
	update_option( 'tmem_doing_upgrade', $doing_upgrade_args );
	if ( $step > $steps ) {
		// Prevent a weird case where the estimate was off. Usually only a couple.
		$steps = $step;
	}
	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'TMEM Event Management - Upgrading, Please wait...', 'mobile-events-manager' ); ?></h2>

		<?php if ( ! empty( $action ) ) : ?>

			<div id="tmem-upgrade-status">
				<p><?php esc_html_e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'mobile-events-manager' ); ?></p>

				<?php if ( ! empty( $total ) ) : ?>
					<p><strong>
						<?php printf( esc_html__( 'Step %1$d of approximately %2$d running', 'mobile-events-manager' ), $step, $steps ); ?>
					</strong><img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/loading.gif'; ?>" id="tmem-upgrade-loader"/></p>
				<?php endif; ?>
			</div>
			<script type="text/javascript">
				setTimeout(function() { document.location.href = "index.php?tmem-action=<?php echo $action; ?>&step=<?php echo $step; ?>&total=<?php echo $total; ?>&custom=<?php echo $custom; ?>"; }, 250);
			</script>

		<?php else : ?>

			<div id="tmem-upgrade-status">
				<p>
					<?php esc_html_e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'mobile-events-manager' ); ?>
					<img src="<?php echo TMEM_PLUGIN_URL . '/assets/images/loading.gif'; ?>" id="tmem-upgrade-loader"/>
				</p>
			</div>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					// Trigger upgrades on page load
					var data = { action: 'tmem_trigger_upgrades' };
					jQuery.post( ajaxurl, data, function (response) {
						if( response == 'complete' ) {
							jQuery('#tmem-upgrade-loader').hide();
							document.location.href = 'index.php?page=tmem-about'; // Redirect to the welcome page
						}
					});
				});
			</script>

		<?php endif; ?>

	</div>
	<?php
} // tmem_upgrades_screen
