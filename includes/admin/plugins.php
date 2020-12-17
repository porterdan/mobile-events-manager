<?php

/**
 * Plugin functions.
 *
 * @package TMEM
 * @subpackage Admin/Plugins
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

/**
 * Customise the TMEM plugin action links on the plugins page.
 *
 * @since 1.1.3
 * @param arr $links Pre-filtered links.
 * @return arr $links Post-filtered links.
 */
function tmem_plugin_action_links( $links ) {

	$tmem_plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=tmem-settings' ) . '">' . __( 'Settings', 'mobile-events-manager' ) . '</a>',
	);

	return array_merge( $links, $tmem_plugin_links );

} // tmem_action_links
add_filter( 'plugin_action_links_' . TMEM_PLUGIN_BASENAME, 'tmem_plugin_action_links' );

/**
 * Add custom links to the TMEM plugin row on the plugins page.
 *
 * @since 1.1.3
 * @param arr $links Pre-filtered links.
 * @param arr $file Current plugin file being displayed.
 */
function tmem_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, 'mobile-events-manager.php' ) === false ) {
		return $links;
	}

	$tmem_links[] = '<a href="https://www.mobileeventsmanager.co.uk/support/" target="_blank">' . __( 'Support Docs', 'mobile-events-manager' ) . '</a>';
	$tmem_links[] = '<a href="https://www.mobileeventsmanager.co.uk/donate/" target="_blank">' . __( 'Donate', 'mobile-events-manager' ) . '</a>';
	$tmem_links[] = '<a href="http://https://www.mobileeventsmanager.co.uk/add-ons/" target="_blank">' . __( 'Extensions', 'mobile-events-manager' ) . '</a>';

	return array_merge( $links, $tmem_links );

}
add_filter( 'plugin_row_meta', 'tmem_plugin_row_meta', 10, 2 );
