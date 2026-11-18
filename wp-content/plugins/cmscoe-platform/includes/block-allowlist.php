<?php
/**
 * Role-based block allowlist. Source of truth: the "Core block access by role" table in the
 * skill's editorial-workflow.md.
 *
 * Administrators: all blocks. Everyone else: core text, media, embed blocks, required child blocks,
 * and approved namespaces (cmscoe/ plus the site prefix, added by the site content-model plugin).
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\BlockAllowlist;

defined( 'ABSPATH' ) || exit;

/**
 * Core blocks allowed for non-administrators.
 *
 * Note: core/list-item is a required child of core/list; core/missing keeps unknown
 * blocks recoverable; core/block lets editors use synced patterns built from allowed blocks.
 *
 * @return string[]
 */
function restricted_core_blocks(): array {
	$text = array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/quote', 'core/code', 'core/preformatted', 'core/pullquote', 'core/verse', 'core/table', 'core/footnotes' );
	// core/details and core/cover are excluded: accordions and layout containers come from Forge.
	$media = array( 'core/image', 'core/gallery', 'core/audio', 'core/video', 'core/file' );
	$embed = array( 'core/embed' );
	$infra = array( 'core/missing', 'core/block' );
	return array_merge( $text, $media, $embed, $infra );
}

/**
 * Block namespaces always allowed (Forge-backed platform blocks and site blocks).
 *
 * @return string[]
 */
function approved_namespaces(): array {
	return (array) apply_filters( 'cmscoe_approved_block_namespaces', array( 'cmscoe/' ) );
}

/**
 * Compute allowed blocks for a user.
 *
 * @param \WP_User|null $user User (defaults to current).
 * @return true|string[] True for all blocks.
 */
function allowed_for_user( $user = null ) {
	$user = $user ?? wp_get_current_user();
	if ( $user && in_array( 'administrator', (array) $user->roles, true ) ) {
		return true;
	}
	$allowed = restricted_core_blocks();
	foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type ) {
		foreach ( approved_namespaces() as $ns ) {
			if ( str_starts_with( $name, $ns ) ) {
				$allowed[] = $name;
			}
		}
	}
	return array_values( array_unique( $allowed ) );
}

/**
 * Filter callback.
 *
 * @param bool|string[]            $allowed Allowed blocks.
 * @param \WP_Block_Editor_Context $context Context.
 * @return bool|string[]
 */
function filter_allowed( $allowed, $context ) {
	unset( $context ); // Applies to every post type, including builder-registered ones.
	$mine = allowed_for_user();
	if ( true === $mine ) {
		return $allowed;
	}
	if ( is_array( $allowed ) ) {
		return array_values( array_intersect( $allowed, $mine ) );
	}
	return $mine;
}
add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\\filter_allowed', 20, 2 );
