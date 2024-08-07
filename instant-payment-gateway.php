<?php
/**
 * Plugin Name: Instant Approval Payment Gateway with Instant Payouts
 * Plugin URI: https://www.highriskshop.com/instant-payment-gateway/
 * Description: Instant Approval High Risk Merchant Gateway with instant payouts to your USDC wallet.
 * Version: 1.0.5
 * Author: HighRiskShop.COM
 * Author URI: https://www.highriskshop.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
        include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-wert.php'); // Include the payment gateway class
		include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-stripe.php'); // Include the payment gateway class
		include_once(plugin_dir_path(__FILE__) . 'includes/class-highriskshop-instant-payment-gateway-paybis.php'); // Include the payment gateway class
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
?>