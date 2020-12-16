<?php
	defined( 'ABSPATH' ) or die( 'Direct access to this page is disabled!!!' );

if ( ! mem_employee_can( 'manage_employees' ) ) {
	wp_die(
		'<h1>' . __( 'Cheatin&#8217; uh?', 'mobile-events-manager' ) . '</h1>' .
		'<p>' . __( 'You do not have permission to manage employees or permissions.', 'mobile-events-manager' ) . '</p>',
		403
	);
}

/**
 * The user table class extended WP_List_Table
 */
class MEM_Employee_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'mem_list_employee', // Singular label
				'plural'   => 'mem_list_employees', // plural label, also this well be one of the table css class
				'ajax'     => false, // We won't support Ajax for this table
			)
		);
	}

	/**
	 * Define items/data to be displayed before and after the list table
	 *
	 * @param str $action Required: top for top of the table or bottom
	 *
	 * @return str The HTML to be output
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="new_role"><?php esc_html_e( 'Change role to', 'mobile-events-manager' ); ?>&hellip;</label>
			<select name="new_role" id="new_role">
				<option value=""><?php esc_html_e( 'Change role to', 'mobile-events-manager' ); ?>&hellip;</option>
				<?php
				add_filter( 'mem_user_roles', array( MEM()->roles, 'no_admin_role' ) );
				echo MEM()->roles->roles_dropdown();
				remove_filter( 'mem_user_roles', array( MEM()->roles, 'no_admin_role' ) );
				?>
			</select>
			<input type="submit" name="change_role" id="change_role" class="button" value="<?php esc_html_e( 'Change', 'mobile-events-manager' ); ?>" />
		</div>
			<?php
			$this->search_box( __( 'Search', 'mobile-events-manager' ), 'search_id' );
		}
	} // extra_tablenav

	/**
	 * Define the table column ID's and names
	 *
	 * @param
	 *
	 * @return $arr $columns The table column IDs and names
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'name'   => __( 'Name', 'mobile-events-manager' ),
			'role'   => __( 'Role(s)', 'mobile-events-manager' ),
			'events' => __( 'Events', 'mobile-events-manager' ),
			// 'earnings' => __( 'Earnings', 'mobile-events-manager' ),
			'login'  => __( 'Last Login', 'mobile-events-manager' ),
		);

		return $columns;
	} // get_columns

	/**
	 * This is where we define the layout of the list table and the data to be used
	 *
	 * @param
	 *
	 * @return
	 */
	public function prepare_items() {
		// The data is prepared in the MEM_Employee_Manager class
		$employees = MEM_Employee_Manager::$employees;

		// Prepare columns
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Pagination. TODO
		$per_page     = 5;
		$current_page = $this->get_pagenum();

		$total_items = count( $employees );

		$this->items = $employees;
	} // prepare_items

	/**
	 * Specifies the default text to be displayed when there is no data
	 *
	 * @param
	 *
	 * @return str The text to be displayed when there are no results to display
	 */
	public function no_items() {
		esc_html_e( "No Employee's found.", 'mobile-events-manager' );
	} // no_items

	/**
	 * Specifies the default data to be displayed within columns that do not have a
	 * defined method within this class
	 *
	 * @param obj $item The object array for the current data object
	 * str $column_name The name of the current column
	 *
	 * @return str The data to be output into the column
	 */
	public function column_default( $item, $column_name ) {
		global $wp_roles;

		switch ( $column_name ) {
			default:
				return;
		}
	} // column_default

	/**
	 * Create the HTML output for the checkboxes column
	 *
	 * @param obj $item The object array for the current item
	 *
	 * @return str The HTML output for the checkbox column
	 */
	public function column_cb( $item ) {
		echo '<input type="checkbox" name="employees[]" id="employees-' . $item->ID . '"';

		if ( in_array( 'administrator', $item->roles ) ) {
			echo ' disabled="disabled"';
		}

		echo ' value="' . $item->ID . '" />';
	} // column_cb

	/**
	 * Create the HTML output for the name column
	 *
	 * @param obj $item The object array for the current item
	 *
	 * @return str The HTML output for the checkbox column
	 */
	public function column_name( $item ) {
		if ( current_user_can( 'edit_users' ) || get_current_user_id() === $item->ID ) {
			$edit_users = true;
		}

		if ( ! empty( $edit_users ) ) {
			echo '<a href="' . get_edit_user_link( $item->ID ) . '">';
		}

		echo $item->display_name;

		if ( ! empty( $edit_users ) ) {
			echo '</a>';
		}

		if ( mem_is_admin( $item->ID ) ) {
			echo '<br />' . __( '<em>MEM Admin</em>', 'mobile-events-manager' );
		}

		if ( user_can( $item->ID, 'administrator' ) ) {
			echo '<br />' . __( '<em>WordPress Admin</em>', 'mobile-events-manager' );
		}

	} // column_name

	/**
	 * Create the HTML output for the role column
	 *
	 * @param obj $item The object array for the current item
	 *
	 * @return str The HTML output for the checkbox column
	 */
	public function column_role( $item ) {

		global $wp_roles;

		if ( ! empty( $item->roles ) ) {

			foreach ( $item->roles as $role ) {

				if ( array_key_exists( $role, MEM_Employee_Manager::$mem_roles ) ) {
					$roles[ $role ] = MEM_Employee_Manager::$mem_roles[ $role ];
				}
			}
		}

		if ( ! empty( $roles ) ) {

			$i = 1;
			foreach ( $roles as $role_id => $role ) {

				printf(
					'%s%s%s',
					$item->roles[0] != $role_id ? '<span style="font-style: italic;">' : '',
					translate_user_role( $wp_roles->roles[ $role_id ]['name'] ),
					$item->roles[0] != $role_id ? '</span>' : ''
				);

				if ( $i < count( $roles ) ) {
					echo '<br />';
				}

				$i++;

			}
		} else {
			echo __( 'No role assigned', 'mobile-events-manager' );
		}

	} // column_role

	/**
	 * Create the HTML output for the events column
	 *
	 * @param obj $item The object array for the current item
	 *
	 * @return str The HTML output for the checkbox column
	 */
	public function column_events( $item ) {
		$next_event   = mem_get_employees_next_event( $item->ID );
		$total_events = mem_count_employee_events( $item->ID );

		printf(
			__( 'Next: %s', 'mobile-events-manager' ),
			! empty( $next_event ) ? '<a href="' . get_edit_post_link( $next_event->ID ) . '">' .
			mem_format_short_date( get_post_meta( $next_event->ID, '_mem_event_date', true ) ) . '</a>' :
			__( 'None', 'mobile-events-manager' )
		);

		echo '<br />';

		printf(
			__( 'Total: %s', 'mobile-events-manager' ),
			! empty( $total_events ) ? '<a href="' . admin_url(
				'edit.php?s&post_type=mem-event&post_status=all' .
				'&action=-1&mem_filter_date=0&mem_filter_type&mem_filter_employee=' . $item->ID .
				'&mem_filter_client=0&filter_action=Filter&paged=1&action2=-1'
			) . '">' . $total_events . '</a>' :
				'0'
		);
	} // column_events

	/**
	 * Create the HTML output for the login column
	 *
	 * @param obj $item The object array for the current item
	 *
	 * @return str The HTML output for the checkbox column
	 */
	public function column_login( $item ) {
		if ( '' != get_user_meta( $item->ID, 'last_login', true ) ) {
			echo date_i18n( 'H:i d M Y', strtotime( get_user_meta( $item->ID, 'last_login', true ) ) );

		} else {
			echo __( 'Never', 'mobile-events-manager' );
		}
	} // column_login

	/**
	 * Generate the role view filters
	 *
	 * @param
	 *
	 * @return $views Array of $view => $link
	 */
	public function get_views() {
		$views   = array();
		$current = MEM_Employee_Manager::$display_role;

		// All roles link
		$class        = ( empty( $current ) || 'all' === $current ? ' class="current"' : '' );
		$all_url      = remove_query_arg( 'display_role' );
		$views['all'] = '<a href="' . $all_url . '" ' . $class . '>' . __( 'All', 'mobile-events-manager' ) .
			' <span class="count">(' . MEM_Employee_Manager::$total_employees . ')</span></a>';

		// Loop through all roles and generate the required views for each
		foreach ( MEM_Employee_Manager::$mem_roles as $role_id => $role ) {
			$count = count( mem_get_employees( $role_id ) );

			if ( empty( $count ) ) {
				continue;
			}

			$class             = ( $current == $role_id ? ' class="current"' : '' );
			$role_url          = add_query_arg( 'display_role', $role_id );
			$views[ $role_id ] = '<a href="' . $role_url . '" ' . $class . '>' . $role .
				 ' <span class="count">(' . $count . ')</span></a>';
		}

		return $views;
	} // get_views

	/**
	 * Add the bulk actions to the table header and footer and define the options
	 *
	 * @params
	 *
	 * @return arr $actions The options for the bulk action dropdown
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete Employee', 'mobile-events-manager' ),
		);

		foreach ( MEM_Employee_Manager::$mem_roles as $role_id => $role ) {
			if ( 'administrator' !== $role_id ) {
				$actions[ 'add_role_' . $role_id ] = sprintf( esc_html__( 'Add %s Role', 'mobile-events-manager' ), $role );
			}
		}

		return $actions;
	} // get_bulk_actions

	/**
	 * Process bulk actions if requested
	 *
	 * @param
	 *
	 * @return
	 */
	public function process_bulk_actions() {

		if ( 'delete' === $this->current_action() && ! empty( $_POST['employees'] ) ) {

			foreach ( sanitize_key( wp_unslash( $_POST['employees'] ) ) as $user_id ) {
				MEM()->debug->log_it( 'Deleting employee with ID ' . $user_id, true );
				wp_delete_user( $user_id );
			}

			mem_update_notice( 'updated', __( 'Employee(s) deleted.', 'mobile-events-manager' ), true );

		}

		// Determine if we are adding an additional role to a user
		foreach ( MEM_Employee_Manager::$mem_roles as $role_id => $role ) {

			if ( 'add_role_' . $role_id === $this->current_action() && ! empty( $_POST['employees'] ) ) {

				foreach ( sanitize_key( wp_unslash( $_POST['employees'] ) ) as $user_id ) {

					MEM()->debug->log_it( 'Adding additional role ' . $role . ' to user ' . $user_id, true );

					$e = new WP_User( $user_id );

					if ( ! in_array( $role_id, $e->roles ) ) {
						$e->add_cap( $role_id );
					}
				}

				mem_update_notice( 'updated', __( $role . ' added to employee(s).', 'mobile-events-manager' ), true );

			}
		}

	} // process_bulk_actions

} // MEM_Employee_Table

