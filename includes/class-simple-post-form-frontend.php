<?php
/**
 * Frontend Class
 *
 * @package Code045\Simple_Post_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend Class.
 */
class Simple_Post_Form_Frontend {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'simple_form', array( $this, 'render_form_shortcode' ) );
		add_shortcode( 'simple_form_modal', array( $this, 'render_modal_button_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public function frontend_scripts() {
		wp_enqueue_style(
			'spf-frontend-css',
			SPF_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			SPF_VERSION
		);

		wp_enqueue_script(
			'spf-frontend-js',
			SPF_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			SPF_VERSION,
			true
		);

		wp_localize_script(
			'spf-frontend-js',
			'spfFrontend',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'spf-frontend-nonce' ),
				'strings' => array(
					'submitting' => __( 'Submitting...', 'simple-post-form' ),
					'success' => __( 'Form submitted successfully!', 'simple-post-form' ),
					'error' => __( 'An error occurred. Please try again.', 'simple-post-form' ),
					'validationError' => __( 'Please fill in all required fields.', 'simple-post-form' ),
				),
			)
		);
	}

	/**
	 * Render form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_form_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'simple_form'
		);

		$form_id = intval( $atts['id'] );

		if ( ! $form_id ) {
			return '<p>' . esc_html__( 'Please provide a valid form ID.', 'simple-post-form' ) . '</p>';
		}

		$form = simple_post_form()->get_form( $form_id );

		if ( ! $form ) {
			return '<p>' . esc_html__( 'Form not found.', 'simple-post-form' ) . '</p>';
		}

		$fields = simple_post_form()->get_form_fields( $form_id );

		if ( empty( $fields ) ) {
			return '<p>' . esc_html__( 'This form has no fields.', 'simple-post-form' ) . '</p>';
		}

		ob_start();
		$this->render_form( $form, $fields );
		return ob_get_clean();
	}

	/**
	 * Render modal button shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_modal_button_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'simple_form_modal'
		);

		$form_id = intval( $atts['id'] );

		if ( ! $form_id ) {
			return '<p>' . esc_html__( 'Please provide a valid form ID.', 'simple-post-form' ) . '</p>';
		}

		$form = simple_post_form()->get_form( $form_id );

		if ( ! $form ) {
			return '<p>' . esc_html__( 'Form not found.', 'simple-post-form' ) . '</p>';
		}

		$fields = simple_post_form()->get_form_fields( $form_id );

		if ( empty( $fields ) ) {
			return '<p>' . esc_html__( 'This form has no fields.', 'simple-post-form' ) . '</p>';
		}

		// Get modal button settings
		$button_text = ! empty( $form->modal_button_text ) ? $form->modal_button_text : __( 'Open Form', 'simple-post-form' );
		
		// Check if form should use global styles for modal button
		$use_global_styles = ! empty( $form->use_global_styles ) && $form->use_global_styles == 1;
		
		if ( $use_global_styles ) {
			// Load global button styles from options
			$global_styles = get_option( 'spf_global_button_styles', array() );
			$modal_button_styles = is_array( $global_styles ) ? $global_styles : array();
		} else {
			// Use form-specific modal button styles
			$modal_button_styles = $form->modal_button_styles ? json_decode( $form->modal_button_styles, true ) : array();
		}
		
		$modal_styles = $form->modal_styles ? json_decode( $form->modal_styles, true ) : array();

		// Generate unique ID for this modal
		$modal_id = 'spf-modal-' . $form->id . '-' . wp_rand( 1000, 9999 );

		// Build button inline styles
		$button_inline_style = '';
		if ( ! empty( $modal_button_styles['backgroundColor'] ) ) {
			$button_inline_style .= 'background-color: ' . esc_attr( $modal_button_styles['backgroundColor'] ) . '; ';
		}
		if ( ! empty( $modal_button_styles['color'] ) ) {
			$button_inline_style .= 'color: ' . esc_attr( $modal_button_styles['color'] ) . '; ';
		}
		if ( ! empty( $modal_button_styles['borderRadius'] ) ) {
			$button_inline_style .= 'border-radius: ' . esc_attr( $modal_button_styles['borderRadius'] ) . '; ';
		}
		if ( ! empty( $modal_button_styles['fontSize'] ) ) {
			$button_inline_style .= 'font-size: ' . esc_attr( $modal_button_styles['fontSize'] ) . '; ';
		}
		if ( ! empty( $modal_button_styles['fontWeight'] ) ) {
			$button_inline_style .= 'font-weight: ' . esc_attr( $modal_button_styles['fontWeight'] ) . '; ';
		}
		if ( ! empty( $modal_button_styles['padding'] ) ) {
			$button_inline_style .= 'padding: ' . esc_attr( $modal_button_styles['padding'] ) . '; ';
		}

		// Build modal inline styles
		$modal_inline_style = '';
		if ( ! empty( $modal_styles['width'] ) ) {
			$modal_inline_style .= 'width: ' . esc_attr( $modal_styles['width'] ) . '; ';
		}
		if ( ! empty( $modal_styles['maxWidth'] ) ) {
			$modal_inline_style .= 'max-width: ' . esc_attr( $modal_styles['maxWidth'] ) . '; ';
		}
		if ( ! empty( $modal_styles['backgroundColor'] ) ) {
			$modal_inline_style .= 'background-color: ' . esc_attr( $modal_styles['backgroundColor'] ) . '; ';
		}
		if ( ! empty( $modal_styles['borderRadius'] ) ) {
			$modal_inline_style .= 'border-radius: ' . esc_attr( $modal_styles['borderRadius'] ) . '; ';
		}
		if ( ! empty( $modal_styles['padding'] ) ) {
			$modal_inline_style .= 'padding: ' . esc_attr( $modal_styles['padding'] ) . '; ';
		}

		// Build overlay styles
		$overlay_style = '';
		if ( ! empty( $modal_styles['overlayColor'] ) ) {
			$overlay_style .= 'background-color: ' . esc_attr( $modal_styles['overlayColor'] ) . '; ';
		}

		ob_start();
		?>
		<button 
			class="spf-modal-trigger" 
			data-modal="<?php echo esc_attr( $modal_id ); ?>"
			data-hover-bg="<?php echo esc_attr( $modal_button_styles['hoverBackgroundColor'] ?? '' ); ?>"
			data-hover-color="<?php echo esc_attr( $modal_button_styles['hoverColor'] ?? '' ); ?>"
			style="<?php echo esc_attr( $button_inline_style ); ?>">
			<?php echo esc_html( $button_text ); ?>
		</button>

		<div id="<?php echo esc_attr( $modal_id ); ?>" class="spf-modal" style="display: none;">
			<div class="spf-modal-overlay" style="<?php echo esc_attr( $overlay_style ); ?>"></div>
			<div class="spf-modal-content" style="<?php echo esc_attr( $modal_inline_style ); ?>">
				<button class="spf-modal-close" aria-label="<?php esc_attr_e( 'Close', 'simple-post-form' ); ?>">&times;</button>
				<?php $this->render_form( $form, $fields ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render form HTML.
	 *
	 * @param object $form Form object.
	 * @param array  $fields Form fields.
	 */
	private function render_form( $form, $fields ) {
		// Check if form should use global styles
		$use_global_styles = ! empty( $form->use_global_styles ) && $form->use_global_styles == 1;
		
		if ( $use_global_styles ) {
			// Load global button styles from options
			$global_styles = get_option( 'spf_global_button_styles', array() );
			$button_styles = is_array( $global_styles ) ? $global_styles : array();
		} else {
			// Use form-specific button styles
			$button_styles = $form->button_styles ? json_decode( $form->button_styles, true ) : array();
		}
		
		$form_id = 'spf-form-' . $form->id;
		?>
		<div class="spf-form-container" id="<?php echo esc_attr( $form_id ); ?>">
			<?php if ( ! empty( $form->form_title ) ) : ?>
				<h2 class="spf-form-title"><?php echo esc_html( $form->form_title ); ?></h2>
			<?php endif; ?>

			<form class="spf-form" data-form-id="<?php echo esc_attr( $form->id ); ?>">
				<?php wp_nonce_field( 'spf_submit_form_' . $form->id, 'spf_form_nonce' ); ?>
				
				<div class="spf-form-fields-wrapper">
					<?php
					$current_row = array();
					$row_width = 0;

					foreach ( $fields as $field ) :
						$field_styles = $field->field_styles ? json_decode( $field->field_styles, true ) : array();
						$field_width = floatval( $field->field_width );
						
						// Check if we need to start a new row
						if ( $row_width + $field_width > 100 ) {
							if ( ! empty( $current_row ) ) {
								$this->render_field_row( $current_row, $form );
								$current_row = array();
								$row_width = 0;
							}
						}
						
						$current_row[] = array(
							'field' => $field,
							'styles' => $field_styles,
						);
						$row_width += $field_width;
						
						// If row is full, render it
						if ( $row_width >= 100 ) {
							$this->render_field_row( $current_row, $form );
							$current_row = array();
							$row_width = 0;
						}
					endforeach;

					// Render remaining fields
					if ( ! empty( $current_row ) ) {
						$this->render_field_row( $current_row, $form );
					}
					?>
				</div>

				<div class="spf-form-messages"></div>

				<div class="spf-form-submit">
					<?php
					$button_style = $this->generate_button_style( $button_styles );
					?>
					<button type="submit" class="spf-submit-button" style="<?php echo esc_attr( $button_style ); ?>">
						<?php echo esc_html( $form->button_text ); ?>
					</button>
				</div>
			</form>
		</div>

		<?php if ( ! empty( $button_styles['hoverBackgroundColor'] ) || ! empty( $button_styles['hoverColor'] ) ) : ?>
			<style>
				#<?php echo esc_attr( $form_id ); ?> .spf-submit-button:hover {
					<?php if ( ! empty( $button_styles['hoverBackgroundColor'] ) ) : ?>
						background-color: <?php echo esc_attr( $button_styles['hoverBackgroundColor'] ); ?> !important;
					<?php endif; ?>
					<?php if ( ! empty( $button_styles['hoverColor'] ) ) : ?>
						color: <?php echo esc_attr( $button_styles['hoverColor'] ); ?> !important;
					<?php endif; ?>
				}
			</style>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a row of fields.
	 *
	 * @param array $fields_in_row Fields to render in this row.
	 * @param object $form Form object.
	 */
	private function render_field_row( $fields_in_row, $form = null ) {
		?>
		<div class="spf-form-row">
			<?php foreach ( $fields_in_row as $field_data ) :
				$field = $field_data['field'];
				$styles = $field_data['styles'];
				?>
				<div class="spf-form-field" style="width: <?php echo esc_attr( $field->field_width ); ?>%;">
					<?php $this->render_field( $field, $styles, $form ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render a single field.
	 *
	 * @param object $field Field object.
	 * @param array  $styles Field styles.
	 * @param object $form Form object.
	 */
	private function render_field( $field, $styles, $form = null ) {
		$field_style = $this->generate_field_style( $styles );
		$field_name = esc_attr( 'spf_field_' . $field->id );
		$required = $field->field_required ? 'required' : '';
		$required_mark = $field->field_required ? '<span class="spf-required">*</span>' : '';
		
		// Check if labels should be hidden
		$hide_labels = $form && ! empty( $form->hide_labels ) && $form->hide_labels == 1;
		$debug_mode = $form && ! empty( $form->debug_mode ) && $form->debug_mode == 1;
		
		// Don't show label for honeypot fields or when hide_labels is enabled (unless it's a honeypot in debug mode)
		$show_label = true;
		if ( $field->field_type === 'honeypot' && ! ( $debug_mode && current_user_can( 'manage_options' ) ) ) {
			$show_label = false;
		} elseif ( $hide_labels && $field->field_type !== 'honeypot' ) {
			$show_label = false;
		}
		
		// If labels are hidden and placeholder is empty, use the label as placeholder
		$placeholder = $field->field_placeholder;
		if ( $hide_labels && empty( $placeholder ) && $field->field_type !== 'honeypot' ) {
			$placeholder = $field->field_label . ( $field->field_required ? ' *' : '' );
		}
		?>
		<?php if ( $show_label ) : ?>
		<label class="spf-field-label">
			<?php echo esc_html( $field->field_label ); ?>
			<?php echo $required_mark; ?>
			<?php if ( $field->field_type === 'honeypot' && $debug_mode && current_user_can( 'manage_options' ) ) : ?>
				<span style="color: #d63638; font-weight: bold;"> [DEBUG: Honeypot Field]</span>
			<?php endif; ?>
		</label>
		<?php endif; ?>

		<?php
		switch ( $field->field_type ) {
			case 'textarea':
				?>
				<textarea 
					name="<?php echo $field_name; ?>" 
					id="<?php echo $field_name; ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					style="<?php echo esc_attr( $field_style ); ?>"
					<?php echo $required; ?>
					rows="5"
				></textarea>
				<?php
				break;

			case 'email':
				?>
				<input 
					type="email" 
					name="<?php echo $field_name; ?>" 
					id="<?php echo $field_name; ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					style="<?php echo esc_attr( $field_style ); ?>"
					<?php echo $required; ?>
				>
				<?php
				break;

			case 'phone':
				?>
				<input 
					type="tel" 
					name="<?php echo $field_name; ?>" 
					id="<?php echo $field_name; ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					style="<?php echo esc_attr( $field_style ); ?>"
					<?php echo $required; ?>
				>
				<?php
				break;

			case 'number':
				?>
				<input 
					type="number" 
					name="<?php echo $field_name; ?>" 
					id="<?php echo $field_name; ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					style="<?php echo esc_attr( $field_style ); ?>"
					<?php echo $required; ?>
				>
				<?php
				break;

			case 'honeypot':
				// Honeypot field - hidden field with name 'email' to catch bots
				// Show field only in debug mode for logged-in admins
				$is_debug_visible = $debug_mode && current_user_can( 'manage_options' );
				$honeypot_style = $is_debug_visible 
					? esc_attr( $field_style ) 
					: 'position: absolute; left: -9999px; width: 1px; height: 1px;';
				?>
				<input 
					type="text" 
					name="email" 
					id="<?php echo $field_name; ?>_honeypot"
					value=""
					placeholder="<?php echo $is_debug_visible ? esc_attr( $field->field_placeholder ?: 'Leave this field empty' ) : ''; ?>"
					tabindex="<?php echo $is_debug_visible ? '0' : '-1'; ?>"
					autocomplete="off"
					style="<?php echo $honeypot_style; ?>"
					aria-hidden="<?php echo $is_debug_visible ? 'false' : 'true'; ?>"
				>
				<?php if ( $is_debug_visible ) : ?>
					<p class="description" style="color: #d63638; font-size: 12px; margin-top: 5px;">
						<strong><?php esc_html_e( 'DEBUG MODE:', 'simple-post-form' ); ?></strong> 
						<?php esc_html_e( 'This honeypot field is only visible to you as an admin. Fill it to test spam blocking. Regular users won\'t see this field.', 'simple-post-form' ); ?>
					</p>
				<?php endif; ?>
				<?php
				break;

			case 'name':
			case 'text':
			default:
				?>
				<input 
					type="text" 
					name="<?php echo $field_name; ?>" 
					id="<?php echo $field_name; ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					style="<?php echo esc_attr( $field_style ); ?>"
					<?php echo $required; ?>
				>
				<?php
				break;
		}
	}

	/**
	 * Generate field style string.
	 *
	 * @param array $styles Field styles.
	 * @return string
	 */
	private function generate_field_style( $styles ) {
		$style_parts = array();

		if ( ! empty( $styles['borderColor'] ) ) {
			$style_parts[] = 'border-color: ' . $styles['borderColor'];
		}

		if ( ! empty( $styles['borderRadius'] ) ) {
			$style_parts[] = 'border-radius: ' . $styles['borderRadius'];
		}

		if ( ! empty( $styles['backgroundColor'] ) ) {
			$style_parts[] = 'background-color: ' . $styles['backgroundColor'];
		}

		if ( ! empty( $styles['color'] ) ) {
			$style_parts[] = 'color: ' . $styles['color'];
		}

		if ( ! empty( $styles['fontSize'] ) ) {
			$style_parts[] = 'font-size: ' . $styles['fontSize'];
		}

		if ( ! empty( $styles['fontWeight'] ) ) {
			$style_parts[] = 'font-weight: ' . $styles['fontWeight'];
		}

		if ( ! empty( $styles['padding'] ) ) {
			$style_parts[] = 'padding: ' . $styles['padding'];
		}

		return implode( '; ', $style_parts );
	}

	/**
	 * Generate button style string.
	 *
	 * @param array $styles Button styles.
	 * @return string
	 */
	private function generate_button_style( $styles ) {
		$style_parts = array();

		if ( ! empty( $styles['backgroundColor'] ) ) {
			$style_parts[] = 'background-color: ' . $styles['backgroundColor'];
		}

		if ( ! empty( $styles['color'] ) ) {
			$style_parts[] = 'color: ' . $styles['color'];
		}

		if ( ! empty( $styles['borderRadius'] ) ) {
			$style_parts[] = 'border-radius: ' . $styles['borderRadius'];
		}

		if ( ! empty( $styles['fontSize'] ) ) {
			$style_parts[] = 'font-size: ' . $styles['fontSize'];
		}

		if ( ! empty( $styles['fontWeight'] ) ) {
			$style_parts[] = 'font-weight: ' . $styles['fontWeight'];
		}

		if ( ! empty( $styles['textTransform'] ) ) {
			$style_parts[] = 'text-transform: ' . $styles['textTransform'];
		}

		return implode( '; ', $style_parts );
	}
}
