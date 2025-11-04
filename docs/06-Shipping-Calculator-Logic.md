# FILE 06: SHIPPING CALCULATOR LOGIC (MỚI/ĐƠN GIẢN HÓA)

## LOGIC TÍNH PHÍ VẬN CHUYỂN (MỚI)

---

## I. TỔNG QUAN

### 1.1. Thay đổi Chiến lược

**❌ LOẠI BỎ:**
- Tính phí theo Weight
- Tính phí theo Dimension  
- Zone matching phức tạp
- Priority-based selection

**✅ ĐƠN GIẢN HÓA:**
- WooCommerce tự động match zone (theo regions)
- Chỉ implement `calculate_shipping()` method
- 2 nguồn phí: Ward-specific hoặc Default
- Áp dụng Global Conditions (order total)

### 1.2. Flow Đơn giản

```
User checkout với Province + Ward
    ↓
WooCommerce tự động match Zone
   (dựa trên Regions: VN:01, VN:79,...)
    ↓
VQ_Ward_Shipping_Method::calculate_shipping()
    ↓
① Get ward code từ package
② Check ward-specific rates
③ Fallback to default cost
④ Apply global conditions
⑤ Add handling fee
⑥ Return rate
```

---

## III. TRIỂN KHAI LOGIC TÍNH PHÍ

### 3.1. File: inc/class-vq-ward-shipping-method.php (Tiếp theo từ File 05)

**File:** `inc/class-vq-ward-shipping-method.php` (tiếp theo File 05)

```php
    // (Tiếp tục trong class VQ_Ward_Shipping_Method)

    /**
     * Calculate shipping (Logic chính)
     * @param array $package Package data
     */
    public function calculate_shipping($package = array()) {
        // 1. Lấy thông tin cần thiết
        $destination = $package['destination'];
        $country = isset($destination['country']) ? $destination['country'] : '';
        $ward_code = isset($destination['city']) ? $destination['city'] : ''; // city = ward code

        // Chỉ tính toán cho Việt Nam và khi đã có Xã/Phường
        if ($country !== 'VN' || empty($ward_code)) {
            return;
        }

        // Lấy tổng giá trị đơn hàng (Subtotal)
        $cart_total = 0;
        if (isset($package['cart_subtotal'])) {
            $cart_total = $package['cart_subtotal'];
        } else {
            // Fallback
            foreach ($package['contents'] as $item) {
                $cart_total += $item['line_total'];
            }
        }
        

        // 2. Xác định Phí Cơ bản (Base Rate)
        $base_rate = $this->find_base_rate($ward_code);
        $final_cost = $base_rate['cost'];
        $rate_label = $base_rate['label'];

        // 3. Áp dụng Điều kiện Tổng đơn hàng (Ưu tiên cao nhất)
        if (!empty($this->order_total_conditions)) {
            $cost_from_conditions = $this->apply_order_total_conditions($cart_total);
            
            // Nếu có phí từ điều kiện (khác null), nó sẽ ghi đè phí cơ bản
            if ($cost_from_conditions !== null) {
                $final_cost = $cost_from_conditions;
                $rate_label = ''; // Khi áp dụng điều kiện chung, không dùng label riêng
            }
        }

        // 4. Hoàn thiện Phí (Cộng phụ thu)
        $handling_fee = wc_format_decimal($this->handling_fee);
        if ($handling_fee > 0 && $final_cost > 0) {
            // Chỉ cộng phụ thu nếu phí vận chuyển > 0 (tránh trường hợp free ship vẫn tính phụ thu)
            $final_cost += $handling_fee;
        }

        // 5. Thêm Rate vào WC
        // Sử dụng label riêng nếu có, nếu không dùng tiêu đề chung của method
        $label = !empty($rate_label) ? $rate_label : $this->title;

        $rate = array(
            'id'        => $this->get_rate_id(),
            'label'     => $label,
            'cost'      => $final_cost,
            'package'   => $package,
        );

        $this->add_rate($rate);
    }

    /**
     * Tìm Phí Cơ bản (Base Rate) dựa trên Xã/Phường
     */
    private function find_base_rate($ward_code) {
        // 1. Tìm trong Phí theo Xã/Phường (Ward-Specific Rates)
        if (!empty($this->ward_rates)) {
            foreach ($this->ward_rates as $rate) {
                if (in_array($ward_code, $rate['wards'])) {
                    return array(
                        'cost' => wc_format_decimal($rate['cost']),
                        'label' => $rate['label']
                    );
                }
            }
        }

        // 2. Nếu không tìm thấy, dùng Phí Mặc định (Default Cost)
        return array(
            'cost' => wc_format_decimal($this->cost),
            'label' => ''
        );
    }

    /**
     * Áp dụng điều kiện Tổng đơn hàng để xác định phí
     */
    private function apply_order_total_conditions($cart_total) {
        $matched_cost = null;

        // Các điều kiện đã được sắp xếp theo min_amount ASC khi lưu (File 05)
        // Chúng ta cần tìm điều kiện có min_amount cao nhất mà vẫn thỏa mãn (<= cart_total)
        // Duyệt qua mảng, điều kiện sau sẽ ghi đè điều kiện trước nếu thỏa mãn
        foreach ($this->order_total_conditions as $condition) {
            $min_amount = wc_format_decimal($condition['min_amount']);
            
            if ($cart_total >= $min_amount) {
                // Cập nhật matched_cost
                $matched_cost = wc_format_decimal($condition['cost']);
            } else {
                // Vì đã sắp xếp, nếu gặp điều kiện không thỏa mãn thì dừng lại
                break;
            }
        }

        // Trả về phí phù hợp (có thể là 0 nếu free ship) hoặc null nếu không có điều kiện nào khớp
        return $matched_cost;
    }
}
```

