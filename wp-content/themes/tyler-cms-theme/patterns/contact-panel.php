<?php
/**
 * Title: Contact panel
 * Slug: tyler-cms-theme/contact-panel
 * Categories: agency, contact
 * Keywords: contact, phone, email, address, hours
 * Description: Three Forge cards for phone, email, and in-person contact. Replace the sample details.
 * Viewport Width: 1280
 *
 * @package CmsTheme
 */

$cmscoe_ways = array(
	array( __( 'Call', 'tyler-cms-theme' ), __( '555-555-0100, Monday to Friday, 8 a.m. to 5 p.m.', 'tyler-cms-theme' ) ),
	array( __( 'Email', 'tyler-cms-theme' ), __( 'info@example.gov. We reply within two business days.', 'tyler-cms-theme' ) ),
	array( __( 'Visit', 'tyler-cms-theme' ), __( 'Find an office near you on the Locations page.', 'tyler-cms-theme' ) ),
);
?>
<!-- wp:cmscoe/section {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"14rem"}} -->
<?php foreach ( $cmscoe_ways as $cmscoe_way ) : ?>
<!-- wp:cmscoe/forge-card -->
<!-- wp:heading {"level":2,"fontSize":"large"} -->
<h2 class="wp-block-heading has-large-font-size"><?php echo esc_html( $cmscoe_way[0] ); ?></h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p><?php echo esc_html( $cmscoe_way[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:cmscoe/forge-card -->
<?php endforeach; ?>
<!-- /wp:cmscoe/section -->
