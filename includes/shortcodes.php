<?php
/**
 * Contains all shortcode related functions
 *
 * @package TMEM
 * @subpackage Shortcodes
 * @since 1.3
 */
// @codingStandardsIgnoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The 'TMEM' shortcode replacements.
 * Used for pages and functions.
 *
 * THIS FUNCTION AND SHORTCODE ARE DEPRECATED SINCE 1.3.
 * Maintained for backwards compatibility.
 *
 * @return string
 * @param array $atts Insert a comment.
 */
function shortcode_tmem( $atts ) {
	// Array mapping the args to the pages/functions.
	$pairs = array(
		'Home'         => 'tmem_shortcode_home',
		'Profile'      => 'tmem_shortcode_profile',
		'Playlist'     => 'tmem_shortcode_playlist',
		'Contract'     => 'tmem_shortcode_contract',
		'Availability' => 'f_tmem_availability_form',
		'Online Quote' => 'tmem_shortcode_quote',
	);

	$pairs = apply_filters( 'tmem_filter_shortcode_pairs', $pairs );

	$args = shortcode_atts( $pairs, $atts, 'TMEM' );

	if ( isset( $atts['page'] ) && ! array_key_exists( $atts['page'], $pairs ) ) {
		$output = __( 'ERROR: Unknown Page', 'mobile-events-manager' );
	} else {
		/* Process pages */
		if ( ! empty( $atts['page'] ) ) {
			ob_start();

			if ( function_exists( $args[ $atts['page'] ] ) ) {
				$func = $args[ $atts['page'] ];
				return $func( $atts );
			} else {
				include_once $args[ $atts['page'] ];
				if ( 'Contact Form' === $atts['page'] ) {
					do_action( 'tmem_dcf_execute_shortcode', $atts );
				}

				$output = ob_get_clean();
			}
		}
		/* Process Functions */
		elseif ( ! empty( $atts['function'] ) ) {
			$func = $args[ $atts['function'] ];
			if ( function_exists( $func ) ) {
				ob_start();
				$func( $atts );
				$output = ob_get_clean();
			} else {
				return __( 'An error has occurred', 'mobile-events-manager' );
			}
		} else {
			return;
		}
	}
	return $output;
} // shortcode_tmem
add_shortcode( 'TMEM', 'shortcode_tmem' );

/**
 * TMEM Home Shortcode.
 *
 * Displays the Client Zone home page which will render event details if the client only has a single event
 * or a list of events if they have multiple events in the system.
 *
 * @since 1.3
 * @param array $atts comment here.
 *
 * @return string
 */
function tmem_shortcode_home( $atts ) {

	if ( is_user_logged_in() ) {

		global $tmem_event;

		$tmem_event = '';

		ob_start();

		$output = '';

		$client_id = get_current_user_id();

		tmem_add_content_tag(
			'event_action_buttons',
			/* translators: %1 is xyz */
			sprintf( esc_html__( '%1$s action buttons within %2$s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ), tmem_get_application_name() ),
			'tmem_do_action_buttons'
		);

		if ( isset( $_GET['event_id'] ) ) {
			$tmem_event = tmem_get_event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

			if ( ! empty( $tmem_event->ID ) ) {
				ob_start();

				tmem_get_template_part( 'event', 'single' );
				$output .= tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $client_id );
				ob_get_clean();
			} else {
				ob_start();
				tmem_get_template_part( 'event', 'none' );
				$output .= tmem_do_content_tags( ob_get_contents(), '', $client_id );
				ob_get_clean();
			}
		} else {
			$client_events = tmem_get_client_events( $client_id, tmem_active_event_statuses() );

			if ( $client_events ) {

				$slug = 'single';

				if ( count( $client_events ) > 1 ) {
					$slug = 'loop';

					ob_start();
					tmem_get_template_part( 'event', 'loop-header' );
					$output .= tmem_do_content_tags( ob_get_contents(), '', $client_id );

					do_action( 'tmem_pre_event_loop' );
					?><div id="tmem-event-loop">
					<?php
					ob_get_clean();
				}

				foreach ( $client_events as $event ) {
					$tmem_event = new TMEM_Event( $event->ID );

					ob_start();

					tmem_get_template_part( 'event', $slug );
					$output .= tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $client_id );
					ob_get_clean();
				}

				if ( 'loop' === $slug ) {
					ob_start();
					tmem_get_template_part( 'event', 'loop-footer' );
					$output .= tmem_do_content_tags( ob_get_contents(), '', $client_id );
					?>
					</div>
					<?php
					do_action( 'tmem_post_event_loop', $client_events );
					ob_get_clean();
				}
			} else {
				tmem_get_template_part( 'event', 'none' );
				$output .= tmem_do_content_tags( ob_get_contents(), '', $client_id );
				ob_get_clean();
			}
		}
		$tmem_event = '';

		return $output;
	} else {
		echo tmem_login_form( tmem_get_current_page_url() );
	}

} // tmem_shortcode_home
add_shortcode( 'tmem-home', 'tmem_shortcode_home' );

