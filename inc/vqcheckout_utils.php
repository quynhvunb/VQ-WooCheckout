<?php
/**
 * VQ Checkout Utility Functions
 * Includes Data Loading, Caching, Settings Management, and Formatting Utilities.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// ----------------------------------------------------
// SETTINGS MANAGEMENT (File 02/07)
// ----------------------------------------------------

/**
 * Get default settings structure (FIXED: Ensure all keys are present)
 * @return array Default settings
 */
function vqcheckout_get_default_settings() {
    return array(
        // Checkout Fields
        'phone_vn_validation' => '1',
        'enable_gender' => '1',
        'not_required_email' => '1',
        'show_postcode' => '0',
        'show_country' => '0', // Mặc định ẩn (force VN)
        'show_last_name' => '0',
        'optimize_field_order' => '1', // Đưa SĐT/Email lên đầu

        // General
        'convert_price_text' => '0',
        'to_vnd' => '0',
        'remove_method_title' => '1',
        'freeship_remove_other_method' => '0',

        // PayPal Conversion
        'paypal_conversion_enabled' => '0',
        'paypal_exchange_rate' => '25000',

        // Modules
        'autofill_enabled' => '1',
        'anti_spam_enabled' => '1',

        // Anti-Spam Settings
        'recaptcha_enabled' => '0',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'blocked_ips' => '',
        'blocked_keywords' => '',
        'max_orders_per_hour' => 5,
    );
}

/**
 * Get plugin option from the main settings array
 */
function vqcheckout_get_option($key, $default = false) {
    // Use static variable to load and parse settings only once per request.
    // Merges saved options with defaults to prevent "Undefined array key" errors.
    static $settings = null;
    if (is_null($settings)) {
        $settings = wp_parse_args(get_option('vqcheckout_settings', array()), vqcheckout_get_default_settings());
    }
    
    // If key exists in the merged settings
    if (isset($settings[$key])) {
        return $settings[$key];
    }
    
    // Fallback (should be rare now)
    if ($default !== false) {
        return $default;
    }

    return false;
}


// ----------------------------------------------------
// DATA LOADING & CACHING
// ----------------------------------------------------
// (Phần còn lại của file Utils giữ nguyên như bản triển khai trước đó)

// Cache keys store the *optimized* data structure (Key-Value Maps)
if (!defined('VQCHECKOUT_PROVINCES_CACHE_KEY')) {
    define('VQCHECKOUT_PROVINCES_CACHE_KEY', 'vqcheckout_provinces_optimized_slug_map');
}
if (!defined('VQCHECKOUT_WARDS_CACHE_KEY')) {
    define('VQCHECKOUT_WARDS_CACHE_KEY', 'vqcheckout_wards_optimized_slug_map');
}
if (!defined('VQCHECKOUT_CACHE_DURATION')) {
    define('VQCHECKOUT_CACHE_DURATION', WEEK_IN_SECONDS * 4); // Cache 1 tháng
}

/**
 * Load JSON data helper (Loads Array of Objects)
 */
function vqcheckout_load_json($file_path) {
    if (!file_exists($file_path)) {
        // error_log("VQ Checkout Error: JSON file not found at " . $file_path);
        return false;
    }
    
    $json_data = file_get_contents($file_path);
    if (empty($json_data)) {
        return false;
    }
    
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        // error_log("VQ Checkout Error: Invalid JSON format in " . $file_path);
        return false;
    }
    
    return $data;
}

/**
 * Get all provinces (Tỉnh/Thành phố) - Optimized Structure
 */
function vqcheckout_get_provinces() {
    // Check cache for optimized structure
    $provinces_optimized = get_transient(VQCHECKOUT_PROVINCES_CACHE_KEY);
    
    if (false === $provinces_optimized) {
        if (!defined('VQCHECKOUT_DATA_DIR')) return array();
        $file_path = VQCHECKOUT_DATA_DIR . 'vietnam_provinces.json';
        $data_raw = vqcheckout_load_json($file_path);
        
        if ($data_raw) {
            $provinces_optimized = array();
            // Transform Array of Objects to Optimized Key-Value pairs
            foreach ($data_raw as $item) {
                if (isset($item['slug']) && isset($item['name_with_type'])) {
                    // Use slug as the key, convert to uppercase (e.g., 'ha-nội' -> 'HANOI')
                    $key = strtoupper(sanitize_title(str_replace('-', '', $item['slug'])));
                    $provinces_optimized[$key] = $item['name_with_type'];
                }
            }
            // Set cache
            set_transient(VQCHECKOUT_PROVINCES_CACHE_KEY, $provinces_optimized, VQCHECKOUT_CACHE_DURATION);
        } else {
            // If loading fails, return empty array
            $provinces_optimized = array();
        }
    }
    
    // Ensure return type is array even if transient somehow stored non-array
    return is_array($provinces_optimized) ? $provinces_optimized : array();
}

/**
 * Get all wards (Xã/Phường) - Optimized Structure
 */
