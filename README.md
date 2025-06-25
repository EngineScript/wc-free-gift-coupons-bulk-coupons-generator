# WC Free Gift Coupons Bulk Coupon Generator

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/2c6de0b116fc421287600a34db137666)](https://app.codacy.com/gh/EngineScript/WC-Free-Gift-Coupons-Bulk-Coupons-Generator/dashboard?utm_source=gh&utm_medium=referral&utm_content=&utm_campaign=Badge_grade)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg?logo=gnu)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.5%2B-blue.svg?logo=wordpress)](https://wordpress.org/)
[![PHP Compatible](https://img.shields.io/badge/PHP-7.4%2B-purple.svg?logo=php)](https://www.php.net/)

## Current Version
[![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg?logo=github)](https://github.com/EngineScript/WC-Free-Gift-Coupons-Bulk-Coupons-Generator/releases/latest/download/free-gift-bulk-coupon-generator-1.0.0.zip)

A WordPress plugin for generating bulk free gift coupons that work specifically with the **Free Gift Coupons for WooCommerce** plugin. Creates coupons with the proper data structure required for free gift functionality.

**⚠️ Important**: This plugin requires the [Free Gift Coupons for WooCommerce](https://woocommerce.com/products/free-gift-coupons/) plugin to function properly. The Free Gift Coupons plugin can be purchased from the official WooCommerce marketplace.

## Features

- **Free Gift Compatibility**: Specifically designed to work with Free Gift Coupons for WooCommerce plugin
- **Easy-to-use Admin Interface**: Generate free gift coupons through a user-friendly WordPress admin panel
- **Multi-Product Support**: Select single or multiple products as free gifts
- **Custom Prefixes**: Add custom prefixes to your coupon codes (e.g., GIFT-ABC123)
- **Bulk Generation**: Generate up to 100 coupons at once (security-optimized limit)
- **Proper Data Structure**: Creates gift_info arrays with correct product ID mapping
- **Security First**: Follows WordPress and OWASP security best practices with comprehensive protection
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Internationalization Ready**: Prepared for translation into multiple languages
- **Developer Friendly**: Extensive hooks and filters for customization

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher
- **[Free Gift Coupons for WooCommerce](https://woocommerce.com/products/free-gift-coupons/)** plugin (required - available for purchase)
- Write access to the WordPress uploads directory

## Installation

1. Download the plugin files
2. Upload the `free-gift-bulk-coupon-generator` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Make sure you have the **Free Gift Coupons for WooCommerce** plugin installed and activated
5. Navigate to **WooCommerce > Free Gift Bulk Coupons** to start using the plugin

## Usage

1. Go to **WooCommerce > Free Gift Bulk Coupons** in your WordPress admin
2. Select one or more products you want to give as free gifts
3. Enter the number of coupons to generate (1-100)
4. Optionally add a custom prefix for the coupon codes
5. Click "Generate Free Gift Coupons"

## Generated Coupon Features

- **Unique Codes**: Each coupon gets a unique random code
- **One-time Use**: Each coupon can only be used once
- **Individual Use**: Coupons cannot be combined with other coupons
- **Auto-expiration**: Coupons expire after 1 year
- **Detailed Descriptions**: Each coupon includes information about the associated product

## Security Features

- User capability verification (`manage_woocommerce` required)
- WordPress nonce verification for form submissions
- Input sanitization and validation
- SQL injection prevention
- XSS protection

## Developer Information

### File Structure
```
free-gift-bulk-coupon-generator/
├── free-gift-bulk-coupon-generator.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── admin.css              # Admin interface styles
│   └── js/
│       └── admin.js               # Admin interface JavaScript
├── languages/
│   └── free-gift-bulk-coupon-generator.pot # Translation template
└── README.md                      # This file
```

### Hooks and Filters

The plugin provides several hooks for developers:

#### Actions
- `scg_before_coupon_generation` - Fired before coupon generation starts
- `scg_after_coupon_generation` - Fired after coupon generation completes
- `scg_coupon_generated` - Fired after each individual coupon is created

#### Filters
- `scg_coupon_code_length` - Filter the length of generated coupon codes
- `scg_coupon_expiry_days` - Filter the number of days until coupon expiry
- `scg_max_coupons_per_batch` - Filter the maximum number of coupons per batch

### Code Example

```php
// Customize coupon expiry to 30 days
add_filter('scg_coupon_expiry_days', function($days) {
    return 30;
});

// Log when coupons are generated
add_action('scg_after_coupon_generation', function($product_id, $count) {
    error_log("Generated {$count} coupons for product {$product_id}");
}, 10, 2);
```

## Changelog

### 1.0.0
- Initial release
- Admin interface for coupon generation
- Support for custom prefixes
- Multiple discount types
- Security and performance optimizations

## Support

For support, feature requests, or bug reports, please visit the [GitHub repository](https://github.com/EngineScript/WC-Free-Gift-Coupons-Bulk-Coupons-Generator).

---

**Note**: This plugin is designed specifically for WooCommerce and requires WooCommerce to be installed and activated to function properly.
