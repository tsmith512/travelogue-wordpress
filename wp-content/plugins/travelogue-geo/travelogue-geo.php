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
function travelogue_geo_add_admin_menu(  ) {
  add_menu_page('Travelogue Geo', 'Travelogue Geo', 'manage_options', 'travelogue-geo', 'travelogue_geo_admin_page', 'dashicons-location-alt', 78);
  add_submenu_page('travelogue-geo', 'Travelogue Geo Settings', 'Settings', 'manage_options', 'travelogue-geo-settings', 'travelogue_geo_options_page');
}
add_action( 'admin_menu', 'travelogue_geo_add_admin_menu' );
require_once "travelogue-geo-admin.php";
require_once "travelogue-geo-settings.php";
