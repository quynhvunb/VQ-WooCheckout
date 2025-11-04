=== Plugin Name ===
Contributors: levantoan
Tags: district shipping, shipping, tỉnh thành, quận huyện, tính phí ship cho quận huyện
Requires at least: 4.1
Requires PHP: 5.2.4
Tested up to: 5.6.1
Stable tag: 4.6.2

/*
* 1 số code hỗ trợ thêm
* Author Lê Văn Toản
* http://levantoan.com/plugin-tinh-phi-van-chuyen-cho-quan-huyen-trong-woocommerce/
*/
=========================================================
= Support hiển thị tên tỉnh thành; quận huyện; xã phường khi dùng plugin =
= Advanced Order Export For WooCommerce =
= https://wordpress.org/plugins/woo-order-export-lite/ =

add_filter('woe_get_order_value_billing_state', 'devvn_billing_state_format', 10, 3);
function devvn_billing_state_format($value, $order, $field){
    return vn_checkout()->get_name_city($value);
}

add_filter('woe_get_order_value_billing_city', 'devvn_billing_city_format', 10, 3);
function devvn_billing_city_format($value, $order, $field){
    return vn_checkout()->get_name_district($value);
}

add_filter('woe_get_order_value_billing_address_2', 'devvn_billing_address2_format', 10, 3);
function devvn_billing_address2_format($value, $order, $field){
    return vn_checkout()->get_name_village($value);
}


/*Code xóa bỏ 1 số tỉnh thành không hỗ trợ*/
add_filter('vn_checkout_tinh_thanhpho','custom_devvn_remove_states_vn');
function custom_devvn_remove_states_vn($tinhthanh){
    unset($tinhthanh['ANGIANG']);
    return $tinhthanh;
}

add_filter('vn_checkout_tinh_thanhpho','custom_devvn_states_vn');
function custom_devvn_states_vn($tinhthanh){
    $tinhthanh = array(
        "HOCHIMINH" => "TP Hồ Chí Minh",
    );
    return $tinhthanh;
}

/*Code custom lại tiêu đề của phương thức vận chuyển*/
add_filter('devvn_shippingrate_title', 'custom_devvn_shippingrate_title', 10, 3);
function custom_devvn_shippingrate_title($title, $rate, $rate_text){
    if ( isset( $rate['box_title'] ) && $rate['box_title'] == "" ) {
        $shipping_method_id = isset($rate['shipping_method_id']) ? intval($rate['shipping_method_id']) : '';
        if($shipping_method_id) {
            $methob_setting = get_option('woocommerce_devvn_district_zone_shipping_'.$shipping_method_id.'_settings');
            if($methob_setting && isset($methob_setting['title']) && $methob_setting['title']) {
                if ($rate_text == 'free') {
                    $title = $methob_setting['title'] . ': ' .__('Miễn phí vận chuyển', 'devvn');
                }
            }
        }
    }
    return $title;
}

/*Code tiêu đề "Miễn phí vận chuyển" khi set riêng cho từng quận huyện*/
add_filter('devvn_shippingrate_title', 'custom_free_ship_title', 10, 3);
function custom_free_ship_title($title, $rate, $rate_text){
    if($rate_text == 'free'){
        $title = 'Miễn phí vận chuyển';
    }
    return $title;
}

/*Subtotal bao gồm giá giảm*/

add_filter('devvn_district_zone_subtotal','devvn_district_zone_subtotal_func');
function devvn_district_zone_subtotal_func($subtotal){
    $subtotal = $subtotal - WC()->cart->get_cart_discount_total();
    return $subtotal;
}

/*Thay tiêu đề thành miễn phí*/
function change_title_rate( $rates ) {
    foreach ( $rates as $rate_id => $rate ) {
        if ( $rate->get_cost() == 0 && in_array($rate->get_method_id() , array('devvn_district_zone_shipping')) ) {
            $rates[ $rate_id ]->label = 'Miễn phí vận chuyển';
        }
    }
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'change_title_rate', 200 );

/*Ẩn phí ship khác nếu có 0đ*/
function hide_other_when_zero( $rates ) {
    $free = array();
    foreach ( $rates as $rate_id => $rate ) {
        if ( $rate->get_cost() == 0 && in_array($rate->get_method_id() , array('devvn_district_zone_shipping')) ) {
            $free[ $rate_id ] = $rate;
            break;
        }
    }
    return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'hide_other_when_zero', 200 );
=========================================================

== Changelog ==

= 4.7.8 - 09.10.2025 =

* Fix miễn phí vận chuyển theo tổng đơn khi có mã giảm giá

= 4.7.7 - 08.09.2025 =

* Hoàn thiện địa chỉ 2 cấp