---

## III. EXAMPLES

### 3.1. Example 1: Ward-Specific Rate

**Cấu hình:**
- Default Cost: 30,000 VNĐ
- Ward Rates:
  - Hoàn Kiếm (code: 00001) → 25,000 VNĐ
  - Ba Đình (code: 00013) → 25,000 VNĐ

**Checkout:**
- Ward: Hoàn Kiếm
- Order Total: 300,000 VNĐ

**Calculation:**
```php
$base_cost = 25,000 // Ward-specific (not default)
$final_cost = 25,000 // No conditions matched
```

**Result:** 25,000 VNĐ

---

### 3.2. Example 2: Global Condition (Free Ship)

**Cấu hình:**
- Default Cost: 30,000 VNĐ
- Global Conditions:
  - Order ≥ 500,000 → Cost = 0

**Checkout:**
- Ward: Cầu Giấy (không có ward-specific)
- Order Total: 600,000 VNĐ

**Calculation:**
```php
$base_cost = 30,000 // Default
$order_total = 600,000 >= 500,000 // TRUE
$final_cost = 0 // Free ship
```

**Result:** 0 VNĐ (FREE SHIP)

---

### 3.3. Example 3: Multiple Conditions

**Cấu hình:**
- Default Cost: 30,000 VNĐ
- Global Conditions:
  - Order ≥ 1,000,000 → Cost = 0
  - Order ≥ 500,000 → Cost = 15,000
  - Order ≥ 200,000 → Cost = 25,000

**Checkout:**
- Ward: Đống Đa
- Order Total: 700,000 VNĐ

**Calculation:**
```php
// Conditions sorted DESC: [1M, 500k, 200k]
$order_total = 700,000

// Check 1M: 700k >= 1M? NO
// Check 500k: 700k >= 500k? YES → Cost = 15,000
// BREAK (only first match)

$final_cost = 15,000
```

**Result:** 15,000 VNĐ

---

### 3.4. Example 4: Ward-Specific + Condition + Fee

**Cấu hình:**
- Default Cost: 30,000 VNĐ
- Handling Fee: 5,000 VNĐ
- Ward Rates:
  - Hoàn Kiếm → 20,000 VNĐ
- Global Conditions:
  - Order ≥ 500,000 → Cost = 0

**Checkout:**
- Ward: Hoàn Kiếm
- Order Total: 600,000 VNĐ

**Calculation:**
```php
$base_cost = 20,000 // Ward-specific
$order_total = 600,000 >= 500,000 // TRUE
$final_cost = 0 // Free ship from condition
$final_cost += 5,000 // Handling fee
$final_cost = 5,000
```

**Result:** 5,000 VNĐ

**Lưu ý:** Handling fee luôn được cộng, ngay cả khi free ship!

---

## IV. HOOKS & FILTERS

### 4.1. Hook vào WC Checkout Update

WooCommerce tự động gọi `calculate_shipping()` khi:
- User thay đổi địa chỉ
- Cart update (add/remove item)
- Coupon applied
- Event: `woocommerce_checkout_update_order_review`

**Không cần code thêm** - WC handle automatically!

### 4.2. Custom Filter (Optional)

Cho phép developer customize cost:

