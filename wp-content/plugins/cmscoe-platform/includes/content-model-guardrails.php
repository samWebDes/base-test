<?php
/**
 * Guardrails applied to every builder-registered (non-core) post type and taxonomy
 * (content-modeling.md "Platform guardrails"), plus builder capability gate and environment lock.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\ContentModel;

use CmsCoe\Platform\Environment;

defined( 'ABSPATH' ) || exit;

const RESERVED = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'action', 'author', 'order', 'theme', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation', 'wp_font_family', 'wp_font_face', 'category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'type', 'term', 'taxonomy', 'name', 'year', 'day', 'month', 'search', 'feed', 'page_id', 'p', 'cat', 'tag' );

/**
 * Guardrail violations collected during registration.
 *
 * @var array<string,string[]>
 */
$GLOBALS['cmscoe_content_model_violations'] = array();

/**
 * Record a guardrail violation (surfaced in Site Health and to Administrators).
 *
 * @param string $key     Type key.
 * @param string $message Message.
 */
function violation( string $key, string $message ): void {
	$GLOBALS['cmscoe_content_model_violations'][ $key ][] = $message;
}

/**
 * Is this a type the platform should govern (not core/internal)?
 *
 * @param string $key  Key.
 * @param array  $args Args.
 * @return bool
 */
function governed( string $key, array $args ): bool {
	return empty( $args['_builtin'] ) && ! str_starts_with( $key, 'wp_' ) && ! str_starts_with( $key, 'acf-' ) && ! empty( $args['public'] );
}

/**
 * Post type guardrails.
 *
 * @param array  $args Args.
 * @param string $key  Post type key.
 * @return array
 */
function post_type_args( array $args, string $key ): array {
	if ( ! governed( $key, $args ) ) {
		return $args;
	}
	if ( in_array( $key, RESERVED, true ) ) {
		violation( $key, __( 'Post type key is a reserved WordPress name.', 'cmscoe-platform' ) );
	}
	if ( strlen( $key ) > 20 ) {
		violation( $key, __( 'Post type key exceeds 20 characters.', 'cmscoe-platform' ) );
	}
	$args['show_in_rest'] = true;
	$args['map_meta_cap'] = true;
	// Own capability set instead of inheriting "post".
	$cap = $args['capability_type'] ?? 'post';
	if ( 'post' === $cap || array( 'post', 'posts' ) === $cap ) {
		$args['capability_type'] = array( $key, $key . 's' );
	}
	$pair = is_array( $args['capability_type'] ) ? $args['capability_type'] : array( $args['capability_type'], $args['capability_type'] . 's' );
	add_filter(
		'cmscoe_managed_capability_types',
		static function ( array $types ) use ( $key, $pair ) {
			$types[ $key ] = array( (string) $pair[0], (string) $pair[1] );
			return $types;
		}
	);
	return $args;
}
add_filter( 'register_post_type_args', __NAMESPACE__ . '\\post_type_args', 20, 2 );

/**
 * Taxonomy guardrails.
 *
 * @param array        $args        Args.
 * @param string       $key         Taxonomy key.
 * @param string|array $object_type Object types.
 * @return array
 */
function taxonomy_args( array $args, string $key, $object_type ): array {
	unset( $object_type );
	if ( ! governed( $key, $args ) ) {
		return $args;
	}
	if ( in_array( $key, RESERVED, true ) ) {
		violation( $key, __( 'Taxonomy key is a reserved WordPress name.', 'cmscoe-platform' ) );
	}
	if ( strlen( $key ) > 32 ) {
		violation( $key, __( 'Taxonomy key exceeds 32 characters.', 'cmscoe-platform' ) );
	}
	$args['show_in_rest'] = true;
	return $args;
}
add_filter( 'register_taxonomy_args', __NAMESPACE__ . '\\taxonomy_args', 20, 3 );

/**
 * Flush rewrites once when the registered public type/taxonomy set changes (deploy or edit), never per request.
 */
function maybe_flush_rewrites(): void {
	$keys = array_merge( array_keys( get_post_types( array( 'public' => true ) ) ), array_keys( get_taxonomies( array( 'public' => true ) ) ) );
	sort( $keys );
	$hash = md5( implode( ',', $keys ) );
	if ( get_option( 'cmscoe_rewrite_hash' ) !== $hash ) {
		flush_rewrite_rules( false );
		update_option( 'cmscoe_rewrite_hash', $hash, false );
	}
}
add_action( 'init', __NAMESPACE__ . '\\maybe_flush_rewrites', 999 );

/**
 * Directory the site's builder writes definitions to (set by the site content-model plugin).
 *
 * @return string
 */
function definitions_dir(): string {
	return (string) apply_filters( 'cmscoe_content_model_dir', WP_CONTENT_DIR . '/plugins' );
}

/**
 * Gate the SCF/ACF builder behind an Administrator-only capability.
 *
 * @return string
 */
function builder_capability(): string {
	return 'cmscoe_manage_content_model';
}
add_filter( 'acf/settings/capability', __NAMESPACE__ . '\\builder_capability' );

/**
 * Hide builder UI where definitions cannot be written (Test, Live, Dev in Git mode).
 *
 * @param bool $show Show admin.
 * @return bool
 */
function builder_show_admin( $show ): bool {
	return (bool) $show && Environment\content_model_editable( definitions_dir() ) && current_user_can( builder_capability() );
}
add_filter( 'acf/settings/show_admin', __NAMESPACE__ . '\\builder_show_admin' );

/**
 * Locked-environment notice for Administrators.
 */
function locked_notice(): void {
	if ( ! current_user_can( builder_capability() ) || Environment\content_model_editable( definitions_dir() ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-page', 'plugins' ), true ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>' . esc_html__( 'Content types and custom fields are read-only in this environment. Make content-model changes in DDEV or a Pantheon Dev/Multidev environment in SFTP mode, commit the generated files, and deploy.', 'cmscoe-platform' ) . '</p></div>';
}
add_action( 'admin_notices', __NAMESPACE__ . '\\locked_notice' );

/**
 * Site Health test reporting guardrail violations.
 *
 * @param array $tests Tests.
 * @return array
 */
function site_health( array $tests ): array {
	$tests['direct']['cmscoe_content_model'] = array(
		'label' => __( 'CMS COE content model guardrails', 'cmscoe-platform' ),
		'test'  => static function () {
			$violations = $GLOBALS['cmscoe_content_model_violations'];
			return array(
				'label'       => $violations ? __( 'Content model guardrail violations found', 'cmscoe-platform' ) : __( 'Content model passes CMS COE guardrails', 'cmscoe-platform' ),
				'status'      => $violations ? 'critical' : 'good',
				'badge'       => array(
					'label' => 'CMS COE',
					'color' => $violations ? 'red' : 'green',
				),
				'description' => '<p>' . esc_html( $violations ? (string) wp_json_encode( $violations ) : __( 'All builder-registered types use reserved-name and length rules.', 'cmscoe-platform' ) ) . '</p>',
				'test'        => 'cmscoe_content_model',
			);
		},
	);
	return $tests;
}
add_filter( 'site_status_tests', __NAMESPACE__ . '\\site_health' );
