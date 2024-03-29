<?php
/**
 * This template is used to generate the page for the shortcode [tmem-playlist].
 *
 * @version 1.1
 * @author Mike Howard
 * @since 1.5
 * @content_tag client
 * @content_tag event
 * @shortcodes Not Supported
 *
 * Do not customise this file!
 * If you wish to make changes, copy this file to your theme directory /theme/tmem-templates/playlist/playlist-client.php
 * @package is the package selected
 */

global $tmem_event;


$intro_text = sprintf(
	__(
		/* translators: %1 company name %2 is DJ Name */
		'The %1$s playlist management system enables you to give %2$s (your %3$s) an idea of the types of songs you would like played during your %4$s on %5$s.',
		'mobile-events-manager'
	),
	'{company_name}',
	'{employee_firstname}',
	'{artist_label}',
	esc_html( tmem_get_label_singular( true ) ),
	'{event_date}'
);

$guest_text = __( 'You can invite your guests to add their suggestions to your playlist too. They won\'t be able to see any existing entries and you will be able to filter through their suggestions and remove any you do not feel are suitable.', 'mobile-events-manager' );

$share_options = array(
	tmem_playlist_facebook_share( $tmem_event->ID ),
	tmem_playlist_twitter_share( $tmem_event->ID ),
);

$share_text           = implode( '&nbsp;&nbsp;&nbsp;', $share_options );
$form_title           = __( 'Add Playlist Entry', 'mobile-events-manager' );
$artist_label         = __( 'Artist', 'mobile-events-manager' );
$artist_description   = __( 'The name of the artist who sang the song', 'mobile-events-manager' );
$song_label           = __( 'Song', 'mobile-events-manager' );
$song_description     = __( 'The name of the song you are adding', 'mobile-events-manager' );
$category_label       = __( 'Category', 'mobile-events-manager' );
$category_description = __( 'Select the category that best suits your song choice', 'mobile-events-manager' );
$notes_label          = sprintf( esc_html__( 'Notes for your %s', 'mobile-events-manager' ), '{artist_label}' );
$notes_description    = __( 'Is this song important to you? Want it played at a specific time? Let us know here!', 'mobile-events-manager' );
$submit_label         = __( 'Add to Playlist', 'mobile-events-manager' );
$playlist_limit       = tmem_get_event_playlist_limit( $tmem_event->ID );
$limit_reached        = sprintf( esc_html__( 'Your playlist has now reached the maximum of %d allowed songs. To add a new entry, an existing one must first be removed.', 'mobile-events-manager' ), $playlist_limit );
$playlist_closed      = sprintf( esc_html__( 'The playlist system is now closed to allow %1$s to prepare for your %2$s. No further songs can be added at this time.', 'mobile-events-manager' ), '{employee_firstname}', esc_html( tmem_get_label_singular( true ) ) );
$delete_entry         = __( 'Remove', 'mobile-events-manager' );
$total_entries        = tmem_count_playlist_entries( $tmem_event->ID );
$view_playlist        = __( 'View Playlist', 'mobile-events-manager' );
$view_playlist_class  = ' tmem-hidden';
?>

