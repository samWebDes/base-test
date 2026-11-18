<?php
/**
 * Plugin Name:       CMS COE Platform
 * Description:       Shared CMS COE platform rules and content blocks: roles and capabilities, approval workflow (PublishPress Revisions), role-based block allowlist, content-model guardrails and environment lock, default content-type templates, Entry fields / Directory / Active alerts blocks, page duplication, how-to dashboard widget, Admin skin and login page.
 * Version:           0.4.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Author:            CMS COE
 * License:           GPL-2.0-or-later
 * Text Domain:       cmscoe-platform
 * Domain Path:       /languages
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform;

defined( 'ABSPATH' ) || exit;

const VERSION        = '0.4.0';
const SCHEMA_VERSION = 3; // 2: design lock (Editors lose edit_theme_options). 3: Editors submit, Reviewers publish.
const OPTION_SCHEMA  = 'cmscoe_platform_schema_version';

require_once __DIR__ . '/includes/environment.php';
require_once __DIR__ . '/includes/roles.php';
require_once __DIR__ . '/includes/approval-workflow.php';
require_once __DIR__ . '/includes/block-allowlist.php';
require_once __DIR__ . '/includes/design-lock.php';
require_once __DIR__ . '/includes/content-model-guardrails.php';
require_once __DIR__ . '/includes/content-templates.php';
require_once __DIR__ . '/includes/duplicate-page.php';
require_once __DIR__ . '/includes/admin-skin.php';
require_once __DIR__ . '/includes/how-to-widget.php';
require_once __DIR__ . '/includes/hardening.php';
require_once __DIR__ . '/includes/discoverability.php';

/**
 * Register the platform's content blocks (Entry fields, Directory, Active alerts).
 */
function register_blocks(): void {
	foreach ( array( 'entry-fields', 'directory', 'entry-accordion', 'active-alerts' ) as $block ) {
		register_block_type( __DIR__ . '/blocks/' . $block );
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_blocks' );

register_activation_hook( __FILE__, __NAMESPACE__ . '\\Roles\\install' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\Roles\\deactivate' );

/**
 * Idempotent, version-gated upgrade routine (runs once per schema bump, also after a Pantheon deploy).
 */
function maybe_upgrade(): void {
	if ( (int) get_option( OPTION_SCHEMA, 0 ) >= SCHEMA_VERSION ) {
		return;
	}
	Roles\install();
	update_option( OPTION_SCHEMA, SCHEMA_VERSION, false );
}
add_action( 'init', __NAMESPACE__ . '\\maybe_upgrade', 1 );

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'cmscoe-platform', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);
