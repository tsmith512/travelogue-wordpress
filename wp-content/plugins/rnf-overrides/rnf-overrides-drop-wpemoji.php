<?php

/**
 * @file Drop the WP Emoji crap. This is pulled wholesale from a blog post at
 * Kinsta because apparently there's not a simple way to do this, it involves
 * pulling out a ton of actions and filters to do it completely.
 *
 * See: https://kinsta.com/knowledgebase/disable-emojis-wordpress/
 */

/**
 * Disable the emoji's
 */
function rnf_disable_emojis() {
  remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
  remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
  remove_action( 'wp_print_styles', 'print_emoji_styles' );
  remove_action( 'admin_print_styles', 'print_emoji_styles' );
  remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
  remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
  remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
  add_filter( 'tiny_mce_plugins', 'rnf_disable_emojis_tinymce' );
  add_filter( 'wp_resource_hints', 'rnf_disable_emojis_remove_dns_prefetch', 10, 2 );
}
add_action( 'init', 'rnf_disable_emojis' );

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @param array $plugins
 * @return array Difference betwen the two arrays
 */
function rnf_disable_emojis_tinymce( $plugins ) {
  if ( is_array( $plugins ) ) {
    return array_diff( $plugins, array( 'wpemoji' ) );
  } else {
    return array();
  }
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @param array $urls URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Difference betwen the two arrays.
 */
function rnf_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
  if ( 'dns-prefetch' == $relation_type ) {
    /** This filter is documented in wp-includes/formatting.php */
    $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/11.2.0/svg/' );
    // TS Edit: This line used 2 instead of 11 and the apply_filters didn't
    // transform it so I edited that. Guess I'll need to be watching for this to
    // change in the future.
    $urls = array_diff( $urls, array( $emoji_svg_url ) );
  }
  return $urls;
}
