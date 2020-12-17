<?php
/**
 * The TMEM Content tags API.
 * Taken from Easy Digital Downloads.
 * Content tags are phrases wrapped in { } placed in HTML or email content
 * that are searched and replaced with TMEM content.
 *
 * Examples:
 * {event_name}
 * {client_fullname}
 *
 * To replace tags in content, use: tmem_do_content_tags( $content, $event_id, $client_id );
 *
 * To add tags, use: tmem_add_content_tag( $tag, $description, $func ). Be sure to wrap tmem_add_content_tag()
 * in a function hooked to the 'tmem_add_content_tags' action
 *
 * @package TMEM
 * @subpackage Content
 * @since 1.3
 * @category TMEM_Content_Tags
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TMEM_Content_Tags {

	/**
	 * Container for storing all tags
	 *
	 * @since 1.3
	 * @var array
	 */
	private $tags;

	/**
	 * Event ID
	 *
	 * @since 1.3
	 * @var string
	 */
	private $event_id;

	/**
	 * Client ID
	 *
	 * @since 1.3
	 * @var string
	 */
	private $client_id;

	/**
	 * Add a content tag.
	 *
	 * @since 1.3
	 *
	 * @param str $tag Content tag to be replace in content.
	 * @param str $description Short description of what content is provided from tag.
	 * @param str $func Hook to run when content tag is found.
	 */
	public function add( $tag, $description, $func ) {
		if ( is_callable( $func ) ) {
			$this->tags[ $tag ] = array(
				'tag'         => $tag,
				'description' => $description,
				'func'        => $func,
			);
		}
	} // add

	/**
	 * Remove a content tag.
	 *
	 * @since 1.3
	 *
	 * @param str $tag Content tag to remove hook from.
	 */
	public function remove( $tag ) {
		unset( $this->tags[ $tag ] );
	} // remove

	/**
	 * Deprecated tags.
	 *
	 * Remap deprecated tags to replacement tags to ensure backwards compatibility.
	 *
	 * @since 1.5
	 * @param str $tag deprecated tags.
	 * @return array Array of deprecated tags with values of replacement tags
	 */
	public function maybe_deprecated( $tag ) {
		$deprecated_tags = array(
			'dj_email' => 'employee_email',
		);

		if ( array_key_exists( $tag, $deprecated_tags ) ) {
			$tag = $deprecated_tags[ $tag ];
		}

		return $tag;
	} // maybe_deprecated

	/**
	 * Check if $tag is a registered content tag.
	 *
	 * @since 1.3
	 *
	 * @param str $tag Content tag that will be searched.
	 *
	 * @return bool
	 */
	public function content_tag_exists( $tag ) {
		$tag = $this->maybe_deprecated( $tag );

		return array_key_exists( $tag, $this->tags );
	} // content_tag_exists

	/**
	 * Returns a list of all content tags.
	 *
	 * @since 1.3
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags;
	} // get_tags

	/**
	 * Search content for tags and filter content tags through their hooks.
	 *
	 * @param str $content Content to search for tags.
	 * @param int $event_id The event id.
	 * @param int $client_id The event id.
	 *
	 * @since 1.3
	 *
	 * @return str Content with tags filtered out.
	 */
	public function do_tags( $content, $event_id, $client_id ) {
		// Check if there is at least one tag added.
		if ( empty( $this->tags ) || ! is_array( $this->tags ) ) {
			return $content;
		}

		$this->event_id  = $event_id;
		$this->client_id = $client_id;

		$new_content = preg_replace_callback( '/{([A-z0-9()\-\_]+)}/s', array( $this, 'do_tag' ), $content );

		$this->event_id  = null;
		$this->client_id = null;

		return $new_content;
	} // do_tags

	/**
	 * Do a specific tag, this function should not be used. Please use tmem_do_content_tags instead.
	 *
	 * @since 1.3
	 *
	 * @param str $m Content.
	 *
	 * @return mixed
	 */
	public function do_tag( $m ) {
		// Get tag. Force to lower case for backwards compatibility.
		$tag = strtolower( $m[1] );

		// Return tag if tag not set.
		if ( ! $this->content_tag_exists( $tag ) ) {
			return $m[0];
		}

		return call_user_func( $this->tags[ $tag ]['func'], $this->event_id, $this->client_id, $tag );
	} // do_tag
} // TMEM_Content_Tags

/**
 * Add a content tag.
 *
 * @since 1.3
 *
 * @param str $tag Content tag to be replace in content.
 * @param str $description Short description of what content is provided from tag.
 * @param str $func Callable hook to run when content tag is found.
 */
function tmem_add_content_tag( $tag, $description, $func ) {
	TMEM()->content_tags->add( $tag, $description, $func );
} // tmem_add_content_tag

/**
 * Remove a content tag.
 *
 * @since 1.3
 *
 * @param str $tag Content tag to remove hook from.
 */
function tmem_remove_content_tag( $tag ) {
	TMEM()->content_tags->remove( $tag );
} // tmem_remove_content_tag

/**
 * Check if $tag is a registered content tag.
 *
 * @since 1.3
 *
 * @param str $tag Content tag that will be searched.
 *
 * @return bool
 */
function tmem_content_tag_exists( $tag ) {
	return TMEM()->content_tags->content_tag_exists( $tag );
} // tmem_content_tag_exists

/**
 * Get all content tags.
 *
 * @since 1.3
 *
 * @return arr
 */
function tmem_get_content_tags() {
	return TMEM()->content_tags->get_tags();
} // tmem_get_content_tags

/**
 * Get a formatted HTML list of all available content tags.
 *
 * @since 1.3
 *
 * @return str
 */
function tmem_get_content_tags_list() {
	// The list.
	$list = '';

	// Get all tags.
	$content_tags = tmem_get_content_tags();

	// Check.
	if ( count( $content_tags ) > 0 ) {
		// Loop.
		foreach ( $content_tags as $content_tag ) {

			// Do not list deprecated tags.
			if ( TMEM()->content_tags->maybe_deprecated( $tag ) !== $tag ) {
				continue;
			}

			// Add email tag to list.
			$list .= '{' . $content_tag['tag'] . '} - ' . $content_tag['description'] . '<br/>';
		}
	}

	// Return the list of tags.
	return $list;
} // tmem_get_content_tags_list

/**
 * Search content for tags and filter content tags through their hooks.
 *
 * @param str $content Required: Content to search for tags.
 * @param int $event_id Optional: The event_id.
 * @param int $client_id Optional: The event_id.
 *
 * @since 1.3
 *
 * @return str Content with content tags filtered out.
 */
function tmem_do_content_tags( $content, $event_id = '', $client_id = '' ) {
	// Replace all tags.
	$content = TMEM()->content_tags->do_tags( $content, $event_id, $client_id );

	// Return content.
	return $content;
} // tmem_do_content_tags

/**
 * Load content tags
 *
 * @since 1.3
 */
function tmem_load_content_tags() {
	do_action( 'tmem_add_content_tags' );
} // tmem_load_content_tags
add_action( 'init', 'tmem_load_content_tags', -999 );

