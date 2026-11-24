# HƯỚNG DẪN CÀI ĐẶT & VẬN HÀNH HỆ THỐNG
## DỰ ÁN QUẢN LÝ & BÁN ĐỒNG HỒ PIN (GIAI ĐOẠN 1: MVC, SQL SERVER & PHÂN QUYỀN)

---

### 1. Giới thiệu tổng quan Giai đoạn 1
Dự án được xây dựng theo mô hình kiến trúc chuẩn **MVC (Model - View - Controller)** kết nối cơ sở dữ liệu **Microsoft SQL Server** thông qua driver **PDO `sqlsrv`**:
* **Ngôn ngữ & Nền tảng:** PHP 8.2+ (XAMPP trên Windows) + Microsoft SQL Server 2017/2019/2022.
* **Giao diện:** Bootstrap 5.3 + Bootstrap Icons (Responsive, giao diện sáng sủa, Flash messages trực quan).
* **Bảo mật & Mã hóa:** Đối chiếu mật khẩu `md5($matkhau)` đồng bộ với CSDL mẫu trong tệp `QLDH.sql`.
* **Phân quyền 3 vai trò:**
  - `loai = 1`: **Quản trị viên (Admin)** — Toàn quyền hệ thống, quản lý nhân viên, khuyến mãi, báo cáo.
  - `loai = 2`: **Nhân viên (Staff)** — Quản lý đơn hàng, nhập/trả hàng, hóa đơn, bảo hành.
  - `loai = 3`: **Khách hàng (Customer)** — Xem danh mục đồng hồ pin, đăng ký, hồ sơ và Sổ địa chỉ giao hàng.

---

### 2. Bước 1: Bật Extension `pdo_sqlsrv` trong PHP của XAMPP

Do PHP mặc định của XAMPP chưa tích hợp sẵn DLL driver SQL Server của Microsoft, bạn thực hiện 3 bước sau:

#### 2.1. Tải Microsoft Drivers for PHP for SQL Server
1. Truy cập trang tải chính thức của Microsoft:
   [Microsoft Drivers for PHP for SQL Server (GitHub Releases)](https://github.com/microsoft/msphpsql/releases) hoặc [Microsoft Learn](https://learn.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server).
2. Tải bản driver tương ứng với phiên bản PHP trong XAMPP của bạn:
   - Phiên bản PHP hiện tại trong máy: **PHP 8.2.12 x64 Thread Safe (TS)**.
   - Giải nén gói tải về, tìm 2 tệp:
     - `php_pdo_sqlsrv_82_ts_x64.dll`
     - `php_sqlsrv_82_ts_x64.dll`
3. Sao chép (Copy) 2 tệp `.dll` trên vào thư mục:
   ```
   D:\xampp\php\ext\
   ```

#### 2.2. Bật Extension trong `php.ini`
1. Mở tệp `D:\xampp\php\php.ini` (hoặc mở XAMPP Control Panel > nhấn nút **Config** cạnh Apache > chọn **PHP (php.ini)**).
2. Tìm đến phần các extension (khoảng dòng 900) và thêm 2 dòng sau:
   ```ini
   extension=php_pdo_sqlsrv
   extension=php_sqlsrv
   ```
3. Lưu tệp `php.ini`.

#### 2.3. Cài đặt ODBC Driver (nếu máy chưa có)
SQL Server PDO yêu cầu máy tính có **Microsoft ODBC Driver 17 hoặc 18 for SQL Server**:
- Tải miễn phí từ Microsoft: [Download ODBC Driver for SQL Server](https://learn.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server).

#### 2.4. Khởi động lại Apache
1. Mở **XAMPP Control Panel**.
2. Nhấn **Stop** Apache, sau đó nhấn **Start** lại Apache.

---

### 3. Bước 2: Tạo Cơ sở dữ liệu & Chạy tệp `QLDH.sql` trong SSMS

Tệp `QLDH.sql` nằm ngay tại thư mục gốc dự án: `D:\xampp\htdocs\web-dong-ho\QLDH.sql`.

1. Khởi động **SQL Server Management Studio (SSMS)**.
2. Kết nối vào máy chủ SQL Server của bạn (Server name: `.` hoặc `localhost` hoặc tên máy tính của bạn như `DESKTOP-XXXX\SQLEXPRESS`).
3. Trong SSMS:
   - Vào menu **File** > **Open** > **File...** (phím tắt `Ctrl + O`).
   - Chọn tệp: `D:\xampp\htdocs\web-dong-ho\QLDH.sql`.
4. Nhấn nút **Execute** (hoặc phím **F5**) để chạy toàn bộ kịch bản.
5. Sau khi chạy, CSDL `quanly_dongho` sẽ được khởi tạo hoàn chỉnh với:
   - 21 bảng dữ liệu có đầy đủ khóa chính, khóa ngoại, chỉ mục lọc `WHERE email IS NOT NULL`, kiểu dữ liệu `NVARCHAR` hiển thị tiếng Việt chuẩn.
   - Dữ liệu mẫu khởi tạo sẵn: 5 tài khoản người dùng, 3 nhân viên, 2 khách hàng, 3 địa chỉ nhận hàng, các mẫu đồng hồ Casio, Seiko, Citizen, Orient, Tissot.

---

### 4. Bước 3: Cấu hình Kết nối CSDL trong `config/db.php`

Tệp cấu hình kết nối nằm tại `config/db.php`:
```php
define('DB_SERVER', 'localhost'); // Hoặc 'localhost\SQLEXPRESS' nếu bạn dùng bản SQL Express
define('DB_NAME', 'quanly_dongho');
define('DB_USER', '');            // Để trống nếu dùng Windows Authentication
define('DB_PASS', '');            // Hoặc 'sa' và mật khẩu sa nếu dùng SQL Authentication
```

*Nếu SQL Server của bạn sử dụng tên instance đặc biệt (ví dụ `DESKTOP-ABC\SQLEXPRESS`), hãy chỉnh `DB_SERVER` thành tên instance đó.*

---

### 5. Bước 4: Danh Sách Tài Khoản Kiểm Thử Mẫu (Test Accounts)

Tất cả các tài khoản mặc định đều có mật khẩu là **`abc`** (đã mã hóa MD5: `900150983cd24fb0d6963f7d28e17f72`):

| Loại tài khoản | Vai trò (`loai`) | Email đăng nhập | Số điện thoại | Mật khẩu | Trang chuyển hướng sau đăng nhập |
|---|:---:|---|---|:---:|---|
| **Quản trị viên (Admin)** | **1** | `admin@watchstore.vn` | `0988994683` | `abc` | `/admin/dashboard` (Khu vực Quản trị Admin) |
| **Nhân viên bán hàng** | **2** | `nv1@watchstore.vn` | `0988994684` | `abc` | `/admin/dashboard` (Bảng điều khiển Nhân viên) |
| **Nhân viên kho** | **2** | `nv2@watchstore.vn` | `0988994685` | `abc` | `/admin/dashboard` (Bảng điều khiển Nhân viên) |
| **Khách hàng 1** | **3** | `kh1@gmail.com` | `0988994686` | `abc` | `/` (Trang chủ mua sắm) |
| **Khách hàng 2** | **3** | `kh2@gmail.com` | `0988994687` | `abc` | `/` (Trang chủ mua sắm) |

*Giao diện trang Đăng nhập (`index.php?route=login`) đã được tích hợp sẵn các nút **Điền nhanh 1-Click** để bạn kiểm thử nhanh chóng mà không cần gõ phím.*

---

### 6. Bước 5: Các Kịch Bản Kiểm Thử Chức Năng Giai Đoạn 1

Truy cập website trên trình duyệt:
```
http://localhost/web-dong-ho/
```

#### 6.1. Kiểm tra Trang chủ & Trạng thái CSDL
- Truy cập `http://localhost/web-dong-ho/index.php?route=home`.
- Quan sát card **"Trạng thái kết nối CSDL Microsoft SQL Server"**:
  - Nếu kết nối thành công, badge màu xanh hiện **"Đã kết nối thành công"** kèm tên CSDL `quanly_dongho`.
  - Bộ sưu tập mẫu đồng hồ Casio, Seiko, Citizen... được nạp trực tiếp từ bảng `dongho`.

#### 6.2. Kiểm thử Đăng nhập & Ánh xạ Phiên làm việc
1. **Đăng nhập Khách hàng:**
   - Đăng nhập bằng Email `kh1@gmail.com` hoặc Số điện thoại `0988994686`, mật khẩu `abc`.
   - Hệ thống tự động chuyển hướng về Trang chủ.
   - Navbar góc trên bên phải hiển thị tên khách hàng: **Nguyễn Thị Thu An** kèm badge màu xanh `Khách hàng`.
2. **Đăng nhập Quản trị viên (Admin):**
   - Đăng nhập bằng `admin@watchstore.vn` / `abc`.
   - Hệ thống chuyển hướng thẳng vào Bảng điều khiển Quản trị (`admin/dashboard`).
   - Hiển thị thông tin: Mã NV `NV01`, vai trò `Quản trị viên (Admin)`.
   - Sidebar bên trái hiển thị đầy đủ nhóm menu: Tổng quan, Nghiệp vụ, và **Quản trị cấp cao (Admin)**.
3. **Đăng nhập Nhân viên (loai = 2):**
   - Đăng nhập bằng `nv1@watchstore.vn` / `abc`.
   - Hệ thống mở Bảng điều khiển Quản trị với mã NV `NV02`.
   - Menu quản trị cấp cao của Admin sẽ tự động ẩn đi.

#### 6.3. Kiểm thử Phân quyền (AuthMiddleware & 403 Forbidden)
- Đăng nhập bằng tài khoản Khách hàng (`kh1@gmail.com`).
- Cố ý truy cập vào đường dẫn quản trị: `http://localhost/web-dong-ho/index.php?route=admin/dashboard`.
- Middleware sẽ kích hoạt cơ chế bảo vệ và trả về trang lỗi **403 - Quyền truy cập bị từ chối** với giao diện cảnh báo rõ ràng.

#### 6.4. Kiểm thử Đăng ký tài khoản Khách hàng mới (`loai = 3`)
1. Truy cập `http://localhost/web-dong-ho/index.php?route=register`.
2. Điền form đăng ký với các trường bắt buộc của cả 3 bảng (`nguoidung`, `khachhang`, `diachi`).
3. Bấm **Hoàn Tất Đăng Ký Tài Khoản**:
   - Hệ thống chạy Transaction tự động thêm vào bảng `nguoidung` (loai=3, trangthai=1), bảng `khachhang`, và thêm bản ghi đầu tiên vào bảng `diachi` với `macdinh = 1`.
   - Chuyển về trang đăng nhập với thông báo thành công xanh lá.

#### 6.5. Kiểm thử Sổ địa chỉ giao hàng (`views/customer/addresses.php`)
1. Đăng nhập bằng tài khoản khách hàng, click menu tài khoản > chọn **Sổ địa chỉ nhận hàng** (`index.php?route=addresses`).
2. **Xem danh sách:** Địa chỉ mặc định có badge vàng **"Mặc định"** và nằm ở vị trí trên cùng.
3. **Thêm địa chỉ mới:** Nhấn nút *Thêm Địa Chỉ Mới*, nhập địa chỉ, tick chọn *Đặt làm địa chỉ mặc định*.
   - Hệ thống đổi địa chỉ cũ về `0`, đặt địa chỉ mới thành `1`, đồng thời cập nhật trường `khachhang.dia_chi`.
4. **Đổi mặc định:** Nhấn nút *Đặt làm mặc định* ở bất kỳ địa chỉ phụ nào.
5. **Xóa địa chỉ:**
   - Không cho xóa nếu chỉ còn duy nhất 1 địa chỉ.
   - Nếu xóa địa chỉ đang là mặc định, hệ thống tự động tìm địa chỉ gần nhất còn lại nâng lên làm `macdinh = 1` và đồng bộ `khachhang.dia_chi`.

#### 6.6. Kiểm thử Hồ sơ & Đổi mật khẩu
- Truy cập `index.php?route=profile`.
- Cập nhật Họ tên, Số điện thoại -> Lưu thành công.
- Đổi mật khẩu: Nhập mật khẩu cũ `abc`, mật khẩu mới và xác nhận -> Đăng xuất và đăng nhập lại bằng mật khẩu mới.
