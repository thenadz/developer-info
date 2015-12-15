<?php
defined( 'WPINC' ) OR exit;

/*
  Plugin Name: WP Developer Info
  Plugin URI: http://wordpress.org/extend/plugins/developer-info/
  Description: Easy access to the WP.org Plugin & Theme APIs so that developers can showcase their work.
  Version: 0.8
  Requires at least: 2.8.0
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2 or later
  Text Domain: dev-info
 */

include_once plugin_dir_path( __FILE__ ) . 'class-plugin.php';

add_shortcode( DeveloperInfo::SHORTCODE_PREFIX, array( 'DeveloperInfo', 'do_shortcode' ) );
add_action( 'wp_enqueue_scripts', array( 'DeveloperInfo', 'enqueue_styles' ) );

class DeveloperInfo {

	/**
	 * Intended to represent "infinity" when querying the WP.org APIs.
	 */
	const FIVE_NINES = 99999;

	/**
	 * All shortcodes supported by this plugin begin with the following.
	 */
	const SHORTCODE_PREFIX = 'dev-info';

	/**
	 * When no shortcode content is given, output will be formatted per plugin/theme as follows.
	 */
	const DEFAULT_OUTPUT_FORMAT = '
		<div class="developer-info">
			<a href="[dev-info-homepage]" target="_blank">[dev-info-icon]</a>
			<div class="title">
				<h3><a href="[dev-info-homepage]" target="_blank" />[dev-info-name]</a></h3>
				<span class="stars">[dev-info-stars]</span> <span class="ratings">([dev-info-num-ratings])</span>
			</div>
			<p class="description">[dev-info-short-description]</p>
		</div>';

	/**
	 * @var array Default values for shortcode attributes.
	 */
	private static $defaults = array( 'author' => null, 'slug' => null );

	/**
	 * @var DI_Plugin Used to maintain state while processing nested shortcodes.
	 */
	private static $plugin = null;

	/**
	 * Enqueue styling for default output.
	 */
	public static function enqueue_styles() {
		wp_enqueue_style( 'dev-info-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
	}

	/**
	 * @param $atts array The attributes passed to the shortcode.
	 * @param $content string|null The content of the shortcode, or null if none given.
	 * @return string The shortcode output, after processing any nested shortcodes.
	 */
	public static function do_shortcode($atts, $content = null) {
		$ret = '<!-- ' . __( 'No plugins matched.', 'dev-info' ) . ' -->';
		$atts = shortcode_atts( self::$defaults, $atts );
		$content = !empty($content) ? $content : self::DEFAULT_OUTPUT_FORMAT;

		$plugins = self::get_plugins($atts);
		if ( count( $plugins ) ) {
			$ret = '';
			self::register_nested_shortcodes();

			foreach ( $plugins as $plugin ) {
				self::$plugin = $plugin;
				$ret .= do_shortcode( $content );
			}

			self::unregister_nested_shortcodes();
		}

		return $ret;
	}

	/**
	 * Processes the nested shortcodes.
	 * @param $atts array The attributes passed to the shortcode.
	 * @param $content string|null The content of the nested shortcode.
	 * @param $shortcode string The shortcode that got us here. Needed to determine what to output.
	 * @return string The output from the nested shortcode.
	 */
	public static function do_nested_shortcode($atts = array(), $content = null, $shortcode = null) {
		$fields = DI_Plugin::get_field_names();
		$field  = self::shortcode_suffix_to_field_name( substr( $shortcode, strlen( self::SHORTCODE_PREFIX ) + 1 ) );

		$ret = 'The requested shortcode is not recognized: ' . $shortcode;
		if ( in_array( $field, $fields ) ) {
			$ret = self::$plugin->$field;
		}

		return $ret;
	}

	/**
	 * Register nested shortcodes to be processed only once outer [dev-info] is reached.
	 */
	private static function register_nested_shortcodes() {
		foreach ( array_diff( DI_Plugin::get_field_names(), DI_Plugin::get_no_shortcode_fields() ) as $field_name ) {
			add_shortcode(
				self::SHORTCODE_PREFIX . '-' . self::field_name_to_shortcode_suffix( $field_name ),
				array( 'DeveloperInfo', 'do_nested_shortcode' ) );
		}
	}

	/**
	 * Unregister nested shortcodes when not inside of [dev-info].
	 */
	private static function unregister_nested_shortcodes() {
		foreach ( array_diff( DI_Plugin::get_field_names(), DI_Plugin::get_no_shortcode_fields() ) as $field_name ) {
			remove_shortcode( self::SHORTCODE_PREFIX . '-' . self::field_name_to_shortcode_suffix( $field_name ) );
		}
	}

	/**
	 * @param $suffix string The shortcode suffix.
	 * @return string The field name.
	 */
	private static function shortcode_suffix_to_field_name($suffix) {
		return str_replace( '-', '_', $suffix );
	}

	/**
	 * @param $field_name string The field name
	 * @return string The shortcode suffix.
	 */
	private static function field_name_to_shortcode_suffix($field_name) {
		return str_replace( '_', '-', $field_name );
	}

	/**
	 * @param $args array The args passed to the outer [dev-info] shortcode.
	 * @return DI_Plugin[] The plugins returned from the WP.org API.
	 */
	private static function get_plugins($args) {
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! isset( $args['author'] ) && ! isset( $args['slug'] ) ) {
			return array();
		}

		$options = array(
			'slug'     => true,
			'name'     => true,
			'version'  => true,
			'per_page' => self::FIVE_NINES,
			'fields'   => array_fill_keys( DI_Plugin::get_field_names(), true )
		);

		// transient name limited to 40 characters so limit accordingly
		$transient_name = 'di';
		if ( isset( $args['author'] ) ) {
			$options['author'] = $args['author'];
			$transient_name .= 'a' . substr( $args['author'], 12 ) .'_';
		}
		if ( isset( $args['slug'] ) ) {
			$options['slug'] = $args['slug'];
			$transient_name .= 's' . substr( $args['slug'], 23 ) . '_';
		}

		// try to retrieve cached value first
		$transient_name = substr( $transient_name, 0, -1 );
		if ( ( !defined( 'WP_DEBUG' ) || !WP_DEBUG ) && ( $ret = get_transient( $transient_name ) ) ) {
			return $ret;
		}

		$resp = plugins_api( 'query_plugins', $options );
		$ret = array();
		if ( ! is_wp_error( $resp ) ) {
			foreach ( $resp->plugins as $plugin ) {
				$ret[$plugin->slug] = new DI_Plugin( $plugin );
			}
		}

		// set cached value for later use
		if ( !defined( 'WP_DEBUG' ) || !WP_DEBUG ) {
			set_transient( $transient_name, $ret, HOUR_IN_SECONDS * 6 );
		}

		return $ret;
	}
}