<?php
/**
 * VQ Checkout - Ward-Specific Shipping Method (devvn pattern)
 * Rebuild theo đúng UI/UX từ devvn_district_zone_shipping
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
    public $title_method;
    public $fee;
    public $cost;
    public $all_price_condition;
    public $all_weight_condition;
    public $ward_rates;

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
        );

        // Initialize all properties with default values
        $this->title_method = '';
        $this->fee = 0;
        $this->cost = 0;
        $this->all_price_condition = array();
        $this->all_weight_condition = array();
        $this->ward_rates = array();

        $this->init();
    }

    /**
     * Initialize settings and hooks
     */
    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        // Load data
        $this->title_method = $this->get_option('title', $this->method_title);
        $this->title = $this->title_method;

        // Only load advanced settings if instance exists
        if ($this->instance_id > 0) {
            $this->fee = $this->get_option('fee', 0);
            $this->cost = $this->get_option('cost', 0);
            $this->all_price_condition = $this->get_option('all_price_condition', array());
            $this->all_weight_condition = $this->get_option('all_weight_condition', array());
            $this->ward_rates = $this->get_option('ward_rates', array());
        }

        // Save hooks
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialize form fields
     */
    public function init_form_fields() {
        $this->instance_form_fields = array(
            'title' => array(
                'title'       => __('Tiêu đề phương thức', 'vq-checkout'),
                'type'        => 'text',
                'description' => __('Tiêu đề khách hàng thấy tại checkout.', 'vq-checkout'),
                'default'     => $this->method_title,
                'desc_tip'    => true,
            ),
            'fee' => array(
                'title'       => __('Phụ thu', 'vq-checkout'),
                'type'        => 'text',
                'description' => __('Phụ thu thêm (nếu có).', 'vq-checkout'),
                'default'     => '0',
                'desc_tip'    => true,
            ),
            'cost' => array(
                'title'       => __('Phí vận chuyển mặc định', 'vq-checkout'),
                'type'        => 'text',
                'description' => __('Phí vận chuyển mặc định cho tất cả xã/phường.', 'vq-checkout'),
                'default'     => '0',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Admin options - Override to add custom HTML
     */
    public function admin_options() {
        // Debug
        error_log('VQ Checkout: admin_options called');
        error_log('VQ Checkout: instance_form_fields = ' . print_r($this->instance_form_fields, true));
        ?>
        <h2><?php echo esc_html($this->get_method_title()); ?></h2>
        <p><?php echo wp_kses_post($this->get_method_description()); ?></p>
        <table class="form-table">
            <?php
            // Manual render for basic fields (generate_settings_html may not work in inline mode)
            echo $this->generate_text_html('title', $this->instance_form_fields['title']);
            echo $this->generate_text_html('fee', $this->instance_form_fields['fee']);
            echo $this->generate_text_html('cost', $this->instance_form_fields['cost']);
            ?>
            <?php $this->generate_custom_conditions_section(); ?>
            <?php $this->generate_ward_rates_table_section(); ?>
        </table>
        <?php
    }

    /**
     * Generate custom conditions section
     */
    protected function generate_custom_conditions_section() {
        $data = array('title' => __('Tùy chỉnh điều kiện', 'vq-checkout'));
        echo $this->generate_custom_conditions_html_html('custom_conditions', $data);
    }

    /**
     * Generate ward rates table section
     */
    protected function generate_ward_rates_table_section() {
        error_log('VQ Checkout: Generating ward rates table section');
        $data = array('title' => __('Giá vận chuyển', 'vq-checkout'));
        $html = $this->generate_ward_rates_table_html_html('ward_rates_table', $data);
        error_log('VQ Checkout: Ward rates HTML length: ' . strlen($html));
        error_log('VQ Checkout: Button exists in HTML: ' . (strpos($html, 'vq-add-ward-rate') !== false ? 'YES' : 'NO'));
        echo $html;
    }

    /**
     * Generate custom HTML for conditions section
     */
    public function generate_custom_conditions_html_html($key, $data) {
        $field_key = $this->get_field_key($key);

        // Load existing conditions to determine checkbox state
        $conditions = $this->all_price_condition;
        $has_conditions = !empty($conditions) && is_array($conditions);

        error_log('VQ Checkout: Rendering conditions section');
        error_log('VQ Checkout: Loaded conditions: ' . print_r($conditions, true));
        error_log('VQ Checkout: Has conditions: ' . ($has_conditions ? 'YES' : 'NO'));

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php echo wp_kses_post($data['title']); ?></th>
            <td class="forminp">
                <!-- Điều kiện tổng giá đơn hàng -->
                <div class="vq-condition-section">
                    <label>
                        <input type="checkbox" name="vq_enable_price_condition" value="1" class="vq-toggle-condition" data-target=".vq-price-condition-table" <?php checked($has_conditions, true); ?>>
                        <?php _e('Tùy chỉnh điều kiện tổng giá đơn hàng cho toàn bộ xã/phường', 'vq-checkout'); ?>
                    </label>

                    <div class="vq-price-condition-table" style="<?php echo $has_conditions ? 'display: block;' : 'display: none;'; ?> margin-top: 10px;">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Điều kiện giá order >=', 'vq-checkout'); ?></th>
                                    <th><?php _e('Giá vận chuyển', 'vq-checkout'); ?></th>
                                    <th width="80"></th>
                                </tr>
                            </thead>
                            <tbody class="vq-condition-rows">
                                <?php
                                // Load existing conditions if any
                                if (empty($conditions) || !is_array($conditions)) {
                                    // Show one empty row by default
                                    ?>
                                    <tr class="vq-condition-row">
                                        <td><input type="number" name="vq_all_price_cond_min[]" min="0" step="1000" class="regular-text" /></td>
                                        <td><input type="number" name="vq_all_price_cond_cost[]" min="0" step="1000" class="regular-text" /></td>
                                        <td><a href="#" class="vq-remove-global-condition button">×</a></td>
                                    </tr>
                                    <?php
                                } else {
                                    foreach ($conditions as $cond) {
                                        ?>
                                        <tr class="vq-condition-row">
                                            <td><input type="number" name="vq_all_price_cond_min[]" min="0" step="1000" class="regular-text" value="<?php echo esc_attr($cond['min'] ?? ''); ?>" /></td>
                                            <td><input type="number" name="vq_all_price_cond_cost[]" min="0" step="1000" class="regular-text" value="<?php echo esc_attr($cond['cost'] ?? ''); ?>" /></td>
                                            <td><a href="#" class="vq-remove-global-condition button">×</a></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 10px;">
                            <button type="button" class="button vq-add-price-condition"><?php _e('Thêm điều kiện', 'vq-checkout'); ?></button>
                        </p>
                    </div>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate custom HTML for ward rates table
     */
    public function generate_ward_rates_table_html_html($key, $data) {
        $field_key = $this->get_field_key($key);
        $provinces_in_zone = $this->get_provinces_in_current_zone();

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php echo wp_kses_post($data['title']); ?></th>
            <td class="forminp">
                <?php if (empty($provinces_in_zone)): ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('Vui lòng thêm Tỉnh/Thành phố vào khu vực này và Lưu thay đổi trước khi cấu hình.', 'vq-checkout'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="vq-ward-rates-wrapper">
                        <table class="widefat vq-ward-rates-table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="check-column"><input type="checkbox" class="vq-select-all"></th>
                                    <th><?php _e('Xã/Phường', 'vq-checkout'); ?></th>
                                    <th><?php _e('Phí vận chuyển', 'vq-checkout'); ?></th>
                                    <th><?php _e('Tiêu đề', 'vq-checkout'); ?></th>
                                    <th class="vq-sort-column"><?php _e('Sắp xếp', 'vq-checkout'); ?></th>
                                </tr>
                            </thead>
                            <tbody class="vq-ward-rates-body ui-sortable">
                                <!-- Rows will be added by JavaScript -->
                            </tbody>
                            <tfoot style="display: table-footer-group !important; visibility: visible !important;">
                                <tr>
                                    <th colspan="5" style="padding: 10px; background: #f9f9f9;">
                                        <button type="button" class="button button-primary vq-add-ward-rate" style="margin-right: 10px;"><?php _e('Thêm xã/phường', 'vq-checkout'); ?></button>
                                        <button type="button" class="button vq-remove-selected"><?php _e('Xóa lựa chọn', 'vq-checkout'); ?></button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Hidden input to store JSON data -->
                        <input type="hidden" name="vq_ward_rates_data" class="vq-ward-rates-data" value="">

                        <!-- Hidden data for JS -->
                        <input type="hidden" class="vq-provinces-data" value="<?php echo esc_attr(json_encode(array_keys($provinces_in_zone))); ?>">
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper: Get provinces in the current zone context
     */
    private function get_provinces_in_current_zone() {
        error_log('VQ Checkout: get_provinces_in_current_zone called');
        error_log('VQ Checkout: instance_id = ' . $this->instance_id);

        if (empty($this->instance_id) || $this->instance_id <= 0) {
            error_log('VQ Checkout: No valid instance_id, returning empty');
            return array();
        }

        if (!class_exists('WC_Shipping_Zones')) {
            error_log('VQ Checkout: WC_Shipping_Zones class not found');
            return array();
        }

        $zone = WC_Shipping_Zones::get_zone_by('instance_id', $this->instance_id);
        error_log('VQ Checkout: Zone object: ' . print_r($zone, true));

        if (!$zone || !method_exists($zone, 'get_zone_locations')) {
            return array();
        }

        $locations = $zone->get_zone_locations();
        error_log('VQ Checkout: Zone locations: ' . print_r($locations, true));

        $vn_provinces = array();
        $all_vn_provinces_data = function_exists('vqcheckout_get_provinces') ? vqcheckout_get_provinces() : array();
        error_log('VQ Checkout: Total VN provinces available: ' . count($all_vn_provinces_data));

        foreach ($locations as $location) {
            error_log('VQ Checkout: Processing location type=' . $location->type . ', code=' . $location->code);

            if ($location->type === 'country' && $location->code === 'VN') {
                error_log('VQ Checkout: Zone covers entire VN, returning all provinces');
                return $all_vn_provinces_data;
            }

            if ($location->type === 'state') {
                $province_code = $location->code;
                if (strpos($province_code, 'VN:') === 0) {
                    $province_code = substr($province_code, 3);
                }
                $province_code = strtoupper($province_code);

                error_log('VQ Checkout: Checking province code: ' . $province_code);

                if (isset($all_vn_provinces_data[$province_code])) {
                    $vn_provinces[$province_code] = $all_vn_provinces_data[$province_code];
                    error_log('VQ Checkout: Added province: ' . $province_code);
                } else {
                    error_log('VQ Checkout: Province not found in data: ' . $province_code);
                }
            }
        }

        error_log('VQ Checkout: Final provinces count: ' . count($vn_provinces));
        error_log('VQ Checkout: Final provinces: ' . print_r(array_keys($vn_provinces), true));

        return $vn_provinces;
    }

    /**
     * Validate and save options
     */
    public function process_admin_options() {
        error_log('VQ Checkout: process_admin_options called for instance ' . $this->instance_id);
        error_log('VQ Checkout: POST data: ' . print_r($_POST, true));

        parent::process_admin_options();

        // Save additional complex data
        $this->save_ward_rates_data();
        $this->save_condition_data();

        error_log('VQ Checkout: Save complete');
    }

    /**
     * Save ward rates data from JSON
     */
    private function save_ward_rates_data() {
        $rates_json = isset($_POST['vq_ward_rates_data']) ? wp_unslash($_POST['vq_ward_rates_data']) : '';
        error_log('VQ Checkout: Ward rates JSON: ' . $rates_json);

        $rates = json_decode($rates_json, true);
        error_log('VQ Checkout: Decoded rates: ' . print_r($rates, true));

        if (is_array($rates)) {
            $this->instance_settings['ward_rates'] = $rates;
            $result = update_option($this->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this), 'yes');
            error_log('VQ Checkout: Ward rates saved, result: ' . ($result ? 'success' : 'failed'));
        } else {
            error_log('VQ Checkout: Ward rates NOT saved - not array');
        }
    }

    /**
     * Save condition data
     */
    private function save_condition_data() {
        // Save price conditions
        $price_cond = array();

        error_log('VQ Checkout: Saving conditions');
        error_log('VQ Checkout: vq_all_price_cond_min isset: ' . (isset($_POST['vq_all_price_cond_min']) ? 'yes' : 'no'));

        if (isset($_POST['vq_all_price_cond_min']) && is_array($_POST['vq_all_price_cond_min'])) {
            $mins = array_map('sanitize_text_field', $_POST['vq_all_price_cond_min']);
            $costs = isset($_POST['vq_all_price_cond_cost']) ? array_map('sanitize_text_field', $_POST['vq_all_price_cond_cost']) : array();

            error_log('VQ Checkout: Mins: ' . print_r($mins, true));
            error_log('VQ Checkout: Costs: ' . print_r($costs, true));

            foreach ($mins as $i => $min) {
                // Allow cost = 0 (free shipping when condition met)
                if (!empty($min) && isset($costs[$i]) && $costs[$i] !== '') {
                    $price_cond[] = array(
                        'min' => floatval($min),
                        'cost' => floatval($costs[$i]),
                    );
                }
            }
        }

        error_log('VQ Checkout: Final conditions: ' . print_r($price_cond, true));

        $this->instance_settings['all_price_condition'] = $price_cond;
        $result = update_option($this->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this), 'yes');
        error_log('VQ Checkout: Conditions saved, result: ' . ($result ? 'success' : 'failed'));
    }

    /**
     * Calculate shipping cost
     */
    public function calculate_shipping($package = array()) {
        if ($this->instance_id <= 0) {
            return;
        }

        $destination = $package['destination'] ?? array();
        $country = $destination['country'] ?? '';
        $ward_code = $destination['city'] ?? ''; // Ward stored in city field

        if ($country !== 'VN') {
            return;
        }

        $cart_total = 0;
        if (isset($package['cart_subtotal'])) {
            $cart_total = (float) $package['cart_subtotal'];
        } elseif (function_exists('WC') && WC()->cart) {
            $cart_total = (float) WC()->cart->get_subtotal();
        }

        // Find matching ward rate
        $matched_rate = $this->find_matching_ward_rate($ward_code);

        if ($matched_rate) {
            // Check for "No Shipping" flag
            if (isset($matched_rate['no_shipping']) && $matched_rate['no_shipping'] == 1) {
                return;
            }

            $cost = $this->calculate_cost_from_rate($matched_rate, $cart_total);
            $label = !empty($matched_rate['title']) ? $matched_rate['title'] : $this->title;
        } else {
            // Use default cost
            $cost = floatval($this->cost);
            $label = $this->title;
        }

        // Add handling fee
        if ($this->fee > 0) {
            $cost += floatval($this->fee);
        }

        $rate = array(
            'id'      => $this->get_rate_id(),
            'label'   => $label,
            'cost'    => $cost,
            'package' => $package,
        );

        $this->add_rate($rate);
    }

    /**
     * Find matching ward rate
     */
    private function find_matching_ward_rate($ward_code) {
        if (empty($ward_code) || empty($this->ward_rates)) {
            return null;
        }

        foreach ($this->ward_rates as $rate) {
            if (isset($rate['wards']) && is_array($rate['wards']) && in_array($ward_code, $rate['wards'])) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Calculate cost from rate configuration
     */
    private function calculate_cost_from_rate($rate, $cart_total) {
        $base_cost = isset($rate['cost']) ? floatval($rate['cost']) : 0;
        $conditions = isset($rate['conditions']) && is_array($rate['conditions']) ? $rate['conditions'] : array();

        if (empty($conditions)) {
            return $base_cost;
        }

        // Sort conditions by min amount ASC
        usort($conditions, function($a, $b) {
            return ($a['min'] ?? 0) <=> ($b['min'] ?? 0);
        });

        $matched_cost = null;
        foreach ($conditions as $cond) {
            if ($cart_total >= ($cond['min'] ?? 0)) {
                $matched_cost = floatval($cond['cost'] ?? 0);
            }
        }

        return $matched_cost !== null ? $matched_cost : $base_cost;
    }
}
