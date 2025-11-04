<?php
/**
 * Plugin Name: VQ Checkout for Woo
 * Description: Tối ưu hóa checkout WooCommerce cho thị trường Việt Nam với địa chỉ 2 cấp và phí vận chuyển theo xã/phường.
 * Version: 2.1.1
 * Author: Your Name
 * Text Domain: vq-checkout
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('VQCHECKOUT_VERSION')) {
    define('VQCHECKOUT_VERSION', '2.1.1');
    define('VQCHECKOUT_PLUGIN_FILE', __FILE__);
    define('VQCHECKOUT_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('VQCHECKOUT_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('VQCHECKOUT_PLUGIN_BASENAME', plugin_basename(__FILE__));
    define('VQCHECKOUT_DATA_DIR', VQCHECKOUT_PLUGIN_DIR . 'data/');
}

// FIX: Include utils immediately. This file MUST be available globally for activation hooks.
require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_utils.php';

/**
 * Main VQ Checkout Class
 */
final class VQ_Checkout {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // FIX: Check dependencies inside the constructor after 'plugins_loaded'.
        if (!$this->check_dependencies()) {
            return; // Stop initialization if dependencies are missing, but the class still exists.
        }

        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check for required dependencies (WooCommerce)
     */
    private function check_dependencies() {
        // Use class_exists('WooCommerce') which is the standard check on 'plugins_loaded'.
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        return true;
    }

    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('VQ Checkout for Woo requires WooCommerce to be installed and active.', 'vq-checkout'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core files (Utils already included globally)
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_core.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_ajax.php';
        
        // Address system (Files 03-04)
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_store_settings.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_checkout_fields.php';
        
        // Shipping system (Files 05-06) - Load Shipping Method Class
        add_action('woocommerce_shipping_init', array($this, 'include_shipping_method'));
        
        // Additional modules (File 07)
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_settings.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_admin_order.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_autofill.php';
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/vqcheckout_anti_spam.php';
    }

    /**
     * Include shipping method class
     */
    public function include_shipping_method() {
        if (!class_exists('WC_Shipping_Method')) {
            return;
        }
        require_once VQCHECKOUT_PLUGIN_DIR . 'inc/class-vq-ward-shipping-method.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Init action
        add_action('init', array($this, 'init'), 0);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        // Register shipping method
        add_filter('woocommerce_shipping_methods', array($this, 'register_shipping_method'));
    }

    /**
     * Register shipping method
     */
    public function register_shipping_method($methods) {
        $methods['vq_ward_shipping'] = 'VQ_Ward_Shipping_Method';
        return $methods;
    }
    
    /**
     * Plugin activation (Static method)
     */
    public static function activate() {
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(VQCHECKOUT_PLUGIN_BASENAME);
            wp_die(__('VQ Checkout requires PHP 7.4 or higher.', 'vq-checkout'));
        }
        
        // Initialize default settings (if not exist)
        // FIX: Use get_option() check. This now works because utils.php is guaranteed to be loaded.
        if (false === get_option('vqcheckout_settings')) {
            // Check if the required function exists before calling
            if (function_exists('vqcheckout_get_default_settings')) {
                $default_settings = vqcheckout_get_default_settings();
                add_option('vqcheckout_settings', $default_settings);
            }
        }

        // Update version
        update_option('vqcheckout_version', VQCHECKOUT_VERSION);
        