function vqcheckout_get_all_wards() {
    // Check cache for optimized structure
    $wards_optimized = get_transient(VQCHECKOUT_WARDS_CACHE_KEY);
    
    if (false === $wards_optimized) {
        if (!defined('VQCHECKOUT_DATA_DIR')) return array();
        $file_path = VQCHECKOUT_DATA_DIR . 'vietnam_wards.json';
        $data_raw = vqcheckout_load_json($file_path);
        
        if ($data_raw) {
            $wards_optimized = array();

            // Transform Flat Array of Objects to Optimized Nested Structure
            foreach ($data_raw as $item) {
                // Identify the province code (slug) from the 'path' field.
                $province_code = null;
                if (isset($item['path'])) {
                    $parts = explode(', ', $item['path']);
                    if (count($parts) > 1) {
                        // The last part of the path is assumed to be the uppercase slug/code.
                        $province_code = trim(end($parts));
                    }
                }

                if ($province_code && isset($item['code']) && isset($item['name_with_type'])) {
                    $ward_code = $item['code'];
                    $ward_name = $item['name_with_type'];

                    if (!isset($wards_optimized[$province_code])) {
                        $wards_optimized[$province_code] = array();
                    }
                    
                    // Store minimal data needed
                    $wards_optimized[$province_code][$ward_code] = array(
                        'name' => $ward_name
                    );
                }
            }
            // Set cache
            set_transient(VQCHECKOUT_WARDS_CACHE_KEY, $wards_optimized, VQCHECKOUT_CACHE_DURATION);
        } else {
            // If loading fails, return empty array
            $wards_optimized = array();
        }
    }
    
    return is_array($wards_optimized) ? $wards_optimized : array();
}

/**
 * Get wards by province code (Uses optimized structure)
 */
function vqcheckout_get_wards_by_province($province_code) {
    if (empty($province_code)) {
        return array();
    }
    
    $all_wards = vqcheckout_get_all_wards();
    
    // Ensure the code is uppercase for consistency (as keys are uppercase slugs)
    $province_code = strtoupper($province_code);

    if (isset($all_wards[$province_code])) {
        return $all_wards[$province_code];
    }
    
    return array();
}

/**
 * Clear all caches
 */
function vqcheckout_clear_all_caches() {
    delete_transient(VQCHECKOUT_PROVINCES_CACHE_KEY);
    delete_transient(VQCHECKOUT_WARDS_CACHE_KEY);
    // Force WC to regenerate shipping cache
    if (class_exists('WC_Cache_Helper')) {
        WC_Cache_Helper::get_transient_version('shipping', true);
    }
}

// ----------------------------------------------------
// ADDRESS UTILITIES
// ----------------------------------------------------

/**
 * Get province name by code (slug)
 */
function vqcheckout_get_province_name($code) {
    $provinces = vqcheckout_get_provinces();
    $code = strtoupper($code);
    return isset($provinces[$code]) ? $provinces[$code] : $code;
}

/**
 * Get ward name by province code and ward code
 */
function vqcheckout_get_ward_name($province_code, $ward_code) {
    $wards = vqcheckout_get_wards_by_province($province_code);
    return isset($wards[$ward_code]['name']) ? $wards[$ward_code]['name'] : $ward_code;
}

/**
 * Check if a ward belongs to a province
 */
function vqcheckout_is_valid_ward($province_code, $ward_code) {
    $wards = vqcheckout_get_wards_by_province($province_code);
    return isset($wards[$ward_code]);
}

// ----------------------------------------------------
// FORMATTING UTILITIES
// ----------------------------------------------------

/**
 * Format price to text (18k, 1tr200, 1tỷ820)
 */
function vqcheckout_format_price_text($price) {
    $price = (float) $price;

    if ($price >= 1000000000) {
        // Tỷ
        $billions = floor($price / 1000000000);
        $remainder = fmod($price, 1000000000);
        $text = $billions . 'tỷ';
        if ($remainder > 0) {
            $millions = floor($remainder / 1000000);
            if ($millions > 0) {
                $text .= $millions;
            }
        }
        return $text;
    } elseif ($price >= 1000000) {
        // Triệu
        $millions = floor($price / 1000000);
        $remainder = fmod($price, 1000000);
        $text = $millions . 'tr';
        if ($remainder > 0) {
            $thousands = floor($remainder / 1000);
            if ($thousands > 0) {
                 $text .= $thousands;
            }
        }
        return $text;
    } elseif ($price >= 1000) {
        // Nghìn
        $thousands = floor($price / 1000);
        $text = $thousands . 'k';
        return $text;
    } else {
        return number_format($price, 0, ',', '.');
    }
}

/**
 * Validate Vietnam phone number format (+84xxx hoặc 0xxx)
 */
function vqcheckout_is_valid_vn_phone($phone) {
    // Remove spaces, dots, dashes
    $phone = preg_replace('/[^0-9\+]/', '', $phone);

    // Normalize format: convert +84 or 84 to 0
    if (substr($phone, 0, 3) === '+84') {
        $phone = '0' . substr($phone, 3);
    } elseif (substr($phone, 0, 2) === '84') {
         $phone = '0' . substr($phone, 2);
    }

    // Check format: Starts with 0, followed by valid VN mobile prefixes (Total 10 digits)
    if (preg_match('/^(0)(3[2-9]|5[25689]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/', $phone)) {
        return true;
    }
    return false;
}