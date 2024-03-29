<?php
/**
 * Package and Addon Posts
 *
 * @package TMEM
 * @subpackage Equipment
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the columns to be displayed for package posts
 *
 * @since 1.4
 * @param arr $columns Array of column names.
 * @return arr $columns Filtered array of column names
 */
function tmem_package_post_columns( $columns ) {

	$category_labels = tmem_get_taxonomy_labels( 'package-category' );

	$columns = array(
		'cb'               => '<input type="checkbox" />',
		'title'            => __( 'Package', 'mobile-events-manager' ),
		'items'            => __( 'Items', 'mobile-events-manager' ),
		'package_category' => $category_labels['column_name'],
		'availability'     => __( 'Availability', 'mobile-events-manager' ),
		/* translators: %s: Event Types */
		'event_types'      => sprintf( esc_html__( '%s Types', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		'employees'        => __( 'Employees', 'mobile-events-manager' ),
		'price'            => __( 'Price', 'mobile-events-manager' ),
		'usage'            => __( 'Usage', 'mobile-events-manager' ),
	);

	if ( ! tmem_employee_can( 'manage_packages' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // tmem_package_post_columns
add_filter( 'manage_tmem-package_posts_columns', 'tmem_package_post_columns' );

/**
 * Define which columns are sortable for package posts
 *
 * @since 1.4
 * @param arr $sortable_columns Array of package post sortable columns.
 * @return arr $sortable_columns Filtered Array of package post sortable columns
 */
function tmem_package_post_sortable_columns( $sortable_columns ) {
	$sortable_columns['price'] = 'price';

	return $sortable_columns;
} // tmem_package_post_sortable_columns
add_filter( 'manage_edit-tmem-package_sortable_columns', 'tmem_package_post_sortable_columns' );

/**
 * Define the data to be displayed in each of the custom columns for the Package post types
 *
 * @since 1.4
 * @param str $column_name The name of the column to display.
 * @param int $post_id The current post ID.
 */
function tmem_package_posts_custom_column( $column_name, $post_id ) {
	global $post;

	switch ( $column_name ) {
		// Items.
		case 'items':
			$items = tmem_get_package_addons( $post_id );

			if ( $items ) {
				$i = 0;
				foreach ( $items as $item ) {
					echo '<a href="' . wp_kses_post( admin_url( "post.php?post={$item}&action=edit" ) ) . '">' . esc_attr( tmem_get_addon_name( $item ) ) . '</a>';
					$i++;
					if ( $i < count( $items ) ) {
						echo '<br />';
					}
				}
			}

			break;

		// Category.
		case 'package_category':
			echo get_the_term_list( $post_id, 'package-category', '', ', ', '' );
			break;

		// Availability.
		case 'availability':
			$output = array();

			if ( ! tmem_package_is_restricted_by_date( $post_id ) ) {
				$output[] = __( 'Always', 'mobile-events-manager' );
			} else {
				$availability = tmem_get_package_months_available( $post_id );

				if ( ! $availability ) {
					$output[] = __( 'Always', 'mobile-events-manager' );
				} else {
					$i = 0;
					foreach ( $availability as $month ) {

						$output[] = tmem_month_num_to_name( $availability[ $i ] );
						$i++;
					}
				}
			}

			echo esc_attr( implode( ', ', $output ) );

			break;

		// Event Types.
		case 'event_types':
			$output      = array();
			$event_label = esc_html( tmem_get_label_singular() );
			$event_types = tmem_get_package_event_types( $post_id );

			if ( ! $event_types ) {
				$event_types = array( 'all' );
			}

			if ( in_array( 'all', $event_types ) ) {
				/* translators: %s: Event Types */
				$output[] = sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), $event_label );
			} else {
				foreach ( $event_types as $event_type ) {
					$term = get_term( $event_type, 'event-types' );

					if ( ! empty( $term ) ) {
						$output[] = $term->name;
					}
				}
			}

			echo esc_attr( implode( ', ', $output ) );

			break;

		// Employees.
		case 'employees':
			$employees = tmem_get_employees_with_package( $post_id );
			$output    = array();

			if ( in_array( 'all', $employees ) ) {
				$output[] .= __( 'All Employees', 'mobile-events-manager' );
			} else {
				foreach ( $employees as $employee ) {
					if ( 'all' === $employee ) {
						continue;
					}
					$output[] = '<a href="' . get_edit_user_link( $employee ) . '">' . tmem_get_employee_display_name( $employee ) . '</a>';
				}
			}
			echo esc_attr( implode( '<br />', $output ) );

			break;

		// Price.
		case 'price':
			if ( tmem_package_has_variable_prices( $post_id ) ) {

				$range = tmem_get_package_price_range( $post_id );

				echo esc_attr( tmem_currency_filter( tmem_format_amount( $range['low'] ) ) );
				echo ' &mdash; ';
				echo esc_attr( tmem_currency_filter( tmem_format_amount( $range['high'] ) ) );

			} else {
				echo esc_attr( tmem_currency_filter( tmem_format_amount( tmem_get_package_price( $post_id ) ) ) );
			}
			break;

		case 'usage':
			$count = tmem_count_events_with_package( $post_id );
			echo esc_attr( $count . ' ' . _n( tmem_get_label_singular(), tmem_get_label_plural(), $count, 'mobile-events-manager' ) );
			break;

	} // switch

} // tmem_package_posts_custom_column
add_action( 'manage_tmem-package_posts_custom_column', 'tmem_package_posts_custom_column', 10, 2 );

/**
 * Set the package post placeholder title.
 *
 * @since 1.4
 * @param str $title Current post placeholder title.
 * @return str $title Post placeholder title
 */
function tmem_package_set_post_title_placeholder( $title ) {

	$screen = get_current_screen();

	if ( 'tmem-package' === $screen->post_type ) {
		$title = __( 'Enter a name for this package', 'mobile-events-manager' );
	}

	return $title;

} // tmem_package_set_post_title_placeholder.
add_action( 'enter_title_here', 'tmem_package_set_post_title_placeholder' );

/**
 * Order package posts.
 *
 * @since 1.4
 * @param obj $query The WP_Query object.
 * @return void
 */
function tmem_package_post_order( $query ) {

	if ( ! is_admin() || 'tmem-package' !== $query->get( 'post_type' ) ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	$order   = $query->get( 'order' );

	switch ( $orderby ) {
		case 'ID':
			$query->set( 'orderby', 'ID' );
			$query->set( 'order', $order );
			break;

		case 'price':
			$query->set( 'meta_key', '_package_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
			break;
	}

} // tmem_package_post_order
add_action( 'pre_get_posts', 'tmem_package_post_order' );

/**
 * Hook into pre_get_posts and limit employees packages if their permissions are not full.
 *
 * @since 1.4
 * @param arr $query The WP_Query.
 * @return void
 */
function tmem_limit_results_to_employee_packages( $query ) {

	if ( ! is_admin() || 'tmem-package' !== $query->get( 'post_type' ) || tmem_employee_can( 'tmem_package_edit' ) ) {
		return;
	}

	global $user_ID;

	$query->set(
		'meta_query',
		array(
			array(
				'key'     => '_package_employees',
				'value'   => sprintf( ':"%s";', $user_ID ),
				'compare' => 'LIKE',
			),
		)
	);

} // tmem_limit_results_to_employee_packages
add_action( 'pre_get_posts', 'tmem_limit_results_to_employee_packages' );

/**
 * Save the meta data for the package
 *
 * @since 1.4
 * @param int $post_id The current event post ID.
 * @param obj $post The current event post object (WP_Post).
 *
 * @return void
 */
function tmem_save_package_post( $post_id, $post ) {

	if ( ! isset( $_POST['tmem_package_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmem_package_meta_box_nonce'], 'tmem-package' ) ) ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
		return;
	}

	// The default fields that get saved.
	$fields = tmem_packages_metabox_fields();

	foreach ( $fields as $field ) {

		if ( ! empty( $_POST[ $field ] ) ) {
			$array_fields = array( '_package_event_types', '_package_employees' );
			if ( in_array( $field, $array_fields ) && ! is_array( $_POST[ $field ] ) ) {
				$_POST[ $field ] = array( 'all' );
			}

			$new_value = apply_filters( 'tmem_package_metabox_save_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			update_post_meta( $post_id, $field, $new_value );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	do_action( 'tmem_save_package', $post_id, $post );

} // tmem_save_package_post
add_action( 'save_post_tmem-package', 'tmem_save_package_post', 10, 2 );

/**
 * Fires when a package is being deleted or trashed.
 *
 * @since 1.4
 * @param int $post_id The Package post ID.
 * @return void
 */
function tmem_deleting_package( $post_id ) {

	if ( 'tmem-package' !== get_post_type( $post_id ) ) {
		return;
	}

	do_action( 'tmem_delete_package', $post_id );

} // tmem_deleting_package
add_action( 'before_delete_post', 'tmem_deleting_package' );
add_action( 'wp_trash_post', 'tmem_deleting_package' );

/***********************************************************
 * Addons
 **********************************************************/

/**
 * Define the columns to be displayed for addon posts
 *
 * @since 1.4
 * @param arr $columns Array of column names.
 * @return arr $columns Filtered array of column names
 */
function tmem_addon_post_columns( $columns ) {

	$category_labels = tmem_get_taxonomy_labels( 'addon-category' );

	$columns = array(
		'cb'             => '<input type="checkbox" />',
		'title'          => __( 'Addon', 'mobile-events-manager' ),
		'addon_category' => $category_labels['column_name'],
		'availability'   => __( 'Availability', 'mobile-events-manager' ),
		/* translators: %s: Event Types */
		'event_types'    => sprintf( esc_html__( '%s Types', 'mobile-events-manager' ), esc_html( tmem_get_label_singular() ) ),
		'employees'      => __( 'Employees', 'mobile-events-manager' ),
		'price'          => __( 'Price', 'mobile-events-manager' ),
		'usage'          => __( 'Usage', 'mobile-events-manager' ),
	);

	if ( ! tmem_employee_can( 'manage_packages' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
	}

	return $columns;
} // tmem_addon_post_columns
add_filter( 'manage_tmem-addon_posts_columns', 'tmem_addon_post_columns' );

/**
 * Define which columns are sortable for addon posts
 *
 * @since 1.4
 * @param arr $sortable_columns Array of addon post sortable columns.
 * @return arr $sortable_columns Filtered Array of addon post sortable columns
 */
function tmem_addon_post_sortable_columns( $sortable_columns ) {
	$sortable_columns['price'] = 'price';

	return $sortable_columns;
} // tmem_addon_post_sortable_columns
add_filter( 'manage_edit-tmem-addon_sortable_columns', 'tmem_addon_post_sortable_columns' );

/**
 * Define the data to be displayed in each of the custom columns for the Addon post types
 *
 * @since 1.4
 * @param str $column_name The name of the column to display.
 * @param int $post_id The current post ID.
 */
function tmem_addon_posts_custom_column( $column_name, $post_id ) {
	global $post;

	switch ( $column_name ) {
		// Category.
		case 'addon_category':
			echo get_the_term_list( $post_id, 'addon-category', '', ', ', '' );
			break;

		// Availability.
		case 'availability':
			$output = array();

			if ( ! tmem_addon_is_restricted_by_date( $post_id ) ) {
				$output[] = __( 'Always', 'mobile-events-manager' );
			} else {
				$availability = tmem_get_addon_months_available( $post_id );

				if ( ! $availability ) {
					$output[] = __( 'Always', 'mobile-events-manager' );
				} else {
					$i = 0;
					foreach ( $availability as $month ) {

						$output[] = tmem_month_num_to_name( $availability[ $i ] );
						$i++;
					}
				}
			}

			echo esc_attr( implode( ', ', $output ) );

			break;

		// Event Types.
		case 'event_types':
			$output      = array();
			$event_label = esc_html( tmem_get_label_singular() );
			$event_types = tmem_get_addon_event_types( $post_id );

			if ( in_array( 'all', $event_types ) ) {
				/* translators: %s: Event Types */
				$output[] = sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), $event_label );
			} else {
				foreach ( $event_types as $event_type ) {
					$term = get_term( $event_type, 'event-types' );

					if ( ! empty( $term ) ) {
						$output[] = $term->name;
					}
				}
			}

			echo esc_attr( implode( ', ', $output ) );

			break;

		// Employees.
		case 'employees':
			$employees = tmem_get_employees_with_addon( $post_id );
			$output    = array();

			if ( in_array( 'all', $employees ) ) {
				$output[] = __( 'All Employees', 'mobile-events-manager' );
			} else {
				foreach ( $employees as $employee ) {
					if ( 'all' === $employee ) {
						continue;
					}
					$output[] = '<a href="' . get_edit_user_link( $employee ) . '">' . tmem_get_employee_display_name( $employee ) . '</a>';
				}
			}
			echo esc_attr( implode( '<br />', $output ) );

			break;

		// Price.
		case 'price':
			if ( tmem_addon_has_variable_prices( $post_id ) ) {

				$range = tmem_get_addon_price_range( $post_id );

				echo esc_attr( tmem_currency_filter( tmem_format_amount( $range['low'] ) ) );
				echo ' &mdash; ';
				echo esc_attr( tmem_currency_filter( tmem_format_amount( $range['high'] ) ) );

			} else {
				echo esc_attr( tmem_currency_filter( tmem_format_amount( tmem_get_addon_price( $post_id ) ) ) );
			}
			break;

		// Usage.
		case 'usage':
			$packages = tmem_count_packages_with_addon( $post_id );
			$events   = tmem_count_events_with_addon( $post_id );

			echo esc_attr( $packages . _n( ' Package', ' Packages', $packages, 'mobile-events-manager' ) . '<br />' );
			echo esc_attr( $events . ' ' . _n( esc_html( tmem_get_label_singular() ), esc_html( tmem_get_label_plural() ), $events, 'mobile-events-manager' ) );

			break;

	} // switch

} // tmem_addon_posts_custom_column
add_action( 'manage_tmem-addon_posts_custom_column', 'tmem_addon_posts_custom_column', 10, 2 );

/**
 * Set the addon post placeholder title.
 *
 * @since 1.4
 * @param str $title Current post placeholder title.
 * @return str $title Post placeholder title
 */
function tmem_addon_set_post_title_placeholder( $title ) {

	$screen = get_current_screen();

	if ( 'tmem-addon' === $screen->post_type ) {
		$title = __( 'Enter a name for this add-on', 'mobile-events-manager' );
	}

	return $title;

} // tmem_addon_set_post_title_placeholder.
add_action( 'enter_title_here', 'tmem_addon_set_post_title_placeholder' );

/**
 * Order addon posts.
 *
 * @since 1.4
 * @param obj $query The WP_Query object.
 */
function tmem_addon_post_order( $query ) {

	if ( ! is_admin() || 'tmem-addon' !== $query->get( 'post_type' ) ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	$order   = $query->get( 'order' );

	switch ( $orderby ) {
		case 'ID':
			$query->set( 'orderby', 'ID' );
			$query->set( 'order', $order );
			break;

		case 'price':
			$query->set( 'meta_key', '_addon_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
			break;
	}

} // tmem_addon_post_order.
add_action( 'pre_get_posts', 'tmem_addon_post_order' );

/**
 * Hook into pre_get_posts and limit employees addons if their permissions are not full.
 *
 * @since 1.4
 * @param arr $query The WP_Query.
 */
function tmem_limit_results_to_employee_addons( $query ) {

	if ( ! is_admin() || 'tmem-addon' !== $query->get( 'post_type' ) || tmem_employee_can( 'tmem_package_edit' ) ) {
		return;
	}

	global $user_ID;

	$query->set(
		'meta_query',
		array(
			array(
				'key'     => '_addon_employees',
				'value'   => sprintf( ':"%s";', $user_ID ),
				'compare' => 'LIKE',
			),
		)
	);

} // tmem_limit_results_to_employee_addons
add_action( 'pre_get_posts', 'tmem_limit_results_to_employee_addons' );

/**
 * Map the meta capabilities
 *
 * @since 1.3
 * @param arr $caps The users actual capabilities.
 * @param str $cap The capability name.
 * @param int $user_id The user ID.
 * @param arr $args Adds the context to the cap. Typically the object ID.
 */
function tmem_addon_map_meta_cap( $caps, $cap, $user_id, $args ) {

	// If editing, deleting, or reading a package or addon, get the post and post type object.
	if ( 'edit_tmem_package' === $cap || 'delete_tmem_package' === $cap || 'read_tmem_package' === $cap || 'publish_tmem_package' === $cap ) {

		$post = get_post( $args[0] );

		if ( empty( $post ) ) {
			return $caps;
		}

		$post_type = get_post_type_object( $post->post_type );

		// Set an empty array for the caps.
		$caps = array();

	}

	// If editing a package or an addon, assign the required capability.
	if ( 'read_tmem_package' === $cap ) {

		if ( in_array( $user_id, tmem_get_event_employees( $post->ID ) ) ) {
			$caps[] = $post_type->cap->edit_posts;
		} else {
			$caps[] = $post_type->cap->edit_others_posts;
		}
	} // If deleting a package or an addon, assign the required capability.
	elseif ( 'delete_tmem_package' === $cap ) {

		if ( in_array( $user_id, tmem_get_event_employees( $post->ID ) ) ) {
			$caps[] = $post_type->cap->delete_posts;
		} else {
			$caps[] = $post_type->cap->delete_others_posts;
		}
	}

	// If reading a private package or addon, assign the required capability.
	elseif ( 'read_tmem_package' === $cap ) {

		if ( 'private' !== $post->post_status ) {
			$caps[] = 'read';
		} elseif ( in_array( $user_id, tmem_get_event_employees( $post->ID ) ) ) {
			$caps[] = 'read';
		} else {
			$caps[] = $post_type->cap->read_private_posts;
		}
	}

	// Return the capabilities required by the user.
	return $caps;

} // tmem_addon_map_meta_cap
// add_filter( 'map_meta_cap', 'tmem_addon_map_meta_cap', 10, 4 );.

/**
 * Save the meta data for the addon
 *
 * @since 1.4
 * @param int $post_id The current event post ID.
 * @param obj $post The current event post object (WP_Post).
 *
 * @return void
 */
function tmem_save_addon_post( $post_id, $post ) {

	if ( ! isset( $_POST['tmem_addon_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tmem_addon_meta_box_nonce'], 'tmem-addon' ) ) ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
		return;
	}

	// The default fields that get saved.
	$fields = tmem_addons_metabox_fields();

	foreach ( $fields as $field ) {

		if ( ! empty( $_POST[ $field ] ) ) {
			if ( '_addon_employees' === $field && ! is_array( $_POST[ $field ] ) ) {
				$_POST[ $field ] = array( 'all' );
			}

			$new_value = apply_filters( 'tmem_addon_metabox_save_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			update_post_meta( $post_id, $field, $new_value );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	do_action( 'tmem_save_addon', $post_id, $post );

} // tmem_save_addon_post
add_action( 'save_post_tmem-addon', 'tmem_save_addon_post', 10, 2 );

/**
 * Fires when an addon is being deleted or trashed.
 *
 * @since 1.4
 * @param int $post_id The Addon post ID.
 * @return void
 */
function tmem_deleting_addon( $post_id ) {

	if ( 'tmem-addon' !== get_post_type( $post_id ) ) {
		return;
	}

	do_action( 'tmem_delete_addon', $post_id );

} // tmem_delete_addon
add_action( 'before_delete_post', 'tmem_deleting_addon' );
add_action( 'wp_trash_post', 'tmem_deleting_addon' );

/**
 * Customise the messages associated with managing addon posts
 *
 * @since 1.4
 * @param arr $messages The current messages.
 * @return arr $messages Filtered messages
 */
function tmem_addon_post_messages( $messages ) {

	global $post;

	if ( 'tmem-addon' !== $post->post_type ) {
		return $messages;
	}

	$url1 = '<a href="' . admin_url( 'edit.php?post_type=tmem-addon' ) . '">';
	$url2 = __( 'Add-on', 'mobile-events-manager' );
	$url3 = __( 'Add-ons', 'mobile-events-manager' );
	$url4 = '</a>';

	$messages['tmem-addon'] = array(
		0 => '', // Unused. Messages start at index 1.
		/* translators: %1: Addon URL %2: Addon %3: s %4: URL End */
		1 => sprintf( esc_html__( '%2$s updated. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		/* translators: %1: Addon URL %2: Addon %3: s %4: URL End */
		4 => sprintf( esc_html__( '%2$s updated. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		/* translators: %1: Addon URL %2: Addon %3: s %4: URL End */
		6 => sprintf( esc_html__( '%2$s created. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		/* translators: %1: Addon URL %2: Addon %3: s %4: URL End */
		7 => sprintf( esc_html__( '%2$s saved. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		/* translators: %1: Addon URL %2: Addon %3: s %4: URL End */
		8 => sprintf( esc_html__( '%2$s submitted. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
	);

	return apply_filters( 'tmem_addon_post_messages', $messages );

} // tmem_event_post_messages
add_filter( 'post_updated_messages', 'tmem_addon_post_messages' );
