<?php
/*
 * Plugin Name: RNF Metatags and Social Sharing Support
 * Description: Provides super minimal metatags for slightly better sharing
 * Version: 0.1
 * Author: Taylor Smith
 */

function rnf_meta_add_header_tags() {
  // Figure out where we are so we can tell the map where to start
  $object = get_queried_object();

  // Set up some defaults and placeholders
  $info = array(
    'title' => FALSE,
    'image' => FALSE,
    'site' => get_bloginfo('title'),
    'trip' => FALSE,
    'url' => is_singular() ? get_permalink() :
      ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://')
      . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
    'type' => is_singular() ? 'article' : 'website',
  );

  // Some stuff is different depending on what it is
  if ($object instanceof WP_Post) {
    $info['title'] = $object->post_title;

    // We have a post, is it on a trip?
    $trip_terms = wp_get_post_categories($object->ID, array('meta_key' => 'rnf_geo_trip_id', 'fields' => 'all'));
    if (!empty($trip_terms)) {
      $info['trip'] = $trip_terms[0]->name;
    }

    $imgreg = '/<img .*src=["\']([^ "^\']*)["\']/';
    preg_match_all( $imgreg, $object->post_content, $matches );

    // Use the first image src as the OG image
    if (!empty($matches[1])) {
      $info['image'] = reset($matches[1]);
    }

  } elseif ($object instanceof WP_Term) {
    // It's a taxonomy term. Check to see if a trip id is associated.
    $trip_id = get_term_meta($object->term_id, 'rnf_geo_trip_id', true);

    // Use the term title as the page title
    $info['title'] = $object->name;

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      // And if it is also a trip, let's copy that value. This would distinguish
      // between a trip archive and Uncategorized or Technical Notes...
      $info['trip'] = $object->name;
    }
  } else {
    // There is no queried object. The main use-case for this is the default
    // blog view.
  }

  // Catch the home page / general archive
  if (!$info['title'] && is_front_page()) {
    $info['title'] = get_bloginfo('title');
  }

  // Sanitize all of these for attributes
  array_walk($info, function(&$value, $key) {
    $value = esc_attr($value);
  });

  // If we haven't identified a title to share, just bail.
  if (!$info['title']) { return; }

  $title = $info['title'] . (($info['trip']) ? ", {$info['trip']}" : NULL);

  $meta = array();
  $meta[] = "<meta property='og:locale' content='en_US' />";
  $meta[] = "<meta property='og:title' content='{$title}' />";
  $meta[] = "<meta property='og:site_name' content='{$info['site']}' />";
  $meta[] = "<meta property='og:url' content='{$info['url']}' />";
  $meta[] = "<meta property='og:type' content='{$info['type']}' />";
  $meta[] = ($info['image']) ? "<meta property='og:image' content='{$info['image']}' />" : "";
  $meta[] = "<meta name='twitter:title' content='{$title}' />";
  $meta[] = "<meta name='twitter:url' content='{$info['url']}' />";
  $meta[] = ($info['image']) ? "<meta name='twitter:image' content='{$info['image']}' />" : "";
  $meta[] = "<meta name='twitter:card' content='summary_large_image' />";

  echo implode("\n", $meta);
}
add_action('wp_head', 'rnf_meta_add_header_tags', 5);

function rnf_meta_add_namespace($output) {
  if (stristr($output, 'xmlns:og') === false) {
    $output = $output . ' xmlns:og="http://ogp.me/ns#"';
  }
  if (stristr($output, 'xmlns:fb') === false) {
    $output = $output . ' xmlns:fb="http://ogp.me/ns/fb#"';
  }

  return $output;
}
add_filter('language_attributes', 'rnf_meta_add_namespace');
