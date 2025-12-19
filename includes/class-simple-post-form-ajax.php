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
		$user_ip = simple_post_form()->get_user_ip();
		
		// Check if IP is blocked
		if ( simple_post_form()->is_ip_blocked( $user_ip ) ) {
			$spam_data = array( 'blocked_reason' => 'IP address is blocked' );
			if ( $form_id ) {
				simple_post_form()->save_submission( $form_id, $spam_data, true );
			}
			wp_send_json_error( array( 'message' => __( 'Your IP address has been blocked from submitting forms.', 'simple-post-form' ) ) );
		}
		
		// Check rate limit
		if ( $form_id && get_option( 'spf_rate_limit_enabled' ) ) {
			if ( simple_post_form()->check_rate_limit( $user_ip, $form_id ) ) {
				// Block IP temporarily
				$block_duration = get_option( 'spf_rate_limit_block_duration', 60 );
				simple_post_form()->block_ip( $user_ip, 'Rate limit exceeded', $block_duration );
				
				$spam_data = array( 'blocked_reason' => 'Rate limit exceeded' );
				simple_post_form()->save_submission( $form_id, $spam_data, true );
				
				wp_send_json_error( array( 'message' => __( 'Too many submissions. Please try again later.', 'simple-post-form' ) ) );
			}
		}
		
		// Check country blocking
		if ( get_option( 'spf_country_blocking_enabled' ) ) {
			$blocked_countries = get_option( 'spf_blocked_countries', '' );
			if ( ! empty( $blocked_countries ) ) {
				$country = simple_post_form()->get_country_from_ip( $user_ip );
				$blocked_list = array_map( 'trim', explode( ',', strtoupper( $blocked_countries ) ) );
				
				if ( in_array( strtoupper( $country ), $blocked_list ) ) {
					$spam_data = array( 'blocked_reason' => 'Country blocked: ' . $country );
					if ( $form_id ) {
						simple_post_form()->save_submission( $form_id, $spam_data, true );
					}
					wp_send_json_error( array( 'message' => __( 'Submissions from your country are not allowed.', 'simple-post-form' ) ) );
				}
			}
		}
		
		// Check honeypot field - if filled, it's spam
		if ( ! empty( $_POST['email'] ) ) {
			// Save as spam submission
			if ( $form_id ) {
				$spam_data = array( 'blocked_reason' => 'Honeypot field filled' );
				simple_post_form()->save_submission( $form_id, $spam_data, true );
			}
			// Silently succeed for bots
			wp_send_json_success( array(
				'message' => __( 'Form submitted successfully!', 'simple-post-form' ),
			) );
		}

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'simple-post-form' ) ) );
		}

		// Verify form nonce
		if ( ! isset( $_POST['spf_form_nonce'] ) || ! wp_verify_nonce( $_POST['spf_form_nonce'], 'spf_submit_form_' . $form_id ) ) {
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
		// Skip honeypot fields - they should not be included in submission data or email
		if ( $field->field_type === 'honeypot' ) {
			continue;
		}

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
	}		if ( ! empty( $errors ) ) {
			wp_send_json_error( array(
				'message' => implode( '<br>', $errors ),
			) );
		}

		// Check for spam keywords
		$spam_keyword = simple_post_form()->contains_spam_keywords( $field_data );
		if ( $spam_keyword !== false ) {
			// Save as spam submission
			$spam_data = $field_data;
			$spam_data['blocked_reason'] = 'Spam keyword detected: ' . $spam_keyword;
			simple_post_form()->save_submission( $form_id, $spam_data, true );
			
			// Return generic success to avoid revealing spam detection
			wp_send_json_success( array(
				'message' => __( 'Form submitted successfully!', 'simple-post-form' ),
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

		// Add Reply-To header if enabled
		if ( ! empty( $form->use_reply_to ) && $form->use_reply_to == 1 ) {
			// Find the first email field value
			foreach ( $fields as $field ) {
				if ( $field->field_type === 'email' ) {
					$field_name = 'spf_field_' . $field->id;
					$email_value = isset( $_POST[ $field_name ] ) ? sanitize_email( wp_unslash( $_POST[ $field_name ] ) ) : '';
					if ( ! empty( $email_value ) && is_email( $email_value ) ) {
						$headers[] = 'Reply-To: ' . $email_value;
						break;
					}
				}
			}
		}

		// Capture PHPMailer errors
		$phpmailer_error = '';
		add_action( 'wp_mail_failed', function( $error ) use ( &$phpmailer_error ) {
			$phpmailer_error = $error->get_error_message();
		});

	// Send email
	$sent = wp_mail( $to, $subject, $message, $headers );

	// Send copy to sender if requested and form allows it
	if ( ! empty( $form->enable_sender_copy ) && $form->enable_sender_copy == 1 && isset( $_POST['spf_send_copy'] ) && $_POST['spf_send_copy'] == '1' ) {
		// Find the sender's email address from the form fields
		$sender_email = '';
		foreach ( $fields as $field ) {
			if ( $field->field_type === 'email' ) {
				$field_name = 'spf_field_' . $field->id;
				$email_value = isset( $_POST[ $field_name ] ) ? sanitize_email( wp_unslash( $_POST[ $field_name ] ) ) : '';
				if ( ! empty( $email_value ) && is_email( $email_value ) ) {
					$sender_email = $email_value;
					break;
				}
			}
		}

		// Send copy if we found a valid email
		if ( ! empty( $sender_email ) ) {
			$copy_subject = sprintf(
				/* translators: %s: form name */
				__( 'Copy of your submission: %s', 'simple-post-form' ),
				$form->form_name
			);
			
			$copy_message = $this->prepare_email_message( $form, $field_data );
			$copy_message = str_replace( 
				'<h2>' . esc_html( $form->form_name ) . '</h2>',
				'<h2>' . esc_html__( 'Copy of Your Submission', 'simple-post-form' ) . '</h2>',
				$copy_message
			);
			
			wp_mail( $sender_email, $copy_subject, $copy_message, $headers );
		}
	}

	// Prepare email status for database
	$email_status = array(
		'sent' => $sent,
		'status' => $sent ? 'delivered' : 'failed',
		'error' => $sent ? '' : $phpmailer_error,
	);

	// Save submission to database with email status
	simple_post_form()->save_submission( $form_id, $field_data, false, $email_status );		// Get success/error messages
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
