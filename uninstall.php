<?php
/**
 * Uninstall script for WC Free Gift Coupons Bulk Coupon Generator
 * 
 * This script runs when the plugin is deleted via WordPress admin.
 * It cleans up any data created by the plugin.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up any plugin options/settings if we had any
// (Currently this plugin doesn't store any options, but this is here for future use)
delete_option('scg_plugin_version');
delete_option('scg_settings');

// Clean up any transients
delete_transient('scg_products_cache');

// Note: We don't delete the generated coupons as they may still be in use
// Users should manually delete coupons if they want to remove them completely
