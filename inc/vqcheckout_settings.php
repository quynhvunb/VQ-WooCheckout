<?php
/**
 * VQ Checkout - Plugin Settings Page (File 07) - COMPLETE AND FIXED
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize settings page hooks
 */
if (is_admin()) {
    add_action('admin_menu', 'vqcheckout_add_admin_menu');
    add_action('admin_init', 'vqcheckout_register_settings');
}

/**
 * Add admin menu under WooCommerce
 */
function vqcheckout_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('VQ Checkout Settings', 'vq-checkout'),
        __('VQ Checkout', 'vq-checkout'),
        'manage_woocommerce',
        'vq-checkout-settings',
        'vqcheckout_settings_page_html' // <- This function must exist!
    );
}

/**
 * Register settings
 */
function vqcheckout_register_settings() {
    register_setting('vqcheckout_settings_group', 'vqcheckout_settings', 'vqcheckout_sanitize_settings');
}


/**
 * Sanitize settings input (CRITICAL FIX: Explicit Boolean Handling)
 */
function vqcheckout_sanitize_settings($input) {
    // CRITICAL: Prevent infinite recursion when update_option is called from within this function
    static $recursion_guard = false;

    if ($recursion_guard) {
        error_log('VQ Checkout: Sanitize called recursively, returning input as-is to break loop');
        return $input;
    }

    $recursion_guard = true;

    // Debug log
    error_log('VQ Checkout: Sanitize settings called');
    error_log('VQ Checkout Input: ' . print_r($input, true));

    $sanitized_input = array();

    // Ensure Utils file is loaded and function exists
    if (!function_exists('vqcheckout_get_default_settings')) {
        error_log('VQ Checkout Error: vqcheckout_get_default_settings() not found during sanitization.');
        // Return existing settings merged with defaults if possible, to prevent data loss
        $defaults = function_exists('vqcheckout_get_default_settings') ? vqcheckout_get_default_settings() : array();
        return wp_parse_args(get_option('vqcheckout_settings', array()), $defaults);
    }

    $default_settings = vqcheckout_get_default_settings();

    // If $input is not an array, return defaults/existing.
    if (!is_array($input)) {
        return wp_parse_args(get_option('vqcheckout_settings', array()), $default_settings);
    }

    // FIX: Define explicitly which keys are checkboxes (booleans).
    // This is the most reliable method to handle unchecked boxes (which are absent in POST data).
    $checkbox_keys = [
        'phone_vn_validation', 'enable_gender', 'not_required_email', 'show_postcode', 
        'show_country', 'show_last_name', 'optimize_field_order', 'convert_price_text', 
        'to_vnd', 'remove_method_title', 'freeship_remove_other_method', 
        'paypal_conversion_enabled', 'autofill_enabled', 'anti_spam_enabled', 'recaptcha_enabled'
    ];

    // Iterate over ALL possible settings keys defined in defaults
    foreach ($default_settings as $key => $default_value) {
        
        $is_checkbox = in_array($key, $checkbox_keys);

        if (isset($input[$key])) {
            // Input is present in the submission.
            if ($is_checkbox) {
                // If checked, the value is '1'.
                $sanitized_input[$key] = '1';
            } else {
                 // Apply specific sanitization for non-checkbox fields
                switch ($key) {
                    case 'blocked_ips':
                    case 'blocked_keywords':
                        // Allow empty strings if user clears the field
                        $sanitized_input[$key] = sanitize_textarea_field($input[$key]);
                        break;
                    case 'max_orders_per_hour':
                        $sanitized_input[$key] = absint($input[$key]);
                        break;
                    case 'paypal_exchange_rate':
                         // Ensure wc_format_decimal function exists
                         if (function_exists('wc_format_decimal')) {
                            $sanitized_input[$key] = wc_format_decimal(sanitize_text_field($input[$key]));
                         } else {
                            $sanitized_input[$key] = sanitize_text_field($input[$key]);
                         }
                         break;
                    default:
                        // Handles text inputs, selects, etc. (Allow empty strings)
                        $sanitized_input[$key] = sanitize_text_field($input[$key]);
                        break;
                }
            }
        } else {
            // Input is absent from the submission.
            if ($is_checkbox) {
                 // CRITICAL FIX: If a checkbox is absent, it means it was unchecked. We MUST explicitly set it to '0'.
                 $sanitized_input[$key] = '0';
            }
            // Non-boolean fields that are absent should ideally not happen with JS tabs unless they are conditionally hidden,
            // but if they do, we ensure they have a value (default or empty string based on previous logic).
            if (!$is_checkbox && !isset($sanitized_input[$key])) {
                 // Assign default if not sanitized yet (fallback for non-checkbox fields)
                 $sanitized_input[$key] = $default_value;
            }
        }
    }

    // Debug: Log what we're returning to WordPress
    error_log('VQ Checkout: Sanitize function returning: ' . print_r($sanitized_input, true));

    // CRITICAL FIX: Force WordPress to save the values
    // WordPress Settings API sometimes doesn't save when using sanitize callback
    // We need to manually call update_option to ensure values are saved
    update_option('vqcheckout_settings', $sanitized_input);
    error_log('VQ Checkout: Forced update_option() called');

    // Reset recursion guard before returning
    $recursion_guard = false;

    return $sanitized_input;
}


