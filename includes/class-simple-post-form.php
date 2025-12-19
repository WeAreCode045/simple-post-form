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
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD success_message text DEFAULT NULL AFTER use_global_styles" );
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}spf_forms ADD error_message text DEFAULT NULL AFTER success_message" );
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
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY form_id (form_id),
			KEY submitted_at (submitted_at)
		) {$charset_collate};";

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

		// Delete form fields.
		$wpdb->delete( $wpdb->prefix . 'spf_form_fields', array( 'form_id' => $form_id ) );

		// Delete form submissions.
		$wpdb->delete( $wpdb->prefix . 'spf_submissions', array( 'form_id' => $form_id ) );

		// Delete form.
		$wpdb->delete( $wpdb->prefix . 'spf_forms', array( 'id' => $form_id ) );

		return true;
	}

	/**
	 * Save form submission.
	 *
	 * @param int   $form_id Form ID.
	 * @param array $data Submission data.
	 * @return int|false Submission ID or false on failure.
	 */
	public function save_submission( $form_id, $data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';

		$submission_data = array(
			'form_id' => $form_id,
			'submission_data' => wp_json_encode( $data ),
			'user_ip' => $this->get_user_ip(),
			'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
		);

		$wpdb->insert( $table_name, $submission_data );
		return $wpdb->insert_id;
	}

	/**
	 * Get submissions.
	 *
	 * @param int $form_id Optional form ID to filter by.
	 * @return array
	 */
	public function get_submissions( $form_id = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spf_submissions';

		if ( $form_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE form_id = %d ORDER BY submitted_at DESC", $form_id ) );
		}

		return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY submitted_at DESC" );
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
	 * Get user IP address.
	 *
	 * @return string
	 */
	private function get_user_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		}
		return sanitize_text_field( $ip );
	}
}
