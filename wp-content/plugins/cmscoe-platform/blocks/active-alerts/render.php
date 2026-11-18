<?php
/**
 * Server render for cmscoe/active-alerts. Alert entries use the standard field names
 * alert_severity (info|warning|critical) and alert_expires (Y-m-d H:i:s, site time).
 *
 * @package CmsCoe\Platform
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post type that holds alerts. The site content-model plugin sets this.
 *
 * @param string $post_type Post type key.
 */
$cmscoe_type = sanitize_key( (string) ( $attributes['postType'] ?? '' ) );
$cmscoe_type = $cmscoe_type ? $cmscoe_type : (string) apply_filters( 'cmscoe_alert_post_type', '' );
if ( ! $cmscoe_type || ! post_type_exists( $cmscoe_type ) ) {
	return;
}

$cmscoe_alerts = get_posts(
	array(
		'post_type'      => $cmscoe_type,
		'post_status'    => 'publish',
		'posts_per_page' => min( 10, max( 1, (int) ( $attributes['max'] ?? 3 ) ) ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		// Alerts are few and short-lived, so this small meta query is bounded.
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'     => 'alert_expires',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			),
			array(
				'key'     => 'alert_expires',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'   => 'alert_expires',
				'value' => '',
			),
		),
	)
);
if ( ! $cmscoe_alerts ) {
	return;
}

$cmscoe_themes = array(
	'info'     => 'info',
	'warning'  => 'warning',
	'critical' => 'error',
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'cmscoe-active-alerts' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?> role="region" aria-label="<?php esc_attr_e( 'Alerts', 'cmscoe-platform' ); ?>">
	<?php
	foreach ( $cmscoe_alerts as $cmscoe_alert ) :
		$cmscoe_severity = (string) get_post_meta( $cmscoe_alert->ID, 'alert_severity', true );
		$cmscoe_severity = isset( $cmscoe_themes[ $cmscoe_severity ] ) ? $cmscoe_severity : 'info';
		$cmscoe_labels   = array(
			'info'     => __( 'Notice', 'cmscoe-platform' ),
			'warning'  => __( 'Warning', 'cmscoe-platform' ),
			'critical' => __( 'Urgent', 'cmscoe-platform' ),
		);
		?>
		<forge-inline-message theme="<?php echo esc_attr( $cmscoe_themes[ $cmscoe_severity ] ); ?>" class="cmscoe-active-alerts__item">
			<?php // Severity is spelled out in text so it is not conveyed by color alone. ?>
			<strong slot="title"><?php echo esc_html( $cmscoe_labels[ $cmscoe_severity ] . ': ' . get_the_title( $cmscoe_alert ) ); ?></strong>
			<?php if ( has_excerpt( $cmscoe_alert ) ) : ?>
				<?php echo esc_html( get_the_excerpt( $cmscoe_alert ) ); ?>
			<?php endif; ?>
			<?php /* translators: %s: alert title. */ ?>
			<a href="<?php echo esc_url( get_permalink( $cmscoe_alert ) ); ?>"><?php esc_html_e( 'Details', 'cmscoe-platform' ); ?><span class="screen-reader-text"><?php echo esc_html( sprintf( __( ' about %s', 'cmscoe-platform' ), get_the_title( $cmscoe_alert ) ) ); ?></span></a>
		</forge-inline-message>
	<?php endforeach; ?>
</div>
