<?php

/**
 * Plugin functions.
 *
 * @package MEM
 * @subpackage Admin/Plugins
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

/**
 * Customise the MEM plugin action links on the plugins page.
 *
 * @since 1.1.3
 * @param arr $links Pre-filtered links.
 * @return arr $links Post-filtered links.
 */
function mem_plugin_action_links( $links ) {

	$mem_plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=mem-settings' ) . '">' . __( 'Settings', 'mobile-events-manager' ) . '</a>',
	);

	return array_merge( $links, $mem_plugin_links );

} // mem_action_links
add_filter( 'plugin_action_links_' . MEM_PLUGIN_BASENAME, 'mem_plugin_action_links' );

/**
 * Add custom links to the MEM plugin row on the plugins page.
 *
 * @since 1.1.3
 * @param arr $links Pre-filtered links.
 * @param arr $file Current plugin file being displayed.
 */
function mem_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, 'mobile-events-manager.php' ) === false ) {
		return $links;
	}

	$mem_links[] = '<a href="http://mem.co.uk/support/" target="_blank">' . __( 'Support Docs', 'mobile-events-manager' ) . '</a>';
	$mem_links[] = '<a href="http://mem.co.uk/donate/" target="_blank">' . __( 'Donate', 'mobile-events-manager' ) . '</a>';
	$mem_links[] = '<a href="http://http://mem.co.uk/add-ons/" target="_blank">' . __( 'Extensions', 'mobile-events-manager' ) . '</a>';

	return array_merge( $links, $mem_links );

}
add_filter( 'plugin_row_meta', 'mem_plugin_row_meta', 10, 2 );
