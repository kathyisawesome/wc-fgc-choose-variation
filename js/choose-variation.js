jQuery(document).ready(function($){
	'use strict';

	$( document.body ).on( 'adding_to_cart', function( event, button, product_data ) {

		// If we find the "update-gift" in the data add a class to the button.
		if( 'undefined' !== typeof product_data.find(isUpdateGift) ) {
			button.addClass( 'fgc-update' );
		} else {
			button.removeClass( 'fgc-update' );
		}

	} );

	function isUpdateGift(data) { 
	  return data.name === 'update-gift';
	}

	$( document.body ).on( 'added_to_cart', function( event, fragments, cart_hash, button ) {
		if ( button.hasClass( 'fgc-update' ) ) {
			window.location = wc_add_to_cart_params.cart_url;
			return;
		}
	} );

});