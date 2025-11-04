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
    // Xác thực Nonce (Frontend hoặc Admin)
    $nonce_check = check_ajax_referer('vqcheckout_ajax_nonce', 'nonce', false) || check_ajax_referer('vqcheckout_admin_nonce', 'nonce', false);

    if (!$nonce_check) {
        wp_send_json_error('Invalid nonce');
    }

    $province_code = isset($_POST['province_code']) ? sanitize_text_field($_POST['province_code']) : '';

    if (empty($province_code)) {
        wp_send_json_error('Missing province code');
    }

    // Lấy dữ liệu từ hàm Utility (sử dụng cấu trúc tối ưu đã cache)
    $wards = vqcheckout_get_wards_by_province($province_code);

    if (empty($wards)) {
        // Trả về mảng rỗng thay vì lỗi nếu không tìm thấy (cải thiện UX)
        wp_send_json_success(array());
        return;
    }

    // Format data for response (Select2 compatible)
    $response_data = array();
    foreach ($wards as $ward_code => $ward_data) {
        $response_data[] = array(
            'id'   => $ward_code,
            'text' => $ward_data['name']
        );
    }

    wp_send_json_success($response_data);
}

/**
 * AJAX: Load wards for Shipping Method settings (Admin WC Zones) (File 05)
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
        
        // Nếu mã tỉnh là VN:CODE (format của WC Zone Location), loại bỏ VN:
        if (strpos($province_code, 'VN:') === 0) {
            $province_code = substr($province_code, 3);
        }

        // Đảm bảo mã tỉnh viết hoa (vì keys là uppercase slugs)
        $province_code = strtoupper($province_code);

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
        wp_send_json_success(array()); // Trả về mảng rỗng
        return;
    }

    // Sắp xếp các nhóm theo tên Tỉnh/Thành (Cải thiện UX)
    usort($results, function($a, $b) {
        return strcmp($a['text'], $b['text']);
    });

    wp_send_json_success($results);
}