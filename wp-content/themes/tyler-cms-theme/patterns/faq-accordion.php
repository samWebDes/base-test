<?php
/**
 * Title: Questions and answers
 * Slug: tyler-cms-theme/faq-accordion
 * Categories: agency, text
 * Keywords: faq, accordion, questions, answers
 * Description: A heading followed by a Forge Accordion of questions. Use on a page; for the site-wide FAQ list, add FAQ entries instead.
 * Viewport Width: 1280
 *
 * @package CmsTheme
 */

$cmscoe_items = array(
	array( __( 'Who can apply?', 'tyler-cms-theme' ), __( 'Explain eligibility in one or two plain-language sentences.', 'tyler-cms-theme' ) ),
	array( __( 'What do I need to bring?', 'tyler-cms-theme' ), __( 'List the documents and information people need.', 'tyler-cms-theme' ) ),
	array( __( 'How long does it take?', 'tyler-cms-theme' ), __( 'Give a realistic processing time and what happens next.', 'tyler-cms-theme' ) ),
);
?>
<!-- wp:cmscoe/section {"layout":{"type":"constrained"}} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Common questions', 'tyler-cms-theme' ); ?></h2>
<!-- /wp:heading -->
<!-- wp:cmscoe/forge-accordion {"headingLevel":3} -->
<?php foreach ( $cmscoe_items as $cmscoe_item ) : ?>
<!-- wp:cmscoe/forge-accordion-item {"heading":"<?php echo esc_attr( $cmscoe_item[0] ); ?>"} -->
<!-- wp:paragraph -->
<p><?php echo esc_html( $cmscoe_item[1] ); ?></p>
<!-- /wp:paragraph -->
<!-- /wp:cmscoe/forge-accordion-item -->
<?php endforeach; ?>
<!-- /wp:cmscoe/forge-accordion -->
<!-- /wp:cmscoe/section -->
