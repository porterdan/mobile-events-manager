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

		<p class="head-nav"><a href="{event_url}"><?php printf( esc_html__( 'Back to %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></a></p>

		<?php do_action( 'mem_pre_contract_header', $mem_event->ID ); ?>

		<p>
		<?php
		printf(
			esc_html( __( 'The contract for your %1$s taking place on %2$s is displayed below.', 'mobile-events-manager' ) ),
			esc_html( mem_get_label_singular( true ) ),
			'{event_date}'
		);
		?>
				</p>

		<p class="mem-alert mem-alert-success"><span style="font-weight: bold;"><?php esc_html_e( 'Your contract is signed', 'mobile-events-manager' ); ?></span><br />
			<?php
			printf(
				esc_html( __( 'Signed on %1$s by %2$s with password verification', 'mobile-events-manager' ) ),
				'{contract_date}',
				'{contract_signatory}'
			);
			?>
				<br />
			<?php printf( esc_html__( 'IP address recorded as: %s', 'mobile-events-manager' ), '{contract_signatory_ip}' ); ?></p>

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

		<p class="mem-alert mem-alert-success"><span style="font-weight: bold;"><?php esc_html_e( 'Your contract is signed', 'mobile-events-manager' ); ?></span><br />
			<?php
			printf(
				esc_html( __( 'Signed on %1$s by %2$s with password verification', 'mobile-events-manager' ) ),
				'{contract_date}',
				'{contract_signatory}'
			);
			?>
				<br />
			<?php printf( esc_html__( 'IP address recorded as: %s', 'mobile-events-manager' ), '{contract_signatory_ip}' ); ?></p>

		<?php do_action( 'mem_post_contract_footer', $mem_event->ID ); ?>
	</div><!-- end mem-contract-footer -->

</div><!-- end mem-contract-wrapper -->
