<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_highriskshopgateway_topper_gateway');

function init_highriskshopgateway_topper_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class HighRiskShop_Instant_Payment_Gateway_Topper extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'highriskshop-instant-payment-gateway-topper';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (topperpay.com)', 'highriskshopgateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using topperpay.com infrastructure', 'highriskshopgateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->topperpaycom_wallet_address = sanitize_text_field($this->get_option('topperpaycom_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'highriskshopgateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable topperpay.com payment gateway', 'highriskshopgateway'), // Escaping label
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
            'topperpaycom_wallet_address' => array(
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
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $highriskshopgateway_topperpaycom_currency = get_woocommerce_currency();
		$highriskshopgateway_topperpaycom_total = $order->get_total();
		$highriskshopgateway_topperpaycom_nonce = wp_create_nonce( 'highriskshopgateway_topperpaycom_nonce_' . $order_id );
		$highriskshopgateway_topperpaycom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $highriskshopgateway_topperpaycom_nonce,), rest_url('highriskshopgateway/v1/highriskshopgateway-topperpaycom/'));
		$highriskshopgateway_topperpaycom_email = urlencode(sanitize_email($order->get_billing_email()));
		$highriskshopgateway_topperpaycom_final_total = $highriskshopgateway_topperpaycom_total;
	
$highriskshopgateway_topperpaycom_gen_wallet = wp_remote_get('https://api.highriskshop.com/control/wallet.php?address=' . $this->topperpaycom_wallet_address .'&callback=' . urlencode($highriskshopgateway_topperpaycom_callback), array('timeout' => 30));

if (is_wp_error($highriskshopgateway_topperpaycom_gen_wallet)) {
    // Handle error
    wc_add_notice(__('Wallet error:', 'woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'hrstopperpaycom'), 'error');
    return null;
} else {
	$highriskshopgateway_topperpaycom_wallet_body = wp_remote_retrieve_body($highriskshopgateway_topperpaycom_gen_wallet);
	$highriskshopgateway_topperpaycom_wallet_decbody = json_decode($highriskshopgateway_topperpaycom_wallet_body, true);

 // Check if decoding was successful
    if ($highriskshopgateway_topperpaycom_wallet_decbody && isset($highriskshopgateway_topperpaycom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $highriskshopgateway_topperpaycom_gen_addressIn = wp_kses_post($highriskshopgateway_topperpaycom_wallet_decbody['address_in']);
        $highriskshopgateway_topperpaycom_gen_polygon_addressIn = sanitize_text_field($highriskshopgateway_topperpaycom_wallet_decbody['polygon_address_in']);
		$highriskshopgateway_topperpaycom_gen_callback = sanitize_url($highriskshopgateway_topperpaycom_wallet_decbody['callback_url']);
		// Save $topperpaycomresponse in order meta data
    $order->add_meta_data('highriskshop_topperpaycom_tracking_address', $highriskshopgateway_topperpaycom_gen_addressIn, true);
    $order->add_meta_data('highriskshop_topperpaycom_polygon_temporary_order_wallet_address', $highriskshopgateway_topperpaycom_gen_polygon_addressIn, true);
    $order->add_meta_data('highriskshop_topperpaycom_callback', $highriskshopgateway_topperpaycom_gen_callback, true);
	$order->add_meta_data('highriskshop_topperpaycom_converted_amount', $highriskshopgateway_topperpaycom_final_total, true);
	$order->add_meta_data('highriskshop_topperpaycom_nonce', $highriskshopgateway_topperpaycom_nonce, true);
    $order->save();
    } else {
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (wallet address error)', 'topperpaycom'), 'error');

        return null;
    }
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://pay.highriskshop.com/process-payment.php?address=' . $highriskshopgateway_topperpaycom_gen_addressIn . '&amount=' . (float)$highriskshopgateway_topperpaycom_final_total . '&provider=topper&email=' . $highriskshopgateway_topperpaycom_email . '&currency=' . $highriskshopgateway_topperpaycom_currency,
        );
    }

}

function highriskshop_add_instant_payment_gateway_topper($gateways) {
    $gateways[] = 'HighRiskShop_Instant_Payment_Gateway_Topper';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'highriskshop_add_instant_payment_gateway_topper');
}

// Add custom endpoint for changing order status
function highriskshopgateway_topperpaycom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'highriskshopgateway/v1', '/highriskshopgateway-topperpaycom/', array(
        'methods'  => 'GET',
        'callback' => 'highriskshopgateway_topperpaycom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'highriskshopgateway_topperpaycom_change_order_status_rest_endpoint' );

// Callback function to change order status
function highriskshopgateway_topperpaycom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$highriskshopgateway_topperpaycomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$highriskshopgateway_topperpaycompaid_txid_out = sanitize_text_field($request->get_param('txid_out'));

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'highriskshop-instant-payment-gateway-topper' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'highriskshop-instant-payment-gateway-topper' ), array( 'status' => 404 ) );
    }
	
	// Verify nonce
    if ( empty( $highriskshopgateway_topperpaycomgetnonce ) || $order->get_meta('highriskshop_topperpaycom_nonce', true) !== $highriskshopgateway_topperpaycomgetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'highriskshop-instant-payment-gateway-topper' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'highriskshop-instant-payment-gateway-topper'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'highriskshop-instant-payment-gateway-topper' === $order->get_payment_method() ) {
        // Change order status to processing
		$order->payment_complete();
        $order->update_status( 'processing' );
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'highriskshop-instant-payment-gateway-topper'), $highriskshopgateway_topperpaycompaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order status changed to processing.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'highriskshop-instant-payment-gateway-topper' ), array( 'status' => 400 ) );
    }
}
?>