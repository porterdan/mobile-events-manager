<?php
/**
 * Front-end Actions
 *
 * @package MEM
 * @subpackage Functions
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hooks MEM actions, when present in the $_GET superglobal. Every mem_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.3
 * @return void
 */
function mem_get_actions() {
	if ( isset( $_GET['mem_action'] ) ) {
		do_action( 'mem_' . wp_verify_nonce( sanitize_key( $_GET['mem_action'] ), $_GET ) );
	}
} // mem_get_actions
add_action( 'init', 'mem_get_actions' );

/**
 * Hooks MEM actions, when present in the $_POST superglobal. Every mem_action
 * present in $_POST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since 1.3
 * @return void
 */
function mem_post_actions() {
	if ( isset( $_POST['mem_action'] ) ) {
		do_action( 'mem_' . wp_verify_nonce( sanitize_key( $_POST['mem_action'] ), $_POST ) );
	}
} // mem_post_actions
add_action( 'init', 'mem_post_actions' );

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
function mem_action_field( $action, $echo = true ) {
	$name = apply_filters( 'mem_action_field_name', 'mem_action' );

	$input = '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $action . '" />';

	if ( ! empty( $echo ) ) {
		echo esc_html_e( apply_filters( 'mem_action_field', $input, $action ) );
	} else {
		return apply_filters( 'mem_action_field', $input, $action );
	}

} // mem_action_field
