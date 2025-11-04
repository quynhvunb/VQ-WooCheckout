<?php
/**
 * VQ Checkout - Ward-Specific Shipping Method (File 05 & 06) - RESTRUCTURED & FIXED
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Shipping_Method')) {
    return;
}

class VQ_Ward_Shipping_Method extends WC_Shipping_Method {

    /**
     * Declared properties to prevent PHP 8.2+ dynamic property deprecation warnings
     */
    public $global_conditions;
    public $ward_rates;
    public $cost;
    public $handling_fee;

    /**
     * Constructor
     */
    public function __construct($instance_id = 0) {
        $this->id                 = 'vq_ward_shipping';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Phí vận chuyển tới Xã/Phường (VQ)', 'vq-checkout');
        $this->method_description = __('Tính phí vận chuyển chi tiết theo từng Xã/Phường tại Việt Nam.', 'vq-checkout');
        
        $this->supports           = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        // Initialize all properties with default values
        $this->global_conditions = array();
        $this->ward_rates        = array();
        $this->cost              = 0;
        $this->handling_fee      = 0;

        $this->init();
    }

    /**
     * Initialize settings and hooks
     */
    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        // Load data
        $this->title            = $this->get_option('title', $this->method_title);
        $this->tax_status       = $this->get_option('tax_status', 'taxable');
        
        // Only load advanced settings if instance exists (Fix for B3/B4 flow)
        if ($this->instance_id > 0) {
            $this->cost             = $this->get_option('cost', 0);
            $this->handling_fee     = $this->get_option('handling_fee', 0);
            
            // Load complex data (Renamed keys)
            $conditions = $this->get_option('global_conditions_data', array());
            $this->global_conditions = is_array($conditions) ? $conditions : array();
            
            $rates = $this->get_option('ward_rates_data', array());
            $this->ward_rates = is_array($rates) ? $rates : array();
        }

        // Save hooks
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'save_complex_fields'), 5);
    }

    // ----------------------------------------------------
    // UI GENERATION (File 05) - REVISED
    // ----------------------------------------------------

    /**
     * Initialize form fields (FIXED: Conditional Fields for UI Flow B1-B6)
     */
    public function init_form_fields() {
        
        $is_editing = ($this->instance_id > 0);

        // Basic fields (B3)
        $fields = array(
            'title' => array(
                'title'       => __('Tiêu đề phương thức', 'vq-checkout'),
                'type'        => 'text',
                'default'     => __('Phí vận chuyển', 'vq-checkout'),
                'custom_attributes' => array('required' => 'required'),
            ),
            'tax_status' => array(
                'title'   => __('Tình trạng thuế', 'woocommerce'),
                'type'    => 'select',
                'default' => 'taxable',
                'options' => array('taxable' => __('Chịu thuế', 'woocommerce'), 'none' => __('Không', 'woocommerce')),
            ),
        );

        // Advanced configuration fields (B5/B6 - Only show when editing)
        if ($is_editing) {
            $fields['cost'] = array(
                'title'       => __('Phí vận chuyển mặc định (Toàn khu vực)', 'vq-checkout'),
                'type'        => 'price',
                'placeholder' => wc_format_localized_price(0),
                'default'     => '0',
                'desc_tip'    => __('Áp dụng nếu không có Xã/Phường nào khớp trong bảng giá chi tiết bên dưới.', 'vq-checkout'),
            );
            $fields['handling_fee'] = array(
                'title'       => __('Phụ thu (Handling Fee)', 'vq-checkout'),
                'type'        => 'price',
                'default'     => '0',
            );
            
            // Section: Global Conditions (B5)
            $fields['section_global_conditions'] = array(
                'title'       => __('Tùy chỉnh điều kiện Chung (Theo Tổng đơn hàng)', 'vq-checkout'),
                'type'        => 'title',
                'description' => __('Áp dụng cho toàn bộ khu vực nếu không có điều kiện riêng nào ở bảng Xã/Phường khớp.', 'vq-checkout'),
            );
            $fields['global_conditions'] = array(
                'type' => 'global_conditions_table',
            );

            // Section: Ward Rates (B6)
            $fields['section_ward_rates'] = array(
                'title'       => __('Giá vận chuyển chi tiết theo Xã/Phường', 'vq-checkout'),
                'type'        => 'title',
                'description' => __('Cấu hình chi tiết cho từng Xã/Phường. Các cấu hình tại đây có độ ưu tiên CAO NHẤT. Sử dụng kéo thả (biểu tượng bên trái) để sắp xếp thứ tự ưu tiên.', 'vq-checkout'),
            );
            $fields['ward_rates'] = array(
                'type' => 'ward_rates_table_complex', // NEW Complex Type
            );
        } else {
            // When adding new (B3)
            $fields['info_message'] = array(
                'title' => __('Lưu ý', 'vq-checkout'),
                'type' => 'title',
                'description' => __('Vui lòng nhập Tiêu đề và nhấn "Tiếp tục" (Continue) hoặc "Tạo và lưu" (Create and save). Bạn sẽ cấu hình chi tiết sau khi phương thức được tạo.', 'vq-checkout'),
            );
        }

        $this->instance_form_fields = $fields;
    }

    /**
     * Generate HTML for Global Conditions Table
     */
    public function generate_global_conditions_table_html($key, $data) {
        $conditions = $this->global_conditions;
        
        ob_start();
        ?>
        <tr valign="top" class="vq-conditions-wrapper vq-dynamic-table-wrapper" data-wrapper-id="global-conditions">
            <td class="forminp" colspan="2">
                <table class="vq-shipping-table widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?php _e('Tổng đơn hàng >= (VNĐ)', 'vq-checkout'); ?></th>
                            <th><?php _e('Phí vận chuyển (VNĐ)', 'vq-checkout'); ?></th>
                            <th width="1%">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody class="vq-dynamic-table-body">
                        <?php
                        $i = -1;
                        foreach ($conditions as $condition) {
                            $i++;
                            $min_amount = $condition['min_amount'] ?? '';
                            $cost = $condition['cost'] ?? '';

                            echo '<tr class="vq-dynamic-row">
                                <td><input type="number" name="vq_global_cond_min_amount['. $i .']" value="'. esc_attr($min_amount) .'" placeholder="0" step="1000" min="0" /></td>
                                <td><input type="number" name="vq_global_cond_cost['. $i .']" value="'. esc_attr($cost) .'" placeholder="0" step="1000" min="0" /></td>
                                <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                            </tr>';
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <a href="#" class="vq-add-row button button-primary" data-template="tmpl-vq-global-condition-row-template" data-rows="<?php echo esc_attr($i); ?>"><?php _e('Thêm điều kiện Chung', 'vq-checkout'); ?></a>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <script type="text/template" id="tmpl-vq-global-condition-row-template">
                    <tr class="vq-dynamic-row">
                        <td><input type="number" name="vq_global_cond_min_amount[{{data.index}}]" placeholder="0" step="1000" min="0" /></td>
                        <td><input type="number" name="vq_global_cond_cost[{{data.index}}]" placeholder="0" step="1000" min="0" /></td>
                        <td><a href="#" class="vq-remove-row button">Xóa</a></td>
                    </tr>
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate HTML for Complex Ward Rates Table (Implementation of B6)
     */
    public function generate_ward_rates_table_complex_html($key, $data) {
        $rates = $this->ward_rates;
        $provinces_in_zone = $this->get_provinces_in_current_zone();

        ob_start();
        ?>
        <tr valign="top" class="vq-ward-rates-wrapper vq-dynamic-table-wrapper" data-wrapper-id="ward-rates" data-provinces="<?php echo esc_attr(json_encode(array_keys($provinces_in_zone))); ?>">
            <td class="forminp" colspan="2">
                <?php if (empty($provinces_in_zone)): ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('Vui lòng thêm Tỉnh/Thành phố vào khu vực này và Lưu thay đổi trước khi cấu hình.', 'vq-checkout'); ?></p>
                    </div>
                <?php else: ?>
                    <p class="vq-loading-wards"><?php _e('Đang tải danh sách Xã/Phường...', 'vq-checkout'); ?></p>
                    <table class="vq-shipping-table vq-shipping-table-complex widefat" cellspacing="0" style="display: none;">
                        <thead>
                            <tr>
                                <th class="vq-col-sort" width="1%"><?php /* Sort Handle */ ?></th>
                                <th class="vq-col-wards"><?php _e('Xã/Phường', 'vq-checkout'); ?></th>
                                <th class="vq-col-cost"><?php _e('Phí Vận chuyển & Điều kiện', 'vq-checkout'); ?></th>
                                <th class="vq-col-label"><?php _e('Tiêu đề', 'vq-checkout'); ?></th>
                                <th width="1%"><?php /* Actions */ ?></th>
                            </tr>
                        </thead>
                        <tbody class="vq-dynamic-table-body vq-sortable">
                            <?php
                            $i = -1;
                            foreach ($rates as $rate) {
                                $i++;
                                $this->render_complex_ward_rate_row($i, $rate);
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5">
                                    <a href="#" class="vq-add-row button button-primary" data-template="tmpl-vq-ward-rate-complex-row-template" data-rows="<?php echo esc_attr($i); ?>"><?php _e('Thêm Xã/Phường', 'vq-checkout'); ?></a>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <script type="text/template" id="tmpl-vq-ward-rate-complex-row-template">
                        <?php 
                        // Render an empty row template using the helper function
                        $this->render_complex_ward_rate_row('{{data.index}}', array(), true); 
                        ?>
                    </script>
                    <script type="text/template" id="tmpl-vq-ward-condition-sub-row-template">
                        <?php 
                        // Render an empty sub-row template
                        $this->render_ward_condition_sub_row('{{data.parentIndex}}', '{{data.subIndex}}', array(), true); 
                        ?>
                    </script>

                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper: Render a single row in the complex Ward Rates table (Handles PHP and JS Template rendering)
     */
    private function render_complex_ward_rate_row($index, $rate, $is_template = false) {
        $selected_wards = $rate['wards'] ?? array();
        $cost = $rate['cost'] ?? '';
        $label = $rate['label'] ?? '';
        // NEW: No Shipping flag
        $no_shipping = isset($rate['no_shipping']) && $rate['no_shipping'] === '1';
        // NEW: Per-row conditions
        $conditions = $rate['conditions'] ?? array();

        // Determine initial sub-index for conditions
        $sub_index = count($conditions) - 1;

        echo '<tr class="vq-ward-rate-row vq-dynamic-row" data-row-index="'. $index .'">';
        // NEW: Sort Handle
        echo '<td class="vq-col-sort"><span class="dashicons dashicons-menu"></span></td>';
        
        // Column: Wards
        echo '<td><select multiple="multiple" name="vq_ward_rates_wards['. $index .'][]" class="vq-ward-select" data-placeholder="'. __('Chọn Xã/Phường...', 'vq-checkout') .'">';
        if (!$is_template) {
            foreach ($selected_wards as $ward_code) {
                // Initial rendering relies on JS to populate the text later
                echo '<option value="' . esc_attr($ward_code) . '" selected="selected">' . esc_html($ward_code) . '</option>';
            }
        }
        echo '</select></td>';

        // Column: Cost & Conditions (Complex structure)
        echo '<td class="vq-cost-conditions-cell">';
        
        // NEW: No Shipping Checkbox
        echo '<label class="vq-no-shipping-label"><input type="checkbox" name="vq_ward_rates_no_shipping['. $index .']" value="1" '. checked($no_shipping, true, false) .' class="vq-toggle-no-shipping"> '.__('Không vận chuyển tới khu vực này', 'vq-checkout').'</label>';
        
        // Wrapper for elements hidden when "No Shipping" is active
        echo '<div class="vq-cost-conditions-wrapper" '. ($no_shipping ? 'style="display:none;"' : '') .'>';
        
        // Base Cost
        echo '<div class="vq-base-cost-wrapper"><label>'.__('Phí cơ bản:', 'vq-checkout').'</label> <input type="number" name="vq_ward_rates_cost['. $index .']" value="'. esc_attr($cost) .'" placeholder="0" step="1000" min="0" /></div>';
        
        // Conditions Sub-Table
        echo '<div class="vq-ward-conditions-sub-table-wrapper">';
        echo '<h4>'.__('Tùy chỉnh điều kiện (Theo Tổng đơn hàng):', 'vq-checkout').'</h4>';
        echo '<table class="vq-shipping-sub-table widefat">';
        echo '<thead><tr><th>>= (VNĐ)</th><th>Phí (VNĐ)</th><th></th></tr></thead>';
        echo '<tbody class="vq-dynamic-table-body">';
        
        $j = -1;
        if (!$is_template) {
             foreach ($conditions as $condition) {
                $j++;
                $this->render_ward_condition_sub_row($index, $j, $condition);
            }
        }

        echo '</tbody><tfoot><tr><td colspan="3">';
        // Add Sub-row button
        echo '<a href="#" class="vq-add-sub-row button" data-template="tmpl-vq-ward-condition-sub-row-template" data-rows="'. $j .'">'.__('Thêm ĐK', 'vq-checkout').'</a>';
        echo '</td></tr></tfoot></table>';
        echo '</div>'; // end vq-ward-conditions-sub-table-wrapper
        echo '</div>'; // end vq-cost-conditions-wrapper

        echo '</td>';

        // Column: Label
        echo '<td><input type="text" name="vq_ward_rates_label['. $index .']" value="'. esc_attr($label) .'" placeholder="'. esc_attr($this->title) .'" /></td>';
        
        // Column: Actions
        echo '<td><a href="#" class="vq-remove-row button">Xóa</a></td>';
        echo '</tr>';
    }

    /**
     * Helper: Render a sub-row for conditions within a Ward Rate row
     */
    private function render_ward_condition_sub_row($parent_index, $sub_index, $condition, $is_template = false) {
        $min_amount = $condition['min_amount'] ?? '';
        $cost = $condition['cost'] ?? '';

        echo '<tr class="vq-dynamic-row">';
        // Note the nested array syntax for the name attribute: name[parent_index][sub_index]
        echo '<td><input type="number" name="vq_ward_cond_min_amount['. $parent_index .']['. $sub_index .']" value="'. esc_attr($min_amount) .'" placeholder="0" step="1000" min="0" /></td>';
        echo '<td><input type="number" name="vq_ward_cond_cost['. $parent_index .']['. $sub_index .']" value="'. esc_attr($cost) .'" placeholder="0 (Free)" step="1000" min="0" /></td>';
        echo '<td><a href="#" class="vq-remove-row button">Xóa</a></td>';
        echo '</tr>';
    }


    /**
     * Helper: Get provinces in the current zone context (FIXED: Fatal Error Prevention)
     */
    private function get_provinces_in_current_zone() {
        // FIX: Safety check for instance_id=0 (prevents Fatal Error)
        if (empty($this->instance_id) || $this->instance_id <= 0) {
            return array();
        }

        if (!class_exists('WC_Shipping_Zones')) {
            return array();
        }

        $zone = WC_Shipping_Zones::get_zone_by('instance_id', $this->instance_id);
        
        // FIX: Safety check if zone lookup fails (prevents Fatal Error)
        if (!$zone || !method_exists($zone, 'get_zone_locations')) {
            return array();
        }

        $locations = $zone->get_zone_locations();
        $vn_provinces = array();
        $all_vn_provinces_data = vqcheckout_get_provinces();

        foreach ($locations as $location) {
            if ($location->type === 'country' && $location->code === 'VN') {
                return $all_vn_provinces_data;
            }

            if ($location->type === 'state') {
                $province_code = $location->code;
                if (strpos($province_code, 'VN:') === 0) {
                    $province_code = substr($province_code, 3);
                }
                $province_code = strtoupper($province_code);

                if (isset($all_vn_provinces_data[$province_code])) {
                    $vn_provinces[$province_code] = $all_vn_provinces_data[$province_code];
                }
            }
        }
        return $vn_provinces;
    }

    // ----------------------------------------------------
    // DATA SAVING (File 05) - REVISED
    // ----------------------------------------------------

    /**
     * Save complex fields (Tables) - REVISED for new structure
     */
    public function save_complex_fields() {
        if ($this->instance_id <= 0) {
            return;
        }

        // 1. Save Global Conditions
        $global_conditions = $this->process_conditions_from_post('vq_global_cond_min_amount', 'vq_global_cond_cost');
        $this->instance_settings['global_conditions_data'] = $global_conditions;

        // 2. Save Ward Rates (Complex structure)
        $rates = array();
        
        // Get raw POST data for the main row elements
        $wards_groups_raw = isset($_POST['vq_ward_rates_wards']) && is_array($_POST['vq_ward_rates_wards']) ? wp_unslash($_POST['vq_ward_rates_wards']) : array();
        $costs_raw = isset($_POST['vq_ward_rates_cost']) && is_array($_POST['vq_ward_rates_cost']) ? wp_unslash($_POST['vq_ward_rates_cost']) : array();
        $labels_raw = isset($_POST['vq_ward_rates_label']) && is_array($_POST['vq_ward_rates_label']) ? wp_unslash($_POST['vq_ward_rates_label']) : array();
        $no_shipping_raw = isset($_POST['vq_ward_rates_no_shipping']) && is_array($_POST['vq_ward_rates_no_shipping']) ? wp_unslash($_POST['vq_ward_rates_no_shipping']) : array();

        $costs = wc_clean($costs_raw);
        $labels = wc_clean($labels_raw);
        $no_shipping_data = wc_clean($no_shipping_raw);

        // Iterate over the submitted ward groups (rows)
        // We rely on the iteration order here to preserve the user's sorting (B6).
        foreach ($wards_groups_raw as $i => $wards_raw) {
            $wards = wc_clean($wards_raw);
            if (!is_array($wards)) {
                $wards = array_filter(array($wards));
            }

            if (!empty($wards)) {
                // Compile the data structure for this row
                $rate_data = array(
                    'wards' => $wards,
                    // Cost is optional if No Shipping is checked, default to 0 if missing.
                    'cost'  => isset($costs[$i]) ? wc_format_decimal($costs[$i]) : 0,
                    'label' => isset($labels[$i]) ? sanitize_text_field($labels[$i]) : '',
                    // NEW: No Shipping flag
                    'no_shipping' => isset($no_shipping_data[$i]) && $no_shipping_data[$i] === '1' ? '1' : '0',
                    'conditions' => array(),
                );

                // NEW: Process Conditions specific to this row (Sub-table)
                // We use the helper function to extract the nested array structure based on the row index $i.
                $row_conditions = $this->process_nested_conditions_from_post('vq_ward_cond_min_amount', 'vq_ward_cond_cost', $i);
                $rate_data['conditions'] = $row_conditions;

                $rates[] = $rate_data;
            }
        }

        $this->instance_settings['ward_rates_data'] = $rates;
    }

    /**
     * Helper: Process simple condition tables from POST data (e.g., Global Conditions)
     */
    private function process_conditions_from_post($amount_key, $cost_key) {
        $conditions = array();
        $min_amounts_raw = isset($_POST[$amount_key]) && is_array($_POST[$amount_key]) ? wp_unslash($_POST[$amount_key]) : array();
        $costs_raw = isset($_POST[$cost_key]) && is_array($_POST[$cost_key]) ? wp_unslash($_POST[$cost_key]) : array();

        $min_amounts = wc_clean($min_amounts_raw);
        $costs = wc_clean($costs_raw);

        foreach ($min_amounts as $i => $min_amount) {
            if (isset($costs[$i]) && $min_amount !== '' && $costs[$i] !== '') {
                $conditions[] = array(
                    'min_amount' => wc_format_decimal($min_amount),
                    'cost'       => wc_format_decimal($costs[$i]),
                );
            }
        }
        
        // Sort by min_amount ASC (Critical for calculation logic)
        usort($conditions, function($a, $b) {
            return (float)$a['min_amount'] <=> (float)$b['min_amount'];
        });

        return $conditions;
    }

    /**
     * Helper: Process nested condition tables from POST data (e.g., Ward-specific Conditions)
     */
    private function process_nested_conditions_from_post($amount_key, $cost_key, $parent_index) {
        $conditions = array();
        
        // Check if the nested structure exists in POST for the specific parent index
        if (isset($_POST[$amount_key][$parent_index]) && is_array($_POST[$amount_key][$parent_index]) &&
            isset($_POST[$cost_key][$parent_index]) && is_array($_POST[$cost_key][$parent_index])) {
            
            $min_amounts = wc_clean(wp_unslash($_POST[$amount_key][$parent_index]));
            $costs = wc_clean(wp_unslash($_POST[$cost_key][$parent_index]));

            foreach ($min_amounts as $i => $min_amount) {
                if (isset($costs[$i]) && $min_amount !== '' && $costs[$i] !== '') {
                    $conditions[] = array(
                        'min_amount' => wc_format_decimal($min_amount),
                        'cost'       => wc_format_decimal($costs[$i]),
                    );
                }
            }
        }

        // Sort by min_amount ASC
        usort($conditions, function($a, $b) {
            return (float)$a['min_amount'] <=> (float)$b['min_amount'];
        });

        return $conditions;
    }


    // ----------------------------------------------------
    // CALCULATION LOGIC (File 06) - REVISED
    // ----------------------------------------------------

    /**
     * Calculate shipping cost (REVISED Logic)
     */
    public function calculate_shipping($package = array()) {
        // If this method is called without an instance ID, it cannot calculate rates.
        if ($this->instance_id <= 0) {
             return;
        }

        // 1. Gather information
        $destination = $package['destination'] ?? array();
        $country = $destination['country'] ?? '';
        $ward_code = $destination['city'] ?? '';

        if ($country !== 'VN') {
            return;
        }
        
        $cart_total = 0;
        if (isset($package['cart_subtotal'])) {
             $cart_total = (float) $package['cart_subtotal'];
        } elseif (function_exists('WC') && WC()->cart) {
            $cart_total = (float) WC()->cart->get_subtotal();
        }

        // 2. Find Matching Ward Rate (Highest Priority)
        $matched_rate = $this->find_matching_ward_rate($ward_code);

        if ($matched_rate) {
            // Check for "No Shipping" flag (B6 Requirement)
            if (isset($matched_rate['no_shipping']) && $matched_rate['no_shipping'] === '1') {
                // Stop processing. No rates are added, WC will show "No shipping options available".
                return;
            }
            
            // Calculate cost based on Ward-Specific Configuration (Conditions or Base Cost)
            $final_cost = $this->calculate_cost_from_rate($matched_rate, $cart_total);
            $rate_label = $matched_rate['label'] ?? '';

        } else {
            // 3. Fallback: Use Global Configuration (if no specific ward matched)
            
            // Create a temporary rate object using global settings
            $default_rate = array(
                'cost' => $this->cost,
                'conditions' => $this->global_conditions, // Use global conditions
            );

            $final_cost = $this->calculate_cost_from_rate($default_rate, $cart_total);
            $rate_label = ''; // Use method title
        }

        // 4. Finalize Cost (Add Handling Fee)
        $handling_fee = wc_format_decimal($this->handling_fee);
        if ($handling_fee > 0 && $final_cost > 0) {
            $final_cost += $handling_fee;
        }

        // 5. Add Rate to WooCommerce
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
     * Find the first matching Ward Rate configuration for the given ward code.
     */
    private function find_matching_ward_rate($ward_code) {
        if (empty($ward_code) || empty($this->ward_rates)) {
            return null;
        }

        // Iterate through rates (order matters as defined by user sorting)
        foreach ($this->ward_rates as $rate) {
            if (isset($rate['wards']) && is_array($rate['wards']) && in_array($ward_code, $rate['wards'])) {
                // Return the entire rate configuration object
                return $rate;
            }
        }

        return null;
    }

    /**
     * Calculate the final cost based on a rate configuration object (Ward-specific or Global)
     */
    private function calculate_cost_from_rate($rate_config, $cart_total) {
        $base_cost = wc_format_decimal($rate_config['cost'] ?? 0);
        $conditions = $rate_config['conditions'] ?? array();

        // If there are no conditions, return the base cost
        if (empty($conditions) || !is_array($conditions)) {
            return $base_cost;
        }

        // Process conditions (They are pre-sorted ASC)
        $matched_cost = null;
        foreach ($conditions as $condition) {
            if (!isset($condition['min_amount']) || !isset($condition['cost'])) continue;

            $min_amount = wc_format_decimal($condition['min_amount']);
            
            if ($cart_total >= $min_amount) {
                // Update matched_cost (last match wins)
                $matched_cost = wc_format_decimal($condition['cost']);
            } else {
                break;
            }
        }

        // If a condition matched, use it; otherwise, use the base cost.
        return ($matched_cost !== null) ? $matched_cost : $base_cost;
    }
}