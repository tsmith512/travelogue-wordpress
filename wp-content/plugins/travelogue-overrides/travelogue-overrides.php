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

/**
 * Add embed handlers for a couple sites I link to a lot on here.
 * Informed by https://github.com/scottmac/opengraph/blob/master/OpenGraph.php
 * Disclaimer: This isn't the most awesome way to do this ever...
 */
function travelogue_overrides_embed_alltrails_handler($matches, $attr, $url, $rawattr) {
  $embed = "$url";
  $response = wp_remote_get($url, array());

  $old_libxml_error = libxml_use_internal_errors(true);

  $doc = new DOMDocument();
  $doc->loadHTML($response['body']);

  libxml_use_internal_errors($old_libxml_error);

  $title_tags = $doc->getElementsByTagName('title');
  $meta_tags = $doc->getElementsByTagName('meta');

  $title = trim($title_tags->item(0)->nodeValue);
  $meta = array();

  foreach($meta_tags as $tag) {
    if ($tag->hasAttribute('property') && strpos($tag->getAttribute('property'), 'og:') === 0) {
      $key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
      $meta[$key] = $tag->getAttribute('content');
    }

    // For pages which use "value" isntead of "content" for the og data (which is wrong, but in the wild)
    if ($tag ->hasAttribute('value') && $tag->hasAttribute('property') && strpos($tag->getAttribute('property'), 'og:') === 0) {
      $key = strtr(substr($tag->getAttribute('property'), 3), '-', '_');
      $meta[$key] = $tag->getAttribute('value');
    }
  }

  $data = array(
    'src' => $meta['image:secure_url'] ?: ($meta['image'] ?: false),
    'height' => $meta['image:height'] ?: false,
    'width' => $meta['image:width'] ?: false,
    'title' => $meta['title'] ?: $title,
    'site' => $meta['site_name'] ?: false,
  );

  $render = array();

  $render[] = "<div class='travelogue-card'>";
  $render[] = "<a href='{$url}'>";

  if (!empty($data['src'])) {
    $render[] = "<img src='{$data['src']}'";
    if (!empty($data['height'] && !empty($data['width']))) {
      $render[] = "height='{$data['height']}' width ='{$data['width']}'";
    }
    $render[] = "/ >";
  }

  if (!empty($data['title'])) {
    $render[] = "<p class='travelogue-card-link'>{$data['title']}</p>";
  }
  $render[] = "</a>";
  $render[] = "</div>";

  $output = implode(' ', $render);

  return apply_filters('embed_alltrails', $output, $matches, $attr, $url, $rawattr);
}
wp_embed_register_handler('alltrails', '#https?://www.alltrails.com.+#', 'travelogue_overrides_embed_alltrails_handler', 5);