/**
 * Class Name: MEM_Employee_Manager
 * User management interface for employees
 */
if ( ! class_exists( 'MEM_Employee_Manager' ) ) :
	class MEM_Employee_Manager {
		private static $active_tab;
		public static $display_role;
		private static $orderby;
		private static $order;
		private static $all_roles;
		public static $employees;
		public static $total_employees;
		public static $mem_roles;
		private static $total_roles;
		private static $mem_employee_table;
		/**
		 * Init
		 */
		public static function init() {
			global $wp_roles;

			// Listen for post requests
			// Update the user role
			if ( isset( $_POST['change_role'], $_POST['new_role'], $_POST['employees'] ) ) {
				foreach ( sanitize_key( wp_unslash( $_POST['employees'] ) ) as $employee ) {
					mem_set_employee_role( $employee, sanitize_key( wp_unslash( $_POST['new_role'] ) ) );
				}

				mem_update_notice( 'updated', __( 'Employee roles updated.', 'mobile-events-manager' ), true );
			}

			wp_enqueue_script( 'jquery-validation-plugin' );

			self::$all_roles = $wp_roles;

			// Filter our search by role if we need to
			self::$display_role = ! empty( $_GET['display_role'] ) ? sanitize_key( wp_unslash( $_GET['display_role'] ) ) : '';
			self::$orderby      = ! empty( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
			self::$order        = ! empty( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : '';

			// Which tab?
			self::$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'user_roles';

			// Display the page tabs
			self::page_header();

			// Retrieve all MEM roles
			self::$mem_roles  = mem_get_roles();
			self::$total_roles = count( self::$mem_roles );

			// Determine the page to display
			if ( 'permissions' === self::$active_tab ) {
				self::permissions_manager();

			} else {
				// Instantiate the user table class
				self::$mem_employee_table = new MEM_Employee_Table();
				self::$mem_employee_table->process_bulk_actions();
				// Retrieve employee list
				self::$employees       = empty( $_POST['s'] ) ? mem_get_employees( self::$display_role, self::$orderby, self::$order ) : self::search();
				self::$total_employees = count( mem_get_employees() );
				self::$mem_employee_table->prepare_items();

				// The header for the user management page
				self::employee_page();
			}
		} // init

		public static function search() {

			foreach ( self::$mem_roles as $role => $label ) {
				$roles[] = $role;
			}

			$employees = array();

			$args = array(
				'search'   => '*' . sanitize_key( wp_unslash( $_POST['s'] ) ) . '*',
				'role__in' => $roles,
			);

			// Execute the query
			$employee_query = new WP_User_Query( $args );

			$results = $employee_query->get_results();

			$employees = array_merge( $employees, $results );
			$employees = array_unique( $employees, SORT_REGULAR );

			return $employees;

		}

		/**
		 * Display the page header for the user management interface
		 */
		public static function page_header() {
			?>
			<div class="wrap">
			<div id="icon-themes" class="icon32"></div>
			<h1><?php esc_html_e( "Employee's &amp; Roles", 'mobile-events-manager' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a href="admin.php?page=mem-employees&amp;tab=user_roles" class="nav-tab
					<?php echo 'user_roles' === self::$active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Employees & Roles', 'mobile-events-manager' ); ?>
				</a>

				<a href="admin.php?page=mem-employees&amp;tab=permissions" class="nav-tab
					<?php echo 'permissions' === self::$active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Permissions', 'mobile-events-manager' ); ?>
				</a>
			</h2>
			<?php
		} // page_header

		/**
		 * Display the interface for managing users and roles
		 */
		public static function employee_page() {
			?>
			<form name="mem_employee_list" id="mem_employee_list" method="post">
			<?php
			wp_nonce_field( 'mem_user_list_table' );
			self::$mem_employee_table->views();
			?>
			<table style="width: 100%;">
			<tr valign="top">
			<td style="width: auto;"><?php self::$mem_employee_table->display(); ?></td>
			<td style="width: auto; vertical-align: top;">

			<table class="widefat" class="alternate">
			<tr>
			<td style="width: 250px;">
			<select name="all_roles[]" id="all_roles" multiple="multiple" style="min-width: 250px; height: auto;">
			<?php
			// Display the roles
			echo MEM()->roles->roles_dropdown( array( 'disable_default' => true ) );
			?>
			</select>
			<br />
			<span style="font-size: smaller; font-style: italic;"><?php esc_html_e( 'Hold CTRL & click to select multiple entries', 'mobile-events-manager' ); ?></span>
			</td>
			</tr>
			<tr>
			<td style="text-align: right;">
			<?php submit_button( __( 'Delete Selected Role(s)', 'mobile-events-manager' ), 'delete', 'delete-roles', false ); ?>
			</td>
			</tr>
			<tr>
			<td>
			<input type="text" name="add_mem_role" id="add_mem_role" />&nbsp;<a id="new_mem_role" class="button button-primary" href="#"><?php esc_html_e( 'Add Role', 'mobile-events-manager' ); ?></a><span id="pleasewait" style="display: none;" class="page-content"><?php esc_html_e( 'Please wait...', 'mobile-events-manager' ); ?></span>
			</td>
			</tr>
			</table>
			</form>
			<?php
			self::add_employee_form();
			?>
			</td>
			</tr>
			</table>
			</div>
			<?php
		} // employee_page

		/**
		 * Display the form for adding a new employee to MEM
		 */
		public static function add_employee_form() {
			// Ensure user has permssion to add an employee
			if ( ! mem_employee_can( 'manage_staff' ) ) {
				return;
			}

			?>
			<h3><?php esc_html_e( 'Employee Quick Add', 'mobile-events-manager' ); ?></h3>
			<form name="mem_employee_add" id="mem_employee_add" method="post">
			<?php mem_admin_action_field( 'add_employee' ); ?>
			<?php wp_nonce_field( 'add_employee', 'mem_nonce', true, true ); ?>
			<table class="widefat">
			<tr>
			<td style="width: 250px;"><label class="mem-label" for="first_name"><?php esc_html_e( 'First Name', 'mobile-events-manager' ); ?>:</label><br />
			<input type="text" name="first_name" id="first_name" required /></td>
			</tr>
			<tr>
			<td style="width: 250px;"><label class="mem-label" for="last_name"><?php esc_html_e( 'Last Name', 'mobile-events-manager' ); ?>:</label><br />
			<input type="text" name="last_name" id="last_name" required /></td>
			</tr>
			<tr>
			<td style="width: 250px;"><label class="mem-label" for="user_email"><?php esc_html_e( 'Email Address', 'mobile-events-manager' ); ?>:</label><br />
			<input type="text" name="user_email" id="user_email" required /></td>
			</tr>
			<tr>
			<td style="width: 250px;"><label class="mem-label" for="employee_role"><?php esc_html_e( 'Role', 'mobile-events-manager' ); ?>:</label><br />
			<select name="employee_role" id="employee_role" required>
				<option value=""><?php esc_html_e( 'Select Role', 'mobile-events-manager' ); ?>&hellip;</option>
				<?php
				echo MEM()->roles->roles_dropdown();
				?>
			</select>
			</td>
			</tr>
			<tr>
			<td style="text-align: right;"><?php submit_button( __( 'Add Employee', 'mobile-events-manager' ), 'primary', 'mem-add-employee', false ); ?></td>
			</tr>
			</table>
			</form>
			<?php
		} // add_employee_form

		/**
		 * Display the interface for managing role permissions
		 */
		public static function permissions_manager() {
			?>
			<form name="mem_permissions" id="mem_permissions" method="post">
			<?php wp_nonce_field( 'mem_permissions_table' ); ?>
			<input type="hidden" name="mem_set_permissions" id="mem_set_permissions" />
			<table class="widefat fixed striped" style="width: 100%;">
			<thead>
			<tr>
			<td id="mem-emp-roles"><label class="screen-reader-text"><?php esc_html_e( 'Roles', 'mobile-events-manager' ); ?></label></td>
			<th scope="col" id="mem-full-admin" style="font-size: small;"><strong><?php esc_html_e( 'MEM Admin', 'mobile-events-manager' ); ?></strong></th>
			<th scope="col" colspan="6" style="font-size: small;"><strong><?php esc_html_e( 'Permissions', 'mobile-events-manager' ); ?></strong></th>
			</tr>
			</thead>
			<tbody id="the-list">
			<?php
			$i = 0;
			foreach ( self::$mem_roles as $role_id => $role ) {
				// Don't show the admin role as this cannot be changed within MEM
				if ( 'administrator' == $role_id ) {
					continue;
				}

				$caps = get_role( $role_id );

				echo '<input type="hidden" name="employee_roles[]" value="' . $role_id . '" />' . "\r\n";
				echo '<tr' . ( 0 === $i ? ' class="alternate"' : '' ) . '>' . "\r\n";
				echo '<th scope="row" id="' . $role_id . '-role" class="manage-row row-' . $role_id . '-roles row-primary" style="font-size: small;"><strong>' . $role . '</strong></th>' . "\r\n";

				echo '<td scope="row" style="font-size: small; vertical-align: middle;">';
					echo '<input type="checkbox" name="manage_mem_' . $role_id . '" id="manage_mem_' . $role_id . '" value="1" style="font-size: small;"';
				if ( ! empty( $caps->capabilities['manage_mem'] ) ) {
					echo ' checked="checked"';
				}
					echo ' />' . "\r\n";
				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Clients', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="client_permissions_' . $role_id . '" id="client_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_client_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_client_edit_own"';
				if ( ! empty( $caps->capabilities['mem_client_edit_own'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit Own', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_client_edit"';
				if ( ! empty( $caps->capabilities['mem_client_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

					echo '<br /><br />' . "\r\n";

					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Employees', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="employee_permissions_' . $role_id . '" id="employee_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_employee_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_employee_edit"';
				if ( ! empty( $caps->capabilities['mem_employee_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Manage', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Comms', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="comm_permissions_' . $role_id . '" id="comm_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_comms_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_comms_send"';
				if ( ! empty( $caps->capabilities['mem_comms_send'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Send', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

					echo '<br /><br />' . "\r\n";

					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Packages', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="package_permissions_' . $role_id . '" id="package_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_package_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_package_edit_own"';
				if ( ! empty( $caps->capabilities['mem_package_edit_own'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit Own', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_package_edit"';
				if ( ! empty( $caps->capabilities['mem_package_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Events', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="event_permissions_' . $role_id . '" id="event_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_event_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_event_read_own"';
				if ( ! empty( $caps->capabilities['mem_event_read_own'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Read Own', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_event_read"';
				if ( ! empty( $caps->capabilities['mem_event_read'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Read All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_event_edit_own"';
				if ( ! empty( $caps->capabilities['mem_event_edit_own'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit Own', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_event_edit"';
				if ( ! empty( $caps->capabilities['mem_event_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

					echo '<br /><br />' . "\r\n";

					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Templates', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="template_permissions_' . $role_id . '" id="template_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_template_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_template_edit"';
				if ( ! empty( $caps->capabilities['mem_template_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Txns', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="txn_permissions_' . $role_id . '" id="txn_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_txn_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_txn_edit"';
				if ( ! empty( $caps->capabilities['mem_txn_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

					echo '<br /><br />' . "\r\n";

					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Reports', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="report_permissions_' . $role_id . '" id="report_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_reports_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_reports_run"';
				if ( ! empty( $caps->capabilities['mem_reports_run'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Run', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";

				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Quotes', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="quote_permissions_' . $role_id . '" id="quote_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_quote_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_quote_view_own"';
				if ( ! empty( $caps->capabilities['mem_quote_view_own'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'View Own', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_quote_view"';
				if ( ! empty( $caps->capabilities['mem_quote_view'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'View All', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '</select>' . "\r\n";
				echo '</td>' . "\r\n";

				echo '<td scope="row" style="font-size: small;">';
					echo '<span style="font-size: small; font-weight: bold;">' . __( 'Venues', 'mobile-events-manager' ) . ':</span><br />' . "\r\n";
					echo '<select name="venue_permissions_' . $role_id . '" id="venue_permissions_' . $role_id . '" style="font-size: small;">' . "\r\n";
					echo '<option value="mem_venue_none">' . __( 'None', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_venue_read"';
				if ( ! empty( $caps->capabilities['mem_venue_read'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'View', 'mobile-events-manager' ) . '</option>' . "\r\n";

					echo '<option value="mem_venue_edit"';
				if ( ! empty( $caps->capabilities['mem_venue_edit'] ) ) {
					echo ' selected="selected"';
				}
					echo '>' . __( 'Edit', 'mobile-events-manager' ) . '</option>' . "\r\n";
					echo '</select>' . "\r\n";
				echo '</td>' . "\r\n";

				echo '</tr>' . "\r\n";

				0 === $i ? $i++ : $i = 0;
			}
			?>
			</tbody>
			</table>
			<p>
			<?php
			submit_button(
				__( 'Update Permissions', 'mobile-events-manager' ),
				'primary',
				'set-permissions',
				'',
				false
			);
			?>
</form>
			</p>
</div>
			<?php
		} // permissions_manager

	} // class MEM_Employee_Manager
endif;
	MEM_Employee_Manager::init();
