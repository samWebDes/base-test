<?php
/**
 * Server render for cmscoe/forge-accordion-item. The trigger is a real <button> inside a heading;
 * forge-expansion-panel's trigger controller manages aria-expanded and aria-controls on it.
 *
 * @package CmsCoe\Forge
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks HTML.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$cmscoe_heading = isset( $attributes['heading'] ) ? trim( wp_strip_all_tags( (string) $attributes['heading'] ) ) : '';
if ( '' === $cmscoe_heading ) {
	return;
}
$cmscoe_level = (int) ( $block->context['cmscoe/accordionHeadingLevel'] ?? 3 );
$cmscoe_level = in_array( $cmscoe_level, array( 2, 3, 4 ), true ) ? $cmscoe_level : 3;
$cmscoe_id    = wp_unique_id( 'cmscoe-accordion-' );
?>
<div class="cmscoe-accordion__item">
	<h<?php echo (int) $cmscoe_level; ?> class="cmscoe-accordion__heading">
		<button type="button" class="cmscoe-accordion__trigger" id="<?php echo esc_attr( $cmscoe_id ); ?>-trigger">
			<span><?php echo esc_html( $cmscoe_heading ); ?></span>
			<forge-open-icon id="<?php echo esc_attr( $cmscoe_id ); ?>-icon" aria-hidden="true"></forge-open-icon>
		</button>
	</h<?php echo (int) $cmscoe_level; ?>>
	<forge-expansion-panel id="<?php echo esc_attr( $cmscoe_id ); ?>" trigger="<?php echo esc_attr( $cmscoe_id ); ?>-trigger" open-icon="<?php echo esc_attr( $cmscoe_id ); ?>-icon"<?php echo empty( $attributes['open'] ) ? '' : ' open'; ?>>
		<div class="cmscoe-accordion__panel" role="region" aria-labelledby="<?php echo esc_attr( $cmscoe_id ); ?>-trigger">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks output. ?>
		</div>
	</forge-expansion-panel>
</div>
