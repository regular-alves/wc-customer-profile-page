import { Module } from './module.js';

export class CopyButton extends Module {
	init() {
		this.el.addEventListener( 'click', () => {
			navigator.clipboard.writeText( this.el.dataset.copy ).then( () => {
				this.el.classList.add( 'wccp-copy--done' );
				setTimeout( () => this.el.classList.remove( 'wccp-copy--done' ), 1500 );
			} );
		} );
		return this;
	}
}
