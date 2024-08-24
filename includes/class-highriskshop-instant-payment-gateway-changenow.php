<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_highriskshopgateway_changenow_gateway');

function init_highriskshopgateway_changenow_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class HighRiskShop_Instant_Payment_Gateway_Changenow extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'highriskshop-instant-payment-gateway-changenow';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (changenow.io)', 'highriskshopgateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDT POLYGON wallet using changenow.io infrastructure', 'highriskshopgateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->changenowio_wallet_address = sanitize_text_field($this->get_option('changenowio_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'highriskshopgateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable changenow.io payment gateway', 'highriskshopgateway'), // Escaping label
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
            'changenowio_wallet_address' => array(
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
        $highriskshopgateway_changenowio_currency = get_woocommerce_currency();
		$highriskshopgateway_changenowio_total = $order->get_total();
		$highriskshopgateway_changenowio_nonce = wp_create_nonce( 'highriskshopgateway_changenowio_nonce_' . $order_id );
		$highriskshopgateway_changenowio_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $highriskshopgateway_changenowio_nonce,), rest_url('highriskshopgateway/v1/highriskshopgateway-changenowio/'));
		$highriskshopgateway_changenowio_email = urlencode(sanitize_email($order->get_billing_email()));
		$highriskshopgateway_changenowio_final_total = $highriskshopgateway_changenowio_total;
		
		if ($highriskshopgateway_changenowio_currency === 'USD') {
		$highriskshopgateway_changenowio_reference_total = (float)$highriskshopgateway_changenowio_final_total;
		} else {
		
$highriskshopgateway_changenowio_response = wp_remote_get('https://api.highriskshop.com/control/convert.php?value=' . $highriskshopgateway_changenowio_total . '&from=' . strtolower($highriskshopgateway_changenowio_currency));

if (is_wp_error($highriskshopgateway_changenowio_response)) {
    // Handle error
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed due to failed currency conversion process, please try again', 'hrschangenowio'), 'error');
    return null;
} else {

$highriskshopgateway_changenowio_body = wp_remote_retrieve_body($highriskshopgateway_changenowio_response);
$highriskshopgateway_changenowio_conversion_resp = json_decode($highriskshopgateway_changenowio_body, true);

if ($highriskshopgateway_changenowio_conversion_resp && isset($highriskshopgateway_changenowio_conversion_resp['value_coin'])) {
    // Escape output
    $highriskshopgateway_changenowio_finalusd_total	= sanitize_text_field($highriskshopgateway_changenowio_conversion_resp['value_coin']);
    $highriskshopgateway_changenowio_reference_total = (float)$highriskshopgateway_changenowio_finalusd_total;	
} else {
    wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (unsupported store currency)', 'hrschangenowio'), 'error');
    return null;
}	
		}
		}
	
$highriskshopgateway_changenowio_gen_wallet = wp_remote_get('https://api.highriskshop.com/control/wallet.php?address=' . $this->changenowio_wallet_address .'&callback=' . urlencode($highriskshopgateway_changenowio_callback));

if (is_wp_error($highriskshopgateway_changenowio_gen_wallet)) {
    // Handle error
    wc_add_notice(__('Wallet error:', 'woocommerce') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'hrschangenowio'), 'error');
    return null;
} else {
	$highriskshopgateway_changenowio_wallet_body = wp_remote_retrieve_body($highriskshopgateway_changenowio_gen_wallet);
	$highriskshopgateway_changenowio_wallet_decbody = json_decode($highriskshopgateway_changenowio_wallet_body, true);

 // Check if decoding was successful
    if ($highriskshopgateway_changenowio_wallet_decbody && isset($highriskshopgateway_changenowio_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $highriskshopgateway_changenowio_gen_addressIn = wp_kses_post($highriskshopgateway_changenowio_wallet_decbody['address_in']);
        $highriskshopgateway_changenowio_gen_polygon_addressIn = sanitize_text_field($highriskshopgateway_changenowio_wallet_decbody['polygon_address_in']);
		$highriskshopgateway_changenowio_gen_callback = sanitize_url($highriskshopgateway_changenowio_wallet_decbody['callback_url']);
		// Save $changenowioresponse in order meta data
    $order->add_meta_data('highriskshop_changenowio_tracking_address', $highriskshopgateway_changenowio_gen_addressIn, true);
    $order->add_meta_data('highriskshop_changenowio_polygon_temporary_order_wallet_address', $highriskshopgateway_changenowio_gen_polygon_addressIn, true);
    $order->add_meta_data('highriskshop_changenowio_callback', $highriskshopgateway_changenowio_gen_callback, true);
	$order->add_meta_data('highriskshop_changenowio_converted_amount', $highriskshopgateway_changenowio_final_total, true);
	$order->add_meta_data('highriskshop_changenowio_expected_amount', $highriskshopgateway_changenowio_reference_total, true);
	$order->add_meta_data('highriskshop_changenowio_nonce', $highriskshopgateway_changenowio_nonce, true);
    $order->save();
    } else {
        wc_add_notice(__('Payment error:', 'woocommerce') . __('Payment could not be processed, please try again (wallet address error)', 'changenowio'), 'error');

        return null;
    }
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://pay.highriskshop.com/process-payment.php?address=' . $highriskshopgateway_changenowio_gen_addressIn . '&amount=' . (float)$highriskshopgateway_changenowio_final_total . '&provider=changenow&email=' . $highriskshopgateway_changenowio_email . '&currency=' . $highriskshopgateway_changenowio_currency,
        );
    }

}

