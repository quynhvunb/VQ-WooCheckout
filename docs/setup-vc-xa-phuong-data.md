Ui/UX thêm phương thức vận chuyển tới xã/phương chưa đúng
Logic đúng phải là:
B1: Vào menu WooCommerce =>Cặt đặt =>Vận chuyển =>Thêm khu vực giao hàng

B2: Nhập Tên vùng (Ví dụ: Hà Nội) ->Chọn khu vực (Ví dụ chọn: Việt Nam, Thành phố Hà Nội) ->Thêm phương thức vận chuyển.

B3: Tại popup "Tạo phương thức vận chuyển" ->chọn "Phí vận chuyển tới xã/phường" ->Bấm "Tiếp tục". Lúc này các setting được lưu và popup "Tạo phương thức vận chuyển" sẽ tắt >> Chuyển sang giao diện như ảnh 7. xa-phuong_b1.png.
=>Nhấn Lưu thay đổi.

B4: Tại giao diện như ảnh 7. xa-phuong_b1.png, trong bảng "Phương thức vận chuyển" chọn "Chỉnh sửa" =>>Chuyển tới giao diện cài đặt phí vận chuyển tới xã/phường, nav: Khu vực giao hàng > Hà Nội > Phí vận chuyển tới xã/phường.

B5: Cài đặt phí vận chuyển mặc định cho phương thức vận chuyển tới xã/phường:
- Tiêu đề phương thức
- Phụ thu
- Phí vận chuyển mặc định
- Tuỷ chỉnh điều kiện (Tùy chỉnh điều kiện tổng giá đơn hàng cho toàn bộ xã/phường)

