<?php
/**
 * TMEM_ClientFields Class
 *
 * @package TMEM
 * @subpackage Clients
 * @copyright Copyright (c) 2020, Jack Mawhinney, Dan Porter
 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -- Build the TMEM_ClientFields class -- */
if ( ! class_exists( 'TMEM_ClientFields' ) ) {
	class TMEM_ClientFields {
		/*
		 * The Constructor
		 */
		public function __construct() {

			$this->fields = get_option( 'tmem_client_fields' );

			foreach ( $this->fields as $key => $row ) {
				$field[ $key ] = $row['position'];
			}

			// Sort the fields into a positional array
			array_multisort( $field, SORT_ASC, $this->fields );

			if ( isset( $_GET['action'], $_GET['id'] ) && 'delete_field' === $_GET['action'] ) {
				$this->delete_field();
			}

			if ( isset( $_POST['submit'] ) ) {

				if ( __( 'Add Field', 'mobile-events-manager' ) === $_POST['submit'] ) {
					$this->add_field();
				}

				if ( __( 'Save Changes', 'mobile-events-manager' ) === $_POST['submit'] ) {
					$this->update_field();
				}
			}

			$this->display_fields();
		} // __construct

		/*
		 * Process field deletions
		 */
		function delete_field() {
			unset( $this->fields[ $_GET['id'] ] );

			if ( update_option( 'tmem_client_fields', $this->fields ) ) {
				tmem_update_notice( 'updated', __( 'The field was deleted successfully.', 'mobile-events-manager' ) );
			} else {
				tmem_update_notice( 'error', __( 'Field could not be deleted', 'mobile-events-manager' ) );
			}
		} // delete_field

		/*
		 * Add new field
		 */
		function add_field() {
			global $current_user;

			$id = sanitize_title_with_dashes( wp_unslash( $_POST['field_label'], '', 'save' ) );

			if ( array_key_exists( $id, $this->fields ) ) {
				$id = $id . '_';
			}

			if ( 'checkbox' === $_POST['field_type'] ) {
				$value = sanitize_option( wp_unslash( $_POST['field_value'] ) );
			} elseif ( 'dropdown' === $_POST['field_type'] ) {
				$value = sanitize_option( wp_unslash( $_POST['field_options'] ) );
			}

			$this->fields[ $id ] = array(
				'label'    => sanitize_text_field( wp_unslash( $_POST['field_label'] ) ),
				'id'       => $id,
				'type'     => sanitize_option( wp_unslash( $_POST['field_type'] ) ),
				'value'    => ! empty( $value ) ? $value : '',
				'checked'  => ! empty( $_POST['field_checked'] ) ? '1' : '0',
				'display'  => ! empty( $_POST['field_enabled'] ) ? '1' : '0',
				'required' => ! empty( $_POST['field_required'] ) ? '1' : '0',
				'desc'     => ! empty( $_POST['field_desc'] ) ? sanitize_text_field( wp_unslash( $_POST['field_desc'] ) ) : '',
				'default'  => '0',
				'position' => sanitize_option( wp_unslash( $_POST['field_position'] ) ),
			);

			if ( update_option( 'tmem_client_fields', $this->fields ) ) {
				tmem_update_notice( 'updated', stripslashes( sanitize_text_field( wp_unslash( $_POST['field_label'] ) ) . __( ' created successfully.', 'mobile-events-manager' ) ) );
			} else {
				tmem_update_notice( 'error', __( 'Field could not be created', 'mobile-events-manager' ) );
			}

		} // add_field

		/*
		 * Update existing field
		 */
		function update_field() {
			global $current_user;

			if ( 'checkbox' === $_POST['field_type'] ) {
				$value = sanitize_option( wp_unslash( $_POST['field_value'] ) );
			} elseif ( 'dropdown' === $_POST['field_type'] ) {
				$value = sanitize_option( wp_unslash( $_POST['field_options'] ) );
			}

			$this->fields[ sanitize_option( wp_unslash( $_POST['field_id'] ) ) ] = array(
				'label'    => sanitize_text_field( wp_unslash( $_POST['field_label'] ) ),
				'id'       => sanitize_text_field( wp_unslash( $_POST['field_id'] ) ),
				'type'     => sanitize_text_field( wp_unslash( $_POST['field_type'] ) ),
				'value'    => ! empty( $value ) ? $value : '',
				'checked'  => ! empty( $_POST['field_checked'] ) ? '1' : '0',
				'display'  => ! empty( $_POST['field_enabled'] ) ? '1' : '0',
				'required' => ! empty( $_POST['field_required'] ) ? '1' : '0',
				'desc'     => ! empty( $_POST['field_desc'] ) ? sanitize_text_field( wp_unslash( $_POST['field_desc'] ) ) : '',
				'default'  => $this->fields[ sanitize_option( wp_unslash( $_POST['field_id'] ) ) ]['default'],
				'position' => $this->fields[ sanitize_option( wp_unslash( $_POST['field_id'] ) ) ]['position'],
			);

			if ( update_option( 'tmem_client_fields', $this->fields ) ) {
				tmem_update_notice( 'updated', sanitize_text_field( wp_unslash( $_POST['field_label'] ) ) . ' updated successfully.' );
			} else {
				tmem_update_notice( 'error', __( 'Field could not be updated', 'mobile-events-manager' ) );
			}

		} // update_field

		/*
		 * Display the client fields admin interface
		 */
		function display_fields() {
			/* -- Enable drag & drop -- */

			$dir = TMEM_PLUGIN_URL . '/assets/images/form-icons';

			// Start the container
			echo '<div class="tmem-client-field-container">' . "\r\n";

			// Left Column
			echo '<div class="tmem-client-field-column-left">' . "\r\n";

			// Start the display table
			echo '<h3>Existing Client Fields</h3>' . "\r\n";
			echo '<table class="widefat tmem-client-list-item" style="width:90%">' . "\r\n";
			echo '<thead>' . "\r\n";
			echo '<tr>' . "\r\n";
			echo '<th style="width: 20px;"></th>' . "\r\n";
			echo '<th style="width: 15%;">' . __( 'Label', 'mobile-events-manager' ) . '</th>' . "\r\n";
			echo '<th style="width: 15%;">' . __( 'Type', 'mobile-events-manager' ) . '</th>' . "\r\n";
			echo '<th style="width: 35%;">' . __( 'Description', 'mobile-events-manager' ) . '</th>' . "\r\n";
			echo '<th style="width: 15%;">' . __( 'Options', 'mobile-events-manager' ) . '</th>' . "\r\n";
			echo '<th>' . __( 'Actions', 'mobile-events-manager' ) . '</th>' . "\r\n";
			echo '</tr>' . "\r\n";
			echo '</thead>' . "\r\n";
			echo '<tbody>' . "\r\n";

			$i = 0;

			foreach ( $this->fields as $field ) {
				if ( 0 === $i && true === $field['display'] ) {
					$class = 'alternate tmem_sortable_row';
				} elseif ( empty( $field['display'] ) ) {
					$class = 'form-invalid tmem_sortable_row';
				} else {
					$class = 'tmem_sortable_row';
				}

				echo '<tr id="fields=' . $field['id'] . '"' .
					' class="' . $class . '" data-key="' . esc_attr( $field['id'] ) . '">' . "\r\n";

				echo '<td><span class="tmem_draghandle"></span></td>' . "\r\n";
				echo '<td>' . wp_kses_post( stripslashes( $field['label'] ) ) . '</td>' . "\r\n";
				echo '<td>' . ucfirst( str_replace( 'dropdown', 'select', $field['type'] ) ) . ' Field</td>' . "\r\n";
				echo '<td>' . ( ! empty( $field['desc'] ) ? esc_attr( $field['desc'] ) : '' ) . '</td>' . "\r\n";
				echo '<td>' . $this->field_icons( $field ) . '</td>' . "\r\n";
				echo '<td>';
				echo '<a href="' . tmem_get_admin_page( 'client_fields' ) . '&action=edit_field&id=' . $field['id'] .
					'" class="button button-primary button-small">' . __( 'Edit', 'mobile-events-manager' ) . '</a>';
				if ( empty( $field['default'] ) ) {
					echo '&nbsp;&nbsp;&nbsp;<a href="' . tmem_get_admin_page( 'client_fields' ) . '&action=delete_field&id=' . $field['id'] .
					'" class="button button-secondary button-small">' . __( 'Delete', 'mobile-events-manager' ) . '</a>';
				}
				echo '</td>' . "\r\n";
				echo '</tr>' . "\r\n";

				if ( 1 === $i ) {
					$i = 0;
				} else {
					$i++;
				}
			}

			// End the display table
			echo '</tbody>' . "\r\n";
			echo '</table>' . "\r\n";

			// End left column
			echo '</div>' . "\r\n";

			// Form fields
			$this->manage_field_form();

			// End the container
			echo '</div>' . "\r\n";

		} // display_fields

		/*
		 * Display form to add or edit a field
		 */
		function manage_field_form() {

			$must = array( 'first_name', 'last_name', 'user_email' );

			$total = count( $this->fields );

			// Right Column
			echo '<div class="tmem-client-field-column-right">' . "\r\n";
			echo '<form name="tmem-client-fields" id="tmem-client-fields" method="post" action="' . tmem_get_admin_page( 'client_fields' ) . '">' . "\r\n";

			if ( isset( $_GET['action'] ) && 'edit_field' === $_GET['action'] ) {
				$editing = true;
			}

			// If editing a field we need this hidden field to identify it
			if ( ! empty( $editing ) ) {
				echo '<input type="hidden" name="field_id" id="field_id" value="' . $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['id'] . '" />' . "\r\n";

			} else {
				echo '<input type="hidden" name="field_position" id="field_position" value="' . $total . '" />' . "\r\n";
			}

			echo '<h3>' . ( empty( $editing ) ? __( 'Add New Client Field', 'mobile-events-manager' ) :
				wp_kses_post( 'Edit the ', 'mobile-events-manager' . '<span class="tmem-color">' . stripslashes( $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['label'] ) . '</span>' . esc_html( __( ' Field', 'mobile-events-manager' ) ) . '</h3>' . "\r\n" ) );

			// Field Label
			echo '<p>';
			echo '<label class="tmem-label" for="field_label">' . __( 'Field Label', 'mobile-events-manager' ) . ':</label><br />' . "\r\n";
			echo sanitize_text_field(
				'<input type="text" name="field_label" id="field_label" class="regular-text" value="' . ( ! empty( $editing ) ? stripslashes( $this->fields[ sanitize_text_field( wp_unslash( $_GET['id'] ) ) ]['label'] ) : '' ) .
				'" class="regular-text" />'
			);
			echo '</p>' . "\r\n";

			// Field Type
			$types = array( 'text', 'checkbox', 'dropdown' );
			echo '<p>' . "\r\n";
			echo '<label class="tmem-label" for="field_type">' . __( 'Field Type', 'mobile-events-manager' ) . ':</label><br />' . "\r\n";
			echo '<select name="field_type" id="field_type"' . ( ! empty( $editing ) && $this->fields[ sanitize_text_field( wp_unslash( true === $_GET['id'] ) ) ]['default'] ?
				' disabled="disabled"' : '' ) . ' onChange="whichField(); showRequired();">' . "\r\n";

			foreach ( $types as $type ) {
				echo '<option value="' . $type . '"';
				if ( ! empty( $editing ) ) {
					selected( $type, $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['type'] );
				}
				echo '>' . __( ucfirst( str_replace( 'dropdown', 'select', $type ) ) . ' Field', 'mobile-events-manager' ) . '</option>' . "\r\n";
			}

			echo '</select>' . "\r\n";
			echo '</p>' . "\r\n";
			if ( ! empty( $editing ) ) { // If the select field is disabled we need to set the value
				echo '<input type="hidden" name="field_type" id="field_type" value="' . $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ] ['type'] . '" />' . "\r\n";
			}

			// Value
			?>
			<style>
			#value_field_dropdown	{
				display: <?php echo ( ! empty( $editing ) && $this->fields[ sanitize_option( wp_unslash( 'dropdown' === $_GET['id'] ) ) ]['type'] ? 'block;' : 'none;' ); ?>
			}
			#value_field_checkbox	{
				display: <?php echo ( ! empty( $editing ) && $this->fields[ sanitize_option( wp_unslash( 'checkbox' === $_GET['id'] ) ) ]['type'] ? 'block;' : 'none;' ); ?>
			}
			#required_checkbox	{
				display: <?php echo ( empty( $editing ) || $this->fields[ sanitize_option( wp_unslash( 'checkbox' !== $_GET['id'] ) ) ]['type'] ? 'block;' : 'none;' ); ?>
			}
			</style>

			<div id="value_field_dropdown">
			<?php
			echo '<p>' . "\r\n";
			echo '<label class="tmem-label" for="field_options">' . __( 'Selectable Options', 'mobile-events-manager' ) . ':</label> <br />' . "\r\n";
			echo '<textarea name="field_options" id="field_options" class="all-options" rows="5">' .
				( ! empty( $editing ) ? $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['value'] : '' ) . '</textarea><br /><span class="description">One entry per line</span>';
			echo '</p>' . "\r\n";
			?>
			</div>
			<div id="value_field_checkbox">
			<?php
			echo '<p>' . "\r\n";
			echo '<label class="tmem-label" for="field_value">' . __( 'Checked Value', 'mobile-events-manager' ) . ':</label><br />' . "\r\n";
			echo '<input type="text" name="field_value" id="field_value" value="' . ( ! empty( $editing ) ? $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['value'] : '' ) .
				'" class="small-text" />';
			echo '</p>' . "\r\n";

			echo '<p>' . "\r\n";
			echo '<label class="tmem-label" for="field_checked">' . __( 'Checked by Default', 'mobile-events-manager' ) . '?</label><br />' . "\r\n";
			echo '<input type="checkbox" name="field_checked" id="field_checked" value="1"' .
				( ! empty( $editing ) && $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['checked'] ? ' checked="checked"' : '' ) . '" />';
			echo '</p>' . "\r\n";
			?>
			</div>

			<script type="text/javascript">
			function whichField() {
				var type = field_type.options[field_type.selectedIndex].value;
				var dropdown_div = document.getElementById("value_field_dropdown");
				var checkbox_div = document.getElementById("value_field_checkbox");

				if (type == 'text') {
					dropdown_div.style.display = "none";
					checkbox_div.style.display = "none";
				}
				if (type == 'dropdown')	{
					dropdown_div.style.display = "block";
					checkbox_div.style.display = "none";
				}
				if (type == 'checkbox')	{
					dropdown_div.style.display = "none";
					checkbox_div.style.display = "block";
				}
			}
			</script>

			<?php
			// Description
			echo '<p>' . "\r\n";
			echo '<label class="tmem-label" for="field_desc">' . __( 'Description', 'mobile-events-manager' ) . ':</label><br />' . "\r\n";
			echo '<input type="text" name="field_desc" id="field_desc" value="' . ( ! empty( $editing ) ? $this->fields[ sanitize_option( wp_unslash( $_GET['id'] ) ) ]['desc'] : '' ) .
				'" class="regular-text" />';
			echo '</p>' . "\r\n";

			// Options
			echo '<p>' . "\r\n";
			echo '<input type="checkbox" name="field_enabled" id="field_enabled" value="1"' .
				( ! empty( $editing ) && ! empty( $this->fields[ $_GET['id'] ]['display'] ) ? ' checked="checked"' : '' ) .
				( ! empty( $editing ) && in_array( $this->fields[ $_GET['id'] ]['id'], $must ) ? ' disabled="disabled"' : '' ) . ' />' .
				'<label class="tmem-label" for="field_enabled">' . __( ' Field Enabled?', 'mobile-events-manager' ) . '</label>';
			if ( ! empty( $editing ) && in_array( $this->fields[ $_GET['id'] ]['id'], $must ) ) {
				echo '<input type="hidden" name="field_enabled" id="field_enabled" value="1" />' . "\r\n";
			}
			echo '</p>' . "\r\n";
			echo '<div id="required_checkbox">' . "\r\n";
			echo '<p>' . "\r\n";
			echo '<input type="checkbox" name="field_required" id="field_required" value="1"' .
				( ! empty( $editing ) && ! empty( $this->fields[ $_GET['id'] ]['required'] ) ? ' checked="checked"' : '' ) .
				( ! empty( $editing ) && in_array( $this->fields[ $_GET['id'] ]['id'], $must ) ? ' disabled="disabled"' : '' ) . ' />' .
				'<label class="tmem-label" for="field_required">' . __( ' Required Field?', 'mobile-events-manager' ) . '</label>';
			if ( ! empty( $editing ) && in_array( $this->fields[ $_GET['id'] ]['id'], $must ) ) {
				echo '<input type="hidden" name="field_required" id="field_required" value="1" />' . "\r\n";
			}
			echo '</p>' . "\r\n";
			echo '</div>' . "\r\n";

			?>
			<script type="text/javascript">
			function showRequired() {
				var type = field_type.options[field_type.selectedIndex].value;
				var required_div = document.getElementById("required_checkbox");

				if (type == 'checkbox')	{
					required_div.style.display = "none";
				}
				else	{
					required_div.style.display = "block";
				}
			}
			</script>
			<?php
			echo '<p>';
			submit_button(
				( empty( $editing ) ? __( 'Add Field', 'mobile-events-manager' ) : __( 'Save Changes', 'mobile-events-manager' ) ),
				'primary',
				'submit',
				false
			);

			if ( ! empty( $editing ) ) {
				echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				echo '<a href="' . tmem_get_admin_page( 'client_fields' ) . '" class="button-secondary">' .
					__( 'Cancel Changes', 'mobile-events-manager' ) . '</a>';
			}

			echo '</p>' . "\r\n";

			// End form
			echo '</form>' . "\r\n";
			// End Right Column
			echo '</div>' . "\r\n";

		} // manage_field_form

		/*
		 * Display icons to identify the field configuration
		 *
		 *
		 * @param	arr		$field		The field which to query
		 */
		function field_icons( $field ) {
			$dir = TMEM_PLUGIN_URL . '/assets/images/form-icons';

			$output = '';

			if ( ! empty( $field['required'] ) ) {
				$output .= '<img src="' . $dir . '/req_field.jpg" width="14" height="14" alt="' . __( 'Required Field', 'mobile-events-manager' ) . '" title="' . __( 'Required Field', 'mobile-events-manager' ) . '" />' . "\r\n";

			} else {
				$output .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			if ( 'checkbox' === $field['type'] && ! empty( $field['checked'] ) ) {
				$output .= '<img src="' . $dir . '/captcha.jpg" width="14" height="14" alt="' . __( 'Checked checkbox Field', 'mobile-events-manager' ) . '" title="' . __( 'Checked', 'mobile-events-manager' ) . '" />';

			} else {
				$output .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			if ( 'dropdown' === $field['type'] ) {
				$output .= '<img src="' . $dir . '/select_list.jpg" width="14" height="14" alt="' . __( 'Dropdown field options', 'mobile-events-manager' ) . '" title="' . str_replace( ',', "\r\n", $field['value'] ) . '" />' . "\r\n";
			}

			if ( 'checkbox' === $field['type'] ) {
				$output .= '<img src="' . $dir . '/select_list.jpg" width="14" height="14" alt="' . __( 'Checked Value', 'mobile-events-manager' ) . '" title="' . $field['value'] . '" />' . "\r\n";

			} else {
				$output .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			}

			return $output;

		} // field_icons
	} // class

} // if( !class_exists( 'TMEM_ClientFields' ) )
