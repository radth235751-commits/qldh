-- ========================================================
-- CSDL: quanly_dongho (Hệ thống Quản lý & Bán Đồng Hồ Pin)
-- Hệ Quản Trị CSDL: Microsoft SQL Server 2017+ / 2019 / 2022
-- ========================================================

IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = N'quanly_dongho')
BEGIN
    CREATE DATABASE quanly_dongho;
END
GO

USE quanly_dongho;
GO

-- Xoá bảng cũ nếu đã tồn tại theo thứ tự ràng buộc
IF OBJECT_ID('ct_phieutrahang', 'U') IS NOT NULL DROP TABLE ct_phieutrahang;
IF OBJECT_ID('phieutrahang', 'U') IS NOT NULL DROP TABLE phieutrahang;
IF OBJECT_ID('phieubaohanh', 'U') IS NOT NULL DROP TABLE phieubaohanh;
IF OBJECT_ID('hoadon', 'U') IS NOT NULL DROP TABLE hoadon;
IF OBJECT_ID('ct_phieudat', 'U') IS NOT NULL DROP TABLE ct_phieudat;
IF OBJECT_ID('phieudat', 'U') IS NOT NULL DROP TABLE phieudat;
IF OBJECT_ID('lichsugia', 'U') IS NOT NULL DROP TABLE lichsugia;
IF OBJECT_ID('ct_khuyenmai', 'U') IS NOT NULL DROP TABLE ct_khuyenmai;
IF OBJECT_ID('khuyenmai', 'U') IS NOT NULL DROP TABLE khuyenmai;
IF OBJECT_ID('ct_phieunhap', 'U') IS NOT NULL DROP TABLE ct_phieunhap;
IF OBJECT_ID('phieunhap', 'U') IS NOT NULL DROP TABLE phieunhap;
IF OBJECT_ID('ct_dondathang', 'U') IS NOT NULL DROP TABLE ct_dondathang;
IF OBJECT_ID('dondathang', 'U') IS NOT NULL DROP TABLE dondathang;
IF OBJECT_ID('nhacungcap', 'U') IS NOT NULL DROP TABLE nhacungcap;
IF OBJECT_ID('dongho', 'U') IS NOT NULL DROP TABLE dongho;
IF OBJECT_ID('hangdongho', 'U') IS NOT NULL DROP TABLE hangdongho;
IF OBJECT_ID('loaidongho', 'U') IS NOT NULL DROP TABLE loaidongho;
IF OBJECT_ID('diachi', 'U') IS NOT NULL DROP TABLE diachi;
IF OBJECT_ID('khachhang', 'U') IS NOT NULL DROP TABLE khachhang;
IF OBJECT_ID('nhanvien', 'U') IS NOT NULL DROP TABLE nhanvien;
IF OBJECT_ID('nguoidung', 'U') IS NOT NULL DROP TABLE nguoidung;
GO

-- 1. Bảng nguoidung
CREATE TABLE nguoidung (
    id INT IDENTITY(1,1) PRIMARY KEY,
    email VARCHAR(100) NULL,
    sodienthoai VARCHAR(15) NOT NULL,
    matkhau VARCHAR(255) NOT NULL,
    hoten NVARCHAR(255) NOT NULL,
    loai TINYINT NOT NULL DEFAULT 3, -- 1: Admin, 2: Nhân viên, 3: Khách hàng
    trangthai TINYINT NOT NULL DEFAULT 1, -- 1: Hoạt động, 0: Khóa
    hinhanh VARCHAR(255) NULL
);
GO

CREATE UNIQUE NONCLUSTERED INDEX uk_nd_sdt ON nguoidung(sodienthoai);
CREATE UNIQUE NONCLUSTERED INDEX uk_nd_email ON nguoidung(email) WHERE email IS NOT NULL;
GO

