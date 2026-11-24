# ⌚ WATCHSTORE — HỆ THỐNG QUẢN LÝ VÀ BÁN ĐỒNG HỒ PIN CHÍNH HÃNG

Dự án Đồ án Chuyên ngành Công nghệ Thông tin: **Xây dựng Hệ thống Quản lý và Bán Đồng Hồ Pin Trực tuyến (WatchStore)**.  
Được phát triển trên nền tảng **PHP 8+ thuần (Pure PHP)** kết hợp **PDO MySQL / MariaDB**, thư viện giao diện **Bootstrap 5.3**, biểu đồ **Chart.js**, và đặc biệt ứng dụng tối đa **Database Triggers & Ràng buộc toàn vẹn CSDL**.

---

## 📁 1. CẤU TRÚC DỰ ÁN (KIẾN TRÚC CHUẨN 5 THƯ MỤC)

Hệ thống được tổ chức mạch lạc theo đúng mô hình 5 thư mục và 1 tệp `index.php` điều hướng ở thư mục gốc:

```text
web-dong-ho/
├── admin/                    # Phân hệ Quản trị viên & Nhân viên (Admin Panel)
│   ├── inc/                  # Header & Footer Layout giao diện quản trị
│   ├── dashboard.php         # Thống kê tổng quan & Biểu đồ doanh thu Chart.js
│   ├── danhmuc.php           # Quản lý 3 Loại Pin & Thương hiệu đồng hồ
│   ├── dongho.php            # Quản lý danh mục đồng hồ, upload ảnh, lịch sử giá
│   ├── khuyenmai.php         # Quản lý đợt khuyến mãi & chi tiết % giảm giá
│   ├── nhaphang.php          # Quản lý NCC, Đơn đặt nhập & Phiếu nhập kho
│   ├── donhang.php           # Duyệt đơn hàng, Giao hàng, Xuất hóa đơn bán lẻ
│   ├── baohanh.php           # Quản lý vòng đời bảo hành lưu vết 3 nhân viên
│   ├── trahang.php           # Quản lý đổi trả trong hạn 7 ngày (Bắt lỗi Trigger)
│   ├── nguoidung.php         # Quản lý tài khoản Admin, Nhân viên, Khách hàng
│   ├── test_nghiep_vu.php    # Trang Kiểm thử Tự động 8 Ràng buộc Nghiệp vụ (TC01-08)
│   ├── bao_cao.php           # Trang Cuốn Báo cáo Học thuật A4 & Xuất Word (.doc)/PDF
│   └── index.php             # Front Router phân hệ quản trị (?action=...)
├── data/                     # Tầng Cơ sở Dữ liệu & Kết nối
│   ├── database.php          # Lớp Singleton PDO kết nối MariaDB/MySQL (Cổng 3307/3306)
│   └── quanly_dongho.sql     # Toàn bộ cấu trúc 21 Bảng, Dữ liệu mẫu & 3 Triggers CSDL
├── images/                   # Thư mục lưu trữ hình ảnh tài nguyên
│   └── products/             # Ảnh các mẫu đồng hồ Pin (dh1.jpg -> dh8.jpg...)
├── model/                    # Tầng truy xuất dữ liệu (17 Model Classes hướng đối tượng)
│   ├── index.php             # Tệp nạp tự động (Autoloader) toàn bộ các Model
│   ├── dongho.php            # Thao tác bảng 'dongho' & bộ lọc đa tiêu chí
│   ├── hoadon.php            # Thao tác bảng 'hoadon' (Ràng buộc UNIQUE 1-1 với phieudat)
│   ├── phieudat.php          # Thao tác bảng 'phieudat' & 'ct_phieudat' (Đặt hàng & duyệt đơn)
│   ├── phieubaohanh.php      # Thao tác bảng 'phieubaohanh' (Lưu vết 3 nhân viên)
│   ├── phieutrahang.php      # Thao tác bảng 'phieutrahang' (Kiểm tra hạn trả 7 ngày)
│   ├── dondathang.php        # Thao tác đơn đặt mua hàng từ Nhà Cung Cấp
│   ├── phieunhap.php         # Thao tác phiếu nhập kho (Ràng buộc UNIQUE 1-1 với dondathang)
│   ├── lichsugia.php         # Thao tác bảng lưu vết biến động giá bán
│   ├── khuyenmai.php         # Thao tác bảng khuyến mãi & chi tiết áp dụng
│   ├── loaidongho.php        # Thao tác 3 loại Pin: Dây kim loại, Dây da, Thể thao
│   ├── hangdongho.php        # Thao tác các thương hiệu: Casio, Seiko, Citizen...
│   ├── nhanvien.php          # Thao tác thông tin nhân viên
│   ├── khachhang.php         # Thao tác thông tin khách hàng
│   ├── nguoidung.php         # Thao tác tài khoản & xác thực mật khẩu MD5
│   ├── diachi.php            # Thao tác sổ địa chỉ giao hàng
│   ├── nhacungcap.php        # Thao tác danh mục nhà cung cấp
│   └── thongke.php           # Truy vấn dữ liệu tổng hợp cho Dashboard
├── public/                   # Phân hệ Cửa hàng Trực tuyến (Storefront Khách hàng)
│   ├── inc/                  # Header & Footer Layout giao diện khách hàng
│   ├── home.php              # Trang chủ hiển thị lưới đồng hồ, lọc 3 loại Pin, Hãng, Giá
│   ├── detail.php            # Chi tiết đồng hồ, tính % khuyến mãi, sản phẩm cùng loại
│   ├── cart.php              # Giỏ hàng mua sắm (Thêm, Sửa số lượng, Xóa)
│   ├── checkout.php          # Đặt hàng trực tuyến & Chọn sổ địa chỉ nhận hàng
│   ├── login.php             # Đăng nhập đa vai trò (Admin / Nhân viên / Khách hàng)
│   ├── register.php          # Đăng ký tài khoản khách hàng mới
│   ├── profile.php           # Hồ sơ thông tin cá nhân & Đổi mật khẩu
│   ├── addresses.php         # Sổ địa chỉ giao hàng
│   ├── orders.php            # Lịch sử đơn mua & Tra cứu Thẻ bảo hành điện tử
│   ├── logout.php            # Đăng xuất tài khoản
│   └── index.php             # Front Router phân hệ khách hàng (?action=...)
├── tests/                    # Bộ kiểm thử độc lập
│   └── test_business_rules.php # Script chạy kiểm thử 8 TC (chạy được cả Web & CLI)
├── docs/                     # Tài liệu thiết kế & Báo cáo
│   ├── ERD_VA_DAC_TA_CSDL.md # Sơ đồ Mermaid ERD & Đặc tả 21 bảng CSDL
│   └── BAO_CAO_DO_AN.md      # Bản thảo báo cáo đồ án học thuật
├── index.php                 # Tệp gốc điều hướng chuyển về public/index.php
└── README.md                 # Hướng dẫn cài đặt & Kịch bản bảo vệ đồ án
```