/**
 * TMEM Contract Shortcode.
 *
 * Displays the TMEM contract page to allow the client to review and sign their event contract.
 *
 * @since 1.3
 *
 * @return string
 */
function tmem_shortcode_contract( $atts ) {

	if ( isset( $_GET['event_id'] ) && tmem_event_exists( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ) {
		if ( is_user_logged_in() ) {
			global $tmem_event;

			$tmem_event = new TMEM_Event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

			$status = ! $tmem_event->get_contract_status() ? '' : 'signed';

			if ( $tmem_event ) {
				ob_start();
				tmem_get_template_part( 'contract', $status );

				// Do not replace tags in a signed contract.
				if ( 'signed' === $status ) {
					$output = tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $tmem_event->client );
				} else {
					$output = tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $tmem_event->client );
				}
				ob_get_clean();
			} else {
				return sprintf( wp_kses_post( "Ooops! There seems to be a slight issue and we've been unable to find your %s.", 'mobile-events-manager' ), tmem_get_label_singular( true ) );
			}

			// Reset global var.
			$tmem_event = '';

			return $output;
		} else {
			echo tmem_login_form( tmem_get_current_page_url() );
		}
	} else {
		return sprintf( esc_html__( "Ooops! There seems to be a slight issue and we've been unable to find your %s.", 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) );
	}

} // tmem_shortcode_contract
add_shortcode( 'tmem-contract', 'tmem_shortcode_contract' );

/**
 * Payment Form shortcode.
 *
 * Displays the payment form to collect event payments.
 *
 * @since 1.3.8
 * @param arr $atts
 * @return string
 */
function tmem_shortcode_payment( $atts ) {

	if ( is_user_logged_in() ) {

		global $tmem_event;

		if ( isset( $_GET['event_id'] ) ) {
			$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
		} else {
			$next_event = tmem_get_clients_next_event( get_current_user_id() );

			if ( $next_event ) {
				$event_id = sanitize_text_field( $next_event[0]->ID );
			}
		}

		if ( ! isset( $event_id ) ) {
			return __( "Ooops! There seems to be a slight issue and we've been unable to find your event", 'mobile-events-manager' );
		}

		$tmem_event = new TMEM_Event( $event_id );

		if ( $tmem_event ) {

			return tmem_payment_form();

		} else {
			return __( "Ooops! There seems to be a slight issue and we've been unable to find your event", 'mobile-events-manager' );
		}

		// Reset global var
		$tmem_event = '';

	} else {
		echo tmem_login_form();
	}

} // tmem_shortcode_payment
add_shortcode( 'tmem-payments', 'tmem_shortcode_payment' );

/**
 * TMEM Profile Shortcode.
 *
 * Displays the TMEM user Profile page.
 *
 * @since 1.3
 * @param arr $atts
 * @return string
 */
function tmem_shortcode_profile( $atts ) {
	ob_start();

	tmem_get_template_part( 'profile', 'client' );

	return ob_get_clean() . '&nbsp;';
} // tmem_shortcode_profile
add_shortcode( 'tmem-profile', 'tmem_shortcode_profile' );

