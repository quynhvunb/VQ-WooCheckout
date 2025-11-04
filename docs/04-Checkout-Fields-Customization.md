# FILE 04: CHECKOUT FIELDS CUSTOMIZATION

## CUSTOM CHECKOUT FORM - ĐỊA CHỈ 2 CẤP CHO KHÁCH HÀNG

---

## MỤC LỤC

1. [Giới thiệu](#i-giới-thiệu)
2. [Checkout Fields Strategy](#ii-checkout-fields-strategy)
3. [Backend Implementation](#iii-backend-implementation)
4. [Frontend JavaScript](#iv-frontend-javascript)
5. [Frontend CSS](#v-frontend-css)
6. [Validation](#vi-validation)
7. [Testing](#vii-testing)

---

## I. GIỚI THIỆU

### 1.1. Mục tiêu

Customize checkout form để sử dụng địa chỉ 2 cấp Việt Nam cho cả **Billing** và **Shipping** addresses.

**Before (WooCommerce default):**
```
Billing Details:
  Country: [All countries]
  State: [Text input or state dropdown]
  City: [Text input]
  Address: [Text input]
```

**After (VQ Checkout):**
```
Thông tin thanh toán:
  Tỉnh/Thành phố: [Dropdown - 34 tỉnh]
  Xã/Phường: [Dropdown - AJAX load theo tỉnh]
  Địa chỉ: [Text input]
  Mã bưu điện: [Text input - optional]
```

### 1.2. Fields Mapping

| WC Field | VQ Checkout | Type | Required |
|----------|-------------|------|----------|
| `billing_country` | Hidden (VN) | hidden | - |
| `billing_state` | Tỉnh/Thành phố | select | Yes |
| `billing_city` | Xã/Phường | select | Yes |
| `billing_address_1` | Địa chỉ | text | Yes |
| `billing_postcode` | Mã bưu điện | text | No |

**Same for shipping fields** (`shipping_*`)

---

## II. CHECKOUT FIELDS STRATEGY

### 2.1. Override Approach

```
WooCommerce Checkout Fields
    ↓
Filter: woocommerce_checkout_fields
    ↓
Customize:
  - Hide country field (default VN)
  - Change state to province dropdown
  - Change city to ward dropdown
  - Update labels to Vietnamese
    ↓
AJAX: Load wards on province change
    ↓
Validate on submit
```

### 2.2. Field Priority Order

```
billing_state (priority 41)      → Tỉnh/Thành phố
billing_city (priority 42)       → Xã/Phường
billing_address_1 (priority 43)  → Địa chỉ
billing_postcode (priority 44)   → Mã bưu điện (optional)
```

---

## III. BACKEND IMPLEMENTATION

### 3.1. File: inc/vqcheckout_checkout_fields.php

```php
<?php
/**
 * VQ Checkout - Checkout Fields Customization
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Checkout Fields customization
 */
function vqcheckout_init_checkout_fields() {
    // Customize default address fields (áp dụng chung)
    add_filter('woocommerce_default_address_fields', 'vqcheckout_customize_default_address_fields', 30);
    
    // Customize checkout fields (cho trang checkout)
    add_filter('woocommerce_checkout_fields', 'vqcheckout_customize_checkout_fields', 30);
    
    // Validate checkout fields
    add_action('woocommerce_after_checkout_validation', 'vqcheckout_validate_checkout_fields', 10, 2);
}
add_action('init', 'vqcheckout_init_checkout_fields');

/**
 * Customize default address fields (Điều chỉnh)
 */
function vqcheckout_customize_default_address_fields($fields) {
    
    // 1. Cấu hình địa chỉ 2 cấp
    
    // State -> Tỉnh/Thành phố
    if (isset($fields['state'])) {
        $fields['state']['type'] = 'select';
        $fields['state']['label'] = __('Tỉnh/Thành phố', 'vq-checkout');
        $fields['state']['class'] = array('form-row-wide', 'address-field', 'update_totals_on_change', 'vqcheckout-province-field');
        $fields['state']['priority'] = 41;
        $fields['state']['required'] = true;
    }
    
    // City -> Xã/Phường
    if (isset($fields['city'])) {
        $fields['city']['type'] = 'select';
        $fields['city']['label'] = __('Xã/Phường', 'vq-checkout');
        $fields['city']['class'] = array('form-row-wide', 'address-field', 'update_totals_on_change', 'vqcheckout-ward-field');
        $fields['city']['priority'] = 42;
        $fields['city']['required'] = true;
        $fields['city']['options'] = array('' => __('Chọn tỉnh/thành trước...', 'vq-checkout'));
    }
    
    // Address 1
    if (isset($fields['address_1'])) {
        $fields['address_1']['priority'] = 43;
    }
    
    // Address 2 - Luôn ẩn
    if (isset($fields['address_2'])) {
        unset($fields['address_2']);
    }

    // 2. Tùy chỉnh hiển thị dựa trên Settings (File 07)

    // Country field
    if (isset($fields['country'])) {
        $fields['country']['priority'] = 40;
        // Nếu tùy chọn hiển thị Quốc gia bị tắt
        if (vqcheckout_get_option('show_country', '0') !== '1') {
            $fields['country']['type'] = 'hidden';
            $fields['country']['default'] = 'VN';
        }
    }
    
    // Postcode field
    if (isset($fields['postcode'])) {
        $fields['postcode']['priority'] = 44;
        // Nếu tùy chọn hiển thị Postcode bị tắt
        if (vqcheckout_get_option('show_postcode', '0') !== '1') {
            unset($fields['postcode']);
        } else {
            $fields['postcode']['required'] = false; // Luôn không bắt buộc
        }
    }

    // Last Name (Họ)
    if (isset($fields['last_name'])) {
        // Nếu tùy chọn hiển thị Last Name bị tắt
        if (vqcheckout_get_option('show_last_name', '0') !== '1') {
            unset($fields['last_name']);
            
            // Điều chỉnh First Name thành "Họ và Tên" và full width
            if (isset($fields['first_name'])) {
                $fields['first_name']['label'] = __('Họ và Tên', 'vq-checkout');
                $fields['first_name']['class'] = array('form-row-wide');
            }
        }
    }
    
    return $fields;
}

/**
 * Customize checkout fields (Điều chỉnh)
 */
function vqcheckout_customize_checkout_fields($fields) {
    
    // Cập nhật options cho State (Tỉnh/Thành)
    $province_options = vqcheckout_get_province_options();
    
    $sections = array('billing', 'shipping');
    foreach ($sections as $section) {
        if (isset($fields[$section][$section . '_state'])) {
            $fields[$section][$section . '_state']['options'] = $province_options;
        }
    }

    // Thêm trường Xưng hô (Gender) nếu được bật
    if (vqcheckout_get_option('enable_gender', '1') === '1') {
        $fields['billing']['billing_gender'] = array(
            'type' => 'select',
            'label' => __('Xưng hô', 'vq-checkout'),
            'required' => true,
            'class' => array('form-row-first', 'vqcheckout-gender-field'),
            'priority' => 5,
            'options' => array(
                '' => __('Chọn xưng hô...', 'vq-checkout'),
                'male' => __('Anh', 'vq-checkout'),
                'female' => __('Chị', 'vq-checkout'),
            )
        );

        // Điều chỉnh lại class của Tên/Họ nếu có Xưng hô
        if (isset($fields['billing']['billing_first_name'])) {
            // Nếu First Name đang full width (do ẩn Last Name)
            if (in_array('form-row-wide', $fields['billing']['billing_first_name']['class'])) {
                 $fields['billing']['billing_first_name']['class'] = array('form-row-last');
            }
            // Nếu đang hiện cả Họ và Tên, cần điều chỉnh phức tạp hơn (xem xét CSS)
        }
    }

    // Tùy chỉnh Email (Không bắt buộc)
    if (vqcheckout_get_option('not_required_email', '1') === '1') {
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['required'] = false;
        }
    }
    
    return $fields;
}

/**
 * Customize address section fields
 * 
 * @param array $fields Address fields
 * @param string $type Address type (billing/shipping)
 * @return array Modified fields
 */
function vqcheckout_customize_address_section($fields, $type) {
    $prefix = $type . '_';
    
    // Country - hide
    if (isset($fields[$prefix . 'country'])) {
        $fields[$prefix . 'country']['type'] = 'hidden';
        $fields[$prefix . 'country']['default'] = 'VN';
    }
    
    // State - province dropdown
    if (isset($fields[$prefix . 'state'])) {
        $fields[$prefix . 'state']['type'] = 'select';
        $fields[$prefix . 'state']['options'] = vqcheckout_get_province_options();
    }
    
    // City - ward dropdown (empty by default, loaded via AJAX)
    if (isset($fields[$prefix . 'city'])) {
        $fields[$prefix . 'city']['type'] = 'select';
        $fields[$prefix . 'city']['options'] = array('' => __('Chọn tỉnh/thành trước...', 'vq-checkout'));
    }
    
    return $fields;
}

/**
 * Get province options for dropdown
 */
function vqcheckout_get_province_options() {
    $provinces = vqcheckout_get_provinces();
    $options = array('' => __('Chọn tỉnh/thành...', 'vq-checkout'));
    if (is_array($provinces)) {
        $options += $provinces;
    }
    return $options;
}

/**
 * Validate checkout fields (BỔ SUNG NẾU THIẾU)
 */
function vqcheckout_validate_checkout_fields($data, $errors) {
    // 1. Validation SĐT Việt Nam (nếu được bật trong Settings)
    if (vqcheckout_get_option('phone_vn_validation', '1') === '1') {
        $phone = isset($data['billing_phone']) ? $data['billing_phone'] : '';
        
        // Hàm vqcheckout_is_valid_vn_phone() được định nghĩa trong Utils (File 02)
        // Chỉ kiểm tra nếu SĐT không rỗng (WC core đã kiểm tra required)
        if (!empty($phone) && function_exists('vqcheckout_is_valid_vn_phone') && !vqcheckout_is_valid_vn_phone($phone)) {
            $errors->add(
                'validation',
                __('Số điện thoại không đúng định dạng Việt Nam (VD: 0912345678 hoặc +84912345678).', 'vq-checkout')
            );
        }
    }

    // 2. Validation Địa chỉ 2 cấp (Kiểm tra xem Xã/Phường có thuộc Tỉnh/Thành đã chọn không)
    $sections = array('billing');
    // Kiểm tra nếu có địa chỉ giao hàng khác
    if (isset($data['ship_to_different_address']) && $data['ship_to_different_address']) {
        $sections[] = 'shipping';
    }

    foreach ($sections as $section) {
        $province_code = isset($data[$section . '_state']) ? $data[$section . '_state'] : '';
        $ward_code = isset($data[$section . '_city']) ? $data[$section . '_city'] : '';

        if (!empty($province_code) && !empty($ward_code)) {
            // Hàm kiểm tra tính hợp lệ của cặp Tỉnh-Xã (Định nghĩa trong Utils - File 02)
            if (function_exists('vqcheckout_is_valid_ward') && !vqcheckout_is_valid_ward($province_code, $ward_code)) {
                $errors->add(
                    'validation',
                    sprintf(__('Xã/Phường đã chọn không hợp lệ hoặc không thuộc Tỉnh/Thành phố trong phần %s.', 'vq-checkout'), ($section == 'billing' ? __('Thanh toán', 'vq-checkout') : __('Giao hàng', 'vq-checkout')))
                );
            }
        }
    }
}

/**
 * Validate address fields
 * 
 * @param array $data Posted data
 * @param WP_Error $errors Error object
 * @param string $type Address type (billing/shipping)
 */
function vqcheckout_validate_address_fields($data, $errors, $type) {
    $prefix = $type . '_';
    
    $province_code = isset($data[$prefix . 'state']) ? sanitize_text_field($data[$prefix . 'state']) : '';
    $ward_code = isset($data[$prefix . 'city']) ? sanitize_text_field($data[$prefix . 'city']) : '';
    
    // Check if province selected
    if (empty($province_code)) {
        $errors->add(
            $type,
            sprintf(__('Vui lòng chọn Tỉnh/Thành phố %s.', 'vq-checkout'), $type === 'billing' ? 'thanh toán' : 'giao hàng')
        );
        return;
    }
    
    // Check if ward selected
    if (empty($ward_code)) {
        $errors->add(
            $type,
            sprintf(__('Vui lòng chọn Xã/Phường %s.', 'vq-checkout'), $type === 'billing' ? 'thanh toán' : 'giao hàng')
        );
        return;
    }
    
    // Validate ward belongs to province
    if (!vqcheckout_validate_ward_code($province_code, $ward_code)) {
        $errors->add(
            $type,
            __('Xã/Phường không hợp lệ với Tỉnh/Thành phố đã chọn.', 'vq-checkout')
        );
    }
}

/**
 * Enqueue checkout scripts
 */
function vqcheckout_enqueue_checkout_scripts() {
    // Only on checkout page
    if (!is_checkout() || is_order_received_page()) {
        return;
    }
    
    // Frontend CSS
    wp_enqueue_style(
        'vqcheckout-checkout',
        VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_checkout.css',
        array(),
        VQCHECKOUT_VERSION
    );
    
    // Frontend JS
    wp_enqueue_script(
        'vqcheckout-checkout',
        VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_checkout.js',
        array('jquery', 'wc-checkout'),
        VQCHECKOUT_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('vqcheckout-checkout', 'vqcheckoutCheckout', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vqcheckout_ajax_nonce'),
        'strings'  => array(
            'loading_wards'    => __('Đang tải...', 'vq-checkout'),
            'select_province'  => __('Chọn tỉnh/thành trước...', 'vq-checkout'),
            'select_ward'      => __('Chọn xã/phường...', 'vq-checkout'),
            'no_wards'         => __('Không có dữ liệu', 'vq-checkout'),
            'error_loading'    => __('Lỗi tải dữ liệu', 'vq-checkout')
        )
    ));
}

/**
 * Set default country to Vietnam
 */
add_filter('default_checkout_billing_country', 'vqcheckout_default_billing_country');
add_filter('default_checkout_shipping_country', 'vqcheckout_default_shipping_country');

function vqcheckout_default_billing_country($country) {
    return 'VN';
}

function vqcheckout_default_shipping_country($country) {
    return 'VN';
}

/**
 * Pre-populate fields for logged-in users
 */
add_filter('woocommerce_checkout_get_value', 'vqcheckout_checkout_get_value', 10, 2);

function vqcheckout_checkout_get_value($value, $input) {
    // Only for logged-in users
    if (!is_user_logged_in()) {
        return $value;
    }
    
    // If value already set, return it
    if (!empty($value)) {
        return $value;
    }
    
    $user_id = get_current_user_id();
    $customer = new WC_Customer($user_id);
    
    // Pre-populate billing fields
    if ($input === 'billing_state' && !$value) {
        return $customer->get_billing_state();
    }
    
    if ($input === 'billing_city' && !$value) {
        return $customer->get_billing_city();
    }
    
    // Pre-populate shipping fields
    if ($input === 'shipping_state' && !$value) {
        return $customer->get_shipping_state();
    }
    
    if ($input === 'shipping_city' && !$value) {
        return $customer->get_shipping_city();
    }
    
    return $value;
}
```

---

## IV. FRONTEND JAVASCRIPT

### 4.1. File: assets/js/vqcheckout_checkout.js

```javascript
/**
 * VQ Checkout - Checkout Page JavaScript
 * Handle ward loading on checkout form
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Ward cache to avoid repeated AJAX calls
    var wardCache = {};
    
    // Flag to prevent multiple simultaneous requests
    var loadingWards = false;
    
    /**
     * Initialize address fields
     */
    function initAddressFields() {
        // Initialize billing address
        initAddressSection('billing');
        
        // Initialize shipping address
        initAddressSection('shipping');
        
        // Handle "Ship to different address" toggle
        $('#ship-to-different-address-checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                initAddressSection('shipping');
            }
        });
    }
    
    /**
     * Initialize address section (billing or shipping)
     */
    function initAddressSection(type) {
        var $provinceField = $('#' + type + '_state');
        var $wardField = $('#' + type + '_city');
        
        if (!$provinceField.length || !$wardField.length) {
            return;
        }
        
        // Load wards when province changes
        $provinceField.on('change', function() {
            var provinceCode = $(this).val();
            loadWards(provinceCode, $wardField, type);
        });
        
        // Trigger ward loading on page load if province already selected
        if ($provinceField.val()) {
            var currentProvince = $provinceField.val();
            var currentWard = $wardField.data('initial-value') || $wardField.val();
            
            // Store current ward to restore after loading
            if (currentWard) {
                $wardField.data('restore-value', currentWard);
            }
            
            loadWards(currentProvince, $wardField, type);
        }
        
        // Trigger checkout update when ward changes
        $wardField.on('change', function() {
            $(document.body).trigger('update_checkout');
        });
    }
    
    /**
     * Load wards for selected province
     */
    function loadWards(provinceCode, $wardField, type) {
        if (!provinceCode) {
            resetWardField($wardField);
            return;
        }
        
        // Check cache first
        if (wardCache[provinceCode]) {
            populateWardField($wardField, wardCache[provinceCode], type);
            return;
        }
        
        // Prevent multiple simultaneous requests
        if (loadingWards) {
            return;
        }
        
        loadingWards = true;
        
        // Show loading state
        $wardField.prop('disabled', true);
        $wardField.html('<option value="">' + vqcheckoutCheckout.strings.loading_wards + '</option>');
        
        // AJAX request
        $.ajax({
            url: vqcheckoutCheckout.ajax_url,
            type: 'POST',
            data: {
                action: 'vqcheckout_load_wards',
                nonce: vqcheckoutCheckout.nonce,
                state: provinceCode
            },
            success: function(response) {
                loadingWards = false;
                
                if (response.success && response.data) {
                    // Cache the wards data
                    wardCache[provinceCode] = response.data;
                    
                    // Populate dropdown
                    populateWardField($wardField, response.data, type);
                } else {
                    showWardError($wardField);
                }
            },
            error: function() {
                loadingWards = false;
                showWardError($wardField);
            }
        });
    }
    
    /**
     * Populate ward field with options
     */
    function populateWardField($wardField, wards, type) {
        // Get value to restore (if any)
        var restoreValue = $wardField.data('restore-value') || '';
        var currentValue = $wardField.val() || restoreValue;
        
        // Clear existing options
        $wardField.empty();
        
        // Add default option
        $wardField.append(
            $('<option>', {
                value: '',
                text: vqcheckoutCheckout.strings.select_ward
            })
        );
        
        // Add ward options
        if (wards && Object.keys(wards).length > 0) {
            $.each(wards, function(wardCode, wardData) {
                var option = $('<option>', {
                    value: wardCode,
                    text: wardData.name
                });
                
                // Select current value if exists
                if (wardCode === currentValue) {
                    option.prop('selected', true);
                }
                
                $wardField.append(option);
            });
        } else {
            $wardField.append(
                $('<option>', {
                    value: '',
                    text: vqcheckoutCheckout.strings.no_wards
                })
            );
        }
        
        // Enable field
        $wardField.prop('disabled', false);
        
        // Clear restore value
        $wardField.removeData('restore-value');
        
        // Trigger change if value was restored
        if (currentValue && $wardField.val()) {
            $wardField.trigger('change');
        }
    }
    
    /**
     * Reset ward field to default state
     */
    function resetWardField($wardField) {
        $wardField.empty();
        $wardField.append(
            $('<option>', {
                value: '',
                text: vqcheckoutCheckout.strings.select_province
            })
        );
        $wardField.prop('disabled', true);
    }
    
    /**
     * Show error in ward field
     */
    function showWardError($wardField) {
        $wardField.empty();
        $wardField.append(
            $('<option>', {
                value: '',
                text: vqcheckoutCheckout.strings.error_loading
            })
        );
        $wardField.prop('disabled', true);
        
        // Add error class for styling
        $wardField.addClass('vqcheckout-error');
        
        // Remove error class after 3 seconds
        setTimeout(function() {
            $wardField.removeClass('vqcheckout-error');
        }, 3000);
    }
    
    /**
     * Store initial values for restoration after AJAX updates
     */
    function storeInitialValues() {
        $('#billing_city').data('initial-value', $('#billing_city').val());
        $('#shipping_city').data('initial-value', $('#shipping_city').val());
    }
    
    /**
     * Handle WooCommerce checkout update
     */
    $(document.body).on('updated_checkout', function() {
        // Re-initialize after checkout update
        initAddressFields();
    });
    
    /**
     * Initialize on page load
     */
    storeInitialValues();
    initAddressFields();
    
    /**
     * Form validation before submit
     */
    $('form.checkout').on('checkout_place_order', function() {
        var valid = true;
        
        // Validate billing address
        var billingProvince = $('#billing_state').val();
        var billingWard = $('#billing_city').val();
        
        if (!billingProvince || !billingWard) {
            valid = false;
            
            // Scroll to billing fields
            $('html, body').animate({
                scrollTop: $('#billing_state').offset().top - 100
            }, 500);
            
            // Highlight empty fields
            if (!billingProvince) {
                $('#billing_state').addClass('vqcheckout-error');
            }
            if (!billingWard) {
                $('#billing_city').addClass('vqcheckout-error');
            }
        }
        
        // Validate shipping address (if different)
        if ($('#ship-to-different-address-checkbox').is(':checked')) {
            var shippingProvince = $('#shipping_state').val();
            var shippingWard = $('#shipping_city').val();
            
            if (!shippingProvince || !shippingWard) {
                valid = false;
                
                // Scroll to shipping fields
                $('html, body').animate({
                    scrollTop: $('#shipping_state').offset().top - 100
                }, 500);
                
                // Highlight empty fields
                if (!shippingProvince) {
                    $('#shipping_state').addClass('vqcheckout-error');
                }
                if (!shippingWard) {
                    $('#shipping_city').addClass('vqcheckout-error');
                }
            }
        }
        
        // Remove error class on focus
        $('.vqcheckout-error').on('focus change', function() {
            $(this).removeClass('vqcheckout-error');
        });
        
        return valid;
    });
    
    /**
     * Auto-remove validation errors after field change
     */
    $('.address-field').on('change', function() {
        $(this).removeClass('vqcheckout-error');
    });
});
```

---

## V. FRONTEND CSS

### 5.1. File: assets/css/vqcheckout_checkout.css

```css
/**
 * VQ Checkout - Checkout Page Styles
 */

/* Province and Ward select fields */
.woocommerce-checkout .vqcheckout-province-field,
.woocommerce-checkout .vqcheckout-ward-field {
    width: 100%;
}

/* Loading state for ward dropdown */
.woocommerce-checkout .vqcheckout-ward-field:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Error state */
.woocommerce-checkout .vqcheckout-error {
    border-color: #e2401c !important;
    box-shadow: 0 0 0 2px rgba(226, 64, 28, 0.2);
    animation: vqcheckout-shake 0.3s;
}

@keyframes vqcheckout-shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Error message styling */
.woocommerce-error li[data-id*="billing"],
.woocommerce-error li[data-id*="shipping"] {
    list-style: none;
    padding: 10px 15px;
    margin-bottom: 10px;
    background-color: #fff6f6;
    border-left: 3px solid #e2401c;
}

/* Hide country field (it's hidden but style just in case) */
.woocommerce-checkout .vqcheckout-country-field {
    display: none !important;
}

/* Select field styling */
.woocommerce-checkout select.vqcheckout-province-field,
.woocommerce-checkout select.vqcheckout-ward-field {
    padding: 10px 12px;
    font-size: 14px;
    line-height: 1.5;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
    transition: border-color 0.3s ease;
}

.woocommerce-checkout select.vqcheckout-province-field:focus,
.woocommerce-checkout select.vqcheckout-ward-field:focus {
    border-color: #96588a;
    outline: none;
    box-shadow: 0 0 0 2px rgba(150, 88, 138, 0.1);
}

/* Loading indicator for ward field */
.woocommerce-checkout .vqcheckout-ward-field.loading {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxMCIgY3k9IjEwIiByPSI4IiBzdHJva2U9IiNjY2MiIHN0cm9rZS13aWR0aD0iMiIgZmlsbD0ibm9uZSIgc3Ryb2tlLWRhc2hhcnJheT0iNTAgNTAiPjxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgdHlwZT0icm90YXRlIiBmcm9tPSIwIDEwIDEwIiB0bz0iMzYwIDEwIDEwIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIvPjwvY2lyY2xlPjwvc3ZnPg==');
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 16px 16px;
}

/* Address field labels */
.woocommerce-checkout .form-row label {
    font-weight: 600;
    color: #333;
}

.woocommerce-checkout .form-row .required {
    color: #e2401c;
    font-weight: bold;
}

/* Responsive design */
@media (max-width: 768px) {
    .woocommerce-checkout .form-row-wide {
        width: 100%;
    }
    
    .woocommerce-checkout select.vqcheckout-province-field,
    .woocommerce-checkout select.vqcheckout-ward-field {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}

/* Success state (optional) */
.woocommerce-checkout .vqcheckout-success {
    border-color: #7ad03a !important;
}

/* Placeholder styling for empty dropdowns */
.woocommerce-checkout select option[value=""] {
    color: #999;
    font-style: italic;
}

/* Loading spinner for entire checkout form */
.woocommerce-checkout.processing {
    opacity: 0.6;
    pointer-events: none;
}

/* Custom scrollbar for long dropdown lists (webkit browsers) */
.woocommerce-checkout select.vqcheckout-ward-field option {
    padding: 8px 12px;
}

/* Fix for some themes that override select styling */
.woocommerce-checkout .select2-container--default .select2-selection--single {
    height: auto;
    min-height: 40px;
}

/* Billing/Shipping toggle */
#ship-to-different-address {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e5e5;
}

/* Address preview (optional enhancement) */
.vqcheckout-address-preview {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border-left: 3px solid #96588a;
    font-size: 13px;
    line-height: 1.6;
    color: #666;
}

.vqcheckout-address-preview strong {
    display: block;
    margin-bottom: 5px;
    color: #333;
}
```

---

## VI. VALIDATION

### 6.1. Server-side Validation

Already implemented in `vqcheckout_validate_checkout_fields()` function above.

**Validation Rules:**
1. ✅ Province must be selected
2. ✅ Ward must be selected
3. ✅ Ward must belong to selected province
4. ✅ Address 1 must not be empty (WC default)

### 6.2. Client-side Validation

Implemented in JavaScript above:
- Check before form submit
- Highlight empty fields
- Scroll to error
- Auto-remove errors on change

### 6.3. AJAX Validation (Real-time)

Optional enhancement:

```javascript
// Add to vqcheckout_checkout.js

/**
 * Real-time ward validation
 */
$('#billing_city, #shipping_city').on('change', function() {
    var $wardField = $(this);
    var type = $wardField.attr('id').replace('_city', '');
    var provinceCode = $('#' + type + '_state').val();
    var wardCode = $wardField.val();
    
    if (!provinceCode || !wardCode) {
        return;
    }
    
    // Validate via AJAX
    $.ajax({
        url: vqcheckoutCheckout.ajax_url,
        type: 'POST',
        data: {
            action: 'vqcheckout_validate_ward',
            nonce: vqcheckoutCheckout.nonce,
            province: provinceCode,
            ward: wardCode
        },
        success: function(response) {
            if (response.success) {
                $wardField.removeClass('vqcheckout-error').addClass('vqcheckout-success');
            } else {
                $wardField.addClass('vqcheckout-error').removeClass('vqcheckout-success');
            }
        }
    });
});
```

---

## VII. TESTING

### 7.1. Test Checklist

#### Basic Functionality
- [ ] Navigate to checkout page
- [ ] Verify province dropdown shows 34 options
- [ ] Select province (e.g., Hà Nội)
- [ ] Verify ward dropdown loads via AJAX
- [ ] Verify ward dropdown shows correct wards
- [ ] Select ward
- [ ] Verify shipping cost updates
- [ ] Complete checkout
- [ ] Verify order saved with correct address

#### Validation
- [ ] Try checkout without province → Should show error
- [ ] Try checkout without ward → Should show error
- [ ] Select ward from different province → Should show error on submit

#### AJAX & Performance
- [ ] Change province multiple times → Should load wards each time
- [ ] Change province quickly → Should not send multiple requests
- [ ] Network error simulation → Should show error message
- [ ] Ward cache → Second load of same province should be instant

#### User Experience
- [ ] Loading state shows during AJAX
- [ ] Error state highlights field
- [ ] Form validation scrolls to error
- [ ] Pre-population works for logged-in users
- [ ] "Ship to different address" works correctly

#### Responsive
- [ ] Test on mobile (< 768px)
- [ ] Dropdowns are touch-friendly
- [ ] Form is usable on small screens

### 7.2. Test Scenarios

**Scenario 1: New Guest Customer**
1. Go to checkout as guest
2. Province dropdown = empty (placeholder)
3. Ward dropdown = disabled
4. Select province → Ward loads
5. Select ward → Shipping updates
6. Complete order → Success

**Scenario 2: Returning Logged-in Customer**
1. Login with existing customer
2. Go to checkout
3. Province auto-filled from last order
4. Wards auto-loaded
5. Ward auto-selected from last order
6. Complete order → Success

**Scenario 3: Ship to Different Address**
1. Go to checkout
2. Fill billing address
3. Check "Ship to different address"
4. Fill shipping address (different province)
5. Both addresses have correct wards
6. Complete order → Success

### 7.3. Debug Tools

```php
/**
 * Debug checkout data
 */
add_action('woocommerce_checkout_order_processed', 'vqcheckout_debug_checkout_data', 10, 3);

function vqcheckout_debug_checkout_data($order_id, $posted_data, $order) {
    if (!WP_DEBUG) {
        return;
    }
    
    error_log('=== VQ Checkout Debug ===');
    error_log('Order ID: ' . $order_id);
    error_log('Billing Province: ' . $order->get_billing_state());
    error_log('Billing Ward: ' . $order->get_billing_city());
    error_log('Shipping Province: ' . $order->get_shipping_state());
    error_log('Shipping Ward: ' . $order->get_shipping_city());
}
```

---

## VIII. CHECKLIST TRIỂN KHAI FILE 04

- [ ] Tạo `inc/vqcheckout_checkout_fields.php`
- [ ] Implement `vqcheckout_customize_default_address_fields()`
- [ ] Implement `vqcheckout_customize_checkout_fields()`
- [ ] Implement `vqcheckout_validate_checkout_fields()`
- [ ] Implement field pre-population for logged-in users
- [ ] Tạo `assets/js/vqcheckout_checkout.js`
- [ ] Implement ward loading logic
- [ ] Implement ward caching
- [ ] Implement form validation
- [ ] Tạo `assets/css/vqcheckout_checkout.css`
- [ ] Test basic functionality
- [ ] Test validation
- [ ] Test AJAX performance
- [ ] Test responsive design
- [ ] Test with different themes

---

**File tiếp theo:** [File 05: Shipping Zones Manager](./05-Shipping-Zones-Manager.md)

---

**Updated:** 2025-01-20
