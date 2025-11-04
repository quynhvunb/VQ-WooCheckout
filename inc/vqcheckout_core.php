<?php
/**
 * VQ Checkout Core Functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize address system and core features
 */
function vqcheckout_init_core_features() {
    // Register Vietnam provinces as WC states
    add_filter('woocommerce_states', 'vqcheckout_register_vietnam_provinces');
    
    // Handle default country based on settings
    $show_country = vqcheckout_get_option('show_country', '0');
    if ($show_country !== '1') {
        add_filter('default_checkout_billing_country', 'vqcheckout_default_country_vn');
        add_filter('default_checkout_shipping_country', 'vqcheckout_default_country_vn');
        
        // Limit allowed countries to VN only
        add_filter('woocommerce_countries_allowed_countries', 'vqcheckout_limit_allowed_countries');
    }

    // Initialize general enhancements
    vqcheckout_init_general_enhancements();
}
// Initialize early
add_action('init', 'vqcheckout_init_core_features', 5);

// ... (Keep vqcheckout_register_vietnam_provinces, vqcheckout_default_country_vn as before) ...

/**
 * Register Vietnam provinces as WooCommerce states
 */
function vqcheckout_register_vietnam_provinces($states) {
    $provinces = vqcheckout_get_provinces();
    
    if (!empty($provinces)) {
        // Ensure VN key exists and set/overwrite with our data
        $states['VN'] = $provinces;
    }
    
    return $states;
}

/**
 * Set default country to Vietnam
 */
function vqcheckout_default_country_vn($country) {
    return 'VN';
}

/**
 * Limit allowed countries to Vietnam (FIXED: Robust check)
 */
function vqcheckout_limit_allowed_countries($countries) {
    // FIX: Ensure WC()->countries is available before accessing it (prevents potential errors)
    if (function_exists('WC') && WC()->countries) {
        $base_countries = WC()->countries->get_countries();
        if (isset($base_countries['VN'])) {
            return array('VN' => $base_countries['VN']);
        }
    }
    
    // Fallback if WC is not fully loaded or VN is somehow not in the list
    return array('VN' => __('Vietnam', 'woocommerce'));
}


/**
 * Initialize general enhancements based on settings (File 07) (UPDATED)
 */
function vqcheckout_init_general_enhancements() {
    // Chuyển ₫ sang VNĐ
    if (vqcheckout_get_option('to_vnd', '0') === '1') {
        add_filter('woocommerce_currency_symbol', 'vqcheckout_change_currency_symbol_vnd', 10, 2);
    }

    // Loại bỏ tiêu đề vận chuyển
    if (vqcheckout_get_option('remove_method_title', '1') === '1') {
        add_filter('woocommerce_shipping_package_name', '__return_empty_string', 10, 3);
    }

    // Ẩn phương thức khác khi có free-shipping
    if (vqcheckout_get_option('freeship_remove_other_method', '0') === '1') {
        add_filter('woocommerce_package_rates', 'vqcheckout_hide_shipping_when_free_is_available', 100);
    }

    // Chuyển giá sang dạng chữ
    if (vqcheckout_get_option('convert_price_text', '0') === '1') {
        add_filter('woocommerce_get_price_html', 'vqcheckout_convert_price_to_text_html', 10, 2);
        // Áp dụng cho các phần tử trong giỏ hàng
        add_filter('woocommerce_cart_item_price', 'vqcheckout_convert_cart_item_price_to_text', 10, 3);
    }

    // NEW: PayPal Currency Conversion
    if (vqcheckout_get_option('paypal_conversion_enabled', '0') === '1' && get_woocommerce_currency() === 'VND') {
        // Hook into PayPal standard gateway arguments (Legacy)
        add_filter('woocommerce_paypal_args', 'vqcheckout_convert_paypal_args_to_usd', 100, 2);
        // Hook into WooCommerce PayPal Payments plugin (Modern)
        add_filter('woocommerce_paypal_payments_request_body', 'vqcheckout_convert_paypal_payments_body_to_usd', 100);
    }
}

// ... (Keep other enhancement helper functions: vqcheckout_change_currency_symbol_vnd, vqcheckout_hide_shipping_when_free_is_available, vqcheckout_convert_price_to_text_html, etc.) ...

/**
 * Change currency symbol to VNĐ
 */
function vqcheckout_change_currency_symbol_vnd($currency_symbol, $currency) {
    if ($currency == 'VND') {
        $currency_symbol = 'VNĐ';
    }
    return $currency_symbol;
}

/**
 * Hide shipping methods when free shipping is available
 */
function vqcheckout_hide_shipping_when_free_is_available($rates) {
    $free = array();
    foreach ($rates as $rate_id => $rate) {
        // Kiểm tra nếu là free_shipping method HOẶC nếu chi phí bằng 0
        if ('free_shipping' === $rate->method_id || (float)$rate->cost === 0.0) {
            $free[$rate_id] = $rate;
        }
    }
    // Nếu có phương thức miễn phí, chỉ trả về các phương thức miễn phí
    return !empty($free) ? $free : $rates;
}

/**
 * Convert price display to text format (HTML wrapper) - Product pages
 */