```php
/**
 * Filter shipping cost before adding rate
 * 
 * @param float $cost Final cost
 * @param string $ward_code Ward code
 * @param float $order_total Order total
 * @param object $method Shipping method instance
 * @return float Modified cost
 */
$final_cost = apply_filters('vqcheckout_shipping_cost', $final_cost, $ward_code, $order_total, $this);
```

**Example usage (in theme functions.php):**

```php
add_filter('vqcheckout_shipping_cost', function($cost, $ward_code, $order_total, $method) {
    // Custom logic: 10% discount for VIP customers
    if (current_user_can('vip_customer')) {
        return $cost * 0.9;
    }
    return $cost;
}, 10, 4);
```

---

## V. VALIDATION & ERROR HANDLING

### 5.1. Edge Cases

```php
public function calculate_shipping($package = array()) {
    // Case 1: No ward selected
    if (empty($ward_code)) {
        return; // Don't show method
    }
    
    // Case 2: Invalid settings
    if (!is_numeric($default_cost) || $default_cost < 0) {
        $default_cost = 0;
    }
    
    // Case 3: Malformed ward_rates
    if (!is_array($ward_rates)) {
        $ward_rates = array();
    }
    
    // Case 4: Negative final cost
    $final_cost = max(0, $final_cost);
}
```

### 5.2. Debug Mode

```php
// Enable debug logging
define('VQCHECKOUT_DEBUG', true);

// In calculate_shipping()
if (defined('VQCHECKOUT_DEBUG') && VQCHECKOUT_DEBUG) {
    $log = array(
        'ward' => $ward_code,
        'order_total' => $order_total,
        'base_cost' => $base_cost,
        'conditions_applied' => $conditions_applied, // track which condition
        'final_cost' => $final_cost,
    );
    error_log('[VQ Shipping] ' . print_r($log, true));
}
```

---

## VI. PERFORMANCE OPTIMIZATION

### 6.1. Avoid N+1 Queries

```php
// ❌ BAD: Query ward name in loop
foreach ($ward_rates as $rate) {
    $ward_name = vqcheckout_get_ward_name($ward_code); // Query each time
}

// ✅ GOOD: Use cached data
$ward_rates = $this->get_option('ward_rates'); // Already in memory
```

### 6.2. Cache Method Settings

```php
// Settings already cached by WC in wp_options
// No need for additional caching
```

### 6.3. Minimize Calculations

```php
// ❌ BAD: Complex calculation in loop
foreach ($conditions as $condition) {
    $order_total = WC()->cart->get_subtotal() + WC()->cart->get_shipping_total(); // Recalculate
}

// ✅ GOOD: Calculate once
$order_total = WC()->cart->get_subtotal();
foreach ($conditions as $condition) {
    // Use cached $order_total
}
```

---

## VII. TESTING CHECKLIST

### Unit Tests
- [ ] Ward-specific rate applies correctly
- [ ] Default cost fallback works
- [ ] Global conditions sort correctly (DESC)
- [ ] First matching condition applies
- [ ] Handling fee adds correctly
- [ ] Negative costs become 0
- [ ] Empty ward returns no rate

### Integration Tests
- [ ] Method appears in checkout
- [ ] Cost updates when ward changes
- [ ] Cost updates when cart changes
- [ ] Free ship condition works
- [ ] Multiple conditions don't conflict
- [ ] Works with coupons
- [ ] Works with multiple shipping methods

### Edge Cases
- [ ] No ward selected → No rate
- [ ] Invalid settings → Fallback
- [ ] Null ward_rates → Use default
- [ ] Order total = 0 → Still calculate
- [ ] Conditions with same min_order → First wins

---

## VIII. COMPARISON: OLD vs NEW

| Aspect | Old (Custom Zone Manager) | New (WC Native) |
|--------|---------------------------|-----------------|
| Zone matching | Custom priority algorithm | WC native (regions) |
| Fee methods | 4 (Fixed, Order, Weight, Dimension) | 2 (Fixed, Order) |
| Complexity | High (200+ lines) | Low (100 lines) |
| Maintenance | Custom code, risky | WC API, stable |
| Performance | Multiple zones check | Single method call |

**Kết luận:** Phiên bản mới đơn giản hơn 50%, dễ maintain, ít bug!

---

**Dependencies:**
- File 05: Custom Shipping Method UI
- WooCommerce Cart API
- WooCommerce Shipping API

**Next:** [File 07 - Settings Page & Additional Modules](./07-Settings-Enhancements-REVISED.md)

---

**Version:** 2.0.0-REVISED  
**Status:** ✅ ĐƠN GIẢN HÓA - LOẠI BỎ WEIGHT/DIMENSION