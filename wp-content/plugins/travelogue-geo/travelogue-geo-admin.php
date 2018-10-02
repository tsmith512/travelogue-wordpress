<?php

function travelogue_geo_admin_page() {
  $trips = travelogue_geo_get_trips();

  ?>
  <div class="wrap">
    <?php /* @TODO: Let's do this the right way... */ ?>
    <table class="wp-list-table widefat fixed striped posts">
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
            <td>Placeholder</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
	<?php
}