<div id="tmem_playlist_wrap">
	<?php do_action( 'tmem_print_notices' ); ?>
	<?php do_action( 'tmem_playlist_top', $tmem_event->ID ); ?>
	<div id="tmem_playlist_form_wrap" class="tmem_clearfix">
		<?php do_action( 'tmem_before_playlist_form' ); ?>

		<p class="head-nav"><a href="{event_url}"><?php esc_html_e( 'Go Back', 'mobile-events-manager' ); ?></a></p>

		<p><?php echo esc_attr( $intro_text ); ?></p>
		<p><?php echo esc_attr( $guest_text ); ?></p>
		<p class="tmem_playlist_share"><?php echo $share_text; ?></p>
		<p class="guesturl">Allow your guests to add to your playlist, with this unique URL: <br/> <?php echo tmem_content_tag_guest_playlist_url($tmem_event->ID);?></p>

		<?php if ( $tmem_event->playlist_is_open() ) : ?>

			<?php if ( $total_entries < $playlist_limit || 0 === $playlist_limit ) : ?>
				<form id="tmem_playlist_form" class="tmem_form" method="post">
					<?php wp_nonce_field( 'add_playlist_entry', 'tmem_nonce', true, true ); ?>
					<input type="hidden" id="tmem_playlist_event" name="tmem_playlist_event" value="<?php echo esc_attr( $tmem_event->ID ); ?>" />
					<input type="hidden" id="action" name="action" value="tmem_submit_playlist" />

					<div class="tmem-alert tmem-alert-error tmem-hidden"></div>

					<?php do_action( 'tmem_playlist_form_top' ); ?>

					<fieldset id="tmem_playlist_form_fields">
						<legend><?php echo esc_attr( $form_title ); ?></legend>

						<?php if ( $total_entries > 0 ) : ?>
							<?php $view_playlist_class = ''; ?>
						<?php endif; ?>
						<p class="view_current_playlist<?php echo esc_attr( $view_playlist_class ); ?>">
							<a class="tmem-scroller" href="#client-playlist-entries"><?php echo esc_attr( $view_playlist ); ?></a>
						</p>

						<div class="tmem-alert tmem-alert-success tmem-hidden"></div>

						<div id="tmem-playlist-input-fields">
							<p class="tmem_artist_field">
								<label for="tmem_artist">
									<?php echo esc_attr( $artist_label ); ?>
								</label>
								<span class="tmem-description"><?php echo esc_html( $artist_description ); ?></span>

								<input type="text" name="tmem_artist" id="tmem_artist" class="tmem-input" />
							</p>

							<p class="tmem_song_field">
								<label for="tmem_song">
									<?php echo esc_attr( $song_label ); ?> <span class="tmem-required-indicator">*</span>
								</label>
								<span class="tmem-description"><?php echo esc_html( $song_description ); ?></span>

								<input type="text" name="tmem_song" id="tmem_song" class="tmem-input" />
							</p>

							<p class="tmem_category_field">
								<label for="tmem_category">
									<?php echo esc_attr( $category_label ); ?>
								</label>
								<span class="tmem-description"><?php echo esc_html( $category_description ); ?></span>

								<?php echo tmem_playlist_category_dropdown(); ?>
							</p>

							<p class="tmem_notes_field">
								<label for="tmem_notes">
									<?php echo esc_attr( $notes_label ); ?>
								</label>
								<span class="tmem-description"><?php echo esc_html( $notes_description ); ?></span>

								<textarea name="tmem_notes" id="tmem_notes" class="tmem-input"></textarea>
							</p>

							<?php do_action( 'tmem_playlist_form_after_fields' ); ?>

							<input class="button" name="playlist_entry_submit" id="playlist_entry_submit" type="submit" value="<?php echo esc_attr( $submit_label ); ?>" />

							<?php do_action( 'tmem_playlist_form_after_submit' ); ?>
						</div>

					</fieldset>

					<?php do_action( 'tmem_playlist_form_bottom' ); ?>

				</form>

				<?php do_action( 'tmem_after_guest_playlist_form' ); ?>

				<?php else : ?>
					<div class="tmem-alert tmem-alert-info"><?php echo esc_attr( $limit_reached ); ?></div>
				<?php endif; ?>

				<?php else : ?>
					<?php do_action( 'tmem_playlist_closed', esc_attr( $tmem_event->ID ) ); ?>
					<div class="tmem-alert tmem-alert-info"><?php echo esc_attr( $playlist_closed ); ?></div>
				<?php endif; ?>

			</div><!--end #tmem_playlist_form_wrap-->

			<?php do_action( 'tmem_playlist_before_entries' ); ?>

			<?php
			$playlist_entries = tmem_get_playlist_by_category( $tmem_event->ID );
			$entries_class    = $playlist_entries ? '' : ' class="tmem-hidden"';
			$your_playlist    = __( 'Your Current Playlist', 'mobile-events-manager' );
			?>

			<div id="playlist-entries"<?php echo esc_attr( $entries_class ); ?>>

				<a id="client-playlist-entries"></a>
				<h5><?php echo esc_attr( $your_playlist ); ?></h5>

				<p>
					<?php
					printf('Your playlist currently consists of <span class="song-count">%2$d songs.</span>', 'mobile-events-manager',
						esc_attr( $total_entries ));
						?>
					</p>

					<div class="playlist-entry-row-headings">
						<div class="playlist-entry-column">
							<span class="playlist-entry-heading"><?php echo esc_attr( $artist_label ); ?></span>
						</div>
						<div class="playlist-entry-column">
							<span class="playlist-entry-heading"><?php echo esc_attr( $song_label ); ?></span>
						</div>
						<div class="playlist-entry-column">
							<span class="playlist-entry-heading"><?php echo esc_attr( $category_label ); ?></span>
						</div>
						<div class="playlist-entry-column">
							<span class="playlist-entry-heading"><?php esc_html_e( 'Notes', 'mobile-events-manager' ); ?></span>
						</div>
						<div class="playlist-entry-column">
							<span class="playlist-entry-heading"></span>
						</div>
					</div>

					<?php foreach ( $playlist_entries as $category => $category_entries ) : ?>

						<?php foreach ( $category_entries as $entry ) : ?>
							<?php $entry_data = tmem_get_playlist_entry_data( $entry->ID ); ?>

							<div class="playlist-entry-row tmem-playlist-entry-<?php echo esc_attr( $entry->ID ); ?>">
								<div class="playlist-entry-column">
									<span class="playlist-entry"><?php echo esc_attr( $entry_data['artist'] ); ?></span>
								</div>
								<div class="playlist-entry-column">
									<span class="playlist-entry"><?php echo esc_attr( $entry_data['song'] ); ?></span>
								</div>
								<div class="playlist-entry-column">
									<span class="playlist-entry"><?php echo esc_attr( $category ); ?></span>
								</div>
								<div class="playlist-entry-column">
									<span class="playlist-entry">
										<?php if ( 'Guest' === $category ) : ?>
											<?php echo esc_attr( $entry_data['added_by'] ); ?>
											<?php elseif ( ! empty( $entry_data['djnotes'] ) ) : ?>
												<?php echo esc_attr( $entry_data['djnotes'] ); ?>
												<?php else : ?>
													<?php echo '&ndash;'; ?>
												<?php endif; ?>
											</span>
										</div>
										<div class="playlist-entry-column">
											<span class="playlist-entry">
												<a class="tmem-delete playlist-delete-entry" data-event="<?php echo esc_attr( $tmem_event->ID ); ?>" data-entry="<?php echo esc_attr( $entry->ID ); ?>"><?php echo esc_attr( $delete_entry ); ?></a>
											</span>
										</div>
									</div>
								<?php endforeach; ?>

							<?php endforeach; ?>

						</div>

					</div><!-- end of #tmem_playlist_wrap -->
