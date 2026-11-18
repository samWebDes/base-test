( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl, CheckboxControl, RangeControl, ToggleControl } = wp.components;
	const { useSelect } = wp.data;

	registerBlockType( 'cmscoe/directory', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			const types = useSelect( ( select ) => ( select( 'core' ).getPostTypes( { per_page: -1 } ) || [] ).filter( ( t ) => t.viewable && ! [ 'attachment', 'page', 'wp_block' ].includes( t.slug ) ), [] );
			const taxes = useSelect( ( select ) => ( attributes.postType ? select( 'core' ).getTaxonomies( { type: attributes.postType, per_page: -1 } ) || [] : [] ), [ attributes.postType ] );
			const toggleTax = ( slug, checked ) => setAttributes( { taxonomies: checked ? [ ...attributes.taxonomies, slug ] : attributes.taxonomies.filter( ( t ) => t !== slug ) } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Directory', 'cmscoe-platform' ) },
						el( SelectControl, {
							label: __( 'Content type', 'cmscoe-platform' ),
							value: attributes.postType,
							options: [ { label: __( 'Select a content type', 'cmscoe-platform' ), value: '' } ].concat( types.map( ( t ) => ( { label: t.name, value: t.slug } ) ) ),
							onChange: ( value ) => setAttributes( { postType: value, taxonomies: [] } ),
						} ),
						taxes.map( ( t ) => el( CheckboxControl, { key: t.slug, label: /* translators: %s: taxonomy name. */ wp.i18n.sprintf( __( 'Filter by %s', 'cmscoe-platform' ), t.name ), checked: attributes.taxonomies.includes( t.slug ), onChange: ( checked ) => toggleTax( t.slug, checked ) } ) ),
						el( ToggleControl, { label: __( 'Keyword search', 'cmscoe-platform' ), checked: attributes.showSearch, onChange: ( value ) => setAttributes( { showSearch: value } ) } ),
						el( SelectControl, {
							label: __( 'Order', 'cmscoe-platform' ),
							value: attributes.orderBy,
							options: [ { label: __( 'Alphabetical', 'cmscoe-platform' ), value: 'title' }, { label: __( 'Newest first', 'cmscoe-platform' ), value: 'date' } ],
							onChange: ( value ) => setAttributes( { orderBy: value } ),
						} ),
						el( RangeControl, { label: __( 'Items per page', 'cmscoe-platform' ), min: 1, max: 48, value: attributes.perPage, onChange: ( value ) => setAttributes( { perPage: value } ) } )
					)
				),
				el( 'div', blockProps, el( wp.serverSideRender, { block: 'cmscoe/directory', attributes } ) )
			);
		},
		save: () => null,
	} );
} )( window.wp );
