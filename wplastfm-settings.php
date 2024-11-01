<?php
/*
 * Most of the options can be changed in the Wordpress Widget back end, except of 'cache' and 'useartistimage'.
 * If you want to use your own timeformat, you have to choose 'Use self-defined' in the widget back end and edit your own timeformat in this file.
 * 
 *
 * Specifies how long the data is kept locally
 * 0 = disabled  (not recommended)
 */
$wplastfm_options['cache'] = 45; // in seconds


/**
 * If there is no album cover, the plugin uses the artist image.
 * This requires an additional API request and may slow performance.
 * If you don't want to display the artist image you can set it to false.
 */
$wplastfm_options['useartistimage'] = true;


/*
 * Maximum length of the text
 * 0 = no truncation
 * 
 *  Only change if the plugin isn't used as a widget
 */
$wplastfm_options['trunctrack'] = 0; // max length of %title%
$wplastfm_options['truncalbum'] = 0; // max length of %album%
$wplastfm_options['truncartist'] = 0; // max length of %artist%
$wplastfm_options['trunctitle'] = 0; // max length of %title%


/**
 * Time format for songs played more than 24 hours ago
 */
$wplastfm_options['timeformat'] = "d.m, H:i";


/**
 * Display link to your Last.fm profile in the widget title (true), or disable it (false)
 */
$wplastfm_options['profilelink'] = true;


/**
 * Image source
 * small: 32x32 pixel
 * medium: 64x64 pixel
 * large: 126x126 pixel
 */
$wplastfm_options['imagesize'] = "medium";

/**
 * Height of the album cover image (depending on your source image)
 */
$wplastfm_options['albumcover_height'] = "32";


/** 
 * Only change if the plugin isn't used as a widget
 * Tags: %track%, %artist%, %title%, %time%, %album%, %img%
 */
$wplastfm_options['template'] = "%track%<br/>%time% %album%";
?>
