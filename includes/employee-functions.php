<?php
/**
 * Contains all employee related functions
 *
 * @package MEM
 * @subpackage Users/Employees
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether or not we're running in a multi employee environment.
 *
 * @since 1.3
 * @param
 * @return bool True if multi employee, otherwise false
 */
function mem_is_employer() {
	return mem_get_option( 'employer', false );
} // mem_is_employer

/**
 * Whether or not the employee has the priviledge.
 *
 * @since 1.3
 * @param str $role The role to check.
 * @param int $user_id The user ID of the employee.
 * @return bool True if multi employee, otherwise false
 */
function mem_employee_can( $role, $user_id = '' ) {
	return MEM()->permissions->employee_can( $role, $user_id );
} // mem_employee_can

/**
 * Whether or not the user is an employee.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return bool True if an employee, otherwise false
 */
function mem_is_employee( $user_id = '' ) {

	$user_id = ! empty( $user_id ) ? $user_id : get_current_user_id();

	return user_can( $user_id, 'mem_employee' );
} // mem_is_employee

/**
 * Whether or not the user is an MEM Admin.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return bool True if an admin, otherwise false
 */
function mem_is_admin( $user_id = '' ) {

	$user_id = ! empty( $user_id ) ? $user_id : get_current_user_id();

	return user_can( $user_id, 'manage_mem' );
} // mem_is_admin

/**
 * Display a dropdown select list with all employees. The label must be handled seperately.
 *
 * @param arr $args Settings for the dropdown See $defaults.
 * 'role' (str|arr) Optional: Only display employees with the given role. Default empty (all).
 * 'name' (str) Optional: The name of the input. Defaults to '_mem_employees'
 * 'id' (str) Optional: ID for the field (uses name if not present)
 * 'class' (str) Optional: Class of the input field
 * 'selected' (str) Optional: Initially selected option
 * 'first_entry' (str) Optional: First entry to be displayed (default none)
 * 'first_entry_val' (str) Optional: First entry value. Only valid if first_entry is set
 * 'multiple' (bool) Optional: Whether multiple options can be selected
 * 'group' (bool) Optional: True to group employees by role
 * 'structure' (bool) Optional: True outputs the <select> tags, false just the <options>
 * 'exclude' (int|arr) Optional: Employee ID's to exclude
 * 'echo' (bool) Optional: Echo the HTML output (default) or false to return as $output
 *
 * @return str $output The HTML output for the dropdown list
 */
function mem_employee_dropdown( $args = '' ) {
	global $wp_roles;

	// Define the default args for the dropdown.
	$defaults = array(
		'role'            => '',
		'name'            => '_mem_employees',
		'id'              => '', // Uses name if not set.
		'class'           => '',
		'selected'        => '',
		'first_entry'     => '',
		'first_entry_val' => '0',
		'multiple'        => false,
		'group'           => false,
		'structure'       => true,
		'exclude'         => false,
		'echo'            => true,
	);

	// Merge default args with those passed to function.
	$args = wp_parse_args( $args, $defaults );

	$args['id'] = ! empty( $args['id'] ) ? $args['id'] : $args['name'];

	if ( ! empty( $args['exclude'] ) && ! is_array( $args['exclude'] ) ) {
		$args['exclude'] = array( $args['exclude'] );
	}

	// We'll store the output here.
	$output = '';

	// Start the structure.
	if ( ! empty( $args['structure'] ) ) {
		$output .= '<select name="' . $args['name'];
		if ( ! empty( $args['multiple'] ) ) {
			$output .= '[]';
		}

		$output .= '"	id="' . $args['id'] . '"';

		if ( ! empty( $args['class'] ) ) {
			$output .= ' class="' . $args['class'] . '"';
		}

		if ( ! empty( $args['multiple'] ) ) {
			$output .= ' multiple="multiple"';
		}

		$output .= '>' . "\r\n";
	}

	$employees = mem_get_employees( $args['role'] );

	if ( empty( $employees ) ) {
		$output .= '<option value="">' . __( 'No employees found', 'mobile-events-manager' ) . '</option>' . "\r\n";
	} else {
		if ( ! empty( $args['first_entry'] ) ) {
			$output .= '<option value="' . $args['first_entry_val'] . '">';
			$output .= $args['first_entry'] . '</option>' . "\r\n";

		}
		$results       = new stdClass();
		$results->role = array();
		foreach ( $employees as $employee ) {
			if ( 'administrator' === $employee->roles[0] && ! empty( $employee->roles[1] ) ) {
				$employee->roles[0] = $employee->roles[1];
			} else {
				$employee->roles[0] = 'dj';
			}

			if ( ! empty( $args['exclude'] ) && in_array( $employee->ID, $args['exclude'] ) ) {
				continue;
			}

			$results->role[ $employee->roles[0] ][] = $employee;
		}
		// Loop through the roles and employees to create the output.
		foreach ( $results->role as $role => $userobj ) {
			if ( ! empty( $args['group'] ) ) {
				$output .= '<optgroup label="' . translate_user_role( $wp_roles->roles[ $role ]['name'] ) . '">' . "\r\n";
			}

			foreach ( $userobj as $user ) {
				$output .= '<option value="' . $user->ID . '"';

				if ( ! empty( $args['selected'] ) && $user->ID === $args['selected'] ) {
					$output .= ' selected="selected"';
				}

				$output .= '>' . $user->display_name . '</option>' . "\r\n";
			}

			if ( ! empty( $args['group'] ) ) {
				$output .= '</optgroup>' . "\r\n";
			}
		}
	}

	// End the structure.
	if ( true === $args['structure'] ) {
		$output .= '</select>' . "\r\n";
	}

	if ( ! empty( $args['echo'] ) ) {
		echo $output;

	} else {
		return $output;
	}
} // mem_employee_dropdown

