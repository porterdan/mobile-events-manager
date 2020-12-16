<?php
/**
 * Manages Event posts admin screen and queries.
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the columns to be displayed for event posts
 *
 * @since 0.5
 * @param arr $columns Array of column names
 * @return arr $columns Filtered array of column names
 */
function mem_event_post_columns( $columns ) {

	$columns = array(
		'cb'           => '<input type="checkbox" />',
		'event_date'   => __( 'Date', 'mobile-events-manager' ),
		'event_id'     => sprintf( esc_html__( '%s ID', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'client'       => __( 'Client', 'mobile-events-manager' ),
		'employees'    => __( 'Employees', 'mobile-events-manager' ),
		'event_status' => __( 'Status', 'mobile-events-manager' ),
		'event_type'   => sprintf( esc_html__( '%s type', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ),
		'event_name'   => __( 'Name', 'mobile-events-manager' ),
		'value'        => __( 'Value', 'mobile-events-manager' ),
		'balance'      => __( 'Due', 'mobile-events-manager' ),
		'playlist'     => __( 'Playlist', 'mobile-events-manager' ),
		'journal'      => __( 'Journal', 'mobile-events-manager' ),
	);

	if ( ! mem_employee_can( 'manage_all_events' ) && isset( $columns['cb'] ) ) {
		unset( $columns['cb'] );
		unset( $columns['journal'] );
	}

	if ( ! mem_employee_can( 'edit_txns' ) ) {
		unset( $columns['value'] );
		unset( $columns['balance'] );
	}

	return $columns;
} // mem_event_post_columns
add_filter( 'manage_mem-event_posts_columns', 'mem_event_post_columns' );

/**
 * Define the event post columns hidden by default
 *
 * @since 1.4.7.3
 * @param arr       $hidden An array of columns hidden by default.
 * @param WP_Screen $screen WP_Screen object of the current screen.
 */
function mem_event_post_hidden_columns( $hidden, $screen ) {

	if ( 'edit-mem-event' == $screen->id ) {
		$hidden[] = 'event_name';
	}

	return $hidden;
} // mem_event_post_hidden_columns
add_filter( 'default_hidden_columns', 'mem_event_post_hidden_columns', 10, 2 );

/**
 * Define which columns are sortable for event posts
 *
 * @since 0.7
 * @param arr $sortable_columns Array of event post sortable columns
 * @return arr $sortable_columns Filtered Array of event post sortable columns
 */
function mem_event_post_sortable_columns( $sortable_columns ) {
	$sortable_columns['event_date'] = 'event_date';
	$sortable_columns['value']      = 'value';

	return $sortable_columns;
} // mem_event_post_sortable_columns
add_filter( 'manage_edit-mem-event_sortable_columns', 'mem_event_post_sortable_columns' );

/**
 * Define the data to be displayed in each of the custom columns for the Transaction post types
 *
 * @since 0.9
 * @param str $column_name The name of the column to display
 * @param int $post_id The current post ID
 * @return
 */
function mem_event_posts_custom_column( $column_name, $post_id ) {
	global $post;

	if ( mem_employee_can( 'edit_txns' ) && 'value' === $column_name ) {
		$value = mem_get_event_price( $post_id );
	}

	switch ( $column_name ) {
		// Event Date
		case 'event_date':
			if ( mem_employee_can( 'read_events' ) ) {
				echo '<strong><a href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">' . gmdate( 'd M Y', strtotime( get_post_meta( $post_id, '_mem_event_date', true ) ) ) . '</a>';
			} else {
				echo '<strong>' . gmdate( 'd M Y', strtotime( get_post_meta( $post_id, '_mem_event_date', true ) ) ) . '</strong>';
			}
			break;

		case 'event_id':
			echo '<strong><a href="' . admin_url( 'post.php?post=' . $post_id . '&action=edit' ) . '">' . mem_get_event_contract_id( $post_id ) . '</a>';
			break;

		// Client
		case 'client':
			$client = get_userdata( get_post_meta( $post->ID, '_mem_event_client', true ) );

			if ( ! empty( $client ) ) {
				if ( mem_employee_can( 'send_comms' ) ) {
					printf(
						'<a href="%s">%s</a>',
						add_query_arg(
							array(
								'recipient' => $client->ID,
								'event_id'  => $post_id,
							),
							admin_url( 'admin.php?page=mem-comms' )
						),
						esc_html( $client->display_name )
					);
				} else {
					echo esc_html( $client->display_name );
				}
			} else {
				_e( '<span class="mem-form-error">Not Assigned</span>', 'mobile-events-manager' );
			}
			break;

		// Employees
		case 'employees':
			global $wp_roles;

			$primary   = get_userdata( mem_get_event_primary_employee( $post->ID ) );
			$employees = mem_get_event_employees_data( $post->ID );

			if ( ! empty( $primary ) ) {

				if ( mem_employee_can( 'send_comms' ) ) {
					printf(
						'<a href="%s" title="%s">%s</a>',
						add_query_arg(
							array(
								'recipient' => $primary->ID,
								'event_id'  => $post_id,
							),
							admin_url( 'admin.php?page=mem-comms' )
						),
						mem_get_option( 'artist', __( 'DJ', 'mobile-events-manager' ) ),
						$primary->display_name
					);
				} else {
					echo '<a title="' . mem_get_option( 'artist', __( 'DJ', 'mobile-events-manager' ) ) . '">' . $primary->display_name . '</a>';
				}
			} else {
				_e( '<span class="mem-form-error">Not Assigned</span>', 'mobile-events-manager' );
			}

			if ( ! empty( $employees ) ) {
				echo '<br />';
				$i = 1;

				foreach ( $employees as $employee ) {

					echo '<em>';

					if ( mem_employee_can( 'send_comms' ) ) {
						printf(
							'<a href="%s" title="%s">%s</a>',
							add_query_arg(
								array(
									'recipient' => $employee['id'],
									'event_id'  => $post_id,
								),
								admin_url( 'admin.php?page=mem-comms' )
							),
							translate_user_role( $wp_roles->roles[ $employee['role'] ]['name'] ),
							mem_get_employee_display_name( $employee['id'] )
						);
					} else {
						echo '<a title="' . translate_user_role( $wp_roles->roles[ $employee['role'] ]['name'] ) . '">' . mem_get_employee_display_name( $employee['id'] ) . '</a>';
					}

					echo '</em>';

					if ( count( $employees ) !== $i ) {
						echo '<br />';
					}
				}
			}

			break;

		// Status
		case 'event_status':
			echo get_post_status_object( $post->post_status )->label;
			break;

		// Event Type
		case 'event_type':
			$event_types = get_the_terms( $post_id, 'event-types' );
			if ( is_array( $event_types ) ) {
				foreach ( $event_types as $key => $event_type ) {
					$event_types[ $key ] = $event_type->name;
				}
				echo implode( '<br/>', $event_types );
			}
			break;

		// Event Name
		case 'event_name':
			echo esc_attr( mem_get_event_name( $post_id ) );
			break;

		// Value
		case 'value':
			if ( mem_employee_can( 'edit_txns' ) ) {
				if ( ! empty( $value ) && '0.00' !== $value ) {

					echo mem_currency_filter( mem_format_amount( $value ) );
					echo '<br />';

				} else {
					echo '<span class="mem-form-error">' . mem_currency_filter( mem_format_amount( '0.00' ) ) . '</span>';
				}
			} else {
				echo '&mdash;';
			}
			break;

		// Balance
		case 'balance':
			if ( mem_employee_can( 'edit_txns' ) ) {

				echo mem_currency_filter( mem_format_amount( mem_get_event_balance( $post_id ) ) );

				echo '<br />';

				$deposit_status = mem_get_event_deposit_status( $post_id );

				if ( 'Paid' == mem_get_event_deposit_status( $post_id ) ) {
					printf(
						__( '<i title="%1$s %2$s paid" class="fa fa-check-square-o" aria-hidden="true">', 'mobile-events-manager' ),
						mem_currency_filter( mem_format_amount( mem_get_event_deposit( $post_id ) ) ),
						mem_get_deposit_label()
					);
				}
			} else {
				echo '&mdash;';
			}
			break;

		// Playlist
		case 'playlist':
			if ( mem_employee_can( 'read_events' ) ) {
				$total = mem_count_playlist_entries( $post_id );

				echo '<a href="' . mem_get_admin_page( 'playlists' ) . $post_id . '">' . $total . ' ' .
					_n( 'Song', 'Songs', $total, 'mobile-events-manager' ) . '</a>' . "\r\n";
			} else {
				echo '&mdash;';
			}
			break;

		// Journal
		case 'journal':
			if ( mem_employee_can( 'read_events_all' ) ) {
				$total = wp_count_comments( $post_id )->approved;
				echo '<a href="' . admin_url( '/edit-comments.php?p=' . $post_id ) . '">' .
					$total . ' ' .
					_n( 'Entry', 'Entries', $total, 'mobile-events-manager' ) .
					'</a>' . "\r\n";
			} else {
				echo '&mdash;';
			}
			break;

	} // switch

} // mem_event_posts_custom_column
add_action( 'manage_mem-event_posts_custom_column', 'mem_event_posts_custom_column', 10, 2 );

/**
 * Remove the edit bulk action from the event posts list.
 *
 * @since 1.0
 * @param arr $actions Array of actions
 * @return arr $actions Filtered Array of actions
 */
function mem_event_bulk_action_list( $actions ) {
	unset( $actions['edit'] );

	return $actions;
} // mem_event_bulk_action_list
add_filter( 'bulk_actions-edit-mem-event', 'mem_event_bulk_action_list' );

/**
 * Adds custom bulk actions.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_event_add_reject_bulk_actions() {

	global $post;

	$current_status = isset( $_GET['post_status'] ) ? sanitize_option( wp_unslash( $_GET['post_status'] ) ) : false;

	if ( 'mem-unattended' !== $current_status || 'mem-event' != get_post_type() ) {
		return;
	}

	?>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('<option>').val('reject_enquiry').text('<?php esc_html_e( 'Reject', 'mobile-events-manager' ); ?>').appendTo("select[name='action']");
		jQuery('<option>').val('reject_enquiry').text('<?php esc_html_e( 'Reject', 'mobile-events-manager' ); ?>').appendTo("select[name='action2']");
	});
	</script>
	<?php

} // mem_event_add_custom_bulk_actions
add_action( 'admin_footer-edit.php', 'mem_event_add_reject_bulk_actions' );

/**
 * Process reject enquiry bulk action requests.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_event_instant_reject() {

	if ( ! isset( $_REQUEST['post_status'] ) || 'mem-unattended' !== $_REQUEST['post_status'] || isset( $_REQUEST['mem-message'] ) ) {
		return;
	}

	if ( isset( $_REQUEST['action'] ) ) {
		$action = sanitize_option( wp_unslash( $_REQUEST['action'] ) );
	} elseif ( isset( $_REQUEST['action2'] ) ) {
		$action = sanitize_option( wp_unslash( $_REQUEST['action2'] ) );
	} else {
		$action = '';
	}

	if ( empty( $action ) || 'reject_enquiry' !== $action || empty( $_REQUEST['post'] ) ) {
		return;
	}

	if ( ! mem_employee_can( 'manage_all_events' ) ) {
		return;
	}

	$args    = array( 'reject_reason' => __( 'No reason specified', 'mobile-events-manager' ) );
	$message = 'unattended_enquiries_rejected_success';

	$i = 0;

	foreach ( sanitize_option( wp_unslash( $_REQUEST['post'] ) ) as $event_id ) {
		if ( ! mem_update_event_status( $event_id, 'mem-rejected', get_post_status( $event_id ), $args ) ) {
			$message = 'unattended_enquiries_rejected_failed';
		} else {
			$i++;
		}
	}

	$url = admin_url( 'edit.php?post_status=mem-unattended&post_type=mem-event&paged=1' );

	wp_safe_redirect(
		add_query_arg(
			array(
				'mem-message' => $message,
				'mem-count'   => $i,
			),
			$url
		)
	);

	die();

} // mem_event_instant_reject
add_action( 'load-edit.php', 'mem_event_instant_reject' );

/**
 * Add the filter dropdowns to the event post list.
 *
 * @since 1.0
 * @param
 * @return void
 */
function mem_event_post_filter_list() {

	if ( ! isset( $_GET['post_type'] ) || 'mem-event' !== $_GET['post_type'] ) {
		return;
	}

	mem_event_date_filter_dropdown();
	mem_event_type_filter_dropdown();

	if ( mem_is_employer() && mem_employee_can( 'manage_employees' ) ) {
		mem_event_employee_filter_dropdown();
	}

	if ( mem_employee_can( 'list_all_clients' ) ) {
		mem_event_client_filter_dropdown();
	}

} // mem_event_post_filter_list
add_action( 'restrict_manage_posts', 'mem_event_post_filter_list' );

/**
 * Display the filter drop down list to enable user to select and filter event by month/year.
 *
 * @since 1.0
 * @param
 * @return void
 */
function mem_event_date_filter_dropdown() {
	global $wpdb, $wp_locale;

	$month_query = 'SELECT DISTINCT YEAR( meta_value ) as year, MONTH( meta_value ) as month
		FROM `' . $wpdb->postmeta . "` WHERE `meta_key` = '_mem_event_date'";

	$months = $wpdb->get_results( $month_query );

	$month_count = count( $months );

	if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
		return;
	}

	$m = isset( $_GET['mem_filter_date'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['mem_filter_date'] ) ) : 0;

	?>
	<label for="filter-by-date" class="screen-reader-text">Filter by Date</label>
	<select name="mem_filter_date" id="filter-by-date">
		<option value="0"><?php esc_html_e( 'All Dates', 'mobile-events-manager' ); ?></option>
	<?php
	foreach ( $months as $arc_row ) {
		if ( 0 == $arc_row->year ) {
			continue;
		}

		$month = zeroise( $arc_row->month, 2 );
		$year  = $arc_row->year;

		printf(
			"<option %s value='%s'>%s</option>\r\n",
			selected( $m, $year . $month, false ),
			esc_attr( $arc_row->year . $month ),
			/* translators: 1: month name, 2: 4-digit year */
			sprintf(
				__( '%1$s %2$d', 'mobile-events-manager' ),
				$wp_locale->get_month( $month ),
				$year
			)
		);
	}
	?>
	</select>
	<?php
} // mem_event_date_filter_dropdown

/**
 * Display the filter drop down list to enable user to select and filter event by type.
 *
 * @since 1.0
 * @param
 * @return
 */
function mem_event_type_filter_dropdown() {

	$event_types = get_categories(
		array(
			'type'       => 'mem-event',
			'taxonomy'   => 'event-types',
			'pad_counts' => false,
			'hide_empty' => true,
			'orderby'    => 'name',
		)
	);

	$current = isset( $_GET['mem_filter_type'] ) ? sanitize_option( wp_unslash( $_GET['mem_filter_type'] ) ) : '';

	?>

	<?php if ( $event_types ) : ?>
		<select name="mem_filter_type">
			<option value=""><?php printf( esc_html__( 'All %s Types', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></option>
			<?php foreach ( $event_types as $event_type ) : ?>
				<option value="<?php echo $event_type->term_id; ?>"<?php selected( $event_type->term_id, $current ); ?>><?php echo esc_html( $event_type->name ); ?> (<?php echo esc_html( $event_type->category_count ); ?>)</option>
			<?php endforeach; ?>
		</select>

		<?php
	endif;

} // mem_event_type_filter_dropdown

/**
 * Display the filter drop down list to enable user to select and filter event by Employee.
 *
 * @since 1.0
 * @param
 * @return str Outputs the dropdown for the employee filter
 */
function mem_event_employee_filter_dropdown() {

	$employees      = mem_get_employees();
	$employee_count = count( $employees );

	if ( ! $employee_count || 1 == $employee_count ) {
		return;
	}

	?>
	<label for="filter-by-employee" class="screen-reader-text"><?php esc_html_e( 'Filter by Employee', 'mobile-events-manager' ); ?></label>

	<?php
	mem_employee_dropdown(
		array(
			'name'            => 'mem_filter_employee',
			'id'              => 'filter-by-employee',
			'selected'        => isset( $_GET['mem_filter_employee'] ) ? sanitize_option( wp_unslash( $_GET['mem_filter_employee'] ) ) : 0,
			'first_entry'     => __( 'All Employees', 'mobile-events-manager' ),
			'first_entry_val' => 0,
			'group'           => true,
			'structure'       => true,
			'echo'            => true,
		)
	);

} // mem_event_employee_filter_dropdown

/**
 * Display the filter drop down list to enable user to select and filter event by Client.
 *
 * @since 1.0
 * @param arr
 * @return arr
 */
function mem_event_client_filter_dropdown() {

	$roles    = array( 'client', 'inactive_client' );
	$employee = ! mem_employee_can( 'read_events_all' ) ? get_current_user_id() : false;

	$all_clients = mem_get_clients( $roles, $employee );

	if ( ! $all_clients || 1 == count( $all_clients ) ) {
		return;
	}

	$selected = isset( $_GET['mem_filter_client'] ) ? (int) $_GET['mem_filter_client'] : 0;

	foreach ( $all_clients as $_client ) {
		$client_events = mem_get_client_events( $_client->ID );

		if ( $client_events ) {
			$clients[ $_client->ID ] = $_client->display_name;
		}
	}

	if ( empty( $clients ) ) {
		return;
	}

	?>
	<label for="filter-by-client" class="screen-reader-text">Filter by <?php esc_html_e( 'Client', 'mobile-events-manager' ); ?></label>
	<select name="mem_filter_client" id="mem_filter_client-by-dj">
		<option value="0"<?php selected( $selected, 0, false ); ?>><?php esc_html_e( "All Client's", 'mobile-events-manager' ); ?></option>
	<?php
	foreach ( $clients as $ID => $display_name ) {

		if ( empty( $display_name ) ) {
			continue;
		}

		printf(
			"<option %s value='%s'>%s</option>\n",
			selected( $selected, $ID, false ),
			$ID,
			$display_name
		);
	}
	?>
	</select>
	<?php
} // mem_event_client_filter_dropdown

/**
 * Customise the view filter counts
 *
 * @since 1.0
 * @param arr $views Array of views
 * @return arr $views Filtered Array of views
 */
function mem_event_view_filters( $views ) {

	$active_only = mem_get_option( 'show_active_only' );

	if ( 'mem-event' != get_post_type() || ! $active_only ) {
		return $views;
	}

	$args = array();
	if ( ! empty( $_GET['mem_filter_employee'] ) || ! mem_employee_can( 'read_events_all' ) ) {
		$args['employee'] = get_current_user_id();
	}

	$all_statuses      = mem_all_event_status_keys();
	$inactive_statuses = mem_inactive_event_status_keys();
	$num_posts         = mem_count_events( $args );
	$count             = 0;

	if ( ! empty( $num_posts ) ) {
		foreach ( $num_posts as $status => $status_count ) {
			if ( ! empty( $num_posts->$status ) && in_array( $status, $all_statuses ) ) {
				$views[ $status ] = preg_replace( '/\(.+\)/U', '(' . number_format_i18n( $num_posts->$status ) . ')', $views[ $status ] );
			}

			if ( ! in_array( $status, $inactive_statuses ) ) {
				$count += $status_count;
			}
		}
	}

	$views['all'] = preg_replace( '/\(.+\)/U', '(' . number_format_i18n( $count ) . ')', $views['all'] );

	if ( $active_only ) {
		$search       = __( 'All', 'mobile-events-manager' );
		$replace      = sprintf( esc_html__( 'Active %s', 'mobile-events-manager' ), esc_html( mem_get_label_plural() ) );
		$views['all'] = str_replace( $search, $replace, $views['all'] );
	}

	foreach ( $views as $status => $link ) {
		if ( 'all' !== $status && ! in_array( $status, $all_statuses ) ) {
			unset( $views[ $status ] );
		}
	}

	return apply_filters( 'mem_event_views', $views );
} // mem_event_view_filters
add_filter( 'views_edit-mem-event', 'mem_event_view_filters' );

/**
 * Customise the post row actions on the event edit screen.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function mem_event_post_row_actions( $actions, $post ) {

	if ( 'mem-event' !== $post->post_type ) {
		return $actions;
	}

	if ( isset( $actions['trash'] ) ) {
		unset( $actions['trash'] );
	}
	if ( isset( $actions['view'] ) ) {
		unset( $actions['view'] );
	}
	if ( isset( $actions['edit'] ) && 'mem-unattended' == $post->post_status ) {
		unset( $actions['edit'] );
	}
	if ( isset( $actions['inline hide-if-no-js'] ) ) {
		unset( $actions['inline hide-if-no-js'] );
	}

	// Unattended events have additional actions to allow one-click responses
	$url = remove_query_arg( array( 'mem-action', 'event_id' ) );

	if ( 'mem-unattended' == $post->post_status ) {

		// Quote for event
		$actions['quote'] = sprintf(
			__( '<a href="%s">Quote</a>', 'mobile-events-manager' ),
			admin_url( 'post.php?post=' . $post->ID . '&action=edit&mem_action=respond' )
		);

		// Check availability
		$actions['availability'] = sprintf(
			__( '<a href="%s">Availability</a>', 'mobile-events-manager' ),
			add_query_arg(
				array(
					'mem-action' => 'get_event_availability',
					'event_id'    => $post->ID,
				),
				wp_nonce_url( $url, 'get_event_availability', 'mem_nonce' )
			)
		);

		// Respond Unavailable
		$actions['respond_unavailable'] = sprintf(
			__( '<span class="trash"><a href="%s">Unavailable</a></span>', 'mobile-events-manager' ),
			add_query_arg(
				array(
					'recipient'   => mem_get_client_id( $post->ID ),
					'template'    => mem_get_option( 'unavailable' ),
					'event_id'    => $post->ID,
					'mem-action' => 'respond_unavailable',
				),
				admin_url( 'admin.php?page=mem-comms' )
			)
		);

	}

	return $actions;
} // mem_event_post_row_actions
add_filter( 'post_row_actions', 'mem_event_post_row_actions', 10, 2 );

/**
 * Output the event post title hidden field.
 *
 * @since 1.0
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function mem_event_set_post_title( $post ) {

	if ( 'mem-event' != $post->post_type ) {
		return;
	}

	?>
	<input type="hidden" name="post_title" value="<?php echo mem_get_event_contract_id( $post->ID ); ?>" id="title" />
	<?php

} // mem_event_set_post_title
add_action( 'edit_form_after_title', 'mem_event_set_post_title' );

/**
 * Output the event name field.
 *
 * @since 1.5
 * @param arr $actions Current post row actions
 * @param obj $post The WP_Post post object
 */
function mem_output_event_name_field( $post ) {

	if ( 'mem-event' != $post->post_type ) {
		return;
	}

	$value       = wp_kses_post( mem_get_event_name( $post->ID ) );
	$placeholder = sprintf( 'Optional: Display name in %s', 'mobile-events-manager', mem_get_option( 'app_name', __( 'Client Zone', 'mobile-events-manager' ) ) );

	?>
	<div id="titlediv">
		<div id="titlewrap">
			<input type="text" name="_mem_event_name" id="_mem_event_name" autocomplete="off" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>" />
		</div>
	</div>
	<?php

} // mem_output_event_name_field
add_action( 'edit_form_after_title', 'mem_output_event_name_field' );

/**
 * Rename the Publish and Update post buttons for events
 *
 * @since 1.3
 * @param str $translation The current button text translation
 * @param str $text The text translation for the button
 * @return str $translation The filtererd text translation
 */
function mem_event_rename_publish_button( $translation, $text ) {

	global $post;

	if ( ! isset( $post ) || 'mem-event' != $post->post_type ) {
		return $translation;
	}

	$event_statuses = mem_all_event_status();

	if ( 'Publish' === $text && isset( $event_statuses[ $post->post_status ] ) ) {
		return __( 'Update Event', 'mobile-events-manager' );
	} elseif ( 'Publish' === $text ) {
		return __( 'Create Event', 'mobile-events-manager' );
	} elseif ( 'Publish' === $text ) {
		return __( 'Update Event', 'mobile-events-manager' );
	} else {
		return $translation;
	}

} // mem_event_rename_publish_button
add_filter( 'gettext', 'mem_event_rename_publish_button', 10, 2 );

/**
 * Highlight unattended events rows within event post listings
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_event_highlight_unattended_event_rows() {

	global $post;

	if ( ! isset( $post ) || 'mem-event' != $post->post_type ) {
		return;
	}

	// Allow the colour to be filtered
	$row_colour = apply_filters( 'mem_unattended_event_row_colour', '#FFEBE8' );

	?>
	<style>
	/* Color by post Status */
	.status-mem-unattended	{
		background: <?php echo $row_colour; ?> !important;
	}
	</style>
	<?php

} // mem_event_highlight_unattended_event_rows
add_action( 'admin_footer', 'mem_event_highlight_unattended_event_rows' );

/**
 * Remove the default date filter from the edit post screen since we store event dates in a meta key.
 *
 * @since 1.3
 * @param
 * @param
 */
function mem_event_remove_date_filter() {

	if ( ! isset( $_GET['post_type'] ) || 'mem-event' !== $_GET['post_type'] ) {
		return;
	}

	add_filter( 'months_dropdown_results', '__return_empty_array' );

} // mem_event_remove_date_filter
add_action( 'admin_head', 'mem_event_remove_date_filter' );

/**
 * Order posts.
 *
 * @since 1.3
 * @param obj $query The WP_Query object
 * @return void
 */
function mem_event_post_order( $query ) {

	if ( ! is_admin() || 'mem-event' != $query->get( 'post_type' ) ) {
		return;
	}

	$orderby = '' == $query->get( 'orderby' ) ? mem_get_option( 'events_order_by', 'event_date' ) : $query->get( 'orderby' );
	$order   = '' == $query->get( 'order' ) ? mem_get_option( 'events_order', 'event_date' ) : $query->get( 'order' );

	switch ( $orderby ) {
		case 'ID':
			$query->set( 'orderby', 'ID' );
			$query->set( 'order', $order );
			break;

		case 'post_date':
			$query->set( 'orderby', 'post_date' );
			$query->set( 'order', $order );
			break;

		case 'event_date':
		default:
			$query->set( 'meta_key', '_mem_event_date' );
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'order', $order );
			break;

		case 'title':
			$query->set( 'orderby', 'ID' );
			$query->set( 'order', $order );
			break;

		case 'value':
			$query->set( 'meta_key', '_mem_event_cost' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', $order );
			break;
	}

} // mem_event_post_order
add_action( 'pre_get_posts', 'mem_event_post_order' );

/**
 * Hook into pre_get_posts and limit employees events if their permissions are not full.
 *
 * @since 1.0
 * @param arr $query The WP_Query
 * @return void
 */
function mem_limit_results_to_employee_events( $query ) {

	if ( ! is_admin() || 'mem-event' != $query->get( 'post_type' ) || mem_employee_can( 'read_events_all' ) ) {
		return;
	}

	global $user_ID;

	$query->set(
		'meta_query',
		array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_mem_event_dj',
					'value'   => $user_ID,
					'compare' => '==',
				),
				array(
					'key'     => '_mem_event_employees',
					'value'   => sprintf( ':"%s";', $user_ID ),
					'compare' => 'LIKE',
				),
			),
		)
	);

} // mem_limit_results_to_employee_events
add_action( 'pre_get_posts', 'mem_limit_results_to_employee_events' );