        // Clear all caches
        if (function_exists('vqcheckout_clear_all_caches')) {
            vqcheckout_clear_all_caches();
        }
    }
    
    /**
     * Plugin deactivation (Static method)
     */
    public static function deactivate() {
        // Clear all caches
        if (function_exists('vqcheckout_clear_all_caches')) {
            vqcheckout_clear_all_caches();
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load textdomain
        load_plugin_textdomain('vq-checkout', false, dirname(VQCHECKOUT_PLUGIN_BASENAME) . '/languages/');
        
        // Upgrade check
        $current_version = get_option('vqcheckout_version');
        if ($current_version && version_compare($current_version, VQCHECKOUT_VERSION, '<')) {
            $this->upgrade($current_version);
        }
    }

    /**
     * Upgrade routine
     */
    private function upgrade($old_version) {
        // Perform necessary upgrades here
        
        // Clear caches on upgrade
        if (function_exists('vqcheckout_clear_all_caches')) {
             vqcheckout_clear_all_caches();
        }
        
        // Update version
        update_option('vqcheckout_version', VQCHECKOUT_VERSION);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function admin_scripts($hook) {
        // Debug
        error_log('=== VQ Checkout: admin_scripts called ===');
        error_log('Hook: ' . $hook);
        error_log('GET params: ' . print_r($_GET, true));

        // Global admin CSS (File 07)
        wp_enqueue_style(
            'vqcheckout-admin-global',
            VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_admin.css',
            array(),
            VQCHECKOUT_VERSION
        );

        // Global Admin JS - Load on VQ Checkout settings page and WC settings
        if (strpos($hook, 'vq-checkout') !== false || $hook === 'woocommerce_page_wc-settings') {
             wp_enqueue_script(
                'vqcheckout-admin-global',
                VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_admin.js',
                // Ensure jquery-ui-sortable dependency for drag/drop (used in Settings JS)
                array('jquery', 'jquery-ui-sortable'),
                VQCHECKOUT_VERSION,
                true
            );

            // Localize script (Global admin AJAX)
            wp_localize_script('vqcheckout-admin-global', 'vqcheckoutAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('vqcheckout_admin_nonce'),
            ));
        }
        
        // Scripts for WooCommerce Shipping Zone Settings (File 05)
        // ALWAYS load on WC settings page (WC uses inline editing for shipping methods)
        if ($hook === 'woocommerce_page_wc-settings') {
            error_log('VQ Checkout: ENQUEUING shipping scripts (WC settings page)');

            // Select2 (Ensure loaded)
            wp_enqueue_style('select2');
            wp_enqueue_script('select2');

            // Shipping Method UI JS V2 - Requires select2 and sortable
            wp_enqueue_script(
                'vqcheckout-shipping-method-ui',
                VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_shipping_method_ui_v2.js',
                array('jquery', 'select2', 'jquery-ui-sortable'),
                VQCHECKOUT_VERSION,
                true
            );

            // CSS for Shipping Method UI V2
            wp_enqueue_style(
                'vqcheckout-shipping-method-ui',
                VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_shipping_method_ui_v2.css',
                array(),
                VQCHECKOUT_VERSION
            );

            // Localize script for AJAX loading wards in admin
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
        // Load on checkout and account page
        if (!is_checkout() && !is_account_page()) {
            return;
        }
        
        // Frontend CSS (File 04)
        wp_enqueue_style(
            'vqcheckout-checkout',
            VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_checkout.css',
            array(),
            VQCHECKOUT_VERSION
        );

        // Frontend JS (File 04)
        wp_enqueue_script(
            'vqcheckout-checkout',
            VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_checkout.js',
            array('jquery', 'selectWoo'),
            VQCHECKOUT_VERSION,
            true
        );
        
        // Localize script
        $load_method = 'ajax';
        
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('vqcheckout_ajax_nonce'),
            'load_method' => $load_method,
            'i18n' => array(
                'select_ward' => __('Chọn xã/phường...', 'vq-checkout'),
                'loading' => __('Đang tải...', 'vq-checkout'),
            ),
        );

        wp_localize_script('vqcheckout-checkout', 'vqcheckoutCheckout', $localize_data);
    }
}

// FIX: Register activation/deactivation hooks statically outside the class definition.
register_activation_hook(VQCHECKOUT_PLUGIN_FILE, array('VQ_Checkout', 'activate'));
register_deactivation_hook(VQCHECKOUT_PLUGIN_FILE, array('VQ_Checkout', 'deactivate'));

// FIX: Initialize plugin on 'plugins_loaded' hook.
function vq_checkout_init() {
    VQ_Checkout::instance();
}
add_action('plugins_loaded', 'vq_checkout_init');