-- 2. Bảng nhanvien
CREATE TABLE nhanvien (
    ma_nv VARCHAR(20) PRIMARY KEY,
    nguoidung_id INT NULL,
    ho NVARCHAR(50) NOT NULL,
    ten NVARCHAR(50) NOT NULL,
    gioi_tinh NVARCHAR(10) NOT NULL DEFAULT N'Nam' CHECK (gioi_tinh IN (N'Nam', N'Nữ', N'Khác')),
    ngay_sinh DATE NOT NULL,
    dia_chi NVARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(15) NOT NULL,
    email VARCHAR(100) NOT NULL,
    CONSTRAINT fk_nv_nguoidung FOREIGN KEY (nguoidung_id) REFERENCES nguoidung(id) ON DELETE SET NULL ON UPDATE CASCADE
);
GO

CREATE UNIQUE NONCLUSTERED INDEX uk_nv_sdt ON nhanvien(so_dien_thoai);
CREATE UNIQUE NONCLUSTERED INDEX uk_nv_email ON nhanvien(email);
CREATE UNIQUE NONCLUSTERED INDEX uk_nv_nd ON nhanvien(nguoidung_id) WHERE nguoidung_id IS NOT NULL;
GO

-- 3. Bảng khachhang
CREATE TABLE khachhang (
    ma_kh INT IDENTITY(1,1) PRIMARY KEY,
    nguoidung_id INT NULL,
    cmnd VARCHAR(20) NOT NULL,
    ho NVARCHAR(50) NOT NULL,
    ten NVARCHAR(50) NOT NULL,
    gioi_tinh NVARCHAR(10) NOT NULL DEFAULT N'Nam' CHECK (gioi_tinh IN (N'Nam', N'Nữ', N'Khác')),
    ngay_sinh DATE NULL,
    dia_chi NVARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(15) NOT NULL,
    email VARCHAR(100) NULL,
    ma_so_thue VARCHAR(20) NULL,
    CONSTRAINT fk_kh_nguoidung FOREIGN KEY (nguoidung_id) REFERENCES nguoidung(id) ON DELETE SET NULL ON UPDATE CASCADE
);
GO

CREATE UNIQUE NONCLUSTERED INDEX uk_kh_cmnd ON khachhang(cmnd);
CREATE UNIQUE NONCLUSTERED INDEX uk_kh_sdt ON khachhang(so_dien_thoai);
CREATE UNIQUE NONCLUSTERED INDEX uk_kh_nd ON khachhang(nguoidung_id) WHERE nguoidung_id IS NOT NULL;
CREATE UNIQUE NONCLUSTERED INDEX uk_kh_email ON khachhang(email) WHERE email IS NOT NULL;
GO

-- 4. Bảng diachi
CREATE TABLE diachi (
    id INT IDENTITY(1,1) PRIMARY KEY,
    nguoidung_id INT NOT NULL,
    diachi NVARCHAR(255) NOT NULL,
    macdinh TINYINT NOT NULL DEFAULT 1,
    CONSTRAINT fk_diachi_nguoidung FOREIGN KEY (nguoidung_id) REFERENCES nguoidung(id) ON DELETE CASCADE ON UPDATE CASCADE
);
GO

CREATE INDEX idx_diachi_nd ON diachi(nguoidung_id);
GO

-- 5. Bảng loaidongho
CREATE TABLE loaidongho (
    ma_loai INT IDENTITY(1,1) PRIMARY KEY,
    ten_loai NVARCHAR(100) NOT NULL UNIQUE
);
GO

-- 6. Bảng hangdongho
CREATE TABLE hangdongho (
    ma_hang INT IDENTITY(1,1) PRIMARY KEY,
    ten_hang NVARCHAR(100) NOT NULL UNIQUE
);
GO

-- 7. Bảng dongho
CREATE TABLE dongho (
    ma_dh VARCHAR(20) PRIMARY KEY,
    ten_dh NVARCHAR(255) NOT NULL,
    gia DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    so_luong_ton INT NOT NULL DEFAULT 0,
    mo_ta NVARCHAR(MAX) NULL,
    status TINYINT NOT NULL DEFAULT 1, -- 1: Đang bán, 0: Ngừng kinh doanh
    image VARCHAR(255) NULL,
    thoi_gian_bh INT NOT NULL DEFAULT 12,
    ma_loai INT NOT NULL,
    ma_hang INT NOT NULL,
    CONSTRAINT fk_dh_loai FOREIGN KEY (ma_loai) REFERENCES loaidongho(ma_loai) ON UPDATE CASCADE,
    CONSTRAINT fk_dh_hang FOREIGN KEY (ma_hang) REFERENCES hangdongho(ma_hang) ON UPDATE CASCADE
);
GO