/**
 * Adds a new employee and assigns the role
 *
 * @param arr $post_data
 * 'first_name' Required: The first name of the employee.
 * 'last_name' Required: The last name of the employee.
 * 'user_email' Required: The email address of the employee.
 * 'employee_role' Required: The role that the employee should be assigned.
 *
 * @return void
 */
function mem_add_employee( $post_data ) {

	if ( empty( $post_data['first_name'] ) || empty( $post_data['last_name'] ) || empty( $post_data['user_email'] ) || empty( $post_data['employee_role'] ) ) {
		return false;
	}

	// We don't need to execute the hooks for user saves.
	remove_action( 'user_register', array( 'MEM_Users', 'save_custom_user_fields' ), 10, 1 );
	remove_action( 'personal_options_update', array( 'MEM_Users', 'save_custom_user_fields' ) );
	remove_action( 'edit_user_profile_update', array( 'MEM_Users', 'save_custom_user_fields' ) );

	// Default employee settings.
	$userdata = array(
		'user_email'           => $post_data['user_email'],
		'user_login'           => $post_data['user_email'],
		'user_pass'            => wp_generate_password( mem_get_option( 'pass_length' ) ),
		'first_name'           => ucfirst( $post_data['first_name'] ),
		'last_name'            => ucfirst( $post_data['last_name'] ),
		'display_name'         => ucfirst( $post_data['first_name'] ) . ' ' . ucfirst( $post_data['last_name'] ),
		'role'                 => $post_data['employee_role'],
		'show_admin_bar_front' => false,
	);

	/**
	 * Insert the new employee into the DB.
	 * Fire a hook on the way to allow filtering of the $default_userdata array.
	 */
	$userdata = apply_filters( 'mem_new_employee_data', $userdata );

	$user_id = wp_insert_user( $userdata );

	// Re-add our custom user save hooks.
	add_action( 'user_register', array( 'MEM_Users', 'save_custom_user_fields' ), 10, 1 );
	add_action( 'personal_options_update', array( 'MEM_Users', 'save_custom_user_fields' ) );
	add_action( 'edit_user_profile_update', array( 'MEM_Users', 'save_custom_user_fields' ) );

	// Success.
	if ( ! is_wp_error( $user_id ) ) {
		if ( MEM_DEBUG === true ) {
			MEM()->debug->log_it(
				'Adding employee ' . ucfirst( $post_data['first_name'] ) . ' ' . ucfirst( $post_data['last_name'] ) . ' with user ID ' . $user_id,
				true
			);
		}

		mem_send_employee_welcome_email( $user_id, $userdata );

		return true;
	} else {
		if ( MEM_DEBUG !== true ) {
			MEM()->debug->log_it( 'ERROR: Unable to add employee. ' . $user_id->get_error_message(), true );
		}

		return false;
	}
} // mem_add_employee

/**
 * Send a welcome email to a new employee.
 *
 * @since 1.3
 * @param int $user_id The new user ID.
 * @param arr $userdata Array of new user data.
 * @return void
 */