/**
 * TMEM Playlist Shortcode.
 *
 * Displays the TMEM playlist management system which will render a client interface for clients
 * or a guest interface for event guests with the access URL.
 *
 * @since 1.3
 *
 * @return string
 */
function tmem_shortcode_playlist( $atts ) {

	global $tmem_event;

	if ( isset( $_GET['tmemeventid'] ) ) {
		$_GET['guest_playlist'] = absint( $_GET['tmemeventid'] );
	}

	$visitor  = isset( $_GET['guest_playlist'] ) ? 'guest' : 'client';
	$output   = '';
	$event_id = '';

	if ( ! empty( $_GET['event_id'] ) ) {
		$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
	} else {
		$next_event = tmem_get_clients_next_event( get_current_user_id() );

		if ( $next_event ) {
			$event_id = sanitize_text_field( $next_event[0]->ID );
		}
	}

	if ( ! isset( $event_id ) && ! isset( $_GET['guest_playlist'] ) ) {
		ob_start();
		tmem_get_template_part( 'playlist', 'noevent' );
		$output .= tmem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
	} else {

		$tmem_event = 'client' === $visitor ? tmem_get_event( $event_id ) : tmem_get_event_by_playlist_code( sanitize_text_field( wp_unslash( $_GET['guest_playlist'] ) ) );

		ob_start();

		if ( 'client' === $visitor && ! is_user_logged_in() ) {
			$output .= tmem_login_form( add_query_arg( 'event_id', $event_id, tmem_get_formatted_url( tmem_get_option( 'playlist_page' ) ) ) );
		} elseif ( $tmem_event ) {
			tmem_get_template_part( 'playlist', $visitor );
			$output .= tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $tmem_event->client );
		} else {
			tmem_get_template_part( 'playlist', 'noevent' );
			$output .= tmem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
		}
	}

	ob_get_clean();

	// Reset global var.
	$tmem_event = '';

	return apply_filters( 'tmem_playlist_form', $output );

} // tmem_shortcode_playlist
add_shortcode( 'tmem-playlist', 'tmem_shortcode_playlist' );

/**
 * TMEM Quote Shortcode.
 *
 * Displays the online quotation to the client.
 *
 * @since 1.3
 * @param arr $atts Arguments passed with the shortcode.
 * @return string
 */
