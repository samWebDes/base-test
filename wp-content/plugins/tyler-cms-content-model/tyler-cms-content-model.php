<?php
/**
 * Plugin Name:       Tyler CMS Base Site Content Model
 * Description:       Site content model. Builder (Secure Custom Fields) definitions are saved to and loaded from ./content-model/ in Git. Also registers the meta data contract and structured data for the default agency content types.
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Requires Plugins:  secure-custom-fields, cmscoe-platform
 * Author:            CMS COE
 * License:           GPL-2.0-or-later
 * Text Domain:       tyler-cms-content-model
 * Domain Path:       /languages
 *
 * @package Cms\ContentModel
 */

namespace Cms\ContentModel;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.1.0';
const PREFIX  = 'cms';
const DIR     = __DIR__ . '/content-model';

// Builder definitions live in this plugin, not the theme (content-modeling.md).
add_filter( 'acf/settings/save_json', static fn() => DIR );
add_filter(
	'acf/settings/load_json',
	static function ( array $paths ): array {
		$paths   = array_values( array_diff( $paths, array( get_stylesheet_directory() . '/acf-json' ) ) );
		$paths[] = DIR;
		return $paths;
	}
);
add_filter( 'cmscoe_content_model_dir', static fn() => DIR );

// Site blocks use the site prefix as their namespace; let Editors and Reviewers insert them.
add_filter(
	'cmscoe_approved_block_namespaces',
	static function ( array $namespaces ): array {
		$namespaces[] = PREFIX . '/';
		return $namespaces;
	}
);

// The platform's Active alerts block reads this site's Alert type.
add_filter( 'cmscoe_alert_post_type', static fn() => PREFIX . '_alert' );

// Coordinates feed structured data and map links; they are not useful as visible rows.
add_filter( 'cmscoe_entry_fields_hidden', static fn( array $hidden ) => array_merge( $hidden, array( 'location_latitude', 'location_longitude' ) ) );

require_once __DIR__ . '/includes/meta.php';
require_once __DIR__ . '/includes/schema.php';

/**
 * FAQs read best as an accordion of questions and answers rather than a card directory, and the
 * FAQPage structured data must match what is visible. This template overrides the platform default.
 */
add_action(
	'init',
	static function () {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}
		register_block_template(
			'tyler-cms-content-model//archive-' . PREFIX . '_faq',
			array(
				'title'       => __( 'FAQs', 'tyler-cms-content-model' ),
				'description' => __( 'All frequently asked questions as expandable sections.', 'tyler-cms-content-model' ),
				'post_types'  => array( PREFIX . '_faq' ),
				'content'     => '<!-- wp:template-part {"slug":"header","tagName":"header"} /-->'
					. '<!-- wp:group {"tagName":"main","anchor":"main","layout":{"type":"constrained"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}}} -->'
					. '<main class="wp-block-group" id="main" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">'
					. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . esc_html__( 'Frequently asked questions', 'tyler-cms-content-model' ) . '</h1><!-- /wp:heading -->'
					. '<!-- wp:cmscoe/entry-accordion {"postType":"' . PREFIX . '_faq"} /-->'
					. '</main><!-- /wp:group -->'
					. '<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->',
			)
		);
	}
);
