( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, SelectControl } = wp.components;
	// Design options are Administrator-only (cmscoe-platform design lock); content-only users edit text.
	const canEditDesign = () => ! window.cmscoeDesign || window.cmscoeDesign.canEdit;

	registerBlockType( 'cmscoe/forge-accordion', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps( { className: 'cmscoe-accordion' } );
			const innerProps = useInnerBlocksProps( blockProps, {
				allowedBlocks: [ 'cmscoe/forge-accordion-item' ],
				template: [ [ 'cmscoe/forge-accordion-item' ], [ 'cmscoe/forge-accordion-item' ] ],
			} );
			return el(
				Fragment,
				null,
				canEditDesign() && el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Accordion', 'cmscoe-forge' ) },
						el( ToggleControl, {
							label: __( 'Only one section open at a time', 'cmscoe-forge' ),
							checked: attributes.singleOpen,
							onChange: ( value ) => setAttributes( { singleOpen: value } ),
						} ),
						el( SelectControl, {
							label: __( 'Section heading level', 'cmscoe-forge' ),
							help: __( 'Pick the level that follows the heading above the accordion.', 'cmscoe-forge' ),
							value: String( attributes.headingLevel ),
							options: [ 2, 3, 4 ].map( ( n ) => ( { label: 'H' + n, value: String( n ) } ) ),
							onChange: ( value ) => setAttributes( { headingLevel: parseInt( value, 10 ) } ),
						} )
					)
				),
				el( 'div', innerProps )
			);
		},
		save: () => el( InnerBlocks.Content ),
	} );
} )( window.wp );
