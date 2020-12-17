<?php
/**
 * Front-end Actions
 *
 * @package TMEM
 * @subpackage Functions
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hooks TMEM actions, when present in the $_GET superglobal. Every tmem_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.3
 * @return void
 */
function tmem_get_actions() {
	if ( isset( $_GET['tmem_action'] ) ) {
		do_action( 'tmem_' . sanitize_key( $_GET['tmem_action'] ), $_GET );
	}
} // tmem_get_actions
add_action( 'init', 'tmem_get_actions' );

/**
 * Hooks TMEM actions, when present in the $_POST superglobal. Every tmem_action
 * present in $_POST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.3
 * @return void
 */
function tmem_post_actions() {
	if ( isset( $_POST['tmem_action'] ) ) {
		do_action( 'tmem_' . sanitize_key( $_POST['tmem_action'] ), $_POST );
	}
} // tmem_post_actions
add_action( 'init', 'tmem_post_actions' );

/**
 * Action field.
 *
 * Prints the output for a hidden form field which is required for post forms.
 *
 * @since 1.3
 * @param str  $action The action identifier.
 * @param bool $echo True echo's the input field, false to return as a string.
 * @return str $input Hidden form field string
 */
function tmem_action_field( $action, $echo = true ) {
	$name = apply_filters( 'tmem_action_field_name', 'tmem_action' );

	$input = '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $action . '" />';

	if ( ! empty( $echo ) ) {
		echo esc_html_e( apply_filters( 'tmem_action_field', $input, $action ) );
	} else {
		return apply_filters( 'tmem_action_field', $input, $action );
	}

} // tmem_action_field
