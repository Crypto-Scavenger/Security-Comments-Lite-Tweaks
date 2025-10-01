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

if ( '1' === $cleanup ) {
	global $wpdb;

	// Drop custom table
	$table = esc_sql( $wpdb->prefix . 'sclt_settings' );
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

	// Clean transients
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_sclt_' ) . '%'
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_timeout_sclt_' ) . '%'
		)
	);

	// Clear object cache
	wp_cache_flush();
}
