<?php
/**
 * Main Simple Post Form Class
 *
 * @package Code045\Simple_Post_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Simple Post Form Class.
 */
class Simple_Post_Form {

	/**
	 * The single instance of the class.
	 *
	 * @var Simple_Post_Form
	 */
	protected static $_instance = null;

	/**
	 * Admin instance.
	 *
	 * @var Simple_Post_Form_Admin
	 */
	public $admin = null;

	/**
	 * Frontend instance.
	 *
	 * @var Simple_Post_Form_Frontend
	 */
	public $frontend = null;

	/**
	 * AJAX instance.
	 *
	 * @var Simple_Post_Form_Ajax
	 */
	public $ajax = null;

	/**
	 * Main Simple_Post_Form Instance.
	 *
	 * @return Simple_Post_Form
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
		$this->includes();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		register_activation_hook( SPF_PLUGIN_FILE, array( $this, 'activate' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'admin_init', array( $this, 'check_version' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		if ( is_admin() ) {
			$this->admin = new Simple_Post_Form_Admin();
		}
		$this->frontend = new Simple_Post_Form_Frontend();
		$this->ajax = new Simple_Post_Form_Ajax();
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'simple-post-form', false, dirname( plugin_basename( SPF_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Check plugin version and run migrations if needed.
	 */
	public function check_version() {
		$installed_version = get_option( 'spf_version', '0.0.0' );
		
		if ( version_compare( $installed_version, SPF_VERSION, '<' ) ) {
			$this->create_tables();
			update_option( 'spf_version', SPF_VERSION );
		}
	}

	/**
	 * Configure SMTP for PHPMailer.
	 *
	 * @param PHPMailer $phpmailer PHPMailer instance.
	 */
	public function configure_smtp( $phpmailer ) {
		// Check if SMTP is enabled
		if ( ! get_option( 'spf_smtp_enabled' ) ) {
			return;
		}

		$smtp_host = get_option( 'spf_smtp_host', '' );
		$smtp_port = get_option( 'spf_smtp_port', 587 );
		$smtp_encryption = get_option( 'spf_smtp_encryption', 'tls' );
		$smtp_auth = get_option( 'spf_smtp_auth', 1 );
		$smtp_username = get_option( 'spf_smtp_username', '' );
		$smtp_password = get_option( 'spf_smtp_password', '' );
		$smtp_from_email = get_option( 'spf_smtp_from_email', '' );
		$smtp_from_name = get_option( 'spf_smtp_from_name', '' );

		// Only configure if host is set
		if ( empty( $smtp_host ) ) {
			return;
		}

		// Tell PHPMailer to use SMTP
		$phpmailer->isSMTP();

		// Set the hostname of the mail server
		$phpmailer->Host = $smtp_host;

		// Set the SMTP port number
		$phpmailer->Port = $smtp_port;

		// Set encryption
		if ( $smtp_encryption && $smtp_encryption !== 'none' ) {
			$phpmailer->SMTPSecure = $smtp_encryption;
		}

		// Whether to use SMTP authentication
		if ( $smtp_auth ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $smtp_username;
			$phpmailer->Password = $smtp_password;
		}

		// Set from email and name if provided
		if ( ! empty( $smtp_from_email ) ) {
			$phpmailer->From = $smtp_from_email;
			if ( ! empty( $smtp_from_name ) ) {
				$phpmailer->FromName = $smtp_from_name;
			}
		}

		// Additional settings for better compatibility
		$phpmailer->SMTPAutoTLS = true;
		$phpmailer->Timeout = 30;
	}

	/**
	 * Activate the plugin.
	 */
	public function activate() {
		$this->create_tables();
	}