= 4.7.6 - 21.08.2025 =

* Fix charset với 1 số web sử dụng charset latin1

= 4.7.5 - 19.08.2025 =

* Update vn checkout tương thích với các plugin shipping khác

= 4.7.4 - 11.03.2025 =

* Thêm tuỳ chọn dùng đơn vị hành chính cũ

= 4.7.3 - 06.03.2025 =

Global
* Fix lỗi hàm wp_mail trong 1 số trường hợp
* Update dữ liệu cơ sở hành chính mới nhất 06.03.2025

= 4.7.2 - 27.12.2024 =

Global
* Fix trường hợp số điện thoại nhập số 0 vẫn cho đặt hàng

= 4.7.1 - 25.12.2024 =

Global
* Thêm tuỳ chọn số điện thoại định dạng ở VN hay không

= 4.7.0 - 14.11.2024 =

Global
* Update hàm dịch tương thích với WP 6.7.0

= 4.6.9 - 01.11.2024 =

Global
* Thêm chức năng chặn order với IP hoặc từ khoá
* Thêm yêu cầu reCAPTCHA khi tạo đơn hàng (optional). Có cả reCAPTCHA V2 và V3

= 4.6.8 - 27.10.2024 =

Global
* Sửa lại giao diện respon của đơn hàng trong admin khi kích hoạt HPOS

= 4.6.7 - 18.07.2024 =

* Fix cảnh báo với PHP8

Global

* Fix không tự động load select2 trong chỉnh sửa hồ sơ thành viên


= 4.6.6 - 09.07.2024 =

* Update encode tương thích với PHP8.2

= 4.6.5 - 17.05.2024 =

GLOBAL
* Fix không load được xã/phường trong admin
* Thêm filter check_file_open_status_transient để bỏ qua check file get-address.php
add_filter('check_file_open_status_transient', function(){
    return 200;
});

= 4.6.4 - 25.03.2024 =

GLOBAL
* Fix với 1 số theme chặn bỏ selectWoo

= 4.6.3 - 19.03.2024 =

* Gửi mail thông báo cho admin khi license bị lỗi. Tránh ảnh hưởng tới khách hàng khi mua hàng

GLOBAL

* Tải dữ liệu tỉnh thành bằng file json
* Sửa lỗi khi một số Quận/Huyện không có dữ liệu xã/phường/thị trấn sẽ không bắt buộc nhập nữa như Huyện Bạch Long Vĩ, Huyện Cồn Cỏ và Huyện Hoàng Sa
* Thêm selectWoo vào field chọn Quận và Xã/Phường

= 4.6.2 - 07.01.2024 =

* Thêm hàm vn_checkout()->convert_gram_to_weight() để convert gram sang đúng đơn vị trọng lượng của woo đang để
* Thêm hàm vn_checkout()->convert_cm_to_dimension() để convert cm sang đúng đơn vị kích thước của woo đang để

= 4.6.1 - 22.12.2023 =

* Update core plugin

= 4.6.0 - 28.09.2023 =

GLOBAL
* Fix lỗi thay đổi trang danh sách đơn hàng ở Woo 8.x
* Thêm ghi chú của khách vào trang danh sách đơn hàng

= 4.5.9 - 19.09.2023 =

* Update fix điều kiện tổng đơn hàng khi có mã giảm giá

= 4.5.8 - 17.09.2023 =

GLOBAL
* Fix lỗi không tính phí ship khi có sẵn thông tin người dùng

= 4.5.7 - 05.05.2023 =

GLOBAL:
* Tối ưu lại hình thức load địa chỉ

= 4.5.6 - 24.04.2023 =

* Tối ưu core

GLOBAL:
* Nâng cấp tương thích với chức năng High-Performance Order Storage (HPOS) trong Woocommerce. 1 chức năng tối ưu cho order trong Woocommerce và sẽ mặc định ở Woocommerce V8.0
* Nâng cấp thư viện recaptcha để hỗ trợ PHP8

= 4.5.5 - 19.03.2023 =

GLOBAL:
* Hỗ trợ tương thích với plugin "Deposits & Partial Payments for WooCommerce"
* Fix lỗi update lại trang thanh toán khi thay đổi hình thức thanh toán ở plugin GHTK

= 4.5.4 - 24.12.2022 =

GLOBAL
* Thêm placeholder vào field sđt và email
* Fix lỗi không load js với 1 số theme
* Thêm option chuyển giá sang dạng chữ
* Cập nhật thêm thông tin địa giới hành chính của Huyện Lý Sơn, Tỉnh Quảng Ngãi

= 4.5.3 - 21.12.2022 =

