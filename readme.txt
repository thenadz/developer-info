=== WP Developer Info ===
Contributors: dan.rossiter
Tags: developer, developers, plugin, theme, info, api
Requires at least: 2.8.0
Tested up to: 4.4
Stable tag: 0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin provides easy access to the WordPress.org Plugin & Theme Info APIs
so that WP.org developers can showcase their work.

== Description ==

Plugin API support developed, themes API forthcoming. `[dev-info author=<author slug> slug=<plugin slug>]`
(both attributes are optional, but at least one must be given). Fully customizable
output through use of nested shortcodes (see `DEFAULT_OUTPUT_FORMAT` in wp-developer-info.php for example).

Better documentation in the works.

== Installation ==

1. Upload `wp-developer-info` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dev-info slug=<plugin slug> author=<author slug>]` in any post or page were you want to
embed part of your information. The field specified will be included inline.

== Screenshots ==

1. Sample default output using `[dev-info author=danrossiter]`.

== Changelog ==

= Coming Soon! =

* More shorttag options
* Widget options (both frontend & admin dashboard)
* Caching options for accessing the API less often
* Supporting Theme Info API (currently not included)
* Much, much more!

= 0.8 =
* Complete rewrite. Now supports customizable output using various nested shortcodes. Documentation is
  forthcoming, but see documented source in wp-developer-info for usage examples.
* Theme support along with full documentation will be out by year's end with the release of 1.0. Yay! :)

= 0.2 =
* Initial release
* *Very* early beta
* Supports querying the Plugin Info API for an individual plugin slug, but not
other functions provided by the API
