<?php
/**
 * Plugin Name:       CMS COE Forge
 * Description:       Registers the shared Tyler Forge runtime once and exposes Forge-backed blocks (button, card, accordion, section) to the block editor.
 * Version:           0.3.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            CMS COE
 * License:           GPL-2.0-or-later
 * Text Domain:       cmscoe-forge
 * Domain Path:       /languages
 *
 * @package CmsCoe\Forge
 */

namespace CmsCoe\Forge;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.3.0';

/**
 * Forge runtime version from the build manifest (falls back to plugin version).
 *
 * @return string
 */
function forge_version(): string {
	static $version = null;
	if ( null === $version ) {
		$manifest = __DIR__ . '/build/manifest.json';
		$data     = is_readable( $manifest ) ? json_decode( (string) file_get_contents( $manifest ), true ) : array(); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$version  = is_array( $data ) && ! empty( $data['forge'] ) ? (string) $data['forge'] : VERSION;
	}
	return $version;
}

/**
 * Register the shared Forge runtime exactly once. Other plugins/themes must depend on the
 * `cmscoe-forge-runtime` handle instead of shipping their own copy of Forge.
 */
function register_runtime(): void {
	if ( wp_script_is( 'cmscoe-forge-runtime', 'registered' ) ) {
		return;
	}
	$url = plugins_url( 'build/', __FILE__ );
	wp_register_script( 'cmscoe-forge-runtime', $url . 'forge-runtime.js', array(), forge_version(), array( 'strategy' => 'defer' ) );
	wp_register_style( 'cmscoe-forge-theme', $url . 'forge-theme.css', array(), forge_version() );
	wp_register_style( 'cmscoe-forge-typography', $url . 'forge-typography.css', array( 'cmscoe-forge-theme' ), forge_version() );
}
add_action( 'init', __NAMESPACE__ . '\\register_runtime', 5 );

/**
 * Register Forge-backed blocks from block.json metadata.
 */
function register_blocks(): void {
	foreach ( array( 'forge-button', 'forge-card', 'forge-accordion', 'forge-accordion-item', 'section' ) as $block ) {
		register_block_type( __DIR__ . '/blocks/' . $block );
	}
	wp_set_script_translations( 'cmscoe-forge-button-editor-script', 'cmscoe-forge', __DIR__ . '/languages' );
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

/**
 * Add a "Forge components" inserter category so editors can find the approved design blocks.
 *
 * @param array $categories Block categories.
 * @return array
 */
function block_category( array $categories ): array {
	array_unshift(
		$categories,
		array(
			'slug'  => 'cmscoe-forge',
			'title' => __( 'Forge components', 'cmscoe-forge' ),
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', __NAMESPACE__ . '\\block_category' );

/**
 * Load the Forge theme tokens in the block editor iframe so previews match the frontend.
 */
function editor_assets(): void {
	wp_enqueue_style( 'cmscoe-forge-theme' );
}
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\\editor_assets' );