/**
 * Hide inactive events from the 'all' events list.
 *
 * @since 1.0
 * @param obj $query The WP_Query.
 * @return void
 */
function mem_hide_inactive_events( $query ) {

	if ( ! is_admin() || ! $query->is_main_query() || 'mem-event' != $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! mem_get_option( 'show_active_only', false ) ) {
		return;
	}

	if ( isset( $_GET['post_status'] ) && 'all' != $_GET['post_status'] ) {
		return;
	}

	$active_statuses   = mem_all_event_status_keys();
	$inactive_statuses = mem_inactive_event_status_keys();

	foreach ( $inactive_statuses as $inactive_status ) {
		if ( ( $key = array_search( $inactive_status, $active_statuses ) ) !== false ) {
			unset( $active_statuses[ $key ] );
		}
	}

	$active_events = mem_get_events(
		array(
			'post_status' => $active_statuses,
			'fields'      => 'ids',
			'number'      => -1,
		)
	);

	if ( $active_events ) {
		$query->set( 'post__in', $active_events );
	}

} // mem_hide_inactive_events
add_action( 'pre_get_posts', 'mem_hide_inactive_events' );

/**
 * Adjust the query when the events are filtered.
 *
 * @since 1.3
 * @param arr $query The WP_Query
 * @return void
 */
