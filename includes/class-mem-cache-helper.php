<?php
/**
 * MEM_Cache_Helper class
 *
 * @package MEM
 * @subpackage Classes/Caching
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MEM_Cache_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ), 0 );
		add_action( 'update_option_mem_settings', array( __CLASS__, 'delete_page_cache' ), 999, 2 );
	} // init

	/**
	 * Get the page name/id for an MEM page.
	 *
	 * @since 1.4.8
	 * @param str $mem_page The page to retrieve the name/id for.
	 * @return arr Array of page id/name
	 */
	private static function get_page_uris( $mem_page ) {
		$mem_page_uris = array();

		if ( ( $page_id = mem_get_page_id( $mem_page ) ) && $page_id > 0 && ( $page = get_post( $page_id ) ) ) {
			$mem_page_uris[] = 'p=' . $page_id;
			$mem_page_uris[] = '/' . $page->post_name . '/';
		}

		return $mem_page_uris;
	} // get_page_uris

	/**
	 * Prevent caching on dynamic pages.
	 */
	public static function prevent_caching() {

		if ( ! is_blog_installed() ) {
			return;
		}

		if ( false === ( $mem_page_uris = get_transient( 'mem_cache_excluded_uris' ) ) ) {
			$mem_page_uris = array_filter(
				array_merge(
					self::get_page_uris( 'app_home' ),
					self::get_page_uris( 'contact' ),
					self::get_page_uris( 'contracts' ),
					self::get_page_uris( 'payment' ),
					self::get_page_uris( 'playlist' ),
					self::get_page_uris( 'profile' ),
					self::get_page_uris( 'quotes' )
				)
			);

				set_transient( 'mem_cache_excluded_uris', $mem_page_uris, DAY_IN_SECONDS );
		}

		if ( is_array( $mem_page_uris ) ) {
			foreach ( $mem_page_uris as $uri ) {
				if ( stristr( trailingslashit( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ), $uri ) ) {
					self::nocache();
					break;
				}
			}
		}
	} // prevent_caching

	/**
	 * Set nocache constants and headers.
	 *
	 * @since 1.4.8
	 * @access private
	 */
	private static function nocache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}
		nocache_headers();
	} // nocache

	/**
	 * Delete the page cache when settings are updated.
	 *
	 * @since 1.1
	 * @param mixed $old_value The pre-save value of the setting.
	 * @param mixed $value The updated value of the setting.
	 * @return void
	 */
	public static function delete_page_cache( $old_value, $value ) {
		if ( ! isset( $old_value['app_home_page'] ) ) {
			return;
		}

		$pages = array(
			'app_home',
			'contact',
			'contracts',
			'payments',
			'playlist',
			'profile',
			'quotes',
		);

		foreach ( $pages as $page ) {
			if ( $value[ $page . '_page' ] !== $old_value[ $page . '_page' ] ) {
				delete_transient( 'mem_cache_excluded_uris' );
				break;
			}
		}

	} // delete_page_cache

} // class MEM_Cache_Helper

MEM_Cache_Helper::init();
