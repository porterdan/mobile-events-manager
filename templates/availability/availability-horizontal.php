<?php
/**
 * This template is used to display the availability form with shortcode [tmem-availability display="hortizontal"].
 *
 * @version 1.0
 * @author Mike Howard
 * @since 1.3
 * @content_tag No {client_*}
 * @content_tag No {event_*}
 * @content_tags {label}, {label_class}, {field}, {field_class}, {submit_text}, {submit_class}, {please_wait_text}, {please_wait_class}
 * @shortcodes Not Supported
 *
 * Do not change any form field ID's.
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/availabilty/availabilty-horizontal.php
 * @package is the package type
 */

?>

<?php do_action( 'tmem_print_notices' ); ?>
<div id="tmem-availability-result"></div>
<div id="tmem-availability-checker">
	<p><label for="{field}" class="{label_class}">{label}</label> 
	<input type="text" name="{field}" id="{field}" class="{field_class}" size="20" placeholder="<?php echo esc_html( tmem_format_datepicker_date() ); ?>" />
	<input type="submit" name="tmem-submit-availability" id="tmem-submit-availability" class="{submit_class}" value="{submit_text}" /></p>
	<span id="pleasewait" class="{please_wait_class}">{please_wait_text} <img src="<?php echo esc_html( TMEM_PLUGIN_URL ); ?>/assets/images/loading.gif" alt="{please_wait_text}" /></span>
</div>