GLOBAL
* Thêm tuỳ chọn hiện trường Postcode. Mặc định là ẩn
* Fix lỗi một số trường hợp đã custom form checkout trước đó
* Fix lỗi với plugin Cartflows

= 4.5.2 - 20.12.2022 =

GLOBAL
* Fix lỗi không ẩn được field xã phường khi đã cài đặt trong setting
* Fix tương thích với 1 số theme

= 4.5.1 - 19.12.2022 =

GLOBAL
* Fix hiển thị địa chỉ với tiếng Việt trong đơn in với mẫu riêng
* Fix XSS security. Thanks for MINKYU (Patchstack Alliance)
* Tối ưu lại các field trong checkout, các field trong Sửa địa chỉ khách hàng...
* Fix lỗi: Sửa chức năng sao chép địa chỉ thanh toán sang địa chỉ giao hàng khi sửa đơn hàng
* Fix lỗi khi sử dụng chức năng tự động điền (autocomplete) của trình duyệt các field trong trang checkout

= 4.5.0 - 05.11.2022 =

GLOBAL
* Thêm hình ảnh trong list sản phẩm tại danh sách đơn hàng trong admin

= 4.4.9 - 09.08.2022 =

* Tối ưu 1 số hàm
* Thêm filter devvn_district_zone_subtotal để tuỳ chỉnh giá subtotal trước khi tính phí ship

= 4.4.8 - 07.07.2022 =

* Cập nhật lại chức năng tính phí vận chuyển khi "thay đổi xã/phường" ở phần "giao tới địa chỉ khác" trong trang checkout
* Tương thích 6.6.x

= 4.4.7 - 27.02.2022 =

* Thêm điều kiện tổng đơn hàng vào chức năng miễn phí vận chuyển theo hình thức thanh toán
* Cập nhật lại dữ liệu địa giới hành chính

= 4.4.6 - 02.11.2021 =

* Thêm filter enable_script_vn_checkout để có thể gọi js ở bất kỳ page nào. Mặc định của có ở trang checkout và trang sửa địa chỉ ở my account
apply_filters('enable_script_vn_checkout', false)
* Đưa dữ liệu địa giới hành chính vào trong SQL để tăng tốc độ load khi ở trang danh sách đơn hàng (Shop order)
* Fix lỗi tên sp với Wholesale plugin

= 4.4.5 - 27.09.2021 =

* Thêm tùy chọn ẩn phương thức GHTK và ViettelPost nếu có các shipping methob khác
* Tối ưu lại bản dịch tiếng Việt
* Thêm chức năng làm tròn phí ship. Ví dụ: 18.050VND -> 18.000VND hoặc 18.503VND -> 19.000VND
* Fix lỗi "Không vận chuyển tới đây" khi có 1 shipping methob

= 4.4.4 - 24.09.2021 =

* Fix lỗi js báo thiếu thư viện magnificPopup

= 4.4.3 - 17.09.2021 =

* Fix: Lỗi không load được quận huyện phần cài đặt trong admin

= 4.4.2 - 16.09.2021 =

* Tối ưu core vn checkout để tương thích với GHTK và ViettelPost plugin
* Fix lỗi cảnh báo lỗi wpdb::prepare khi active plugin

= 4.4.1 - 13.09.2021 =

* update vn checkout core
* Thay đổi filter sang filter mới
devvn_states_vn => vn_checkout_tinh_thanhpho

= 4.4.0 - 11.09.2021 =

* Add: Hỗ trợ tính phí vận chuyển tới cấp Xã/Phường/Thị trấn
* Tối ưu lại cấu trúc của plugin để hoạt động chung với các plugin shipping khác
* Bắt buộc nhập license để hoạt động
* PHP >= 7.2 và có kích hoạt ioncube

= 4.3.2 - 29.08.2021 =

* Tối ưu lại mục update qua license
* Thêm bộ lọc đơn hàng theo quận huyện trong admin
* Fix lỗi order giá vận chuyển theo quận trong admin
* Tối ưu lại chức năng không vận chuyển tới quận huyện

= 4.3.1 - 04.06.2021 =

* Update: Chuyển họ và tên sang dùng first_name thay vì last_name như các phiên bản trước

= 4.3.0 - 15.05.2021 =

* Update tối ưu lại hàm check file get-address.php để tránh gây tốn tài nguyên hosting/vps

= 4.2.9 - 28.04.2021 =

* Update danh sách địa giới hành chính mới nhất ngày 27/04/2021

= 4.2.8 - 24.02.2021 =

* Update danh sách địa giới hành chính mới nhất ngày 15/01/2021

= 4.2.7 - 13.12.2020 =

* Fix với WordPress 5.6 và Woo 4.8.x
* Xóa bỏ đường dẫn thừa trong đơn hàng do bản trước quên không tắt debug :)

