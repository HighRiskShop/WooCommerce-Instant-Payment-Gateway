<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'init_paygatedottogateway_simpleswap_gateway');

function init_paygatedottogateway_simpleswap_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }


class PayGateDotTo_Instant_Payment_Gateway_Simpleswap extends WC_Payment_Gateway {

    protected $icon_url;
    protected $simpleswapio_wallet_address;

    public function __construct() {
        $this->id                 = 'paygatedotto-instant-payment-gateway-simpleswap';
        $this->icon = sanitize_url($this->get_option('icon_url'));
        $this->method_title       = esc_html__('Instant Approval Payment Gateway with Instant Payouts (simpleswap.io)', 'instant-approval-payment-gateway'); // Escaping title
        $this->method_description = esc_html__('Instant Approval High Risk Merchant Gateway with instant payouts to your USDC POLYGON wallet using simpleswap.io infrastructure', 'instant-approval-payment-gateway'); // Escaping description
        $this->has_fields         = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = sanitize_text_field($this->get_option('title'));
        $this->description = sanitize_text_field($this->get_option('description'));

        // Use the configured settings for redirect and icon URLs
        $this->simpleswapio_wallet_address = sanitize_text_field($this->get_option('simpleswapio_wallet_address'));
        $this->icon_url     = sanitize_url($this->get_option('icon_url'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => esc_html__('Enable/Disable', 'instant-approval-payment-gateway'), // Escaping title
                'type'    => 'checkbox',
                'label'   => esc_html__('Enable simpleswap.io payment gateway', 'instant-approval-payment-gateway'), // Escaping label
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
            'simpleswapio_wallet_address' => array(
                'title'       => esc_html__('Wallet Address', 'instant-approval-payment-gateway'), // Escaping title
                'type'        => 'text',
                'description' => esc_html__('Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.', 'instant-approval-payment-gateway'), // Escaping description
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
        $simpleswapio_admin_wallet_address = isset($_POST[$this->plugin_id . $this->id . '_simpleswapio_wallet_address']) ? sanitize_text_field( wp_unslash( $_POST[$this->plugin_id . $this->id . '_simpleswapio_wallet_address'])) : '';

        // Check if wallet address starts with "0x"
        if (substr($simpleswapio_admin_wallet_address, 0, 2) !== '0x') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'instant-approval-payment-gateway'));
            return false;
        }

        // Check if wallet address matches the USDC contract address
        if (strtolower($simpleswapio_admin_wallet_address) === '0x3c499c542cef5e3811e1192ce70d8cc03d5c3359') {
            WC_Admin_Settings::add_error(__('Invalid Wallet Address: Please insert your USDC Polygon wallet address.', 'instant-approval-payment-gateway'));
            return false;
        }

        // Proceed with the default processing if validations pass
        return parent::process_admin_options();
    }
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $paygatedottogateway_simpleswapio_currency = get_woocommerce_currency();
		$paygatedottogateway_simpleswapio_total = $order->get_total();
		$paygatedottogateway_simpleswapio_nonce = wp_create_nonce( 'paygatedottogateway_simpleswapio_nonce_' . $order_id );
		$paygatedottogateway_simpleswapio_callback = add_query_arg(array('order_id' => $order_id, 'nonce' => $paygatedottogateway_simpleswapio_nonce,), rest_url('paygatedottogateway/v1/paygatedottogateway-simpleswapio/'));
		$paygatedottogateway_simpleswapio_email = urlencode(sanitize_email($order->get_billing_email()));
		$paygatedottogateway_simpleswapio_final_total = $paygatedottogateway_simpleswapio_total;
		
if ($paygatedottogateway_simpleswapio_currency === 'USD') {
        $paygatedottogateway_simpleswapio_minimumcheck = $paygatedottogateway_simpleswapio_total;
		} else {
		
$paygatedottogateway_simpleswapio_minimumcheck_response = wp_remote_get('https://api.paygate.to/control/convert.php?value=' . $paygatedottogateway_simpleswapio_total . '&from=' . strtolower($paygatedottogateway_simpleswapio_currency), array('timeout' => 30));

if (is_wp_error($paygatedottogateway_simpleswapio_minimumcheck_response)) {
    // Handle error
    paygatedottogateway_add_notice(__('Payment error:', 'instant-approval-payment-gateway') . __('Payment could not be processed due to failed currency conversion process, please try again', 'instant-approval-payment-gateway'), 'error');
    return null;
} else {

$paygatedottogateway_simpleswapio_minimumcheck_body = wp_remote_retrieve_body($paygatedottogateway_simpleswapio_minimumcheck_response);
$paygatedottogateway_simpleswapio_minimum_conversion_resp = json_decode($paygatedottogateway_simpleswapio_minimumcheck_body, true);

if ($paygatedottogateway_simpleswapio_minimum_conversion_resp && isset($paygatedottogateway_simpleswapio_minimum_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedottogateway_simpleswapio_minimum_conversion_total	= sanitize_text_field($paygatedottogateway_simpleswapio_minimum_conversion_resp['value_coin']);
    $paygatedottogateway_simpleswapio_minimumcheck = (float)$paygatedottogateway_simpleswapio_minimum_conversion_total;	
} else {
    paygatedottogateway_add_notice(__('Payment error:', 'instant-approval-payment-gateway') . __('Payment could not be processed, please try again (unsupported store currency)', 'instant-approval-payment-gateway'), 'error');
    return null;
}	
		}
		}
		
if ($paygatedottogateway_simpleswapio_minimumcheck < 30) {
paygatedottogateway_add_notice(__('Payment error:', 'instant-approval-payment-gateway') . __('Order total for this payment provider must be $30 USD or more.', 'instant-approval-payment-gateway'), 'error');
return null;
}		
	
$paygatedottogateway_simpleswapio_gen_wallet = wp_remote_get('https://api.paygate.to/control/wallet.php?address=' . $this->simpleswapio_wallet_address .'&callback=' . urlencode($paygatedottogateway_simpleswapio_callback), array('timeout' => 30));

if (is_wp_error($paygatedottogateway_simpleswapio_gen_wallet)) {
    // Handle error
    paygatedottogateway_add_notice(__('Wallet error:', 'instant-approval-payment-gateway') . __('Payment could not be processed due to incorrect payout wallet settings, please contact website admin', 'instant-approval-payment-gateway'), 'error');
    return null;
} else {
	$paygatedottogateway_simpleswapio_wallet_body = wp_remote_retrieve_body($paygatedottogateway_simpleswapio_gen_wallet);
	$paygatedottogateway_simpleswapio_wallet_decbody = json_decode($paygatedottogateway_simpleswapio_wallet_body, true);

 // Check if decoding was successful
    if ($paygatedottogateway_simpleswapio_wallet_decbody && isset($paygatedottogateway_simpleswapio_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedottogateway_simpleswapio_gen_addressIn = wp_kses_post($paygatedottogateway_simpleswapio_wallet_decbody['address_in']);
        $paygatedottogateway_simpleswapio_gen_polygon_addressIn = sanitize_text_field($paygatedottogateway_simpleswapio_wallet_decbody['polygon_address_in']);
		$paygatedottogateway_simpleswapio_gen_callback = sanitize_url($paygatedottogateway_simpleswapio_wallet_decbody['callback_url']);
		// Save $simpleswapioresponse in order meta data
    $order->add_meta_data('paygatedotto_simpleswapio_tracking_address', $paygatedottogateway_simpleswapio_gen_addressIn, true);
    $order->add_meta_data('paygatedotto_simpleswapio_polygon_temporary_order_wallet_address', $paygatedottogateway_simpleswapio_gen_polygon_addressIn, true);
    $order->add_meta_data('paygatedotto_simpleswapio_callback', $paygatedottogateway_simpleswapio_gen_callback, true);
	$order->add_meta_data('paygatedotto_simpleswapio_converted_amount', $paygatedottogateway_simpleswapio_final_total, true);
	$order->add_meta_data('paygatedotto_simpleswapio_nonce', $paygatedottogateway_simpleswapio_nonce, true);
    $order->save();
    } else {
        paygatedottogateway_add_notice(__('Payment error:', 'instant-approval-payment-gateway') . __('Payment could not be processed, please try again (wallet address error)', 'instant-approval-payment-gateway'), 'error');

        return null;
    }
}

// Check if the Checkout page is using Checkout Blocks
if (paygatedottogateway_is_checkout_block()) {
    global $woocommerce;
	$woocommerce->cart->empty_cart();
}

        // Redirect to payment page
        return array(
            'result'   => 'success',
            'redirect' => 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedottogateway_simpleswapio_gen_addressIn . '&amount=' . (float)$paygatedottogateway_simpleswapio_final_total . '&provider=simpleswap&email=' . $paygatedottogateway_simpleswapio_email . '&currency=' . $paygatedottogateway_simpleswapio_currency,
        );
    }

public function paygatedotto_instant_payment_gateway_get_icon_url() {
        return !empty($this->icon_url) ? esc_url($this->icon_url) : '';
    }
}

function paygatedotto_add_instant_payment_gateway_simpleswap($gateways) {
    $gateways[] = 'PayGateDotTo_Instant_Payment_Gateway_Simpleswap';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'paygatedotto_add_instant_payment_gateway_simpleswap');
}

