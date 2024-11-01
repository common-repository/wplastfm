=== WPLastfm ===
Contributors: loeffler
Donate link: http://www.kosmonauten.cc/wordpress/wplastfm
Tags: lastfm, last.fm, music, widget
Requires at least: 2.9
Tested up to: 3.1
Stable tag: 1.1.1

Displays recent tracks from your last.fm account. Requires PHP 5!


== Description ==


Displays recent tracks from your last.fm account.

**Important: Requires PHP 5!**

**Features**

*   Easy to set up via widgets (requires Wordpress > 2.8)
*   Displays your current tracks (Listening now)
*   Customizable by template and css (you decide what is displayed)
*   Data is kept locally (default: 45 seconds)
*   English and German localization


== Installation ==


1. Upload folder `wplastfm` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Done


== Frequently Asked Questions ==


= How to create your own timeformat for tracks played more than 24 hours ago = 
* Go to the Widget page in Wordpress and choose 'Use self-defined' in the dropdown menu.
* Open the wplastfm-settings.php and edit the `$wplastfm_options['timeformat']` variable
* Done


== Screenshots ==


1. Example
2. Widget configuration options
3. Example


== Changelog ==


= 1.1.1 =
* Bugfix: The time format chose in the dropdown menu is now displayed correctly
* Added the possiblity to choose the size of the album cover (small, medium, large)
* Added the option to display or hide the link to your profile in the widget title
* Timeout for the HTTP request has been reduced (3 seconds for recent tracks, 2 seconds for artist image)

= 1.1.0 =
* Uses the Wordpress HTTP API instead of c_url
* Added template tags for %artist% and %title%
* Moved the cache files to /cache folder
* Now uses the artist image, if no album cover exists
* Some other small improvements and bugfixes.
* (The Wordpress HTTP API doesn't work properly in 2.8, so please update Wordpress)

= 1.0.6 =
* Bugfixes
* Tested up to Wordpress 2.9

= 1.0.5 =
* Fixed error message due PHP 5.3.0 curl bug

= 1.0.4 =
* Added a fixed height for the album cover
* Added the option to configure the maximum length of the title/album

= 1.0.3 =
* Fixed a problem with the right version number

= 1.0.2 =
* First stable release


== Integration ==


**If you are using widgets**

  *   Design → Widgets
  *   Drag the widget into your sidebar
  *   Configure your username, the number of shown tracks and the template
  *   Done


**If you are not using widgets**

Copy the following PHP code in your template:

`<?php wplastfm('username' [, number of tracks [, $options]]); ?>`

You can configure the plugin via "wplastfm-settings.php" or via array. E.g.

`<?php $options = array("template" => '%track%', "cache" => 45, "trunctrack" => 40, "truncalbum" => 25); ?>`


== Customization ==

The plugin provides the following CSS classes:

  *   ul.lastfm — the list
  *   ul.lastfm li — ul list elements
  *   ul.lastfm li a — last.fm links
  *   ul.lastfm li img — image formatting
  *   ul.lastfm li span.lastfm-time — time formatting
  *   ul.lastfm li span.lastfm-album — album formatting

If you don't need the `wplastfm.css`, you can delete it.


== Settings ==


You can change the following settings in the "wplastfm-settings.php" or via array.


  *   `$wplastfm_options['cache']` - Specifies how long the data is kept locally
  *   `$wplastfm_options['trunctrack']` / `$wplastfm_options['truncalbum']` / `$wplastfm_options['truncartist']` / `$wplastfm_options['trunctitle']`  - Maximum length of the text
  *   `$wplastfm_options['timeformat']` - Time format for songs played more than 24 hours ago
  *   `$wplastfm_options['imagesize']` - Image source (small, medium or large)
  *   `$wplastfm_options['albumcover_height']` - Height of the album cover
  *   `$wplastfm_options['useartistimage']` - If there is no album cover, the plugin uses the artist image instead (can be true or false)
  *   `$wplastfm_options['profilelink']` - Displays link to your profile or disables it.
  *   `$wplastfm_options['template']`