function mem_send_employee_welcome_email( $user_id, $userdata ) {

	global $wp_roles;

	$subject = sprintf( esc_html__( 'Your Employee Details from %s', 'mobile-events-manager' ), mem_get_option( 'company_name' ) );
	$subject = apply_filters( 'mem_new_employee_subject', $subject, $user_id, $userdata );

	$message = '<p>' . sprintf( esc_html__( 'Hello %s,', 'mobile-events-manager' ), $userdata['first_name'] ) . '</p>' . "\r\n" .

				'<p>' . sprintf(
					__( 'Your user account on the <a href="%1$s">%2$s website</a> is now ready for use.', 'mobile-events-manager' ),
					get_bloginfo( 'url' ),
					mem_get_option( 'company_name' )
				) . '</p>' . "\r\n" .

				'<hr />' . "\r\n" .

				'<p>' . sprintf( esc_html__( '<strong>Username</strong>: %s', 'mobile-events-manager' ), $userdata['user_login'] ) .
						'<br />' . "\r\n" .
						sprintf( esc_html__( '<strong>Password</strong>: %s', 'mobile-events-manager' ), $userdata['user_pass'] ) .
						'<br />' . "\r\n" .
						sprintf( esc_html__( '<strong>Employee Role</strong>: %s', 'mobile-events-manager' ), translate_user_role( $wp_roles->roles[ $userdata['role'] ]['name'] ) ) .
						'<br />' . "\r\n" .
						sprintf( esc_html__( '<strong>Login URL</strong>: <a href="%1$s">%1$s</a>', 'mobile-events-manager' ), admin_url() ) .
				'</p>' . "\r\n" .

				'<p>' . __( 'Thanks', 'mobile-events-manager' ) .
						'<br />' . "\r\n" .
						mem_get_option( 'company_name' ) .
				'</p>';

	$message = apply_filters( 'mem_new_employee_message', $message, $user_id, $userdata );

	$email_args = apply_filters(
		'mem_new_employee_email',
		array(
			'to_email' => $userdata['user_email'],
			'subject'  => $subject,
			'track'    => false,
			'message'  => $message,
		)
	);

	mem_send_email_content( $email_args );

} // mem_send_employee_welcome_email

/**
 * Retrieve a list of all employees
 *
 * @param str|arr $roles Optional. The roles for which we want to retrieve the employees from.
 * str $orderby Optional: The field by which to order. Default display_name
 * str $order Optional: ASC (default) | Desc
 *
 * @return $arr $employees or false if no employees for the specified roles.
 */
function mem_get_employees( $roles = '', $orderby = 'display_name', $order = 'ASC' ) {
	// We'll work with an array of roles.
	if ( ! empty( $roles ) && ! is_array( $roles ) ) {
		$roles = array( $roles );
	}

	// Define the default query.
	if ( empty( $roles ) ) {
		$roles = mem_get_roles();
	}

	// This array will store our employees.
	$employees = array();

	// Create and execute the WP_User_Query for each role.
	foreach ( $roles as $role_id => $role_name ) {
		$args = array(
			'role'    => is_numeric( $role_id ) ? $role_name : $role_id,
			'orderby' => $orderby,
			'order'   => $order,
		);

		// Execute the query.
		$employee_query = new WP_User_Query( $args );

		// Merge the results into our $employees array.
		$results = $employee_query->get_results();

		$employees = array_merge( $employees, $results );
		$employees = array_unique( $employees, SORT_REGULAR );
	}

	return $employees;
} // mem_get_employees

/**
 * Returns a count of employees.
 *
 * @since 1.4
 * @return int Employee count.
 */
function mem_employee_count() {

	$mem_roles = mem_get_roles();
	$roles      = array();

	foreach ( $mem_roles as $role_id => $role_name ) {
		$roles[] = $role_id;
	}

	$args = array(
		'role__in'    => $roles,
		'count_total' => true,
	);

	$employees = new WP_User_Query( $args );

	return $employees->get_total();

} // mem_employee_count

/**
 * Retrieve the primary event employee.
 *
 * @since 1.3
 * @param int $event_id The event for which we want the employee.
 * @return int|bool User ID of the primary employee, or false if not set.
 */
function mem_get_event_primary_employee( $event_id ) {
	return mem_get_event_primary_employee_id( $event_id );
} // mem_get_event_primary_employee

/**
 * Retrieve all event employees.
 *
 * Does not return the primary employee.
 *
 * @since 1.3
 * @param int $event_id The event for which we want employees.
 * @return arr|bool Array of event employees or false if none.
 */
function mem_get_event_employees( $event_id ) {
	return get_post_meta( $event_id, '_mem_event_employees', true );
} // mem_get_event_employees

/**
 * Retrieve all event employees data.
 *
 * Does not return the primary employees data.
 *
 * @since 1.3
 * @param int $event_id The event for which we want employees data.
 * @return arr|bool Array of event employees data or false if none.
 */
function mem_get_event_employees_data( $event_id ) {
	return get_post_meta( $event_id, '_mem_event_employees_data', true );
} // mem_get_event_employees_data

/**
 * Retrieve an employees first name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The first name of the employee.
 */
function mem_get_employee_firstname( $user_id ) {
	$first_name = '';
	$employee   = get_userdata( $user_id );

	if ( $employee && ! empty( $employee->first_name ) ) {
		$first_name = ucwords( $employee->first_name );
	}

	return apply_filters( 'mem_employee_firstname', $first_name, $user_id );
} // mem_get_employee_firstname

/**
 * Retrieve an employees last name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The last name of the employee.
 */
function mem_get_employee_lastname( $user_id ) {
	$last_name = '';
	$employee  = get_userdata( $user_id );

	if ( $employee && ! empty( $employee->last_name ) ) {
		$last_name = ucwords( $employee->last_name );
	}

	return apply_filters( 'mem_employee_lastname', $last_name, $user_id );
} // mem_get_employee_lastname

/**
 * Retrieve an employees display name.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The display name of the employee.
 */
