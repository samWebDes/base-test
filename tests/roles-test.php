<?php
/**
 * Role / capability / block-allowlist / duplication / environment-lock / shared-package tests.
 * Run: ddev wp eval-file tests/roles-test.php   (exits non-zero on failure)
 * Needs the local users from bin/seed-local.php.
 *
 * @package Cms\Tests
 */

$cmscoe_failures = 0;
$cmscoe_passes   = 0;
$cmscoe_assert   = static function ( bool $cond, string $label ) use ( &$cmscoe_failures, &$cmscoe_passes ) {
	if ( $cond ) {
		++$cmscoe_passes;
		WP_CLI::log( "PASS  {$label}" );
	} else {
		++$cmscoe_failures;
		WP_CLI::log( "FAIL  {$label}" );
	}
};
$cmscoe_user     = static function ( string $login ): WP_User {
	$u = get_user_by( 'login', $login );
	if ( ! $u ) {
		WP_CLI::error( "Missing test user {$login}; run bin/seed-local.php" );
	}
	return $u;
};

$cmscoe_p        = 'cms';
$cmscoe_admin    = $cmscoe_user( 'admin' );
$cmscoe_editor   = $cmscoe_user( 'editor1' );
$cmscoe_reviewer = $cmscoe_user( 'reviewer1' );
$cmscoe_author   = $cmscoe_user( 'author1' );

// 1. Block allowlist.
$cmscoe_ctx = new WP_Block_Editor_Context( array( 'post' => get_post( (int) get_option( 'page_on_front' ) ) ) );
foreach ( array(
	'admin'     => $cmscoe_admin,
	'editor1'   => $cmscoe_editor,
	'reviewer1' => $cmscoe_reviewer,
) as $cmscoe_login => $cmscoe_u ) {
	wp_set_current_user( $cmscoe_u->ID );
	$cmscoe_allowed = apply_filters( 'allowed_block_types_all', true, $cmscoe_ctx );
	if ( 'admin' === $cmscoe_login ) {
		$cmscoe_assert( true === $cmscoe_allowed, 'admin: all block types allowed' );
		continue;
	}
	$cmscoe_assert( is_array( $cmscoe_allowed ), "{$cmscoe_login}: allowlist is restricted" );
	foreach ( array( 'core/group', 'core/columns', 'core/buttons', 'core/cover', 'core/details', 'core/separator', 'core/spacer', 'core/html', 'core/navigation', 'core/query', 'core/shortcode' ) as $cmscoe_b ) {
		$cmscoe_assert( ! in_array( $cmscoe_b, $cmscoe_allowed, true ), "{$cmscoe_login}: {$cmscoe_b} denied" );
	}
	foreach ( array( 'core/paragraph', 'core/heading', 'core/list', 'core/list-item', 'core/image', 'core/embed', 'core/table', 'core/block', 'cmscoe/forge-button', 'cmscoe/forge-card', 'cmscoe/forge-accordion', 'cmscoe/forge-accordion-item', 'cmscoe/section', 'cmscoe/entry-fields', 'cmscoe/directory', 'cmscoe/entry-accordion', 'cmscoe/active-alerts' ) as $cmscoe_b ) {
		$cmscoe_assert( in_array( $cmscoe_b, $cmscoe_allowed, true ), "{$cmscoe_login}: {$cmscoe_b} allowed" );
	}
}

// 1b. Patterns usable by editors contain only allowed blocks.
wp_set_current_user( $cmscoe_editor->ID );
$cmscoe_allowed  = apply_filters( 'allowed_block_types_all', true, $cmscoe_ctx );
$cmscoe_patterns = array_filter( array_column( WP_Block_Patterns_Registry::get_instance()->get_all_registered(), 'name' ), static fn( $n ) => str_starts_with( $n, 'tyler-cms-theme/' ) );
$cmscoe_assert( count( $cmscoe_patterns ) >= 6, 'theme registers its pattern catalog (' . count( $cmscoe_patterns ) . ' patterns)' );
foreach ( $cmscoe_patterns as $cmscoe_slug ) {
	$cmscoe_pat   = WP_Block_Patterns_Registry::get_instance()->get_registered( $cmscoe_slug );
	$cmscoe_names = array();
	$cmscoe_walk  = static function ( array $blocks ) use ( &$cmscoe_walk, &$cmscoe_names ) {
		foreach ( $blocks as $b ) {
			if ( $b['blockName'] ) {
				$cmscoe_names[] = $b['blockName'];
			}
			$cmscoe_walk( $b['innerBlocks'] );
		}
	};
	$cmscoe_walk( parse_blocks( $cmscoe_pat ? $cmscoe_pat['content'] : '' ) );
	$cmscoe_bad = array_diff( array_unique( $cmscoe_names ), $cmscoe_allowed );
	$cmscoe_assert( $cmscoe_pat && ! $cmscoe_bad, "pattern {$cmscoe_slug} usable by Editors" . ( $cmscoe_bad ? ' (disallowed: ' . implode( ',', $cmscoe_bad ) . ')' : '' ) );
}