/**
 * Settings page HTML rendering (Tabbed interface)
 */
function vqcheckout_settings_page_html() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }

    // Ensure Utils file is loaded and function exists
    if (!function_exists('vqcheckout_get_default_settings')) {
        echo '<div class="error"><p>Error: vqcheckout_utils.php is missing or corrupted. Cannot load settings.</p></div>';
        return;
    }

    // Get settings using wp_parse_args to ensure all keys exist.
    $saved_settings = get_option('vqcheckout_settings', array());
    $default_settings = vqcheckout_get_default_settings();
    $settings = wp_parse_args($saved_settings, $default_settings);

    // Debug: Log what we loaded
    error_log('VQ Checkout: Loading settings page');
    error_log('VQ Checkout: Saved settings from DB: ' . print_r($saved_settings, true));
    error_log('VQ Checkout: Merged settings (after wp_parse_args): ' . print_r($settings, true));

    ?>
    <div class="wrap vqcheckout-settings-wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php settings_errors(); ?>

        <h2 class="nav-tab-wrapper vq-nav-tabs">
            <a href="#tab-checkout_fields" data-tab="tab-checkout_fields" class="nav-tab"><?php _e('Trường Checkout', 'vq-checkout'); ?></a>
            <a href="#tab-general" data-tab="tab-general" class="nav-tab"><?php _e('Chung & Vận chuyển', 'vq-checkout'); ?></a>
            <a href="#tab-anti_spam" data-tab="tab-anti_spam" class="nav-tab"><?php _e('Chống SPAM', 'vq-checkout'); ?></a>
            <a href="#tab-tools" data-tab="tab-tools" class="nav-tab"><?php _e('Công cụ', 'vq-checkout'); ?></a>
        </h2>

        <div class="vq-tabs-content-wrapper">
            
            <form action="options.php" method="post" class="vqcheckout-settings-form">
                <?php
                // Output security fields
                settings_fields('vqcheckout_settings_group');
                ?>

                <div id="tab-checkout_fields" class="vq-tab-content">
                    <?php vqcheckout_render_checkout_fields_settings($settings); ?>
                </div>

                <div id="tab-general" class="vq-tab-content">
                    <?php vqcheckout_render_general_settings($settings); ?>
                </div>

                <div id="tab-anti_spam" class="vq-tab-content">
                    <?php vqcheckout_render_anti_spam_settings($settings); ?>
                </div>
                
                <div class="vq-submit-button-wrapper">
                <?php 
                // Submit button is outside the tabs but inside the form
                submit_button(__('Lưu thay đổi', 'vq-checkout')); 
                ?>
                </div>
            </form>

            <div id="tab-tools" class="vq-tab-content">
                <?php vqcheckout_render_tools_section(); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render: Checkout Fields Settings Tab
 */
