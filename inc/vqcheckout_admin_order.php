<?php
/**
 * VQ Checkout - Admin Order Display (File 07)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// (Hàm vqcheckout_init_admin_order và các hàm formatting/injection giữ nguyên)

/**
 * Initialize Admin Order customizations
 */
function vqcheckout_init_admin_order() {
    // Customize address formatting for VN (Applies globally: Admin, Emails, PDFs)
    add_filter('woocommerce_localisation_address_formats', 'vqcheckout_vn_address_format', 20);
        
    // Populate formatted address fields with localized names instead of codes
    add_filter('woocommerce_formatted_address_replacements', 'vqcheckout_replace_address_codes_with_names', 20, 2);

    // Handle Gender display integration
    if (vqcheckout_get_option('enable_gender', '1') === '1') {
        // Make gender data available during address formatting
        add_filter('woocommerce_my_account_my_address_formatted_address', 'vqcheckout_inject_gender_into_address_args', 10, 3);
        add_filter('woocommerce_order_get_billing_address', 'vqcheckout_inject_gender_from_order', 10, 2);
        // Display Gender field in admin order screen (Billing)
        add_filter('woocommerce_admin_billing_fields', 'vqcheckout_admin_order_gender_field_data');
    }

    // Customize columns in the main Orders list view (HPOS compatible)
    if (is_admin()) {
        // Determine if HPOS is active
        $is_hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        if ($is_hpos) {
            add_filter('manage_woocommerce_page_wc-orders_columns', 'vqcheckout_add_order_list_columns', 20);
            add_action('manage_woocommerce_page_wc-orders_custom_column', 'vqcheckout_render_order_list_columns_hpos', 20, 2);
        } else {
            add_filter('manage_edit-shop_order_columns', 'vqcheckout_add_order_list_columns', 20);
            add_action('manage_shop_order_posts_custom_column', 'vqcheckout_render_order_list_columns_legacy', 20, 2);
        }
    }
}
add_action('init', 'vqcheckout_init_admin_order');

// (Các hàm formatting giữ nguyên)

/**
 * Define the address format for Vietnam (VN)
 */
function vqcheckout_vn_address_format($formats) {
    // Format: Name (includes Gender prefix), Address 1, Ward (City), Province (State)
    $formats['VN'] = "{name}\n{company}\n{address_1}\n{city}, {state}";
    
    // Add postcode if enabled
    if (vqcheckout_get_option('show_postcode', '0') === '1') {
        $formats['VN'] .= "\n{postcode}";
    }
    
    // Add country if enabled
    if (vqcheckout_get_option('show_country', '0') === '1') {
         $formats['VN'] .= "\n{country}";
    }

    return $formats;
}

/**
 * Replace address codes (State/City) with names in formatted output
 */
function vqcheckout_replace_address_codes_with_names($replacements, $args) {
    $country = $args['country'] ?? '';
    
    // Apply transformation for Vietnam
    if ($country === 'VN') {
        $province_code = $args['state'] ?? '';
        $ward_code = $args['city'] ?? ''; // City holds the ward code

        // Replace State code with Province Name
        if (!empty($province_code)) {
            $province_name = vqcheckout_get_province_name($province_code);
            $replacements['{state}'] = $province_name;
        }

        // Replace City code with Ward Name
        if (!empty($ward_code) && !empty($province_code)) {
            $ward_name = vqcheckout_get_ward_name($province_code, $ward_code);
            $replacements['{city}'] = $ward_name;
        }
    }
    
    // Handle Gender prefix if enabled and available in args
    if (isset($args['gender']) && !empty($args['gender'])) {
        $prefix = ($args['gender'] === 'male') ? __('Anh', 'vq-checkout') : ($args['gender'] === 'female' ? __('Chị', 'vq-checkout') : '');

        if ($prefix) {
            // Prepend prefix to the name replacement
            $replacements['{name}'] = $prefix . ' ' . $replacements['{name}'];
        }
    }
    
    return $replacements;
}

/**
 * Inject Gender data into address arguments (My Account)
 */
