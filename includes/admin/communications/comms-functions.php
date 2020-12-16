<?php
/**
 * Contains the communication page for sending manual emails.
 *
 * @package MEM
 * @subpackage Comms
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the send email form on the communications page.
 *
 * @since 1.3
 */
function mem_comms_page() {

	if ( ! mem_employee_can( 'send_comms' ) ) {
		wp_die(
			wp_kses_post(
				'<h1>' . __( 'Cheatin&#8217; uh?', 'mobile-events-manager' ) . '</h1>' .
				'<p>' . __( 'You do not have permission to access this page.', 'mobile-events-manager' ) . '</p>',
				403
			)
		);
	}

	global $current_user;

	if ( mem_employee_can( 'list_all_clients' ) ) {
		$clients = mem_get_clients();
	} else {
		$clients = mem_get_employee_clients();
	}

	if ( mem_employee_can( 'mem_employee_edit' ) ) {
		$employees = mem_get_employees();
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Client and Employee Communications', 'mobile-events-manager' ); ?></h1>
		<form name="mem_form_send_comms" id="mem_form_send_comms" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'send_comm_email', 'mem_nonce', true, true ); ?>
			<?php mem_admin_action_field( 'send_comm_email' ); ?>
			<input type="hidden" name="mem_email_from_address" id="mem_email_from_address" value="<?php echo esc_html( $current_user->user_email ); ?>" />
			<input type="hidden" name="mem_email_from_name" id="mem_email_from_name" value="<?php echo esc_html( $current_user->display_name ); ?>" />
			<?php do_action( 'mem_pre_comms_table' ); ?>
			<table class="form-table">
				<?php do_action( 'mem_add_comms_fields_before_recipient' ); ?>
				<tr>
					<th scope="row"><label for="mem_email_to"><?php esc_html_e( 'Select a Recipient', 'mobile-events-manager' ); ?></label></th>
					<td>
						<select name="mem_email_to" id="mem_email_to">
							<option value=""><?php esc_html_e( 'Select a Recipient', 'mobile-events-manager' ); ?></option>
							<optgroup label="<?php esc_html_e( 'Clients', 'mobile-events-manager' ); ?>">
								<?php
								if ( empty( $clients ) ) {
									echo esc_html_e( '<option disabled="disabled">' . __( 'No Clients Found', 'mobile-events-manager' ) . '</option>' );
								} else {
									foreach ( $clients as $client ) {
										echo '<option value="' . esc_attr( $client->ID ) . '">' . esc_attr( $client->display_name ) . '</option>';
									}
								}
								?>
							</optgroup>
							<?php
							if ( ! empty( $employees ) ) {

								echo '<optgroup label="' . esc_html_e( 'Employees', 'mobile-events-manager' ) . '">';

								foreach ( $employees as $employee ) {
									echo '<option value="' . esc_attr( $employee->ID ) . '">' . esc_attr( $employee->display_name ) . '</option>';
								}

								echo '</optgroup>';
							}
							?>
						</select>
					</td>
				</tr>
				<?php do_action( 'mem_add_comms_fields_before_subject' ); ?>
				<tr>
					<th scope="row"><label for="mem_email_subject"><?php esc_html_e( 'Subject', 'mobile-events-manager' ); ?></label></th>
					<td><input type="text" name="mem_email_subject" id="mem_email_subject" class="regular-text" value="<?php echo isset( $_GET['template'] ) ? esc_attr( get_the_title( sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) ) : ''; ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="mem_email_copy_to"><?php esc_html_e( 'Copy Yourself?', 'mobile-events-manager' ); ?></label></th>
					<td><input type="checkbox" name="mem_email_copy_to" id="mem_email_copy_to" value="<?php echo esc_attr( $current_user->user_email ); ?>" /> <span class="description"><?php esc_html_e( 'Settings may dictate that additional email copies are also sent', 'mobile-events-manager' ); ?></span></td>
				</tr>
				<?php do_action( 'mem_add_comms_fields_before_template' ); ?>
				<tr>
					<th scope="row"><label for="mem_email_template"><?php esc_html_e( 'Select a Template', 'mobile-events-manager' ); ?></label></th>
					<td>
						<select name="mem_email_template" id="mem_email_template">
							<option value="0"><?php esc_html_e( 'No Template', 'mobile-events-manager' ); ?></option>
							<?php esc_attr( $template = esc_attr( isset( $_GET['template'] ) ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) : 0; ?>
							<?php echo esc_attr( mem_comms_template_options( $template ) ); ?>
						</select>
					</td>
				</tr>
				<?php do_action( 'mem_add_comms_fields_before_event' ); ?>
				<tr>
					<th scope="row"><label for="mem_email_event"><?php /* translators: %s: Company Type */ printf( esc_html__( 'Associated %s', 'mobile-events-manager' ), esc_html( mem_get_label_singular() ) ); ?></label></th>
					<td>
						<?php if ( isset( $_GET['event_id'] ) || ( isset( $_GET['mem-action'] ) && sanitize_key( $_GET['mem-action'] ) == 'respond_unavailable' ) ) : ?>
							<?php
							$value  = mem_get_event_date( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) . ' ';
							$value .= __( 'from', 'mobile-events-manager' ) . ' ';
							$value .= mem_get_event_start( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) . ' ';
							$value .= '(' . mem_get_event_status( sanitize_text_field( wp_unslash( $_GET['event_id'] ) ) ) . ')';
							?>
							<input type="text" name="mem_email_event_show" id="mem_email_event_show" value="<?php echo esc_attr( $value ); ?>" readonly size="50" />
							<input type="hidden" name="mem_email_event" id="mem_email_event" value="<?php esc_attr( sanitize_text_field( wp_unslash( $_GET['event_id'], 'mobile-events-manager' ) ) ); ?>" />
						<?php else : ?>
							<select name="mem_email_event" id="mem_email_event">
							<option value="0"><?php esc_html_e( 'Select an Event', 'mobile-events-manager' ); ?></option>
							</select>
						<?php endif; ?>
						<p class="description"><?php /* translators: %s: Event Type */ printf( esc_html__( 'If no %s is selected <code>{event_*}</code> content tags may not be used', 'mobile-events-manager' ), esc_html( mem_get_label_singular( true ) ) ); ?></p>
					</td>
				</tr>
				<?php do_action( 'mem_add_comms_fields_before_file' ); ?>
				<tr>
					<th scope="row"><label for="mem_email_upload_file"><?php esc_html_e( 'Attach a File', 'mobile-events-manager' ); ?></label></th>
					<td><input type="file" name="mem_email_upload_file" id="mem_email_upload_file" class="regular-text" value="" />
						<p class="description"><?php /* translators: %d: upload preset size */ printf( esc_html__( 'Max file size %dMB. Change php.ini <code>post_max_size</code> to increase', 'mobile-events-manager' ), esc_html( ini_get( 'post_max_size' ) ) ); ?></p>
					</td>
				</tr>
				<?php do_action( 'mem_add_comms_fields_before_content' ); ?>
				<tr>
					<td colspan="2">
						<?php
							$content = sanitize_text_field( wp_unslash( isset( $_GET['template'] ) ? mem_get_email_template_content( sanitize_text_field( wp_unslash( $_GET['template'] ) ) ) : '' ) );

							wp_editor(
								$content,
								'mem_email_content',
								array(
									'media_buttons' => true,
									'textarea_rows' => '10',
									'editor_class'  => 'required',
								)
							);
						?>
					</td>
				</tr>
			</table>
			<?php do_action( 'mem_post_comms_table' ); ?>
			<?php submit_button( __( 'Send Email', 'mobile-events-manager' ), 'primary', 'submit', true ); ?>
		</form>
	</div>
	<?php

} // mem_comms_page

/**
 * Retrieve the templates
 *
 * @since 1.3
 * @param str|arr $type The type of template to retrieve.
 * @return obj|bool WP_Query object or false if none found
 */
function mem_get_templates( $type = array( 'contract', 'email_template' ) ) {

	$templates = get_posts(
		array(
			'post_type'      => $type,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);

	return $templates;

} // mem_get_templates

/**
 * Generates the options for a select list of templates grouped by post type
 *
 * @since 1.3
 * @param int $selected The ID of the template that should be initially selected.
 * @return str HTML Output for the select options.
 */
function mem_comms_template_options( $selected = 0 ) {

	$templates = mem_get_templates();

	$output = '';

	if ( ! $templates ) {
		$output .= '<option disabled>' . __( 'No Templates Found', 'mobile-events-manager' ) . '</option>';
	} else {

		foreach ( $templates as $template ) {

			$comms_templates[ $template->post_type ][ $template->ID ] = $template->post_title;

		}

		foreach ( $comms_templates as $group => $comms_template ) {
			$output .= '<optgroup label="' . strtoupper( get_post_type_object( $group )->label ) . '">';

			foreach ( $comms_template as $template_id => $template_name ) {

				$output .= '<option value="' . $template_id . '"' . selected( $selected, $template_id, false ) . '>' . $template_name . '</option>';
			}

			$output .= '</optgroup>';
		}
	}

	return $output;

} // mem_comms_template_options

/**
 * Process the sending of the email
 *
 * @since 1.3
 * @param arr $data Super global $_POST array.
 * @return void
 */
function mem_send_comm_email( $data ) {

	$url = remove_query_arg( array( 'mem-message', 'event_id', 'template', 'recipient', 'mem-action' ) );

	if ( ! wp_verify_nonce( $data['mem_nonce'], 'send_comm_email' ) ) {
		$message = 'nonce_fail';
	} elseif ( empty( $data['mem_email_to'] ) || empty( $data['mem_email_subject'] ) || empty( $data['mem_email_content'] ) ) {
		$message = 'comm_missing_content';
	} else {

		if ( isset( $_FILES['mem_email_upload_file'] ) && '' !== $_FILES['mem_email_upload_file']['name'] ) {
			$upload_dir = wp_upload_dir();

			$file_name = sanitize_text_field( wp_unslash( $_FILES['mem_email_upload_file']['name'] ) );
			$file_path = $upload_dir['path'] . '/' . $file_name;
			$tmp_path  = sanitize_text_field( wp_unslash( $_FILES['mem_email_upload_file']['tmp_name'] ) );

			if ( move_uploaded_file( $tmp_path, $file_path ) ) {
				$attachments[] = $file_path;
			}
		}

		if ( empty( $attachments ) ) {
			$attachments = array();
		}

		$attachments = apply_filters( 'mem_send_comm_email_attachments', $attachments, $data );
		$client_id   = $data['mem_email_to'];

		if ( ! empty( $data['mem_email_event'] ) ) {
			$event     = new MEM_Event( $data['mem_email_event'] );
			$client_id = $event->client;
		}

		$email_args = array(
			'to_email'    => mem_get_client_email( $data['mem_email_to'] ),
			'from_name'   => $data['mem_email_from_name'],
			'from_email'  => $data['mem_email_from_address'],
			'event_id'    => $data['mem_email_event'],
			'client_id'   => $client_id,
			'subject'     => stripslashes( $data['mem_email_subject'] ),
			'attachments' => ! empty( $attachments ) ? $attachments : array(),
			'message'     => stripslashes( $data['mem_email_content'] ),
			'track'       => true,
			'copy_to'     => ! empty( $data['mem_email_copy_to'] ) ? array( $data['mem_email_copy_to'] ) : array(),
			'source'      => __( 'Communication Feature', 'mobile-events-manager' ),
		);

		if ( mem_send_email_content( $email_args ) ) {
			$message = 'comm_sent';

			if ( ! empty( $data['mem_event_reject'] ) ) {

				$args = array(
					'reject_reason' => ! empty( $data['mem_email_reject_reason'] ) ? $data['mem_email_reject_reason'] : __( 'No reason specified', 'mobile-events-manager' ),
				);

				mem_update_event_status( $email_args['event_id'], 'mem-rejected', get_post_status( $email_args['event_id'] ), $args );
			}
		} else {
			$message = 'comm_not_sent';
		}
	}

	wp_safe_redirect( add_query_arg( 'mem-message', $message, $url ) );

	die();

} // mem_send_comm_email
add_action( 'mem-send_comm_email', 'mem_send_comm_email' );

/**
 * Add the comment field to record why an event is being rejected.
 *
 * @since 1.3.3
 */
function mem_add_reject_reason_field() {

	if ( ! isset( $_GET['mem-action'] ) || sanitize_key( $_GET['mem-action'] ) != 'respond_unavailable' ) {
		return;
	}

	$output  = '<tr>';
	$output .= '<th scope="row"><label for="mem_email_reject_reason">' . __( 'Rejection Reason', 'mobile-events-manager' ) . '</label></th>';
	$output .= '<td><textarea name="mem_email_reject_reason" id="mem_email_reject_reason" cols="50" rows="3" clas="class="large-text code"></textarea>';
	$output .= '<p class="description">' . __( 'Optional. If completed, this entry will be added to the event journal.', 'mobile-events-manager' ) . '</p>';
	$output .= '<input type="hidden" name="mem_event_reject" id="mem_event_reject" value="1" />';
	$output .= '</td>';
	$output .= '</tr>';

	echo esc_attr( apply_filters( 'mem_add_reject_reason_field', $output ) );

} // mem_add_reject_reason_field
add_action( 'mem_add_comms_fields_before_file', 'mem_add_reject_reason_field' );
