<?php
/**
 * Roles and capabilities.
 *
 * Capability matrix (see the starter kit docs/editorial-workflow.md):
 * - Administrator: everything, plus cmscoe_manage_content_model, cmscoe_manage_admin_skin, cmscoe_edit_design, cmscoe_duplicate_pages.
 * - Editor: creates, edits, and deletes unpublished content of every type, including others'; cannot
 *   publish, edit published content directly, or delete published content. Changes to live content go
 *   through PublishPress Revisions (approval-workflow.php). No edit_theme_options (no Site Editor,
 *   templates, Global Styles, or navigation). cmscoe_duplicate_pages. Content only: see design-lock.php.
 * - Reviewer: approves and publishes. Edits, publishes, and deletes all content of every type, including
 *   published content and submitted revisions; cmscoe_duplicate_pages. No settings, users, plugins, or design.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Roles;

defined( 'ABSPATH' ) || exit;

const REVIEWER     = 'cmscoe_reviewer';
const OPTION_TYPES = 'cmscoe_platform_cpt_caps_hash';

/**
 * Primitive capabilities for one capability type (core "posts"/"pages" or a builder type's plural).
 *
 * @param string $plural Capability type plural, e.g. "posts" or "dhs_services".
 * @return array{submit:string[],publish:string[]} submit = Editor (unpublished content only); publish = the
 *         extra caps that make a Reviewer the approver.
 */
function type_caps( string $plural ): array {
	return array(
		'submit'  => array( "edit_{$plural}", "edit_others_{$plural}", "edit_private_{$plural}", "read_private_{$plural}", "delete_{$plural}", "delete_others_{$plural}", "delete_private_{$plural}" ),
		'publish' => array( "publish_{$plural}", "edit_published_{$plural}", "delete_published_{$plural}" ),
	);
}

/**
 * Reviewer base capabilities: approve and publish all content; no options, users, plugins, or design.
 *
 * @return array<string,bool>
 */
function reviewer_caps(): array {
	$caps = array(
		'read'                   => true,
		'upload_files'           => true,
		'cmscoe_duplicate_pages' => true,
	);
	foreach ( array( 'posts', 'pages' ) as $plural ) {
		foreach ( array_merge( ...array_values( type_caps( $plural ) ) ) as $cap ) {
			$caps[ $cap ] = true;
		}
	}
	return $caps;
}

/**
 * Create/refresh roles. Idempotent.
 */
function install(): void {
	$reviewer = get_role( REVIEWER );
	if ( ! $reviewer ) {
		add_role( REVIEWER, 'Reviewer', reviewer_caps() );
	} else {
		foreach ( reviewer_caps() as $cap => $grant ) {
			$reviewer->add_cap( $cap, $grant );
		}
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array( 'cmscoe_manage_content_model', 'cmscoe_manage_admin_skin', 'cmscoe_edit_design', 'cmscoe_duplicate_pages' ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}
	$editor = get_role( 'editor' );
	if ( $editor ) {
		$editor->add_cap( 'cmscoe_duplicate_pages' );
		// Explicitly deny platform-admin caps even if a plugin granted them.
		// Layout and design are Administrator-only: edit_theme_options opens the Site Editor, templates,
		// template parts, Global Styles, and navigation.
		foreach ( array( 'cmscoe_manage_content_model', 'cmscoe_manage_admin_skin', 'cmscoe_edit_design', 'edit_theme_options' ) as $cap ) {
			$editor->remove_cap( $cap );
		}
		// Editors submit; Reviewers publish. Live content changes only through an approved revision.
		foreach ( array( 'posts', 'pages' ) as $plural ) {
			foreach ( type_caps( $plural )['publish'] as $cap ) {
				$editor->remove_cap( $cap );
			}
		}
	}
	delete_option( OPTION_TYPES ); // Force CPT capability resync.
}

/**
 * Deactivation: leave roles in place (content ownership), but drop the resync hash.
 * Full role removal belongs in uninstall.php after content reassignment.
 */
function deactivate(): void {
	delete_option( OPTION_TYPES );
}

/**
 * Map of post type => capability_type plural for every type whose caps the platform manages.
 * Populated by the content-model guardrail.
 *
 * @return array<string,array{0:string,1:string}>
 */
function managed_types(): array {
	return (array) apply_filters( 'cmscoe_managed_capability_types', array() );
}

/**
 * Grant primitive caps for managed post types to roles per the matrix. Runs only when the set of
 * managed types changes (hash stored), never as a per-request write.
 */
function sync_type_caps(): void {
	$types = managed_types();
	ksort( $types );
	$hash = md5( (string) wp_json_encode( $types ) . SCHEMA_VERSION_TAG );
	if ( get_option( OPTION_TYPES ) === $hash ) {
		return;
	}
	foreach ( $types as $pair ) {
		$caps = type_caps( $pair[1] );
		$all  = array_merge( $caps['submit'], $caps['publish'] );
		foreach ( array( 'administrator', REVIEWER ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $all as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( $caps['submit'] as $cap ) {
				$editor->add_cap( $cap );
			}
			foreach ( $caps['publish'] as $cap ) {
				$editor->remove_cap( $cap );
			}
		}
	}
	update_option( OPTION_TYPES, $hash, false );
}
const SCHEMA_VERSION_TAG = 'v2'; // v2: Editors submit, Reviewers publish.
add_action( 'init', __NAMESPACE__ . '\\sync_type_caps', 99 );