= 4.2.6 - 25.04.2020 =

* Fix chọn quận huyện trong trang giỏ hàng
* Thay lại cách load địa giới hành chính để load nhanh hơn

= 4.2.5 - 16.04.2020 =

* Chỉnh lại cách hiển thị tiêu đề thay cho Miễn Phí Vận Chuyển. Có thể để là Báo giá sau hoặc bất kỳ nội dung nào bạn thích
* Thêm bộ lọc đơn hàng theo Ngày, tháng, năm và theo tỉnh/thành phố
* Thêm filter devvn_states_vn để có thể custom lại list tỉnh/thành phố
* Update: Định dạng lại tên quận huyện và xã phường khi lấy thông tin qua API
* Add: Thêm 2 filter devvn_district_zone_rate_modify và devvn_district_zone_subtotal

= 4.2.4 - 14.08.2019 =

* Fix: Sửa lỗi với phiên bản 3.7.0
* Update: Thêm các khu ở huyện Côn Đảo

= 4.2.3 - 30.05.2019 =

* Fix: Sửa lại chức năng lấy địa chỉ qua số điện thoại mua hàng trước đó khi khách chưa có tài khoản

= 4.2.2 - 27.05.2019 =

* Update: Sắp xếp quận huyện và xã phường theo A->Z

= 4.2.1 - 24.05.2019 =

* Add: Thêm tính năng "Lấy địa chỉ mua hàng trước" bằng "số điện thoại khách hàng"
* Fix: Sửa lỗi không load được quận huyện khi thêm đơn hàng mới trong admin

= 4.2.0 - 14.02.2019 =

* Fix: sửa lỗi thứ tự các field trong phần địa chỉ của khách hàng
* Add: Thêm chức năng sửa số điện thoại nhận hàng trong admin

= 4.1.9.1 - 07.11.2018 =

*Fix: Fix nhanh lỗi hiển thị địa chỉ ở bản update 4.1.9

= 4.1.9 - 06.11.2018 =

* Fix: Sửa một số lỗi js trong lúc cài đặt phí vận chuyển cho quận huyện
* Update: Cập nhật tương thích với Woocommerce 3.5.1

= 4.1.8 - 30.10.2018 =

* Update: Cập nhật tương thích với Woocommerce 3.5.0

= 4.1.7 - 27.06.2018 =

* Update: Sắp xếp tỉnh thành theo chữ cái A-Z. Chuyển Hà Nội và Hồ Chí Minh lên đầu tiên
* FIX: Hiển thị tên tỉnh thành trong thông tin địa chỉ đơn hàng

= 4.1.6 - 01.06.2018 =

* Thay đổi giá trị của tỉnh thành (Từ số -> chữ)
* Chuyển Hà Nội và Hồ Chí Minh lên đầu danh sách tỉnh thành
* Thay đổi link cài đặt thành Woocommerce -> Woo VN Checkout
* Chia địa chỉ thành 2 cột cho form checkout gọn gàng hơn
* Thêm placeholder cho số điện thoại và email

= 4.1.5 - 03.04.2018 =

* FIX: Sửa lỗi không hiển thị trường first_name khi kích hoạt hỗ trợ thanh toán qua Alepay

= 4.1.4 - 12.03.2018 =

* Update: Thêm js ở phần tính phí vận chuyển tại trang giỏ hàng để phù hợp với 1 số theme
* Update: Hiển thị tên của tỉnh/thành phố, quận huyện và xã phường thị trấn trong APP IOS của Woocommerce

= 4.1.3 - 08.03.2018 =

* Update: Với bản Woo 3.3.x đã sử dụng được tính năng tính phí vận chuyển theo quận/huyện tại trang giỏ hàng.

= 4.1.2 - 07.03.2018 =

* Fix: Sửa lỗi khi ẩn mục xã phường ở bản 4.1.1

= 4.1.1 - 06.03.2018 =

* Update: Tự động chuyển đổi gram (g) sang kilogam để tính phí vận chuyển
* Add: Thêm mục license key để update plugin tự động

= 4.1.0 - 27.02.2018 =

* Update: Support cổng thanh toán Alepay (Setting -> Cài đặt GHTK -> Kích hoạt Alepay)
* Update: 99% Tương thích với plugin "WooCommerce Checkout Field Editor (Manager) Pro"
* Update: Cho phép chỉnh sửa địa chỉ trong trang my account
* Update: Ghi nhớ địa chỉ của khách hàng đã checkout. Khách không cần nhập lại địa chỉ từ lần thứ 2 trở đi
* Fix: sửa 1 số lỗi với Flatsome theme