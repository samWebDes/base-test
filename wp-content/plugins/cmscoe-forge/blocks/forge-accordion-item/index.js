( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	// Design options are Administrator-only (cmscoe-platform design lock); content-only users edit text.
	const canEditDesign = () => ! window.cmscoeDesign || window.cmscoeDesign.canEdit;

	const ALLOWED = [ 'core/paragraph', 'core/list', 'core/heading', 'core/image', 'core/table', 'core/file', 'cmscoe/forge-button' ];

	registerBlockType( 'cmscoe/forge-accordion-item', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps( { className: 'cmscoe-accordion__item' } );
			const innerProps = useInnerBlocksProps(
				{ className: 'cmscoe-accordion__panel' },
				{ allowedBlocks: ALLOWED, template: [ [ 'core/paragraph', { placeholder: __( 'Section content', 'cmscoe-forge' ) } ] ] }
			);
			return el(
				Fragment,
				null,
				canEditDesign() && el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Section', 'cmscoe-forge' ) },
						el( ToggleControl, {
							label: __( 'Open when the page loads', 'cmscoe-forge' ),
							checked: attributes.open,
							onChange: ( value ) => setAttributes( { open: value } ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( RichText, {
						tagName: 'p',
						className: 'cmscoe-accordion__trigger',
						value: attributes.heading,
						allowedFormats: [],
						placeholder: __( 'Section heading (for example, a question)', 'cmscoe-forge' ),
						onChange: ( value ) => setAttributes( { heading: value } ),
					} ),
					el( 'div', innerProps )
				)
			);
		},
		save: () => el( InnerBlocks.Content ),
	} );
} )( window.wp );
