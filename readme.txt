=== WP Developer Info ===
Contributors: dan.rossiter
Tags: developer, developers, plugin, theme, info, api
Description: This plugin provides easy access to the WordPress.org Plugin & Theme Info APIs.
Tested up to: 3.5
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin provides easy access to the WordPress.org Plugin & Theme Info APIs
so that WP.org developers can showcase their work.

= Description =

**NOTICE: This plugin is in the *painful-to-use* stage of beta. Unless you are
extremely daring, I recommend holding off on downloading until it has made
some progress. You've been warned.**

Unlike the other plugins floating around that achieve similar goals, this
plugin does **not** page scrape. Instead, this plugin directly accesses the
Plugin & Theme Info API, making it put less load on your server and provide
much more flexibility. Additionally, since the plugin does not depend on the
format of the HTML pages that page scrapers use, it is far more reliable
long-term.

== Installation ==

1. Upload `wp-developer-info` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dinfo slug=<plugin-slug> field=<Field Name>]` in any post or page were you want to
embed part of your information. The field specified will be included inline.

= Field Options =

Coming soon. In the interim, [see
here](http://dd32.id.au/projects/wordpressorg-plugin-information-api-docs/).

== Changelog ==

= Coming Soon! =

* More shorttag options
* Widget options (both frontend & admin dashboard)
* Caching options for accessing the API less often
* Supporting Theme Info API (currently not included)
* Much, much more!

= 0.2 =

* Initial release
* *Very* early beta
* Supports querying the Plugin Info API for an individual plugin slug, but not
other functions provided by the API
