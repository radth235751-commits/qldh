-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1:3307
-- Thời gian đã tạo: Th10 17, 2026 lúc 04:40 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanly_dongho`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_dondathang`
--

CREATE TABLE `ct_dondathang` (
  `ma_ddh` varchar(20) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `so_luong_dat` int(11) NOT NULL,
  `don_gia` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_dondathang`
--

INSERT INTO `ct_dondathang` (`ma_ddh`, `ma_dh`, `so_luong_dat`, `don_gia`) VALUES
('DDH01', 'DH01', 10, 2300000.00),
('DDH01', 'DH02', 10, 2100000.00),
('DDH02', 'DH03', 5, 3600000.00),
('DDH02', 'DH06', 3, 7400000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_khuyenmai`
--

CREATE TABLE `ct_khuyenmai` (
  `ma_km` varchar(20) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `phan_tram_giam` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_khuyenmai`
--

INSERT INTO `ct_khuyenmai` (`ma_km`, `ma_dh`, `phan_tram_giam`) VALUES
('KM01', 'DH01', 10.00),
('KM01', 'DH03', 5.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_phieudat`
--

CREATE TABLE `ct_phieudat` (
  `ma_pd` int(11) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_phieudat`
--

INSERT INTO `ct_phieudat` (`ma_pd`, `ma_dh`, `so_luong`, `don_gia`) VALUES
(1, 'DH01', 1, 3105000.00),
(1, 'DH02', 1, 3100000.00),
(2, 'DH05', 1, 3850000.00),
(3, 'DH04', 1, 3250000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_phieunhap`
--

CREATE TABLE `ct_phieunhap` (
  `ma_pn` varchar(20) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `so_luong_nhap` int(11) NOT NULL,
  `don_gia_nhap` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_phieunhap`
--

INSERT INTO `ct_phieunhap` (`ma_pn`, `ma_dh`, `so_luong_nhap`, `don_gia_nhap`) VALUES
('PN01', 'DH01', 10, 2300000.00),
('PN01', 'DH02', 8, 2100000.00);

--
-- Bẫy `ct_phieunhap`
--
DELIMITER $$
CREATE TRIGGER `trg_cong_ton_khi_nhap` AFTER INSERT ON `ct_phieunhap` FOR EACH ROW BEGIN
    UPDATE `dongho`
    SET `so_luong_ton` = `so_luong_ton` + NEW.`so_luong_nhap`
    WHERE `ma_dh` = NEW.`ma_dh`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ct_phieutrahang`
--

CREATE TABLE `ct_phieutrahang` (
  `ma_pth` varchar(20) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `so_luong_tra` int(11) NOT NULL,
  `ly_do` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ct_phieutrahang`
--

INSERT INTO `ct_phieutrahang` (`ma_pth`, `ma_dh`, `so_luong_tra`, `ly_do`) VALUES
('PTH01', 'DH02', 1, 'Khách đổi sang mẫu đồng hồ pin mặt tròn dây da');

--
-- Bẫy `ct_phieutrahang`
--
DELIMITER $$
CREATE TRIGGER `trg_cong_ton_khi_tra` AFTER INSERT ON `ct_phieutrahang` FOR EACH ROW BEGIN
    UPDATE `dongho`
    SET `so_luong_ton` = `so_luong_ton` + NEW.`so_luong_tra`
    WHERE `ma_dh` = NEW.`ma_dh`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `diachi`
--

CREATE TABLE `diachi` (
  `id` int(11) NOT NULL,
  `nguoidung_id` int(11) NOT NULL,
  `diachi` varchar(255) NOT NULL,
  `macdinh` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `diachi`
--

INSERT INTO `diachi` (`id`, `nguoidung_id`, `diachi`, `macdinh`) VALUES
(1, 4, '102 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên, An Giang', 1),
(2, 4, 'Khu Công Nghiệp Bình Hòa, Châu Thành, An Giang', 0),
(3, 5, '25 Nguyễn Trãi, Mỹ Long, Long Xuyên, An Giang', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dondathang`
--

CREATE TABLE `dondathang` (
  `ma_ddh` varchar(20) NOT NULL,
  `ngay_dat` datetime NOT NULL DEFAULT current_timestamp(),
  `ma_ncc` varchar(20) NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên lập đơn đặt hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dondathang`
--

INSERT INTO `dondathang` (`ma_ddh`, `ngay_dat`, `ma_ncc`, `ma_nv`) VALUES
('DDH01', '2026-11-01 09:00:00', 'NCC01', 'NV02'),
('DDH02', '2026-11-10 14:15:00', 'NCC02', 'NV02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dongho`
--

CREATE TABLE `dongho` (
  `ma_dh` varchar(20) NOT NULL,
  `ten_dh` varchar(255) NOT NULL,
  `gia` decimal(15,2) NOT NULL DEFAULT 0.00,
  `so_luong_ton` int(11) NOT NULL DEFAULT 0,
  `mo_ta` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Đang bán, 0: Ngừng kinh doanh',
  `image` varchar(255) DEFAULT NULL,
  `thoi_gian_bh` int(11) NOT NULL DEFAULT 12 COMMENT 'Thời gian bảo hành (tháng)',
  `ma_loai` int(11) NOT NULL,
  `ma_hang` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `dongho`
--

INSERT INTO `dongho` (`ma_dh`, `ten_dh`, `gia`, `so_luong_ton`, `mo_ta`, `status`, `image`, `thoi_gian_bh`, `ma_loai`, `ma_hang`) VALUES
('DH01', 'Casio Edifice EFR-526L-1AVUDF (Pin Dây Da)', 3450000.00, 15, '<p>Máy Pin Quartz Nhật Bản, kính khoáng cứng, chống nước 10ATM, dây da cao cấp, bấm giờ Chronograph.</p>', 1, 'images/products/dh1.jpg', 24, 2, 1),
('DH02', 'Casio G-Shock GA-2100-1A1DR (Pin Thể Thao)', 3100000.00, 20, '<p>Máy Pin Quartz tuổi thọ pin 3 năm, thiết kế bát giác siêu mỏng, chống sốc tuyệt đối, chống nước 20ATM.</p>', 1, 'images/products/dh2.jpg', 24, 3, 1),
('DH03', 'Seiko Quartz SUR309P1 (Pin Dây Thép)', 5200000.00, 10, '<p>Bộ máy Pin Cal.6N52 độ chính xác cao, kính Sapphire chống trầy xước, dây thép không gỉ 316L.</p>', 1, 'images/products/dh3.jpg', 24, 1, 3),
('DH04', 'Citizen Quartz BI5000-87E (Pin Dây Thép)', 3250000.00, 12, '<p>Máy Pin Quartz Caliber G111 Nhật Bản, mặt đen tối giản, dây thép không gỉ sang trọng.</p>', 1, 'images/products/dh4.jpg', 24, 1, 2),
('DH05', 'Orient Classic Quartz RA-QC1703S10B (Pin Dây Da)', 3850000.00, 8, '<p>Máy Pin Quartz siêu mỏng, mặt trắng thanh lịch, dây da dập vân cá sấu sang trọng.</p>', 1, 'images/products/dh5.jpg', 24, 2, 4),
('DH06', 'Tissot PRX Quartz T137.410.11.041.00 (Pin Dây Thép)', 10500000.00, 5, '<p>Bộ máy Pin ETA F06.115 Thụy Sĩ có chức năng báo hết pin (EOL), kính Sapphire phủ chống lóa.</p>', 1, 'images/products/dh6.jpg', 24, 1, 5),
('DH07', 'Casio MTP-1374D-1AVDF (Pin 6 Kim Dây Thép)', 2150000.00, 18, '<p>Máy Pin Quartz 3 mặt số phụ hiển thị lịch ngày, thứ và 24h, dây thép không gỉ bền bỉ.</p>', 1, 'images/products/dh7.jpg', 12, 1, 1),
('DH08', 'Casio Edifice EFV-540D-1AVUDF (Pin Thể Thao)', 3650000.00, 9, '<p>Máy Pin Quartz Chronograph thể thao, vỏ và dây thép đúc đặc, chống nước 100m.</p>', 1, 'images/products/dh8.jpg', 24, 3, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hangdongho`
--

CREATE TABLE `hangdongho` (
  `ma_hang` int(11) NOT NULL,
  `ten_hang` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hangdongho`
--

INSERT INTO `hangdongho` (`ma_hang`, `ten_hang`) VALUES
(1, 'Casio'),
(2, 'Citizen'),
(4, 'Orient'),
(3, 'Seiko'),
(5, 'Tissot');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoadon`
--

CREATE TABLE `hoadon` (
  `so_hoa_don` varchar(20) NOT NULL,
  `ngay_lap_hd` datetime NOT NULL DEFAULT current_timestamp(),
  `tong_tien` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ma_so_thue` varchar(20) DEFAULT NULL,
  `ma_pd` int(11) NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên duyệt đơn kiêm lập hóa đơn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hoadon`
--

INSERT INTO `hoadon` (`so_hoa_don`, `ngay_lap_hd`, `tong_tien`, `ma_so_thue`, `ma_pd`, `ma_nv`) VALUES
('HD01', '2026-11-14 11:30:00', 6205000.00, '8391029384', 1, 'NV02'),
('HD02', '2026-11-16 17:00:00', 3850000.00, '8391029999', 2, 'NV02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khachhang`
--

CREATE TABLE `khachhang` (
  `ma_kh` int(11) NOT NULL,
  `nguoidung_id` int(11) DEFAULT NULL,
  `cmnd` varchar(20) DEFAULT NULL,
  `ho` varchar(50) NOT NULL,
  `ten` varchar(50) NOT NULL,
  `gioi_tinh` enum('Nam','Nữ','Khác') NOT NULL DEFAULT 'Nam',
  `ngay_sinh` date DEFAULT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ma_so_thue` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khachhang`
--

INSERT INTO `khachhang` (`ma_kh`, `nguoidung_id`, `cmnd`, `ho`, `ten`, `gioi_tinh`, `ngay_sinh`, `dia_chi`, `so_dien_thoai`, `email`, `ma_so_thue`) VALUES
(1, 4, '089199001234', 'Nguyễn Thị Thu', 'An', 'Nữ', '1999-03-15', '102 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên, An Giang', '0988994686', 'kh1@gmail.com', '8391029384'),
(2, 5, '089095005678', 'Hồ Xuân', 'Minh', 'Nam', '1995-09-22', '25 Nguyễn Trãi, Mỹ Long, Long Xuyên, An Giang', '0988994687', 'kh2@gmail.com', '8391029999');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khuyenmai`
--

CREATE TABLE `khuyenmai` (
  `ma_km` varchar(20) NOT NULL,
  `ly_do_km` varchar(255) NOT NULL,
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_ket_thuc` datetime NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên tạo chương trình khuyến mãi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `khuyenmai`
--

INSERT INTO `khuyenmai` (`ma_km`, `ly_do_km`, `ngay_bat_dau`, `ngay_ket_thuc`, `ma_nv`) VALUES
('KM01', 'Ưu đãi Tuần lễ Vàng tháng 11', '2026-11-01 00:00:00', '2026-11-30 23:59:59', 'NV01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lichsugia`
--

CREATE TABLE `lichsugia` (
  `ma_dh` varchar(20) NOT NULL,
  `ngay_ap_dung` datetime NOT NULL DEFAULT current_timestamp(),
  `gia_moi` decimal(15,2) NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên cập nhật giá mới'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lichsugia`
--

INSERT INTO `lichsugia` (`ma_dh`, `ngay_ap_dung`, `gia_moi`, `ma_nv`) VALUES
('DH01', '2026-09-01 08:00:00', 3200000.00, 'NV01'),
('DH01', '2026-10-15 09:30:00', 3450000.00, 'NV01'),
('DH03', '2026-10-20 10:00:00', 5200000.00, 'NV02');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `loaidongho`
--

CREATE TABLE `loaidongho` (
  `ma_loai` int(11) NOT NULL,
  `ten_loai` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `loaidongho`
--

INSERT INTO `loaidongho` (`ma_loai`, `ten_loai`) VALUES
(2, 'Đồng hồ Pin dây da'),
(1, 'Đồng hồ Pin dây kim loại'),
(3, 'Đồng hồ Pin thể thao (G-Shock / Edifice)');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoidung`
--

CREATE TABLE `nguoidung` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `sodienthoai` varchar(15) NOT NULL,
  `matkhau` varchar(255) NOT NULL,
  `hoten` varchar(255) NOT NULL,
  `loai` tinyint(4) NOT NULL DEFAULT 3 COMMENT '1: Admin, 2: Nhân viên, 3: Khách hàng',
  `trangthai` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1: Hoạt động, 0: Khóa',
  `hinhanh` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoidung`
--

INSERT INTO `nguoidung` (`id`, `email`, `sodienthoai`, `matkhau`, `hoten`, `loai`, `trangthai`, `hinhanh`) VALUES
(1, 'admin@watchstore.vn', '0988994683', '900150983cd24fb0d6963f7d28e17f72', 'Trần Hoàng Nam', 1, 1, 'admin.png'),
(2, 'nv1@watchstore.vn', '0988994684', '900150983cd24fb0d6963f7d28e17f72', 'Lê Minh Tuấn', 2, 1, 'nv1.jpg'),
(3, 'nv2@watchstore.vn', '0988994685', '900150983cd24fb0d6963f7d28e17f72', 'Phạm Thị Lan', 2, 1, 'nv2.jpg'),
(4, 'kh1@gmail.com', '0988994686', '900150983cd24fb0d6963f7d28e17f72', 'Nguyễn Thị Thu An', 3, 1, NULL),
(5, 'kh2@gmail.com', '0988994687', '900150983cd24fb0d6963f7d28e17f72', 'Hồ Xuân Minh', 3, 1, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhacungcap`
--

CREATE TABLE `nhacungcap` (
  `ma_ncc` varchar(20) NOT NULL,
  `ten_ncc` varchar(150) NOT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhacungcap`
--

INSERT INTO `nhacungcap` (`ma_ncc`, `ten_ncc`, `dia_chi`, `email`, `so_dien_thoai`) VALUES
('NCC01', 'Công ty CP Phân Phối Đồng Hồ Anh Khuê', '20 Đường 3/2, Quận 10, TP.HCM', 'sales@anhkhuewatch.vn', '02839270317'),
('NCC02', 'Công ty TNHH Thương Mại Hải Triều Import', '190D Trần Quang Khải, Quận 1, TP.HCM', 'import@haitrieu.vn', '02877797779');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhanvien`
--

CREATE TABLE `nhanvien` (
  `ma_nv` varchar(20) NOT NULL,
  `nguoidung_id` int(11) DEFAULT NULL,
  `ho` varchar(50) NOT NULL,
  `ten` varchar(50) NOT NULL,
  `gioi_tinh` enum('Nam','Nữ','Khác') NOT NULL DEFAULT 'Nam',
  `ngay_sinh` date NOT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nhanvien`
--

INSERT INTO `nhanvien` (`ma_nv`, `nguoidung_id`, `ho`, `ten`, `gioi_tinh`, `ngay_sinh`, `dia_chi`, `so_dien_thoai`, `email`) VALUES
('NV01', 1, 'Trần Hoàng', 'Nam', 'Nam', '1992-05-14', '12 Tôn Đức Thắng, Long Xuyên, An Giang', '0988994683', 'admin@watchstore.vn'),
('NV02', 2, 'Lê Minh', 'Tuấn', 'Nam', '1996-08-20', '45 Hùng Vương, Long Xuyên, An Giang', '0988994684', 'nv1@watchstore.vn'),
('NV03', 3, 'Phạm Thị', 'Lan', 'Nữ', '1998-11-02', '88 Trần Hưng Đạo, Long Xuyên, An Giang', '0988994685', 'nv2@watchstore.vn');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieubaohanh`
--

CREATE TABLE `phieubaohanh` (
  `so_phieu_bh` varchar(20) NOT NULL,
  `ngay_bat_dau` date NOT NULL,
  `ngay_ket_thuc` date NOT NULL,
  `trang_thai_truoc_bh` text DEFAULT NULL,
  `trang_thai_sau_bh` text DEFAULT NULL,
  `so_hoa_don` varchar(20) NOT NULL,
  `ma_dh` varchar(20) NOT NULL,
  `ma_nv_lap` varchar(20) NOT NULL COMMENT 'Nhân viên lập phiếu bảo hành',
  `ma_nv_nhan` varchar(20) DEFAULT NULL COMMENT 'Nhân viên nhận đồng hồ bảo hành',
  `ma_nv_giao_tra` varchar(20) DEFAULT NULL COMMENT 'Nhân viên giao trả đồng hồ bảo hành'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieubaohanh`
--

INSERT INTO `phieubaohanh` (`so_phieu_bh`, `ngay_bat_dau`, `ngay_ket_thuc`, `trang_thai_truoc_bh`, `trang_thai_sau_bh`, `so_hoa_don`, `ma_dh`, `ma_nv_lap`, `ma_nv_nhan`, `ma_nv_giao_tra`) VALUES
('BH01', '2026-11-15', '2028-11-15', NULL, NULL, 'HD01', 'DH01', 'NV02', NULL, NULL),
('BH02', '2026-11-15', '2028-11-15', NULL, NULL, 'HD01', 'DH02', 'NV02', NULL, NULL),
('BH03', '2026-11-16', '2028-11-16', NULL, NULL, 'HD02', 'DH05', 'NV02', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieudat`
--

CREATE TABLE `phieudat` (
  `ma_pd` int(11) NOT NULL,
  `ngay_dat` datetime NOT NULL DEFAULT current_timestamp(),
  `ho_ten_nguoi_nhan` varchar(100) NOT NULL,
  `dia_chi_nguoi_nhan` varchar(255) NOT NULL,
  `sdt_nguoi_nhan` varchar(15) NOT NULL,
  `ngay_gio_giao` datetime DEFAULT NULL,
  `trang_thai` enum('Chờ duyệt','Đã duyệt','Đã phân công nhân viên giao hàng','Hoàn tất','Đã hủy') NOT NULL DEFAULT 'Chờ duyệt',
  `ma_kh` int(11) NOT NULL,
  `ma_nv_duyet` varchar(20) DEFAULT NULL COMMENT 'Nhân viên duyệt đơn',
  `ma_nv_giao` varchar(20) DEFAULT NULL COMMENT 'Nhân viên giao hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieudat`
--

INSERT INTO `phieudat` (`ma_pd`, `ngay_dat`, `ho_ten_nguoi_nhan`, `dia_chi_nguoi_nhan`, `sdt_nguoi_nhan`, `ngay_gio_giao`, `trang_thai`, `ma_kh`, `ma_nv_duyet`, `ma_nv_giao`) VALUES
(1, '2026-11-14 10:00:00', 'Nguyễn Thị Thu An', '102 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên, An Giang', '0988994686', '2026-11-15 14:00:00', 'Hoàn tất', 1, 'NV02', 'NV03'),
(2, '2026-11-16 16:20:00', 'Hồ Xuân Minh', '25 Nguyễn Trãi, Mỹ Long, Long Xuyên, An Giang', '0988994687', '2026-11-18 09:30:00', 'Đã phân công nhân viên giao hàng', 2, 'NV02', 'NV03'),
(3, '2026-11-17 19:00:00', 'Nguyễn Thị Thu An', 'Khu Công Nghiệp Bình Hòa, Châu Thành, An Giang', '0988994686', NULL, 'Chờ duyệt', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieunhap`
--

CREATE TABLE `phieunhap` (
  `ma_pn` varchar(20) NOT NULL,
  `ngay_nhap` datetime NOT NULL DEFAULT current_timestamp(),
  `ma_ddh` varchar(20) NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên lập phiếu nhập'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieunhap`
--

INSERT INTO `phieunhap` (`ma_pn`, `ngay_nhap`, `ma_ddh`, `ma_nv`) VALUES
('PN01', '2026-11-03 15:30:00', 'DDH01', 'NV03');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieutrahang`
--

CREATE TABLE `phieutrahang` (
  `ma_pth` varchar(20) NOT NULL,
  `ngay_tra` datetime NOT NULL DEFAULT current_timestamp(),
  `so_hoa_don` varchar(20) NOT NULL,
  `ma_nv` varchar(20) NOT NULL COMMENT 'Nhân viên lập phiếu nhận trả hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieutrahang`
--

INSERT INTO `phieutrahang` (`ma_pth`, `ngay_tra`, `so_hoa_don`, `ma_nv`) VALUES
('PTH01', '2026-11-17 10:15:00', 'HD01', 'NV03');

--
-- Bẫy `phieutrahang`
--
DELIMITER $$
CREATE TRIGGER `trg_kiem_tra_han_tra_hang` BEFORE INSERT ON `phieutrahang` FOR EACH ROW BEGIN
    DECLARE v_ngay_mua DATETIME;

    SELECT `ngay_lap_hd` INTO v_ngay_mua
    FROM `hoadon`
    WHERE `so_hoa_don` = NEW.`so_hoa_don`;

    IF DATEDIFF(NEW.`ngay_tra`, v_ngay_mua) > 7 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Lỗi: Đã quá thời hạn 1 tuần (7 ngày) kể từ ngày mua hàng, không thể lập phiếu trả!';
    END IF;
END
$$
DELIMITER ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `ct_dondathang`
--
ALTER TABLE `ct_dondathang`
  ADD PRIMARY KEY (`ma_ddh`,`ma_dh`),
  ADD KEY `idx_ctddh_dh` (`ma_dh`);

--
-- Chỉ mục cho bảng `ct_khuyenmai`
--
ALTER TABLE `ct_khuyenmai`
  ADD PRIMARY KEY (`ma_km`,`ma_dh`),
  ADD KEY `idx_ctkm_dh` (`ma_dh`);

--
-- Chỉ mục cho bảng `ct_phieudat`
--
ALTER TABLE `ct_phieudat`
  ADD PRIMARY KEY (`ma_pd`,`ma_dh`),
  ADD KEY `idx_ctpd_dh` (`ma_dh`);

--
-- Chỉ mục cho bảng `ct_phieunhap`
--
ALTER TABLE `ct_phieunhap`
  ADD PRIMARY KEY (`ma_pn`,`ma_dh`),
  ADD KEY `idx_ctpn_dh` (`ma_dh`);

--
-- Chỉ mục cho bảng `ct_phieutrahang`
--
ALTER TABLE `ct_phieutrahang`
  ADD PRIMARY KEY (`ma_pth`,`ma_dh`),
  ADD KEY `idx_ctpth_dh` (`ma_dh`);

--
-- Chỉ mục cho bảng `diachi`
--
ALTER TABLE `diachi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diachi_nd` (`nguoidung_id`);

--
-- Chỉ mục cho bảng `dondathang`
--
ALTER TABLE `dondathang`
  ADD PRIMARY KEY (`ma_ddh`),
  ADD KEY `idx_ddh_ncc` (`ma_ncc`),
  ADD KEY `idx_ddh_nv` (`ma_nv`);

--
-- Chỉ mục cho bảng `dongho`
--
ALTER TABLE `dongho`
  ADD PRIMARY KEY (`ma_dh`),
  ADD KEY `idx_dh_loai` (`ma_loai`),
  ADD KEY `idx_dh_hang` (`ma_hang`);

--
-- Chỉ mục cho bảng `hangdongho`
--
ALTER TABLE `hangdongho`
  ADD PRIMARY KEY (`ma_hang`),
  ADD UNIQUE KEY `uk_ten_hang` (`ten_hang`);

--
-- Chỉ mục cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  ADD PRIMARY KEY (`so_hoa_don`),
  ADD UNIQUE KEY `uk_hd_pd` (`ma_pd`),
  ADD KEY `idx_hd_nv` (`ma_nv`);

--
-- Chỉ mục cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD PRIMARY KEY (`ma_kh`),
  ADD UNIQUE KEY `uk_kh_cmnd` (`cmnd`),
  ADD UNIQUE KEY `uk_kh_sdt` (`so_dien_thoai`),
  ADD UNIQUE KEY `uk_kh_nd` (`nguoidung_id`);

--
-- Chỉ mục cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD PRIMARY KEY (`ma_km`),
  ADD KEY `idx_km_nv` (`ma_nv`);

--
-- Chỉ mục cho bảng `lichsugia`
--
ALTER TABLE `lichsugia`
  ADD PRIMARY KEY (`ma_dh`,`ngay_ap_dung`),
  ADD KEY `idx_lsg_nv` (`ma_nv`);

--
-- Chỉ mục cho bảng `loaidongho`
--
ALTER TABLE `loaidongho`
  ADD PRIMARY KEY (`ma_loai`),
  ADD UNIQUE KEY `uk_ten_loai` (`ten_loai`);

--
-- Chỉ mục cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nd_sdt` (`sodienthoai`),
  ADD UNIQUE KEY `uk_nd_email` (`email`);

--
-- Chỉ mục cho bảng `nhacungcap`
--
ALTER TABLE `nhacungcap`
  ADD PRIMARY KEY (`ma_ncc`);

--
-- Chỉ mục cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD PRIMARY KEY (`ma_nv`),
  ADD UNIQUE KEY `uk_nv_sdt` (`so_dien_thoai`),
  ADD UNIQUE KEY `uk_nv_email` (`email`),
  ADD UNIQUE KEY `uk_nv_nd` (`nguoidung_id`);

--
-- Chỉ mục cho bảng `phieubaohanh`
--
ALTER TABLE `phieubaohanh`
  ADD PRIMARY KEY (`so_phieu_bh`),
  ADD KEY `idx_pbh_hd` (`so_hoa_don`),
  ADD KEY `idx_pbh_dh` (`ma_dh`),
  ADD KEY `idx_pbh_nv_lap` (`ma_nv_lap`),
  ADD KEY `idx_pbh_nv_nhan` (`ma_nv_nhan`),
  ADD KEY `idx_pbh_nv_tra` (`ma_nv_giao_tra`);

--
-- Chỉ mục cho bảng `phieudat`
--
ALTER TABLE `phieudat`
  ADD PRIMARY KEY (`ma_pd`),
  ADD KEY `idx_pd_kh` (`ma_kh`),
  ADD KEY `idx_pd_nv_duyet` (`ma_nv_duyet`),
  ADD KEY `idx_pd_nv_giao` (`ma_nv_giao`);

--
-- Chỉ mục cho bảng `phieunhap`
--
ALTER TABLE `phieunhap`
  ADD PRIMARY KEY (`ma_pn`),
  ADD UNIQUE KEY `uk_pn_ddh` (`ma_ddh`),
  ADD KEY `idx_pn_nv` (`ma_nv`);

--
-- Chỉ mục cho bảng `phieutrahang`
--
ALTER TABLE `phieutrahang`
  ADD PRIMARY KEY (`ma_pth`),
  ADD KEY `idx_pth_hd` (`so_hoa_don`),
  ADD KEY `idx_pth_nv` (`ma_nv`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `diachi`
--
ALTER TABLE `diachi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `hangdongho`
--
ALTER TABLE `hangdongho`
  MODIFY `ma_hang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  MODIFY `ma_kh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `loaidongho`
--
ALTER TABLE `loaidongho`
  MODIFY `ma_loai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `phieudat`
--
ALTER TABLE `phieudat`
  MODIFY `ma_pd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `ct_dondathang`
--
ALTER TABLE `ct_dondathang`
  ADD CONSTRAINT `fk_ctddh_ddh` FOREIGN KEY (`ma_ddh`) REFERENCES `dondathang` (`ma_ddh`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctddh_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `ct_khuyenmai`
--
ALTER TABLE `ct_khuyenmai`
  ADD CONSTRAINT `fk_ctkm_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctkm_km` FOREIGN KEY (`ma_km`) REFERENCES `khuyenmai` (`ma_km`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `ct_phieudat`
--
ALTER TABLE `ct_phieudat`
  ADD CONSTRAINT `fk_ctpd_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctpd_pd` FOREIGN KEY (`ma_pd`) REFERENCES `phieudat` (`ma_pd`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `ct_phieunhap`
--
ALTER TABLE `ct_phieunhap`
  ADD CONSTRAINT `fk_ctpn_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctpn_pn` FOREIGN KEY (`ma_pn`) REFERENCES `phieunhap` (`ma_pn`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `ct_phieutrahang`
--
ALTER TABLE `ct_phieutrahang`
  ADD CONSTRAINT `fk_ctpth_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ctpth_pth` FOREIGN KEY (`ma_pth`) REFERENCES `phieutrahang` (`ma_pth`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `diachi`
--
ALTER TABLE `diachi`
  ADD CONSTRAINT `fk_diachi_nguoidung` FOREIGN KEY (`nguoidung_id`) REFERENCES `nguoidung` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `dondathang`
--
ALTER TABLE `dondathang`
  ADD CONSTRAINT `fk_ddh_ncc` FOREIGN KEY (`ma_ncc`) REFERENCES `nhacungcap` (`ma_ncc`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ddh_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `dongho`
--
ALTER TABLE `dongho`
  ADD CONSTRAINT `fk_dh_hang` FOREIGN KEY (`ma_hang`) REFERENCES `hangdongho` (`ma_hang`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dh_loai` FOREIGN KEY (`ma_loai`) REFERENCES `loaidongho` (`ma_loai`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hoadon`
--
ALTER TABLE `hoadon`
  ADD CONSTRAINT `fk_hd_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hd_pd` FOREIGN KEY (`ma_pd`) REFERENCES `phieudat` (`ma_pd`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `khachhang`
--
ALTER TABLE `khachhang`
  ADD CONSTRAINT `fk_kh_nguoidung` FOREIGN KEY (`nguoidung_id`) REFERENCES `nguoidung` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `khuyenmai`
--
ALTER TABLE `khuyenmai`
  ADD CONSTRAINT `fk_km_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lichsugia`
--
ALTER TABLE `lichsugia`
  ADD CONSTRAINT `fk_lsg_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lsg_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `nhanvien`
--
ALTER TABLE `nhanvien`
  ADD CONSTRAINT `fk_nv_nguoidung` FOREIGN KEY (`nguoidung_id`) REFERENCES `nguoidung` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieubaohanh`
--
ALTER TABLE `phieubaohanh`
  ADD CONSTRAINT `fk_pbh_dh` FOREIGN KEY (`ma_dh`) REFERENCES `dongho` (`ma_dh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pbh_hd` FOREIGN KEY (`so_hoa_don`) REFERENCES `hoadon` (`so_hoa_don`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pbh_nv_lap` FOREIGN KEY (`ma_nv_lap`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pbh_nv_nhan` FOREIGN KEY (`ma_nv_nhan`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pbh_nv_tra` FOREIGN KEY (`ma_nv_giao_tra`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieudat`
--
ALTER TABLE `phieudat`
  ADD CONSTRAINT `fk_pd_kh` FOREIGN KEY (`ma_kh`) REFERENCES `khachhang` (`ma_kh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pd_nv_duyet` FOREIGN KEY (`ma_nv_duyet`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pd_nv_giao` FOREIGN KEY (`ma_nv_giao`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieunhap`
--
ALTER TABLE `phieunhap`
  ADD CONSTRAINT `fk_pn_ddh` FOREIGN KEY (`ma_ddh`) REFERENCES `dondathang` (`ma_ddh`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pn_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phieutrahang`
--
ALTER TABLE `phieutrahang`
  ADD CONSTRAINT `fk_pth_hd` FOREIGN KEY (`so_hoa_don`) REFERENCES `hoadon` (`so_hoa_don`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pth_nv` FOREIGN KEY (`ma_nv`) REFERENCES `nhanvien` (`ma_nv`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
