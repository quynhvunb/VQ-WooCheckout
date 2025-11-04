<?php
/**
 * VQ Checkout - Auto-fill Module (File 07)
 * Automatically fills customer details based on phone number from previous orders (for guests).
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Auto-fill module
 */
function vqcheckout_init_autofill() {
    // Check if enabled in settings
    if (vqcheckout_get_option('autofill_enabled', '1') !== '1') {
        return;
    }

    // Enqueue frontend script
    add_action('wp_enqueue_scripts', 'vqcheckout_enqueue_autofill_script');
    
    // Register AJAX handler (only wp_ajax_nopriv_ needed as it targets guests)
    add_action('wp_ajax_nopriv_vqcheckout_autofill_lookup', 'vqcheckout_ajax_autofill_lookup');
}
add_action('init', 'vqcheckout_init_autofill');

/**
 * Enqueue Auto-fill script on checkout page
 */
function vqcheckout_enqueue_autofill_script() {
    // Only load on checkout page AND if user is NOT logged in
    if (is_checkout() && !is_user_logged_in()) {
        wp_enqueue_script(
            'vqcheckout-autofill',
            VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_autofill.js',
            array('jquery', 'vqcheckout-checkout'), // Depends on the main checkout script
            VQCHECKOUT_VERSION,
            true
        );
        
        // Localization relies on the global 'vqcheckoutCheckout' object defined in Stage 1 (File 02).
    }
}

/**
 * AJAX: Lookup customer data by phone number
 */
function vqcheckout_ajax_autofill_lookup() {
    // Use the main checkout nonce for security
    check_ajax_referer('vqcheckout_ajax_nonce', 'nonce');

    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

    // Validate phone format
    if (empty($phone) || !vqcheckout_is_valid_vn_phone($phone)) {
        wp_send_json_error(array('code' => 'invalid_phone'));
    }

    // Use WC Data Query (HPOS compatible) to find the most recent order
    $query = new WC_Order_Query(array(
        'limit' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'billing_phone' => $phone,
        // Look up from reliable statuses (paid orders)
        'status' => wc_get_is_paid_statuses(),
        'return' => 'ids',
    ));

    $order_ids = $query->get_orders();

    if (empty($order_ids)) {
        wp_send_json_error(array('code' => 'not_found'));
    }

    $order = wc_get_order($order_ids[0]);

    // Prepare data (Keys match checkout field IDs)
    $data = array(
        'billing_first_name' => $order->get_billing_first_name(),
        'billing_last_name'  => $order->get_billing_last_name(),
        'billing_email'      => $order->get_billing_email(),
        'billing_address_1'  => $order->get_billing_address_1(),
        'billing_country'    => $order->get_billing_country(),
        'billing_state'      => $order->get_billing_state(),   // Province Code
        'billing_city'       => $order->get_billing_city(),    // Ward Code
        'billing_postcode'   => $order->get_billing_postcode(),
        'billing_gender'     => $order->get_meta('_billing_gender', true),
    );

    // If Last Name is hidden in settings, combine names into the First Name field
    if (vqcheckout_get_option('show_last_name', '0') !== '1' && !empty($data['billing_last_name'])) {
        // Assuming Vietnamese convention: Họ (Last) + Tên (First)
        $data['billing_first_name'] = trim($data['billing_last_name'] . ' ' . $data['billing_first_name']);
        // Unset last name so JS doesn't try to fill a hidden field
        unset($data['billing_last_name']);
    }

    wp_send_json_success($data);
}