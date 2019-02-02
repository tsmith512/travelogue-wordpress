<?php
/**
 * Implements wp_enqueue_scripts to pull in the parent theme's scripts and
 * stylesheets (since this is a twentyseventeen with very light customization).
 */
function rnf_theme_enqueue_parent_styles() {
  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'rnf_theme_enqueue_parent_styles', 5 );

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
  // twentyseventeen-style is registered by the parent theme but it's the active
  // (so, child) theme's CSS file. I'll unregister that because it has a cache
  // buster tied to the WP core version, not its own revision. Reregistering it
  // by the same name makes sure that the parent theme's CSS dependencies
  // (mostly for gberg blocks) still render.
  wp_deregister_style('twentyseventeen-style');
  wp_register_style('twentyseventeen-style', get_stylesheet_uri(), array(), RNF_VERSION);

  // (Own) General site-wide stuff
  wp_register_script('rnf-alfa-js-main', get_stylesheet_directory_uri() . '/js/main.js', array('sticky-sidebar'), RNF_VERSION, true);
  wp_enqueue_script('rnf-alfa-js-main');

  // (Own) Media handlers
  wp_register_script('rnf-alfa-js-media', get_stylesheet_directory_uri() . '/js/media.js', array('colorbox-script', 'jquery'), RNF_VERSION, true);

  // Was loading this conditionally on `post_gallery` filter, but I haven't
  // figured out how to attach it to Gutenberg blocks yet, and let's face it,
  // this is an entirely photo-driven site, this is actually needed on all
  // pages.
  wp_enqueue_style('colorbox-style');
  wp_enqueue_script('rnf-alfa-js-media');

  // (Vendor) Sticky Sidebar
  wp_register_script('sticky-sidebar', get_stylesheet_directory_uri() . '/vendor/sticky-sidebar/sticky-sidebar.min.js', array(), RNF_VERSION, true);

  // And remove TwentySeventeen stuff we do not need
  wp_dequeue_style('twentyseventeen-ie8');
  wp_dequeue_script('html5');
  wp_dequeue_script('twentyseventeen-global');
  wp_dequeue_script('jquery-scrollto');
}
add_action( 'wp_enqueue_scripts', 'rnf_theme_register_scripts_and_styles', 20 );

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
  wp_register_style( 'colorbox-style', get_stylesheet_directory_uri() . '/vendor/colorbox/example2/colorbox.css', array(), null);
  wp_register_script('colorbox-script', get_stylesheet_directory_uri() . '/vendor/colorbox/jquery.colorbox.js', array('jquery'), null, true);

  wp_add_inline_style('colorbox-style', "#cboxWrapper button {transition: none !important; filter: invert(100%);}");
  wp_add_inline_style('colorbox-style', "#cboxOverlay {background: black;}");
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

	$time_string = sprintf( $time_string,
		get_the_date( DATE_W3C ),
		get_the_date(),
		get_the_modified_date( DATE_W3C ),
		get_the_modified_date()
	);

  // Wrap the time string in a link, and preface it with 'Posted on'.
  $esc_path = esc_url( get_permalink() );
  $timestamp = get_post_time('U', true);
  return "<a href='{$esc_path}' rel='bookmark'>{$time_string}</a> | " .
         "<a href='#' class='tqor-map-jump' data-timestamp='{$timestamp}'>Map</a>";

}
