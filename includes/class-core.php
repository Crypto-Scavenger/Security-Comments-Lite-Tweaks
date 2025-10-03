<?php
/**
 * Core functionality for Scripts & Styles Lite Tweaks
 *
 * @package ScriptsStylesLiteTweaks
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles core plugin functionality
 */
class SSLT_Core {

	/**
	 * Database instance
	 *
	 * @var SSLT_Database
	 */
	private $database;

	/**
	 * Settings
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Constructor
	 *
	 * @param SSLT_Database $database Database instance
	 */
	public function __construct( $database ) {
		$this->database = $database;
		$this->init_hooks();
	}

	/**
	 * Get settings (lazy loading)
	 *
	 * @return array Settings
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->database->get_all_settings();
		}
		return $this->settings;
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// Disable jQuery Migrate
		add_action( 'wp_default_scripts', array( $this, 'disable_jquery_migrate' ) );
		
		// Disable Emoji Scripts
		add_action( 'init', array( $this, 'disable_emoji_scripts' ) );
		
		// Disable Embeds
		add_action( 'init', array( $this, 'disable_embeds' ), 9999 );
		
		// Disable Admin Bar Scripts (Frontend)
		add_action( 'wp_enqueue_scripts', array( $this, 'disable_admin_bar_scripts' ), 999 );
		
		// Disable Dashicons
		add_action( 'wp_enqueue_scripts', array( $this, 'disable_dashicons' ) );
		
		// Enable Selective Block Loading
		add_action( 'wp_enqueue_scripts', array( $this, 'enable_selective_blocks' ), 999 );
		
		// Disable Global Styles
		add_action( 'wp_enqueue_scripts', array( $this, 'disable_global_styles' ), 999 );
		
		// Disable Classic Theme Styles
		add_filter( 'wp_theme_json_data_default', array( $this, 'disable_classic_theme_styles' ) );
		
		// Disable Recent Comments Style
		add_action( 'widgets_init', array( $this, 'disable_recent_comments_style' ) );
	}

	/**
	 * Disable jQuery Migrate
	 *
	 * @param WP_Scripts $scripts Scripts object
	 */
	public function disable_jquery_migrate( $scripts ) {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_jquery_migrate'] && ! is_admin() ) {
			if ( isset( $scripts->registered['jquery'] ) ) {
				$script = $scripts->registered['jquery'];
				
				if ( $script->deps ) {
					$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
				}
			}
		}
	}

	/**
	 * Disable Emoji Scripts
	 */
	public function disable_emoji_scripts() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_emoji_scripts'] ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			
			add_filter( 'tiny_mce_plugins', array( $this, 'disable_emoji_tinymce' ) );
			add_filter( 'wp_resource_hints', array( $this, 'disable_emoji_dns_prefetch' ), 10, 2 );
		}
	}

	/**
	 * Disable emoji TinyMCE plugin
	 *
	 * @param array $plugins TinyMCE plugins
	 * @return array Modified plugins
	 */
	public function disable_emoji_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}
		return $plugins;
	}

	/**
	 * Disable emoji DNS prefetch
	 *
	 * @param array  $urls URLs to prefetch
	 * @param string $relation_type Relation type
	 * @return array Modified URLs
	 */
	public function disable_emoji_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
			$urls = array_diff( $urls, array( $emoji_svg_url ) );
		}
		return $urls;
	}

	/**
	 * Disable WordPress Embeds
	 */
	public function disable_embeds() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_embeds'] ) {
			wp_deregister_script( 'wp-embed' );
			
			remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
			remove_action( 'rest_api_init', 'wp_oembed_register_route' );
			remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'wp_oembed_add_host_js' );
			
			add_filter( 'embed_oembed_discover', '__return_false' );
			add_filter( 'tiny_mce_plugins', array( $this, 'disable_embeds_tiny_mce_plugin' ) );
			add_filter( 'rewrite_rules_array', array( $this, 'disable_embeds_rewrites' ) );
		}
	}

	/**
	 * Disable embeds TinyMCE plugin
	 *
	 * @param array $plugins TinyMCE plugins
	 * @return array Modified plugins
	 */
	public function disable_embeds_tiny_mce_plugin( $plugins ) {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	/**
	 * Disable embeds rewrite rules
	 *
	 * @param array $rules Rewrite rules
	 * @return array Modified rules
	 */
	public function disable_embeds_rewrites( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}

	/**
	 * Disable Admin Bar Scripts on frontend
	 */
	public function disable_admin_bar_scripts() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_admin_bar_scripts'] && ! is_user_logged_in() ) {
			wp_dequeue_style( 'admin-bar' );
			wp_dequeue_script( 'admin-bar' );
		}
	}

	/**
	 * Disable Dashicons for non-logged users
	 */
	public function disable_dashicons() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_dashicons'] && ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}

	/**
	 * Enable Selective Block Loading
	 */
	public function enable_selective_blocks() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['enable_selective_blocks'] ) {
			global $wp_styles;
			
			if ( ! is_singular() && ! is_page() ) {
				return;
			}
			
			$post = get_post();
			if ( ! $post ) {
				return;
			}
			
			$blocks = parse_blocks( $post->post_content );
			$block_names = $this->get_block_names_from_blocks( $blocks );
			
			// Essential blocks that may exist outside post content
			// These are typically in theme templates, headers, footers
			$essential_blocks = array(
				'navigation',        // Navigation menus
				'site-logo',        // Site logo
				'site-title',       // Site title
				'site-tagline',     // Site tagline
				'template-part',    // Template parts
				'post-navigation-link', // Post navigation
				'query',            // Query blocks (archives, loops)
				'post-template',    // Post template
				'avatar',           // User avatars
				'loginout',         // Login/logout link
			);
			
			// Remove block styles that aren't used
			foreach ( $wp_styles->registered as $handle => $style ) {
				if ( 0 === strpos( $handle, 'wp-block-' ) ) {
					$block_name = str_replace( 'wp-block-', '', $handle );
					
					// Skip essential blocks
					if ( in_array( $block_name, $essential_blocks, true ) ) {
						continue;
					}
					
					// Check if block is in content
					if ( ! in_array( $block_name, $block_names, true ) && ! in_array( 'core/' . $block_name, $block_names, true ) ) {
						wp_dequeue_style( $handle );
					}
				}
			}
		}
	}

	/**
	 * Get block names from parsed blocks
	 *
	 * @param array $blocks Parsed blocks
	 * @return array Block names
	 */
	private function get_block_names_from_blocks( $blocks ) {
		$block_names = array();
		
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$block_names[] = $block['blockName'];
				
				// Extract just the block type name
				$parts = explode( '/', $block['blockName'] );
				if ( count( $parts ) === 2 ) {
					$block_names[] = $parts[1];
				}
			}
			
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block_names = array_merge( $block_names, $this->get_block_names_from_blocks( $block['innerBlocks'] ) );
			}
		}
		
		return array_unique( $block_names );
	}

	/**
	 * Disable Global Styles
	 */
	public function disable_global_styles() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_global_styles'] ) {
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'wp-block-library-theme' );
		}
	}

	/**
	 * Disable Classic Theme Styles
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data
	 * @return WP_Theme_JSON_Data Modified theme JSON
	 */
	public function disable_classic_theme_styles( $theme_json ) {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_classic_theme_styles'] ) {
			remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
			
			$new_data = $theme_json->get_data();
			
			if ( isset( $new_data['styles'] ) ) {
				unset( $new_data['styles'] );
			}
			
			return new WP_Theme_JSON_Data( $new_data, 'default' );
		}
		
		return $theme_json;
	}

	/**
	 * Disable Recent Comments Style
	 */
	public function disable_recent_comments_style() {
		$settings = $this->get_settings();
		
		if ( '1' === $settings['disable_recent_comments_style'] ) {
			global $wp_widget_factory;
			
			if ( isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
				remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
			}
		}
	}
}