	/**
	 * Create database tables.
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Forms table.
		$table_name = $wpdb->prefix . 'spf_forms';
		$sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_name varchar(255) NOT NULL,
			form_title varchar(255) DEFAULT NULL,
			form_subject varchar(255) DEFAULT NULL,
			recipient_email varchar(255) DEFAULT NULL,
			sender_name varchar(255) DEFAULT NULL,
			sender_email varchar(255) DEFAULT NULL,
			button_text varchar(100) DEFAULT 'Submit',
			button_styles longtext DEFAULT NULL,
			modal_button_text varchar(100) DEFAULT 'Open Form',
			modal_button_styles longtext DEFAULT NULL,
		modal_styles longtext DEFAULT NULL,
		use_global_styles tinyint(1) DEFAULT 0,
		use_reply_to tinyint(1) DEFAULT 0,
		enable_sender_copy tinyint(1) DEFAULT 0,
		hide_labels tinyint(1) DEFAULT 0,
		debug_mode tinyint(1) DEFAULT 0,
		success_message text DEFAULT NULL,
		error_message text DEFAULT NULL,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		// Check if we need to add new columns to existing table
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'modal_button_text'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD modal_button_text varchar(100) DEFAULT 'Open Form' AFTER button_styles" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD modal_button_styles longtext DEFAULT NULL AFTER modal_button_text" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD modal_styles longtext DEFAULT NULL AFTER modal_button_styles" );
		}
		
		// Check if we need to add message columns
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'use_global_styles'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD use_global_styles tinyint(1) DEFAULT 0 AFTER modal_styles" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD use_reply_to tinyint(1) DEFAULT 0 AFTER use_global_styles" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD success_message text DEFAULT NULL AFTER use_reply_to" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD error_message text DEFAULT NULL AFTER success_message" );
		}
		
		// Check if we need to add use_reply_to column
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'use_reply_to'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD use_reply_to tinyint(1) DEFAULT 0 AFTER use_global_styles" );
		}
		
		// Check if we need to add hide_labels column
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'hide_labels'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD hide_labels tinyint(1) DEFAULT 0 AFTER use_reply_to" );
		}
		
		// Check if we need to add enable_sender_copy column
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'enable_sender_copy'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD enable_sender_copy tinyint(1) DEFAULT 0 AFTER use_reply_to" );
		}
		
		// Check if we need to add debug_mode column
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_forms' AND column_name = 'debug_mode'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD debug_mode tinyint(1) DEFAULT 0 AFTER hide_labels" );
		}
		
		// Check if we need to add is_spam column to submissions table
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_submissions' AND column_name = 'is_spam'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_submissions ADD is_spam tinyint(1) DEFAULT 0 AFTER user_agent" );
		}
		
		// Check if we need to add email delivery tracking columns
		$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->prefix}spf_submissions' AND column_name = 'email_sent'" );
		if ( empty( $row ) ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_submissions ADD email_sent tinyint(1) DEFAULT 0 AFTER is_spam" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_submissions ADD email_status varchar(50) DEFAULT NULL AFTER email_sent" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_submissions ADD email_error text DEFAULT NULL AFTER email_status" );
		}

		// Form fields table.
		$table_name = $wpdb->prefix . 'spf_form_fields';
		$sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			field_type varchar(50) NOT NULL,
			field_label varchar(255) NOT NULL,
			field_name varchar(255) NOT NULL,
			field_placeholder varchar(255) DEFAULT NULL,
			field_required tinyint(1) DEFAULT 0,
			field_width varchar(20) DEFAULT '100',
			field_order int(11) DEFAULT 0,
			field_styles longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id)
		) {$charset_collate};";

		// Form submissions table.
		$table_name = $wpdb->prefix . 'spf_submissions';
		$sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			form_id bigint(20) NOT NULL,
			submission_data longtext NOT NULL,
			user_ip varchar(100) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			is_spam tinyint(1) DEFAULT 0,
			email_sent tinyint(1) DEFAULT 0,
			email_status varchar(50) DEFAULT NULL,
			email_error text DEFAULT NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY submitted_at (submitted_at),
			KEY is_spam (is_spam)
		) {$charset_collate};";

		// Blocked IPs table.
		$table_name = $wpdb->prefix . 'spf_blocked_ips';
		$sql[] = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			ip_address varchar(100) NOT NULL,
			block_reason varchar(255) DEFAULT NULL,
			blocked_at datetime DEFAULT CURRENT_TIMESTAMP,
			blocked_until datetime DEFAULT NULL,
			is_permanent tinyint(1) DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY ip_address (ip_address),
			KEY blocked_until (blocked_until)
		) {$charset_collate}";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
	}

	/**
	 * Get a form by ID.
	 *
	 * @param int $form_id Form ID.
	 * @return object|null
	 */
	public function get_form( $form_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_forms';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $form_id ) );
	}

