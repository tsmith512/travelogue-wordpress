<?php
/**
 * Override to twentyseventeen's sidebar situation. This site's sidebar is only
 * the one box for the map so we're gonna float it as content scrolls.
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>

<aside id="secondary" class="widget-area" role="complementary" aria-label="<?php esc_attr_e( 'Blog Sidebar', 'twentyseventeen' ); ?>">
  <div class="secondary_inner">
    <?php dynamic_sidebar( 'sidebar-1' ); ?>
  </div>
</aside><!-- #secondary -->
