<?php
/**
 * Core functionality handler
 *
 * @package SecurityCommentsLiteTweaks
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles core plugin functionality and applies tweaks
 *
 * @since 1.0.0
 */
class SCLT_Core {

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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks based on settings
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		$settings = $this->get_settings();

		// Hide WordPress Version
		if ( '1' === $settings['hide_wp_version'] ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}

		// Disable Generator Meta Tag
		if ( '1' === $settings['disable_generator_meta'] ) {
			remove_action( 'wp_head', 'wp_generator' );
		}

		// Remove Script/Style Versions
		if ( '1' === $settings['remove_script_versions'] ) {
			add_filter( 'style_loader_src', array( $this, 'remove_version_parameter' ), 9999 );
			add_filter( 'script_loader_src', array( $this, 'remove_version_parameter' ), 9999 );
		}

		// Disable Application Passwords
		if ( '1' === $settings['disable_app_passwords'] ) {
			add_filter( 'wp_is_application_passwords_available', '__return_false' );
		}

		// Disable Code Editors
		if ( '1' === $settings['disable_code_editors'] ) {
			if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
				define( 'DISALLOW_FILE_EDIT', true );
			}
		}

		// Disable Admin Email Confirmation
		if ( '1' === $settings['disable_admin_email_check'] ) {
			add_filter( 'admin_email_check_interval', '__return_false' );
		}

		// Optimize Comment Scripts
		if ( '1' === $settings['optimize_comment_scripts'] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'optimize_comment_scripts' ), 100 );
		}

		// Disable Comment Hyperlinks
		if ( '1' === $settings['disable_comment_links'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );
		}

		// Disable Trackbacks & Pingbacks
		if ( '1' === $settings['disable_trackbacks'] ) {
			add_filter( 'pings_open', '__return_false', 9999 );
			add_filter( 'xmlrpc_methods', array( $this, 'disable_pingback_xmlrpc' ) );
			add_action( 'wp_head', array( $this, 'remove_x_pingback' ) );
		}

		// Disable Comments Site-wide
		if ( '1' === $settings['disable_comments'] ) {
			$this->disable_comments_completely();
		}
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
	 * Removes version parameter from scripts and styles
	 *
	 * @since 1.0.0
	 *
	 * @param string $src Source URL
	 * @return string Modified URL
	 */
	public function remove_version_parameter( $src ) {
		if ( strpos( $src, 'ver=' ) ) {
			$src = remove_query_arg( 'ver', $src );
		}
		return $src;
	}

	/**
	 * Optimizes comment scripts to only load when needed
	 *
	 * @since 1.0.0
	 */
	public function optimize_comment_scripts() {
		if ( ! is_singular() || ! comments_open() || ! get_option( 'thread_comments' ) ) {
			wp_dequeue_script( 'comment-reply' );
		}
	}

	/**
	 * Disables pingback from XML-RPC
	 *
	 * @since 1.0.0
	 *
	 * @param array $methods XML-RPC methods
	 * @return array Modified methods
	 */
	public function disable_pingback_xmlrpc( $methods ) {
		unset( $methods['pingback.ping'] );
		unset( $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}

	/**
	 * Removes X-Pingback header
	 *
	 * @since 1.0.0
	 */
	public function remove_x_pingback() {
		if ( function_exists( 'header_remove' ) ) {
			header_remove( 'X-Pingback' );
		}
	}

	/**
	 * Disables comments completely across the site
	 *
	 * @since 1.0.0
	 */
	private function disable_comments_completely() {
		// Close comments on front-end
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );

		// Hide existing comments
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );

		// Remove comment support from post types
		add_action( 'admin_init', array( $this, 'remove_comment_support' ) );

		// Hide comment menu and dashboard widget
		add_action( 'admin_menu', array( $this, 'remove_comment_menu' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'remove_comment_dashboard' ) );

		// Redirect comment page to dashboard
		add_action( 'admin_init', array( $this, 'redirect_comment_page' ) );

		// Remove comment links from admin bar
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_comment_admin_bar' ) );

		// Hide comment metaboxes
		add_action( 'admin_head', array( $this, 'hide_comment_metaboxes' ) );
	}

	/**
	 * Removes comment support from all post types
	 *
	 * @since 1.0.0
	 */
	public function remove_comment_support() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	/**
	 * Removes comment menu from admin
	 *
	 * @since 1.0.0
	 */
	public function remove_comment_menu() {
		remove_menu_page( 'edit-comments.php' );
	}

	/**
	 * Removes comment dashboard widget
	 *
	 * @since 1.0.0
	 */
	public function remove_comment_dashboard() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/**
	 * Redirects comment page to dashboard
	 *
	 * @since 1.0.0
	 */
	public function redirect_comment_page() {
		global $pagenow;
		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}

	/**
	 * Removes comment links from admin bar
	 *
	 * @since 1.0.0
	 */
	public function remove_comment_admin_bar() {
		global $wp_admin_bar;
		if ( is_object( $wp_admin_bar ) ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}

	/**
	 * Hides comment metaboxes via CSS
	 *
	 * @since 1.0.0
	 */
	public function hide_comment_metaboxes() {
		echo '<style>#commentstatusdiv, #commentsdiv, #trackbacksdiv { display: none !important; }</style>';
	}
}
