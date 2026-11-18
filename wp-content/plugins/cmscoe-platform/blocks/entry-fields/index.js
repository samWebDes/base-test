( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl, Placeholder } = wp.components;

	registerBlockType( 'cmscoe/entry-fields', {
		edit( { attributes, setAttributes, context } ) {
			const blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Entry fields', 'cmscoe-platform' ) },
						el( ToggleControl, {
							label: __( 'Show field group headings', 'cmscoe-platform' ),
							checked: attributes.showGroupHeadings,
							onChange: ( value ) => setAttributes( { showGroupHeadings: value } ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					context.postId
						? el( wp.serverSideRender, { block: 'cmscoe/entry-fields', attributes, urlQueryArgs: { post_id: context.postId } } )
						: el( Placeholder, { label: __( 'Entry fields', 'cmscoe-platform' ), instructions: __( 'Shows the current entry’s custom fields. Fields appear here on the site.', 'cmscoe-platform' ) } )
				)
			);
		},
		save: () => null,
	} );
} )( window.wp );
