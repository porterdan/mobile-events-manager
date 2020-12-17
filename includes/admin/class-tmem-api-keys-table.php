<?php
/**
 * API Key Table Class
 *
 * @package TMEM
 * @subpackage Admin/Tools/APIKeys
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TMEM_API_Keys_Table Class
 *
 * Renders the API Keys table
 *
 * @since 1.4
 */
class TMEM_API_Keys_Table extends WP_List_Table {

	/**
	 * Items per page
	 *
	 * @var int Number of items per page
	 * @since 1.4
	 */
	public $per_page = 30;

	/**
	 * Table results
	 *
	 * @var obj Query results
	 * @since 1.4
	 */
	private $keys;

	/**
	 * Get things started
	 *
	 * @since 1.4
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'API Key', 'mobile-events-manager' ),
				'plural'   => __( 'API Keys', 'mobile-events-manager' ),
				'ajax'     => false,
			)
		);

		$this->query();

	} // __construct

	/**
	 * Gets the name of the primary column.
	 *
	 * @since 1.4
	 * @access protected
	 *
	 * @return str Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'user';
	} // get_primary_column_name

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @access public
	 * @since 1.4
	 *
	 * @param arr $item Contains all the data of the keys.
	 * @param str $column_name The name of the column.
	 *
	 * @return str Column Name
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	} // column_default

	/**
	 * Displays the public key rows
	 *
	 * @access public
	 * @since 1.4
	 *
	 * @param arr $item Contains all the data of the keys.
	 * @param str $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_key( $item ) {
		return '<input type="text" class="large-text" value="' . esc_attr( $item['key'] ) . '" readonly="readonly" />';
	} // column_key

	/**
	 * Displays the token rows
	 *
	 * @access public
	 * @since 1.4
	 *
	 * @param arr $item Contains all the data of the keys.
	 * @param str $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_token( $item ) {
		return '<input type="text" class="large-text" value="' . esc_attr( $item['token'] ) . '" readonly="readonly" />';
	} // column_token

	/**
	 * Displays the secret key rows
	 *
	 * @access public
	 * @since 1.4
	 *
	 * @param arr $item Contains all the data of the keys.
	 * @param str $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_secret( $item ) {
		return '<input type="text" class="large-text" value="' . esc_attr( $item['secret'] ) . '" readonly="readonly" />';
	} // column_secret

	/**
	 * Renders the column for the user field
	 *
	 * @access public
	 * @since 1.4
	 * @param str $item renders the column.
	 */
	public function column_user( $item ) {

		$actions = array();

		$actions['reissue'] = sprintf(
			'<a href="%s" class="tmem-regenerate-api-key">%s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'user_id'          => $item['id'],
							'tmem-action'      => 'process_api_key',
							'tmem_api_process' => 'regenerate',
						)
					),
					'tmem-api-nonce',
					'api_nonce'
				)
			),
			__( 'Reissue', 'mobile-events-manager' )
		);

		$actions['revoke'] = sprintf(
			'<a href="%s" class="tmem-revoke-api-key tmem-delete">%s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						array(
							'user_id'          => $item['id'],
							'tmem-action'      => 'process_api_key',
							'tmem_api_process' => 'revoke',
						)
					),
					'tmem-api-nonce',
					'api_nonce'
				)
			),
			__( 'Revoke', 'mobile-events-manager' )
		);

		$actions = apply_filters( 'tmem_api_row_actions', array_filter( $actions ) );

		return sprintf( '%1$s %2$s', $item['user'], $this->row_actions( $actions ) );

	} // column_user

	/**
	 * Retrieve the table columns
	 *
	 * @access public
	 * @since 1.4
	 * @return arr $columns Array of all the list table columns
	 */
	public function get_columns() {
		$columns = array(
			'user'   => __( 'User', 'mobile-events-manager' ),
			'key'    => __( 'Public Key', 'mobile-events-manager' ),
			'token'  => __( 'Token', 'mobile-events-manager' ),
			'secret' => __( 'Secret Key', 'mobile-events-manager' ),
		);

		return $columns;
	}

	/**
	 * Display the key generation form
	 *
	 * @access public
	 * @since 1.4
	 * @param str $which display key gen form.
	 * @return void
	 */
	public function extra_tablenav( $which = '' ) {

		if ( 'top' != $which ) {
			return;
		}

		?>
		<form id="api-key-generate-form" method="post" action="<?php echo esc_url_raw( admin_url( 'edit.php?post_type=tmem-event&page=tmem-tools&tab=api_keys' ) ); ?>">
			<?php tmem_admin_action_field( 'process_api_key' ); ?>
			<input type="hidden" name="tmem_api_process" value="generate" />
			<?php wp_nonce_field( 'tmem-api-nonce', 'api_nonce' ); ?>
			<?php
			echo esc_attr(
				TMEM()->html->users_dropdown(
					array(
						'name'             => 'user_id',
						'chosen'           => true,
						'show_option_all'  => false,
						'show_option_none' => 'Select a User',
						'no dropdown items',
						'mobile-events-manager',
					)
				)
			);
			?>
			<?php submit_button( __( 'Generate New API Keys', 'mobile-events-manager' ), 'secondary', 'submit', false ); ?>
		</form>
		<?php

	} // extra_tablenav

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 1.4
	 * @access protected
	 * @param str $which generate table nav.
	 */
	protected function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
		<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
		?>
			<br class="clear" />
		</div>
		<?php

	} // display_tablenav

	/**
	 * Retrieve the current page number
	 *
	 * @access public
	 * @since 1.4
	 * @return int Current page number
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	} // get_paged

	/**
	 * Performs the key query
	 *
	 * @access public
	 * @since 1.4
	 */
	public function query() {

		$users = get_users(
			array(
				'meta_value' => 'tmem_user_secret_key',
				'number'     => $this->per_page,
				'offset'     => $this->per_page * ( $this->get_paged() - 1 ),
			)
		);

		$keys = array();

		foreach ( $users as $user ) {

			$keys[ $user->ID ]['id']    = $user->ID;
			$keys[ $user->ID ]['email'] = $user->user_email;
			$keys[ $user->ID ]['user']  = '<a href="' . add_query_arg( 'user_id', $user->ID, 'user-edit.php' ) . '"><strong>' . $user->user_login . '</strong></a>';

			$keys[ $user->ID ]['key']    = TMEM()->api->get_user_public_key( $user->ID );
			$keys[ $user->ID ]['secret'] = TMEM()->api->get_user_secret_key( $user->ID );
			$keys[ $user->ID ]['token']  = TMEM()->api->get_token( $user->ID );

		}

		return $keys;

	} // query

	/**
	 * Retrieve count of total users with keys
	 *
	 * @access public
	 * @since 1.4
	 * @return int
	 */
	public function total_items() {
		global $wpdb;

		if ( ! get_transient( 'tmem-total-api-keys' ) ) {
			$total_items = $wpdb->get_var( "SELECT count(user_id) FROM $wpdb->usermeta WHERE meta_key = 'tmem_user_secret_key'" );

			set_transient( 'tmem-total-api-keys', $total_items, 60 * 60 );
		}

		return get_transient( 'tmem-total-api-keys' );
	} // total_items

	/**
	 * Message displayed when no API keys are defined.
	 *
	 * @access public
	 * @since 1.4
	 */
	public function no_items() {
		esc_html_e( 'No API keys have been generated.', 'mobile-events-manager' );
	} // no_items

	/**
	 * Setup the final data for the table
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function prepare_items() {
		$columns = $this->get_columns();

		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable, 'id' );

		$data = $this->query();

		$total_items = $this->total_items();

		$this->items = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $total_items / $this->per_page ),
			)
		);
	} // prepare_items

} // TMEM_API_Keys_Table