function mem_event_post_filtered( $query ) {

	global $pagenow;

	$post_type   = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
	$post_status = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';

	if ( 'edit.php' != $pagenow || 'mem-event' != $post_type || ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['filter_action'] ) ) {
		return;
	}

	// Filter by selected date
	if ( ! empty( $_GET['mem_filter_date'] ) ) {

		// Create the date start and end range
		$start = gmdate( 'Y-m-d', strtotime( substr( sanitize_option( wp_unslash( $_GET['mem_filter_date'], 0, 4 ) ) ) . '-' . substr( sanitize_option( wp_unslash( $_GET['mem_filter_date'], -2 ) ) ) . '-01' ) );
		$end   = gmdate( 'Y-m-t', strtotime( $start ) );

		$query->query_vars['meta_query'] = array(
			array(
				'key'     => '_mem_event_date',
				'value'   => array( $start, $end ),
				'compare' => 'BETWEEN',
			),
		);

	}

	// Filter by event type
	if ( ! empty( $_GET['mem_filter_type'] ) ) {

		$type = isset( $_GET['mem_filter_type'] ) ? absint( $_GET['mem_filter_type'] ) : 0;

		if ( 0 !== $type ) {
			$query->set(
				'tax_query',
				array(
					array(
						'taxonomy' => 'event-types',
						'field'    => 'term_id',
						'terms'    => $type,
					),
				)
			);
		}
	}

	// Filter by selected employee
	if ( ! empty( $_GET['mem_filter_employee'] ) ) {

		$query->query_vars['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'   => '_mem_event_dj',
				'value' => sanitize_option( wp_unslash( $_GET['mem_filter_employee'] ) ),
			),
			array(
				'key'     => '_mem_event_employees',
				'value'   => sprintf( sanitize_option( wp_unslash( ':"%s";', $_GET['mem_filter_employee'] ) ) ),
				'compare' => 'LIKE',
			),
		);

	}

	// Filter by selected client
	if ( ! empty( $_GET['mem_filter_client'] ) ) {

		$query->query_vars['meta_query'] = array(
			array(
				'key'   => '_mem_event_client',
				'value' => absint( $_GET['mem_filter_client'] ),
			),
		);

	}

	// Filter by selected venue
	if ( ! empty( $_GET['mem_filter_venue'] ) ) {

		$query->query_vars['meta_query'] = array(
			array(
				'key'   => '_mem_event_venue_id',
				'value' => absint( $_GET['mem_filter_venue'] ),
			),
		);

	}

	if ( ! empty( $post_status ) ) {
		$query->set( 'post_status', $post_status );
	}

} // mem_event_post_filtered
add_filter( 'parse_query', 'mem_event_post_filtered' );

