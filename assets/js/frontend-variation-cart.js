jQuery(document).ready(function($){

	/**
	 * Check if a node is blocked for processing.
	 *
	 * @param {JQuery Object} $node
	 * @return {bool} True if the DOM Element is UI Blocked, false if not.
	 */
	var is_blocked = function( $node ) {
		return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
	};

	/**
	 * Block a node visually for processing.
	 *
	 * @param {JQuery Object} $node
	 */
	var block = function( $node ) {
		if ( ! is_blocked( $node ) ) {
			$node.addClass( 'processing' ).block( {
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			} );
		}
	};

	/**
	 * Unblock a node after processing is complete.
	 *
	 * @param {JQuery Object} $node
	 */
	var unblock = function( $node ) {
		$node.removeClass( 'processing' ).unblock();
	};

	/**
	 * Trigger wc_fgc_updatenow click.
	 * 
	 * Only when variation hasn't been selected
	 */
	var observer = new MutationObserver( function( mutations ) {
		// Check if window is already opened.
		let $editRow = $( '.wc_fgc_cart' ).closest( 'tr.wc-fgc-new-row' );
		let $editBtnParent = $( '.wc-fgc-show-edit' );

		// If variation to edit is only 1, and the edit row is not yet opened.
		if ( $editBtnParent.length == 1  && $editRow.length == 0 ) {
			// Get particular id so we do not trigger multiple.
			let btnParentIdAttr = $editBtnParent.attr( 'id' );
			$( `#${btnParentIdAttr} .wc_fgc_updatenow` ).trigger( 'click' );
			// observer.disconnect();
		}
	 });
	//observer.observe( document, {attributes: false, childList: true, characterData: false, subtree:true} );


	var ajax_url = wc_fgc_var_cart_params.ajax_url;

	$(document).on('click', '.wc_fgc_updatenow', function() {
		// hide button
		$( this ).fadeOut();

		let cartItemIdAttr = $( this ).closest( '.wc-fgc-cart-update' ).attr( 'id' );
		let cartItemId     = cartItemIdAttr.split( '_' )[1];

		let $editRow = $( 'tr#wc-fgc-new-row_' + cartItemId );

		// Check if window is already opened.
		if ( $editRow.length > 0 ) {
			// toggle :) better UX.
			$editRow.fadeIn( 'slow' );
			return;
		}

		block( $( '.woocommerce-cart-form' ) );
		$("#wc-fgc-variation-container").hide();
		var proID = $(this).data('product_id');
		var variationID = $(this).data('variation_id');
		var $thisTR = $(this).closest('td').parent();
		var extdQty = $($thisTR).find('.input-text').val();
		var $cartItem = $(this).parent().parent();
		if(Math.floor(extdQty) != extdQty || !$.isNumeric(extdQty))
			extdQty = 1;
		var current_item_product = $(this).closest('tr');
		var cart_item_key = $(this).data('item_key');
		$.ajax({
			url: ajax_url,
			cache: false,
			type: "POST",
			headers : { "cache-control": "no-cache" },
			data: {
				'action': 'wc_fgc_get_product_html',
				'nonce' : wc_fgc_var_cart_params.wc_fgc_nonce,
				'product_id': proID,
				'variation_id' : variationID,
				'cart_item_key' : cart_item_key,
			},
			success:function( response ) {

				// var html = '<input type="hidden" id="wc_fgc_prevproid" value="'+variationID+'"><p class="close_icon"><span class="wc-fgc-close-btn">&times;</span></p>';
				var length = $('#wc_fgc_'+cart_item_key).length;
				// Custom Code
 				// console.log( $('#wc_fgc_'+cart_item_key) );
				if ( 0 == length ) {
 					current_item_product.after('<tr class="wc-fgc-new-row" id="wc-fgc-new-row_'+cart_item_key+'"><td colspan="6">'+response+'</td></tr>');
				}

				// Run variation saga.
				$( '.woocommerce-product-gallery' ).each( function() {
					$( this ).wc_product_gallery( wc_fgc_var_cart_params );	
				});	
				$form = $('#wc_fgc_'+cart_item_key).find( '.variations_form' );

				if ($form) {
					$form.wc_variation_form();
				}

				// Temporarily find/update data-title attr for responsive table. Need to find a better way.
				$( '#wc-fgc-new-row_' + cart_item_key ).data( 'title', $('#wc_fgc_'+cart_item_key).data( 'title' ) );

				// Changing the add-to-cart button to update and hiding quantity field.
				var $cartDiv = $('#wc_fgc_'+cart_item_key).find('div .woocommerce-variation-add-to-cart');
				 // $($cartDiv).find('.input-text').hide();
				 $($cartDiv).find('.input-text').val(extdQty);
				 $($cartDiv).find('.input-text').attr('disabled',true);
				 $($cartDiv).find('.input-text').hide();
				 $form.find( '.variations select' ).last().trigger('change');
					//$form.trigger( 'reset_data' );
					$($cartDiv).find('.single_add_to_cart_button').html('Update');
					//$($cartDiv).find('.single_add_to_cart_button').attr('disabled','disabled');
					$("#wc-fgc-variation-container").show();
				//Alter scroll nature
				$.scroll_to_notices=function( scrollElement ) {
					var offset = 300;
					if ( scrollElement.length ) {
						$( 'html, body' ).animate( {
							 scrollTop: ( scrollElement.offset().top-offset )
						 }, 5000 );
					}
				};

					// scroll to the section, cool UX 8-)
					//$.scroll_to_notices( $( '#wc_fgc_' + cart_item_key ) );
					//$( 'body,html' ).animate( {
					//	scrollTop: ( $( '#wc_fgc_' + cart_item_key ).offset().top - 100 )
					//}, 100 );
	
				},
				complete:function( response, statusText ) {
					// If 200 wasn't returned.
					if( 'success' !== statusText ){
						alert( wc_fgc_var_cart_params.server_error );

						// Show button again :)
						$( this ).fadeIn();
					}
					unblock( $( '.woocommerce-cart-form' ) );
				}
			});
		
	});

	/*
	* This code is used from WooCommerce. We are using this js to implement 
	* single page flexslider
	*
	*/

	 $(document).on('click','.reset_variations',function(){

	 	// $('form.variations_form').find('div .woocommerce-variation-add-to-cart .input-text').hide();
	 	$(".wc-fgc-stock-error").html('');
	 	$(".wc-fgc-stock-error").hide();
	 });

	 $(document).on("click",".single_add_to_cart_button",function( e ){

		 e.preventDefault();
		 if ( $( this ).is('.disabled') ) {

			if ( $( this ).is('.wc-variation-is-unavailable') ) {
				window.alert( "weird"+wc_add_to_cart_variation_params.i18n_unavailable_text );
		   } else if ( $( this ).is('.wc-variation-selection-needed') ) {
				window.alert( "noway"+wc_fgc_var_cart_params.error_make_a_selection_text ); 
		   }
		   return;

		}

		block( $( '.wc_fgc_cart' ) );

		$id = $(this).closest('.wc_fgc_cart').attr('id');
	 	var product_id = $('#'+$id).find('input[name="product_id"]').val();

	 	var quantity = $('#'+$id).find('input[name="quantity"]').val();

	 	var PrevProId = $("#"+$id+" #wc_fgc_prevproid").val();

	 	var cart_item_key = $("#"+$id+" #wc_fgc_cart_item_key").val();

	 	var variation_id = $('#'+$id).find('input[name="variation_id"]').val();
	 	if(Math.floor(quantity))
	 	var variation = {};

	 	variations_html = $('#'+$id).find( 'select[name^=attribute]' );

	 	variations_html.each( function() {

	 		var attrName = $(this).attr('name');
	 		var attrValue = $(this).val();
	 		variation[attrName] = attrValue;
	 	});

	 	$.ajax({
	 		url: ajax_url,
	 		cache: false,
	 		type: "POST",
	 		headers : { "cache-control": "no-cache" },
	 		data: {
	 			'action': 'wc_fgc_update_variation_in_cart',
	 			'product_id': product_id,
	 			'quantity':quantity,
	 			'PrevProId':PrevProId,
	 			'variation_id':variation_id,
	 			'variation':variation,
	 			'cart_item_key':cart_item_key,
	 			'nonce' : wc_fgc_var_cart_params.wc_fgc_nonce,
	 		},
	 		success:function( response, statusText, xhr ) {
				// console.log({response, statusText, xhr});
	 			if( "success" === statusText ){
					// Update WooCommerce Cart
					let $wcCart = $( '.woocommerce-cart-form [name="update_cart"]' );
					$wcCart.removeAttr( 'disabled' ).trigger( 'click' );
				}else{
	 				if( response ) {
	 					$(".wc-fgc-stock-error").html(response);
	 					$(".wc-fgc-stock-error").show();
	 				}
	 				
	 				$('form.variations_form').find('div .woocommerce-variation-add-to-cart .input-text').show();
	 			}
			 },
			 complete:function() {
				 unblock( $( '.wc_fgc_cart' ) );
			 }
	 	});

	 });

	 $( document ).on( 'click', '.wc-fgc-close-btn', function() {
		let $cartContainer = $( this ).closest( '.wc-fgc-new-row' );
		let cartItemIdAttr = $cartContainer.attr( 'id' );
		let cartItemId     = cartItemIdAttr.split( '_' )[1];

		let cartItemBtnId = 'wc-fgc-item_' + cartItemId;

		$cartContainer.fadeOut( 'slow' );
	 	// $("#wc-fgc-variation-container").html( ' ' );
	 	// $("#wc-fgc-variation-container").hide();
	 	$( `#${cartItemBtnId} .wc_fgc_updatenow` ).fadeIn( 'slow' );
	 });

});
