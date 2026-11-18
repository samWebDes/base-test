<?php
/**
 * Server render for cmscoe/section. Layout classes are added by core layout support.
 *
 * @package CmsCoe\Forge
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner blocks HTML.
 */

defined( 'ABSPATH' ) || exit;

$cmscoe_tag       = ( isset( $attributes['tagName'] ) && 'div' === $attributes['tagName'] ) ? 'div' : 'section';
$cmscoe_label     = isset( $attributes['label'] ) ? trim( (string) $attributes['label'] ) : '';
$cmscoe_has_image = ! empty( $attributes['style']['background']['backgroundImage'] );
// Background images always get the overlay so text keeps 4.5:1 contrast whatever the photo.
$cmscoe_extra = array( 'class' => 'cmscoe-section' . ( $cmscoe_has_image ? ' cmscoe-section--image' : '' ) );
if ( '' !== $cmscoe_label ) {
	$cmscoe_extra['aria-label'] = $cmscoe_label;
}
printf(
	'<%1$s %2$s>%3$s</%1$s>',
	tag_escape( $cmscoe_tag ),
	get_block_wrapper_attributes( $cmscoe_extra ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
