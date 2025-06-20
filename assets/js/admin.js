/**
 * WooCommerce Free Gift Bulk Coupons Generator Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    const SCG_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initFormValidation();
        },
        
        bindEvents: function() {
            // Form submission
            $('.scg-form').on('submit', this.handleFormSubmission);
            
            // Prefix input formatting
            $('#coupon_prefix').on('input', this.formatPrefix);
            
            // Number input validation
            $('#number_of_coupons').on('input', this.validateCouponCount);
            
            // Product selection change
            $('#product_id').on('change', this.handleProductChange);
        },
        
        handleFormSubmission: function(e) {
            const $form = $(this);
            const $submitBtn = $form.find('.button-primary');
            
            // Validate form before submission
            if (!SCG_Admin.validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state
            $form.addClass('loading');
            $submitBtn.prop('disabled', true);
            
            // Show confirmation for large batches
            const couponCount = parseInt($('#number_of_coupons').val(), 10);
            if (couponCount > 25) {
                const confirmed = confirm(
                    'You are about to generate ' + couponCount + ' coupons. ' +
                    'This may take a while and could potentially timeout depending on your server settings. Do you want to continue?'
                );
                
                if (!confirmed) {
                    $form.removeClass('loading');
                    $submitBtn.prop('disabled', false);
                    e.preventDefault();
                    return false;
                }
            }
        },
        
        formatPrefix: function() {
            let value = $(this).val();
            // Remove special characters and convert to uppercase
            value = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            // Limit to 10 characters
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            $(this).val(value);
        },
        
        validateCouponCount: function() {
            const $input = $(this);
            const value = parseInt($input.val(), 10);
            const $warning = $('#coupon-count-warning');
            
            // Remove existing warning
            $warning.remove();
            
            if (value > 100) {
                $input.val(100);
                $input.after('<span id="coupon-count-warning" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">Maximum 100 coupons allowed</span>');
            } else if (value > 50) {
                $input.after('<span id="coupon-count-warning" style="color: #dba617; font-size: 12px; display: block; margin-top: 5px;">Generating many coupons may take some time and could timeout</span>');
            }
        },
        
        handleProductChange: function() {
            const $select = $(this);
            const $submitBtn = $('.button-primary');
            const selectedValues = $select.val();
            
            if (selectedValues && selectedValues.length > 0) {
                $submitBtn.removeClass('disabled');
            } else {
                $submitBtn.addClass('disabled');
            }
            
            // Update info text based on selection
            const $description = $select.next('.description');
            const selectedCount = selectedValues ? selectedValues.length : 0;
            
            if (selectedCount > 1) {
                $description.text('Selected ' + selectedCount + ' products. Each coupon will include all selected products as free gifts.');
            } else if (selectedCount === 1) {
                $description.text('Selected 1 product. Each coupon will include this product as a free gift.');
            } else {
                $description.text('Select one or more products that will be given as free gifts with the coupon. Hold Ctrl (Windows) or Cmd (Mac) to select multiple products.');
            }
        },
        
        validateForm: function($form) {
            let isValid = true;
            const errors = [];
            
            // Validate product selection
            const productIds = $('#product_id').val();
            if (!productIds || productIds.length === 0) {
                errors.push('Please select at least one product.');
                $('#product_id').focus();
                isValid = false;
            }
            
            // Validate coupon count
            const couponCount = parseInt($('#number_of_coupons').val(), 10);
            if (!couponCount || couponCount < 1) {
                errors.push('Please enter a valid number of coupons.');
                if (isValid) $('#number_of_coupons').focus();
                isValid = false;
            } else if (couponCount > 100) {
                errors.push('Maximum number of coupons is 100.');
                if (isValid) $('#number_of_coupons').focus();
                isValid = false;
            }
            
            // Show errors if any
            if (errors.length > 0) {
                this.showErrorMessage(errors.join('\n'));
            }
            
            return isValid;
        },
        
        initFormValidation: function() {
            // Real-time validation
            $('#product_id, #number_of_coupons').on('blur', function() {
                $(this).removeClass('error');
            });
        },
        
        showErrorMessage: function(message) {
            // Remove existing error messages
            $('.scg-error-message').remove();
            
            // Create and show error message - use text() to prevent XSS
            const $errorDiv = $('<div class="notice notice-error scg-error-message"><p></p></div>');
            $errorDiv.find('p').text(message);
            $('.scg-form').before($errorDiv);
            
            // Scroll to error message
            $('html, body').animate({
                scrollTop: $errorDiv.offset().top - 50
            }, 300);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $errorDiv.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        showSuccessMessage: function(message) {
            const $successDiv = $('<div class="notice notice-success is-dismissible"><p></p></div>');
            $successDiv.find('p').text(message);
            $('.scg-form').before($successDiv);
            
            // Scroll to success message
            $('html, body').animate({
                scrollTop: $successDiv.offset().top - 50
            }, 300);
        }
    };
    
    // Initialize admin functionality
    SCG_Admin.init();
    
    // Handle page unload during form submission
    $(window).on('beforeunload', function() {
        if ($('.scg-form').hasClass('loading')) {
            return 'Coupon generation is in progress. Are you sure you want to leave?';
        }
    });
    
    // Remove loading state when page loads (in case of refresh)
    $('.scg-form').removeClass('loading');
    $('.button-primary').prop('disabled', false);
});
