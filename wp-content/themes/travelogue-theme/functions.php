<?php
/**
 * Implements wp_enqueue_scripts to pull in the parent theme's scripts and
 * stylesheets (since this is a twentyseventeen with very light customization).
 */
function travelogue_theme_enqueue_parent_styles() {
  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'travelogue_theme_enqueue_parent_styles' );

