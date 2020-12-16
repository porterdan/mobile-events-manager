<?php
/**
 * Admin Actions
 *
 * @package MEM
 * @subpackage Admin/Actions
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes all MEM actions sent via POST and GET by looking for the 'mem-action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function mem_process_actions() {
	if ( isset( $_POST['mem-action'] )) {

		if ( isset( $_FILES ) ) {
			$_POST['FILES'] = $_FILES;
		}

		do_action( 'mem_' . sanitize_key( $_POST['mem-action'] ), $_POST );

	}

	if ( isset( $_GET['mem-action'] ) ) {

		if ( isset( $_FILES ) ) {
			$_POST['FILES'] = $_FILES;
		}

		do_action( 'mem_' . sanitize_key( $_GET['mem-action'] ), $_GET );

	}

}
add_action( 'admin_init', 'mem_process_actions' );

/**
 * Admin action field.
 *
 * Prints the output for a hidden form field which is required for admin post forms.
 *
 * @since 1.3
 * @param  str  $action The action identifier.
 * @param bool $echo True echo's the input field, false to return as a string.
 * @return str $input Hidden form field string
 */
function mem_admin_action_field( $action, $echo = true ) {
	$name = apply_filters( 'mem_action_field_name', 'mem-action' );

	$input = '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $action . '" />';

	if ( ! empty( $echo ) ) {
		echo esc_html_e( apply_filters( 'mem_action_field', $input, $action ) );
	} else {
		return apply_filters( 'mem_action_field', $input, $action );
	}

} // mem_admin_action_field
