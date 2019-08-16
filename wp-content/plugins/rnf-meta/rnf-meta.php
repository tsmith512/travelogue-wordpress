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
  $current = rnf_geo_current_trip();

  $meta = array();
  // <meta (name|property)="X" content="Y">

  $info = array(
    'title' => false,
    'site' => get_bloginfo('title'),
    'current' => false,
    'description' => false,
    'url' => is_singular() ? get_permalink() :
      ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://')
      . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
  );

/*
<link rel="canonical" href="http://dizzy.site/2019/08/15/heres-a-longer-test-message/"/>
<meta name="description" content="So we made it to the place...paragraph...more text ..Here&#039;s a picture:Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut leo mi. Proin ultricies sed lorem id gravida. Ut consectetur nec magna vitae interdum. Sed finibus placerat malesuada. Nunc bibendum mauris a ante eleifend, in"/>
<meta property="og:locale" content="en_US"/>
<meta property="og:title" content="Here&#039;s a longer test message"/>
<meta property="og:url" content="http://dizzy.site/2019/08/15/heres-a-longer-test-message/"/>
<meta property="og:type" content="article"/>
<meta name="twitter:title" content="Here&#039;s a longer test message"/>
<meta name="twitter:url" content="http://dizzy.site/2019/08/15/heres-a-longer-test-message/"/>
<meta name="twitter:card" content="summary_large_image"/>
*/

  if ($object instanceof WP_Post) {

    // We have a post, is it on a trip?
    $trip_terms = wp_get_post_categories($object->ID, array('meta_key' => 'rnf_geo_trip_id', 'fields' => 'all'));
    if (!empty($trip_terms)) {
      $info['current'] = $trip_terms[0]->name;
    }

  } elseif ($object instanceof WP_Term) {
    // It's a taxonomy term. Check to see if a trip id is associated.
    $trip_id = get_term_meta($object->term_id, 'rnf_geo_trip_id', true);

    // Use the term title as the page title
    $info['title'] = $object->name;

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      // And if it is also a trip, let's copy that value. This would distinguish
      // between a trip archive and Uncategorized or Technical Notes...
      $info['current'] = $object->name;
    }
  } else if (!empty($current->wp_category)) {
    // There's no queried object, but we're currently traveling and the trip has
    // a corresponding category, we can use that in titles
    $info['current'] = $current->wp_category->name;
  } else {
    // There is no queried object. The main use-case for this is the default
    // blog view.
  }

  if (!$info['title'] && is_front_page()) {
    $info['title'] = get_bloginfo('title');
  }
var_dump($info);
}
add_action('wp_head', 'rnf_meta_add_header_tags', 5);
