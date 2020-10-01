<?php
/**
 * Plugin Name: WC Free Gift Coupons -  Update Variation Cart 3rd party version (origo version)
 * Description: Update variable product if added as a Free Gift.- to replace the original
 * Version: 2.0.0
 * Author: Kathy Darling, Precious Omonze
 * Requires at least: 4.4
 * Tested up to: 5.3.0
 * WC requires at least: 3.1.0
 * WC tested up to: 4.0.0
 *
 * Text Domain: wc_free_gift_coupons
 * Domain Path: /languages/
 *
 * @package WooCommerce Update Varaition Cart
 *
 * Copyright: © 2012 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Boot up the plugin
 *
 * @since   1.0.0
 */
function _wc_fcg_update_variation_cart() {
	// Defined this way cause it'll later be integrated into core plugin, so these are temporary.
	if ( ! defined( '_WC_FGC_PLUGIN_NAME' ) ) {
		define( '_WC_FGC_PLUGIN_NAME', plugin_basename( __FILE__ ) );
	}
	require_once 'includes/class-wc-fgc-update-variation-cart.php';
	WC_FGC_Update_Variation_Cart::init();
}
add_action( 'plugins_loaded', '_wc_fcg_update_variation_cart' );

