<?php
/**
 * Exports Functions
 *
 * These are functions are used for exporting data from Mobile Events Manager (MEM).
 *
 * @package MEM
 * @subpackage Admin/Export
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/class-mem-export.php';
require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/export/export-actions.php';

/**
 * Exports earnings for a specified time period
 * MEM_Earnings_Export class.
 *
 * @since 1.4
 * @return void
 */
function mem_export_earnings() {
	require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/class-mem-export-earnings.php';

	$earnings_export = new MEM_Earnings_Export();

	$earnings_export->export();
} // mem_export_earnings
add_action( 'mem-earnings_export', 'mem_export_earnings' );

/**
 * Process batch exports via ajax
 *
 * @since 1.4
 * @return void
 */
function mem_do_ajax_export() {

	require_once MEM_PLUGIN_DIR . '/includes/admin/reporting/export/class-batch-export.php';

	parse_str( sanitize_key( wp_unslash( $_POST['form'], $form ) ) );

	$_REQUEST = $form = (array) $form;

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['mem_ajax_export'], 'mem_ajax_export' ) ) ) ) {
		die( '-2' );
	}

	do_action( 'mem_batch_export_class_include', $form['mem-export-class'] );

	$step   = absint( $_POST['step'] );
	$class  = sanitize_text_field( $form['mem-export-class'] );
	$export = new $class( $step );

	if ( ! $export->can_export() ) {
		die( '-1' );
	}

	if ( ! $export->is_writable ) {
		echo json_encode(
			array(
				'error'   => true,
				'message' => __(
					'Export location or file not writable',
					'mobile-events-manager'
				),
			)
		);
		exit;
	}

	$export->set_properties( $_REQUEST );

	// Allow a bulk processor to pre-fetch some data to speed up the remaining steps and cache data
	$export->pre_fetch();

	$ret = $export->process_step( $step );

	$percentage = $export->get_percentage_complete();

	if ( $ret ) {

		$step += 1;
		echo json_encode(
			array(
				'step'       => $step,
				'percentage' => $percentage,
			)
		);
		exit;

	} elseif ( true === $export->is_empty ) {

		echo json_encode(
			array(
				'error'   => true,
				'message' => __(
					'No data found for export parameters',
					'mobile-events-manager'
				),
			)
		);
		exit;

	} elseif ( true === $export->done && true === $export->is_void ) {

		$message = ! empty( $export->message ) ? $export->message : __( 'Batch Processing Complete', 'mobile-events-manager' );
		echo json_encode(
			array(
				'success' => true,
				'message' => $message,
			)
		);
		exit;

	} else {

		$args = array_merge(
			$_REQUEST,
			array(
				'step'        => $step,
				'class'       => $class,
				'nonce'       => wp_create_nonce( 'mem-batch-export' ),
				'mem_action' => 'download_batch_export',
			)
		);

		$event_url = add_query_arg( $args, admin_url() );

		echo json_encode(
			array(
				'step' => 'done',
				'url'  => $event_url,
			)
		);
		exit;

	}
} // mem_do_ajax_export
add_action( 'wp_ajax_mem_do_ajax_export', 'mem_do_ajax_export' );
