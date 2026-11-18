<?php
/**
 * Default single and archive templates for every governed custom post type, registered from this
 * plugin so content types render their fields with any block theme (architecture-and-platform.md:
 * post-type templates ship with plugins). A template with the same slug in the active theme, or one
 * registered by a site plugin, takes precedence.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\ContentTemplates;

defined( 'ABSPATH' ) || exit;

/**
 * Governed post types: public, non-builtin, not internal.
 *
 * @return \WP_Post_Type[]
 */
function governed_types(): array {
	$types = get_post_types(
		array(
			'public'   => true,
			'_builtin' => false,
		),
		'objects'
	);
	return array_filter(
		$types,
		static fn( \WP_Post_Type $type ) => ! str_starts_with( $type->name, 'wp_' ) && ! str_starts_with( $type->name, 'acf-' )
	);
}

/**
 * Public taxonomies attached to a post type.
 *
 * @param string $post_type Post type.
 * @return string[]
 */
function type_taxonomies( string $post_type ): array {
	return array_values( get_object_taxonomies( $post_type ) ? array_intersect( get_object_taxonomies( $post_type ), get_taxonomies( array( 'public' => true ) ) ) : array() );
}

/**
 * Template markup for a single entry.
 *
 * @param \WP_Post_Type $type Post type.
 * @return string
 */
function single_markup( \WP_Post_Type $type ): string {
	$terms = '';
	foreach ( type_taxonomies( $type->name ) as $taxonomy ) {
		$tax    = get_taxonomy( $taxonomy );
		$terms .= '<!-- wp:post-terms ' . wp_json_encode(
			array(
				'term'   => $taxonomy,
				'prefix' => $tax->labels->singular_name . ': ',
			)
		) . ' /-->';
	}
	$archive = $type->has_archive ? get_post_type_archive_link( $type->name ) : '';
	$back    = $archive ? '<!-- wp:cmscoe/forge-button ' . wp_json_encode(
		array(
			/* translators: %s: post type plural label. */
			'text'    => sprintf( __( 'All %s', 'cmscoe-platform' ), $type->labels->name ),
			'url'     => wp_make_link_relative( $archive ),
			'variant' => 'outlined',
		)
	) . ' /-->' : '';
	return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
		. '<!-- wp:group {"tagName":"main","anchor":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}}} -->'
		. '<main class="wp-block-group" id="main" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">'
		. '<!-- wp:post-title {"level":1} /-->'
		. '<!-- wp:post-featured-image {"align":"wide"} /-->'
		. '<!-- wp:cmscoe/entry-fields /-->'
		. '<!-- wp:post-content {"layout":{"type":"constrained"}} /-->'
		. $terms
		. '<!-- wp:post-date {"displayType":"modified","format":"F j, Y","fontSize":"small"} /-->'
		. $back
		. '</main><!-- /wp:group -->'
		. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}

/**
 * Template markup for an archive (filterable directory).
 *
 * @param \WP_Post_Type $type Post type.
 * @return string
 */
function archive_markup( \WP_Post_Type $type ): string {
	return '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
		. '<!-- wp:group {"tagName":"main","anchor":"main","layout":{"type":"constrained","contentSize":"80rem"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}}} -->'
		. '<main class="wp-block-group" id="main" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">'
		. '<!-- wp:query-title {"type":"archive","showPrefix":false,"level":1} /-->'
		. '<!-- wp:term-description /-->'
		. '<!-- wp:cmscoe/directory ' . wp_json_encode(
			array(
				'postType'   => $type->name,
				'taxonomies' => type_taxonomies( $type->name ),
			)
		) . ' /-->'
		. '</main><!-- /wp:group -->'
		. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->';
}

/**
 * Register templates. Runs late on init so builder-registered types exist.
 */
function register(): void {
	if ( ! function_exists( 'register_block_template' ) ) {
		return;
	}
	$registry = \WP_Block_Templates_Registry::get_instance();
	foreach ( governed_types() as $type ) {
		$templates = array(
			'single-' . $type->name => array(
				/* translators: %s: post type singular label. */
				sprintf( __( 'Single %s', 'cmscoe-platform' ), $type->labels->singular_name ),
				single_markup( $type ),
			),
		);
		if ( $type->has_archive ) {
			$templates[ 'archive-' . $type->name ] = array(
				/* translators: %s: post type plural label. */
				sprintf( __( '%s directory', 'cmscoe-platform' ), $type->labels->name ),
				archive_markup( $type ),
			);
		}
		foreach ( $templates as $slug => list( $title, $content ) ) {
			// A site plugin that registered its own template for this slug wins.
			foreach ( $registry->get_all_registered() as $registered ) {
				if ( $registered->slug === $slug ) {
					continue 2;
				}
			}
			register_block_template(
				'cmscoe-platform//' . $slug,
				array(
					'title'       => $title,
					'description' => __( 'Default CMS COE template. Override it with a theme template of the same slug.', 'cmscoe-platform' ),
					'content'     => $content,
					'post_types'  => array( $type->name ),
				)
			);
		}
	}
}
add_action( 'init', __NAMESPACE__ . '\\register', 200 );