-- 8. Bảng nhacungcap
CREATE TABLE nhacungcap (
    ma_ncc VARCHAR(20) PRIMARY KEY,
    ten_ncc NVARCHAR(150) NOT NULL,
    dia_chi NVARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(15) NOT NULL
);
GO

-- 9. Bảng dondathang (đặt hàng từ NCC)
CREATE TABLE dondathang (
    ma_ddh VARCHAR(20) PRIMARY KEY,
    ngay_dat DATETIME NOT NULL DEFAULT GETDATE(),
    ma_ncc VARCHAR(20) NOT NULL,
    ma_nv VARCHAR(20) NOT NULL,
    CONSTRAINT fk_ddh_ncc FOREIGN KEY (ma_ncc) REFERENCES nhacungcap(ma_ncc) ON UPDATE CASCADE,
    CONSTRAINT fk_ddh_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 10. Bảng ct_dondathang
CREATE TABLE ct_dondathang (
    ma_ddh VARCHAR(20) NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    so_luong_dat INT NOT NULL,
    don_gia DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (ma_ddh, ma_dh),
    CONSTRAINT fk_ctddh_ddh FOREIGN KEY (ma_ddh) REFERENCES dondathang(ma_ddh) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ctddh_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE
);
GO

-- 11. Bảng phieunhap
CREATE TABLE phieunhap (
    ma_pn VARCHAR(20) PRIMARY KEY,
    ngay_nhap DATETIME NOT NULL DEFAULT GETDATE(),
    ma_ddh VARCHAR(20) NOT NULL UNIQUE,
    ma_nv VARCHAR(20) NOT NULL,
    CONSTRAINT fk_pn_ddh FOREIGN KEY (ma_ddh) REFERENCES dondathang(ma_ddh) ON UPDATE CASCADE,
    CONSTRAINT fk_pn_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 12. Bảng ct_phieunhap
CREATE TABLE ct_phieunhap (
    ma_pn VARCHAR(20) NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    so_luong_nhap INT NOT NULL,
    don_gia_nhap DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (ma_pn, ma_dh),
    CONSTRAINT fk_ctpn_pn FOREIGN KEY (ma_pn) REFERENCES phieunhap(ma_pn) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ctpn_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE
);
GO

-- 13. Bảng khuyenmai
CREATE TABLE khuyenmai (
    ma_km VARCHAR(20) PRIMARY KEY,
    ly_do_km NVARCHAR(255) NOT NULL,
    ngay_bat_dau DATETIME NOT NULL,
    ngay_ket_thuc DATETIME NOT NULL,
    ma_nv VARCHAR(20) NOT NULL,
    CONSTRAINT fk_km_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 14. Bảng ct_khuyenmai
CREATE TABLE ct_khuyenmai (
    ma_km VARCHAR(20) NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    phan_tram_giam DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (ma_km, ma_dh),
    CONSTRAINT fk_ctkm_km FOREIGN KEY (ma_km) REFERENCES khuyenmai(ma_km) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ctkm_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE
);
GO

-- 15. Bảng lichsugia
CREATE TABLE lichsugia (
    ma_dh VARCHAR(20) NOT NULL,
    ngay_ap_dung DATETIME NOT NULL DEFAULT GETDATE(),
    gia_moi DECIMAL(15,2) NOT NULL,
    ma_nv VARCHAR(20) NOT NULL,
    PRIMARY KEY (ma_dh, ngay_ap_dung),
    CONSTRAINT fk_lsg_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE,
    CONSTRAINT fk_lsg_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 16. Bảng phieudat (đơn đặt hàng của Khách hàng)
CREATE TABLE phieudat (
    ma_pd INT IDENTITY(1,1) PRIMARY KEY,
    ngay_dat DATETIME NOT NULL DEFAULT GETDATE(),
    trang_thai_pd NVARCHAR(50) NOT NULL DEFAULT N'Chờ xác nhận',
    dia_chi_giao NVARCHAR(255) NOT NULL,
    ghi_chu NVARCHAR(MAX) NULL,
    ma_kh INT NOT NULL,
    ma_nv_duyet VARCHAR(20) NULL,
    ma_nv_giao VARCHAR(20) NULL,
    CONSTRAINT fk_pd_kh FOREIGN KEY (ma_kh) REFERENCES khachhang(ma_kh) ON UPDATE CASCADE,
    CONSTRAINT fk_pd_nv_duyet FOREIGN KEY (ma_nv_duyet) REFERENCES nhanvien(ma_nv),
    CONSTRAINT fk_pd_nv_giao FOREIGN KEY (ma_nv_giao) REFERENCES nhanvien(ma_nv)
);
GO

-- 17. Bảng ct_phieudat
CREATE TABLE ct_phieudat (
    ma_pd INT NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    so_luong INT NOT NULL,
    don_gia DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (ma_pd, ma_dh),
    CONSTRAINT fk_ctpd_pd FOREIGN KEY (ma_pd) REFERENCES phieudat(ma_pd) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ctpd_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE
);
GO

-- 18. Bảng hoadon
CREATE TABLE hoadon (
    so_hoa_don VARCHAR(20) PRIMARY KEY,
    ngay_lap_hd DATETIME NOT NULL DEFAULT GETDATE(),
    tong_tien DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ma_so_thue VARCHAR(20) NULL,
    ma_pd INT NOT NULL UNIQUE,
    ma_nv VARCHAR(20) NOT NULL,
    CONSTRAINT fk_hd_pd FOREIGN KEY (ma_pd) REFERENCES phieudat(ma_pd) ON UPDATE CASCADE,
    CONSTRAINT fk_hd_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 19. Bảng phieubaohanh
CREATE TABLE phieubaohanh (
    so_phieu_bh VARCHAR(20) PRIMARY KEY,
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL,
    trang_thai_truoc_bh NVARCHAR(MAX) NULL,
    trang_thai_sau_bh NVARCHAR(MAX) NULL,
    so_hoa_don VARCHAR(20) NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    ma_nv_lap VARCHAR(20) NOT NULL,
    ma_nv_nhan VARCHAR(20) NULL,
    ma_nv_giao_tra VARCHAR(20) NULL,
    CONSTRAINT fk_pbh_hd FOREIGN KEY (so_hoa_don) REFERENCES hoadon(so_hoa_don) ON UPDATE CASCADE,
    CONSTRAINT fk_pbh_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE,
    CONSTRAINT fk_pbh_nv_lap FOREIGN KEY (ma_nv_lap) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE,
    CONSTRAINT fk_pbh_nv_nhan FOREIGN KEY (ma_nv_nhan) REFERENCES nhanvien(ma_nv),
    CONSTRAINT fk_pbh_nv_tra FOREIGN KEY (ma_nv_giao_tra) REFERENCES nhanvien(ma_nv)
);
GO

-- 20. Bảng phieutrahang
CREATE TABLE phieutrahang (
    ma_pth VARCHAR(20) PRIMARY KEY,
    ngay_tra DATETIME NOT NULL DEFAULT GETDATE(),
    so_hoa_don VARCHAR(20) NOT NULL,
    ma_nv VARCHAR(20) NOT NULL,
    CONSTRAINT fk_pth_hd FOREIGN KEY (so_hoa_don) REFERENCES hoadon(so_hoa_don) ON UPDATE CASCADE,
    CONSTRAINT fk_pth_nv FOREIGN KEY (ma_nv) REFERENCES nhanvien(ma_nv) ON UPDATE CASCADE
);
GO

-- 21. Bảng ct_phieutrahang
CREATE TABLE ct_phieutrahang (
    ma_pth VARCHAR(20) NOT NULL,
    ma_dh VARCHAR(20) NOT NULL,
    so_luong_tra INT NOT NULL,
    ly_do NVARCHAR(255) NULL,
    PRIMARY KEY (ma_pth, ma_dh),
    CONSTRAINT fk_ctpth_pth FOREIGN KEY (ma_pth) REFERENCES phieutrahang(ma_pth) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ctpth_dh FOREIGN KEY (ma_dh) REFERENCES dongho(ma_dh) ON UPDATE CASCADE
);
GO

-- ========================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- Mật khẩu mặc định cho các tài khoản là: abc (md5 = 900150983cd24fb0d6963f7d28e17f72)
-- ========================================================

SET IDENTITY_INSERT nguoidung ON;
INSERT INTO nguoidung (id, email, sodienthoai, matkhau, hoten, loai, trangthai, hinhanh) VALUES
(1, 'admin@watchstore.vn', '0988994683', '900150983cd24fb0d6963f7d28e17f72', N'Trần Hoàng Nam', 1, 1, 'admin.png'),
(2, 'nv1@watchstore.vn', '0988994684', '900150983cd24fb0d6963f7d28e17f72', N'Lê Minh Tuấn', 2, 1, 'nv1.jpg'),
(3, 'nv2@watchstore.vn', '0988994685', '900150983cd24fb0d6963f7d28e17f72', N'Phạm Thị Lan', 2, 1, 'nv2.jpg'),
(4, 'kh1@gmail.com', '0988994686', '900150983cd24fb0d6963f7d28e17f72', N'Nguyễn Thị Thu An', 3, 1, NULL),
(5, 'kh2@gmail.com', '0988994687', '900150983cd24fb0d6963f7d28e17f72', N'Hồ Xuân Minh', 3, 1, NULL);
SET IDENTITY_INSERT nguoidung OFF;
GO

INSERT INTO nhanvien (ma_nv, nguoidung_id, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email) VALUES
('NV01', 1, N'Trần Hoàng', N'Nam', N'Nam', '1992-05-14', N'12 Tôn Đức Thắng, Long Xuyên, An Giang', '0988994683', 'admin@watchstore.vn'),
('NV02', 2, N'Lê Minh', N'Tuấn', N'Nam', '1996-08-20', N'45 Hùng Vương, Long Xuyên, An Giang', '0988994684', 'nv1@watchstore.vn'),
('NV03', 3, N'Phạm Thị', N'Lan', N'Nữ', '1998-11-02', N'88 Trần Hưng Đạo, Long Xuyên, An Giang', '0988994685', 'nv2@watchstore.vn');
GO

SET IDENTITY_INSERT khachhang ON;
INSERT INTO khachhang (ma_kh, nguoidung_id, cmnd, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email, ma_so_thue) VALUES
(1, 4, '089199001234', N'Nguyễn Thị Thu', N'An', N'Nữ', '1999-03-15', N'102 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên, An Giang', '0988994686', 'kh1@gmail.com', '8391029384'),
(2, 5, '089095005678', N'Hồ Xuân', N'Minh', N'Nam', '1995-09-22', N'25 Nguyễn Trãi, Mỹ Long, Long Xuyên, An Giang', '0988994687', 'kh2@gmail.com', '8391029999');
SET IDENTITY_INSERT khachhang OFF;
GO

SET IDENTITY_INSERT diachi ON;
INSERT INTO diachi (id, nguoidung_id, diachi, macdinh) VALUES
(1, 4, N'102 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên, An Giang', 1),
(2, 4, N'Khu Công Nghiệp Bình Hòa, Châu Thành, An Giang', 0),
(3, 5, N'25 Nguyễn Trãi, Mỹ Long, Long Xuyên, An Giang', 1);
SET IDENTITY_INSERT diachi OFF;
GO

SET IDENTITY_INSERT hangdongho ON;
INSERT INTO hangdongho (ma_hang, ten_hang) VALUES
(1, N'Casio'),
(2, N'Citizen'),
(3, N'Seiko'),
(4, N'Orient'),
(5, N'Tissot');
SET IDENTITY_INSERT hangdongho OFF;
GO

SET IDENTITY_INSERT loaidongho ON;
INSERT INTO loaidongho (ma_loai, ten_loai) VALUES
(1, N'Đồng hồ Pin dây kim loại'),
(2, N'Đồng hồ Pin dây da'),
(3, N'Đồng hồ Pin thể thao (G-Shock / Edifice)');
SET IDENTITY_INSERT loaidongho OFF;
GO

INSERT INTO dongho (ma_dh, ten_dh, gia, so_luong_ton, mo_ta, status, image, thoi_gian_bh, ma_loai, ma_hang) VALUES
('DH01', N'Casio Edifice EFR-526L-1AVUDF (Pin Dây Da)', 3450000.00, 15, N'Máy Pin Quartz Nhật Bản, kính khoáng cứng, chống nước 10ATM, dây da cao cấp, bấm giờ Chronograph.', 1, 'images/products/dh1.jpg', 24, 2, 1),
('DH02', N'Casio G-Shock GA-2100-1A1DR (Pin Thể Thao)', 3100000.00, 20, N'Máy Pin Quartz tuổi thọ pin 3 năm, thiết kế bát giác siêu mỏng, chống sốc tuyệt đối, chống nước 20ATM.', 1, 'images/products/dh2.jpg', 24, 3, 1),
('DH03', N'Seiko Quartz SUR309P1 (Pin Dây Thép)', 5200000.00, 10, N'Bộ máy Pin Cal.6N52 độ chính xác cao, kính Sapphire chống trầy xước, dây thép không gỉ 316L.', 1, 'images/products/dh3.jpg', 24, 1, 3),
('DH04', N'Citizen Quartz BI5000-87E (Pin Dây Thép)', 3250000.00, 12, N'Máy Pin Quartz Caliber G111 Nhật Bản, mặt đen tối giản, dây thép không gỉ sang trọng.', 1, 'images/products/dh4.jpg', 24, 1, 2),
('DH05', N'Orient Classic Quartz RA-QC1703S10B (Pin Dây Da)', 3850000.00, 8, N'Máy Pin Quartz siêu mỏng, mặt trắng thanh lịch, dây da dập vân cá sấu sang trọng.', 1, 'images/products/dh5.jpg', 24, 2, 4),
('DH06', N'Tissot PRX Quartz T137.410.11.041.00 (Pin Dây Thép)', 10500000.00, 5, N'Bộ máy Pin ETA F06.115 Thụy Sĩ có chức năng báo hết pin (EOL), kính Sapphire phủ chống lóa.', 1, 'images/products/dh6.jpg', 24, 1, 5),
('DH07', N'Casio MTP-1374D-1AVDF (Pin 6 Kim Dây Thép)', 2150000.00, 18, N'Máy Pin Quartz 3 mặt số phụ hiển thị lịch ngày, thứ và 24h, dây thép không gỉ bền bỉ.', 1, 'images/products/dh7.jpg', 12, 1, 1),
('DH08', N'Casio Edifice EFV-540D-1AVUDF (Pin Thể Thao)', 3650000.00, 9, N'Máy Pin Quartz Chronograph thể thao, vỏ và dây thép đúc đặc, chống nước 100m.', 1, 'images/products/dh8.jpg', 24, 3, 1);
GO

INSERT INTO nhacungcap (ma_ncc, ten_ncc, dia_chi, email, so_dien_thoai) VALUES
('NCC01', N'Công ty CP Phân Phối Đồng Hồ Anh Khuê', N'20 Đường 3/2, Quận 10, TP.HCM', 'sales@anhkhuewatch.vn', '02839270317'),
('NCC02', N'Công ty TNHH Thương Mại Hải Triều Import', N'190D Trần Quang Khải, Quận 1, TP.HCM', 'import@haitrieu.vn', '02877797779');
GO

PRINT N'Khởi tạo cơ sở dữ liệu quanly_dongho thành công!';
GO
