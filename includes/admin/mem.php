<?php
/**
 * Class: MEM
 * Description: The main MEM class
 */

	/* -- Build the MEM class -- */
if ( ! class_exists( 'MEM' ) ) {
	class MEM {
		// Publicise the Events class so we can use it throughout
		public $mem_events;
		/**
		 * Class constructor
		 */
		public function __construct() {
			global $mem_post_types;

			$mem_post_types = array(
				'mem_communication',
				'contract',
				'mem-custom-fields',
				'mem-signed-contract',
				'email_template',
				'mem-event',
				'mem-quotes',
				'mem-transaction',
				'mem-venue',
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
			global $mem_post_types;

			// jQuery Validation
			wp_register_script( 'jquery-validation-plugin', MEM_PLUGIN_URL . '/assets/libs/jquery-validate/jquery.validate.min.js', false );

			if ( in_array( get_post_type(), $mem_post_types ) || ( isset( $_GET['section'] ) && 'mem_custom_event_fields' === $_GET['section'] ) ) {

				wp_register_style( 'mem-posts', MEM_PLUGIN_URL . '/assets/css/mem-posts.css', '', MEM_VERSION_NUM );
				wp_enqueue_style( 'mem-posts' );

				wp_enqueue_script( 'jquery-validation-plugin' );

			}
		} // admin_enqueue

	} // MEM class
} // if( !class_exists )
