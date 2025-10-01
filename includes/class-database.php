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
		$result = self::create_table();
		if ( is_wp_error( $result ) ) {
			error_log( 'SCLT Activation Error: ' . $result->get_error_message() );
			return;
		}
		
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
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	private static function create_table() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		$charset_collate = $wpdb->get_charset_collate();
		
		// Use prepared statement with %i placeholder for WordPress 6.2+
		$sql = $wpdb->prepare(
			"CREATE TABLE IF NOT EXISTS %i (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				setting_key varchar(191) NOT NULL,
				setting_value varchar(10) NOT NULL DEFAULT '0',
				PRIMARY KEY (id),
				UNIQUE KEY setting_key (setting_key)
			) %s",
			$table_name,
			$charset_collate
		);
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		
		// Verify table was created successfully
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$table_name
			)
		);
		
		if ( $table_name !== $table_exists ) {
			return new WP_Error(
				'table_creation_failed',
				__( 'Failed to create database table', 'security-comments-lite-tweaks' )
			);
		}
		
		return true;
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
				$result = $database->save_setting( $key, $value );
				if ( is_wp_error( $result ) ) {
					error_log( 'SCLT: Failed to set default for ' . $key . ' - ' . $result->get_error_message() );
				}
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
				"SELECT setting_value FROM %i WHERE setting_key = %s",
				$table,
				$key
			)
		);
		
		if ( null === $result ) {
			return false;
		}
		
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
		
		// Use direct query with prepare since replace() doesn't support %i
		$result = $wpdb->query(
			$wpdb->prepare(
				"REPLACE INTO %i (setting_key, setting_value) VALUES (%s, %s)",
				$table,
				$key,
				$value
			)
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
		
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}
		
		global $wpdb;
		
		$table = $wpdb->prefix . SCLT_TABLE_SETTINGS;
		
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT setting_key, setting_value FROM %i",
				$table
			),
			ARRAY_A
		);
		
		if ( null === $results ) {
			error_log( 'SCLT: Failed to retrieve settings - ' . $wpdb->last_error );
			return array();
		}
		
		$settings = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $row ) {
				if ( isset( $row['setting_key'] ) && isset( $row['setting_value'] ) ) {
					$settings[ $row['setting_key'] ] = $row['setting_value'];
				}
			}
		}
		
		set_transient( 'sclt_settings_cache', $settings, 12 * HOUR_IN_SECONDS );
		
		return $settings;
	}
}
