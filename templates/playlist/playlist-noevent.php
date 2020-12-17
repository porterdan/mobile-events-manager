<?php
/**
 * This template is used when the current logged in client accesses the playlist page but has no events.
 *
 * @version 1.1
 * @author Jack Mawhinney, Dan Porter
 * @since 1.3
 * @content_tag {client_*}
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/playlist/playlist-noevent.php
 * @package TMEM
 */

$notice = sprintf(
	wp_kses_post( "We haven't been able to locate your %1\$s. Please return to our <a href='%2\$s'>%3\$s</a> page to see a list of your %4\$s.", 'mobile-events-manager' ),
	tmem_get_label_singular( true ),
	'{application_home}',
	'{application_name}',
	tmem_get_label_plural( true )
);
?>
<div id="client-playlist-no-events">
	<p><?php echo wp_kses_post( $notice ); ?></p>
</div>
