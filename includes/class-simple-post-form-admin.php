<?php
/**
 * Admin Class
 *
 * @package Code045\Simple_Post_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Class.
 */
class Simple_Post_Form_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_spf_save_form', array( $this, 'ajax_save_form' ) );
		add_action( 'wp_ajax_spf_delete_form', array( $this, 'ajax_delete_form' ) );
		add_action( 'wp_ajax_spf_duplicate_form', array( $this, 'ajax_duplicate_form' ) );
		add_action( 'wp_ajax_spf_get_form', array( $this, 'ajax_get_form' ) );
		add_action( 'wp_ajax_spf_delete_submission', array( $this, 'ajax_delete_submission' ) );
		add_action( 'wp_ajax_spf_unblock_ip', array( $this, 'ajax_unblock_ip' ) );
		add_action( 'wp_ajax_spf_block_ip', array( $this, 'ajax_block_ip' ) );
		add_action( 'wp_ajax_spf_resend_submission', array( $this, 'ajax_resend_submission' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function admin_menu() {
		add_menu_page(
			__( 'Simple Form Builder', 'simple-post-form' ),
			__( 'Form Builder', 'simple-post-form' ),
			'manage_options',
			'simple-post-form',
			array( $this, 'forms_list_page' ),
			'dashicons-feedback',
			30
		);

		add_submenu_page(
			'simple-post-form',
			__( 'All Forms', 'simple-post-form' ),
			__( 'All Forms', 'simple-post-form' ),
			'manage_options',
			'simple-post-form',
			array( $this, 'forms_list_page' )
		);

		add_submenu_page(
			'simple-post-form',
			__( 'Add New Form', 'simple-post-form' ),
			__( 'Add New', 'simple-post-form' ),
			'manage_options',
			'simple-post-form-new',
			array( $this, 'form_builder_page' )
		);

		add_submenu_page(
			'simple-post-form',
			__( 'Submissions', 'simple-post-form' ),
			__( 'Submissions', 'simple-post-form' ),
			'manage_options',
			'simple-post-form-submissions',
			array( $this, 'submissions_page' )
		);

		add_submenu_page(
			'simple-post-form',
			__( 'Blocked IPs', 'simple-post-form' ),
			__( 'Blocked IPs', 'simple-post-form' ),
			'manage_options',
			'simple-post-form-blocked-ips',
			array( $this, 'blocked_ips_page' )
		);

		add_submenu_page(
			'simple-post-form',
			__( 'Settings', 'simple-post-form' ),
			__( 'Settings', 'simple-post-form' ),
			'manage_options',
			'simple-post-form-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		if ( strpos( $hook, 'simple-post-form' ) === false ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		
		wp_enqueue_style(
			'spf-admin-css',
			SPF_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SPF_VERSION
		);

		wp_enqueue_script(
			'spf-admin-js',
			SPF_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ),
			SPF_VERSION,
			true
		);

		wp_localize_script(
			'spf-admin-js',
			'spfAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'spf-admin-nonce' ),
				'strings' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this form?', 'simple-post-form' ),
'confirmDuplicate' => __( 'Create a copy of this form?', 'simple-post-form' ),
					'confirmDeleteField' => __( 'Are you sure you want to delete this field?', 'simple-post-form' ),
					'formSaved' => __( 'Form saved successfully!', 'simple-post-form' ),
					'error' => __( 'An error occurred. Please try again.', 'simple-post-form' ),
				),
			)
		);
	}

	/**
	 * Forms list page.
	 */
	public function forms_list_page() {
		$forms = simple_post_form()->get_forms();
		?>
		<div class="wrap spf-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'All Forms', 'simple-post-form' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-new' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'simple-post-form' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( empty( $forms ) ) : ?>
				<div class="spf-empty-state">
					<p><?php esc_html_e( 'No forms found. Create your first form!', 'simple-post-form' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-new' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Create Form', 'simple-post-form' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Form Name', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Inline Shortcode', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Modal Shortcode', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Created', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'simple-post-form' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $forms as $form ) : ?>
							<tr>
								<td>
									<strong>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-new&form_id=' . $form->id ) ); ?>">
											<?php echo esc_html( $form->form_name ); ?>
										</a>
									</strong>
								</td>
								<td>
									<input type="text" readonly value='[simple_form id="<?php echo esc_attr( $form->id ); ?>"]' 
										   onclick="this.select()" style="width: 100%; max-width: 250px;">
								</td>
								<td>
									<input type="text" readonly value='[simple_form_modal id="<?php echo esc_attr( $form->id ); ?>"]' 
										   onclick="this.select()" style="width: 100%; max-width: 250px;">
								</td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $form->created_at ) ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-new&form_id=' . $form->id ) ); ?>" 
									   class="button button-small">
										<?php esc_html_e( 'Edit', 'simple-post-form' ); ?>
									</a>
									<button class="button button-small spf-delete-form" data-form-id="<?php echo esc_attr( $form->id ); ?>">
