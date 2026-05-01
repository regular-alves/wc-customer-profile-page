export class Module {
	constructor( selector ) {
		this.el = typeof selector === 'string'
			? document.querySelector( selector )
			: selector;
	}

	find( sel )    { return this.el.querySelector( sel ); }
	findAll( sel ) { return this.el.querySelectorAll( sel ); }
	init()         { return this; }
}
