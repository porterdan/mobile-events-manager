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

		<p class="head-nav"><a href="{event_url}"><?php printf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ); ?></a></p>

		<?php do_action( 'tmem_pre_contract_header', $tmem_event->ID ); ?>

		<p>
		<?php
		printf(
			esc_html( __( 'The contract for your %1$s taking place on %2$s is displayed below.', 'mobile-events-manager' ) ),
			esc_html( tmem_get_label_singular( true ) ),
			'{event_date}'
		);
		?>
				</p>

		<p class="tmem-alert tmem-alert-success"><span style="font-weight: bold;"><?php esc_html_e( 'Your contract is signed', 'mobile-events-manager' ); ?></span><br />
			<?php
			printf(
				esc_html( __( 'Signed on %1$s by %2$s with password verification', 'mobile-events-manager' ) ),
				'{contract_date}',
				'{contract_signatory}'
			);
			?>
				<br />
			<?php printf( esc_html__( 'IP address recorded as: %s', 'mobile-events-manager' ), '{contract_signatory_ip}' ); ?></p>

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

		<p class="tmem-alert tmem-alert-success"><span style="font-weight: bold;"><?php esc_html_e( 'Your contract is signed', 'mobile-events-manager' ); ?></span><br />
			<?php
			printf(
				esc_html( __( 'Signed on %1$s by %2$s with password verification', 'mobile-events-manager' ) ),
				'{contract_date}',
				'{contract_signatory}'
			);
			?>
				<br />
			<?php printf( esc_html__( 'IP address recorded as: %s', 'mobile-events-manager' ), '{contract_signatory_ip}' ); ?></p>

		<?php do_action( 'tmem_post_contract_footer', $tmem_event->ID ); ?>
	</div><!-- end tmem-contract-footer -->

</div><!-- end tmem-contract-wrapper -->