/**
 * Customise the event post query during a search so that clients and employees are included in results.
 *
 * @since 1.0
 * @param arr $query The WP_Query
 * @return void
 */
function mem_event_post_search( $query ) {
	global $pagenow;

	if ( ! is_admin() || 'mem-event' != $query->get( 'post_type' ) || ! $query->is_search() || 'edit.php' != $pagenow ) {
		return;
	}

	// If searching it's only useful if we include clients and employees
	$users = new WP_User_Query(
		array(
			'search'         => sanitize_option( wp_unslash( $_GET['s'] ) ),
			'search_columns' => array(
				'user_login',
				'user_email',
				'user_nicename',
				'display_name',
			),
		)
	); // WP_User_Query

	$user_results = $users->get_results();

	// Loop through WP_User_Query search looking for events where user is client or employee
	if ( ! empty( $user_results ) ) {

		foreach ( $user_results as $user ) {

			$results = get_posts(
				array(
					'post_type'      => 'mem-event',
					'post_status'    => 'any',
					'posts_per_page' => -1,
					'meta_query'     => array(
						'relation' => 'OR',
						array(
							'key'   => '_mem_event_dj',
							'value' => $user->ID,
							'type'  => 'NUMERIC',
						),
						array(
							'key'   => '_mem_event_client',
							'value' => $user->ID,
							'type'  => 'NUMERIC',
						),
						array(
							'key'     => '_mem_event_employees',
							'value'   => sprintf( ':"%s";', $user->ID ),
							'compare' => 'LIKE',
						),
					),
				)
			); // get_posts

			if ( ! empty( $results ) ) {

				foreach ( $results as $result ) {

					$events[] = $result->ID;

				}
			}
		} // foreach( $users as $user )

		if ( ! empty( $events ) ) {

			$query->set( 'post__in', $events );
			$query->set( 'post_status', array( 'mem-unattended', 'mem-enquiry', 'mem-contract', 'mem-approved', 'mem-failed', 'mem-rejected', 'mem-completed' ) );

		}
	} // if( !empty( $users ) )

} // mem_event_post_search
add_action( 'pre_get_posts', 'mem_event_post_search' );

