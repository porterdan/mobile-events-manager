<?php

/**
 * Contains all role functions.
 *
 * @package TMEM
 * @subpackage Roles
 * @since 1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return all registered TMEM roles.
 *
 * @since 1.3
 * @global $wp_roles
 * @param str|arr $which_roles Which roles to retrieve
 * @return arr $tmem_roles Array of TMEM registered roles
 */
function tmem_get_roles( $which_roles = array() ) {
	global $wp_roles;

	// Retrieve all roles within this WP instance
	$roles      = $wp_roles->get_names();
	$tmem_roles = array();

	if ( ! empty( $which_roles ) && ! is_array( $which_roles ) ) {
		$which_roles = array( $which_roles );
	}

	// Loop through the $raw_roles and filter for tmem specific roles
	foreach ( $roles as $role_id => $role_name ) {

		if ( ! empty( $which_roles ) && ! in_array( $role_id, $which_roles ) ) {
			continue;
		}

		if ( 'dj' === $role_id || false !== strpos( $role_id, 'tmem-' ) ) {
			$tmem_roles[ $role_id ] = $role_name;
		}
	}

	// Filter the roles
	return apply_filters( 'tmem_user_roles', $tmem_roles );
} // tmem_get_roles
