<?php
/**
 * Scripts
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
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 1.3
 * @global $post
 * @return void
 */
function mem_load_scripts() {

	$js_dir        = MEM_PLUGIN_URL . '/assets/js/';
	$suffix        = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$is_payment    = mem_is_payment() ? '1' : '0';
	$agree_privacy = mem_get_option( 'show_agree_to_privacy_policy', false );
	$privacy_page  = mem_get_privacy_page();
	$privacy       = false;
	$agree_terms   = mem_get_option( 'show_agree_to_terms', false );
	$terms_text    = mem_get_option( 'agree_terms_text', false );
	$terms_label   = mem_get_option( 'agree_terms_label', false );
	$terms         = false;
	$thickbox      = false;
	$privacy_error = mem_messages( 'agree_to_policy' );
	$privacy_error = $privacy_error['message'];
	$terms_error   = mem_messages( 'agree_to_terms' );
	$terms_error   = $terms_error['message'];

	if ( ! empty( $agree_privacy ) && ! empty( $privacy_page ) ) {
		$privacy = true;

		if ( 'thickbox' === mem_get_option( 'show_agree_policy_type' ) ) {
			$thickbox = true;
		}
	}

	if ( ! empty( $agree_terms ) && ! empty( $terms_text ) && ! empty( $terms_label ) ) {
		$terms    = true;
		$thickbox = true;
	}

	if ( $is_payment && $thickbox && ( $privacy || $terms ) ) {
		add_thickbox();
	}

	wp_register_script( 'mem-ajax', $js_dir . 'mem-ajax.min.js', array( 'jquery' ), MEM_VERSION_NUM );
	wp_enqueue_script( 'mem-ajax' );

	wp_localize_script(
		'mem-ajax',
		'mem_vars',
		apply_filters(
			'mem_script_vars',
			array(
				'ajaxurl'                   => mem_get_ajax_url(),
				'ajax_loader'               => MEM_PLUGIN_URL . '/assets/images/loading.gif',
				'availability_ajax'         => mem_get_option( 'avail_ajax', false ),
				'available_redirect'        => mem_get_option( 'availability_check_pass_page', 'text' ) !== 'text' ? mem_get_formatted_url( mem_get_option( 'availability_check_pass_page' ) ) : 'text',
				'available_text'            => mem_get_option( 'availability_check_pass_text', false ),
				'complete_payment'          => mem_get_payment_button_text(),
				'date_format'               => mem_format_datepicker_date(),
				'default_gateway'           => mem_get_default_gateway(),
				'default_playlist_category' => mem_get_option( 'playlist_default_cat' ),
				'first_day'                 => get_option( 'start_of_week' ),
				'guest_playlist_closed'     => sprintf( esc_html__( 'The playlist for this %s is now closed and not accepting suggestions', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
				'is_payment'                => $is_payment,
				'no_card_name'              => __( 'Enter the name printed on your card', 'mobile-events-manager' ),
				'no_payment_amount'         => __( 'Select Payment Amount', 'mobile-events-manager' ),
				'payment_loading'           => __( 'Please Wait...', 'mobile-events-manager' ),
				'playlist_page'             => mem_get_formatted_url( mem_get_option( 'playlist_page' ) ),
				'playlist_updated'          => __( 'Your entry was added successfully', 'mobile-events-manager' ),
				'privacy_error'             => esc_html( $privacy_error ),
				'profile_page'              => mem_get_formatted_url( mem_get_option( 'profile_page' ) ),
				'profile_updated'           => __( 'Your details have been updated', 'mobile-events-manager' ),
				'required_date_message'     => __( 'Please select a date', 'mobile-events-manager' ),
				'require_privacy'           => $privacy,
				'require_terms'             => $terms,
				'rest_url'                  => esc_url_raw( rest_url( 'mem/v1/' ) ),
				'submit_client_profile'     => __( 'Update Details', 'mobile-events-manager' ),
				'submit_guest_playlist'     => __( 'Suggest Song', 'mobile-events-manager' ),
				'submit_playlist'           => __( 'Add to Playlist', 'mobile-events-manager' ),
				'submit_playlist_loading'   => __( 'Please Wait...', 'mobile-events-manager' ),
				'submit_profile_loading'    => __( 'Please Wait...', 'mobile-events-manager' ),
				'unavailable_redirect'      => mem_get_option( 'availability_check_fail_page', 'text' ) !== 'text' ? mem_get_formatted_url( mem_get_option( 'availability_check_fail_page' ) ) : 'text',
				'terms_error'               => esc_html( $terms_error ),
				'unavailable_text'          => mem_get_option( 'availability_check_fail_text', false ),
			)
		)
	);

	wp_register_script( 'jquery-validation-plugin', MEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );

	wp_enqueue_script( 'jquery-validation-plugin' );

	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

} // mem_load_scripts
add_action( 'wp_enqueue_scripts', 'mem_load_scripts' );

/**
 * Load Frontend Styles
 *
 * Enqueues the required styles for the frontend.
 *
 * @since 1.3
 * @return void
 */
function mem_register_styles() {
	global $post;

	$templates_dir = mem_get_theme_template_dir_name();
	$css_dir       = MEM_PLUGIN_URL . '/assets/css/';
	$suffix        = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file          = 'mem.css';

	$child_theme_style_sheet  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$parent_theme_style_sheet = trailingslashit( get_template_directory() ) . $templates_dir . $file;
	$mem_plugin_style_sheet  = trailingslashit( mem_get_templates_dir() ) . $file;

	// Look in the child theme, followed by the parent theme, and finally the MEM template DIR.
	// Allows users to copy the MEM stylesheet to their theme DIR and customise.
	if ( file_exists( $child_theme_style_sheet ) ) {
		$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
	} elseif ( file_exists( $parent_theme_style_sheet ) ) {
		$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
	} elseif ( file_exists( $mem_plugin_style_sheet ) || file_exists( $mem_plugin_style_sheet ) ) {
		$url = trailingslashit( mem_get_templates_url() ) . $file;
	}

	wp_register_style( 'mem-styles', $url, array(), MEM_VERSION_NUM );
	wp_enqueue_style( 'mem-styles' );

	wp_register_style( 'font-awesome', MEM_PLUGIN_URL . '/assets/libs/font-awesome/font-awesome.min.css' );
	wp_enqueue_style( 'font-awesome' );

	if ( ! empty( $post ) ) {
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'mem-availability' ) ) {
			wp_register_style( 'jquery-ui-css', $css_dir . 'jquery-ui.min.css', array(), MEM_VERSION_NUM );
		}
	}

	if ( mem_is_payment( true ) ) {
		wp_enqueue_style( 'dashicons' );
	}

} // mem_register_styles
add_action( 'wp_enqueue_scripts', 'mem_register_styles' );

