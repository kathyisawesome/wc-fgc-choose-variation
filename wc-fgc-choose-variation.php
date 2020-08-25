<?php
/**
 * Plugin Name: WC Free Gift Coupons - Choose Variation
 * Description: Update variable product if added as a Free Gift.
 * Version: 1.0.1
 * Author: Kathy Darling
 * Author URI: https://www.kathyisawesome.com
 * Requires at least: 5.0
 * Tested up to: 5.3.0
 * WC requires at least: 4.0
 * WC tested up to: 4.2.0
 *
 * Text Domain: wc_fgc_update_variation
 * Domain Path: /languages/
 *
 * Copyright: © 2020 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Main WC_FGC_Choose_Variation Class
 *
 * @package Class
 */
class WC_FGC_Choose_Variation {

	/**
	 * WC Update Variation Cart constructor
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		if ( ! defined( 'WC_FGC_PLUGIN_NAME' ) ) {
			return;
		}

		// Add edit link on the cart page.
		add_action( 'woocommerce_cart_item_name', array( __CLASS__, 'add_edit_link_in_cart' ), 10, 3 );

		// Hide quantity input when updating.
		add_filter( 'woocommerce_quantity_input_args', array( __CLASS__, 'hide_quantity_input' ), 10, 2 );

		// Change add to cart link.
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'single_add_to_cart_text' ), 10, 2 );

		// Add hidden input to add to cart form.
		add_action( 'woocommerce_after_single_variation', array( __CLASS__, 'display_hidden_update_input' ) );	

		// Update cart.
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 5, 3 );

		// Show error message on the checkout page.
		add_action( 'woocommerce_before_checkout_process', array( __CLASS__, 'validate_variation_selection' ) );

	}


	/*-----------------------------------------------------------------------------------*/
	/* Display                                                                          */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Add edit link to cart items.
	 *
	 * @param  string $content
	 * @param  array  $cart_item
	 * @param  string $cart_item_key
	 * @return string
	 */
	public static function add_edit_link_in_cart( $content, $cart_item, $cart_item_key ) {

		if ( ! empty( $cart_item['free_gift'] ) && ! empty( $cart_item['fgc_type'] ) && 'variable' === $cart_item['fgc_type'] ) {

			if ( function_exists( 'is_cart' ) && is_cart() && ! self::is_cart_widget() ) {

				$_product = $cart_item['data'];

				$edit_in_cart_link = add_query_arg(
					array(
						'update-gift'  => $cart_item_key
					),
					$_product->get_permalink()
				);

				$edit_in_cart_text = $cart_item['variation_id'] > 0 ? _x( 'Change options', 'edit in cart link text', 'wc_fgc_update_variation' ) : _x( 'Choose options', 'edit in cart link text', 'wc_fgc_update_variation' );

				// Translators: %1$s Original cart price string. %2$s URL for edit price link. %3$s text for edit price link.
				$content = sprintf( __( '%1$s<br/><a class="edit_price_in_cart_text edit_in_cart_text" href="%2$s" style="text-decoration:none;"><small>%3$s<span class="dashicons dashicons-after dashicons-edit"></span></small></a>', 'edit in cart text', 'wc_fgc_update_variation' ), $content, esc_url( $edit_in_cart_link ), $edit_in_cart_text );

			}
		}

		return $content;

	}

	/**
	 * Hide the quantity input when updating.
	 *
	 * @param array $args
	 * @param object $product
	 * @return string
	 */
	public static function hide_quantity_input( $args, $product ) {

		if ( isset( $_GET['update-gift'] ) ) {
			$updating_cart_key = wc_clean( $_GET['update-gift'] );

			if ( isset( WC()->cart->cart_contents[ $updating_cart_key ] ) ) {
				$args['min_value'] = WC()->cart->cart_contents[ $updating_cart_key ]['quantity'];
				$args['max_value'] = WC()->cart->cart_contents[ $updating_cart_key ]['quantity'];
			}
		}

		return $args;

	}



	/**
	 * If Updating a gift change single item's add to cart button text.
	 *
	 * @param string $text
	 * @param object $product
	 * @return string
	 */
	public static function single_add_to_cart_text( $text, $product ) {

		if ( isset( $_GET['update-gift'] ) ) {
			$updating_cart_key = wc_clean( $_GET['update-gift'] );

			if ( isset( WC()->cart->cart_contents[ $updating_cart_key ] ) ) {
				$text = apply_filters( 'wc_fgc_single_update_cart_text', __( 'Update Gift', 'wc_fgc_update_variation' ), $product );
			}
		}

		return $text;

	}


