<?php
/**
 * Contains all functions to address compatibility issues with other plugins and/or themes.
 *
 * @package MEM
 * @subpackage Functions
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CloudFlare uses Rocket Loader which has a tendancy to disrupt some JavaScript.
 *
 * Adding the [data-cfasync="false"] attribute to MEM scripts instructs CloudFlare to ignore.
 *
 * @since 1.4.3
 * @param str $tag The <script> tag for the enqueued script.
 * @param str $handle The script's registered handle.
 */
function mem_cloudflare_rocketscript_ignore( $tag, $handle ) {
	if ( false === strpos( $handle, 'mem-' ) ) {
		return $tag;
	}

	$tag = str_replace( ' src', ' data-cfasync="false" src', $tag );

	return $tag;

} // mem_cloudflare_rocketscript_ignore
add_filter( 'script_loader_tag', 'mem_cloudflare_rocketscript_ignore', 10, 2 );
