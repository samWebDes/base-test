<?php
/**
 * Design lock (editorial-workflow.md "Design controls"): only users with cmscoe_edit_design
 * (Administrators) may change layout, colors, typography, borders, spacing, dimensions, shadows,
 * or block style variations. Everyone else edits content only. Styling that patterns and templates
 * already apply is kept; the controls to change it are removed.
 *
 * Two layers: this file locks the settings the server sends, and assets/js/design-lock.js locks
 * them again in the browser (blockEditor.useSetting.before), because the editor rebuilds design
 * settings from Global Styles on the client. The browser test tests/editor-lock.mjs checks both.
 *
 * Templates, template parts, Global Styles, and navigation are protected separately: they need
 * edit_theme_options, which roles.php removes from Editors.
 *
 * @package CmsCoe\Platform
 */

namespace CmsCoe\Platform\DesignLock;

defined( 'ABSPATH' ) || exit;

const CAP = 'cmscoe_edit_design';

/**
 * Whether the current user may change design settings.
 *
 * @return bool
 */
function can_edit_design(): bool {
	return current_user_can( CAP );
}

/**
 * Feature settings turned off for content-only users. Keys follow theme.json "settings".
 *
 * @return array
 */
function locked_features(): array {
	return array(
		'background' => array(
			'backgroundSize' => false,
			'gradient'       => false,
		),
		'border'     => array(
			'color'  => false,
			'radius' => false,
			'style'  => false,
			'width'  => false,
		),
		'color'      => array(
			'background'       => false,
			'text'             => false,
			'link'             => false,
			'heading'          => false,
			'button'           => false,
			'caption'          => false,
			'custom'           => false,
			'customDuotone'    => false,
			'customGradient'   => false,
			'defaultDuotone'   => false,
			'defaultGradients' => false,
			'defaultPalette'   => false,
		),
		'dimensions' => array(
			'aspectRatio' => false,
			'height'      => false,
			'minHeight'   => false,
			'minWidth'    => false,
			'width'       => false,
		),
		'layout'     => array(
			'allowEditing'                  => false,
			'allowCustomContentAndWideSize' => false,
		),
		'position'   => array(
			'fixed'  => false,
			'sticky' => false,
		),
		'shadow'     => array( 'defaultPresets' => false ),
		'spacing'    => array(
			'blockGap'          => false,
			'margin'            => false,
			'padding'           => false,
			'customSpacingSize' => false,
		),
		'typography' => array(
			'customFontSize'   => false,
			'defaultFontSizes' => false,
			'dropCap'          => false,
			'fontStyle'        => false,
			'fontWeight'       => false,
			'letterSpacing'    => false,
			'lineHeight'       => false,
			'textAlign'        => false,
			'textColumns'      => false,
			'textDecoration'   => false,
			'textIndent'       => false,
			'textTransform'    => false,
			'writingMode'      => false,
		),
	);
}

/**
 * Preset lists hidden from content-only users, so no picker has anything to offer.
 * Blocks that already use a preset keep rendering it.
 *
 * @return array<int,array{0:string,1:string}>
 */
function locked_presets(): array {
	return array(
		array( 'color', 'palette' ),
		array( 'color', 'gradients' ),
		array( 'color', 'duotone' ),
		array( 'typography', 'fontSizes' ),
		array( 'typography', 'fontFamilies' ),
		array( 'spacing', 'spacingSizes' ),
		array( 'shadow', 'presets' ),
		array( 'border', 'radiusSizes' ),
	);
}

/**
 * Apply the lock to one feature-settings array (root or per-block).
 *
 * @param array $features Resolved theme.json settings.
 * @return array
 */
function lock_features( array $features ): array {
	$features = array_replace_recursive( $features, locked_features() );
	foreach ( locked_presets() as list( $group, $key ) ) {
		if ( isset( $features[ $group ][ $key ] ) ) {
			$features[ $group ][ $key ] = array();
		}
	}
	return $features;
}

/**
 * Remove design controls from the block editor for content-only users.
 *
 * @param array $settings Block editor settings.
 * @return array
 */
function editor_settings( array $settings ): array {
	if ( can_edit_design() ) {
		return $settings;
	}
	if ( isset( $settings['__experimentalFeatures'] ) && is_array( $settings['__experimentalFeatures'] ) ) {
		$features = lock_features( $settings['__experimentalFeatures'] );
		// theme.json can re-enable features per block; lock those too.
		if ( isset( $features['blocks'] ) && is_array( $features['blocks'] ) ) {
			foreach ( $features['blocks'] as $name => $block_features ) {
				$features['blocks'][ $name ] = lock_features( (array) $block_features );
			}
		}
		$settings['__experimentalFeatures'] = $features;
	}
	// Legacy flat settings some blocks still read.
	$settings['colors']                 = array();
	$settings['gradients']              = array();
	$settings['fontSizes']              = array();
	$settings['disableCustomColors']    = true;
	$settings['disableCustomGradients'] = true;
	$settings['disableCustomFontSizes'] = true;
	$settings['enableCustomLineHeight'] = false;
	$settings['enableCustomSpacing']    = false;
	$settings['enableCustomUnits']      = false;
	// Wide/full alignment is a layout choice.
	$settings['alignWide'] = false;
	// The code editor would let anyone type style attributes into block markup.
	$settings['codeEditingEnabled'] = false;
	return $settings;
}
add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\\editor_settings', 99 );

/**
 * Editor script: removes block style variations and the text-color format for content-only users,
 * and tells CMS COE blocks whether to show their own design options.
 */
function editor_assets(): void {
	$handle = 'cmscoe-design-lock';
	wp_register_script( $handle, plugins_url( 'assets/js/design-lock.js', __DIR__ ), array( 'wp-blocks', 'wp-dom-ready', 'wp-rich-text', 'wp-data', 'wp-hooks' ), \CmsCoe\Platform\VERSION, true );
	wp_add_inline_script( $handle, 'window.cmscoeDesign = ' . wp_json_encode( array( 'canEdit' => can_edit_design() ) ) . ';', 'before' );
	wp_enqueue_script( $handle );
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\editor_assets', 1 );
