<?php
/**
 * Exports Functions
 *
 * These are functions are used for exporting data from TMEM Event Management.
 *
 * @package TMEM
 * @subpackage Admin/Export
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/class-tmem-export.php';
require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/export/export-actions.php';

/**
 * Exports earnings for a specified time period
 * TMEM_Earnings_Export class.
 *
 * @since 1.4
 * @return void
 */
function tmem_export_earnings() {
	require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/class-tmem-export-earnings.php';

	$earnings_export = new TMEM_Earnings_Export();

	$earnings_export->export();
} // tmem_export_earnings
add_action( 'tmem-earnings_export', 'tmem_export_earnings' );

/**
 * Process batch exports via ajax
 *
 * @since 1.4
 * @return void
 */
function tmem_do_ajax_export() {

	require_once TMEM_PLUGIN_DIR . '/includes/admin/reporting/export/class-batch-export.php';

	parse_str( sanitize_key( wp_unslash( $_POST['form'], $form ) ) );

	$_REQUEST = $form = (array) $form;

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['tmem_ajax_export'], 'tmem_ajax_export' ) ) ) ) {
		die( '-2' );
	}

	do_action( 'tmem_batch_export_class_include', $form['tmem-export-class'] );

	$step   = absint( $_POST['step'] );
	$class  = sanitize_text_field( $form['tmem-export-class'] );
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
				'nonce'       => wp_create_nonce( 'tmem-batch-export' ),
				'tmem_action' => 'download_batch_export',
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
} // tmem_do_ajax_export
add_action( 'wp_ajax_tmem_do_ajax_export', 'tmem_do_ajax_export' );
