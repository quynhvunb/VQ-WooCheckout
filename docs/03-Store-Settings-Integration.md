# FILE 03: STORE SETTINGS INTEGRATION

## OVERRIDE WOOCOMMERCE STORE SETTINGS - ĐỊA CHỈ 2 CẤP

---

## MỤC LỤC

1. [Giới thiệu](#i-giới-thiệu)
2. [Override Strategy](#ii-override-strategy)
3. [Backend Implementation](#iii-backend-implementation)
4. [Admin JavaScript](#iv-admin-javascript)
5. [Admin CSS](#v-admin-css)
6. [Testing](#vi-testing)

---

## I. GIỚI THIỆU

### 1.1. Mục tiêu

Override phần **Store Address** trong WooCommerce > Settings > General để sử dụng địa chỉ 2 cấp Việt Nam.

**Before (WooCommerce default):**
```
Country / Region: [All countries dropdown]
Address line 1: [Text input]
Address line 2: [Text input]
City: [Text input]
Postcode / ZIP: [Text input]
```

**After (VQ Checkout):**
```
Quốc gia / Tiểu bang: [Dropdown - Việt Nam với 34 tỉnh/thành]
Phường/Xã: [Dropdown - AJAX load theo tỉnh]
Địa chỉ dòng 1: [Text input]
Mã bưu điện: [Text input]
```

### 1.2. Fields Mapping

| WC Option | Giá trị | Ví dụ |
|-----------|---------|-------|
| `woocommerce_default_country` | VN:PROVINCE_CODE | "VN:HANOI" |
| `woocommerce_store_city` | WARD_CODE | "00634" |
| `woocommerce_store_address` | Street address | "Số 04 Lô 6..." |
| `woocommerce_store_postcode` | Postcode | "12011" |

---

## II. OVERRIDE STRATEGY

### 2.1. Hook vào WC Settings

```
WooCommerce Settings API
    ↓
Filter: woocommerce_general_settings
    ↓
Find & Replace Fields:
  - default_country → Custom dropdown with VN provinces
  - store_city → AJAX ward dropdown
    ↓
Enqueue custom JS/CSS
    ↓
AJAX: Load wards when province changes
```

### 2.2. Không Ảnh Hưởng Fields Khác

Plugin chỉ override 2 fields:
- `woocommerce_default_country` (Country/State)
- `woocommerce_store_city` (City → Ward)

Các fields khác giữ nguyên:
- `woocommerce_store_address` (Address line 1)
- `woocommerce_store_address_2` (Address line 2 - ẩn đi)
- `woocommerce_store_postcode` (Postcode)

---

## III. BACKEND IMPLEMENTATION

### 3.1. File: inc/vqcheckout_store_settings.php

```php
<?php
/**
 * VQ Checkout Store Settings Integration
 * Override WooCommerce Store Settings
 * 
 * @package VQ_Checkout_For_Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Store Settings integration
 */
function vqcheckout_init_store_settings() {
    // Override WC general settings
    add_filter('woocommerce_general_settings', 'vqcheckout_override_general_settings', 10, 1);
    
    // Enqueue admin scripts only on WC settings page
    add_action('admin_enqueue_scripts', 'vqcheckout_enqueue_store_settings_scripts');
}
add_action('init', 'vqcheckout_init_store_settings');

/**
 * Override WooCommerce general settings
 * 
 * @param array $settings WC general settings
 * @return array Modified settings
 */
function vqcheckout_override_general_settings($settings) {
    // Only on General settings tab
    if (!isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
        return $settings;
    }
    
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'general') {
        return $settings;
    }
    
    $new_settings = array();
    
    foreach ($settings as $setting) {
        // Override default_country field
        if (isset($setting['id']) && $setting['id'] === 'woocommerce_default_country') {
            $new_settings[] = vqcheckout_get_country_state_field();
        }
        // Override store_city field
        elseif (isset($setting['id']) && $setting['id'] === 'woocommerce_store_city') {
            $new_settings[] = vqcheckout_get_store_ward_field();
        }
        // Hide store_address_2 (not used in 2-tier system)
        elseif (isset($setting['id']) && $setting['id'] === 'woocommerce_store_address_2') {
            $setting['type'] = 'hidden';
            $new_settings[] = $setting;
        }
        // Keep other settings unchanged
        else {
            $new_settings[] = $setting;
        }
    }
    
    return $new_settings;
}

/**
 * Get custom country/state field
 * 
 * @return array Field config
 */
function vqcheckout_get_country_state_field() {
    $provinces = vqcheckout_get_provinces();
    $current_value = get_option('woocommerce_default_country', '');
    
    // Parse current value (format: VN:HANOI)
    $parts = explode(':', $current_value);
    $selected_province = isset($parts[1]) ? $parts[1] : '';
    
    // Build options
    $options = array('' => __('Chọn tỉnh/thành...', 'vq-checkout'));
    foreach ($provinces as $code => $name) {
        $options['VN:' . $code] = 'Việt Nam — ' . $name;
    }
    
    return array(
        'title'    => __('Quốc gia / Tiểu bang', 'woocommerce'),
        'desc'     => __('Quốc gia và thành phố nơi trụ sở cửa hàng của bạn.', 'woocommerce'),
        'id'       => 'woocommerce_default_country',
        'default'  => '',
        'type'     => 'select',
        'class'    => 'wc-enhanced-select vqcheckout-province-select',
        'css'      => 'width: 400px;',
        'desc_tip' => true,
        'options'  => $options,
        'value'    => $current_value
    );
}

/**
 * Get custom ward field
 * 
 * @return array Field config
 */
function vqcheckout_get_store_ward_field() {
    $current_country = get_option('woocommerce_default_country', '');
    $current_ward = get_option('woocommerce_store_city', '');
    
    // Parse province code from country option
    $parts = explode(':', $current_country);
    $province_code = isset($parts[1]) ? $parts[1] : '';
    
    // Load wards if province selected
    $ward_options = array('' => __('Chọn phường/xã...', 'vq-checkout'));
    
    if (!empty($province_code)) {
        $wards = vqcheckout_get_wards_by_province($province_code);
        foreach ($wards as $ward_code => $ward_data) {
            $ward_options[$ward_code] = $ward_data['name'];
        }
    }
    
    return array(
        'title'    => __('Phường/Xã', 'vq-checkout'),
        'desc'     => __('Phường/Xã của địa chỉ cửa hàng.', 'vq-checkout'),
        'id'       => 'woocommerce_store_city',
        'default'  => '',
        'type'     => 'select',
        'class'    => 'wc-enhanced-select vqcheckout-ward-select',
        'css'      => 'width: 400px;',
        'desc_tip' => true,
        'options'  => $ward_options,
        'value'    => $current_ward
    );
}

/**
 * Enqueue admin scripts for Store Settings page
 * 
 * @param string $hook Current admin page hook
 */
function vqcheckout_enqueue_store_settings_scripts($hook) {
    // Only on WC Settings page
    if ($hook !== 'woocommerce_page_wc-settings') {
        return;
    }
    
    // Only on General tab
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'general') {
        return;
    }
    
    // Enqueue Select2 (already loaded by WC, but ensure it's there)
    wp_enqueue_style('select2');
    wp_enqueue_script('select2');
    
    // Custom CSS for Store Settings
    wp_enqueue_style(
        'vqcheckout-store-settings',
        VQCHECKOUT_PLUGIN_URL . 'assets/css/vqcheckout_store_settings.css',
        array(),
        VQCHECKOUT_VERSION
    );
    
    // Custom JS for Store Settings
    wp_enqueue_script(
        'vqcheckout-store-settings',
        VQCHECKOUT_PLUGIN_URL . 'assets/js/vqcheckout_store_settings.js',
        array('jquery', 'select2'),
        VQCHECKOUT_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('vqcheckout-store-settings', 'vqcheckoutStoreSettings', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vqcheckout_ajax_nonce'),
        'strings'  => array(
            'loading'      => __('Đang tải...', 'vq-checkout'),
            'select_ward'  => __('Chọn phường/xã...', 'vq-checkout'),
            'no_wards'     => __('Không có dữ liệu', 'vq-checkout'),
            'error'        => __('Lỗi tải dữ liệu', 'vq-checkout')
        )
    ));
}

/**
 * AJAX: Load wards for Store Settings
 * (Uses same endpoint as checkout, but specific for admin)
 */
add_action('wp_ajax_vqcheckout_load_store_wards', 'vqcheckout_ajax_load_store_wards');

function vqcheckout_ajax_load_store_wards() {
    check_ajax_referer('vqcheckout_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $province_code = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    
    if (empty($province_code)) {
        wp_send_json_error('Missing province code');
    }
    
    // Get wards
    $wards = vqcheckout_get_wards_by_province($province_code);
    
    // Format for select2
    $formatted = array();
    foreach ($wards as $ward_code => $ward_data) {
        $formatted[] = array(
            'id'   => $ward_code,
            'text' => $ward_data['name']
        );
    }
    
    wp_send_json_success($formatted);
}

/**
 * Validate Store Settings before save
 */
add_action('woocommerce_settings_save_general', 'vqcheckout_validate_store_settings');

function vqcheckout_validate_store_settings() {
    // Get posted values
    $country = isset($_POST['woocommerce_default_country']) ? sanitize_text_field($_POST['woocommerce_default_country']) : '';
    $ward = isset($_POST['woocommerce_store_city']) ? sanitize_text_field($_POST['woocommerce_store_city']) : '';
    
    if (empty($country) || empty($ward)) {
        return; // Allow empty values
    }
    
    // Parse province code
    $parts = explode(':', $country);
    $province_code = isset($parts[1]) ? $parts[1] : '';
    
    if (empty($province_code)) {
        return;
    }
    
    // Validate ward belongs to province
    if (!vqcheckout_validate_ward_code($province_code, $ward)) {
        WC_Admin_Settings::add_error(__('Phường/Xã không hợp lệ với Tỉnh/Thành đã chọn.', 'vq-checkout'));
        
        // Clear invalid ward value
        $_POST['woocommerce_store_city'] = '';
    }
}
```

---

## IV. ADMIN JAVASCRIPT

### 4.1. File: assets/js/vqcheckout_store_settings.js

```javascript
/**
 * VQ Checkout - Store Settings JavaScript
 * Handle ward loading in WooCommerce Store Settings
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Cache selectors
    var $provinceSelect = $('#woocommerce_default_country');
    var $wardSelect = $('#woocommerce_store_city');
    
    if (!$provinceSelect.length || !$wardSelect.length) {
        return;
    }
    
    // Ensure Select2 is initialized
    if (!$provinceSelect.hasClass('select2-hidden-accessible')) {
        $provinceSelect.select2();
    }
    
    if (!$wardSelect.hasClass('select2-hidden-accessible')) {
        $wardSelect.select2();
    }
    
    /**
     * Load wards when province changes
     */
    $provinceSelect.on('change', function() {
        var selectedValue = $(this).val();
        
        if (!selectedValue) {
            resetWardDropdown();
            return;
        }
        
        // Parse province code from "VN:HANOI" format
        var parts = selectedValue.split(':');
        if (parts.length !== 2 || parts[0] !== 'VN') {
            resetWardDropdown();
            return;
        }
        
        var provinceCode = parts[1];
        loadWards(provinceCode);
    });
    
    /**
     * Load wards via AJAX
     */
    function loadWards(provinceCode) {
        // Show loading state
        $wardSelect.prop('disabled', true);
        $wardSelect.html('<option value="">' + vqcheckoutStoreSettings.strings.loading + '</option>');
        
        // AJAX request
        $.ajax({
            url: vqcheckoutStoreSettings.ajax_url,
            type: 'POST',
            data: {
                action: 'vqcheckout_load_store_wards',
                nonce: vqcheckoutStoreSettings.nonce,
                state: provinceCode
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateWardDropdown(response.data);
                } else {
                    showError();
                }
            },
            error: function() {
                showError();
            }
        });
    }
    
    /**
     * Populate ward dropdown with data
     */
    function populateWardDropdown(wards) {
        var currentValue = $wardSelect.data('current-value') || '';
        
        // Clear existing options
        $wardSelect.empty();
        
        // Add default option
        $wardSelect.append(
            $('<option>', {
                value: '',
                text: vqcheckoutStoreSettings.strings.select_ward
            })
        );
        
        // Add ward options
        if (wards.length > 0) {
            $.each(wards, function(index, ward) {
                var option = $('<option>', {
                    value: ward.id,
                    text: ward.text
                });
                
                // Select current value if exists
                if (ward.id === currentValue) {
                    option.prop('selected', true);
                }
                
                $wardSelect.append(option);
            });
        } else {
            $wardSelect.append(
                $('<option>', {
                    value: '',
                    text: vqcheckoutStoreSettings.strings.no_wards
                })
            );
        }
        
        // Re-initialize Select2
        $wardSelect.prop('disabled', false);
        $wardSelect.trigger('change.select2');
    }
    
    /**
     * Reset ward dropdown to default state
     */
    function resetWardDropdown() {
        $wardSelect.empty();
        $wardSelect.append(
            $('<option>', {
                value: '',
                text: vqcheckoutStoreSettings.strings.select_ward
            })
        );
        $wardSelect.prop('disabled', true);
        $wardSelect.trigger('change.select2');
    }
    
    /**
     * Show error in ward dropdown
     */
    function showError() {
        $wardSelect.empty();
        $wardSelect.append(
            $('<option>', {
                value: '',
                text: vqcheckoutStoreSettings.strings.error
            })
        );
        $wardSelect.prop('disabled', true);
        $wardSelect.trigger('change.select2');
    }
    
    /**
     * Store current ward value for persistence
     */
    if ($wardSelect.val()) {
        $wardSelect.data('current-value', $wardSelect.val());
    }
    
    /**
     * Trigger ward loading on page load if province already selected
     */
    if ($provinceSelect.val()) {
        var selectedValue = $provinceSelect.val();
        var parts = selectedValue.split(':');
        
        if (parts.length === 2 && parts[0] === 'VN') {
            var provinceCode = parts[1];
            
            // Store current ward value before loading
            if ($wardSelect.val()) {
                $wardSelect.data('current-value', $wardSelect.val());
            }
            
            // Load wards
            loadWards(provinceCode);
        }
    }
    
    /**
     * Form validation before submit
     */
    $('form').on('submit', function(e) {
        var provinceValue = $provinceSelect.val();
        var wardValue = $wardSelect.val();
        
        // If province selected but no ward, show warning
        if (provinceValue && !wardValue) {
            var confirmSubmit = confirm('Bạn chưa chọn Phường/Xã. Bạn có chắc muốn lưu?');
            if (!confirmSubmit) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    /**
     * Highlight changes (optional UX enhancement)
     */
    $provinceSelect.on('change', function() {
        $(this).closest('tr').addClass('vqcheckout-changed');
        setTimeout(function() {
            $provinceSelect.closest('tr').removeClass('vqcheckout-changed');
        }, 1000);
    });
    
    $wardSelect.on('change', function() {
        $(this).closest('tr').addClass('vqcheckout-changed');
        setTimeout(function() {
            $wardSelect.closest('tr').removeClass('vqcheckout-changed');
        }, 1000);
    });
});
```

---

## V. ADMIN CSS

### 5.1. File: assets/css/vqcheckout_store_settings.css

```css
/**
 * VQ Checkout - Store Settings Styles
 */

/* Province select styling */
.vqcheckout-province-select {
    min-width: 400px !important;
}

/* Ward select styling */
.vqcheckout-ward-select {
    min-width: 400px !important;
}

/* Loading state */
.vqcheckout-ward-select:disabled {
    background-color: #f5f5f5;
    cursor: not-allowed;
}

/* Highlight changed fields */
.vqcheckout-changed {
    background-color: #ffffcc;
    transition: background-color 0.3s ease;
}

/* Select2 customization for VQ Checkout fields */
.select2-container--default .select2-selection--single.vqcheckout-province-select,
.select2-container--default .select2-selection--single.vqcheckout-ward-select {
    height: 32px;
    line-height: 30px;
}

/* Help tip styling */
.vqcheckout-help-tip {
    color: #999;
    font-size: 14px;
    cursor: help;
}

/* Error state */
.vqcheckout-ward-select option[value=""]:only-child {
    color: #999;
    font-style: italic;
}

/* Success indicator (optional) */
.vqcheckout-ward-select.vqcheckout-valid {
    border-left: 3px solid #7ad03a;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .vqcheckout-province-select,
    .vqcheckout-ward-select {
        min-width: 100% !important;
        max-width: 100% !important;
    }
}

/* Admin notice styling for validation errors */
.notice.vqcheckout-notice {
    border-left-color: #d63638;
}

.notice.vqcheckout-notice p {
    margin: 0.5em 0;
}

/* Loading spinner for ward dropdown */
.vqcheckout-ward-select:disabled::after {
    content: '';
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-left: 8px;
    border: 2px solid #ccc;
    border-top-color: #555;
    border-radius: 50%;
    animation: vqcheckout-spin 0.6s linear infinite;
}

@keyframes vqcheckout-spin {
    to { transform: rotate(360deg); }
}

/* Table row spacing */
.woocommerce table.form-table tr.vqcheckout-field {
    border-top: 1px solid #e5e5e5;
}

/* Enhanced Select2 dropdown styling */
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #2271b1;
}

/* Store address section heading (optional) */
.vqcheckout-section-heading {
    font-weight: 600;
    font-size: 14px;
    color: #1d2327;
    margin: 20px 0 10px;
    padding: 10px 0;
    border-bottom: 1px solid #c3c4c7;
}
```

---

## VI. TESTING

### 6.1. Test Checklist

#### Basic Functionality
- [ ] Navigate to WooCommerce > Settings > General
- [ ] Verify "Quốc gia / Tiểu bang" dropdown shows 34 provinces
- [ ] Select a province (e.g., "Việt Nam — Thành phố Hà Nội")
- [ ] Verify ward dropdown loads via AJAX
- [ ] Verify ward dropdown shows correct wards for selected province
- [ ] Select a ward
- [ ] Click "Save changes"
- [ ] Verify settings saved correctly

#### Data Validation
- [ ] Try saving with province but no ward → Should show warning
- [ ] Try selecting ward from different province → Should show error
- [ ] Reload page → Verify values persisted correctly

#### AJAX Testing
- [ ] Change province → Ward dropdown should reload
- [ ] Change to different province → Ward dropdown should update
- [ ] Network error simulation → Should show error message

#### UI/UX
- [ ] Select2 dropdowns initialized correctly
- [ ] Loading states display properly
- [ ] Error states display properly
- [ ] Responsive on mobile (< 782px)
- [ ] Highlight changes animation works

### 6.2. Test Data

**Test Province:** HANOI  
**Test Wards:**
- 00004 - Phường Ba Đình
- 00634 - Phường Tây Mỗ
- 00637 - Phường Đại Mỗ

**Expected Behavior:**
1. Select "Việt Nam — Thành phố Hà Nội"
2. Ward dropdown loads 126 wards
3. Select "Phường Tây Mỗ"
4. Save
5. Option `woocommerce_default_country` = "VN:HANOI"
6. Option `woocommerce_store_city` = "00634"

### 6.3. Debug Functions

```php
/**
 * Debug: Display Store Settings values
 */
function vqcheckout_debug_store_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $country = get_option('woocommerce_default_country');
    $city = get_option('woocommerce_store_city');
    $address = get_option('woocommerce_store_address');
    $postcode = get_option('woocommerce_store_postcode');
    
    echo '<div class="notice notice-info">';
    echo '<h3>VQ Checkout - Store Settings Debug</h3>';
    echo '<p><strong>Country/State:</strong> ' . esc_html($country) . '</p>';
    
    // Parse province
    $parts = explode(':', $country);
    if (count($parts) === 2) {
        $province_name = vqcheckout_get_province_name($parts[1]);
        echo '<p><strong>Province:</strong> ' . esc_html($province_name) . '</p>';
    }
    
    echo '<p><strong>Ward Code:</strong> ' . esc_html($city) . '</p>';
    
    // Get ward name
    if (!empty($parts[1]) && !empty($city)) {
        $ward_name = vqcheckout_get_ward_name($parts[1], $city);
        echo '<p><strong>Ward Name:</strong> ' . esc_html($ward_name) . '</p>';
    }
    
    echo '<p><strong>Address:</strong> ' . esc_html($address) . '</p>';
    echo '<p><strong>Postcode:</strong> ' . esc_html($postcode) . '</p>';
    echo '</div>';
}
add_action('admin_notices', 'vqcheckout_debug_store_settings');
```

### 6.4. Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| Ward dropdown doesn't load | AJAX endpoint not working | Check nonce, check AJAX URL |
| Wrong wards displayed | Province code not parsed correctly | Verify country value format (VN:CODE) |
| Settings not saving | Validation error | Check error messages in admin notices |
| Select2 not working | Conflict with other plugins | Load Select2 from WooCommerce |
| Page reload clears ward | Value not persisted | Check `data-current-value` attribute |

---

## VII. INTEGRATION WITH OTHER MODULES

### 7.1. Used by Checkout (File 04)

Store settings data is used to pre-fill checkout fields for local customers.

```php
// In checkout fields module
$store_country = get_option('woocommerce_default_country');
$store_ward = get_option('woocommerce_store_city');

// Use for default values or suggestions
```

### 7.2. Used by Shipping Calculator (File 06)

Store location is used to calculate shipping from store to customer.

```php
// Get store address for shipping calculation
$store_province = vqcheckout_get_store_province_code();
$store_ward = get_option('woocommerce_store_city');
```

---

## VIII. CHECKLIST TRIỂN KHAI FILE 03

- [ ] Tạo `inc/vqcheckout_store_settings.php`
- [ ] Implement `vqcheckout_override_general_settings()`
- [ ] Implement custom field generators
- [ ] Implement AJAX handler `vqcheckout_ajax_load_store_wards()`
- [ ] Implement validation `vqcheckout_validate_store_settings()`
- [ ] Tạo `assets/js/vqcheckout_store_settings.js`
- [ ] Implement ward loading logic
- [ ] Implement form validation
- [ ] Tạo `assets/css/vqcheckout_store_settings.css`
- [ ] Test on WC Settings page
- [ ] Test AJAX ward loading
- [ ] Test data persistence
- [ ] Test validation
- [ ] Test responsive design

---

**File tiếp theo:** [File 04: Checkout Fields Customization](./04-Checkout-Fields-Customization.md)

---

**Updated:** 2025-01-20
