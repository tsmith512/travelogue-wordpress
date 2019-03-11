<?php
/*
 * Plugin Name: RNF Trips, Maps, and Geography
 * Description: Provides taxonomy support, Location Tracker API integration, and Maps
 * Version: 0.1
 * Author: Taylor Smith
 */

require_once "rnf-geo-map-widget.php";
add_action( 'widgets_init', function() { register_widget( 'RNF_Geo_Map_Widget' ); } );

/**
 * Implements admin_menu to add the options page to the sidebar menu for admins.
 * See rnf-geo-settings.php for the output of this page.
 */
function rnf_geo_add_admin_menu() {
  add_menu_page('RNF Geo', 'RNF Geo', 'manage_options', 'rnf-geo', 'rnf_geo_admin_page', 'dashicons-location-alt', 78);
  add_submenu_page('rnf-geo', 'RNF Geo Settings', 'Settings', 'manage_options', 'rnf-geo-settings', 'rnf_geo_options_page');
}
add_action('admin_menu', 'rnf_geo_add_admin_menu');
require_once "rnf-geo-admin.php";
require_once "rnf-geo-settings.php";

/**
 * Register but do not enqueue scripts and stylesheets for integrations and map
 * displays, including passing in the rnf_geo_settings data.
 */
function rnf_geo_register_assets() {
  wp_register_script('mapbox-core', 'https://api.mapbox.com/mapbox.js/v3.0.1/mapbox.js', array(), null, true);
  wp_register_style('mapbox-style', 'https://api.mapbox.com/mapbox.js/v3.0.1/mapbox.css', array(), null);
  wp_register_script('rnf-geo-js', plugin_dir_url( __FILE__ ) . 'js/rnf-geo.js', array('mapbox-core'), RNF_VERSION, true);
  wp_register_style('rnf-geo-style', plugin_dir_url( __FILE__ ) . 'css/rnf-geo-maps.css', array('mapbox-style'), RNF_VERSION);

  // Figure out where we are so we can tell the map where to start
  $object = get_queried_object();
  $start = array();
  if ($object instanceof WP_Post) {
    // A single post was called, let's start the map on the post's location
    $start = array(
      'type' => 'post',
      'timestamp' => get_post_time('U', true),
    );

    // And only show the line for the trip the post is on
    $trip_term_id = wp_get_post_categories($object->ID, array('meta_key' => 'rnf_geo_trip_id'));
    if (!empty($trip_term_id) && $trip_term_id[0] > 0) {
      $trip_id = get_term_meta($trip_term_id[0], 'rnf_geo_trip_id', true);
      if (is_numeric($trip_id) && (int) $trip_id > 0) {
        $start['trip_id'] = $trip_id;
      }
    }

  } elseif ($object instanceof WP_Term) {
    // It's a taxonomy term. Check to see if a trip id is associated.
    $trip_id = get_term_meta($object->term_id, 'rnf_geo_trip_id', true);

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      $start = array(
        'type' => 'trip',
        'trip_id' => $trip_id,
      );
    }
  } else {
    // There is no queried object. The main use-case for this is the default
    // blog view.
    $start = array(
      'type' => false,
    );
  }

  $options = get_option( 'rnf_geo_settings' );
  $tqor = array(
    'mapboxApi' => !empty($options['mapbox_api_token']) ? $options['mapbox_api_token'] : null,
    'mapboxStyle' => !empty($options['mapbox_style']) ? $options['mapbox_style'] : null,
    'locationApi' => !empty($options['location_tracker_endpoint']) ? $options['location_tracker_endpoint'] : null,
    'cache' => array(),
    'trips' => array(),
    'trips_with_content' => rnf_geo_get_trips_with_content(),
    'start' => $start
  );

  wp_localize_script('rnf-geo-js', 'tqor', $tqor);
}
add_action('wp_enqueue_scripts', 'rnf_geo_register_assets', 5);
add_action('admin_enqueue_scripts', 'rnf_geo_register_assets');

/**
 * Go get the trips list from the location tracker, match 'em up with WP
 * categories if possible.
 */
