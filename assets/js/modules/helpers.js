const { ajaxUrl, nonce, i18n, currentUserId } = wccpNotes;

export { i18n, currentUserId };

export function post( body ) {
	return fetch( ajaxUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams( { _ajax_nonce: nonce, ...body } ),
	} ).then( ( r ) => r.json() );
}

export function el( tag, props = {}, ...children ) {
	const node = document.createElement( tag );
	for ( const [ key, value ] of Object.entries( props ) ) {
		if ( value == null ) continue;
		if ( key === 'class' ) node.className = value;
		else node.setAttribute( key, value );
	}
	node.append( ...children.flat().filter( ( c ) => c != null ) );
	return node;
}

export function editorIsEmpty( node ) {
	return ! node.textContent.trim() && ! node.querySelector( 'img, li' );
}

export function debounce( fn, delay ) {
	let timer;
	return function ( ...args ) {
		clearTimeout( timer );
		timer = setTimeout( () => fn.apply( this, args ), delay );
	};
}
