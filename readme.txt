=== WP Developer Info ===
Contributors: dan.rossiter
Tags: developer, developers, plugin, theme, info, api
Requires at least: 2.8.0
Tested up to: 4.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

You worked hard developing your plugins & themes for the WP.org community. Don't you think you should be able
to show off your hard work?

== Description ==

You worked hard developing your plugins & themes for the WP.org community. Don't you think you should be able
to show off your hard work? I did, so I threw together this plugin which will poll the WP.org plugin &
theme APIs and dynamically display all of your hard work. Unlike some similar plugins, there is no page scraping
happening here (eww!).

Usage could not be more easy. The simplest usage is the following shortcode: `[dev-info author=<author slug>]`.
Advanced usage is documented below.

= [dev-info] Arguments =
* **author:** The author slug. This is the same value at the end of your WP.org profile (eg: *https://profiles.wordpress.org/**danrossiter**/*).
* **slug:** The plugin or theme slug to be retrieved (useful if you just want to display info about a single plugin).
* **api:** Optional. This indicates whether to query the plugins API, the themes API, or both ("plugins", "themes", "plugins,themes").
  The default is both, but if you only want one then you should explicitly set this value to avoid making two HTTP
  calls from your server.
* **orderby:** Field to order by (eg: name, slug, downloaded). Any field with an associated shortcode may be ordered against,
  though some such as `stars` might not make a whole lot of sense to order by.
* **order:** Ascending or descending sort (ASC or DESC).

The `[dev-info]` shortcode supports a number of nested shortcodes allowing complete customization of the output generated.
An example of this is the following (this is infact the default output format):
`<div class="developer-info">
    <a href="[dev-info-homepage]" target="_blank">[dev-info-icon]</a>
    <div class="title">
        <h3><a href="[dev-info-homepage]" target="_blank" />[dev-info-name]</a></h3>
        <span class="stars">[dev-info-stars]</span> <span class="ratings">([dev-info-num-ratings])</span>
    </div>
    <p class="description">[dev-info-short-description]</p>
</div>`

* **[dev-info-name]:** The name of the plugin/theme.
* **[dev-info-slug]:** The slug identifying the plugin/theme.
* **[dev-info-description]:** The full description.
* **[dev-info-short-description]:** The short description. For themes this is the first 150 characters of the description.
* **[dev-info-version]:** The current version of the plugin/theme.
* **[dev-info-author]:** The name of the author.
* **[dev-info-author-profile]:** The URL for the author's WP.org profile.
* **[dev-info-active-installs]:** The number of active installs for the plugin/theme.
* **[dev-info-rating]:** Percent rating of the plugin/theme.
* **[dev-info-num-ratings]:** The number of users that have rated the plugin/theme.
* **[dev-info-downloaded]:** The number of downloads for the plugin/theme.
* **[dev-info-downloadlink]:** The download link for the plugin/theme.
* **[dev-info-last-updated]:** The last time the plugin/theme was updated.
* **[dev-info-homepage]:** The homepage of the plugin/theme.
* **[dev-info-icon]:** The IMG tag containing the icon for plugins and the first screenshot for themes.
* **[dev-info-stars]:** The rating represented in stars (same as what is displayed on the WP.org profile).

== Installation ==

1. Upload `wp-developer-info` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `[dev-info slug=<plugin slug> author=<author slug>]` in any post or page were you want to
embed part of your information. The field specified will be included inline.

== Screenshots ==

1. Sample default output using `[dev-info author=danrossiter]`.

== Changelog ==

= 1.0.2 =
* **Bug Fix:** Fixed broken ordering for numeric values.

= 1.0.1 =
* **Bug Fix:** Removing inadvertently-promoted debug code.

= 1.0 =
* **Enhancement:** Adding themes API support.
* **Enhancement:** Documenting functionality.
* **Yay! We're out of beta!**

= 0.8.1 =
* Minor tweak to how cached API results are persisted.

= 0.8 =
* Complete rewrite. Now supports customizable output using various nested shortcodes. Documentation is
  forthcoming, but see documented source in wp-developer-info for usage examples.
* Theme support along with full documentation will be out by year's end with the release of 1.0. Yay! :)

= 0.2 =
* Initial release
* *Very* early beta
* Supports querying the Plugin Info API for an individual plugin slug, but not
other functions provided by the API
