( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, RangeControl } = wp.components;
	const { useSelect } = wp.data;

	registerBlockType( 'cmscoe/entry-accordion', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const types = useSelect( ( select ) => ( select( 'core' ).getPostTypes( { per_page: -1 } ) || [] ).filter( ( t ) => t.viewable && ! [ 'attachment', 'wp_block' ].includes( t.slug ) ), [] );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Entry accordion', 'cmscoe-platform' ) },
						el( SelectControl, {
							label: __( 'Content type', 'cmscoe-platform' ),
							value: attributes.postType,
							options: [ { label: __( 'Select a content type', 'cmscoe-platform' ), value: '' } ].concat( types.map( ( t ) => ( { label: t.name, value: t.slug } ) ) ),
							onChange: ( value ) => setAttributes( { postType: value } ),
						} ),
						el( SelectControl, {
							label: __( 'Heading level', 'cmscoe-platform' ),
							value: String( attributes.headingLevel ),
							options: [ { label: 'H2', value: '2' }, { label: 'H3', value: '3' } ],
							onChange: ( value ) => setAttributes( { headingLevel: parseInt( value, 10 ) } ),
						} ),
						el( RangeControl, { label: __( 'Maximum entries', 'cmscoe-platform' ), min: 1, max: 100, value: attributes.max, onChange: ( value ) => setAttributes( { max: value } ) } )
					)
				),
				el( 'div', blockProps, el( wp.serverSideRender, { block: 'cmscoe/entry-accordion', attributes } ) )
			);
		},
		save: () => null,
	} );
} )( window.wp );
