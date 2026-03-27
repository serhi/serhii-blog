( function () {
	const spoilers = document.querySelectorAll( '.entry-content .spoiler' );

	spoilers.forEach( function ( spoiler ) {

		// Make spoiler keyboard-accessible.
		spoiler.setAttribute( 'role', 'button' );
		spoiler.setAttribute( 'tabindex', '0' );
		spoiler.setAttribute( 'aria-expanded', 'false' );

		spoiler.addEventListener( 'click', function () {
			const expanded = this.getAttribute( 'aria-expanded' ) === 'true';
			this.setAttribute( 'aria-expanded', String( ! expanded ) );
			this.classList.toggle( 'spoiler--show' );
		} );

		// Support Enter and Space keys for keyboard users.
		spoiler.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				this.click();
			}
		} );

		// Prevent toggling off when selecting revealed text.
		spoiler.addEventListener( 'mousedown', function ( e ) {
			if ( this.classList.contains( 'spoiler--show' ) && e.detail > 1 ) {
				e.preventDefault();
			}
		} );
	} );
} )();
