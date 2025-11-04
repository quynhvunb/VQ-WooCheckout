# FILE 01: DATA STRUCTURE & JSON (GIỮ NGUYÊN - KHÔNG THAY ĐỔI)

## VIETNAM ADDRESS DATA - 2 CẤP

---

## I. TỔNG QUAN

File này **GIỮ NGUYÊN 100%** so với kế hoạch ban đầu vì:
- Dữ liệu địa chỉ 2 cấp không thay đổi
- Mapping với WC fields vẫn đúng
- AJAX loading logic không đổi
- Caching strategy vẫn hiệu quả

**Nội dung chi tiết giống hệt File 01 ban đầu.**

---

## II. CẤU TRÚC DỮ LIỆU

### 2.1. Tỉnh/Thành Phố (Provinces)

**File:** `data/vietnam_provinces.json`

```json
[
  {
    "code": "01",
    "name": "Hà Nội",
    "name_with_type": "Thành phố Hà Nội",
    "slug": "ha-noi",
    "type": "thanh-pho"
  },
  {
    "code": "79",
    "name": "TP. Hồ Chí Minh",
    "name_with_type": "Thành phố Hồ Chí Minh",
    "slug": "tp-ho-chi-minh",
    "type": "thanh-pho"
  }
]
```

**Tổng cộng:** 34 items

### 2.2. Xã/Phường (Wards)

**File:** `data/vietnam_wards.json`

```json
[
  {
    "code": "00001",
    "name": "Phúc Xá",
    "name_with_type": "Phường Phúc Xá",
    "parent_code": "001",
    "path": "Phúc Xá, Ba Đình, Hà Nội",
    "path_with_type": "Phường Phúc Xá, Quận Ba Đình, Thành phố Hà Nội"
  }
]
```

**Tổng cộng:** 3,321 items

---

## III. MAPPING VỚI WOOCOMMERCE

| VN Address | WC Field | Display In |
|------------|----------|-----------|
| Tỉnh/Thành | `billing_state` | Checkout, Store Settings |
| Xã/Phường | `billing_city` | Checkout, Store Settings |
| ~~Quận/Huyện~~ | ~~N/A~~ | **KHÔNG SỬ DỤNG** |

**Lưu ý quan trọng:**
- `billing_country` luôn = `VN`
- `billing_address_1` = Địa chỉ chi tiết (Số nhà, Đường)
- `billing_postcode` = Có thể ẩn (tùy settings)

---

## IV. UTILS FUNCTIONS

### 4.1. vqcheckout_get_provinces()

```php
/**
 * Lấy danh sách tỉnh/thành
 * 
 * @return array Mảng provinces với code làm key
 */
function vqcheckout_get_provinces() {
    // Check cache
    $cached = get_transient('vqcheckout_provinces');
    if ($cached !== false) {
        return $cached;
    }
    
    // Load JSON
    $file = VQCHECKOUT_PLUGIN_DIR . 'data/vietnam_provinces.json';
    if (!file_exists($file)) {
        return array();
    }
    
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    
    // Convert to key-value array
    $provinces = array();
    foreach ($data as $province) {
        $provinces[$province['code']] = $province['name'];
    }
    
    // Cache for 1 day
    set_transient('vqcheckout_provinces', $provinces, DAY_IN_SECONDS);
    
    return $provinces;
}
```

### 4.2. vqcheckout_get_wards()

```php
/**
 * Lấy danh sách xã/phường theo tỉnh
 * 
 * @param string $province_code Mã tỉnh/thành
 * @return array Mảng wards với code làm key
 */
function vqcheckout_get_wards($province_code = '') {
    if (empty($province_code)) {
        return array();
    }
    
    // Check cache
    $cache_key = 'vqcheckout_wards_' . $province_code;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached;
    }
    
    // Load JSON
    $file = VQCHECKOUT_PLUGIN_DIR . 'data/vietnam_wards.json';
    if (!file_exists($file)) {
        return array();
    }
    
    $json = file_get_contents($file);
    $all_wards = json_decode($json, true);
    
    // Filter by province
    $wards = array();
    foreach ($all_wards as $ward) {
        // Match by parent_code (first 2 digits)
        if (substr($ward['parent_code'], 0, 2) === $province_code) {
            $wards[$ward['code']] = $ward['name'];
        }
    }
    
    // Cache for 1 day
    set_transient($cache_key, $wards, DAY_IN_SECONDS);
    
    return $wards;
}
```

