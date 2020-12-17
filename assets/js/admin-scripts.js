var tmem_admin_vars;
jQuery( document ).ready(
	function ($) {

		// Setup Chosen menus
		$( '.tmem-select-chosen' ).chosen(
			{
				inherit_select_classes: true,
				placeholder_text_single: tmem_admin_vars.one_option,
				placeholder_text_multiple: tmem_admin_vars.one_or_more_option
			}
		);

		$( '.tmem-select-chosen .chosen-search input' ).each(
			function() {
				var selectElem = $( this ).parent().parent().parent().prev( 'select.tmem-select-chosen' ),
				placeholder    = selectElem.data( 'search-placeholder' );
				$( this ).attr( 'placeholder', placeholder );
			}
		);

		// Add placeholders for Chosen input fields
		$( '.chosen-choices' ).on(
			'click',
			function () {
				var placeholder = $( this ).parent().prev().data( 'search-placeholder' );
				if ( typeof placeholder === 'undefined' ) {
					placeholder = tmem_admin_vars.type_to_search;
				}
				$( this ).children( 'li' ).children( 'input' ).attr( 'placeholder', placeholder );
			}
		);

		// Dismiss admin notices
		$( document ).on(
			'click',
			'.notice-tmem-dismiss .notice-dismiss',
			function () {
				var notice = $( this ).closest( '.notice-tmem-dismiss' ).data( 'notice' );

				var postData = {
					notice    : notice,
					action       : 'tmem_dismiss_notice'
				};

				$.ajax(
					{
						type: 'POST',
						dataType: 'json',
						data: postData,
						url: ajaxurl
					}
				);
			}
		);

		// Set the deposit value for the event
		var setDeposit = function()	{
			var current_deposit = $( '#_tmem_event_deposit' ).val();
			var postData        = {
				current_cost : $( '#_tmem_event_cost' ).val(),
				action       : 'update_event_deposit'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					beforeSend : function()	{
						$( '#_tmem_event_deposit' ).attr( 'readonly', true );
						$( '#tmem-event-pricing-detail' ).addClass( 'tmem-mute' );
					},
					success: function (response) {
						if (response.type === 'success') {
							$( '#_tmem_event_deposit' ).val( response.deposit );
						} else {
							alert( response.msg );
							$( '#_tmem_event_deposit' ).val( current_deposit );
						}
						$( '#tmem-event-pricing-detail' ).removeClass( 'tmem-mute' );
						$( '#_tmem_event_deposit' ).attr( 'readonly', false );
					}
				}
			).fail(
				function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
					$( '#_tmem_event_deposit' ).val( current_deposit );
				}
			);
		};

		// Set the event cost.
		var setCost = function()	{

			var current_cost = $( '#_tmem_event_cost' ).val();
			var venue;

			if ( 'manual' === $( '#venue_id' ).val() || 'client' === $( '#venue_id' ).val() ) {
				venue = [
				$( '#venue_address1' ).val(),
				$( '#venue_address2' ).val(),
				$( '#venue_town' ).val(),
				$( '#venue_county' ).val(),
				$( '#venue_postcode' ).val(),
				];
			} else {
				venue = $( '#venue_id' ).val();
			}

			var postData = {
				addons          : $( '#event_addons' ).val() || [],
				package         : $( '#_tmem_event_package option:selected' ).val(),
				event_id        : $( '#post_ID' ).val(),
				current_cost    : $( '#_tmem_event_cost' ).val(),
				event_date      : $( '#_tmem_event_date' ).val(),
				venue           : venue,
				employee_id     : $( '#_tmem_event_dj' ).val(),
				additional      : $( '#_tmem_event_additional_cost' ).val(),
				discount        : $( '#_tmem_event_discount' ).val(),
				action          : 'tmem_update_event_cost'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					beforeSend : function()	{
						$( '#tmem-event-pricing-detail' ).addClass( 'tmem-mute' );
					},
					success: function (response) {
						if (response.type === 'success') {
							$( '#_tmem_event_package_cost' ).val( response.package_cost );
							$( '#_tmem_event_addons_cost' ).val( response.addons_cost );
							$( '#_tmem_event_travel_cost' ).val( response.travel_cost );
							$( '#_tmem_event_additional_cost' ).val( response.additional_cost );
							$( '#_tmem_event_discount' ).val( response.discount );

							var value = Number( response.package_cost ) + Number( response.addons_cost ) + Number( response.travel_cost ) + Number( response.additional_cost );
							value     = Number( value ) - Number( response.discount );
							value     = value.toFixed( 2 );

							$( '#_tmem_event_cost' ).val( value );

							if ( tmem_admin_vars.update_deposit ) {
								setDeposit();
							}

						} else {
							$( '#_tmem_event_cost' ).val( current_cost );
						}

						$( '#tmem-event-pricing-detail' ).removeClass( 'tmem-mute' );
					}
				}
			).fail(
				function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				}
			);

		};

		// Set travel data for event
		var setTravelData = function()	{
			var venue;
			if ( 'manual' === $( '#venue_id' ).val() || 'client' === $( '#venue_id' ).val() ) {
				venue = [
				$( '#venue_address1' ).val(),
				$( '#venue_address2' ).val(),
				$( '#venue_town' ).val(),
				$( '#venue_county' ).val(),
				$( '#venue_postcode' ).val(),
				];
			} else {
				venue = $( '#venue_id' ).val();
			}
			var postData = {
				employee_id : $( '#_tmem_event_dj' ).val(),
				venue : venue,
				action  : 'tmem_update_travel_data'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					success: function (response) {
						if (response.type === 'success') {
							$( '.tmem-travel-distance' ).parents( 'tr' ).show();
							$( '.tmem-travel-directions' ).parents( 'tr' ).show();
							$( '.tmem-travel-distance' ).html( response.distance );
							$( '.tmem-travel-time' ).html( response.time );
							$( '.tmem-travel-cost' ).html( response.cost );
							$( '#travel_directions' ).attr( 'href', response.directions_url );
							$( '#tmem_travel_distance' ).val( response.distance );
							$( '#tmem_travel_time' ).val( response.time );
							$( '#tmem_travel_cost' ).val( response.raw_cost );
							$( '#tmem_travel_directions_url' ).val( response.directions_url );
						} else {
							$( '.tmem-travel-distance' ).parents( 'tr' ).hide();
							$( '#travel-directions' ).attr( 'href', '' );
							$( '.tmem-travel-directions' ).parents( 'tr' ).hide();
							$( '#tmem_travel_distance' ).val( '' );
							$( '#tmem_travel_time' ).val( '' );
							$( '#tmem_travel_cost' ).val( '' );
							$( '#tmem_travel_directions_url' ).val( '' );
						}
					}
				}
			).fail(
				function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				}
			);
		};

		/**
		 * General Settings Screens JS
		 */
		var TMEM_Settings = {
			init : function()	{
				this.general();
				if ( 'admin_page_tmem-custom-event-fields' === tmem_admin_vars.current_page || 'admin_page_tmem-custom-client-fields' === tmem_admin_vars.current_page ) {
					this.custom_fields();
				}
			},

			general : function()    {
				var tmem_color_picker = $( '.tmem-color-picker' );

				if ( tmem_color_picker.length ) {
					tmem_color_picker.wpColorPicker();
				}
			},

			custom_fields : function()	{
				// Sortable Client Fields
				jQuery( document ).ready(
					function($) 	{
						$( '.tmem-client-list-item' ).sortable(
							{
								handle: '.tmem_draghandle',
								items: '.tmem_sortable_row',
								opacity: 0.6,
								cursor: 'move',
								axis: 'y',
								update: function()	{
									var order = $( this ).sortable( 'serialize', { expression: / (.+) = (.+) / } ) + '&action=tmem_update_client_field_order';
									$.post(
										ajaxurl,
										order,
										function()	{
											// Success
										}
									);
								}
							}
						);
					}
				);

				// Sortable Custom Event Fields
				$( '.tmem-custom-client-list-item,.tmem-custom-event-list-item,.tmem-custom-venue-list-item' ).sortable(
					{

						handle: '.tmem_draghandle',
						items: '.tmem_sortable_row',
						opacity: 0.6,
						cursor: 'move',
						axis: 'y',
						update: function()	{
							var order = $( this ).sortable( 'serialize' ) + '&action=order_custom_event_fields';
							$.post(
								ajaxurl,
								order,
								function()	{
									// Success
								}
							);
						}
					}
				);
			}
		};
		TMEM_Settings.init();

		/**
		 * Availability screen JS
		 */
		var TMEM_Availability = {
			init : function()	{
				this.options();
				this.absence();
				this.checker();
			},

			options : function()	{
				// Toggle display of availability checker
				$( document.body ).on(
					'click',
					'.toggle-availability-checker-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === tmem_admin_vars.show_avail_form ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_avail_form );
						} else {
							$( this ).html( tmem_admin_vars.show_avail_form );
						}

						$( '.tmem-availability-checker-fields' ).slideToggle();
					}
				);

				// Toggle display of absence form section
				$( document.body ).on(
					'click',
					'.toggle-add-absence-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === tmem_admin_vars.show_absence_form ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_absence_form );
						} else {
							$( this ).html( tmem_admin_vars.show_absence_form );
						}

						var header = $( this ).parents( '.tmem-availability-row-header' );
						header.siblings( '.tmem-availability-add-absence-sections-wrap' ).slideToggle();

						if ( $( '#absence_all_day' ).is( ':checked' ) ) {
							$( '.tmem-absence-start-time-option' ).hide( 'fast' );
							$( '.tmem-absence-end-time-option' ).hide( 'fast' );
						}

						var first_input;
						if ( show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.tmem-availability-add-absence-sections-wrap' ) );
						} else {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.tmem-repeatable-row-standard-fields' ) );
						}
						first_input.focus();
					}
				);

				// Toggle display of absence time fields
				$( document.body ).on(
					'change',
					'#absence_all_day',
					function() {
						$( '.tmem-absence-start-time-option' ).slideToggle();
						$( '.tmem-absence-end-time-option' ).slideToggle();
					}
				);

			},

			absence : function()	{
				// Add a new absence entry
				$( document.body ).on(
					'click',
					'#add-absence',
					function(e)	{
						e.preventDefault();

						var employee_id   = $( '#absence_employee_id' ).val(),
						start_date        = $( '#absence_start' ).val(),
						end_date          = $( '#absence_end' ).val(),
						all_day           = $( '#absence_all_day' ).is( ':checked' ) ? 1 : 0,
						start_time_hr     = $( '#absence_start_time_hr' ).val(),
						start_time_min    = $( '#absence_start_time_min' ).val(),
						start_time_period = 0,
						end_time_hr       = $( '#absence_end_time_hr' ).val(),
						end_time_min      = $( '#absence_end_time_min' ).val(),
						end_time_period   = 0,
						notes             = $( '#absence_notes' ).val();

						if ( 'H:i' !== tmem_admin_vars.time_format ) {
							start_time_period = $( '#absence_start_time_period' ).val();
							end_time_period   = $( '#absence_end_time_period' ).val();
						}

						if ( ! start_date )	{
							$( '#display_absence_start' ).addClass( 'tmem-form-error' );
							return;
						}
						if ( ! end_date ) {
							$( '#display_absence_end' ).addClass( 'tmem-form-error' );
							return;
						}

						var postData = {
							employee_id       : employee_id,
							start_date        : start_date,
							end_date          : end_date,
							all_day           : all_day,
							start_time_hr     : start_time_hr,
							start_time_min    : start_time_min,
							start_time_period : start_time_period,
							end_time_hr       : end_time_hr,
							end_time_min      : end_time_min,
							end_time_period   : end_time_period,
							notes             : notes,
							action            : 'tmem_add_employee_absence'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem-add-absence-fields' ).addClass( 'tmem-mute' );
								},
								success: function (response) {
									if ( true === response.success ) {
										$( '#tmem-calendar' ).fullCalendar( 'refetchEvents' );
										$( '#tmem-calendar' ).fullCalendar( 'gotoDate', response.date );
									}
									$( '#display_absence_start' ).val( '' );
									$( '#absence_start' ).val( '' );
									$( '#display_absence_end' ).val( '' );
									$( '#absence_end' ).val( '' );
									$( '#absence_all_day' ).prop( 'checked, true' );
									$( '#absence_start_time_hr' ).val( '00' );
									$( '#absence_start_time_min' ).val( '00' );
									$( '#absence_end_time_hr' ).val( '00' );
									$( '#absence_end_time_min' ).val( '00' );
									$( '#absence_notes' ).val( '' );

									if ( 'H:i' !== tmem_admin_vars.time_format ) {
										$( '#absence_start_time_period' ).val( 'am' );
										$( '#absence_end_time_period' ).val( 'am' );
									}

									$( '#tmem-calendar' ).removeClass( 'tmem-mute' );
									$( '#tmem-add-absence-fields' ).removeClass( 'tmem-mute' );
									$( 'html, body' ).animate(
										{
											scrollTop: $( '.toggle-add-absence-section' ).offset().top
										},
										500
									);
									$( '.toggle-add-absence-section' ).trigger( 'click' );
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

				// Delete an entry from the calendar screen
				$( document.body ).on(
					'click',
					'.delete-absence',
					function(e)  {
						e.preventDefault();

						$( '#tmem-calendar' ).addClass( 'tmem-mute' );
						var postData = {
							id     : $( this ).data( 'entry' ),
							action : 'tmem_delete_employee_absence'
						};

						$.ajax(
							{
								type: 'POST',
								url: ajaxurl,
								dataType: 'json',
								data: postData,
								success: function(response) {
									if ( true === response.success ) {
										$( '#tmem-calendar' ).fullCalendar( 'refetchEvents' );
										$( '.popover' ).hide( 'fast' );
									}
									$( '#tmem-calendar' ).removeClass( 'tmem-mute' );
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				);
			},

			checker : function()	{
				$( document.body ).on(
					'click',
					'#check-availability',
					function()	{
						var date     = $( '#check_date' ),
						display_date = $( '#display_date' ),
						employees    = $( '#display_date' ),
						roles        = $( '#check_roles' );

						if ( ! date.val() || ! display_date.val() )	{
							display_date.addClass( 'tmem-form-error' );
							return;
						}

						var postData = {
							date     : date,
							employees : employees,
							roles     : roles,
							action : 'do_availability_check_ajax'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem_availability_fields' ).addClass( 'tmem-mute' );
								},
								success: function () {
									$( '#tmem_availability_fields' ).removeClass( 'tmem-mute' );
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);
			}
		};
		TMEM_Availability.init();

		/**
		 * Events screen JS
		 */
		var TMEM_Events = {

			init : function()	{
				this.options();
				this.client();
				this.employee();
				this.equipment();
				this.playlist();
				this.tasks();
				this.time();
				this.travel();
				this.type();
				this.txns();
				this.venue();
			},

			options : function()	{
				// Toggle display of event options
				$( document.body ).on(
					'click',
					'.toggle-event-options-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === tmem_admin_vars.show_event_options ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_event_options );
						} else {
							$( this ).html( tmem_admin_vars.show_event_options );
						}

						$( '.tmem-event-options-sections-wrap' ).slideToggle();
					}
				);
			},

			client : function()	{
				// Toggle display of new client fields section for an event
				$( document.body ).on(
					'click',
					'.toggle-client-add-option-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === tmem_admin_vars.show_client_form ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_client_form );
						} else {
							$( this ).html( tmem_admin_vars.show_client_form );
						}

						var header = $( this ).parents( '.tmem-client-row-header' );
						header.siblings( '.tmem-client-add-event-sections-wrap' ).slideToggle();

						var first_input;
						if ( show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.tmem-client-add-event-sections-wrap' ) );
						} else {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.tmem-repeatable-row-standard-fields' ) );
						}
						first_input.focus();
					}
				);

				// Toggle display of client details section for an event
				$( document.body ).on(
					'click',
					'.toggle-client-details-option-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === tmem_admin_vars.show_client_details ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_client_details );
						} else {
							$( this ).html( tmem_admin_vars.show_client_details );
						}

						var header = $( this ).parents( '.tmem-client-row-header' );
						header.siblings( '.tmem-client-details-event-sections-wrap' ).slideToggle();

						var first_input;
						if ( ! show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.tmem-repeatable-row-standard-fields' ) );
							first_input.focus();
						}
					}
				);

				// Display client details
				$( document.body ).on(
					'click',
					'#toggle_client_details',
					function() {
						$( '#tmem-event-client-details' ).toggle( 'slow' );
					}
				);

				// Update the client details when the client selection changes
				$( document.body ).on(
					'change',
					'#client_name',
					function(event) {

						event.preventDefault();

						if ( '' === $( '#client_name' ).val() ) {
							$( '#tmem-event-add-new-client-fields' ).hide( 'slow' );
							return;
						} else if ( 'tmem_add_client' === $( '#client_name' ).val() ) {
							$( '#tmem-event-add-new-client-fields' ).show( 'slow' );
						} else {

							$( '#tmem-event-add-new-client-fields' ).hide( 'slow' );

							var postData = {
								client_id  : $( '#client_name' ).val(),
								event_id   : $( '#post_ID' ).val(),
								action     : 'tmem_refresh_client_details'
							};

							$.ajax(
								{
									type       : 'POST',
									dataType   : 'json',
									data       : postData,
									url        : ajaxurl,
									beforeSend : function()	{
										$( '#tmem-event-client-details' ).replaceWith( '<div id="tmem-loading" class="tmem-loader"><img src="' + tmem_admin_vars.ajax_loader + '" /></div>' );
									},
									success: function (response) {
										$( '#tmem-loading' ).replaceWith( response.client_details );

									}
								}
							).fail(
								function (data) {
									$( '#tmem-event-client-details' ).replaceWith( '<div id="tmem-loading" class="tmem-loader"><img src="' + tmem_admin_vars.ajax_loader + '" /></div>' );

									if ( window.console && window.console.log ) {
										console.log( data );
									}
								}
							);

						}

					}
				);

				// Add a new client from the event screen
				$( document.body ).on(
					'click',
					'#tmem-add-client',
					function(event) {

						event.preventDefault();

						if ( $( '#client_firstname' ).val().length < 1 ) {
							$( '#client_firstname' ).addClass( 'tmem-form-error' );
							return;
						}
						if ( $( '#client_email' ).val().length < 1 ) {
							$( '#client_email' ).addClass( 'tmem-form-error' );
							return;
						}

						var postData = {
							client_firstname : $( '#client_firstname' ).val(),
							client_lastname  : $( '#client_lastname' ).val(),
							client_email     : $( '#client_email' ).val(),
							client_address1  : $( '#client_address1' ).val(),
							client_address2  : $( '#client_address2' ).val(),
							client_town      : $( '#client_town' ).val(),
							client_county    : $( '#client_county' ).val(),
							client_postcode  : $( '#client_postcode' ).val(),
							client_phone     : $( '#client_phone' ).val(),
							client_phone2    : $( '#client_phone2' ).val(),
							action           : 'tmem_event_add_client'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.tmem-client-option-fields' ).addClass( 'tmem-mute' );
									$( '#tmem-add-client' ).hide( 'fast' );
								},
								success: function (response) {
									$( '#tmem-add-client-fields' ).slideToggle();
									$( '#add-client-action' ).html( tmem_admin_vars.show_client_form );
									$( '#client_name' ).empty();
									$( '#client_firstname' ).val( '' );
									$( '#client_lastname' ).val( '' );
									$( '#client_email' ).val( '' );
									$( '#client_address1' ).val( '' );
									$( '#client_address2' ).val( '' );
									$( '#client_town' ).val( '' );
									$( '#client_county' ).val( '' );
									$( '#client_postcode' ).val( '' );
									$( '#client_phone' ).val( '' );
									$( '#client_phone2' ).val( '' );
									$( '#client_name' ).append( response.client_list );
									$( '#tmem-add-client' ).show( 'fast' );
									$( '#_tmem_event_block_emails' ).prop( 'checked', false );
									$( '#tmem_reset_pw' ).prop( 'checked', true );
									$( '#client_name' ).trigger( 'chosen:updated' );

									$( '.tmem-client-option-fields' ).removeClass( 'tmem-mute' );

									if ( response.type === 'error' ) {
										alert( response.message );
									}

								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

				// Display custom client fields
				$( document.body ).on(
					'click',
					'#toggle_custom_client_fields',
					function() {
						$( '#tmem_event_custom_client_fields' ).toggle( 'fast' );
					}
				);
				// Display custom event fields
				$( document.body ).on(
					'click',
					'#toggle_custom_event_fields',
					function() {
						$( '#tmem_event_custom_event_fields' ).toggle( 'fast' );
					}
				);
				// Display custom venue fields
				$( document.body ).on(
					'click',
					'#toggle_custom_venue_fields',
					function() {
						$( '#tmem_event_custom_venue_fields' ).toggle( 'fast' );
					}
				);

			},

			employee : function()	{

				// Add a new employee role
				$( document.body ).on(
					'click',
					'#new_tmem_role',
					function(e) {
						e.preventDefault();

						if ( $( '#add_tmem_role' ).hasClass( 'tmem-form-error' ) ) {
							$( '#add_tmem_role' ).removeClass( 'tmem-form-error' );
						}

						if ( $( '#add_tmem_role' ).val().length < 1 ) {
							$( '#add_tmem_role' ).addClass( 'tmem-form-error' );
							return;
						}

						var postData = {
							role_name : $( '#add_tmem_role' ).val(),
							action    : 'tmem_add_role'
						};

						$.ajax(
							{
								type: 'POST',
								dataType: 'json',
								data: postData,
								url: ajaxurl,
								beforeSend: function()	{
									$( 'input[type="submit"]' ).prop( 'disabled', true );
									$( '#new_tmem_role' ).hide();
									$( '#pleasewait' ).show();
									$( '#all_roles' ).addClass( 'tmem-mute' );
									$( '#employee_role' ).addClass( 'tmem-mute' );
									$( '#all_roles' ).fadeTo( 'slow', 0.5 );
									$( '#employee_role' ).fadeTo( 'slow', 0.5 );
								},
								success: function(response)	{
									if (response.type === 'success') {
										$( '#all_roles' ).empty(); // Remove existing options
										$( '#employee_role' ).empty();
										$( '#all_roles' ).append( response.options );
										$( '#employee_role' ).append( response.options );
										$( '#add_tmem_role' ).val( '' );
										$( '#all_roles' ).fadeTo( 'slow', 1 );
										$( '#all_roles' ).removeClass( 'tmem-mute' );
										$( '#employee_role' ).fadeTo( 'slow', 1 );
										$( '#employee_role' ).removeClass( 'tmem-mute' );
										$( 'input[type="submit"]' ).prop( 'disabled', false );
										$( '#pleasewait' ).hide();
										$( '#new_tmem_role' ).show();
									} else {
										alert( response.msg );
										$( '#all_roles' ).fadeTo( 'slow', 1 );
										$( '#all_roles' ).removeClass( 'tmem-mute' );
										$( '#employee_role' ).fadeTo( 'slow', 1 );
										$( '#employee_role' ).removeClass( 'tmem-mute' );
										$( 'input[type="submit"]' ).prop( 'disabled', false );
										$( '#pleasewait' ).hide();
										$( '#new_tmem_role' ).show();
									}
								}
							}
						);
					}
				);

				// Reveal the input field to add a new event worker
				$( document.body ).on(
					'click',
					'.toggle-add-worker-section',
					function(e) {
						e.preventDefault();

						var show = $( this ).html() === tmem_admin_vars.show_workers ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_workers );
						} else {
							$( this ).html( tmem_admin_vars.show_workers );
						}

						$( '.tmem-event-workers-sections-wrap' ).slideToggle();
						if ( $( '.tmem-event-workers-sections-wrap' ).is( ':visible' ) ) {
							$( '#event_new_employee_role' ).focus();
						}
					}
				);

				// Currency format for new employee wage
				$( document.body ).on(
					'change',
					'#event_new_employee_wage',
					function() {
						var value = $( this ).val();

						if ( value.length > 0) {
							$( this ).val( value.toFixed( 2 ) );
						}
					}
				);

				// Add an employee to the event
				$( document.body ).on(
					'click',
					'#add_event_employee',
					function(event) {

						event.preventDefault();

						var postData = {
							event_id      : $( '#post_ID' ).val(),
							employee_id   : $( '#event_new_employee' ).val(),
							employee_role : $( '#event_new_employee_role' ).val(),
							employee_wage : $( '#event_new_employee_wage' ).val(),
							action        : 'add_employee_to_event'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.tmem-event-workers-sections-wrap' ).addClass( 'tmem-mute' );
									$( '#tmem-event-employee-list' ).slideToggle();
								},
								success: function (response) {
									if (response.type !== 'success') {
										alert( response.msg );
									}
									$( '#tmem-event-employee-list' ).html( response.employees );
									$( '#tmem-event-employee-list' ).slideToggle();
									$( '.tmem-event-workers-sections-wrap' ).removeClass( 'tmem-mute' );
									$( '#event_new_employee_role' ).val( '' ).trigger( 'chosen:updated' );
									$( '#event_new_employee' ).val( '' ).trigger( 'chosen:updated' );
									$( '#event_new_employee_wage' ).val( '' );

								}
							}
						).fail(
							function (data) {
								$( '#tmem-event-employee-list' ).slideToggle();
								$( '.tmem-event-workers-sections-wrap' ).removeClass( 'tmem-mute' );

								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

				// Remove an employee from the event
				$( document.body ).on(
					'click',
					'.remove_event_employee',
					function(event) {

						event.preventDefault();

						var postData = {
							event_id    : $( '#post_ID' ).val(),
							employee_id : $( this ).data( 'employee_id' ),
							action      : 'remove_employee_from_event'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.tmem-event-workers-sections-wrap' ).addClass( 'tmem-mute' );
									$( '#tmem-event-employee-list' ).slideToggle();
								},
								success: function(response) {
									if (response.type !== 'success') {
										alert( 'Error' );
									}
									$( '#tmem-event-employee-list' ).html( response.employees );
									$( '#tmem-event-employee-list' ).slideToggle();
									$( '.tmem-event-workers-sections-wrap' ).removeClass( 'tmem-mute' );

								}
							}
						).fail(
							function (data) {
								$( '#tmem-event-employee-list' ).slideToggle();
								$( '.tmem-event-workers-sections-wrap' ).removeClass( 'tmem-mute' );

								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);
			},

			equipment : function()	{

				$( document.body ).on(
					'change',
					'#_tmem_event_package,#event_addons,#_tmem_event_additional_cost,#_tmem_event_discount',
					function() {
						setCost();
					}
				);

				$( document.body ).on(
					'focusout',
					'#_tmem_event_cost',
					function() {
						if ( tmem_admin_vars.deposit_is_pct ) {
							setDeposit();
						}
					}
				);

				// Update package and add-on options when the event type, date or primary employee are updated.
				$( document.body ).on(
					'change',
					'#_tmem_event_dj,#tmem_event_type,#display_event_date',
					function(event) {
						event.preventDefault();
						var current_deposit = $( '#_tmem_event_deposit' ).val();
						var postData        = {
							package    : $( '#_tmem_event_package option:selected' ).val(),
							addons     : $( '#event_addons' ).val() || [],
							employee   : $( '#_tmem_event_dj' ).val(),
							event_type : $( '#tmem_event_type' ).val(),
							event_date : $( '#_tmem_event_date' ).val(),
							action     : 'refresh_event_package_options'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem-event-equipment-row' ).hide();
									$( '#tmem-equipment-loader' ).show();
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#_tmem_event_package' ).empty(); // Remove existing package options
										$( '#_tmem_event_package' ).append( response.packages );
										$( '#_tmem_event_package' ).trigger( 'chosen:updated' );

										$( '#event_addons' ).empty(); // Remove existing addon options
										$( '#event_addons' ).append( response.addons );
										$( '#event_addons' ).trigger( 'chosen:updated' );

										$( '#tmem-equipment-loader' ).hide();
										$( '#tmem-event-equipment-row' ).show();

										setCost();
									} else {
										alert( response.msg );
									}

									$( '#tmem-equipment-loader' ).hide();
									$( '#tmem-event-equipment-row' ).show();

								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
								$( '#_tmem_event_deposit' ).val( current_deposit );
							}
						);

					}
				);

				// Refresh the add-ons when the package is updated
				$( document.body ).on(
					'change',
					'#_tmem_event_package',
					function(event) {

						event.preventDefault();

						var postData = {
							package  : $( '#_tmem_event_package option:selected' ).val(),
							employee : $( '#_tmem_event_dj' ).val(),
							event_type : $( '#tmem_event_type' ).val(),
							event_date : $( '#_tmem_event_date' ).val(),
							selected : $( '#event_addons' ).val() || [],
							action   : 'refresh_event_addon_options'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem-event-equipment-row' ).hide();
									$( '#tmem-equipment-loader' ).show();
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#event_addons' ).empty();
										$( '#event_addons' ).append( response.addons );
										$( '#event_addons' ).trigger( 'chosen:updated' );
										setCost();

										$( '#tmem-equipment-loader' ).hide();
										$( '#tmem-event-equipment-row' ).show();
									} else {
										alert( response.msg );
									}

									$( '#tmem-equipment-loader' ).hide();
									$( '#tmem-event-equipment-row' ).show();
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

			},

			playlist : function()	{
				$( document.body ).on(
					'change',
					'#_tmem_event_playlist',
					function() {
						$( '#tmem-playlist-limit' ).toggle( 'fast' );
					}
				);
			},

			tasks : function()	{
				// Render the run task button when an event task is selected
				$( document.body ).on(
					'change',
					'#tmem_event_task',
					function() {
						var task = $( this ).val();

						if ( '0' === task ) {
							$( '#tmem-event-task-run' ).addClass( 'tmem-hidden' );
						} else {
							$( '#tmem-event-task-run' ).removeClass( 'tmem-hidden' );
						}
					}
				);

				// Execute the selected event task
				$( document.body ).on(
					'click',
					'#tmem-run-task',
					function(e)   {
						e.preventDefault();

						$( '#tmem-event-tasks' ).addClass( 'tmem-mute' );

						var task = $( '#tmem_event_task' ).val(),
						event_id = $( '#post_ID' ).val();

						if ( 'reject-enquiry' === task ) {
							var client = $( '#client_name' ).val(),
							params     = { page:'tmem-comms', recipient:client, template:tmem_admin_vars.unavailable_template, event_id:event_id, 'tmem-action':'respond_unavailable' },
							url        = tmem_admin_vars.admin_url + 'admin.php?';

							window.location.href = url + $.param( params );
							return;
						}

						var postData = {
							event_id : event_id,
							task     : task,
							action   : 'tmem_execute_event_task'
						};
						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function() {
									$( '#tmem-run-task' ).addClass( 'tmem-hidden' );
									$( '#tmem-spinner' ).css( 'visibility', 'visible' );
								},
								complete : function()	{
									$( '#tmem-event-tasks' ).removeClass( 'tmem-mute' );
								},
								success: function (response) {
									if ( response.success ) {
										$( '#tmem-event-task-run' ).addClass( 'tmem-hidden' );
										$( '#tmem_event_status' ).val( response.data.status );
										$( '#tmem_event_status' ).trigger( 'chosen:updated' );
										$( '.task-history-items' ).html( response.data.history );
										$( '.task-history-items' ).removeClass( 'description' );
										$( '#tmem-run-task' ).removeClass( 'tmem-hidden' );
										$( '#tmem_event_task' ).val( '0' );
										$( '#tmem_event_task' ).trigger( 'chosen:updated' );
										$( '#tmem-spinner' ).css( 'visibility', 'hidden' );
									} else {
										alert( 'Error' );
									}
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				);
			},

			time : function()	{
				// Set the setup date
				$( document.body ).on(
					'change',
					'#display_event_date',
					function() {
						if ( $( '#dj_setup_date' ).val().length < 1 ) {
							$( '#dj_setup_date' ).val( $( '#display_event_date' ).val() );
						}
					}
				);

				// Set the setup time
				if ( tmem_admin_vars.setup_time_interval ) {
					$( document.body ).on(
						'change',
						'#event_start_hr, #event_start_min, #event_start_period',
						function() {
							var hour     = $( '#event_start_hr' ).val();
							var minute   = $( '#event_start_min' ).val();
							var meridiem = '';
							var date     = $( '#_tmem_event_date' ).val();

							if ( 'H:i' !== tmem_admin_vars.time_format ) {
								meridiem = ' ' + $( '#event_start_period' ).val();
							}

							var time = hour + ':' + minute + meridiem;

							var postData = {
								time   : time,
								date   : date,
								action : 'tmem_event_setup_time'
							};
							$.ajax(
								{
									type       : 'POST',
									dataType   : 'json',
									data       : postData,
									url        : ajaxurl,
									success: function (response) {
										$( '#dj_setup_hr' ).val( response.data.hour );
										$( '#dj_setup_min' ).val( response.data.minute );
										if ( 'H:i' !== tmem_admin_vars.time_format ) {
											$( '#dj_setup_period' ).val( response.data.meridiem );
										}
										if ( $( '#dj_setup_date' ).val().length < 1 || $( '#dj_setup_date' ).val() !== response.data.date ) {
											$( '#dj_setup_date' ).val( response.data.date );
											$( '#_tmem_event_djsetup' ).val( response.data.datepicker );
										}
									}
								}
							).fail(
								function (data) {
									if ( window.console && window.console.log ) {
										console.log( data );
									}
								}
							);
						}
					);
				}
			},

			travel : function()	{
				$( document.body ).on(
					'change',
					'#venue_id',
					function()	{
						if ( 'client' === $( '#venue_id' ).val() ) {
							setClientAddress();
						}
					}
				);
				// Update the travel data when the primary employee or venue fields are updated
				$( document.body ).on(
					'change',
					'#_tmem_event_dj,#venue_address1,#venue_address2,#venue_town,#venue_county,#venue_postcode',
					function() {
						setTravelData();
						$( '#_tmem_event_package' ).trigger( 'change' );
					}
				);

				var setClientAddress = function(){
					if ( $( '#client_name' ).length ) {
						var client   = $( '#client_name' ).val();
						var postData = {
							client_id : client,
							action    : 'tmem_set_client_venue'
						};
						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								success: function (response) {
									if (response.address1) {
										$( '#venue_address1' ).val( response.address1 );
									}
									if (response.address2) {
										$( '#venue_address2' ).val( response.address2 );
									}
									if (response.town) {
										$( '#venue_town' ).val( response.town );
									}
									if (response.county) {
										$( '#venue_county' ).val( response.county );
									}
									if (response.postcode) {
										$( '#venue_postcode' ).val( response.postcode );
									}
									setTimeout(
										function(){
											setTravelData();
										},
										1000
									);
									setTimeout(
										function(){
											setCost();
										},
										1750
									);
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				};

			},

			type : function()	{
				// Reveal the input field to add a new event type
				$( document.body ).on(
					'click',
					'.toggle-event-type-option-section',
					function(e) {
						e.preventDefault();

						$( '.tmem-add-event-type-sections-wrap' ).slideToggle();
						if ( $( '.tmem-add-event-type-sections-wrap' ).is( ':visible' ) ) {
							$( '#event_type_name' ).focus();
						}
					}
				);

				// Save a new event type
				$( document.body ).on(
					'click',
					'#tmem-add-event-type',
					function(event) {

						event.preventDefault();

						var postData = {
							type    : $( '#event_type_name' ).val(),
							current : $( '#tmem_event_type' ).val(),
							action  : 'add_event_type'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.tmem-event-option-fields' ).addClass( 'tmem-mute' );
									$( '#tmem-add-event-type' ).hide( 'fast' );
								},
								success: function (response) {
									if (response) {
										if ( 'success' !== response.data.msg ) {
											$( '#event_type_name' ).addClass( 'tmem-form-error' );
											$( '#tmem-add-event-type' ).show( 'fast' );
											return;
										}
										$( '#event_type_name' ).val( '' );
										$( '.tmem-add-event-type-sections-wrap' ).slideToggle();
										$( '#tmem_event_type' ).empty();
										$( '#tmem_event_type' ).append( response.data.event_types );
										$( '#tmem_event_type' ).trigger( 'chosen:updated' );
										$( '#tmem-add-event-type' ).show( 'fast' );

										$( '.tmem-event-option-fields' ).removeClass( 'tmem-mute' );
									} else {
										alert( response.data.msg );
										$( '#tmem-add-event-type' ).show( 'fast' );
									}
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

				// Show/Hide the email templates when the disable emails checkbox is toggled
				$( document.body ).on(
					'click',
					'#tmem_block_emails',
					function() {
						$( '.tmem-quote-template' ).toggle( 'fast' );
					}
				);

			},

			txns : function()	{

				// Show/Hide transaction table
				$( document.body ).on(
					'click',
					'#tmem_txn_toggle',
					function() {
						$( '#tmem_event_txn_table' ).toggle( 'slow' );
					}
				);

				// Show/Hide transaction table
				$( document.body ).on(
					'click',
					'#toggle_add_txn_fields',
					function() {
						$( '#tmem_event_add_txn_table tbody' ).toggle( 'slow' );
						$( '#save-event-txn' ).toggle( 'fast' );
						if ( 'show form' === $( '#toggle_add_txn_fields' ).text() ) {
							$( '#toggle_add_txn_fields' ).text( 'hide form' );
						} else {
							$( '#toggle_add_txn_fields' ).text( 'show form' );
						}
					}
				);

				// Transaction direction
				$( document.body ).on(
					'change',
					'#tmem_txn_direction',
					function() {
						if ( 'In' === $( '#tmem_txn_direction' ).val() ) {
							$( '#tmem_txn_from_container' ).removeClass( 'tmem-hidden' );
							$( '#tmem_txn_to_container' ).addClass( 'tmem-hidden' );
							$( '#tmem-txn-email' ).removeClass( 'tmem-hidden' );
						}
						if ( 'Out' === $( '#tmem_txn_direction' ).val() || '' === $( '#tmem_txn_direction' ).val() ) {
							$( '#tmem_txn_to_container' ).removeClass( 'tmem-hidden' );
							$( '#tmem_txn_from_container' ).addClass( 'tmem-hidden' );
							$( '#tmem-txn-email' ).addClass( 'tmem-hidden' );
						}
					}
				);

				// Save an event transation
				$( document.body ).on(
					'click',
					'#save_transaction',
					function(event) {

						event.preventDefault();

						if ( $( '#tmem_txn_amount' ).val().length < 1 ) {
							alert( tmem_admin_vars.no_txn_amount );
							return false;
						}
						if ( $( '#tmem_txn_date' ).val().length < 1 ) {
							alert( tmem_admin_vars.no_txn_date );
							return false;
						}
						if ( $( '#tmem_txn_for' ).val().length < 1 ) {
							alert( tmem_admin_vars.no_txn_for );
							return false;
						}
						if ( $( '#tmem_txn_src' ).val().length < 1 ) {
							alert( tmem_admin_vars.no_txn_src );
							return false;
						}

						var postData = {
							event_id        : $( '#post_ID' ).val(),
							client          : $( '#client_name' ).val(),
							amount          : $( '#tmem_txn_amount' ).val(),
							date            : $( '#tmem_txn_date' ).val(),
							direction       : $( '#tmem_txn_direction' ).val(),
							from            : $( '#tmem_txn_from' ).val(),
							to              : $( '#tmem_txn_to' ).val(),
							for : $( '#tmem_txn_for' ) {
								.val(),
								src             : $( '#tmem_txn_src' ).val(),
								send_notice     : $( '#tmem_manual_txn_email' ).is( ':checked' ) ? 1 : 0,
								action          : 'add_event_transaction'
							};
						}

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem_event_txn_table' ).replaceWith( '<div id="tmem-loading" class="tmem-loader"><img src="' + tmem_admin_vars.ajax_loader + '" /></div>' );
								},
								success: function (response) {
									if (response.type === 'success') {
										if (response.deposit_paid === 'Y') {
											$( '#deposit_paid' ).prop( 'checked', true );
										}
										if (response.balance_paid === 'Y') {
											$( '#balance_paid' ).prop( 'checked', true );
										}
										if ( response.event_status !== $( '#tmem_event_status' ).val() ) {
											$( '#tmem_event_status' ).val( response.event_status );
										}
									} else {
										alert( response.msg );
									}
									$( '#tmem-loading' ).replaceWith( '<div id="tmem_event_txn_table">' + response.transactions + '</div>' );
								}
							}
						).fail(
							function (data) {
								$( '#tmem-loading' ).replaceWith( '<div id="tmem_event_txn_table">' + response.transactions + '</div>' );
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				);

			},

			venue : function()	{
				// Show manual venue details if pre-selected on event load
				if ( tmem_admin_vars.current_page === 'post.php' ) {
					if ( 'manual' === $( '#venue_id' ).val() ) {
						$( '.tmem-add-event-venue-sections-wrap' ).show();
					}
				}

				// Reveal the venue details on the event screen
				$( document.body ).on(
					'click',
					'.toggle-event-view-venue-option-section',
					function(e) {
						e.preventDefault();

						var show = $( this ).html() === tmem_admin_vars.show_venue_details ? true : false;

						if ( show ) {
							$( this ).html( tmem_admin_vars.hide_venue_details );
						} else {
							$( this ).html( tmem_admin_vars.show_venue_details );
						}

						$( '.tmem-event-venue-details-sections-wrap' ).slideToggle();
					}
				);

				// Reveal the input field to add a new venue from events screen
				$( document.body ).on(
					'click',
					'.toggle-event-add-venue-option-section',
					function(e) {
						e.preventDefault();

						$( '.tmem-add-event-venue-sections-wrap' ).slideToggle();
						if ( $( '.tmem-add-event-venue-sections-wrap' ).is( ':visible' ) ) {
							$( '#venue_name' ).focus();
						}
					}
				);

				// Update the venue details when the venue selection changes
				$( document.body ).on(
					'change',
					'#venue_id',
					function(event) {

						event.preventDefault();

						refreshEventVenueDetails();
					}
				);

				// Add a new venue from the event screen
				$( document.body ).on(
					'click',
					'#tmem-add-venue',
					function(event) {

						event.preventDefault();

						if ( $( '#venue_name' ).val().length < 1 ) {
							$( '#venue_name' ).addClass( 'tmem-form-error' );
							return;
						}

						var postData = {
							venue_name        : $( '#venue_name' ).val(),
							venue_contact     : $( '#venue_contact' ).val(),
							venue_email       : $( '#venue_email' ).val(),
							venue_address1    : $( '#venue_address1' ).val(),
							venue_address2    : $( '#venue_address2' ).val(),
							venue_town        : $( '#venue_town' ).val(),
							venue_county      : $( '#venue_county' ).val(),
							venue_postcode    : $( '#venue_postcode' ).val(),
							venue_phone       : $( '#venue_phone' ).val(),
							action            : 'tmem_add_venue'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.tmem-event-option-fields' ).addClass( 'tmem-mute' );
									$( '#tmem-add-venue' ).hide( 'fast' );
								},
								success: function (response) {
									$( '.tmem-add-event-venue-sections-wrap' ).slideToggle();
									$( '#venue_id' ).empty();
									$( '#venue_id' ).append( response.venue_list );
									$( '#tmem-add-venue' ).show();
									$( '#venue_id' ).trigger( 'chosen:updated' );
									refreshEventVenueDetails();

									$( '.tmem-event-option-fields' ).removeClass( 'tmem-mute' );

									if ( response.type === 'error' ) {
										alert( response.message );
										$( '#tmem-add-venue' ).show( 'fast' );
									}

								}
							}
						).fail(
							function (data) {
								$( '#tmem-add-venue' ).show( 'fast' );

								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				);

			}

		};
		TMEM_Events.init();

		/**
		 * Packages & Addons screen JS
		 */
		var TMEM_Equipment = {

			init : function()	{
				this.add();
				this.remove();
				this.price();
			},

			clone_repeatable : function(row) {

				// Retrieve the highest current key
				var highest = 1;
				var key     = highest;
				row.parent().find( 'tr.tmem_repeatable_row' ).each(
					function() {
						var current = $( this ).data( 'key' );
						if ( parseInt( current ) > highest ) {
							highest = current;
						}
					}
				);
				key = highest += 1;

				clone = row.clone();

				/** manually update any select box values */
				clone.find( 'select' ).each(
					function() {
						$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
					}
				);

				clone.removeClass( 'tmem_add_blank' );

				clone.attr( 'data-key', key );
				clone.find( 'td input, td select, textarea' ).val( '' );
				clone.find( 'input, select, textarea' ).each(
					function() {
						var name = $( this ).attr( 'name' );
						var id   = $( this ).attr( 'id' );

						if ( name ) {

							name = name.replace( /\[(\d+)\]/, '[' + parseInt( key ) + ']' );
							$( this ).attr( 'name', name );

						}

						if ( typeof id !== 'undefined' ) {

							id = id.replace( /(\d+)/, parseInt( key ) );
							$( this ).attr( 'id', id );

						}

					}
				);

				clone.find( 'span.tmem_price_id' ).each(
					function() {
						$( this ).text( parseInt( key ) );
					}
				);

				clone.find( 'span.tmem_file_id' ).each(
					function() {
						$( this ).text( parseInt( key ) );
					}
				);

				clone.find( '.tmem_repeatable_default_input' ).each(
					function() {
						$( this ).val( parseInt( key ) ).removeAttr( 'checked' );
					}
				);

				// Remove Chosen elements
				clone.find( '.search-choice' ).remove();
				clone.find( '.chosen-container' ).remove();

				return clone;
			},

			add : function() {
				$( document.body ).on(
					'click',
					'.submit .tmem_add_repeatable',
					function(e) {
						e.preventDefault();
						var button = $( this ),
						row        = button.parent().parent().prev( 'tr' ),
						clone      = TMEM_Equipment.clone_repeatable( row );

						clone.insertAfter( row ).find( 'input, textarea, select' ).filter( ':visible' ).eq( 0 ).focus();

						// Setup chosen fields again if they exist
						clone.find( '.tmem-select-chosen' ).chosen(
							{
								inherit_select_classes: true,
								placeholder_text_multiple: tmem_admin_vars.select_months
							}
						);
						clone.find( '.package-items' ).css( 'width', '100%' );
					}
				);
			},

			move : function() {

				$( '.tmem_repeatable_table tbody' ).sortable(
					{
						handle: '.tmem_draghandle', items: '.tmem_repeatable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
							var count = 0;
							$( this ).find( 'tr' ).each(
								function() {
									$( this ).find( 'input.tmem_repeatable_index' ).each(
										function() {
											$( this ).val( count );
										}
									);
									count++;
								}
							);
						}
					}
				);

			},

			remove : function() {
				$( document.body ).on(
					'click',
					'.tmem_remove_repeatable',
					function(e) {
						e.preventDefault();

						var row    = $( this ).parent().parent( 'tr' ),
						count      = row.parent().find( 'tr' ).length - 1,
						type       = $( this ).data( 'type' ),
						repeatable = 'tr.tmem_repeatable_' + type + 's';

						if ( type === 'price' ) {
							var price_row_id = row.data( 'key' );
							/** remove from price condition */
							$( '.tmem_repeatable_condition_field option[value="' + price_row_id + '"]' ).remove();
						}

						if ( count > 1 ) {
							$( 'input, select', row ).val( '' );
							row.fadeOut( 'fast' ).remove();
						} else {
							switch ( type ) {
								case 'price':
									alert( tmem_admin_vars.one_month_min );
									break;
								case 'item':
									alert( tmem_admin_vars.one_item_min );
									break;
								default:
									alert( tmem_admin_vars.one_month_min );
									break;
							}
						}

						/* re-index after deleting */
						$( repeatable ).each(
							function( rowIndex ) {
								$( this ).find( 'input, select' ).each(
									function() {
										var name = $( this ).attr( 'name' );
										name     = name.replace( /\[(\d+)\]/, '[' + rowIndex + ']' );
										$( this ).attr( 'name', name ).attr( 'id', name );
									}
								);
							}
						);
					}
				);
			},

			price : function()	{
				$( document.body ).on(
					'click',
					'#_package_restrict_date',
					function() {
						$( '#tmem-package-month-selection' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_addon_restrict_date',
					function() {
						$( '#tmem-addon-month-selection' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_package_variable_pricing',
					function()	{
						$( '#tmem-package-variable-price-fields' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_addon_variable_pricing',
					function()	{
						$( '#tmem-addon-variable-price-fields' ).toggle( 'fast' );
					}
				);

			}
		};
		TMEM_Equipment.init();

		/**
		 * Communications screen JS
		 */
		var TMEM_Comms = {

			init : function()	{
				this.content();
			},

			content: function()	{

				// Refresh the events list for the current recipient
				var loadEvents = function(recipient)	{
					var postData = {
						recipient : recipient,
						action    : 'tmem_user_events_dropdown'
					};

					$.ajax(
						{
							type       : 'POST',
							dataType   : 'json',
							data       : postData,
							url        : ajaxurl,
							beforeSend : function()	{
								$( '#tmem_email_event' ).addClass( 'tmem-updating' );
								$( '#tmem_email_event' ).fadeTo( 'slow', 0.5 );
							},
							success: function (response) {
								$( '#tmem_email_event' ).empty();
								$( '#tmem_email_event' ).append( response.event_list );
								$( '#tmem_email_event' ).fadeTo( 'slow', 1 );
								$( '#tmem_email_event' ).removeClass( 'tmem-updating' );
							}
						}
					).fail(
						function (data) {
							if ( window.console && window.console.log ) {
								console.log( data );
							}
						}
					);

				};

				// Set initial event list when page loads
				if ( tmem_admin_vars.load_recipient ) {
					$( '#tmem_email_to' ).val( tmem_admin_vars.load_recipient );
					loadEvents( tmem_admin_vars.load_recipient );
				}

				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#tmem_email_to',
					function(event) {

						event.preventDefault();

						var recipient = $( '#tmem_email_to' ).val();
						loadEvents( recipient );

					}
				);

				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#tmem_email_template',
					function(event) {

						event.preventDefault();

						var postData = {
							template : $( '#tmem_email_template' ).val(),
							action   : 'tmem_set_email_content'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem_email_subject' ).addClass( 'tmem-updating' );
									$( '#tmem_email_subject' ).fadeTo( 'slow', 0.5 );
									$( '#tmem_email_content' ).addClass( 'tmem-updating' );
									$( '#tmem_email_content' ).fadeTo( 'slow', 0.5 );
									$( '#tmem_email_template' ).addClass( 'tmem-updating' );
									$( '#tmem_email_template' ).fadeTo( 'slow', 0.5 );
									tinymce.execCommand( 'mceToggleEditor',false,'tmem_email_content' );
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#tmem_email_content' ).empty();
										tinyMCE.activeEditor.setContent( '' );
										$( '#tmem_email_subject' ).val( response.updated_subject );
										tinyMCE.activeEditor.setContent( response.updated_content );
										$( '#tmem_email_content' ).val( response.updated_content );
									} else {
										alert( response.msg );
									}
									$( '#tmem_email_subject' ).fadeTo( 'slow', 1 );
									$( '#tmem_email_subject' ).removeClass( 'tmem-updating' );
									$( '#tmem_email_content' ).fadeTo( 'slow', 1 );
									$( '#tmem_email_content' ).removeClass( 'tmem-updating' );
									$( '#tmem_email_template' ).removeClass( 'tmem-updating' );
									$( '#tmem_email_template' ).fadeTo( 'slow', 1 );
									tinymce.execCommand( 'mceToggleEditor',false,'tmem_email_content' );
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);

			}

		};
		TMEM_Comms.init();

		/**
		 * Tasks screen JS
		 */
		var TMEM_Tasks = {

			init : function()	{
				this.template_select();
			},

			template_select: function()	{
				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#tmem_task_email_template',
					function(event) {
						event.preventDefault();

						var postData = {
							template : $( '#tmem_task_email_template' ).val(),
							action   : 'tmem_get_template_title'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#tmem-task-email-subject' ).addClass( 'tmem-updating' );
									$( '#tmem-task-email-subject' ).fadeTo( 'slow', 0.5 );
								},
								success: function (response) {
									if (response.title) {
										$( '#tmem-task-email-subject' ).val( response.title );
									}
									$( '#tmem-task-email-subject' ).fadeTo( 'slow', 1 );
									$( '#tmem-task-email-subject' ).removeClass( 'tmem-updating' );
								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);

					}
				);
			}
		};
		TMEM_Tasks.init();

		/**
		 * Reports / Exports screen JS
		 */
		var TMEM_Reports = {

			init : function() {
				this.date_options();
			},

			date_options : function() {

				// Show hide extended date options
				$( '#tmem-graphs-date-options' ).change(
					function() {
						var $this          = $( this ),
						date_range_options = $( '#tmem-date-range-options' );

						if ( 'other' === $this.val() ) {
							  date_range_options.show();
						} else {
							date_range_options.hide();
						}
					}
				);

			}

		};
		TMEM_Reports.init();

		/**
		 * Export screen JS
		 */
		var TMEM_Export = {

			init : function() {
				this.submit();
				this.dismiss_message();
			},

			submit : function() {

				var self = this;

				$( document.body ).on(
					'submit',
					'.tmem-export-form',
					function(e) {
						e.preventDefault();

						var submitButton = $( this ).find( 'input[type="submit"]' );

						if ( ! submitButton.hasClass( 'button-disabled' ) ) {

							var data = $( this ).serialize();

							submitButton.addClass( 'button-disabled' );
							$( this ).find( '.notice-wrap' ).remove();
							$( this ).append( '<div class="notice-wrap"><span class="spinner is-active"></span><div class="tmem-progress"><div></div></div></div>' );

							// start the process
							self.process_step( 1, data, self );

						}

					}
				);
			},

			process_step : function( step, data, self ) {

				$.ajax(
					{
						type: 'POST',
						url: ajaxurl,
						data: {
							form: data,
							action: 'tmem_do_ajax_export',
							step: step,
						},
						dataType: 'json',
						success: function( response ) {
							if ( 'done' === response.step || response.error || response.success ) {

								// We need to get the actual in progress form, not all forms on the page
								var export_form = $( '.tmem-export-form' ).find( '.tmem-progress' ).parent().parent();
								var notice_wrap = export_form.find( '.notice-wrap' );

								export_form.find( '.button-disabled' ).removeClass( 'button-disabled' );

								if ( response.error ) {

									var error_message = response.message;
									notice_wrap.html( '<div class="updated error"><p>' + error_message + '</p></div>' );

								} else if ( response.success ) {

									var success_message = response.message;
									notice_wrap.html( '<div id="tmem-batch-success" class="updated notice is-dismissible"><p>' + success_message + '<span class="notice-dismiss"></span></p></div>' );

								} else {

									notice_wrap.remove();
									window.location = response.url;

								}

							} else {
								$( '.tmem-progress div' ).animate(
									{
										width: response.percentage + '%',
									},
									50,
									function() {
										// Animation complete.
									}
								);
								self.process_step( parseInt( response.step ), data, self );
							}

						}
					}
				).fail(
					function (response) {
						if ( window.console && window.console.log ) {
							console.log( response );
						}
					}
				);

			},

			dismiss_message : function() {
				$( 'body' ).on(
					'click',
					'#tmem-batch-success .notice-dismiss',
					function() {
						$( '#tmem-batch-success' ).parent().slideUp( 'fast' );
					}
				);
			}

		};
		TMEM_Export.init();

		function refreshEventVenueDetails() {
			// Update the venue details when the venue selection changes
			if ( 'manual' === $( '#venue_id' ).val() || 'client' === $( '#venue_id' ).val() ) {

				$( '.tmem-event-venue-details-sections-wrap' ).hide( 'fast' );
				$( '.tmem-add-event-venue-sections-wrap' ).show( 'fast' );
			} else {
				$( '.tmem-add-event-venue-sections-wrap' ).hide( 'fast' );

				var postData = {
					venue_id   : $( '#venue_id' ).val(),
					event_id   : $( '#post_ID' ).val(),
					action     : 'tmem_refresh_venue_details'
				};

				$.ajax(
					{
						type       : 'POST',
						dataType   : 'json',
						data       : postData,
						url        : ajaxurl,
						success: function (response) {
							$( '#tmem-venue-details-fields' ).html( response.data.venue );
							$( '.tmem-event-venue-details-sections-wrap' ).show( 'fast' );

							var show = $( '.toggle-event-view-venue-option-section' ).html() === tmem_admin_vars.show_venue_details ? true : false;

							if ( show ) {
								$( '.toggle-event-view-venue-option-section' ).html( tmem_admin_vars.hide_venue_details );
							}

						}
					}
				).fail(
					function (data) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					}
				);
				setTravelData();
			}
			$( '#_tmem_event_package' ).trigger( 'change' );
		}

		/*
		* Validation Rules
		******************************************/
		// Comms page
		if ( 'tmem-event_page_tmem-comms' === tmem_admin_vars.current_page ) {
			$( '#tmem_form_send_comms' ).validate(
				{
					errorClass: 'tmem-form-error',
					validClass: 'tmem-form-valid',
					focusInvalid: false,

					rules:	{
					},

					messages:	{
						tmem_email_to      : null,
						tmem_email_subject : null,
						tmem_email_content : null
					}
				}
			);
		}

		// Events page
		if ( tmem_admin_vars.editing_event ) {
			$( '#post' ).validate(
				{
					errorClass: 'tmem-form-error',
					validClass: 'tmem-form-valid',
					focusInvalid: false,

					rules:	{
						client_name : { required: true, minlength : 1 },
						display_event_date : { required: true },
						_tmem_event_cost   : {
							number: true
						},
						_tmem_event_deposit : { number: true }
					},

					messages:	{
						client_name      : null,
						display_event_date : null,
						_tmem_event_cost : null,
						_tmem_event_deposit : null
					}
				}
			);

			$( document.body ).on(
				'click',
				'#save-post',
				function() {
					if ( $( '#_tmem_event_cost' ).val() < '0.01' ) {
						return confirm( tmem_admin_vars.zero_cost );
					}
				}
			);
		}

	}
);

var tmemFormatCurrency = function (value) {
	// Convert the value to a floating point number in case it arrives as a string.
	var numeric = parseFloat( value );
	// Specify the local currency.
	var eventCurrency = tmem_admin_vars.currency;
	var decimalPlaces = tmem_admin_vars.currency_decimals;
	return numeric.toLocaleString( eventCurrency, { style: 'currency', currency: eventCurrency, minimumFractionDigits: decimalPlaces, maximumFractionDigits: decimalPlaces } );
};

var tmemFormatNumber = function(value) {
	// Convert the value to a floating point number in case it arrives as a string.
	var numeric = parseFloat( value );
	// Specify the local currency.
	var eventCurrency = tmem_admin_vars.currency;
	return numeric.toLocaleString( eventCurrency, { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 } );
};

var tmemLabelFormatter = function (label) {
	return '<div style="font-size:12px; text-align:center; padding:2px">' + label + '</div>';
};

var tmemLegendFormatterSources = function (label, series) {
	var slug  = label.toLowerCase().replace( /\s/g, '-' );
	var color = '<div class="tmem-legend-color" style="background-color: ' + series.color + '"></div>';
	var value = '<div class="tmem-pie-legend-item">' + label + ': ' + Math.round( series.percent ) + '% (' + tmemFormatNumber( series.data[0][1] ) + ')</div>';
	var item  = '<div id="' + series.tmem_vars.id + slug + '" class="tmem-legend-item-wrapper">' + color + value + '</div>';

	jQuery( '#tmem-pie-legend-' + series.tmem_vars.id ).append( item );
	return item;
};

var tmemLegendFormatterEarnings = function (label, series) {
	var slug  = label.toLowerCase().replace( /\s/g, '-' );
	var color = '<div class="tmem-legend-color" style="background-color: ' + series.color + '"></div>';
	var value = '<div class="tmem-pie-legend-item">' + label + ': ' + Math.round( series.percent ) + '% (' + tmemFormatCurrency( series.data[0][1] ) + ')</div>';
	var item  = '<div id="' + series.tmem_vars.id + slug + '" class="tmem-legend-item-wrapper">' + color + value + '</div>';

	jQuery( '#tmem-pie-legend-' + series.tmem_vars.id ).append( item );
	return item;
};
