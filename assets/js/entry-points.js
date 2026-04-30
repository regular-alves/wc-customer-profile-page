( function () {
	var config = window.wccpEntryPoints;
	if ( ! config || ! config.profileUrl ) {
		return;
	}

	var profileBase = config.profileUrl;

	function rewriteLinks( root ) {
		root.querySelectorAll( 'a[href*="user-edit.php"]' ).forEach( function ( anchor ) {
			var match = anchor.href.match( /[?&]user_id=(\d+)/ );
			if ( ! match ) {
				return;
			}
			anchor.href = profileBase + '&user_id=' + match[ 1 ];
		} );
	}

	function init() {
		rewriteLinks( document );

		new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== Node.ELEMENT_NODE ) {
						return;
					}
					rewriteLinks( /** @type {Element} */ ( node ) );
				} );
			} );
		} ).observe( document.body, { childList: true, subtree: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