/**
 * Map the meta capabilities
 *
 * @since 1.3
 * @param arr $caps The users actual capabilities
 * @param str $cap The capability name
 * @param int $user_id The user ID
 * @param arr $args Adds the context to the cap. Typically the object ID.
 */
function mem_event_map_meta_cap( $caps, $cap, $user_id, $args ) {

	// If editing, deleting, or reading an event, get the post and post type object.
	if ( 'edit_mem_event' == $cap || 'delete_mem_event' == $cap || 'read_mem_event' == $cap || 'publish_mem_event' == $cap ) {

		$post = get_post( $args[0] );

		if ( empty( $post ) ) {
			return $caps;
		}

		$post_type = get_post_type_object( $post->post_type );

		// Set an empty array for the caps.
		$caps = array();

	}

	// If editing a event, assign the required capability. */
	if ( 'edit_mem_event' == $cap ) {

		if ( in_array( $user_id, mem_get_event_employees( $post->ID ) ) ) {
			$caps[] = $post_type->cap->edit_posts;
		} else {
			$caps[] = $post_type->cap->edit_others_posts;
		}
	}

	// If deleting a event, assign the required capability.
	elseif ( 'delete_mem_event' == $cap ) {

		if ( in_array( $user_id, mem_get_event_employees( $post->ID ) ) ) {
			$caps[] = $post_type->cap->delete_posts;
		} else {
			$caps[] = $post_type->cap->delete_others_posts;
		}
	}

	// If reading a private event, assign the required capability.
	elseif ( 'read_mem_event' == $cap ) {

		if ( 'private' != $post->post_status ) {
			$caps[] = 'read';
		} elseif ( in_array( $user_id, mem_get_event_employees( $post->ID ) ) ) {
			$caps[] = 'read';
		} else {
			$caps[] = $post_type->cap->read_private_posts;
		}
	}

	// Return the capabilities required by the user.
	return $caps;

} // mem_event_map_meta_cap
add_filter( 'map_meta_cap', 'mem_event_map_meta_cap', 10, 4 );

