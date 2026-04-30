document.querySelectorAll( '.wccp-copy' ).forEach( function( btn ) {
	btn.addEventListener( 'click', function() {
		navigator.clipboard.writeText( btn.dataset.copy ).then( function() {
			btn.classList.add( 'wccp-copy--done' );
			setTimeout( function() { btn.classList.remove( 'wccp-copy--done' ); }, 1500 );
		} );
	} );
} );
