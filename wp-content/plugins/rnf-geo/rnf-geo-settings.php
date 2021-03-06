<?php


function rnf_geo_settings_init() {
  register_setting('rnf-geo-settings', 'rnf_geo_settings');

  add_settings_section(
    'integrations',
    'RNF Geo Settings',
    'integrations_callback',
    'rnf-geo-settings'
  );

  add_settings_field(
    'location_tracker_endpoint',
    'Location Tracker Endpoint URL',
    'location_tracker_endpoint_render',
    'rnf-geo-settings',
    'integrations'
  );

  add_settings_field(
    'mapbox_api_token',
    'Mapbox API Token',
    'mapbox_api_token_render',
    'rnf-geo-settings',
    'integrations'
  );

  add_settings_field(
    'mapbox_style',
    'Mapbox Map Style URI',
    'mapbox_style_render',
    'rnf-geo-settings',
    'integrations'
  );
}
add_action('admin_init', 'rnf_geo_settings_init');


function location_tracker_endpoint_render() {
  $options = get_option( 'rnf_geo_settings' );
  $value = isset($options['location_tracker_endpoint']) ? esc_attr($options['location_tracker_endpoint']) : '';
  echo "<input type='text' name='rnf_geo_settings[location_tracker_endpoint]' value='{$value}'>";
}

function mapbox_api_token_render() {
  $options = get_option( 'rnf_geo_settings' );
  $value = isset($options['mapbox_api_token']) ? esc_attr($options['mapbox_api_token']) : '';
  echo "<input type='text' name='rnf_geo_settings[mapbox_api_token]' value='{$value}'>";
}

function mapbox_style_render() {
  $options = get_option( 'rnf_geo_settings' );
  $value = isset($options['mapbox_style']) ? esc_attr($options['mapbox_style']) : '';
  echo "<input type='text' name='rnf_geo_settings[mapbox_style]' value='{$value}'>";
}

function integrations_callback() {
  echo "External Services Integrations";
}

function rnf_geo_options_page() {
  ?>
  <form action='options.php' method='post'>

    <h1>RNF Geo, Location, and Maps Configuration</h1>

    <?php
    settings_fields( 'rnf-geo-settings' );
    do_settings_sections( 'rnf-geo-settings' );
    submit_button();
    ?>

  </form>
  <?php
}
