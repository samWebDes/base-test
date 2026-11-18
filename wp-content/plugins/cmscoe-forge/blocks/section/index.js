( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, TextControl } = wp.components;

	registerBlockType( 'cmscoe/section', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const innerProps = useInnerBlocksProps( blockProps, { templateLock: false } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Section', 'cmscoe-forge' ) },
						el( SelectControl, {
							label: __( 'HTML element', 'cmscoe-forge' ),
							value: attributes.tagName,
							options: [
								{ label: __( 'Section (landmark when labelled)', 'cmscoe-forge' ), value: 'section' },
								{ label: __( 'Generic container (div)', 'cmscoe-forge' ), value: 'div' },
							],
							onChange: ( value ) => setAttributes( { tagName: value } ),
						} ),
						el( TextControl, {
							label: __( 'Accessible label (optional)', 'cmscoe-forge' ),
							help: __( 'Only needed when the section has no visible heading.', 'cmscoe-forge' ),
							value: attributes.label,
							onChange: ( value ) => setAttributes( { label: value } ),
						} )
					)
				),
				el( 'div', innerProps )
			);
		},
		save() {
			return el( InnerBlocks.Content );
		},
	} );
} )( window.wp );
