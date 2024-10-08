<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_highriskshopgateway_wertio_gateway');

function init_highriskshopgateway_wertio_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

class HighRiskShop_Instant_Payment_Gateway_Wert extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'highriskshop-instant-payment-gateway-wert';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (wert.io)', 'highriskshopgateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using wert.io infrastructure', 'highriskshopgateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->wertio_wallet_address = sanitize_text_field($this->get_option('wertio_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'highriskshopgateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable wert.io payment gateway', 'highriskshopgateway'), // Escaping label
                'default' => 'no',
            ),
            'title' => array(
                'title'       => esc_html__('Title', 'highriskshopgateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Payment method title that users will see during checkout.', 'highriskshopgateway'), // Escaping description
                'default'     => esc_html__('Credit Card', 'highriskshopgateway'), // Escaping default value
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => esc_html__('Description', 'highriskshopgateway'), // Escaping title
                'type'        => 'textarea',
                'description' => esc_html__('Payment method description that users will see during checkout.', 'highriskshopgateway'), // Escaping description
                'default'     => esc_html__('Pay via credit card', 'highriskshopgateway'), // Escaping default value
                'desc_tip'    => true,
            ),
            'wertio_wallet_address' => array(
                'title'       => esc_html__('Wallet Address', 'highriskshopgateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Insert your USDC (Polygon) wallet address to receive instant payouts.', 'highriskshopgateway'), // Escaping description
                'desc_tip'    => true,
            ),
            'icon_url' => array(
                'title'       => esc_html__('Icon URL', 'highriskshopgateway'), // Escaping title
                'type'        => 'url',
                'description' => esc_html__('Enter the URL of the icon image for the payment method.', 'highriskshopgateway'), // Escaping description
                'desc_tip'    => true,
            ),
        );
    }
	 // Add this method to validate the wallet address in wp-admin
    public function process_admin_options() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')) {
    WC_Admin_Settings::add_error(__('Nonce verification failed. Please try again.', 'highriskshopgateway'));
    return false;
}
        $wertio_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_wertio_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_wertio_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($wertio_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'highriskshopgateway'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($wertio_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'highriskshopgateway'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $highriskshopgateway_wertio_currency = get_woocommerce_currency();
		$highriskshopgateway_wertio_total = $order->get_total();
		$highriskshopgateway_wertio_nonce = wp_create_nonce( 'highriskshopgateway_wertio_nonce_' . $order_id );
		$highriskshopgateway_wertio_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $highriskshopgateway_wertio_nonce,), rest_url('highriskshopgateway/v1/highriskshopgateway-wertio/'));
		$highriskshopgateway_wertio_email = urlencode(sanitize_email($order->get_billing_email()));
		
		if ($highriskshopgateway_wertio_currency === 'USD') {
        $highriskshopgateway_wertio_final_total = $highriskshopgateway_wertio_total;
		$highriskshopgateway_wertio_reference_total = (float)$highriskshopgateway_wertio_final_total;
		} else {
		
$highriskshopgateway_wertio_response = wp_remote_get('https://api.highriskshop.com/control/convert.php?value=' . $highriskshopgateway_wertio_total . '&from=' . strtolower($highriskshopgateway_wertio_currency), array('timeout' => 30));

if (is_wp_error($highriskshopgateway_wertio_response)) {
    // Handle error
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'hrswertio'), 'error');
    return null;
} else {

$highriskshopgateway_wertio_body = wp_remote_retrieve_body($highriskshopgateway_wertio_response);
$highriskshopgateway_wertio_conversion_resp = json_decode($highriskshopgateway_wertio_body, true);

if ($highriskshopgateway_wertio_conversion_resp && isset($highriskshopgateway_wertio_conversion_resp['value_coin'])) {
    // Escape output
    $highriskshopgateway_wertio_final_total	= sanitize_text_field($highriskshopgateway_wertio_conversion_resp['value_coin']);
    $highriskshopgateway_wertio_reference_total = (float)$highriskshopgateway_wertio_final_total;	
} else {
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'hrswertio'), 'error');
    return null;
}	
		}
		}
$highriskshopgateway_wertio_gen_wallet = wp_remote_get('https://api.highriskshop.com/control/wallet.php?address=' . $this->wertio_wallet_address .'&callback=' . urlencode($highriskshopgateway_wertio_callback), array('timeout' => 30));

