<?php
/**
 * One-time bootstrap: turns wp-content/plugins/tyler-cms-content-model/content-model-spec.php into
 * Secure Custom Fields definitions through SCF's own import API (the same path as an Administrator
 * using the builder), so SCF writes the JSON files into the plugin's content-model/ folder.
 * After this runs, the JSON files are the source of truth; commit them.
 *
 * Usage: ddev wp eval-file bin/bootstrap-content-model.php
 * Re-running is safe: definitions that already exist are skipped.
 *
 * @package Cms\ContentModel
 */

if ( ! function_exists( 'acf_import_post_type' ) ) {
	WP_CLI::error( 'Secure Custom Fields is not active.' );
}
if ( ! CmsCoe\Platform\Environment\content_model_editable( WP_CONTENT_DIR . '/plugins/tyler-cms-content-model/content-model' ) ) {
	WP_CLI::error( 'The content model is read-only in this environment. Run this in DDEV.' );
}

$cmscoe_spec = require WP_CONTENT_DIR . '/plugins/tyler-cms-content-model/content-model-spec.php';
$cmscoe_p    = 'cms';

$cmscoe_labels = static function ( string $singular, string $plural ): array {
	return array(
		'name'          => $plural,
		'singular_name' => $singular,
		'menu_name'     => $plural,
		'all_items'     => "All {$plural}",
		'add_new_item'  => "Add New {$singular}",
		'edit_item'     => "Edit {$singular}",
		'view_item'     => "View {$singular}",
		'search_items'  => "Search {$plural}",
		'not_found'     => "No {$plural} found",
	);
};

$cmscoe_exists = static function ( string $post_type, string $key ): bool {
	return (bool) get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'name'           => $key,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	) || ( 'acf-field-group' === $post_type && function_exists( 'acf_get_local_field_group' ) && acf_get_local_field_group( $key ) );
};

// Taxonomies attached to each post type, from the taxonomy side of the spec.
$cmscoe_type_taxes = array();
foreach ( $cmscoe_spec['taxonomies'] as $cmscoe_tax ) {
	foreach ( $cmscoe_tax['types'] as $cmscoe_type ) {
		$cmscoe_type_taxes[ $cmscoe_type ][] = $cmscoe_tax['key'];
	}
}

foreach ( $cmscoe_spec['taxonomies'] as $cmscoe_tax ) {
	$cmscoe_key = 'taxonomy_' . $cmscoe_tax['key'];
	if ( taxonomy_exists( $cmscoe_tax['key'] ) ) {
		WP_CLI::log( "taxonomy {$cmscoe_tax['key']}: exists, skipped" );
		continue;
	}
	$cmscoe_result = acf_import_taxonomy(
		array(
			'key'          => $cmscoe_key,
			'title'        => $cmscoe_tax['plural'],
			'taxonomy'     => $cmscoe_tax['key'],
			'object_type'  => $cmscoe_tax['types'],
			'labels'       => array(
				'name'          => $cmscoe_tax['plural'],
				'singular_name' => $cmscoe_tax['singular'],
				'menu_name'     => $cmscoe_tax['plural'],
			),
			'public'       => true,
			'hierarchical' => ! empty( $cmscoe_tax['hierarchical'] ),
			'show_in_rest' => true,
			'rewrite'      => array(
				'permalink_rewrite' => 'custom_permalink',
				'slug'              => $cmscoe_tax['slug'],
				'with_front'        => false,
			),
		)
	);
	WP_CLI::log( "taxonomy {$cmscoe_tax['key']}: " . ( empty( $cmscoe_result['ID'] ) ? 'FAILED' : 'created' ) );
}

foreach ( $cmscoe_spec['post_types'] as $cmscoe_type ) {
	if ( post_type_exists( $cmscoe_type['key'] ) ) {
		WP_CLI::log( "post type {$cmscoe_type['key']}: exists, skipped" );
		continue;
	}
	$cmscoe_result = acf_import_post_type(
		array(
			'key'                      => 'post_type_' . $cmscoe_type['key'],
			'title'                    => $cmscoe_type['plural'],
			'post_type'                => $cmscoe_type['key'],
			'labels'                   => $cmscoe_labels( $cmscoe_type['singular'], $cmscoe_type['plural'] ),
			'description'              => $cmscoe_type['description'],
			'public'                   => true,
			'show_in_rest'             => true,
			'menu_icon'                => array(
				'type'  => 'dashicons',
				'value' => $cmscoe_type['icon'],
			),
			'supports'                 => $cmscoe_type['supports'],
			'taxonomies'               => $cmscoe_type_taxes[ $cmscoe_type['key'] ] ?? array(),
			'has_archive'              => true,
			'has_archive_slug'         => $cmscoe_type['slug'],
			'rewrite'                  => array(
				'permalink_rewrite' => 'custom_permalink',
				'slug'              => $cmscoe_type['slug'],
				'feeds'             => true,
				'pages'             => true,
				'with_front'        => false,
			),
			'rename_capabilities'      => true,
			'singular_capability_name' => $cmscoe_type['key'],
			'plural_capability_name'   => $cmscoe_type['key'] . 's',
		)
	);
	WP_CLI::log( "post type {$cmscoe_type['key']}: " . ( empty( $cmscoe_result['ID'] ) ? 'FAILED' : 'created' ) );
}

foreach ( $cmscoe_spec['field_groups'] as $cmscoe_group ) {
	$cmscoe_key = "group_{$cmscoe_p}_{$cmscoe_group['key']}";
	if ( $cmscoe_exists( 'acf-field-group', $cmscoe_key ) ) {
		WP_CLI::log( "field group {$cmscoe_key}: exists, skipped" );
		continue;
	}
	$cmscoe_fields = array();
	foreach ( $cmscoe_group['fields'] as $cmscoe_f ) {
		list( $cmscoe_name, $cmscoe_label, $cmscoe_ftype ) = $cmscoe_f;
		$cmscoe_fields[]                                   = array_merge(
			array(
				'key'          => "field_{$cmscoe_p}_{$cmscoe_name}",
				'name'         => $cmscoe_name,
				'label'        => $cmscoe_label,
				'type'         => $cmscoe_ftype,
				'instructions' => '',
				'required'     => 0,
			),
			$cmscoe_f[3] ?? array()
		);
	}
	$cmscoe_result = acf_import_field_group(
		array(
			'key'                   => $cmscoe_key,
			'title'                 => $cmscoe_group['title'],
			'show_in_rest'          => 1,
			'position'              => 'normal',
			'instruction_placement' => 'field',
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => $cmscoe_group['post_type'],
					),
				),
			),
			'fields'                => $cmscoe_fields,
		)
	);
	WP_CLI::log( "field group {$cmscoe_key}: " . ( empty( $cmscoe_result['ID'] ) ? 'FAILED' : 'created' ) );
}

$cmscoe_files = glob( WP_CONTENT_DIR . '/plugins/tyler-cms-content-model/content-model/*.json' );
WP_CLI::success( count( (array) $cmscoe_files ) . ' definition files in content-model/. Commit them.' );
