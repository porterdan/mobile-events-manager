<?php
/**
 * Scripts
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
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 1.3
 * @global $post
 * @return void
 */
function tmem_load_scripts() {

	$js_dir        = TMEM_PLUGIN_URL . '/assets/js/';
	$suffix        = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$is_payment    = tmem_is_payment() ? '1' : '0';
	$agree_privacy = tmem_get_option( 'show_agree_to_privacy_policy', false );
	$privacy_page  = tmem_get_privacy_page();
	$privacy       = false;
	$agree_terms   = tmem_get_option( 'show_agree_to_terms', false );
	$terms_text    = tmem_get_option( 'agree_terms_text', false );
	$terms_label   = tmem_get_option( 'agree_terms_label', false );
	$terms         = false;
	$thickbox      = false;
	$privacy_error = tmem_messages( 'agree_to_policy' );
	$privacy_error = $privacy_error['message'];
	$terms_error   = tmem_messages( 'agree_to_terms' );
	$terms_error   = $terms_error['message'];

	if ( ! empty( $agree_privacy ) && ! empty( $privacy_page ) ) {
		$privacy = true;

		if ( 'thickbox' === tmem_get_option( 'show_agree_policy_type' ) ) {
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

	wp_register_script( 'tmem-ajax', $js_dir . 'tmem-ajax.min.js', array( 'jquery' ), TMEM_VERSION_NUM );
	wp_enqueue_script( 'tmem-ajax' );

	wp_localize_script(
		'tmem-ajax',
		'tmem_vars',
		apply_filters(
			'tmem_script_vars',
			array(
				'ajaxurl'                   => tmem_get_ajax_url(),
				'ajax_loader'               => TMEM_PLUGIN_URL . '/assets/images/loading.gif',
				'availability_ajax'         => tmem_get_option( 'avail_ajax', false ),
				'available_redirect'        => tmem_get_option( 'availability_check_pass_page', 'text' ) !== 'text' ? tmem_get_formatted_url( tmem_get_option( 'availability_check_pass_page' ) ) : 'text',
				'available_text'            => tmem_get_option( 'availability_check_pass_text', false ),
				'complete_payment'          => tmem_get_payment_button_text(),
				'date_format'               => tmem_format_datepicker_date(),
				'default_gateway'           => tmem_get_default_gateway(),
				'default_playlist_category' => tmem_get_option( 'playlist_default_cat' ),
				'first_day'                 => get_option( 'start_of_week' ),
				'guest_playlist_closed'     => sprintf( esc_html__( 'The playlist for this %s is now closed and not accepting suggestions', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
				'is_payment'                => $is_payment,
				'no_card_name'              => __( 'Enter the name printed on your card', 'mobile-events-manager' ),
				'no_payment_amount'         => __( 'Select Payment Amount', 'mobile-events-manager' ),
				'payment_loading'           => __( 'Please Wait...', 'mobile-events-manager' ),
				'playlist_page'             => tmem_get_formatted_url( tmem_get_option( 'playlist_page' ) ),
				'playlist_updated'          => __( 'Your entry was added successfully', 'mobile-events-manager' ),
				'privacy_error'             => esc_html( $privacy_error ),
				'profile_page'              => tmem_get_formatted_url( tmem_get_option( 'profile_page' ) ),
				'profile_updated'           => __( 'Your details have been updated', 'mobile-events-manager' ),
				'required_date_message'     => __( 'Please select a date', 'mobile-events-manager' ),
				'require_privacy'           => $privacy,
				'require_terms'             => $terms,
				'rest_url'                  => esc_url_raw( rest_url( 'tmem/v1/' ) ),
				'submit_client_profile'     => __( 'Update Details', 'mobile-events-manager' ),
				'submit_guest_playlist'     => __( 'Suggest Song', 'mobile-events-manager' ),
				'submit_playlist'           => __( 'Add to Playlist', 'mobile-events-manager' ),
				'submit_playlist_loading'   => __( 'Please Wait...', 'mobile-events-manager' ),
				'submit_profile_loading'    => __( 'Please Wait...', 'mobile-events-manager' ),
				'unavailable_redirect'      => tmem_get_option( 'availability_check_fail_page', 'text' ) !== 'text' ? tmem_get_formatted_url( tmem_get_option( 'availability_check_fail_page' ) ) : 'text',
				'terms_error'               => esc_html( $terms_error ),
				'unavailable_text'          => tmem_get_option( 'availability_check_fail_text', false ),
			)
		)
	);

	wp_register_script( 'jquery-validation-plugin', TMEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );

	wp_enqueue_script( 'jquery-validation-plugin' );

	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

} // tmem_load_scripts
add_action( 'wp_enqueue_scripts', 'tmem_load_scripts' );

/**
 * Load Frontend Styles
 *
 * Enqueues the required styles for the frontend.
 *
 * @since 1.3
 * @return void
 */
function tmem_register_styles() {
	global $post;

	$templates_dir = tmem_get_theme_template_dir_name();
	$css_dir       = TMEM_PLUGIN_URL . '/assets/css/';
	$suffix        = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file          = 'tmem' . $suffix . '.css';

	$child_theme_style_sheet  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$parent_theme_style_sheet = trailingslashit( get_template_directory() ) . $templates_dir . $file;
	$tmem_plugin_style_sheet  = trailingslashit( tmem_get_templates_dir() ) . $file;

	// Look in the child theme, followed by the parent theme, and finally the TMEM template DIR.
	// Allows users to copy the TMEM stylesheet to their theme DIR and customise.
	if ( file_exists( $child_theme_style_sheet ) ) {
		$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
	} elseif ( file_exists( $parent_theme_style_sheet ) ) {
		$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
	} elseif ( file_exists( $tmem_plugin_style_sheet ) || file_exists( $tmem_plugin_style_sheet ) ) {
		$url = trailingslashit( tmem_get_templates_url() ) . $file;
	}

	wp_register_style( 'tmem-styles', $url, array(), TMEM_VERSION_NUM );
	wp_enqueue_style( 'tmem-styles' );

	wp_register_style( 'font-awesome', TMEM_PLUGIN_URL . '/assets/libs/font-awesome/font-awesome.min.css' );
	wp_enqueue_style( 'font-awesome' );

	if ( ! empty( $post ) ) {
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'tmem-availability' ) ) {
			wp_register_style( 'jquery-ui-css', $css_dir . 'jquery-ui.min.css', array(), TMEM_VERSION_NUM );
		}
	}

	if ( tmem_is_payment( true ) ) {
		wp_enqueue_style( 'dashicons' );
	}

} // tmem_register_styles
add_action( 'wp_enqueue_scripts', 'tmem_register_styles' );

/**
 * Load Admin Styles
 *
 * Enqueues the required styles for admin.
 *
 * @since 1.3
 * @return void
 */
function tmem_register_admin_styles( $hook ) {

	$ui_style = ( 'classic' === get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';
	$css_dir  = TMEM_PLUGIN_URL . '/assets/css/';
	$libs_dir = TMEM_PLUGIN_URL . '/assets/libs/';
	$suffix   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file     = 'tmem-admin.min.css';

	wp_register_style( 'jquery-chosen', $css_dir . 'chosen.min.css', array(), TMEM_PLUGIN_URL );
	wp_register_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . '.min.css' );
	wp_register_style( 'font-awesome', TMEM_PLUGIN_URL . '/assets/libs/font-awesome/font-awesome.min.css' );

	wp_enqueue_style( 'jquery-chosen' );
	wp_enqueue_style( 'jquery-ui-css' );
	wp_enqueue_style( 'font-awesome' );

	// Settings page color picker.
	if ( 'tmem-event_page_tmem-settings' === $hook ) {
		wp_enqueue_style( 'wp-color-picker' );
	}

	// Availability calendar
	if ( 'tmem-event_page_tmem-availability' === $hook ) {

		wp_register_style( TMEM_PLUGIN_URL . '/assets/libs/bootstrap/bootstrap.min.css', array(), TMEM_VERSION_NUM );
		wp_register_style(
			'tmem-fullcalendar-css',
			$libs_dir . 'fullcalendar/fullcalendar.min.css',
			array(),
			TMEM_VERSION_NUM
		);

		wp_enqueue_style( 'tmem-fullcalendar-css' );
		wp_enqueue_style( 'tmem-bootstrap-css' );
	}

	wp_register_style( 'tmem-admin', $css_dir . $file, '', TMEM_VERSION_NUM );
	wp_enqueue_style( 'tmem-admin' );

} // tmem_register_styles
add_action( 'admin_enqueue_scripts', 'tmem_register_admin_styles' );

/**
 * Load Admin Scripts
 *
 * Enqueues the required scripts for admin.
 *
 * @since 1.3
 * @return void
 */
function tmem_register_admin_scripts( $hook ) {

	$js_dir             = TMEM_PLUGIN_URL . '/assets/js/';
	$libs_dir           = TMEM_PLUGIN_URL . '/assets/libs/';
	$suffix             = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$file               = 'admin-scripts.min.js';
	$dashboard          = 'index.php' === $hook ? true : false;
	$editing_event      = false;
	$require_validation = array( 'tmem-event_page_tmem-comms' );
	$sortable           = array(
		'admin_page_tmem-custom-event-fields',
		'admin_page_tmem-custom-client-fields',
	);

	wp_register_script( 'jquery-chosen', $js_dir . 'chosen.jquery.min.js', array( 'jquery' ), TMEM_VERSION_NUM );
	wp_enqueue_script( 'jquery-chosen' );

	wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );

	if ( strpos( $hook, 'tmem' ) ) {
		wp_enqueue_script( 'jquery' );
	}

	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		if ( isset( $_GET['post'] ) && 'tmem-addon' !== get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			$sortable[] = 'post.php';
			$sortable[] = 'post-new.php';
		}

		if ( isset( $_GET['post'] ) && 'tmem-event' === get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			$editing_event = true;
		}

		if ( isset( $_GET['post_type'] ) && 'tmem-event' === $_GET['post_type'] ) {
			$editing_event = true;
		}

		if ( $editing_event ) {
			$require_validation[] = 'post.php';
			$require_validation[] = 'post-new.php';
		}

		if ( isset( $_GET['post'] ) && 'tmem-transaction' !== get_post_type( sanitize_text_field( wp_unslash( $_GET['post'] ) ) ) ) {
			wp_register_script( 'tmem-trans-js', TMEM_PLUGIN_URL . '/assets/js/tmem-trans-post-val.js', array( 'jquery' ), TMEM_VERSION_NUM );
			wp_enqueue_script( 'tmem-trans-js' );
			wp_localize_script( 'tmem-trans-js', 'transaction_type', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}
	}

	if ( in_array( $hook, $require_validation ) ) {
		wp_register_script( 'jquery-validation-plugin', TMEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );
		wp_enqueue_script( 'jquery-validation-plugin' );
	}

	if ( in_array( $hook, $sortable ) ) {
		wp_enqueue_script( 'jquery-ui-sortable' );
	}

	// Settings page color picker.
	if ( 'tmem-event_page_tmem-settings' === $hook ) {
		wp_enqueue_script( 'wp-color-picker' );
	}

	// Availability calendar.
	if ( 'tmem-event_page_tmem-availability' === $hook ) {
		wp_register_script(
			'tmem-moment-js',
			$libs_dir . 'moment/moment-with-locales.min.js',
			array( 'jquery' ),
			TMEM_VERSION_NUM
		);
		wp_register_script(
			'tmem-fullcalendar-js',
			$libs_dir . 'fullcalendar/fullcalendar.min.js',
			array( 'jquery', 'tmem-moment-js' ),
			TMEM_VERSION_NUM
		);

				wp_register_script( 'jquery-validation-plugin', TMEM_PLUGIN_URL . '/assets/libs/popper/popper.min.js', false );

		wp_register_script(
			'tmem-popper-js',
			TMEM_PLUGIN_URL . '/assets/libs/popper/popper.min.js',
			array( 'jquery' ),
			TMEM_VERSION_NUM
		);
		wp_register_script(
			'tmem-bootstrap-js',
			TMEM_PLUGIN_URL . '/assets/libs/bootstrap/bootstrap.min.js',
			array( 'jquery' ),
			TMEM_VERSION_NUM
		);
		wp_register_script(
			'tmem-availability-scripts-js',
			$js_dir . 'availability-scripts.min.js',
			array( 'jquery', 'tmem-moment-js', 'tmem-fullcalendar-js' ),
			TMEM_VERSION_NUM
		);

		wp_enqueue_script( 'tmem-moment-js' );
		wp_enqueue_script( 'tmem-fullcalendar-js' );
		wp_enqueue_script( 'tmem-popper-js' );
		wp_enqueue_script( 'tmem-bootstrap-js' );
		wp_enqueue_script( 'tmem-availability-scripts-js' );

		wp_localize_script(
			'tmem-availability-scripts-js',
			'tmem_calendar_vars',
			apply_filters(
				'tmem_calendar_vars',
				array(
					'default_view' => tmem_get_calendar_view( $dashboard ),
					'first_day'    => get_option( 'start_of_week' ),
					'time_format'  => tmem_format_calendar_time(),
				)
			)
		);
	}

	wp_register_script( 'tmem-admin-scripts', $js_dir . $file, array( 'jquery' ), TMEM_VERSION_NUM );
	wp_enqueue_script( 'tmem-admin-scripts' );

	wp_localize_script(
		'tmem-admin-scripts',
		'tmem_admin_vars',
		apply_filters(
			'tmem_admin_script_vars',
			array(
				'admin_url'            => ! is_multisite() ? admin_url() : network_admin_url(),
				'ajax_loader'          => TMEM_PLUGIN_URL . '/assets/images/loading.gif',
				'ajaxurl'              => tmem_get_ajax_url(),
				'currency'             => tmem_get_currency(),
				'currency_decimals'    => tmem_currency_decimal_filter(),
				'currency_position'    => tmem_get_option( 'currency_format', 'before' ),
				'currency_sign'        => tmem_currency_filter( '' ),
				'currency_symbol'      => tmem_currency_symbol(),
				'current_page'         => $hook,
				'deposit_is_pct'       => ( 'percentage' === tmem_get_event_deposit_type() ) ? true : false,
				'editing_event'        => $editing_event,
				'load_recipient'       => isset( $_GET['recipient'] ) ? sanitize_text_field( wp_unslash( $_GET['recipient'] ) ) : false,
				'min_travel_distance'  => tmem_get_option( 'travel_min_distance' ),
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
				'time_format'          => tmem_get_option( 'time_format' ),
				'update_deposit'       => ( 'percentage' === tmem_get_event_deposit_type() ) ? true : false,
				'update_travel_cost'   => tmem_get_option( 'travel_add_cost', false ),
				'zero_cost'            => sprintf( esc_html__( 'Are you sure you want to save this %1$s with a total cost of %2$s?', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ), tmem_currency_filter( tmem_format_amount( '0.00' ) ) ),
				'setup_time_change'    => __( 'Do you want to auto set the setup time?', 'mobile-events-manager' ),
				'setup_time_interval'  => tmem_get_option( 'setup_time', false ),
				'show_absence_form'    => __( 'Show absence form', 'mobile-events-manager' ),
				'hide_absence_form'    => __( 'Hide absence form', 'mobile-events-manager' ),
				'show_avail_form'      => __( 'Show availability checker', 'mobile-events-manager' ),
				'hide_avail_form'      => __( 'Hide availability checker', 'mobile-events-manager' ),
				'show_client_form'     => __( 'Show client form', 'mobile-events-manager' ),
				'hide_client_form'     => __( 'Hide client form', 'mobile-events-manager' ),
				'show_client_details'  => __( 'Show client details', 'mobile-events-manager' ),
				'hide_client_details'  => __( 'Hide client details', 'mobile-events-manager' ),
				'show_event_options'   => sprintf( esc_html__( 'Show %s options', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
				'hide_event_options'   => sprintf( esc_html__( 'Hide %s options', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
				'show_workers'         => sprintf( esc_html__( 'Show %s workers', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
				'hide_workers'         => sprintf( esc_html__( 'Hide %s workers', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
				'show_venue_details'   => __( 'Show venue', 'mobile-events-manager' ),
				'hide_venue_details'   => __( 'Hide venue', 'mobile-events-manager' ),
				'one_option'           => __( 'Choose an option', 'mobile-events-manager' ),
				'one_or_more_option'   => __( 'Choose one or more options', 'mobile-events-manager' ),
				'search_placeholder'   => __( 'Type to search all options', 'mobile-events-manager' ),
				'task_completed'       => __( 'Task executed successfully', 'mobile-events-manager' ),
				'type_to_search'       => __( 'Type to search', 'mobile-events-manager' ),
				'unavailable_template' => tmem_get_option( 'unavailable' ),
			)
		)
	);

	wp_register_script( 'jquery-flot', $js_dir . 'jquery.flot.min.js' );
	wp_enqueue_script( 'jquery-flot' );

} // tmem_register_admin_scripts
add_action( 'admin_enqueue_scripts', 'tmem_register_admin_scripts' );
