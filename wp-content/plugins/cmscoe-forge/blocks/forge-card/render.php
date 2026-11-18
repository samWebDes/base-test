<?php
/**
 * Server render for cmscoe/forge-card.
 *
 * @package CmsCoe\Forge
 *
 * @var array  $attributes Block attributes.
 * @var string $content    Inner blocks HTML (already rendered and escaped by core).
 */

defined( 'ABSPATH' ) || exit;

if ( '' === trim( wp_strip_all_tags( $content, true ) ) && false === strpos( $content, '<img' ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-forge-card' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<forge-card<?php echo empty( $attributes['raised'] ) ? '' : ' raised'; ?>>
		<div class="cmscoe-forge-card__body">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks output. ?>
		</div>
	</forge-card>
</div>
