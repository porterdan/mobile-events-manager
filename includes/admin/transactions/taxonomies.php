<?php
/**
 * Contains all transaction taxonomy functions
 *
 * @package TMEM
 * @subpackage Transactions
 * @since 1.3
 */

/**
 * Ensure that built-in terms cannot be deleted by removing the
 * delete, edit and quick edit options from the hover menu on the edit screen.
 *
 * @since 1.0
 * @param arr $actions The array of actions in the hover menu
 * obj $tag The object array for the term
 * @return arr $actions The filtered array of actions in the hover menu
 */
function tmem_txn_protected_terms_remove_row_actions( $actions, $tag ) {

	$protected_terms = tmem_get_txn_protected_terms();

	if ( in_array( $tag->slug, $protected_terms ) ) {
		unset( $actions['delete'], $actions['edit'], $actions['inline hide-if-no-js'], $actions['view'] );
	}

	return $actions;

} // tmem_txn_protected_terms_remove_row_actions
add_filter( 'transaction-types_row_actions', 'tmem_txn_protected_terms_remove_row_actions', 10, 2 );

/**
 * Ensure that built-in terms cannot be deleted by removing the
 * bulk action checkboxes
 *
 * @param
 *
 * @return
 */
function tmem_txn_protected_terms_remove_checkbox() {

	if ( ! isset( $_GET['taxonomy'] ) || 'transaction-types' !== $_GET['taxonomy'] ) {
		return;
	}

	$protected_terms = tmem_get_txn_protected_terms();

	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		<?php
		foreach ( $protected_terms as $term_slug ) {

			$obj_term = get_term_by( 'slug', $term_slug, 'transaction-types' );

			if ( ! empty( $obj_term ) ) {
				?>
				$('input#cb-select-<?php echo $obj_term->term_id; ?>').prop('disabled', true).hide();
											 <?php
			}
		}
		?>
	});
	</script>
	<?php
} // tmem_txn_protected_terms_remove_checkbox
add_action( 'admin_footer-edit-tags.php', 'tmem_txn_protected_terms_remove_checkbox' );

/**
 * Retrieve protected (built-in) txn terms.
 *
 * @since 1.3
 * @param
 * @return arr $protected_terms Array of protected terms
 */
function tmem_get_txn_protected_terms() {

	$other_amount_term = get_term_by( 'name', tmem_get_option( 'other_amount_label' ), 'transaction-types' );

	$protected_terms = array(
		'tmem-balance-payments',
		'tmem-deposit-payments',
		'tmem-employee-wages',
		'tmem-merchant-fees',
	);

	if ( ! empty( $other_amount_term ) ) {
		$protected_terms[] = $other_amount_term->slug;
	}

	return apply_filters( 'tmem_txn_protected_terms', $protected_terms );

} // tmem_get_txn_protected_terms

/**
 * Make the Deposit, Balance and Wages term slugs read-only when editing.
 *
 * @since 1.3
 * @param obj $tag The tag object
 * @return str
 */
function tmem_set_protected_txn_terms_readonly( $tag ) {

	$protected_terms = tmem_get_txn_protected_terms();

	if ( in_array( $tag->slug, $protected_terms ) ) {
		?>
		<script type="text/javascript">
		jQuery().ready(function($)	{
			$("#slug").attr('readonly','true');
		});
		</script>
		<?php
	}
} // tmem_set_protected_txn_terms_readonly
add_action( 'transaction-types_edit_form_fields', 'tmem_set_protected_txn_terms_readonly' );

/**
 * Update the transaction category name.
 *
 * Runs when the options are updated and checks if the Label for Deposit,
 * Balance or Other Amount options have been changed
 *
 * @since 1.3
 * @param str $old_value
 * @param str $new_value
 * @return void
 */
function tmem_update_txn_cat( $old_value, $new_value ) {

	$options = array( 'other_amount_label' );

	foreach ( $options as $key ) {

		if ( 'other_amount_label' !== $key || $new_value[ $key ] == $old_value[ $key ] ) {
			continue;
		}

		$term = get_term_by( 'name', $old_value[ $key ], 'transaction-types' );

		wp_update_term(
			$term->term_id,
			'transaction-types',
			array(
				'name' => $new_value[ $key ],
				'slug' => 'tmem-' . sanitize_title( $new_value[ $key ] ),
			)
		);

	}

} // tmem_update_txn_deposit_cat
add_action( 'update_option_tmem_settings', 'tmem_update_txn_cat', 10, 2 );