// Add custom endpoint for changing order status
function paygatedottogateway_simpleswapio_change_order_status_rest_endpoint() {
    // Register custom route
    register_rest_route( 'paygatedottogateway/v1', '/paygatedottogateway-simpleswapio/', array(
        'methods'  => 'GET',
        'callback' => 'paygatedottogateway_simpleswapio_change_order_status_callback',
        'permission_callback' => '__return_true',
    ));
}
add_action( 'rest_api_init', 'paygatedottogateway_simpleswapio_change_order_status_rest_endpoint' );

// Callback function to change order status
function paygatedottogateway_simpleswapio_change_order_status_callback( $request ) {
    $order_id = absint($request->get_param( 'order_id' ));
	$paygatedottogateway_simpleswapiogetnonce = sanitize_text_field($request->get_param( 'nonce' ));
	$paygatedottogateway_simpleswapiopaid_txid_out = sanitize_text_field($request->get_param('txid_out'));

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
    if ( empty( $paygatedottogateway_simpleswapiogetnonce ) || $order->get_meta('paygatedotto_simpleswapio_nonce', true) !== $paygatedottogateway_simpleswapiogetnonce ) {
        return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'instant-approval-payment-gateway' ), array( 'status' => 403 ) );
    }

    // Check if the order is pending and payment method is 'paygatedotto-instant-payment-gateway-simpleswap'
    if ( $order && $order->get_status() !== 'processing' && $order->get_status() !== 'completed' && 'paygatedotto-instant-payment-gateway-simpleswap' === $order->get_payment_method() ) {
        // Change order status to processing
		$order->payment_complete();
		/* translators: 1: Transaction ID */
		$order->add_order_note( sprintf(__('Payment completed by the provider TXID: %1$s', 'instant-approval-payment-gateway'), $paygatedottogateway_simpleswapiopaid_txid_out) );
        // Return success response
        return array( 'message' => 'Order marked as paid and status changed.' );
    } else {
        // Return error response if conditions are not met
        return new WP_Error( 'order_not_eligible', __( 'Order is not eligible for status change.', 'instant-approval-payment-gateway' ), array( 'status' => 400 ) );
    }
}
?>