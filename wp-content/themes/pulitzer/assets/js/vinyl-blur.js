document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.type-vinyl .wp-block-post-featured-image' ).forEach( function ( figure ) {
		const img = figure.querySelector( 'img' );
		if ( ! img ) return;

		const glow = document.createElement( 'img' );
		glow.src = img.currentSrc || img.src;
		glow.alt = '';
		glow.setAttribute( 'aria-hidden', 'true' );
		glow.className = 'vinyl-blur-glow';
		figure.appendChild( glow );
	} );
} );
