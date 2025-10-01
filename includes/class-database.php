<?php
/**
 * Database operations handler
 *
 * @package SecurityCommentsLiteTweaks
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all database operations for the plugin
 *
 * @since 1.0.0
 */
class SCLT_Database {

	/**
	 * Plugin activation handler
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::create_table();
		self::set_default_settings();
	}

	/**
	 * Plugin deactivation handler
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		// Clear any transients if needed
		delete_transient( 'sclt_settings_cache' );
	}

	/**
	 * Creates the custom settings table
	 *
	 * @since 1.0.0
	 */
	private static function create_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(191) NOT NULL,
			setting_value varchar(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (id),
			UNIQUE KEY setting_key (setting_key)
		) {$charset_collate};";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Sets default settings on activation
	 *
	 * @since 1.0.0
	 */
	private static function set_default_settings() {
		$defaults = array(
			'hide_wp_version' => '0',
			'disable_generator_meta' => '0',
			'remove_script_versions' => '0',
			'disable_app_passwords' => '0',
			'disable_code_editors' => '0',
			'disable_admin_email_check' => '0',
			'optimize_comment_scripts' => '0',
			'disable_comment_links' => '0',
			'disable_trackbacks' => '0',
			'disable_comments' => '0',
			'cleanup_on_uninstall' => '1'
		);
		
		$database = new self();
		foreach ( $defaults as $key => $value ) {
			$existing = $database->get_setting( $key );
			if ( false === $existing ) {
				$database->save_setting( $key, $value );
			}
		}
	}

	/**
	 * Retrieves a setting value
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Setting key to retrieve
	 * @return string|false Setting value or false if not found
	 */
	public function get_setting( $key ) {
		global $wpdb;
		
		$table = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT setting_value FROM `{$table}` WHERE setting_key = %s",
				$key
			)
		);
		
		return $result;
	}

	/**
	 * Saves a setting value
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Setting key
	 * @param string $value Setting value
	 * @return bool|WP_Error Success or error
	 */
	public function save_setting( $key, $value ) {
		global $wpdb;
		
		$table = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		
		$result = $wpdb->replace(
			$table,
			array(
				'setting_key' => $key,
				'setting_value' => $value,
			),
			array( '%s', '%s' )
		);
		
		if ( false === $result ) {
			error_log( 'SCLT DB Error: ' . $wpdb->last_error );
			return new WP_Error( 'db_error', __( 'Failed to save setting', 'security-comments-lite-tweaks' ) );
		}
		
		// Clear cache
		delete_transient( 'sclt_settings_cache' );
		
		return true;
	}

	/**
	 * Gets all settings
	 *
	 * @since 1.0.0
	 *
	 * @return array Settings array
	 */
	public function get_all_settings() {
		$cached = get_transient( 'sclt_settings_cache' );
		
		if ( false !== $cached ) {
			return $cached;
		}
		
		global $wpdb;
		
		$table = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		
		$results = $wpdb->get_results(
			"SELECT setting_key, setting_value FROM `{$table}`",
			ARRAY_A
		);
		
		$settings = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$settings[ $row['setting_key'] ] = $row['setting_value'];
			}
		}
		
		set_transient( 'sclt_settings_cache', $settings, 12 * HOUR_IN_SECONDS );
		
		return $settings;
	}
}