function vqcheckout_convert_price_to_text_html($price_html, $product) {
    
    // Handle variable products (ranges)
    if ($product->is_type('variable')) {
        $prices = $product->get_variation_prices(true);
        if (empty($prices['price'])) {
            return $price_html;
        }
        $min_price = current($prices['price']);
        $max_price = end($prices['price']);

        if ($min_price !== $max_price) {
            $text_price = vqcheckout_format_price_text($min_price) . ' – ' . vqcheckout_format_price_text($max_price);
        } else {
            $text_price = vqcheckout_format_price_text($min_price);
        }
    } else {
        // Simple products
        $price = $product->get_price();
        if (empty($price) || $price == 0) {
            return $price_html;
        }
        $text_price = vqcheckout_format_price_text($price);
    }
    
    return vqcheckout_format_price_html_helper($text_price);
}

/**
 * Convert cart item price to text format
 */
function vqcheckout_convert_cart_item_price_to_text($price_html, $cart_item, $cart_item_key) {
    $product = $cart_item['data'];
    // Use wc_get_price_to_display to handle taxes correctly
    $price = wc_get_price_to_display($product, array('price' => $cart_item['data']->get_price()));

    if (empty($price) || $price == 0) {
        return $price_html;
    }
    $text_price = vqcheckout_format_price_text($price);
    return vqcheckout_format_price_html_helper($text_price);
}

/**
 * Helper to wrap text price in HTML
 */
function vqcheckout_format_price_html_helper($text_price) {
    $currency_symbol = get_woocommerce_currency_symbol();
    return '<span class="woocommerce-Price-amount amount vqcheckout-price-text">' . $text_price . '<span class="woocommerce-Price-currencySymbol">' . esc_html($currency_symbol) . '</span></span>';
}


// ----------------------------------------------------
// NEW: PAYPAL VND TO USD CONVERSION LOGIC
// ----------------------------------------------------

/**
 * Convert standard PayPal arguments (Legacy Gateway)
 */
function vqcheckout_convert_paypal_args_to_usd($args, $order) {
    // Ensure currency code is VND before processing (though checked in init)
    if (isset($args['currency_code']) && $args['currency_code'] !== 'VND') {
        return $args;
    }

    $rate = (float) vqcheckout_get_option('paypal_exchange_rate', 25000);
    if ($rate <= 0) return $args; // Safety check

    $args['currency_code'] = 'USD';

    // Convert item prices
    $i = 1;
    while (isset($args['amount_' . $i])) {
        $args['amount_' . $i] = number_format((float)$args['amount_' . $i] / $rate, 2, '.', '');
        $i++;
    }

    // Convert shipping, discount, tax if present
    if (isset($args['shipping_1'])) {
        $args['shipping_1'] = number_format((float)$args['shipping_1'] / $rate, 2, '.', '');
    }
    if (isset($args['discount_amount_cart'])) {
        $args['discount_amount_cart'] = number_format((float)$args['discount_amount_cart'] / $rate, 2, '.', '');
    }
    if (isset($args['tax_cart'])) {
        $args['tax_cart'] = number_format((float)$args['tax_cart'] / $rate, 2, '.', '');
    }

    return $args;
}

/**
 * Convert WooCommerce PayPal Payments request body (Modern Gateway)
 */
function vqcheckout_convert_paypal_payments_body_to_usd($body) {
    if (isset($body['purchase_units']) && is_array($body['purchase_units'])) {
        $rate = (float) vqcheckout_get_option('paypal_exchange_rate', 25000);
        if ($rate <= 0) return $body;

        foreach ($body['purchase_units'] as &$unit) {
            if (isset($unit['amount']['currency_code']) && $unit['amount']['currency_code'] === 'VND') {
                $unit['amount']['currency_code'] = 'USD';
                
                // Convert total amount
                if (isset($unit['amount']['value'])) {
                    $unit['amount']['value'] = (string) round((float)$unit['amount']['value'] / $rate, 2);
                }

                // Convert breakdown amounts
                if (isset($unit['amount']['breakdown'])) {
                    $breakdown_items = ['item_total', 'shipping', 'handling', 'tax_total', 'shipping_discount', 'discount'];
                    foreach ($breakdown_items as $item_key) {
                        if (isset($unit['amount']['breakdown'][$item_key])) {
                            $unit['amount']['breakdown'][$item_key]['currency_code'] = 'USD';
                            $value = (float) $unit['amount']['breakdown'][$item_key]['value'];
                            $unit['amount']['breakdown'][$item_key]['value'] = (string) round($value / $rate, 2);
                        }
                    }
                }
            }
            
            // Convert individual item prices
            if (isset($unit['items']) && is_array($unit['items'])) {
                foreach ($unit['items'] as &$item) {
                     if (isset($item['unit_amount']['currency_code']) && $item['unit_amount']['currency_code'] === 'VND') {
                        $item['unit_amount']['currency_code'] = 'USD';
                        $value = (float) $item['unit_amount']['value'];
                        $item['unit_amount']['value'] = (string) round($value / $rate, 2);
                     }
                }
            }
        }
    }
    return $body;
}