/*
 * Design lock, editor side (see includes/design-lock.php). For content-only users (everyone without
 * cmscoe_edit_design):
 * - every block gets no layout, color, typography, border, spacing, dimension, shadow, or position
 *   controls, through the blockEditor.useSetting.before filter. The editor rebuilds these settings in
 *   the browser from Global Styles, so the server-side lock alone does not hide the controls;
 * - block style variations and the inline text-color ("Highlight") format are removed.
 * Existing content keeps its classes and colors; only the controls to change them go away.
 */
( function ( wp ) {
	if ( ! window.cmscoeDesign || window.cmscoeDesign.canEdit ) {
		return;
	}

	// The editor calls this filter before resolving a setting (value is undefined), so the lock
	// returns explicit values by path: preset lists become empty, switches become false.
	const GROUPS = [ 'border', 'color', 'dimensions', 'position', 'shadow', 'spacing', 'typography', 'background' ];
	const PRESETS = [ 'color.palette', 'color.gradients', 'color.duotone', 'typography.fontSizes', 'typography.fontFamilies', 'spacing.spacingSizes', 'shadow.presets', 'border.radiusSizes', 'dimensions.dimensionSizes' ];
	// Read-only values the editor needs to lay out and size content correctly; not controls.
	const KEEP = [ 'typography.fluid', 'spacing.units', 'background.backgroundImage', 'dimensions.aspectRatios', 'dimensions.defaultAspectRatios' ];

	wp.hooks.addFilter( 'blockEditor.useSetting.before', 'cmscoe/design-lock', function ( value, path ) {
		if ( 'string' !== typeof path ) {
			return value;
		}
		if ( 'layout' === path ) {
			const layout = wp.data.select( 'core/block-editor' ).getSettings().__experimentalFeatures?.layout || {};
			return Object.assign( {}, layout, { allowEditing: false, allowCustomContentAndWideSize: false } );
		}
		if ( 'layout.allowEditing' === path || 'layout.allowCustomContentAndWideSize' === path ) {
			return false;
		}
		const group = path.split( '.' )[ 0 ];
		if ( ! GROUPS.includes( group ) || KEEP.some( ( keep ) => path === keep || path.startsWith( keep + '.' ) ) ) {
			return value;
		}
		if ( PRESETS.some( ( preset ) => path === preset || path.startsWith( preset + '.' ) ) ) {
			return [];
		}
		return false;
	} );

	wp.domReady( function () {
		wp.blocks.getBlockTypes().forEach( function ( type ) {
			( wp.data.select( 'core/blocks' ).getBlockStyles( type.name ) || [] ).forEach( function ( style ) {
				wp.blocks.unregisterBlockStyle( type.name, style.name );
			} );
		} );
		if ( wp.richText.unregisterFormatType && wp.data.select( 'core/rich-text' ).getFormatType( 'core/text-color' ) ) {
			wp.richText.unregisterFormatType( 'core/text-color' );
		}
	} );
} )( window.wp );