// 2. Administrator-only platform capabilities.
$cmscoe_admin_only = array( 'cmscoe_manage_content_model', 'cmscoe_manage_admin_skin', 'cmscoe_edit_design', 'edit_roles', 'create_roles', 'install_plugins', 'activate_plugins', 'update_plugins', 'delete_plugins', 'promote_users', 'edit_users', 'manage_options', 'edit_theme_options' );
foreach ( $cmscoe_admin_only as $cmscoe_cap ) {
	$cmscoe_assert( user_can( $cmscoe_admin, $cmscoe_cap ), "admin has {$cmscoe_cap}" );
	foreach ( array(
		'reviewer1' => $cmscoe_reviewer,
		'author1'   => $cmscoe_author,
	) as $cmscoe_login => $cmscoe_u ) {
		$cmscoe_assert( ! user_can( $cmscoe_u, $cmscoe_cap ), "{$cmscoe_login} lacks {$cmscoe_cap}" );
	}
	// Includes edit_theme_options: Editors cannot open the Site Editor, templates, or Global Styles.
	$cmscoe_assert( ! user_can( $cmscoe_editor, $cmscoe_cap ), "editor1 lacks {$cmscoe_cap}" );
}
$cmscoe_assert( 'cmscoe_manage_content_model' === acf_get_setting( 'capability' ), 'SCF builder capability is cmscoe_manage_content_model' );

// 2b. Design lock: content-only users get no layout, color, typography, border, or spacing controls.
foreach ( array(
	'admin'     => $cmscoe_admin,
	'editor1'   => $cmscoe_editor,
	'reviewer1' => $cmscoe_reviewer,
) as $cmscoe_login => $cmscoe_u ) {
	wp_set_current_user( $cmscoe_u->ID );
	$cmscoe_settings = get_block_editor_settings( array(), $cmscoe_ctx );
	$cmscoe_f        = $cmscoe_settings['__experimentalFeatures'];
	$cmscoe_checks   = array(
		'background color'    => false === ( $cmscoe_f['color']['background'] ?? true ),
		'text color'          => false === ( $cmscoe_f['color']['text'] ?? true ),
		'color palette'       => empty( $cmscoe_f['color']['palette']['theme'] ),
		'font sizes'          => empty( $cmscoe_f['typography']['fontSizes']['theme'] ),
		'custom font size'    => false === ( $cmscoe_f['typography']['customFontSize'] ?? true ),
		'font families'       => empty( $cmscoe_f['typography']['fontFamilies']['theme'] ),
		'line height'         => false === ( $cmscoe_f['typography']['lineHeight'] ?? true ),
		'padding'             => false === ( $cmscoe_f['spacing']['padding'] ?? true ),
		'margin'              => false === ( $cmscoe_f['spacing']['margin'] ?? true ),
		'block gap'           => false === ( $cmscoe_f['spacing']['blockGap'] ?? true ),
		'border radius'       => false === ( $cmscoe_f['border']['radius'] ?? true ),
		'border color'        => false === ( $cmscoe_f['border']['color'] ?? true ),
		'layout editing'      => false === ( $cmscoe_f['layout']['allowEditing'] ?? true ),
		'min height'          => false === ( $cmscoe_f['dimensions']['minHeight'] ?? true ),
		'wide/full alignment' => false === ( $cmscoe_settings['alignWide'] ?? true ),
		'code editor'         => false === ( $cmscoe_settings['codeEditingEnabled'] ?? true ),
	);
	foreach ( $cmscoe_checks as $cmscoe_label => $cmscoe_locked ) {
		if ( 'admin' !== $cmscoe_login ) {
			$cmscoe_assert( $cmscoe_locked, "{$cmscoe_login}: {$cmscoe_label} control removed" );
		}
	}
	if ( 'admin' === $cmscoe_login ) {
		$cmscoe_assert( ! $cmscoe_checks['color palette'] && ! $cmscoe_checks['font sizes'] && ! $cmscoe_checks['layout editing'] && ! $cmscoe_checks['code editor'], 'admin keeps palette, font sizes, layout editing, and code editor' );
	}
}

