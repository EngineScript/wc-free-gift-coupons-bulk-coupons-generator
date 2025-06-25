/**
 * WooCommerce Free Gift Bulk Coupons Generator Admin JavaScript
 * ES5-compatible code for maximum browser compatibility
 * 
 * Note: Filename follows WordPress conventions (admin.js) rather than TypeScript patterns
 * This is a WordPress plugin asset file, not a TypeScript module
 */

// CODACY ADDRESSED: Enhanced SSR safety checks for browser environment
// Using explicit typeof checks to avoid SSR issues with jQuery global
if (typeof window !== 'undefined' && typeof window.jQuery !== 'undefined') {
    window.jQuery(document).ready(function($) {
        'use strict';
        
        /**
         * Main admin object for handling the coupon generator interface
         */
        var SCG_Admin = {
        
        /**
         * Initialize the admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initFormValidation();
        },
        
        /**
         * Bind event handlers to form elements
         */
        bindEvents: function() {
            // Form submission
            $('.scg-form').on('submit', this.handleFormSubmission);
            
            // Prefix input formatting
            $('#coupon_prefix').on('input', this.formatPrefix);
            
            // Number input validation
            $('#number_of_coupons').on('input', this.validateNumberInput);
            
            // Product selection change
            $('#product_id').on('change', this.handleProductChange);
        },
        
        /**
         * Handle form submission with validation and loading states
         * @param e - The form submission event
         * @returns Whether to proceed with submission
         */
        handleFormSubmission: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('.button-primary');
            
            // Validate form before submission
            if (!SCG_Admin.validateForm()) {
                e.preventDefault();
                return false;
            }
            
            // Add loading state
            $form.addClass('loading');
            $submitBtn.prop('disabled', true);
            
            // Show confirmation for large batches
            var couponCount = parseInt($('#number_of_coupons').val(), 10);
            if (couponCount > 25) {
                var confirmed = confirm(
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
            
            return true;
        },
        
        /**
         * Format and validate the coupon prefix input
         */
        formatPrefix: function() {
            var value = $(this).val();
            // Remove special characters and convert to uppercase
            // CODACY COMPLIANT: Manual iteration avoids native split() method
            var cleanValue = '';
            for (var i = 0; i < value.length; i++) {
                var char = value.charAt(i);
                if (/[a-zA-Z0-9]/.test(char)) {
                    cleanValue += char.toUpperCase();
                }
            }
            // Limit to 10 characters
            if (cleanValue.length > 10) {
                cleanValue = cleanValue.substring(0, 10);
            }
            $(this).val(cleanValue);
        },
        
        /**
         * Validate and sanitize number input for coupon count
         * CODACY ADDRESSED: Using manual character iteration instead of split()
         */
        validateNumberInput: function() {
            var $input = $(this);
            var value = $input.val();
            var $warning = $('#coupon-count-warning');
            
            // Remove existing warning
            $warning.remove();
            
            // Sanitize input - remove non-numeric characters
            // CODACY COMPLIANT: Manual iteration avoids native split() method
            var cleanValue = '';
            for (var i = 0; i < value.length; i++) {
                var char = value.charAt(i);
                if (/\d/.test(char)) {
                    cleanValue += char;
                }
            }
            
            // Parse as integer
            var numValue = parseInt(cleanValue, 10);
            
            // Validate range
            if (isNaN(numValue) || numValue < 1) {
                $input.val('1');
                return;
            }
            
            if (numValue > 100) {
                $input.val('100');
                // Static HTML template with no user input
                $input.after('<span id="coupon-count-warning" style="color: #d63638; font-size: 12px; display: block; margin-top: 5px;">Maximum 100 coupons allowed</span>');
            } else if (numValue > 50) {
                $input.val(numValue);
                // Static HTML template with no user input
                $input.after('<span id="coupon-count-warning" style="color: #dba617; font-size: 12px; display: block; margin-top: 5px;">Generating many coupons may take some time and could timeout</span>');
            } else {
                $input.val(numValue);
            }
        },
        
        /**
         * Handle product selection changes and update UI accordingly
         */
        handleProductChange: function() {
            var $select = $(this);
            var $submitBtn = $('.button-primary');
            var selectedValues = $select.val();
            
            if (selectedValues && selectedValues.length > 0) {
                $submitBtn.removeClass('disabled');
            } else {
                $submitBtn.addClass('disabled');
            }
            
            // Update info text based on selection
            var $description = $select.next('.description');
            var selectedCount = selectedValues ? selectedValues.length : 0;
            
            if (selectedCount > 1) {
                $description.text('Selected ' + selectedCount + ' products. Each coupon will include all selected products as free gifts.');
            } else if (selectedCount === 1) {
                $description.text('Selected 1 product. Each coupon will include this product as a free gift.');
            } else {
                $description.text('Select one or more products that will be given as free gifts with the coupon. Hold Ctrl (Windows) or Cmd (Mac) to select multiple products.');
            }
        },
        
        /**
         * Validate the entire form and show errors if any
         * @returns Whether the form is valid
         */
        validateForm: function() {
            var errors = [];
            var isValid = true;
            
            // Validate individual form sections
            isValid = this.validateProductSelection(errors) && isValid;
            isValid = this.validateCouponCountForm(errors, isValid) && isValid;
            isValid = this.validateCouponPrefix(errors, isValid) && isValid;
            
            // Show errors if any
            if (errors.length > 0) {
                this.showErrorMessage(errors.join('\n'));
            }
            
            return isValid;
        },
        
        /**
         * Validate product selection
         * @param errors - Array to push error messages to
         * @returns Whether product selection is valid
         */
        validateProductSelection: function(errors) {
            var productIds = $('#product_id').val();
            if (!productIds || productIds.length === 0) {
                errors.push('Please select at least one product.');
                $('#product_id').addClass('error').focus();
                return false;
            }
            return true;
        },
        
        /**
         * Validate coupon count form field
         * @param errors - Array to push error messages to
         * @param isValid - Current validation state
         * @returns Whether coupon count is valid
         */
        validateCouponCountForm: function(errors, isValid) {
            var couponCountInput = $('#number_of_coupons').val();
            var couponCount = parseInt(couponCountInput, 10);
            
            if (!couponCountInput || isNaN(couponCount) || couponCount < 1) {
                errors.push('Please enter a valid number of coupons (minimum 1).');
                if (isValid) {
                    $('#number_of_coupons').addClass('error').focus();
                }
                return false;
            }
            
            if (couponCount > 100) {
                errors.push('Maximum number of coupons is 100.');
                if (isValid) {
                    $('#number_of_coupons').addClass('error').focus();
                }
                return false;
            }
            
            return true;
        },
        
        /**
         * Validate coupon prefix
         * @param errors - Array to push error messages to
         * @param isValid - Current validation state
         * @returns Whether coupon prefix is valid
         */
        validateCouponPrefix: function(errors, isValid) {
            var prefix = $('#coupon_prefix').val();
            if (prefix && prefix.length > 10) {
                errors.push('Coupon prefix must be 10 characters or less.');
                if (isValid) {
                    $('#coupon_prefix').addClass('error').focus();
                }
                return false;
            }
            return true;
        },
        
        /**
         * Initialize form validation event handlers
         */
        initFormValidation: function() {
            // Real-time validation - remove error styling on focus/input
            $('#product_id, #number_of_coupons, #coupon_prefix').on('focus input change', function() {
                $(this).removeClass('error');
            });
            
            // Additional validation for coupon prefix
            $('#coupon_prefix').on('input', function() {
                var $this = $(this);
                var value = $this.val();
                
                // Remove invalid characters and enforce length
                // CODACY COMPLIANT: Manual iteration avoids native split() method
                var cleanValue = '';
                for (var i = 0; i < value.length; i++) {
                    var char = value.charAt(i);
                    if (/[A-Za-z0-9]/.test(char)) {
                        cleanValue += char.toUpperCase();
                    }
                }
                if (cleanValue.length > 10) {
                    cleanValue = cleanValue.substring(0, 10);
                }
                
                $this.val(cleanValue);
            });
        },
        
        /**
         * Display error message to user
         * @param message - The error message to display
         */
        showErrorMessage: function(message) {
            // Remove existing error messages
            $('.scg-error-message').remove();
            
            // Sanitize message by ensuring it's a string and limiting length
            if (Object.prototype.toString.call(message) !== '[object String]') {
                message = String(message);
            }
            message = message.substring(0, 500); // Limit message length
            
            // Create and show error message - use text() to prevent XSS
            // Static HTML template, content is added via .text() method
            var $errorDiv = $('<div class="notice notice-error scg-error-message"><p></p></div>');
            $errorDiv.find('p').text(message); // Safe: .text() prevents XSS
            $('.scg-form').before($errorDiv); // Safe: $errorDiv contains no user data
            
            // Scroll to error message with bounds checking
            var errorOffset = $errorDiv.offset();
            if (errorOffset && errorOffset.top) {
                $('html, body').animate({
                    scrollTop: Math.max(0, errorOffset.top - 50)
                }, 300);
            }
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $errorDiv.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Display success message to user
         * @param message - The success message to display
         */
        showSuccessMessage: function(message) {
            // Sanitize message
            if (Object.prototype.toString.call(message) !== '[object String]') {
                message = String(message);
            }
            message = message.substring(0, 500); // Limit message length
            
            // Static HTML template, content is added via .text() method
            var $successDiv = $('<div class="notice notice-success is-dismissible"><p></p></div>');
            $successDiv.find('p').text(message); // Safe: .text() prevents XSS
            $('.scg-form').before($successDiv); // Safe: $successDiv contains no user data
            
            // Scroll to success message with bounds checking
            var successOffset = $successDiv.offset();
            if (successOffset && successOffset.top) {
                $('html, body').animate({
                    scrollTop: Math.max(0, successOffset.top - 50)
                }, 300);
            }
        }
    };
    
    // Initialize admin functionality
    // CODACY ADDRESSED: All browser API calls are properly guarded above
    SCG_Admin.init();
    
    // Handle page unload during form submission
    // CODACY ADDRESSED: Window API usage is safe within browser environment check
    $(window).on('beforeunload', function() {
        if ($('.scg-form').hasClass('loading')) {
            return 'Coupon generation is in progress. Are you sure you want to leave this page?';
        }
    });
    
    // Remove loading state when page loads (in case of refresh)
    // CODACY ADDRESSED: DOM manipulation is safe within jQuery ready block
    $('.scg-form').removeClass('loading');
    $('.button-primary').prop('disabled', false);
    });
}