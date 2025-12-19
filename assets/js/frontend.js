jQuery(document).ready(function($) {
    'use strict';

    // Handle form submission
    $('.spf-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.spf-submit-button');
        const $messages = $form.find('.spf-form-messages');
        const formId = $form.data('form-id');

        // Clear previous messages
        $messages.html('').removeClass('spf-success spf-error');

        // Validate required fields
        let isValid = true;
        $form.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('spf-field-error');
            } else {
                $(this).removeClass('spf-field-error');
            }
        });

        if (!isValid) {
            $messages.addClass('spf-error').html(spfFrontend.strings.validationError);
            return;
        }

        // Disable submit button
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text(spfFrontend.strings.submitting);

        // Prepare form data
        const formData = $form.serialize() + '&action=spf_submit_form&nonce=' + spfFrontend.nonce + '&form_id=' + formId;

        // Submit form
        $.ajax({
            url: spfFrontend.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $messages.addClass('spf-success').html(response.data.message);
                    $form[0].reset();
                    $form.find('.spf-field-error').removeClass('spf-field-error');
                } else {
                    $messages.addClass('spf-error').html(response.data.message || spfFrontend.strings.error);
                }
            },
            error: function() {
                $messages.addClass('spf-error').html(spfFrontend.strings.error);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
                
                // Scroll to messages
                $('html, body').animate({
                    scrollTop: $messages.offset().top - 100
                }, 500);
            }
        });
    });

    // Remove error class on input
    $('.spf-form input, .spf-form textarea').on('input', function() {
        $(this).removeClass('spf-field-error');
    });

    // Modal functionality
    $('.spf-modal-trigger').on('click', function(e) {
        e.preventDefault();
        const modalId = $(this).data('modal');
        const $modal = $('#' + modalId);
        
        if ($modal.length) {
            $modal.fadeIn(300);
            $('body').addClass('spf-modal-open');
        }
    });

    // Close modal on close button
    $(document).on('click', '.spf-modal-close', function() {
        $(this).closest('.spf-modal').fadeOut(300);
        $('body').removeClass('spf-modal-open');
    });

    // Close modal on overlay click
    $(document).on('click', '.spf-modal-overlay', function() {
        $(this).closest('.spf-modal').fadeOut(300);
        $('body').removeClass('spf-modal-open');
    });

    // Close modal on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.spf-modal:visible').fadeOut(300);
            $('body').removeClass('spf-modal-open');
        }
    });

    // Modal button hover effects
    $('.spf-modal-trigger').on('mouseenter', function() {
        const hoverBg = $(this).data('hover-bg');
        const hoverColor = $(this).data('hover-color');
        const $btn = $(this);
        
        if (!$btn.data('original-bg')) {
            $btn.data('original-bg', $btn.css('background-color'));
            $btn.data('original-color', $btn.css('color'));
        }
        
        if (hoverBg) $btn.css('background-color', hoverBg);
        if (hoverColor) $btn.css('color', hoverColor);
    }).on('mouseleave', function() {
        const $btn = $(this);
        const originalBg = $btn.data('original-bg');
        const originalColor = $btn.data('original-color');
        
        if (originalBg) $btn.css('background-color', originalBg);
        if (originalColor) $btn.css('color', originalColor);
    });

    // Prevent clicks inside modal content from closing modal
    $('.spf-modal-content').on('click', function(e) {
        e.stopPropagation();
    });

    // Auto-close modal after successful form submission
    $(document).on('ajaxComplete', function(event, xhr, settings) {
        if (settings.data && settings.data.indexOf('action=spf_submit_form') !== -1) {
            const response = xhr.responseJSON;
            if (response && response.success) {
                setTimeout(function() {
                    $('.spf-modal:visible').fadeOut(300);
                    $('body').removeClass('spf-modal-open');
                }, 2000);
            }
        }
    });
});