function mem_get_employee_display_name( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	$display_name = '';
	$employee     = get_userdata( $user_id );

	if ( $employee && ! empty( $employee->display_name ) ) {
		$display_name = ucwords( $employee->display_name );
	}

	return apply_filters( 'mem_employee_display_name', $display_name, $user_id );
} // mem_get_employee_display_name

/**
 * Retrieve an employees phone number.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The phone number of the employee.
 */
function mem_get_employee_phone( $user_id ) {
	$phone    = '';
	$employee = get_userdata( $user_id );

	if ( $employee && ! empty( $employee->phone1 ) ) {
		$phone = $employee->phone1;
	}

	return apply_filters( 'mem_employee_phone', $phone, $user_id );
} // mem_get_employee_phone

/**
 * Retrieve an employees alternative phone number.
 *
 * @since 1.3.8.4
 * @param int $user_id The ID of the user to check.
 * @return str The alternative phone number of the employee.
 */
function mem_get_employee_alt_phone( $user_id ) {
	$alt_phone = get_user_meta( $user_id, 'phone2', true );

	return apply_filters( 'mem_employee_alt_phone', $alt_phone, $user_id );
} // mem_get_employee_alt_phone

/**
 * Retrieve an employees post code.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The display name of the employee.
 */
function mem_get_employee_post_code( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	$employee = get_userdata( $user_id );
	$postcode = '';

	if ( $employee && ! empty( $employee->postcode ) ) {
		$postcode = stripslashes( $employee->postcode );
	}

	return apply_filters( 'mem_get_employee_post_code', $postcode, $user_id );
} // mem_get_employee_post_code

/**
 * Retrieve an employees address.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The address of the employee.
 */
function mem_get_employee_address( $user_id = '' ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	$employee = get_userdata( $user_id );
	$address  = array();

	if ( ! empty( $employee->address1 ) ) {
		$address[] = stripslashes( $employee->address1 );
	}
	if ( ! empty( $employee->address2 ) ) {
		$address[] = stripslashes( $employee->address2 );
	}
	if ( ! empty( $employee->town ) ) {
		$address[] = stripslashes( $employee->town );
	}
	if ( ! empty( $employee->county ) ) {
		$address[] = stripslashes( $employee->county );
	}
	if ( ! empty( $employee->postcode ) ) {
		$address[] = stripslashes( $employee->postcode );
	}

	return apply_filters( 'mem_get_employee_address', $address, $user_id );
} // mem_get_employee_address

/**
 * Retrieve an employees email address.
 *
 * @since 1.3
 * @param int $user_id The ID of the user to check.
 * @return str The email address of the employee.
 */
function mem_get_employee_email( $user_id ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	$employee = get_userdata( $user_id );

	if ( $employee && ! empty( $employee->user_email ) ) {
		$email = $employee->user_email;
	} else {
		$email = __( 'Email address not set', 'mobile-events-manager' );
	}

	return apply_filters( 'mem_get_employee_email', $email, $user_id );
} // mem_get_employee_email

/**
 * Generate a list of all event employees and output as a HTML table.
 *
 * @since 1.3
 * @param
 * @return
 */
function mem_do_event_employees_list_table( $event_id ) {
	global $wp_roles;

	$employees = mem_get_event_employees_data( $event_id );

	if ( ! $employees || ! is_array( $employees ) ) {
		return;
	}

	?>

	<table class="mem_event_employee_list">
		<tbody>
			<?php foreach ( $employees as $employee ) : ?>

				<?php
				$role = translate_user_role( $wp_roles->roles[ $employee['role'] ]['name'] );
				$name = mem_get_employee_display_name( $employee['id'] );
				$wage = mem_currency_filter( mem_sanitize_amount( $employee['wage'] ) );
				?>

				<tr class="mem_field_wrapper">
					<td><?php printf( '%s (%s)', $name, $role ); ?></td>
					<td>
						<?php if ( mem_get_option( 'enable_employee_payments' ) && mem_employee_can( 'manage_txns' ) ) : ?>
							<?php echo $wage; ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( mem_employee_can( 'mem_event_edit' ) ) : ?>
							<?php if ( 'paid' !== mem_get_employees_event_payment_status( $event_id, $employee['id'] ) ) : ?>

								<?php printf( '<a class="mem-delete remove_event_employee mem-fake" style="margin: 6px 0 10px;" data-employee_id="%1$d" id="remove-employee-%1$d">Remove</a>', $employee['id'] ); ?>


							<?php elseif ( mem_get_option( 'enable_employee_payments' ) ) : ?>

								<?php printf( esc_html__( '<a href="%s">Paid</a>', 'mobile-events-manager' ), ! empty( $employee['txn_id'] ) ? get_edit_post_link( $employee['txn_id'] ) : '' ); ?>

							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>

			<?php endforeach; ?>
		</tbody>
	</table>

	<?php

} // mem_do_event_employees_list_table

