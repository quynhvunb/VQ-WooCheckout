# FILE 02: CORE & UTILS

## CORE FUNCTIONS, UTILITIES & MAIN PLUGIN FILE

---

## MỤC LỤC

1. [Main Plugin File](#i-main-plugin-file)
2. [Core Functions](#ii-core-functions)
3. [Utility Functions](#iii-utility-functions)
4. [AJAX Base Handlers](#iv-ajax-base-handlers)
5. [Hooks & Filters](#v-hooks--filters)
6. [Activation & Deactivation](#vi-activation--deactivation)

---

## I. MAIN PLUGIN FILE

### 1.1. File: VQ-woo-checkout.php

**Plugin Header + Bootstrap**

```php
<?php
/**
 * Plugin Name: VQ Checkout for Woo
 * Plugin URI: https://example.com/vq-checkout
 * Description: Tối ưu hóa checkout WooCommerce cho thị trường Việt Nam với địa chỉ 2 cấp và zone-based shipping.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: vq-checkout
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VQCHECKOUT_VERSION', '2.0.0');
define('VQCHECKOUT_PLUGIN_FILE', __FILE__);
define('VQCHECKOUT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VQCHECKOUT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VQCHECKOUT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // WooCommerce not active, show admin notice
    add_action('admin_notices', 'vqcheckout_woocommerce_missing_notice');
    return;
}

function vqcheckout_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('VQ Checkout for Woo requires WooCommerce to be installed and active.', 'vq-checkout'); ?></p>
    </div>
    <?php
}

/**
 * Main VQ Checkout Class
 */
final class VQ_Checkout {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core files
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_core.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_utils.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_ajax.php';
        
        // Address system (Files 03-04)
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_store_settings.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_checkout_fields.php';
        
        // Shipping system (Files 05-06) - Load Shipping Method Class
        // Phải dùng hook này để đảm bảo WC_Shipping_Method tồn tại
        add_action('woocommerce_shipping_init', array($this, 'include_shipping_method'));
        
        // Additional modules (File 07)
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_settings.php'; // THÊM MỚI: Settings Page
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_admin_order.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_autofill.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_anti_spam.php';
    }
	
	/**
     * THÊM MỚI: Include shipping method class
     */
    public function include_shipping_method() {
        if (!class_exists('WC_Shipping_Method')) {
            return;
        }
        // Đây là file implementation của File 05 và 06
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/class-vq-ward-shipping-method.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Plugin activation/deactivation
        register_activation_hook(VQCHECKOUT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(VQCHECKOUT_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Init
        add_action('init', array($this, 'init'), 0);
        
        // Load plugin textdomain
        add_action('init', array($this, 'load_textdomain'));
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
		
		// Register shipping method
        add_filter('woocommerce_shipping_methods', array($this, 'register_shipping_method'));

        // Admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
    }
	
	/**
     * Register shipping method với WooCommerce
     */
    public function register_shipping_method($methods) {
        $methods['vq_ward_shipping'] = 'VQ_Ward_Shipping_Method';
        return $methods;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(VQCHECKOUT_PLUGIN_BASENAME);
            wp_die(__('VQ Checkout requires PHP 7.4 or higher.', 'vq-checkout'));
        }

        // Check WooCommerce version
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '5.0', '<')) {
            deactivate_plugins(VQCHECKOUT_PLUGIN_BASENAME);
            wp_die(__('VQ Checkout requires WooCommerce 5.0 or higher.', 'vq-checkout'));
        }
        
        // Set default options
        if (!get_option('vqcheckout_version')) {
            add_option('vqcheckout_version', VQCHECKOUT_VERSION);
        }
		
		// Initialize default settings (Sử dụng option mới)
        if (!get_option('vqcheckout_settings')) {
            // Hàm vqcheckout_get_default_settings() sẽ được định nghĩa trong Utils
            $default_settings = vqcheckout_get_default_settings(); 
            add_option('vqcheckout_settings', $default_settings);
        }
        
        // Set activation flag
        set_transient('vqcheckout_activated', true, 60);
        
        // Clear all caches
        vqcheckout_clear_all_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all caches
        vqcheckout_clear_all_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check for updates
        $current_version = get_option('vqcheckout_version');
        if (version_compare($current_version, VQCHECKOUT_VERSION, '<')) {
            $this->upgrade($current_version);
        }
    }
    
    /**
     * Upgrade plugin data
     */
    private function upgrade($old_version) {
        // Clear caches on upgrade
        vqcheckout_clear_all_caches();
        
        // Update version
        update_option('vqcheckout_version', VQCHECKOUT_VERSION);
        
        // Set upgrade notice
        set_transient('vqcheckout_upgraded', true, 60);
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'vq-checkout',
            false,
            dirname(VQCHECKOUT_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue admin scripts (Sửa đổi)
     */
    public function admin_scripts($hook) {
        // Global admin CSS
        wp_enqueue_style(
            'vqcheckout-admin',
            VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_admin.css',
            array(),
            VQCHECKOUT_VERSION
        );

        // Global Admin JS (Dùng cho Settings Page và các trang khác của VQ)
        // Kiểm tra nếu hook có chứa 'vqcheckout' (cho trang settings của plugin) HOẶC đang ở trang WC Settings
        if (strpos($hook, 'vqcheckout') !== false || $hook === 'woocommerce_page_wc-settings') {
             wp_enqueue_script(
                'vqcheckout-admin',
                VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_admin.js',
                array('jquery'),
                VQCHECKOUT_VERSION,
                true
            );

            // Localize script (Global admin AJAX)
            wp_localize_script('vqcheckout-admin', 'vqcheckoutAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('vqcheckout_admin_nonce'),
                'strings'  => array(
                    'confirm_delete' => __('Are you sure you want to delete this?', 'vq-checkout'),
                    //... (các strings khác nếu có)
                )
            ));
        }
        
        // QUAN TRỌNG: Scripts for WooCommerce Shipping Zone Settings (Để File 05 hoạt động)
        // Chỉ tải khi đang ở trang Cài đặt Vận chuyển của WooCommerce
        if ($hook === 'woocommerce_page_wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'shipping') {
            
            // Select2 (Đảm bảo được load)
            wp_enqueue_style('select2');
            wp_enqueue_script('select2');

            // Shipping Method UI JS (File 05) - Cần wp-util để dùng template JS
            wp_enqueue_script(
                'vqcheckout-shipping-method-ui',
                VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_shipping_method_ui.js',
                array('jquery', 'select2', 'wp-util'),
                VQCHECKOUT_VERSION,
                true
            );
            
            // CSS cho Shipping Method UI (File 05)
            wp_enqueue_style(
                'vqcheckout-shipping-method-ui',
                VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_shipping_method_ui.css',
                array(),
                VQCHECKOUT_VERSION
            );

            // Localize script for AJAX loading wards in admin (Specific cho Shipping UI)
            // Lưu ý: Sử dụng handle khác ('vqcheckout-shipping-method-ui') và object name 'vqcheckoutShippingAdmin'
            wp_localize_script('vqcheckout-shipping-method-ui', 'vqcheckoutShippingAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('vqcheckout_admin_nonce'),
            ));
        }
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function frontend_scripts() {
        // Only on checkout page
        if (!is_checkout()) {
            return;
        }
        
        // Frontend CSS
        wp_enqueue_style(
            'vqcheckout-frontend',
            VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_frontend.css',
            array(),
            VQCHECKOUT_VERSION
        );
        
        // Frontend JS
        wp_enqueue_script(
            'vqcheckout-frontend',
            VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_frontend.js',
            array('jquery', 'wc-checkout'),
            VQCHECKOUT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vqcheckout-frontend', 'vqcheckoutAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('vqcheckout_ajax_nonce'),
            'strings'  => array(
                'loading_wards' => __('Đang tải...', 'vq-checkout'),
                'select_ward'   => __('Chọn xã/phường...', 'vq-checkout'),
                'error_loading' => __('Lỗi tải dữ liệu', 'vq-checkout')
            )
        ));
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Activation notice
        if (get_transient('vqcheckout_activated')) {
            delete_transient('vqcheckout_activated');
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('VQ Checkout for Woo has been activated successfully!', 'vq-checkout'); ?></p>
            </div>
            <?php
        }
        
        // Upgrade notice
        if (get_transient('vqcheckout_upgraded')) {
            delete_transient('vqcheckout_upgraded');
            ?>
            <div class="notice notice-info is-dismissible">
                <p><?php printf(__('VQ Checkout has been upgraded to version %s', 'vq-checkout'), VQCHECKOUT_VERSION); ?></p>
            </div>
            <?php
        }
    }
}

/**
 * Returns the main instance of VQ_Checkout
 */
function VQ_Checkout() {
    return VQ_Checkout::instance();
}

// Initialize plugin
VQ_Checkout();
```

---

## II. CORE FUNCTIONS

### 2.1. File: inc/vqcheckout_core.php

```php
<?php
/**
 * VQ Checkout Core Functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize address system
 */
function vqcheckout_init_address_system() {
    // Register Vietnam provinces as WC states
    add_filter('woocommerce_states', 'vqcheckout_register_vietnam_provinces');
    
    // Set default country to Vietnam (nếu tùy chọn hiển thị Quốc gia bị tắt)
    $show_country = vqcheckout_get_option('show_country', '0');
    if ($show_country !== '1') {
        add_filter('default_checkout_billing_country', 'vqcheckout_default_country');
        add_filter('default_checkout_shipping_country', 'vqcheckout_default_country');
    }

    // Initialize general enhancements
    vqcheckout_init_general_enhancements();
}
add_action('init', 'vqcheckout_init_address_system', 5);

/**
 * Register Vietnam provinces as WooCommerce states
 */
function vqcheckout_register_vietnam_provinces($states) {
    $provinces = vqcheckout_get_provinces();
    
    if (!empty($provinces)) {
        $states['VN'] = $provinces;
    }
    
    return $states;
}

/**
 * Set default country to Vietnam
 */
function vqcheckout_default_country($country) {
    return 'VN';
}

/**
 * Initialize general enhancements (Chuyển đổi tiền tệ, ẩn shipping title...)
 */
function vqcheckout_init_general_enhancements() {
    // Chuyển ₫ sang VNĐ
    if (vqcheckout_get_option('to_vnd', '0') === '1') {
        add_filter('woocommerce_currency_symbol', 'vqcheckout_change_currency_symbol', 10, 2);
    }

    // Loại bỏ tiêu đề vận chuyển
    if (vqcheckout_get_option('remove_method_title', '1') === '1') {
        // Dùng filter này để loại bỏ tiêu đề package (VD: "Vận chuyển")
        add_filter('woocommerce_shipping_package_name', '__return_empty_string', 10, 3);
    }

    // Ẩn phương thức khác khi có free-shipping
    if (vqcheckout_get_option('freeship_remove_other_method', '0') === '1') {
        add_filter('woocommerce_package_rates', 'vqcheckout_hide_shipping_when_free_is_available', 100);
    }

    // Chuyển giá sang dạng chữ
    if (vqcheckout_get_option('convert_price_text', '0') === '1') {
        add_filter('woocommerce_get_price_html', 'vqcheckout_convert_price_to_text', 10, 2);
    }
}

/**
 * Change currency symbol
 */
function vqcheckout_change_currency_symbol($currency_symbol, $currency) {
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
 * Convert price to text format (e.g., 18k, 1tr200)
 */
function vqcheckout_convert_price_to_text($price_html, $product) {
    // Lấy giá trị số thực tế của sản phẩm
    $price = $product->get_price();
    if (empty($price) || $price == 0) {
        return $price_html;
    }

    // Sử dụng hàm utility để format
    $text_price = vqcheckout_format_price_text($price);
    
    // Lấy ký hiệu tiền tệ
    $currency_symbol = get_woocommerce_currency_symbol();

    // Trả về HTML mới
    return '<span class="woocommerce-Price-amount amount vqcheckout-price-text">' . $text_price . '<span class="woocommerce-Price-currencySymbol">' . $currency_symbol . '</span></span>';
}
```

---

## III. UTILITY FUNCTIONS

### 3.1. File: inc/vqcheckout_utils.php

```php
<?php
/**
 * VQ Checkout Utility Functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// ----------------------------------------------------
// SETTINGS MANAGEMENT (Điều chỉnh)
// ----------------------------------------------------

/**
 * Get default settings structure
 * @return array Default settings
 */
function vqcheckout_get_default_settings() {
    return array(
        // Checkout Fields
        'phone_vn_validation' => '1',
        'enable_gender' => '1',
        'not_required_email' => '1',
        'show_postcode' => '0',
        'show_country' => '0', // Mặc định ẩn (tức là force VN)
        'show_last_name' => '0',
        'load_address_method' => 'json', // json | ajax

        // General
        'convert_price_text' => '0',
        'to_vnd' => '0',
        'remove_method_title' => '1',
        'freeship_remove_other_method' => '0',

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
 * Get plugin option (Lấy từ mảng cài đặt chung)
 */
function vqcheckout_get_option($key, $default = false) {
    // Lấy toàn bộ settings
    $settings = get_option('vqcheckout_settings', array());
    
    // Nếu key tồn tại trong settings đã lưu
    if (isset($settings[$key])) {
        return $settings[$key];
    }
    
    // Nếu key không tồn tại, kiểm tra xem có giá trị default được truyền vào không
    if ($default !== false) {
        return $default;
    }

    // Nếu không có default truyền vào, lấy từ cấu trúc mặc định
    $default_settings = vqcheckout_get_default_settings();
    return isset($default_settings[$key]) ? $default_settings[$key] : false;
}

/**
 * Get provinces data from JSON
 * 
 * @return array Province code => Province name
 */
function vqcheckout_get_provinces() {
    // Check cache
    $cached = get_transient('vqcheckout_provinces');
    if (false !== $cached) {
        return $cached;
    }
    
    // Load from JSON
    $file = VQCHECKOUT_PLUGIN_DIR . 'data/vietnam_provinces.json';
    
    if (!file_exists($file)) {
        vqcheckout_log('Provinces JSON file not found: ' . $file, 'error');
        return array();
    }
    
    $json = file_get_contents($file);
    $provinces = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        vqcheckout_log('Error decoding provinces JSON: ' . json_last_error_msg(), 'error');
        return array();
    }
    
    // Cache for 1 day
    set_transient('vqcheckout_provinces', $provinces, DAY_IN_SECONDS);
    
    return $provinces;
}

/**
 * Get all wards data from JSON
 * 
 * @return array Province code => Wards array
 */
function vqcheckout_get_all_wards_data() {
    $file = VQCHECKOUT_PLUGIN_DIR . 'data/vietnam_wards.json';
    
    if (!file_exists($file)) {
        vqcheckout_log('Wards JSON file not found: ' . $file, 'error');
        return array();
    }
    
    $json = file_get_contents($file);
    $wards = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        vqcheckout_log('Error decoding wards JSON: ' . json_last_error_msg(), 'error');
        return array();
    }
    
    return $wards;
}

/**
 * Get wards for specific province
 * 
 * @param string $province_code Province code
 * @return array Ward code => Ward data
 */
function vqcheckout_get_wards_by_province($province_code) {
    if (empty($province_code)) {
        return array();
    }
    
    // Check cache
    $cache_key = 'vqcheckout_wards_' . $province_code;
    $cached = get_transient($cache_key);
    if (false !== $cached) {
        return $cached;
    }
    
    // Load all wards
    $all_wards = vqcheckout_get_all_wards_data();
    
    // Get wards for this province
    $wards = isset($all_wards[$province_code]) ? $all_wards[$province_code] : array();
    
    // Cache for 1 day
    set_transient($cache_key, $wards, DAY_IN_SECONDS);
    
    return $wards;
}

/**
 * Get province name by code
 * 
 * @param string $province_code Province code
 * @return string Province name
 */
function vqcheckout_get_province_name($province_code) {
    $provinces = vqcheckout_get_provinces();
    return isset($provinces[$province_code]) ? $provinces[$province_code] : '';
}

/**
 * Get ward name by code
 * 
 * @param string $province_code Province code
 * @param string $ward_code Ward code
 * @return string Ward name
 */
function vqcheckout_get_ward_name($province_code, $ward_code) {
    $wards = vqcheckout_get_wards_by_province($province_code);
    return isset($wards[$ward_code]['name']) ? $wards[$ward_code]['name'] : '';
}

/**
 * Format full address for display
 * 
 * @param array $address Address data
 * @return string Formatted address HTML
 */
function vqcheckout_format_full_address($address) {
    $lines = array();
    
    // Street address
    if (!empty($address['address_1'])) {
        $lines[] = esc_html($address['address_1']);
    }
    
    // Ward, Province
    $ward_name = vqcheckout_get_ward_name($address['state'], $address['city']);
    $province_name = vqcheckout_get_province_name($address['state']);
    
    if ($ward_name && $province_name) {
        $lines[] = esc_html($ward_name . ', ' . $province_name);
    } elseif ($province_name) {
        $lines[] = esc_html($province_name);
    }
    
    // Country
    $lines[] = 'Việt Nam';
    
    // Postcode
    if (!empty($address['postcode'])) {
        $lines[] = 'Mã bưu điện: ' . esc_html($address['postcode']);
    }
    
    return implode('<br>', $lines);
}

/**
 * Clear address-related caches
 */
function vqcheckout_clear_address_cache() {
    delete_transient('vqcheckout_provinces');
    
    // Clear wards cache for all provinces
    $provinces = vqcheckout_get_provinces();
    foreach (array_keys($provinces) as $province_code) {
        delete_transient('vqcheckout_wards_' . $province_code);
    }
}

/**
 * Validate ward code belongs to province
 * 
 * @param string $province_code Province code
 * @param string $ward_code Ward code
 * @return bool Valid or not
 */
function vqcheckout_validate_ward_code($province_code, $ward_code) {
    if (empty($province_code) || empty($ward_code)) {
        return false;
    }
    
    $wards = vqcheckout_get_wards_by_province($province_code);
    return isset($wards[$ward_code]);
}

/**
 * Format price to text (18k, 1tr200, 1tỷ820)
 */
function vqcheckout_format_price_text($price) {
    $price = (int) $price;

    if ($price >= 1000000000) {
        // Tỷ
        $billions = floor($price / 1000000000);
        $remainder = $price % 1000000000;
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
        $remainder = $price % 1000000;
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
        $remainder = $price % 1000;
        $text = $thousands . 'k';
        if ($remainder > 0) {
            $text .= $remainder;
        }
        return $text;
    } else {
        return $price . 'đ';
    }
}

/**
 * Validate Vietnam phone number format (+84xxx hoặc 0xxx)
 */
function vqcheckout_is_valid_vn_phone($phone) {
    // Remove spaces
    $phone = str_replace(' ', '', $phone);

    // Check format: Bắt đầu bằng 0 hoặc +84, theo sau là các đầu số di động VN hợp lệ
    if (preg_match('/^(0|\+84)(3[2-9]|5[6|8|9]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/', $phone)) {
        return true;
    }
    return false;
}

/**
 * Sanitize address data
 * 
 * @param array $address Address data
 * @return array Sanitized address
 */
function vqcheckout_sanitize_address($address) {
    return array(
        'country'   => 'VN',
        'state'     => sanitize_text_field($address['state'] ?? ''),
        'city'      => sanitize_text_field($address['city'] ?? ''),
        'address_1' => sanitize_text_field($address['address_1'] ?? ''),
        'address_2' => sanitize_text_field($address['address_2'] ?? ''),
        'postcode'  => sanitize_text_field($address['postcode'] ?? '')
    );
}

/**
 * Generate unique zone ID
 * 
 * @param string $name Zone name
 * @return string Zone ID
 */
function vqcheckout_generate_zone_id($name) {
    $slug = sanitize_title($name);
    return 'zone_' . $slug . '_' . time();
}

/**
 * Round fee to nearest thousand
 * 
 * @param float $fee Fee amount
 * @return float Rounded fee
 */
function vqcheckout_round_fee($fee) {
    return round($fee / 1000) * 1000;
}

/**
 * Check if string contains Vietnamese characters
 * 
 * @param string $string String to check
 * @return bool
 */
function vqcheckout_has_vietnamese_chars($string) {
    return preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $string);
}

/**
 * Remove Vietnamese accents
 * 
 * @param string $string String with accents
 * @return string String without accents
 */
function vqcheckout_remove_accents($string) {
    $accents = array(
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd'
    );
    
    return strtr($string, $accents);
}
```

---

## IV. AJAX BASE HANDLERS

### 4.1. File: inc/vqcheckout_ajax.php

```php
<?php
/**
 * VQ Checkout AJAX Handlers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX: Load wards by province (Frontend & Admin Store Settings)
 */
add_action('wp_ajax_vqcheckout_load_wards', 'vqcheckout_ajax_load_wards');
add_action('wp_ajax_nopriv_vqcheckout_load_wards', 'vqcheckout_ajax_load_wards');

function vqcheckout_ajax_load_wards() {
    // Verify nonce
    check_ajax_referer('vqcheckout_ajax_nonce', 'nonce');
    
    // Get province code
    $province_code = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    
    if (empty($province_code)) {
        wp_send_json_error('Missing province code');
    }
    
    // Get wards
    $wards = vqcheckout_get_wards_by_province($province_code);
    
    // Format response
    $formatted = array();
    foreach ($wards as $ward_code => $ward_data) {
        $formatted[$ward_code] = array(
            'name' => $ward_data['name']
        );
    }
    
    wp_send_json_success($formatted);
}

/**
 * AJAX: Load wards for Shipping Method settings (Admin WC Zones) (MỚI)
 * Dùng để load dữ liệu Xã/Phường khi cấu hình phí vận chuyển trong Admin.
 */
add_action('wp_ajax_vqcheckout_load_shipping_wards_admin', 'vqcheckout_ajax_load_shipping_wards_admin');

function vqcheckout_ajax_load_shipping_wards_admin() {
    check_ajax_referer('vqcheckout_admin_nonce', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }

    // Lấy danh sách Tỉnh/Thành được truyền lên (dựa trên cấu hình của Zone)
    $province_codes = isset($_POST['provinces']) ? (array) $_POST['provinces'] : array();

    if (empty($province_codes)) {
        wp_send_json_error('Missing province codes');
    }

    $results = array();
    $provinces_list = vqcheckout_get_provinces();

    // Tạo cấu trúc dữ liệu phù hợp cho Select2 (Grouped by Province)
    foreach ($province_codes as $province_code) {
        $province_code = sanitize_text_field($province_code);
        $wards = vqcheckout_get_wards_by_province($province_code);
        
        if (!empty($wards)) {
            $province_name = isset($provinces_list[$province_code]) ? $provinces_list[$province_code] : $province_code;
            $group = array(
                'text' => $province_name,
                'children' => array()
            );

            foreach ($wards as $ward_code => $ward_data) {
                $group['children'][] = array(
                    'id'   => $ward_code,
                    'text' => $ward_data['name']
                );
            }
            $results[] = $group;
        }
    }

    if (empty($results)) {
        wp_send_json_error('No wards found for selected provinces');
    }

    wp_send_json_success($results);
}

/**
 * AJAX: Get ward name
 */
add_action('wp_ajax_vqcheckout_get_ward_name', 'vqcheckout_ajax_get_ward_name');
add_action('wp_ajax_nopriv_vqcheckout_get_ward_name', 'vqcheckout_ajax_get_ward_name');

function vqcheckout_ajax_get_ward_name() {
    check_ajax_referer('vqcheckout_ajax_nonce', 'nonce');
    
    $province_code = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    $ward_code = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    
    if (empty($province_code) || empty($ward_code)) {
        wp_send_json_error('Missing parameters');
    }
    
    $ward_name = vqcheckout_get_ward_name($province_code, $ward_code);
    
    if (empty($ward_name)) {
        wp_send_json_error('Ward not found');
    }
    
    wp_send_json_success(array('name' => $ward_name));
}

/**
 * AJAX: Clear all caches (admin only)
 */
add_action('wp_ajax_vqcheckout_clear_caches', 'vqcheckout_ajax_clear_caches');

function vqcheckout_ajax_clear_caches() {
    check_ajax_referer('vqcheckout_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    vqcheckout_clear_all_caches();
    
    wp_send_json_success('Caches cleared successfully');
}
```

---

## V. HOOKS & FILTERS

### 5.1. Key WordPress/WooCommerce Hooks

```php
/**
 * Validate address on checkout
 */
add_action('woocommerce_after_checkout_validation', 'vqcheckout_validate_checkout_address', 10, 2);

function vqcheckout_validate_checkout_address($data, $errors) {
    // Validate billing
    if (!empty($data['billing_state']) && !empty($data['billing_city'])) {
        if (!vqcheckout_validate_ward_code($data['billing_state'], $data['billing_city'])) {
            $errors->add('billing', __('Xã/Phường không hợp lệ với Tỉnh/Thành đã chọn.', 'vq-checkout'));
        }
    }
    
    // Validate shipping (if different)
    if (!empty($data['ship_to_different_address'])) {
        if (!empty($data['shipping_state']) && !empty($data['shipping_city'])) {
            if (!vqcheckout_validate_ward_code($data['shipping_state'], $data['shipping_city'])) {
                $errors->add('shipping', __('Xã/Phường giao hàng không hợp lệ.', 'vq-checkout'));
            }
        }
    }
}

/**
 * Save custom address fields to order
 */
add_action('woocommerce_checkout_update_order_meta', 'vqcheckout_save_order_address_meta');

function vqcheckout_save_order_address_meta($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }
    
    // Save province names
    $billing_state = $order->get_billing_state();
    if ($billing_state) {
        $order->update_meta_data('_billing_state_name', vqcheckout_get_province_name($billing_state));
    }
    
    // Save ward names
    $billing_city = $order->get_billing_city();
    if ($billing_city && $billing_state) {
        $order->update_meta_data('_billing_city_name', vqcheckout_get_ward_name($billing_state, $billing_city));
    }
    
    $order->save();
}
```

---

## VI. ACTIVATION & DEACTIVATION

### 6.1. Activation Tasks

```php
/**
 * Plugin activation tasks
 */
function vqcheckout_activate() {
    // Check requirements
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die('VQ Checkout requires PHP 7.4+');
    }
    
    if (!class_exists('WooCommerce')) {
        wp_die('VQ Checkout requires WooCommerce');
    }
    
    // Set default options
    add_option('vqcheckout_version', VQCHECKOUT_VERSION);
    add_option('vqcheckout_shipping_zones', array());
    
    // Create default zone (optional)
    $default_zone = array(
        'zone_default' => array(
            'id' => 'zone_default',
            'name' => 'Toàn quốc',
            'enabled' => true,
            'priority' => 1,
            'scope' => array(
                'type' => 'all',
                'provinces' => array(),
                'exclude_provinces' => array()
            ),
            'default_config' => array(
                'no_shipping' => false,
                'method' => 'fixed',
                'fee' => 30000,
                'free_ship' => array(
                    'enabled' => false
                ),
                'round_fee' => true
            ),
            'ward_configs' => array()
        )
    );
    
    // Uncomment to create default zone
    // update_option('vqcheckout_shipping_zones', $default_zone);
    
    // Set activation flag
    set_transient('vqcheckout_activated', true, 60);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation tasks
 */
function vqcheckout_deactivate() {
    // Clear caches
    vqcheckout_clear_all_caches();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Note: We don't delete data on deactivation
    // Users may want to reactivate later
}
```

---

## VII. CHECKLIST TRIỂN KHAI FILE 02

- [ ] Tạo `VQ-woo-checkout.php` với plugin header
- [ ] Implement main `VQ_Checkout` class
- [ ] Tạo `inc/vqcheckout_core.php`
- [ ] Tạo `inc/vqcheckout_utils.php`
- [ ] Tạo `inc/vqcheckout_ajax.php`
- [ ] Test activation/deactivation hooks
- [ ] Test asset loading (admin & frontend)
- [ ] Test AJAX endpoints
- [ ] Verify cache clearing
- [ ] Test validation functions

---

**File tiếp theo:** [File 03: Store Settings Integration](./03-Store-Settings-Integration.md)

---

**Updated:** 2025-01-20
