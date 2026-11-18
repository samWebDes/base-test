( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { __ } = wp.i18n;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, RangeControl, Placeholder } = wp.components;

	registerBlockType( 'cmscoe/active-alerts', {
		edit( { attributes, setAttributes } ) {
			const blockProps = useBlockProps();
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Alerts', 'cmscoe-platform' ) },
						el( RangeControl, { label: __( 'Maximum alerts shown', 'cmscoe-platform' ), min: 1, max: 10, value: attributes.max, onChange: ( value ) => setAttributes( { max: value } ) } )
					)
				),
				el( 'div', blockProps, el( Placeholder, { icon: 'warning', label: __( 'Active alerts', 'cmscoe-platform' ), instructions: __( 'Current, unexpired alerts appear here on the site. Manage them under Alerts in the admin menu.', 'cmscoe-platform' ) } ) )
			);
		},
		save: () => null,
	} );
} )( window.wp );
