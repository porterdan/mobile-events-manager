<?php
/**
 * Contains all shortcode related functions
 *
 * @package MEM
 * @subpackage Shortcodes
 * @since 1.0
 */
// @codingStandardsIgnoreFile
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The 'MEM' shortcode replacements.
 * Used for pages and functions.
 *
 * THIS FUNCTION AND SHORTCODE ARE DEPRECATED SINCE 1.3.
 * Maintained for backwards compatibility.
 *
 * @return string
 * @param array $atts Insert a comment.
 */
function shortcode_mem( $atts ) {
	// Array mapping the args to the pages/functions.
	$pairs = array(
		'Home'         => 'mem_shortcode_home',
		'Profile'      => 'mem_shortcode_profile',
		'Playlist'     => 'mem_shortcode_playlist',
		'Contract'     => 'mem_shortcode_contract',
		'Availability' => 'f_mem_availability_form',
		'Online Quote' => 'mem_shortcode_quote',
	);

	$pairs = apply_filters( 'mem_filter_shortcode_pairs', $pairs );

	$args = shortcode_atts( $pairs, $atts, 'MEM' );

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
					do_action( 'mem_dcf_execute_shortcode', $atts );
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
} // shortcode_mem
add_shortcode( 'MEM', 'shortcode_mem' );

/**
 * MEM Home Shortcode.
 *
 * Displays the Client Zone home page which will render event details if the client only has a single event
 * or a list of events if they have multiple events in the system.
 *
 * @since 1.3
 * @param array $atts comment here.
 *
 * @return string
 */
