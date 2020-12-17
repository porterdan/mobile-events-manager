<?php
/**
 * Admin Actions
 *
 * @package TMEM
 * @subpackage Admin/Actions
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes all TMEM actions sent via POST and GET by looking for the 'tmem-action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function tmem_process_actions() {
	if ( isset( $_POST['tmem-action'] ) ) {

		if ( isset( $_FILES ) ) {
			$_POST['FILES'] = $_FILES;
		}

		do_action( 'tmem_' . sanitize_key( $_POST['tmem-action'] ), $_POST );

	}

	if ( isset( $_GET['tmem-action'] ) ) {

		if ( isset( $_FILES ) ) {
			$_POST['FILES'] = $_FILES;
		}

		do_action( 'tmem_' . sanitize_key( $_GET['tmem-action'] ), $_GET );

	}

}
add_action( 'admin_init', 'tmem_process_actions' );

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
function tmem_admin_action_field( $action, $echo = true ) {
	$name = apply_filters( 'tmem_action_field_name', 'tmem-action' );

	$input = '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $action . '" />';

	if ( ! empty( $echo ) ) {
		echo esc_html_e( apply_filters( 'tmem_action_field', $input, $action ) );
	} else {
		return apply_filters( 'tmem_action_field', $input, $action );
	}

} // tmem_admin_action_field
