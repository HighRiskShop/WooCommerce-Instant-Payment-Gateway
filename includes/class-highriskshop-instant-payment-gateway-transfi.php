<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_highriskshopgateway_transficom_gateway');

function init_highriskshopgateway_transficom_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

class HighRiskShop_Instant_Payment_Gateway_Transfi extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'highriskshop-instant-payment-gateway-transfi';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (transfi.com)', 'highriskshopgateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDT POLYGON wallet using transfi.com infrastructure', 'highriskshopgateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->transficom_wallet_address = sanitize_text_field($this->get_option('transficom_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'highriskshopgateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable transfi.com payment gateway', 'highriskshopgateway'), // Escaping label
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
            'transficom_wallet_address' => array(
                'title'       => esc_html__('Wallet Address', 'highriskshopgateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Insert your USDT (Polygon) wallet address to receive instant payouts.', 'highriskshopgateway'), // Escaping description
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
        $highriskshopgateway_transficom_currency = get_woocommerce_currency();
		$highriskshopgateway_transficom_total = $order->get_total();
		$highriskshopgateway_transficom_nonce = wp_create_nonce( 'highriskshopgateway_transficom_nonce_' . $order_id );
		$highriskshopgateway_transficom_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $highriskshopgateway_transficom_nonce,), rest_url('highriskshopgateway/v1/highriskshopgateway-transficom/'));
		$highriskshopgateway_transficom_email = urlencode(sanitize_email($order->get_billing_email()));
		
		if ($highriskshopgateway_transficom_currency === 'USD') {
        $highriskshopgateway_transficom_final_total = $highriskshopgateway_transficom_total;
		} else {
		
$highriskshopgateway_transficom_response = wp_remote_get('https://api.highriskshop.com/control/convert.php?value=' . $highriskshopgateway_transficom_total . '&from=' . strtolower($highriskshopgateway_transficom_currency));

if (is_wp_error($highriskshopgateway_transficom_response)) {
    // Handle error
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'hrstransficom'), 'error');
    return null;
} else {

$highriskshopgateway_transficom_body = wp_remote_retrieve_body($highriskshopgateway_transficom_response);
$highriskshopgateway_transficom_conversion_resp = json_decode($highriskshopgateway_transficom_body, true);

if ($highriskshopgateway_transficom_conversion_resp && isset($highriskshopgateway_transficom_conversion_resp['value_coin'])) {
    // Escape output
    $highriskshopgateway_transficom_final_total	= sanitize_text_field($highriskshopgateway_transficom_conversion_resp['value_coin']);      
} else {
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'hrstransficom'), 'error');
    return null;
}	
		}
		}
$highriskshopgateway_transficom_gen_wallet = wp_remote_get('https://api.highriskshop.com/control/wallet.php?address=' . $this->transficom_wallet_address .'&callback=' . urlencode($highriskshopgateway_transficom_callback));

if (is_wp_error($highriskshopgateway_transficom_gen_wallet)) {
    // Handle error
    wc_add_notice(__('Wallet error:', 'woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'hrstransficom'), 'error');
    return null;
} else {
	$highriskshopgateway_transficom_wallet_body = wp_remote_retrieve_body($highriskshopgateway_transficom_gen_wallet);
	$highriskshopgateway_transficom_wallet_decbody = json_decode($highriskshopgateway_transficom_wallet_body, true);

 // Check if decoding was successful
    if ($highriskshopgateway_transficom_wallet_decbody && isset($highriskshopgateway_transficom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $highriskshopgateway_transficom_gen_addressIn = wp_kses_post($highriskshopgateway_transficom_wallet_decbody['address_in']);
        $highriskshopgateway_transficom_gen_polygon_addressIn = sanitize_text_field($highriskshopgateway_transficom_wallet_decbody['polygon_address_in']);
		$highriskshopgateway_transficom_gen_callback = sanitize_url($highriskshopgateway_transficom_wallet_decbody['callback_url']);
		// Save $transficomresponse in order meta data
    $order->update_meta_data('highriskshop_transficom_tracking_address', $highriskshopgateway_transficom_gen_addressIn);
    $order->update_meta_data('highriskshop_transficom_polygon_temporary_order_wallet_address', $highriskshopgateway_transficom_gen_polygon_addressIn);
    $order->update_meta_data('highriskshop_transficom_callback', $highriskshopgateway_transficom_gen_callback);
	$order->update_meta_data('highriskshop_transficom_converted_amount', $highriskshopgateway_transficom_final_total);
    $order->save();
    } else {
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (wallet address error)', 'transficom'), 'error');

        return null;
    }
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://pay.highriskshop.com/process-payment.php?address=' . $highriskshopgateway_transficom_gen_addressIn . '&amount=' . (float)$highriskshopgateway_transficom_final_total . '&provider=transfi&email=' . $highriskshopgateway_transficom_email . '&currency=' . $highriskshopgateway_transficom_currency,
        );
    }

}

function highriskshop_add_instant_payment_gateway_transficom($gateways) {
    $gateways[] = 'HighRiskShop_Instant_Payment_Gateway_Transfi';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'highriskshop_add_instant_payment_gateway_transficom');
}

// Add custom endpoint for changing order status
function highriskshopgateway_transficom_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'highriskshopgateway/v1', '/highriskshopgateway-transficom/', array(
        'methods'  => 'GET',
        'callback' => 'highriskshopgateway_transficom_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'highriskshopgateway_transficom_change_order_status_rest_endpoint' );

// Callback function to change order status
function highriskshopgateway_transficom_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$highriskshopgateway_transficomgetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	
	 // Verify nonce
    if ( empty( $highriskshopgateway_transficomgetnonce ) || ! wp_verify_nonce( $highriskshopgateway_transficomgetnonce, 'highriskshopgateway_transficom_nonce_' . $order_id ) ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'highriskshop-instant-payment-gateway-transfi' ), array( 'status' => 403 ) );
    }

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'highriskshop-instant-payment-gateway-transfi' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'highriskshop-instant-payment-gateway-transfi' ), array( 'status' => 404 ) );
    }

    // Check if the order is pending and payment method is 'highriskshop-instant-payment-gateway-transfi'
    if ( $order && $order->get_status() === 'pending' && 'highriskshop-instant-payment-gateway-transfi' === $order->get_payment_method() ) {
        // Change order status to processing
		 $order->payment_complete();
        $order->update_status( 'processing' );
        // Return success response
        return array( 'message' => 'Order status changed to processing.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'highriskshop-instant-payment-gateway-transfi' ), array( 'status' => 400 ) );
    }
}
?>