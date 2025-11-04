<?php
/**
 * VQ Checkout - Store Settings Integration (File 03)
 * Override WooCommerce Store Settings (General Tab) to use 2-level address system.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Store Settings customization
 */
function vqcheckout_init_store_settings() {
    if (is_admin()) {
        // Hook into WC General Settings fields
        add_filter('woocommerce_general_settings', 'vqcheckout_customize_store_address_fields', 20);
        // Enqueue specific admin script for this page
        add_action('admin_enqueue_scripts', 'vqcheckout_enqueue_store_settings_script');
    }
}
add_action('init', 'vqcheckout_init_store_settings');

/**
 * Customize Store Address fields in WC Settings > General
 * We manipulate the settings array to redefine the City field and ensure proper positioning.
 */
function vqcheckout_customize_store_address_fields($settings) {
    $updated_settings = array();
    
    // Determine initial Ward options based on currently saved Province
    // WC stores base location in 'woocommerce_default_country' option as 'CC:STATE'
    $current_location = get_option('woocommerce_default_country', 'VN');
    $current_province_code = '';
    
    if (is_string($current_location) && strpos($current_location, 'VN:') === 0) {
        $current_province_code = substr($current_location, 3);
    }

    // Pre-populate Ward options in PHP for reliability on initial load
    $initial_ward_options = array('' => __('Chọn tỉnh/thành trước...', 'vq-checkout'));
    if (!empty($current_province_code)) {
        $wards = vqcheckout_get_wards_by_province($current_province_code);
        if (!empty($wards)) {
            $initial_ward_options = array('' => __('Chọn xã/phường...', 'vq-checkout'));
            foreach ($wards as $code => $data) {
                $initial_ward_options[$code] = $data['name'];
            }
        }
    }

    foreach ($settings as $field) {
        
        // 1. Modify Country/State field (woocommerce_default_country)
        if (isset($field['id']) && $field['id'] === 'woocommerce_default_country') {
            $field['title'] = __('Quốc gia / Tỉnh Thành phố (Cơ sở)', 'vq-checkout');
            $field['desc_tip'] = __('Chọn quốc gia và tỉnh/thành phố chính của cửa hàng.', 'vq-checkout');
            // WC handles the options display logic based on registered states.
            $updated_settings[] = $field;
            
            // 2. Insert Custom Ward field (Mapped to woocommerce_store_city) immediately after
            $updated_settings[] = array(
                'title'    => __('Xã/Phường (Cơ sở)', 'vq-checkout'),
                'id'       => 'woocommerce_store_city', // Reuse the standard ID
                'type'     => 'select', // Change to select
                'class'    => 'wc-enhanced-select vqcheckout-store-ward',
                'css'      => 'min-width: 350px;',
                'default'  => get_option('woocommerce_store_city', ''), // Set default to current value
                'options'  => $initial_ward_options,
                'desc_tip' => __('Chọn xã/phường của cửa hàng.', 'vq-checkout'),
            );
            continue;
        }
        
        // 3. Remove the original City field definition (as we inserted a new one above)
        if (isset($field['id']) && $field['id'] === 'woocommerce_store_city') {
            continue;
        }

        // 4. Optionally hide Address Line 2 for a cleaner interface
        if (isset($field['id']) && $field['id'] === 'woocommerce_store_address_2') {
            continue;
        }

        // Keep other fields
        $updated_settings[] = $field;
    }
    
    return $updated_settings;
}

/**
 * Enqueue admin script for Store Settings page
 */
function vqcheckout_enqueue_store_settings_script($hook) {
    // Only load on WooCommerce Settings page
    if ($hook !== 'woocommerce_page_wc-settings') {
        return;
    }

    // Check if we are on the General tab (default tab)
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    if ($current_tab !== 'general') {
        return;
    }

    wp_enqueue_script(
        'vqcheckout-store-settings',
        VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_store_settings.js',
        array('jquery', 'selectWoo'), // WC uses selectWoo in admin
        VQCHECKOUT_VERSION,
        true
    );

    // Localization relies on the global 'vqcheckoutAdmin' object defined in Stage 1 (File 02).
}