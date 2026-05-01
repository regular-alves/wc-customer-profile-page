import { Module } from './module.js';
import { RteToolbar } from './rte-toolbar.js';
import { post, el, editorIsEmpty, i18n } from './helpers.js';

export class NoteItem extends Module {
	constructor( element, { onDelete } = {} ) {
		super( element );
		this.onDelete = onDelete;
	}

	init() {
		this.find( '.wccp-note-edit' )?.addEventListener( 'click', () => this._startEdit() );
		this.find( '.wccp-note-delete' )?.addEventListener( 'click', () => this._confirmDelete() );
		return this;
	}

	_startEdit() {
		const textEl       = this.find( '.wccp-note-text' );
		const originalHtml = textEl.innerHTML;

		const dashicon = ( name ) => el( 'span', { class: `dashicons ${ name }` } );
		const sep      = ()       => el( 'span', { class: 'wccp-rte-sep' } );
		const rteBtn   = ( cmd, title, ...children ) =>
			el( 'button', { type: 'button', class: 'wccp-rte-btn', 'data-cmd': cmd, title: title || null }, ...children );

		const saveBtn   = el( 'button', { type: 'button', class: 'button button-primary button-small wccp-note-save' }, i18n.save );
		const cancelBtn = el( 'button', { type: 'button', class: 'button-link wccp-note-cancel' }, i18n.cancel );

		const toolbar = el( 'div', { class: 'wccp-rte-toolbar wccp-rte-toolbar--inline' },
			rteBtn( 'bold',               i18n.bold,      dashicon( 'dashicons-editor-bold' ) ),
			rteBtn( 'italic',             i18n.italic,    dashicon( 'dashicons-editor-italic' ) ),
			el( 'button', { type: 'button', class: 'wccp-rte-btn wccp-rte-btn--text', 'data-cmd': 'underline', title: i18n.underline }, el( 'u', {}, 'U' ) ),
			sep(),
			rteBtn( 'insertUnorderedList', null, dashicon( 'dashicons-editor-ul' ) ),
			rteBtn( 'insertOrderedList',   null, dashicon( 'dashicons-editor-ol' ) ),
			sep(),
			rteBtn( 'createLink',          null, dashicon( 'dashicons-admin-links' ) ),
			sep(),
			saveBtn,
			cancelBtn,
		);

		textEl.before( toolbar );
		new RteToolbar( toolbar ).init();

		textEl.contentEditable = 'true';
		textEl.classList.add( 'wccp-rte-editor' );
		textEl.focus();

		saveBtn.addEventListener( 'click', () => this._saveEdit( originalHtml ) );
		cancelBtn.addEventListener( 'click', () => this._cancelEdit( originalHtml ) );
	}

	_saveEdit( originalHtml ) {
		const textEl  = this.find( '.wccp-note-text' );
		const note    = textEl.innerHTML.trim();
		const saveBtn = this.find( '.wccp-note-save' );

		if ( ! note || editorIsEmpty( textEl ) ) return;

		saveBtn.disabled = true;

		post( { action: 'wccp_update_note', note_id: this.el.dataset.noteId, note } )
			.then( ( res ) => {
				if ( res.success ) {
					this._finishEdit();
					textEl.innerHTML = res.data.note;
				} else {
					this._cancelEdit( originalHtml );
				}
			} )
			.catch( () => this._cancelEdit( originalHtml ) );
	}

	_cancelEdit( originalHtml ) {
		this._finishEdit();
		this.find( '.wccp-note-text' ).innerHTML = originalHtml;
	}

	_finishEdit() {
		const textEl = this.find( '.wccp-note-text' );
		textEl.contentEditable = 'false';
		textEl.classList.remove( 'wccp-rte-editor' );
		this.find( '.wccp-rte-toolbar--inline' )?.remove();
	}

	_confirmDelete() {
		if ( ! window.confirm( i18n.confirmDelete ) ) return;

		post( { action: 'wccp_delete_note', note_id: this.el.dataset.noteId } ).then( ( res ) => {
			if ( res.success ) this.onDelete?.();
		} );
	}
}
