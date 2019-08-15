<?php


function rnf_postie_settings_init() {
  register_setting('rnf-postie-settings', 'rnf_postie_settings', array('sanitize_callback' => 'rnf_postie_validation'));

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

  add_settings_field(
    'postie_accepted_domains',
    'Acceptable domans',
    'postie_accepted_domains_render',
    'rnf-postie-settings',
    'rnf_postie'
  );
}
add_action('admin_init', 'rnf_postie_settings_init');


function postie_accepted_emails_render() {
  $options = get_option( 'rnf_postie_settings' );
  $value = isset($options['emails']) ? esc_attr(implode(' ', $options['emails'])) : '';
  echo "<input type='text' name='rnf_postie_settings[emails]' value='{$value}'>";
  echo "<p class='description'><strong>Email addresses</strong> to accept email from, separated by spaces.</p>";
}

function postie_accepted_domains_render() {
  $options = get_option( 'rnf_postie_settings' );
  $value = isset($options['domains']) ? esc_attr(implode(' ', $options['domains'])) : '';
  echo "<input type='text' name='rnf_postie_settings[domains]' value='{$value}'>";
  echo "<p class='description'><strong>Domains</strong> to accept email from, separated by spaces.</p>";
}

function rnf_postie_header_callback() {
  $admin = get_userdata(1);
  $email = $admin->get('user_email');

  ?>
    <h2>Email Whitelisting</h2>
    <p>
      Accept email from these addresses and domains and label from
      <strong><?php echo $email; ?></strong>.
    </p>
    <!-- @TODO: This is cute... -->
    <pre><?php var_dump(get_option( 'rnf_postie_settings' )); ?></pre>
  <?php
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

/**
 * A validation callback to split those values into arrays on save.
 * @TODO: Only a site admin can edit this, but hostnames and emails should be
 * actually _validated_ instead of just exploded.
 */
function rnf_postie_validation($input) {
  $output = array();
  $output["emails"] = explode(" ", $input["emails"]);
  $output["domains"] = explode(" ", $input["domains"]);
  return $output;
}
