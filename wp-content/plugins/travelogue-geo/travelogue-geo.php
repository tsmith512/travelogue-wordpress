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


  $options = get_option( 'travelogue_geo_settings' );
  $tqor = array(
    'mapboxApi' => !empty($options['mapbox_api_token']) ? $options['mapbox_api_token'] : null,
    'mapboxStyle' => !empty($options['mapbox_style']) ? $options['mapbox_style'] : null,
    'locationApi' => !empty($options['location_tracker_endpoint']) ? $options['location_tracker_endpoint'] : null,
    'cache' => array(),
  );
  wp_localize_script('travelogue-geo-js', 'tqor', $tqor);
}
add_action('wp_enqueue_scripts', 'travelogue_geo_register_assets', 5);
add_action('admin_enqueue_scripts', 'travelogue_geo_register_assets');

/**
 * Go get the trips list from the location tracker, match 'em up with WP
 * categories if possible.
 */
function travelogue_geo_get_trips() {
  $transient = get_transient('travelogue_geo_trips_cache');
  if( ! empty( $transient ) ) {
    return $transient;
  }

  $options = get_option('travelogue_geo_settings');
  $endpoint = $options['location_tracker_endpoint'] . '/api/trips';
  $result = wp_remote_get($endpoint);

  if ($result['response']['code'] == 200) {
    $trips = json_decode($result['body']);
    set_transient( 'travelogue_geo_trips_cache', $output, 10 /*WEEK_IN_SECONDS*/ );
    return $trips;
  } else {
    return false;
  }
}
