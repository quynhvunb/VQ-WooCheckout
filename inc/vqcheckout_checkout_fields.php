<?php
/**
 * VQ Checkout - Checkout Fields Customization (File 04)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Checkout Fields customization
 */
function vqcheckout_init_checkout_fields() {
    // Customize default address fields (applies globally: checkout, account edit)
    add_filter('woocommerce_default_address_fields', 'vqcheckout_customize_default_address_fields', 30);
    
    // Customize checkout fields (specific for checkout page)
    add_filter('woocommerce_checkout_fields', 'vqcheckout_customize_checkout_fields', 30);
    
    // Validate checkout fields
    add_action('woocommerce_after_checkout_validation', 'vqcheckout_validate_checkout_fields', 10, 2);

    // Handle saving custom fields (like Gender)
    add_action('woocommerce_checkout_update_order_meta', 'vqcheckout_save_custom_checkout_fields');
    
    // Display custom fields in admin order view (Billing)
    // Note: Gender display in admin is handled in admin_order.php for consistency
}
add_action('init', 'vqcheckout_init_checkout_fields');


/**
 * Customize default address fields (Core structure and behavior)
 */
function vqcheckout_customize_default_address_fields($fields) {
    
    // 1. Configure 2-tier address system
    // Reset priorities to WC defaults initially
    
    // State -> Tỉnh/Thành phố
    if (isset($fields['state'])) {
        $fields['state']['label'] = __('Tỉnh/Thành phố', 'vq-checkout');
        $fields['state']['class'] = array('form-row-wide', 'address-field', 'update_totals_on_change', 'vqcheckout-province-field');
        $fields['state']['priority'] = 80; // WC default
        $fields['state']['required'] = true;
    }
    
    // City -> Xã/Phường
    if (isset($fields['city'])) {
        $fields['city']['type'] = 'select';
        $fields['city']['label'] = __('Xã/Phường', 'vq-checkout');
        $fields['city']['class'] = array('form-row-wide', 'address-field', 'update_totals_on_change', 'vqcheckout-ward-field');
        $fields['city']['priority'] = 70; // WC default
        $fields['city']['required'] = true;
        $fields['city']['options'] = array('' => __('Chọn tỉnh/thành trước...', 'vq-checkout'));
    }
    
    // Address 1
    if (isset($fields['address_1'])) {
        $fields['address_1']['priority'] = 50; // WC default
        $fields['address_1']['label'] = __('Địa chỉ', 'vq-checkout');
        $fields['address_1']['placeholder'] = __('Số nhà, tên đường...', 'vq-checkout');
    }
    
    // Address 2 - Always hide
    if (isset($fields['address_2'])) {
        unset($fields['address_2']);
    }

    // 2. Apply display customizations based on Settings (File 07)

    // Country field
    if (isset($fields['country'])) {
        $fields['country']['priority'] = 40; // WC default
        // If 'Show Country' option is disabled
        if (vqcheckout_get_option('show_country', '0') !== '1') {
            $fields['country']['type'] = 'hidden';
            $fields['country']['required'] = false;
        }
    }
    
    // Postcode field
    if (isset($fields['postcode'])) {
        $fields['postcode']['priority'] = 90; // WC default
        // If 'Show Postcode' option is disabled
        if (vqcheckout_get_option('show_postcode', '0') !== '1') {
            unset($fields['postcode']);
        } else {
            $fields['postcode']['required'] = false; // Always optional if shown
            $fields['postcode']['label'] = __('Mã bưu điện (Tùy chọn)', 'vq-checkout');
        }
    }

    // Last Name (Họ)
    if (isset($fields['last_name'])) {
        // If 'Show Last Name' option is disabled
        if (vqcheckout_get_option('show_last_name', '0') !== '1') {
            unset($fields['last_name']);
            
            // Adjust First Name to "Họ và Tên" and full width
            if (isset($fields['first_name'])) {
                $fields['first_name']['label'] = __('Họ và Tên', 'vq-checkout');
                // Remove form-row-first/last, add form-row-wide
                $fields['first_name']['class'] = array_diff($fields['first_name']['class'] ?? [], array('form-row-first', 'form-row-last'));
                $fields['first_name']['class'][] = 'form-row-wide';
            }
        } else {
            // If shown, ensure labels are Vietnamese
            if (isset($fields['first_name'])) {
                $fields['first_name']['label'] = __('Tên', 'vq-checkout');
            }
            $fields['last_name']['label'] = __('Họ', 'vq-checkout');
        }
    }
    
    return $fields;
}

/**
 * Customize checkout fields specifically for the checkout page (UPDATED)
 */
