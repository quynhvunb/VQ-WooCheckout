Các trường dữ liệu trong mục Setting của plugins gồm đầy đủ các trường như form tham khảo dưới đây, lưu ý khi đã có trong setting page thì nó phải hoạt động hoàn hảo (tức là bạn phải check cả các logic liên quan tới từng chức năng):
<form method="post" action="options.php" novalidate="novalidate">
	<input type="hidden" name="option_page" value="devvn-vn-checkout-options-group"><input type="hidden" name="action" value="update"><input type="hidden" id="_wpnonce" name="_wpnonce" value="43f1bddb27"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=devvn-district-address">    <h2>Checkout field</h2>
    <table class="form-table infor-shop">
        <tbody>
        <tr>
            <th scope="row"><label for="phone_vn">Định dạng SĐT ở VN</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[phone_vn]" checked="checked" value="1"> Bắt buộc SĐT có định dạng ở VN</label>
                <br>
                <small>Định dạng +84xxx hoặc 0xxx</small>
            </td>
        </tr>
                <tr>
            <th scope="row"><label for="alepay_support">Hiển thị trường country và last name</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[alepay_support]" value="1"> Hiển thị trường country và last name</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="enable_postcode">Hiện trường postcode cho Việt Nam</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[enable_postcode]" value="1"> Hiện trường postcode cho Việt Nam</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="show_postcode">Hiện trường postcode</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[show_postcode]" value="1"> Check vào để hiện trường postcode. Mặc định là ẩn</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="active_orderstyle">Xưng hô</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[enable_gender]" checked="checked" value="1"> Hiển thị mục chọn cách xưng hô Anh/Chị</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="not_required_email">KHÔNG bắt buộc nhập Email</label></th>
            <td>
                <label><input type="checkbox" name="devvn_vncheckout[not_required_email]" checked="checked" value="1" id="not_required_email"> Trường email sẽ KHÔNG bắt buộc phải nhập nữa</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="load_address">Hình thức tải địa chỉ</label></th>
            <td>

                <label><input type="radio" name="devvn_vncheckout[load_address]" checked="&quot;checked&quot;" value="2"> Tải bằng file json. Tốc độ cực nhanh. Khuyến khích dùng cái này.</label><br>
                <label><input type="radio" name="devvn_vncheckout[load_address]" value="3"> Tải bằng admin-ajax.php. Tốc độ chậm hơn</label><br>
                <label><input type="radio" name="devvn_vncheckout[load_address]" value="4"> Tải bằng get-address.php trong plugin. Tốc độ rất nhanh. Nhưng nếu chặn thực thi php trong plugin sẽ không hoạt động được.</label>
            </td>
        </tr>
        </tbody>
    </table>

    <hr>
    <h2 style="display: none">Hiển thị phí vận chuyển trong trang chi tiết sản phẩm <span class="new_label">Mới</span></h2>
    <table class="form-table infor-shop" style="display: none">
        <tbody>
            <tr>
                <th scope="row"><label for="enable_ship_single">Kích hoạt</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[enable_ship_single]" value="1"> Kích hoạt tính phí vận chuyển ở chi tiết sản phẩm</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ship_single_hook">Tên hook</label></th>
                <td>
                    <input type="text" name="devvn_vncheckout[ship_single_hook]" value="woocommerce_single_product_summary"><br>
                    <small>Mặc định: woocommerce_single_product_summary</small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="ship_single_priority">Độ ưu tiên</label></th>
                <td>
                    <input type="text" name="devvn_vncheckout[ship_single_priority]" value="36"><br>
                    <small>Mặc định: 36</small>
                </td>
            </tr>
        </tbody>
    </table>

    <hr>
    <h2>Cài đặt chung</h2>
    <table class="form-table">
        <tbody>
                        <tr>
                <th scope="row"><label for="convert_price_text">Chuyển giá sang dạng chữ</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[convert_price_text]" value="1" id="convert_price_text"> Cho phép chuyển giá sang dạng chữ</label><br>
                    <small>Ví dụ:<br>
                        900đ =&gt; 900đ<br>
                        18.000đ =&gt; 18k<br>
                        18.200đ =&gt; 18k200<br>
                        18.200.000đ =&gt; 18tr200<br>
                        1.820.000.000đ =&gt; 1tỷ820
                    </small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="to_vnd">Chuyển ₫ sang VNĐ</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[to_vnd]" value="1" id="to_vnd"> Cho phép chuyển sang VNĐ</label><br>
                    <small>Xem thêm <a href="http://levantoan.com/thay-doi-ky-hieu-tien-te-dong-viet-nam-trong-woocommerce-d-sang-vnd/" target="_blank"> cách thiết lập đơn vị tiền tệ ₫ (Việt Nam đồng)</a></small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="remove_methob_title">Loại bỏ tiêu đề vận chuyển</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[remove_methob_title]" checked="checked" value="1" id="remove_methob_title"> Loại bỏ hoàn toàn tiêu đề của phương thức vận chuyển</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="freeship_remove_other_methob">Ẩn phương thức khi có free-shipping</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[freeship_remove_other_methob]" checked="checked" value="1" id="freeship_remove_other_methob"> Ẩn tất cả những phương thức vận chuyển khác khi có miễn phí vận chuyển</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="active_vnd2usd">Kích hoạt chuyển đổi VNĐ sang USD</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[active_vnd2usd]" value="1"> Kích hoạt chuyển đổi VNĐ sang USD để có thể sử dụng paypal</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vnd_usd_rate">VNĐ quy đổi sang tiền</label></th>
                <td>
                    <select name="devvn_vncheckout[vnd2usd_currency]" id="vnd2usd_currency">
                        <option value="AUD">AUD</option><option value="BRL">BRL</option><option value="CAD">CAD</option><option value="MXN">MXN</option><option value="NZD">NZD</option><option value="HKD">HKD</option><option value="SGD">SGD</option><option selected="selected" value="USD">USD</option><option value="EUR">EUR</option><option value="JPY">JPY</option><option value="TRY">TRY</option><option value="NOK">NOK</option><option value="CZK">CZK</option><option value="DKK">DKK</option><option value="HUF">HUF</option><option value="ILS">ILS</option><option value="MYR">MYR</option><option value="PHP">PHP</option><option value="PLN">PLN</option><option value="SEK">SEK</option><option value="CHF">CHF</option><option value="TWD">TWD</option><option value="THB">THB</option><option value="GBP">GBP</option><option value="RMB">RMB</option><option value="RUB">RUB</option>                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="vnd_usd_rate">Số quy đổi</label></th>
                <td>
                    <input type="number" min="0" name="devvn_vncheckout[vnd_usd_rate]" value="22745" id="vnd_usd_rate"> <br>
                    <small>Tỷ giá quy đổi từ VNĐ</small>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="active_orderstyle">Thay đổi giao diện trang đơn hàng</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[active_orderstyle]" checked="checked" value="1"> Thay đổi giao diện trang danh sách đơn hàng</label>
                </td>
            </tr>
                        <tr>
                <th scope="row"><label for="hide_special_method">Làm tròn phí ship</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[roundup_ship]" value="1" id="roundup_ship"> Có làm tròn phí ship</label>
                    <br><small>Ví dụ: <span class="woocommerce-Price-amount amount"><bdi>18.050&nbsp;<span class="woocommerce-Price-currencySymbol">₫</span></bdi></span> -&gt; <span class="woocommerce-Price-amount amount"><bdi>18.000&nbsp;<span class="woocommerce-Price-currencySymbol">₫</span></bdi></span> hoặc <span class="woocommerce-Price-amount amount"><bdi>18.503&nbsp;<span class="woocommerce-Price-currencySymbol">₫</span></bdi></span> -&gt; <span class="woocommerce-Price-amount amount"><bdi>19.000&nbsp;<span class="woocommerce-Price-currencySymbol">₫</span></bdi></span></small>
                </td>
            </tr>
                    </tbody>
    </table>

    <hr>
    <h2>Lấy địa chỉ tự động	</h2>
    <p>Lấy địa chỉ tự động từ SĐT đặt hàng của khách hàng</p>
    <table class="form-table infor-shop">
        <tbody>
            <tr>
                <th scope="row"><label for="enable_getaddressfromphone">Lấy địa chỉ tự động</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[enable_getaddressfromphone]" checked="checked" value="1"> Lấy địa chỉ tự động</label>
                    <br><small>Chức năng này cho phép nhập SĐT để lấy địa chỉ đã có của khách hàng.</small>
                </td>
            </tr>
        </tbody>
    </table>

    <hr>
    <h2>Cấu hình Google reCAPTCHA <span class="new_label">Mới</span></h2>
    <p>Chức năng này để thêm Google reCAPTCHA vào trang checkout để tránh spam</p>
    <p>Tạo sitekey và secret key <a href="https://www.google.com/recaptcha/admin" target="_blank" title="">tại đây</a></p>
    <table class="form-table infor-shop">
        <tbody>
            <tr>
                <th scope="row"><label for="enable_recaptcha">Kích hoạt</label></th>
                <td>
                    <label><input type="radio" name="devvn_vncheckout[enable_recaptcha]" checked="checked" value="0"> KHÔNG kích hoạt</label><br>
                    <label><input type="radio" name="devvn_vncheckout[enable_recaptcha]" value="1"> Sử dụng Google reCAPTCHA V2</label><br>
                    <label><input type="radio" name="devvn_vncheckout[enable_recaptcha]" value="2"> Sử dụng Google reCAPTCHA V3 (Khuyên dùng)</label><br>
                </td>
            </tr>
            <tr class="recaptcha_v3" style="display: none">
                <th scope="row"><label for="recaptcha_sitekey_v3">Sitekey</label></th>
                <td>
                    <input type="text" name="devvn_vncheckout[recaptcha_sitekey_v3]" id="recaptcha_sitekey_v3" value="">
                </td>
            </tr>
            <tr class="recaptcha_v3" style="display: none">
                <th scope="row"><label for="recaptcha_secretkey_v3">Secretkey</label></th>
                <td>
                    <input type="password" name="devvn_vncheckout[recaptcha_secretkey_v3]" id="recaptcha_secretkey_v3" value="">
                </td>
            </tr>
            <tr class="recaptcha_v2" style="display: none;">
                <th scope="row"><label for="recaptcha_sitekey">Sitekey</label></th>
                <td>
                    <input type="text" name="devvn_vncheckout[recaptcha_sitekey]" value="" id="recaptcha_sitekey"> <br>
                </td>
            </tr>
            <tr class="recaptcha_v2" style="display: none;">
                <th scope="row"><label for="recaptcha_secretkey">Secretkey</label></th>
                <td>
                    <input type="password" name="devvn_vncheckout[recaptcha_secretkey]" value="" id="recaptcha_secretkey"> <br>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Kích hoạt cho</label></th>
                <td>
                    <label><input type="checkbox" name="devvn_vncheckout[enable_recaptcha_get_address]" value="1"> Kích hoạt cho lấy địa chỉ tự động từ sđt</label><br>
                    <label><input type="checkbox" name="devvn_vncheckout[enable_recaptcha_create_order]" value="1"> Kích hoạt cho tạo đơn hàng</label><br>
                </td>
            </tr>
        </tbody>
    </table>

    <hr>
    <h2>Nâng cao  <span class="new_label">Mới</span></h2>
    <table class="form-table infor-shop">
        <tbody>
        <tr>
            <th scope="row"><label for="block_order_ip">Chặn order với các IP</label></th>
            <td>
                <textarea name="devvn_vncheckout[block_order_ip]" id="block_order_ip"></textarea><br>
                Mỗi IP một dòng<br>
                Nếu dải IP thì thêm dạng * ví dụ 192.168.1.*
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="block_order_name">Chặn order với các từ khoá</label></th>
            <td>
                <textarea name="devvn_vncheckout[block_order_name]" id="block_order_name"></textarea><br>
                Từ khoá tính theo tên, địa chỉ, email và sđt. Mỗi giá trị một dòng<br>
                Nếu muốn thêm field khác thì hook và vn_checkout_fields_block_order_args
            </td>
        </tr>
        </tbody>
    </table>

    <hr>
    <h2>Công cụ.</h2>
    <table class="form-table infor-shop">
        <tbody>
        <tr>
            <th scope="row"><label for="send_shipid_active">Cập nhật quốc gia</label></th>
            <td>
                <button class="button update_country" type="button" data-nonce="d27bd274a9">Cập nhật quốc gia</button><span class="ajax_mess"></span> <br>
                <small>Cập nhật quốc gia VN cho toàn bộ user</small>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="send_shipid_active">Cập nhật dữ liệu địa chỉ</label></th>
            <td>
                <p style="margin-bottom: 15px">
                    Đơn vị hành chính<br>
                    <label style="margin-right: 20px">
                                                <input type="radio" name="devvn_vncheckout[version_address]" value="v2.1" checked="checked">
                        <span>Mới nhất</span>
                    </label>
                    <label style="margin-right: 20px">
                        <input type="radio" name="devvn_vncheckout[version_address]" value="v2.0">
                        <span>v2.0 - trước 01.07.2025</span>
                    </label>
                    <label>
                        <input type="radio" name="devvn_vncheckout[version_address]" value="v1.8">
                        <span>V1.8 - trước năm 2025</span>
                    </label>
                </p>
                <p style="margin-bottom: 10px;">Sau khi chọn thì hãy ấn nút "Cập nhật database" bên dưới</p>
                <p><button type="button" class="button-primary button_change_tables" data-nonce="1555542fb3">Cập nhật database</button></p>            </td>
        </tr>
        </tbody>
    </table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Lưu thay đổi"></p></form>