---

## 🛠️ 2. HƯỚNG DẪN CÀI ĐẶT TRÊN XAMPP

### Bước 1: Sao chép mã nguồn
Đặt toàn bộ thư mục `web-dong-ho` vào thư mục `htdocs` của XAMPP:
```text
C:\xampp\htdocs\web-dong-ho\
hoặc
D:\xampp\htdocs\web-dong-ho\
```

### Bước 2: Khởi động XAMPP Control Panel
1. Mở **XAMPP Control Panel**.
2. Nhấn **Start** tại 2 dịch vụ:
   - **Apache** (Cổng mặc định 80, 443).
   - **MySQL** (Hệ thống tự động hỗ trợ cả **Cổng 3307** lẫn **Cổng 3306** nhờ cơ chế kết nối linh hoạt trong `data/database.php`).

### Bước 3: Nhập (Import) Cơ sở Dữ liệu vào phpMyAdmin
1. Mở trình duyệt web và truy cập: `http://localhost/phpmyadmin` (hoặc `http://localhost:8080/phpmyadmin` tùy cổng máy bạn).
2. Tạo mới một Cơ sở dữ liệu:
   - Tên CSDL: `quanly_dongho`
   - Bảng mã (Collation): `utf8mb4_unicode_ci`
3. Nhấp vào tab **Nhập (Import)**.
4. Chọn tệp SQL tại đường dẫn: `web-dong-ho/data/quanly_dongho.sql`.
5. Nhấn nút **Thực hiện (Import)** ở cuối trang.
   - *Kết quả:* CSDL khởi tạo đầy đủ **21 bảng dữ liệu**, các bản ghi mẫu đồng hồ Pin và **3 Triggers tự động**:
     - `trg_cong_ton_khi_nhap` (AFTER INSERT ON ct_phieunhap)
     - `trg_cong_ton_khi_tra` (AFTER INSERT ON ct_phieutrahang)
     - `trg_kiem_tra_han_tra_hang` (BEFORE INSERT ON phieutrahang)