function vqcheckout_render_checkout_fields_settings($settings) {
    ?>
    <table class="form-table">
        <tbody>
            <tr><td colspan="2"><h3><?php _e('Hiển thị và Thứ tự', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="optimize_field_order"><?php _e('Tối ưu thứ tự trường', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'optimize_field_order', __('Đưa trường SĐT và Email lên đầu trang Checkout.', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="enable_gender"><?php _e('Trường Xưng hô', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'enable_gender', __('Hiển thị mục chọn cách xưng hô Anh/Chị.', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="show_last_name"><?php _e('Hiển thị trường Họ (Last Name)', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'show_last_name', __('Hiển thị trường Họ. Nếu tắt, trường Tên sẽ đổi thành "Họ và Tên".', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="show_postcode"><?php _e('Hiển thị trường Postcode', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'show_postcode', __('Hiện trường mã bưu điện (Postcode) cho Việt Nam (không bắt buộc nhập).', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="show_country"><?php _e('Hiển thị trường Quốc gia', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'show_country', __('Hiển thị trường chọn Quốc gia. Nếu tắt, mặc định và chỉ cho phép Việt Nam.', 'vq-checkout')); ?>
                </td>
            </tr>

            <tr><td colspan="2"><h3><?php _e('Validation (Kiểm tra dữ liệu)', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="phone_vn_validation"><?php _e('Kiểm tra định dạng SĐT VN', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'phone_vn_validation', __('Bắt buộc SĐT có định dạng Việt Nam (+84xxx hoặc 0xxx).', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="not_required_email"><?php _e('Email tùy chọn', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'not_required_email', __('Trường email sẽ KHÔNG bắt buộc phải nhập.', 'vq-checkout')); ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Render: General & Shipping Settings Tab
 */
function vqcheckout_render_general_settings($settings) {
    ?>
    <table class="form-table" id="vq-general-settings-table">
        <tbody>
            <tr><td colspan="2"><h3><?php _e('Hiển thị Tiền tệ', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="convert_price_text"><?php _e('Chuyển giá sang dạng chữ', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'convert_price_text', __('Cho phép chuyển giá sang dạng chữ (VD: 18k, 1tr200, 1tỷ820).', 'vq-checkout')); ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="to_vnd"><?php _e('Chuyển ₫ sang VNĐ', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'to_vnd', __('Cho phép chuyển ký hiệu ₫ sang VNĐ.', 'vq-checkout')); ?>
                </td>
            </tr>

            <tr><td colspan="2"><h3><?php _e('Chuyển đổi tiền tệ PayPal (VND sang USD)', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="paypal_conversion_enabled"><?php _e('Kích hoạt chuyển đổi', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'paypal_conversion_enabled', __('Tự động chuyển đổi đơn hàng VND sang USD khi thanh toán qua PayPal (vì PayPal không hỗ trợ VND). Hỗ trợ cổng PayPal chuẩn và WooCommerce PayPal Payments.', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr class="vq-sub-setting vq-paypal-conversion-rate">
                <th scope="row"><label for="paypal_exchange_rate"><?php _e('Tỷ giá hối đoái (1 USD = ? VND)', 'vq-checkout'); ?></label></th>
                <td>
                    <input type="text" name="vqcheckout_settings[paypal_exchange_rate]" id="paypal_exchange_rate" value="<?php echo esc_attr($settings['paypal_exchange_rate']); ?>" class="regular-text">
                    <p class="description"><?php _e('Nhập tỷ giá hiện tại (VD: 25000). Có thể dùng số thập phân.', 'vq-checkout'); ?></p>
                </td>
            </tr>
            <tr><td colspan="2"><h3><?php _e('Tối ưu Vận chuyển', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="remove_method_title"><?php _e('Loại bỏ tiêu đề vận chuyển', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'remove_method_title', __('Loại bỏ tiêu đề "Vận chuyển" phía trên các phương thức.', 'vq-checkout')); ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="freeship_remove_other_method"><?php _e('Ẩn khi có Free Shipping', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'freeship_remove_other_method', __('Ẩn các phương thức vận chuyển khác khi có phương thức miễn phí (cost=0).', 'vq-checkout')); ?>
                </td>
            </tr>
            
            <tr><td colspan="2"><h3><?php _e('Modules Khác', 'vq-checkout'); ?></h3></td></tr>
            <tr>
                <th scope="row"><label for="autofill_enabled"><?php _e('Kích hoạt Auto-fill', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'autofill_enabled', __('Tự động điền thông tin khách hàng cũ dựa trên SĐT (chỉ áp dụng cho khách chưa đăng nhập).', 'vq-checkout')); ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Render: Anti-Spam Settings Tab
 */
function vqcheckout_render_anti_spam_settings($settings) {
    ?>
    <table class="form-table" id="vq-antispam-settings-table">
        <tbody>
            <tr>
                <th scope="row"><label for="anti_spam_enabled"><?php _e('Kích hoạt Chống SPAM', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'anti_spam_enabled', __('Bật các tính năng chống SPAM đơn hàng.', 'vq-checkout')); ?>
                </td>
            </tr>
            
            <tr class="vq-sub-setting"><td colspan="2"><h3><?php _e('Google reCAPTCHA v3', 'vq-checkout'); ?></h3></td></tr>
            <tr class="vq-sub-setting">
                <th scope="row"><label for="recaptcha_enabled"><?php _e('Bật reCAPTCHA v3', 'vq-checkout'); ?></label></th>
                <td>
                    <?php vqcheckout_render_checkbox($settings, 'recaptcha_enabled', __('Sử dụng Google reCAPTCHA v3 tại trang Checkout.', 'vq-checkout')); ?>
                </td>
            </tr>
            <tr class="vq-sub-setting vq-recaptcha-keys">
                <th scope="row"><label for="recaptcha_site_key"><?php _e('Site Key', 'vq-checkout'); ?></label></th>
                <td>
                    <input type="text" name="vqcheckout_settings[recaptcha_site_key]" id="recaptcha_site_key" value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>" class="regular-text">
                </td>
            </tr>
            <tr class="vq-sub-setting vq-recaptcha-keys">
                <th scope="row"><label for="recaptcha_secret_key"><?php _e('Secret Key', 'vq-checkout'); ?></label></th>
                <td>
                    <input type="text" name="vqcheckout_settings[recaptcha_secret_key]" id="recaptcha_secret_key" value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>" class="regular-text">
                </td>
            </tr>

            <tr class="vq-sub-setting"><td colspan="2"><h3><?php _e('Chặn thủ công & Giới hạn', 'vq-checkout'); ?></h3></td></tr>
            <tr class="vq-sub-setting">
                <th scope="row"><label for="blocked_ips"><?php _e('Chặn IP', 'vq-checkout'); ?></label></th>
                <td>
                    <textarea name="vqcheckout_settings[blocked_ips]" id="blocked_ips" rows="5" cols="50" class="large-text"><?php echo esc_textarea($settings['blocked_ips']); ?></textarea>
                    <p class="description"><?php _e('Mỗi địa chỉ IP một dòng.', 'vq-checkout'); ?></p>
                </td>
            </tr>

            <tr class="vq-sub-setting">
                <th scope="row"><label for="blocked_keywords"><?php _e('Chặn từ khóa', 'vq-checkout'); ?></label></th>
                <td>
                    <textarea name="vqcheckout_settings[blocked_keywords]" id="blocked_keywords" rows="5" cols="50" class="large-text"><?php echo esc_textarea($settings['blocked_keywords']); ?></textarea>
                    <p class="description"><?php _e('Mỗi từ khóa một dòng. Áp dụng cho Tên, Địa chỉ, Email, SĐT, Ghi chú.', 'vq-checkout'); ?></p>
                </td>
            </tr>

            <tr class="vq-sub-setting">
                <th scope="row"><label for="max_orders_per_hour"><?php _e('Giới hạn đơn hàng/giờ/IP', 'vq-checkout'); ?></label></th>
                <td>
                    <input type="number" name="vqcheckout_settings[max_orders_per_hour]" id="max_orders_per_hour" value="<?php echo esc_attr($settings['max_orders_per_hour']); ?>" class="small-text" min="0">
                    <p class="description"><?php _e('Số đơn hàng tối đa một IP có thể đặt trong 1 giờ. Để 0 để tắt tính năng này.', 'vq-checkout'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

/**
 * Render: Tools Section (FIXED: WSOD Issue by replacing check_admin_referer)
 */
function vqcheckout_render_tools_section() {
    // Handle tool actions if requested via POST
    
    // FIX: Check if the request method is POST and the specific action field is set.
    // Use isset($_SERVER['REQUEST_METHOD']) for robustness.
    if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['vqcheckout_tool_action'])) {
        
        // FIX: Use wp_verify_nonce() instead of check_admin_referer().
        // check_admin_referer() terminates the script (wp_die) on failure, causing WSOD if headers were already partially sent.
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        
        if (!wp_verify_nonce($nonce, 'vqcheckout_tools_nonce')) {
            echo '<div class="notice notice-error is-dismissible"><p>'.__('Xác thực bảo mật thất bại (Nonce invalid). Vui lòng tải lại trang và thử lại.', 'vq-checkout').'</p></div>';
        } elseif (!current_user_can('manage_woocommerce')) {
             echo '<div class="notice notice-error is-dismissible"><p>'.__('Bạn không có quyền thực hiện hành động này.', 'vq-checkout').'</p></div>';
        } else {
            $action = sanitize_text_field($_POST['vqcheckout_tool_action']);
            
            if ($action === 'clear_cache') {
                // Ensure the function exists before calling
                if (function_exists('vqcheckout_clear_all_caches')) {
                    vqcheckout_clear_all_caches();
                    echo '<div class="notice notice-success is-dismissible"><p>'.__('Đã xóa bộ nhớ đệm (Cache) và tối ưu hóa lại dữ liệu địa chỉ thành công.', 'vq-checkout').'</p></div>';
                }
            }

            // User Migration Tool
            if ($action === 'migrate_users_country') {
                $users_updated = vqcheckout_tool_migrate_users_country();
                echo '<div class="notice notice-success is-dismissible"><p>'.sprintf(__('Cập nhật quốc gia thành công cho %d người dùng.', 'vq-checkout'), $users_updated).'</p></div>';
            }
        }
    }

    ?>
    <div class="vqcheckout-tools-wrap">
        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=vq-checkout-settings#tab-tools')); ?>">
            <?php 
            // FIX: Use wp_nonce_field() to generate the hidden nonce fields (_wpnonce and _wp_http_referer)
            wp_nonce_field('vqcheckout_tools_nonce'); 
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Xóa Cache và Tối ưu hóa Dữ liệu', 'vq-checkout'); ?></th>
                        <td>
                            <p class="description"><?php _e('Sử dụng công cụ này nếu bạn vừa cập nhật file JSON dữ liệu địa chỉ hoặc nếu dữ liệu hiển thị không chính xác.', 'vq-checkout'); ?></p>
                            <button type="submit" name="vqcheckout_tool_action" value="clear_cache" class="button button-primary"><?php _e('Xóa Cache & Tối ưu hóa ngay', 'vq-checkout'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cập nhật Quốc gia cho User cũ', 'vq-checkout'); ?></th>
                        <td>
                            <p class="description"><?php _e('Chạy công cụ này để cập nhật trường Quốc gia (billing_country/shipping_country) của khách hàng hiện tại về Việt Nam (VN).', 'vq-checkout'); ?></p>
                            <button type="submit" name="vqcheckout_tool_action" value="migrate_users_country" class="button"><?php _e('Cập nhật người dùng cũ', 'vq-checkout'); ?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}

/**
 * Tool Logic - Migrate Users Country to VN
 */
function vqcheckout_tool_migrate_users_country() {
    $args = array(
        'role__in' => array('customer', 'subscriber'), // Only customers and subscribers, not admins
        'fields'    => 'ID',
        'number'    => -1 // Get all users
    );

    $users = get_users($args);
    $count = 0;

    // Log the total users found for debugging
    error_log('VQ Checkout: Found ' . count($users) . ' users to migrate (excluding administrators)');

    foreach ($users as $user_id) {
        // Double-check: Skip if user has administrator capability
        if (user_can($user_id, 'manage_options')) {
            error_log('VQ Checkout: Skipping user ID ' . $user_id . ' (has admin capabilities)');
            continue;
        }

        // Only update if billing_country is empty or not VN
        $current_billing = get_user_meta($user_id, 'billing_country', true);
        $current_shipping = get_user_meta($user_id, 'shipping_country', true);

        if (empty($current_billing) || $current_billing !== 'VN') {
            update_user_meta($user_id, 'billing_country', 'VN');
            $count++;
        }

        if (empty($current_shipping) || $current_shipping !== 'VN') {
            update_user_meta($user_id, 'shipping_country', 'VN');
        }
    }

    error_log('VQ Checkout: Successfully updated ' . $count . ' users');
    return $count;
}


/**
 * Helper function to render checkboxes consistently
 */
function vqcheckout_render_checkbox($settings, $key, $description) {
    // Value is guaranteed to exist because we merged with defaults in the main render function.
    // Check if key exists before accessing, just in case defaults were not loaded correctly.
    $value = isset($settings[$key]) ? $settings[$key] : '0';
    ?>
    <label for="<?php echo esc_attr($key); ?>">
        <input type="checkbox" name="vqcheckout_settings[<?php echo esc_attr($key); ?>]" id="<?php echo esc_attr($key); ?>" value="1" <?php checked($value, '1'); ?>>
        <?php echo wp_kses_post($description); // Use kses_post for descriptions that might contain HTML ?>
    </label>
    <?php
}