// 3. Default content model: every type registered, own capabilities, templates from the platform.
$cmscoe_types = array( 'service', 'location', 'event', 'alert', 'document', 'faq', 'person' );
foreach ( $cmscoe_types as $cmscoe_t ) {
	$cmscoe_key = "{$cmscoe_p}_{$cmscoe_t}";
	$cmscoe_obj = get_post_type_object( $cmscoe_key );
	$cmscoe_assert( (bool) $cmscoe_obj, "{$cmscoe_key} registered" );
	if ( ! $cmscoe_obj ) {
		continue;
	}
	$cmscoe_assert( "edit_{$cmscoe_key}s" === $cmscoe_obj->cap->edit_posts, "{$cmscoe_key} has its own capability set" );
	$cmscoe_assert( $cmscoe_obj->show_in_rest, "{$cmscoe_key} available in the block editor (show_in_rest)" );
	$cmscoe_assert( user_can( $cmscoe_editor, "edit_others_{$cmscoe_key}s" ) && user_can( $cmscoe_editor, "delete_others_{$cmscoe_key}s" ), "editor can edit and delete unpublished {$cmscoe_key}" );
	$cmscoe_assert( ! user_can( $cmscoe_editor, "publish_{$cmscoe_key}s" ) && ! user_can( $cmscoe_editor, "edit_published_{$cmscoe_key}s" ) && ! user_can( $cmscoe_editor, "delete_published_{$cmscoe_key}s" ), "editor cannot publish, edit live, or delete live {$cmscoe_key}" );
	$cmscoe_assert( user_can( $cmscoe_reviewer, "publish_{$cmscoe_key}s" ) && user_can( $cmscoe_reviewer, "edit_published_{$cmscoe_key}s" ) && user_can( $cmscoe_reviewer, "delete_published_{$cmscoe_key}s" ), "reviewer can publish, edit live, and delete {$cmscoe_key}" );
	$cmscoe_assert( ! user_can( $cmscoe_author, "edit_{$cmscoe_key}s" ), "author cannot edit {$cmscoe_key}" );
	$cmscoe_found = array_filter( get_block_templates( array( 'slug__in' => array( "single-{$cmscoe_key}", "archive-{$cmscoe_key}" ) ) ), static fn( $t ) => '' !== $t->content );
	$cmscoe_assert( count( $cmscoe_found ) >= 2, "{$cmscoe_key} has single and archive templates" );
}
foreach ( array( 'topic', 'audience', 'division', 'doc_type' ) as $cmscoe_t ) {
	$cmscoe_assert( taxonomy_exists( "{$cmscoe_p}_{$cmscoe_t}" ), "{$cmscoe_p}_{$cmscoe_t} taxonomy registered" );
}
foreach ( array( 'publish_posts', 'publish_pages', 'edit_published_posts', 'edit_published_pages', 'delete_published_posts', 'delete_published_pages' ) as $cmscoe_cap ) {
	$cmscoe_assert( ! user_can( $cmscoe_editor, $cmscoe_cap ), "editor lacks {$cmscoe_cap}" );
	$cmscoe_assert( user_can( $cmscoe_reviewer, $cmscoe_cap ), "reviewer has {$cmscoe_cap}" );
}

