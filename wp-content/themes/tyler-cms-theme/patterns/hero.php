<?php
/**
 * Title: Page hero with call to action
 * Slug: tyler-cms-theme/hero
 * Categories: agency, featured
 * Keywords: hero, banner, intro, call to action
 * Description: Full-width brand-color intro with a heading, one sentence, and a Forge button. Uses the approved Section block, so Editors and Reviewers can insert it.
 * Block Types: core/post-content
 * Viewport Width: 1280
 *
 * @package CmsTheme
 */

?>
<!-- wp:cmscoe/section {"align":"full","backgroundColor":"primary","textColor":"on-primary","layout":{"type":"constrained"}} -->
<!-- wp:heading {"level":1,"textColor":"on-primary"} -->
<h1 class="wp-block-heading has-on-primary-color has-text-color"><?php esc_html_e( 'How can we help you today?', 'tyler-cms-theme' ); ?></h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'Find services, apply online, and get answers from the agency.', 'tyler-cms-theme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:cmscoe/forge-button {"text":"<?php echo esc_attr__( 'Browse services', 'tyler-cms-theme' ); ?>","url":"/services/","variant":"tonal"} /-->
<!-- /wp:cmscoe/section -->
