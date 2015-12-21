<?php
defined( 'WPINC' ) OR exit;

class DI_Theme extends DI_Item {

	/**
	 * @var string The URL of the first screenshot.
	 */
	public $screenshot_url;

	/**
	 * Weird. Only exists in requests (not resp). Modifies the resp author value to be array with values we need.
	 * @var void Never actually used other than reflectively getting the field name.
	 */
	public $extended_author;

	/**
	 * Initialize the $icon field.
	 */
	protected function init_icon() {
		$this->icon = "<img class='icon theme-icon' src='$this->screenshot_url' alt='$this->name Screenshot' />";
	}

	/**
	 * Initialize author and author_profile fields.
	 */
	protected function init_author() {
		$this->author_profile = '//profiles.wordpress.org/' . $this->author->user_nicename;
		$this->author = $this->author->display_name;
	}

	/**
	 * Initialize short_description field.
	 */
	protected function init_short_description() {
		$this->short_description = substr( $this->description, 0, 150 );
	}
}