// 3b. Approval workflow: Editors submit, Reviewers approve and publish. These meta-capability checks are
// what WordPress (and its REST API) enforce, so an Editor cannot bypass review by calling the API directly.
$cmscoe_live  = get_page_by_path( 'about' );
$cmscoe_draft = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_title'  => 'Approval test draft',
		'post_status' => 'draft',
	)
);
foreach ( array(
	array( $cmscoe_editor, 'edit_post', $cmscoe_live->ID, false, 'editor cannot edit the live About page directly' ),
	array( $cmscoe_editor, 'delete_post', $cmscoe_live->ID, false, 'editor cannot delete the live About page' ),
	array( $cmscoe_editor, 'edit_post', $cmscoe_draft, true, 'editor can edit a draft page' ),
	array( $cmscoe_editor, 'delete_post', $cmscoe_draft, true, 'editor can delete a draft page' ),
	array( $cmscoe_reviewer, 'edit_post', $cmscoe_live->ID, true, 'reviewer can edit the live About page' ),
	array( $cmscoe_reviewer, 'delete_post', $cmscoe_live->ID, true, 'reviewer can delete the live About page' ),
	array( $cmscoe_reviewer, 'publish_post', $cmscoe_draft, true, 'reviewer can publish a draft page' ),
) as list( $cmscoe_u, $cmscoe_cap, $cmscoe_id, $cmscoe_expect, $cmscoe_label ) ) {
	$cmscoe_assert( user_can( $cmscoe_u, $cmscoe_cap, $cmscoe_id ) === $cmscoe_expect, $cmscoe_label );
}
wp_delete_post( $cmscoe_draft, true );
$cmscoe_assert( defined( 'PUBLISHPRESS_REVISIONS_VERSION' ), 'PublishPress Revisions is loaded' );
$cmscoe_assert( defined( 'RVY_MONITOR_ROLES' ) && 'administrator,cmscoe_reviewer' === RVY_MONITOR_ROLES, 'submission emails go to Administrators and Reviewers' );
$cmscoe_enabled = isset( $GLOBALS['revisionary'] ) ? (array) $GLOBALS['revisionary']->enabled_post_types : array();
foreach ( array_merge( array( 'post', 'page' ), array_map( static fn( $t ) => "{$cmscoe_p}_{$t}", $cmscoe_types ) ) as $cmscoe_key ) {
	$cmscoe_assert( ! empty( $cmscoe_enabled[ $cmscoe_key ] ), "{$cmscoe_key} is in the revision workflow" );
}

// 4. Page duplication: Editor + Reviewer yes, Author no.
$cmscoe_about = get_page_by_path( 'about' );
foreach ( array(
	'editor1'   => array( $cmscoe_editor, true ),
	'reviewer1' => array( $cmscoe_reviewer, true ),
	'author1'   => array( $cmscoe_author, false ),
) as $cmscoe_login => list( $cmscoe_u, $cmscoe_expect ) ) {
	wp_set_current_user( $cmscoe_u->ID );
	$cmscoe_r  = CmsCoe\Platform\Duplicate\duplicate( $cmscoe_about->ID );
	$cmscoe_ok = $cmscoe_expect ? ( is_int( $cmscoe_r ) && 'draft' === get_post_status( $cmscoe_r ) ) : is_wp_error( $cmscoe_r );
	$cmscoe_assert( $cmscoe_ok, "{$cmscoe_login} page duplication " . ( $cmscoe_expect ? 'allowed (draft created)' : 'denied' ) );
	if ( is_int( $cmscoe_r ) ) {
		wp_delete_post( $cmscoe_r, true );
	}
}

// 5. Environment lock for the builder UI.
$cmscoe_dir = WP_CONTENT_DIR . '/plugins/tyler-cms-content-model/content-model';
$cmscoe_assert( CmsCoe\Platform\Environment\content_model_editable( $cmscoe_dir ), 'builder editable in DDEV' );
foreach ( array(
	'test' => false,
	'live' => false,
	'dev'  => true,
) as $cmscoe_env => $cmscoe_expect ) {
	$_ENV['PANTHEON_ENVIRONMENT'] = $cmscoe_env;
	$cmscoe_actual                = CmsCoe\Platform\Environment\content_model_editable( $cmscoe_dir );
	$cmscoe_assert( $cmscoe_actual === $cmscoe_expect, "builder editable on Pantheon {$cmscoe_env} = " . ( $cmscoe_expect ? 'true' : 'false' ) . ' (dev assumes writable/SFTP)' );
}
unset( $_ENV['PANTHEON_ENVIRONMENT'] );
wp_set_current_user( $cmscoe_editor->ID );
$cmscoe_assert( false === (bool) apply_filters( 'acf/settings/show_admin', true ), 'builder UI hidden for editor1 even in DDEV' );
wp_set_current_user( $cmscoe_admin->ID );
$cmscoe_assert( true === (bool) apply_filters( 'acf/settings/show_admin', true ), 'builder UI shown for admin in DDEV' );

// 6. Content model definitions live in files (Git), not only in the database.
$cmscoe_json = glob( $cmscoe_dir . '/*.json' );
$cmscoe_assert( count( (array) $cmscoe_json ) >= 17, 'content-model/ holds the definition files (' . count( (array) $cmscoe_json ) . ')' );
$cmscoe_db = ( new WP_Query(
	array(
		'post_type'      => array( 'acf-post-type', 'acf-taxonomy', 'acf-field-group' ),
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => -1,
	)
) )->posts;
WP_CLI::log( 'INFO  builder DB definition rows: ' . count( $cmscoe_db ) . ' (files are the source of truth)' );

