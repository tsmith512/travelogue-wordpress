<?php


function rnf_postie_settings_init() {
  register_setting('rnf-postie-settings', 'rnf_postie_settings');

  add_settings_section(
    'rnf_postie',
    'RNF Postie Settings',
    'rnf_postie_header_callback',
    'rnf-postie-settings'
  );

  add_settings_field(
    'postie_accepted_emails',
    'Acceptable email addresses',
    'postie_accepted_emails_render',
    'rnf-postie-settings',
    'rnf_postie'
  );
}
add_action('admin_init', 'rnf_postie_settings_init');


function postie_accepted_emails_render() {
  $options = get_option( 'rnf_postie_settings' );
  $value = isset($options['location_tracker_endpoint']) ? esc_attr($options['location_tracker_endpoint']) : '';
  echo "<input type='text' name='rnf_postie_settings[location_tracker_endpoint]' value='{$value}'>";
}
function rnf_postie_header_callback() {
  echo "Email Whitelisting Options";
}

function rnf_postie_options_page() {
  ?>
  <form action='options.php' method='post'>

    <h1>RNF Postie Overrides</h1>

    <?php
    settings_fields( 'rnf-postie-settings' );
    do_settings_sections( 'rnf-postie-settings' );
    submit_button();
    ?>

  </form>
  <?php
}
