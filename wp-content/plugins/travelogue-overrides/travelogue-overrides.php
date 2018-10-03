<?php
/*
 * Plugin Name: Travelogue Overrides
 * Description: Limited-scope overrides to tweak defaults for Travelogue Powered by WordPress
 * Version: 0.1
 * Author: Taylor Smith
 */

/**
 * Implements shortcode_atts_{$shortcode} filter to do an override on all
 * galleries to have links points to media files instead of attachment pages.
 * Legacy posts from Tumblr all link to attachment pages and that's hundreds of
 * images, and also this will just make it easier to ensure consistency on
 * hastily uploaded stuff from mobile.
 */
function travelogue_gallery_overrides($out, $pairs, $atts, $shortcode) {
  $out['link'] = 'file';
  return $out;
};
add_filter('shortcode_atts_gallery', 'travelogue_gallery_overrides', 10, 4);

/**
 * Implements pre_get_posts on category pages only for categories that have a
 * trip ID saved (i.e. someone is reading a full trip) so we can flip the order
 */
function travelogue_trip_archives_in_chorno(&$query) {
  if ($query->is_category) {
    $term = get_queried_object();
    $trip_id = get_term_meta($term->term_id, 'travelogue_geo_trip_id', true);

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      $query->set( 'order', 'ASC' );
    }
  }
}
add_filter('pre_get_posts', 'travelogue_trip_archives_in_chorno');