### Bước 4: Truy cập ứng dụng
- **Trang Cửa hàng (Storefront):** `http://localhost/web-dong-ho/` (tự động chuyển đến `public/index.php`).
- **Trang Quản trị (Admin Panel):** `http://localhost/web-dong-ho/admin/index.php`.

---

## 🔑 3. BẢNG TÀI KHOẢN ĐĂNG NHẬP MẪU

Tất cả các tài khoản mặc định đều có mật khẩu được mã hóa **MD5**: `abc`

| Vai trò | Email đăng nhập | Mật khẩu | Quyền hạn (`loai`) | Mã định danh ánh xạ |
| :--- | :--- | :---: | :---: | :--- |
| **Quản trị viên (Admin)** | `admin@watchstore.vn` | `abc` | `loai = 1` | Toàn quyền Quản trị, Danh mục, Khuyến mãi, Tài khoản, Dashboard |
| **Nhân viên (Staff)** | `nv1@watchstore.vn` | `abc` | `loai = 2` | `ma_nv = NV02` (Duyệt đơn, Phân công giao hàng, Nhập kho, Bảo hành, Đổi trả) |
| **Khách hàng (Customer)** | `kh1@gmail.com` | `abc` | `loai = 3` | `ma_kh = 1` (Đặt hàng online, Sổ địa chỉ, Tra cứu đơn mua & Thẻ bảo hành) |

---

## 🎯 4. KỊCH BẢN TRÌNH BÀY DEMO TỪNG BƯỚC KHI BẢO VỆ ĐỒ ÁN

Để đạt điểm tối đa khi báo cáo trước Hội đồng Giảng viên, sinh viên thực hiện demo theo 7 bước mạch lạc sau:

### 🌟 Bước 1: Khách hàng mua sắm trên Storefront (`public/index.php`)
1. Truy cập `http://localhost/web-dong-ho/`.
2. Trình bày **Bộ lọc kết hợp**:
   - Chọn lọc theo **3 loại đồng hồ Pin**: *Đồng hồ Pin dây kim loại*, *Đồng hồ Pin dây da*, hoặc *Đồng hồ Pin thể thao*.
   - Chọn lọc theo hãng (**Casio**, **Seiko**, **Citizen**...) hoặc khoảng giá.
3. Bấm vào xem **Chi tiết sản phẩm** (ví dụ `DH01`): Giảng viên sẽ thấy hiển thị huy hiệu giảm giá theo chương trình khuyến mãi đang hiệu lực (KM01 giảm 10% từ 3.450.000đ còn 3.105.000đ).
4. Nhấn **"Thêm vào giỏ hàng"**, xem trang Giỏ hàng (`public/cart.php`), rồi nhấn **"Tiến hành đặt hàng"** (`public/checkout.php`).
5. Đăng nhập với tài khoản khách `kh1@gmail.com` / `abc`, chọn địa chỉ nhận hàng từ Sổ địa chỉ và bấm **"Xác nhận đặt hàng"**.
   - *Kết quả:* Đơn hàng được tạo thành công ở trạng thái **"Chờ duyệt"**.

---