---

## V. AJAX ENDPOINT

### 5.1. Load Wards by Province

**File:** `inc/vqcheckout_ajax.php`

```php
/**
 * AJAX: Load wards by province
 */
add_action('wp_ajax_vqcheckout_load_wards', 'vqcheckout_ajax_load_wards');
add_action('wp_ajax_nopriv_vqcheckout_load_wards', 'vqcheckout_ajax_load_wards');

function vqcheckout_ajax_load_wards() {
    // Verify nonce
    check_ajax_referer('vqcheckout_nonce', 'security');
    
    // Get province code
    $province_code = isset($_POST['province_code']) ? sanitize_text_field($_POST['province_code']) : '';
    
    if (empty($province_code)) {
        wp_send_json_error('Invalid province code');
    }
    
    // Get wards
    $wards = vqcheckout_get_wards($province_code);
    
    // Format for Select2
    $formatted = array();
    foreach ($wards as $code => $name) {
        $formatted[] = array(
            'id' => $code,
            'text' => $name
        );
    }
    
    wp_send_json_success($formatted);
}
```

### 5.2. Frontend JS Call

```javascript
// File: assets/js/vqcheckout_frontend.js

jQuery(document).ready(function($) {
    // Listen to province change
    $(document.body).on('change', '#billing_state', function() {
        var provinceCode = $(this).val();
        
        if (!provinceCode) {
            $('#billing_city').html('<option value="">Chọn xã/phường</option>');
            return;
        }
        
        // Show loading
        $('#billing_city').html('<option value="">Đang tải...</option>').prop('disabled', true);
        
        // AJAX load wards
        $.ajax({
            url: vqcheckout_params.ajax_url,
            type: 'POST',
            data: {
                action: 'vqcheckout_load_wards',
                security: vqcheckout_params.nonce,
                province_code: provinceCode
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Chọn xã/phường</option>';
                    $.each(response.data, function(i, ward) {
                        options += '<option value="' + ward.id + '">' + ward.text + '</option>';
                    });
                    $('#billing_city').html(options).prop('disabled', false);
                } else {
                    alert('Lỗi tải danh sách xã/phường');
                }
            },
            error: function() {
                alert('Lỗi kết nối');
            }
        });
    });
});
```

---

## VI. CACHING STRATEGY

### 6.1. Transients Usage

| Data | Transient Key | Expiry | Invalidate When |
|------|---------------|--------|-----------------|
| Provinces | `vqcheckout_provinces` | 1 day | Plugin update |
| Wards (per province) | `vqcheckout_wards_{CODE}` | 1 day | Plugin update |

### 6.2. Clear Cache Function

```php
/**
 * Clear all VQ Checkout caches
 */
function vqcheckout_clear_cache() {
    // Clear provinces
    delete_transient('vqcheckout_provinces');
    
    // Clear all wards transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_vqcheckout_wards_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_vqcheckout_wards_%'");
}

// Clear cache on plugin activation
register_activation_hook(VQCHECKOUT_PLUGIN_FILE, 'vqcheckout_clear_cache');
```

---

## VII. TESTING CHECKLIST

- [ ] JSON files load correctly
- [ ] Provinces cache works (1 day)
- [ ] Wards cache works per province
- [ ] AJAX endpoint returns correct wards
- [ ] Frontend dropdown populates
- [ ] Cache clears on plugin activation

---

**Phụ lục:**
- [Danh sách 34 tỉnh/thành đầy đủ](./appendix-provinces.json)
- [Danh sách 3,321 xã/phường đầy đủ](./appendix-wards.json)

---

**Version:** 2.0.0-REVISED  
**Status:** ✅ GIỮ NGUYÊN 100% - KHÔNG THAY ĐỔI