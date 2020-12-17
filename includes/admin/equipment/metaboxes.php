<?php
/**
 * Contains all metaboxe functions for the tmem-package post type
 *
 * @package TMEM
 * @subpackage Equipment
 * @since 1.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define and add the metaboxes for the tmem-package post type.
 * Apply the `tmem_package_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses is_callable to verify the callback function exists.
 *
 * @since 1.4
 * @global $post WP_Post object.
 * @param arr $post Metaboxes for the post.
 * @return void
 */
function tmem_register_package_meta_boxes( $post ) {
	$metaboxes = apply_filters(
		'tmem_package_metaboxes',
		array(
			array(
				'id'         => 'tmem-package-availability-mb',
				'title'      => __( 'Availability', 'mobile-events-manager' ),
				'callback'   => 'tmem_package_metabox_availability_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-package-items-mb',
				'title'      => __( 'Items', 'mobile-events-manager' ),
				'callback'   => 'tmem_package_metabox_items_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-package-pricing-mb',
				'title'      => __( 'Pricing Options', 'mobile-events-manager' ),
				'callback'   => 'tmem_package_metabox_pricing_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output.
	do_action( 'tmem_package_before_metaboxes' );

	// Begin metaboxes.
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check.
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check.
		if ( ! empty( $metabox['permission'] ) && ! tmem_employee_can( $metabox['permission'] ) ) {
			continue;
		}

		// Callback check.
		if ( ! is_callable( $metabox['callback'] ) ) {
			continue;
		}

		add_meta_box(
			$metabox['id'],
			$metabox['title'],
			$metabox['callback'],
			'tmem-package',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output.
	do_action( 'tmem_package_after_metaboxes' );
} // tmem_register_package_meta_boxes
add_action( 'add_meta_boxes_tmem-package', 'tmem_register_package_meta_boxes' );

/**
 * Returns default TMEM Package meta fields.
 *
 * @since 1.4
 * @return arr $fields Array of fields.
 */
function tmem_packages_metabox_fields() {

	$fields = array(
		'_package_employees',
		'_package_event_types',
		'_package_restrict_date',
		'_package_months',
		'_package_items',
		'_package_price',
		'_package_variable_pricing',
		'_package_variable_prices',
	);

	return apply_filters( 'tmem_packages_metabox_fields_save', $fields );
} // tmem_packages_metabox_fields

/**
 * Output for the Package Availability meta box.
 *
 * @since 1.4
 * @param obj $post The post object (WP_Post).
 */
function tmem_package_metabox_availability_callback( $post ) {

	/**
	 * Output the items for the package availability metabox
	 *
	 * @since 1.4
	 * @param int $post The post object (WP_Post).
	 */
	do_action( 'tmem_package_availability_fields', $post );

} // tmem_package_metabox_availability_callback

/**
 * Output for the Package Items meta box.
 *
 * @since 1.4
 * @param obj $post The post object (WP_Post).
 */
function tmem_package_metabox_items_callback( $post ) {

	/**
	 * Output the items for the package items metabox
	 *
	 * @since 1.4
	 * @param int $post The post object (WP_Post).
	 */
	do_action( 'tmem_package_items_fields', $post );

} // tmem_package_metabox_items_callback

/**
 * Output for the Package Pricing meta box.
 *
 * @since 1.4
 * @param obj $post The post object (WP_Post).
 */
function tmem_package_metabox_pricing_callback( $post ) {

	/**
	 * Output the items for the package pricing metabox
	 *
	 * @since 1.4
	 * @param int $post The post object (WP_Post).
	 */
	do_action( 'tmem_package_price_fields', $post );

	wp_nonce_field( 'tmem-package', 'tmem_package_meta_box_nonce' );

} // tmem_package_metabox_pricing_callback

/**
 * Output the package availability employee_row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 */
function tmem_package_metabox_availability_employee_row( $post ) {

	$employees_with = tmem_get_employees_with_package( $post->ID );
	$event_types    = tmem_get_package_event_types( $post->ID );
	$event_label    = esc_html( tmem_get_label_singular( true ) );

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<?php if ( tmem_is_employer() ) : ?>
			<div id="package-employee-select" class="tmem_col col2">
				<p><label for="_package_employees"><?php esc_html_e( 'Employees with this package', 'mobile-events-manager' ); ?></label><br />
				<?php
				echo TMEM()->html->employee_dropdown(
					array(
						'name'             => '_package_employees',
						'selected'         => ! empty( $employees_with ) ? $employees_with : array( 'all' ),
						'show_option_none' => false,
						'show_option_all'  => __( 'All Employees', 'mobile-events-manager' ),
						'group'            => true,
						'chosen'           => true,
						'multiple'         => true,
						'placeholder'      => __( 'Click to select employees', 'mobile-events-manager' ),
					)
				);
				?>
				</p>
			</div>
		<?php else : ?>
			<input type="hidden" name="_package_employees" value="all" />
		<?php endif; ?>

			<div id="package-event-type" class="tmem_col col2">
				<p><label for="_package_event_types"><?php printf( esc_html__( 'Available for %s types', 'mobile-events-manager' ), $event_label ); ?></label><br />
				<?php
				echo TMEM()->html->event_type_dropdown(
					array(
						'name'             => '_package_event_types',
						'selected'         => ! empty( $event_types ) ? $event_types : array( 'all' ),
						'show_option_none' => false,
						'show_option_all'  => sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), ucfirst( $event_label ) ),
						'multiple'         => true,
						'chosen'           => true,
						'placeholder'      => sprintf( esc_html__( 'Click to select %s types', 'mobile-events-manager' ), $event_label ),
					)
				);
				?>
				</p>
			</div>
	</div>
	<?php

} // tmem_package_metabox_availability_employee_row
add_action( 'tmem_package_availability_fields', 'tmem_package_metabox_availability_employee_row', 10 );

/**
 * Output the package availability period row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_package_metabox_availability_period_row( $post ) {

	$restricted = tmem_package_is_restricted_by_date( $post->ID );
	$class      = $restricted ? '' : ' class="tmem-hidden"';

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<div id="package-date-restrict">
			 <p>
			 <?php
				echo TMEM()->html->checkbox(
					array(
						'name'    => '_package_restrict_date',
						'current' => $restricted,
					)
				);
				?>
			<label for="_package_restrict_date"><?php esc_html_e( 'Select if this package is only available during certain months of the year', 'mobile-events-manager' ); ?></label></p>
		</div>

		<div id="tmem-package-month-selection"<?php echo $class; ?>>
			 <p><label for="_package_months"><?php esc_html_e( 'Select the months this package is available', 'mobile-events-manager' ); ?></label><br />
				<?php
				echo TMEM()->html->month_dropdown(
					array(
						'name'        => '_package_months',
						'selected'    => tmem_get_package_months_available( $post->ID ),
						'fullname'    => true,
						'multiple'    => true,
						'chosen'      => true,
						'placeholder' => __( 'Select Months', 'mobile-events-manager' ),
					)
				);
				?>
				</p>
		</div>
	</div>

	<?php

} // tmem_package_metabox_availability_period_row
add_action( 'tmem_package_availability_fields', 'tmem_package_metabox_availability_period_row', 20 );

/**
 * Output the package items row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_package_metabox_items_row( $post ) {

	$items             = tmem_get_package_addons( $post->ID );
	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<div id="tmem-package-items-fields" class="tmem_items_fields">
		<input type="hidden" id="tmem_package_items" class="tmem_package_item_name_field" value="" />
		<div id="tmem_item_fields" class="tmem_meta_table_wrap">
			<table class="widefat tmem_repeatable_table">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'Item', 'mobile-events-manager' ); ?></th>
						<?php do_action( 'tmem_package_price_table_head', $post->ID ); ?>
						<th style="width: 2%"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $items ) ) : ?>
						<?php foreach ( $items as $item ) : ?>
							<tr class="tmem_items_wrapper tmem_repeatable_row">
								<?php do_action( 'tmem_render_item_row', $item, $post->ID ); ?>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr class="tmem_items_wrapper tmem_repeatable_row">
							<?php do_action( 'tmem_render_item_row', null, $post->ID ); ?>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="submit" colspan="2" style="float: none; clear:both; background:#fff;">
							<a class="button-secondary tmem_add_repeatable" style="margin: 6px 0;"><?php esc_html_e( 'Add New Item', 'mobile-events-manager' ); ?></a>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>
	<?php

} // tmem_package_metabox_items_row
add_action( 'tmem_package_items_fields', 'tmem_package_metabox_items_row', 10 );

/**
 * Individual Item Row
 *
 * Used to output a table row for each item associated with a package.
 * Can be called directly, or attached to an action.
 *
 * @since 1.3.9
 *
 * @param str $item
 * @param int $post_id
 */
function tmem_package_metabox_item_row( $item, $post_id ) {

	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<td>
		<?php
		echo TMEM()->html->addons_dropdown(
			array(
				'name'             => '_package_items[]',
				'selected'         => ! empty( $item ) ? $item : '',
				'show_option_none' => false,
				'show_option_all'  => false,
				'employee'         => false,
				'chosen'           => true,
				'class'            => 'package-items',
				'placeholder'      => __( 'Select an add-on', 'mobile-events-manager' ),
				'cost'             => false,
				'desc'             => 7,
				'blank_first'      => true,
				'multiple'         => false,
			)
		);
		?>
	</td>

	<?php do_action( 'tmem_package_item_table_row', $post_id, $item ); ?>

	<td>
		<a href="#" class="tmem_remove_repeatable" data-type="item" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
	</td>
	<?php
} // tmem_package_metabox_item_row
add_action( 'tmem_render_item_row', 'tmem_package_metabox_item_row', 10, 4 );

/**
 * Output the package availability pricing options row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_package_metabox_pricing_options_row( $post ) {

	$month             = 1;
	$price             = tmem_get_package_price( $post->ID );
	$variable          = tmem_package_has_variable_prices( $post->ID );
	$prices            = tmem_get_package_variable_prices( $post->ID );
	$variable_display  = $variable ? '' : ' style="display:none;"';
	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<div id="tmem-package-regular-price-field" class="tmem_pricing_fields">
		<?php
				$price_args = array(
					'name'        => '_package_price',
					'value'       => isset( $price ) ? esc_attr( tmem_format_amount( $price ) ) : '',
					'class'       => 'tmem-currency',
					'desc'        => __( 'Will be used if variable pricing is not in use, or for months that are not defined within variable pricing', 'mobile-events-manager' ),
					'placeholder' => tmem_format_amount( '10.00' ),
				);
				?>
			<p><label for="<?php echo $price_args['name']; ?>"><?php esc_html_e( 'Standard Price', 'mobile-events-manager' ); ?></label><br />
			<?php if ( 'before' === $currency_position ) : ?>
				<?php echo tmem_currency_filter( '' ); ?>
				<?php echo TMEM()->html->text( $price_args ); ?>
			<?php else : ?>
				<?php echo TMEM()->html->text( $price_args ); ?>
				<?php echo tmem_currency_filter( '' ); ?>
			<?php endif; ?></p>

			<?php do_action( 'tmem_package_price_field', $post->ID ); ?>
		</div>
		<?php do_action( 'tmem_after_package_price_field', $post->ID ); ?>
		<div id="package-variable-price">
			<p>
			<?php
			echo TMEM()->html->checkbox(
				array(
					'name'    => '_package_variable_pricing',
					'current' => $variable,
				)
			);
			?>
			<label for="_package_variable_pricing"><?php esc_html_e( 'Enable variable pricing', 'mobile-events-manager' ); ?></label></p>
		</div>
		<?php do_action( 'tmem_after_package_variable_pricing_field', $post->ID ); ?>
	</div>

	<div id="tmem-package-variable-price-fields" class="tmem_pricing_fields" <?php echo $variable_display; ?>>
		<input type="hidden" id="tmem_variable_prices" class="tmem_variable_prices_name_field" value="" />
		<div id="tmem_price_fields" class="tmem_meta_table_wrap">
			<table class="widefat tmem_repeatable_table">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'Month', 'mobile-events-manager' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Price', 'mobile-events-manager' ); ?></th>
						<?php do_action( 'tmem_package_price_table_head', $post->ID ); ?>
						<th style="width: 2%"></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $prices ) ) :
						foreach ( $prices as $key => $value ) :
							$months = isset( $value['months'] ) ? $value['months'] : '';
							$amount = isset( $value['amount'] ) ? $value['amount'] : '';
							$index  = isset( $value['index'] ) ? $value['index'] : $key;
							$args   = apply_filters( 'tmem_price_row_args', compact( 'months', 'amount' ), $value );
							?>
								<tr class="tmem_variable_prices_wrapper tmem_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
								<?php do_action( 'tmem_render_package_price_row', $key, $args, $post->ID, $index ); ?>
								</tr>
							<?php
							endforeach;
						else :
							?>
						<tr class="tmem_variable_prices_wrapper tmem_repeatable_row" data-key="1">
							<?php do_action( 'tmem_render_package_price_row', 1, array(), $post->ID, 1 ); ?>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="submit" colspan="3" style="float: none; clear:both; background:#fff;">
							<a class="button-secondary tmem_add_repeatable" style="margin: 6px 0;"><?php esc_html_e( 'Add New Price', 'mobile-events-manager' ); ?></a>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>

	<?php

} // tmem_package_metabox_pricing_options_row
add_action( 'tmem_package_price_fields', 'tmem_package_metabox_pricing_options_row', 10 );

/**
 * Individual Price Row
 *
 * Used to output a table row for each price associated with a package.
 * Can be called directly, or attached to an action.
 *
 * @since 1.3.9
 *
 * @param int $key
 * @param arr $args
 * @param int $post_id
 * @param int $index
 */
function tmem_package_metabox_price_row( $key, $args, $post_id, $index ) {

	$defaults = array(
		'name'   => null,
		'amount' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<td>
		<?php
		echo TMEM()->html->month_dropdown(
			array(
				'name'        => '_package_variable_prices[' . $key . '][months]',
				'selected'    => ! empty( $args['months'] ) ? $args['months'] : '',
				'fullname'    => true,
				'multiple'    => true,
				'chosen'      => true,
				'placeholder' => __( 'Select Months', 'mobile-events-manager' ),
			)
		);
		?>
	</td>

	<td>
		<?php
			$price_args = array(
				'name'        => '_package_variable_prices[' . $key . '][amount]',
				'value'       => tmem_format_amount( $args['amount'] ),
				'placeholder' => tmem_format_amount( 10.00 ),
				'class'       => 'tmem-price-field',
			);
			?>

		<?php if ( 'before' === $currency_position ) : ?>
			<span><?php echo tmem_currency_filter( '' ); ?></span>
			<?php echo TMEM()->html->text( $price_args ); ?>
		<?php else : ?>
			<?php echo TMEM()->html->text( $price_args ); ?>
			<?php echo tmem_currency_filter( '' ); ?>
		<?php endif; ?>
	</td>

	<?php do_action( 'tmem_package_price_table_row', $post_id, $key, $args ); ?>

	<td>
		<a href="#" class="tmem_remove_repeatable" data-type="price" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
	</td>
	<?php
} // tmem_package_metabox_price_row
add_action( 'tmem_render_package_price_row', 'tmem_package_metabox_price_row', 10, 4 );

/***********************************************************
 * Addons
 **********************************************************/

/**
 * Define and add the metaboxes for the tmem-addon post type.
 * Apply the `tmem_addon_add_metaboxes` filter to allow for filtering of metaboxes and settings.
 * Uses is_callable to verify the callback function exists.
 *
 * @since 1.4
 * @global $post WP_Post object.
 * @return void
 */
function tmem_register_addon_meta_boxes( $post ) {

	$metaboxes = apply_filters(
		'tmem_addon_metaboxes',
		array(
			array(
				'id'         => 'tmem-addon-availability-mb',
				'title'      => __( 'Availability', 'mobile-events-manager' ),
				'callback'   => 'tmem_addon_metabox_availability_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
			array(
				'id'         => 'tmem-addon-pricing-mb',
				'title'      => __( 'Pricing Options', 'mobile-events-manager' ),
				'callback'   => 'tmem_addon_metabox_pricing_callback',
				'context'    => 'normal',
				'priority'   => 'high',
				'args'       => array(),
				'dependancy' => '',
				'permission' => '',
			),
		)
	);
	// Runs before metabox output
	do_action( 'tmem_addon_before_metaboxes' );

	// Begin metaboxes
	foreach ( $metaboxes as $metabox ) {
		// Dependancy check
		if ( ! empty( $metabox['dependancy'] ) && false === $metabox['dependancy'] ) {
			continue;
		}

		// Permission check
		if ( ! empty( $metabox['permission'] ) && ! tmem_employee_can( $metabox['permission'] ) ) {
			continue;
		}

		// Callback check
		if ( ! is_callable( $metabox['callback'] ) ) {
			continue;
		}

		add_meta_box(
			$metabox['id'],
			$metabox['title'],
			$metabox['callback'],
			'tmem-addon',
			$metabox['context'],
			$metabox['priority'],
			$metabox['args']
		);
	}

	// Runs after metabox output
	do_action( 'tmem_addon_after_metaboxes' );
} // tmem_register_addon_meta_boxes
add_action( 'add_meta_boxes_tmem-addon', 'tmem_register_addon_meta_boxes' );

/**
 * Returns default TMEM Addon meta fields.
 *
 * @since 1.4
 * @return arr $fields Array of fields.
 */
function tmem_addons_metabox_fields() {

	$fields = array(
		'_addon_employees',
		'_addon_event_types',
		'_addon_restrict_date',
		'_addon_months',
		'_addon_price',
		'_addon_variable_pricing',
		'_addon_variable_prices',
	);

	return apply_filters( 'tmem_addons_metabox_fields_save', $fields );
} // tmem_addons_metabox_fields

/**
 * Output for the Addon Availability meta box.
 *
 * @since 1.4
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_addon_metabox_availability_callback( $post ) {

	/*
	 * Output the items for the addon availability metabox
	 * @since	1.4
	 * @param	int	$post_id	The addon post ID
	 */
	do_action( 'tmem_addon_availability_fields', $post );

} // tmem_addon_metabox_availability_callback

/**
 * Output for the Addon Pricing meta box.
 *
 * @since 1.4
 * @param obj $post The post object (WP_Post).
 * @return
 */
function tmem_addon_metabox_pricing_callback( $post ) {

	/*
	 * Output the items for the addon pricing metabox
	 * @since	1.4
	 * @param	int	$post_id	The addon post ID
	 */
	do_action( 'tmem_addon_price_fields', $post );

	wp_nonce_field( 'tmem-addon', 'tmem_addon_meta_box_nonce' );

} // tmem_addon_metabox_pricing_callback

/**
 * Output the addon availability employee_row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_addon_metabox_availability_employee_row( $post ) {

	$employees_with = tmem_get_employees_with_addon( $post->ID );
	$event_types    = tmem_get_addon_event_types( $post->ID );
	$event_label    = esc_html( tmem_get_label_singular( true ) );

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<?php if ( tmem_is_employer() ) : ?>
			<div id="addon-employee-select" class="tmem_col col2">
				<p><label for="_addon_employees"><?php esc_html_e( 'Employees with this package', 'mobile-events-manager' ); ?></label><br />
				<?php
				echo TMEM()->html->employee_dropdown(
					array(
						'name'             => '_addon_employees',
						'selected'         => ! empty( $employees_with ) ? $employees_with : array( 'all' ),
						'show_option_none' => false,
						'show_option_all'  => __( 'All Employees', 'mobile-events-manager' ),
						'group'            => true,
						'chosen'           => true,
						'multiple'         => true,
						'placeholder'      => __( 'Click to select employees', 'mobile-events-manager' ),
					)
				);
				?>
				</p>
			</div>
		<?php else : ?>
			<input type="hidden" name="_addon_employees" value="all" />
		<?php endif; ?>

			<div id="addon-event-type" class="tmem_col col2">
				<p><label for="_addon_event_types"><?php printf( esc_html__( 'Available for %s types', 'mobile-events-manager' ), $event_label ); ?></label><br />
				<?php
				echo TMEM()->html->event_type_dropdown(
					array(
						'name'             => '_addon_event_types',
						'selected'         => ! empty( $event_types ) ? $event_types : array( 'all' ),
						'show_option_none' => false,
						'show_option_all'  => sprintf( esc_html__( 'All %s Types', 'mobile-events-manager' ), ucfirst( $event_label ) ),
						'multiple'         => true,
						'chosen'           => true,
						'placeholder'      => sprintf( esc_html__( 'Click to select %s types', 'mobile-events-manager' ), $event_label ),
					)
				);
				?>
				</p>
			</div>
	</div>
	<?php

} // tmem_addon_metabox_availability_employee_row
add_action( 'tmem_addon_availability_fields', 'tmem_addon_metabox_availability_employee_row', 10 );

/**
 * Output the addon availability availability period row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_addon_metabox_availability_period_row( $post ) {

	$restricted = tmem_addon_is_restricted_by_date( $post->ID );
	$class      = $restricted ? '' : ' class="tmem-hidden"';

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<div id="addon-date-restrict">
			 <p>
			 <?php
				echo TMEM()->html->checkbox(
					array(
						'name'    => '_addon_restrict_date',
						'current' => $restricted,
					)
				);
				?>
			<label for="_addon_restrict_date"><?php esc_html_e( 'Select if this add-on is only available during certain months of the year', 'mobile-events-manager' ); ?></label></p>
		</div>

		<div id="tmem-addon-month-selection"<?php echo $class; ?>>
			 <p><label for="_addon_months"><?php esc_html_e( 'Select the months this add-on is available', 'mobile-events-manager' ); ?></label><br />
				<?php
				echo TMEM()->html->month_dropdown(
					array(
						'name'        => '_addon_months',
						'selected'    => tmem_get_addon_months_available( $post->ID ),
						'fullname'    => true,
						'multiple'    => true,
						'chosen'      => true,
						'placeholder' => __( 'Select Months', 'mobile-events-manager' ),
					)
				);
				?>
				</p>
		</div>
	</div>

	<?php

} // tmem_addon_metabox_availability_period_row
add_action( 'tmem_addon_availability_fields', 'tmem_addon_metabox_availability_period_row', 20 );

/**
 * Output the addon availability pricing options row
 *
 * @since 1.4
 * @param int $post The WP_Post object.
 * @return str
 */
function tmem_addon_metabox_pricing_options_row( $post ) {

	$month             = 1;
	$price             = tmem_get_addon_price( $post->ID );
	$variable          = tmem_addon_has_variable_prices( $post->ID );
	$prices            = tmem_get_addon_variable_prices( $post->ID );
	$variable_display  = $variable ? '' : ' style="display:none;"';
	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<div class="tmem_field_wrap tmem_form_fields">
		<div id="tmem-addon-regular-price-field" class="tmem_pricing_fields">
		<?php
				$price_args = array(
					'name'        => '_addon_price',
					'value'       => isset( $price ) ? esc_attr( tmem_format_amount( $price ) ) : '',
					'class'       => 'tmem-currency',
					'placeholder' => tmem_format_amount( '10.00' ),
					'desc'        => __( 'Will be used if variable pricing is not in use, or for months that are not defined within variable pricing', 'mobile-events-manager' ),
				);
				?>
			<p><label for="<?php echo $price_args['name']; ?>"><?php esc_html_e( 'Standard Price', 'mobile-events-manager' ); ?></label><br />
			<?php if ( 'before' === $currency_position ) : ?>
				<?php echo tmem_currency_filter( '' ); ?>
				<?php echo TMEM()->html->text( $price_args ); ?>
			<?php else : ?>
				<?php echo TMEM()->html->text( $price_args ); ?>
				<?php echo tmem_currency_filter( '' ); ?>
			<?php endif; ?></p>

			<?php do_action( 'tmem_addon_price_field', $post->ID ); ?>
		</div>
		<?php do_action( 'tmem_after_addon_price_field', $post->ID ); ?>
		<div id="addon-variable-price">
			<p>
			<?php
			echo TMEM()->html->checkbox(
				array(
					'name'    => '_addon_variable_pricing',
					'current' => $variable,
				)
			);
			?>
			<label for="_addon_variable_pricing"><?php esc_html_e( 'Enable variable pricing', 'mobile-events-manager' ); ?></label></p>
		</div>
		<?php do_action( 'tmem_after_addon_variable_pricing_field', $post->ID ); ?>
	</div>

	<div id="tmem-addon-variable-price-fields" class="tmem_pricing_fields" <?php echo $variable_display; ?>>
		<input type="hidden" id="tmem_variable_prices" class="tmem_variable_prices_name_field" value=""/>
		<div id="tmem_price_fields" class="tmem_meta_table_wrap">
			<table class="widefat tmem_repeatable_table">
				<thead>
					<tr>
						<th style="width: 50px;"><?php esc_html_e( 'Month', 'mobile-events-manager' ); ?></th>
						<th style="width: 100px;"><?php esc_html_e( 'Price', 'mobile-events-manager' ); ?></th>
						<?php do_action( 'tmem_addon_price_table_head', $post->ID ); ?>
						<th style="width: 2%"></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( ! empty( $prices ) ) :
						foreach ( $prices as $key => $value ) :
							$months = isset( $value['months'] ) ? $value['months'] : '';
							$amount = isset( $value['amount'] ) ? $value['amount'] : '';
							$index  = isset( $value['index'] ) ? $value['index'] : $key;
							$args   = apply_filters( 'tmem_price_row_args', compact( 'months', 'amount' ), $value );
							?>
								<tr class="tmem_variable_prices_wrapper tmem_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
								<?php do_action( 'tmem_render_addon_price_row', $key, $args, $post->ID, $index ); ?>
								</tr>
							<?php
							endforeach;
						else :
							?>
						<tr class="tmem_variable_prices_wrapper tmem_repeatable_row" data-key="1">
							<?php do_action( 'tmem_render_addon_price_row', 1, array(), $post->ID, 1 ); ?>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="submit" colspan="3" style="float: none; clear:both; background:#fff;">
							<a class="button-secondary tmem_add_repeatable" style="margin: 6px 0;"><?php esc_html_e( 'Add New Price', 'mobile-events-manager' ); ?></a>
						</td>
					</tr>

				</tbody>
			</table>
		</div>
	</div>

	<?php

} // tmem_addon_metabox_pricing_options_row
add_action( 'tmem_addon_price_fields', 'tmem_addon_metabox_pricing_options_row', 10 );

/**
 * Individual Price Row
 *
 * Used to output a table row for each price associated with an add-on.
 * Can be called directly, or attached to an action.
 *
 * @since 1.3.9
 *
 * @param int $key
 * @param arr $args
 * @param int $post_id
 * @param int $index
 */
function tmem_addon_metabox_price_row( $key, $args, $post_id, $index ) {

	$defaults = array(
		'name'   => null,
		'amount' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	$currency_position = tmem_get_option( 'currency_format', 'before' );

	?>
	<td>
		<?php
		echo TMEM()->html->month_dropdown(
			array(
				'name'        => '_addon_variable_prices[' . $key . '][months]',
				'selected'    => ! empty( $args['months'] ) ? $args['months'] : '',
				'fullname'    => true,
				'multiple'    => true,
				'chosen'      => true,
				'placeholder' => __( 'Select Months', 'mobile-events-manager' ),
			)
		);
		?>
	</td>

	<td>
		<?php
			$price_args = array(
				'name'        => '_addon_variable_prices[' . $key . '][amount]',
				'value'       => tmem_format_amount( $args['amount'] ),
				'placeholder' => tmem_format_amount( 10.00 ),
				'class'       => 'tmem-price-field',
			);
			?>

		<?php if ( 'before' === $currency_position ) : ?>
			<span><?php echo tmem_currency_filter( '' ); ?></span>
			<?php echo TMEM()->html->text( $price_args ); ?>
		<?php else : ?>
			<?php echo TMEM()->html->text( $price_args ); ?>
			<?php echo tmem_currency_filter( '' ); ?>
		<?php endif; ?>
	</td>

	<?php do_action( 'tmem_addon_price_table_row', $post_id, $key, $args ); ?>

	<td>
		<a href="#" class="tmem_remove_repeatable" data-type="price" style="background: url(<?php echo admin_url( '/images/xit.gif' ); ?>) no-repeat;">&times;</a>
	</td>
	<?php
}
add_action( 'tmem_render_addon_price_row', 'tmem_addon_metabox_price_row', 10, 4 );