<button class="button button-small spf-duplicate-form" data-form-id="<?php echo esc_attr( $form->id ); ?>">
<?php esc_html_e( 'Duplicate', 'simple-post-form' ); ?>
</button>
										<?php esc_html_e( 'Delete', 'simple-post-form' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Form builder page.
	 */
	public function form_builder_page() {
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
		$form = $form_id ? simple_post_form()->get_form( $form_id ) : null;
		$fields = $form_id ? simple_post_form()->get_form_fields( $form_id ) : array();

		$button_styles = $form && $form->button_styles ? json_decode( $form->button_styles, true ) : array();
		$modal_button_styles = $form && $form->modal_button_styles ? json_decode( $form->modal_button_styles, true ) : array();
		$modal_styles = $form && $form->modal_styles ? json_decode( $form->modal_styles, true ) : array();
		?>
		<div class="wrap spf-wrap">
			<h1><?php echo $form_id ? esc_html__( 'Edit Form', 'simple-post-form' ) : esc_html__( 'Create New Form', 'simple-post-form' ); ?></h1>
			
			<input type="hidden" id="spf-form-id" value="<?php echo esc_attr( $form_id ); ?>">

			<!-- Tab Navigation -->
			<h2 class="nav-tab-wrapper spf-tab-wrapper">
				<a href="#" class="nav-tab nav-tab-active" data-tab="builder"><?php esc_html_e( 'Form Builder', 'simple-post-form' ); ?></a>
				<a href="#" class="nav-tab" data-tab="settings"><?php esc_html_e( 'Form Settings', 'simple-post-form' ); ?></a>
			</h2>

			<!-- Tab: Form Builder -->
			<div id="spf-tab-builder" class="spf-tab-content spf-tab-active">
				<div class="spf-builder-container">
					<div class="spf-builder-sidebar">
						<h2><?php esc_html_e( 'Available Fields', 'simple-post-form' ); ?></h2>
						<div class="spf-field-types">
							<div class="spf-field-type" data-type="text" draggable="true">
								<span class="dashicons dashicons-text"></span>
								<?php esc_html_e( 'Text Field', 'simple-post-form' ); ?>
							</div>
							<div class="spf-field-type" data-type="textarea" draggable="true">
								<span class="dashicons dashicons-editor-alignleft"></span>
								<?php esc_html_e( 'Textarea', 'simple-post-form' ); ?>
							</div>
							<div class="spf-field-type" data-type="email" draggable="true">
								<span class="dashicons dashicons-email"></span>
								<?php esc_html_e( 'Email', 'simple-post-form' ); ?>
							</div>
							<div class="spf-field-type" data-type="phone" draggable="true">
								<span class="dashicons dashicons-phone"></span>
								<?php esc_html_e( 'Phone', 'simple-post-form' ); ?>
							</div>
							<div class="spf-field-type" data-type="name" draggable="true">
								<span class="dashicons dashicons-admin-users"></span>
								<?php esc_html_e( 'Name', 'simple-post-form' ); ?>
							</div>
						<div class="spf-field-type" data-type="number" draggable="true">
							<span class="dashicons dashicons-calculator"></span>
							<?php esc_html_e( 'Number', 'simple-post-form' ); ?>
						</div>
						<div class="spf-field-type" data-type="honeypot" draggable="true">
							<span class="dashicons dashicons-shield"></span>
							<?php esc_html_e( 'Honeypot (Anti-Spam)', 'simple-post-form' ); ?>
						</div>
					</div>
				</div>

				<div class="spf-builder-main">
						<div class="spf-form-canvas">
							<h2>
								<?php esc_html_e( 'Form Preview', 'simple-post-form' ); ?>
								<span class="spf-preview-badge"><?php esc_html_e( 'Live Preview', 'simple-post-form' ); ?></span>
							</h2>
							<div id="spf-form-fields" class="spf-drop-zone">
								<?php if ( empty( $fields ) ) : ?>
									<div class="spf-empty-message">
										<?php esc_html_e( 'Drag fields here to build your form', 'simple-post-form' ); ?>
									</div>
								<?php endif; ?>
							</div>
							<div class="spf-preview-submit-wrapper">
								<button type="button" id="spf-preview-submit-button" class="spf-preview-submit-button">
									<?php echo $form ? esc_html( $form->button_text ) : esc_html__( 'Submit', 'simple-post-form' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Tab: Form Settings -->
			<div id="spf-tab-settings" class="spf-tab-content" style="display: none;">
				<div class="spf-settings-container">
					<div class="spf-form-settings-panel">
						<h2><?php esc_html_e( 'Form Information', 'simple-post-form' ); ?></h2>
						
						<div class="spf-form-group">
							<label><?php esc_html_e( 'Form Name', 'simple-post-form' ); ?> *</label>
							<input type="text" id="spf-form-name" value="<?php echo $form ? esc_attr( $form->form_name ) : ''; ?>" required>
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Form Title', 'simple-post-form' ); ?></label>
							<input type="text" id="spf-form-title" value="<?php echo $form ? esc_attr( $form->form_title ) : ''; ?>">
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Form Subject', 'simple-post-form' ); ?></label>
							<input type="text" id="spf-form-subject" value="<?php echo $form ? esc_attr( $form->form_subject ) : ''; ?>">
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Recipient Email', 'simple-post-form' ); ?> *</label>
							<input type="email" id="spf-recipient-email" value="<?php echo $form ? esc_attr( $form->recipient_email ) : get_option( 'admin_email' ); ?>" required>
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Sender Name', 'simple-post-form' ); ?></label>
							<input type="text" id="spf-sender-name" value="<?php echo $form ? esc_attr( $form->sender_name ) : get_bloginfo( 'name' ); ?>">
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Sender Email', 'simple-post-form' ); ?></label>
							<input type="email" id="spf-sender-email" value="<?php echo $form ? esc_attr( $form->sender_email ) : get_option( 'admin_email' ); ?>">
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Button Text', 'simple-post-form' ); ?></label>
							<input type="text" id="spf-button-text" value="<?php echo $form ? esc_attr( $form->button_text ) : 'Submit'; ?>">
						</div>

						<h3><?php esc_html_e( 'Response Messages', 'simple-post-form' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Customize the success and error messages for this form. Leave empty to use global default messages.', 'simple-post-form' ); ?></p>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Success Message', 'simple-post-form' ); ?></label>
							<textarea id="spf-success-message" rows="2" placeholder="<?php esc_attr_e( 'Use global default message', 'simple-post-form' ); ?>"><?php echo $form ? esc_textarea( $form->success_message ) : ''; ?></textarea>
						</div>

						<div class="spf-form-group">
							<label><?php esc_html_e( 'Error Message', 'simple-post-form' ); ?></label>
							<textarea id="spf-error-message" rows="2" placeholder="<?php esc_attr_e( 'Use global default message', 'simple-post-form' ); ?>"><?php echo $form ? esc_textarea( $form->error_message ) : ''; ?></textarea>
						</div>

					<h3><?php esc_html_e( 'Email Settings', 'simple-post-form' ); ?></h3>

					<div class="spf-form-group">
						<label>
					<input type="checkbox" id="spf-use-reply-to" value="1" <?php checked( $form && $form->use_reply_to, 1 ); ?>>
					<?php esc_html_e( 'Use email field as Reply-To address', 'simple-post-form' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, the first email field in the form will be set as the Reply-To address in the notification email.', 'simple-post-form' ); ?></p>
			</div>

			<div class="spf-form-group">
				<label>
					<input type="checkbox" id="spf-enable-sender-copy" value="1" <?php checked( $form && $form->enable_sender_copy, 1 ); ?>>
					<?php esc_html_e( 'Show "Send me a copy" checkbox on form', 'simple-post-form' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, a checkbox will appear on the form allowing the sender to receive a copy of their submission at the email address they provide.', 'simple-post-form' ); ?></p>
			</div>

			<h3><?php esc_html_e( 'Display Options', 'simple-post-form' ); ?></h3>					<div class="spf-form-group">
						<label>
							<input type="checkbox" id="spf-hide-labels" value="1" <?php checked( $form && $form->hide_labels, 1 ); ?>>
							<?php esc_html_e( 'Hide field labels on frontend', 'simple-post-form' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, field labels will be hidden. Placeholders will still be visible.', 'simple-post-form' ); ?></p>
					</div>

					<h3><?php esc_html_e( 'Debug & Testing', 'simple-post-form' ); ?></h3>

					<div class="spf-form-group">
						<label>
							<input type="checkbox" id="spf-debug-mode" value="1" <?php checked( $form && $form->debug_mode, 1 ); ?>>
							<?php esc_html_e( 'Enable debug mode for honeypot field', 'simple-post-form' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, the honeypot anti-spam field will be visible to logged-in administrators so you can test spam blocking. The field will remain hidden for non-admin users.', 'simple-post-form' ); ?></p>
					</div>

					<h3><?php esc_html_e( 'Styling Options', 'simple-post-form' ); ?></h3>

					<div class="spf-form-group">
						<label>
							<input type="checkbox" id="spf-use-global-styles" value="1" <?php checked( $form && $form->use_global_styles, 1 ); ?>>
							<?php esc_html_e( 'Use global styling settings', 'simple-post-form' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, this form will use the global styling settings from the plugin settings page.', 'simple-post-form' ); ?></p>
					</div>						<h3><?php esc_html_e( 'Button Styling', 'simple-post-form' ); ?></h3>

						<div class="spf-form-row">
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Background Color', 'simple-post-form' ); ?></label>
								<input type="text" class="spf-color-picker" id="spf-btn-bg-color" 
									   value="<?php echo esc_attr( $button_styles['backgroundColor'] ?? '#0073aa' ); ?>">
							</div>
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Hover Background', 'simple-post-form' ); ?></label>
								<input type="text" class="spf-color-picker" id="spf-btn-hover-bg" 
									   value="<?php echo esc_attr( $button_styles['hoverBackgroundColor'] ?? '#005177' ); ?>">
							</div>
						</div>

						<div class="spf-form-row">
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Text Color', 'simple-post-form' ); ?></label>
								<input type="text" class="spf-color-picker" id="spf-btn-color" 
									   value="<?php echo esc_attr( $button_styles['color'] ?? '#ffffff' ); ?>">
							</div>
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Hover Text Color', 'simple-post-form' ); ?></label>
								<input type="text" class="spf-color-picker" id="spf-btn-hover-color" 
									   value="<?php echo esc_attr( $button_styles['hoverColor'] ?? '#ffffff' ); ?>">
							</div>
						</div>

						<div class="spf-form-row">
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Border Radius', 'simple-post-form' ); ?></label>
								<input type="text" id="spf-btn-border-radius" 
									   value="<?php echo esc_attr( $button_styles['borderRadius'] ?? '4px' ); ?>" 
									   placeholder="4px">
							</div>
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Font Size', 'simple-post-form' ); ?></label>
								<input type="text" id="spf-btn-font-size" 
									   value="<?php echo esc_attr( $button_styles['fontSize'] ?? '16px' ); ?>" 
									   placeholder="16px">
							</div>
						</div>

						<div class="spf-form-row">
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Font Weight', 'simple-post-form' ); ?></label>
								<select id="spf-btn-font-weight">
									<?php
									$weights = array( '300', '400', '500', '600', '700', '800' );
									$current_weight = $button_styles['fontWeight'] ?? '400';
									foreach ( $weights as $weight ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $weight ),
											selected( $current_weight, $weight, false ),
											esc_html( $weight )
										);
									}
									?>
								</select>
							</div>
							<div class="spf-form-group spf-col-6">
								<label><?php esc_html_e( 'Text Transform', 'simple-post-form' ); ?></label>
								<select id="spf-btn-text-transform">
									<?php
									$transforms = array(
										'none' => __( 'None', 'simple-post-form' ),
										'uppercase' => __( 'Uppercase', 'simple-post-form' ),
										'lowercase' => __( 'Lowercase', 'simple-post-form' ),
										'capitalize' => __( 'Capitalize', 'simple-post-form' ),
									);
									$current_transform = $button_styles['textTransform'] ?? 'none';
									foreach ( $transforms as $value => $label ) {
										printf(
											'<option value="%s" %s>%s</option>',
											esc_attr( $value ),
											selected( $current_transform, $value, false ),
											esc_html( $label )
										);
									}
									?>
								</select>
							</div>
						</div>
					</div>

				<h3><?php esc_html_e( 'Modal Button Settings', 'simple-post-form' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Configure a button that opens the form in a modal popup. Use shortcode: [simple_form_modal id="X"]', 'simple-post-form' ); ?></p>

				<div class="spf-form-group">
					<label><?php esc_html_e( 'Modal Button Text', 'simple-post-form' ); ?></label>
					<input type="text" id="spf-modal-button-text" value="<?php echo $form ? esc_attr( $form->modal_button_text ) : 'Open Form'; ?>">
				</div>

				<h4><?php esc_html_e( 'Modal Button Styling', 'simple-post-form' ); ?></h4>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Background Color', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-btn-bg-color" 
							   value="<?php echo esc_attr( $modal_button_styles['backgroundColor'] ?? '#0073aa' ); ?>">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Hover Background', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-btn-hover-bg" 
							   value="<?php echo esc_attr( $modal_button_styles['hoverBackgroundColor'] ?? '#005177' ); ?>">
					</div>
				</div>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Text Color', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-btn-color" 
							   value="<?php echo esc_attr( $modal_button_styles['color'] ?? '#ffffff' ); ?>">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Hover Text Color', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-btn-hover-color" 
							   value="<?php echo esc_attr( $modal_button_styles['hoverColor'] ?? '#ffffff' ); ?>">
					</div>
				</div>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Border Radius', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-btn-border-radius" 
							   value="<?php echo esc_attr( $modal_button_styles['borderRadius'] ?? '4px' ); ?>" 
							   placeholder="4px">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Font Size', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-btn-font-size" 
							   value="<?php echo esc_attr( $modal_button_styles['fontSize'] ?? '16px' ); ?>" 
							   placeholder="16px">
					</div>
				</div>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Font Weight', 'simple-post-form' ); ?></label>
						<select id="spf-modal-btn-font-weight">
							<?php
							$current_weight = $modal_button_styles['fontWeight'] ?? '400';
							foreach ( $weights as $weight ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $weight ),
									selected( $current_weight, $weight, false ),
									esc_html( $weight )
								);
							}
							?>
						</select>
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Padding', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-btn-padding" 
							   value="<?php echo esc_attr( $modal_button_styles['padding'] ?? '15px 40px' ); ?>" 
							   placeholder="15px 40px">
					</div>
				</div>

				<h4><?php esc_html_e( 'Modal Styling', 'simple-post-form' ); ?></h4>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Modal Width', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-width" 
							   value="<?php echo esc_attr( $modal_styles['width'] ?? '600px' ); ?>" 
							   placeholder="600px">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Modal Max Width', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-max-width" 
							   value="<?php echo esc_attr( $modal_styles['maxWidth'] ?? '90%' ); ?>" 
							   placeholder="90%">
					</div>
				</div>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Modal Background Color', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-bg-color" 
							   value="<?php echo esc_attr( $modal_styles['backgroundColor'] ?? '#ffffff' ); ?>">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Overlay Color', 'simple-post-form' ); ?></label>
						<input type="text" class="spf-color-picker" id="spf-modal-overlay-color" 
							   value="<?php echo esc_attr( $modal_styles['overlayColor'] ?? 'rgba(0,0,0,0.75)' ); ?>">
					</div>
				</div>

				<div class="spf-form-row">
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Border Radius', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-border-radius" 
							   value="<?php echo esc_attr( $modal_styles['borderRadius'] ?? '8px' ); ?>" 
							   placeholder="8px">
					</div>
					<div class="spf-form-group spf-col-6">
						<label><?php esc_html_e( 'Padding', 'simple-post-form' ); ?></label>
						<input type="text" id="spf-modal-padding" 
							   value="<?php echo esc_attr( $modal_styles['padding'] ?? '40px' ); ?>" 
							   placeholder="40px">
					</div>
					</div>
				</div>
			</div>

			<!-- Form Actions (visible on all tabs) -->
			<div class="spf-form-actions">
				<button type="button" id="spf-save-form" class="button button-primary button-large">
					<?php esc_html_e( 'Save Form', 'simple-post-form' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form' ) ); ?>" class="button button-large">
					<?php esc_html_e( 'Cancel', 'simple-post-form' ); ?>
				</a>
			</div>

			<?php if ( $form_id ) : ?>
				<div class="spf-shortcode-display">
					<h3><?php esc_html_e( 'Shortcode', 'simple-post-form' ); ?></h3>
					<input type="text" readonly value='[simple_form id="<?php echo esc_attr( $form_id ); ?>"]' 
						   onclick="this.select()" class="large-text">
					<p class="description">
						<?php esc_html_e( 'Copy this shortcode and paste it into your post or page.', 'simple-post-form' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<script type="text/template" id="spf-existing-fields">
			<?php echo wp_json_encode( $fields ); ?>
		</script>
		<?php
	}

	/**
	 * AJAX save form.
	 */
	public function ajax_save_form() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$form_data = isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : array();
		$fields_data = isset( $_POST['fields_data'] ) ? json_decode( wp_unslash( $_POST['fields_data'] ), true ) : array();

		if ( empty( $form_data['form_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Form name is required.', 'simple-post-form' ) ) );
		}

		$form_id = simple_post_form()->save_form( $form_data );

		if ( $form_id ) {
			simple_post_form()->save_form_fields( $form_id, $fields_data );
			wp_send_json_success( array(
				'message' => __( 'Form saved successfully!', 'simple-post-form' ),
				'form_id' => $form_id,
				'shortcode' => '[simple_form id="' . $form_id . '"]',
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to save form.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX delete form.
	 */
	public function ajax_delete_form() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		if ( $form_id && simple_post_form()->delete_form( $form_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Form deleted successfully!', 'simple-post-form' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete form.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX duplicate form.
	 */
	public function ajax_duplicate_form() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		if ( ! $form_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'simple-post-form' ) ) );
		}

		$new_form_id = simple_post_form()->duplicate_form( $form_id );

		if ( $new_form_id ) {
			wp_send_json_success( array( 
				'message' => __( 'Form duplicated successfully!', 'simple-post-form' ),
				'form_id' => $new_form_id,
				'edit_url' => admin_url( 'admin.php?page=simple-post-form-new&form_id=' . $new_form_id )
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to duplicate form.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX get form.
	 */
	public function ajax_get_form() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : 0;

		if ( $form_id ) {
			$form = simple_post_form()->get_form( $form_id );
			$fields = simple_post_form()->get_form_fields( $form_id );

			wp_send_json_success( array(
				'form' => $form,
				'fields' => $fields,
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * Submissions page.
	 */
	public function submissions_page() {
		$form_id = isset( $_GET['form_id'] ) ? intval( $_GET['form_id'] ) : 0;
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'submissions';
		$forms = simple_post_form()->get_forms();
		?>
		<div class="wrap spf-wrap">
			<h1><?php esc_html_e( 'Form Submissions', 'simple-post-form' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-submissions&tab=submissions' . ( $form_id ? '&form_id=' . $form_id : '' ) ) ); ?>" 
				   class="nav-tab <?php echo $tab === 'submissions' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Submissions', 'simple-post-form' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-submissions&tab=blocked' . ( $form_id ? '&form_id=' . $form_id : '' ) ) ); ?>" 
				   class="nav-tab <?php echo $tab === 'blocked' ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Blocked (Spam)', 'simple-post-form' ); ?>
				</a>
			</h2>

			<?php if ( ! empty( $forms ) ) : ?>
				<div style="margin: 20px 0;">
					<label for="spf-filter-form"><?php esc_html_e( 'Filter by form:', 'simple-post-form' ); ?></label>
					<select id="spf-filter-form" onchange="location = this.value;">
						<option value="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-submissions&tab=' . $tab ) ); ?>"><?php esc_html_e( 'All Forms', 'simple-post-form' ); ?></option>
						<?php foreach ( $forms as $form ) : ?>
							<option value="<?php echo esc_url( admin_url( 'admin.php?page=simple-post-form-submissions&tab=' . $tab . '&form_id=' . $form->id ) ); ?>" <?php selected( $form_id, $form->id ); ?>>
								<?php echo esc_html( $form->form_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>

			<?php
			if ( $tab === 'blocked' ) {
				$this->render_blocked_submissions( $form_id );
			} else {
				$this->render_submissions_list( $form_id );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render submissions list.
	 *
	 * @param int $form_id Form ID filter.
	 */
	private function render_submissions_list( $form_id ) {
		$submissions = simple_post_form()->get_submissions( $form_id, false );
		?>
		<?php if ( empty( $submissions ) ) : ?>
			<div class="spf-empty-state">
				<p><?php esc_html_e( 'No submissions found.', 'simple-post-form' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped spf-submissions-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Form Name', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Submission Data', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Email Status', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Submitted', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'simple-post-form' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $submissions as $submission ) : ?>
						<?php
						$form = simple_post_form()->get_form( $submission->form_id );
						$data = json_decode( $submission->submission_data, true );
						?>
						<tr>
							<td><strong><?php echo $form ? esc_html( $form->form_name ) : __( 'Unknown', 'simple-post-form' ); ?></strong></td>
							<td>
								<?php if ( $data ) : ?>
									<details>
										<summary><?php esc_html_e( 'View Data', 'simple-post-form' ); ?></summary>
										<div style="margin-top: 10px;">
											<?php foreach ( $data as $key => $value ) : ?>
												<p style="margin: 5px 0;"><strong><?php echo esc_html( $key ); ?>:</strong> <?php echo esc_html( $value ); ?></p>
											<?php endforeach; ?>
										</div>
									</details>
								<?php endif; ?>
							</td>
							<td class="spf-email-status" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
								<?php
								$status_class = '';
								$status_text = __( 'Unknown', 'simple-post-form' );
								$status_icon = 'dashicons-minus';
								
								if ( ! empty( $submission->email_sent ) ) {
									$status_class = 'success';
									$status_text = __( 'Delivered', 'simple-post-form' );
									$status_icon = 'dashicons-yes-alt';
								} elseif ( isset( $submission->email_sent ) && $submission->email_sent == 0 && ! empty( $submission->email_status ) ) {
									$status_class = 'error';
									$status_text = __( 'Failed', 'simple-post-form' );
									$status_icon = 'dashicons-dismiss';
								}
								?>
								<span class="spf-status-badge <?php echo esc_attr( $status_class ); ?>">
									<span class="dashicons <?php echo esc_attr( $status_icon ); ?>" style="font-size: 14px; width: 14px; height: 14px; line-height: 1;"></span>
									<?php echo esc_html( $status_text ); ?>
								</span>
								<?php if ( ! empty( $submission->email_error ) ) : ?>
									<details style="margin-top: 5px;">
										<summary style="cursor: pointer; color: #d63638; font-size: 12px;"><?php esc_html_e( 'View Error', 'simple-post-form' ); ?></summary>
										<div style="margin-top: 5px; padding: 8px; background: #fee; border: 1px solid #fcc; border-radius: 3px; font-size: 12px;">
											<code><?php echo esc_html( $submission->email_error ); ?></code>
										</div>
									</details>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $submission->user_ip ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->submitted_at ) ) ); ?></td>
							<td>
								<button class="button button-small spf-resend-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>" title="<?php esc_attr_e( 'Resend email notification', 'simple-post-form' ); ?>">
									<span class="dashicons dashicons-email" style="margin-top: 3px;"></span>
									<?php esc_html_e( 'Resend', 'simple-post-form' ); ?>
								</button>
								<button class="button button-small spf-delete-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
									<?php esc_html_e( 'Delete', 'simple-post-form' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render blocked submissions list.
	 *
	 * @param int $form_id Form ID filter.
	 */
	private function render_blocked_submissions( $form_id ) {
		$submissions = simple_post_form()->get_submissions( $form_id, true );
		?>
		<?php if ( empty( $submissions ) ) : ?>
			<div class="spf-empty-state">
				<p><?php esc_html_e( 'No blocked submissions found.', 'simple-post-form' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped spf-submissions-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Form Name', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Blocked Reason', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'User Agent', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Blocked At', 'simple-post-form' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'simple-post-form' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $submissions as $submission ) : ?>
						<?php
						$form = simple_post_form()->get_form( $submission->form_id );
						$data = json_decode( $submission->submission_data, true );
						$blocked_reason = isset( $data['blocked_reason'] ) ? $data['blocked_reason'] : __( 'Unknown', 'simple-post-form' );
						?>
						<tr>
							<td><strong><?php echo $form ? esc_html( $form->form_name ) : __( 'Unknown', 'simple-post-form' ); ?></strong></td>
							<td>
								<span class="dashicons dashicons-shield" style="color: #d63638;"></span>
								<?php echo esc_html( $blocked_reason ); ?>
							</td>
							<td><?php echo esc_html( $submission->user_ip ); ?></td>
							<td><code style="font-size: 11px;"><?php echo esc_html( wp_trim_words( $submission->user_agent, 10, '...' ) ); ?></code></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->submitted_at ) ) ); ?></td>
							<td>
								<button class="button button-small spf-delete-submission" data-submission-id="<?php echo esc_attr( $submission->id ); ?>">
									<?php esc_html_e( 'Delete', 'simple-post-form' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Settings page.
	 */
	public function settings_page() {
		// Save settings
		if ( isset( $_POST['spf_save_settings'] ) && check_admin_referer( 'spf-settings-nonce' ) ) {
			update_option( 'spf_global_button_styles', wp_json_encode( $_POST['button_styles'] ?? array() ) );
			update_option( 'spf_global_modal_button_styles', wp_json_encode( $_POST['modal_button_styles'] ?? array() ) );
			update_option( 'spf_global_modal_styles', wp_json_encode( $_POST['modal_styles'] ?? array() ) );
			update_option( 'spf_success_message', sanitize_textarea_field( $_POST['success_message'] ?? '' ) );
			update_option( 'spf_error_message', sanitize_textarea_field( $_POST['error_message'] ?? '' ) );
			update_option( 'spf_rate_limit_enabled', isset( $_POST['rate_limit_enabled'] ) ? 1 : 0 );
			update_option( 'spf_rate_limit_submissions', absint( $_POST['rate_limit_submissions'] ?? 5 ) );
			update_option( 'spf_rate_limit_minutes', absint( $_POST['rate_limit_minutes'] ?? 10 ) );
			update_option( 'spf_rate_limit_block_duration', absint( $_POST['rate_limit_block_duration'] ?? 60 ) );
			update_option( 'spf_country_blocking_enabled', isset( $_POST['country_blocking_enabled'] ) ? 1 : 0 );
			update_option( 'spf_blocked_countries', sanitize_textarea_field( $_POST['blocked_countries'] ?? '' ) );
			update_option( 'spf_smtp_enabled', isset( $_POST['smtp_enabled'] ) ? 1 : 0 );
			update_option( 'spf_smtp_host', sanitize_text_field( $_POST['smtp_host'] ?? '' ) );
			update_option( 'spf_smtp_port', absint( $_POST['smtp_port'] ?? 587 ) );
			update_option( 'spf_smtp_encryption', sanitize_text_field( $_POST['smtp_encryption'] ?? 'tls' ) );
			update_option( 'spf_smtp_auth', isset( $_POST['smtp_auth'] ) ? 1 : 0 );
			update_option( 'spf_smtp_username', sanitize_text_field( $_POST['smtp_username'] ?? '' ) );
			update_option( 'spf_smtp_password', sanitize_text_field( $_POST['smtp_password'] ?? '' ) );
			update_option( 'spf_smtp_from_email', sanitize_email( $_POST['smtp_from_email'] ?? '' ) );
			update_option( 'spf_smtp_from_name', sanitize_text_field( $_POST['smtp_from_name'] ?? '' ) );
			update_option( 'spf_spam_keywords_enabled', isset( $_POST['spam_keywords_enabled'] ) ? 1 : 0 );
			update_option( 'spf_spam_keywords', sanitize_textarea_field( $_POST['spam_keywords'] ?? '' ) );
			
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully!', 'simple-post-form' ) . '</p></div>';
		}

		$button_styles = json_decode( get_option( 'spf_global_button_styles', '{}' ), true );
		$modal_button_styles = json_decode( get_option( 'spf_global_modal_button_styles', '{}' ), true );
		$modal_styles = json_decode( get_option( 'spf_global_modal_styles', '{}' ), true );
		$success_message = get_option( 'spf_success_message', '' );
		$error_message = get_option( 'spf_error_message', '' );
		$weights = array( '300', '400', '500', '600', '700', '800' );
		?>
		<div class="wrap spf-wrap">
			<h1><?php esc_html_e( 'Plugin Settings', 'simple-post-form' ); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field( 'spf-settings-nonce' ); ?>

				<h2><?php esc_html_e( 'Response Messages', 'simple-post-form' ); ?></h2>
				<p class="description"><?php esc_html_e( 'These messages will be used as default for all forms. You can override them per form in the form settings.', 'simple-post-form' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="spf-success-message"><?php esc_html_e( 'Success Message', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<textarea id="spf-success-message" name="success_message" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Form submitted successfully! We will get back to you soon.', 'simple-post-form' ); ?>"><?php echo esc_textarea( $success_message ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Message shown when form is submitted successfully.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="spf-error-message"><?php esc_html_e( 'Error Message', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<textarea id="spf-error-message" name="error_message" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Failed to send email. Please try again later.', 'simple-post-form' ); ?>"><?php echo esc_textarea( $error_message ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Message shown when form submission fails.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Global Styling', 'simple-post-form' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Set default styles for all forms. Forms can opt-in to use these global styles.', 'simple-post-form' ); ?></p>

				<h3><?php esc_html_e( 'Submit Button Styling', 'simple-post-form' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Colors', 'simple-post-form' ); ?></th>
						<td>
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
								<div>
									<label><?php esc_html_e( 'Background Color', 'simple-post-form' ); ?></label>
									<input type="text" class="spf-color-picker" name="button_styles[backgroundColor]" value="<?php echo esc_attr( $button_styles['backgroundColor'] ?? '#0073aa' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Hover Background', 'simple-post-form' ); ?></label>
									<input type="text" class="spf-color-picker" name="button_styles[hoverBackgroundColor]" value="<?php echo esc_attr( $button_styles['hoverBackgroundColor'] ?? '#005177' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Text Color', 'simple-post-form' ); ?></label>
									<input type="text" class="spf-color-picker" name="button_styles[color]" value="<?php echo esc_attr( $button_styles['color'] ?? '#ffffff' ); ?>">
								</div>
								<div>
									<label><?php esc_html_e( 'Hover Text Color', 'simple-post-form' ); ?></label>
									<input type="text" class="spf-color-picker" name="button_styles[hoverColor]" value="<?php echo esc_attr( $button_styles['hoverColor'] ?? '#ffffff' ); ?>">
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Typography', 'simple-post-form' ); ?></th>
						<td>
							<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
								<div>
									<label><?php esc_html_e( 'Font Size', 'simple-post-form' ); ?></label>
									<input type="text" name="button_styles[fontSize]" value="<?php echo esc_attr( $button_styles['fontSize'] ?? '16px' ); ?>" placeholder="16px">
								</div>
								<div>
									<label><?php esc_html_e( 'Font Weight', 'simple-post-form' ); ?></label>
									<select name="button_styles[fontWeight]">
										<?php foreach ( $weights as $weight ) : ?>
											<option value="<?php echo esc_attr( $weight ); ?>" <?php selected( $button_styles['fontWeight'] ?? '400', $weight ); ?>><?php echo esc_html( $weight ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div>
									<label><?php esc_html_e( 'Border Radius', 'simple-post-form' ); ?></label>
									<input type="text" name="button_styles[borderRadius]" value="<?php echo esc_attr( $button_styles['borderRadius'] ?? '4px' ); ?>" placeholder="4px">
								</div>
							</div>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Security & Anti-Spam', 'simple-post-form' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure rate limiting and country blocking to prevent spam and abuse.', 'simple-post-form' ); ?></p>

				<h3><?php esc_html_e( 'Rate Limiting', 'simple-post-form' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Rate Limiting', 'simple-post-form' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="rate_limit_enabled" value="1" <?php checked( get_option( 'spf_rate_limit_enabled' ), 1 ); ?>>
								<?php esc_html_e( 'Enable automatic IP blocking for excessive submissions', 'simple-post-form' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically block IPs that exceed the submission limit within the specified time window.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rate_limit_submissions"><?php esc_html_e( 'Maximum Submissions', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="number" id="rate_limit_submissions" name="rate_limit_submissions" value="<?php echo esc_attr( get_option( 'spf_rate_limit_submissions', 5 ) ); ?>" min="1" max="100" class="small-text">
							<p class="description"><?php esc_html_e( 'Maximum number of submissions allowed per form.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rate_limit_minutes"><?php esc_html_e( 'Time Window (minutes)', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="number" id="rate_limit_minutes" name="rate_limit_minutes" value="<?php echo esc_attr( get_option( 'spf_rate_limit_minutes', 10 ) ); ?>" min="1" max="1440" class="small-text">
							<p class="description"><?php esc_html_e( 'Time window to count submissions (in minutes).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rate_limit_block_duration"><?php esc_html_e( 'Block Duration (minutes)', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="number" id="rate_limit_block_duration" name="rate_limit_block_duration" value="<?php echo esc_attr( get_option( 'spf_rate_limit_block_duration', 60 ) ); ?>" min="1" max="10080" class="small-text">
							<p class="description"><?php esc_html_e( 'How long to block the IP address when rate limit is exceeded.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Country Blocking', 'simple-post-form' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Country Blocking', 'simple-post-form' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="country_blocking_enabled" value="1" <?php checked( get_option( 'spf_country_blocking_enabled' ), 1 ); ?>>
								<?php esc_html_e( 'Block form submissions from specific countries', 'simple-post-form' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Prevent submissions from specified countries using IP geolocation.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="blocked_countries"><?php esc_html_e( 'Blocked Countries', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<textarea id="blocked_countries" name="blocked_countries" rows="3" class="large-text" placeholder="US, CN, RU"><?php echo esc_textarea( get_option( 'spf_blocked_countries', '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter country codes separated by commas (e.g., US, CN, RU). Uses ISO 3166-1 alpha-2 codes.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
				</table>

				<h3><?php esc_html_e( 'Spam Keywords Filtering', 'simple-post-form' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Keyword Filtering', 'simple-post-form' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="spam_keywords_enabled" value="1" <?php checked( get_option( 'spf_spam_keywords_enabled' ), 1 ); ?>>
								<?php esc_html_e( 'Block submissions containing spam keywords', 'simple-post-form' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Automatically block form submissions that contain specified spam keywords in any field.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="spam_keywords"><?php esc_html_e( 'Spam Keywords', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<textarea id="spam_keywords" name="spam_keywords" rows="8" class="large-text" placeholder="bitcoin&#10;cryptocurrency&#10;porn&#10;viagra&#10;casino&#10;forex"><?php echo esc_textarea( get_option( 'spf_spam_keywords', '' ) ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter one keyword or phrase per line. Matching is case-insensitive.', 'simple-post-form' ); ?></p>
							<details style="margin-top: 10px;">
								<summary style="cursor: pointer; color: #2271b1; font-weight: 500;"><?php esc_html_e( 'Common Spam Keywords (Click to view)', 'simple-post-form' ); ?></summary>
								<div style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px; font-family: monospace; font-size: 12px; line-height: 1.8;">
									<strong><?php esc_html_e( 'Cryptocurrency & Finance:', 'simple-post-form' ); ?></strong><br>
									bitcoin, cryptocurrency, crypto, ethereum, NFT, forex, binary options, investment opportunity, trading signals, crypto mining<br><br>
									
									<strong><?php esc_html_e( 'Adult Content:', 'simple-post-form' ); ?></strong><br>
									porn, xxx, adult, sex, escort, dating, hookup<br><br>
									
									<strong><?php esc_html_e( 'Pharmaceuticals:', 'simple-post-form' ); ?></strong><br>
									viagra, cialis, pharmacy, pills, prescription, weight loss, diet pills<br><br>
									
									<strong><?php esc_html_e( 'Gambling:', 'simple-post-form' ); ?></strong><br>
									casino, gambling, poker, betting, slots, jackpot<br><br>
									
									<strong><?php esc_html_e( 'Scams & Schemes:', 'simple-post-form' ); ?></strong><br>
									make money fast, get rich quick, work from home, mlm, multi level marketing, earn money online<br><br>
									
									<strong><?php esc_html_e( 'Counterfeit Goods:', 'simple-post-form' ); ?></strong><br>
									replica, fake, counterfeit, knock off, designer handbags<br><br>
									
									<strong><?php esc_html_e( 'Other Common Spam:', 'simple-post-form' ); ?></strong><br>
									seo services, link building, backlinks, website traffic, buy followers, instagram likes
								</div>
							</details>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'SMTP Email Settings', 'simple-post-form' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Configure SMTP to send form emails through your mail server instead of PHP mail().', 'simple-post-form' ); ?></p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable SMTP', 'simple-post-form' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="smtp_enabled" value="1" <?php checked( get_option( 'spf_smtp_enabled' ), 1 ); ?>>
								<?php esc_html_e( 'Use SMTP for sending emails', 'simple-post-form' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Enable this to use SMTP instead of the default PHP mail() function.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_host"><?php esc_html_e( 'SMTP Host', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="text" id="smtp_host" name="smtp_host" value="<?php echo esc_attr( get_option( 'spf_smtp_host', '' ) ); ?>" class="regular-text" placeholder="smtp.gmail.com">
							<p class="description"><?php esc_html_e( 'Your SMTP server hostname (e.g., smtp.gmail.com).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_port"><?php esc_html_e( 'SMTP Port', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="number" id="smtp_port" name="smtp_port" value="<?php echo esc_attr( get_option( 'spf_smtp_port', 587 ) ); ?>" class="small-text" min="1" max="65535">
							<p class="description"><?php esc_html_e( 'SMTP port (587 for TLS, 465 for SSL, 25 for non-encrypted).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_encryption"><?php esc_html_e( 'Encryption', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<select id="smtp_encryption" name="smtp_encryption">
								<option value="none" <?php selected( get_option( 'spf_smtp_encryption', 'tls' ), 'none' ); ?>><?php esc_html_e( 'None', 'simple-post-form' ); ?></option>
								<option value="tls" <?php selected( get_option( 'spf_smtp_encryption', 'tls' ), 'tls' ); ?>><?php esc_html_e( 'TLS', 'simple-post-form' ); ?></option>
								<option value="ssl" <?php selected( get_option( 'spf_smtp_encryption', 'tls' ), 'ssl' ); ?>><?php esc_html_e( 'SSL', 'simple-post-form' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Encryption method (TLS recommended for port 587).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'SMTP Authentication', 'simple-post-form' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="smtp_auth" value="1" <?php checked( get_option( 'spf_smtp_auth', 1 ), 1 ); ?>>
								<?php esc_html_e( 'Use SMTP authentication', 'simple-post-form' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Most SMTP servers require authentication.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_username"><?php esc_html_e( 'SMTP Username', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="text" id="smtp_username" name="smtp_username" value="<?php echo esc_attr( get_option( 'spf_smtp_username', '' ) ); ?>" class="regular-text" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Your SMTP username (usually your email address).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_password"><?php esc_html_e( 'SMTP Password', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="password" id="smtp_password" name="smtp_password" value="<?php echo esc_attr( get_option( 'spf_smtp_password', '' ) ); ?>" class="regular-text" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Your SMTP password or app-specific password.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_from_email"><?php esc_html_e( 'From Email', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo esc_attr( get_option( 'spf_smtp_from_email', '' ) ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Email address to send from (must be authorized by your SMTP server).', 'simple-post-form' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="smtp_from_name"><?php esc_html_e( 'From Name', 'simple-post-form' ); ?></label>
						</th>
						<td>
							<input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo esc_attr( get_option( 'spf_smtp_from_name', '' ) ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Name to display in the From field.', 'simple-post-form' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'simple-post-form' ), 'primary', 'spf_save_settings' ); ?>
			</form>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				if ($.fn.wpColorPicker) {
					$('.spf-color-picker').wpColorPicker();
				}
			});
		</script>
		<?php
	}

	/**
	 * AJAX delete submission.
	 */
	public function ajax_delete_submission() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;

		if ( ! $submission_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid submission ID.', 'simple-post-form' ) ) );
		}

		$result = simple_post_form()->delete_submission( $submission_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Submission deleted successfully.', 'simple-post-form' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to delete submission.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX unblock IP.
	 */
	public function ajax_unblock_ip() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';

		if ( empty( $ip_address ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address.', 'simple-post-form' ) ) );
		}

		$result = simple_post_form()->unblock_ip( $ip_address );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'IP unblocked successfully.', 'simple-post-form' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to unblock IP.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX block IP.
	 */
	public function ajax_block_ip() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$ip_address = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : 'Manually blocked';
		$duration = isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 0;

		if ( empty( $ip_address ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address.', 'simple-post-form' ) ) );
		}

		$result = simple_post_form()->block_ip( $ip_address, $reason, $duration );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'IP blocked successfully.', 'simple-post-form' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to block IP.', 'simple-post-form' ) ) );
		}
	}

	/**
	 * AJAX resend submission email.
	 */
	public function ajax_resend_submission() {
		check_ajax_referer( 'spf-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'simple-post-form' ) ) );
		}

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;

		if ( ! $submission_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid submission ID.', 'simple-post-form' ) ) );
		}

		// Get submission
		$submission = simple_post_form()->get_submission( $submission_id );
		if ( ! $submission ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'simple-post-form' ) ) );
		}

		// Get form
		$form = simple_post_form()->get_form( $submission->form_id );
		if ( ! $form ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'simple-post-form' ) ) );
		}

		// Get fields
		$fields = simple_post_form()->get_form_fields( $submission->form_id );
		$submission_data = json_decode( $submission->submission_data, true );

		// Prepare email
		$to = $form->recipient_email;
		$subject = ! empty( $form->form_subject ) ? $form->form_subject : sprintf( __( 'Form Submission: %s', 'simple-post-form' ), $form->form_name );
		
		// Build email message
		$message = '<html><body>';
		$message .= '<h2>' . esc_html( $form->form_name ) . '</h2>';
		$message .= '<p style="background: #fffacd; padding: 10px; border-left: 4px solid #ff9800;"><strong>' . __( 'RESENT EMAIL', 'simple-post-form' ) . '</strong></p>';
		
		if ( ! empty( $form->form_title ) ) {
			$message .= '<p><strong>' . esc_html( $form->form_title ) . '</strong></p>';
		}

		$message .= '<table style="border-collapse: collapse; width: 100%; max-width: 600px;">';
		
		foreach ( $submission_data as $label => $value ) {
			if ( $label !== 'blocked_reason' ) {
				$message .= '<tr>';
				$message .= '<td style="padding: 10px; border: 1px solid #ddd; background-color: #f5f5f5;"><strong>' . esc_html( $label ) . ':</strong></td>';
				$message .= '<td style="padding: 10px; border: 1px solid #ddd;">' . nl2br( esc_html( $value ) ) . '</td>';
				$message .= '</tr>';
			}
		}

		$message .= '</table>';
		$message .= '<br><hr>';
		$message .= '<p style="color: #666; font-size: 12px;">';
		$message .= sprintf(
			__( 'Originally submitted on %s - Resent on %s', 'simple-post-form' ),
			date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submission->submitted_at ) ),
			date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
		);
		$message .= '</p>';
		$message .= '</body></html>';

		// Prepare headers
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		
		if ( ! empty( $form->sender_email ) && ! empty( $form->sender_name ) ) {
			$headers[] = 'From: ' . $form->sender_name . ' <' . $form->sender_email . '>';
		} elseif ( ! empty( $form->sender_email ) ) {
			$headers[] = 'From: ' . $form->sender_email;
		}

		// Capture PHPMailer errors
		$phpmailer_error = '';
		add_action( 'wp_mail_failed', function( $error ) use ( &$phpmailer_error ) {
			$phpmailer_error = $error->get_error_message();
		});

		// Send email
		$sent = wp_mail( $to, $subject, $message, $headers );

		// Update email status
		$email_status = array(
			'sent' => $sent,
			'status' => $sent ? 'delivered' : 'failed',
			'error' => $sent ? '' : $phpmailer_error,
		);
		
		simple_post_form()->update_submission_email_status( $submission_id, $email_status );

		if ( $sent ) {
			wp_send_json_success( array( 
				'message' => __( 'Email resent successfully.', 'simple-post-form' ),
				'status' => 'delivered'
			) );
		} else {
			wp_send_json_error( array( 
				'message' => __( 'Failed to resend email.', 'simple-post-form' ) . ' ' . $phpmailer_error,
				'status' => 'failed',
				'error' => $phpmailer_error
			) );
		}
	}

	/**
	 * Blocked IPs page.
	 */
	public function blocked_ips_page() {
		$blocked_ips = simple_post_form()->get_blocked_ips();
		
		// Handle manual IP blocking
		if ( isset( $_POST['spf_block_ip'] ) && check_admin_referer( 'spf-block-ip-nonce' ) ) {
			$ip_to_block = sanitize_text_field( $_POST['ip_address'] ?? '' );
			$block_reason = sanitize_text_field( $_POST['block_reason'] ?? 'Manually blocked' );
			$block_type = sanitize_text_field( $_POST['block_type'] ?? 'permanent' );
			$block_duration = absint( $_POST['block_duration'] ?? 0 );
			
			if ( ! empty( $ip_to_block ) ) {
				$duration = $block_type === 'temporary' ? $block_duration : 0;
				simple_post_form()->block_ip( $ip_to_block, $block_reason, $duration );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'IP address blocked successfully!', 'simple-post-form' ) . '</p></div>';
				$blocked_ips = simple_post_form()->get_blocked_ips(); // Refresh list
			}
		}
		?>
		<div class="wrap spf-wrap">
			<h1><?php esc_html_e( 'Blocked IP Addresses', 'simple-post-form' ); ?></h1>
			
			<div class="card" style="max-width: 800px; margin: 20px 0;">
				<h2><?php esc_html_e( 'Block New IP Address', 'simple-post-form' ); ?></h2>
				<form method="post" action="">
					<?php wp_nonce_field( 'spf-block-ip-nonce' ); ?>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="ip_address"><?php esc_html_e( 'IP Address', 'simple-post-form' ); ?></label>
							</th>
							<td>
								<input type="text" id="ip_address" name="ip_address" class="regular-text" required placeholder="192.168.1.1">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="block_reason"><?php esc_html_e( 'Reason', 'simple-post-form' ); ?></label>
							</th>
							<td>
								<input type="text" id="block_reason" name="block_reason" class="regular-text" placeholder="<?php esc_attr_e( 'Manually blocked', 'simple-post-form' ); ?>">
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Block Type', 'simple-post-form' ); ?></th>
							<td>
								<label>
									<input type="radio" name="block_type" value="permanent" checked onchange="document.getElementById('block_duration').disabled = true;">
									<?php esc_html_e( 'Permanent', 'simple-post-form' ); ?>
								</label><br>
								<label>
									<input type="radio" name="block_type" value="temporary" onchange="document.getElementById('block_duration').disabled = false;">
									<?php esc_html_e( 'Temporary', 'simple-post-form' ); ?>
								</label>
								<input type="number" id="block_duration" name="block_duration" value="60" min="1" style="width: 80px; margin-left: 10px;" disabled>
								<span><?php esc_html_e( 'minutes', 'simple-post-form' ); ?></span>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Block IP Address', 'simple-post-form' ), 'primary', 'spf_block_ip' ); ?>
				</form>
			</div>

			<h2><?php esc_html_e( 'Currently Blocked IPs', 'simple-post-form' ); ?></h2>
			
			<?php if ( empty( $blocked_ips ) ) : ?>
				<div class="spf-empty-state">
					<p><?php esc_html_e( 'No blocked IP addresses.', 'simple-post-form' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'IP Address', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Reason', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Type', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Blocked At', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Expires', 'simple-post-form' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'simple-post-form' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blocked_ips as $blocked_ip ) : ?>
							<tr>
								<td><code><?php echo esc_html( $blocked_ip->ip_address ); ?></code></td>
								<td><?php echo esc_html( $blocked_ip->block_reason ); ?></td>
								<td>
									<?php if ( $blocked_ip->is_permanent ) : ?>
										<span class="dashicons dashicons-lock" style="color: #d63638;"></span>
										<?php esc_html_e( 'Permanent', 'simple-post-form' ); ?>
									<?php else : ?>
										<span class="dashicons dashicons-clock" style="color: #dba617;"></span>
										<?php esc_html_e( 'Temporary', 'simple-post-form' ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $blocked_ip->blocked_at ) ) ); ?></td>
								<td>
									<?php
									if ( $blocked_ip->is_permanent ) {
										esc_html_e( 'Never', 'simple-post-form' );
									} elseif ( $blocked_ip->blocked_until ) {
										$expiry_time = strtotime( $blocked_ip->blocked_until );
										if ( $expiry_time < time() ) {
											echo '<span style="color: #999;">' . esc_html__( 'Expired', 'simple-post-form' ) . '</span>';
										} else {
											echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expiry_time ) );
										}
									}
									?>
								</td>
								<td>
									<button class="button button-small spf-unblock-ip" data-ip="<?php echo esc_attr( $blocked_ip->ip_address ); ?>">
										<?php esc_html_e( 'Unblock', 'simple-post-form' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
