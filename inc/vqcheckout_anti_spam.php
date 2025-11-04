<?php
/**
 * VQ Checkout - Anti-Spam Module (File 07)
 * Implements reCaptcha v3, IP blocking, Keyword blocking, and Rate Limiting.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Anti-Spam module
 */
function vqcheckout_init_anti_spam() {
    // Check if the main feature is enabled
    if (vqcheckout_get_option('anti_spam_enabled', '1') !== '1') {
        return;
    }

    // Hook into checkout validation process (Priority 5: runs early)
    add_action('woocommerce_after_checkout_validation', 'vqcheckout_run_anti_spam_checks', 5, 2);
    
    // Initialize reCaptcha frontend assets if enabled
    if (vqcheckout_is_recaptcha_configured()) {
        add_action('wp_enqueue_scripts', 'vqcheckout_enqueue_recaptcha_script');
        add_action('woocommerce_review_order_before_submit', 'vqcheckout_add_recaptcha_field');
    }
}
add_action('init', 'vqcheckout_init_anti_spam');

/**
 * Helper: Check if reCaptcha is enabled and keys are configured
 */
function vqcheckout_is_recaptcha_configured() {
    return (
        vqcheckout_get_option('recaptcha_enabled', '0') === '1' &&
        !empty(vqcheckout_get_option('recaptcha_site_key')) &&
        !empty(vqcheckout_get_option('recaptcha_secret_key'))
    );
}

/**
 * Run all anti-spam checks during checkout validation
 */
function vqcheckout_run_anti_spam_checks($data, $errors) {
    
    // 1. IP Blocking
    if (vqcheckout_check_ip_block()) {
        $errors->add('spam', __('Rất tiếc, địa chỉ IP của bạn đã bị chặn đặt hàng.', 'vq-checkout'));
        return;
    }

    // 2. Keyword Blocking
    if (vqcheckout_check_keyword_block($data)) {
        $errors->add('spam', __('Rất tiếc, thông tin đặt hàng của bạn chứa từ khóa không hợp lệ.', 'vq-checkout'));
        return;
    }

    // 3. Rate Limiting
    // Note: Rate limiting check also increments the counter.
    if (vqcheckout_check_rate_limit()) {
        $errors->add('spam', __('Bạn đã đặt hàng quá nhanh. Vui lòng thử lại sau một khoảng thời gian.', 'vq-checkout'));
        return;
    }

    // 4. reCaptcha Verification
    if (vqcheckout_is_recaptcha_configured()) {
        if (!vqcheckout_verify_recaptcha()) {
            $errors->add('spam', __('Xác thực reCAPTCHA không thành công (Bot suspected). Vui lòng thử lại.', 'vq-checkout'));
            return;
        }
    }
}

// ----------------------------------------------------
// IP Blocking Implementation
// ----------------------------------------------------

function vqcheckout_check_ip_block() {
    $blocked_ips_raw = vqcheckout_get_option('blocked_ips', '');
    if (empty($blocked_ips_raw)) {
        return false;
    }

    $blocked_ips = array_map('trim', explode("\n", $blocked_ips_raw));
    $client_ip = WC_Geolocation::get_ip_address();

    return in_array($client_ip, $blocked_ips);
}

// ----------------------------------------------------
// Keyword Blocking Implementation
// ----------------------------------------------------

