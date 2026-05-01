import { Module } from './module.js';
import { NoteItem } from './note-item.js';
import { post, el, debounce, i18n, currentUserId } from './helpers.js';

const PER_PAGE = 5;

export class NotesList extends Module {
	constructor( selector, { customerId } = {} ) {
		super( selector );
		this.customerId = customerId;
		this.state      = { page: 1, search: '', authorId: 0 };
	}

	init() {
		this._list   = this.find( '#wccp-notes-list' );
		this._search = this.find( '#wccp-notes-search' );
		this._author = this.find( '#wccp-notes-author' );
		this._nav    = this.find( '#wccp-notes-pagination' );

		this._search.addEventListener( 'input', debounce( () => {
			this.state.search = this._search.value.trim();
			this.state.page   = 1;
			this.load();
		}, 400 ) );

		this._author.addEventListener( 'change', () => {
			this.state.authorId = Number( this._author.value ) || 0;
			this.state.page     = 1;
			this.load();
		} );

		this._nav.querySelector( '.wccp-page-prev' ).addEventListener( 'click', () => {
			if ( this.state.page > 1 ) { this.state.page--; this.load(); }
		} );

		this._nav.querySelector( '.wccp-page-next' ).addEventListener( 'click', () => {
			this.state.page++;
			this.load();
		} );

		this.load();
		return this;
	}

	load() {
		this._list.replaceChildren( el( 'p', { class: 'wccp-notes-empty' }, i18n.loading ) );

		post( {
			action:      'wccp_get_notes',
			customer_id: this.customerId,
			page:        this.state.page,
			per_page:    PER_PAGE,
			search:      this.state.search,
			author_id:   this.state.authorId,
		} )
			.then( ( res ) => {
				if ( ! res.success ) {
					this._list.replaceChildren( el( 'p', { class: 'wccp-notes-empty' }, i18n.error ) );
					return;
				}

				const { notes, total, page, total_pages, authors } = res.data;

				if ( notes.length === 0 ) {
					this._list.replaceChildren( el( 'p', { class: 'wccp-notes-empty' }, i18n.empty ) );
				} else {
					const frag = document.createDocumentFragment();
					notes.forEach( ( note ) => frag.appendChild( this._renderNote( note ) ) );
					this._list.replaceChildren( frag );
				}

				this._updatePagination( page, total_pages, total );
				this._updateAuthors( authors );
			} )
			.catch( () => {
				this._list.replaceChildren( el( 'p', { class: 'wccp-notes-empty' }, i18n.error ) );
			} );
	}

	_renderNote( data ) {
		const isOwner = String( data.author_id ) === String( currentUserId );

		// innerHTML is intentional: note content is HTML sanitized by wp_kses on the server
		const noteText     = el( 'div', { class: 'wccp-note-text' } );
		noteText.innerHTML = data.note;

		const actions = isOwner
			? el( 'div', { class: 'wccp-note-actions' },
				el( 'button', { type: 'button', class: 'button-link wccp-note-edit' }, i18n.edit ),
				el( 'button', { type: 'button', class: 'button-link wccp-note-delete' }, i18n.delete ),
			  )
			: null;

		const meta = el( 'div', { class: 'wccp-note-meta' },
			el( 'div', { class: 'wccp-note-avatar' },
				el( 'img', { src: data.avatar_url, alt: data.author_name } ),
			),
			el( 'span', { class: 'wccp-note-date' }, data.date_formatted ),
			actions,
		);

		const item = el( 'div', {
			class:            'wccp-note',
			'data-note-id':   data.id,
			'data-author-id': data.author_id,
		} );
		item.append( meta, noteText );
		new NoteItem( item, { onDelete: () => this.load() } ).init();
		return item;
	}

	_updatePagination( page, totalPages, total ) {
		const info = this._nav.querySelector( '.wccp-page-info' );
		const prev = this._nav.querySelector( '.wccp-page-prev' );
		const next = this._nav.querySelector( '.wccp-page-next' );

		this._nav.hidden = total === 0 || totalPages <= 1;
		info.textContent = `${ i18n.page } ${ page } ${ i18n.of } ${ totalPages }`;
		prev.disabled    = page <= 1;
		next.disabled    = page >= totalPages;
	}

	_updateAuthors( authors ) {
		const current = this._author.value;

		const options = [ el( 'option', { value: '' }, i18n.allAuthors ) ];
		authors.forEach( ( a ) => {
			const opt = el( 'option', { value: a.author_id }, a.author_name );
			if ( String( a.author_id ) === String( current ) ) opt.selected = true;
			options.push( opt );
		} );
		this._author.replaceChildren( ...options );
	}
}
