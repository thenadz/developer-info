<?php
defined( 'WPINC' ) OR exit;

class DI_Plugin {

	/**
	 * @return string[] The public field names.
	 */
	public static function get_field_names() {
		return array_map( array( __CLASS__, 'get_name_from_property' ), self::get_fields() );
	}

	/**
	 * @return string[] TODO
	 */
	public static function get_excluded_field_names() {
		return array( 'compatibility' );
	}

	/**
	 * @return string[] The fields for which no shortcode should be generated.
	 */
	public static function get_no_shortcode_fields() {
		return array( 'icons', 'banners' );
	}

	/**
	 * Construct new instance of DI_Plugin.
	 */
	public function __construct($plugin) {
		foreach ( self::get_fields() as $field ) {
			if ( isset( $plugin->{$field->name} ) ) {
				$field->setValue( $this, $plugin->{$field->name} );
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
	 * @var string The current released version.
	 */
	public $version;

	/**
	 * @var string The author of the plugin, wrapped in an anchor tag pointing to their homepage.
	 */
	public $author;

	/**
	 * @var string The URL pointing to the author's WP.org profile.
	 */
	public $author_profile;

	/**
	 * @var string The full plugin description.
	 */
	public $description;

	/**
	 * @var string The short description of the plugin.
	 */
	public $short_description;

	/**
	 * @var string[] plugin readme sections: description, installation, FAQ, screenshots, other notes, and changelog. Keys are the section names.
	 */
	//public $sections;

	/**
	 * @var string The 'Compatible up to' value.
	 */
	public $tested;

	/**
	 * @var string The required WordPress version.
	 */
	public $requires;

	/**
	 * @var int Rating in percent and total number of ratings.
	 */
	public $rating;

	/**
	 * @var int[] The number of rating for each star (1-5).
	 */
	public $ratings;

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
	//public $downloadlink;

	/**
	 * @var string The date of the last plugin update.
	 */
	public $last_updated;

	/**
	 * @var string The date the plugin was released.
	 */
	public $added;

	/**
	 * @var string[] The plugin tags.
	 */
	public $tags;

	/**
	 * @var string The HTML for the star control as used on WP.org plugin profiles.
	 */
	public $stars;

	/**
	 * @var array[] {
	 *     The list of reported compatibilities for each release. Keys are WP version.
	 *
	 *     @type array[] {
	 *         The list of plugin versions. Keys are plugin versions.
	 *
	 *         @type int
	 *         @type int
	 *         @type int
	 *     }
	 * }
	 */
	//public $compatibility;

	/**
	 * @var string The plugin's homepage URL.
	 */
	public $homepage;

	/**
	 * @var string[] All versions that have been released for the plugin.
	 */
	//public $versions;

	/**
	 * @var string The URL where donations can be made for the plugin.
	 */
	public $donate_link;

	/**
	 * @var string[] The reviews for the plugin.
	 */
	//public $reviews;

	/**
	 * @var string[] Links to the plugin banner(s).
	 */
	public $banners;

	/**
	 * @var string[] {
	 *     The plugin icon(s).
	 *
	 *     @type string $default Base64-encoded default icon if no icon is given by plugin. Will only exist if the latter values do not.
	 *
	 *     @type string $svg The URL pointing to the SVG plugin icon.
	 *     @type string $2x The URL pointing to the 2x plugin icon.
	 *     @type string $1x The URL pointing to the 1x plugin icon.
	 * }
	 */
	public $icons;

	/**
	 * @var string The IMG tag representing the auto-selected icon from $icons.
	 */
	public $icon;

	/**
	 * @var int The number of active installs.
	 */
	public $active_installs;

	/**
	 * @var string The assigned group.
	 */
	//public $group;

	/**
	 * @var array[] {
	 *     The WP.org users that have contributed to the plugin.
	 *
	 *     @type string $profile The URL pointing to the contributor's WP.org profile.
	 *     @type string $avatar The URL pointing to the contributor's avatar.
	 * }
	 */
	//public $contributors;

	private function init_stars() {
		static $full_star = '<span class="dashicons dashicons-star-filled"></span>';
		static $half_star = '<span class="dashicons dashicons-star-half"></span>';
		static $empty_star = '<span class="dashicons dashicons-star-empty"></span>';

		$this->stars = '';
		for ( $i = 0; $i < 5; $i++ ) {
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
	 * @return string HTML IMG tag for best icon available.
	 */
	private function init_icon() {
		if ( !empty( $this->icons['svg'] ) ) {
			$src = $this->icons['svg'];
		} elseif ( !empty( $this->icons['2x'] ) ) {
			$src = $this->icons['2x'];
		} elseif ( !empty( $this->icons['1x'] ) ) {
			$src = $this->icons['1x'];
		} else {
			$src = $this->icons['default'];
		}

		$this->icon = "<img class='plugin-icon' src='$src' alt='$this->name Icon' />";
	}

	/**
	 * @var ReflectionClass
	 */
	private static $reflection_class;

	/**
	 * @return ReflectionClass
	 */
	private static function get_reflection_class() {
		if ( !isset( self::$reflection_class ) ) {
			self::$reflection_class = new ReflectionClass(__CLASS__);
		}

		return self::$reflection_class;
	}

	/**
	 * @var ReflectionProperty[] The instance fields for this class.
	 */
	private static $fields;

	/**
	 * @return ReflectionProperty[]
	 */
	private static function get_fields() {
		if ( !isset( self::$fields ) ) {
			$ref = self::get_reflection_class();
			self::$fields = $ref->getProperties( ReflectionProperty::IS_PUBLIC & ~ReflectionProperty::IS_STATIC );
		}

		return self::$fields;
	}

	/**
	 * @var ReflectionMethod[] The instance functions prefixed by "init_"
	 */
	private static $init_functions;

	/**
	 * @return ReflectionMethod[] The instance functions prefixed by "init_"
	 */
	private static function get_init_functions() {
		if ( !isset( self::$init_functions ) ) {
			$ref = self::get_reflection_class();
			self::$init_functions = array_filter(
					$ref->getMethods( ReflectionMethod::IS_PRIVATE & ~ReflectionProperty::IS_STATIC ),
					array( __CLASS__, 'is_init_function' ) );
		}

		return self::$init_functions;
	}

	/**
	 * @param $function ReflectionFunction
	 * @return bool Whether the given function is prefixed by "init_"
	 */
	private static function is_init_function($function) {
		return 'init_' === substr( $function->name, 0, 5 ) && $function->getNumberOfRequiredParameters() == 0;
	}

	/**
	 * @param $property ReflectionProperty The property to extract name from.
	 */
	private static function get_name_from_property($property ) {
		return $property->name;
	}
}