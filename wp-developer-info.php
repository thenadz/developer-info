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
	private static $defaults = array( 'author' => null, 'slug' => null, 'orderby' => 'name', 'order' => 'ASC' );

	/**
	 * @var array The args derived from values passed into shortcode combined with the defaults.
	 */
	private static $atts;

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
		self::$atts = shortcode_atts( self::$defaults, $atts );
		$content = !empty($content) ? $content : self::DEFAULT_OUTPUT_FORMAT;

		$plugins = self::get_plugins( self::$atts );
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
	 * @return DI_Plugin[] The plugins returned from the WP.org API.
	 */
	private static function get_plugins() {
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( ! isset( self::$atts['author'] ) && ! isset( self::$atts['slug'] ) ) {
			return array();
		}

		$options = array(
			'slug'     => true,
			'name'     => true,
			'version'  => true,
			'per_page' => self::FIVE_NINES,
			'fields'   => array_fill_keys( DI_Plugin::get_field_names(), true )
		);

		// transient name limited to 40 characters so use hash to record all info w/o exceeding size constraints
		$to_hash = '';
		if ( isset( self::$atts['author'] ) ) {
			$options['author'] = self::$atts['author'];
			$to_hash .= 'a=' . self::$atts['author'] .';';
		}
		if ( isset( self::$atts['slug'] ) ) {
			$options['slug'] = self::$atts['slug'];
			$to_hash .= 's=' . self::$atts['slug'] . ';';
		}

		// try to retrieve cached value first
		$transient_name = 'di_plugin_' . hash( 'crc32', $to_hash );
		if ( ( !defined( 'WP_DEBUG' ) || !WP_DEBUG ) && ( $ret = get_transient( $transient_name ) ) ) {
			return $ret;
		}

		$resp = plugins_api( 'query_plugins', $options );
		$ret = array();
		if ( ! is_wp_error( $resp ) ) {
			usort( $resp->plugins, array( __CLASS__, 'cmp_plugins' ) );
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

	/**
	 * @param $p1 Plugin 1.
	 * @param $p2 Plugin 2.
	 * @return int Order value used by usort.
	 */
	private static function cmp_plugins( $p1, $p2 ) {
		$v1 = $p1->{self::$atts['orderby']};
		$v2 = $p2->{self::$atts['orderby']};
		if (is_string( $v1 ) ) {
			$ret = strcmp( $v1, $v2 );
		} else {
			$ret = $v1 - $v2;
		}

		return 'ASC' === strtoupper( self::$atts['order'] ) ? $ret : -$ret;
	}
}