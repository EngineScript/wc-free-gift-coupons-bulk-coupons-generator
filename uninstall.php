<?php
/**
 * Uninstall script for Free Gift Coupons Bulk Coupon Generator
 *
 * This script runs when the plugin is deleted via WordPress admin.
 * It cleans up any data created by the plugin.
 *
 * @package free-gift-coupons-bulk-coupons-generator
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Clean up any plugin options/settings if we had any.
// (Currently this plugin doesn't store any options, but this is here for future use).
delete_option( 'fgcbg_plugin_version' );
delete_option( 'fgcbg_settings' );

/**
 * Deletes all transients with a specific prefix.
 *
 * Namespaced to avoid collisions — only runs during uninstall.
 *
 * @since 1.5.0
 * @param string $prefix The prefix to search for.
 * @return void
 */
function fgcbg_delete_transients_with_prefix( $prefix ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$transients = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . $prefix ) . '%'
		)
	);

	foreach ( $transients as $transient ) {
		// Remove the '_transient_' prefix to get the transient name.
		$transient_name = str_replace( '_transient_', '', $transient );
		delete_transient( $transient_name );
	}
}

// Clean up any transients.
fgcbg_delete_transients_with_prefix( 'fgcbg_generating_' );

// Note: We don't delete the generated coupons as they may still be in use.
// Users should manually delete coupons if they want to remove them completely.
