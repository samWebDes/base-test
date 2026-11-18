<?php
/**
 * Server render for cmscoe/entry-accordion. Reuses the Forge Accordion markup and styles.
 *
 * @package CmsCoe\Platform
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$cmscoe_type = sanitize_key( (string) ( $attributes['postType'] ?? '' ) );
if ( ! $cmscoe_type && is_post_type_archive() ) {
	$cmscoe_type = (string) get_query_var( 'post_type' );
}
if ( ! $cmscoe_type || ! post_type_exists( $cmscoe_type ) || ! get_post_type_object( $cmscoe_type )->public ) {
	return;
}
$cmscoe_entries = get_posts(
	array(
		'post_type'      => $cmscoe_type,
		'post_status'    => 'publish',
		'posts_per_page' => min( 100, max( 1, (int) ( $attributes['max'] ?? 50 ) ) ),
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	)
);
if ( ! $cmscoe_entries ) {
	return;
}
$cmscoe_level = in_array( (int) ( $attributes['headingLevel'] ?? 2 ), array( 2, 3 ), true ) ? (int) $attributes['headingLevel'] : 2;
wp_enqueue_style( 'cmscoe-forge-accordion-style' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-accordion cmscoe-entry-accordion' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php
	foreach ( $cmscoe_entries as $cmscoe_entry ) :
		$cmscoe_id = wp_unique_id( 'cmscoe-entry-accordion-' );
		?>
		<div class="cmscoe-accordion__item">
			<h<?php echo (int) $cmscoe_level; ?> class="cmscoe-accordion__heading">
				<button type="button" class="cmscoe-accordion__trigger" id="<?php echo esc_attr( $cmscoe_id ); ?>-trigger">
					<span><?php echo esc_html( get_the_title( $cmscoe_entry ) ); ?></span>
					<forge-open-icon id="<?php echo esc_attr( $cmscoe_id ); ?>-icon" aria-hidden="true"></forge-open-icon>
				</button>
			</h<?php echo (int) $cmscoe_level; ?>>
			<forge-expansion-panel id="<?php echo esc_attr( $cmscoe_id ); ?>" trigger="<?php echo esc_attr( $cmscoe_id ); ?>-trigger" open-icon="<?php echo esc_attr( $cmscoe_id ); ?>-icon">
				<div class="cmscoe-accordion__panel" role="region" aria-labelledby="<?php echo esc_attr( $cmscoe_id ); ?>-trigger">
					<?php echo apply_filters( 'the_content', $cmscoe_entry->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core content filters. ?>
				</div>
			</forge-expansion-panel>
		</div>
	<?php endforeach; ?>
</div>
