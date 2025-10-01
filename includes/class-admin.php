<?php
/**
 * Admin interface handler
 *
 * @package SecurityCommentsLiteTweaks
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin interface and settings page
 *
 * @since 1.0.0
 */
class SCLT_Admin {

	/**
	 * Database instance
	 *
	 * @var SCLT_Database
	 */
	private $database;

	/**
	 * Settings cache
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param SCLT_Database $database Database instance
	 */
	public function __construct( $database ) {
		$this->database = $database;
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Adds admin menu under Tools
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'tools.php',
			__( 'Security & Comments Lite Tweaks', 'security-comments-lite-tweaks' ),
			__( 'Security & Comments', 'security-comments-lite-tweaks' ),
			'manage_options',
			'security-comments-lite-tweaks',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueues admin assets
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'tools_page_security-comments-lite-tweaks' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'sclt-admin',
			SCLT_URL . 'assets/admin.css',
			array(),
			SCLT_VERSION
		);

		wp_enqueue_script(
			'sclt-admin',
			SCLT_URL . 'assets/admin.js',
			array( 'jquery' ),
			SCLT_VERSION,
			true
		);
	}

	/**
	 * Handles settings save
	 *
	 * @since 1.0.0
	 */
	public function handle_save() {
		if ( ! isset( $_POST['sclt_save_settings'] ) ) {
			return;
		}

		// Verify capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'security-comments-lite-tweaks' ) );
		}

		// Verify nonce
		if ( ! isset( $_POST['sclt_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sclt_nonce'] ) ), 'sclt_save_settings' ) ) {
			wp_die( esc_html__( 'Security check failed', 'security-comments-lite-tweaks' ) );
		}

		$result = $this->save_settings();

		if ( is_wp_error( $result ) ) {
			add_settings_error(
				'sclt_messages',
				'sclt_error',
				$result->get_error_message(),
				'error'
			);
		} else {
			add_settings_error(
				'sclt_messages',
				'sclt_success',
				__( 'Settings saved successfully.', 'security-comments-lite-tweaks' ),
				'updated'
			);
		}

		set_transient( 'sclt_admin_notices', get_settings_errors( 'sclt_messages' ), 30 );

		wp_safe_redirect( admin_url( 'tools.php?page=security-comments-lite-tweaks' ) );
		exit;
	}

	/**
	 * Saves settings with validation
	 *
	 * @since 1.0.0
	 *
	 * @return bool|WP_Error Success or error
	 */
	private function save_settings() {
		// Verify capability again
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'security-comments-lite-tweaks' ) );
		}

		$settings = array(
			'hide_wp_version',
			'disable_generator_meta',
			'remove_script_versions',
			'disable_app_passwords',
			'disable_code_editors',
			'disable_admin_email_check',
			'optimize_comment_scripts',
			'disable_comment_links',
			'disable_trackbacks',
			'disable_comments',
			'cleanup_on_uninstall'
		);

		foreach ( $settings as $setting ) {
			$value = isset( $_POST[ $setting ] ) ? '1' : '0';
			$result = $this->database->save_setting( $setting, $value );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Gets all settings with lazy loading
	 *
	 * @since 1.0.0
	 *
	 * @return array Settings array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->database->get_all_settings();
		}
		return $this->settings;
	}

	/**
	 * Gets a setting value safely with default
	 *
	 * @since 1.0.0
	 *
	 * @param array  $settings Settings array
	 * @param string $key      Setting key
	 * @param string $default  Default value
	 * @return string Setting value
	 */
	private function get_setting_value( $settings, $key, $default = '0' ) {
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Renders the admin settings page
	 *
	 * @since 1.0.0
	 */
	public function render_admin_page() {
		// Verify capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access', 'security-comments-lite-tweaks' ) );
		}

		// Display notices
		$notices = get_transient( 'sclt_admin_notices' );
		if ( is_array( $notices ) ) {
			delete_transient( 'sclt_admin_notices' );
			foreach ( $notices as $notice ) {
				if ( isset( $notice['type'] ) && isset( $notice['message'] ) ) {
					echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
				}
			}
		}

		$settings = $this->get_settings();
		?>
		<div class="wrap sclt-admin-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'sclt_save_settings', 'sclt_nonce' ); ?>

				<h2><?php esc_html_e( 'Security Settings', 'security-comments-lite-tweaks' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Hide WordPress Version', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="hide_wp_version" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'hide_wp_version' ) ); ?>>
								<?php esc_html_e( 'Removes WordPress version number from HTML source for security', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Generator Meta Tag', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_generator_meta" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_generator_meta' ) ); ?>>
								<?php esc_html_e( 'Removes generator meta tag from page head', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Remove Script/Style Versions', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="remove_script_versions" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'remove_script_versions' ) ); ?>>
								<?php esc_html_e( 'Removes version parameters from CSS/JS files for better caching', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Application Passwords', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_app_passwords" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_app_passwords' ) ); ?>>
								<?php esc_html_e( 'Disables WordPress application passwords feature for enhanced security', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Code Editors', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_code_editors" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_code_editors' ) ); ?>>
								<?php esc_html_e( 'Removes file and plugin editor from admin for security', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Admin Email Confirmation', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_admin_email_check" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_admin_email_check' ) ); ?>>
								<?php esc_html_e( 'Removes the admin email verification prompt', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Comment Settings', 'security-comments-lite-tweaks' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Optimize Comment Scripts', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="optimize_comment_scripts" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'optimize_comment_scripts' ) ); ?>>
								<?php esc_html_e( 'Only load comment reply scripts when comments are enabled and open', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Comment Hyperlinks', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_comment_links" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_comment_links' ) ); ?>>
								<?php esc_html_e( 'Prevents automatic URL linking in comments for security and spam prevention', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Trackbacks & Pingbacks', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_trackbacks" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_trackbacks' ) ); ?>>
								<?php esc_html_e( 'Disables automatic notifications when linking to other sites', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Disable Comments Site-wide', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="disable_comments" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'disable_comments' ) ); ?>>
								<?php esc_html_e( 'Completely removes comment functionality from your entire site', 'security-comments-lite-tweaks' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Uninstall Settings', 'security-comments-lite-tweaks' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Cleanup on Uninstall', 'security-comments-lite-tweaks' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="cleanup_on_uninstall" value="1" <?php checked( '1', $this->get_setting_value( $settings, 'cleanup_on_uninstall', '1' ) ); ?>>
								<?php esc_html_e( 'Remove all plugin data when uninstalling', 'security-comments-lite-tweaks' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If enabled, all plugin settings and database tables will be removed when you uninstall this plugin.', 'security-comments-lite-tweaks' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'security-comments-lite-tweaks' ), 'primary', 'sclt_save_settings' ); ?>
			</form>
		</div>
		<?php
	}
}
