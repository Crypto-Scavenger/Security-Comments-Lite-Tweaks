<?php
/**
 * Plugin Name: Security & Comments Lite Tweaks
 * Description: Lightweight plugin for essential security hardening and comment management tweaks
 * Version: 1.0.0
 * Text Domain: security-comments-lite-tweaks
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'SCLT_VERSION', '1.0.0' );
define( 'SCLT_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCLT_URL', plugin_dir_url( __FILE__ ) );
define( 'SCLT_TABLE_SETTINGS', 'sclt_settings' );

// Include classes
require_once SCLT_DIR . 'includes/class-database.php';
require_once SCLT_DIR . 'includes/class-core.php';
require_once SCLT_DIR . 'includes/class-admin.php';

// Initialize
function sclt_init() {
	$database = new SCLT_Database();
	$core = new SCLT_Core( $database );
	
	if ( is_admin() ) {
		$admin = new SCLT_Admin( $database );
	}
}
add_action( 'plugins_loaded', 'sclt_init' );

// Activation
register_activation_hook( __FILE__, array( 'SCLT_Database', 'activate' ) );

// Deactivation
register_deactivation_hook( __FILE__, array( 'SCLT_Database', 'deactivate' ) );
