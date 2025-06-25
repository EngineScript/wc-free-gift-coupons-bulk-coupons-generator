/**
 * WooCommerce Free Gift Bulk Coupons Generator Admin JavaScript
 * ES5-compatible code for maximum browser compatibility
 */

jQuery(document).ready(function($) {
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
         * @param {Event} e - The form submission event
         * @returns {boolean} - Whether to proceed with submission
         */
        handleFormSubmission: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('.button-primary');
            
            // Validate form before submission
            if (!SCG_Admin.validateForm($form)) {
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
            value = value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            // Limit to 10 characters
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            $(this).val(value);
        },
        
        /**
         * Validate and sanitize number input for coupon count
         */
        validateNumberInput: function() {
            var $input = $(this);
            var value = $input.val();
            var $warning = $('#coupon-count-warning');
            
            // Remove existing warning
            $warning.remove();
            
            // Sanitize input - remove non-numeric characters
            value = value.replace(/[^\d]/g, '');
            
            // Parse as integer
            var numValue = parseInt(value, 10);
            
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
         * @param {Object} $form - The jQuery form object
         * @returns {boolean} - Whether the form is valid
         */
        validateForm: function($form) {
            var self = this;
            var errors = [];
            var isValid = true;
            
            // Validate individual form sections
            isValid = self.validateProductSelection(errors) && isValid;
            isValid = self.validateCouponCountForm(errors, isValid) && isValid;
            isValid = self.validateCouponPrefix(errors, isValid) && isValid;
            
            // Show errors if any
            if (errors.length > 0) {
                self.showErrorMessage(errors.join('\n'));
            }
            
            return isValid;
        },
        
        /**
         * Validate product selection
         * @param {Array} errors - Array to push error messages to
         * @returns {boolean} - Whether product selection is valid
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
         * @param {Array} errors - Array to push error messages to
         * @param {boolean} isValid - Current validation state
         * @returns {boolean} - Whether coupon count is valid
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
         * @param {Array} errors - Array to push error messages to
         * @param {boolean} isValid - Current validation state
         * @returns {boolean} - Whether coupon prefix is valid
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
                value = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                $this.val(value);
            });
        },
        
        /**
         * Display error message to user
         * @param {string} message - The error message to display
         */
        showErrorMessage: function(message) {
            // Remove existing error messages
            $('.scg-error-message').remove();
            
            // Sanitize message by ensuring it's a string and limiting length
            if (typeof message !== 'string') {
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
         * @param {string} message - The success message to display
         */
        showSuccessMessage: function(message) {
            // Sanitize message
            if (typeof message !== 'string') {
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
    SCG_Admin.init();
    
    // Handle page unload during form submission
    $(window).on('beforeunload', function() {
        if ($('.scg-form').hasClass('loading')) {
            return 'Coupon generation is in progress. Are you sure you want to leave this page?';
        }
    });
    
    // Remove loading state when page loads (in case of refresh)
    $('.scg-form').removeClass('loading');
    $('.button-primary').prop('disabled', false);
});
