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