/**
 * Load Admin Styles
 *
 * Enqueues the required styles for admin.
 *
 * @since 1.3
 * @return void
 */
function mem_register_admin_styles( $hook ) {

	$ui_style = ( 'classic' === get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';
	$css_dir  = MEM_PLUGIN_URL . '/assets/css/';
	$libs_dir = MEM_PLUGIN_URL . '/assets/libs/';
	$suffix   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file     = 'mem-admin.min.css';

	wp_register_style( 'jquery-chosen', $css_dir . 'chosen.min.css', array(), MEM_PLUGIN_URL );
	wp_register_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . '.min.css' );
	wp_register_style( 'font-awesome', MEM_PLUGIN_URL . '/assets/libs/font-awesome/font-awesome.min.css' );

	wp_enqueue_style( 'jquery-chosen' );
	wp_enqueue_style( 'jquery-ui-css' );
	wp_enqueue_style( 'font-awesome' );

	// Settings page color picker.
	if ( 'mem-event_page_mem-settings' === $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
	}

	// Availability calendar
	if ( 'mem-event_page_mem-availability' === $hook ) {

		wp_register_style( MEM_PLUGIN_URL . '/assets/libs/bootstrap/bootstrap.min.css', array(), MEM_VERSION_NUM );
		wp_register_style(
			'mem-fullcalendar-css',
			$libs_dir . 'fullcalendar/fullcalendar.min.css',
			array(),
			MEM_VERSION_NUM
		);

		wp_enqueue_style( 'mem-fullcalendar-css' );
		wp_enqueue_style( 'mem-bootstrap-css' );
	}

	wp_register_style( 'mem-admin', $css_dir . $file, '', MEM_VERSION_NUM );
	wp_enqueue_style( 'mem-admin' );

} // mem_register_styles
add_action( 'admin_enqueue_scripts', 'mem_register_admin_styles' );

/**
 * Load Admin Scripts
 *
 * Enqueues the required scripts for admin.
 *
 * @since 1.3
 * @return void
 */