function vqcheckout_check_keyword_block($data) {
    $blocked_keywords_raw = vqcheckout_get_option('blocked_keywords', '');
    if (empty($blocked_keywords_raw)) {
        return false;
    }

    $blocked_keywords = array_map('trim', explode("\n", $blocked_keywords_raw));
    $blocked_keywords = array_filter($blocked_keywords); // Remove empty lines
    
    // Combine relevant fields into a single string for checking
    $content_to_check = implode(' ', array_filter(array(
        $data['billing_first_name'] ?? null,
        $data['billing_last_name'] ?? null,
        $data['billing_email'] ?? null,
        $data['billing_phone'] ?? null,
        $data['billing_address_1'] ?? null,
        $data['order_comments'] ?? null,
        $data['shipping_first_name'] ?? null,
        $data['shipping_last_name'] ?? null,
        $data['shipping_address_1'] ?? null
    )));
    
    // Case-insensitive check (using mb_strtolower for Unicode support)
    $content_to_check = mb_strtolower($content_to_check, 'UTF-8');

    foreach ($blocked_keywords as $keyword) {
        $keyword = mb_strtolower($keyword, 'UTF-8');
        if (!empty($keyword) && strpos($content_to_check, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

// ----------------------------------------------------
// Rate Limiting Implementation (Fixed Window)
// ----------------------------------------------------

function vqcheckout_check_rate_limit() {
    $max_orders = (int) vqcheckout_get_option('max_orders_per_hour', 5);
    
    // Disabled if set to 0
    if ($max_orders <= 0) {
        return false;
    }

    $client_ip = WC_Geolocation::get_ip_address();
    if (empty($client_ip)) {
        return false;
    }

    // Use transients for efficient tracking
    $transient_key = 'vqcheckout_rl_' . md5($client_ip);
    $order_count = get_transient($transient_key);

    if ($order_count === false) {
        // First order in the window
        set_transient($transient_key, 1, HOUR_IN_SECONDS);
        return false;
    }

    $order_count = (int) $order_count;

    if ($order_count >= $max_orders) {
        // Rate limit exceeded
        return true;
    }

    // Increment count while maintaining the original expiration window (Fixed Window approach)
    // We fetch the timeout value directly from the options table to get remaining time.
    $timeout = get_option('_transient_timeout_' . $transient_key);
    $remaining_time = $timeout ? $timeout - time() : HOUR_IN_SECONDS;

    if ($remaining_time > 0) {
        set_transient($transient_key, $order_count + 1, $remaining_time);
    } else {
        // Fallback if timeout retrieval fails or time expired during processing
        set_transient($transient_key, 1, HOUR_IN_SECONDS);
    }
    
    return false;
}

// ----------------------------------------------------
// reCaptcha Implementation (v3)
// ----------------------------------------------------

/**
 * Enqueue reCaptcha v3 script on checkout page
 */
function vqcheckout_enqueue_recaptcha_script() {
    if (!is_checkout()) {
        return;
    }
    
    $site_key = vqcheckout_get_option('recaptcha_site_key');
    $api_url = 'https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key);

    wp_enqueue_script('google-recaptcha-v3', $api_url, array('jquery'), null, true);
    
    // Inline script to handle token generation and refresh
    $inline_script = "
        jQuery(function($) {
            if (typeof grecaptcha === 'undefined') return;
            
            // Function to update the token
            function updateRecaptchaToken() {
                grecaptcha.ready(function() {
                    grecaptcha.execute('" . esc_js($site_key) . "', {action: 'checkout'}).then(function(token) {
                        $('#vqcheckout_recaptcha_token').val(token);
                    });
                });
            }
            
            // Update token on load
            updateRecaptchaToken();
            
            // Update token periodically (e.g., every 90 seconds, as tokens expire in 2 mins)
            setInterval(updateRecaptchaToken, 90000);

            // Also update token if checkout validation fails (AJAX update)
            $(document.body).on('checkout_error', updateRecaptchaToken);
        });
    ";
    wp_add_inline_script('google-recaptcha-v3', $inline_script);
}

/**
 * Add hidden field for reCaptcha token
 */
function vqcheckout_add_recaptcha_field() {
    echo '<input type="hidden" name="vqcheckout_recaptcha_token" id="vqcheckout_recaptcha_token">';
}

/**
 * Verify reCaptcha token
 */
function vqcheckout_verify_recaptcha() {
    $token = isset($_POST['vqcheckout_recaptcha_token']) ? sanitize_text_field($_POST['vqcheckout_recaptcha_token']) : '';
    $secret_key = vqcheckout_get_option('recaptcha_secret_key');

    if (empty($token)) {
        return false;
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret' => $secret_key,
            'response' => $token,
            'remoteip' => WC_Geolocation::get_ip_address(),
        ),
    ));

    if (is_wp_error($response)) {
        // Log error. Decide whether to fail open (true) or fail closed (false) on network error.
        // Failing closed is generally safer.
        error_log('VQ Checkout reCAPTCHA verification network error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body);

    // Check for success, correct action, and score threshold (0.5 default)
    if ($result && $result->success && $result->action === 'checkout') {
        $threshold = apply_filters('vqcheckout_recaptcha_v3_threshold', 0.5);
        if ($result->score >= $threshold) {
            return true;
        }
    }

    return false;
}