// 7. Plugins the platform depends on are active.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
foreach ( array( 'secure-custom-fields/secure-custom-fields.php', 'cmscoe-platform/cmscoe-platform.php', 'cmscoe-forge/cmscoe-forge.php', 'tyler-cms-content-model/tyler-cms-content-model.php', 'members/members.php', 'admin-menu-editor/menu-editor.php', 'revisionary/revisionary.php' ) as $cmscoe_plugin ) {
	$cmscoe_assert( is_plugin_active( $cmscoe_plugin ), "{$cmscoe_plugin} active" );
}

// 8. Shared cmscoe/* packages are installed exactly as released (not forked in this site).
$cmscoe_lock = json_decode( (string) file_get_contents( ABSPATH . 'composer.lock' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
$cmscoe_pkgs = array_filter( (array) ( $cmscoe_lock['packages'] ?? array() ), static fn( $p ) => str_starts_with( (string) ( $p['name'] ?? '' ), 'cmscoe/' ) );
$cmscoe_assert( count( $cmscoe_pkgs ) >= 2, 'composer.lock pins the shared cmscoe/* packages (' . count( $cmscoe_pkgs ) . ')' );
foreach ( $cmscoe_pkgs as $cmscoe_pkg ) {
	$cmscoe_dir = (string) ( $cmscoe_pkg['extra']['installer-name'] ?? basename( $cmscoe_pkg['name'] ) );
	$cmscoe_zip = ABSPATH . ( $cmscoe_pkg['dist']['url'] ?? '' );
	$cmscoe_ok  = is_readable( $cmscoe_zip ) && sha1_file( $cmscoe_zip ) === ( $cmscoe_pkg['dist']['shasum'] ?? '' );
	$cmscoe_assert( $cmscoe_ok, "{$cmscoe_pkg['name']} {$cmscoe_pkg['version']}: release zip matches the composer.lock shasum" );
	$cmscoe_data = get_plugin_data( WP_PLUGIN_DIR . "/{$cmscoe_dir}/{$cmscoe_dir}.php", false, false );
	$cmscoe_assert( $cmscoe_data['Version'] === $cmscoe_pkg['version'], "{$cmscoe_dir} installed version {$cmscoe_data['Version']} is the locked {$cmscoe_pkg['version']}" );
	if ( ! $cmscoe_ok ) {
		continue;
	}
	$cmscoe_released = array();
	$cmscoe_archive  = new ZipArchive();
	$cmscoe_archive->open( $cmscoe_zip );
	for ( $cmscoe_i = 0, $cmscoe_n = $cmscoe_archive->count(); $cmscoe_i < $cmscoe_n; $cmscoe_i++ ) {
		$cmscoe_name = $cmscoe_archive->getNameIndex( $cmscoe_i );
		if ( ! str_ends_with( $cmscoe_name, '/' ) ) {
			$cmscoe_released[ $cmscoe_name ] = sha1( (string) $cmscoe_archive->getFromIndex( $cmscoe_i ) );
		}
	}
	$cmscoe_archive->close();
	$cmscoe_installed = array();
	$cmscoe_root      = WP_PLUGIN_DIR . "/{$cmscoe_dir}/";
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $cmscoe_root, FilesystemIterator::SKIP_DOTS ) ) as $cmscoe_file ) {
		$cmscoe_installed[ substr( $cmscoe_file->getPathname(), strlen( $cmscoe_root ) ) ] = sha1_file( $cmscoe_file->getPathname() );
	}
	$cmscoe_diff = array_keys( array_diff_assoc( $cmscoe_installed, $cmscoe_released ) + array_diff_assoc( $cmscoe_released, $cmscoe_installed ) );
	$cmscoe_assert( ! $cmscoe_diff, "{$cmscoe_dir} files match the release" . ( $cmscoe_diff ? ' (differs: ' . implode( ', ', $cmscoe_diff ) . '; change it in the starter kit and release a new version)' : '' ) );
}

WP_CLI::log( "\n{$cmscoe_passes} passed, {$cmscoe_failures} failed" );
if ( $cmscoe_failures ) {
	WP_CLI::halt( 1 );
}
