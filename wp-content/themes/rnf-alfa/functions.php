<?php
/**
 * Implements init to drop twentyseventeen's social icons SVGs include in the
 * footer.
 */
function rnf_theme_dequeue_icons() {
  remove_action( 'wp_footer', 'twentyseventeen_include_svg_icons', 9999 );
}
add_action('init', 'rnf_theme_dequeue_icons', 100);

/**
 * Implements wp_enqueue_scripts to register scripts/styles for my overrides.
 * Currently this is only CSS and JS for Colorbox, so we'll only actually
 * enqueue this stuff on post_gallery filter.
 */
function rnf_theme_register_scripts_and_styles() {
  // I've copied in twentyseventeen's original CSS and modified it.
  // Reregistering it by the same name makes sure that the parent theme's CSS
  // dependencies (mostly for gutenberg blocks) still render.
  wp_deregister_style('twentyseventeen-style');
  wp_register_style('twentyseventeen-style', get_stylesheet_uri(), array(), RNF_VERSION);

  // Drop the Libre Franklin, I'm gonna use something else.
  wp_deregister_style('twentyseventeen-fonts');

  wp_register_style('rnf-header-images', get_stylesheet_directory_uri() . '/dist/css/header-images.css', array(), RNF_VERSION);
  wp_enqueue_style('rnf-header-images');

  wp_register_style('rnf-hco-typefaces', '//cloud.typography.com/6795652/6519212/css/fonts.css', array(), null);
  wp_enqueue_style('rnf-hco-typefaces');

  // (Own) General site-wide stuff
  wp_register_script('rnf-alfa-js-main', get_stylesheet_directory_uri() . '/js/main.js', array('sticky-sidebar'), RNF_VERSION, true);
  wp_enqueue_script('rnf-alfa-js-main');
  wp_register_script('rnf-alfa-js-header-images', get_stylesheet_directory_uri() . '/dist/js/header-images.js', array(), RNF_VERSION, true);
  wp_enqueue_script('rnf-alfa-js-header-images');

  // (Own) Media handlers
  wp_register_script('rnf-alfa-js-media', get_stylesheet_directory_uri() . '/js/media.js', array('fancybox-script', 'jquery'), RNF_VERSION, true);

  // Was loading this conditionally on `post_gallery` filter, but I haven't
  // figured out how to attach it to Gutenberg blocks yet, and let's face it,
  // this is an entirely photo-driven site, this is actually needed on all
  // pages.
  wp_enqueue_style('fancybox-style');
  wp_enqueue_script('rnf-alfa-js-media');

  // (Vendor) Sticky Sidebar
  wp_register_script('sticky-sidebar', get_stylesheet_directory_uri() . '/vendor/sticky-sidebar/sticky-sidebar.min.js', array(), RNF_VERSION, true);

  // And remove TwentySeventeen stuff we do not need
  wp_dequeue_style('twentyseventeen-ie8');
  wp_dequeue_script('html5');
  wp_dequeue_script('twentyseventeen-global');
  wp_dequeue_script('jquery-scrollto');
  /* Remove twentyseventeen's preconnect for Google Fonts */
  remove_filter('wp_resource_hints', 'twentyseventeen_resource_hints', 10);
}
add_action( 'wp_enqueue_scripts', 'rnf_theme_register_scripts_and_styles', 20 );

/**
 * Implements style_loader_tag filter to rewrite CSS tags after they've been
 * assembled so we can use rel=preload to reduce render-blocking of external
 * CSS.
 */
function rn_theme_css_preload($html, $handle, $href, $media) {
  // Only working on the HCO typefaces
  if (in_array($handle, array('rnf-hco-typefaces'))) {
    // Set rel=preload
    $preload = str_replace('stylesheet', 'preload', $html);

    // And apply once it is loaded
    $resolve = str_replace('/>', "as=\"style\" onload=\"this.rel='stylesheet';this.onload=null;\" /><noscript>{$html}</noscript>", $preload);

    // Return the new tag
    return $resolve;
  }

  // No work to do for non HCO typefaces
  return $html;
}
add_filter('style_loader_tag', 'rn_theme_css_preload', 900, 4);


/**
 * Implements wp_default_scripts to drop jQuery Migrate from pages viewed in
 * this theme. (Or should this go in rnf_overrides and limit to !is_admin?)
 */
function rnf_theme_drop_jqmigrate($scripts) {
  if (isset( $scripts->registered['jquery'] ) ) {
    $script = $scripts->registered['jquery'];

    if ( $script->deps ) { // Check whether the script has any dependencies
      $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
    }
  }
}
add_action('wp_default_scripts', 'rnf_theme_drop_jqmigrate', 100);

/**
 * Implements wp_default_scripts to move jQuery core to the footer for pages
 * viewed in this theme.
 * See: https://wordpress.stackexchange.com/a/240612
 */
function rnf_theme_move_jq($scripts) {
  // This function would probably only execute on the customizer, but check
  // anyway, this would break admin.
  if (is_admin()) return;

  $scripts->add_data('jquery', 'group', 1);
  $scripts->add_data('jquery-core', 'group', 1);
  // StackOverflow answer also moved jquery-migrate, but I've dropped that.
}
add_action('wp_default_scripts', 'rnf_theme_move_jq');

/**
 * Implements wp_enqueue_scripts to register the scripts and styles for the
 * Colorbox lightbox library which will attach to all image galleries.
 *
 * @TODO: Can this be executed only when needed? Also replace with PhotoSwipe
 */
function rnf_theme_register_lightbox() {
  wp_register_style('fancybox-style', content_url('/vendor/fancyapps/fancybox/dist/jquery.fancybox.min.css'), array(), null);
  wp_register_script('fancybox-script', content_url('/vendor/fancyapps/fancybox/dist/jquery.fancybox.min.js'), array('jquery'), null, true);
}
add_action( 'wp_enqueue_scripts', 'rnf_theme_register_lightbox', 10 );

/**
 * Output a post date as a permalink. Overrides twentyseventeen's default to also
 * add a "show on map" link in the same place.
 */
function twentyseventeen_time_link() {
  $time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
  if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
    $time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
  }

  // Put together the date permalink:
  $time_string = sprintf( $time_string,
    get_the_date( DATE_W3C ),
    get_the_date(),
    get_the_modified_date( DATE_W3C ),
    get_the_modified_date()
  );

  // Wrap the time string in a link, and preface it with 'Posted on'.
  $esc_path = esc_url( get_permalink() );
  $time_header = "// <a href='{$esc_path}' rel='bookmark'>{$time_string}</a>";

  // Now determine if we should show a link to the post on a map. That logic
  // is determined in rnf-geo.php and is: is post _about_ a trip _during_ a trip?
  $post = get_post();
  if (isset($post->rnf_geo_post_is_on_trip) && $post->rnf_geo_post_is_on_trip === TRUE) {
    // Wrap the time string in a link, and preface it with 'Posted on'.
    $timestamp = get_post_time('U', true);

    $time_header .= " / <a href='#' class='tqor-map-jump' data-timestamp='{$timestamp}'>Map</a>";
  }

  return $time_header;
}
