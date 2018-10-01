<?php
/**
 * Create Travelogue_Geo_Map_Widget widget which will be a container for a
 * Mapbox map with annotations built in Mapbox and/or Location data from the
 * Location Tracker API.
 */
class Travelogue_Geo_Map_Widget extends WP_Widget {
  /**
   * Constructor with admin info
   */
  public function __construct() {
    parent::__construct(
      'travelogue_geo_map_widget',
      'Travelogue Map',
      array(
        'description' => 'A Mapbox Map with TL/Location Tracker Data'
      )
    );
  }

  /**
   * Display widget frontend. @TODO: Placeholder.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget($args, $instance) {
    print '<pre>';
    var_dump($args);
    var_dump($instance);
    print '</pre>';
  }
}