/**
 * Add an employee event employee.
 *
 * @since 1.3
 * @param int $event_id Required: The event to which we're adding the employee.
 * @param arr $args Required: Array of detail for the employee.
 * @return arr|bool All employees attached to event, or false on failure.
 */
function mem_add_employee_to_event( $event_id, $args ) {

	$defaults = array(
		'id'             => '',
		'role'           => '',
		'wage'           => '0',
		'payment_status' => 'unpaid',
	);

	$data = wp_parse_args( $args, $defaults );

	$data['wage'] = $data['wage'];

	// If we're missing data then we fail.
	if ( empty( $data['id'] ) || empty( $data['role'] ) ) {
		return false;
	}

	$employees      = mem_get_event_employees( $event_id );
	$employees      = ! empty( $employees ) ? $employees : array();
	$employees_data = mem_get_event_employees_data( $event_id );
	$employees_data = ! empty( $employees_data ) ? $employees_data : array();

	$mem_txn = new MEM_Txn();

	$mem_txn->create(
		array(
			'post_title'  => sprintf( esc_html__( 'Wage payment to %1$s for %2$d', 'mobile-events-manager' ), mem_get_employee_display_name( $data['id'] ), $event_id ),
			'post_status' => 'mem-expenditure',
			'post_author' => 1,
			'post_parent' => $event_id,
		),
		array(
			'_mem_txn_status' => 'Pending',
			'_mem_payment_to' => $data['id'],
			'_mem_txn_total'  => $data['wage'],
		)
	);

	if ( ! empty( $mem_txn ) ) {
		$data['txn_id'] = $mem_txn->ID;
	}

	mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'slug', 'mem-employee-wages' ) );
	array_push( $employees, $data['id'] );
	$employees_data[ $data['id'] ] = $data;

	if ( update_post_meta( $event_id, '_mem_event_employees', $employees ) && update_post_meta( $event_id, '_mem_event_employees_data', $employees_data ) ) {
		return true;
	} else {
		return false;
	}

} // mem_add_employee_to_event

/**
 * Remove an employee from an event.
 *
 * @since 1.3
 * @param int $employee_id The employee user ID.
 * @param int $event_id The event to which we're adding the employee.
 * @return void
 */
function mem_remove_employee_from_event( $employee_id, $event_id ) {

	$employees      = mem_get_event_employees( $event_id );
	$employees_data = mem_get_event_employees_data( $event_id );

	if ( empty( $employees ) ) {
		$employees = array();
	}

	if ( ! empty( $employees ) ) {
		foreach ( $employees as $key => $employee ) {
			if ( $employee === $employee_id ) {
				unset( $employees[ $key ] );
			}
		}

		update_post_meta( $event_id, '_mem_event_employees', $employees );
	}

	if ( ! empty( $employees_data ) ) {

		remove_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );

		foreach ( $employees_data as $key => $employee_data ) {
			if ( $employee_data['id'] === $employee_id ) {
				if ( ! empty( $employee_data['txn_id'] ) ) {
					wp_delete_post( $employee_data['txn_id'] );
				}
				unset( $employees_data[ $key ] );
			}
		}

		add_action( 'save_post_mem-transaction', 'mem_save_txn_post', 10, 3 );
		update_post_meta( $event_id, '_mem_event_employees_data', $employees_data );

	}

} // mem_remove_employee_from_event

/**
 * Set the role for the given employees
 *
 * @param int|arr $employees Required: Single user ID or array of user ID's to adjust.
 * str $role Required: The role ID to which the users will be moved
 *
 * @return
 */
function mem_set_employee_role( $employees, $role ) {

	if ( ! is_array( $employees ) ) {
		$employees = array( $employees );
	}

	foreach ( $employees as $employee ) {

		// Fetch the WP_User object of our user.
		$user = new WP_User( $employee );

		if ( ! empty( $user ) ) {
			MEM()->debug->log_it( sprintf( esc_html__( 'Updating user role for %d to $s', 'mobile-events-manager' ), $employee, $role ), true );
		}

		// Replace the current role with specified role.
		$user->set_role( $role );

	}

} // mem_set_employee_role

/**
 * Retrieve the employees events
 *
 * @since 1.3
 * @param int $employee_id The employees user ID. Uses current user ID if not value is passed.
 * @param arr $args Array of possible arguments. See $defaults.
 * @return mixed $events False if no events, otherwise an object array of all employees events.
 */
