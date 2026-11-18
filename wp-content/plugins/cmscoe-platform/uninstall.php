<?php
/**
 * Uninstall: remove platform options. The Reviewer role is removed only if no users hold it,
 * so content ownership is never orphaned silently.
 *
 * @package CmsCoe\Platform
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

foreach ( array( 'cmscoe_admin_skin', 'cmscoe_platform_schema_version', 'cmscoe_platform_cpt_caps_hash', 'cmscoe_rewrite_hash' ) as $cmscoe_option ) {
	delete_option( $cmscoe_option );
}
// Give Editors back the core capability the platform removed.
$cmscoe_editor_role = get_role( 'editor' );
if ( $cmscoe_editor_role ) {
	$cmscoe_editor_role->add_cap( 'edit_theme_options' );
}
if ( ! get_users(
	array(
		'role'   => 'cmscoe_reviewer',
		'number' => 1,
		'fields' => 'ID',
	)
) ) {
	remove_role( 'cmscoe_reviewer' );
}
foreach ( array( 'administrator', 'editor' ) as $cmscoe_role_name ) {
	$cmscoe_role = get_role( $cmscoe_role_name );
	if ( $cmscoe_role ) {
		foreach ( array( 'cmscoe_manage_content_model', 'cmscoe_manage_admin_skin', 'cmscoe_edit_design', 'cmscoe_duplicate_pages' ) as $cmscoe_cap ) {
			$cmscoe_role->remove_cap( $cmscoe_cap );
		}
	}
}
