<?php
/**
 * Theme bootstrap. Presentation only — content model, roles, and blocks live in plugins.
 *
 * @package CmsTheme
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'post-thumbnails' );
		add_editor_style( 'assets/css/forge-bridge.css' );
		load_theme_textdomain( 'tyler-cms-theme', get_template_directory() . '/languages' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$deps = wp_style_is( 'cmscoe-forge-theme', 'registered' ) ? array( 'cmscoe-forge-theme' ) : array();
		wp_enqueue_style( 'tyler-cms-forge-bridge', get_theme_file_uri( 'assets/css/forge-bridge.css' ), $deps, wp_get_theme()->get( 'Version' ) );
	}
);

add_action(
	'init',
	static function () {
		register_block_pattern_category( 'agency', array( 'label' => __( 'Agency', 'tyler-cms-theme' ) ) );
	}
);
