jQuery(document).ready(function($) {
    'use strict';

    let fieldCounter = 0;
    let formFields = [];

    // Tab switching
    $('.spf-tab-wrapper .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const tabId = $(this).data('tab');
        
        // Update tab navigation
        $('.spf-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update tab content
        $('.spf-tab-content').removeClass('spf-tab-active').hide();
        $('#spf-tab-' + tabId).addClass('spf-tab-active').show();
        
        // Reinitialize color pickers in settings tab if needed
        if (tabId === 'settings' && $.fn.wpColorPicker) {
            $('#spf-tab-settings .spf-color-picker').each(function() {
                if (!$(this).hasClass('wp-color-picker')) {
                    const $input = $(this);
                    const inputId = $input.attr('id');
                    
                    if (inputId && inputId.startsWith('spf-btn-')) {
                        $input.wpColorPicker({
                            change: function(event, ui) {
                                setTimeout(updateButtonPreview, 10);
                            },
                            clear: function() {
                                setTimeout(updateButtonPreview, 10);
                            }
                        });
                    } else {
                        $input.wpColorPicker();
                    }
                }
            });
        }
    });

    // Initialize color pickers will be done after loading or on tab switch

    // Load existing fields if editing
    const existingFieldsData = $('#spf-existing-fields').text();
    if (existingFieldsData) {
        try {
            const existingFields = JSON.parse(existingFieldsData);
            if (existingFields && existingFields.length > 0) {
                $('.spf-empty-message').remove();
                existingFields.forEach(function(field) {
                    const fieldStyles = field.field_styles ? JSON.parse(field.field_styles) : {};
                    addFieldToCanvas({
                        id: fieldCounter++,
                        type: field.field_type,
                        label: field.field_label,
                        name: field.field_name,
                        placeholder: field.field_placeholder || '',
                        required: field.field_required == 1,
                        width: field.field_width || '100',
                        styles: fieldStyles
                    });
                });
            }
        } catch (e) {
            console.error('Error parsing existing fields:', e);
        }
    }

    // Initialize color pickers on page load
    if ($.fn.wpColorPicker) {
        $('#spf-tab-settings .spf-color-picker').each(function() {
            const $input = $(this);
            const inputId = $input.attr('id');
            
            if (inputId && inputId.startsWith('spf-btn-')) {
                $input.wpColorPicker({
                    change: function(event, ui) {
                        setTimeout(updateButtonPreview, 10);
                    },
                    clear: function() {
                        setTimeout(updateButtonPreview, 10);
                    }
                });
            } else {
                $input.wpColorPicker();
            }
        });
    }

    // Initialize button preview after a short delay to ensure fields are loaded
    setTimeout(function() {
        updateButtonPreview();
    }, 100);

    // Draggable field types
    $('.spf-field-type').on('dragstart', function(e) {
        const fieldType = $(this).data('type');
        e.originalEvent.dataTransfer.setData('fieldType', fieldType);
        e.originalEvent.dataTransfer.effectAllowed = 'copy';
    });

    // Drop zone for form fields
    $('#spf-form-fields').on('dragover', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'copy';
        $(this).addClass('spf-drag-over');
    });

    $('#spf-form-fields').on('dragleave', function(e) {
        $(this).removeClass('spf-drag-over');
    });

    $('#spf-form-fields').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('spf-drag-over');
        
        const fieldType = e.originalEvent.dataTransfer.getData('fieldType');
        if (fieldType) {
            $('.spf-empty-message').remove();
            addFieldToCanvas({
                id: fieldCounter++,
                type: fieldType,
                label: getFieldLabel(fieldType),
                name: generateFieldName(fieldType),
                placeholder: '',
                required: false,
                width: '100',
                styles: {}
            });
        }
    });

    // Make fields sortable
    $('#spf-form-fields').sortable({
        handle: '.spf-field-handle',
        placeholder: 'spf-field-placeholder',
        tolerance: 'pointer'
    });

    // Add field to canvas
    function addFieldToCanvas(fieldData) {
        const width = fieldData.width || '100';
        const fieldHtml = `
            <div class="spf-field-item" data-field-id="${fieldData.id}" style="width: ${width}%; display: inline-block; vertical-align: top; padding-right: 10px; box-sizing: border-box; margin-bottom: 15px;">
                <div class="spf-field-header">
                    <span class="spf-field-handle">
                        <span class="dashicons dashicons-move"></span>
                    </span>
                    <span class="spf-field-type-label">${getFieldTypeLabel(fieldData.type)}</span>
                    <div class="spf-field-actions">
                        <button type="button" class="spf-field-edit" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="spf-field-delete" title="Delete">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
                <div class="spf-field-preview">
                    <label>${fieldData.label}${fieldData.required ? ' <span class="required">*</span>' : ''}</label>
                    ${renderFieldInput(fieldData)}
                </div>
            </div>
        `;

        $('#spf-form-fields').append(fieldHtml);
        formFields.push(fieldData);
    }

    // Render field input preview
    function renderFieldInput(fieldData) {
        const styles = generateInlineStyles(fieldData.styles);
        const placeholder = fieldData.placeholder ? `placeholder="${fieldData.placeholder}"` : '';
        
        switch (fieldData.type) {
            case 'textarea':
                return `<textarea disabled ${placeholder} style="${styles}"></textarea>`;
            case 'email':
                return `<input type="email" disabled ${placeholder} style="${styles}">`;
            case 'phone':
                return `<input type="tel" disabled ${placeholder} style="${styles}">`;
            case 'number':
                return `<input type="number" disabled ${placeholder} style="${styles}">`;
            default:
                return `<input type="text" disabled ${placeholder} style="${styles}">`;
        }
    }

    // Generate inline styles
    function generateInlineStyles(styles) {
        let styleStr = '';
        if (styles.borderColor) styleStr += `border-color: ${styles.borderColor}; `;
        if (styles.borderRadius) styleStr += `border-radius: ${styles.borderRadius}; `;
        if (styles.backgroundColor) styleStr += `background-color: ${styles.backgroundColor}; `;
        if (styles.color) styleStr += `color: ${styles.color}; `;
        if (styles.fontSize) styleStr += `font-size: ${styles.fontSize}; `;
        if (styles.fontWeight) styleStr += `font-weight: ${styles.fontWeight}; `;
        if (styles.padding) styleStr += `padding: ${styles.padding}; `;
        return styleStr;
    }

    // Edit field
    $(document).on('click', '.spf-field-edit', function() {
        const fieldItem = $(this).closest('.spf-field-item');
        const fieldId = fieldItem.data('field-id');
        const fieldData = formFields.find(f => f.id == fieldId);
        
        if (fieldData) {
            showFieldEditor(fieldData, fieldItem);
        }
    });

    // Delete field
    $(document).on('click', '.spf-field-delete', function() {
        if (confirm(spfAdmin.strings.confirmDeleteField)) {
            const fieldItem = $(this).closest('.spf-field-item');
            const fieldId = fieldItem.data('field-id');
            
            formFields = formFields.filter(f => f.id != fieldId);
            fieldItem.remove();
            
            if (formFields.length === 0) {
                $('#spf-form-fields').html('<div class="spf-empty-message">Drag fields here to build your form</div>');
            }
        }
    });

    // Show field editor
    function showFieldEditor(fieldData, fieldItem) {
        const editorHtml = `
            <div class="spf-field-editor-overlay">
                <div class="spf-field-editor">
                    <div class="spf-editor-header">
                        <h3>Edit Field: ${getFieldTypeLabel(fieldData.type)}</h3>
                        <button type="button" class="spf-editor-close">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                    <div class="spf-editor-body">
                        <div class="spf-form-group">
                            <label>Field Label *</label>
                            <input type="text" class="spf-editor-label" value="${fieldData.label}">
                        </div>
                        <div class="spf-form-group">
                            <label>Field Name *</label>
                            <input type="text" class="spf-editor-name" value="${fieldData.name}">
                        </div>
                        <div class="spf-form-group">
                            <label>Placeholder Text</label>
                            <input type="text" class="spf-editor-placeholder" value="${fieldData.placeholder}">
                        </div>
                        <div class="spf-form-group">
                            <label>
                                <input type="checkbox" class="spf-editor-required" ${fieldData.required ? 'checked' : ''}>
                                Required Field
                            </label>
                        </div>
                        <div class="spf-form-group">
                            <label>Field Width</label>
                            <select class="spf-editor-width">
                                <option value="100" ${fieldData.width == '100' ? 'selected' : ''}>Full Width (100%)</option>
                                <option value="50" ${fieldData.width == '50' ? 'selected' : ''}>Half Width (50%)</option>
                                <option value="33.33" ${fieldData.width == '33.33' ? 'selected' : ''}>Third Width (33.3%)</option>
                            </select>
                        </div>

                        <h4>Field Styling</h4>
                        
                        <div class="spf-form-row">
                            <div class="spf-form-group spf-col-6">
                                <label>Border Color</label>
                                <input type="text" class="spf-editor-style-color spf-editor-border-color" value="${fieldData.styles.borderColor || '#ddd'}">
                            </div>
                            <div class="spf-form-group spf-col-6">
                                <label>Border Radius</label>
                                <input type="text" class="spf-editor-border-radius" value="${fieldData.styles.borderRadius || '4px'}" placeholder="4px">
                            </div>
                        </div>

                        <div class="spf-form-row">
                            <div class="spf-form-group spf-col-6">
                                <label>Background Color</label>
                                <input type="text" class="spf-editor-style-color spf-editor-bg-color" value="${fieldData.styles.backgroundColor || '#ffffff'}">
                            </div>
                            <div class="spf-form-group spf-col-6">
                                <label>Text Color</label>
                                <input type="text" class="spf-editor-style-color spf-editor-text-color" value="${fieldData.styles.color || '#333333'}">
                            </div>
                        </div>

                        <div class="spf-form-row">
                            <div class="spf-form-group spf-col-6">
                                <label>Font Size</label>
                                <input type="text" class="spf-editor-font-size" value="${fieldData.styles.fontSize || '14px'}" placeholder="14px">
                            </div>
                            <div class="spf-form-group spf-col-6">
                                <label>Font Weight</label>
                                <select class="spf-editor-font-weight">
                                    <option value="300" ${fieldData.styles.fontWeight == '300' ? 'selected' : ''}>300</option>
                                    <option value="400" ${fieldData.styles.fontWeight == '400' ? 'selected' : ''}>400</option>
                                    <option value="500" ${fieldData.styles.fontWeight == '500' ? 'selected' : ''}>500</option>
                                    <option value="600" ${fieldData.styles.fontWeight == '600' ? 'selected' : ''}>600</option>
                                    <option value="700" ${fieldData.styles.fontWeight == '700' ? 'selected' : ''}>700</option>
                                </select>
                            </div>
                        </div>

                        <div class="spf-form-group">
                            <label>Padding</label>
                            <input type="text" class="spf-editor-padding" value="${fieldData.styles.padding || '10px'}" placeholder="10px">
                        </div>
                    </div>
                    <div class="spf-editor-footer">
                        <button type="button" class="button button-primary spf-editor-save">Save Changes</button>
                        <button type="button" class="button spf-editor-close">Cancel</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(editorHtml);

        // Initialize color pickers in editor
        setTimeout(function() {
            if ($.fn.wpColorPicker) {
                $('.spf-editor-style-color').wpColorPicker();
            }
        }, 100);

        // Save field changes
        $('.spf-editor-save').on('click', function() {
            fieldData.label = $('.spf-editor-label').val();
            fieldData.name = $('.spf-editor-name').val();
            fieldData.placeholder = $('.spf-editor-placeholder').val();
            fieldData.required = $('.spf-editor-required').is(':checked');
            fieldData.width = $('.spf-editor-width').val();
            fieldData.styles = {
                borderColor: $('.spf-editor-border-color').val(),
                borderRadius: $('.spf-editor-border-radius').val(),
                backgroundColor: $('.spf-editor-bg-color').val(),
                color: $('.spf-editor-text-color').val(),
                fontSize: $('.spf-editor-font-size').val(),
                fontWeight: $('.spf-editor-font-weight').val(),
                padding: $('.spf-editor-padding').val()
            };

            // Update field preview
            fieldItem.find('.spf-field-preview label').html(
                fieldData.label + (fieldData.required ? ' <span class="required">*</span>' : '')
            );
            fieldItem.find('.spf-field-preview input, .spf-field-preview textarea').attr('placeholder', fieldData.placeholder);
            fieldItem.find('.spf-field-preview input, .spf-field-preview textarea').attr('style', generateInlineStyles(fieldData.styles));

            // Update field width
            fieldItem.css('width', fieldData.width + '%');

            // Update live preview
            updateFieldPreview(fieldData.id);

            $('.spf-field-editor-overlay').remove();
        });

        // Close editor
        $('.spf-editor-close').on('click', function() {
            $('.spf-field-editor-overlay').remove();
        });
    }

    // Save form
    $('#spf-save-form').on('click', function() {
        const $button = $(this);
        const formId = $('#spf-form-id').val();
        const formName = $('#spf-form-name').val();

        if (!formName) {
            alert('Please enter a form name.');
            return;
        }

        // Collect form data
        const formData = {
            form_id: formId,
            form_name: formName,
            form_title: $('#spf-form-title').val(),
            form_subject: $('#spf-form-subject').val(),
            recipient_email: $('#spf-recipient-email').val(),
            sender_name: $('#spf-sender-name').val(),
            sender_email: $('#spf-sender-email').val(),
            button_text: $('#spf-button-text').val(),
            use_global_styles: $('#spf-use-global-styles').is(':checked') ? 1 : 0,
            use_reply_to: $('#spf-use-reply-to').is(':checked') ? 1 : 0,
            success_message: $('#spf-success-message').val(),
            error_message: $('#spf-error-message').val(),
            button_styles: {
                backgroundColor: $('#spf-btn-bg-color').val(),
                hoverBackgroundColor: $('#spf-btn-hover-bg').val(),
                color: $('#spf-btn-color').val(),
                hoverColor: $('#spf-btn-hover-color').val(),
                borderRadius: $('#spf-btn-border-radius').val(),
                fontSize: $('#spf-btn-font-size').val(),
                fontWeight: $('#spf-btn-font-weight').val(),
                textTransform: $('#spf-btn-text-transform').val()
            },
            modal_button_text: $('#spf-modal-button-text').val(),
            modal_button_styles: {
                backgroundColor: $('#spf-modal-btn-bg-color').val(),
                hoverBackgroundColor: $('#spf-modal-btn-hover-bg').val(),
                color: $('#spf-modal-btn-color').val(),
                hoverColor: $('#spf-modal-btn-hover-color').val(),
                borderRadius: $('#spf-modal-btn-border-radius').val(),
                fontSize: $('#spf-modal-btn-font-size').val(),
                fontWeight: $('#spf-modal-btn-font-weight').val(),
                padding: $('#spf-modal-btn-padding').val()
            },
            modal_styles: {
                width: $('#spf-modal-width').val(),
                maxWidth: $('#spf-modal-max-width').val(),
                backgroundColor: $('#spf-modal-bg-color').val(),
                overlayColor: $('#spf-modal-overlay-color').val(),
                borderRadius: $('#spf-modal-border-radius').val(),
                padding: $('#spf-modal-padding').val()
            }
        };

        // Update field order based on DOM
        const orderedFields = [];
        $('#spf-form-fields .spf-field-item').each(function() {
            const fieldId = $(this).data('field-id');
            const field = formFields.find(f => f.id == fieldId);
            if (field) {
                orderedFields.push(field);
            }
        });

        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_save_form',
                nonce: spfAdmin.nonce,
                form_data: formData,
                fields_data: JSON.stringify(orderedFields)
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.form_id && !formId) {
                        window.location.href = 'admin.php?page=simple-post-form-new&form_id=' + response.data.form_id;
                    } else {
                        location.reload();
                    }
                } else {
                    alert(response.data.message || spfAdmin.strings.error);
                }
            },
            error: function() {
                alert(spfAdmin.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false).text('Save Form');
            }
        });
    });

    // Delete form
    $(document).on('click', '.spf-delete-form', function() {
        if (!confirm(spfAdmin.strings.confirmDelete)) {
            return;
        }

        const $button = $(this);
        const formId = $button.data('form-id');

        $button.prop('disabled', true);

        $.ajax({
            url: spfAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_delete_form',
                nonce: spfAdmin.nonce,
                form_id: formId
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || spfAdmin.strings.error);
                }
            },
            error: function() {
                alert(spfAdmin.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Helper functions
    function getFieldLabel(type) {
        const labels = {
            text: 'Text Field',
            textarea: 'Textarea',
            email: 'Email Address',
            phone: 'Phone Number',
            name: 'Name',
            number: 'Number'
        };
        return labels[type] || 'Field';
    }

    function getFieldTypeLabel(type) {
        const labels = {
            text: 'Text',
            textarea: 'Textarea',
            email: 'Email',
            phone: 'Phone',
            name: 'Name',
            number: 'Number',
            honeypot: 'Honeypot (Anti-Spam)'
        };
        return labels[type] || type;
    }

    function generateFieldName(type) {
        return type + '_' + Date.now();
    }

    // Live preview for button styling
    function updateButtonPreview() {
        const $previewBtn = $('#spf-preview-submit-button');
        
        // Add pulse animation
        $previewBtn.addClass('spf-preview-updating');
        setTimeout(function() {
            $previewBtn.removeClass('spf-preview-updating');
        }, 600);
        
        // Update button text
        const buttonText = $('#spf-button-text').val() || 'Submit';
        $previewBtn.text(buttonText);
        
        // Update button styles
        const styles = {
            backgroundColor: $('#spf-btn-bg-color').val() || '#0073aa',
            color: $('#spf-btn-color').val() || '#ffffff',
            borderRadius: $('#spf-btn-border-radius').val() || '4px',
            fontSize: $('#spf-btn-font-size').val() || '16px',
            fontWeight: $('#spf-btn-font-weight').val() || '400',
            textTransform: $('#spf-btn-text-transform').val() || 'none'
        };
        
        $previewBtn.css(styles);
        
        // Store hover colors as data attributes
        $previewBtn.data('hover-bg', $('#spf-btn-hover-bg').val() || '#005177');
        $previewBtn.data('hover-color', $('#spf-btn-hover-color').val() || '#ffffff');
    }

    // Update field preview styling
    function updateFieldPreview(fieldId) {
        const $field = $('.spf-field-item[data-field-id="' + fieldId + '"]');
        const field = formFields.find(f => f.id == fieldId);
        
        if (!field || !$field.length) return;
        
        const $preview = $field.find('.spf-field-preview');
        const $input = $preview.find('input, textarea');
        
        if ($input.length && field.styles) {
            $input.css({
                borderColor: field.styles.borderColor || '#ddd',
                borderRadius: field.styles.borderRadius || '4px',
                backgroundColor: field.styles.backgroundColor || '#ffffff',
                color: field.styles.color || '#333333',
                fontSize: field.styles.fontSize || '14px',
                fontWeight: field.styles.fontWeight || '400',
                padding: field.styles.padding || '10px'
            });
        }
    }

    // Initialize button preview on page load
    updateButtonPreview();

    // Listen for button text changes
    $('#spf-button-text').on('input', function() {
        updateButtonPreview();
    });

    // Listen for button style changes
    $('#spf-btn-border-radius, #spf-btn-font-size').on('input', function() {
        updateButtonPreview();
    });

    $('#spf-btn-font-weight, #spf-btn-text-transform').on('change', function() {
        updateButtonPreview();
    });

    // Add hover effect to preview button
    $('#spf-preview-submit-button').on('mouseenter', function() {
        const hoverBg = $(this).data('hover-bg');
        const hoverColor = $(this).data('hover-color');
        
        if (!$(this).data('original-bg')) {
            $(this).data('original-bg', $(this).css('background-color'));
            $(this).data('original-color', $(this).css('color'));
        }
        
        if (hoverBg) $(this).css('background-color', hoverBg);
        if (hoverColor) $(this).css('color', hoverColor);
    }).on('mouseleave', function() {
        const originalBg = $(this).data('original-bg');
        const originalColor = $(this).data('original-color');
        
        if (originalBg) $(this).css('background-color', originalBg);
        if (originalColor) $(this).css('color', originalColor);
    });

    // Handle delete submission
    $('.spf-delete-submission').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
            return;
        }
        
        const $button = $(this);
        const submissionId = $button.data('submission-id');
        const $row = $button.closest('tr');
        
        $button.prop('disabled', true).text('Deleting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_delete_submission',
                submission_id: submissionId,
                nonce: spfAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.spf-submissions-table tbody tr').length === 0) {
                            $('.spf-submissions-table tbody').html('<tr><td colspan="5" style="text-align: center; padding: 30px;">' + 
                                'No submissions found.' + '</td></tr>');
                        }
                    });
                } else {
                    alert(response.data.message || 'Failed to delete submission.');
                    $button.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Delete');
            }
        });
    });

    // Handle unblock IP
    $('.spf-unblock-ip').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to unblock this IP address?')) {
            return;
        }
        
        const $button = $(this);
        const ipAddress = $button.data('ip');
        const $row = $button.closest('tr');
        
        $button.prop('disabled', true).text('Unblocking...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spf_unblock_ip',
                ip_address: ipAddress,
                nonce: spfAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if table is empty
                        if ($('.wp-list-table tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.data.message || 'Failed to unblock IP.');
                    $button.prop('disabled', false).text('Unblock');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false).text('Unblock');
            }
        });
    });
});
