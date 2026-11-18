<?php
/**
 * Dashboard how-to guides filtered by role (editorial-workflow.md "Admin dashboard how-to guides").
 * Users can hide the widget from Screen Options; core remembers that per user.
 * Sites can change guide text with the cmscoe_how_to_guides filter instead of editing this file.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\HowTo;

defined( 'ABSPATH' ) || exit;

/**
 * Guides keyed by audience. Each guide: title, steps, optional admin link.
 *
 * @return array<string,array<int,array{title:string,steps:string[],link?:string}>>
 */
function guides(): array {
	$guides = array(
		'all'     => array(
			array(
				'title' => __( 'Create and edit pages and posts', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Pages or Posts and choose Add New, or select an existing title to edit it.', 'cmscoe-platform' ),
					__( 'Use the + button to add blocks. Forge components are in the "Forge components" category.', 'cmscoe-platform' ),
					__( 'Layout, colors, fonts, and spacing come from the site design and cannot be changed in the editor. Pick a pattern for a different layout.', 'cmscoe-platform' ),
					__( 'Save a draft and use Preview to check it. Editors then choose Submit for Review; a Reviewer approves and publishes.', 'cmscoe-platform' ),
				),
				'link'  => 'edit.php?post_type=page',
			),
			array(
				'title' => __( 'Use patterns', 'cmscoe-platform' ),
				'steps' => array(
					__( 'In the block inserter, open the Patterns tab.', 'cmscoe-platform' ),
					__( 'Pick a pattern such as Hero or Card grid, then replace the sample text and images.', 'cmscoe-platform' ),
					__( 'Patterns already follow the site layout and accessibility rules, so you only need to edit the content.', 'cmscoe-platform' ),
				),
			),
			array(
				'title' => __( 'Upload and describe media', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Media and choose Add New, or add an Image block and upload.', 'cmscoe-platform' ),
					__( 'Write alternative text that says what the image shows or does. Leave it empty only for decorative images.', 'cmscoe-platform' ),
					__( 'Upload PDFs only when a web page will not work, and give them a descriptive title.', 'cmscoe-platform' ),
				),
				'link'  => 'upload.php',
			),
			array(
				'title' => __( 'Find your way around the admin', 'cmscoe-platform' ),
				'steps' => array(
					__( 'The left menu lists content types such as Pages, News, Events, and Services.', 'cmscoe-platform' ),
					__( 'Use Duplicate on the Pages list to start a new page from an existing one.', 'cmscoe-platform' ),
				),
			),
		),
		'terms'   => array(
			array(
				'title' => __( 'Manage categories, tags, and topics', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Each content type has its own taxonomy screens in its menu, for example Services, then Topics.', 'cmscoe-platform' ),
					__( 'Reuse existing terms where you can; directories use them as filters.', 'cmscoe-platform' ),
				),
			),
		),
		'submit'  => array(
			array(
				'title' => __( 'Submit changes for review', 'cmscoe-platform' ),
				'steps' => array(
					__( 'New content: write it, then choose Submit for Review. A Reviewer approves and publishes it.', 'cmscoe-platform' ),
					__( 'Live content: in the list, hover over the title and choose Create Revision. The live page stays as it is while you work.', 'cmscoe-platform' ),
					__( 'Make your changes in the revision, then choose Submit Revision. Reviewers get an email.', 'cmscoe-platform' ),
					__( 'You can delete drafts and revisions. Ask a Reviewer to take live content down.', 'cmscoe-platform' ),
				),
				'link'  => 'admin.php?page=revisionary-q',
			),
		),
		'approve' => array(
			array(
				'title' => __( 'Review, approve, and publish', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Revisions, then New Revisions, to see submitted changes. Pending Review in each content list shows new content waiting for you.', 'cmscoe-platform' ),
					__( 'Open a revision and use Compare or Preview Revision to check it.', 'cmscoe-platform' ),
					__( 'Choose Publish Revision to make it live, or Decline Revision to send it back.', 'cmscoe-platform' ),
					__( 'To schedule, open the publish date, pick a future date and time, then choose Schedule.', 'cmscoe-platform' ),
				),
				'link'  => 'admin.php?page=revisionary-q',
			),
		),
		'admin'   => array(
			array(
				'title' => __( 'Manage menus and navigation', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Appearance, then Editor, then Navigation.', 'cmscoe-platform' ),
					__( 'Add, remove, or reorder links, then save. Keep labels short and specific.', 'cmscoe-platform' ),
				),
				'link'  => 'site-editor.php?p=%2Fnavigation',
			),
			array(
				'title' => __( 'Change layouts, colors, fonts, and spacing', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Only Administrators see design settings. Editors and Reviewers edit text and media only.', 'cmscoe-platform' ),
					__( 'Change site-wide colors and fonts in Appearance, then Editor, then Styles. Keep the brand palette: its colors are already checked for contrast.', 'cmscoe-platform' ),
					__( 'Edit templates and template parts (header, footer) in Appearance, then Editor, then Templates. Test changes in DDEV or Dev before deploying.', 'cmscoe-platform' ),
				),
				'link'  => 'site-editor.php',
			),
			array(
				'title' => __( 'Create content types and custom fields', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Work in DDEV or a Pantheon Dev/Multidev environment in SFTP mode. The builder is read-only on Test and Live.', 'cmscoe-platform' ),
					__( 'Use the Secure Custom Fields menu to add a post type, taxonomy, or field group. Prefix keys with the site prefix.', 'cmscoe-platform' ),
					__( 'Commit the new JSON files in the content-model plugin and deploy Dev → Test → Live.', 'cmscoe-platform' ),
					__( 'The new type gets default single and directory templates automatically, and its fields appear through the Entry fields block.', 'cmscoe-platform' ),
				),
			),
			array(
				'title' => __( 'Manage user roles and permissions', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Users to add people and assign a role: Administrator, Editor, Reviewer, Author, or Contributor.', 'cmscoe-platform' ),
					__( 'Use Members, then Roles, to review or change capabilities. Grant the least access that lets someone do their job.', 'cmscoe-platform' ),
				),
				'link'  => 'users.php',
			),
			array(
				'title' => __( 'Install and manage plugins', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Complete the plugin governance checklist before adding a plugin.', 'cmscoe-platform' ),
					__( 'Add plugins in DDEV through Composer, test on Dev or Multidev, then deploy. Plugin installs are blocked on Test and Live.', 'cmscoe-platform' ),
				),
				'link'  => 'plugins.php',
			),
			array(
				'title' => __( 'Edit the admin menu', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Settings, then Menu Editor, to rename, reorder, or hide admin menu items for each role.', 'cmscoe-platform' ),
					__( 'Hiding a menu item does not remove the permission; change capabilities in Members for that.', 'cmscoe-platform' ),
				),
				'link'  => 'options-general.php?page=menu_editor',
			),
			array(
				'title' => __( 'Configure the Admin skin and login page', 'cmscoe-platform' ),
				'steps' => array(
					__( 'Go to Settings, then Admin skin, to set colors, the logo, and the login background image.', 'cmscoe-platform' ),
					__( 'Colors that do not reach 4.5:1 contrast with white text are rejected.', 'cmscoe-platform' ),
				),
				'link'  => 'options-general.php?page=cmscoe-admin-skin',
			),
		),
	);
	/**
	 * Filter the how-to guides.
	 *
	 * @param array $guides Guides keyed by audience (all, editor, admin).
	 */
	return (array) apply_filters( 'cmscoe_how_to_guides', $guides );
}