function mem_register_admin_scripts( $hook ) {

	$js_dir             = MEM_PLUGIN_URL . '/assets/js/';
	$libs_dir           = MEM_PLUGIN_URL . '/assets/libs/';
	$suffix             = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file               = 'admin-scripts.min.js';
	$dashboard          = 'index.php' === $hook ? true : false;
	$editing_event      = false;
	$require_validation = array( 'mem-event_page_mem-comms' );
	$sortable           = array(
		'admin_page_mem-custom-event-fields',
		'admin_page_mem-custom-client-fields',
	);

	wp_register_script( 'jquery-chosen', $js_dir . 'chosen.jquery.min.js', array( 'jquery' ), MEM_VERSION_NUM );
	wp_enqueue_script( 'jquery-chosen' );

	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

	if ( strpos( $hook, 'mem' ) ) {
		wp_enqueue_script( 'jquery' );
	}

	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		if ( isset( $_GET['post'] ) && 'mem-addon' !== get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			$sortable[] = 'post.php';
			$sortable[] = 'post-new.php';
		}

		if ( isset( $_GET['post'] ) && 'mem-event' === get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			$editing_event = true;
		}

		if ( isset( $_GET['post_type'] ) && 'mem-event' === $_GET['post_type'] ) {
			$editing_event = true;
		}

		if ( $editing_event ) {
			$require_validation[] = 'post.php';
			$require_validation[] = 'post-new.php';
		}

		if ( isset( $_GET['post'] ) && 'mem-transaction' !== get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			wp_register_script( 'mem-trans-js', MEM_PLUGIN_URL . '/assets/js/mem-trans-post-val.js', array( 'jquery' ), MEM_VERSION_NUM );
			wp_enqueue_script( 'mem-trans-js' );
			wp_localize_script( 'mem-trans-js', 'transaction_type', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	if ( in_array( $hook, $require_validation ) ) {
		wp_register_script( 'jquery-validation-plugin', MEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );
		wp_enqueue_script( 'jquery-validation-plugin' );
	}

	if ( in_array( $hook, $sortable ) ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	// Settings page color picker.
	if ( 'mem-event_page_mem-settings' === $hook ) {
		wp_enqueue_script( 'wp-color-picker' );
	}

	// Availability calendar.
	if ( 'mem-event_page_mem-availability' === $hook ) {
		wp_register_script(
			'mem-moment-js',
			$libs_dir . 'moment/moment-with-locales.min.js',
			array( 'jquery' ),
			MEM_VERSION_NUM
		);
		wp_register_script(
			'mem-fullcalendar-js',
			$libs_dir . 'fullcalendar/fullcalendar.min.js',
			array( 'jquery', 'mem-moment-js' ),
			MEM_VERSION_NUM
		);

				wp_register_script( 'jquery-validation-plugin', MEM_PLUGIN_URL . '/assets/libs/popper/popper.min.js', false );

		wp_register_script(
			'mem-popper-js',
			MEM_PLUGIN_URL . '/assets/libs/popper/popper.min.js',
			array( 'jquery' ),
			MEM_VERSION_NUM
		);
		wp_register_script(
			'mem-bootstrap-js',
			MEM_PLUGIN_URL . '/assets/libs/bootstrap/bootstrap.min.js',
			array( 'jquery' ),
			MEM_VERSION_NUM
		);
		wp_register_script(
			'mem-availability-scripts-js',
			$js_dir . 'availability-scripts.min.js',
			array( 'jquery', 'mem-moment-js', 'mem-fullcalendar-js' ),
			MEM_VERSION_NUM
		);

		wp_enqueue_script( 'mem-moment-js' );
		wp_enqueue_script( 'mem-fullcalendar-js' );
		wp_enqueue_script( 'mem-popper-js' );
		wp_enqueue_script( 'mem-bootstrap-js' );
		wp_enqueue_script( 'mem-availability-scripts-js' );

		wp_localize_script(
			'mem-availability-scripts-js',
			'mem_calendar_vars',
			apply_filters(
				'mem_calendar_vars',
				array(
					'default_view' => mem_get_calendar_view( $dashboard ),
					'first_day'    => get_option( 'start_of_week' ),
					'time_format'  => mem_format_calendar_time(),
				)
			)
		);
	}

	wp_register_script( 'mem-admin-scripts', $js_dir . $file, array( 'jquery' ), MEM_VERSION_NUM );
	wp_enqueue_script( 'mem-admin-scripts' );

	wp_localize_script(
		'mem-admin-scripts',
		'mem_admin_vars',
		apply_filters(
			'mem_admin_script_vars',
			array(
				'admin_url'            => ! is_multisite() ? admin_url() : network_admin_url(),
				'ajax_loader'          => MEM_PLUGIN_URL . '/assets/images/loading.gif',
				'ajaxurl'              => mem_get_ajax_url(),
				'currency'             => mem_get_currency(),
				'currency_decimals'    => mem_currency_decimal_filter(),
				'currency_position'    => mem_get_option( 'currency_format', 'before' ),
				'currency_sign'        => mem_currency_filter( '' ),
				'currency_symbol'      => mem_currency_symbol(),
				'current_page'         => $hook,
				'deposit_is_pct'       => ( 'percentage' === mem_get_event_deposit_type() ) ? true : false,
				'editing_event'        => $editing_event,
				'load_recipient'       => isset( $_GET['recipient'] ) ? sanitize_text_field( wp_unslash( $_GET['recipient'] ) ) : false,
				'min_travel_distance'  => mem_get_option( 'travel_min_distance' ),
				'no_client_email'      => __( 'Enter an email address for the client', 'mobile-events-manager' ),
				'no_client_first_name' => __( 'Enter a first name for the client', 'mobile-events-manager' ),
				'no_txn_amount'        => __( 'Enter a transaction value', 'mobile-events-manager' ),
				'no_txn_date'          => __( 'Enter a transaction date', 'mobile-events-manager' ),
				'no_txn_for'           => __( 'What is the transaction for?', 'mobile-events-manager' ),
				'no_txn_src'           => __( 'Enter a transaction source', 'mobile-events-manager' ),
				'no_venue_name'        => __( 'Enter a name for the venue', 'mobile-events-manager' ),
				'one_month_min'        => __( 'You must have a pricing option for at least one month', 'mobile-events-manager' ),
				'one_item_min'         => __( 'Select at least one Add-on', 'mobile-events-manager' ),
				'select_months'        => __( 'Select Months', 'mobile-events-manager' ),
				'time_format'          => mem_get_option( 'time_format' ),
				'update_deposit'       => ( 'percentage' === mem_get_event_deposit_type() ) ? true : false,
				'update_travel_cost'   => mem_get_option( 'travel_add_cost', false ),
				'zero_cost'            => sprintf( esc_html__( 'Are you sure you want to save this %1$s with a total cost of %2$s?', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ), mem_currency_filter( mem_format_amount( '0.00' ) ) ),
				'setup_time_change'    => __( 'Do you want to auto set the setup time?', 'mobile-events-manager' ),
				'setup_time_interval'  => mem_get_option( 'setup_time', false ),
				'show_absence_form'    => __( 'Show absence form', 'mobile-events-manager' ),
				'hide_absence_form'    => __( 'Hide absence form', 'mobile-events-manager' ),
				'show_avail_form'      => __( 'Show availability checker', 'mobile-events-manager' ),
				'hide_avail_form'      => __( 'Hide availability checker', 'mobile-events-manager' ),
				'show_client_form'     => __( 'Show client form', 'mobile-events-manager' ),
				'hide_client_form'     => __( 'Hide client form', 'mobile-events-manager' ),
				'show_client_details'  => __( 'Show client details', 'mobile-events-manager' ),
				'hide_client_details'  => __( 'Hide client details', 'mobile-events-manager' ),
				'show_event_options'   => sprintf( esc_html__( 'Show %s options', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
				'hide_event_options'   => sprintf( esc_html__( 'Hide %s options', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
				'show_workers'         => sprintf( esc_html__( 'Show %s workers', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
				'hide_workers'         => sprintf( esc_html__( 'Hide %s workers', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ),
				'show_venue_details'   => __( 'Show venue', 'mobile-events-manager' ),
				'hide_venue_details'   => __( 'Hide venue', 'mobile-events-manager' ),
				'one_option'           => __( 'Choose an option', 'mobile-events-manager' ),
				'one_or_more_option'   => __( 'Choose one or more options', 'mobile-events-manager' ),
				'search_placeholder'   => __( 'Type to search all options', 'mobile-events-manager' ),
				'task_completed'       => __( 'Task executed successfully', 'mobile-events-manager' ),
				'type_to_search'       => __( 'Type to search', 'mobile-events-manager' ),
				'unavailable_template' => mem_get_option( 'unavailable' ),
			)
		)
	);

	wp_register_script( 'jquery-flot', $js_dir . 'jquery.flot.min.js' );
	wp_enqueue_script( 'jquery-flot' );

} // mem_register_admin_scripts
add_action( 'admin_enqueue_scripts', 'mem_register_admin_scripts' );