function mem_shortcode_home( $atts ) {

	if ( is_user_logged_in() ) {

		global $mem_event;

		$mem_event = '';

		ob_start();

		$output = '';

		$client_id = get_current_user_id();

		mem_add_content_tag(
			'event_action_buttons',
			/* translators: %1 is xyz */
			sprintf( esc_html__( '%1$s action buttons within %2$s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ), mem_get_application_name() ),
			'mem_do_action_buttons'
		);

		if ( isset( $_GET['event_id'] ) ) {
			$mem_event = mem_get_event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

			if ( ! empty( $mem_event->ID ) ) {
				ob_start();

				mem_get_template_part( 'event', 'single' );
				$output .= mem_do_content_tags( ob_get_contents(), $mem_event->ID, $client_id );
				ob_get_clean();
			} else {
				ob_start();
				mem_get_template_part( 'event', 'none' );
				$output .= mem_do_content_tags( ob_get_contents(), '', $client_id );
				ob_get_clean();
			}
		} else {
			$client_events = mem_get_client_events( $client_id, mem_active_event_statuses() );

			if ( $client_events ) {

				$slug = 'single';

				if ( count( $client_events ) > 1 ) {
					$slug = 'loop';

					ob_start();
					mem_get_template_part( 'event', 'loop-header' );
					$output .= mem_do_content_tags( ob_get_contents(), '', $client_id );

					do_action( 'mem_pre_event_loop' );
					?><div id="mem-event-loop">
					<?php
					ob_get_clean();
				}

				foreach ( $client_events as $event ) {
					$mem_event = new MEM_Event( $event->ID );

					ob_start();

					mem_get_template_part( 'event', $slug );
					$output .= mem_do_content_tags( ob_get_contents(), $mem_event->ID, $client_id );
					ob_get_clean();
				}

				if ( 'loop' === $slug ) {
					ob_start();
					mem_get_template_part( 'event', 'loop-footer' );
					$output .= mem_do_content_tags( ob_get_contents(), '', $client_id );
					?>
					</div>
					<?php
					do_action( 'mem_post_event_loop', $client_events );
					ob_get_clean();
				}
			} else {
				mem_get_template_part( 'event', 'none' );
				$output .= mem_do_content_tags( ob_get_contents(), '', $client_id );
				ob_get_clean();
			}
		}
		$mem_event = '';

		return $output;
	} else {
		echo mem_login_form( mem_get_current_page_url() );
	}

} // mem_shortcode_home
add_shortcode( 'mem-home', 'mem_shortcode_home' );

/**
 * MEM Contract Shortcode.
 *
 * Displays the MEM contract page to allow the client to review and sign their event contract.
 *
 * @since 1.3
 *
 * @return string
 */
function mem_shortcode_contract( $atts ) {

	if ( isset( $_GET['event_id'] ) && mem_event_exists( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) ) {
		if ( is_user_logged_in() ) {
			global $mem_event;

			$mem_event = new MEM_Event( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) );

			$status = ! $mem_event->get_contract_status() ? '' : 'signed';

			if ( $mem_event ) {
				ob_start();
				mem_get_template_part( 'contract', $status );

				// Do not replace tags in a signed contract.
				if ( 'signed' === $status ) {
					$output = mem_do_content_tags( ob_get_contents(), $mem_event->ID, $mem_event->client );
				} else {
					$output = mem_do_content_tags( ob_get_contents(), $mem_event->ID, $mem_event->client );
				}
				ob_get_clean();
			} else {
				return sprintf( wp_kses_post( "Ooops! There seems to be a slight issue and we've been unable to find your %s.", 'mobile-events-manager' ), mem_get_label_singular( true ) );
			}

			// Reset global var.
			$mem_event = '';

			return $output;
		} else {
			echo mem_login_form( mem_get_current_page_url() );
		}
	} else {
		return sprintf( esc_html__( "Ooops! There seems to be a slight issue and we've been unable to find your %s.", 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) );
	}

} // mem_shortcode_contract
add_shortcode( 'mem-contract', 'mem_shortcode_contract' );

/**
 * Payment Form shortcode.
 *
 * Displays the payment form to collect event payments.
 *
 * @since 1.3.8
 * @param arr $atts
 * @return string
 */
function mem_shortcode_payment( $atts ) {

	if ( is_user_logged_in() ) {

		global $mem_event;

		if ( isset( $_GET['event_id'] ) ) {
			$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
		} else {
			$next_event = mem_get_clients_next_event( get_current_user_id() );

			if ( $next_event ) {
				$event_id = sanitize_text_field( $next_event[0]->ID );
			}
		}

		if ( ! isset( $event_id ) ) {
			return __( "Ooops! There seems to be a slight issue and we've been unable to find your event", 'mobile-events-manager' );
		}

		$mem_event = new MEM_Event( $event_id );

		if ( $mem_event ) {

			return mem_payment_form();

		} else {
			return __( "Ooops! There seems to be a slight issue and we've been unable to find your event", 'mobile-events-manager' );
		}

		// Reset global var
		$mem_event = '';

	} else {
		echo mem_login_form();
	}

} // mem_shortcode_payment
add_shortcode( 'mem-payments', 'mem_shortcode_payment' );

/**
 * MEM Profile Shortcode.
 *
 * Displays the MEM user Profile page.
 *
 * @since 1.3
 * @param arr $atts
 * @return string
 */
function mem_shortcode_profile( $atts ) {
	ob_start();

	mem_get_template_part( 'profile', 'client' );

	return ob_get_clean() . '&nbsp;';
} // mem_shortcode_profile
add_shortcode( 'mem-profile', 'mem_shortcode_profile' );

/**
 * MEM Playlist Shortcode.
 *
 * Displays the MEM playlist management system which will render a client interface for clients
 * or a guest interface for event guests with the access URL.
 *
 * @since 1.3
 *
 * @return string
 */
function mem_shortcode_playlist( $atts ) {

	global $mem_event;

	if ( isset( $_GET['memeventid'] ) ) {
		$_GET['guest_playlist'] = absint( $_GET['memeventid'] );
	}

	$visitor  = isset( $_GET['guest_playlist'] ) ? 'guest' : 'client';
	$output   = '';
	$event_id = '';

	if ( ! empty( $_GET['event_id'] ) ) {
		$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
	} else {
		$next_event = mem_get_clients_next_event( get_current_user_id() );

		if ( $next_event ) {
			$event_id = sanitize_text_field( $next_event[0]->ID );
		}
	}

	if ( ! isset( $event_id ) && ! isset( $_GET['guest_playlist'] ) ) {
		ob_start();
		mem_get_template_part( 'playlist', 'noevent' );
		$output .= mem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
	} else {

		$mem_event = 'client' === $visitor ? mem_get_event( $event_id ) : mem_get_event_by_playlist_code( sanitize_text_field( wp_unslash( $_GET['guest_playlist'] ) ) );

		ob_start();

		if ( 'client' === $visitor && ! is_user_logged_in() ) {
			$output .= mem_login_form( add_query_arg( 'event_id', $event_id, mem_get_formatted_url( mem_get_option( 'playlist_page' ) ) ) );
		} elseif ( $mem_event ) {
			mem_get_template_part( 'playlist', $visitor );
			$output .= mem_do_content_tags( ob_get_contents(), $mem_event->ID, $mem_event->client );
		} else {
			mem_get_template_part( 'playlist', 'noevent' );
			$output .= mem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
		}
	}

	ob_get_clean();

	// Reset global var.
	$mem_event = '';

	return apply_filters( 'mem_playlist_form', $output );

} // mem_shortcode_playlist
add_shortcode( 'mem-playlist', 'mem_shortcode_playlist' );

/**
 * MEM Quote Shortcode.
 *
 * Displays the online quotation to the client.
 *
 * @since 1.3
 * @param arr $atts Arguments passed with the shortcode.
 * @return string
 */
function mem_shortcode_quote( $atts ) {

	$atts = shortcode_atts(
		array( // These are our default values.
			'button_text' => sprintf( esc_html__( 'Book this %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		),
		$atts,
		'mem-quote'
	);

	$event_id = '';

	if ( ! empty( $_GET['event_id'] ) ) {
		$event_id = sanitize_text_field( wp_unslash( $_GET['event_id'] ) );
	} else {
		$next_event = mem_get_clients_next_event( get_current_user_id() );

		if ( $next_event ) {
			$event_id = sanitize_text_field( $next_event[0]->ID );
		}
	}

	if ( isset( $event_id ) && mem_event_exists( $event_id ) ) {

		if ( is_user_logged_in() ) {

			global $mem_event, $mem_quote_button_atts;

			$mem_quote_button_atts = $atts;

			$mem_event = new MEM_Event( $event_id );

			ob_start();

			if ( $mem_event ) {

				// Some verification.
				if ( get_current_user_id() !== $mem_event->client ) {
					mem_get_template_part( 'quote', 'noevent' );
				} else {
					mem_get_template_part( 'quote' );
				}

				$output = mem_do_content_tags( ob_get_contents(), $mem_event->ID, $mem_event->client );

			} else {
				mem_get_template_part( 'quote', 'noevent' );

				$output = mem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
			}

			ob_get_clean();

			// Reset global var.
			$mem_event = '';

			return $output;

		} else {
			echo mem_login_form( mem_get_current_page_url() );
		}
	} else {
		ob_start();
		mem_get_template_part( 'quote', 'noevent' );
		$output = mem_do_content_tags( ob_get_contents(), '', get_current_user_id() );
		ob_get_clean();
	}

} // mem_shortcode_quote
add_shortcode( 'mem-quote', 'mem_shortcode_quote' );

/**
 * MEM Availability Checker Shortcode.
 *
 * Displays the MEM Availability Checker form which allows clients to determine if you are
 * available on their chosen event date.
 *
 * @since 1.3
 *
 * @return string
 */
function mem_shortcode_availability( $atts ) {

	$atts = shortcode_atts(
		array( // These are our default values.
			'label'             => __( 'Select Date', 'mobile-events-manager' ) . ':',
			'label_class'       => 'mem-label',
			'field_class'       => '',
			'submit_text'       => __( 'Check Availability', 'mobile-events-manager' ),
			'submit_class'      => '',
			'please_wait_text'  => __( 'Please wait...', 'mobile-events-manager' ),
			'please_wait_class' => '',
			'display'           => 'horizontal',
		),
		$atts,
		'mem-availability'
	);

	$field_id = 'mem-availability-datepicker';

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

	mem_insert_datepicker(
		array(
			'class'    => '',
			'id'       => $field_id,
			'altfield' => 'availability_check_date',
			'mindate'  => '1',
		)
	);

	echo '<!-- ' . __( 'MEM Availability Checker', 'mobile-events-manager' ) . ' (' . MEM_VERSION_NUM . ') -->';
	echo '<form name="mem-availability-check" id="mem-availability-check" method="post">';
	wp_nonce_field( 'do_availability_check', 'mem_nonce', true, true );
	mem_action_field( 'do_availability_check' );
	echo '<input type="hidden" name="availability_check_date" id="availability_check_date" />';
	mem_get_template_part( 'availability', $atts['display'], true );
	echo '</form>';

	$output  = ob_get_clean();
	$output  = str_replace( $search, $replace, $output );
	$output  = mem_do_content_tags( $output );
	$output .= '<!-- ' . __( 'MEM Availability Checker', 'mobile-events-manager' ) . ' (' . MEM_VERSION_NUM . ') -->';

	return apply_filters( 'mem_availability_form', esc_html( $output ) );

} // mem_shortcode_availability
add_shortcode( 'mem-availability', 'mem_shortcode_availability' );

/**
 * Addons List Shortcode.
 *
 * @param arr     $atts Shortcode attributes. See $atts.
 * @param str|int $filter_value The value to which to filter $filter_by. Default false (all).
 * @param str     $list List type to display. li for bulleted. Default p.
 * @param bool    $cost Whether or not display the price. Default false.
 */
function mem_shortcode_addons_list( $atts ) {

	global $post;

	$atts = shortcode_atts(
		array( // These are our default values.
			'filter_by'    => false,
			'filter_value' => false,
			'list'         => 'p',
			'desc'         => false,
			'desc_length'  => mem_get_option( 'package_excerpt_length', 55 ),
			'cost'         => false,
			'addon_class'  => false,
			'cost_class'   => false,
			'desc_class'   => false,
		),
		$atts,
		'mem-addons'
	);

	ob_start();
	$output = '';

	if ( ! empty( $post ) && 'mem-package' === get_post_type( $post->ID ) ) {
		$package_addons = mem_get_package_addons( $post->ID );

		$addons = array();
		foreach ( $package_addons as $package ) {
			$addons[] = mem_get_addon( $package );
		}
	} elseif ( ! empty( $atts['filter_by'] ) && ! empty( $atts['filter_value'] ) && 'false' !== $atts['filter_by'] && 'false' !== $atts['filter_value'] ) {

		// Filter addons by user.
		if ( 'category' === $atts['filter_by'] ) {

			$addons = mem_get_addons_in_category( $atts['filter_value'] );

		} elseif ( 'package' === $atts['filter_by'] ) {
			if ( ! is_numeric( $atts['filter_value'] ) ) { // For backwards compatibility.
				$package = mem_get_package_by( 'slug', $atts['filter_value'] );
				if ( $package ) {
					$atts['filter_value'] = $package->ID;
				}
			}

			$package_addons = mem_get_package_addons( $atts['filter_value'] );

			$addons = array();
			foreach ( $package_addons as $package ) {
				$addons[] = mem_get_addon( $package );
			}
		} elseif ( 'user' === $atts['filter_by'] ) {
			$addons = mem_get_addons_by_employee( $atts['filter_value'] );
		}
	} else {
		$addons = mem_get_addons();
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

			$cost = mem_get_addon_price( $addon->ID );
			if ( ! empty( $atts['cost'] ) && 'false' !== $atts['cost'] && ! empty( $cost ) ) {

				if ( ! empty( $atts['cost_class'] ) && 'false' === $atts['cost_class'] ) {
					$output = '<span class="' . esc_html( $atts['cost_class'] ) . '">';
				}

				$output .= '&nbsp;&ndash;&nbsp;' . mem_currency_filter( mem_format_amount( $cost ) );

				if ( ! empty( $atts['cost_class'] ) && 'false' !== $atts['cost_class'] ) {
					$output = '</span>';
				}
			}

			$desc = mem_get_addon_excerpt( $addon->ID, $atts['desc_length'] );
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

	echo esc_html_e( apply_filters( 'mem_shortcode_addons_list', $output ) );

	return ob_get_clean();

} // mem_shortcode_addons_list
add_shortcode( 'mem-addons', 'mem_shortcode_addons_list' );

/**
 * MEM Login Shortcode.
 *
 * Displays a login form for the front end of the website.
 *
 * @since 1.3
 *
 * @return string
 */
function mem_shortcode_login( $atts ) {

	extract(
		shortcode_atts(
			array(
				'redirect' => '',
			),
			$atts,
			'mem-login'
		)
	);

	return mem_login_form( $redirect );

} // mem_shortcode_home
add_shortcode( 'mem-login', 'mem_shortcode_login' );
