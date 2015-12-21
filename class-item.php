<?php
defined( 'WPINC' ) OR exit;

abstract class DI_Item {

	/**
	 * Construct new instance of DI_Item.
	 *
	 * @param $item object The plugin item returned from the WP.org API.
	 */
	public function __construct( $item ) {
		foreach ( self::get_fields() as $field ) {
			if ( isset( $item->{$field->name} ) ) {
				$field->setValue( $this, $item->{$field->name} );
			}
		}

		foreach ( self::get_init_functions() as $function ) {
			$function->setAccessible( true );
			$function->invoke( $this );
		}
	}

	/**
	 * @var string The name of the plugin.
	 */
	public $name;

	/**
	 * @var string The uniquely-identifying slug for the plugin.
	 */
	public $slug;

	/**
	 * @var string The full plugin description.
	 */
	public $description;

	/**
	 * @var string The short description of the plugin.
	 */
	public $short_description;

	/**
	 * @var string The current released version.
	 */
	public $version;

	/**
	 * @var string The author of the plugin.
	 */
	public $author;

	/**
	 * @var string The URL to the author profile.
	 */
	public $author_profile;

	/**
	 * @var int The number of active installs.
	 */
	public $active_installs;

	/**
	 * @var int Rating in percent and total number of ratings.
	 */
	public $rating;

	/**
	 * @var int The number of ratings for the plugin.
	 */
	public $num_ratings;

	/**
	 * @var int The downloaded count.
	 */
	public $downloaded;

	/**
	 * @var string The plugin download link.
	 */
	public $downloadlink;

	/**
	 * @var string The date of the last plugin update.
	 */
	public $last_updated;

	/**
	 * @var string The plugin's homepage URL.
	 */
	public $homepage;

	/**
	 * Dummy property derived from DI_Plugin->$icons OR DI_Theme->$screenshot_url.
	 * @var string The IMG tag representing the auto-selected icon from $icons.
	 */
	public $icon;

	/**
	 * Dummy property derived from $rating.
	 * @var string The HTML for the star control as used on WP.org plugin profiles.
	 */
	public $stars;

	/**
	 * @return array The fields to be requested from the API.
	 */
	public static function get_api_fields() {
		return array_fill_keys( array_diff( self::get_field_names(), self::get_dummy_fields() ), true ) +
		       array_fill_keys( self::get_api_excluded_field_names(), false );
	}

	/**
	 * @return array The fields to be exposed via nested shortcodes.
	 */
	public static function get_shortcode_field_names() {
		return self::get_field_names( __CLASS__ );
	}

	/**
	 * Initialize the $icon field.
	 */
	protected abstract function init_icon();

	/**
	 * Initialize the $stars field.
	 */
	private function init_stars() {
		static $full_star = '<span class="dashicons dashicons-star-filled"></span>';
		static $half_star = '<span class="dashicons dashicons-star-half"></span>';
		static $empty_star = '<span class="dashicons dashicons-star-empty"></span>';

		$this->stars = '';
		for ( $i = 0; $i < 5; $i ++ ) {
			if ( ( $i * 20 ) + 15 <= $this->rating ) {
				$this->stars .= $full_star;
			} elseif ( ( $i * 20 ) + 5 <= $this->rating ) {
				$this->stars .= $half_star;
			} else {
				$this->stars .= $empty_star;
			}
		}
	}

	/**
	 * @var ReflectionClass[] ReflectionClass for all children of this class.
	 */
	private static $reflection_class;

	/**
	 * @return ReflectionClass The ReflectionClass for the calling class.
	 */
	private static function get_reflection_class() {
		$clazz = get_called_class();
		if ( empty( self::$reflection_class[ $clazz ] ) ) {
			self::$reflection_class[ $clazz ] = new ReflectionClass( $clazz );
		}

		return self::$reflection_class[ $clazz ];
	}

	/**
	 * @var ReflectionProperty[][] The instance fields for all children of this class.
	 */
	private static $fields;

	/**
	 * @param string|null $clazz The class name.
	 *
	 * @return ReflectionProperty[] The instance fields for this class.
	 */
	private static function get_fields( $clazz = null ) {
		if ( is_null( $clazz ) ) {
			$clazz = get_called_class();
		}

		if ( ! isset( self::$fields[ $clazz ] ) ) {
			$ref                    = self::get_reflection_class();
			self::$fields[ $clazz ] = $ref->getProperties( ReflectionProperty::IS_PUBLIC );
		}

		return self::$fields[ $clazz ];
	}

	/**
	 * @var ReflectionMethod[][] The instance functions prefixed by "init_"
	 */
	private static $init_functions;

	/**
	 * @return ReflectionMethod[] The instance functions prefixed by "init_"
	 */
	private static function get_init_functions() {
		$clazz = get_called_class();
		if ( ! isset( self::$init_functions[ $clazz ] ) ) {
			$ref                            = self::get_reflection_class();
			self::$init_functions[ $clazz ] = array_filter(
				$ref->getMethods( ReflectionMethod::IS_PRIVATE | ReflectionMethod::IS_PROTECTED ),
				array( __CLASS__, 'is_init_function' ) );
		}

		return self::$init_functions[ $clazz ];
	}

	/**
	 * @param $function ReflectionFunction
	 *
	 * @return bool Whether the given function is prefixed by "init_"
	 */
	private static function is_init_function( $function ) {
		return 'init_' === substr( $function->name, 0, 5 ) && $function->getNumberOfRequiredParameters() == 0;
	}

	/**
	 * @param $property ReflectionProperty The property to extract name from.
	 */
	private static function get_name_from_property( $property ) {
		return $property->name;
	}

	/**
	 * @param string|null $clazz The class name.
	 *
	 * @return string[] The public field names.
	 */
	private static function get_field_names( $clazz = null ) {
		return array_map( array( __CLASS__, 'get_name_from_property' ), self::get_fields( $clazz ) );
	}

	/**
	 * @return array The fields dynamically generated and not part of the WP.org API response.
	 */
	protected static function get_dummy_fields() {
		return array( 'stars', 'icon' );
	}

	/**
	 * @return string[] The fields we don't want from the API.
	 */
	protected static function get_api_excluded_field_names() {
		return array( 'compatibility', 'tags', 'ratings' );
	}
}