var FusionPageBuilder = FusionPageBuilder || {};

FusionPageBuilder.options = FusionPageBuilder.options || {};

FusionPageBuilder.options.fusionConnectedSortable = {
	optionConnectedSortable: function( $element ) {
		let $sortable = $element.find( '.fusion-connected-sortable' );

		$sortable.sortable( {
			connectWith: '.fusion-connected-sortable',

			stop: function() {
				updateSortables();
			},			
		} ).disableSelection();

		$sortable.find( 'li' ).on( 'dblclick', function() {
			if ( jQuery( this ).parent().hasClass( 'fusion-connected-sortable-enabled' ) ) {
				$element.find( '.fusion-connected-sortable-disabled' ).prepend( this );
			} else {
				$element.find( '.fusion-connected-sortable-enabled' ).append( this );
			}

			updateSortables();
		} );

		function updateSortables() {
			console.log("updateSortables");
			var $enabled   = $element.find( '.fusion-connected-sortable-enabled' ),
				$container = $element.find( '.fusion-builder-option.connected_sortable' ),
				sortOrder  = '';

			$enabled.children( '.fusion-connected-sortable-option' ).each( function() {
				sortOrder += jQuery( this ).data( 'value' ) + ',';
			} );

			sortOrder = sortOrder.slice( 0, -1 );

			$container.find( '.fusion-connected-sortable' ).each( function() {
				if ( jQuery( this ).find( 'li' ).length ) {
					jQuery( this ).removeClass( 'empty' );
				} else {
					jQuery( this ).addClass( 'empty' );
				}
			} );

			$container.find( '.sort-order' ).val( sortOrder ).trigger( 'change' );
		}
	}
};
