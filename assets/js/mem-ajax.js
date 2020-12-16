/**
 * JS for ajax.
 *
 * @mem-ajax File name.
 * @package MEM
 */

var mem_vars;
jQuery( document ).ready(
	function ($) {

		/* = Datepicker
		====================================================================================== */
		var mem_datepicker = $( '.mem_datepicker' );
		if ( mem_datepicker.length > 0 ) {
			var dateFormat = mem_vars.date_format;
			var firstDay   = mem_vars.first_day;
			mem_datepicker.datepicker(
				{
					dateFormat : dateFormat,
					altfield : '#_mem_event_date',
					altformat : 'yy-mm-dd',
					firstday : firstDay,
					changeyear : true,
					changemonth : true
				}
			);
		}

		/* = When a link with class .mem-scroller is clicked, we scroll
		====================================================================================== */
		$( document ).on(
			'click',
			'.mem-scroller',
			function(e) {
				e.preventDefault();
				var goto = $( this ).attr( 'href' );
				$( 'html, body' ).animate(
					{
						scrollTop: $( goto ).offset().top
					},
					500
				);
			}
		);

		/*=Payments Form
		---------------------------------------------------- */
		// Load the fields for the selected payment method.
		$( 'select#mem-gateway, input.mem-gateway' ).change(
			function () {

				var payment_mode = $( '#mem-gateway option:selected, input.mem-gateway:checked' ).val();

				if ( payment_mode === '0' ) {
					return false;
				}

				mem_load_gateway( payment_mode );

				return false;
			}
		);

		// Auto load first payment gateway.
		if ( mem_vars.is_payment === '1' && $( 'select#mem-gateway, input.mem-gateway' ).length ) {
			setTimeout(
				function() {
					mem_load_gateway( mem_vars.default_gateway );
				},
				200
			);
		}

		$( document.body ).on(
			'click',
			'#mem-payment-part',
			function() {
				$( '#mem-payment-custom' ).show( 'fast' );
			}
		);

		$( document.body ).on(
			'click',
			'#mem-payment-deposit, #mem-payment-balance',
			function() {
				$( '#mem-payment-custom' ).hide( 'fast' );
			}
		);

		$( document ).on(
			'click',
			'#mem_payment_form #mem_payment_submit input[type=submit]',
			function(e) {
				var memPurchaseform = document.getElementById( 'mem_payment_form' );

				if ( typeof memPurchaseform.checkValidity === 'function' && false === memPurchaseform.checkValidity() ) {
					return;
				}

				e.preventDefault();

				$( this ).val( mem_vars.payment_loading );
				$( this ).prop( 'disabled', true );
				$( this ).after( '<span class="mem-payment-ajax"><i class="mem-icon-spinner mem-icon-spin"></i></span>' );

				var valid = mem_validate_payment_form( memPurchaseform );

				if ( valid.type === 'success' )	{
					$( memPurchaseform ).find( '.mem-alert' ).hide( 'fast' );
					$( memPurchaseform ).find( '.error' ).removeClass( 'error' );
					$( memPurchaseform ).submit();
				} else {
					$( memPurchaseform ).find( '.mem-alert' ).show( 'fast' );
					$( memPurchaseform ).find( '.mem-alert' ).text( valid.msg );

					if ( valid.field ) {
						$( '#' + valid.field ).addClass( 'error' );
					}

					$( this ).val( mem_vars.complete_payment );
					$( this ).prop( 'disabled', false );
				}

			}
		);

		/* = Playlist form validation and submission
		====================================================================================== */
		$( document ).on(
			'click',
			'#mem_playlist_form #playlist_entry_submit',
			function(e) {
				var memPlaylistForm = document.getElementById( 'mem_playlist_form' );

				if ( typeof memPlaylistForm.checkValidity === 'function' && false === memPlaylistForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( mem_vars.submit_playlist_loading );
				$( this ).prop( 'disabled', true );
				$( '#mem_playlist_form_fields' ).addClass( 'mem_mute' );
				$( this ).after( ' <span id="mem-loading" class="mem-loader"><img src="' + mem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form        = $( '#mem_playlist_form' );
				var playlistData = $( '#mem_playlist_form' ).serialize();

				$form.find( '.mem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : playlistData,
						url : mem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.mem-alert-error' ).show( 'fast' );
								$form.find( '.mem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								$( '#mem_artist' ).val( '' );
								$( '#mem_song' ).val( '' );
								$( '#mem_notes' ).val( '' );
								$( '#mem_artist' ).focus();

								if ( $( '#playlist-entries' ).hasClass( 'mem-hidden' ) ) {
									$( '#playlist-entries' ).removeClass( 'mem-hidden' );
								}

								$form.find( '.mem-alert-success' ).show( 'fast' ).delay( 3000 ).hide( 'fast' );
								$form.find( '.mem-alert-success' ).html( mem_vars.playlist_updated );

								$( '#playlist-entries' ).append( response.data.row_data );

								if ( response.data.closed )	{
									window.location.href = mem_vars.playlist_page;
								} else {
									$( '.song-count' ).text( response.data.songs );
									$( '.playlist-length' ).text( response.data.length );

									if ( 0 !== response.data.total && $( '.view_current_playlist' ).hasClass( 'mem-hidden' ) ) {
										$( '.view_current_playlist' ).removeClass( 'mem-hidden' );
									}
								}
							}

							$( '#playlist_entry_submit' ).prop( 'disabled', false );
							$( '#mem_playlist_form_fields' ).find( '#mem-loading' ).remove();
							$( '#playlist_entry_submit' ).val( mem_vars.submit_playlist );
							$( '#mem_playlist_form_fields' ).removeClass( 'mem_mute' );

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

		/* = Remove playlist entry
		====================================================================================== */
		$( document ).on(
			'click',
			'.playlist-delete-entry',
			function() {
				var event_id = $( this ).data( 'event' );
				var song_id  = $( this ).data( 'entry' );
				var row      = '.mem-playlist-entry-' + song_id;
				var postData = {
					event_id: event_id,
					song_id : song_id,
					action : 'mem_remove_playlist_entry'
				};

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : postData,
						url : mem_vars.ajaxurl,
						beforeSend : function()	{
							$( '#playlist-entries' ).addClass( 'mem_mute' );
							$( row ).addClass( 'mem_playlist_removing' );
						},
						complete : function() {
							$( '#playlist-entries' ).removeClass( 'mem_mute' );
						},
						success : function (response) {
							if ( response.success )	{
								$( row ).remove();

								if ( response.data.count > 0 ) {
									$( '.song-count' ).text( response.data.songs );
									$( '.playlist-length' ).text( response.data.length );
								} else {
									$( '#playlist-entries' ).addClass( 'mem-hidden' );

									if ( ! $( '.view_current_playlist' ).hasClass( 'mem-hidden' ) ) {
										$( '.view_current_playlist' ).addClass( 'mem-hidden' );
									}
								}

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

		/* = Guest playlist form validation and submission
		====================================================================================== */
		$( document ).on(
			'click',
			'#mem_guest_playlist_form #entry_guest_submit',
			function(e) {
				var memGuestPlaylistForm = document.getElementById( 'mem_guest_playlist_form' );

				if ( typeof memGuestPlaylistForm.checkValidity === 'function' && false === memGuestPlaylistForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( mem_vars.submit_playlist_loading );
				$( this ).prop( 'disabled', true );
				$( '#mem_guest_playlist_form_fields' ).addClass( 'mem_mute' );
				$( this ).after( ' <span id="mem-loading" class="mem-loader"><img src="' + mem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form        = $( '#mem_guest_playlist_form' );
				var playlistData = $( '#mem_guest_playlist_form' ).serialize();

				$form.find( '.mem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : playlistData,
						url : mem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.mem-alert-error' ).show( 'fast' );
								$form.find( '.mem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								if ( $( '.mem_guest_name_field' ).is( ':visible' ) ) {
									$( '.mem_guest_name_field' ).slideToggle( 'fast' );
								}

								$( '#mem-guest-artist' ).val( '' );
								$( '#mem-guest-song' ).val( '' );
								$( '#mem-guest-artist' ).focus();

								if ( $( '#guest-playlist-entries' ).hasClass( 'mem-hidden' ) ) {
									$( '#guest-playlist-entries' ).removeClass( 'mem-hidden' );
								}

								$form.find( '.mem-alert-success' ).show( 'fast' ).delay( 3000 ).hide( 'fast' );
								$form.find( '.mem-alert-success' ).html( mem_vars.playlist_updated );

								$( '#guest-playlist-entries' ).append( response.entry );

								if ( response.closed ) {
									$( '#mem-guest-playlist-input-fields' ).addClass( 'mem-hidden' );
									$( '#guest-playlist-entries' ).append( '<div class="mem-alert mem-alert-info">' + mem_vars.guest_playlist_closed + '</div>' );
								}

							}

							$( '#entry_guest_submit' ).prop( 'disabled', false );
							$( '#mem_guest_playlist_form_fields' ).find( '#mem-loading' ).remove();
							$( '#entry_guest_submit' ).val( mem_vars.submit_guest_playlist );
							$( '#mem_guest_playlist_form_fields' ).removeClass( 'mem_mute' );

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

		/* = Remove guest playlist entry
		====================================================================================== */
		$( document ).on(
			'click',
			'.guest-playlist-delete-entry',
			function() {
				var event_id = $( this ).data( 'event' );
				var song_id  = $( this ).data( 'entry' );
				var row      = '.mem-playlist-entry-' + song_id;
				var postData = {
					event_id: event_id,
					song_id : song_id,
					action : 'mem_remove_guest_playlist_entry'
				};

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : postData,
						url : mem_vars.ajaxurl,
						beforeSend : function()	{
							$( '#guest-playlist-entries' ).addClass( 'mem_mute' );
							$( row ).addClass( 'mem_playlist_removing' );
						},
						complete : function() {
							$( '#guest-playlist-entries' ).removeClass( 'mem_mute' );
						},
						success : function (response) {
							if ( response.success )	{
								$( row ).remove();
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

		/* = Client profile form validation and submission
		====================================================================================== */
		$( document ).on(
			'click',
			'#mem_client_profile_form #update_profile_submit',
			function(e) {
				var memClientProfileForm = document.getElementById( 'mem_client_profile_form' );

				if ( typeof memClientProfileForm.checkValidity === 'function' && false === memClientProfileForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( mem_vars.submit_profile_loading );
				$( this ).prop( 'disabled', true );
				$( '#mem_client_profile_form_fields' ).addClass( 'mem_mute' );
				$( this ).after( ' <span id="mem-loading" class="mem-loader"><img src="' + mem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form       = $( '#mem_client_profile_form' );
				var profileData = $( '#mem_client_profile_form' ).serialize();

				$form.find( '.mem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : profileData,
						url : mem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.mem-alert-error' ).show( 'fast' );
								$form.find( '.mem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								$form.find( '.mem-alert-success' ).show( 'fast' );
								$form.find( '.mem-alert-success' ).html( mem_vars.profile_updated );

								$( 'html, body' ).animate(
									{
										scrollTop: $( '.mem-alert-success' ).offset().top
									},
									500
								);
							}

							if ( response.password ) {
								window.location.href = mem_vars.profile_page;
							} else {
								$( '#update_profile_submit' ).prop( 'disabled', false );
								$( '#mem_client_profile_form_fields' ).find( '#mem-loading' ).remove();
								$( '#update_profile_submit' ).val( mem_vars.submit_client_profile );
								$( '#mem_client_profile_form_fields' ).removeClass( 'mem_mute' );
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

		/*=Availability Checker
		---------------------------------------------------- */
		if ( mem_vars.availability_ajax ) {
			$( '#mem-availability-check' ).submit(
				function(event)	{
					if ( ! $( '#availability_check_date' ).val() ) {
						return false;
					}
					event.preventDefault ? event.preventDefault() : false;
					var date    = $( '#availability_check_date' ).val();
					var postURL = mem_vars.rest_url;
					postURL    += 'availability/';
					postURL    += '?date=' + date;
					$.ajax(
						{
							type: 'GET',
							dataType: 'json',
							url: postURL,
							beforeSend: function()	{
								$( 'input[type="submit"]' ).hide();
								$( '#pleasewait' ).show();
							},
							success: function(response)	{
								var availability = response.data.availability;
								if (availability.response === 'available') {
									if ( mem_vars.available_redirect !== 'text' ) {
										window.location.href = mem_vars.available_redirect + 'mem_avail_date=' + date;
									} else {
										$( '#mem-availability-result' ).replaceWith( '<div id="mem-availability-result">' + availability.message + '</div>' );
										$( '#mem-submit-availability' ).fadeTo( 'slow', 1 );
										$( '#pleasewait' ).hide();
									}
									$( 'input[type="submit"]' ).prop( 'disabled', false );
								} else {
									if ( mem_vars.unavailable_redirect !== 'text' ) {
										window.location.href = mem_vars.unavailable_redirect + 'mem_avail_date=' + date;
									} else {
										$( '#mem-availability-result' ).replaceWith( '<div id="mem-availability-result">' + availability.message + '</div>' );
										$( '#mem-submit-availability' ).fadeTo( 'slow', 1 );
										$( '#pleasewait' ).hide();
									}

									$( 'input[type="submit"]' ).prop( 'disabled', false );
								}
							}
						}
					);
				}
			);
		}

		$( '#mem-availability-check' ).validate(
			{
				rules: {
					'mem-availability-datepicker' : {
						required: true,
					},
				},
				messages: {
					'mem-availability-datepicker': {
						required: mem_vars.required_date_message,
					},
				},

				errorClass: 'mem_form_error',
				validClass: 'mem_form_valid',
			}
		);
	}
);

function mem_validate_payment_form() {

	var msg = false;

	// Make sure an amount is selected.
	var payment = jQuery( 'input[type="radio"][name="mem_payment_amount"]:checked' );

	if ( payment.length === 0 ) {
		return( {msg:mem_vars.no_payment_amount} );
	}

	// If part payment, make sure the value is greater than 0.
	if ( 'part_payment' === payment.val() )	{
		var amount = jQuery( '#part-payment' ).val();

		if ( ! jQuery.isNumeric( amount ) ) {
			return( {type:'error', field:'part-payment', msg:mem_vars.no_payment_amount} );
		}
	}

	if ( mem_vars.require_privacy ) {
		if ( ! jQuery( '#mem-agree-privacy-policy' ).is( ':checked' ) ) {
			return( {type:'error', field:'mem-agree-privacy-policy', msg:mem_vars.privacy_error} );
		}
	}

	if ( mem_vars.require_terms ) {
		if ( ! jQuery( '#mem-agree-terms' ).is( ':checked' ) )	{
			return( {type:'error', field:'mem-agree-terms', msg:mem_vars.terms_error} );
		}
	}

	return( {type:'success'} );

}

function mem_load_gateway( payment_mode ) {

	// Show the ajax loader.
	jQuery( '.mem-payment-ajax' ).show();
	jQuery( '#mem_payment_form_wrap' ).html( '<img src="' + mem_vars.ajax_loader + '"/>' );

	var url = mem_vars.ajaxurl;

	if ( url.indexOf( '?' ) > 0 ) {
		url = url + '&';
	} else {
		url = url + '?';
	}

	url = url + 'payment-mode=' + payment_mode;

	jQuery.post(
		url,
		{ action: 'mem_load_gateway', mem_payment_mode: payment_mode },
		function(response){
			jQuery( '#mem_payment_form_wrap' ).html( response );
			jQuery( '.mem-no-js' ).hide();
			jQuery( '.mem-payment-ajax' ).hide();
		}
	);

}
