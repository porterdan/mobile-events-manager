<?php
/**
 * This template is used when the current logged in client accesses the playlist page but has no events.
 *
 * @version 1.0
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag {client_*}
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/mem-templates/quote/quote-noevent.php
 * @package is the package
 */

?>
<div id="client-quote-no-events">
	<p>
	<?php
	printf(
		wp_kses_post( __( "We haven't been able to locate your %1\$s. Please return to our <a href='%2\$s'>%3\$s</a> page to see a list of your %4\$s.", 'mobile-events-manager' ) ),
		mem_get_label_singular(),
		'{application_home}',
		'{application_name}',
		mem_get_label_plural()
	);
	?>
		</p>    
</div>
