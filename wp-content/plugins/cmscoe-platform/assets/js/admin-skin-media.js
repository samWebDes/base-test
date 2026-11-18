/* Admin skin media picker: enhances the attachment-ID inputs with the WordPress media library. */
( function ( wp ) {
	const { __ } = wp.i18n;
	document.querySelectorAll( '[data-cmscoe-media]' ).forEach( ( cell ) => {
		const input = cell.querySelector( 'input[type="number"]' );
		const choose = cell.querySelector( '.cmscoe-media-field__choose' );
		const remove = cell.querySelector( '.cmscoe-media-field__remove' );
		const preview = cell.querySelector( '.cmscoe-media-field__preview' );
		let frame;
		const label = document.querySelector( 'label[for="' + input.id + '"]' );
		input.type = 'hidden';
		choose.hidden = false;
		// Give the buttons the row's label so "Choose image" is not ambiguous between rows.
		if ( label ) {
			choose.setAttribute( 'aria-label', label.textContent + ': ' + choose.textContent );
			remove.setAttribute( 'aria-label', label.textContent + ': ' + remove.textContent );
		}
		choose.setAttribute( 'aria-describedby', input.getAttribute( 'aria-describedby' ) );
		remove.hidden = ! input.value || '0' === input.value;
		choose.addEventListener( 'click', () => {
			frame = frame || wp.media( { title: __( 'Choose image', 'cmscoe-platform' ), library: { type: 'image' }, multiple: false } );
			frame.off( 'select' ).on( 'select', () => {
				const att = frame.state().get( 'selection' ).first().toJSON();
				input.value = att.id;
				const img = document.createElement( 'img' );
				img.src = ( att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url );
				img.alt = '';
				img.style.maxWidth = '300px';
				preview.replaceChildren( img );
				remove.hidden = false;
				choose.focus();
			} );
			frame.open();
		} );
		remove.addEventListener( 'click', () => {
			input.value = '0';
			preview.replaceChildren();
			remove.hidden = true;
			choose.focus();
		} );
	} );
} )( window.wp );