/**
 * Save the meta data for the event
 *
 * @since 0.7
 * @param int  $post_id The current event post ID.
 * @param obj  $post The current event post object (WP_Post).
 * @param bool $update Whether this is an existing post being updated or not.
 *
 * @return void
 */
function mem_save_event_post( $post_id, $post, $update ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( 'trash' === $post->post_status ) {
		return;
	}

	if ( empty( $update ) ) {
		return;
	}

	// Permission Check
	if ( ! mem_employee_can( 'manage_events' ) ) {
		MEM()->debug->log_it( sprintf( 'PERMISSION ERROR: User %s is not allowed to edit events', get_current_user_id() ) );

		return;
	}

	// Remove the save post action to avoid loops.
	remove_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	// Fire our pre-save hook
	do_action( 'mem_pre_event_save', $post_id, $post, $update );

	$debug[] = 'Starting Event Save';

	// Get current meta data for the post so we can track changes within the journal.
	$current_meta = get_post_meta( $post_id );

	/**
	 * Get the Client ID and store it in the event data array.
	 * If a client has been selected from the dropdown, we simply use that ID.
	 * If adding a new client, call the method and use the returned user ID.
	 */
	$event_data['_mem_event_client'] = 'add_new' !== $_POST['client_name'] ? sanitize_text_field( wp_unslash( $_POST['client_name'] ) ) : mem_add_client();

	/**
	 * For new events we fire the 'mem_add_new_event' action
	 */
	if ( empty( $update ) ) {
		do_action( 'mem_create_new_event', $post );
	}

	/**
	 * If the client is flagged to have their password reset, set the flag.
	 * The flag will be checked and processed during the content tag filtering process.
	 */
	if ( ! empty( $_POST['mem_reset_pw'] ) ) {

		$debug[] = sprintf( 'Client %s flagged for password reset', $event_data['_mem_event_client'] );

		update_user_meta( $event_data['_mem_event_client'], 'mem_pass_action', true );
	}

	/**
	* Determine the Venue ID if an existing venue was selected.
	* Otherwise, determine if we're using the client's address or adding a manual venue address
	*/
	if ( 'manual' !== $_POST['venue_id'] && 'client' !== $_POST['venue_id'] ) {
		$event_data['_mem_event_venue_id'] = sanitize_text_field( wp_unslash( $_POST['venue_id'] ) );
	} elseif ( ! empty( $_POST['_mem_event_venue_id'] ) && 'client' === $_POST['_mem_event_venue_id'] ) {
		$event_data['_mem_event_venue_id'] = 'client';
	} else {
		$event_data['_mem_event_venue_id'] = 'manual';
	}

	/**
	 * If the option was selected to save the venue, prepare the post and post meta data
	 * for the venue.
	 */
	if ( 'manual' === $_POST['venue_id'] && ! empty( $_POST['save_venue'] ) ) {

		foreach ( $_POST as $venue_key => $venue_value ) {

			if ( substr( $venue_key, 0, 6 ) == 'venue_' ) {

				$venue_meta[ $venue_key ] = $venue_value;

				if ( 'venue_postcode' === $venue_key && ! empty( $venue_value ) ) {
					$venue_meta[ $venue_key ] = strtoupper( $venue_value );
				} elseif ( 'venue_email' === $venue_key && ! empty( $venue_value ) ) {
					$venue_meta[ $venue_key ] = sanitize_email( $venue_value );
				} else {
					$venue_meta[ $venue_key ] = sanitize_text_field( ucwords( $venue_value ) );
				}
			}
		}

		// Create the new venue
		$event_data['_mem_event_venue_id'] = mem_add_venue( sanitize_text_field( wp_unslash( $_POST['venue_name'] ) ), $venue_meta );

	}

	// The venue is set to manual or client for this event so store the values in event post meta data.
	else {
		// Manual venue address entry
		if ( 'client' !== $_POST['venue_id'] ) {

			$event_data['_mem_event_venue_name']     = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_name'] ) ) );
			$event_data['_mem_event_venue_contact']  = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_contact'] ) ) );
			$event_data['_mem_event_venue_phone']    = sanitize_text_field( wp_unslash( $_POST['venue_phone'] ) );
			$event_data['_mem_event_venue_email']    = strtolower( sanitize_email( wp_unslash( $_POST['venue_email'] ) ) );
			$event_data['_mem_event_venue_address1'] = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_address1'] ) ) );
			$event_data['_mem_event_venue_address2'] = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_address2'] ) ) );
			$event_data['_mem_event_venue_town']     = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_town'] ) ) );
			$event_data['_mem_event_venue_county']   = ucwords( sanitize_text_field( wp_unslash( $_POST['venue_county'] ) ) );
			$event_data['_mem_event_venue_postcode'] = strtoupper( sanitize_text_field( wp_unslash( $_POST['venue_postcode'] ) ) );

		} else { // Using clients address

			$client_data = get_userdata( $event_data['_mem_event_client'] );

			$event_data['_mem_event_venue_name'] = __( 'Client Address', 'mobile-events-manager' );

			$event_data['_mem_event_venue_contact'] = sprintf(
				'%s %s',
				! empty( $client_data->first_name ) ? sanitize_text_field( $client_data->first_name ) : '',
				! empty( $client_data->last_name ) ? sanitize_text_field( $client_data->last_name ) : ''
			);

			$event_data['_mem_event_venue_phone']    = ! empty( $client_data->phone1 ) ? $client_data->phone1 : '';
			$event_data['_mem_event_venue_email']    = ! empty( $client_data->user_email ) ? $client_data->user_email : '';
			$event_data['_mem_event_venue_address1'] = ! empty( $client_data->address1 ) ? $client_data->address1 : '';
			$event_data['_mem_event_venue_address2'] = ! empty( $client_data->address2 ) ? $client_data->address2 : '';
			$event_data['_mem_event_venue_town']     = ! empty( $client_data->town ) ? $client_data->town : '';
			$event_data['_mem_event_venue_county']   = ! empty( $client_data->county ) ? $client_data->county : '';
			$event_data['_mem_event_venue_postcode'] = ! empty( $client_data->postcode ) ? $client_data->postcode : '';

		}
	}

	/**
	 * Travel data
	 */
	$travel_fields = mem_get_event_travel_fields();

	foreach ( $travel_fields as $travel_field ) {
		$field = 'travel_' . $travel_field;

		$travel_data[ $travel_field ] = ! empty( $_POST[ $field ] ) ? sanitize_option( wp_unslash( $_POST[ $field ] ) ) : '';

		if ( 'cost' == $travel_field && ! empty( $_POST[ $field ] ) ) {
			$travel_data[ $travel_field ] = mem_sanitize_amount( sanitize_option( wp_unslash( $_POST[ $field ] ) ) );
		}
	}

	$event_data['_mem_event_travel_data'] = $travel_data;

	/**
	 * Prepare the remaining event meta data.
	 */
	$event_data['_mem_event_last_updated_by'] = get_current_user_id();
	if ( ! get_post_meta( $post_id, '_mem_event_tasks', true ) ) {
		$event_data['_mem_event_tasks'] = array();
	}

	/**
	 * Event name.
	 * If no name is defined, use the event type.
	 * Allow filtering of the event name with the `mem_event_name` filter.
	 */
	if ( empty( $_POST['_mem_event_name'] ) ) {
		$_POST['_mem_event_name'] = get_term( sanitize_text_field( wp_unslash( $_POST['mem_event_type'] ) ), 'event-types' )->name;
	}

	$_POST['_mem_event_name'] = apply_filters( 'mem_event_name', sanitize_text_field( wp_unslash( $_POST['_mem_event_name'] ), $post_id ) );

	// Generate the playlist reference for guest access
	if ( empty( $update ) || empty( $current_meta['_mem_event_playlist_access'][0] ) ) {
		$event_data['_mem_event_playlist_access'] = mem_generate_playlist_guest_code();
	}

	// Set whether or not the playlist is enabled for the event
	$event_data['_mem_event_playlist'] = ! empty( $_POST['enable_playlist'] ) ? sanitize_text_field( wp_unslash( $_POST['enable_playlist'] ) ) : 'N';

	/**
	 * All the remaining custom meta fields are prefixed with '_mem_event_'.
	 * Loop through all $_POST data and put all event meta fields into the $event_data array
	 */
	foreach ( $_POST as $key => $value ) {

		if ( substr( $key, 0, 12 ) == '_mem_event_' ) {
			$cost_keys = array(
				'_mem_event_dj_wage',
				'_mem_event_package_cost',
				'_mem_event_addons_cost',
				'_mem_event_travel_cost',
				'_mem_event_additional_cost',
				'_mem_event_discount',
				'_mem_event_deposit',
				'_mem_event_cost',
			);
			if ( in_array( $key, $cost_keys ) ) {
				$value = mem_sanitize_amount( $value );
			}

			$event_data[ $key ] = $value;

		}
	}

	/**
	 * We store all times in H:i:s but the user may prefer a different format so we
	 * determine their time format setting and adjust to H:i:s for saving.
	 */
	if ( mem_get_option( 'time_format', 'H:i' ) == 'H:i' ) { // 24 Hr

		$event_data['_mem_event_start']        = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['event_start_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['event_start_min'] ) ) ) );
		$event_data['_mem_event_finish']       = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['event_finish_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['event_finish_min'] ) ) ) );
		$event_data['_mem_event_djsetup_time'] = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['dj_setup_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['dj_setup_min'] ) ) ) );
	} else { // 12 hr
		$event_data['_mem_event_start']        = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['event_start_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['event_start_min'] ) ) . sanitize_text_field( wp_unslash( $_POST['event_start_period'] ) ) ) );
		$event_data['_mem_event_finish']       = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['event_finish_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['event_finish_min'] ) ) . sanitize_text_field( wp_unslash( $_POST['event_finish_period'] ) ) ) );
		$event_data['_mem_event_djsetup_time'] = gmdate( 'H:i:s', strtotime( sanitize_text_field( wp_unslash( $_POST['dj_setup_hr'] ) ) . ':' . sanitize_text_field( wp_unslash( $_POST['dj_setup_min'] ) ) . sanitize_text_field( wp_unslash( $_POST['dj_setup_period'] ) ) ) );
	}

	if ( empty( $_POST['_mem_event_djsetup'] ) ) {
		$event_data['_mem_event_djsetup'] = sanitize_text_field( wp_unslash( $_POST['_mem_event_date'] ) );
	}

	/**
	 * Set the event end date.
	 * If a value is set from the field, use it otherwise determine fom start/finish time
	 * If the finish time is less than the start time, assume following day.
	 */
	if ( empty( $event_data['_mem_event_end_date'] ) ) {
		if ( gmdate( 'H', strtotime( sanitize_text_field( wp_unslash( $event_data['_mem_event_finish'] ) ) ) ) > gmdate( 'H', strtotime( sanitize_text_field( wp_unslash( $event_data['_mem_event_start'] ) ) ) ) ) {
			$event_data['_mem_event_end_date'] = sanitize_text_field( wp_unslash( $_POST['_mem_event_date'] ) );
		} else { // End date is following day
			$event_data['_mem_event_end_date'] = gmdate( 'Y-m-d', strtotime( '+1 day', strtotime( sanitize_text_field( wp_unslash( $_POST['_mem_event_date'] ) ) ) ) );
		}
	}

	/**
	 * Determine the state of the Deposit & Balance payments.
	 */
	$event_data['_mem_event_deposit_status'] = ! empty( $_POST['deposit_paid'] ) ? sanitize_text_field( wp_unslash( $_POST['deposit_paid'] ) ) : 'Due';
	$event_data['_mem_event_balance_status'] = ! empty( $_POST['balance_paid'] ) ? sanitize_text_field( wp_unslash( $_POST['balance_paid'] ) ) : 'Due';

	$deposit_payment = ( 'Paid' === $event_data['_mem_event_deposit_status'] && 'Paid' !== $current_meta['_mem_event_deposit_status'][0] ) ? true : false;

	$balance_payment = ( 'Paid' === $event_data['_mem_event_balance_status'] && 'Paid' !== $current_meta['_mem_event_balance_status'][0] ) ? true : false;

	// Add-Ons
	if ( mem_packages_enabled() ) {
		$event_data['_mem_event_addons'] = ! empty( $_POST['event_addons'] ) ? sanitize_text_field( wp_unslash( $_POST['event_addons'] ) ) : '';
	}

	// Assign the event type
	$existing_event_type = wp_get_object_terms( $post_id, 'event-types' );

	mem_set_event_type( $post_id, (int) $_POST['mem_event_type'] );

	// Assign the enquiry source
	mem_set_enquiry_source( $post_id, (int) $_POST['mem_enquiry_source'] );

	/**
	 * Update the event post meta data
	 */
	$debug[] = 'Beginning Meta Updates';

	mem_update_event_meta( $post_id, $event_data );

	$debug[] = 'Meta Updates Completed';

	if ( true === $deposit_payment || true === $balance_payment ) {

		if ( true === $balance_payment ) {
			unset( $event_data['_mem_event_balance_status'] );
			unset( $event_data['_mem_event_deposit_status'] );
			mem_mark_event_balance_paid( $post_id );
		} else {
			unset( $event_data['_mem_event_deposit_status'] );
			mem_mark_event_deposit_paid( $post_id );
		}
	}

	// Set the event status & initiate tasks based on the status
	if ( $_POST['original_post_status'] != $_POST['mem_event_status'] ) {

		mem_update_event_status(
			$post_id,
			sanitize_text_field( wp_unslash( $_POST['mem_event_status'] ) ),
			sanitize_text_field( wp_unslash( $_POST['original_post_status'] ) ),
			array(
				'client_notices' => empty( $_POST['mem_block_emails'] ) ? true : false,
				'email_template' => ! empty( $_POST['mem_email_template'] ) ? sanitize_text_field( wp_unslash( $_POST['mem_email_template'] ) ) : false,
				'quote_template' => ! empty( $_POST['mem_online_quote'] ) ? sanitize_text_field( wp_unslash( $_POST['mem_online_quote'] ) ) : false,
			)
		);

	} else { // Event status is un-changed so just log the changes to the journal

		mem_add_journal(
			array(
				'user_id'         => get_current_user_id(),
				'event_id'        => $post_id,
				'comment_content' => sprintf(
					'%s %s via Admin',
					esc_html( mem_get_label_singular() ),
					empty( $update ) ? 'created' : 'updated'
				),
			),
			array(
				'type'       => 'update-event',
				'visibility' => '2',
			)
		);

	}

	// Fire the save event hook
	do_action( 'mem_save_event', $post, sanitize_text_field( wp_unslash( $_POST['mem_event_status'] ) ) );

	// Fire our post save hook
	do_action( 'mem_after_event_save', $post_id, $post, $update );

	// Re-add the save post action to avoid loops
	add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

	$debug[] = sprintf( 'Completed Event Save for event %s', $post_id );

	if ( ! empty( $debug ) && MEM_DEBUG == true ) {

		$true = true;

		foreach ( $debug as $log ) {
			MEM()->debug->log_it( $log, $true );
			$true = false;
		}
	}

} // mem_save_event_post
add_action( 'save_post_mem-event', 'mem_save_event_post', 10, 3 );

/**
 * Customise the messages associated with managing event posts
 *
 * @since 1.3
 * @param arr $messages The current messages
 * @return arr $messages Filtered messages
 */
function mem_event_post_messages( $messages ) {

	global $post;

	if ( 'mem-event' != $post->post_type ) {
		return $messages;
	}

	$url1 = '<a href="' . admin_url( 'edit.php?post_type=mem-event' ) . '">';
	$url2 = esc_html( mem_get_label_singular() );
	$url3 = esc_html( mem_get_label_plural() );
	$url4 = '</a>';

	$messages['mem-event'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( esc_html__( '%2$s updated. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		4 => sprintf( esc_html__( '%2$s updated. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		6 => sprintf( esc_html__( '%2$s created. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		7 => sprintf( esc_html__( '%2$s saved. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
		8 => sprintf( esc_html__( '%2$s submitted. %1$s%3$s List%4$s.', 'mobile-events-manager' ), $url1, $url2, $url3, $url4 ),
	);

	return apply_filters( 'mem_event_post_messages', $messages );

} // mem_event_post_messages
add_filter( 'post_updated_messages', 'mem_event_post_messages' );
