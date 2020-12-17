/**
 * JS for ajax.
 *
 * @tmem-ajax File name.
 * @package TMEM
 */

var tmem_vars;
jQuery( document ).ready(
	function ($) {

		/* = Datepicker
		====================================================================================== */
		var tmem_datepicker = $( '.tmem_datepicker' );
		if ( tmem_datepicker.length > 0 ) {
			var dateFormat = tmem_vars.date_format;
			var firstDay   = tmem_vars.first_day;
			tmem_datepicker.datepicker(
				{
					dateFormat : dateFormat,
					altfield : '#_tmem_event_date',
					altformat : 'yy-mm-dd',
					firstday : firstDay,
					changeyear : true,
					changemonth : true
				}
			);
		}

		/* = When a link with class .tmem-scroller is clicked, we scroll
		====================================================================================== */
		$( document ).on(
			'click',
			'.tmem-scroller',
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
		$( 'select#tmem-gateway, input.tmem-gateway' ).change(
			function () {

				var payment_mode = $( '#tmem-gateway option:selected, input.tmem-gateway:checked' ).val();

				if ( payment_mode === '0' ) {
					return false;
				}

				tmem_load_gateway( payment_mode );

				return false;
			}
		);

		// Auto load first payment gateway.
		if ( tmem_vars.is_payment === '1' && $( 'select#tmem-gateway, input.tmem-gateway' ).length ) {
			setTimeout(
				function() {
					tmem_load_gateway( tmem_vars.default_gateway );
				},
				200
			);
		}

		$( document.body ).on(
			'click',
			'#tmem-payment-part',
			function() {
				$( '#tmem-payment-custom' ).show( 'fast' );
			}
		);

		$( document.body ).on(
			'click',
			'#tmem-payment-deposit, #tmem-payment-balance',
			function() {
				$( '#tmem-payment-custom' ).hide( 'fast' );
			}
		);

		$( document ).on(
			'click',
			'#tmem_payment_form #tmem_payment_submit input[type=submit]',
			function(e) {
				var tmemPurchaseform = document.getElementById( 'tmem_payment_form' );

				if ( typeof tmemPurchaseform.checkValidity === 'function' && false === tmemPurchaseform.checkValidity() ) {
					return;
				}

				e.preventDefault();

				$( this ).val( tmem_vars.payment_loading );
				$( this ).prop( 'disabled', true );
				$( this ).after( '<span class="tmem-payment-ajax"><i class="tmem-icon-spinner tmem-icon-spin"></i></span>' );

				var valid = tmem_validate_payment_form( tmemPurchaseform );

				if ( valid.type === 'success' )	{
					$( tmemPurchaseform ).find( '.tmem-alert' ).hide( 'fast' );
					$( tmemPurchaseform ).find( '.error' ).removeClass( 'error' );
					$( tmemPurchaseform ).submit();
				} else {
					$( tmemPurchaseform ).find( '.tmem-alert' ).show( 'fast' );
					$( tmemPurchaseform ).find( '.tmem-alert' ).text( valid.msg );

					if ( valid.field ) {
						$( '#' + valid.field ).addClass( 'error' );
					}

					$( this ).val( tmem_vars.complete_payment );
					$( this ).prop( 'disabled', false );
				}

			}
		);

		/* = Playlist form validation and submission
		====================================================================================== */
		$( document ).on(
			'click',
			'#tmem_playlist_form #playlist_entry_submit',
			function(e) {
				var tmemPlaylistForm = document.getElementById( 'tmem_playlist_form' );

				if ( typeof tmemPlaylistForm.checkValidity === 'function' && false === tmemPlaylistForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( tmem_vars.submit_playlist_loading );
				$( this ).prop( 'disabled', true );
				$( '#tmem_playlist_form_fields' ).addClass( 'tmem_mute' );
				$( this ).after( ' <span id="tmem-loading" class="tmem-loader"><img src="' + tmem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form        = $( '#tmem_playlist_form' );
				var playlistData = $( '#tmem_playlist_form' ).serialize();

				$form.find( '.tmem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : playlistData,
						url : tmem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.tmem-alert-error' ).show( 'fast' );
								$form.find( '.tmem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								$( '#tmem_artist' ).val( '' );
								$( '#tmem_song' ).val( '' );
								$( '#tmem_notes' ).val( '' );
								$( '#tmem_artist' ).focus();

								if ( $( '#playlist-entries' ).hasClass( 'tmem-hidden' ) ) {
									$( '#playlist-entries' ).removeClass( 'tmem-hidden' );
								}

								$form.find( '.tmem-alert-success' ).show( 'fast' ).delay( 3000 ).hide( 'fast' );
								$form.find( '.tmem-alert-success' ).html( tmem_vars.playlist_updated );

								$( '#playlist-entries' ).append( response.data.row_data );

								if ( response.data.closed )	{
									window.location.href = tmem_vars.playlist_page;
								} else {
									$( '.song-count' ).text( response.data.songs );
									$( '.playlist-length' ).text( response.data.length );

									if ( 0 !== response.data.total && $( '.view_current_playlist' ).hasClass( 'tmem-hidden' ) ) {
										$( '.view_current_playlist' ).removeClass( 'tmem-hidden' );
									}
								}
							}

							$( '#playlist_entry_submit' ).prop( 'disabled', false );
							$( '#tmem_playlist_form_fields' ).find( '#tmem-loading' ).remove();
							$( '#playlist_entry_submit' ).val( tmem_vars.submit_playlist );
							$( '#tmem_playlist_form_fields' ).removeClass( 'tmem_mute' );

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
				var row      = '.tmem-playlist-entry-' + song_id;
				var postData = {
					event_id: event_id,
					song_id : song_id,
					action : 'tmem_remove_playlist_entry'
				};

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : postData,
						url : tmem_vars.ajaxurl,
						beforeSend : function()	{
							$( '#playlist-entries' ).addClass( 'tmem_mute' );
							$( row ).addClass( 'tmem_playlist_removing' );
						},
						complete : function() {
							$( '#playlist-entries' ).removeClass( 'tmem_mute' );
						},
						success : function (response) {
							if ( response.success )	{
								$( row ).remove();

								if ( response.data.count > 0 ) {
									$( '.song-count' ).text( response.data.songs );
									$( '.playlist-length' ).text( response.data.length );
								} else {
									$( '#playlist-entries' ).addClass( 'tmem-hidden' );

									if ( ! $( '.view_current_playlist' ).hasClass( 'tmem-hidden' ) ) {
										$( '.view_current_playlist' ).addClass( 'tmem-hidden' );
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
			'#tmem_guest_playlist_form #entry_guest_submit',
			function(e) {
				var tmemGuestPlaylistForm = document.getElementById( 'tmem_guest_playlist_form' );

				if ( typeof tmemGuestPlaylistForm.checkValidity === 'function' && false === tmemGuestPlaylistForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( tmem_vars.submit_playlist_loading );
				$( this ).prop( 'disabled', true );
				$( '#tmem_guest_playlist_form_fields' ).addClass( 'tmem_mute' );
				$( this ).after( ' <span id="tmem-loading" class="tmem-loader"><img src="' + tmem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form        = $( '#tmem_guest_playlist_form' );
				var playlistData = $( '#tmem_guest_playlist_form' ).serialize();

				$form.find( '.tmem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : playlistData,
						url : tmem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.tmem-alert-error' ).show( 'fast' );
								$form.find( '.tmem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								if ( $( '.tmem_guest_name_field' ).is( ':visible' ) ) {
									$( '.tmem_guest_name_field' ).slideToggle( 'fast' );
								}

								$( '#tmem-guest-artist' ).val( '' );
								$( '#tmem-guest-song' ).val( '' );
								$( '#tmem-guest-artist' ).focus();

								if ( $( '#guest-playlist-entries' ).hasClass( 'tmem-hidden' ) ) {
									$( '#guest-playlist-entries' ).removeClass( 'tmem-hidden' );
								}

								$form.find( '.tmem-alert-success' ).show( 'fast' ).delay( 3000 ).hide( 'fast' );
								$form.find( '.tmem-alert-success' ).html( tmem_vars.playlist_updated );

								$( '#guest-playlist-entries' ).append( response.entry );

								if ( response.closed ) {
									$( '#tmem-guest-playlist-input-fields' ).addClass( 'tmem-hidden' );
									$( '#guest-playlist-entries' ).append( '<div class="tmem-alert tmem-alert-info">' + tmem_vars.guest_playlist_closed + '</div>' );
								}

							}

							$( '#entry_guest_submit' ).prop( 'disabled', false );
							$( '#tmem_guest_playlist_form_fields' ).find( '#tmem-loading' ).remove();
							$( '#entry_guest_submit' ).val( tmem_vars.submit_guest_playlist );
							$( '#tmem_guest_playlist_form_fields' ).removeClass( 'tmem_mute' );

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
				var row      = '.tmem-playlist-entry-' + song_id;
				var postData = {
					event_id: event_id,
					song_id : song_id,
					action : 'tmem_remove_guest_playlist_entry'
				};

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : postData,
						url : tmem_vars.ajaxurl,
						beforeSend : function()	{
							$( '#guest-playlist-entries' ).addClass( 'tmem_mute' );
							$( row ).addClass( 'tmem_playlist_removing' );
						},
						complete : function() {
							$( '#guest-playlist-entries' ).removeClass( 'tmem_mute' );
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
			'#tmem_client_profile_form #update_profile_submit',
			function(e) {
				var tmemClientProfileForm = document.getElementById( 'tmem_client_profile_form' );

				if ( typeof tmemClientProfileForm.checkValidity === 'function' && false === tmemClientProfileForm.checkValidity() ) {
					return;
				}

				e.preventDefault();
				$( this ).val( tmem_vars.submit_profile_loading );
				$( this ).prop( 'disabled', true );
				$( '#tmem_client_profile_form_fields' ).addClass( 'tmem_mute' );
				$( this ).after( ' <span id="tmem-loading" class="tmem-loader"><img src="' + tmem_vars.ajax_loader + '" /></span>' );
				$( 'input' ).removeClass( 'error' );

				var $form       = $( '#tmem_client_profile_form' );
				var profileData = $( '#tmem_client_profile_form' ).serialize();

				$form.find( '.tmem-alert' ).hide( 'fast' );

				$.ajax(
					{
						type : 'POST',
						dataType : 'json',
						data : profileData,
						url : tmem_vars.ajaxurl,
						success : function (response) {
							if ( response.error ) {
								$form.find( '.tmem-alert-error' ).show( 'fast' );
								$form.find( '.tmem-alert-error' ).html( response.error );
								$form.find( '#' + response.field ).addClass( 'error' );
								$form.find( '#' + response.field ).focus();
							} else {
								$form.find( '.tmem-alert-success' ).show( 'fast' );
								$form.find( '.tmem-alert-success' ).html( tmem_vars.profile_updated );

								$( 'html, body' ).animate(
									{
										scrollTop: $( '.tmem-alert-success' ).offset().top
									},
									500
								);
							}

							if ( response.password ) {
								window.location.href = tmem_vars.profile_page;
							} else {
								$( '#update_profile_submit' ).prop( 'disabled', false );
								$( '#tmem_client_profile_form_fields' ).find( '#tmem-loading' ).remove();
								$( '#update_profile_submit' ).val( tmem_vars.submit_client_profile );
								$( '#tmem_client_profile_form_fields' ).removeClass( 'tmem_mute' );
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
		if ( tmem_vars.availability_ajax ) {
			$( '#tmem-availability-check' ).submit(
				function(event)	{
					if ( ! $( '#availability_check_date' ).val() ) {
						return false;
					}
					event.preventDefault ? event.preventDefault() : false;
					var date    = $( '#availability_check_date' ).val();
					var postURL = tmem_vars.rest_url;
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
									if ( tmem_vars.available_redirect !== 'text' ) {
										window.location.href = tmem_vars.available_redirect + 'tmem_avail_date=' + date;
									} else {
										$( '#tmem-availability-result' ).replaceWith( '<div id="tmem-availability-result">' + availability.message + '</div>' );
										$( '#tmem-submit-availability' ).fadeTo( 'slow', 1 );
										$( '#pleasewait' ).hide();
									}
									$( 'input[type="submit"]' ).prop( 'disabled', false );
								} else {
									if ( tmem_vars.unavailable_redirect !== 'text' ) {
										window.location.href = tmem_vars.unavailable_redirect + 'tmem_avail_date=' + date;
									} else {
										$( '#tmem-availability-result' ).replaceWith( '<div id="tmem-availability-result">' + availability.message + '</div>' );
										$( '#tmem-submit-availability' ).fadeTo( 'slow', 1 );
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

		$( '#tmem-availability-check' ).validate(
			{
				rules: {
					'tmem-availability-datepicker' : {
						required: true,
					},
				},
				messages: {
					'tmem-availability-datepicker': {
						required: tmem_vars.required_date_message,
					},
				},

				errorClass: 'tmem_form_error',
				validClass: 'tmem_form_valid',
			}
		);
	}
);

function tmem_validate_payment_form() {

	var msg = false;

	// Make sure an amount is selected.
	var payment = jQuery( 'input[type="radio"][name="tmem_payment_amount"]:checked' );

	if ( payment.length === 0 ) {
		return( {msg:tmem_vars.no_payment_amount} );
	}

	// If part payment, make sure the value is greater than 0.
	if ( 'part_payment' === payment.val() )	{
		var amount = jQuery( '#part-payment' ).val();

		if ( ! jQuery.isNumeric( amount ) ) {
			return( {type:'error', field:'part-payment', msg:tmem_vars.no_payment_amount} );
		}
	}

	if ( tmem_vars.require_privacy ) {
		if ( ! jQuery( '#tmem-agree-privacy-policy' ).is( ':checked' ) ) {
			return( {type:'error', field:'tmem-agree-privacy-policy', msg:tmem_vars.privacy_error} );
		}
	}

	if ( tmem_vars.require_terms ) {
		if ( ! jQuery( '#tmem-agree-terms' ).is( ':checked' ) )	{
			return( {type:'error', field:'tmem-agree-terms', msg:tmem_vars.terms_error} );
		}
	}

	return( {type:'success'} );

}

function tmem_load_gateway( payment_mode ) {

	// Show the ajax loader.
	jQuery( '.tmem-payment-ajax' ).show();
	jQuery( '#tmem_payment_form_wrap' ).html( '<img src="' + tmem_vars.ajax_loader + '"/>' );

	var url = tmem_vars.ajaxurl;

	if ( url.indexOf( '?' ) > 0 ) {
		url = url + '&';
	} else {
		url = url + '?';
	}

	url = url + 'payment-mode=' + payment_mode;

	jQuery.post(
		url,
		{ action: 'tmem_load_gateway', tmem_payment_mode: payment_mode },
		function(response){
			jQuery( '#tmem_payment_form_wrap' ).html( response );
			jQuery( '.tmem-no-js' ).hide();
			jQuery( '.tmem-payment-ajax' ).hide();
		}
	);

}
