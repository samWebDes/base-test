( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, useInnerBlocksProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	// Design options are Administrator-only (cmscoe-platform design lock); content-only users edit text.
	const canEditDesign = () => ! window.cmscoeDesign || window.cmscoeDesign.canEdit;

	const ALLOWED = [ 'core/image', 'core/heading', 'core/paragraph', 'core/list', 'cmscoe/forge-button' ];
	const TEMPLATE = [
		[ 'core/heading', { level: 3, placeholder: __( 'Card heading', 'cmscoe-forge' ) } ],
		[ 'core/paragraph', { placeholder: __( 'One or two sentences.', 'cmscoe-forge' ) } ],
	];

	registerBlockType( 'cmscoe/forge-card', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps( { className: 'cmscoe-forge-card' } );
			const innerProps = useInnerBlocksProps( { className: 'cmscoe-forge-card__body' }, { allowedBlocks: ALLOWED, template: TEMPLATE } );
			return el(
				Fragment,
				null,
				canEditDesign() && el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Card', 'cmscoe-forge' ) },
						el( ToggleControl, {
							label: __( 'Raised (shadow)', 'cmscoe-forge' ),
							checked: attributes.raised,
							onChange: ( value ) => setAttributes( { raised: value } ),
						} )
					)
				),
				el( 'div', blockProps, el( 'forge-card', attributes.raised ? { raised: '' } : {}, el( 'div', innerProps ) ) )
			);
		},
		save() {
			return el( InnerBlocks.Content );
		},
	} );
} )( window.wp );
