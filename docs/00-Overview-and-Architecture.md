# FILE 00: OVERVIEW & ARCHITECTURE (REVISED)

## VQ CHECKOUT FOR WOO v2.0 - TỔNG QUAN & KIẾN TRÚC (ĐIỀU CHỈNH)

---

## 📋 MỤC LỤC

1. [Giới thiệu](#i-giới-thiệu)
2. [Thay đổi Chiến lược](#ii-thay-đổi-chiến-lược)
3. [Kiến trúc Hệ thống Mới](#iii-kiến-trúc-hệ-thống-mới)
4. [Cấu trúc Thư mục](#iv-cấu-trúc-thư-mục)
5. [Workflow](#v-workflow)
6. [Database & Storage](#vi-database--storage)
7. [Danh mục 8 Files](#vii-danh-mục-8-files)

---

## I. GIỚI THIỆU

### 1.1. Về Plugin

**VQ Checkout for Woo v2.0** - Plugin WordPress tối ưu hóa checkout WooCommerce cho thị trường Việt Nam.

**Tính năng Chính (Điều chỉnh):**
- ✅ Địa chỉ **2 cấp Việt Nam** (34 tỉnh/thành + 3,321 xã/phường)
- ✅ **Tích hợp WooCommerce Shipping Zones** (không custom zone manager)
- ✅ **Custom Shipping Method** với ward-specific rates
- ✅ **2 phương thức tính phí**: Fixed (mặc định), By Order Total (conditions)
- ✅ **Ward-specific fees** trong bảng động
- ✅ **Settings Page** đầy đủ (Checkout, General, Anti-spam)
- ✅ **AJAX loading** mượt mà
- ✅ **Auto-fill, Anti-spam** tích hợp
- ✅ **Override WC Store Settings** với địa chỉ VN

### 1.2. Yêu cầu Hệ thống

- WordPress: **5.0+**
- WooCommerce: **5.0+**
- PHP: **7.4+**
- MySQL: **5.7+**

---

## II. THAY ĐỔI CHIẾN LƯỢC

### 2.1. Quyết định Chiến lược

**❌ LOẠI BỎ:**
- Custom Zone Manager (quản lý zones độc lập)
- Tính phí theo Weight (By Weight)
- Tính phí theo Dimension (By Dimension)
- Priority-based zone matching
- Zone scope (specific provinces)

**✅ SỬ DỤNG:**
- **WooCommerce Shipping Zones** chuẩn
- **WooCommerce Shipping Methods API**
- Custom Shipping Method class
- Ward-specific rates table
- Global conditions (by order total)

### 2.2. Lợi ích

| Lợi ích | Mô tả |
|---------|-------|
| **Giảm độ phức tạp** | Không cần xây dựng zone manager riêng |
| **Tương thích** | Sử dụng API chuẩn của WooCommerce |
| **Bảo trì dễ** | Ít custom code, ít conflict |
| **Update-proof** | Ít bị ảnh hưởng khi WC update |
| **UX tốt** | Admin quen thuộc với WC Zones UI |

### 2.3. Trade-offs

| Trade-off | Giải pháp |
|-----------|-----------|
| Mất khả năng priority zones | Dùng zone regions của WC (tỉnh/thành) |
| Không có global scope | Admin tạo nhiều zones nếu cần |
| Phí phức tạp hơn trong 1 zone | UI bảng động để quản lý ward rates |

---

## III. KIẾN TRÚC HỆ THỐNG MỚI

### 3.1. Sơ đồ Tổng quan

```
┌───────────────────────────────────────────────────┐
│               WORDPRESS CORE                       │
└────────────────┬──────────────────────────────────┘
                 │
┌────────────────▼──────────────────────────────────┐
│              WOOCOMMERCE                           │
│  • Cart System                                     │
│  • Checkout Process                                │
│  • Shipping Zones (REGIONS)                        │ ◄─── SỬ DỤNG
│  • Shipping Methods API                            │ ◄─── TÍCH HỢP
│  • Settings API                                    │
└────────────────┬──────────────────────────────────┘
                 │
┌────────────────▼──────────────────────────────────┐
│          VQ CHECKOUT PLUGIN                        │
│                                                     │
│  ┌──────────────────────────────────────────┐    │
│  │ LAYER 1: CORE & DATA (Files 01-02)      │    │
│  │  • JSON data management (2-level)        │    │
│  │  • Core functions                        │    │
│  │  • Utilities                             │    │
│  │  • AJAX handlers (load wards)            │    │
│  │  • Caching (Transients)                  │    │
│  └──────────────────────────────────────────┘    │
│                                                     │
│  ┌──────────────────────────────────────────┐    │
│  │ LAYER 2: ADDRESS (Files 03-04)          │    │
│  │  • Store Settings override               │    │
│  │  • Checkout fields customization         │    │
│  │  • AJAX address loading                  │    │
│  │  • Validation (SĐT, Email optional)      │    │
│  │  • Hide/Show fields (Settings-driven)    │    │
│  └──────────────────────────────────────────┘    │
│                                                     │
│  ┌──────────────────────────────────────────┐    │
│  │ LAYER 3: SHIPPING (Files 05-06)         │ ◄── THAY ĐỔI LỚN
│  │  • Custom Shipping Method class          │    │
│  │    - VQ_Ward_Shipping_Method             │    │
│  │  • Admin UI (Dynamic Table)              │    │
│  │    - Ward-specific rates table           │    │
│  │    - Global conditions (order total)     │    │
│  │    - Select2 integration                 │    │
│  │  • Shipping Calculator                   │    │
│  │    - Ward-specific priority              │    │
│  │    - Default cost fallback               │    │
│  │    - Apply conditions (free ship)        │    │
│  │    - Handling fee                        │    │
│  └──────────────────────────────────────────┘    │
│                                                     │
│  ┌──────────────────────────────────────────┐    │
│  │ LAYER 4: ENHANCEMENTS (File 07)         │ ◄── MỞ RỘNG
│  │  • Settings Page (3 tabs)                │    │
│  │    - Checkout Fields tab                 │    │
│  │    - General & Shipping tab              │    │
│  │    - Anti-Spam tab                       │    │
│  │  • Admin order display                   │    │
│  │  • Auto-fill                             │    │
│  │  • Anti-spam                             │    │
│  │  • Frontend assets (JS/CSS)              │    │
│  └──────────────────────────────────────────┘    │
└─────────────────────────────────────────────────┘
                 │
┌────────────────▼──────────────────────────────────┐
│              DATA LAYER                            │
│  • vietnam_provinces.json (34 tỉnh)               │
│  • vietnam_wards.json (3,321 xã/phường)           │
│  • WC Shipping Zones (WC native)                  │
│  • Method Instance Settings (WC native)           │
│  • vqcheckout_settings (WP Option)                │
└────────────────────────────────────────────────────┘
```

### 3.2. Custom Shipping Method Flow

```
Admin tạo WC Shipping Zone
    ↓
Thêm VQ Ward Shipping Method vào Zone
    ↓
Cấu hình Method:
  • Method Title
  • Handling Fee
  • Default Cost (phí mặc định)
  • Global Conditions (theo order total)
  • Ward-Specific Rates Table (bảng động)
    ↓
Lưu settings vào method instance
    ↓
────────────────────────────────────
User checkout:
    ↓
Chọn Province (state)
    ↓
AJAX load Wards (city)
    ↓
Chọn Ward
    ↓
WC calculate_shipping() trigger
    ↓
VQ_Ward_Shipping_Method::calculate_shipping()
    ↓
Lấy ward code từ package['destination']['city']
    ↓
IF ward có trong Ward-Specific Rates:
  Use ward-specific cost
ELSE:
  Use Default Cost
    ↓
Apply Global Conditions:
  IF order_total matches condition:
    Adjust cost (e.g. free ship)
    ↓
Add Handling Fee
    ↓
Return rate via $this->add_rate()
    ↓
Display in checkout
```

---

## IV. CẤU TRÚC THƯ MỤC

```
vq-checkout-for-woo/
│
├── VQ-woo-checkout.php                 # Main plugin file
│
├── inc/                                # PHP Modules
│   ├── vqcheckout_core.php            # Core functions (File 02)
│   ├── vqcheckout_utils.php           # Utilities (File 02)
│   ├── vqcheckout_ajax.php            # AJAX handlers (File 02)
│   │
│   ├── vqcheckout_store_settings.php  # Override WC Settings (File 03)
│   ├── vqcheckout_checkout_fields.php # Checkout customization (File 04)
│   │
│   ├── class-vq-ward-shipping-method.php  # Custom Shipping Method (File 05) ◄── MỚI
│   ├── vqcheckout_shipping_calculator.php # Shipping logic (File 06) ◄── ĐƠN GIẢN HÓA
│   │
│   ├── vqcheckout_settings_page.php   # Settings Page (File 07) ◄── MỚI
│   ├── vqcheckout_admin_order.php     # Admin order display (File 07)
│   ├── vqcheckout_autofill.php        # Auto-fill (File 07)
│   └── vqcheckout_anti_spam.php       # Anti-spam (File 07)
│
├── assets/                             # Assets (File 07)
│   ├── css/
│   │   ├── vqcheckout_admin.css
│   │   ├── vqcheckout_frontend.css
│   │   └── vqcheckout_settings.css    ◄── MỚI
│   └── js/
│       ├── vqcheckout_admin.js
│       ├── vqcheckout_frontend.js
│       ├── vqcheckout_shipping_method.js  ◄── MỚI (Dynamic table)
│       └── vqcheckout_settings.js     ◄── MỚI
│
├── data/                               # JSON data (File 01)
│   ├── vietnam_provinces.json         # 34 tỉnh/thành
│   └── vietnam_wards.json             # 3,321 xã/phường
│
└── readme.txt                          # WordPress plugin description
```

---

## V. WORKFLOW

### 5.1. Admin Setup Shipping

```
1. WooCommerce → Settings → Shipping
    ↓
2. Click vào Zone (hoặc tạo mới)
    ↓
3. Add Regions:
   - Chọn các Tỉnh/Thành (VD: Hà Nội, TP.HCM)
    ↓
4. Add Shipping Method:
   - Chọn "Phí vận chuyển tới Xã/Phường" (VQ Ward Shipping)
    ↓
5. Cấu hình Method:
   ┌─────────────────────────────────────┐
   │ Method Title: "Giao hàng tiêu chuẩn"│
   │ Handling Fee: 0                      │
   │ Default Cost: 30000                  │
   ├─────────────────────────────────────┤
   │ Global Conditions:                   │
   │  [Order Total ≥ 500000] → [Cost: 0] │
   │  [Add Rule]                          │
   ├─────────────────────────────────────┤
   │ Ward-Specific Rates:                 │
   │  Ward(s)           | Cost            │
   │  ─────────────────┼─────────────────│
   │  [Hoàn Kiếm▼]     | 25000           │
   │  [Ba Đình▼]       | 25000           │
   │  [Cầu Giấy▼]      | 20000           │
   │  [+ Add Row]                         │
   └─────────────────────────────────────┘
    ↓
6. Save changes
```

**Lưu ý quan trọng:**
- Dropdown Ward chỉ hiển thị các xã/phường thuộc **Regions đã chọn** trong Zone
- Dùng Select2 Multiple để chọn nhiều wards cùng lúc
- Bảng động cho phép thêm/xóa hàng

### 5.2. Shipping Calculation Logic

```
User checkout với:
  - Province: Hà Nội
  - Ward: Hoàn Kiếm
  - Order Total: 400,000 VNĐ
    ↓
WC tìm Zone match: "Miền Bắc" (có HN)
    ↓
Method: VQ Ward Shipping
    ↓
calculate_shipping() trigger:
    ↓
1. Get ward: "Hoàn Kiếm"
    ↓
2. Check Ward-Specific Rates:
   ✓ "Hoàn Kiếm" → 25,000 VNĐ
    ↓
3. Check Global Conditions:
   ✗ Order Total (400k) < 500k
   → Không áp dụng free ship
    ↓
4. Final Cost:
   25,000 + Handling Fee (0) = 25,000 VNĐ
    ↓
5. add_rate():
   {
     id: 'vq_ward_shipping',
     label: 'Giao hàng tiêu chuẩn',
     cost: 25000
   }
```

---

## VI. DATABASE & STORAGE

### 6.1. WordPress Options

| Option Name | Type | Size | Purpose |
|-------------|------|------|---------|
| `woocommerce_shipping_zones` | Array | - | WC Zones (native) |
| `woocommerce_{method_instance_id}_settings` | Array | ~10-50 KB | Method settings (native) |
| `vqcheckout_settings` | Array | ~5 KB | Plugin settings (NEW) |
| `woocommerce_default_country` | String | - | Store province (overridden) |
| `woocommerce_store_city` | String | - | Store ward (overridden) |

### 6.2. Method Instance Settings Structure

```php
array(
    'title' => 'Giao hàng tiêu chuẩn',
    'handling_fee' => '0',
    'default_cost' => '30000',
    
    // Global conditions (serialized)
    'global_conditions' => array(
        array(
            'min_order_total' => '500000',
            'cost' => '0' // Free ship
        ),
        array(
            'min_order_total' => '1000000',
            'cost' => '0'
        )
    ),
    
    // Ward-specific rates (serialized)
    'ward_rates' => array(
        array(
            'wards' => array('10101', '10102'), // Ward codes
            'cost' => '25000'
        ),
        array(
            'wards' => array('10103'),
            'cost' => '20000'
        )
    )
)
```

### 6.3. Plugin Settings Structure (vqcheckout_settings)

```php
array(
    // Checkout Fields Settings
    'checkout' => array(
        'validate_phone' => 'yes', // Validate SĐT VN
        'add_salutation' => 'yes', // Thêm trường "Anh/Chị"
        'hide_postcode' => 'yes',
        'email_optional' => 'no',
        'address_loading' => 'ajax' // ajax | on_page_load
    ),
    
    // General & Shipping Settings
    'general' => array(
        'currency_symbol' => 'vnd', // ₫ → VNĐ
        'price_format' => 'short', // 18k, 1tr200
        'hide_shipping_title' => 'no',
        'hide_other_when_free' => 'yes' // Ẩn methods khác khi có Free Ship
    ),
    
    // Anti-Spam Settings
    'antispam' => array(
        'recaptcha_enabled' => 'no',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'ip_blocking' => '',
        'keyword_blocking' => '',
        'rate_limit' => '5' // orders per hour per IP
    )
)
```

### 6.4. Transients (Cache)

| Transient | Expiry | Purpose |
|-----------|--------|---------|
| `vqcheckout_provinces` | 1 day | Cache provinces |
| `vqcheckout_wards_{STATE}` | 1 day | Cache wards by province |

---

## VII. DANH MỤC 8 FILES

### ✅ File 00: Overview & Architecture (FILE NÀY)
Tổng quan hệ thống, kiến trúc điều chỉnh, workflows mới

---

### 📝 File 01: Data Structure & JSON (GIỮ NGUYÊN)
- Format JSON provinces/wards (2-level)
- Mapping với WC fields
- Utils functions
- AJAX load wards endpoint
- Caching strategy

---

### 📝 File 02: Core & Utils (ĐIỀU CHỈNH NHẸ)
- Main plugin file `VQ-woo-checkout.php`
- Core functions `vqcheckout_core.php`
- Utilities `vqcheckout_utils.php`
- Base AJAX handlers `vqcheckout_ajax.php`
- **Điều chỉnh**: Include class shipping method

---

### 📝 File 03: Store Settings Integration (GIỮ NGUYÊN)
- Module `vqcheckout_store_settings.php`
- Override WooCommerce Store Settings
- Admin AJAX ward dropdown
- Admin JS/CSS

---

### 📝 File 04: Checkout Fields Customization (MỞ RỘNG)
- Module `vqcheckout_checkout_fields.php`
- Custom checkout fields (2-level)
- Frontend AJAX ward loading
- **Mở rộng**: Validation hooks (SĐT, Email)
- **Mở rộng**: Hide/Show fields (Settings-driven)
- **Mở rộng**: Salutation field (Anh/Chị)
- Frontend JS/CSS

---

### 📝 File 05: Custom Shipping Method - UI (MỚI/THAY THẾ)
- Class `class-vq-ward-shipping-method.php`
- Extend `WC_Shipping_Method`
- Register method via `woocommerce_shipping_methods`
- **UI cấu hình**:
  - Basic settings (Title, Handling Fee, Default Cost)
  - Global Conditions table (by order total)
  - **Ward-Specific Rates table** (Dynamic Repeater)
- Admin JS: Dynamic table, Select2 integration
- Admin CSS: Table styling

---

### 📝 File 06: Shipping Calculator Logic (MỚI/ĐƠN GIẢN HÓA)
- Implement `calculate_shipping()` method
- **Logic ưu tiên**:
  1. Check ward-specific rates
  2. Fallback to default cost
  3. Apply global conditions (order total)
  4. Add handling fee
- Return rate via `$this->add_rate()`
- **Loại bỏ**: Weight/Dimension calculations

---

### 📝 File 07: Settings Page & Additional Modules (MỞ RỘNG)
- **Settings Page** `vqcheckout_settings_page.php`:
  - Tab 1: Checkout Fields (Validation, Salutation, Hide fields)
  - Tab 2: General & Shipping (Currency, Price format, Free ship)
  - Tab 3: Anti-Spam (reCAPTCHA, IP/Keyword blocking, Rate limit)
- Admin order display `vqcheckout_admin_order.php`
- Auto-fill `vqcheckout_autofill.php`
- Anti-spam `vqcheckout_anti_spam.php`
- Complete JS files
- Complete CSS files
- Asset loading strategy

---

## VIII. KEY FEATURES RECAP

### 8.1. Địa chỉ 2 cấp Việt Nam
- **34 tỉnh/thành phố** (state)
- **3,321 xã/phường/thị trấn** (city)
- **KHÔNG có cấp Quận/Huyện**
- AJAX loading động
- Caching (Transients)

### 8.2. Tích hợp WooCommerce Shipping
- **Sử dụng WC Shipping Zones** (không custom zone manager)
- **Custom Shipping Method** class
- **Ward-specific rates** trong bảng động
- **Global conditions** (theo order total)
- **2 phương thức tính phí**:
  1. Fixed (default cost hoặc ward-specific)
  2. By Order Total (conditions)

### 8.3. Settings Page Đầy đủ
- **Checkout Fields**: Validation, Salutation, Hide/Show fields
- **General & Shipping**: Currency, Price format, Free ship behavior
- **Anti-Spam**: reCAPTCHA, IP/Keyword blocking, Rate limiting

### 8.4. Additional Features
- **Admin order display**: Hiển thị địa chỉ 2 cấp đẹp
- **Auto-fill**: Tự động điền thông tin khách cũ (theo SĐT)
- **Anti-spam**: Chặn order spam

---

## IX. TESTING CHECKLIST

### Phase 1: Data & Core
- [ ] Load provinces dropdown (admin & frontend)
- [ ] AJAX load wards
- [ ] Caching works (Transients)
- [ ] Store Settings override

### Phase 2: Checkout
- [ ] Checkout fields display (2-level)
- [ ] AJAX ward loading in checkout
- [ ] Validation (SĐT, Email)
- [ ] Hide/Show fields (Settings-driven)
- [ ] Salutation field

### Phase 3: Shipping Method
- [ ] Method registered in WC
- [ ] Method appears in Zone settings
- [ ] Admin UI displays correctly
- [ ] Dynamic table works (Add/Remove rows)
- [ ] Select2 loads wards (filtered by Zone regions)
- [ ] Settings save/load correctly

### Phase 4: Shipping Calculation
- [ ] Ward-specific cost applies
- [ ] Default cost fallback works
- [ ] Global conditions apply (order total)
- [ ] Handling fee adds
- [ ] Free ship works (when condition met)
- [ ] Rate displays in checkout

### Phase 5: Settings & Enhancements
- [ ] Settings page loads (3 tabs)
- [ ] All settings save/load
- [ ] Admin order displays address
- [ ] Auto-fill works (SĐT)
- [ ] Anti-spam blocks (IP, Keywords, reCAPTCHA)

---

## X. DEVELOPMENT PRIORITY

### High Priority (Core functionality)
1. File 01: Data Structure & JSON
2. File 02: Core & Utils
3. File 04: Checkout Fields
4. File 05: Custom Shipping Method UI
5. File 06: Shipping Calculator Logic

### Medium Priority (Admin features)
6. File 03: Store Settings Integration
7. File 07: Settings Page (Tab 1-2)

### Low Priority (Enhancements)
8. File 07: Auto-fill
9. File 07: Anti-spam
10. File 07: Admin order display

---

**Các file tiếp theo:**
- [File 01: Data Structure & JSON](./01-Data-Structure-JSON-REVISED.md) (Giữ nguyên)
- [File 02: Core & Utils](./02-Core-Utils-REVISED.md) (Điều chỉnh nhẹ)
- [File 03: Store Settings Integration](./03-Store-Settings-REVISED.md) (Giữ nguyên)
- [File 04: Checkout Fields Customization](./04-Checkout-Fields-REVISED.md) (Mở rộng)
- [File 05: Custom Shipping Method UI](./05-Custom-Shipping-Method-REVISED.md) (Mới/Thay thế)
- [File 06: Shipping Calculator Logic](./06-Shipping-Calculator-REVISED.md) (Mới/Đơn giản hóa)
- [File 07: Settings Page & Additional Modules](./07-Settings-Enhancements-REVISED.md) (Mở rộng)

---

**Version:** 2.0.0-REVISED  
**Last Updated:** 2025-11-01
**Strategy:** Integrate deeply with WooCommerce Shipping API