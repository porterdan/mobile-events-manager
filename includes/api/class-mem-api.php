<?php
/**
 * MEM Rest API
 *
 * Provides an API REST interface.
 *
 * The primary purpose of this class is for availability checking and
 * event queries.
 *
 * @package MEM
 * @subpackage Classes/API
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MEM_API Class
 *
 * Renders API returns as a JSON array
 *
 * @since 1.4
 */
class MEM_API {

	/**
	 * Latest API Version
	 */
	const VERSION = 1;

	/**
	 * Namespace.
	 *
	 * @var array $namespace Namespace.
	 */
	public $namespace;

	/**
	 * Log API requests?
	 *
	 * @var bool
	 * @access public
	 * @since 1.4
	 */
	public $log_requests = true;

	/**
	 * Request data.
	 *
	 * @var arr
	 * @access public
	 * @since 1.4
	 */
	private $request = array();

	/**
	 * Is this a valid user?
	 *
	 * @var bool
	 * @access private
	 * @since 1.4
	 */
	private $is_valid_user = false;

	/**
	 * Is this a valid request?
	 *
	 * @var bool
	 * @access private
	 * @since 1.4
	 */
	private $is_valid_request = false;

	/**
	 * User ID Performing the API Request.
	 *
	 * @var int
	 * @access private
	 * @since 1.4
	 */
	public $user_id = 0;

	/**
	 * Response data to return
	 *
	 * @var array
	 * @access private
	 * @since 1.4
	 */
	private $data = array();

	/**
	 * Setup the MEM API.
	 *
	 * @since 1.4
	 */
	public function __construct() {
		$this->namespace = 'mem/v' . self::VERSION;

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
		add_action( 'mem-process_api_key', array( $this, 'process_api_key' ) );
		add_action( '/mem/v1/availability', array( $this, 'availability_check' ) );
		add_action( '/mem/v1/client', array( $this, 'get_client' ) );
		add_action( '/mem/v1/employee', array( $this, 'get_employee' ) );
		add_action( '/mem/v1/event', array( $this, 'get_event' ) );
		add_action( '/mem/v1/events', array( $this, 'list_events' ) );
		add_action( '/mem/v1/package', array( $this, 'get_package' ) );
		add_action( '/mem/v1/packages', array( $this, 'list_packages' ) );
		add_action( '/mem/v1/packages/options', array( $this, 'package_options' ) );
		add_action( '/mem/v1/addon', array( $this, 'get_addon' ) );
		add_action( '/mem/v1/addons', array( $this, 'list_addons' ) );
		add_action( '/mem/v1/addons/options', array( $this, 'addon_options' ) );
	} // __construct

	/**
	 * Register the API endpoints.
	 *
	 * @since 1.4
	 */
	public function register_endpoints() {

		$endpoints = $this->define_endpoints();

		if ( $endpoints ) {

			foreach ( $endpoints as $base => $args ) {
				register_rest_route( $this->namespace, $base, $args, false );
			}
		}

	} // register_endpoints