### 🌟 Bước 2: Nhân viên duyệt đơn, phân công giao hàng & Tự động xuất Hóa đơn + Phiếu bảo hành (`admin/index.php?action=donhang`)
1. Đăng nhập tài khoản nhân viên `nv1@watchstore.vn` / `abc`.
2. Truy cập mục **Đơn đặt hàng online**: Thấy đơn hàng vừa tạo ở Bước 1.
3. Bấm nút **"Duyệt đơn"** &rarr; Trạng thái chuyển sang **"Đã duyệt"**.
4. Bấm nút **"Phân công giao hàng"**:
   - Chọn nhân viên giao hàng, ngày giờ giao dự kiến và nhập mã số thuế (nếu có).
   - Bấm **"Xác nhận & Xuất hóa đơn"**.
5. **Chứng minh nghiệp vụ cốt lõi:**
   - Hệ thống thực thi **Database Transaction** bảo toàn dữ liệu.
   - Sinh ra đúng **1 Hóa đơn bán lẻ duy nhất** (bảo vệ bởi ràng buộc `UNIQUE uk_hd_pd` trên bảng `hoadon`).
   - Tồn kho các mặt hàng trong đơn tự động bị trừ chính xác.
   - Bảng `phieubaohanh` tự động phát sinh đúng số lượng **Phiếu bảo hành điện tử riêng biệt cho từng chiếc máy** với thời hạn chuẩn: `ngay_ket_thuc = DATE_ADD(ngay_bat_dau, INTERVAL thoi_gian_bh MONTH)` (24 tháng).
6. Khách hàng đăng nhập vào mục **Đơn mua & Bảo hành** (`public/index.php?action=orders`) có thể tra cứu ngay mã thẻ bảo hành của chiếc đồng hồ mình vừa mua.

---

### 🌟 Bước 3: Nghiệp vụ Hậu mãi & Lưu vết Bảo hành 3 Nhân viên (`admin/index.php?action=baohanh`)
1. Truy cập mục **Quản lý Bảo hành**.
2. Tìm phiếu bảo hành của chiếc đồng hồ khách vừa mua:
   - Thấy đã có sẵn `ma_nv_lap` (Nhân viên đã xuất hóa đơn ở Bước 2).
3. Bấm **"Tiếp nhận bảo hành"**: Nhập tình trạng máy (ví dụ: *Hết pin, đứng kim giây*).
   - Hệ thống tự động ghi nhận nhân viên tiếp nhận: `ma_nv_nhan`.
4. Bấm **"Hoàn thành & Bàn giao"**: Nhập kết quả sửa chữa (ví dụ: *Đã thay pin Maxell chính hãng*).
   - Hệ thống tự động ghi nhận nhân viên bàn giao: `ma_nv_giao_tra`.
   - *Minh chứng:* CSDL lưu vết minh bạch đầy đủ **3 nhân viên độc lập** phụ trách 3 khâu khác nhau của chiếc máy.

---

### 🌟 Bước 4: Nghiệp vụ Đổi trả Hàng & Bắt lỗi Trigger 7 Ngày (`admin/index.php?action=trahang`)
1. Truy cập mục **Quản lý Trả hàng**.
2. **Kịch bản 4A (Trả hàng hợp lệ trong 7 ngày):**
   - Chọn hóa đơn vừa xuất ở Bước 2 (lập trong ngày).
   - Nhập số lượng trả: 1 chiếc.
   - Bấm **"Xác nhận lập phiếu trả"**.
   - *Kết quả:* Lập phiếu thành công! Trigger `trg_cong_ton_khi_tra` tự động cộng hoàn trả lại tồn kho 1 chiếc đồng hồ vào bảng `dongho`.
3. **Kịch bản 4B (Bắt lỗi Trigger khi trả hàng quá hạn 7 ngày):**
   - Chọn một hóa đơn giả lập đã lập cách đây hơn 7 ngày (hoặc thử nghiệm trên trang test).
   - Bấm lập phiếu trả.
   - *Kết quả:* Trigger `trg_kiem_tra_han_tra_hang` ném ngoại lệ `SQLSTATE 45000`: *"Lỗi: Đã quá thời hạn 1 tuần (7 ngày) kể từ ngày mua hàng, không thể lập phiếu trả!"*.
   - Giao diện Admin bắt lỗi `PDOException` và hiển thị cảnh báo từ chối thân thiện màu đỏ.

