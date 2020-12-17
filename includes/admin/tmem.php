<?php
/**
 * Class: TMEM
 * Description: The main TMEM class
 */

	/* -- Build the TMEM class -- */
if ( ! class_exists( 'TMEM' ) ) {
	class TMEM {
		// Publicise the Events class so we can use it throughout
		public $tmem_events;
		/**
		 * Class constructor
		 */
		public function __construct() {
			global $tmem_post_types;

			$tmem_post_types = array(
				'tmem_communication',
				'contract',
				'tmem-custom-fields',
				'tmem-signed-contract',
				'email_template',
				'tmem-event',
				'tmem-quotes',
				'tmem-transaction',
				'tmem-venue',
			);

			/* -- Hooks -- */
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue' ) ); // Admin styles & scripts
		} // __construct

		/*
		* --
		* STYLES & SCRIPTS
		* --
		*/
		/*
		 * admin_enqueue
		 * Register & enqueue the scripts & styles we want to use
		 * Only register those scripts we want on all pages
		 * Or those we can control
		 * Others should be called from the pages themselves
		 */
		public function admin_enqueue() {
			global $tmem_post_types;

			// jQuery Validation
			wp_register_script( 'jquery-validation-plugin', TMEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );

			if ( in_array( get_post_type(), $tmem_post_types ) || ( isset( $_GET['section'] ) && 'tmem_custom_event_fields' === $_GET['section'] ) ) {

				wp_register_style( 'tmem-posts', TMEM_PLUGIN_URL . '/assets/css/tmem-posts.css', '', TMEM_VERSION_NUM );
				wp_enqueue_style( 'tmem-posts' );

				wp_enqueue_script( 'jquery-validation-plugin' );

			}
		} // admin_enqueue

	} // TMEM class
} // if( !class_exists )
