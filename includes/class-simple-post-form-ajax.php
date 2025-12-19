<?php
/**
 * AJAX Handler Class
 *
 * @package Code045\Simple_Post_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Class.
 */
class Simple_Post_Form_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_spf_submit_form', array( $this, 'submit_form' ) );
		add_action( 'wp_ajax_nopriv_spf_submit_form', array( $this, 'submit_form' ) );
	}

	/**
	 * Handle form submission.
	 */
	public function submit_form() {
		check_ajax_referer( 'spf-frontend-nonce', 'nonce' );

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'simple-post-form' ) ) );
		}

		// Verify form nonce
		if ( ! isset( $_POST['form_nonce'] ) || ! wp_verify_nonce( $_POST['form_nonce'], 'spf_submit_form_' . $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'simple-post-form' ) ) );
		}

		$form = simple_post_form()->get_form( $form_id );

		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'simple-post-form' ) ) );
		}

		$fields = simple_post_form()->get_form_fields( $form_id );

		if ( empty( $fields ) ) {
			wp_send_json_error( array( 'message' => __( 'Form has no fields.', 'simple-post-form' ) ) );
		}

		// Validate and collect field data
		$field_data = array();
		$errors = array();

		foreach ( $fields as $field ) {
			$field_name = 'spf_field_' . $field->id;
			$field_value = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : '';

			// Check required fields
			if ( $field->field_required && empty( $field_value ) ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( '%s is required.', 'simple-post-form' ),
					$field->field_label
				);
			}

			// Validate email fields
			if ( $field->field_type === 'email' && ! empty( $field_value ) && ! is_email( $field_value ) ) {
				$errors[] = sprintf(
					/* translators: %s: field label */
					__( '%s must be a valid email address.', 'simple-post-form' ),
					$field->field_label
				);
			}

			$field_data[ $field->field_label ] = $field_value;
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( array(
				'message' => implode( '<br>', $errors ),
			) );
		}

		// Prepare email
		$to = $form->recipient_email;
		$subject = ! empty( $form->form_subject ) ? $form->form_subject : sprintf(
			/* translators: %s: form name */
			__( 'New submission from %s', 'simple-post-form' ),
			$form->form_name
		);

		$message = $this->prepare_email_message( $form, $field_data );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( ! empty( $form->sender_email ) && ! empty( $form->sender_name ) ) {
			$headers[] = 'From: ' . $form->sender_name . ' <' . $form->sender_email . '>';
		} elseif ( ! empty( $form->sender_email ) ) {
			$headers[] = 'From: ' . $form->sender_email;
		}

		// Save submission to database
		simple_post_form()->save_submission( $form_id, $field_data );

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Get success/error messages
		$success_message = ! empty( $form->success_message ) ? $form->success_message : get_option( 'spf_success_message', __( 'Form submitted successfully! We will get back to you soon.', 'simple-post-form' ) );
		$error_message = ! empty( $form->error_message ) ? $form->error_message : get_option( 'spf_error_message', __( 'Failed to send email. Please try again later.', 'simple-post-form' ) );

		if ( $sent ) {
			wp_send_json_success( array(
				'message' => $success_message,
			) );
		} else {
			wp_send_json_error( array(
				'message' => $error_message,
			) );
		}
	}

	/**
	 * Prepare email message.
	 *
	 * @param object $form Form object.
	 * @param array  $field_data Field data.
	 * @return string
	 */
	private function prepare_email_message( $form, $field_data ) {
		$message = '<html><body>';
		$message .= '<h2>' . esc_html( $form->form_name ) . '</h2>';
		
		if ( ! empty( $form->form_title ) ) {
			$message .= '<p><strong>' . esc_html( $form->form_title ) . '</strong></p>';
		}

		$message .= '<table style="border-collapse: collapse; width: 100%; max-width: 600px;">';
		
		foreach ( $field_data as $label => $value ) {
			$message .= '<tr>';
			$message .= '<td style="padding: 10px; border: 1px solid #ddd; background-color: #f5f5f5;"><strong>' . esc_html( $label ) . ':</strong></td>';
			$message .= '<td style="padding: 10px; border: 1px solid #ddd;">' . nl2br( esc_html( $value ) ) . '</td>';
			$message .= '</tr>';
		}

		$message .= '</table>';
		
		$message .= '<br><hr>';
		$message .= '<p style="color: #666; font-size: 12px;">';
		$message .= sprintf(
			/* translators: 1: site name, 2: date and time */
			__( 'This form was submitted from %1$s on %2$s', 'simple-post-form' ),
			get_bloginfo( 'name' ),
			date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
		);
		$message .= '</p>';
		$message .= '</body></html>';

		return $message;
	}
}
