<?php
/**
 * Title: Call to action band
 * Slug: tyler-cms-theme/call-to-action
 * Categories: agency, call-to-action
 * Keywords: cta, apply, contact, banner
 * Description: A tinted band with a short heading, one sentence, and primary and secondary Forge buttons.
 * Viewport Width: 1280
 *
 * @package CmsTheme
 */

?>
<!-- wp:cmscoe/section {"align":"full","backgroundColor":"primary-tint","layout":{"type":"constrained"}} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Ready to apply?', 'tyler-cms-theme' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php esc_html_e( 'Most applications take about 15 minutes. Have your documents ready before you start.', 'tyler-cms-theme' ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:cmscoe/section {"tagName":"div","layout":{"type":"flex","flexWrap":"wrap"}} -->
<!-- wp:cmscoe/forge-button {"text":"<?php echo esc_attr__( 'Start an application', 'tyler-cms-theme' ); ?>","url":"/services/","variant":"raised"} /-->
<!-- wp:cmscoe/forge-button {"text":"<?php echo esc_attr__( 'Contact us', 'tyler-cms-theme' ); ?>","url":"/contact/","variant":"outlined"} /-->
<!-- /wp:cmscoe/section -->
<!-- /wp:cmscoe/section -->
