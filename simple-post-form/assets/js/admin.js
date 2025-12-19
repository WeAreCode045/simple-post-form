jQuery(document).ready(function($) {
    'use strict';

    let fieldCounter = 0;
    let formFields = [];

    // Initialize color pickers
    if ($.fn.wpColorPicker) {
        $('.spf-color-picker').wpColorPicker();
    }

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
        const fieldHtml = `
            <div class="spf-field-item" data-field-id="${fieldData.id}">
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
            number: 'Number'
        };
        return labels[type] || type;
    }

    function generateFieldName(type) {
        return type + '_' + Date.now();
    }
});