if (is_wp_error($highriskshopgateway_wertio_gen_wallet)) {
    // Handle error
    wc_add_notice(__('Wallet error:', 'woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'hrswertio'), 'error');
    return null;
} else {
	$highriskshopgateway_wertio_wallet_body = wp_remote_retrieve_body($highriskshopgateway_wertio_gen_wallet);
	$highriskshopgateway_wertio_wallet_decbody = json_decode($highriskshopgateway_wertio_wallet_body, true);

 // Check if decoding was successful
    if ($highriskshopgateway_wertio_wallet_decbody && isset($highriskshopgateway_wertio_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $highriskshopgateway_wertio_gen_addressIn = wp_kses_post($highriskshopgateway_wertio_wallet_decbody['address_in']);
        $highriskshopgateway_wertio_gen_polygon_addressIn = sanitize_text_field($highriskshopgateway_wertio_wallet_decbody['polygon_address_in']);
		$highriskshopgateway_wertio_gen_callback = sanitize_url($highriskshopgateway_wertio_wallet_decbody['callback_url']);
		// Save $wertioresponse in order meta data
    $order->add_meta_data('highriskshop_wertio_tracking_address', $highriskshopgateway_wertio_gen_addressIn, true);
    $order->add_meta_data('highriskshop_wertio_polygon_temporary_order_wallet_address', $highriskshopgateway_wertio_gen_polygon_addressIn, true);
    $order->add_meta_data('highriskshop_wertio_callback', $highriskshopgateway_wertio_gen_callback, true);
	$order->add_meta_data('highriskshop_wertio_converted_amount', $highriskshopgateway_wertio_final_total, true);
	$order->add_meta_data('highriskshop_wertio_expected_amount', $highriskshopgateway_wertio_reference_total, true);
	$order->add_meta_data('highriskshop_wertio_nonce', $highriskshopgateway_wertio_nonce, true);
    $order->save();
    } else {
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (wallet address error)', 'wertio'), 'error');

        return null;
    }
}

// Check if the Checkout page is using Checkout Blocks
if (highriskshopgateway_is_checkout_block()) {
    global $woocommerce;
	$woocommerce->cart->empty_cart();
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://pay.highriskshop.com/process-payment.php?address=' . $highriskshopgateway_wertio_gen_addressIn . '&amount=' . (float)$highriskshopgateway_wertio_final_total . '&provider=wert&email=' . $highriskshopgateway_wertio_email . '&currency=' . $highriskshopgateway_wertio_currency,
        );
    }

}

function highriskshop_add_instant_payment_gateway_wertio($gateways) {
    $gateways[] = 'HighRiskShop_Instant_Payment_Gateway_Wert';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'highriskshop_add_instant_payment_gateway_wertio');
}

// Add custom endpoint for changing order status
function highriskshopgateway_wertio_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'highriskshopgateway/v1', '/highriskshopgateway-wertio/', array(
        'methods'  => 'GET',
        'callback' => 'highriskshopgateway_wertio_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'highriskshopgateway_wertio_change_order_status_rest_endpoint' );

// Callback function to change order status
function highriskshopgateway_wertio_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$highriskshopgateway_wertiogetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$highriskshopgateway_wertiopaid_txid_out = sanitize_text_field($request->get_param('txid_out'));
	$highriskshopgateway_wertiopaid_value_coin = sanitize_text_field($request->get_param('value_coin'));
	$highriskshopgateway_wertiofloatpaid_value_coin = (float)$highriskshopgateway_wertiopaid_value_coin;

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'highriskshop-instant-payment-gateway-wert' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'highriskshop-instant-payment-gateway-wert' ), array( 'status' => 404 ) );
    }
	
	// Verify nonce
    if ( empty( $highriskshopgateway_wertiogetnonce ) || $order->get_meta('highriskshop_wertio_nonce', true) !== $highriskshopgateway_wertiogetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'highriskshop-instant-payment-gateway-wert' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'highriskshop-instant-payment-gateway-wert'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'highriskshop-instant-payment-gateway-wert' === $order->get_payment_method() ) {
	$highriskshopgateway_wertioexpected_amount = (float)$order->get_meta('highriskshop_wertio_expected_amount', true);
	$highriskshopgateway_wertiothreshold = 0.60 * $highriskshopgateway_wertioexpected_amount;
		if ( $highriskshopgateway_wertiofloatpaid_value_coin < $highriskshopgateway_wertiothreshold ) {
			// Mark the order as failed and add an order note
            $order->update_status('failed', __( 'Payment received is less than 60% of the order total. Customer may have changed the payment values on the checkout page.', 'highriskshop-instant-payment-gateway-wert' ));
            /* translators: 1: Transaction ID */
            $order->add_order_note(sprintf( __( 'Order marked as failed: Payment received is less than 60%% of the order total. Customer may have changed the payment values on the checkout page. TXID: %1$s', 'highriskshop-instant-payment-gateway-wert' ), $highriskshopgateway_wertiopaid_txid_out));
            return array( 'message' => 'Order status changed to failed due to partial payment.' );
			
		} else {
        // Change order status to processing
		$order->payment_complete();
        $order->update_status( 'processing' );
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'highriskshop-instant-payment-gateway-wert'), $highriskshopgateway_wertiopaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order status changed to processing.' );
		}
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'highriskshop-instant-payment-gateway-wert' ), array( 'status' => 400 ) );
    }
}
?>