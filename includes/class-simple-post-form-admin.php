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
		add_action( 'wp_ajax_spf_get_form', array( $this, 'ajax_get_form' ) );
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

						<h3><?php esc_html_e( 'Button Styling', 'simple-post-form' ); ?></h3>

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
}