	/**
	 * Get all forms.
	 *
	 * @return array
	 */
	public function get_forms() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_forms';
		return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC" );
	}

	/**
	 * Get form fields.
	 *
	 * @param int $form_id Form ID.
	 * @return array
	 */
	public function get_form_fields( $form_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_form_fields';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE form_id = %d ORDER BY field_order ASC", $form_id ) );
	}

	/**
	 * Save form.
	 *
	 * @param array $data Form data.
	 * @return int|false
	 */
	public function save_form( $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_forms';

		$form_data = array(
			'form_name' => sanitize_text_field( $data['form_name'] ),
			'form_title' => sanitize_text_field( $data['form_title'] ?? '' ),
			'form_subject' => sanitize_text_field( $data['form_subject'] ?? '' ),
			'recipient_email' => sanitize_email( $data['recipient_email'] ?? '' ),
			'sender_name' => sanitize_text_field( $data['sender_name'] ?? '' ),
			'sender_email' => sanitize_email( $data['sender_email'] ?? '' ),
			'button_text' => sanitize_text_field( $data['button_text'] ?? 'Submit' ),
			'button_styles' => wp_json_encode( $data['button_styles'] ?? array() ),
			'modal_button_text' => sanitize_text_field( $data['modal_button_text'] ?? 'Open Form' ),
			'modal_button_styles' => wp_json_encode( $data['modal_button_styles'] ?? array() ),
			'modal_styles' => wp_json_encode( $data['modal_styles'] ?? array() ),
		'use_global_styles' => ! empty( $data['use_global_styles'] ) ? 1 : 0,
		'use_reply_to' => ! empty( $data['use_reply_to'] ) ? 1 : 0,
		'enable_sender_copy' => ! empty( $data['enable_sender_copy'] ) ? 1 : 0,
		'hide_labels' => ! empty( $data['hide_labels'] ) ? 1 : 0,
		'debug_mode' => ! empty( $data['debug_mode'] ) ? 1 : 0,
		'success_message' => sanitize_textarea_field( $data['success_message'] ?? '' ),
			'error_message' => sanitize_textarea_field( $data['error_message'] ?? '' ),
		);

		if ( ! empty( $data['form_id'] ) ) {
			$wpdb->update( $table_name, $form_data, array( 'id' => intval( $data['form_id'] ) ) );
			return intval( $data['form_id'] );
		} else {
			$wpdb->insert( $table_name, $form_data );
			return $wpdb->insert_id;
		}
	}

	/**
	 * Save form fields.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $fields Fields data.
	 * @return bool
	 */
	public function save_form_fields( $form_id, $fields ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_form_fields';

		// Delete existing fields.
		$wpdb->delete( $table_name, array( 'form_id' => $form_id ) );

		// Insert new fields.
		foreach ( $fields as $index => $field ) {
			$field_data = array(
				'form_id' => $form_id,
				'field_type' => sanitize_text_field( $field['type'] ),
				'field_label' => sanitize_text_field( $field['label'] ),
				'field_name' => sanitize_title( $field['name'] ),
				'field_placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
				'field_required' => ! empty( $field['required'] ) ? 1 : 0,
				'field_width' => sanitize_text_field( $field['width'] ?? '100' ),
				'field_order' => $index,
				'field_styles' => wp_json_encode( $field['styles'] ?? array() ),
			);
			$wpdb->insert( $table_name, $field_data );
		}

		return true;
	}

	/**
	 * Delete form.
	 *
	 * @param int $form_id Form ID.
	 * @return bool
	 */
	public function delete_form( $form_id ) {
		global $wpdb;

		$forms_table = $wpdb->prefix . 'spf_forms';
		$fields_table = $wpdb->prefix . 'spf_fields';
		$submissions_table = $wpdb->prefix . 'spf_submissions';

		// Delete form fields.
		$wpdb->delete( $fields_table, array( 'form_id' => $form_id ) );

		// Delete submissions.
		$wpdb->delete( $submissions_table, array( 'form_id' => $form_id ) );

		// Delete form.
		return $wpdb->delete( $forms_table, array( 'id' => $form_id ) );
	}

	/**
	 * Duplicate form.
	 *
	 * @param int $form_id Form ID to duplicate.
	 * @return int|false New form ID on success, false on failure.
	 */
	public function duplicate_form( $form_id ) {
		global $wpdb;

		// Get original form.
		$form = $this->get_form( $form_id );
		if ( ! $form ) {
			return false;
		}

		// Get original fields.
		$fields = $this->get_form_fields( $form_id );

		// Prepare form data for duplication.
		$form_data = array(
			'form_name' => $form->form_name . ' (Copy)',
			'form_title' => $form->form_title,
			'form_subject' => $form->form_subject,
			'recipient_email' => $form->recipient_email,
			'sender_name' => $form->sender_name,
			'sender_email' => $form->sender_email,
			'button_text' => $form->button_text,
			'button_styles' => $form->button_styles,
			'modal_button_text' => $form->modal_button_text,
			'modal_button_styles' => $form->modal_button_styles,
			'modal_styles' => $form->modal_styles,
		'use_global_styles' => $form->use_global_styles,
		'use_reply_to' => $form->use_reply_to,
		'enable_sender_copy' => $form->enable_sender_copy ?? 0,
		'hide_labels' => $form->hide_labels ?? 0,
		'debug_mode' => $form->debug_mode ?? 0,
		'success_message' => $form->success_message,
			'error_message' => $form->error_message,
		);

		// Insert new form.
		$forms_table = $wpdb->prefix . 'spf_forms';
		$wpdb->insert( $forms_table, $form_data );
		$new_form_id = $wpdb->insert_id;

		if ( ! $new_form_id ) {
			return false;
		}

		// Duplicate fields.
		if ( ! empty( $fields ) ) {
			$fields_table = $wpdb->prefix . 'spf_fields';
			foreach ( $fields as $field ) {
				$field_data = array(
					'form_id' => $new_form_id,
					'field_type' => $field->field_type,
					'field_label' => $field->field_label,
					'field_name' => $field->field_name,
					'field_placeholder' => $field->field_placeholder,
					'field_required' => $field->field_required,
					'field_width' => $field->field_width,
					'field_styles' => $field->field_styles,
					'field_order' => $field->field_order,
				);
				$wpdb->insert( $fields_table, $field_data );
			}
		}

		return $new_form_id;
	}

	/**
	 * Save form submission.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data Submission data.
	 * @param bool  $is_spam Whether this is a spam submission.
	 * @param array $email_status Optional email delivery status.
	 * @return int|false Submission ID or false on failure.
	 */
	public function save_submission( $form_id, $data, $is_spam = false, $email_status = array() ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';

		$submission_data = array(
			'form_id' => $form_id,
			'submission_data' => wp_json_encode( $data ),
			'user_ip' => $this->get_user_ip(),
			'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
			'is_spam' => $is_spam ? 1 : 0,
			'email_sent' => ! empty( $email_status['sent'] ) ? 1 : 0,
			'email_status' => sanitize_text_field( $email_status['status'] ?? '' ),
			'email_error' => sanitize_text_field( $email_status['error'] ?? '' ),
		);

		$wpdb->insert( $table_name, $submission_data );
		return $wpdb->insert_id;
	}

	/**
	 * Get submissions.
	 *
	 * @param int  $form_id Optional form ID to filter by.
	 * @param bool $spam_only Whether to get only spam submissions.
	 * @return array
	 */
	public function get_submissions( $form_id = 0, $spam_only = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';
		$spam_filter = $spam_only ? 1 : 0;

		if ( $form_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE form_id = %d AND is_spam = %d ORDER BY submitted_at DESC", $form_id, $spam_filter ) );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE is_spam = %d ORDER BY submitted_at DESC", $spam_filter ) );
	}

	/**
	 * Get submission by ID.
	 *
	 * @param int $submission_id Submission ID.
	 * @return object|null
	 */
	public function get_submission( $submission_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $submission_id ) );
	}

	/**
	 * Delete submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return bool
	 */
	public function delete_submission( $submission_id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'spf_submissions', array( 'id' => $submission_id ) );
	}

	/**
	 * Update submission email status.
	 *
	 * @param int   $submission_id Submission ID.
	 * @param array $email_status Email status data.
	 * @return bool
	 */
	public function update_submission_email_status( $submission_id, $email_status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';
		
		$update_data = array(
			'email_sent' => ! empty( $email_status['sent'] ) ? 1 : 0,
			'email_status' => sanitize_text_field( $email_status['status'] ?? '' ),
			'email_error' => sanitize_text_field( $email_status['error'] ?? '' ),
		);
		
		return $wpdb->update( $table_name, $update_data, array( 'id' => $submission_id ) );
	}

	/**
	 * Get user IP address.
	 *
	 * @return string
	 */
	public function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return '';
	}

	/**
	 * Check if IP is blocked.
	 *
	 * @param string $ip_address IP address to check.
	 * @return bool
	 */
	public function is_ip_blocked( $ip_address ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_blocked_ips';
		
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE ip_address = %s AND (is_permanent = 1 OR blocked_until IS NULL OR blocked_until > NOW())",
			$ip_address
		) );
		
		return ! empty( $result );
	}

	/**
	 * Block an IP address.
	 *
	 * @param string $ip_address IP address to block.
	 * @param string $reason Block reason.
	 * @param int    $duration Duration in minutes (0 for permanent).
	 * @return bool
	 */
	public function block_ip( $ip_address, $reason = '', $duration = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_blocked_ips';
		
		$blocked_until = null;
		$is_permanent = 1;
		
		if ( $duration > 0 ) {
			$blocked_until = date( 'Y-m-d H:i:s', time() + ( $duration * 60 ) );
			$is_permanent = 0;
		}
		
		$data = array(
			'ip_address' => $ip_address,
			'block_reason' => $reason,
			'blocked_until' => $blocked_until,
			'is_permanent' => $is_permanent,
		);
		
		// Check if IP already blocked
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE ip_address = %s",
			$ip_address
		) );
		
		if ( $existing ) {
			return $wpdb->update( $table_name, $data, array( 'ip_address' => $ip_address ) );
		}
		
		return $wpdb->insert( $table_name, $data );
	}

	/**
	 * Unblock an IP address.
	 *
	 * @param string $ip_address IP address to unblock.
	 * @return bool
	 */
	public function unblock_ip( $ip_address ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_blocked_ips';
		return $wpdb->delete( $table_name, array( 'ip_address' => $ip_address ) );
	}

	/**
	 * Get all blocked IPs.
	 *
	 * @return array
	 */
	public function get_blocked_ips() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_blocked_ips';
		return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY blocked_at DESC" );
	}

	/**
	 * Check rate limit for IP.
	 *
	 * @param string $ip_address IP address.
	 * @param int    $form_id Form ID.
	 * @return bool True if rate limit exceeded.
	 */
	public function check_rate_limit( $ip_address, $form_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';
		
		$rate_limit = get_option( 'spf_rate_limit_submissions', 5 );
		$time_window = get_option( 'spf_rate_limit_minutes', 10 );
		
		if ( empty( $rate_limit ) || empty( $time_window ) ) {
			return false;
		}
		
		$time_ago = date( 'Y-m-d H:i:s', time() - ( $time_window * 60 ) );
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE user_ip = %s AND form_id = %d AND submitted_at > %s",
			$ip_address,
			$form_id,
			$time_ago
		) );
		
		return $count >= $rate_limit;
	}

	/**
	 * Get country from IP address.
	 *
	 * @param string $ip_address IP address.
	 * @return string Country code or empty string.
	 */
	public function get_country_from_ip( $ip_address ) {
		// Use a free IP geolocation API
		$response = wp_remote_get( 'http://ip-api.com/json/' . $ip_address . '?fields=countryCode' );
		
		if ( is_wp_error( $response ) ) {
			return '';
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		return isset( $data['countryCode'] ) ? $data['countryCode'] : '';
	}

	/**
	 * Check if content contains spam keywords.
	 *
	 * @param array $field_data Associative array of field data.
	 * @return string|false Returns matched keyword if found, false otherwise.
	 */
	public function contains_spam_keywords( $field_data ) {
		if ( ! get_option( 'spf_spam_keywords_enabled' ) ) {
			return false;
		}

		$keywords_text = get_option( 'spf_spam_keywords', '' );
		if ( empty( $keywords_text ) ) {
			return false;
		}

		// Parse keywords - one per line
		$keywords = array_filter( array_map( 'trim', explode( "\n", $keywords_text ) ) );
		if ( empty( $keywords ) ) {
			return false;
		}

		// Combine all field values into searchable text
		$content = implode( ' ', array_values( $field_data ) );
		$content_lower = strtolower( $content );

		// Check each keyword
		foreach ( $keywords as $keyword ) {
			$keyword_lower = strtolower( trim( $keyword ) );
			if ( empty( $keyword_lower ) ) {
				continue;
			}

			// Check if keyword is found in content
			if ( strpos( $content_lower, $keyword_lower ) !== false ) {
				return $keyword; // Return the matched keyword
			}
		}

		return false;
	}
}
