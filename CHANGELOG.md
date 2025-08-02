# Changelog for WC Free Gift Coupons Bulk Coupon Generator

## 1.4.0 - August 2, 2025
### Breaking Changes
- **Text Domain Standardization**: Changed text domain from `WC-Free-Gift-Coupons-Bulk-Coupons-Generator` to `wc-free-gift-coupons-bulk-coupons-generator` to comply with WordPress standards that require only lowercase letters, numbers, and hyphens.

### Security
- **Nonce Verification Fix**: Added proper nonce verification in the `admin_init` method to prevent unauthorized form processing and address WordPress.Security.NonceVerification.Missing warning.

### Improvements
- **Deprecated Function Removal**: Removed the deprecated `load_plugin_textdomain()` call as WordPress automatically handles plugin translations for plugins hosted on WordPress.org since version 4.6.
- **Repository Links Update**: Updated all GitHub repository links to use lowercase format to match the renamed repository.
- **Testing Support**: Added `wc_free_gift_bulk_coupons_is_loaded()` helper function to improve compatibility with testing frameworks and replace the removed test function.

### Development
- **Package References**: Updated all `@package` references to use the new lowercase text domain format.

## 1.3.0 - August 2, 2025
### Security
- **Secure Coupon Generation**: Replaced `wp_rand()` with the more secure `random_int()` for generating coupon codes, ensuring cryptographic-level randomness and reducing the risk of predictable codes.
- **Streamlined Nonce Verification**: Removed a redundant nonce check in `admin_init` to simplify the security workflow, as the primary verification is already handled in `handle_coupon_generation`.

### Bug Fixes
- **Corrected Transient Removal**: Fixed a bug in `uninstall.php` where an incorrect transient key was used, preventing cached product data from being properly deleted upon plugin uninstallation.

### Refactoring
- **Code Cleanup**: Removed the unused `fgbcg_admin_menu()` function to improve code clarity and maintainability.

## 1.2.0 - July 15, 2025
### Security
- **Comprehensive Security Audit**: Conducted a full security review and implemented hardening measures across the plugin to protect against common vulnerabilities.
- **HTTP Security Headers**: Added `X-Content-Type-Options: nosniff` and `X-Frame-Options: SAMEORIGIN` headers to enhance protection against content sniffing and clickjacking attacks.
- **Enhanced Input Sanitization**: Improved sanitization and validation of all user inputs to provide stronger defense against XSS and other injection-based threats.
- **Rate Limiting**: Implemented a rate-limiting mechanism to prevent brute-force attacks and abuse of the coupon generation feature.

### Bug Fixes
- **Prefix Sanitization**: Resolved an issue where the coupon prefix was not being properly sanitized, closing a potential security gap.
- **Invalid Product ID Handling**: Fixed a bug that could allow invalid product IDs to be processed, improving data integrity.

### Improvements
- **Performance Boost**: Added transient caching for the product dropdown, significantly improving performance on sites with many products.
- **Error Messaging**: Implemented more descriptive error messages for failed coupon generation, making it easier to diagnose issues.

## 1.1.0 - June 25, 2025
### Improvements & Fixes
- **Text Domain Standardization**: Fixed WordPress textdomain to match plugin slug `WC-Free-Gift-Coupons-Bulk-Coupons-Generator`
- **Internationalization Compliance**: Updated all translation functions and POT file to use consistent textdomain
- **Code Quality**: Enhanced code documentation and inline comments for better maintainability
- **WordPress Standards**: Improved compliance with WordPress coding standards and best practices
- **Coupon Generation**: Restored full character set for coupon codes (all lowercase letters and digits)
- **File Structure**: Renamed POT file to match WordPress naming conventions
- **Security Enhancement**: Additional input validation and sanitization improvements
- **Documentation**: Added comprehensive changelog.txt file for WordPress.org compatibility

### Technical Changes
- Updated load_plugin_textdomain() to use correct textdomain
- Standardized all __(), esc_html__(), esc_attr_e() function calls
- Enhanced PHPDoc comments and function documentation
- Improved error logging and debug information
- Added comprehensive version tracking across all project files

## 1.0.0 - June 17, 2025
### Initial Release
- **Core Features**: WordPress plugin for generating bulk free gift coupons that work with Free Gift Coupons for WooCommerce
- **Admin Interface**: User-friendly admin panel in WooCommerce menu
- **Multi-Product Support**: Select single or multiple products for free gift coupon generation
- **Custom Prefixes**: Add custom prefixes to coupon codes (e.g., GIFT-ABC123)
- **Bulk Generation**: Generate up to 100 coupons at once with timeout protection
- **Free Gift Compatibility**: Creates coupons with proper gift_info data structure for Free Gift Coupons for WooCommerce plugin
- **Security First**: Enterprise-grade security with nonces, capability checks, input sanitization
- **Rate Limiting**: Prevent concurrent generation requests
- **Performance Optimized**: Caching, optimized queries, memory-efficient processing
- **Internationalization**: Ready for translation into multiple languages
- **WordPress Standards**: Follows all WordPress coding standards and best practices
- **OWASP Compliant**: Meets OWASP security guidelines for web applications
- **Error Handling**: Graceful error handling with user-friendly messages
- **Responsive Design**: Works perfectly on desktop and mobile devices

### Security Features
- **XSS Prevention**: All outputs properly escaped using WordPress functions
- **CSRF Protection**: WordPress nonce verification for all form submissions
- **Input Validation**: Comprehensive sanitization and validation of all inputs
- **SQL Injection Protection**: Uses WordPress/WooCommerce APIs exclusively
- **Rate Limiting**: User-based request limiting with transients
- **HTTP Security Headers**: X-Content-Type-Options, X-Frame-Options
- **File System Protection**: .htaccess protection for sensitive files