	/**
	 * Define the API endpoints.
	 *
	 * @since 1.4
	 * @return arr
	 */
	public function define_endpoints() {

		$endpoints = array(
			// For checking agency availability.
			'/availability/'     => array(
				'methods'      => array( WP_REST_Server::READABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// Single client.
			'/client/'           => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => true,
			),
			// Single employee.
			'/employee/'         => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => true,
			),
			// Single event.
			'/event/'            => array(
				'methods'  => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback' => array( $this, 'process_request' ),
				// 'require_auth' => true
			),
			// Multiple events.
			'/events/'           => array(
				'methods'  => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback' => array( $this, 'process_request' ),
				// 'require_auth' => true
			),
			// Retrieving a Package.
			'/package/'          => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// Retrieving Multiple Packages.
			'/packages/'         => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// Retrieving Multiple Packages.
			'/packages/options/' => array(
				'methods'      => array( WP_REST_Server::READABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// Retrieving an Addon.
			'/addon/'            => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// Retrieving Multiple Addons.
			'/addons/'           => array(
				'methods'      => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
			// For retrieving addon options.
			'/addons/options/'   => array(
				'methods'      => array( WP_REST_Server::READABLE ),
				'callback'     => array( $this, 'process_request' ),
				'require_auth' => false,
			),
		);

		return apply_filters( 'mem_api_endpoints', $endpoints );

	} // define_endpoints

	/**
	 * Validate the current user.
	 *
	 * @access private
	 * @since 1.4
	 * @return void
	 */
	private function validate_user() {

		$endpoints = $this->define_endpoints();
		$endpoint  = trailingslashit( str_replace( '/' . $this->namespace, '', $this->request->get_route() ) );

		if ( array_key_exists( 'require_auth', $endpoints[ $endpoint ] ) && false === $endpoints[ $endpoint ]['require_auth'] ) {

			$this->is_valid_user = true;

		} elseif ( empty( $this->request['api_key'] ) || empty( $this->request['token'] ) ) {

			$this->missing_auth();

		} elseif ( ! ( $user = $this->get_user() ) ) {

			$this->invalid_key();

		} else {

			$public = $this->request->get_param( 'api_key' );
			$token  = $this->request->get_param( 'token' );
			$secret = $this->get_user_secret_key( $user );

			if ( hash_equals( md5( $secret . $public ), $token ) ) {
				$this->is_valid_user = true;
			} else {
				$this->invalid_auth();
			}
		}

	} // validate_user

	/**
	 * Retrieve a user ID from the API key provided.
	 *
	 * @since 1.4
	 * @global obj $wpdb
	 * @param str $api_key The API from which to retrieve the user.
	 */
	public function get_user() {
		global $wpdb;

		if ( empty( $key ) ) {
			$key = $this->request->get_param( 'api_key' );
		}

		if ( empty( $key ) ) {
			return false;
		}

		$user = get_transient( md5( 'mem_api_user_' . $key ) );

		if ( false === $user ) {
			$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s LIMIT 1", $key ) );

			set_transient( md5( 'mem_api_user_' . $key ), $user, DAY_IN_SECONDS );
		}

		if ( null !== $user ) {
			$this->user_id = $user;
			return $user;
		}

		return false;

	} // get_user

	/**
	 * Retrieve a user's public key.
	 *
	 * @since 1.4
	 * @global obj $wpdb
	 * @param int $user_id User ID.
	 * @return str
	 */
	public function get_user_public_key( $user_id = 0 ) {

		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}

		$cache_key       = md5( 'mem_api_user_public_key' . $user_id );
		$user_public_key = get_transient( $cache_key );

		if ( empty( $user_public_key ) ) {

			$user_public_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_value = 'mem_user_public_key' AND user_id = %d", $user_id ) );

			set_transient( $cache_key, $user_public_key, HOUR_IN_SECONDS );

		}

		return $user_public_key;

	} // get_user_public_key

	/**
	 * Retrieve a user's secret key.
	 *
	 * @since 1.4
	 * @global obj $wpdb
	 * @param int $user_id User ID.
	 * @return str
	 */
	public function get_user_secret_key( $user_id = 0 ) {

		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}

		$cache_key       = md5( 'mem_api_user_secret_key' . $user_id );
		$user_secret_key = get_transient( $cache_key );

		if ( empty( $user_secret_key ) ) {

			$user_secret_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_value = 'mem_user_secret_key' AND user_id = %d", $user_id ) );

			set_transient( $cache_key, $user_secret_key, HOUR_IN_SECONDS );

		}

		return $user_secret_key;

	} // get_user_secret_key

	/**
	 * Displays an authentication error if api key is invalid.
	 *
	 * @since 1.4
	 * @access private
	 * @uses MEM_API::output()
	 * @return void
	 */
	private function invalid_key() {
		$error          = array();
		$error['error'] = __( 'Invalid API key.', 'mobile-events-manager' );

		$this->data = $error;
		$this->output( 403 );
	} // invalid_key

	/**
	 * Displays a missing authentication error if required paramaters are not provided.
	 *
	 * @since 1.4
	 * @access private
	 * @uses MEM_API::output()
	 * @return void
	 */
	private function missing_auth() {
		$error          = array();
		$error['error'] = __( 'No API and/or token key provided.', 'mobile-events-manager' );

		$this->data = $error;
		$this->output( 401 );
	} // missing_auth

	/**
	 * Displays an authentication error if credentials are invalid.
	 *
	 * @since 1.4
	 * @access private
	 * @uses MEM_API::output()
	 * @return void
	 */
	private function invalid_auth() {
		$error          = array();
		$error['error'] = __( 'Authentication failed.', 'mobile-events-manager' );

		$this->data = $error;
		$this->output( 403 );
	} // invalid_auth

	/**
	 * Displays a missing parameters error if required paramaters are not provided.
	 *
	 * @since 1.4
	 * @access private
	 * @uses MEM_API::output()
	 * @param str|arr $params Required parameters.
	 * @return void
	 */
	private function missing_params( $params ) {
		$error          = array();
		$error['error'] = sprintf(
			__( 'Not all required parameters were provided. Missing: %s', 'mobile-events-manager' ),
			is_array( $params ) ? implode( ', ', $params ) : $params
		);

		$this->data = $error;
		$this->output( 401 );
	} // missing_params

	/**
	 * Displays a permissions error if required permissions are not set.
	 *
	 * @since 1.4
	 * @access private
	 * @uses MEM_API::output()
	 * @return void
	 */
	private function no_permsission() {
		$error          = array();
		$error['error'] = __( 'You do not have appropriate permissions to perform this action', 'mobile-events-manager' );

		$this->data = $error;
		$this->output( 403 );
	} // no_permsission

	/**
	 * Process an API key generation/revocation
	 *
	 * @access public
	 * @since 1.4
	 * @param arr $args API Key Gen.
	 * @return void
	 */
	public function process_api_key( $args ) {

		if ( ! isset( $_REQUEST['api_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['api_nonce'], 'mem-api-nonce' ) ) ) ) {

			wp_die( __( 'Nonce verification failed', 'mobile-events-manager' ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );

		}

		if ( empty( $args['user_id'] ) ) {
			wp_die( sprintf( esc_html__( 'User ID Required', 'mobile-events-manager' ), $process ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 401 ) );
		}

		if ( is_numeric( $args['user_id'] ) ) {
			$user_id = isset( $args['user_id'] ) ? absint( $args['user_id'] ) : get_current_user_id();
		} else {
			$userdata = get_user_by( 'login', $args['user_id'] );
			$user_id  = $userdata->ID;
		}

		$process = isset( $args['mem_api_process'] ) ? strtolower( $args['mem_api_process'] ) : false;

		if ( ! mem_employee_can( 'manage_mem' ) ) {

			wp_die( sprintf( esc_html__( 'You do not have permission to %s API keys for this user', 'mobile-events-manager' ), $process ), __( 'Error', 'mobile-events-manager' ), array( 'response' => 403 ) );

		}

		switch ( $process ) {

			case 'generate':
				if ( $this->generate_api_key( $user_id ) ) {

					delete_transient( 'mem-total-api-keys' );

					wp_safe_redirect(
						add_query_arg(
							array( 'mem-message' => 'api-key-generated' ),
							'edit.php?post_type=mem-event&page=mem-tools&tab=api_keys'
						)
					);
					exit();

				} else {

					wp_safe_redirect(
						add_query_arg(
							array( 'mem-message' => 'api-key-failed' ),
							'edit.php?post_type=mem-event&page=mem-tools&tab=api_keys'
						)
					);
					exit();

				}

				break;

			case 'regenerate':
				$this->generate_api_key( $user_id, true );
				delete_transient( 'mem-total-api-keys' );

				wp_safe_redirect(
					add_query_arg(
						array( 'mem-message' => 'api-key-regenerated' ),
						'edit.php?post_type=mem-event&page=mem-tools&tab=api_keys'
					)
				);
				exit();
				break;

			case 'revoke':
				$this->revoke_api_key( $user_id );
				delete_transient( 'mem-total-api-keys' );

				wp_safe_redirect(
					add_query_arg(
						array( 'mem-message' => 'api-key-revoked' ),
						'edit.php?post_type=mem-event&page=mem-tools&tab=api_keys'
					)
				);
				exit();
				break;

			default:
				break;

		}

	} // process_api_key

	/**
	 * Generate new API keys for a user.
	 *
	 * @access public
	 * @since 1.4
	 * @param int  $user_id User ID the key is being generated for.
	 * @param bool $regenerate Regenerate the key for the user.
	 * @return bool True if (re)generated succesfully, false otherwise.
	 */
	public function generate_api_key( $user_id = 0, $regenerate = false ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );
		$secret_key = $this->get_user_secret_key( $user_id );

		if ( empty( $public_key ) || true === $regenerate ) {

			$new_public_key = $this->generate_public_key( $user->user_email );
			$new_secret_key = $this->generate_private_key( $user->ID );

		} else {
			return false;
		}

		if ( true === $regenerate ) {
			$this->revoke_api_key( $user->ID );
		}

		update_user_meta( $user_id, $new_public_key, 'mem_user_public_key' );
		update_user_meta( $user_id, $new_secret_key, 'mem_user_secret_key' );

		return true;

	} // generate_api_key

	/**
	 * Revoke a users API keys.
	 *
	 * @access public
	 * @since 1.4
	 * @param int $user_id User ID of user to revoke key for.
	 * @return str
	 */
	public function revoke_api_key( $user_id = 0 ) {

		if ( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );
		$secret_key = $this->get_user_secret_key( $user_id );

		if ( ! empty( $public_key ) ) {

			delete_transient( md5( 'mem_api_user_' . $public_key ) );
			delete_transient( md5( 'mem_api_user_public_key' . $user_id ) );
			delete_transient( md5( 'mem_api_user_secret_key' . $user_id ) );
			delete_user_meta( $user_id, $public_key );
			delete_user_meta( $user_id, $secret_key );

		} else {
			return false;
		}

		return true;

	} // revoke_api_key

	/**
	 * Generate and Save API key
	 *
	 * Generates the key requested by user_key_field and stores it in the database
	 *
	 * @access public
	 * @since 1.4
	 * @param int $user_id user ID.
	 * @return void
	 */
	public function update_key( $user_id ) {
		MEM()->users->update_user_api_key( $user_id );
	} // update_key

	/**
	 * Generate the public key for a user
	 *
	 * @access public
	 * @since 1.4
	 * @param str $user_email User Email.
	 * @return str
	 */
	public function generate_public_key( $user_email = '' ) {

		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$public   = hash( 'md5', $user_email . $auth_key . gmdate( 'U' ) );

		return $public;

	} // generate_public_key

	/**
	 * Generate the secret key for a user
	 *
	 * @access public
	 * @since 1.4
	 * @param int $user_id User ID.
	 * @return str
	 */
	public function generate_private_key( $user_id = 0 ) {

		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secret   = hash( 'md5', $user_id . $auth_key . gmdate( 'U' ) );

		return $secret;

	} // generate_private_key

	/**
	 * Retrieve the user's token
	 *
	 * @access public
	 * @since 1.4
	 * @param int $user_id User ID.
	 * @return str
	 */
	public function get_token( $user_id = 0 ) {
		return hash( 'md5', $this->get_user_secret_key( $user_id ) . $this->get_user_public_key( $user_id ) );
	} // get_token

	/**
	 * Retrieve the API version.
	 *
	 * @since 1.4
	 * @return int
	 */
	public function get_version() {
		return self::VERSION;
	} // get_version

	/**
	 * Process API requests.
	 *
	 * @since 1.4
	 * @param arr $request API Request data.
	 * @return arr
	 */
	public function process_request( WP_REST_Request $request ) {

		$start = microtime( true ); // Start time for logging.
		$route = $request->get_route();

		$this->request = $request; // The request parameters.

		$this->validate_user();

		if ( $this->is_valid_user ) {
			$status_code = 200;
			$this->data  = do_action( $route, $this );
		}

		return $this->data;

	} // process_request

	/**
	 * Sends a response to the API request.
	 *
	 * @since 1.4
	 * @param int $status_code Status code.
	 * @return void
	 */
	public function output( $status_code = 200 ) {

		$response = new WP_REST_Response( array( 'result' => true ) );
		$response->set_status( $status_code );
		$response->header( 'Content-type', 'application/json' );
		$response->set_data( $this->data );

		echo wp_json_encode( $response );

		die();
	} // output

	/**
	 * Prepare and execute an availability check.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function availability_check() {

		$result   = false;
		$response = array();

		if ( ! isset( $this->request['date'] ) ) {
			$this->missing_params( 'date' );
		} else {

			do_action( 'mem_before_api_availability_check', $this );

			$date      = $this->request['date'];
			$employees = isset( $this->request['employees'] ) ? explode( ',', $this->request['employees'] ) : '';
			$roles     = isset( $this->request['roles'] ) ? explode( ',', $this->request['roles'] ) : '';

			$available_text   = ! empty( $this->request['avail_text'] ) ? $this->request['avail_text'] : mem_get_option( 'availability_check_pass_text' );
			$unavailable_text = ! empty( $this->request['unavail_text'] ) ? $this->request['unavail_text'] : mem_get_option( 'availability_check_fail_text' );
			$search           = array( '{EVENT_DATE}', '{EVENT_DATE_SHORT}' );
			$replace          = array( gmdate( 'l, jS F Y', strtotime( $date ) ), mem_format_short_date( $date ) );

			$result = mem_do_availability_check( $date, '', $employees, $roles );

		}

		if ( ! empty( $result ) && ! empty( $result['available'] ) ) {

			$message = str_replace( $search, $replace, $available_text );

			$response['availability'] = array(
				'date'      => $date,
				'response'  => 'available',
				'employees' => $result,
				'message'   => mem_do_content_tags( $message ),
			);
		} else {
			$message = str_replace( $search, $replace, $unavailable_text );

			$response['availability'] = array(
				'date'      => $date,
				'response'  => 'unavailable',
				'employees' => '',
				'message'   => mem_do_content_tags( $message ),
			);
		}

		do_action( 'mem_after_api_availability_check', $this );

		$this->data = array_merge( $this->data, $response );
		$this->output();

	} // availability_check

	/**
	 * Retrieve a client.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function get_client() {

		$response = array();

		if ( ! isset( $this->request['client_id'] ) && ! isset( $this->request['client_email'] ) ) {
			$this->missing_params( 'client_id or client_email' );
		}

		do_action( 'mem_before_api_get_client', $this );

		if ( isset( $this->request['client_email'] ) && ! isset( $this->request['client_id'] ) ) {
			$field = 'email';
			$value = $this->request['client_email'];
		} else {
			$field = 'id';
			$value = $this->request['client_id'];
		}

		$client = get_user_by( $field, $value );

		if ( ! user_can( $client->ID, 'client' ) && ! user_can( $client->ID, 'inactive_client' ) ) {
			$response['error'] = __( 'Error retrieving client.', 'mobile-events-manager' );

			$this->data = array_merge( $response, $this->data );
			$this->output();
		}

		if ( ! $client ) {
			$response['error'] = __( 'Client could not be found.', 'mobile-events-manager' );

			$this->data = array_merge( $response, $this->data );
			$this->output();
		}

		$events        = array();
		$client_events = mem_get_client_events( $client->ID );
		$next_event    = mem_get_clients_next_event( $client->ID );

		if ( $client_events ) {
			foreach ( $client_events as $event ) {
				$events[ $event->ID ] = get_post_meta( $event->ID, '_mem_event_date', true );
			}
		}

		$response['client'] = array(
			'ID'         => $client->ID,
			'first_name' => $client->first_name,
			'last_name'  => $client->last_name,
			'email'      => $client->user_email,
			'last_login' => $client->last_login,
			'events'     => $events,
			'next_event' => array(
				'id'   => ! empty( $next_event ) ? $next_event[0]->ID : '',
				'date' => ! empty( $next_event ) ? get_post_meta( $next_event[0]->ID, '_mem_event_date', true ) : '',
			),
		);

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_get_client', $this );

		$this->output();

	} // get_client

	/**
	 * Retrieve an employee.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function get_employee() {

		global $wp_roles;

		if ( ! isset( $this->request['employee_id'] ) && ! isset( $this->request['employee_email'] ) ) {
			$this->missing_params( 'employee_id or employee_email' );
		}

		do_action( 'mem_before_api_get_employee', $this );

		if ( isset( $this->request['employee_email'] ) && ! isset( $this->request['employee_id'] ) ) {
			$field = 'email';
			$value = $this->request['employee_email'];
		} else {
			$field = 'id';
			$value = $this->request['employee_id'];
		}

		$employee = get_user_by( $field, $value );

		if ( ! $employee ) {
			$response['error'] = __( 'Employee could not be found.', 'mobile-events-manager' );

			$this->data = array_merge( $response, $this->data );
			$this->output();
		}

		if ( ! mem_is_employee( $employee->ID ) ) {
			$response['error'] = __( 'Error retrieving employee.', 'mobile-events-manager' );

			$this->data = array_merge( $response, $this->data );
			$this->output();
		}

		$events          = array();
		$roles           = array();
		$mem_roles      = MEM()->roles->get_roles();
		$employee_events = mem_get_employee_events( $employee->ID );
		$next_event      = mem_get_employees_next_event( $employee->ID );
		$i               = 0;

		if ( $employee_events ) {
			foreach ( $employee_events as $event ) {
				$events[ $event->ID ] = get_post_meta( $event->ID, '_mem_event_date', true );
				$i++;
			}
		}

		if ( ! empty( $employee->roles ) ) {

			foreach ( $employee->roles as $role ) {
				if ( array_key_exists( $role, $mem_roles ) ) {
					$roles[ $role ] = $mem_roles[ $role ];
				}
			}
		}

		$response['employee'] = array(
			'ID'           => $employee->ID,
			'first_name'   => $employee->first_name,
			'last_name'    => $employee->last_name,
			'email'        => $employee->user_email,
			'roles'        => $roles,
			'last_login'   => $employee->last_login,
			'events'       => $events,
			'next_event'   => array(
				'id'   => ! empty( $next_event ) ? $next_event->ID : '',
				'date' => ! empty( $next_event ) ? get_post_meta( $next_event->ID, '_mem_event_date', true ) : '',
			),
			'total_events' => $i,
		);

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_get_employee', $this );

		$this->output();

	} // get_employee

	/**
	 * Retrieve a single event by id.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function get_event() {

		$response = array();

		if ( ! isset( $this->request['event_id'] ) ) {
			$this->missing_params( 'event_id' );
		}

		if ( ! mem_employee_can( 'read_events', $this->user_id ) ) {
			$this->no_permsission();
		}

		do_action( 'mem_before_api_get_event', $this );

		$mem_event = mem_get_event( $this->request['event_id'] );

		if ( ! $mem_event ) {
			$error = array();
			/* translators: %s: Event Type */
			$error['error'] = sprintf( esc_html__( '%s does not exist.', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) );

			$this->data = $error;
			$this->output();
		}

		$response['event'] = mem_get_event_data( $mem_event );

		$response['event'] = array_merge( array( 'id' => $mem_event->ID ), $response['event'] );

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_get_event', $this );

		$this->output();

	} // get_event

	/**
	 * Retrieve events filtered by employee, client, date or status.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function list_events() {

		$response = array();

		if ( ! mem_employee_can( 'read_events', $this->user_id ) ) {
			$this->no_permsission();
		}

		if ( ! isset( $this->request['employee_id'] ) && ! mem_employee_can( 'read_events_all', $this->user_id ) ) {
			$this->no_permsission();
		}

		do_action( 'mem_before_api_event_list', $this );

		if ( isset( $this->request['employee_id'] ) ) {
			$events = mem_get_employee_events( $this->request['employee_id'] );
		} elseif ( isset( $this->request['client_id'] ) ) {
			$events = mem_get_client_events( $this->request['client_id'] );
		} elseif ( isset( $this->request['date'] ) ) {
			$events = mem_get_events_by_date( $this->request['date'] );
		} elseif ( isset( $this->request['status'] ) ) {
			$events = mem_get_events_by_status( $this->request['status'] );
		} else {
			$events = mem_get_events();
		}

		if ( ! $events ) {
			$error = array();
			/* translators: %s: Event Type */
			$error['error'] = sprintf( esc_html__( 'No %s found.', 'mobile-events-manager' ), mem_get_label_plural( true ) );

			$this->data = $error;
			$this->output();
		}