function vqcheckout_inject_gender_into_address_args($address, $customer_id, $name) {
    if ($name === 'billing' && is_array($address)) {
        $gender = get_user_meta($customer_id, 'billing_gender', true);
        if ($gender) {
            $address['gender'] = $gender;
        }
    }
    return $address;
}

/**
 * Inject Gender data into address arguments (Order Object)
 */
function vqcheckout_inject_gender_from_order($address, $order) {
     if (is_array($address)) {
        $gender = $order->get_meta('_billing_gender');
        if ($gender) {
            $address['gender'] = $gender;
        }
    }
    return $address;
}

/**
 * Make Gender data available in admin order screen fields structure
 */
function vqcheckout_admin_order_gender_field_data($fields) {
    // 'show' => false means data is available for formatting but not displayed as a separate field.
    // We check if gender is enabled before adding it.
    if (vqcheckout_get_option('enable_gender', '1') === '1') {
        $fields['gender'] = array(
            'label' => __('Xưng hô', 'vq-checkout'),
            'show'  => false
        );
    }
    return $fields;
}


/**
 * Add custom columns to the admin order list
 */
function vqcheckout_add_order_list_columns($columns) {
    $new_columns = array();

    // Remove default WC columns that are verbose or show codes
    if (isset($columns['billing_address'])) unset($columns['billing_address']);
    if (isset($columns['shipping_address'])) unset($columns['shipping_address']);

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // Insert custom columns after 'order_total'
        if ($key === 'order_total') {
            $new_columns['vq_billing_phone'] = __('SĐT', 'vq-checkout');
            $new_columns['vq_location'] = __('Khu vực (Tỉnh/Xã)', 'vq-checkout');
        }
    }
    return $new_columns;
}

/**
 * Render custom columns content (HPOS)
 */
function vqcheckout_render_order_list_columns_hpos($column, $order) {
    vqcheckout_render_order_list_columns_shared($column, $order);
}

/**
 * Render custom columns content (Legacy Posts)
 */
function vqcheckout_render_order_list_columns_legacy($column, $post_id) {
    $order = wc_get_order($post_id);
    vqcheckout_render_order_list_columns_shared($column, $order);
}

/**
 * Shared logic for rendering custom columns (FIXED: Headers already sent error)
 */
function vqcheckout_render_order_list_columns_shared($column, $order) {
    // Ensure $order is a valid WC_Order object
    if (!($order instanceof WC_Order)) {
        // Attempt fallback if ID was passed (robustness)
        if (is_numeric($order)) {
            $order = wc_get_order($order);
        }
        if (!($order instanceof WC_Order)) {
            return;
        }
    }

    switch ($column) {
        case 'vq_billing_phone':
            $phone = $order->get_billing_phone();
            if ($phone) {
                echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
            } else {
                echo '—';
            }
            break;

        case 'vq_location':
            // Prioritize Shipping address for location, fallback to Billing
            $country = $order->get_shipping_country() ?: $order->get_billing_country();
            $province_code = $order->get_shipping_state() ?: $order->get_billing_state();
            $ward_code = $order->get_shipping_city() ?: $order->get_billing_city();
            
            if ($country === 'VN') {
                $province_name = vqcheckout_get_province_name($province_code);
                $ward_name = vqcheckout_get_ward_name($province_code, $ward_code);

                if ($province_name && $province_name != $province_code) {
                    echo esc_html($province_name);
                    // Display Ward name below Province name
                    if ($ward_name && $ward_name != $ward_code) {
                         echo '<br><small>' . esc_html($ward_name) . '</small>';
                    }
                } else {
                    echo '—';
                }
            } else if ($country) {
                 // Fallback for non-VN
                 $city = $ward_code; // City code is actual city name
                 
                 // FIX: Robust way to get country name without causing PHP Notices (Headers already sent issue)
                 // Check if WC()->countries object exists before attempting to use it.
                 $countries_list = (function_exists('WC') && WC()->countries) ? WC()->countries->get_countries() : array();
                 $country_name = isset($countries_list[$country]) ? $countries_list[$country] : $country;

                 echo esc_html($city . ', ' . $country_name);
            } else {
                echo '—';
            }
            break;
    }
}