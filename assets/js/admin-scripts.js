var mem_admin_vars;
jQuery( document ).ready(
	function ($) {

		// Setup Chosen menus
		$( '.mem-select-chosen' ).chosen(
			{
				inherit_select_classes: true,
				placeholder_text_single: mem_admin_vars.one_option,
				placeholder_text_multiple: mem_admin_vars.one_or_more_option
			}
		);

		$( '.mem-select-chosen .chosen-search input' ).each(
			function() {
				var selectElem = $( this ).parent().parent().parent().prev( 'select.mem-select-chosen' ),
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
					placeholder = mem_admin_vars.type_to_search;
				}
				$( this ).children( 'li' ).children( 'input' ).attr( 'placeholder', placeholder );
			}
		);

		// Dismiss admin notices
		$( document ).on(
			'click',
			'.notice-mem-dismiss .notice-dismiss',
			function () {
				var notice = $( this ).closest( '.notice-mem-dismiss' ).data( 'notice' );

				var postData = {
					notice    : notice,
					action       : 'mem_dismiss_notice'
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
			var current_deposit = $( '#_mem_event_deposit' ).val();
			var postData        = {
				current_cost : $( '#_mem_event_cost' ).val(),
				action       : 'update_event_deposit'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					beforeSend : function()	{
						$( '#_mem_event_deposit' ).attr( 'readonly', true );
						$( '#mem-event-pricing-detail' ).addClass( 'mem-mute' );
					},
					success: function (response) {
						if (response.type === 'success') {
							$( '#_mem_event_deposit' ).val( response.deposit );
						} else {
							alert( response.msg );
							$( '#_mem_event_deposit' ).val( current_deposit );
						}
						$( '#mem-event-pricing-detail' ).removeClass( 'mem-mute' );
						$( '#_mem_event_deposit' ).attr( 'readonly', false );
					}
				}
			).fail(
				function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
					$( '#_mem_event_deposit' ).val( current_deposit );
				}
			);
		};

		// Set the event cost.
		var setCost = function()	{

			var current_cost = $( '#_mem_event_cost' ).val();
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
				package         : $( '#_mem_event_package option:selected' ).val(),
				event_id        : $( '#post_ID' ).val(),
				current_cost    : $( '#_mem_event_cost' ).val(),
				event_date      : $( '#_mem_event_date' ).val(),
				venue           : venue,
				employee_id     : $( '#_mem_event_dj' ).val(),
				additional      : $( '#_mem_event_additional_cost' ).val(),
				discount        : $( '#_mem_event_discount' ).val(),
				action          : 'mem_update_event_cost'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					beforeSend : function()	{
						$( '#mem-event-pricing-detail' ).addClass( 'mem-mute' );
					},
					success: function (response) {
						if (response.type === 'success') {
							$( '#_mem_event_package_cost' ).val( response.package_cost );
							$( '#_mem_event_addons_cost' ).val( response.addons_cost );
							$( '#_mem_event_travel_cost' ).val( response.travel_cost );
							$( '#_mem_event_additional_cost' ).val( response.additional_cost );
							$( '#_mem_event_discount' ).val( response.discount );

							var value = Number( response.package_cost ) + Number( response.addons_cost ) + Number( response.travel_cost ) + Number( response.additional_cost );
							value     = Number( value ) - Number( response.discount );
							value     = value.toFixed( 2 );

							$( '#_mem_event_cost' ).val( value );

							if ( mem_admin_vars.update_deposit ) {
								setDeposit();
							}

						} else {
							$( '#_mem_event_cost' ).val( current_cost );
						}

						$( '#mem-event-pricing-detail' ).removeClass( 'mem-mute' );
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
				employee_id : $( '#_mem_event_dj' ).val(),
				venue : venue,
				action  : 'mem_update_travel_data'
			};

			$.ajax(
				{
					type       : 'POST',
					dataType   : 'json',
					data       : postData,
					url        : ajaxurl,
					success: function (response) {
						if (response.type === 'success') {
							$( '.mem-travel-distance' ).parents( 'tr' ).show();
							$( '.mem-travel-directions' ).parents( 'tr' ).show();
							$( '.mem-travel-distance' ).html( response.distance );
							$( '.mem-travel-time' ).html( response.time );
							$( '.mem-travel-cost' ).html( response.cost );
							$( '#travel_directions' ).attr( 'href', response.directions_url );
							$( '#mem_travel_distance' ).val( response.distance );
							$( '#mem_travel_time' ).val( response.time );
							$( '#mem_travel_cost' ).val( response.raw_cost );
							$( '#mem_travel_directions_url' ).val( response.directions_url );
						} else {
							$( '.mem-travel-distance' ).parents( 'tr' ).hide();
							$( '#travel-directions' ).attr( 'href', '' );
							$( '.mem-travel-directions' ).parents( 'tr' ).hide();
							$( '#mem_travel_distance' ).val( '' );
							$( '#mem_travel_time' ).val( '' );
							$( '#mem_travel_cost' ).val( '' );
							$( '#mem_travel_directions_url' ).val( '' );
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
		var MEM_Settings = {
			init : function()	{
				this.general();
				if ( 'admin_page_mem-custom-event-fields' === mem_admin_vars.current_page || 'admin_page_mem-custom-client-fields' === mem_admin_vars.current_page ) {
					this.custom_fields();
				}
			},

			general : function()    {
				var mem_color_picker = $( '.mem-color-picker' );

				if ( mem_color_picker.length ) {
					mem_color_picker.wpColorPicker();
				}
			},

			custom_fields : function()	{
				// Sortable Client Fields
				jQuery( document ).ready(
					function($) 	{
						$( '.mem-client-list-item' ).sortable(
							{
								handle: '.mem_draghandle',
								items: '.mem_sortable_row',
								opacity: 0.6,
								cursor: 'move',
								axis: 'y',
								update: function()	{
									var order = $( this ).sortable( 'serialize', { expression: / (.+) = (.+) / } ) + '&action=mem_update_client_field_order';
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
				$( '.mem-custom-client-list-item,.mem-custom-event-list-item,.mem-custom-venue-list-item' ).sortable(
					{

						handle: '.mem_draghandle',
						items: '.mem_sortable_row',
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
		MEM_Settings.init();

		/**
		 * Availability screen JS
		 */
		var MEM_Availability = {
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
						var show = $( this ).html() === mem_admin_vars.show_avail_form ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_avail_form );
						} else {
							$( this ).html( mem_admin_vars.show_avail_form );
						}

						$( '.mem-availability-checker-fields' ).slideToggle();
					}
				);

				// Toggle display of absence form section
				$( document.body ).on(
					'click',
					'.toggle-add-absence-section',
					function(e) {
						e.preventDefault();
						var show = $( this ).html() === mem_admin_vars.show_absence_form ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_absence_form );
						} else {
							$( this ).html( mem_admin_vars.show_absence_form );
						}

						var header = $( this ).parents( '.mem-availability-row-header' );
						header.siblings( '.mem-availability-add-absence-sections-wrap' ).slideToggle();

						if ( $( '#absence_all_day' ).is( ':checked' ) ) {
							$( '.mem-absence-start-time-option' ).hide( 'fast' );
							$( '.mem-absence-end-time-option' ).hide( 'fast' );
						}

						var first_input;
						if ( show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.mem-availability-add-absence-sections-wrap' ) );
						} else {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.mem-repeatable-row-standard-fields' ) );
						}
						first_input.focus();
					}
				);

				// Toggle display of absence time fields
				$( document.body ).on(
					'change',
					'#absence_all_day',
					function() {
						$( '.mem-absence-start-time-option' ).slideToggle();
						$( '.mem-absence-end-time-option' ).slideToggle();
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

						if ( 'H:i' !== mem_admin_vars.time_format ) {
							start_time_period = $( '#absence_start_time_period' ).val();
							end_time_period   = $( '#absence_end_time_period' ).val();
						}

						if ( ! start_date )	{
							$( '#display_absence_start' ).addClass( 'mem-form-error' );
							return;
						}
						if ( ! end_date ) {
							$( '#display_absence_end' ).addClass( 'mem-form-error' );
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
							action            : 'mem_add_employee_absence'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#mem-add-absence-fields' ).addClass( 'mem-mute' );
								},
								success: function (response) {
									if ( true === response.success ) {
										$( '#mem-calendar' ).fullCalendar( 'refetchEvents' );
										$( '#mem-calendar' ).fullCalendar( 'gotoDate', response.date );
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

									if ( 'H:i' !== mem_admin_vars.time_format ) {
										$( '#absence_start_time_period' ).val( 'am' );
										$( '#absence_end_time_period' ).val( 'am' );
									}

									$( '#mem-calendar' ).removeClass( 'mem-mute' );
									$( '#mem-add-absence-fields' ).removeClass( 'mem-mute' );
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

						$( '#mem-calendar' ).addClass( 'mem-mute' );
						var postData = {
							id     : $( this ).data( 'entry' ),
							action : 'mem_delete_employee_absence'
						};

						$.ajax(
							{
								type: 'POST',
								url: ajaxurl,
								dataType: 'json',
								data: postData,
								success: function(response) {
									if ( true === response.success ) {
										$( '#mem-calendar' ).fullCalendar( 'refetchEvents' );
										$( '.popover' ).hide( 'fast' );
									}
									$( '#mem-calendar' ).removeClass( 'mem-mute' );
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
							display_date.addClass( 'mem-form-error' );
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
									$( '#mem_availability_fields' ).addClass( 'mem-mute' );
								},
								success: function () {
									$( '#mem_availability_fields' ).removeClass( 'mem-mute' );
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
		MEM_Availability.init();

		/**
		 * Events screen JS
		 */
		var MEM_Events = {

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
						var show = $( this ).html() === mem_admin_vars.show_event_options ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_event_options );
						} else {
							$( this ).html( mem_admin_vars.show_event_options );
						}

						$( '.mem-event-options-sections-wrap' ).slideToggle();
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
						var show = $( this ).html() === mem_admin_vars.show_client_form ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_client_form );
						} else {
							$( this ).html( mem_admin_vars.show_client_form );
						}

						var header = $( this ).parents( '.mem-client-row-header' );
						header.siblings( '.mem-client-add-event-sections-wrap' ).slideToggle();

						var first_input;
						if ( show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.mem-client-add-event-sections-wrap' ) );
						} else {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.mem-repeatable-row-standard-fields' ) );
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
						var show = $( this ).html() === mem_admin_vars.show_client_details ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_client_details );
						} else {
							$( this ).html( mem_admin_vars.show_client_details );
						}

						var header = $( this ).parents( '.mem-client-row-header' );
						header.siblings( '.mem-client-details-event-sections-wrap' ).slideToggle();

						var first_input;
						if ( ! show ) {
							first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.mem-repeatable-row-standard-fields' ) );
							first_input.focus();
						}
					}
				);

				// Display client details
				$( document.body ).on(
					'click',
					'#toggle_client_details',
					function() {
						$( '#mem-event-client-details' ).toggle( 'slow' );
					}
				);

				// Update the client details when the client selection changes
				$( document.body ).on(
					'change',
					'#client_name',
					function(event) {

						event.preventDefault();

						if ( '' === $( '#client_name' ).val() ) {
							$( '#mem-event-add-new-client-fields' ).hide( 'slow' );
							return;
						} else if ( 'mem_add_client' === $( '#client_name' ).val() ) {
							$( '#mem-event-add-new-client-fields' ).show( 'slow' );
						} else {

							$( '#mem-event-add-new-client-fields' ).hide( 'slow' );

							var postData = {
								client_id  : $( '#client_name' ).val(),
								event_id   : $( '#post_ID' ).val(),
								action     : 'mem_refresh_client_details'
							};

							$.ajax(
								{
									type       : 'POST',
									dataType   : 'json',
									data       : postData,
									url        : ajaxurl,
									beforeSend : function()	{
										$( '#mem-event-client-details' ).replaceWith( '<div id="mem-loading" class="mem-loader"><img src="' + mem_admin_vars.ajax_loader + '" /></div>' );
									},
									success: function (response) {
										$( '#mem-loading' ).replaceWith( response.client_details );

									}
								}
							).fail(
								function (data) {
									$( '#mem-event-client-details' ).replaceWith( '<div id="mem-loading" class="mem-loader"><img src="' + mem_admin_vars.ajax_loader + '" /></div>' );

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
					'#mem-add-client',
					function(event) {

						event.preventDefault();

						if ( $( '#client_firstname' ).val().length < 1 ) {
							$( '#client_firstname' ).addClass( 'mem-form-error' );
							return;
						}
						if ( $( '#client_email' ).val().length < 1 ) {
							$( '#client_email' ).addClass( 'mem-form-error' );
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
							action           : 'mem_event_add_client'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.mem-client-option-fields' ).addClass( 'mem-mute' );
									$( '#mem-add-client' ).hide( 'fast' );
								},
								success: function (response) {
									$( '#mem-add-client-fields' ).slideToggle();
									$( '#add-client-action' ).html( mem_admin_vars.show_client_form );
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
									$( '#mem-add-client' ).show( 'fast' );
									$( '#_mem_event_block_emails' ).prop( 'checked', false );
									$( '#mem_reset_pw' ).prop( 'checked', true );
									$( '#client_name' ).trigger( 'chosen:updated' );

									$( '.mem-client-option-fields' ).removeClass( 'mem-mute' );

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
						$( '#mem_event_custom_client_fields' ).toggle( 'fast' );
					}
				);
				// Display custom event fields
				$( document.body ).on(
					'click',
					'#toggle_custom_event_fields',
					function() {
						$( '#mem_event_custom_event_fields' ).toggle( 'fast' );
					}
				);
				// Display custom venue fields
				$( document.body ).on(
					'click',
					'#toggle_custom_venue_fields',
					function() {
						$( '#mem_event_custom_venue_fields' ).toggle( 'fast' );
					}
				);

			},

			employee : function()	{

				// Add a new employee role
				$( document.body ).on(
					'click',
					'#new_mem_role',
					function(e) {
						e.preventDefault();

						if ( $( '#add_mem_role' ).hasClass( 'mem-form-error' ) ) {
							$( '#add_mem_role' ).removeClass( 'mem-form-error' );
						}

						if ( $( '#add_mem_role' ).val().length < 1 ) {
							$( '#add_mem_role' ).addClass( 'mem-form-error' );
							return;
						}

						var postData = {
							role_name : $( '#add_mem_role' ).val(),
							action    : 'mem_add_role'
						};

						$.ajax(
							{
								type: 'POST',
								dataType: 'json',
								data: postData,
								url: ajaxurl,
								beforeSend: function()	{
									$( 'input[type="submit"]' ).prop( 'disabled', true );
									$( '#new_mem_role' ).hide();
									$( '#pleasewait' ).show();
									$( '#all_roles' ).addClass( 'mem-mute' );
									$( '#employee_role' ).addClass( 'mem-mute' );
									$( '#all_roles' ).fadeTo( 'slow', 0.5 );
									$( '#employee_role' ).fadeTo( 'slow', 0.5 );
								},
								success: function(response)	{
									if (response.type === 'success') {
										$( '#all_roles' ).empty(); // Remove existing options
										$( '#employee_role' ).empty();
										$( '#all_roles' ).append( response.options );
										$( '#employee_role' ).append( response.options );
										$( '#add_mem_role' ).val( '' );
										$( '#all_roles' ).fadeTo( 'slow', 1 );
										$( '#all_roles' ).removeClass( 'mem-mute' );
										$( '#employee_role' ).fadeTo( 'slow', 1 );
										$( '#employee_role' ).removeClass( 'mem-mute' );
										$( 'input[type="submit"]' ).prop( 'disabled', false );
										$( '#pleasewait' ).hide();
										$( '#new_mem_role' ).show();
									} else {
										alert( response.msg );
										$( '#all_roles' ).fadeTo( 'slow', 1 );
										$( '#all_roles' ).removeClass( 'mem-mute' );
										$( '#employee_role' ).fadeTo( 'slow', 1 );
										$( '#employee_role' ).removeClass( 'mem-mute' );
										$( 'input[type="submit"]' ).prop( 'disabled', false );
										$( '#pleasewait' ).hide();
										$( '#new_mem_role' ).show();
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

						var show = $( this ).html() === mem_admin_vars.show_workers ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_workers );
						} else {
							$( this ).html( mem_admin_vars.show_workers );
						}

						$( '.mem-event-workers-sections-wrap' ).slideToggle();
						if ( $( '.mem-event-workers-sections-wrap' ).is( ':visible' ) ) {
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
									$( '.mem-event-workers-sections-wrap' ).addClass( 'mem-mute' );
									$( '#mem-event-employee-list' ).slideToggle();
								},
								success: function (response) {
									if (response.type !== 'success') {
										alert( response.msg );
									}
									$( '#mem-event-employee-list' ).html( response.employees );
									$( '#mem-event-employee-list' ).slideToggle();
									$( '.mem-event-workers-sections-wrap' ).removeClass( 'mem-mute' );
									$( '#event_new_employee_role' ).val( '' ).trigger( 'chosen:updated' );
									$( '#event_new_employee' ).val( '' ).trigger( 'chosen:updated' );
									$( '#event_new_employee_wage' ).val( '' );

								}
							}
						).fail(
							function (data) {
								$( '#mem-event-employee-list' ).slideToggle();
								$( '.mem-event-workers-sections-wrap' ).removeClass( 'mem-mute' );

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
									$( '.mem-event-workers-sections-wrap' ).addClass( 'mem-mute' );
									$( '#mem-event-employee-list' ).slideToggle();
								},
								success: function(response) {
									if (response.type !== 'success') {
										alert( 'Error' );
									}
									$( '#mem-event-employee-list' ).html( response.employees );
									$( '#mem-event-employee-list' ).slideToggle();
									$( '.mem-event-workers-sections-wrap' ).removeClass( 'mem-mute' );

								}
							}
						).fail(
							function (data) {
								$( '#mem-event-employee-list' ).slideToggle();
								$( '.mem-event-workers-sections-wrap' ).removeClass( 'mem-mute' );

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
					'#_mem_event_package,#event_addons,#_mem_event_additional_cost,#_mem_event_discount',
					function() {
						setCost();
					}
				);

				$( document.body ).on(
					'focusout',
					'#_mem_event_cost',
					function() {
						if ( mem_admin_vars.deposit_is_pct ) {
							setDeposit();
						}
					}
				);

				// Update package and add-on options when the event type, date or primary employee are updated.
				$( document.body ).on(
					'change',
					'#_mem_event_dj,#mem_event_type,#display_event_date',
					function(event) {
						event.preventDefault();
						var current_deposit = $( '#_mem_event_deposit' ).val();
						var postData        = {
							package    : $( '#_mem_event_package option:selected' ).val(),
							addons     : $( '#event_addons' ).val() || [],
							employee   : $( '#_mem_event_dj' ).val(),
							event_type : $( '#mem_event_type' ).val(),
							event_date : $( '#_mem_event_date' ).val(),
							action     : 'refresh_event_package_options'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#mem-event-equipment-row' ).hide();
									$( '#mem-equipment-loader' ).show();
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#_mem_event_package' ).empty(); // Remove existing package options
										$( '#_mem_event_package' ).append( response.packages );
										$( '#_mem_event_package' ).trigger( 'chosen:updated' );

										$( '#event_addons' ).empty(); // Remove existing addon options
										$( '#event_addons' ).append( response.addons );
										$( '#event_addons' ).trigger( 'chosen:updated' );

										$( '#mem-equipment-loader' ).hide();
										$( '#mem-event-equipment-row' ).show();

										setCost();
									} else {
										alert( response.msg );
									}

									$( '#mem-equipment-loader' ).hide();
									$( '#mem-event-equipment-row' ).show();

								}
							}
						).fail(
							function (data) {
								if ( window.console && window.console.log ) {
									console.log( data );
								}
								$( '#_mem_event_deposit' ).val( current_deposit );
							}
						);

					}
				);

				// Refresh the add-ons when the package is updated
				$( document.body ).on(
					'change',
					'#_mem_event_package',
					function(event) {

						event.preventDefault();

						var postData = {
							package  : $( '#_mem_event_package option:selected' ).val(),
							employee : $( '#_mem_event_dj' ).val(),
							event_type : $( '#mem_event_type' ).val(),
							event_date : $( '#_mem_event_date' ).val(),
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
									$( '#mem-event-equipment-row' ).hide();
									$( '#mem-equipment-loader' ).show();
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#event_addons' ).empty();
										$( '#event_addons' ).append( response.addons );
										$( '#event_addons' ).trigger( 'chosen:updated' );
										setCost();

										$( '#mem-equipment-loader' ).hide();
										$( '#mem-event-equipment-row' ).show();
									} else {
										alert( response.msg );
									}

									$( '#mem-equipment-loader' ).hide();
									$( '#mem-event-equipment-row' ).show();
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
					'#_mem_event_playlist',
					function() {
						$( '#mem-playlist-limit' ).toggle( 'fast' );
					}
				);
			},

			tasks : function()	{
				// Render the run task button when an event task is selected
				$( document.body ).on(
					'change',
					'#mem_event_task',
					function() {
						var task = $( this ).val();

						if ( '0' === task ) {
							$( '#mem-event-task-run' ).addClass( 'mem-hidden' );
						} else {
							$( '#mem-event-task-run' ).removeClass( 'mem-hidden' );
						}
					}
				);

				// Execute the selected event task
				$( document.body ).on(
					'click',
					'#mem-run-task',
					function(e)   {
						e.preventDefault();

						$( '#mem-event-tasks' ).addClass( 'mem-mute' );

						var task = $( '#mem_event_task' ).val(),
						event_id = $( '#post_ID' ).val();

						if ( 'reject-enquiry' === task ) {
							var client = $( '#client_name' ).val(),
							params     = { page:'mem-comms', recipient:client, template:mem_admin_vars.unavailable_template, event_id:event_id, 'mem-action':'respond_unavailable' },
							url        = mem_admin_vars.admin_url + 'admin.php?';

							window.location.href = url + $.param( params );
							return;
						}

						var postData = {
							event_id : event_id,
							task     : task,
							action   : 'mem_execute_event_task'
						};
						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function() {
									$( '#mem-run-task' ).addClass( 'mem-hidden' );
									$( '#mem-spinner' ).css( 'visibility', 'visible' );
								},
								complete : function()	{
									$( '#mem-event-tasks' ).removeClass( 'mem-mute' );
								},
								success: function (response) {
									if ( response.success ) {
										$( '#mem-event-task-run' ).addClass( 'mem-hidden' );
										$( '#mem_event_status' ).val( response.data.status );
										$( '#mem_event_status' ).trigger( 'chosen:updated' );
										$( '.task-history-items' ).html( response.data.history );
										$( '.task-history-items' ).removeClass( 'description' );
										$( '#mem-run-task' ).removeClass( 'mem-hidden' );
										$( '#mem_event_task' ).val( '0' );
										$( '#mem_event_task' ).trigger( 'chosen:updated' );
										$( '#mem-spinner' ).css( 'visibility', 'hidden' );
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
				if ( mem_admin_vars.setup_time_interval ) {
					$( document.body ).on(
						'change',
						'#event_start_hr, #event_start_min, #event_start_period',
						function() {
							var hour     = $( '#event_start_hr' ).val();
							var minute   = $( '#event_start_min' ).val();
							var meridiem = '';
							var date     = $( '#_mem_event_date' ).val();

							if ( 'H:i' !== mem_admin_vars.time_format ) {
								meridiem = ' ' + $( '#event_start_period' ).val();
							}

							var time = hour + ':' + minute + meridiem;

							var postData = {
								time   : time,
								date   : date,
								action : 'mem_event_setup_time'
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
										if ( 'H:i' !== mem_admin_vars.time_format ) {
											$( '#dj_setup_period' ).val( response.data.meridiem );
										}
										if ( $( '#dj_setup_date' ).val().length < 1 || $( '#dj_setup_date' ).val() !== response.data.date ) {
											$( '#dj_setup_date' ).val( response.data.date );
											$( '#_mem_event_djsetup' ).val( response.data.datepicker );
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
					'#_mem_event_dj,#venue_address1,#venue_address2,#venue_town,#venue_county,#venue_postcode',
					function() {
						setTravelData();
						$( '#_mem_event_package' ).trigger( 'change' );
					}
				);

				var setClientAddress = function(){
					if ( $( '#client_name' ).length ) {
						var client   = $( '#client_name' ).val();
						var postData = {
							client_id : client,
							action    : 'mem_set_client_venue'
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

						$( '.mem-add-event-type-sections-wrap' ).slideToggle();
						if ( $( '.mem-add-event-type-sections-wrap' ).is( ':visible' ) ) {
							$( '#event_type_name' ).focus();
						}
					}
				);

				// Save a new event type
				$( document.body ).on(
					'click',
					'#mem-add-event-type',
					function(event) {

						event.preventDefault();

						var postData = {
							type    : $( '#event_type_name' ).val(),
							current : $( '#mem_event_type' ).val(),
							action  : 'add_event_type'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.mem-event-option-fields' ).addClass( 'mem-mute' );
									$( '#mem-add-event-type' ).hide( 'fast' );
								},
								success: function (response) {
									if (response) {
										if ( 'success' !== response.data.msg ) {
											$( '#event_type_name' ).addClass( 'mem-form-error' );
											$( '#mem-add-event-type' ).show( 'fast' );
											return;
										}
										$( '#event_type_name' ).val( '' );
										$( '.mem-add-event-type-sections-wrap' ).slideToggle();
										$( '#mem_event_type' ).empty();
										$( '#mem_event_type' ).append( response.data.event_types );
										$( '#mem_event_type' ).trigger( 'chosen:updated' );
										$( '#mem-add-event-type' ).show( 'fast' );

										$( '.mem-event-option-fields' ).removeClass( 'mem-mute' );
									} else {
										alert( response.data.msg );
										$( '#mem-add-event-type' ).show( 'fast' );
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
					'#mem_block_emails',
					function() {
						$( '.mem-quote-template' ).toggle( 'fast' );
					}
				);

			},

			txns : function()	{

				// Show/Hide transaction table
				$( document.body ).on(
					'click',
					'#mem_txn_toggle',
					function() {
						$( '#mem_event_txn_table' ).toggle( 'slow' );
					}
				);

				// Show/Hide transaction table
				$( document.body ).on(
					'click',
					'#toggle_add_txn_fields',
					function() {
						$( '#mem_event_add_txn_table tbody' ).toggle( 'slow' );
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
					'#mem_txn_direction',
					function() {
						if ( 'In' === $( '#mem_txn_direction' ).val() ) {
							$( '#mem_txn_from_container' ).removeClass( 'mem-hidden' );
							$( '#mem_txn_to_container' ).addClass( 'mem-hidden' );
							$( '#mem-txn-email' ).removeClass( 'mem-hidden' );
						}
						if ( 'Out' === $( '#mem_txn_direction' ).val() || '' === $( '#mem_txn_direction' ).val() ) {
							$( '#mem_txn_to_container' ).removeClass( 'mem-hidden' );
							$( '#mem_txn_from_container' ).addClass( 'mem-hidden' );
							$( '#mem-txn-email' ).addClass( 'mem-hidden' );
						}
					}
				);

				// Save an event transation
				$( document.body ).on(
					'click',
					'#save_transaction',
					function(event) {

						event.preventDefault();

						if ( $( '#mem_txn_amount' ).val().length < 1 ) {
							alert( mem_admin_vars.no_txn_amount );
							return false;
						}
						if ( $( '#mem_txn_date' ).val().length < 1 ) {
							alert( mem_admin_vars.no_txn_date );
							return false;
						}
						if ( $( '#mem_txn_for' ).val().length < 1 ) {
							alert( mem_admin_vars.no_txn_for );
							return false;
						}
						if ( $( '#mem_txn_src' ).val().length < 1 ) {
							alert( mem_admin_vars.no_txn_src );
							return false;
						}

						var postData = {
							event_id        : $( '#post_ID' ).val(),
							client          : $( '#client_name' ).val(),
							amount          : $( '#mem_txn_amount' ).val(),
							date            : $( '#mem_txn_date' ).val(),
							direction       : $( '#mem_txn_direction' ).val(),
							from            : $( '#mem_txn_from' ).val(),
							to              : $( '#mem_txn_to' ).val(),
							for : $( '#mem_txn_for' ) {
								.val(),
								src             : $( '#mem_txn_src' ).val(),
								send_notice     : $( '#mem_manual_txn_email' ).is( ':checked' ) ? 1 : 0,
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
									$( '#mem_event_txn_table' ).replaceWith( '<div id="mem-loading" class="mem-loader"><img src="' + mem_admin_vars.ajax_loader + '" /></div>' );
								},
								success: function (response) {
									if (response.type === 'success') {
										if (response.deposit_paid === 'Y') {
											$( '#deposit_paid' ).prop( 'checked', true );
										}
										if (response.balance_paid === 'Y') {
											$( '#balance_paid' ).prop( 'checked', true );
										}
										if ( response.event_status !== $( '#mem_event_status' ).val() ) {
											$( '#mem_event_status' ).val( response.event_status );
										}
									} else {
										alert( response.msg );
									}
									$( '#mem-loading' ).replaceWith( '<div id="mem_event_txn_table">' + response.transactions + '</div>' );
								}
							}
						).fail(
							function (data) {
								$( '#mem-loading' ).replaceWith( '<div id="mem_event_txn_table">' + response.transactions + '</div>' );
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
				if ( mem_admin_vars.current_page === 'post.php' ) {
					if ( 'manual' === $( '#venue_id' ).val() ) {
						$( '.mem-add-event-venue-sections-wrap' ).show();
					}
				}

				// Reveal the venue details on the event screen
				$( document.body ).on(
					'click',
					'.toggle-event-view-venue-option-section',
					function(e) {
						e.preventDefault();

						var show = $( this ).html() === mem_admin_vars.show_venue_details ? true : false;

						if ( show ) {
							$( this ).html( mem_admin_vars.hide_venue_details );
						} else {
							$( this ).html( mem_admin_vars.show_venue_details );
						}

						$( '.mem-event-venue-details-sections-wrap' ).slideToggle();
					}
				);

				// Reveal the input field to add a new venue from events screen
				$( document.body ).on(
					'click',
					'.toggle-event-add-venue-option-section',
					function(e) {
						e.preventDefault();

						$( '.mem-add-event-venue-sections-wrap' ).slideToggle();
						if ( $( '.mem-add-event-venue-sections-wrap' ).is( ':visible' ) ) {
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
					'#mem-add-venue',
					function(event) {

						event.preventDefault();

						if ( $( '#venue_name' ).val().length < 1 ) {
							$( '#venue_name' ).addClass( 'mem-form-error' );
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
							action            : 'mem_add_venue'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '.mem-event-option-fields' ).addClass( 'mem-mute' );
									$( '#mem-add-venue' ).hide( 'fast' );
								},
								success: function (response) {
									$( '.mem-add-event-venue-sections-wrap' ).slideToggle();
									$( '#venue_id' ).empty();
									$( '#venue_id' ).append( response.venue_list );
									$( '#mem-add-venue' ).show();
									$( '#venue_id' ).trigger( 'chosen:updated' );
									refreshEventVenueDetails();

									$( '.mem-event-option-fields' ).removeClass( 'mem-mute' );

									if ( response.type === 'error' ) {
										alert( response.message );
										$( '#mem-add-venue' ).show( 'fast' );
									}

								}
							}
						).fail(
							function (data) {
								$( '#mem-add-venue' ).show( 'fast' );

								if ( window.console && window.console.log ) {
									console.log( data );
								}
							}
						);
					}
				);

			}

		};
		MEM_Events.init();

		/**
		 * Packages & Addons screen JS
		 */
		var MEM_Equipment = {

			init : function()	{
				this.add();
				this.remove();
				this.price();
			},

			clone_repeatable : function(row) {

				// Retrieve the highest current key
				var highest = 1;
				var key     = highest;
				row.parent().find( 'tr.mem_repeatable_row' ).each(
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

				clone.removeClass( 'mem_add_blank' );

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

				clone.find( 'span.mem_price_id' ).each(
					function() {
						$( this ).text( parseInt( key ) );
					}
				);

				clone.find( 'span.mem_file_id' ).each(
					function() {
						$( this ).text( parseInt( key ) );
					}
				);

				clone.find( '.mem_repeatable_default_input' ).each(
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
					'.submit .mem_add_repeatable',
					function(e) {
						e.preventDefault();
						var button = $( this ),
						row        = button.parent().parent().prev( 'tr' ),
						clone      = MEM_Equipment.clone_repeatable( row );

						clone.insertAfter( row ).find( 'input, textarea, select' ).filter( ':visible' ).eq( 0 ).focus();

						// Setup chosen fields again if they exist
						clone.find( '.mem-select-chosen' ).chosen(
							{
								inherit_select_classes: true,
								placeholder_text_multiple: mem_admin_vars.select_months
							}
						);
						clone.find( '.package-items' ).css( 'width', '100%' );
					}
				);
			},

			move : function() {

				$( '.mem_repeatable_table tbody' ).sortable(
					{
						handle: '.mem_draghandle', items: '.mem_repeatable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
							var count = 0;
							$( this ).find( 'tr' ).each(
								function() {
									$( this ).find( 'input.mem_repeatable_index' ).each(
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
					'.mem_remove_repeatable',
					function(e) {
						e.preventDefault();

						var row    = $( this ).parent().parent( 'tr' ),
						count      = row.parent().find( 'tr' ).length - 1,
						type       = $( this ).data( 'type' ),
						repeatable = 'tr.mem_repeatable_' + type + 's';

						if ( type === 'price' ) {
							var price_row_id = row.data( 'key' );
							/** remove from price condition */
							$( '.mem_repeatable_condition_field option[value="' + price_row_id + '"]' ).remove();
						}

						if ( count > 1 ) {
							$( 'input, select', row ).val( '' );
							row.fadeOut( 'fast' ).remove();
						} else {
							switch ( type ) {
								case 'price':
									alert( mem_admin_vars.one_month_min );
									break;
								case 'item':
									alert( mem_admin_vars.one_item_min );
									break;
								default:
									alert( mem_admin_vars.one_month_min );
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
						$( '#mem-package-month-selection' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_addon_restrict_date',
					function() {
						$( '#mem-addon-month-selection' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_package_variable_pricing',
					function()	{
						$( '#mem-package-variable-price-fields' ).toggle( 'fast' );
					}
				);

				$( document.body ).on(
					'click',
					'#_addon_variable_pricing',
					function()	{
						$( '#mem-addon-variable-price-fields' ).toggle( 'fast' );
					}
				);

			}
		};
		MEM_Equipment.init();

		/**
		 * Communications screen JS
		 */
		var MEM_Comms = {

			init : function()	{
				this.content();
			},

			content: function()	{

				// Refresh the events list for the current recipient
				var loadEvents = function(recipient)	{
					var postData = {
						recipient : recipient,
						action    : 'mem_user_events_dropdown'
					};

					$.ajax(
						{
							type       : 'POST',
							dataType   : 'json',
							data       : postData,
							url        : ajaxurl,
							beforeSend : function()	{
								$( '#mem_email_event' ).addClass( 'mem-updating' );
								$( '#mem_email_event' ).fadeTo( 'slow', 0.5 );
							},
							success: function (response) {
								$( '#mem_email_event' ).empty();
								$( '#mem_email_event' ).append( response.event_list );
								$( '#mem_email_event' ).fadeTo( 'slow', 1 );
								$( '#mem_email_event' ).removeClass( 'mem-updating' );
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
				if ( mem_admin_vars.load_recipient ) {
					$( '#mem_email_to' ).val( mem_admin_vars.load_recipient );
					loadEvents( mem_admin_vars.load_recipient );
				}

				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#mem_email_to',
					function(event) {

						event.preventDefault();

						var recipient = $( '#mem_email_to' ).val();
						loadEvents( recipient );

					}
				);

				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#mem_email_template',
					function(event) {

						event.preventDefault();

						var postData = {
							template : $( '#mem_email_template' ).val(),
							action   : 'mem_set_email_content'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#mem_email_subject' ).addClass( 'mem-updating' );
									$( '#mem_email_subject' ).fadeTo( 'slow', 0.5 );
									$( '#mem_email_content' ).addClass( 'mem-updating' );
									$( '#mem_email_content' ).fadeTo( 'slow', 0.5 );
									$( '#mem_email_template' ).addClass( 'mem-updating' );
									$( '#mem_email_template' ).fadeTo( 'slow', 0.5 );
									tinymce.execCommand( 'mceToggleEditor',false,'mem_email_content' );
								},
								success: function (response) {
									if (response.type === 'success') {
										$( '#mem_email_content' ).empty();
										tinyMCE.activeEditor.setContent( '' );
										$( '#mem_email_subject' ).val( response.updated_subject );
										tinyMCE.activeEditor.setContent( response.updated_content );
										$( '#mem_email_content' ).val( response.updated_content );
									} else {
										alert( response.msg );
									}
									$( '#mem_email_subject' ).fadeTo( 'slow', 1 );
									$( '#mem_email_subject' ).removeClass( 'mem-updating' );
									$( '#mem_email_content' ).fadeTo( 'slow', 1 );
									$( '#mem_email_content' ).removeClass( 'mem-updating' );
									$( '#mem_email_template' ).removeClass( 'mem-updating' );
									$( '#mem_email_template' ).fadeTo( 'slow', 1 );
									tinymce.execCommand( 'mceToggleEditor',false,'mem_email_content' );
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
		MEM_Comms.init();

		/**
		 * Tasks screen JS
		 */
		var MEM_Tasks = {

			init : function()	{
				this.template_select();
			},

			template_select: function()	{
				// Update event list when recipient changes
				$( document.body ).on(
					'change',
					'#mem_task_email_template',
					function(event) {
						event.preventDefault();

						var postData = {
							template : $( '#mem_task_email_template' ).val(),
							action   : 'mem_get_template_title'
						};

						$.ajax(
							{
								type       : 'POST',
								dataType   : 'json',
								data       : postData,
								url        : ajaxurl,
								beforeSend : function()	{
									$( '#mem-task-email-subject' ).addClass( 'mem-updating' );
									$( '#mem-task-email-subject' ).fadeTo( 'slow', 0.5 );
								},
								success: function (response) {
									if (response.title) {
										$( '#mem-task-email-subject' ).val( response.title );
									}
									$( '#mem-task-email-subject' ).fadeTo( 'slow', 1 );
									$( '#mem-task-email-subject' ).removeClass( 'mem-updating' );
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
		MEM_Tasks.init();

		/**
		 * Reports / Exports screen JS
		 */
		var MEM_Reports = {

			init : function() {
				this.date_options();
			},

			date_options : function() {

				// Show hide extended date options
				$( '#mem-graphs-date-options' ).change(
					function() {
						var $this          = $( this ),
						date_range_options = $( '#mem-date-range-options' );

						if ( 'other' === $this.val() ) {
							  date_range_options.show();
						} else {
							date_range_options.hide();
						}
					}
				);

			}

		};
		MEM_Reports.init();

		/**
		 * Export screen JS
		 */
		var MEM_Export = {

			init : function() {
				this.submit();
				this.dismiss_message();
			},

			submit : function() {

				var self = this;

				$( document.body ).on(
					'submit',
					'.mem-export-form',
					function(e) {
						e.preventDefault();

						var submitButton = $( this ).find( 'input[type="submit"]' );

						if ( ! submitButton.hasClass( 'button-disabled' ) ) {

							var data = $( this ).serialize();

							submitButton.addClass( 'button-disabled' );
							$( this ).find( '.notice-wrap' ).remove();
							$( this ).append( '<div class="notice-wrap"><span class="spinner is-active"></span><div class="mem-progress"><div></div></div></div>' );

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
							action: 'mem_do_ajax_export',
							step: step,
						},
						dataType: 'json',
						success: function( response ) {
							if ( 'done' === response.step || response.error || response.success ) {

								// We need to get the actual in progress form, not all forms on the page
								var export_form = $( '.mem-export-form' ).find( '.mem-progress' ).parent().parent();
								var notice_wrap = export_form.find( '.notice-wrap' );

								export_form.find( '.button-disabled' ).removeClass( 'button-disabled' );

								if ( response.error ) {

									var error_message = response.message;
									notice_wrap.html( '<div class="updated error"><p>' + error_message + '</p></div>' );

								} else if ( response.success ) {

									var success_message = response.message;
									notice_wrap.html( '<div id="mem-batch-success" class="updated notice is-dismissible"><p>' + success_message + '<span class="notice-dismiss"></span></p></div>' );

								} else {

									notice_wrap.remove();
									window.location = response.url;

								}

							} else {
								$( '.mem-progress div' ).animate(
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
					'#mem-batch-success .notice-dismiss',
					function() {
						$( '#mem-batch-success' ).parent().slideUp( 'fast' );
					}
				);
			}

		};
		MEM_Export.init();

		function refreshEventVenueDetails() {
			// Update the venue details when the venue selection changes
			if ( 'manual' === $( '#venue_id' ).val() || 'client' === $( '#venue_id' ).val() ) {

				$( '.mem-event-venue-details-sections-wrap' ).hide( 'fast' );
				$( '.mem-add-event-venue-sections-wrap' ).show( 'fast' );
			} else {
				$( '.mem-add-event-venue-sections-wrap' ).hide( 'fast' );

				var postData = {
					venue_id   : $( '#venue_id' ).val(),
					event_id   : $( '#post_ID' ).val(),
					action     : 'mem_refresh_venue_details'
				};

				$.ajax(
					{
						type       : 'POST',
						dataType   : 'json',
						data       : postData,
						url        : ajaxurl,
						success: function (response) {
							$( '#mem-venue-details-fields' ).html( response.data.venue );
							$( '.mem-event-venue-details-sections-wrap' ).show( 'fast' );

							var show = $( '.toggle-event-view-venue-option-section' ).html() === mem_admin_vars.show_venue_details ? true : false;

							if ( show ) {
								$( '.toggle-event-view-venue-option-section' ).html( mem_admin_vars.hide_venue_details );
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
			$( '#_mem_event_package' ).trigger( 'change' );
		}

		/*
		* Validation Rules
		******************************************/
		// Comms page
		if ( 'mem-event_page_mem-comms' === mem_admin_vars.current_page ) {
			$( '#mem_form_send_comms' ).validate(
				{
					errorClass: 'mem-form-error',
					validClass: 'mem-form-valid',
					focusInvalid: false,

					rules:	{
					},

					messages:	{
						mem_email_to      : null,
						mem_email_subject : null,
						mem_email_content : null
					}
				}
			);
		}

		// Events page
		if ( mem_admin_vars.editing_event ) {
			$( '#post' ).validate(
				{
					errorClass: 'mem-form-error',
					validClass: 'mem-form-valid',
					focusInvalid: false,

					rules:	{
						client_name : { required: true, minlength : 1 },
						display_event_date : { required: true },
						_mem_event_cost   : {
							number: true
						},
						_mem_event_deposit : { number: true }
					},

					messages:	{
						client_name      : null,
						display_event_date : null,
						_mem_event_cost : null,
						_mem_event_deposit : null
					}
				}
			);

			$( document.body ).on(
				'click',
				'#save-post',
				function() {
					if ( $( '#_mem_event_cost' ).val() < '0.01' ) {
						return confirm( mem_admin_vars.zero_cost );
					}
				}
			);
		}

	}
);

var memFormatCurrency = function (value) {
	// Convert the value to a floating point number in case it arrives as a string.
	var numeric = parseFloat( value );
	// Specify the local currency.
	var eventCurrency = mem_admin_vars.currency;
	var decimalPlaces = mem_admin_vars.currency_decimals;
	return numeric.toLocaleString( eventCurrency, { style: 'currency', currency: eventCurrency, minimumFractionDigits: decimalPlaces, maximumFractionDigits: decimalPlaces } );
};

var memFormatNumber = function(value) {
	// Convert the value to a floating point number in case it arrives as a string.
	var numeric = parseFloat( value );
	// Specify the local currency.
	var eventCurrency = mem_admin_vars.currency;
	return numeric.toLocaleString( eventCurrency, { style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0 } );
};

var memLabelFormatter = function (label) {
	return '<div style="font-size:12px; text-align:center; padding:2px">' + label + '</div>';
};

var memLegendFormatterSources = function (label, series) {
	var slug  = label.toLowerCase().replace( /\s/g, '-' );
	var color = '<div class="mem-legend-color" style="background-color: ' + series.color + '"></div>';
	var value = '<div class="mem-pie-legend-item">' + label + ': ' + Math.round( series.percent ) + '% (' + memFormatNumber( series.data[0][1] ) + ')</div>';
	var item  = '<div id="' + series.mem_vars.id + slug + '" class="mem-legend-item-wrapper">' + color + value + '</div>';

	jQuery( '#mem-pie-legend-' + series.mem_vars.id ).append( item );
	return item;
};

var memLegendFormatterEarnings = function (label, series) {
	var slug  = label.toLowerCase().replace( /\s/g, '-' );
	var color = '<div class="mem-legend-color" style="background-color: ' + series.color + '"></div>';
	var value = '<div class="mem-pie-legend-item">' + label + ': ' + Math.round( series.percent ) + '% (' + memFormatCurrency( series.data[0][1] ) + ')</div>';
	var item  = '<div id="' + series.mem_vars.id + slug + '" class="mem-legend-item-wrapper">' + color + value + '</div>';

	jQuery( '#mem-pie-legend-' + series.mem_vars.id ).append( item );
	return item;
};