/**
 * Guides visible to the current user.
 *
 * @return array<int,array{title:string,steps:string[],link?:string}>
 */
function visible_guides(): array {
	$all  = guides();
	$list = $all['all'] ?? array();
	if ( current_user_can( 'manage_categories' ) ) {
		$list = array_merge( $list, $all['terms'] ?? array() );
	}
	if ( current_user_can( 'edit_others_pages' ) ) {
		$list = array_merge( $list, $all[ current_user_can( 'publish_pages' ) ? 'approve' : 'submit' ] ?? array() );
	}
	if ( current_user_can( 'cmscoe_manage_content_model' ) ) {
		$list = array_merge( $list, $all['admin'] ?? array() );
	}
	return $list;
}

/**
 * Render the widget. Native details/summary gives keyboard and screen-reader expand/collapse for free.
 */
function render(): void {
	echo '<div class="cmscoe-how-to">';
	foreach ( visible_guides() as $guide ) {
		echo '<details class="cmscoe-how-to__guide"><summary><h3>' . esc_html( $guide['title'] ) . '</h3></summary><ol>';
		foreach ( $guide['steps'] as $step ) {
			echo '<li>' . esc_html( $step ) . '</li>';
		}
		echo '</ol>';
		if ( ! empty( $guide['link'] ) ) {
			/* translators: %s: guide title. */
			echo '<p><a href="' . esc_url( admin_url( $guide['link'] ) ) . '">' . esc_html( sprintf( __( 'Go to: %s', 'cmscoe-platform' ), $guide['title'] ) ) . '</a></p>';
		}
		echo '</details>';
	}
	echo '</div>';
}

add_action(
	'wp_dashboard_setup',
	static function () {
		if ( current_user_can( 'edit_posts' ) ) {
			wp_add_dashboard_widget( 'cmscoe_how_to', __( 'How-to guides', 'cmscoe-platform' ), __NAMESPACE__ . '\\render', null, null, 'normal', 'high' );
		}
	}
);

add_action(
	'admin_head-index.php',
	static function () {
		echo '<style>.cmscoe-how-to__guide{border-block-end:1px solid #dcdcde;padding-block:.5rem}.cmscoe-how-to__guide summary{cursor:pointer}.cmscoe-how-to__guide summary h3{display:inline;font-size:14px;margin:0}.cmscoe-how-to__guide summary:focus-visible{outline:2px solid currentColor;outline-offset:2px}</style>';
	}
);
