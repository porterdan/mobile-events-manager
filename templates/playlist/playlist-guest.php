<?php
/**
 * This template is used to generate the page for the shortcode [tmem-playlist] and is used for guests.
 *
 * @version 1.1
 * @author Mike Howard
 * @since 1.5
 * @content_tag client
 * @content_tag event
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/playlist/playlist-guest.php
 * @package TMEM
 */

// These global vars must remain.
global $tmem_event;

$intro_text = sprintf(
	__( 'Welcome to the %1$s %2$s music playlist management system for %3$s %4$s taking place on %5$s.', 'mobile-events-manager' ),
	'{company_name}',
	'{application_name}',
	"{client_fullname}'s",
	'{event_type}',
	'{event_date}'
);

$lead_in_text = sprintf(
	__( '%1$s has invited you to provide input for the music that will be played during their %2$s. Simply add your selections below and %1$s will be able to review them.', 'mobile-events-manager' ),
	'{client_firstname}',
	esc_html( tmem_get_label_singular( true ) )
);

$playlist_closed = sprintf(
	__( 'The playlist for this %s is now closed and not accepting suggestions', 'mobile-events-manager' ),
	esc_html( tmem_get_label_singular( true ) )
);

$limit_reached = sprintf(
	__( 'The playlist for this %s is full and not accepting suggestions', 'mobile-events-manager' ),
	esc_html( tmem_get_label_singular( true ) )
);

$form_title         = sprintf( esc_html__( '%1$s %2$s Playlist', 'mobile-events-manager' ), "{client_firstname}'s", '{event_type}' );
$existing_entries   = __( "Here's what you've added so far...", 'mobile-events-manager' );
$name_label         = __( 'Name', 'mobile-events-manager' );
$name_description   = sprintf( esc_html__( 'So %s knows who added this song', 'mobile-events-manager' ), '{client_firstname}' );
$artist_label       = __( 'Artist', 'mobile-events-manager' );
$artist_description = __( 'The name of the artist who sang the song', 'mobile-events-manager' );
$song_label         = __( 'Song', 'mobile-events-manager' );
$song_description   = __( 'The name of the song you are suggesting', 'mobile-events-manager' );
$submit_label       = __( 'Suggest Song', 'mobile-events-manager' );

?>
<div id="tmem_guest_playlist_wrap">
	<?php do_action( 'tmem_print_notices' ); ?>
	<div id="tmem_guest_playlist_form_wrap" class="tmem_clearfix">
		<?php do_action( 'tmem_before_guest_playlist_form' ); ?>

		<p><?php echo esc_attr( $intro_text ); ?></p>
		<p><?php echo esc_attr( $lead_in_text ); ?></p>

		<?php if ( $tmem_event->playlist_is_open() ) : ?>
			<?php $event_playlist_limit = tmem_get_event_playlist_limit( $tmem_event->ID ); ?>
			<?php $entries_in_playlist = tmem_count_playlist_entries( $tmem_event->ID ); ?>

			<?php if ( $entries_in_playlist < $event_playlist_limit || 0 === $event_playlist_limit ) : ?> 

				<form id="tmem_guest_playlist_form" class="tmem_form" method="post">
					<?php wp_nonce_field( 'add_guest_playlist_entry', 'tmem_nonce', true, true ); ?>
					<input type="hidden" id="tmem_playlist_event" name="tmem_playlist_event" value="<?php echo esc_html( $tmem_event->ID ); ?>" />
					<input type="hidden" id="action" name="action" value="tmem_submit_guest_playlist" />

					<div class="tmem-alert tmem-alert-error tmem-hidden"></div>
					<div class="tmem-alert tmem-alert-success tmem-hidden"></div>

					<?php do_action( 'tmem_guest_playlist_form_top' ); ?>

					<fieldset id="tmem_guest_playlist_form_fields">
						<legend><?php echo esc_attr( $form_title ); ?></legend>

						<?php do_action( 'tmem_guest_playlist_before_entries' ); ?>
						<div id="guest-playlist-entries" class="tmem-hidden">
							<p><?php echo esc_attr( $existing_entries ); ?></p>
							<div class="guest-playlist-entry-row-headings">
								<div class="guest-playlist-entry-column">
									<span class="guest-playlist-entry-heading"><?php echo esc_attr( $artist_label ); ?></span>
								</div>
								<div class="guest-playlist-entry-column">
									<span class="guest-playlist-entry-heading"><?php echo esc_attr( $song_label ); ?></span>
								</div>
								<div class="guest-playlist-entry-column">
									<span class="guest-playlist-entry-heading"></span>
								</div>
							</div>
						</div>

						<div id="tmem-guest-playlist-input-fields">
							<p class="tmem_guest_name_field">
								<label for="tmem_guest_name">
									<?php echo esc_attr( $name_label ); ?> <span class="tmem-required-indicator">*</span>
								</label>
								<span class="tmem-description"><?php echo esc_html( $name_description ); ?></span>

								<input type="text" name="tmem_guest_name" id="tmem-guest-name" class="tmem-input" />
							</p>

							<p class="tmem_guest_artist_field">
								<label for="tmem_guest_artist">
									<?php echo esc_attr( $artist_label ); ?>
								</label>
								<span class="tmem-description"><?php echo esc_html( $artist_description ); ?></span>

								<input type="text" name="tmem_guest_artist" id="tmem-guest-artist" class="tmem-input" />
							</p>

							<p class="tmem_guest_song_field">
								<label for="tmem_guest_song">
									<?php echo esc_attr( $song_label ); ?> <span class="tmem-required-indicator">*</span>
								</label>
								<span class="tmem-description"><?php echo esc_html( $song_description ); ?></span>

								<input type="text" name="tmem_guest_song" id="tmem-guest-song" class="tmem-input" />
							</p>

							<?php do_action( 'tmem_guest_playlist_form_after_fields' ); ?>

							<input class="button" name="entry_guest_submit" id="entry_guest_submit" type="submit" value="<?php echo esc_attr( $submit_label ); ?>" />

							<?php do_action( 'tmem_guest_playlist_form_after_submit' ); ?>
						</div>

					</fieldset>

					<?php do_action( 'tmem_guest_playlist_form_bottom' ); ?>

				</form>

				<?php do_action( 'tmem_after_guest_playlist_form' ); ?>

			<?php else : ?>
				<div class="tmem-alert tmem-alert-info"><?php echo esc_attr( $limit_reached ); ?></div>
			<?php endif; ?>

		<?php else : ?>
			<?php do_action( 'tmem_guest_playlist_closed', $tmem_event->ID ); ?>
			<div class="tmem-alert tmem-alert-info"><?php echo esc_attr( $playlist_closed ); ?></div>
		<?php endif; ?>

	</div><!--end #tmem_guest_playlist_form_wrap-->
</div><!-- end of #tmem_guest_playlist_wrap -->
