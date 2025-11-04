# FILE 05: CUSTOM SHIPPING METHOD - UI (MỚI/THAY THẾ HOÀN TOÀN)

## PHƯƠNG THỨC VẬN CHUYỂN TÙY CHỈNH - GIAO DIỆN CẤU HÌNH (MỚI)

---

## I. GIỚI THIỆU

**Mục tiêu:** Xây dựng UI cấu hình cho Custom Shipping Method (`vq_ward_shipping`) bên trong WooCommerce Shipping Zones.

**Các thành phần UI chính:**
1. Cấu hình cơ bản (Tiêu đề, Phí mặc định, Phụ thu).
2. Bảng điều kiện theo Tổng đơn hàng (Global Conditions).
3. Bảng phí theo Xã/Phường (Ward-Specific Rates).

---

## II. TRIỂN KHAI CLASS VÀ UI FIELDS

### 2.1. File: inc/class-vq-ward-shipping-method.php (Phần 1 - UI)```php
<?php
/**
 * VQ Checkout - Ward-Specific Shipping Method
 */

if (!defined('ABSPATH')) {
    exit;
}

// Kế thừa từ WC_Shipping_Method
class VQ_Ward_Shipping_Method extends WC_Shipping_Method {

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id                 = 'vq_ward_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Phí vận chuyển tới Xã/Phường', 'vq-checkout');
        $this->method_description = __('Tính phí vận chuyển chi tiết theo từng Xã/Phường tại Việt Nam.', 'vq-checkout');
        
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    /**
     * Initialize settings
     */
    public function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables (Lấy dữ liệu từ options)
        $this->title            = $this->get_option('title', $this->method_title);
        $this->tax_status       = $this->get_option('tax_status', 'taxable');
        $this->cost             = $this->get_option('cost', 0);
        $this->handling_fee     = $this->get_option('handling_fee', 0);
        
        // Load complex data (được lưu thủ công)
        $this->order_total_conditions = $this->get_option('order_total_conditions_data', array());
        $this->ward_rates             = $this->get_option('ward_rates_data', array());

        // Save settings hook
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        
        // Custom action to save complex fields (tables)
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'save_complex_fields'));
    }

    /**
     * Initialize form fields (Giao diện cài đặt)
     */
    public function init_form_fields() {
        $this->instance_form_fields = array(
            // Cấu hình cơ bản
            'title' => array(
                'title'       => __('Tiêu đề phương thức', 'vq-checkout'),
                'type'        => 'text',
                'default'     => __('Phí vận chuyển', 'vq-checkout'),
            ),
            'tax_status' => array(
                'title'   => __('Tình trạng thuế', 'woocommerce'),
                'type'    => 'select',
                'default' => 'taxable',
                'options' => array(
                    'taxable' => __('Chịu thuế', 'woocommerce'),
                    'none'    => __('Không', 'woocommerce'),
                ),
            ),
            'cost' => array(
                'title'       => __('Phí vận chuyển mặc định', 'vq-checkout'),
                'type'        => 'price',
                'placeholder' => wc_format_localized_price(0),
                'default'     => '0',
            ),
            'handling_fee' => array(
                'title'       => __('Phụ thu (Handling Fee)', 'vq-checkout'),
                'type'        => 'price',
                'placeholder' => wc_format_localized_price(0),
                'default'     => '0',
            ),
            
            // Section: Điều kiện theo tổng đơn hàng
            'section_order_total' => array(
                'title'       => __('Tùy chỉnh điều kiện theo Tổng đơn hàng', 'vq-checkout'),
                'type'        => 'title',
            ),
            'order_total_conditions' => array(
                'type' => 'order_total_conditions_table', // Custom field type
            ),

            // Section: Phí theo Xã/Phường
            'section_ward_rates' => array(
                'title'       => __('Giá vận chuyển theo Xã/Phường', 'vq-checkout'),
                'type'        => 'title',
            ),
            'ward_rates' => array(
                'type' => 'ward_rates_table', // Custom field type
            ),
        );
    }

    /**
     * Generate HTML for Order Total Conditions Table
     */
    public function generate_order_total_conditions_table_html($key, $data) {
        $field_key = $this->get_field_key($key);
        $conditions = $this->order_total_conditions;
        
        ob_start();
        ?>
        <tr valign="top" class="vq-conditions-wrapper">
            <td class="forminp" colspan="2">
                <table class="vq-shipping-table widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php _e('Tổng đơn hàng >= (VNĐ)', 'vq-checkout'); ?></th>
                            <th><?php _e('Phí vận chuyển (VNĐ)', 'vq-checkout'); ?></th>
                            <th width="1%">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody class="vq-conditions-body">
                        <?php
                        $i = -1;
                        if ($conditions) {
                            foreach ($conditions as $condition) {
                                $i++;
                                echo '<tr class="vq-condition-row">
                                    <td><input type="number" name="vq_conditions_min_amount['. $i .']" value="'. esc_attr($condition['min_amount']) .'" placeholder="0" step="1000" min="0" /></td>
                                    <td><input type="number" name="vq_conditions_cost['. $i .']" value="'. esc_attr($condition['cost']) .'" placeholder="0" step="1000" min="0" /></td>
                                    <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                                </tr>';
                            }
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <a href="#" class="vq-add-row button button-primary" data-template="tmpl-vq-condition-row-template" data-rows="<?php echo esc_attr($i); ?>"><?php _e('Thêm điều kiện', 'vq-checkout'); ?></a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <script type="text/template" id="tmpl-vq-condition-row-template">
                    <tr class="vq-condition-row">
                        <td><input type="number" name="vq_conditions_min_amount[{{data.index}}]" placeholder="0" step="1000" min="0" /></td>
                        <td><input type="number" name="vq_conditions_cost[{{data.index}}]" placeholder="0" step="1000" min="0" /></td>
                        <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                    </tr>
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for Ward Rates Table
     */
    public function generate_ward_rates_table_html($key, $data) {
        $field_key = $this->get_field_key($key);
        $rates = $this->ward_rates;
        
        // Lấy danh sách Tỉnh/Thành thuộc Zone hiện tại để xác định Xã/Phường khả dụng
        $provinces_in_zone = $this->get_provinces_in_current_zone();

        ob_start();
        ?>
        <tr valign="top" class="vq-ward-rates-wrapper" data-provinces="<?php echo esc_attr(json_encode(array_keys($provinces_in_zone))); ?>">
            <td class="forminp" colspan="2">
                <?php if (empty($provinces_in_zone)): ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('Vui lòng thêm Tỉnh/Thành phố Việt Nam vào khu vực giao hàng này và Lưu thay đổi trước khi cấu hình phí Xã/Phường.', 'vq-checkout'); ?></p>
                    </div>
                <?php else: ?>
                    <p class="vq-loading-wards"><?php _e('Đang tải danh sách Xã/Phường...', 'vq-checkout'); ?></p>
                    <table class="vq-shipping-table widefat" cellspacing="0" style="display: none;">
                        <thead>
                            <tr>
                                <th><?php _e('Chọn Xã/Phường', 'vq-checkout'); ?></th>
                                <th><?php _e('Phí vận chuyển (VNĐ)', 'vq-checkout'); ?></th>
                                <th><?php _e('Tiêu đề (Tùy chọn)', 'vq-checkout'); ?></th>
                                <th width="1%">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody class="vq-ward-rates-body">
                            <?php
                            $i = -1;
                            if ($rates) {
                                foreach ($rates as $rate) {
                                    $i++;
                                    $selected_wards = isset($rate['wards']) ? (array) $rate['wards'] : array();
                                    echo '<tr class="vq-ward-rate-row">
                                        <td>
                                            <select multiple="multiple" name="vq_ward_rates_wards['. $i .'][]" class="vq-ward-select" data-placeholder="'. __('Chọn Xã/Phường...', 'vq-checkout') .'">';
                                            
                                            // Hiển thị các options đã chọn (JS sẽ populate tên sau khi load xong data)
                                            foreach ($selected_wards as $ward_code) {
                                                echo '<option value="' . esc_attr($ward_code) . '" selected="selected">' . esc_html($ward_code) . '</option>';
                                            }
                                            
                                    echo '</select>
                                        </td>
                                        <td><input type="number" name="vq_ward_rates_cost['. $i .']" value="'. esc_attr($rate['cost']) .'" placeholder="0" step="1000" min="0" /></td>
                                        <td><input type="text" name="vq_ward_rates_label['. $i .']" value="'. esc_attr($rate['label']) .'" /></td>
                                        <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                                    </tr>';
                                }
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <a href="#" class="vq-add-ward-rate button button-primary" data-template="tmpl-vq-ward-rate-row-template" data-rows="<?php echo esc_attr($i); ?>"><?php _e('Thêm Xã/Phường', 'vq-checkout'); ?></a>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <script type="text/template" id="tmpl-vq-ward-rate-row-template">
                        <tr class="vq-ward-rate-row">
                            <td>
                                <select multiple="multiple" name="vq_ward_rates_wards[{{data.index}}][]" class="vq-ward-select" data-placeholder="<?php _e('Chọn Xã/Phường...', 'vq-checkout'); ?>"></select>
                            </td>
                            <td><input type="number" name="vq_ward_rates_cost[{{data.index}}]" placeholder="0" step="1000" min="0" /></td>
                            <td><input type="text" name="vq_ward_rates_label[{{data.index}}]" /></td>
                            <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                        </tr>
                    </script>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper: Get provinces in the current zone
     */
    private function get_provinces_in_current_zone() {
        if (empty($this->instance_id)) {
            return array();
        }

        $zone = WC_Shipping_Zones::get_zone_by('instance_id', $this->instance_id);
        if (!$zone) {
            return array();
        }

        $locations = $zone->get_zone_locations();
        $vn_provinces = array();

        foreach ($locations as $location) {
            // Trường hợp 1: Toàn quốc Việt Nam
            if ($location->type === 'country' && $location->code === 'VN') {
                return vqcheckout_get_provinces();
            }

            // Trường hợp 2: Tỉnh/Thành cụ thể
            if ($location->type === 'state') {
                // WC lưu mã Tỉnh (e.g., HANOI)
                $province_code = $location->code;
                $province_name = vqcheckout_get_province_name($province_code);
                if ($province_name) {
                    $vn_provinces[$province_code] = $province_name;
                }
            }
        }
        return $vn_provinces;
    }

    /**
     * Save complex fields (Tables) - Xử lý lưu dữ liệu từ POST data.
     */
    public function save_complex_fields() {
        // 1. Save Order Total Conditions
        $conditions = array();
        if (isset($_POST['vq_conditions_min_amount']) && isset($_POST['vq_conditions_cost'])) {
            $min_amounts = wc_clean(wp_unslash($_POST['vq_conditions_min_amount']));
            $costs = wc_clean(wp_unslash($_POST['vq_conditions_cost']));

            foreach ($min_amounts as $i => $min_amount) {
                if (isset($costs[$i]) && $min_amount !== '' && $costs[$i] !== '') {
                    $conditions[] = array(
                        'min_amount' => wc_format_decimal($min_amount),
                        'cost'       => wc_format_decimal($costs[$i]),
                    );
                }
            }
        }
        
        // Sắp xếp theo min_amount ASC
        usort($conditions, function($a, $b) {
            return $a['min_amount'] <=> $b['min_amount'];
        });

        // Serialize và cập nhật vào instance settings
        $this->instance_settings['order_total_conditions_data'] = $conditions;

        // 2. Save Ward Rates
        $rates = array();
        if (isset($_POST['vq_ward_rates_wards']) && isset($_POST['vq_ward_rates_cost'])) {
            $wards_groups = wc_clean(wp_unslash($_POST['vq_ward_rates_wards']));
            $costs = wc_clean(wp_unslash($_POST['vq_ward_rates_cost']));
            $labels = isset($_POST['vq_ward_rates_label']) ? wc_clean(wp_unslash($_POST['vq_ward_rates_label'])) : array();

            foreach ($wards_groups as $i => $wards) {
                if (!empty($wards) && isset($costs[$i]) && $costs[$i] !== '') {
                    $rates[] = array(
                        'wards' => (array) $wards,
                        'cost'  => wc_format_decimal($costs[$i]),
                        'label' => isset($labels[$i]) ? sanitize_text_field($labels[$i]) : '',
                    );
                }
            }
        }

        // Serialize và cập nhật vào instance settings
        $this->instance_settings['ward_rates_data'] = $rates;
    }
?>
```
// (Tiếp tục với phần Logic tính phí - File 06)
---

III. ADMIN JAVASCRIPT & CSS
3.1. File: assets/js/vqcheckout_shipping_method_ui.js

```javascript
jQuery(function($) {
    'use strict';

    const VQShippingUI = {
        wardsData: null,

        init: function() {
            // Chỉ chạy nếu thấy wrapper của VQ Shipping Method
            if ($('.vq-ward-rates-wrapper').length === 0 && $('.vq-conditions-wrapper').length === 0) {
                return;
            }

            // Sử dụng wp.template để render các hàng mới
            this.handleRowActions('.vq-conditions-wrapper', '#tmpl-vq-condition-row-template');
            this.handleRowActions('.vq-ward-rates-wrapper', '#tmpl-vq-ward-rate-row-template');
            
            // Load dữ liệu Xã/Phường qua AJAX
            this.loadWardsData();
        },

        loadWardsData: function() {
            const wrapper = $('.vq-ward-rates-wrapper');
            const provinces = wrapper.data('provinces');

            if (!provinces || provinces.length === 0) {
                return;
            }

            $.ajax({
                url: vqcheckoutShippingAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'vqcheckout_load_shipping_wards_admin',
                    nonce: vqcheckoutShippingAdmin.nonce,
                    provinces: provinces
                },
                success: function(response) {
                    if (response.success) {
                        VQShippingUI.wardsData = response.data;
                        // Ẩn thông báo loading, hiện bảng
                        $('.vq-loading-wards').hide();
                        $('.vq-shipping-table').show();
                        // Khởi tạo Select2 cho các hàng hiện có
                        VQShippingUI.initSelect2($('.vq-ward-select'));
                    } else {
                        $('.vq-loading-wards').text('Lỗi tải dữ liệu: ' + response.data);
                    }
                },
                error: function() {
                    $('.vq-loading-wards').text('Lỗi tải dữ liệu.');
                }
            });
        },

        initSelect2: function(elements) {
            elements.each(function() {
                const select = $(this);
                
                // Lấy các giá trị đã chọn trước khi xóa options cũ
                const selectedValues = select.val();

                // Xóa options cũ và thêm options mới từ dữ liệu AJAX
                select.empty();
                if (VQShippingUI.wardsData) {
                    $.each(VQShippingUI.wardsData, function(i, group) {
                        const optgroup = $('<optgroup label="' + group.text + '"></optgroup>');
                        $.each(group.children, function(j, ward) {
                            optgroup.append($('<option></option>').attr('value', ward.id).text(ward.text));
                        });
                        select.append(optgroup);
                    });
                }

                // Set lại các giá trị đã chọn
                if (selectedValues) {
                    select.val(selectedValues);
                }

                // Khởi tạo Select2
                select.select2({
                    width: '100%'
                });
            });
        },

        handleRowActions: function(wrapperSelector, templateId) {
            const wrapper = $(wrapperSelector);
            // Lấy template JS
            const template = wp.template(templateId.substring(1)); 

            // Sự kiện thêm hàng mới
            wrapper.on('click', '.vq-add-row, .vq-add-ward-rate', function(e) {
                e.preventDefault();
                const tbody = wrapper.find('tbody');
                // Lấy index hiện tại và tăng lên 1
                let index = parseInt($(this).data('rows')) + 1;
                
                // Render HTML từ template
                const html = template({ index: index });
                tbody.append(html);

                // Nếu là thêm Xã/Phường, cần khởi tạo lại Select2 cho hàng mới
                if (wrapperSelector === '.vq-ward-rates-wrapper') {
                    VQShippingUI.initSelect2(tbody.find('tr:last-child .vq-ward-select'));
                }

                // Cập nhật index mới
                $(this).data('rows', index);
            });

            // Sự kiện xóa hàng
            wrapper.on('click', '.vq-remove-row', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        }
    };

    // Khởi tạo khi tài liệu sẵn sàng
    $(document).ready(function() {
        VQShippingUI.init();
    });

    // Khởi tạo lại khi modal settings của WC mở ra (Quan trọng)
    $(document).on('wc_backbone_modal_loaded', function(e, target) {
        if (target === 'wc-modal-shipping-method-settings') {
            VQShippingUI.init();
        }
    });
});
```

### 4.2. CSS - Styling

**File:** `assets/css/vqcheckout_admin.css`

```css
/* Global Conditions Table */
.vq-global-conditions-table {
    border-collapse: collapse;
    margin: 10px 0;
}

.vq-global-conditions-table th,
.vq-global-conditions-table td {
    padding: 10px;
    border: 1px solid #ddd;
}

.vq-global-conditions-table thead {
    background: #f9f9f9;
}

.vq-global-conditions-table input[type="number"] {
    padding: 5px;
}

/* Ward Rates Table */
.vq-ward-rates-table {
    border-collapse: collapse;
    margin: 10px 0;
}

.vq-ward-rates-table th,
.vq-ward-rates-table td {
    padding: 10px;
    border: 1px solid #ddd;
}

.vq-ward-rates-table thead {
    background: #f9f9f9;
}

.vq-ward-rates-table .select2-container {
    max-width: 100%;
}

/* Buttons */
.vq-add-condition,
.vq-add-ward-rate {
    margin-top: 5px;
}

.vq-remove-condition,
.vq-remove-ward-rate {
    color: #a00;
}

.vq-remove-condition:hover,
.vq-remove-ward-rate:hover {
    color: #dc3232;
}

/* assets/css/vqcheckout_shipping_method_ui.css */
.vq-shipping-table {
    margin-top: 10px;
}
.vq-shipping-table th {
    text-align: left;
    padding: 10px;
}
.vq-shipping-table td {
    padding: 10px;
    vertical-align: middle;
}
.vq-shipping-table input[type=number],
.vq-shipping-table input[type=text] {
    width: 100%;
    box-sizing: border-box;
}
.vq-ward-select {
    width: 100% !important;
}
.vq-loading-wards {
    font-style: italic;
    color: #777;
}
```

---

## V. AJAX ENDPOINT

### 5.1. Load Wards for Zone

**File:** `inc/vqcheckout_ajax.php`

```php
/**
 * AJAX: Load wards for shipping method admin
 * 
 * Load wards for multiple provinces (zone regions)
 */
add_action('wp_ajax_vqcheckout_load_wards_for_zone', 'vqcheckout_ajax_load_wards_for_zone');

function vqcheckout_ajax_load_wards_for_zone() {
    // Verify nonce
    check_ajax_referer('vqcheckout_nonce', 'security');
    
    // Get provinces
    $provinces = isset($_POST['provinces']) ? (array) $_POST['provinces'] : array();
    
    if (empty($provinces)) {
        wp_send_json_error('No provinces provided');
    }
    
    // Get wards for all provinces
    $all_wards = array();
    foreach ($provinces as $province_code) {
        $wards = vqcheckout_get_wards($province_code);
        $all_wards = array_merge($all_wards, $wards);
    }
    
    wp_send_json_success($all_wards);
}
```

---

## VI. TESTING CHECKLIST

### Admin UI
- [ ] Method xuất hiện trong WC Shipping → Add method
- [ ] Basic settings display correctly
- [ ] Global conditions table: Add/Remove rows
- [ ] Ward rates table: Add/Remove rows
- [ ] Select2 initializes cho ward dropdown
- [ ] Ward dropdown chỉ hiển thị wards thuộc Zone regions
- [ ] Settings save correctly
- [ ] Settings load correctly khi edit

### Data Validation
- [ ] Empty conditions không save
- [ ] Empty ward rates không save
- [ ] Numbers sanitized correctly
- [ ] Ward codes sanitized

### Edge Cases
- [ ] Zone không có regions → Alert user
- [ ] Nhiều rows không conflict
- [ ] Delete row không break indexing

---

**Dependencies:**
- File 01: Data Structure (Load wards)
- File 02: Core (Register hooks)
- WooCommerce Shipping API

**Next:** [File 06 - Shipping Calculator Logic](./06-Shipping-Calculator-REVISED.md)

---

**Version:** 2.0.0-REVISED  
**Status:** ✅ HOÀN TOÀN MỚI - THAY THẾ FILE 05 CŨ