function tmem_shortcode_quote( $atts ) {

	$atts = shortcode_atts(
		array( // These are our default values.
			'button_text' => sprintf( esc_html__( 'Book this %s', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		),
		$atts,
		'tmem-quote'
	);

	$event_id = '';

	if ( ! empty( $_GET['event_id'] ) ) {
		$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
	} else {
		$next_event = tmem_get_clients_next_event( get_current_user_id() );

		if ( $next_event ) {
			$event_id = sanitize_text_field( $next_event[0]->ID );
		}
	}

	if ( isset( $event_id ) && tmem_event_exists( $event_id ) ) {

		if ( is_user_logged_in() ) {

			global $tmem_event, $tmem_quote_button_atts;

			$tmem_quote_button_atts = $atts;

			$tmem_event = new TMEM_Event( $event_id );

			ob_start();

			if ( $tmem_event ) {

				// Some verification.
				if ( get_current_user_id() !== $tmem_event->client ) {
					tmem_get_template_part( 'quote', 'noevent' );
				} else {
					tmem_get_template_part( 'quote' );
				}

				$output = tmem_do_content_tags( ob_get_contents(), $tmem_event->ID, $tmem_event->client );

			} else {
				tmem_get_template_part( 'quote', 'noevent' );

				$output = tmem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
			}

			ob_get_clean();

			// Reset global var.
			$tmem_event = '';

			return $output;

		} else {
			echo tmem_login_form( tmem_get_current_page_url() );
		}
	} else {
		ob_start();
		tmem_get_template_part( 'quote', 'noevent' );
		$output = tmem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
		ob_get_clean();
	}

} // tmem_shortcode_quote
add_shortcode( 'tmem-quote', 'tmem_shortcode_quote' );

/**
 * TMEM Availability Checker Shortcode.
 *
 * Displays the TMEM Availability Checker form which allows clients to determine if you are
 * available on their chosen event date.
 *
 * @since 1.3
 *
 * @return string
 */
function tmem_shortcode_availability( $atts ) {

	$atts = shortcode_atts(
		array( // These are our default values.
			'label'             => __( 'Select Date', 'mobile-events-manager' ) . ':',
			'label_class'       => 'tmem-label',
			'field_class'       => '',
			'submit_text'       => __( 'Check Availability', 'mobile-events-manager' ),
			'submit_class'      => '',
			'please_wait_text'  => __( 'Please wait...', 'mobile-events-manager' ),
			'please_wait_class' => '',
			'display'           => 'horizontal',
		),
		$atts,
		'tmem-availability'
	);

	$field_id = 'tmem-availability-datepicker';

	$search  = array( '{label}', '{label_class}', '{field}', '{field_class}', '{submit_text}', '{submit_class}', '{please_wait_text}', '{please_wait_class}' );
	$replace = array(
		$atts['label'],
		$atts['label_class'],
		$field_id,
		$atts['field_class'],
		$atts['submit_text'],
		$atts['submit_class'],
		$atts['please_wait_text'],
		$atts['please_wait_class'],
	);

	ob_start();

	tmem_insert_datepicker(
		array(
			'class'    => '',
			'id'       => $field_id,
			'altfield' => 'availability_check_date',
			'mindate'  => '1',
		)
	);

	echo '<!-- ' . __( 'TMEM Availability Checker', 'mobile-events-manager' ) . ' (' . TMEM_VERSION_NUM . ') -->';
	echo '<form name="tmem-availability-check" id="tmem-availability-check" method="post">';
	wp_nonce_field( 'do_availability_check', 'tmem_nonce', true, true );
	tmem_action_field( 'do_availability_check' );
	echo '<input type="hidden" name="availability_check_date" id="availability_check_date" />';
	tmem_get_template_part( 'availability', $atts['display'], true );
	echo '</form>';

	$output  = ob_get_clean();
	$output  = str_replace( $search, $replace, $output );
	$output  = tmem_do_content_tags( $output );
	$output .= '<!-- ' . __( 'TMEM Availability Checker', 'mobile-events-manager' ) . ' (' . TMEM_VERSION_NUM . ') -->';

	return apply_filters( 'tmem_availability_form', esc_html( $output ) );

} // tmem_shortcode_availability
add_shortcode( 'tmem-availability', 'tmem_shortcode_availability' );

/**
 * Addons List Shortcode.
 *
 * @param arr     $atts Shortcode attributes. See $atts.
 * @param str|int $filter_value The value to which to filter $filter_by. Default false (all).
 * @param str     $list List type to display. li for bulleted. Default p.
 * @param bool    $cost Whether or not display the price. Default false.
 */
function tmem_shortcode_addons_list( $atts ) {

	global $post;

	$atts = shortcode_atts(
		array( // These are our default values.
			'filter_by'    => false,
			'filter_value' => false,
			'list'         => 'p',
			'desc'         => false,
			'desc_length'  => tmem_get_option( 'package_excerpt_length', 55 ),
			'cost'         => false,
			'addon_class'  => false,
			'cost_class'   => false,
			'desc_class'   => false,
		),
		$atts,
		'tmem-addons'
	);

	ob_start();
	$output = '';

	if ( ! empty( $post ) && 'tmem-package' === get_post_type( $post->ID ) ) {
		$package_addons = tmem_get_package_addons( $post->ID );

		$addons = array();
		foreach ( $package_addons as $package ) {
			$addons[] = tmem_get_addon( $package );
		}
	} elseif ( ! empty( $atts['filter_by'] ) && ! empty( $atts['filter_value'] ) && 'false' !== $atts['filter_by'] && 'false' !== $atts['filter_value'] ) {

		// Filter addons by user.
		if ( 'category' === $atts['filter_by'] ) {

			$addons = tmem_get_addons_in_category( $atts['filter_value'] );

		} elseif ( 'package' === $atts['filter_by'] ) {
			if ( ! is_numeric( $atts['filter_value'] ) ) { // For backwards compatibility.
				$package = tmem_get_package_by( 'slug', $atts['filter_value'] );
				if ( $package ) {
					$atts['filter_value'] = $package->ID;
				}
			}

			$package_addons = tmem_get_package_addons( $atts['filter_value'] );

			$addons = array();
			foreach ( $package_addons as $package ) {
				$addons[] = tmem_get_addon( $package );
			}
		} elseif ( 'user' === $atts['filter_by'] ) {
			$addons = tmem_get_addons_by_employee( $atts['filter_value'] );
		}
	} else {
		$addons = tmem_get_addons();
	}

	/**
	 * Output the results
	 */
	if ( ! $addons ) {
		$output .= '<p>' . __( 'No addons available', 'mobile-events-manager' ) . '</p>';
	} else {

		// Check to start bullet list.
		if ( 'li' === $atts['list'] ) {
			$output .= '<ul>';
		}

		foreach ( $addons as $addon ) {

			// Output the remaining addons.
			if ( ! empty( $atts['list'] ) ) {
				$output .= '<' . esc_html( $atts['list'] ) . '>';
			}

			if ( ! empty( $atts['addon_class'] ) && 'false' !== $atts['addon_class'] ) {
				$output = '<span class="' . esc_html( $atts['addon_class'] ) . '">';
			}

			$output .= $addon->post_title;

			if ( ! empty( $atts['addon_class'] ) && 'false' !== $atts['addon_class'] ) {
				$output = '</span>';
			}

			$cost = tmem_get_addon_price( $addon->ID );
			if ( ! empty( $atts['cost'] ) && 'false' !== $atts['cost'] && ! empty( $cost ) ) {

				if ( ! empty( $atts['cost_class'] ) && 'false' === $atts['cost_class'] ) {
					$output = '<span class="' . esc_html( $atts['cost_class'] ) . '">';
				}

				$output .= '&nbsp;&ndash;&nbsp;' . tmem_currency_filter( tmem_format_amount( $cost ) );

				if ( ! empty( $atts['cost_class'] ) && 'false' !== $atts['cost_class'] ) {
					$output = '</span>';
				}
			}

			$desc = tmem_get_addon_excerpt( $addon->ID, $atts['desc_length'] );
			if ( ! empty( $atts['desc'] ) && 'false' !== $atts['desc'] && ! empty( $desc ) ) {

				$output .= '<br />';

				if ( ! empty( $atts['desc_class'] ) && 'false' !== $atts['desc_class'] ) {
					$output = '<span class="' . esc_html( $atts['desc_class'] ) . '">';
				} else {
					$output .= '<span style="font-style: italic; font-size: smaller;">';
				}

				$output .= $desc;
				$output .= '</span>';

			}

			if ( ! empty( $atts['list'] ) ) {
				$output .= '</' . esc_html( $atts['list'] ) . '>';
			}
		}

		// Check to end bullet list.
		if ( 'li' === $atts['list'] ) {
			$output .= '</ul>';
		}
	}

	echo esc_html_e( apply_filters( 'tmem_shortcode_addons_list', $output ) );

	return ob_get_clean();

} // tmem_shortcode_addons_list
add_shortcode( 'tmem-addons', 'tmem_shortcode_addons_list' );

/**
 * TMEM Login Shortcode.
 *
 * Displays a login form for the front end of the website.
 *
 * @since 1.3
 *
 * @return string
 */
function tmem_shortcode_login( $atts ) {

	extract(
		shortcode_atts(
			array(
				'redirect' => '',
			),
			$atts,
			'tmem-login'
		)
	);

	return tmem_login_form( $redirect );

} // tmem_shortcode_home
add_shortcode( 'tmem-login', 'tmem_shortcode_login' );
