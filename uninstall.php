<?php
/**
 * Uninstall handler for Security & Comments Lite Tweaks
 *
 * @package SecurityCommentsLiteTweaks
 * @since   1.0.0
 */

// Security check
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Include database class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

// Get cleanup preference
$database = new SCLT_Database();
$cleanup = $database->get_setting( 'cleanup_on_uninstall' );

// If setting retrieval failed, default to not cleaning up (safer)
if ( false === $cleanup ) {
	error_log( 'SCLT Uninstall: Could not retrieve cleanup setting, skipping cleanup for safety' );
	return;
}

if ( '1' === $cleanup ) {
	global $wpdb;
	
	$table = $wpdb->prefix . 'sclt_settings';

	// Drop custom table using prepared statement with %i placeholder
	$result = $wpdb->query(
		$wpdb->prepare(
			"DROP TABLE IF EXISTS %i",
			$table
		)
	);

	if ( false === $result ) {
		error_log( 'SCLT Uninstall: Failed to drop table - ' . $wpdb->last_error );
	}

	// Clean transients using prepared statement with wildcards
	$transient_result = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_sclt_' ) . '%'
		)
	);

	if ( false === $transient_result ) {
		error_log( 'SCLT Uninstall: Failed to delete transients - ' . $wpdb->last_error );
	}

	$timeout_result = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_timeout_sclt_' ) . '%'
		)
	);

	if ( false === $timeout_result ) {
		error_log( 'SCLT Uninstall: Failed to delete transient timeouts - ' . $wpdb->last_error );
	}

	// Clear object cache
	wp_cache_flush();
}
