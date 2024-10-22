<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_highriskshopgateway_transak_gateway');

function init_highriskshopgateway_transak_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

class HighRiskShop_Instant_Payment_Gateway_Transak extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'highriskshop-instant-payment-gateway-transak';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (transak.com)', 'instant-approval-payment-gateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using transak.com infrastructure', 'instant-approval-payment-gateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->transakcom_wallet_address = sanitize_text_field($this->get_option('transakcom_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'instant-approval-payment-gateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable transak.com payment gateway', 'instant-approval-payment-gateway'), // Escaping label
                'default' => 'no',
            ),
            'title' => array(
                'title'       => esc_html__('Title', 'instant-approval-payment-gateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Payment method title that users will see during checkout.', 'instant-approval-payment-gateway'), // Escaping description
                'default'     => esc_html__('Credit Card', 'instant-approval-payment-gateway'), // Escaping default value
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => esc_html__('Description', 'instant-approval-payment-gateway'), // Escaping title
                'type'        => 'textarea',
                'description' => esc_html__('Payment method description that users will see during checkout.', 'instant-approval-payment-gateway'), // Escaping description
                'default'     => esc_html__('Pay via credit card', 'instant-approval-payment-gateway'), // Escaping default value
                'desc_tip'    => true,
            ),
            'transakcom_wallet_address' => array(
                'title'       => esc_html__('Wallet Address', 'instant-approval-payment-gateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Insert your USDC (Polygon) wallet address to receive instant payouts.', 'instant-approval-payment-gateway'), // Escaping description
                'desc_tip'    => true,
            ),
            'icon_url' => array(
                'title'       => esc_html__('Icon URL', 'instant-approval-payment-gateway'), // Escaping title
                'type'        => 'url',
                'description' => esc_html__('Enter the URL of the icon image for the payment method.', 'instant-approval-payment-gateway'), // Escaping description
                'desc_tip'    => true,
            ),
        );
    }
	 // Add this method to validate the wallet address in wp-admin
    public function process_admin_options() {
		if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')) {
    WC_Admin_Settings::add_error(__('Nonce verification failed. Please try again.', 'instant-approval-payment-gateway'));
    return false;
}
        $transakcom_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_transakcom_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_transakcom_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($transakcom_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'instant-approval-payment-gateway'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($transakcom_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'instant-approval-payment-gateway'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $highriskshopgateway_transakcom_currency = get_woocommerce_currency();
		$highriskshopgateway_transakcom_total = $order->get_total();
		$highriskshopgateway_transakcom_nonce = wp_create_nonce( 'highriskshopgateway_transakcom_nonce_' . $order_id );
		$highriskshopgateway_transakcom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $highriskshopgateway_transakcom_nonce,), rest_url('highriskshopgateway/v1/highriskshopgateway-transakcom/'));
		$highriskshopgateway_transakcom_email = urlencode(sanitize_email($order->get_billing_email()));
		$highriskshopgateway_transakcom_final_total = $highriskshopgateway_transakcom_total;
	
$highriskshopgateway_transakcom_gen_wallet = wp_remote_get('https://api.highriskshop.com/control/wallet.php?address=' . $this->transakcom_wallet_address .'&callback=' . urlencode($highriskshopgateway_transakcom_callback), array('timeout' => 30));

if (is_wp_error($highriskshopgateway_transakcom_gen_wallet)) {
    // Handle error
    wc_add_notice(__('Wallet error:', 'instant-approval-payment-gateway') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'instant-approval-payment-gateway'), 'error');
    return null;
} else {
	$highriskshopgateway_transakcom_wallet_body = wp_remote_retrieve_body($highriskshopgateway_transakcom_gen_wallet);
	$highriskshopgateway_transakcom_wallet_decbody = json_decode($highriskshopgateway_transakcom_wallet_body, true);

 // Check if decoding was successful
    if ($highriskshopgateway_transakcom_wallet_decbody && isset($highriskshopgateway_transakcom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $highriskshopgateway_transakcom_gen_addressIn = wp_kses_post($highriskshopgateway_transakcom_wallet_decbody['address_in']);
        $highriskshopgateway_transakcom_gen_polygon_addressIn = sanitize_text_field($highriskshopgateway_transakcom_wallet_decbody['polygon_address_in']);
		$highriskshopgateway_transakcom_gen_callback = sanitize_url($highriskshopgateway_transakcom_wallet_decbody['callback_url']);
		// Save $transakcomresponse in order meta data
    $order->add_meta_data('highriskshop_transakcom_tracking_address', $highriskshopgateway_transakcom_gen_addressIn, true);
    $order->add_meta_data('highriskshop_transakcom_polygon_temporary_order_wallet_address', $highriskshopgateway_transakcom_gen_polygon_addressIn, true);
    $order->add_meta_data('highriskshop_transakcom_callback', $highriskshopgateway_transakcom_gen_callback, true);
	$order->add_meta_data('highriskshop_transakcom_converted_amount', $highriskshopgateway_transakcom_final_total, true);
	$order->add_meta_data('highriskshop_transakcom_nonce', $highriskshopgateway_transakcom_nonce, true);
    $order->save();
    } else {
        wc_add_notice(__('Payment error:', 'instant-approval-payment-gateway') . __('Payment could not be processed, please try again (wallet address error)', 'instant-approval-payment-gateway'), 'error');

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
            'redirect' => 'https://pay.highriskshop.com/process-payment.php?address=' . $highriskshopgateway_transakcom_gen_addressIn . '&amount=' . (float)$highriskshopgateway_transakcom_final_total . '&provider=transak&email=' . $highriskshopgateway_transakcom_email . '&currency=' . $highriskshopgateway_transakcom_currency,
        );
    }

}

function highriskshop_add_instant_payment_gateway_transak($gateways) {
    $gateways[] = 'HighRiskShop_Instant_Payment_Gateway_Transak';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'highriskshop_add_instant_payment_gateway_transak');
}

// Add custom endpoint for changing order status
function highriskshopgateway_transakcom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'highriskshopgateway/v1', '/highriskshopgateway-transakcom/', array(
        'methods'  => 'GET',
        'callback' => 'highriskshopgateway_transakcom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'highriskshopgateway_transakcom_change_order_status_rest_endpoint' );

// Callback function to change order status
function highriskshopgateway_transakcom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$highriskshopgateway_transakcomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$highriskshopgateway_transakcompaid_txid_out = sanitize_text_field($request->get_param('txid_out'));

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'instant-approval-payment-gateway' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'instant-approval-payment-gateway' ), array( 'status' => 404 ) );
    }
	
	// Verify nonce
    if ( empty( $highriskshopgateway_transakcomgetnonce ) || $order->get_meta('highriskshop_transakcom_nonce', true) !== $highriskshopgateway_transakcomgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'instant-approval-payment-gateway' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'highriskshop-instant-payment-gateway-transak'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'highriskshop-instant-payment-gateway-transak' === $order->get_payment_method() ) {
        // Change order status to processing
		$order->payment_complete();
        $order->update_status( 'processing' );
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'instant-approval-payment-gateway'), $highriskshopgateway_transakcompaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order status changed to processing.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'instant-approval-payment-gateway' ), array( 'status' => 400 ) );
    }
}
?>