---

### 🌟 Bước 5: Nhập kho từ Nhà Cung Cấp & Trigger cộng tồn (`admin/index.php?action=nhaphang`)
1. Truy cập mục **NCC & Phiếu nhập kho**.
2. Lập một Đơn đặt hàng nhập (`dondathang`): Đặt 10 chiếc.
3. Khi hàng về, bấm **"Lập phiếu nhập kho"** (`phieunhap`): Nhập số lượng thực nhận 7 chiếc (nhập thiếu 3 chiếc).
4. Bấm **"Xác nhận nhập kho"**:
   - Trigger `trg_cong_ton_khi_nhap` tự động cộng đúng **7 chiếc** vào tồn kho của sản phẩm.
   - Nếu cố tình lập thêm phiếu nhập thứ 2 cho cùng đơn đặt hàng này: Ràng buộc `UNIQUE uk_pn_ddh` sẽ chặn lại lập tức.

---

### 🌟 Bước 6: Minh chứng Trang Kiểm Thử Tự Động (`admin/index.php?action=test`)
1. Nhấp vào mục menu **Kiểm thử tự động (TC01-08)** trên Sidebar Admin.
2. Bấm nút **"Chạy Lại Toàn Bộ Kiểm Thử"**.
3. Màn hình thực thi và hiển thị bảng tổng kết **8/8 PASS (100%)** với thời gian chạy chỉ ~140ms.
4. Mở rộng từng mục để giảng viên xem chi tiết:
   - Bằng chứng xác thực MD5.
   - Bằng chứng lịch sử giá (`lichsugia`).
   - Bằng chứng Trigger tăng/giảm tồn kho.
   - Bằng chứng chặn trả hàng 7 ngày và ràng buộc duy nhất.
   - Toàn bộ dữ liệu kiểm thử dùng tiền tố `TEST_*` và được **Cleanup / Hoàn tác 100%**, không để lại rác trong database.

---

### 🌟 Bước 7: Xuất Cuốn Báo Cáo Đồ Án & Sơ Đồ ERD (`admin/index.php?action=baocao`)
1. Nhấp vào mục **Cuốn Báo cáo & ERD** trên Sidebar Admin.
2. Giới thiệu với Giảng viên:
   - Sơ đồ **ERD 21 bảng dữ liệu** được vẽ trực quan bằng thư viện **Mermaid.js**.
   - Bảng đặc tả chi tiết 21 bảng và 3 triggers CSDL.
   - Trích dẫn và giải thích các đoạn mã nguồn then chốt.
3. Trình diễn 2 nút chức năng:
   - Bấm **"In / Lưu File PDF (A4)"**: Gọi lệnh in chuẩn ngắt trang từng chương A4.
   - Bấm **"Tải File Word (.doc)"**: Tải ngay file Word `Bao_Cao_Do_An_Quan_Ly_Dong_Ho.doc` có định dạng font Times New Roman, đầy đủ bảng biểu và mã nguồn để nộp cho nhà trường.

---

## 🏆 5. TỔNG KẾT KỸ THUẬT NỔI BẬT

1. **Tuân thủ Tuyệt đối Ràng buộc Kiến trúc:** 100% mã nguồn nằm gọn trong đúng 5 thư mục (`admin`, `data`, `images`, `model`, `public`) và 1 file điều hướng `index.php`.
2. **Bảo mật & Toàn vẹn Dữ liệu:** Không có bất kỳ truy vấn SQL nối chuỗi trực tiếp; 100% sử dụng **PDO Prepared Statements** và **Transaction ACID**.
3. **Database-First Business Logic:** Tối ưu hóa hiệu năng bằng cách đẩy các nghiệp vụ sống còn xuống trực tiếp **3 Triggers CSDL** và các ràng buộc **UNIQUE Constraints**.
4. **Hệ thống Kiểm thử Khép kín:** Đi kèm bộ kiểm thử tự động 8 Test Cases chứng minh độ tin cậy của phần mềm.
