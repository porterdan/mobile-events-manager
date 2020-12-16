<?php
/**
 * Manage playlists in admin.
 *
 * @since 1.0
 * @package MEM
 * @subpackage Functions/Playlists
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure that the guest playlist term cannot be deleted by removing the
 * delete option from the hover menu on the edit screen.
 *
 * @since 1.5
 * @param arr $actions The array of actions in the hover menu
 * @param obj $tag The object array for the term
 * @return arr $actions The filtered array of actions in the hover menu
 */
function mem_playlist_guest_term_remove_delete_row_action( $actions, $tag ) {

	if ( 'guest' == $tag->slug ) {
		unset( $actions['delete'] );
	}

	return $actions;

} // mem_playlist_guest_term_remove_delete_row_action
add_filter( 'playlist-category_row_actions', 'mem_playlist_guest_term_remove_delete_row_action', 10, 2 );

/**
 * Ensure that the guest playlist category term cannot be deleted by removing the
 * bulk action checkboxes.
 *
 * @since 1.05
 * @return void
 */
function mem_playlist_guest_term_remove_checkbox() {

	if ( ! isset( $_GET['taxonomy'] ) || 'playlist-category' != $_GET['taxonomy'] ) {
		return;
	}

	$terms = mem_get_playlist_categories();

	if ( empty( $terms ) ) {
		return;
	}

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		<?php
		foreach ( $terms as $term ) {

			if ( ! empty( $term->term_id ) && 'guest' == $term->slug ) {
				?>
				$('input#cb-select-<?php echo $term->term_id; ?>').prop('disabled', true).hide();
											 <?php
			}
		}
		?>
	});
	</script>
	<?php
} // kbs_edd_download_terms_remove_checkbox
add_action( 'admin_footer-edit-tags.php', 'mem_playlist_guest_term_remove_checkbox' );

/**
 * Make the guest playlist term slug readonly when editing
 *
 * @since 1.5
 * @param obj $tag The tag object
 * @return str
 */
function mem_playlist_set_guest_term_readonly( $tag ) {

	if ( 'guest' == $tag->slug ) :
		?>
		<script type="text/javascript">
		jQuery().ready(function($)	{
			$("#slug").attr('readonly','true');
		});
		</script>
		<?php
	endif;

} // mem_playlist_set_guest_term_readonly
add_action( 'playlist-category_edit_form_fields', 'mem_playlist_set_guest_term_readonly' );
