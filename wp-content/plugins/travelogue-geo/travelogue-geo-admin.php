<?php

function travelogue_geo_admin_page() {
  $trips = travelogue_geo_get_trips();

  ?>
  <div class="wrap">
    <h1>Trips in Location Tracker</h1>
    <?php /* @TODO: Let's do this the right way... */ ?>
    <table id="tqor-trips-list" class="wp-list-table widefat fixed striped posts">
      <thead>
        <tr>
          <th>Trip ID</th>
          <th>Machine Name</th>
          <th>Title</th>
          <th>Started</th>
          <th>Ended</th>
          <th>WordPress Category Assigned?</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($trips as $index => $trip): ?>
          <tr>
            <td><?php print $trip->id; ?></td>
            <td><?php print $trip->machine_name; ?></td>
            <td><?php print $trip->label; ?></td>
            <td><?php print $trip->starttime; ?></td>
            <td><?php print $trip->endtime; ?></td>
            <td>
              <?php
                if ($trip->wp_category !== false) {
                  $url = get_term_link($trip->wp_category);
                  $title = $trip->wp_category->name;
                  print "<a href='{$url}'>{$title}</a>";
                } else {
                  print "<button data-trip-id='{$trip->id}' class='button-secondary'>Create?</button>";
                }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php /* @TODO: This definitely doesn't go here. */ ?>
  <script>
    jQuery(document).ready(function($) {
      $('#tqor-trips-list button').on('click', function(){
        var data = {
          'action': 'tqor_create_term',
          'trip_id': $(this).attr('data-trip-id')
        };
        jQuery.post(ajaxurl, data, function(response) {
          // @TODO: This could be something that isn't a page refresh...
          window.location.reload(true);
        });
      });
    });
  </script>
	<?php
}
