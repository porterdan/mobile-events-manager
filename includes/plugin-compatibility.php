<?php
/**
 * Contains all functions to address compatibility issues with other plugins and/or themes.
 *
 * @package TMEM
 * @subpackage Functions
 * @since 1.4.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CloudFlare uses Rocket Loader which has a tendancy to disrupt some JavaScript.
 *
 * Adding the [data-cfasync="false"] attribute to TMEM scripts instructs CloudFlare to ignore.
 *
 * @since 1.4.3
 * @param str $tag The <script> tag for the enqueued script.
 * @param str $handle The script's registered handle.
 */
function tmem_cloudflare_rocketscript_ignore( $tag, $handle ) {
	if ( false === strpos( $handle, 'tmem-' ) ) {
		return $tag;
	}

	$tag = str_replace( ' src', ' data-cfasync="false" src', $tag );

	return $tag;

} // tmem_cloudflare_rocketscript_ignore
add_filter( 'script_loader_tag', 'tmem_cloudflare_rocketscript_ignore', 10, 2 );