function vqcheckout_customize_checkout_fields($fields) {
    
    // Add Gender field (Xưng hô) if enabled
    if (vqcheckout_get_option('enable_gender', '1') === '1') {
        $fields['billing']['billing_gender'] = array(
            'type' => 'select',
            'label' => __('Xưng hô', 'vq-checkout'),
            'required' => true,
            'class' => array('form-row-first', 'vqcheckout-gender-field'),
            'priority' => 5, // Default position near name
            'options' => array(
                '' => __('Chọn xưng hô...', 'vq-checkout'),
                'male' => __('Anh', 'vq-checkout'),
                'female' => __('Chị', 'vq-checkout'),
            )
        );

        // Adjust Name field layout if Gender is present
        if (isset($fields['billing']['billing_first_name'])) {
            // If First Name is currently full width (due to hidden Last Name)
            if (in_array('form-row-wide', $fields['billing']['billing_first_name']['class'])) {
                 $fields['billing']['billing_first_name']['class'] = array_diff($fields['billing']['billing_first_name']['class'], array('form-row-wide'));
                 $fields['billing']['billing_first_name']['class'][] = 'form-row-last';
            }
        }
    }

    // Customize Email (Optional)
    if (vqcheckout_get_option('not_required_email', '1') === '1') {
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['required'] = false;
            $fields['billing']['billing_email']['label'] = __('Email (Tùy chọn)', 'vq-checkout');
        }
    }

    // Customize Phone Label
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = __('Số điện thoại', 'vq-checkout');
    }

    // NEW: Optimize Field Order (Move Phone/Email up)
    if (vqcheckout_get_option('optimize_field_order', '1') === '1') {
        $priority = 1; // Start priority at the very top

        // Move Phone up
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['priority'] = $priority++;
            // Make phone full width
            $fields['billing']['billing_phone']['class'] = array('form-row-wide');
        }
        
        // Move Email up
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['priority'] = $priority++;
            // Make email full width
            $fields['billing']['billing_email']['class'] = array('form-row-wide');
        }

        // Adjust priorities of subsequent fields (Gender, Name)
        if (isset($fields['billing']['billing_gender'])) {
            $fields['billing']['billing_gender']['priority'] = $priority++;
        }
        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['priority'] = $priority++;
        }
        if (isset($fields['billing']['billing_last_name'])) {
             $fields['billing']['billing_last_name']['priority'] = $priority++;
        }
    }
    
    return $fields;
}

// (Các hàm Validation và Save Custom Fields giữ nguyên)
// ... (vqcheckout_validate_checkout_fields, vqcheckout_save_custom_checkout_fields)

/**
 * Validate checkout fields
 */
function vqcheckout_validate_checkout_fields($data, $errors) {
    // 1. Vietnam Phone Validation (if enabled)
    if (vqcheckout_get_option('phone_vn_validation', '1') === '1') {
        $phone = isset($data['billing_phone']) ? $data['billing_phone'] : '';
        
        // Check only if phone is not empty (WC core handles 'required' check)
        if (!empty($phone) && function_exists('vqcheckout_is_valid_vn_phone') && !vqcheckout_is_valid_vn_phone($phone)) {
            $errors->add(
                'validation',
                __('Số điện thoại không đúng định dạng Việt Nam (VD: 0912345678).', 'vq-checkout')
            );
        }
    }

    // 2. 2-level Address Validation (Check if Ward belongs to Province)
    $sections = array('billing');
    // Check if shipping address is different
    if (isset($data['ship_to_different_address']) && $data['ship_to_different_address']) {
        $sections[] = 'shipping';
    }

    foreach ($sections as $section) {
        // Determine the country. If field is hidden, default is VN (handled by Core).
        $country = isset($data[$section . '_country']) ? $data[$section . '_country'] : 'VN';

        // Only validate if country is VN
        if ($country !== 'VN') {
            continue;
        }

        $province_code = isset($data[$section . '_state']) ? $data[$section . '_state'] : '';
        $ward_code = isset($data[$section . '_city']) ? $data[$section . '_city'] : '';

        // WC already checks if required fields are filled. We check validity if they are filled.
        if (!empty($province_code) && !empty($ward_code)) {
            // Use utility function to check validity
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
 * Save custom checkout fields (e.g., Gender)
 */
function vqcheckout_save_custom_checkout_fields($order_id) {
    // Save Gender
    if (isset($_POST['billing_gender']) && !empty($_POST['billing_gender'])) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $gender = sanitize_text_field($_POST['billing_gender']);
        $order->update_meta_data('_billing_gender', $gender);
        $order->save();

        // Optional: Save to user meta if user is logged in
        $user_id = $order->get_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'billing_gender', $gender);
        }
    }
}