/**
 * Add the default TMEM content tags.
 *
 * @since 1.3
 */
function tmem_setup_content_tags() {
	// Setup default tags array.
	$content_tags = array(
		array(
			'tag'         => 'additional_cost',
			'description' => __( 'The total additional cost for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_additional_cost',
		),
		array(
			'tag'         => 'client_notes',
			'description' => __( 'The client notes associated with the event, viewable in the client area', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_notes',
		),
		array(
			'tag'         => 'admin_notes',
			'description' => __( 'The admin notes associated with the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_admin_notes',
		),
		array(
			'tag'         => 'admin_url',
			'description' => __( 'The admin URL to WordPress', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_admin_url',
		),
		array(
			'tag'         => 'application_home',
			'description' => __( 'The Client Zone application URL', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_application_home',
		),
		array(
			'tag'         => 'application_name',
			'description' => __( 'The name of this TMEM application', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_application_name',
		),
		array(
			'tag'         => 'artist_label',
			'description' => __( 'The label defined for artists (default is DJ).', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_artist_label',
		),
		array(
			'tag'         => 'available_addons',
			'description' => __( 'The list of add-ons available. No price. If an event can be referenced, only lists add-ons not already assigned to the event, or included in the event package', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_available_addons',
		),
		array(
			'tag'         => 'available_addons_cost',
			'description' => __( 'The list of add-ons available. With price. If an event can be referenced, only lists add-ons not already assigned to the event, or included in the event package', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_available_addons_cost',
		),
		array(
			'tag'         => 'available_packages',
			'description' => __( 'The list of packages available. No price', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_available_packages',
		),
		array(
			'tag'         => 'available_packages_cost',
			'description' => __( 'The list of packages available. With price', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_available_packages_cost',
		),
		array(
			'tag'         => 'balance',
			'description' => __( 'The remaining balance owed for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_balance',
		),
		array(
			'tag'         => 'balance_label',
			'description' => __( 'The label used for balance payments', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_balance_label',
		),
		array(
			'tag'         => 'client_email',
			'description' => __( 'The event clients email address', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_email',
		),
		array(
			'tag'         => 'client_firstname',
			'description' => __( 'The event clients first name', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_firstname',
		),
		array(
			'tag'         => 'client_full_address',
			'description' => __( 'The event clients full address', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_full_address',
		),
		array(
			'tag'         => 'client_fullname',
			'description' => __( 'The event clients full name', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_fullname',
		),
		array(
			'tag'         => 'client_lastname',
			'description' => __( 'The event clients last name', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_lastname',
		),
		array(
			'tag'         => 'client_password',
			/* Translators: %s: site name */
			'description' => sprintf( esc_html__( 'The event clients password for logging into %s', 'mobile-events-manager' ), 'Client Zone' ),
			'function'    => 'tmem_content_tag_client_password',
		),
		array(
			'tag'         => 'client_primary_phone',
			'description' => __( 'The event clients primary phone number', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_primary_phone',
		),
		array(
			'tag'         => 'client_alt_phone',
			'description' => __( 'The event clients alternative phone number', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_client_alt_phone',
		),
		array(
			'tag'         => 'client_username',
			/* Translators: %s: site name */
			'description' => sprintf( esc_html__( 'The event clients username for logging into %s', 'mobile-events-manager' ), 'Client Zone' ),
			'function'    => 'tmem_content_tag_client_username',
		),
		array(
			'tag'         => 'company_name',
			'description' => __( 'The name of your company', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_company_name',
		),
		array(
			'tag'         => 'contact_page',
			'description' => __( 'The URL to your websites contact page', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contact_page',
		),
		array(
			'tag'         => 'contract_date',
			'description' => __( "The date the event contract was signed, or today's date", 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contract_date',
		),
		array(
			'tag'         => 'contract_id',
			'description' => __( 'The contract / event ID', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contract_id',
		),
		array(
			'tag'         => 'contract_signatory',
			'description' => __( 'The name of the person who signed the contract', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contract_signatory',
		),
		array(
			'tag'         => 'contract_signatory_ip',
			'description' => __( 'The IP address recorded during contract signing', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contract_signatory_ip',
		),
		array(
			'tag'         => 'contract_url',
			'description' => __( 'The URL for the client to access their event contract', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_contract_url',
		),
		array(
			'tag'         => 'ddmmyyyy',
			'description' => __( 'Todays date in shortdate format', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_ddmmyyyy',
		),
		array(
			'tag'         => 'deposit',
			'description' => __( 'The deposit amount for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_deposit',
		),
		array(
			'tag'         => 'deposit_label',
			'description' => __( 'The label used for deposit payments', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_deposit_label',
		),
		array(
			'tag'         => 'deposit_remaining',
			/* Translators: %1: deposit %2: event type */
			'description' => sprintf( esc_html__( 'The remaining %1$s value due for the %2$s', 'mobile-events-manager' ), tmem_get_deposit_label(), esc_html( tmem_get_label_singular( true ) ) ),
			'function'    => 'tmem_content_tag_deposit_remaining',
		),
		array(
			'tag'         => 'deposit_status',
			'description' => __( "The deposit payment status. Generally 'Paid' or 'Due'", 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_deposit_status',
		),
		array(
			'tag'         => 'discount',
			'description' => __( 'The total discount applied to the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_discount',
		),
		array(
			'tag'         => 'dj_email',
			'description' => __( 'The email address of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_email',
		),
		array(
			'tag'         => 'dj_firstname',
			'description' => __( 'The first name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_firstname',
		),
		array(
			'tag'         => 'dj_lastname',
			'description' => __( 'The last name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_lastname',
		),
		array(
			'tag'         => 'dj_fullname',
			'description' => __( 'The full name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_fullname',
		),
		array(
			'tag'         => 'dj_notes',
			'description' => __( 'The DJ notes that have been entered against the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_dj_notes',
		),
		array(
			'tag'         => 'dj_primary_phone',
			'description' => __( 'The primary phone number of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_primary_phone',
		),
		array(
			'tag'         => 'dj_setup_date',
			'description' => __( 'The setup date for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_dj_setup_date',
		),
		array(
			'tag'         => 'dj_setup_time',
			'description' => __( 'The setup time for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_dj_setup_time',
		),
		array(
			'tag'         => 'employee_address',
			'description' => __( 'The mailing address of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_address',
		),
		array(
			'tag'         => 'employee_email',
			'description' => __( 'The email address of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_email',
		),
		array(
			'tag'         => 'employee_firstname',
			'description' => __( 'The first name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_firstname',
		),
		array(
			'tag'         => 'employee_fullname',
			'description' => __( 'The full name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_fullname',
		),
		array(
			'tag'         => 'employee_lastname',
			'description' => __( 'The last name of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_lastname',
		),
		array(
			'tag'         => 'employee_primary_phone',
			'description' => __( 'The primary phone number of the events assigned primary employee', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_employee_primary_phone',
		),
		array(
			'tag'         => 'end_date',
			'description' => __( 'The date the event completes', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_end_date',
		),
		array(
			'tag'         => 'end_time',
			'description' => __( 'The time the event completes', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_end_time',
		),
		array(
			'tag'         => 'event_addons',
			'description' => __( 'The add-ons included with the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_addons',
		),
		array(
			'tag'         => 'event_addons_cost',
			'description' => __( 'The add-ons included with the event, with costs', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_addons_cost',
		),
		array(
			'tag'         => 'event_date',
			'description' => __( 'The date of the event in long format', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_date',
		),
		array(
			'tag'         => 'event_date_short',
			'description' => __( 'The date of the event in short format', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_date_short',
		),
		array(
			'tag'         => 'event_description',
			'description' => __( 'The contents of the event description field', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_description',
		),
		array(
			'tag'         => 'event_duration',
			'description' => __( 'The duration of the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_duration',
		),
		array(
			'tag'         => 'event_employees',
			'description' => __( 'The list of employees working their event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_employees',
		),
		array(
			'tag'         => 'event_employees_roles',

			'description' => __( 'The list of employees working their event and their assigned role', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_employees_roles',
		),
		array(
			'tag'         => 'event_name',
			'description' => __( 'The assigned name of the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_name',
		),
		array(
			'tag'         => 'event_package',
			/* Translators: %s: client/customer */
			'description' => sprintf( esc_html__( 'The package associated with the %s or "No Package".', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
			'function'    => 'tmem_content_tag_event_package',
		),
		array(
			'tag'         => 'event_package_cost',
			/* Translators: %s: client/customer */
			'description' => sprintf( esc_html__( 'The package associated with the %s and its cost, or "No Package".', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
			'function'    => 'tmem_content_tag_event_package_cost',
		),
		array(
			'tag'         => 'event_package_description',
			/* Translators: %s: client/customer */
			'description' => sprintf( esc_html__( 'The description of the package associated with the %s.', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
			'function'    => 'tmem_content_tag_event_package_description',
		),
		array(
			'tag'         => 'event_status',
			'description' => __( 'The current status of the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_status',
		),
		array(
			'tag'         => 'event_type',
			'description' => __( 'The type of event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_type',
		),
		array(
			'tag'         => 'event_url',
			'description' => __( 'The URL of the event page', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_url',
		),
		array(
			'tag'         => 'event_admin_url',
			'description' => __( 'The URL of the event admin page', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_event_admin_url',
		),
		array(
			'tag'         => 'final_balance',
			'description' => __( 'The final balance payment for an event which is the total cost minus deposit, even if the deposit is unpaid.', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_final_balance',
		),
		array(
			'tag'         => 'guest_playlist_url',
			'description' => __( 'The URL to your event playlist page for guests', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_guest_playlist_url',
		),
		array(
			'tag'         => 'part_payment_label',
			'description' => 'The label used for Part Payments. i.e. Not the full amount',
			'function'    => 'tmem_content_tag_part_payment_label',
		),
		array(
			'tag'         => 'payment_history',
			'description' => __( 'An overview of payments made by the client for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_payment_history',
		),
		array(
			'tag'         => 'payment_url',
			'description' => __( 'The URL to your payments page', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_payment_url',
		),
		array(
			'tag'         => 'pdf_pagebreak',
			'description' => __( 'Adds a page break into a PDF document', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_pdf_pagebreak',
		),
		array(
			'tag'         => 'playlist_close',
			'description' => __( 'The number of days before the event that the playlist closes', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_playlist_close',
		),
		array(
			'tag'         => 'playlist_duration',
			/* Translators: %s: client/customer */
			'description' => sprintf( esc_html__( 'The approximate length of the %s playlist', 'mobile-events-manager' ), esc_html( tmem_get_label_singular( true ) ) ),
			'function'    => 'tmem_content_tag_playlist_duration',
		),
		array(
			'tag'         => 'playlist_url',
			'description' => __( 'The URL to your event playlist page for clients', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_playlist_url',
		),
		array(
			'tag'         => 'quotes_url',
			'description' => __( 'The URL to your online quotes page', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_quotes_url',
		),
		array(
			'tag'         => 'start_time',
			'description' => __( 'The event start time', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_start_time',
		),
		array(
			'tag'         => 'total_cost',
			'description' => __( 'The total cost of the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_total_cost',
		),
		array(
			'tag'         => 'travel_cost',
			'description' => __( 'The cost of travel for the event', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_travel_cost',
		),
		array(
			'tag'         => 'venue',
			'description' => __( 'The name of the event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue',
		),
		array(
			'tag'         => 'venue_contact',
			'description' => __( 'The name of the contact at event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_contact',
		),
		array(
			'tag'         => 'venue_details',
			'description' => __( 'Details stored for the venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_details',
		),
		array(
			'tag'         => 'venue_email',
			'description' => __( 'The email address of the event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_email',
		),
		array(
			'tag'         => 'venue_full_address',
			'description' => __( 'The full address of the event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_full_address',
		),
		array(
			'tag'         => 'venue_notes',
			'description' => __( 'Notes associated with the event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_notes',
		),
		array(
			'tag'         => 'venue_telephone',
			'description' => __( 'The phone number of the event venue', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_venue_telephone',
		),
		array(
			'tag'         => 'website_url',
			'description' => __( 'The URL to your website', 'mobile-events-manager' ),
			'function'    => 'tmem_content_tag_website_url',
		),
	);

	// Apply tmem_content_tags filter.
	$content_tags = apply_filters( 'tmem_content_tags', $content_tags );

	// Add content tags.
	foreach ( $content_tags as $content_tag ) {
		tmem_add_content_tag( $content_tag['tag'], $content_tag['description'], $content_tag['function'] );
	}
} // tmem_setup_content_tags
add_action( 'tmem_add_content_tags', 'tmem_setup_content_tags' );

/**
 * Content tag: admin_url.
 * The admin url to this WordPress instance.
 *
 * @return str The WP instance admin URL.
 */
function tmem_content_tag_admin_url() {
	return admin_url();
} // tmem_content_tag_admin_url

/**
 * Content tag: application_home.
 * The url to the TMEM Client Zone home page for this instance.
 *
 * @param str $url client zone url.
 *
 * @return str The URL to the Client Zone home page.
 */
function tmem_content_tag_application_home() {
	$url = tmem_get_formatted_url( tmem_get_option( 'app_home_page' ), false );
	$url = apply_filters( 'tmem_tag_clientzone_url', $url );

	return $url;
} // tmem_content_tag_application_home

/**
 * Content tag: application_name.
 * The name given to this Client Zone instance.
 *
 * @return str The customised name of the Client Zone.
 */
function tmem_content_tag_application_name() {
	return tmem_get_application_name();
} // tmem_content_tag_application_name

/**
 * Content tag: artist_label.
 * The label for the primary role (default DJ).
 *
 * @return str The customised name of the primary employee role.
 */
function tmem_content_tag_artist_label() {
	return tmem_get_option( 'artist', __( 'DJ', 'mobile-events-manager' ) );
} // tmem_content_tag_artist_label

/**
 * Content tag: available_addons.
 * The list of add-ons available with line breaks. No price.
 * If an event can be referenced, only lists add-ons not already assigned to the event,
 * or included within the event package
 *
 * @param int $event_id Event ID if applicable.
 * @return str The list of available addo-ns. No cost.
 */
function tmem_content_tag_available_addons( $event_id = '' ) {
	return tmem_list_available_addons();
} // tmem_content_tag_available_addons

/**
 * Content tag: available_addons_cost.
 * The list of add-ons available with line breaks. With price.
 * If an event can be referenced, only lists add-ons not already assigned to the event,
 * or included within the event package
 *
 * @param int $event_id The event ID.
 * @return str The list of available add-ons. With cost.
 */
function tmem_content_tag_available_addons_cost( $event_id = '' ) {
	return tmem_list_available_addons( 0, true );
} // tmem_content_tag_available_addons_cost

/**
 * Content tag: available_packages.
 * The list of available packages.
 *
 * @return str The list of available packages. No cost.
 */
function tmem_content_tag_available_packages() {
	return tmem_list_available_packages();
} // tmem_content_tag_available_packages

/**
 * Content tag: available_packages_cost.
 * The list of available packages with cost.
 *
 * @return str The list of available packages. With cost.
 */
function tmem_content_tag_available_packages_cost() {
	return tmem_list_available_packages( 0, true );
} // tmem_content_tag_available_packages_cost

/**
 * Content tag: company_name.
 * The name of the company running this TMEM instance.
 *
 * @return str The name of the company running this TMEM instance.
 */
function tmem_content_tag_company_name() {
	return tmem_get_option( 'company_name' );
} // tmem_content_tag_company_name

/**
 * Content tag: contact_page.
 * The contact page.
 *
 * @return str The URL of the contact page.
 */
function tmem_content_tag_contact_page() {
	$url = tmem_get_formatted_url( tmem_get_option( 'contact_page' ), false );
	$url = apply_filters( 'tmem_tag_contact_page_url', $url );

	return $url;
} // tmem_content_tag_contact_page

/**
 * Content tag: ddmmyyyy.
 * The date in short format.
 *
 * @return str The current date in short format.
 */
function tmem_content_tag_ddmmyyyy() {
	return tmem_format_short_date();
} // tmem_content_tag_ddmmyyyy

/**
 * Content tag: website_url.
 * The website URL.
 *
 * @return str The URL of the website hosting TMEM.
 */
function tmem_content_tag_website_url() {
	return home_url();
} // tmem_content_tag_website_url

/**
 * Content tag: client_firstname.
 * The first name of the client.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The first name of the client.
 */
function tmem_content_tag_client_firstname( $event_id = '', $client_id = '' ) {

	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$first_name = '';

	if ( ! empty( $user_id ) ) {
		$first_name = tmem_get_client_firstname( $user_id );
	}

	return $first_name;

} // tmem_content_tag_client_firstname

/**
 * Content tag: client_lastname.
 * The last name of the client.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The last name of the client.
 */
function tmem_content_tag_client_lastname( $event_id = '', $client_id = '' ) {

	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$last_name = '';

	if ( ! empty( $user_id ) ) {
		$last_name = tmem_get_client_lastname( $user_id );
	}

	return $last_name;

} // tmem_content_tag_client_lastname

/**
 * Content tag: client_fullname.
 * The full name of the client.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The full name (display name) of the client.
 */
function tmem_content_tag_client_fullname( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$full_name = '';

	if ( ! empty( $user_id ) ) {
		$full_name = tmem_get_client_display_name( $user_id );
	}

	return $full_name;
} // tmem_content_tag_client_fullname

/**
 * Content tag: client_full_address.
 * The full address of the client.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The address of the client.
 */
function tmem_content_tag_client_full_address( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$address = '';

	if ( ! empty( $user_id ) ) {
		$address = tmem_get_client_full_address( $user_id );
	}

	return $address;
} // tmem_content_tag_client_full_address

/**
 * Content tag: client_email.
 * The email address of the client.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The email address of the client.
 */
function tmem_content_tag_client_email( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$email = '';

	if ( ! empty( $user_id ) ) {
		$email = tmem_get_client_email( $user_id );
	}

	return $email;
} // tmem_content_tag_client_email

/**
 * Content tag: client_primary_phone.
 * The client phone number.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The primary phone number of the client.
 */
function tmem_content_tag_client_primary_phone( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$primary_phone = '';

	if ( ! empty( $user_id ) ) {
		$primary_phone = tmem_get_client_phone( $user_id );
	}

	return $primary_phone;
} // tmem_content_tag_client_primary_phone

/**
 * Content tag: client_alt_phone.
 * The client phone number.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The alternative phone number of the client.
 */
function tmem_content_tag_client_alt_phone( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$alt_phone = '';

	if ( ! empty( $user_id ) ) {
		$alt_phone = tmem_get_client_alt_phone( $user_id );
	}

	return $alt_phone;
} // tmem_content_tag_client_alt_phone

/**
 * Content tag: client_username.
 * The client login.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 *
 * @return str The login name of the client.
 */
function tmem_content_tag_client_username( $event_id = '', $client_id = '' ) {
	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = tmem_get_event_client_id( $event_id );
	} else {
		$user_id = '';
	}

	$login = '';

	if ( ! empty( $user_id ) ) {
		$login = tmem_get_client_login( $user_id );
	}

	return $login;
} // tmem_content_tag_client_username


/**
 * Content tag: client_notes.
 * Client notes associated with event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Event client notes.
 *
 * Added in 1.0.4
 */
function tmem_content_tag_client_notes( $event_id = '' ) {
	return ! empty( $event_id ) ? get_post_meta( $event_id, '_tmem_event_notes', true ) : '';
} // mem_content_tag_client_notes


/**
 * Content tag: client_password.
 * The client password. Reset the password and return the new password.
 *
 * @param int $event_id The event ID.
 * @param int $client_id client ID.
 * @return str The login password for the client.
 */
function tmem_content_tag_client_password( $event_id = '', $client_id = '' ) {

	if ( ! empty( $client_id ) ) {
		$user_id = $client_id;
	} elseif ( ! empty( $event_id ) ) {
		$user_id = get_post_meta( $event_id, '_tmem_event_client', true );
	} else {
		$user_id = '';
	}

	$return = sprintf(
		/* Translators: %s: is website URL */
		__( 'Please <a href="%s">click here</a> to reset your password', 'mobile-events-manager' ),
		home_url( '/wp-login.php?action=lostpassword' )
	);

	$reset = get_user_meta( $user_id, 'tmem_pass_action', true );

	if ( ! empty( $reset ) ) {
		if ( TMEM_DEBUG === true ) {
			TMEM()->debug->log_it( '	-- Password reset for user ' . $user_id );
		}

		$reset = wp_generate_password( tmem_get_option( 'pass_length', 8 ), tmem_get_option( 'complex_passwords', true ) );

		wp_set_password( $reset, $user_id );

		delete_user_meta( $user_id, 'tmem_pass_action' );

		$return = $reset;
	}

	return $return;
} // tmem_content_tag_client_password

/**
 * Content tag: admin_notes.
 * Admin notes associated with event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Event admin notes.
 */
function tmem_content_tag_admin_notes( $event_id = '' ) {
	return ! empty( $event_id ) ? get_post_meta( $event_id, '_tmem_event_admin_notes', true ) : '';
} // tmem_content_tag_admin_notes

/**
 * Content tag: balance.
 * The remaining balance for the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The event payment balance.
 */
function tmem_content_tag_balance( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$rcvd = TMEM()->txns->get_transactions( $event_id, 'tmem-income' );
	$cost = get_post_meta( $event_id, '_tmem_event_cost', true );

	if ( ! empty( $rcvd ) && '0.00' !== $rcvd && ! empty( $cost ) ) {
		return tmem_currency_filter( tmem_format_amount( ( $cost - $rcvd ) ) );
	}

	return tmem_currency_filter( tmem_format_amount( $cost ) );
} // tmem_content_tag_balance

/**
 * Content tag: balance_label.
 * The label used for the balance label term.
 *
 * @return str The label for balances.
 */
function tmem_content_tag_balance_label() {
	return tmem_get_balance_label();
} // tmem_content_tag_balance_label

/**
 * Content tag: contract_date.
 * The date of the contract. If the contract is signed, return the signing date.
 * Otherwise, return the current date.
 *
 * @param int $event_id The event ID.
 *
 * @return str The event contract date.
 */
function tmem_content_tag_contract_date( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$signed = get_post_meta( $event_id, '_tmem_event_contract_approved', true );

	if ( ! empty( $signed ) ) {
		$return = $return = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $signed ) );
	} else {
		$return = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ) );
	}

	return $return;
} // tmem_content_tag_contract_date

/**
 * Content tag: contract_id.
 * The event ID.
 *
 * @param int $event_id The event ID.
 *
 * @return str The event contract ID.
 */
function tmem_content_tag_contract_id( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return get_the_title( $event_id );
} // tmem_content_tag_contract_id

/**
 * Content tag: contract_signatory.
 * The event ID.
 *
 * @param int $event_id The event ID.
 *
 * @return str The name of the person who signed the contract.
 */
function tmem_content_tag_contract_signatory( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_get_contract_signatory_name( $event_id );
} // tmem_content_tag_contract_signatory

/**
 * Content tag: contract_signatory_ip.
 * The event ID.
 *
 * @param int $event_id The event ID.
 *
 * @return str The IP address of the person who signed the contract.
 */
function tmem_content_tag_contract_signatory_ip( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_get_contract_signatory_ip( $event_id );
} // tmem_content_tag_contract_signatory_ip

/**
 * Content tag: contract_url.
 * The event contract URL for the client.
 *
 * @param int $event_id The event ID.
 *
 * @return str The URL to the client contract within Client Zone
 */
function tmem_content_tag_contract_url( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$url = tmem_get_formatted_url( tmem_get_option( 'contracts_page' ) ) . 'event_id=' . $event_id;
	$url = apply_filters( 'tmem_tag_event_contract_url', $url, $event_id );

	return $url;
} // tmem_content_tag_contract_url

/**
 * Content tag: additional_cost.
 *
 * @since 1.5
 * @param int $event_id The event ID.
 * @return string Total additional cost applied to event
 */
function tmem_content_tag_additional_cost( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$additional_cost = get_post_meta( $event_id, '_tmem_event_additional_cost', true );

	if ( ! $additional_cost ) {
		$additional_cost = 0;
	}

	return tmem_currency_filter( tmem_sanitize_amount( $additional_cost ) );
} // tmem_content_tag_additional_cost

/**
 * Content tag: deposit.
 * The required deposit amount.
 *
 * @param int $event_id The event ID.
 *
 * @return str The formatted event deposit amount
 */
function tmem_content_tag_deposit( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$deposit = get_post_meta( $event_id, '_tmem_event_deposit', true );

	if ( ! empty( $deposit ) ) {
		$return = tmem_currency_filter( tmem_format_amount( $deposit ) );
	} else {
		$return = '';
	}

	return $return;
} // tmem_content_tag_deposit

/**
 * Content tag: deposit_label.
 * The label used for deposit.
 *
 * @return str The chosen label for deposit
 */
function tmem_content_tag_deposit_label() {
	return tmem_get_deposit_label();
} // tmem_content_tag_deposit_label

/**
 * Content tag: deposit_remaining.
 * Value of deposit remaining to be paid.
 *
 * @param int $event_id The event ID.
 *
 * @return str The remaining amount to be paid towards the deposit.
 */
function tmem_content_tag_deposit_remaining( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_currency_filter( tmem_format_amount( tmem_get_event_remaining_deposit( $event_id ) ) );
} // tmem_content_tag_deposit_remaining

/**
 * Content tag: deposit_status.
 * Current status of the deposit.
 *
 * @param int $event_id The event ID.
 *
 * @return str The status of the deposit payment, or Due if no status is found.
 */
function tmem_content_tag_deposit_status( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = get_post_meta( $event_id, '_tmem_event_deposit_status', true );

	if ( empty( $return ) ) {
		$return = 'Due';
	}

	return $return;
} // tmem_content_tag_deposit_status

/**
 * Content tag: discount.
 *
 * @param int $event_id The event ID.
 *
 * @return str The event final balance (cost - unpaid deposit).
 */
function tmem_content_tag_discount( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$discount = get_post_meta( $event_id, '_tmem_event_discount', true );

	if ( ! $discount ) {
		$discount = 0;
	}

	return tmem_currency_filter( tmem_sanitize_amount( $discount ) );
} // tmem_content_tag_discount

/**
 * Content tag: dj_email.
 * Email address of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The email address of the primary employee assigned to the event.
 */
function tmem_content_tag_dj_email( $event_id = '' ) {

	return tmem_content_tag_employee_email( $event_id );

} // tmem_content_tag_dj_email

/**
 * Content tag: dj_firstname.
 * First name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The first name of the primary employee assigned to the event.
 */
function tmem_content_tag_dj_firstname( $event_id = '' ) {
	return tmem_content_tag_employee_firstname( $event_id );
} // tmem_content_tag_dj_firstname

/**
 * Content tag: dj_lastname.
 * Last name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The last name of the primary employee assigned to the event.
 */
function tmem_content_tag_dj_lastname( $event_id = '' ) {
	return tmem_content_tag_employee_lastname( $event_id );
} // tmem_content_tag_dj_lastname

/**
 * Content tag: dj_fullname.
 * Full name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The full name (display name) of the primary employee assigned to the event.
 */
function tmem_content_tag_dj_fullname( $event_id = '' ) {
	return tmem_content_tag_employee_fullname( $event_id );
} // tmem_content_tag_dj_fullname

/**
 * Content tag: dj_primary_phone.
 * DJ Notes associated with event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The notes tassociated with the event that are for the DJ.
 */
function tmem_content_tag_dj_primary_phone( $event_id = '' ) {
	return tmem_content_tag_employee_primary_phone( $event_id );
} // tmem_content_tag_dj_primary_phone

/**
 * Content tag: dj_notes.
 * DJ Notes associated with event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The notes tassociated with the event that are for the DJ.
 */
function tmem_content_tag_dj_notes( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return get_post_meta( $event_id, '_tmem_event_dj_notes', true );
} // tmem_content_tag_dj_notes

/**
 * Content tag: dj_setup_date.
 * The date to setup for the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The date for which the event needs to be setup.
 */
function tmem_content_tag_dj_setup_date( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = __( 'Not specified', 'mobile-events-manager' );

	$date = get_post_meta( $event_id, '_tmem_event_djsetup', true );

	if ( ! empty( $date ) ) {
		$return = gmdate( 'l, jS F Y', strtotime( $date ) );
	}

	return $return;
} // tmem_content_tag_dj_setup_date

/**
 * Content tag: dj_setup_time.
 * The time to setup for the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted time for which the event needs to be setup.
 */
function tmem_content_tag_dj_setup_time( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = __( 'Not specified', 'mobile-events-manager' );

	$time = get_post_meta( $event_id, '_tmem_event_djsetup_time', true );

	if ( ! empty( $time ) ) {
		$return = gmdate( tmem_get_option( 'time_format', 'H:i' ), strtotime( $time ) );
	}

	return $return;
} // tmem_content_tag_dj_setup_time

/**
 * Content tag: employee_address.
 * The address of the primary employee assigned to the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Address of employee.
 */
function tmem_content_tag_employee_address( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$employee_id = tmem_get_event_primary_employee_id( $event_id );
	$address     = array();

	if ( ! empty( $employee_id ) ) {
		$address = tmem_get_employee_address( $employee_id );
	}

	return is_array( $address ) ? implode( '<br />', $address ) : '';
} // tmem_content_tag_employee_address

/**
 * Content tag: employee_email.
 * Email address of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The email address of the primary employee assigned to the event.
 */
function tmem_content_tag_employee_email( $event_id = '' ) {

	if ( empty( $event_id ) ) {
		return;
	}

	$employee_id = tmem_get_event_primary_employee_id( $event_id );

	if ( ! empty( $employee_id ) ) {
		return tmem_get_employee_email( $employee_id );
	}

} // tmem_content_tag_employee_email

/**
 * Content tag: employee_firstname.
 * First name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The first name of the primary employee assigned to the event.
 */
function tmem_content_tag_employee_firstname( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$first_name = '';
	$user_id    = tmem_get_event_primary_employee_id( $event_id );

	if ( ! empty( $user_id ) ) {
		$first_name = tmem_get_employee_firstname( $user_id );
	}

	return $first_name;
} // tmem_content_tag_employee_firstname

/**
 * Content tag: employee_fullname.
 * Full name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The full name (display name) of the primary employee assigned to the event.
 */
function tmem_content_tag_employee_fullname( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$full_name = '';
	$user_id   = tmem_get_event_primary_employee_id( $event_id );

	if ( ! empty( $user_id ) ) {
		$full_name = tmem_get_employee_display_name( $user_id );
	}

	return $full_name;
} // tmem_content_tag_employee_fullname

/**
 * Content tag: employee_lastname.
 * Last name of primary employee assigned to event.
 *
 * @param int $event_id The event ID.
 * @return str The last name of the primary employee assigned to the event.
 */
function tmem_content_tag_employee_lastname( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$last_name = '';
	$user_id   = tmem_get_event_primary_employee_id( $event_id );

	if ( ! empty( $user_id ) ) {
		$last_name = tmem_get_employee_lastname( $user_id );
	}

	return $last_name;
} // tmem_content_tag_employee_lastname

/**
 * Content tag: employee_primary_phone.
 * DJ Notes associated with event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The notes tassociated with the event that are for the DJ.
 */
function tmem_content_tag_employee_primary_phone( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$phone   = '';
	$user_id = tmem_get_event_primary_employee_id( $event_id );

	if ( ! empty( $user_id ) ) {
		$phone = tmem_get_employee_phone( $user_id );
	}

	return $phone;
} // tmem_content_tag_employee_primary_phone

/**
 * Content tag: end_date.
 * The date the event completes.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted date the event finishes.
 */
function tmem_content_tag_end_date( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = '';

	$date = get_post_meta( $event_id, '_tmem_event_end_date', true );

	if ( ! empty( $date ) ) {
		$return = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $date ) );
	}

	return $return;
} // tmem_content_tag_end_date

/**
 * Content tag: end_time.
 * The time the event completes.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted time for when the event finishes.
 */
function tmem_content_tag_end_time( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = '';

	$time = get_post_meta( $event_id, '_tmem_event_finish', true );

	if ( ! empty( $time ) ) {
		$return = gmdate( tmem_get_option( 'time_format', 'H:i' ), strtotime( $time ) );
	}

	return $return;
} // tmem_content_tag_end_time

/**
 * Content tag: event_addons.
 * The add-ons attached to the event.
 *
 * @param int $event_id The event ID.
 * @return str The add-on names or "No addons are assigned to this event".
 */
function tmem_content_tag_event_addons( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_list_event_addons( $event_id );
} // tmem_content_tag_event_addons

/**
 * Content tag: event_addons_cost.
 * The add-ons attached to the event and their cost.
 *
 * @param int $event_id The event ID.
 * @return str The add-on names and cost or "No addons are assigned to this event".
 */
function tmem_content_tag_event_addons_cost( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_list_event_addons( $event_id, true );
} // tmem_content_tag_event_addons_cost

/**
 * Content tag: event_date.
 * The date of the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted long date of the event.
 */
function tmem_content_tag_event_date( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = '';

	$date = get_post_meta( $event_id, '_tmem_event_date', true );

	if ( ! empty( $date ) ) {
		$return = gmdate( get_option( 'date_format' ), strtotime( $date ) );
	}

	return $return;
} // tmem_content_tag_event_date

/**
 * Content tag: event_date_short.
 * The date of the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted short date of the event.
 */
function tmem_content_tag_event_date_short( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = '';

	$date = get_post_meta( $event_id, '_tmem_event_date', true );

	if ( ! empty( $date ) ) {
		$return = gmdate( tmem_get_option( 'short_date_format', 'd/m/Y' ), strtotime( $date ) );
	}

	return $return;
} // tmem_content_tag_event_date_short

/**
 * Content tag: event_description.
 * The event description as defined by the description field.
 *
 * @param int $event_id The event ID.
 *
 * @return str Contents of the event description field.
 */
function tmem_content_tag_event_description( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = get_post_meta( $event_id, '_tmem_event_notes', true );

	return $return;
} // tmem_content_tag_event_description

/**
 * Content tag: event_duration.
 * Duration of the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The duration of the event in hours, minutes.
 */
function tmem_content_tag_event_duration( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_event_duration( $event_id );
} // event_duration

/**
 * Content tag: event_employees.
 * List of event employees.
 *
 * @param int $event_id The event ID.
 *
 * @return str List of employees working the event.
 */
function tmem_content_tag_event_employees( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$employees = tmem_get_all_event_employees( $event_id );

	if ( empty( $employees ) ) {
		return;
	}

	foreach ( $employees as $employee_id => $employee_data ) {
		$event_employees[] = tmem_get_employee_display_name( $employee_id );
	}

	$return = implode( '<br />', $event_employees );

	return $return;
} // tmem_content_tag_event_employees

/**
 * Content tag: event_employees.
 * List of event employees.
 *
 * @param int $event_id The event ID.
 *
 * @return str List of employees working the event.
 */
function tmem_content_tag_event_employees_roles( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$employees = tmem_get_all_event_employees( $event_id );

	if ( empty( $employees ) ) {
		return;
	}

	foreach ( $employees as $employee_id => $employee_data ) {
		$event_employees[] = tmem_get_employee_display_name( $employee_id ) . ' - ' . $employee_data['role'];
	}

	$return = implode( '<br />', $event_employees );

	return $return;
} // tmem_content_tag_event_employees_roles

/**
 * Content tag: event_name.
 * The assigned event name.
 *
 * @param int $event_id The event ID.
 *
 * @return str Contents of the event name field.
 */
function tmem_content_tag_event_name( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return get_post_meta( $event_id, '_tmem_event_name', true );
} // tmem_content_tag_event_name

/**
 * Content tag: event_package.
 * The package attached to the event.
 *
 * @param int $event_id The event ID.
 * @return str The package name or "No Package".
 */
function tmem_content_tag_event_package( $event_id = '' ) {
	$return = __( 'No package is assigned to this event', 'mobile-events-manager' );

	if ( ! empty( $event_id ) ) {

		$package_id = tmem_get_event_package( $event_id );

		$package_name = tmem_get_package_name( $package_id );

		if ( ! empty( $package_name ) ) {
			$return = $package_name;
		}
	}

	return $return;
} // tmem_content_tag_event_package

/**
 * Content tag: event_package_cost.
 * The package attached to the event and it's cost.
 *
 * @param int $event_id The event ID.
 * @return str The package name and cost or "No Package".
 */
function tmem_content_tag_event_package_cost( $event_id = '' ) {
	$return = '0.00';

	if ( ! empty( $event_id ) ) {
		$tmem_event = new TMEM_Event( $event_id );
		$package_id = $tmem_event->get_package();

		$package_price = tmem_get_package_price( $package_id, $tmem_event->date );

		if ( ! empty( $package_price ) ) {
			$return = $package_price;
		}
	}

	return tmem_currency_filter( tmem_format_amount( $return ) );
} // tmem_content_tag_event_package_cost

/**
 * Content tag: event_package_desciption.
 * The package attached to the event.
 *
 * @param int $event_id The event ID.
 * @return str The package description.
 */
function tmem_content_tag_event_package_description( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return get_event_package_description( $event_id );
} // tmem_content_tag_event_package_description

/**
 * Content tag: event_status.
 * The current event status.
 *
 * @param int $event_id The event ID.
 *
 * @return str The current event status label.
 */
function tmem_content_tag_event_status( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_get_event_status( $event_id );
} // tmem_content_tag_event_status

/**
 * Content tag: event_type.
 * The current event type.
 *
 * @param int $event_id The event ID.
 *
 * @return str The current event type label.
 */
function tmem_content_tag_event_type( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	return tmem_get_event_type( $event_id );
} // tmem_content_tag_event_type

/**
 * Content tag: event_url.
 * The front end event url.
 *
 * @param int $event_id The event ID.
 *
 * @return str The current event type label.
 */
function tmem_content_tag_event_url( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$url = tmem_get_event_uri( $event_id );
	$url = apply_filters( 'tmem_tag_event_url', $url, $event_id );

	return $url;
} // tmem_content_tag_event_url

/**
 * Content tag: event_admin_url.
 * The admin event url.
 *
 * @param int $event_id The event ID.
 *
 * @return str The current event type label.
 */
function tmem_content_tag_event_admin_url( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$url = tmem_get_event_uri( $event_id, true );
	$url = apply_filters( 'tmem_tag_event_admin_url', $url, $event_id );

	return $url;
} // tmem_content_tag_event_admin_url

/**
 * Content tag: final_balance.
 * The event ID.
 *
 * @param int $event_id The event ID.
 *
 * @return str The event final balance (cost - unpaid deposit).
 */
function tmem_content_tag_final_balance( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$final_balance = get_post_meta( $event_id, '_tmem_event_cost', true );
	$deposit       = get_post_meta( $event_id, '_tmem_event_deposit', true );

	if ( ! $final_balance ) {
		return;
	}

	if ( $deposit ) {
		$final_balance = $final_balance - $deposit;
	}

	return tmem_currency_filter( tmem_sanitize_amount( $final_balance ) );
} // tmem_content_tag_final_balance

/**
 * Content tag: guest_playlist_url.
 * The URL to the guest playlist page.
 *
 * @param int $event_id The event ID.
 * @return str The playlist page URL for guests.
 */
function tmem_content_tag_guest_playlist_url( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$url = tmem_guest_playlist_url( $event_id );

	if ( empty( $url ) ) {
		$return = __( 'Guest playlist is disabled.', 'mobile-events-manager' );
	} else {
		$url    = apply_filters( 'tmem_tag_event_guest_playlist_url', $url, $event_id );
		$return = $url;
	}

	return $return;
} // tmem_content_tag_guest_playlist_url

/**
 * Content tag: part_payment_label.
 * The label used for part payments.
 *
 * @return str The label used for part payments.
 */
function tmem_content_tag_part_payment_label() {
	return tmem_get_other_amount_label();
} // tmem_content_tag_part_payment_label

/**
 * Content tag: payment_history.
 * The payment history for the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str The events payment history.
 */
function tmem_content_tag_payment_history( $event_id = '' ) {
	if ( ! empty( $event_id ) ) {
		return tmem_list_event_txns( $event_id );
	}
} // tmem_content_tag_payment_history

/**
 * Content tag: payment_url.
 * The URL to the payments page.
 *
 * @param atr $url payment url.
 *
 * @return str The payments page URL.
 */
function tmem_content_tag_payment_url() {
	$url = tmem_get_formatted_url( tmem_get_option( 'payments_page' ), false );
	$url = apply_filters( 'tmem_tag_payment_url', $url );

	return $url;
} // tmem_content_tag_payment_url

/**
 * Content tag: pdf_pagebreak.
 * A page break in a PDF document.
 *
 * @return str A page break in a PDF document.
 */
function tmem_content_tag_pdf_pagebreak() {
	return '<pagebreak />';
} // tmem_content_tag_pdf_pagebreak

/**
 * Content tag: playlist_close.
 * The number of days until the playlist closes.
 *
 * @param int $event_id The event ID.
 *
 * @return int|str The number of days until the playlist for the event closes, or 'never' if it does not.
 */
function tmem_content_tag_playlist_close( $event_id = '' ) {
	$close = tmem_get_option( 'close' );

	return ! empty( $close ) ? $close : 'never';
} // tmem_content_tag_playlist_close

/**
 * Content tag: playlist_duration.
 * The approximate length of the playlist.
 *
 * @param int $event_id The event ID.
 *
 * @return int|str The approximate length of the playlist.
 */
function tmem_content_tag_playlist_duration( $event_id = '' ) {
	$total_entries = tmem_count_playlist_entries( $event_id );

	return tmem_playlist_duration( $event_id, $total_entries );
} // tmem_content_tag_playlist_duration

/**
 * Content tag: playlist_url.
 * The URL to the playlist page.
 *
 * @param int $event_id The event ID.
 * @return str The playlist page URL for clients.
 */
function tmem_content_tag_playlist_url( $event_id = '' ) {
	$access = get_post_meta( $event_id, '_tmem_event_playlist', true );

	$return = __( 'Event playlist disabled', 'mobile-events-manager' );

	if ( 'Y' === $access ) {
		$return = tmem_get_formatted_url( tmem_get_option( 'playlist_page' ), true );

		if ( ! empty( $event_id ) ) {
			$return .= 'event_id=' . $event_id;
		}

		$return = apply_filters( 'tmem_tag_event_playlist_url', $return, $event_id );

	}

	return $return;
} // tmem_content_tag_playlist_url

/**
 * Content tag: quotes_url.
 * The URL to the online quotes page.
 *
 * @param int $event_id The event ID.
 *
 * @return str The online quote page URL for clients.
 */
function tmem_content_tag_quotes_url( $event_id = '' ) {
	$return = add_query_arg(
		array(
			'event_id' => $event_id,
		),
		tmem_get_formatted_url( tmem_get_option( 'quotes_page' ), true )
	);

	return apply_filters( 'tmem_tag_event_quote_url', $return, $event_id );
} // tmem_content_tag_quotes_url

/**
 * Content tag: start_time.
 * The event start time.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted event start time.
 */
function tmem_content_tag_start_time( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = '';

	$time = get_post_meta( $event_id, '_tmem_event_start', true );

	if ( ! empty( $time ) ) {
		$return = gmdate( tmem_get_option( 'time_format', 'H:i' ), strtotime( $time ) );
	}

	return $return;
} // tmem_content_tag_start_time

/**
 * Content tag: total_cost.
 * The event start time.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted event total cost.
 */
function tmem_content_tag_total_cost( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$return = __( 'Not specified', 'mobile-events-manager' );

	$cost = get_post_meta( $event_id, '_tmem_event_cost', true );

	if ( ! empty( $cost ) ) {
		$return = tmem_currency_filter( tmem_sanitize_amount( $cost ) );
	}

	return $return;
} // tmem_content_tag_total_cost

/**
 * Content tag: travel_cost.
 * The travel cost for the event.
 *
 * @param int $event_id The event ID.
 *
 * @return str Formatted event travel cost.
 */
function tmem_content_tag_travel_cost( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$travel_cost = tmem_get_event_travel_data( $event_id );

	if ( ! empty( $travel_cost ) ) {
		return tmem_currency_filter( tmem_format_amount( $travel_cost ) );
	}
} // tmem_content_tag_travel_cost

/**
 * Content tag: travel_directions.
 * A URL for the travel directions to the event location.
 *
 * @param int $event_id The event ID.
 *
 * @return str URL to Google Maps directions.
 */
function tmem_content_tag_travel_directions( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$travel_directions = tmem_get_event_travel_data( $event_id, 'directions_url' );

	if ( ! empty( $travel_directions ) ) {
		return $travel_directions;
	}
} // tmem_content_tag_travel_directions

/**
 * Content tag: travel_distance.
 * The travel distance to the event location.
 *
 * @param int $event_id The event ID.
 *
 * @return str Travel distance to the event including units.
 */
function tmem_content_tag_travel_distance( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$travel_distance = tmem_get_event_travel_data( $event_id, 'distance' );

	if ( ! empty( $travel_distance ) ) {
		return $travel_distance;
	}
} // tmem_content_tag_travel_distance

/**
 * Content tag: travel_time.
 * The travel time to the event location.
 *
 * @param int $event_id The event ID.
 *
 * @return str Travel time to the event.
 */
function tmem_content_tag_travel_time( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$travel_time = tmem_get_event_travel_data( $event_id, 'time' );

	if ( ! empty( $travel_time ) ) {
		return $travel_time;
	}
} // tmem_content_tag_travel_time

/**
 * Content tag: venue.
 * The event venue name.
 *
 * @param int $event_id The event ID.
 *
 * @return str Name of the event venue.
 */
function tmem_content_tag_venue( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	return tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'name' );
} // tmem_content_tag_total_venue

/**
 * Content tag: venue_contact.
 * The event venue contact name.
 *
 * @param int $event_id The event ID.
 *
 * @return str Name of the contact at the event venue.
 */
function tmem_content_tag_venue_contact( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	return tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'contact' );
} // tmem_content_tag_venue_contact

/**
 * Content tag: venue_details.
 * The details associated with the venue.
 *
 * @param int $event_id The event ID.
 *
 * @return str The details (tags) associated with the venue.
 */
function tmem_content_tag_venue_details( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	$details = tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'details' );
	$return  = '';

	if ( empty( $details ) ) {
		return;
	}

	$return = is_array( $details ) ? implode( '<br />', $details ) : $details;

	return $return;
} // tmem_content_tag_venue_details

/**
 * Content tag: venue_email.
 * The event venue email address.
 *
 * @param int $event_id The email.
 *
 * @return str Email address of the event venue.
 */
function tmem_content_tag_venue_email( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	return tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'email' );
} // tmem_content_tag_venue_email

/**
 * Content tag: venue_full_address.
 * The address of the venue.
 *
 * @param int $event_id The event ID.
 *
 * @return str The details (tags) associated with the venue.
 */
function tmem_content_tag_venue_full_address( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	$address = tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'address' );
	$return  = '';

	if ( empty( $address ) ) {
		return;
	}

	$return = is_array( $address ) ? implode( '<br />', $address ) : $address;

	return $return;
} // tmem_content_tag_venue_full_address

/**
 * Content tag: venue_notes.
 * The notes for the venue.
 *
 * @param str $event_id Venue Notes.
 *
 * @return str Notes associated with venue.
 */
function tmem_content_tag_venue_notes( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	return tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'notes' );
} // tmem_content_tag_venue_notes


/**
 * Content tag: venue_telephone.
 * The event venue phone number.
 *
 * @param int $event_id venue telephone no.
 *
 * @return str Phone number of the event venue.
 */
function tmem_content_tag_venue_telephone( $event_id = '' ) {
	if ( empty( $event_id ) ) {
		return;
	}

	$tmem_event = new TMEM_Event( $event_id );

	return tmem_get_event_venue_meta( $tmem_event->get_venue_id(), 'phone' );
} // tmem_content_tag_venue_telephone
