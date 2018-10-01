<?php
/**
 * Implements wp_enqueue_scripts to pull in the parent theme's scripts and
 * stylesheets (since this is a twentyseventeen with very light customization).
 */
function travelogue_theme_enqueue_parent_styles() {
  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'travelogue_theme_enqueue_parent_styles', 5 );

/**
 * Implements wp_enqueue_scripts to register scripts/styles for my overrides.
 * Currently this is only CSS and JS for Colorbox, so we'll only actually
 * enqueue this stuff on post_gallery filter.
 */
function travelogue_theme_register_own_scripts_and_styles() {
  wp_register_script('travelogue-js-media', get_stylesheet_directory_uri() . '/js/media.js', array('colorbox-script', 'jquery'), false, true);
}
add_action( 'wp_enqueue_scripts', 'travelogue_theme_register_own_scripts_and_styles', 20 );
// On the post_gallery filter's execution, enqueue our scripts, but return
// nothing so that WP's own gallery builder will still run. My JS is written for
// its output.
add_filter('post_gallery', function() { wp_enqueue_script('travelogue-js-media'); wp_enqueue_style('colorbox-style'); });

/**
 * Implements wp_enqueue_scripts to register the scripts and styles for the
 * Colorbox lightbox library which will attach to all image galleries.
 *
 * @TODO: Can this be executed only when needed? Also replace with PhotoSwipe
 */
function travelogue_theme_register_lightbox() {
  wp_register_style( 'colorbox-style', get_stylesheet_directory_uri() . '/vendor/colorbox/example2/colorbox.css', array());
  wp_register_script('colorbox-script', get_stylesheet_directory_uri() . '/vendor/colorbox/jquery.colorbox.js', array('jquery'), false, true);

  wp_add_inline_style('colorbox-style', "#cboxWrapper button {transition: none !important; filter: invert(100%);}");
  wp_add_inline_style('colorbox-style', "#cboxOverlay {background: black;}");
}
add_action( 'wp_enqueue_scripts', 'travelogue_theme_register_lightbox', 10 );
