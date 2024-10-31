<?php
/**
 * Plugin Name: Popular Products Block
 * Description: Display WooCommerce Popular Products
 * Version: 1.0.1
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: popular-products
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

// Constant
define( 'WPP_PLUGIN_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.0.1' );
define( 'WPP_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPP_DIR_PATH', plugin_dir_path( __FILE__ ) );

require_once WPP_DIR_PATH . 'inc/block.php';

class WPPPlugin{
	function __construct(){
		add_action( 'plugins_loaded', [$this, 'pluginsLoaded'] );
	}

	function pluginsLoaded(){
		if ( !did_action( 'woocommerce_loaded' ) ) {
			add_action( 'admin_notices', [$this, 'wooCommerceNotLoaded'] );
			return;
		}
	}

	function wooCommerceNotLoaded(){
		if ( !current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$woocommerce = 'woocommerce/woocommerce.php';

		if ( $this->isPluginInstalled( $woocommerce ) ) {
			$activationUrl = wp_nonce_url( 'plugins.php?action=activate&amp;plugin='. $woocommerce .'&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_'. $woocommerce );

			$message = sprintf( __( '%1$s WooCommerce Popular Products Block.%2$s requires %1$sWooCommerce%2$s plugin to be active. Please activate WooCommerce to continue.', 'popular-products' ), "<strong>", "</strong>" );

			$button_text = __( 'Activate WooCommerce', 'popular-products' );
		} else {
			$activationUrl = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );

			$message = sprintf( __( '%1$s WooCommerce Popular Products Block.%2$s requires %1$sWooCommerce%2$s plugin to be installed and activated. Please install WooCommerce to continue.', 'popular-products' ), '<strong>', '</strong>' );

			$button_text = __( 'Install WooCommerce', 'popular-products' );
		}

		$button = '<p><a href="'. esc_url( $activationUrl ) . '" class="button-primary">'. esc_html( $button_text ) .'</a></p>';

		printf( '<div class="error"><p>%1$s</p>%2$s</div>', $message, $button );
	}

	function isPluginInstalled( $basename ) {
		if ( !function_exists( 'get_plugins' ) ) {
			include_once ABSPATH .'/wp-admin/includes/plugin.php';
		}

		$installedPlugins = get_plugins();

		return isset( $installedPlugins[$basename] );
	}
}
new WPPPlugin();