function mem_get_employee_events( $employee_id = '', $args = array() ) {

	$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();

	$employee_query = array(
		'relation' => 'OR',
		array(
			'key'     => '_mem_event_dj',
			'value'   => $employee_id,
			'compare' => '=',
			'type'    => 'numeric',
		),
		array(
			'key'     => '_mem_event_employees',
			'value'   => sprintf( ':"%s";', $employee_id ),
			'compare' => 'LIKE',
		),
	);

	$defaults = array(
		'post_type'      => 'mem-event',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'meta_query'     => $employee_query,
		'date'           => false, // Required if checking for events on a specific date. Parse an array if querying a date range.
		'date_compare'   => '=',
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! empty( $args['date'] ) ) {
		$date_query = array(
			'relation' => 'AND',
			array(
				'key'     => '_mem_event_date',
				'value'   => $args['date'],
				'type'    => 'DATE',
				'compare' => $args['date_compare'],
			),
		);

		$args['meta_query'] = array_merge( $employee_query, $date_query );

	}

	// We don't need the date args any longer.
	unset( $args['date'], $args['date_compare'] );

	$events = get_posts( $args );

	// Return the results.
	if ( $events ) {
		return $events;
	} else {
		return false;
	}

} // mem_get_employee_events

/**
 * Get the count of an employees events.
 *
 * @since 1.3
 * @param int $employee_id The employees user ID. Uses current user ID if not value is passed.
 * @param arr $args Array of possible arguments. See mem_get_employee_events()->$defaults.
 * @return mixed $events False if no events, otherwise an object array of all employees events.
 */
function mem_count_employee_events( $employee_id = '', $args = array() ) {

	$count = 0;

	$events = mem_get_employee_events( $employee_id, $args );

	if ( $events ) {
		$count = count( $events );
	}

	return $count;

} // mem_count_employee_events

/**
 * Get the employees next event.
 *
 * @since 1.3
 * @param int $employee_id The employees user ID. Uses current user ID if not value is passed.
 * @param arr $args see mem_get_employee_events()->$defaults.
 * @return mixed $event False if no events, otherwise an object array of the next event.
 */
function mem_get_employees_next_event( $employee_id = '' ) {

	$args = array(
		'post_status'    => mem_active_event_statuses(),
		'posts_per_page' => 1,
		'meta_key'       => '_mem_event_date',
		'meta_value'     => gmdate( 'Y-m-d' ),
		'meta_compare'   => '>=',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	);

	$next_event = mem_get_employee_events( $employee_id, $args );

	if ( $next_event ) {
		return $next_event[0];
	} else {
		false;
	}

} // mem_get_employees_next_event

/**
 * Determine if an employee is working a specific event
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $employee_id The employee user ID.
 * @return bool True if working the event, otherwise false
 */
function mem_employee_working_event( $event_id, $employee_id = '' ) {

	$event_employees = mem_get_event_employees( $event_id );

	$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();

	if ( ! $event_employees || ! in_array( $employee_id, $event_employees ) ) {
		return false;
	} else {
		return true;
	}

} // mem_employee_working_event

/**
 * Retrieve a list of the employee's clients.
 *
 * @since 1.3
 * @param int  $employee The user ID of the employee.
 * @param bool $active_only True to only query active clients, false for all.
 * @param str  $return Return resultset as WP_User OBJECTS or ARRAY of user ID's.
 * @return arr|bool Array of client user ID's or
 */
function mem_get_employee_clients( $employee_id = '', $active_only = true, $return = 'OBJECT' ) {

	$employee_id         = ! empty( $employee_id ) ? $employee_id : get_current_user_id();
	$args['post_status'] = ! empty( $active_only ) ? 'any' : mem_active_event_statuses();

	// If we only want active events set an extra check for the event date.

	/*
	If ( ! empty( $active_only ) ) {
		$args['date'] = gmdate( 'Y-m-d');
		$args['date_compare'] = '>=';
	}
	*/

	$events = mem_get_employee_events( $employee_id, $args );

	if ( ! $events ) {
		return false;
	}

	$clients = array();

	// Loop through the events and retrieve the client.
	foreach ( $events as $event ) {
		$clients[] = mem_get_event_client_id( $event->ID );
	}

	if ( empty( $clients ) ) {
		return false;
	}

	$clients = array_unique( $clients );
	$clients = apply_filters( 'mem_get_employee_clients', $clients, $employee_id );

	if ( 'ARRAY' !== $return ) {

		foreach ( $clients as $client ) {
			$client_objects[] = get_userdata( $client );
		}

		$clients = $client_objects;

	}

	return $clients;

} // mem_get_employee_clients

/**
 * Get an employees wage for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $employee_id The employee user ID.
 * @return str Employees wage for the event.
 */
function mem_get_employees_event_wage( $event_id, $employee_id = '' ) {

	$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();

	$wage = 0;

	if ( mem_get_event_primary_employee( $event_id ) === $employee_id ) {

		$wage = get_post_meta( $event_id, '_mem_event_dj_wage', true );

	} else {

		$employees_data = mem_get_event_employees_data( $event_id );

		if ( $employees_data ) {

			foreach ( $employees_data as $employee_data ) {

				if ( $employee_data['id'] === $employee_id ) {

					if ( ! empty( $employee_data['wage'] ) ) {
						$wage = $employee_data['wage'];
					}
				}
			}
		}
	}

	return $wage;

} // mem_get_employees_event_wage

