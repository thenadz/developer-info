<?php
defined( 'WPINC' ) OR exit;

/*
  Plugin Name: WP Developer Info
  Plugin URI: http://wordpress.org/extend/plugins/developer-info/
  Description: Easy access to the WP.org Plugin & Theme APIs so that developers can showcase their work.
  Version: 1.0.2
  Requires at least: 2.8.0
  Author: Dan Rossiter
  Author URI: http://danrossiter.org/
  License: GPLv2 or later
  Text Domain: dev-info
 */

include_once plugin_dir_path( __FILE__ ) . 'class-item.php';
include_once plugin_dir_path( __FILE__ ) . 'class-plugin.php';
include_once plugin_dir_path( __FILE__ ) . 'class-theme.php';

add_shortcode( DeveloperInfo::SHORTCODE_PREFIX, array( 'DeveloperInfo', 'do_shortcode' ) );
add_action( 'wp_enqueue_scripts', array( 'DeveloperInfo', 'enqueue_styles' ) );

class DeveloperInfo {

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
	private static $defaults = array( 'author' => null, 'slug' => null, 'orderby' => 'name', 'order' => 'ASC', 'api' => array( 'plugins', 'themes' ) );

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
		$content = !empty($content) ? $content : self::DEFAULT_OUTPUT_FORMAT;
		self::$atts = shortcode_atts( self::$defaults, $atts );
		self::sanitize_atts();

		$plugins = self::get_items();

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
	 * Sanitize the attributes given.
	 */
	private static function sanitize_atts() {
		if ( is_string( self::$atts['api'] ) ) {
			self::$atts['api'] = explode( ',', self::$atts['api'] );
		}
		foreach ( self::$atts['api'] as &$api ) {
			$api = trim($api);
		}
		self::$atts['api'] = array_intersect( self::$atts['api'], self::$defaults['api'] );

		self::$atts['order'] = strtoupper( self::$atts['order'] );

		if ( !in_array( self::$atts['orderby'], DI_Item::get_shortcode_field_names() ) ) {
			self::$atts['orderby'] = self::$defaults['orderby'];
		}
	}

	/**
	 * Processes the nested shortcodes.
	 * @param $atts array The attributes passed to the shortcode.
	 * @param $content string|null The content of the nested shortcode.
	 * @param $shortcode string The shortcode that got us here. Needed to determine what to output.
	 * @return string The output from the nested shortcode.
	 */
	public static function do_nested_shortcode($atts = array(), $content = null, $shortcode = null) {
		$fields = DI_Item::get_shortcode_field_names();
		$field  = self::shortcode_suffix_to_field_name( substr( $shortcode, strlen( self::SHORTCODE_PREFIX ) + 1 ) );

		$ret = __( 'The requested shortcode is not recognized', 'dev-info' ) . ': ' . $shortcode;
		if ( in_array( $field, $fields ) ) {
			$ret = self::$plugin->$field;
		}

		return $ret;
	}

	/**
	 * Register nested shortcodes to be processed only once outer [dev-info] is reached.
	 */
	private static function register_nested_shortcodes() {
		foreach ( DI_Item::get_shortcode_field_names() as $field_name ) {
			add_shortcode(
				self::SHORTCODE_PREFIX . '-' . self::field_name_to_shortcode_suffix( $field_name ),
				array( __CLASS__, 'do_nested_shortcode' ) );
		}
	}

	/**
	 * Unregister nested shortcodes when not inside of [dev-info].
	 */
	private static function unregister_nested_shortcodes() {
		foreach ( DI_Item::get_shortcode_field_names() as $field_name ) {
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
	 * @return DI_Item[] The matched items from the plugin and theme APIs.
	 */
	private static function get_items() {
		$debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

		if ( ( ! isset( self::$atts['author'] ) && ! isset( self::$atts['slug'] ) ) || empty( self::$atts['api'] ) ) {
			return array();
		}

		$items = array();
		foreach ( self::$atts['api'] as $api ) {
			$transient_name = self::get_transient_name( $api );
			if ( $debug || !( $ret = get_transient( $transient_name ) ) ) {
				$options = array(
						'slug'     => true,
						'name'     => true,
						'version'  => true,
						'per_page' => null
				);
				if ( isset( self::$atts['author'] ) ) {
					$options['author'] = self::$atts['author'];
				}
				if ( isset( self::$atts['slug'] ) ) {
					$options['slug'] = self::$atts['slug'];
				}

				$method = "get_{$api}";
				$ret = self::$method( $options );

				// set cached value for later use
				if ( !$debug ) {
					set_transient( $transient_name, $ret, HOUR_IN_SECONDS * 6 );
				}
			}

			$items = array_merge( $items, $ret );
		}

		usort( $items, array( __CLASS__, 'cmp_items' ) );

		return $items;
	}

	/**
	 * @param $options mixed[] The query options.
	 *
	 * @return DI_Plugin[] The plugins returned from the WP.org API.
	 */
	private static function get_plugins( $options ) {
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$options['fields'] = DI_Plugin::get_api_fields();
		$resp = plugins_api( 'query_plugins', $options );
		$ret = array();
		if ( ! is_wp_error( $resp ) ) {
			foreach ( $resp->plugins as $plugin ) {
				$ret[] = new DI_Plugin( $plugin );
			}
		}

		return $ret;
	}

	/**
	 * @param $options mixed[] The query options.
	 *
	 * @return DI_Theme[] The themes returned from the WP.org API.
	 */
	private static function get_themes( $options ) {
		include_once ABSPATH . 'wp-admin/includes/theme.php';

		$options['fields'] = DI_Theme::get_api_fields();
		$resp = themes_api( 'query_themes', $options );
		$ret = array();
		if ( ! is_wp_error( $resp ) ) {
			foreach ( $resp->themes as $theme ) {
				$ret[] = new DI_Theme( $theme );
			}
		}

		return $ret;
	}

	/**
	 * @param $type string plugins or themes
	 *
	 * @return string The unique transient name based on type, author, and slug.
	 */
	private static function get_transient_name($type) {
		// transient name limited to 40 characters so use hash to record all info w/o exceeding size constraints
		$to_hash = '';
		if ( isset( self::$atts['author'] ) ) {
			$to_hash .= 'a=' . self::$atts['author'] .';';
		}
		if ( isset( self::$atts['slug'] ) ) {
			$to_hash .= 's=' . self::$atts['slug'] . ';';
		}

		// try to retrieve cached value first
		return "di_{$type}_" . hash( 'crc32', $to_hash );
	}

	/**
	 * @param $i1 DI_Item 1.
	 * @param $i2 DI_Item 2.
	 *
	 * @return int Order value used by usort.
	 */
	private static function cmp_items( $i1, $i2 ) {
		$v1 = $i1->{self::$atts['orderby']};
		$v2 = $i2->{self::$atts['orderby']};

		if ( self::$atts['orderby'] == 'version' ) {
			$ret = version_compare( $v1, $v2 );
		} else if ( is_string( $v1 ) ) {
			$ret = strcmp( $v1, $v2 );
		} else {
			$ret = $v1 - $v2;
		}

		return 'ASC' === self::$atts['order'] ? $ret : -$ret;
	}
}