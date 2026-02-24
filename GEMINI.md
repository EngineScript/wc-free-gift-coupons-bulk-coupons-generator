# WC Free Gift Coupons Bulk Coupon Generator - WordPress Plugin

## Project Overview

This is a WordPress plugin that generates bulk free gift coupon codes for WooCommerce stores. It integrates with the "Free Gift Coupons for WooCommerce" plugin to create coupons with the proper data structure for free gift functionality. Designed for store administrators who need to generate large quantities of gift coupons efficiently.

## Plugin Details

- **Name:** WC Free Gift Coupons Bulk Coupon Generator
- **Version:** 1.5.1
- **WordPress Compatibility:** 6.5+
- **PHP Compatibility:** 7.4+
- **WooCommerce Compatibility:** 5.0+
- **License:** GPL-3.0-or-later
- **Text Domain:** wc-free-gift-coupons-bulk-coupons-generator
- **Dependencies:** WooCommerce, Free Gift Coupons for WooCommerce

## Architecture & Design Patterns

### Single-Class Plugin Architecture

The plugin follows a singleton pattern with proper WordPress initialization:

```php
class WooCommerceFreeGiftBulkCoupons {
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Plugin Initialization

The plugin uses proper WordPress initialization patterns with dependency checking:

```php
function wc_free_gift_bulk_coupons_init() {
    WooCommerceFreeGiftBulkCoupons::get_instance();
}
add_action( 'plugins_loaded', 'wc_free_gift_bulk_coupons_init' );
```

### File Structure

- `free-gift-bulk-coupon-generator.php` - Main plugin file (all functionality)
- `assets/css/admin.css` - Admin interface styling
- `assets/js/admin.js` - Admin interface JavaScript
- `languages/` - Translation files (.pot file included)
- `CHANGELOG.md` - Developer changelog
- `README.md` - Developer documentation
- `readme.txt` - WordPress.org plugin directory readme
- `.github/workflows/` - CI/CD automation

## WordPress Coding Standards

### Naming Conventions

- **Class:** `WooCommerceFreeGiftBulkCoupons` (PascalCase)
- **Methods:** `snake_case` (WordPress standard)
- **Variables:** `$snake_case`
- **Constants:** `SCG_UPPER_SNAKE_CASE`
- **Text Domain:** Always use `'wc-free-gift-coupons-bulk-coupons-generator'`

### Security Requirements

- Always use `esc_html()`, `esc_attr()`, `esc_url()` for output
- Sanitize input with `sanitize_text_field()`, `wp_unslash()`, `absint()` etc.
- Use `current_user_can( 'manage_woocommerce' )` for capability checks
- Implement proper nonce verification for all forms and actions
- Validate product IDs and coupon parameters
- Use rate limiting to prevent coupon generation abuse

### WordPress & WooCommerce Integration

- **Hooks:** Proper use of WordPress and WooCommerce actions and filters
- **WooCommerce API:** WC_Coupon class for coupon creation
- **Database:** WordPress/WooCommerce APIs only, no direct SQL
- **Internationalization:** All strings use `esc_html__()` or `esc_html_e()`
- **Admin Interface:** Integrated into WooCommerce admin menu

## Plugin-Specific Context

### Core Functionality

#### Bulk Coupon Generation Process

- **Product Selection:** Multi-select interface for choosing gift products
- **Coupon Creation:** Bulk generation using WooCommerce WC_Coupon class
- **Free Gift Integration:** Creates coupons compatible with Free Gift Coupons plugin
- **Security:** Rate limiting and unique code generation to prevent abuse

#### Coupon Security Features

- **Unique Code Generation:** Cryptographically secure random_int() for coupon codes
- **Rate Limiting:** Transient-based system prevents concurrent generation abuse
- **Input Validation:** Comprehensive sanitization of all coupon parameters
- **Capability Checks:** manage_woocommerce permission required for all operations
- **Nonce Verification:** CSRF protection on all forms and AJAX requests

#### Performance Optimizations

- **Bulk Operations:** Efficient batch processing for large coupon quantities
- **Caching:** Transient caching for product dropdown lists
- **Memory Management:** Micro-delays during bulk generation to prevent timeouts
- **Database Efficiency:** Optimized WooCommerce API usage for coupon creation

#### Admin Interface

- **WooCommerce Integration:** Seamlessly integrated into WooCommerce admin menu
- **User-Friendly Forms:** Intuitive interface with validation and feedback
- **Product Selection:** Multi-select dropdown with search functionality
- **Batch Limits:** Configurable limits (max 100 coupons) to prevent server overload

### WooCommerce Integration Security

- **WC_Coupon API:** Proper use of WooCommerce coupon creation methods
- **Product Validation:** Verification of product existence and purchasability
- **Meta Data Structure:** Correct gift_info format for Free Gift Coupons compatibility
- **Coupon Properties:** Proper expiration, usage limits, and individual use settings

### Performance Considerations

- **Large Batch Support:** Handles up to 100 coupons per batch efficiently
- **Server Compatibility:** Timeout prevention and memory management
- **Caching Strategy:** Product list caching to improve form load times
- **Database Optimization:** Minimal queries using WooCommerce APIs

### Free Gift Coupons Plugin Compatibility

- **Meta Data Structure:** Correct _wc_free_gift_coupon_data format
- **Product Integration:** Proper product_id, variation_id, quantity structure
- **Gift Information:** Compatible data structure for seamless integration
- **Plugin Detection:** Graceful handling when Free Gift Coupons plugin unavailable

## Development Standards

### Error Handling

- **WooCommerce Integration:** Proper exception handling for WC_Coupon operations
- **Validation Errors:** User-friendly error messages for invalid input
- **Security Logging:** Debug logging for security events when WP_DEBUG enabled
- **Graceful Degradation:** Proper handling when dependencies unavailable

### Documentation

- **PHPDoc Compliance:** Complete documentation for all methods and parameters
- **Security Comments:** Detailed security justifications and PHPStan annotations
- **WooCommerce Context:** Clear documentation of WooCommerce API usage
- **Integration Notes:** Documentation of Free Gift Coupons plugin compatibility

### Testing & Quality Assurance

- **PHPStan Level 5:** Static analysis compliance with WooCommerce stubs
- **PHPCS WordPress Standards:** Full WordPress and WooCommerce coding standards
- **PHPMD Compliance:** Code quality and complexity management
- **WooCommerce Testing:** Compatibility testing with various WooCommerce versions

## When Reviewing Code

### Critical Issues to Flag

1. **Coupon Security Vulnerabilities** (weak random generation, duplicate codes)
2. **WooCommerce Integration Issues** (incorrect API usage, compatibility problems)
3. **Bulk Operation Security** (rate limiting bypass, resource exhaustion)
4. **Input Validation Failures** (product ID manipulation, parameter injection)
5. **Permission and Capability Issues** (admin access bypass, privilege escalation)

### Plugin-Specific Security Concerns

1. **Coupon Code Generation:** Ensure cryptographically secure randomness
2. **Product Validation:** Verify product existence and permissions
3. **Rate Limiting:** Prevent coupon generation abuse and spam
4. **Meta Data Security:** Validate Free Gift Coupons data structure
5. **Batch Size Limits:** Enforce reasonable limits to prevent resource exhaustion

### Performance Focus Areas

1. **Bulk Generation Efficiency:** Optimize large batch processing
2. **Database Query Optimization:** Minimize WooCommerce API calls
3. **Memory Management:** Handle large product lists efficiently
4. **Caching Implementation:** Effective use of transients for performance
5. **Timeout Prevention:** Micro-delays and batch size management

### Positive Patterns to Recognize

1. **WooCommerce API Compliance:** Proper use of WC_Coupon and related classes
2. **Security-First Design:** Multiple validation and rate limiting layers
3. **User Experience:** Intuitive admin interface with clear feedback
4. **Performance Optimization:** Efficient bulk operations and caching
5. **Plugin Compatibility:** Proper integration with Free Gift Coupons plugin

### Suggestions to Provide

1. **WooCommerce-Specific Solutions:** Prefer WooCommerce APIs over generic approaches
2. **Coupon Security Enhancements:** Additional validation and uniqueness checks
3. **Performance Improvements:** Bulk operation and caching optimizations
4. **User Experience:** Interface improvements and better error handling
5. **Documentation Updates:** WooCommerce integration and security documentation

Remember: This plugin prioritizes coupon generation security, WooCommerce ecosystem compatibility, and efficient bulk operations. All coupon operations must be thoroughly validated, secure, and integrate properly with WooCommerce and the Free Gift Coupons plugin.