/**
 * Checks if event employees have been paid in full.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $employee_id User ID of employee to check.
 * @return bool True if all employees, or selected employee have been paid.
 * False if one employee, or the selected employee has not been paid.
 * If no employees are assigned, a true value is returned.
 */
function mem_event_employees_paid( $event_id, $employee_id = '' ) {

	if ( ! mem_get_option( 'enable_employee_payments' ) ) {
		return false;
	}

	$employees = mem_get_all_event_employees( $event_id );

	if ( empty( $employees ) ) {
		return true;
	}

	if ( ! empty( $employee_id ) ) {

		if ( 'paid' === $employees[ $employee_id ]['payment_status'] && 'Completed' === get_post_meta( $employees[ $employee_id ]['txn_id'], '_mem_txn_status', true ) ) {
			return false;
		}
	} else {

		foreach ( $employees as $employee ) {
			if ( 'paid' === $employee['payment_status'] && 'Completed' === get_post_meta( $employee['txn_id'], '_mem_txn_status', true ) ) {
				return false;
			}
		}
	}

	return true;

} // mem_event_employees_paid

/**
 * Whether or not an employee has been paid for an event.
 *
 * @since 1.3
 * @param int $event_id The event ID.
 * @param int $employee_id The employee user ID.
 * @return bool True if paid, otherwise false.
 */
function mem_get_employees_event_payment_status( $event_id, $employee_id = '' ) {

	$employee_id = ! empty( $employee_id ) ? $employee_id : get_current_user_id();

	$payment_status = 'unpaid';

	if ( mem_get_event_primary_employee( $event_id ) === $employee_id ) {

		$payment_data = get_post_meta( $event_id, '_mem_event_dj_payment_status', true );

		$payment_status = empty( $payment_data ) ? 'unpaid' : $payment_data['payment_status'];

	} else {

		$employees_data = mem_get_event_employees_data( $event_id );

		if ( ! empty( $employees_data ) ) {

			foreach ( $employees_data as $employee_data ) {

				if ( $employee_data['id'] === $employee_id ) {

					$payment_status = $employee_data['payment_status'];

				}
			}
		}
	}

	return $payment_status;

} // mem_get_employees_event_payment_status

/**
 * Mark an event employee as paid.
 *
 * @since 1.3
 * @param int $employee_id User ID of employee.
 * @param int $event_id Event ID.
 * @param int $txn_id The transaction ID associated with this payment.
 * @return bool True if payment data updated for event employee, otherwise false.
 */
function mem_set_employee_paid( $employee_id, $event_id, $txn_id = '' ) {

	global $wp_roles;

	if ( ! mem_get_option( 'enable_employee_payments' ) ) {
		return;
	}

	if ( ! mem_is_employee( $employee_id ) ) {

		return false;
	}

	$return = false;

	if ( mem_get_event_primary_employee( $event_id ) === $employee_id ) {

		/**
		 *
		 * Hook fires before marking event employee as paid.
		 *
		 * @since 1.3
		 * @param int $event_id The event ID.
		 */
		do_action( "mem_pre_mem_set_employee_paid_{$employee_id}", $event_id );

		$role    = 'dj';
		$payment = mem_get_txn_price( $txn_id );

		$payment_data = get_post_meta( $event_id, '_mem_event_dj_payment_status', true );

		$payment_data['payment_status'] = mem_get_employees_event_wage( $event_id, $employee_id ) > $payment ? 'part-paid' : 'paid';
		$payment_data['payment_date']   = current_time( 'mysql' );
		$payment_data['txn_id']         = $txn_id;
		$payment_data['payment_amount'] = $payment;

		$payment_update = update_post_meta( $event_id, '_mem_event_dj_payment_status', $payment_data );

		if ( ! empty( $payment_update ) ) {

			MEM()->debug->log_it(
				sprintf(
					'%s successfully paid %s for Event %d',
					mem_get_employee_display_name( $employee_id ),
					mem_currency_filter( mem_get_txn_price( $txn_id ) ),
					$event_id
				)
			);

			$return = true;

		} else {
			MEM()->debug->log_it( sprintf( 'Unable to pay %s for Event %d', mem_get_employee_display_name( $employee_id ), $event_id ) );

			$return = false;
		}
	} else {

		$payment_data = get_post_meta( $event_id, '_mem_event_employees_data', true );

		if ( ! mem_employee_working_event( $event_id, $employee_id ) ) {

			MEM()->debug->log_it( 'Employee not working this event' );
			return false;

		} else {

			/**
			 *
			 * Hook fires before marking event employee as paid.
			 *
			 * @since 1.3
			 * @param int $event_id The event ID.
			 */
			do_action( "mem_pre_mem_set_employee_paid_{$employee_id}", $event_id );

			$role    = $payment_data[ $employee_id ]['role'];
			$payment = mem_get_txn_price( $payment_data[ $employee_id ]['txn_id'] );

			$payment_data[ $employee_id ]['payment_status'] = mem_get_employees_event_wage( $event_id, $employee_id ) > $payment ? 'part-paid' : 'paid';
			$payment_data[ $employee_id ]['payment_date']   = current_time( 'mysql' );
			$payment_data[ $employee_id ]['payment_amount'] = $payment;

			$payment_update = mem_update_txn_meta( $payment_data[ $employee_id ]['txn_id'], array( '_mem_txn_status' => 'Completed' ) );

			if ( ! empty( $payment_update ) ) {
				$payment_update = update_post_meta( $event_id, '_mem_event_employees_data', $payment_data );
			}

			if ( ! empty( $payment_update ) ) {

				MEM()->debug->log_it(
					sprintf(
						'%s successfully paid %s for Event %d',
						mem_get_employee_display_name( $employee_id ),
						mem_currency_filter( mem_get_txn_price( $txn_id ) ),
						$event_id
					)
				);

				$return = true;

			} else {

				MEM()->debug->log_it( sprintf( 'Unable to pay %s for Event %d', mem_get_employee_display_name( $employee_id ), $event_id ) );

				$return = false;

			}
		}
	}

	if ( ! empty( $return ) ) {

		$journal_args = array(
			'user_id'         => 1,
			'event_id'        => $event_id,
			'comment_content' => sprintf(
				__( 'Employee %1$s paid %2$s for their role as %3$s', 'mobile-events-manager' ),
				mem_get_employee_display_name( $employee_id ),
				$payment,
				translate_user_role( $wp_roles->roles[ $role ]['name'] )
			),
		);

		$journal_meta = array(
			'mem_visibility' => ! empty( $meta['visibility'] ) ? $meta['visibility'] : '2',
		);

		mem_add_journal( $journal_args, $journal_meta );

		/**
		 *
		 * Hook fires after successfully marking event employee as paid.
		 *
		 * @since 1.3
		 * @param int $event_id The event ID.
		 * @param int $txn_id The transaction ID associated with the payment
		 */
		do_action( "mem_post_mem_set_employee_paid_{$employee_id}", $event_id, $txn_id );
	}

	return $return;

} // mem_set_employee_paid