		$response['events'] = array();
		$i                  = 0;

		foreach ( $events as $event ) {
			$response['events'][ $event->ID ] = mem_get_event_data( $event->ID );
			$i++;
		}

		$response['count'] = $i;

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_event_list', $this );

		$this->output();

	} // list_events

	/**
	 * Retrieve a single package by ID, name, or slug.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function get_package() {

		$response = array();

		if ( ! isset( $this->request['package'] ) ) {
			$this->missing_params( 'package' );
		}

		do_action( 'mem_before_api_get_package', $this );

		if ( ! is_numeric( $this->request['package'] ) ) { // Using name or slug.
			$package = mem_get_package_by( 'name', $this->request['package'] );
		} else {
			$package = mem_get_package( $this->request['package'] );
		}

		if ( ! $package ) {
			$error          = array();
			$error['error'] = __( 'Package does not exist.', 'mobile-events-manager' );

			$this->data = $error;
			$this->output();
		}

		$response['package'] = mem_get_package_data( $package );

		$response['package'] = array_merge( array( 'id' => $package->ID ), $response['package'] );

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_get_package', $this );

		$this->output();

	} // get_package

	/**
	 * Retrieve packages filtered by employee, event month, event type or category.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function list_packages() {

		$response = array();
		$packages = array();

		do_action( 'mem_before_api_list_packages', $this );

		$all_packages = mem_get_packages( array( 'suppress_filters' => false ) );

		if ( $all_packages ) {
			foreach ( $all_packages as $package ) {
				if ( isset( $this->request['employee_id'] ) && ! mem_employee_has_package( $package->ID, $this->request['employee_id'] ) ) {
					continue;
				}
				if ( isset( $this->request['event_month'] ) && ! mem_package_is_available_for_event_date( $package->ID, $this->request['event_month'] ) ) {
					continue;
				}
				if ( isset( $this->request['event_type'] ) && ! mem_package_is_available_for_event_type( $package->ID, $this->request['event_type'] ) ) {
					continue;
				}

				$packages[] = $package->ID;

			}
		}

		if ( empty( $packages ) ) {
			$error          = array();
			$error['error'] = __( 'No packages found.', 'mobile-events-manager' );

			$this->data = $error;
			$this->output();
		}

		$response['packages'] = array();
		$i                    = 0;

		foreach ( $packages as $package ) {
			$response['packages'][ $package ] = mem_get_package_data( $package );
			$i++;
		}

		$response['count'] = $i;

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_list_packages', $this );

		$this->output();

	} // list_packages

	/**
	 * Retrieve package options.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function package_options() {

		$response = array();

		$event_type   = ! empty( $this->request['event_type'] ) ? $this->request['event_type'] : false;
		$event_date   = ! empty( $this->request['event_date'] ) ? $this->request['event_date'] : false;
		$package_cost = isset( $this->request['package_cost'] ) ? true : false;
		$selected     = isset( $this->request['selected'] ) ? $this->request['selected'] : '';

		$args = array(
			'event_type' => $event_type,
			'event_date' => $event_date,
			'cost'       => $package_cost,
			'selected'   => $selected,
		);

		$packages = mem_package_dropdown( $args, false );

		if ( ! empty( $packages ) ) {
			$response['type']     = 'success';
			$response['packages'] = $packages;
		} else {
			$response['type']     = 'success';
			$response['packages'] = '<option value="0" disabled="disabled">' . __( 'No packages available', 'mobile-events-manager' ) . '</option>';
		}

		$this->data = array_merge( $this->data, $response );

		$this->output();

	} // package_options

	/**
	 * Retrieve a single addon by ID, name, or slug.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function get_addon() {

		$response = array();

		if ( ! isset( $this->request['addon'] ) ) {
			$this->missing_params( 'addon' );
		}

		do_action( 'mem_before_api_get_addon', $this );

		if ( ! is_numeric( $this->request['addon'] ) ) { // Using name or slug.
			$addon = mem_get_addon_by( 'name', $this->request['addon'] );
		} else {
			$addon = mem_get_addon( $this->request['addon'] );
		}

		if ( ! $addon ) {
			$error          = array();
			$error['error'] = __( 'Addon does not exist.', 'mobile-events-manager' );

			$this->data = $error;
			$this->output();
		}

		$response['addon'] = mem_get_addon_data( $addon );

		$response['addon'] = array_merge( array( 'id' => $addon->ID ), $response['addon'] );

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_get_addon', $this );

		$this->output();

	} // get_addon

	/**
	 * Retrieve addons filtered by package, employee, event month, event type or category.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function list_addons() {

		$response       = array();
		$package_addons = array();
		$addons         = array();

		do_action( 'mem_before_api_list_addons', $this );

		if ( isset( $this->request['package'] ) ) {
			if ( ! is_numeric( $this->request['package'] ) ) { // Using name or slug.
				$package = mem_get_package_by( 'name', $this->request['package'] );

				if ( $package ) {
					$package_id = $package->ID;
				}
			} else {
				$package_id = $this->request['package'];
			}

			if ( ! empty( $package_id ) ) {
				$package_addons = mem_get_package_addons( $package_id );

				if ( $package_addons ) {
					foreach ( $package_addons as $package_addon ) {
						$all_addons[] = mem_get_addon( $package_addon );
					}
				}
			}
		} else {
			$all_addons = mem_get_addons( array( 'suppress_filters' => false ) );
		}

		if ( ! empty( $all_addons ) ) {

			foreach ( $all_addons as $addon ) {
				if ( isset( $this->request['employee_id'] ) && ! mem_employee_has_addon( $addon->ID, $this->request['employee_id'] ) ) {
					continue;
				}
				if ( isset( $this->request['event_month'] ) && ! mem_addon_is_available_for_event_date( $addon->ID, $this->request['event_month'] ) ) {
					continue;
				}
				if ( isset( $this->request['event_type'] ) && ! mem_addon_is_available_for_event_type( $addon->ID, $this->request['event_type'] ) ) {
					continue;
				}

				$addons[] = $addon->ID;

			}
		}

		if ( empty( $addons ) ) {
			$error          = array();
			$error['error'] = __( 'No addons found.', 'mobile-events-manager' );

			$this->data = $error;
			$this->output();
		}

		$response['addons'] = array();
		$i                  = 0;

		foreach ( $addons as $addon ) {
			$response['addons'][ $addon ] = mem_get_addon_data( $addon );
			$i++;
		}

		$response['count'] = $i;

		$this->data = array_merge( $this->data, $response );

		do_action( 'mem_after_api_list_addons', $this );

		$this->output();

	} // list_addons

	/**
	 * Retrieve addon options.
	 *
	 * @since 1.4
	 * @return void
	 */
	public function addon_options() {

		$response = array();

		$event_package = ! empty( $this->request['package'] ) ? $this->request['package'] : false;
		$event_type    = ! empty( $this->request['event_type'] ) ? $this->request['event_type'] : false;
		$event_date    = ! empty( $this->request['event_date'] ) ? $this->request['event_date'] : false;
		$addons_type   = isset( $this->request['addons_type'] ) ? $this->request['addons_type'] : 'dropdown';
		$addons_cost   = isset( $this->request['addons_cost'] ) ? true : false;
		$selected      = isset( $this->request['selected'] ) ? explode( ',', $this->request['selected'] ) : '';
		$input_name    = ! empty( $this->request['field'] ) ? $this->request['field'] : 'event_addons';

		$func = 'mem_addons_' . $addons_type;
		$args = array(
			'name'       => $input_name,
			'package'    => $event_package,
			'event_type' => $event_type,
			'event_date' => $event_date,
			'cost'       => $addons_cost,
			'selected'   => $selected,
		);

		$addons = $func( $args, false );

		if ( ! empty( $addons ) ) {
			$response['type']   = 'success';
			$response['addons'] = $addons;
		} else {
			$response['type']   = 'success';
			$response['addons'] = 'dropdown' === $addons_type ?
				'<option value="0" disabled="disabled">' . __( 'No addons available', 'mobile-events-manager' ) . '</option>' :
				__( 'No addons available', 'mobile-events-manager' );
		}

		$this->data = array_merge( $this->data, $response );

		$this->output();

	} // addon_options

} // MEM_API
