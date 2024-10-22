<?php
/**
 * Plugin Name: Instant Approval Payment Gateway with Instant Payouts
 * Plugin URI: https://www.highriskshop.com/instant-payment-gateway/
 * Description: Instant Approval High Risk Merchant Gateway with instant payouts to your USDC wallet.
 * Version: 1.1.2
 * Requires at least: 5.8
 * Tested up to: 6.6.2
 * WC requires at least: 5.8
 * WC tested up to: 9.3.3
 * Requires PHP: 7.2
 * Author: HighRiskShop.COM
 * Author URI: https://www.highriskshop.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

    // Exit if accessed directly.
    if (!defined('ABSPATH')) {
        exit;
    }

    add_action('before_woocommerce_init', function() {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    });
	
	add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

/**
 * Enqueue block assets for the gateway.
 */
function highriskshopgateway_enqueue_block_assets() {
    // Fetch all enabled WooCommerce payment gateways
    $highriskshopgateway_available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $highriskshopgateway_gateways_data = array();

    foreach ($highriskshopgateway_available_gateways as $gateway_id => $gateway) {
		if (strpos($gateway_id, 'highriskshop-instant-payment-gateway') === 0) {
        $icon_url = !empty($gateway->icon_url) ? esc_url($gateway->icon_url) : '';
        $highriskshopgateway_gateways_data[] = array(
            'id' => sanitize_key($gateway_id),
            'label' => sanitize_text_field($gateway->get_title()),
            'description' => wp_kses_post($gateway->get_description()),
            'icon_url' => sanitize_url($icon_url),
        );
		}
    }

    wp_enqueue_script(
        'highriskshopgateway-block-support',
        plugin_dir_url(__FILE__) . 'assets/js/highriskshopgateway-block-checkout-support.js',
        array('wc-blocks-registry', 'wp-element', 'wp-i18n', 'wp-components', 'wp-blocks', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/highriskshopgateway-block-checkout-support.js'),
        true
    );

    // Localize script with gateway data
    wp_localize_script(
        'highriskshopgateway-block-support',
        'highriskshopgatewayData',
        $highriskshopgateway_gateways_data
    );
}
add_action('enqueue_block_assets', 'highriskshopgateway_enqueue_block_assets');

/**
 * Enqueue styles for the gateway on checkout page.
 */
function highriskshopgateway_enqueue_styles() {
    if (is_checkout()) {
        wp_enqueue_style(
            'highriskshopgateway-styles',
            plugin_dir_url(__FILE__) . 'assets/css/highriskshopgateway-payment-gateway-styles.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/highriskshopgateway-payment-gateway-styles.css')
        );
    }
}
add_action('wp_enqueue_scripts', 'highriskshopgateway_enqueue_styles');

    include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-wert.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-stripe.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-simpleswap.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-rampnetwork.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-mercuryo.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-transak.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-moonpay.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-banxa.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-guardarian.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-particle.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-utorg.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-transfi.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-alchemypay.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-changenow.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-sardine.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-topper.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-unlimit.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-bitnovo.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-robinhood.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-coinbase.php'); // Include the payment gateway class
	include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-upi.php'); // Include the payment gateway class

	// Conditional function that check if Checkout page use Checkout Blocks
function highriskshopgateway_is_checkout_block() {
    return WC_Blocks_Utils::has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' );
}
	
?>