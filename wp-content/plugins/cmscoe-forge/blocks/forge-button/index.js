( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
	const { PanelBody, TextControl, SelectControl, ToggleControl, Notice } = wp.components;
	// Design options are Administrator-only (cmscoe-platform design lock); content-only users edit text.
	const canEditDesign = () => ! window.cmscoeDesign || window.cmscoeDesign.canEdit;

	registerBlockType( 'cmscoe/forge-button', {
		edit( { attributes, setAttributes } ) {
			const { text, url, variant, opensInNewTab } = attributes;
			const blockProps = useBlockProps( { className: 'cmscoe-forge-button' } );
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Link', 'cmscoe-forge' ) },
						el( TextControl, {
							label: __( 'Destination URL', 'cmscoe-forge' ),
							help: __( 'Required. Use a full URL or a site path such as /services/.', 'cmscoe-forge' ),
							type: 'url',
							value: url,
							onChange: ( value ) => setAttributes( { url: value } ),
						} ),
						canEditDesign() && el( SelectControl, {
							label: __( 'Style', 'cmscoe-forge' ),
							value: variant,
							options: [
								{ label: __( 'Primary (raised)', 'cmscoe-forge' ), value: 'raised' },
								{ label: __( 'Secondary (outlined)', 'cmscoe-forge' ), value: 'outlined' },
								{ label: __( 'Subtle (tonal)', 'cmscoe-forge' ), value: 'tonal' },
							],
							onChange: ( value ) => setAttributes( { variant: value } ),
						} ),
						el( ToggleControl, {
							label: __( 'Open in a new tab', 'cmscoe-forge' ),
							help: __( 'Avoid unless the user would lose work. The link text will announce the new tab.', 'cmscoe-forge' ),
							checked: opensInNewTab,
							onChange: ( value ) => setAttributes( { opensInNewTab: value } ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					! url && el( Notice, { status: 'warning', isDismissible: false }, __( 'Add a destination URL in the block settings. Buttons without a link are not shown on the site.', 'cmscoe-forge' ) ),
					el(
						'forge-button',
						{ variant },
						el( RichText, {
							tagName: 'span',
							value: text,
							allowedFormats: [],
							placeholder: __( 'Button text (describe the destination)', 'cmscoe-forge' ),
							onChange: ( value ) => setAttributes( { text: value } ),
						} )
					)
				)
			);
		},
		save() {
			return null;
		},
	} );
} )( window.wp );
