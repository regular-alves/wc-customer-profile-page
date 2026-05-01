import { Module } from './module.js';
import { i18n } from './helpers.js';

export class RteToolbar extends Module {
	init() {
		this.findAll( '.wccp-rte-btn' ).forEach( ( btn ) => {
			btn.addEventListener( 'mousedown', ( e ) => {
				e.preventDefault();
				const cmd = btn.dataset.cmd;
				if ( cmd === 'createLink' ) {
					const url = window.prompt( i18n.insertLink );
					if ( url ) document.execCommand( 'createLink', false, url );
				} else {
					document.execCommand( cmd, false, null );
				}
			} );
		} );
		return this;
	}
}