	/**
	 * Add a hidden input to facilitate changing the variation from cart.
	 */
	public static function display_hidden_update_input() {
		if ( isset( $_GET['update-gift'] ) ) {
			$updating_cart_key = wc_clean( $_GET['update-gift'] );
			if ( isset( WC()->cart->cart_contents[ $updating_cart_key ] ) ) {
				echo '<input type="hidden" name="update-gift" value="' . esc_attr( $updating_cart_key ) . '" />';
			}
		}
	}


	/*-----------------------------------------------------------------------------------*/
	/* Cart                                                                              */
	/*-----------------------------------------------------------------------------------*/


	/**
	 * Redirect to the cart when editing a price "in-cart".
	 *
	 * @since   3.0.0
	 * @param  string $url
	 * @return string
	 */
	public static function edit_in_cart_redirect( $url ) {
		return wc_get_cart_url();
	}


	/**
	 * Filter the displayed notice after redirecting to the cart when editing a price "in-cart".
	 *
	 * @param  string $url
	 * @return string
	 */
	public static function edit_in_cart_redirect_message( $message ) {
		return __( 'Cart updated.', 'wc_fgc_update_variation' );
	}

	/**
	 * Add cart session data.
	 *
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @param int   $variation_id ID of the variation being added to the cart.
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {

		// Updating container in cart?
		if ( isset( $_POST['update-gift'] ) ) {

			$product_type = WC_Product_Factory::get_product_type( $product_id );

			// Is this a variable product?
			if ( in_array( $product_type, array( 'variable', 'variable-subscription' ) ) ) {

				$updating_cart_key = wc_clean( $_POST['update-gift'] );

				if ( isset( WC()->cart->cart_contents[ $updating_cart_key ] ) ) {

					$cart_item = WC()->cart->cart_contents[ $updating_cart_key ];

					// Pass free gift coupon code from existing product to new product.
					if( isset( $cart_item['free_gift'] ) ) {
						$cart_item_data['free_gift'] = $cart_item['free_gift'];
					}

					// Pass free gift quantity from existing product to new product.
					if( isset( $cart_item['fgc_quantity'] ) ) {
						$cart_item_data['fgc_quantity'] = $cart_item['fgc_quantity'];
					}

					// Pass free gift "type" from existing product to new product.
					if( isset( $cart_item['fgc_type'] ) ) {
						$cart_item_data['fgc_type'] = $cart_item['fgc_type'];
					}

					// Remove.
					WC()->cart->remove_cart_item( $updating_cart_key );

					// Redirect to cart.
					add_filter( 'woocommerce_add_to_cart_redirect', array( __CLASS__, 'edit_in_cart_redirect' ) );

					// Edit notice.
					add_filter( 'wc_add_to_cart_message_html', array( __CLASS__, 'edit_in_cart_redirect_message' ) );
				}
			}

		}

		return $cart_item_data;
	}

	/**
	 * Check is valid product in the cart.
	 *
	 * @since 1.1.0
	 */
	public static function validate_variation_selection() {
		$cart = WC()->cart->get_cart();

		if ( ! empty( $cart ) ) {
			
			$needs_selection = false;
			
			foreach ( $cart as $cart_item_key => $cart_item ) {

				if ( $cart_item['data']->is_type( 'variable' ) && isset( $cart_item['free_gift'] ) && 0 === $cart_item['variation_id'] ) {

					$needs_selection = true;

					$product_name = $cart_item['data']->get_name();
					break;
					
				}
			}

			if ( $needs_selection ) {

				$message = sprintf( __( 'We were unable to process your order, please try again by choosing attributes for "%s".', 'wc-fgc-choose-variation' ), $product_name );

				throw new Exception( $message );
			}
		}
	}

	/*-----------------------------------------------------------------------------------*/
	/* Helpers                                                                           */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Rendering cart widget?
	 *
	 * @return boolean
	 */
	public static function is_cart_widget() {
		return did_action( 'woocommerce_before_mini_cart' ) > did_action( 'woocommerce_after_mini_cart' );
	}

}

add_action( 'plugins_loaded', array( 'WC_FGC_Choose_Variation', 'init' ), 20 );