<?php
/*
 * Plugin Name: Travelogue Trips, Maps, and Geography
 * Description: Provides taxonomy support, Location Tracker API integration, and Maps
 * Version: 0.1
 * Author: Taylor Smith
 */

require_once "travelogue-geo-map-widget.php";
add_action( 'widgets_init', function() { register_widget( 'Travelogue_Geo_Map_Widget' ); } );

/**
 * Implements admin_menu to add the options page to the sidebar menu for admins.
 * See travelogue-geo-settings.php for the output of this page.
 */
function travelogue_geo_add_admin_menu() {
  add_menu_page('Travelogue Geo', 'Travelogue Geo', 'manage_options', 'travelogue-geo', 'travelogue_geo_admin_page', 'dashicons-location-alt', 78);
  add_submenu_page('travelogue-geo', 'Travelogue Geo Settings', 'Settings', 'manage_options', 'travelogue-geo-settings', 'travelogue_geo_options_page');
}
add_action('admin_menu', 'travelogue_geo_add_admin_menu');
require_once "travelogue-geo-admin.php";
require_once "travelogue-geo-settings.php";

/**
 * Register but do not enqueue scripts and stylesheets for integrations and map
 * displays, including passing in the travelogue_geo_settings data.
 */
function travelogue_geo_register_assets() {
  wp_register_script('mapbox-core', 'https://api.mapbox.com/mapbox.js/v3.0.1/mapbox.js', array(), false, true);
  wp_register_style('mapbox-style', 'https://api.mapbox.com/mapbox.js/v3.0.1/mapbox.css', array(), false);
  wp_register_script('travelogue-geo-js', plugin_dir_url( __FILE__ ) . 'js/travelogue-geo.js', array('mapbox-core'), false, true);
  wp_register_style('travelogue-style', plugin_dir_url( __FILE__ ) . 'css/travelogue-geo-maps.css', array(), false);

  // Figure out where we are so we can tell the map where to start
  $object = get_queried_object();
  $start = array();
  if ($object instanceof WP_Post) {
    // A single post was called, let's start the map on the post's location
    $start = array(
      'type' => 'post',
      'timestamp' => get_the_time('U'),
    );
  } elseif ($object instanceof WP_Term) {
    // It's a taxonomy term. Check to see if a trip id is associated.
    $trip_id = get_term_meta($object->term_id, 'travelogue_geo_trip_id', true);

    if (is_numeric($trip_id) && (int) $trip_id > 0) {
      print "And the ID is $trip_id";
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

  $options = get_option( 'travelogue_geo_settings' );
  $tqor = array(
    'mapboxApi' => !empty($options['mapbox_api_token']) ? $options['mapbox_api_token'] : null,
    'mapboxStyle' => !empty($options['mapbox_style']) ? $options['mapbox_style'] : null,
    'locationApi' => !empty($options['location_tracker_endpoint']) ? $options['location_tracker_endpoint'] : null,
    'cache' => array(),
    'trips' => array(),
    'start' => $start
  );

  wp_localize_script('travelogue-geo-js', 'tqor', $tqor);
}
add_action('wp_enqueue_scripts', 'travelogue_geo_register_assets', 5);
add_action('admin_enqueue_scripts', 'travelogue_geo_register_assets');

/**
 * Go get the trips list from the location tracker, match 'em up with WP
 * categories if possible.
 */
function travelogue_geo_get_trips($trip_id = null) {
  $transient = get_transient('travelogue_geo_trips_cache');
  if( ! empty( $transient ) ) {
    return $transient;
  }

  $options = get_option('travelogue_geo_settings');
  $endpoint = $options['location_tracker_endpoint'] . '/api/trips';
  $result = wp_remote_get($endpoint);
  $trips = array();

  if ($result['response']['code'] == 200) {
    $trips = json_decode($result['body']);
    set_transient( 'travelogue_geo_trips_cache', $output, DAY_IN_SECONDS );
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
 * Create a category for a given trip it. Should receive a trip object or trip it
 */
function travelogue_geo_ajax_create_trip_category() {
  $trip_id = (int) $_POST['trip_id'];
  $trips = travelogue_geo_get_trips($trip_id);

  if (empty($trips)) {
    // @TODO: ERROR
    return false;
  }

  $trip = reset($trips);
  $term = wp_insert_term($trip->label, 'category', array(
    'slug' => $trip->machine_name
  ));
  add_term_meta($term['term_id'], 'travelogue_geo_trip_id', $trip->id, true);

  print json_encode($term);
  wp_die();
}
add_action( 'wp_ajax_tqor_create_term', 'travelogue_geo_ajax_create_trip_category' );

/**
 * Display a Location Tracker Trip ID on the taxonomy term management page if
 * there is one.
 */
function travelogue_geo_category_add_id_display($term) {
  $trip_id = get_term_meta( $term->term_id, 'travelogue_geo_trip_id', true );

  if ($trip_id) {
    print "Location Tracker Trip ID: $trip_id";
  } else {
    print "<em>Term not associated to a trip. Create from Travelogue Geo page directly.</em>";
  }
}
add_action('category_edit_form_fields', 'travelogue_geo_category_add_id_display');
