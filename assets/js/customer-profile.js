import { CopyButton } from './modules/copy-button.js';
import { NoteForm }   from './modules/note-form.js';
import { NotesList }  from './modules/notes-list.js';

document.querySelectorAll( '.wccp-copy' ).forEach( ( btn ) => new CopyButton( btn ).init() );

const notesList = new NotesList( '.wccp-notes-list-col', {
	customerId: document.querySelector( '#wccp-add-note-form' ).dataset.customerId,
} ).init();

new NoteForm( '.wccp-notes-form-col', {
	onSuccess: () => { notesList.state.page = 1; notesList.load(); },
} ).init();

document.querySelectorAll( '.wccp-map-placeholder' ).forEach( ( el ) => {
	el.querySelector( '.wccp-map-load' ).addEventListener( 'click', () => {
		const iframe          = document.createElement( 'iframe' );
		iframe.src            = `https://maps.google.com/maps?q=${ encodeURIComponent( el.dataset.address ) }&output=embed`;
		iframe.loading        = 'lazy';
		iframe.referrerPolicy = 'no-referrer-when-downgrade';
		iframe.title          = el.dataset.title;
		el.replaceWith( iframe );
	} );
} );
