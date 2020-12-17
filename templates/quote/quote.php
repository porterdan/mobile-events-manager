<?php
/**
 * This template is used to display the online quote page content.
 *
 * @version         1.0
 * @author          Mike Howard
 * @since           1.3
 * @content_tag     client
 * @content_tag     event
 * @shortcodes      Supported
 * @global          $tmem_event     TMEM Event object
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/quote/quote.php
 */
global $tmem_event;
?>

<div id="tmem-quote-wrapper">
	<?php do_action( 'tmem_pre_quote', $tmem_event->ID ); ?>
	
	<div id="tmem-quote-header">
		
		<?php do_action( 'tmem_print_notices' ); ?>
		
		<p class="head-nav"><a href="{event_url}"><?php printf( __( 'Back to %s', 'mobile-events-manager' ), tmem_get_label_singular() ); ?></a></p>
		
		<?php do_action( 'tmem_pre_quote_header', $tmem_event->ID ); ?>

	</div><!-- end tmem-quote-header -->
	
	<div id="tmem-quote-content">
	
		<?php do_action( 'tmem_pre_quote_content', $tmem_event->ID ); ?>
		
		<p class="head-nav"><?php echo tmem_display_book_event_button( $tmem_event->ID ); ?></p>
		
		<?php echo tmem_display_quote( $tmem_event->ID ); ?>
		
		<p class="head-nav"><?php echo tmem_display_book_event_button( $tmem_event->ID ); ?></p>
		
		<?php do_action( 'tmem_post_quote_content', $tmem_event->ID ); ?>
	
	</div><!-- end tmem-quote-content -->
	<hr />
	
	<div id="tmem-quote-footer">
		<?php do_action( 'tmem_pre_quote_footer', $tmem_event->ID ); ?>
						
		<?php do_action( 'tmem_post_quote_footer', $tmem_event->ID ); ?>
	</div><!-- end tmem-quote-footer -->
	
</div><!-- end tmem-quote-wrapper -->
