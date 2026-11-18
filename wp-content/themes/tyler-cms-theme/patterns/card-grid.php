<?php
/**
 * Title: Three feature cards
 * Slug: tyler-cms-theme/card-grid
 * Categories: agency
 * Keywords: cards, grid, columns, features
 * Description: A responsive grid of three Forge cards with heading, text, and a link button. Replaces core Columns for Editors and Reviewers.
 * Viewport Width: 1280
 *
 * @package CmsTheme
 */

$cmscoe_cards = array(
	array( __( 'Services', 'tyler-cms-theme' ), __( 'Programs, licenses, permits, and benefits.', 'tyler-cms-theme' ), __( 'Browse services', 'tyler-cms-theme' ), '/services/' ),
	array( __( 'Locations', 'tyler-cms-theme' ), __( 'Offices and service centers near you, with hours.', 'tyler-cms-theme' ), __( 'Find a location', 'tyler-cms-theme' ), '/locations/' ),
	array( __( 'Events', 'tyler-cms-theme' ), __( 'Public meetings, hearings, and classes.', 'tyler-cms-theme' ), __( 'See events', 'tyler-cms-theme' ), '/events/' ),
);
?>
<!-- wp:cmscoe/section {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"16rem"}} -->
<?php foreach ( $cmscoe_cards as $cmscoe_card ) : ?>
<!-- wp:cmscoe/forge-card -->
<!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php echo esc_html( $cmscoe_card[0] ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html( $cmscoe_card[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:cmscoe/forge-button {"text":"<?php echo esc_attr( $cmscoe_card[2] ); ?>","url":"<?php echo esc_attr( $cmscoe_card[3] ); ?>","variant":"outlined"} /-->
<!-- /wp:cmscoe/forge-card -->
<?php endforeach; ?>
<!-- /wp:cmscoe/section -->
