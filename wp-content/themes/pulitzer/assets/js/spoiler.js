( function () {
	const spoilers = document.querySelectorAll( '.entry-content .spoiler' );

	spoilers.forEach( function ( spoiler ) {

		spoiler.addEventListener( 'click', function () {
			this.classList.toggle( 'spoiler--show' );
		} );

		// Prevent toggling off when selecting revealed text.
		spoiler.addEventListener( 'mousedown', function ( e ) {
			if ( this.classList.contains( 'spoiler--show' ) && e.detail > 1 ) {
				e.preventDefault();
			}
		} );
	} );
} )();
