var FusionPageBuilder = FusionPageBuilder || {};
FusionPageBuilder.options = FusionPageBuilder.options || {};

FusionPageBuilder.options.fusionTextarea = {

	/**
	 * Inits the textarea char counters.
	 *
	 * @param {Object} $element
	 */
	optionTextarea: function( $element ) {
		const self = this;

		jQuery( $element ).find( '.fusion-builder-option.counter textarea' ).each( function() {
			self.setCounter( jQuery( this ) );
		} );
	},
	
	/**
	 * Set the textarea char counter.
	 *
	 * @param {Object} $textarea
	 */
	setCounter: function( $textarea ) {
		const max         = '' !== $textarea.attr( 'maxlength' ) ? $textarea.attr( 'maxlength' ) : '',
			delimiter     = max ? ' / ' : '',
			range         = String( $textarea.data( 'range' ) ),
			steps         = range.split( '|' ),
			step1         = '' !== steps[ 0 ] ? steps[ 0 ] : 0,
			step2         = 'undefined' !== typeof steps[ 1 ] ? steps[ 1 ] : 0,
			currentLength = $textarea.val().length,
			counter       = $textarea.next();
		let color         = step1 ? '#e0284f' : '';

		if ( step2 && step1 < currentLength && step2 > currentLength ) {
			color = '#14c983';
		} else if ( ! step2 && step1 > currentLength ) {
			color = '#14c983';
		}

		counter.html( currentLength + delimiter + max );
		counter.css( 'color', color );
	},
};
