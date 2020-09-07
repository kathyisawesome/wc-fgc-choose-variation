<?php
/**
 * The main class of the plugin.
 *
 * Handles all plugin related functionalities of
 * this plugin.
 *
 * @since      1.0.0
 * @package    wc-update-variations-cart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'WC_Update_Variation_Cart' ) ) {
	return; // Exit if class exists.
}

/**
 * Main WC_Update_Variation_Cart Class
 *
 * @class WC_Update_Variation_Cart
 * @package Class
 * @author   Kevin
 * @version 1.0.0
 */
class WC_Update_Variation_Cart {
	/**
	 * The plugin version
	 *
	 * @var string
	 */
	public static $version = '1.0.0';
	/**
	 * The plugin name
	 *
	 * @var string
	 */
	public static $name = 'wc_fgc';

	/**
	 * The required WooCommerce version
	 *
	 * @var string
	 */
	public static $required_woo = '3.1.0';

	/**
	 * WC Update Variation Cart constructor
	 *
	 * @since 1.0.0
	 */
	public static function init() {

		// Enqueue required js and css.
		add_filter( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_cart_script' ) );

		// Create seprate div for showing the loader.
		add_action( 'woocommerce_after_cart', array( __CLASS__, 'add_product_div' ) );

		// Remove owl carousel
		if( function_exists( 'is_cart' ) && is_cart() ) {
			add_filter("woocommerce_single_product_carousel_options", "wuv_product_carousel_options", 10 );
		}

		// Add edit option on the cart page.
		add_action( 'woocommerce_after_cart_item_name', array( __CLASS__, 'variation_update_icon' ), 1, 2 );

		// Handle the ajax request of the update cart.
		add_action( 'wp_ajax_wc_fgc_get_product_html', array( __CLASS__, 'get_variation_html' ) );
		add_action( 'wp_ajax_nopriv_wc_fgc_get_product_html', array( __CLASS__, 'get_variation_html' ) );

		// Update the cart as per the user choice.
		add_action( 'wp_ajax_wc_fgc_update_variation_in_cart', array( __CLASS__, 'update_variation_in_cart' ) );
		add_action( 'wp_ajax_nopriv_wc_fgc_update_variation_in_cart', array( __CLASS__, 'update_variation_in_cart' ) );

		// Show error message on the checkout page.
		add_action( 'woocommerce_before_checkout_process', array( __CLASS__, 'check_is_valid_product' ) );
	}

	/**
	 * Enqueue needed js and css
	 *
	 * @name enqueue_cart_script
	 * @param string $page   Name of the page.
	 * @since 1.0.0
	 */
	public static function enqueue_cart_script( $page ) {

		// check is cart page or not.
		if ( is_cart() ) {
			// Enqueue js.
			wp_enqueue_script( self::$name . '_js', plugin_dir_url( WC_fgc_PLUGIN_NAME ) . 'assests/js/wc-update-variation-cart-front.js', array( 'jquery', 'flexslider' ), self::$version, false );

			$wc_fgc_trans_array = array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ), // ajax url.
				'wc_fgc_nonce'       => wp_create_nonce( 'wc-fgc-verify-nonce' ),
				'flexslider'         => apply_filters(
					'woocommerce_single_product_carousel_options',
					array(
						'rtl'            => is_rtl(),
						'animation'      => 'slide',
						'smoothHeight'   => false,
						'directionNav'   => false,
						'controlNav'     => 'thumbnails',
						'slideshow'      => false,
						'animationSpeed' => 500,
						'animationLoop'  => false, // Breaks photoswipe pagination if true.
					)
				),
				'zoom_enabled'       => get_theme_support( 'wc-product-gallery-zoom' ),
				'photoswipe_enabled' => get_theme_support( 'wc-product-gallery-lightbox' ),
				'flexslider_enabled' => get_theme_support( 'wc-product-gallery-slider' ),

			);

			// Enqueue default js for woocommerce.
			wp_enqueue_script( 'wc-add-to-cart-variation' );

			// Enqueue needed css for it.
			wp_enqueue_style( self::$name . '_css', plugin_dir_url( WC_fgc_PLUGIN_NAME ) . 'assests/css/wc-update-variation-front.css', array(), self::$version, 'all' );

			// localize array here.
			wp_localize_script( self::$name . '_js', 'wc_fgc_params', $wc_fgc_trans_array );
		}

	}

	/**
	 * Remove slider for the updation
	 *
	 * @since 1.0.0
	 * @name product_carousel_options
	 */
	public static function product_carousel_options( $options ) {
		$options['controlNav'] = false;
		return $options;
	}
  
	/**
	 * Create seprate div for loader
	 *
	 * @name wc_fgc_add_product_container
	 * @since 1.0.0
	 */
	public static function add_product_div() {
		echo '<div class="wc-fgc-overlay2">
				<div id="wc-fgc-cart-loader"  class="wc-fgc-product-loader">
					<img alt="Loading.." src="' . esc_url( plugin_dir_url( WC_fgc_PLUGIN_NAME ) ) . 'assests/images/loader.gif">
				</div>
			</div>';
	}

	/**
	 * Add edit icon on the product page.
	 *
	 * @name variation_update_icon
	 * @since 1.0.0
	 * @param array        $cart_item  Cart item array.
	 * @param array string $cart_item_key Cart item key.
	 */
	public static function variation_update_icon( $cart_item, $cart_item_key ) {

		$_product = $cart_item['data'];

	//	if ( $_product->get_parent_id() ) {}

		$product_id = isset( $cart_item['product_id'] ) ? intval( $cart_item['product_id'] ) : 0; // Get the variation id.
		$variation_id = isset( $cart_item['variation_id'] ) ? intval( $cart_item['variation_id'] ) : 0; // Get the variation id.

	//	$_product = $variation_id ?  ? $cart_item['product_id'] : 0; // get the product id.
		
		$get_gift_cart_meta = isset( $cart_item['free_gift'] ) ? $cart_item['free_gift'] : '';

		// check is product is varaible and has free gift item meta in it.
		if ( ( $_product->is_type( 'variable' ) || $cart_item['variation_id'] > 0 ) && ! empty( $cart_item['free_gift'] ) ) {

			$edit_in_cart_text = $cart_item['variation_id'] > 0 ? _x( 'Change options', 'edit in cart link text', 'wc_fgc_update_variation' ) : _x( 'Choose options', 'edit in cart link text', 'wc_fgc_update_variation' );

			// Translators: %1$s text for edit price link.
			$edit_in_cart_link_content = sprintf( __( '<small>%1$s<span class="dashicons dashicons-after dashicons-edit"></span></small>', 'edit in cart text', 'wc_fgc_update_variation' ), $edit_in_cart_text );

			$variation_html =
			'<div class="wc-fgc-cart-update">
				<a href="javascript:void(0)" class="edit_price_in_cart_text edit_in_cart_text wc_fgc_updatenow" data-item_key="'. esc_attr( $cart_item_key ) .' "data-product_id="' . esc_attr( $product_id ) . '" data-variation_id="' . esc_attr( $variation_id ) . '">'
				. $edit_in_cart_link_content .
				'</a>
			</div>';
			echo $variation_html;
		}
	}

	/**
	 * Ajax Handler for update cart.
	 *
	 * @name wc_fgc_get_variation_html
	 * @since 1.0.0
	 */
	public static function wc_fgc_get_variation_html() {
		check_ajax_referer( 'wc-fgc-verify-nonce', 'nonce' );

		global $product,$post;
		// verify the ajax request.

		// get the product id from the ajax request.
		$product_id = isset( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : '';

		// Get the variation id from the ajax.
		$variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : '';
		$cart_item_key_ajax = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		// Get total cart.
		$wc_cart = WC()->cart->get_cart();

		if ( ! empty( $wc_cart ) ) {
			foreach ( $wc_cart as $cart_item_key => $cart_item ) {

				// check that product is exists in the cart.
				if ( $cart_item_key_ajax == $cart_item_key ) {
					$_cart_item_key = $cart_item_key;
					foreach ( $cart_item['variation'] as $key => $value ) {
						$_REQUEST[ $key ] = $value;
					}
				}
			}
		}

		// Get product and post from the id.
		$product = wc_get_product( $product_id );

		// Get post product from the id.
		$post = get_post( $product_id );

		/* setting global things on our own for getting things in woocommerce manner ::end */
		do_action( 'wc_fgc_before_product_html' );
		?>
		<div class="wc_fgc_cart" data-title="<?php echo esc_attr( $product->add_to_cart_text() ); ?>" id="wc_fgc_<?php echo $_cart_item_key; ?>">

			<div class="wc-fgc-stock-error" style="display: none;"></div>

			<input type="hidden" id="wc_fgc_prevproid" value="<? echo $variation_id; ?>">
			<input type="hidden" id="wc_fgc_cart_item_key" value="<?php echo $_cart_item_key; ?>">
		<div itemscope itemtype="<?php echo woocommerce_get_product_schema(); ?>" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php
			/*
			 * woocommerce_before_single_product_summary hook.
			 * @hooked woocommerce_show_product_sale_flash - 10 , @hooked woocommerce_show_product_images - 20
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
			 <div class="summary entry-summary">
				<?php
				// get single product name.
				wc_get_template( 'single-product/title.php' );
				// get single product price.
				wc_get_template( 'single-product/price.php' );

				// update for selected attributes.
				global $product;

				// Enqueue variation scripts.
				wp_enqueue_script( 'wc-add-to-cart-variation' );

				// Get Available variations?
				$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

				// Load the template.
				wc_get_template(
					'single-product/add-to-cart/variable.php',
					array(
						'available_variations' => $get_variations ? $product->get_available_variations() : false,
						'attributes'           => $product->get_variation_attributes(),
					)
				);
				?>
				 </div>
				 <meta itemprop="url" content="<?php the_permalink(); ?>" />
			 </div>
			</div>
			<?php

			do_action( 'wc_fgc_after_product_html' );

			wp_die();
	}

	/**
	 * Handle ajax as per the updation in the cart.
	 *
	 * @name wc_fgc_update_variation_in_cart
	 * @since 1.0.0
	 */
	public static function update_variation_in_cart() {
		// verify the ajax request.
		check_ajax_referer( 'wc-fgc-verify-nonce', 'nonce' );
		global $woocommerce;

		do_action( 'wc_fgc_before_updating_product_in_cart' );

		$_product_id = ! empty( $_POST['product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) : 0;

		/* addition setup for variable product ::start */
		$variation_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( absint( $_POST['variation_id'] ) ) ) : 0;
		$cart_item_key_ajax = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : 0;
		$variation    = isset( $_POST['variation'] ) ? map_deep( wp_unslash( $_POST['variation'] ), 'sanitize_text_field' ) : array();
		/* addition setup for variable product ::end */
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $_product_id, 1 );

		if ( $passed_validation ) {

			if ( isset( $_POST['PrevProId'] ) ) {

				$product_to_remove = sanitize_text_field( wp_unslash( $_POST['PrevProId'] ) );

				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

					$_cart_remove_pro = wc_get_product( $cart_item['product_id'] );
					if ( $_cart_remove_pro->is_type( 'variable' ) && isset( $cart_item['free_gift'] ) ) {

						if ( $cart_item_key_ajax == $cart_item_key ) {
							// remove single product.
							$wc_cart_item = $cart_item;
							WC()->cart->remove_cart_item( $cart_item_key );
						}
					}
				}
			}
			ob_start();

			$cart_item_data['free_gift'] = $wc_cart_item['free_gift'];

			$cart_item_data['fgc_quantity'] = $wc_cart_item['fgc_quantity'];

			$product_status = get_post_status( $_product_id );
			if ( $passed_validation && WC()->cart->add_to_cart( $_product_id, $cart_item_data['fgc_quantity'], $variation_id, $variation, $cart_item_data ) && 'publish' === $product_status ) {

				do_action( 'woocommerce_ajax_added_to_cart', $_product_id );

				$success = true;

			} else {

				$success = false;
			}

			wp_die( esc_html( $success ) );

		} else {

			$success = false;
			wp_die( esc_html( $success ) );
		}
	}

	/**
	 * Check is valid product in the cart.
	 *
	 * @name check_is_valid_product
	 * @since 1.0.0
	 */
	public static function check_is_valid_product() {
		$cart = WC()->cart->get_cart();

		if ( ! empty( $cart ) ) {
			$_is_not_valid_product = false;
			foreach ( $cart as $cart_item_key => $cart_item ) {

				$_cart_remove_pro = wc_get_product( $cart_item['product_id'] );

				if ( $_cart_remove_pro->is_type( 'variable' ) && isset( $cart_item['free_gift'] ) ) {

					if ( 0 == $cart_item['variation_id'] ) {

						$_is_not_valid_product = true;

						$_product_name = $_cart_remove_pro->get_name();
						break;
					}
				}
			}

			if ( $_is_not_valid_product ) {

				$message = __( 'We were unable to process your order, please try again by choosing proper attributes of the ', 'woocommerce' ) . $_product_name . __( '.', 'woocommerce' );

				throw new Exception( $message );
			}
		}
	}
}
