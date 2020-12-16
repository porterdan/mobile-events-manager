<?php

/**
 * Contains all role functions.
 *
 * @package MEM
 * @subpackage Roles
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return all registered MEM roles.
 *
 * @since 1.3
 * @global $wp_roles
 * @param str|arr $which_roles Which roles to retrieve
 * @return arr $mem_roles Array of MEM registered roles
 */
function mem_get_roles( $which_roles = array() ) {
	global $wp_roles;

	// Retrieve all roles within this WP instance
	$roles      = $wp_roles->get_names();
	$mem_roles = array();

	if ( ! empty( $which_roles ) && ! is_array( $which_roles ) ) {
		$which_roles = array( $which_roles );
	}

	// Loop through the $raw_roles and filter for mem specific roles
	foreach ( $roles as $role_id => $role_name ) {

		if ( ! empty( $which_roles ) && ! in_array( $role_id, $which_roles ) ) {
			continue;
		}

		if ( 'dj' === $role_id || false !== strpos( $role_id, 'mem-' ) ) {
			$mem_roles[ $role_id ] = $role_name;
		}
	}

	// Filter the roles
	return apply_filters( 'mem_user_roles', $mem_roles );
} // mem_get_roles