function highriskshop_add_instant_payment_gateway_changenow($gateways) {
    $gateways[] = 'HighRiskShop_Instant_Payment_Gateway_Changenow';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'highriskshop_add_instant_payment_gateway_changenow');
}

// Add custom endpoint for changing order status
function highriskshopgateway_changenowio_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'highriskshopgateway/v1', '/highriskshopgateway-changenowio/', array(
        'methods'  => 'GET',
        'callback' => 'highriskshopgateway_changenowio_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'highriskshopgateway_changenowio_change_order_status_rest_endpoint' );

// Callback function to change order status
function highriskshopgateway_changenowio_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$highriskshopgateway_changenowiogetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$highriskshopgateway_changenowiopaid_value_coin = sanitize_text_field($request->get_param('value_coin'));
	$highriskshopgateway_changenowiofloatpaid_value_coin = (float)$highriskshopgateway_changenowiopaid_value_coin;

    // Check if order ID parameter exists
    if ( empty( $order_id ) ) {
        return new WP_Error( 'missing_order_id', __( 'Order ID parameter is missing.', 'highriskshop-instant-payment-gateway-changenow' ), array( 'status' => 400 ) );
    }

    // Get order object
    $order = wc_get_order( $order_id );

    // Check if order exists
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', __( 'Invalid order ID.', 'highriskshop-instant-payment-gateway-changenow' ), array( 'status' => 404 ) );
    }
	
	// Verify nonce
    if ( empty( $highriskshopgateway_changenowiogetnonce ) || $order->get_meta('highriskshop_changenowio_nonce', true) !== $highriskshopgateway_changenowiogetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'highriskshop-instant-payment-gateway-changenow' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'highriskshop-instant-payment-gateway-changenow'
    if ( $order && $order->get_status() === 'pending' && 'highriskshop-instant-payment-gateway-changenow' === $order->get_payment_method() ) {
	$highriskshopgateway_changenowioexpected_amount = (float)$order->get_meta('highriskshop_changenowio_expected_amount', true);
	$highriskshopgateway_changenowiothreshold = 0.80 * $highriskshopgateway_changenowioexpected_amount;
		if ( $highriskshopgateway_changenowiofloatpaid_value_coin < $highriskshopgateway_changenowiothreshold ) {
			// Mark the order as failed and add an order note
            $order->update_status('failed', __( 'Payment received is less than 80% of the order total. Customer may have changed the payment values on the checkout page.', 'highriskshop-instant-payment-gateway-changenow' ));
            $order->add_order_note( __( 'Order marked as failed: Payment received is less than 80% of the order total. Customer may have changed the payment values on the checkout page.', 'highriskshop-instant-payment-gateway-changenow' ) );
            return array( 'message' => 'Order status changed to failed due to partial payment.' );
			
		} else {
        // Change order status to processing
		 $order->payment_complete();
        $order->update_status( 'processing' );
        // Return success response
        return array( 'message' => 'Order status changed to processing.' );
	}
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'highriskshop-instant-payment-gateway-changenow' ), array( 'status' => 400 ) );
    }
}
?>