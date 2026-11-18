<?php
/**
 * Server render for cmscoe/forge-accordion. forge-accordion keeps only one panel open at a time.
 *
 * @package CmsCoe\Forge
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner blocks HTML (accordion items).
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( $content ) ) {
	return;
}
$cmscoe_inner = empty( $attributes['singleOpen'] ) ? $content : '<forge-accordion>' . $content . '</forge-accordion>';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-accordion' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php echo $cmscoe_inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks output. ?>
</div>