/**
 * Log the primary employees payment settings and update if employee or wage changes.
 *
 * @since 1.3
 * @param int $event_id Event ID.
 * @param arr $old_meta Old meta values from before event save.
 * @param arr $new_meta New meta values after event save.
 * @return void
 */
function mem_manage_primary_employee_payment_status( $event_id, $old_meta, $new_meta ) {

	if ( ! mem_get_option( 'enable_employee_payments' ) ) {
		return;
	}

	$mem_event = new MEM_Event( $event_id );

	$employee_id = $mem_event->get_employee();

	if ( empty( $employee_id ) ) {
		return;
	}

	$payment_amount = mem_get_employees_event_wage( $event_id, $employee_id );
	$payment_status = get_post_meta( $event_id, '_mem_event_dj_payment_status', true );

	if ( empty( $payment_status ) ) {

		if ( empty( $payment_amount ) || $payment_amount < 1 ) {
			return;
		}

		$mem_txn = new MEM_Txn();

		$mem_txn->create(
			array(
				'post_title'  => sprintf( esc_html__( 'Wage payment to %1$s for %2$d', 'mobile-events-manager' ), mem_get_employee_display_name( $employee_id ), $event_id ),
				'post_status' => 'mem-expenditure',
				'post_author' => 1,
				'post_parent' => $event_id,
			),
			array(
				'_mem_txn_status' => 'Pending',
				'_mem_payment_to' => $employee_id,
				'_mem_txn_total'  => $payment_amount,
			)
		);

		if ( ! empty( $mem_txn ) ) {
			$data['txn_id'] = $mem_txn->ID;
		}

		mem_set_txn_type( $mem_txn->ID, mem_get_txn_cat_id( 'slug', 'mem-employee-wages' ) );

		$payment_data = array(
			'payment_status' => 'unpaid',
			'payment_date'   => '',
			'txn_id'         => $mem_txn->ID,
			'payment_amount' => '',
		);

		update_post_meta( $event_id, '_mem_event_dj_payment_status', $payment_data );

	} else {

		if ( 'paid' === $payment_status['payment_status'] ) {
			return;
		}

		if ( in_array( $mem_event->post_status, array( 'mem-cancelled', 'mem-rejected', 'mem-failed' ) ) ) {
			update_post_meta( $mem_txn->ID, '_mem_txn_status', 'Cancelled' );
		}

		$mem_txn = new MEM_Txn( $payment_status['txn_id'] );

		if ( $mem_txn->recipient_id === $employee_id ) {
			update_post_meta( $mem_txn->ID, '_mem_payment_to', $employee_id );
		}

		if ( $payment_amount === $mem_txn->price ) {
			update_post_meta( $mem_txn->ID, '_mem_txn_total', $payment_amount );
		}
	}

} // mem_manage_primary_employee_payment_status
add_action( 'mem_primary_employee_payment_status', 'mem_manage_primary_employee_payment_status', 10, 3 );
