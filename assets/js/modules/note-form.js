import { Module } from './module.js';
import { RteToolbar } from './rte-toolbar.js';
import { post, el, editorIsEmpty, i18n } from './helpers.js';

// Rooted at .wccp-notes-form-col so it can reach the toolbar that sits
// outside the <form> element but inside the same column.
export class NoteForm extends Module {
	constructor( selector, { onSuccess } = {} ) {
		super( selector );
		this.onSuccess = onSuccess;
	}

	init() {
		this._form      = this.find( '#wccp-add-note-form' );
		this._editor    = this.find( '#wccp-rte-editor' );
		this._submitBtn = this.find( 'button[type="submit"]' );

		new RteToolbar( this.find( '.wccp-rte-toolbar' ) ).init();
		this._form.addEventListener( 'submit', ( e ) => this._onSubmit( e ) );
		return this;
	}

	_onSubmit( e ) {
		e.preventDefault();
		const note = this._editor.innerHTML.trim();
		if ( ! note || editorIsEmpty( this._editor ) ) return;

		this._submitBtn.disabled = true;
		this._clearNotice();

		post( { action: 'wccp_add_note', customer_id: this._form.dataset.customerId, note } )
			.then( ( res ) => {
				if ( res.success ) {
					this._editor.innerHTML = '';
					this.onSuccess?.();
				} else {
					this._showNotice( res.data?.message || i18n.error, 'error' );
				}
			} )
			.catch( () => this._showNotice( i18n.error, 'error' ) )
			.finally( () => { this._submitBtn.disabled = false; } );
	}

	_showNotice( message, type ) {
		this._clearNotice();
		this._form.appendChild(
			el( 'div', { class: `notice notice-${ type } wccp-notice inline` },
				el( 'p', {}, message )
			)
		);
	}

	_clearNotice() {
		this._form.querySelector( '.wccp-notice' )?.remove();
	}
}
