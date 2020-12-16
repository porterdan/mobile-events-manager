<?php
/**
 * This template is used to display the online quote page content.
 *
 * @version         1.0
 * @author          Jack Mawhinney, Dan Porter
 * @since           1.3
 * @content_tag     client
 * @content_tag     event
 * @shortcodes      Supported
 * @global          $mem_event     MEM Event object
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/quote/quote.php
 */
global $mem_event;
?>

<div id="mem-quote-wrapper">
	<?php do_action( 'mem_pre_quote', $mem_event->ID ); ?>
	
	<div id="mem-quote-header">
		
		<?php do_action( 'mem_print_notices' ); ?>
		
		<p class="head-nav"><a href="{event_url}"><?php printf( __( 'Back to %s', 'mobile-events-manager' ), mem_get_label_singular() ); ?></a></p>
		
		<?php do_action( 'mem_pre_quote_header', $mem_event->ID ); ?>

	</div><!-- end mem-quote-header -->
	
	<div id="mem-quote-content">
	
		<?php do_action( 'mem_pre_quote_content', $mem_event->ID ); ?>
		
		<p class="head-nav"><?php echo mem_display_book_event_button( $mem_event->ID ); ?></p>
		
		<?php echo mem_display_quote( $mem_event->ID ); ?>
		
		<p class="head-nav"><?php echo mem_display_book_event_button( $mem_event->ID ); ?></p>
		
		<?php do_action( 'mem_post_quote_content', $mem_event->ID ); ?>
	
	</div><!-- end mem-quote-content -->
	<hr />
	
	<div id="mem-quote-footer">
		<?php do_action( 'mem_pre_quote_footer', $mem_event->ID ); ?>
						
		<?php do_action( 'mem_post_quote_footer', $mem_event->ID ); ?>
	</div><!-- end mem-quote-footer -->
	
</div><!-- end mem-quote-wrapper -->