B6: Cài dặt giá vận chuyển chi tiết cho xã/phường:
- Trong bảng dữ liệu "Giá vận chuyển", chọn "Thêm xã/phường". Lúc này sẽ có 1 hàng dữ liệu được thêm vào để user: Chọn tên xã/phường cần cài đặt phí ship riêng (có thể chọn nhiều) ở cột Xã/phường; cài đặt phí vận chuyển tương ứng với list xã/phường đã chọn ở cột Phí vận chuyển (gồm phí ship cho xã/phường, tuỳ chỉnh điều kiện phí ship theo tổng đơn hàng hoặc chọn không vận chuyển tới xã/phường này; Cột Điền tên Tiêu đề vận chuyển tương ứng với xã phường (để trống sẽ lấy tiêu đề mặc định của phương thức chung); Cột sắp sếp.
Giao diện giống ảnh "8. xa-phuong_b2.png".
Đọc cả file "4. setup-vc-xa-phuong-data.md"

Luồng cài đặt phương thức vận chuyển như sau:
1. vào Woocommer =>Cài đặt =>Vận chuyển=>Thêm khu vực vận chuyển,
2. Thêm khu vực:
- Điền Tên vùng (ví dụ Hà Nội)
- Chọn khu vực: Châu Á=>Việt Nam=>Thành phố Hà Nội
3. Thêm Phương thức vận chuyển:
- Thêm phương thức vận chuyển: Có 4 lựa chọn:
+ Miễn phí vận chuyển
+ Đồng Giá
+ Giao tại cửa hàng 
+ Phí vận chuyển tới Xã/Phường: Khu vực giao hàng > Hà Nội > Phí vận chuyển tới Xã/Phường
3 phương thức đầu tiên sử dụng logic của woo nguyên bản, chỉ xử lý riêng "Phí vận chuyển tới Xã/Phường".
B1: Giả lúc này tôi chọn "Phí vận chuyển tới Xã/Phường" =>bấm tiếp tục
B2: Trong bảng thông tin Phương thức vận chuyển, chọn "Chỉnh sửa"
B3: Cấu hình phí vận chuyển theo Xã/Phường
- Tiêu đề phương thức
- Phụ thu
- Phí vận chuyển mặc định
- Tuỳ chỉnh điều kiện:
	* Tùy chỉnh điều kiện tổng giá đơn hàng cho toàn bộ xã/phường
- Giá vận chuyển (setup giá vận chuyển theo xã/phường):
	* Thêm xã/phường (có thể chọn nhiều); thêm quy tắc (giá, tuỳ chỉnh điều kiện)
	* Có thể thêm nhiều rule, mỗi xã phường một rule, hoặc nhiều xã phường chung 1 rule.
Sau đó nhấtn Lưu thay đổi.
Logic được áp dụng.

<form method="post" id="mainform" action="" enctype="multipart/form-data">
					<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=general" class="nav-tab ">Cài đặt chung</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=products" class="nav-tab ">Sản phẩm</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping" class="nav-tab nav-tab-active">Vận chuyển</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=checkout" class="nav-tab ">Thanh toán</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=account" class="nav-tab ">Tài khoản &amp; Bảo mật</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=email" class="nav-tab ">Email</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=integration" class="nav-tab ">Kết hợp</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=site-visibility" class="nav-tab ">Hiển thị cửa hàng</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=point-of-sale" class="nav-tab ">Điểm bán hàng</a><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=advanced" class="nav-tab ">Nâng cao</a>			</nav>
					<h1 class="screen-reader-text">Vận chuyển</h1>
			<ul class="subsubsub"><li><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=" class="current">Khu vực giao hàng</a> | </li><li><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=options" class="">Cài đặt vận chuyển</a> | </li><li><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=classes" class="">Mức phân loại</a> | </li><li><a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=devvn_freeshipping_by_paymentmethod" class="">Freeship bởi hình thức thanh toán</a>  </li></ul><br class="clear"><h2>
	<a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping">Khu vực giao hàng</a> &gt;
	<a href="https://duoctamhang.vn/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id=11">Hà Nội</a> &gt;
	Phí vận chuyển tới xã/phường</h2>

		<table class="form-table dwas_table">
					<tbody><tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_devvn_district_zone_shipping_title">Tiêu đề phương thức <span class="woocommerce-help-tip" tabindex="0" aria-label="The title which the user sees during checkout, if not defined in Shipping Rates."></span></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Tiêu đề phương thức</span></legend>
					<input class="input-text regular-input " type="text" name="woocommerce_devvn_district_zone_shipping_title" id="woocommerce_devvn_district_zone_shipping_title" style="" value="Phí vận chuyển" placeholder="">
									</fieldset>
			</td>
		</tr>
				<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_devvn_district_zone_shipping_fee">Phụ thu <span class="woocommerce-help-tip" tabindex="0" aria-label="Fee excluding tax, e.g. 3.50. Leave blank to disable."></span></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Phụ thu</span></legend>
					<input class="input-text regular-input " type="text" name="woocommerce_devvn_district_zone_shipping_fee" id="woocommerce_devvn_district_zone_shipping_fee" style="" value="" placeholder="">
									</fieldset>
			</td>
		</tr>
				<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="woocommerce_devvn_district_zone_shipping_cost">Phí vận chuyển mặc định <span class="woocommerce-help-tip" tabindex="0" aria-label="Phí vận chuyển mặc định cho tất cả xã/phường"></span></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Phí vận chuyển mặc định</span></legend>
					<input class="input-text regular-input " type="text" name="woocommerce_devvn_district_zone_shipping_cost" id="woocommerce_devvn_district_zone_shipping_cost" style="" value="0" placeholder="">
									</fieldset>
			</td>
		</tr>
				<tr valign="top" style="display: none;">
			<th scope="row" class="titledesc">
				<label for="woocommerce_devvn_district_zone_shipping_all_price_condition">Tùy chỉnh điều kiện </label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Tùy chỉnh điều kiện</span></legend>
					<input class="input-text regular-input " type="text" name="woocommerce_devvn_district_zone_shipping_all_price_condition" id="woocommerce_devvn_district_zone_shipping_all_price_condition" style="" value="" placeholder="">
					<p class="description">Điều kiện mặc định cho toàn bộ xã/phường</p>
				</fieldset>
			</td>
		</tr>
				<tr valign="top" style="display: none;">
			<th scope="row" class="titledesc">
				<label for="woocommerce_devvn_district_zone_shipping_all_price_condition_w">Tùy chỉnh điều kiện cân nặng </label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Tùy chỉnh điều kiện cân nặng</span></legend>
					<input class="input-text regular-input " type="text" name="woocommerce_devvn_district_zone_shipping_all_price_condition_w" id="woocommerce_devvn_district_zone_shipping_all_price_condition_w" style="" value="" placeholder="">
					<p class="description">Điều kiện cân nặng mặc định cho toàn bộ xã/phường</p>
				</fieldset>
			</td>
		</tr>
		            <tr>
                <th>Tùy chỉnh điều kiện <span class="woocommerce-help-tip"></span></th>
                <td>
                        <div class="district_shipping_advance all_condition_district">
        <label><input type="checkbox" name="all_district_condition_checked" value="1" class="shipping_advance"> Tùy chỉnh điều kiện tổng giá đơn hàng cho toàn bộ xã/phường</label>
        <div class="dwas_price_list dwas_show">
            <div class="dwas_price_list_box">
                <div class="dwas_price_list_tr">
                    <div class="dwas_price_list_td">Điều kiện giá order &gt;=</div>
                    <div class="dwas_price_list_td">Giá vận chuyển</div>
                </div>
                                <div class="dwas_price_list_tr">
                    <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="all_district_condition[dk_0][dk]" min="0" value="" step="any"></div>
                    <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="all_district_condition[dk_0][price]" min="0" value="" step="any"></div>
                    <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                </div>
                
            </div>
            <div class="dwas_price_list_tfoot">
                <a href="javascript:void(0)" class="dwas_save_condition">Lưu điều kiện</a>
                <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
            </div>
        </div>
    </div>
    <hr>
    
                    </td>
            </tr>
			<tr>
				<th>Giá vận chuyển <span class="woocommerce-help-tip"></span></th>
				<td>
					        <div class="vn_checkout_box">
		<table id="flat_rate_boxes" class="shippingrows widefat" cellspacing="0" style="position:relative;">
			<thead>
				<tr>
					<th class="check-column"><input type="checkbox"></th>
					<th>xã/phường</th>
                    					<th>Phí vận chuyển</th>
					<th>Tiêu đề</th>				
					<th class="sort-column">Sắp xếp</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th colspan="5"><a href="#" class="add-box button button-primary">Thêm xã/phường</a> <a href="#" class="remove button">Xóa lựa chọn</a></th>
				</tr>
			</tfoot>
			<tbody class="flat_rate_boxes ui-sortable" data-boxes="">
							
		<tr class="flat_rate_box">
			<td class="check-column">
				<input type="checkbox" name="select">
				<input type="hidden" class="box_id" name="box_id[0]" value="">
			</td>
			<td><select class="select chosen_select box_district_select select2-hidden-accessible" multiple="" name="box_district[0][]" data-value="" tabindex="-1" aria-hidden="true"><optgroup label="Thành phố Hà Nội"><option value="00004">Phường Ba Đình</option><option value="00292">Phường Bạch Mai</option><option value="00118">Phường Bồ Đề</option><option value="10015">Phường Chương Mỹ</option><option value="00166">Phường Cầu Giấy</option><option value="00082">Phường Cửa Nam</option><option value="09886">Phường Dương Nội</option><option value="00025">Phường Giảng Võ</option><option value="00256">Phường Hai Bà Trưng</option><option value="00337">Phường Hoàng Liệt</option><option value="00331">Phường Hoàng Mai</option><option value="00070">Phường Hoàn Kiếm</option><option value="09556">Phường Hà Đông</option><option value="00097">Phường Hồng Hà</option><option value="00364">Phường Khương Đình</option><option value="00229">Phường Kim Liên</option><option value="09552">Phường Kiến Hưng</option><option value="00145">Phường Long Biên</option><option value="00199">Phường Láng</option><option value="00328">Phường Lĩnh Nam</option><option value="00160">Phường Nghĩa Đô</option><option value="00008">Phường Ngọc Hà</option><option value="00136">Phường Phúc Lợi</option><option value="00619">Phường Phú Diễn</option><option value="09568">Phường Phú Lương</option><option value="00091">Phường Phú Thượng</option><option value="00352">Phường Phương Liệt</option><option value="09574">Phường Sơn Tây</option><option value="00643">Phường Thanh Liệt</option><option value="00367">Phường Thanh Xuân</option><option value="00598">Phường Thượng Cát</option><option value="00103">Phường Tây Hồ</option><option value="00634">Phường Tây Mỗ</option><option value="00613">Phường Tây Tựu</option><option value="09604">Phường Tùng Thiện</option><option value="00322">Phường Tương Mai</option><option value="00592">Phường Từ Liêm</option><option value="00127">Phường Việt Hưng</option><option value="00226">Phường Văn Miếu - Quốc Tử Giám</option><option value="00301">Phường Vĩnh Hưng</option><option value="00283">Phường Vĩnh Tuy</option><option value="00622">Phường Xuân Phương</option><option value="00611">Phường Xuân Đỉnh</option><option value="00175">Phường Yên Hòa</option><option value="09562">Phường Yên Nghĩa</option><option value="00340">Phường Yên Sở</option><option value="00190">Phường Ô Chợ Dừa</option><option value="00602">Phường Đông Ngạc</option><option value="00637">Phường Đại Mỗ</option><option value="00316">Phường Định Công</option><option value="00235">Phường Đống Đa</option><option value="09931">Xã Hưng Đạo</option><option value="00385">Xã Trung Giã</option><option value="09784">Xã Đan Phượng</option><option value="09877">Xã An Khánh</option><option value="09700">Xã Ba Vì</option><option value="00577">Xã Bát Tràng</option><option value="10126">Xã Bình Minh</option><option value="09676">Xã Bất Bạt</option><option value="10330">Xã Chuyên Mỹ</option><option value="10237">Xã Chương Dương</option><option value="09634">Xã Cổ Đô</option><option value="10180">Xã Dân Hòa</option><option value="09856">Xã Dương Hòa</option><option value="00565">Xã Gia Lâm</option><option value="09832">Xã Hoài Đức</option><option value="09772">Xã Hát Môn</option><option value="09988">Xã Hòa Lạc</option><option value="10096">Xã Hòa Phú</option><option value="10417">Xã Hòa Xá</option><option value="10489">Xã Hương Sơn</option><option value="09982">Xã Hạ Bằng</option><option value="10465">Xã Hồng Sơn</option><option value="10210">Xã Hồng Vân</option><option value="00382">Xã Kim Anh</option><option value="09910">Xã Kiều Phú</option><option value="09787">Xã Liên Minh</option><option value="09661">Xã Minh Châu</option><option value="09022">Xã Mê Linh</option><option value="10441">Xã Mỹ Đức</option><option value="00685">Xã Nam Phù</option><option value="00679">Xã Ngọc Hồi</option><option value="00433">Xã Nội Bài</option><option value="00541">Xã Phù Đổng</option><option value="09739">Xã Phúc Lộc</option><option value="10459">Xã Phúc Sơn</option><option value="00466">Xã Phúc Thịnh</option><option value="09715">Xã Phúc Thọ</option><option value="09952">Xã Phú Cát</option><option value="10030">Xã Phú Nghĩa</option><option value="10273">Xã Phú Xuyên</option><option value="10279">Xã Phượng Dực</option><option value="08974">Xã Quang Minh</option><option value="10072">Xã Quảng Bị</option><option value="09619">Xã Quảng Oai</option><option value="09895">Xã Quốc Oai</option><option value="09694">Xã Suối Hai</option><option value="00376">Xã Sóc Sơn</option><option value="09871">Xã Sơn Đồng</option><option value="10144">Xã Tam Hưng</option><option value="10114">Xã Thanh Oai</option><option value="00640">Xã Thanh Trì</option><option value="00493">Xã Thiên Lộc</option><option value="00562">Xã Thuận An</option><option value="00475">Xã Thư Lâm</option><option value="10183">Xã Thường Tín</option><option value="10231">Xã Thượng Phúc</option><option value="09955">Xã Thạch Thất</option><option value="08995">Xã Tiến Thắng</option><option value="10081">Xã Trần Phú</option><option value="10003">Xã Tây Phương</option><option value="10354">Xã Vân Đình</option><option value="00508">Xã Vĩnh Thanh</option><option value="09664">Xã Vật Lại</option><option value="10045">Xã Xuân Mai</option><option value="09706">Xã Yên Bài</option><option value="08980">Xã Yên Lãng</option><option value="04930">Xã Yên Xuân</option><option value="09817">Xã Ô Diên</option><option value="00430">Xã Đa Phúc</option><option value="09616">Xã Đoài Phương</option><option value="00454">Xã Đông Anh</option><option value="00664">Xã Đại Thanh</option><option value="10342">Xã Đại Xuyên</option><option value="10402">Xã Ứng Hòa</option><option value="10369">Xã Ứng Thiên</option></optgroup></select><span class="select2 select2-container select2-container--default" dir="ltr" style="width: 300px;"><span class="selection"><span class="select2-selection select2-selection--multiple" aria-haspopup="true" aria-expanded="false" tabindex="-1"><ul class="select2-selection__rendered" aria-live="polite" aria-relevant="additions removals" aria-atomic="true"><li class="select2-selection__choice" title="Phường Ba Đình"><span class="select2-selection__choice__remove" role="presentation" aria-hidden="true">×</span>Phường Ba Đình</li><li class="select2-selection__choice" title="Phường Bạch Mai"><span class="select2-selection__choice__remove" role="presentation" aria-hidden="true">×</span>Phường Bạch Mai</li><li class="select2-search select2-search--inline"><input class="select2-search__field" type="text" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="textbox" aria-autocomplete="list" placeholder="" style="width: 0.75em;"></li></ul></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span></td>
            			<td>
                <input type="text" class="text" name="box_cost[0]" placeholder="0" size="4" value="">
                <div class="district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance[0]" value="1" class="shipping_advance"> Tùy ship theo tổng đơn hàng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Điều kiện giá order &gt;=</div>
                                <div class="dwas_price_list_td">Giá vận chuyển</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[0][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[0][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_advance_weight district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance_w[0]" value="2" class="shipping_advance_w"> Tính ship theo cân nặng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">&lt;= (kg)</div>
                                <div class="dwas_price_list_td">Phí</div>
                                <div class="dwas_price_list_td"></div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[0][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[0][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                        <div class="dwas_price_list_box2">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Mỗi kg sau</div>
                                <div class="dwas_price_list_td">Phí</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">
                                    <select name="district_condition_limit[0]">
                                        <option value="">Không chọn</option>
                                        <option value="0.5">Mỗi 0.5kg vượt mức</option>
                                        <option value="1">Mỗi 1kg vượt mức</option>
                                    </select>
                                </div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_limitprice[0]" min="0" value=""></div>
                            </div>
                        </div>
                        <div class="dwas_hesoquydoi">
                            Hệ số quy đổi<br>
                            <input type="number" class="district_hequydoi" name="district_hequydoi[0]" min="0" value="6000"><br>
                            <small>Mặc định theo ViettelPost là 6000</small>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_disable">
                    <label><input type="checkbox" name="shipping_disable[0]" value="1" class="shipping_disable"> Không vận chuyển tới đây</label>
                </div>
            </td>
			<td><input type="text" class="text" name="box_title[0]" placeholder="Tiêu đề của hình thức vận chuyển" value=""></td>
            <td class="sort-column sort_dwas_td">
                <span class="icon_sort_dwas"></span>
            </td>
		</tr>
		
		<tr class="flat_rate_box">
			<td class="check-column">
				<input type="checkbox" name="select">
				<input type="hidden" class="box_id" name="box_id[1]" value="">
			</td>
			<td><select class="select chosen_select box_district_select select2-hidden-accessible" multiple="" name="box_district[1][]" data-value="" tabindex="-1" aria-hidden="true"><optgroup label="Thành phố Hà Nội"><option value="00004">Phường Ba Đình</option><option value="00292">Phường Bạch Mai</option><option value="00118">Phường Bồ Đề</option><option value="10015">Phường Chương Mỹ</option><option value="00166">Phường Cầu Giấy</option><option value="00082">Phường Cửa Nam</option><option value="09886">Phường Dương Nội</option><option value="00025">Phường Giảng Võ</option><option value="00256">Phường Hai Bà Trưng</option><option value="00337">Phường Hoàng Liệt</option><option value="00331">Phường Hoàng Mai</option><option value="00070">Phường Hoàn Kiếm</option><option value="09556">Phường Hà Đông</option><option value="00097">Phường Hồng Hà</option><option value="00364">Phường Khương Đình</option><option value="00229">Phường Kim Liên</option><option value="09552">Phường Kiến Hưng</option><option value="00145">Phường Long Biên</option><option value="00199">Phường Láng</option><option value="00328">Phường Lĩnh Nam</option><option value="00160">Phường Nghĩa Đô</option><option value="00008">Phường Ngọc Hà</option><option value="00136">Phường Phúc Lợi</option><option value="00619">Phường Phú Diễn</option><option value="09568">Phường Phú Lương</option><option value="00091">Phường Phú Thượng</option><option value="00352">Phường Phương Liệt</option><option value="09574">Phường Sơn Tây</option><option value="00643">Phường Thanh Liệt</option><option value="00367">Phường Thanh Xuân</option><option value="00598">Phường Thượng Cát</option><option value="00103">Phường Tây Hồ</option><option value="00634">Phường Tây Mỗ</option><option value="00613">Phường Tây Tựu</option><option value="09604">Phường Tùng Thiện</option><option value="00322">Phường Tương Mai</option><option value="00592">Phường Từ Liêm</option><option value="00127">Phường Việt Hưng</option><option value="00226">Phường Văn Miếu - Quốc Tử Giám</option><option value="00301">Phường Vĩnh Hưng</option><option value="00283">Phường Vĩnh Tuy</option><option value="00622">Phường Xuân Phương</option><option value="00611">Phường Xuân Đỉnh</option><option value="00175">Phường Yên Hòa</option><option value="09562">Phường Yên Nghĩa</option><option value="00340">Phường Yên Sở</option><option value="00190">Phường Ô Chợ Dừa</option><option value="00602">Phường Đông Ngạc</option><option value="00637">Phường Đại Mỗ</option><option value="00316">Phường Định Công</option><option value="00235">Phường Đống Đa</option><option value="09931">Xã Hưng Đạo</option><option value="00385">Xã Trung Giã</option><option value="09784">Xã Đan Phượng</option><option value="09877">Xã An Khánh</option><option value="09700">Xã Ba Vì</option><option value="00577">Xã Bát Tràng</option><option value="10126">Xã Bình Minh</option><option value="09676">Xã Bất Bạt</option><option value="10330">Xã Chuyên Mỹ</option><option value="10237">Xã Chương Dương</option><option value="09634">Xã Cổ Đô</option><option value="10180">Xã Dân Hòa</option><option value="09856">Xã Dương Hòa</option><option value="00565">Xã Gia Lâm</option><option value="09832">Xã Hoài Đức</option><option value="09772">Xã Hát Môn</option><option value="09988">Xã Hòa Lạc</option><option value="10096">Xã Hòa Phú</option><option value="10417">Xã Hòa Xá</option><option value="10489">Xã Hương Sơn</option><option value="09982">Xã Hạ Bằng</option><option value="10465">Xã Hồng Sơn</option><option value="10210">Xã Hồng Vân</option><option value="00382">Xã Kim Anh</option><option value="09910">Xã Kiều Phú</option><option value="09787">Xã Liên Minh</option><option value="09661">Xã Minh Châu</option><option value="09022">Xã Mê Linh</option><option value="10441">Xã Mỹ Đức</option><option value="00685">Xã Nam Phù</option><option value="00679">Xã Ngọc Hồi</option><option value="00433">Xã Nội Bài</option><option value="00541">Xã Phù Đổng</option><option value="09739">Xã Phúc Lộc</option><option value="10459">Xã Phúc Sơn</option><option value="00466">Xã Phúc Thịnh</option><option value="09715">Xã Phúc Thọ</option><option value="09952">Xã Phú Cát</option><option value="10030">Xã Phú Nghĩa</option><option value="10273">Xã Phú Xuyên</option><option value="10279">Xã Phượng Dực</option><option value="08974">Xã Quang Minh</option><option value="10072">Xã Quảng Bị</option><option value="09619">Xã Quảng Oai</option><option value="09895">Xã Quốc Oai</option><option value="09694">Xã Suối Hai</option><option value="00376">Xã Sóc Sơn</option><option value="09871">Xã Sơn Đồng</option><option value="10144">Xã Tam Hưng</option><option value="10114">Xã Thanh Oai</option><option value="00640">Xã Thanh Trì</option><option value="00493">Xã Thiên Lộc</option><option value="00562">Xã Thuận An</option><option value="00475">Xã Thư Lâm</option><option value="10183">Xã Thường Tín</option><option value="10231">Xã Thượng Phúc</option><option value="09955">Xã Thạch Thất</option><option value="08995">Xã Tiến Thắng</option><option value="10081">Xã Trần Phú</option><option value="10003">Xã Tây Phương</option><option value="10354">Xã Vân Đình</option><option value="00508">Xã Vĩnh Thanh</option><option value="09664">Xã Vật Lại</option><option value="10045">Xã Xuân Mai</option><option value="09706">Xã Yên Bài</option><option value="08980">Xã Yên Lãng</option><option value="04930">Xã Yên Xuân</option><option value="09817">Xã Ô Diên</option><option value="00430">Xã Đa Phúc</option><option value="09616">Xã Đoài Phương</option><option value="00454">Xã Đông Anh</option><option value="00664">Xã Đại Thanh</option><option value="10342">Xã Đại Xuyên</option><option value="10402">Xã Ứng Hòa</option><option value="10369">Xã Ứng Thiên</option></optgroup></select><span class="select2 select2-container select2-container--default select2-container--below" dir="ltr" style="width: 300px;"><span class="selection"><span class="select2-selection select2-selection--multiple" aria-haspopup="true" aria-expanded="false" tabindex="-1"><ul class="select2-selection__rendered" aria-live="polite" aria-relevant="additions removals" aria-atomic="true"><li class="select2-selection__choice" title="Phường Hoàng Liệt"><span class="select2-selection__choice__remove" role="presentation" aria-hidden="true">×</span>Phường Hoàng Liệt</li><li class="select2-selection__choice" title="Phường Long Biên"><span class="select2-selection__choice__remove" role="presentation" aria-hidden="true">×</span>Phường Long Biên</li><li class="select2-search select2-search--inline"><input class="select2-search__field" type="text" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="textbox" aria-autocomplete="list" placeholder="" style="width: 0.75em;"></li></ul></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span></td>
            			<td>
                <input type="text" class="text" name="box_cost[1]" placeholder="0" size="4" value="">
                <div class="district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance[1]" value="1" class="shipping_advance"> Tùy ship theo tổng đơn hàng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Điều kiện giá order &gt;=</div>
                                <div class="dwas_price_list_td">Giá vận chuyển</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[1][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[1][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_advance_weight district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance_w[1]" value="2" class="shipping_advance_w"> Tính ship theo cân nặng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">&lt;= (kg)</div>
                                <div class="dwas_price_list_td">Phí</div>
                                <div class="dwas_price_list_td"></div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[1][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[1][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                        <div class="dwas_price_list_box2">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Mỗi kg sau</div>
                                <div class="dwas_price_list_td">Phí</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">
                                    <select name="district_condition_limit[1]">
                                        <option value="">Không chọn</option>
                                        <option value="0.5">Mỗi 0.5kg vượt mức</option>
                                        <option value="1">Mỗi 1kg vượt mức</option>
                                    </select>
                                </div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_limitprice[1]" min="0" value=""></div>
                            </div>
                        </div>
                        <div class="dwas_hesoquydoi">
                            Hệ số quy đổi<br>
                            <input type="number" class="district_hequydoi" name="district_hequydoi[1]" min="0" value="6000"><br>
                            <small>Mặc định theo ViettelPost là 6000</small>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_disable">
                    <label><input type="checkbox" name="shipping_disable[1]" value="1" class="shipping_disable"> Không vận chuyển tới đây</label>
                </div>
            </td>
			<td><input type="text" class="text" name="box_title[1]" placeholder="Tiêu đề của hình thức vận chuyển" value=""></td>
            <td class="sort-column sort_dwas_td">
                <span class="icon_sort_dwas"></span>
            </td>
		</tr>
		</tbody>
		</table>
        </div>
		<script type="text/template" id="tmpl-district-rate-box-row-template">
		<tr class="flat_rate_box">
			<td class="check-column">
				<input type="checkbox" name="select" />
				<input type="hidden" class="box_id" name="box_id[{{{ data.index }}}]" value="{{{ data.box.box_id }}}" />
			</td>
			<td><select class="select chosen_select box_district_select" multiple="multiple" name="box_district[{{{ data.index }}}][]" data-value="{{{ data.box.box_district }}}"><optgroup label="Thành phố Hà Nội"><option value="00004" >Phường Ba Đình</option><option value="00292" >Phường Bạch Mai</option><option value="00118" >Phường Bồ Đề</option><option value="10015" >Phường Chương Mỹ</option><option value="00166" >Phường Cầu Giấy</option><option value="00082" >Phường Cửa Nam</option><option value="09886" >Phường Dương Nội</option><option value="00025" >Phường Giảng Võ</option><option value="00256" >Phường Hai Bà Trưng</option><option value="00337" >Phường Hoàng Liệt</option><option value="00331" >Phường Hoàng Mai</option><option value="00070" >Phường Hoàn Kiếm</option><option value="09556" >Phường Hà Đông</option><option value="00097" >Phường Hồng Hà</option><option value="00364" >Phường Khương Đình</option><option value="00229" >Phường Kim Liên</option><option value="09552" >Phường Kiến Hưng</option><option value="00145" >Phường Long Biên</option><option value="00199" >Phường Láng</option><option value="00328" >Phường Lĩnh Nam</option><option value="00160" >Phường Nghĩa Đô</option><option value="00008" >Phường Ngọc Hà</option><option value="00136" >Phường Phúc Lợi</option><option value="00619" >Phường Phú Diễn</option><option value="09568" >Phường Phú Lương</option><option value="00091" >Phường Phú Thượng</option><option value="00352" >Phường Phương Liệt</option><option value="09574" >Phường Sơn Tây</option><option value="00643" >Phường Thanh Liệt</option><option value="00367" >Phường Thanh Xuân</option><option value="00598" >Phường Thượng Cát</option><option value="00103" >Phường Tây Hồ</option><option value="00634" >Phường Tây Mỗ</option><option value="00613" >Phường Tây Tựu</option><option value="09604" >Phường Tùng Thiện</option><option value="00322" >Phường Tương Mai</option><option value="00592" >Phường Từ Liêm</option><option value="00127" >Phường Việt Hưng</option><option value="00226" >Phường Văn Miếu - Quốc Tử Giám</option><option value="00301" >Phường Vĩnh Hưng</option><option value="00283" >Phường Vĩnh Tuy</option><option value="00622" >Phường Xuân Phương</option><option value="00611" >Phường Xuân Đỉnh</option><option value="00175" >Phường Yên Hòa</option><option value="09562" >Phường Yên Nghĩa</option><option value="00340" >Phường Yên Sở</option><option value="00190" >Phường Ô Chợ Dừa</option><option value="00602" >Phường Đông Ngạc</option><option value="00637" >Phường Đại Mỗ</option><option value="00316" >Phường Định Công</option><option value="00235" >Phường Đống Đa</option><option value="09931" >Xã Hưng Đạo</option><option value="00385" >Xã Trung Giã</option><option value="09784" >Xã Đan Phượng</option><option value="09877" >Xã An Khánh</option><option value="09700" >Xã Ba Vì</option><option value="00577" >Xã Bát Tràng</option><option value="10126" >Xã Bình Minh</option><option value="09676" >Xã Bất Bạt</option><option value="10330" >Xã Chuyên Mỹ</option><option value="10237" >Xã Chương Dương</option><option value="09634" >Xã Cổ Đô</option><option value="10180" >Xã Dân Hòa</option><option value="09856" >Xã Dương Hòa</option><option value="00565" >Xã Gia Lâm</option><option value="09832" >Xã Hoài Đức</option><option value="09772" >Xã Hát Môn</option><option value="09988" >Xã Hòa Lạc</option><option value="10096" >Xã Hòa Phú</option><option value="10417" >Xã Hòa Xá</option><option value="10489" >Xã Hương Sơn</option><option value="09982" >Xã Hạ Bằng</option><option value="10465" >Xã Hồng Sơn</option><option value="10210" >Xã Hồng Vân</option><option value="00382" >Xã Kim Anh</option><option value="09910" >Xã Kiều Phú</option><option value="09787" >Xã Liên Minh</option><option value="09661" >Xã Minh Châu</option><option value="09022" >Xã Mê Linh</option><option value="10441" >Xã Mỹ Đức</option><option value="00685" >Xã Nam Phù</option><option value="00679" >Xã Ngọc Hồi</option><option value="00433" >Xã Nội Bài</option><option value="00541" >Xã Phù Đổng</option><option value="09739" >Xã Phúc Lộc</option><option value="10459" >Xã Phúc Sơn</option><option value="00466" >Xã Phúc Thịnh</option><option value="09715" >Xã Phúc Thọ</option><option value="09952" >Xã Phú Cát</option><option value="10030" >Xã Phú Nghĩa</option><option value="10273" >Xã Phú Xuyên</option><option value="10279" >Xã Phượng Dực</option><option value="08974" >Xã Quang Minh</option><option value="10072" >Xã Quảng Bị</option><option value="09619" >Xã Quảng Oai</option><option value="09895" >Xã Quốc Oai</option><option value="09694" >Xã Suối Hai</option><option value="00376" >Xã Sóc Sơn</option><option value="09871" >Xã Sơn Đồng</option><option value="10144" >Xã Tam Hưng</option><option value="10114" >Xã Thanh Oai</option><option value="00640" >Xã Thanh Trì</option><option value="00493" >Xã Thiên Lộc</option><option value="00562" >Xã Thuận An</option><option value="00475" >Xã Thư Lâm</option><option value="10183" >Xã Thường Tín</option><option value="10231" >Xã Thượng Phúc</option><option value="09955" >Xã Thạch Thất</option><option value="08995" >Xã Tiến Thắng</option><option value="10081" >Xã Trần Phú</option><option value="10003" >Xã Tây Phương</option><option value="10354" >Xã Vân Đình</option><option value="00508" >Xã Vĩnh Thanh</option><option value="09664" >Xã Vật Lại</option><option value="10045" >Xã Xuân Mai</option><option value="09706" >Xã Yên Bài</option><option value="08980" >Xã Yên Lãng</option><option value="04930" >Xã Yên Xuân</option><option value="09817" >Xã Ô Diên</option><option value="00430" >Xã Đa Phúc</option><option value="09616" >Xã Đoài Phương</option><option value="00454" >Xã Đông Anh</option><option value="00664" >Xã Đại Thanh</option><option value="10342" >Xã Đại Xuyên</option><option value="10402" >Xã Ứng Hòa</option><option value="10369" >Xã Ứng Thiên</option></optgroup></select></td>
            			<td>
                <input type="text" class="text" name="box_cost[{{{ data.index }}}]" placeholder="0" size="4" value="{{{ data.box.box_cost }}}" />
                <div class="district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance[{{{ data.index }}}]" value="1" class="shipping_advance"/> Tùy ship theo tổng đơn hàng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Điều kiện giá order >=</div>
                                <div class="dwas_price_list_td">Giá vận chuyển</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[{{{ data.index }}}][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition[{{{ data.index }}}][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_advance_weight district_shipping_advance">
                    <label><input type="checkbox" name="shipping_advance_w[{{{ data.index }}}]" value="2" class="shipping_advance_w"/> Tính ship theo cân nặng</label>
                    <div class="dwas_price_list dwas_hidden">
                        <div class="dwas_price_list_box">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><= (kg)</div>
                                <div class="dwas_price_list_td">Phí</div>
                                <div class="dwas_price_list_td"></div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[{{{ data.index }}}][dk_0][dk]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_w[{{{ data.index }}}][dk_0][price]" min="0" step="any"></div>
                                <div class="dwas_price_list_td"><a href="javascript:void(0)" class="dwas_delete_condition">x</a></div>
                            </div>
                        </div>
                        <div class="dwas_price_list_tfoot">
                            <a href="javascript:void(0)" class="dwas_add_condition">Thêm điều kiện</a>
                        </div>
                        <div class="dwas_price_list_box2">
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">Mỗi kg sau</div>
                                <div class="dwas_price_list_td">Phí</div>
                            </div>
                            <div class="dwas_price_list_tr">
                                <div class="dwas_price_list_td">
                                    <select name="district_condition_limit[{{{ data.index }}}]">
                                        <option value="">Không chọn</option>
                                        <option value="0.5">Mỗi 0.5kg vượt mức</option>
                                        <option value="1">Mỗi 1kg vượt mức</option>
                                    </select>
                                </div>
                                <div class="dwas_price_list_td"><input type="number" class="input_district_condition" name="district_condition_limitprice[{{{ data.index }}}]" min="0" value=""></div>
                            </div>
                        </div>
                        <div class="dwas_hesoquydoi">
                            Hệ số quy đổi<br>
                            <input type="number" class="district_hequydoi" name="district_hequydoi[{{{ data.index }}}]" min="0" value="6000"><br>
                            <small>Mặc định theo ViettelPost là 6000</small>
                        </div>
                    </div>
                </div>
                <div class="district_shipping_disable">
                    <label><input type="checkbox" name="shipping_disable[{{{ data.index }}}]" value="1" class="shipping_disable"/> Không vận chuyển tới đây</label>
                </div>
            </td>
			<td><input type="text" class="text" name="box_title[{{{ data.index }}}]" placeholder="Tiêu đề của hình thức vận chuyển" value="{{{ data.box.box_title }}}" /></td>
            <td class="sort-column sort_dwas_td">
                <span class="icon_sort_dwas"></span>
            </td>
		</tr>
		</script>
						</td>
			</tr>
		</tbody></table>
					<p class="submit">
									<button name="save" class="woocommerce-save-button components-button is-primary" type="submit" value="Lưu thay đổi">Lưu thay đổi</button>
								<input type="hidden" id="_wpnonce" name="_wpnonce" value="c9760544d2"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id=17">			</p>
	</form>