function rnf_geo_get_trips($trip_id = null) {
  $trips = array();

  $transient = get_transient('rnf_geo_trips_cache');

  if (empty($transient)) {
    $options = get_option('rnf_geo_settings');
    $endpoint = $options['location_tracker_endpoint'] . '/api/trips';
    $result = wp_remote_get($endpoint);

    if ($result['response']['code'] == 200) {
      $trips = json_decode($result['body']);
      set_transient( 'rnf_geo_trips_cache', $trips, DAY_IN_SECONDS );
    }
  } else {
    $trips = $transient;
  }

  // If we're only looking for data on a single Trip (an ID was provided),
  // then filter _now_ so we don't pound the DB looking for taxonomy terms for
  // trips we don't care about.
  if ($trip_id) {
    $trips = array_filter($trips, function($t) use ($trip_id) {
      // Typecast both because the ID we're testing for may have come in as a
      // string via AJAX, and for some stupid reason I'm returning the ID as a
      // string from the Location Tracker API as well. @TODO: Don't.
      return (int) $t->id === (int) $trip_id;
    });
  }

  foreach ($trips as &$trip) {
    $trip->wp_category = get_term_by('slug', $trip->machine_name, 'category');
  }

  return empty($trips) ? false : $trips;
}

/**
 * Return a list of trip IDs we have taxonomy terms for (i.e. not the list of
 * trips that exist on the remote service, but the ones we've written about).
 */
function rnf_geo_get_trips_with_content() {
  // The trip IDs are stored as a term-meta value for each category that is a
  // trip. There's not a Term Meta API way to aggregate "all values for this key
  // across all terms" without first fetching all terms, so just get 'em from
  // the DB:
  global $wpdb;
  $trip_ids = $wpdb->get_col("SELECT meta_value FROM {$wpdb->prefix}termmeta WHERE meta_key = 'rnf_geo_trip_id'");

  // WP meta values are all strings, but these should be integers
  array_walk($trip_ids, function(&$e) { $e = (int) $e; });

  return $trip_ids;
}

/**
 * Create a category for a given trip it. Should receive a trip object or trip it
 */
function rnf_geo_ajax_create_trip_category() {
  $trip_id = (int) $_POST['trip_id'];
  $trips = rnf_geo_get_trips($trip_id);

  if (empty($trips)) {
    // @TODO: ERROR
    return false;
  }

  $trip = reset($trips);
  $term = wp_insert_term($trip->label, 'category', array(
    'slug' => $trip->machine_name
  ));
  add_term_meta($term['term_id'], 'rnf_geo_trip_id', $trip->id, true);

  print json_encode($term);
  wp_die();
}
add_action( 'wp_ajax_tqor_create_term', 'rnf_geo_ajax_create_trip_category' );

/**
 * Display a Location Tracker Trip ID on the taxonomy term management page if
 * there is one.
 */
function rnf_geo_category_add_id_display($term) {
  $trip_id = get_term_meta( $term->term_id, 'rnf_geo_trip_id', true );

  if ($trip_id) {
    print "Location Tracker Trip ID: $trip_id";
  } else {
    print "<em>Term not associated to a trip. Create from RNF Geo page directly.</em>";
  }
}
add_action('category_edit_form_fields', 'rnf_geo_category_add_id_display');

/**
 * Check to see if a post was actually published during the trip it is about.
 * We will only show a "map" link on posts that are actually visible on the map.
 */
function rnf_geo_is_post_during_trip(&$post) {
  // Assume a post isn't written during the trip it is about as a baseline.
  $post->rnf_geo_post_is_on_trip = false;

  // @TODO: This is repeated from rnf_geo_register_assets, need to abstract it
  $trip_term_id = wp_get_post_categories($post->ID, array('meta_key' => 'rnf_geo_trip_id'));

  if (!empty($trip_term_id) && $trip_term_id[0] > 0) {
    // We got a WP category ID, look up its associated trip:
    $trip_id = get_term_meta($trip_term_id[0], 'rnf_geo_trip_id', true);

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      // We have a trip ID to match up with.

      // Get the post's unix timestamp
      $timestamp = get_post_time('U', true);

      // Get the timestamps of the beginning and end of the trip
      $trip_details = rnf_geo_get_trips($trip_id);
      $trip_details = reset($trip_details);

      // So is this post actually dated _during_ the trip it is about?
      $post->rnf_geo_post_is_on_trip = ($trip_details->starttime <= $timestamp && $timestamp <= $trip_details->endtime);
    } else {
      // There was a category attached to this post with an rnf_geo_trip_id
      // value, but we didn't get a value... that's really weird.
    }
  } else {
    // This isn't even about a trip.
    // @TODO: So clearly, no link should show, but does that logic belong here?
  }
}
add_action('the_post', 'rnf_geo_is_post_during_trip');
