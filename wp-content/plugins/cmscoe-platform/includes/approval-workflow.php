<?php
/**
 * Approval workflow integration with PublishPress Revisions (wordpress.org slug "revisionary").
 *
 * Editors cannot publish or edit published content (roles.php), so WordPress sends their new content
 * to Pending Review and Revisions turns their edits to live content into revisions that stay unpublished
 * until a Reviewer or Administrator approves them. Approval in Revisions requires being able to edit the
 * published original, which only Reviewers and Administrators can.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\Approval;

use CmsCoe\Platform\Roles;

defined( 'ABSPATH' ) || exit;

// "Submitted for review" emails go to the roles that can approve (the plugin default is administrator,editor).
if ( ! defined( 'RVY_MONITOR_ROLES' ) ) {
	define( 'RVY_MONITOR_ROLES', 'administrator,' . Roles\REVIEWER );
}

/**
 * Keep every content type in the revision workflow, including types Administrators add later in the
 * builder. Revisions only enables new types itself until its settings are first saved.
 *
 * @param array $types Post type => enabled.
 * @return array
 */
function enabled_post_types( $types ): array {
	$types = (array) $types;
	foreach ( array_merge( array( 'post', 'page' ), array_keys( Roles\managed_types() ) ) as $type ) {
		$types[ $type ] = true;
	}
	return $types;
}
add_filter( 'revisionary_enabled_post_types', __NAMESPACE__ . '\\enabled_post_types' );

/**
 * Hide the "Upgrade to Pro" banner on the revision queue from Editors and Reviewers; Administrators,
 * who manage plugins, still see it.
 *
 * @param array $settings Top-notice settings keyed by plugin.
 * @return array
 */
function hide_upsell( $settings ): array {
	$settings = (array) $settings;
	if ( ! current_user_can( 'manage_options' ) ) {
		unset( $settings['revisionary'] );
	}
	return $settings;
}
add_filter( 'pp_version_notice_top_notice_settings', __NAMESPACE__ . '\\hide_upsell', 20 );
add_filter(
	'publishpress_wp_reviews_display_banner_revisionary',
	static fn( $show ) => $show && current_user_can( 'manage_options' ),
	20
);

/**
 * Whether PublishPress Revisions is active.
 *
 * @return bool
 */
function revisions_active(): bool {
	return defined( 'PUBLISHPRESS_REVISIONS_VERSION' );
}

/**
 * Site Health: without Revisions, Editors cannot change published content at all.
 *
 * @param array $tests Tests.
 * @return array
 */
function site_health( array $tests ): array {
	$tests['direct']['cmscoe_approval_workflow'] = array(
		'label' => __( 'CMS COE approval workflow', 'cmscoe-platform' ),
		'test'  => static function () {
			$ok = revisions_active();
			return array(
				'label'       => $ok ? __( 'Approval workflow is active', 'cmscoe-platform' ) : __( 'PublishPress Revisions is not active', 'cmscoe-platform' ),
				'status'      => $ok ? 'good' : 'critical',
				'badge'       => array(
					'label' => 'CMS COE',
					'color' => $ok ? 'green' : 'red',
				),
				'description' => '<p>' . esc_html( $ok ? __( 'Editors submit changes to published content for review; Reviewers and Administrators approve and publish them.', 'cmscoe-platform' ) : __( 'Editors cannot publish or edit published content, so without PublishPress Revisions they cannot submit changes to live pages. Activate PublishPress Revisions.', 'cmscoe-platform' ) ) . '</p>',
				'test'        => 'cmscoe_approval_workflow',
			);
		},
	);
	return $tests;
}
add_filter( 'site_status_tests', __NAMESPACE__ . '\\site_health' );
