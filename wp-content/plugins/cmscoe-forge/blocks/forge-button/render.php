<?php
/**
 * Server render for cmscoe/forge-button.
 *
 * @package CmsCoe\Forge
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$cmscoe_text = isset( $attributes['text'] ) ? trim( wp_strip_all_tags( (string) $attributes['text'] ) ) : '';
$cmscoe_url  = isset( $attributes['url'] ) ? esc_url( (string) $attributes['url'] ) : '';

// No link or no text: render nothing rather than an empty, unnamed control.
if ( '' === $cmscoe_text || '' === $cmscoe_url ) {
	return;
}

$cmscoe_variant = in_array( $attributes['variant'] ?? 'raised', array( 'raised', 'outlined', 'tonal' ), true ) ? $attributes['variant'] : 'raised';
$cmscoe_new_tab = ! empty( $attributes['opensInNewTab'] );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-forge-button' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<forge-button variant="<?php echo esc_attr( $cmscoe_variant ); ?>">
		<a href="<?php echo $cmscoe_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url above. ?>"<?php echo $cmscoe_new_tab ? ' target="_blank" rel="noopener"' : ''; ?>>
			<?php echo esc_html( $cmscoe_text ); ?>
			<?php if ( $cmscoe_new_tab ) : ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'cmscoe-forge' ); ?></span>
			<?php endif; ?>
		</a>
	</forge-button>
</div>
