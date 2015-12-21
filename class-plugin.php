<?php
defined( 'WPINC' ) OR exit;

class DI_Plugin extends DI_Item {

	/**
	 * Construct new instance of DI_Plugin.
	 *
	 * @param $item object The plugin item returned from the WP.org API.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );
	}

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
	 * @return string HTML IMG tag for best icon available.
	 */
	protected function init_icon() {
		if ( !empty( $this->icons['svg'] ) ) {
			$src = $this->icons['svg'];
		} elseif ( !empty( $this->icons['2x'] ) ) {
			$src = $this->icons['2x'];
		} elseif ( !empty( $this->icons['1x'] ) ) {
			$src = $this->icons['1x'];
		} else {
			$src = $this->icons['default'];
		}

		$this->icon = "<img class='icon plugin-icon' src='$src' alt='$this->name Icon' />";
	}

	/**
	 * Cleanup author to be uniform format between Plugins & Themes API.
	 */
	protected function init_author() {
		$this->author = preg_replace( '#^<a [^>]+>([^<]*)</a>$#', '$1', $this->author );
	}
}