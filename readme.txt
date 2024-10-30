=== Plugin Name ===
Contributors: mufasaagency
Tags: mufasa, slider
Requires at least: 4.9
Tested up to: 5.8
Stable tag: 1.6.2
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Created for better banner management, you can set banner entry and exit date and time with the ability to include link

== Description ==

Use shortcode to show all banners

do_shortcode('[mfs_slider]');

You can display banners with unique category, use this code below, where Header is category name

`do_shortcode('[mfs_slider Header]');`

== Installation ==

1. Upload 'mfs_slider' to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place <?php do_shortcode('[mfs_slider]'); ?> in your templates

== Changelog ==

= 1994.1 =
* First version

= 1994.2 =
* Feat: Added link to banner

= 1994.3 =
* Fix: wp_date() function for minor WordPress version

= 1994.4 =
* Feat: We changed the way to display the images, now we are using <picture> with srcset instead <img> with class

= 1994.5 =
* Fox: image for mobile and desktop (max-width and height)

= 1994.6 =
* Feat: You can reorder Slides using a Drag and Drop 😍

= 1994.7 =
* Fix: reorder

= 1994.8 =
* Refactor: Change Speed Animation
* Chore: Update Tiny Slider
* Chore: Update ACF

= 1994.9 =
* Fix: Elementor error when edit page