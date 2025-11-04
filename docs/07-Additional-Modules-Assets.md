# FILE 07: ADDITIONAL MODULES & ASSETS

## MODULES BỔ SUNG & FRONTEND ASSETS

---

## MỤC LỤC

1. [Giới thiệu](#i-giới-thiệu)
2. [Admin Order Display](#ii-admin-order-display)
3. [Auto-fill Module](#iii-auto-fill-module)
4. [Anti-spam Module](#iv-anti-spam-module)
5. [Plugin Settings Page](#v-plugin-settings-page)
6. [Complete CSS Files](#vi-complete-css-files)
7. [Complete JavaScript Files](#vii-complete-javascript-files)
8. [Asset Loading Strategy](#viii-asset-loading-strategy)

---

## I. GIỚI THIỆU

### 1.1. Mục tiêu

File này bao gồm các modules bổ sung và tất cả frontend assets:
- ✅ Admin order display (hiển thị địa chỉ đẹp hơn)
- ✅ Auto-fill (tự động điền thông tin khách cũ)
- ✅ Anti-spam (chặn spam orders)
- ✅ Plugin settings page (như ảnh `5__settingpage.jpg`)
- ✅ Complete CSS files
- ✅ Complete JavaScript files

---

## II. ADMIN ORDER DISPLAY

### 2.1. File: inc/vqcheckout_admin_order.php

```php
<?php
/**
 * VQ Checkout - Admin Order Display
 * Improve address display in admin order page
 * 
 * @package VQ_Checkout_For_Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize admin order display
 */
function vqcheckout_init_admin_order_display() {
    // Custom admin order columns
    add_filter('manage_edit-shop_order_columns', 'vqcheckout_add_order_column');
    add_action('manage_shop_order_posts_custom_column', 'vqcheckout_order_column_content', 10, 2);
    
    // Format address in order details
    add_filter('woocommerce_order_formatted_billing_address', 'vqcheckout_format_order_billing_address', 10, 2);
    add_filter('woocommerce_order_formatted_shipping_address', 'vqcheckout_format_order_shipping_address', 10, 2);
    
    // Add custom meta box
    add_action('add_meta_boxes', 'vqcheckout_add_order_meta_box');
}
add_action('init', 'vqcheckout_init_admin_order_display');

/**
 * Add custom column to orders list
 */
function vqcheckout_add_order_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        
        // Add address column after billing column
        if ($key === 'billing_address') {
            $new_columns['shipping_address_vq'] = __('Địa chỉ giao hàng', 'vq-checkout');
        }
    }
    
    return $new_columns;
}

/**
 * Display column content
 */
function vqcheckout_order_column_content($column, $post_id) {
    if ($column === 'shipping_address_vq') {
        $order = wc_get_order($post_id);
        
        if ($order) {
            $province_name = vqcheckout_get_province_name($order->get_shipping_state());
            $ward_name = vqcheckout_get_ward_name($order->get_shipping_state(), $order->get_shipping_city());
            
            if ($province_name && $ward_name) {
                echo '<strong>' . esc_html($ward_name) . '</strong><br>';
                echo esc_html($province_name);
            }
        }
    }
}

/**
 * Format billing address for order details
 */
function vqcheckout_format_order_billing_address($address, $order) {
    $province_code = $order->get_billing_state();
    $ward_code = $order->get_billing_city();
    
    if ($province_code && $ward_code) {
        $address['state'] = vqcheckout_get_province_name($province_code);
        $address['city'] = vqcheckout_get_ward_name($province_code, $ward_code);
    }
    
    return $address;
}

/**
 * Format shipping address for order details
 */
function vqcheckout_format_order_shipping_address($address, $order) {
    $province_code = $order->get_shipping_state();
    $ward_code = $order->get_shipping_city();
    
    if ($province_code && $ward_code) {
        $address['state'] = vqcheckout_get_province_name($province_code);
        $address['city'] = vqcheckout_get_ward_name($province_code, $ward_code);
    }
    
    return $address;
}

/**
 * Add custom meta box to order edit page
 */
function vqcheckout_add_order_meta_box() {
    add_meta_box(
        'vqcheckout_order_address',
        __('Địa chỉ chi tiết', 'vq-checkout'),
        'vqcheckout_order_address_meta_box',
        'shop_order',
        'side',
        'default'
    );
}

/**
 * Render order address meta box
 */
function vqcheckout_order_address_meta_box($post) {
    $order = wc_get_order($post->ID);
    
    if (!$order) {
        return;
    }
    
    ?>
    <div class="vqcheckout-order-address">
        <h4><?php _e('Địa chỉ thanh toán', 'vq-checkout'); ?></h4>
        <p>
            <strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></strong><br>
            <?php echo esc_html($order->get_billing_phone()); ?><br>
            <?php echo esc_html($order->get_billing_email()); ?>
        </p>
        <p>
            <?php echo esc_html($order->get_billing_address_1()); ?><br>
            <?php
            $billing_ward = vqcheckout_get_ward_name($order->get_billing_state(), $order->get_billing_city());
            $billing_province = vqcheckout_get_province_name($order->get_billing_state());
            echo esc_html($billing_ward . ', ' . $billing_province);
            ?>
        </p>
        
        <h4><?php _e('Địa chỉ giao hàng', 'vq-checkout'); ?></h4>
        <p>
            <strong><?php echo esc_html($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name()); ?></strong>
        </p>
        <p>
            <?php echo esc_html($order->get_shipping_address_1()); ?><br>
            <?php
            $shipping_ward = vqcheckout_get_ward_name($order->get_shipping_state(), $order->get_shipping_city());
            $shipping_province = vqcheckout_get_province_name($order->get_shipping_state());
            echo esc_html($shipping_ward . ', ' . $shipping_province);
            ?>
        </p>
    </div>
    
    <style>
        .vqcheckout-order-address h4 {
            margin-top: 15px;
            margin-bottom: 10px;
            color: #2271b1;
        }
        .vqcheckout-order-address p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
    </style>
    <?php
}
```

---

## III. AUTO-FILL MODULE

### 3.1. File: inc/vqcheckout_autofill.php

```php
<?php
/**
 * VQ Checkout - Auto-fill Module
 * Auto-fill customer information based on previous orders
 * 
 * @package VQ_Checkout_For_Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize auto-fill module
 */
function vqcheckout_init_autofill() {
    // Check if auto-fill is enabled
    if (!vqcheckout_get_option('autofill_enabled', true)) {
        return;
    }
    
    // Pre-fill checkout fields for returning customers
    add_filter('woocommerce_checkout_get_value', 'vqcheckout_autofill_checkout_fields', 10, 2);
    
    // Enqueue autofill script
    add_action('wp_enqueue_scripts', 'vqcheckout_enqueue_autofill_script');
}
add_action('init', 'vqcheckout_init_autofill');

/**
 * Auto-fill checkout fields for logged-in users
 */
function vqcheckout_autofill_checkout_fields($value, $input) {
    // Only for logged-in users
    if (!is_user_logged_in()) {
        return $value;
    }
    
    // Don't override if value already exists
    if ($value) {
        return $value;
    }
    
    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);
    
    // Map fields
    $autofill_map = array(
        'billing_state' => $customer->get_billing_state(),
        'billing_city' => $customer->get_billing_city(),
        'billing_address_1' => $customer->get_billing_address_1(),
        'billing_postcode' => $customer->get_billing_postcode(),
        'shipping_state' => $customer->get_shipping_state(),
        'shipping_city' => $customer->get_shipping_city(),
        'shipping_address_1' => $customer->get_shipping_address_1(),
        'shipping_postcode' => $customer->get_shipping_postcode()
    );
    
    if (isset($autofill_map[$input])) {
        return $autofill_map[$input];
    }
    
    return $value;
}

/**
 * Enqueue autofill script
 */
function vqcheckout_enqueue_autofill_script() {
    if (!is_checkout()) {
        return;
    }
    
    wp_add_inline_script('vqcheckout-checkout', '
        jQuery(document).ready(function($) {
            // Store form data on successful order
            $(document.body).on("checkout_place_order_success", function(e, result) {
                if (result.result === "success") {
                    // Data will be auto-saved by WooCommerce
                    console.log("VQ Checkout: Order data saved for autofill");
                }
            });
        });
    ');
}
```

---

## IV. ANTI-SPAM MODULE

### 4.1. File: inc/vqcheckout_anti_spam.php

```php
<?php
/**
 * VQ Checkout - Anti-spam Module
 * Prevent spam orders
 * 
 * @package VQ_Checkout_For_Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize anti-spam module
 */
function vqcheckout_init_anti_spam() {
    // Check if anti-spam is enabled
    if (!vqcheckout_get_option('anti_spam_enabled', true)) {
        return;
    }
    
    // Validate checkout before processing
    add_action('woocommerce_after_checkout_validation', 'vqcheckout_anti_spam_validation', 10, 2);
    
    // Add reCAPTCHA if enabled
    if (vqcheckout_get_option('recaptcha_enabled', false)) {
        add_action('woocommerce_review_order_before_submit', 'vqcheckout_add_recaptcha');
        add_action('woocommerce_after_checkout_validation', 'vqcheckout_verify_recaptcha', 10, 2);
    }
}
add_action('init', 'vqcheckout_init_anti_spam');

/**
 * Anti-spam validation
 */
function vqcheckout_anti_spam_validation($data, $errors) {
    // Check blocked IPs
    $blocked_ips = vqcheckout_get_option('blocked_ips', array());
    $user_ip = vqcheckout_get_user_ip();
    
    if (in_array($user_ip, $blocked_ips)) {
        $errors->add('spam', __('Đơn hàng của bạn không thể được xử lý.', 'vq-checkout'));
        vqcheckout_log('Blocked order from IP: ' . $user_ip, 'warning');
        return;
    }
    
    // Check blocked keywords
    $blocked_keywords = vqcheckout_get_option('blocked_keywords', array());
    
    if (!empty($blocked_keywords)) {
        $check_fields = array(
            $data['billing_first_name'],
            $data['billing_last_name'],
            $data['billing_address_1'],
            $data['billing_email'],
            $data['billing_phone']
        );
        
        foreach ($check_fields as $field_value) {
            foreach ($blocked_keywords as $keyword) {
                if (stripos($field_value, $keyword) !== false) {
                    $errors->add('spam', __('Đơn hàng chứa thông tin không hợp lệ.', 'vq-checkout'));
                    vqcheckout_log('Blocked order with keyword: ' . $keyword, 'warning');
                    return;
                }
            }
        }
    }
    
    // Check rate limiting (max orders per IP per hour)
    $max_orders_per_hour = vqcheckout_get_option('max_orders_per_hour', 5);
    
    if ($max_orders_per_hour > 0) {
        $recent_orders = vqcheckout_count_recent_orders_by_ip($user_ip);
        
        if ($recent_orders >= $max_orders_per_hour) {
            $errors->add('spam', __('Bạn đã đặt quá nhiều đơn hàng. Vui lòng thử lại sau.', 'vq-checkout'));
            vqcheckout_log('Rate limit exceeded for IP: ' . $user_ip, 'warning');
            return;
        }
    }
}

/**
 * Count recent orders by IP
 */
function vqcheckout_count_recent_orders_by_ip($ip) {
    global $wpdb;
    
    $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_customer_ip_address'
        AND pm.meta_value = %s
        AND p.post_type = 'shop_order'
        AND p.post_date > %s
    ", $ip, $one_hour_ago));
    
    return (int) $count;
}

/**
 * Add reCAPTCHA to checkout
 */
function vqcheckout_add_recaptcha() {
    $site_key = vqcheckout_get_option('recaptcha_site_key', '');
    
    if (empty($site_key)) {
        return;
    }
    
    $recaptcha_version = vqcheckout_get_option('recaptcha_version', 'v3');
    
    if ($recaptcha_version === 'v3') {
        // reCAPTCHA v3
        wp_enqueue_script('google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, array(), null, true);
        
        ?>
        <input type="hidden" name="vqcheckout_recaptcha_token" id="vqcheckout_recaptcha_token" />
        <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo esc_js($site_key); ?>', {action: 'checkout'}).then(function(token) {
                document.getElementById('vqcheckout_recaptcha_token').value = token;
            });
        });
        </script>
        <?php
    } else {
        // reCAPTCHA v2
        wp_enqueue_script('google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), null, true);
        
        ?>
        <div class="form-row form-row-wide vqcheckout-recaptcha-row">
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
        </div>
        <?php
    }
}

/**
 * Verify reCAPTCHA
 */
function vqcheckout_verify_recaptcha($data, $errors) {
    $secret_key = vqcheckout_get_option('recaptcha_secret_key', '');
    
    if (empty($secret_key)) {
        return;
    }
    
    $recaptcha_version = vqcheckout_get_option('recaptcha_version', 'v3');
    
    if ($recaptcha_version === 'v3') {
        $token = isset($_POST['vqcheckout_recaptcha_token']) ? sanitize_text_field($_POST['vqcheckout_recaptcha_token']) : '';
    } else {
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
    }
    
    if (empty($token)) {
        $errors->add('recaptcha', __('Vui lòng xác thực reCAPTCHA.', 'vq-checkout'));
        return;
    }
    
    // Verify token with Google
    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret' => $secret_key,
            'response' => $token,
            'remoteip' => vqcheckout_get_user_ip()
        )
    ));
    
    if (is_wp_error($response)) {
        vqcheckout_log('reCAPTCHA verification failed: ' . $response->get_error_message(), 'error');
        return;
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (empty($body['success'])) {
        $errors->add('recaptcha', __('Xác thực reCAPTCHA thất bại. Vui lòng thử lại.', 'vq-checkout'));
        vqcheckout_log('reCAPTCHA verification failed: ' . json_encode($body), 'warning');
    }
}
```

---

## V. PLUGIN SETTINGS PAGE (Quan trọng)

Triển khai trang cài đặt dựa trên yêu cầu chi tiết.

### 5.1. File: inc/vqcheckout_settings.php (Mới)

```php
<?php
/**
 * VQ Checkout - Plugin Settings Page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize settings page
 */
function vqcheckout_init_settings_page() {
    // Chỉ chạy trong admin
    if (is_admin()) {
        add_action('admin_menu', 'vqcheckout_add_admin_menu');
        add_action('admin_init', 'vqcheckout_register_settings');
    }
}
add_action('init', 'vqcheckout_init_settings_page');

/**
 * Add admin menu
 */
function vqcheckout_add_admin_menu() {
    // Thêm menu dưới WooCommerce
    add_submenu_page(
        'woocommerce',
        __('VQ Checkout Settings', 'vq-checkout'),
        __('VQ Checkout', 'vq-checkout'),
        'manage_woocommerce',
        'vq-checkout-settings',
        'vqcheckout_settings_page_html'
    );
}

/**
 * Register settings
 */
function vqcheckout_register_settings() {
    // Đăng ký setting group và hàm sanitize
    register_setting('vqcheckout_settings_group', 'vqcheckout_settings', 'vqcheckout_sanitize_settings');
}

/**
 * Sanitize settings input
 */
function vqcheckout_sanitize_settings($input) {
    $sanitized_input = array();
    $default_settings = vqcheckout_get_default_settings();

    foreach ($default_settings as $key => $default_value) {
        if (isset($input[$key])) {
            // Sanitize cơ bản
            if (is_array($input[$key])) {
                $sanitized_input[$key] = array_map('sanitize_text_field', $input[$key]);
            } else {
                // Dùng sanitize_textarea_field cho các trường nhiều dòng
                if (in_array($key, ['blocked_ips', 'blocked_keywords'])) {
                    $sanitized_input[$key] = sanitize_textarea_field($input[$key]);
                } else {
                    $sanitized_input[$key] = sanitize_text_field($input[$key]);
                }
            }
        } else {
            // Xử lý các checkbox không được check (gán giá trị '0')
            if ($default_value === '1' || $default_value === '0') {
                 $sanitized_input[$key] = '0';
            }
        }
    }
    
    return $sanitized_input;
}

/**
 * Settings page HTML
 */
function vqcheckout_settings_page_html() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    // Get current settings
    $settings = get_option('vqcheckout_settings', vqcheckout_get_default_settings());

    ?>
    <div class="wrap vqcheckout-settings-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors(); ?>

        <form action="options.php" method="post">
            <?php
            settings_fields('vqcheckout_settings_group');
            
            // Render các phần cài đặt
            vqcheckout_render_checkout_fields_settings($settings);
            vqcheckout_render_general_settings($settings);
            vqcheckout_render_anti_spam_settings($settings);
            
            submit_button(__('Lưu thay đổi', 'vq-checkout'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Render Checkout Fields Settings
 */
function vqcheckout_render_checkout_fields_settings($settings) {
    ?>
    <h2><?php _e('Cài đặt trường Checkout', 'vq-checkout'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="phone_vn_validation"><?php _e('Định dạng SĐT ở VN', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[phone_vn_validation]" id="phone_vn_validation" value="1" <?php checked($settings['phone_vn_validation'], '1'); ?>>
                        <?php _e('Bắt buộc SĐT có định dạng ở VN (+84xxx hoặc 0xxx)', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="enable_gender"><?php _e('Xưng hô', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[enable_gender]" id="enable_gender" value="1" <?php checked($settings['enable_gender'], '1'); ?>>
                        <?php _e('Hiển thị mục chọn cách xưng hô Anh/Chị', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="not_required_email"><?php _e('Email tùy chọn', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[not_required_email]" id="not_required_email" value="1" <?php checked($settings['not_required_email'], '1'); ?>>
                        <?php _e('Trường email sẽ KHÔNG bắt buộc phải nhập', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="show_last_name"><?php _e('Hiển thị trường Họ (Last Name)', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[show_last_name]" id="show_last_name" value="1" <?php checked($settings['show_last_name'], '1'); ?>>
                        <?php _e('Hiển thị trường Họ. Nếu tắt, trường Tên sẽ đổi thành Họ và Tên.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="show_postcode"><?php _e('Hiển thị trường Postcode', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[show_postcode]" id="show_postcode" value="1" <?php checked($settings['show_postcode'], '1'); ?>>
                        <?php _e('Hiện trường mã bưu điện (Postcode) cho Việt Nam.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="show_country"><?php _e('Hiển thị trường Quốc gia', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[show_country]" id="show_country" value="1" <?php checked($settings['show_country'], '1'); ?>>
                        <?php _e('Hiển thị trường chọn Quốc gia. Nếu tắt, mặc định là Việt Nam.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php _e('Hình thức tải địa chỉ', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="vqcheckout_settings[load_address_method]" value="json" <?php checked($settings['load_address_method'], 'json'); ?>>
                        <?php _e('Tải bằng file json (Tốc độ nhanh, khuyến khích).', 'vq-checkout'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="vqcheckout_settings[load_address_method]" value="ajax" <?php checked($settings['load_address_method'], 'ajax'); ?>>
                        <?php _e('Tải bằng admin-ajax.php (Tốc độ chậm hơn).', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <?php
}

/**
 * Render General Settings
 */
function vqcheckout_render_general_settings($settings) {
    ?>
    <h2><?php _e('Cài đặt chung & Vận chuyển', 'vq-checkout'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="convert_price_text"><?php _e('Chuyển giá sang dạng chữ', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[convert_price_text]" id="convert_price_text" value="1" <?php checked($settings['convert_price_text'], '1'); ?>>
                        <?php _e('Cho phép chuyển giá sang dạng chữ (VD: 18k, 1tr200, 1tỷ820)', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="to_vnd"><?php _e('Chuyển ₫ sang VNĐ', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[to_vnd]" id="to_vnd" value="1" <?php checked($settings['to_vnd'], '1'); ?>>
                        <?php _e('Cho phép chuyển ký hiệu ₫ sang VNĐ', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="remove_method_title"><?php _e('Loại bỏ tiêu đề vận chuyển', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[remove_method_title]" id="remove_method_title" value="1" <?php checked($settings['remove_method_title'], '1'); ?>>
                        <?php _e('Loại bỏ tiêu đề "Vận chuyển" phía trên các phương thức.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="freeship_remove_other_method"><?php _e('Ẩn khi có Free Shipping', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[freeship_remove_other_method]" id="freeship_remove_other_method" value="1" <?php checked($settings['freeship_remove_other_method'], '1'); ?>>
                        <?php _e('Ẩn các phương thức vận chuyển khác khi có phương thức miễn phí.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="autofill_enabled"><?php _e('Kích hoạt Auto-fill', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[autofill_enabled]" id="autofill_enabled" value="1" <?php checked($settings['autofill_enabled'], '1'); ?>>
                        <?php _e('Tự động điền thông tin khách hàng cũ.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>
        </tbody>
    </table>
    <hr>
    <?php
}

/**
 * Render Anti-Spam Settings
 */
function vqcheckout_render_anti_spam_settings($settings) {
    ?>
    <h2><?php _e('Chống SPAM đơn hàng', 'vq-checkout'); ?></h2>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><label for="anti_spam_enabled"><?php _e('Kích hoạt', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[anti_spam_enabled]" id="anti_spam_enabled" value="1" <?php checked($settings['anti_spam_enabled'], '1'); ?>>
                        <?php _e('Bật các tính năng chống SPAM.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="recaptcha_enabled"><?php _e('Google reCAPTCHA v3', 'vq-checkout'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="vqcheckout_settings[recaptcha_enabled]" id="recaptcha_enabled" value="1" <?php checked($settings['recaptcha_enabled'], '1'); ?>>
                        <?php _e('Bật Google reCAPTCHA v3 tại trang Checkout.', 'vq-checkout'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="blocked_ips"><?php _e('Chặn IP', 'vq-checkout'); ?></label></th>
                <td>
                    <textarea name="vqcheckout_settings[blocked_ips]" id="blocked_ips" rows="5" cols="50"><?php echo esc_textarea($settings['blocked_ips']); ?></textarea>
                    <p class="description"><?php _e('Mỗi địa chỉ IP một dòng.', 'vq-checkout'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="blocked_keywords"><?php _e('Chặn từ khóa', 'vq-checkout'); ?></label></th>
                <td>
                    <textarea name="vqcheckout_settings[blocked_keywords]" id="blocked_keywords" rows="5" cols="50"><?php echo esc_textarea($settings['blocked_keywords']); ?></textarea>
                    <p class="description"><?php _e('Mỗi từ khóa một dòng. Áp dụng cho Tên, Địa chỉ, Email, SĐT.', 'vq-checkout'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="max_orders_per_hour"><?php _e('Giới hạn đơn hàng/giờ/IP', 'vq-checkout'); ?></label></th>
                <td>
                    <input type="number" name="vqcheckout_settings[max_orders_per_hour]" id="max_orders_per_hour" value="<?php echo esc_attr($settings['max_orders_per_hour']); ?>" class="small-text" min="0">
                    <p class="description"><?php _e('Số đơn hàng tối đa một IP có thể đặt trong 1 giờ. Để 0 để tắt.', 'vq-checkout'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
```

---

## VI. COMPLETE CSS FILES

### 6.1. assets/css/vqcheckout_admin.css

```css
/**
 * VQ Checkout - Admin Global Styles
 */

/* General admin styles */
.vqcheckout-settings-wrap {
    max-width: 1200px;
}

.vqcheckout-settings-wrap .form-table th {
    width: 250px;
    font-weight: 600;
}

/* Admin notices */
.vqcheckout-notice {
    padding: 12px 15px;
    border-left-width: 4px;
    border-left-style: solid;
    margin: 15px 0;
}

.vqcheckout-notice.notice-success {
    border-left-color: #00a32a;
    background: #f0f9f4;
}

.vqcheckout-notice.notice-error {
    border-left-color: #d63638;
    background: #fcf0f1;
}

/* Loading spinner */
.vqcheckout-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #2271b1;
    border-radius: 50%;
    animation: vqcheckout-spin 1s linear infinite;
}

@keyframes vqcheckout-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Order meta box */
.vqcheckout-order-address {
    font-size: 13px;
    line-height: 1.6;
}

.vqcheckout-order-address h4 {
    margin-top: 15px;
    margin-bottom: 10px;
    color: #2271b1;
    font-size: 14px;
}

.vqcheckout-order-address strong {
    color: #1d2327;
}

/* Responsive */
@media (max-width: 782px) {
    .vqcheckout-settings-wrap .form-table th,
    .vqcheckout-settings-wrap .form-table td {
        display: block;
        width: 100%;
    }
    
    .vqcheckout-settings-wrap .form-table th {
        padding-bottom: 5px;
    }
}
```

### 6.2. assets/css/vqcheckout_frontend.css

**Already created in File 04** - checkout page styles

---

## VII. COMPLETE JAVASCRIPT FILES

### 7.1. assets/js/vqcheckout_admin.js

```javascript
/**
 * VQ Checkout - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Confirm delete actions
    $('.vqcheckout-delete-zone, .vqcheckout-delete-item').on('click', function(e) {
        if (!confirm(vqcheckoutAdmin.strings.confirm_delete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Show loading on form submit
    $('form.vqcheckout-form').on('submit', function() {
        var $submit = $(this).find('input[type="submit"], button[type="submit"]');
        $submit.prop('disabled', true);
        $submit.append(' <span class="vqcheckout-spinner"></span>');
    });
    
    // Auto-dismiss notices after 5 seconds
    setTimeout(function() {
        $('.notice.is-dismissible').fadeOut();
    }, 5000);
});
```

### 7.2. assets/js/vqcheckout_frontend.js

**Already created in File 01** - AJAX ward loading

### 7.3. assets/js/vqcheckout_checkout.js

**Already created in File 04** - Checkout page logic

### 7.4. assets/js/vqcheckout_zone_manager.js

**Already created in File 05** - Zone manager logic

---

## VIII. ASSET LOADING STRATEGY

### 8.1. Conditional Loading

**File:** Already implemented in `VQ-woo-checkout.php` (File 02)

```php
/**
 * Enqueue admin scripts only on relevant pages
 */
function vqcheckout_admin_scripts($hook) {
    // Global admin CSS
    wp_enqueue_style('vqcheckout-admin', VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_admin.css');
    
    // Only on VQ Checkout pages
    if (strpos($hook, 'vqcheckout') !== false) {
        wp_enqueue_script('vqcheckout-admin', ...);
    }
    
    // Zone Manager page
    if ($hook === 'woocommerce_page_vqcheckout-zones') {
        wp_enqueue_style('vqcheckout-zone-manager', ...);
        wp_enqueue_script('vqcheckout-zone-manager', ...);
    }
}

/**
 * Enqueue frontend scripts only on checkout
 */
function vqcheckout_frontend_scripts() {
    if (!is_checkout()) {
        return;
    }
    
    wp_enqueue_style('vqcheckout-checkout', ...);
    wp_enqueue_script('vqcheckout-checkout', ...);
}
```

### 8.2. Minification (Production)

```bash
# CSS
npx csso assets/css/vqcheckout_frontend.css -o assets/css/vqcheckout_frontend.min.css

# JavaScript
npx terser assets/js/vqcheckout_checkout.js -o assets/js/vqcheckout_checkout.min.js
```

### 8.3. Version Control

```php
define('VQCHECKOUT_VERSION', '2.0.0');

wp_enqueue_script('vqcheckout-checkout', ..., VQCHECKOUT_VERSION, true);
```

---

## IX. FINAL CHECKLIST

### 9.1. File Structure

```
vq-checkout-for-woo/
├── VQ-woo-checkout.php ✅
├── inc/
│   ├── vqcheckout_core.php ✅
│   ├── vqcheckout_utils.php ✅
│   ├── vqcheckout_ajax.php ✅
│   ├── vqcheckout_store_settings.php ✅
│   ├── vqcheckout_checkout_fields.php ✅
│   ├── vqcheckout_shipping_zones.php ✅
│   ├── vqcheckout_shipping.php ✅
│   ├── vqcheckout_admin_order.php ✅
│   ├── vqcheckout_autofill.php ✅
│   ├── vqcheckout_anti_spam.php ✅
│   └── vqcheckout_settings.php ✅
├── assets/
│   ├── css/
│   │   ├── vqcheckout_admin.css ✅
│   │   ├── vqcheckout_frontend.css ✅ (File 04)
│   │   ├── vqcheckout_checkout.css ✅ (File 04)
│   │   ├── vqcheckout_zone_manager.css ✅ (File 05)
│   │   └── vqcheckout_store_settings.css ✅ (File 03)
│   └── js/
│       ├── vqcheckout_admin.js ✅
│       ├── vqcheckout_frontend.js ✅ (File 01)
│       ├── vqcheckout_checkout.js ✅ (File 04)
│       ├── vqcheckout_zone_manager.js ✅ (File 05)
│       └── vqcheckout_store_settings.js ✅ (File 03)
└── data/
    ├── vietnam_provinces.json ✅
    └── vietnam_wards.json ✅
```

### 9.2. Testing Checklist

#### Admin Features
- [ ] Settings page loads
- [ ] Auto-fill settings save
- [ ] Anti-spam settings save
- [ ] reCAPTCHA integration works
- [ ] Clear cache tool works
- [ ] Admin order display shows addresses correctly
- [ ] Order meta box displays

#### Frontend Features
- [ ] Auto-fill works for returning customers
- [ ] Anti-spam blocks spam orders
- [ ] Rate limiting works
- [ ] reCAPTCHA shows on checkout
- [ ] reCAPTCHA validation works

#### Performance
- [ ] Assets load conditionally
- [ ] No conflicts with other plugins
- [ ] Page load time acceptable

---

## X. DEPLOYMENT

### 10.1. Package Plugin

```bash
# Create release package
cd /path/to/plugin
zip -r vq-checkout-for-woo-v2.0.0.zip vq-checkout-for-woo/ \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*.DS_Store"
```

### 10.2. Installation

1. Upload `vq-checkout-for-woo` folder to `/wp-content/plugins/`
2. Activate plugin
3. Go to WooCommerce > VQ Shipping Zones
4. Create zones
5. Go to WooCommerce > Settings > General
6. Configure store address
7. Test checkout

---

## XI. DOCUMENTATION

### 11.1. User Guide

**Topics to cover:**
- Installation
- Configure store address
- Create shipping zones
- Zone priority explained
- Fee calculation methods
- Free shipping rules
- Ward-specific configs
- Settings page options
- Troubleshooting

### 11.2. Developer Guide

**Topics to cover:**
- Hooks & filters list
- Extending zones
- Custom fee methods
- AJAX endpoints
- Database schema
- Performance tips

---

## XII. SUMMARY

**File 07 đã bao gồm:**
✅ Admin order display module (hiển thị địa chỉ đẹp, meta box)
✅ Auto-fill module (tự động điền cho khách cũ)
✅ Anti-spam module (chặn IP, keywords, rate limit, reCAPTCHA)
✅ Settings page (đầy đủ như ảnh `5__settingpage.jpg`)
✅ Complete admin CSS
✅ Complete JavaScript files
✅ Asset loading strategy
✅ Final checklist
✅ Deployment guide

---

**HOÀN THÀNH TẤT CẢ 8 FILES KẾ HOẠCH!** 🎉

---

**Updated:** 2025-01-20
