<?php
/**
 * app/Models/PhieuBaoHanhModel.php
 * Model thao tác với bảng 'phieubaohanh' (Phiếu bảo hành đồng hồ bán lẻ & Nghiệp vụ Hậu mãi)
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/database.php';

class PhieuBaoHanhModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ phiếu bảo hành có bộ lọc tìm kiếm, thời hạn và tiến độ bảo hành
     */
    public static function getAllFiltered(
        ?string $search = null,
        ?string $progress = null,
        mixed $validityOrLimit = null,
        mixed $pdoOrOffset = null,
        ?PDO $maybePdo = null
    ): array {
        // Hỗ trợ linh hoạt cả 2 dạng lời gọi tham số:
        // Dạng 1: getAllFiltered($search, $progress, $validity, $pdo)
        // Dạng 2: getAllFiltered($search, $progress, $limit, $offset, $pdo)
        $validity = is_string($validityOrLimit) ? $validityOrLimit : null;
        $pdo = ($pdoOrOffset instanceof PDO) ? $pdoOrOffset : (($maybePdo instanceof PDO) ? $maybePdo : null);
        $db = self::getPdo($pdo);

        $sql = "SELECT pbh.so_phieu_bh, pbh.ngay_bat_dau, pbh.ngay_ket_thuc, 
                       pbh.trang_thai_truoc_bh, pbh.trang_thai_sau_bh, 
                       pbh.so_hoa_don, pbh.ma_dh, pbh.ma_nv_lap, pbh.ma_nv_nhan, pbh.ma_nv_giao_tra,
                       d.ten_dh, d.image, d.thoi_gian_bh,
                       h.ten_hang, l.ten_loai,
                       hd.ngay_lap_hd, hd.ma_pd,
                       pd.ho_ten_nguoi_nhan, pd.sdt_nguoi_nhan,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt,
                       CONCAT(nv_lap.ho, ' ', nv_lap.ten) AS ten_nv_lap,
                       CONCAT(nv_nhan.ho, ' ', nv_nhan.ten) AS ten_nv_nhan,
                       CONCAT(nv_tra.ho, ' ', nv_tra.ten) AS ten_nv_giao_tra,
                       CASE 
                           WHEN CURDATE() <= pbh.ngay_ket_thuc THEN 1 
                           ELSE 0 
                       END AS con_han_bh,
                       CASE 
                           WHEN pbh.ma_nv_nhan IS NULL THEN 'Chưa bảo hành'
                           WHEN pbh.ma_nv_nhan IS NOT NULL AND pbh.ma_nv_giao_tra IS NULL THEN 'Đang bảo hành'
                           ELSE 'Đã trả khách'
                       END AS tien_do_bh
                FROM phieubaohanh pbh
                JOIN dongho d ON pbh.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hoadon hd ON pbh.so_hoa_don = hd.so_hoa_don
                JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv_lap ON pbh.ma_nv_lap = nv_lap.ma_nv
                LEFT JOIN nhanvien nv_nhan ON pbh.ma_nv_nhan = nv_nhan.ma_nv
                LEFT JOIN nhanvien nv_tra ON pbh.ma_nv_giao_tra = nv_tra.ma_nv
                WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $s = '%' . trim($search) . '%';
            $sql .= " AND (pbh.so_phieu_bh LIKE :s1 
                       OR pbh.so_hoa_don LIKE :s2 
                       OR kh.so_dien_thoai LIKE :s3 
                       OR pd.sdt_nguoi_nhan LIKE :s4 
                       OR kh.ten LIKE :s5 
                       OR d.ten_dh LIKE :s6 
                       OR d.ma_dh LIKE :s7)";
            $params[':s1'] = $s;
            $params[':s2'] = $s;
            $params[':s3'] = $s;
            $params[':s4'] = $s;
            $params[':s5'] = $s;
            $params[':s6'] = $s;
            $params[':s7'] = $s;
        }

        if ($validity === 'valid') {
            $sql .= " AND CURDATE() <= pbh.ngay_ket_thuc";
        } elseif ($validity === 'expired') {
            $sql .= " AND CURDATE() > pbh.ngay_ket_thuc";
        }

        if ($progress === 'not_received' || $progress === 'chua_bh') {
            $sql .= " AND pbh.ma_nv_nhan IS NULL";
        } elseif ($progress === 'in_progress' || $progress === 'dang_bh') {
            $sql .= " AND pbh.ma_nv_nhan IS NOT NULL AND pbh.ma_nv_giao_tra IS NULL";
        } elseif ($progress === 'returned' || $progress === 'da_tra') {
            $sql .= " AND pbh.ma_nv_giao_tra IS NOT NULL";
        }

        $sql .= " ORDER BY pbh.ngay_bat_dau DESC, pbh.so_phieu_bh DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tra cứu chi tiết phiếu bảo hành theo mã số phiếu
     */
    public static function findByCode(string $soPhieuBh, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);

        $sql = "SELECT pbh.so_phieu_bh, pbh.ngay_bat_dau, pbh.ngay_ket_thuc, 
                       pbh.trang_thai_truoc_bh, pbh.trang_thai_sau_bh, 
                       pbh.so_hoa_don, pbh.ma_dh, pbh.ma_nv_lap, pbh.ma_nv_nhan, pbh.ma_nv_giao_tra,
                       d.ten_dh, d.image, d.thoi_gian_bh, d.gia,
                       h.ten_hang, l.ten_loai,
                       hd.ngay_lap_hd, hd.ma_pd, hd.tong_tien AS hd_tong_tien,
                       pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, kh.email AS kh_email,
                       CONCAT(nv_lap.ho, ' ', nv_lap.ten) AS ten_nv_lap,
                       CONCAT(nv_nhan.ho, ' ', nv_nhan.ten) AS ten_nv_nhan,
                       CONCAT(nv_tra.ho, ' ', nv_tra.ten) AS ten_nv_giao_tra,
                       CASE 
                           WHEN CURDATE() <= pbh.ngay_ket_thuc THEN 1 
                           ELSE 0 
                       END AS con_han_bh,
                       CASE 
                           WHEN pbh.ma_nv_nhan IS NULL THEN 'Chưa bảo hành'
                           WHEN pbh.ma_nv_nhan IS NOT NULL AND pbh.ma_nv_giao_tra IS NULL THEN 'Đang bảo hành'
                           ELSE 'Đã trả khách'
                       END AS tien_do_bh
                FROM phieubaohanh pbh
                JOIN dongho d ON pbh.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hoadon hd ON pbh.so_hoa_don = hd.so_hoa_don
                JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv_lap ON pbh.ma_nv_lap = nv_lap.ma_nv
                LEFT JOIN nhanvien nv_nhan ON pbh.ma_nv_nhan = nv_nhan.ma_nv
                LEFT JOIN nhanvien nv_tra ON pbh.ma_nv_giao_tra = nv_tra.ma_nv
                WHERE pbh.so_phieu_bh = :code";

        $stmt = $db->prepare($sql);
        $stmt->execute([':code' => trim($soPhieuBh)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy danh sách phiếu bảo hành theo số hóa đơn
     */
    public static function getBySoHoaDon(string $soHoaDon, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT pbh.so_phieu_bh, pbh.ngay_bat_dau, pbh.ngay_ket_thuc, 
                       pbh.trang_thai_truoc_bh, pbh.trang_thai_sau_bh, 
                       pbh.so_hoa_don, pbh.ma_dh, pbh.ma_nv_lap, pbh.ma_nv_nhan, pbh.ma_nv_giao_tra,
                       d.ten_dh, d.image, d.thoi_gian_bh,
                       h.ten_hang, l.ten_loai,
                       CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien_lap,
                       CASE WHEN CURDATE() <= pbh.ngay_ket_thuc THEN 1 ELSE 0 END AS con_han_bh
                FROM phieubaohanh pbh
                JOIN dongho d ON pbh.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                LEFT JOIN nhanvien nv ON pbh.ma_nv_lap = nv.ma_nv
                WHERE pbh.so_hoa_don = :so_hd
                ORDER BY pbh.ma_dh ASC, pbh.so_phieu_bh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':so_hd' => $soHoaDon]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách phiếu bảo hành theo mã phiếu đặt
     */
    public static function getByMaPd(int $maPd, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT pbh.so_phieu_bh, pbh.ngay_bat_dau, pbh.ngay_ket_thuc, 
                       pbh.so_hoa_don, pbh.ma_dh,
                       d.ten_dh, d.image, d.thoi_gian_bh,
                       h.ten_hang, l.ten_loai,
                       CASE WHEN CURDATE() <= pbh.ngay_ket_thuc THEN 1 ELSE 0 END AS con_han_bh
                FROM phieubaohanh pbh
                JOIN hoadon hd ON pbh.so_hoa_don = hd.so_hoa_don
                JOIN dongho d ON pbh.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE hd.ma_pd = :ma_pd
                ORDER BY pbh.ma_dh ASC, pbh.so_phieu_bh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pd' => $maPd]);
        return $stmt->fetchAll();
    }

    /**
     * Bước 1: Tiếp nhận đồng hồ vào quy trình bảo hành
     * Kiểm tra điều kiện: CURDATE() <= ngay_ket_thuc
     */
    public static function receiveWarranty(string $soPhieuBh, string $trangThaiTruoc, string $maNvNhan): bool {
        $db = Database::getConnection();

        $pbh = self::findByCode($soPhieuBh, $db);
        if (!$pbh) {
            throw new Exception("Không tìm thấy phiếu bảo hành mã '{$soPhieuBh}'.");
        }

        // Kiểm tra thời hạn bảo hành
        $today = date('Y-m-d');
        if ($today > $pbh['ngay_ket_thuc']) {
            $han = date('d/m/Y', strtotime($pbh['ngay_ket_thuc']));
            throw new Exception("Phiếu bảo hành '{$soPhieuBh}' đã hết hạn vào ngày {$han}. Không thể tiếp nhận bảo hành miễn phí!");
        }

        $trangThaiTruocTrim = trim($trangThaiTruoc);
        if ($trangThaiTruocTrim === '') {
            throw new InvalidArgumentException("Vui lòng nhập mô tả tình trạng lỗi của đồng hồ trước khi bảo hành.");
        }

        $sql = "UPDATE phieubaohanh 
                SET trang_thai_truoc_bh = :truoc, 
                    ma_nv_nhan = :ma_nv 
                WHERE so_phieu_bh = :code";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':truoc' => $trangThaiTruocTrim,
            ':ma_nv' => $maNvNhan,
            ':code'  => $soPhieuBh
        ]);
    }

    /**
     * Bước 2: Hoàn tất sửa chữa & Giao trả đồng hồ cho khách
     * Điều kiện: Phiếu đã được tiếp nhận (ma_nv_nhan IS NOT NULL)
     */
    public static function completeAndReturnWarranty(string $soPhieuBh, string $trangThaiSau, string $maNvGiaoTra): bool {
        $db = Database::getConnection();

        $pbh = self::findByCode($soPhieuBh, $db);
        if (!$pbh) {
            throw new Exception("Không tìm thấy phiếu bảo hành mã '{$soPhieuBh}'.");
        }

        if (empty($pbh['ma_nv_nhan'])) {
            throw new Exception("Đồng hồ chưa được tiếp nhận vào hệ thống bảo hành. Vui lòng thực hiện Bước 1 trước!");
        }

        $trangThaiSauTrim = trim($trangThaiSau);
        if ($trangThaiSauTrim === '') {
            throw new InvalidArgumentException("Vui lòng nhập mô tả kết quả xử lý và tình trạng đồng hồ sau bảo hành.");
        }

        $sql = "UPDATE phieubaohanh 
                SET trang_thai_sau_bh = :sau, 
                    ma_nv_giao_tra = :ma_nv 
                WHERE so_phieu_bh = :code";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':sau'   => $trangThaiSauTrim,
            ':ma_nv' => $maNvGiaoTra,
            ':code'  => $soPhieuBh
        ]);
    }

    /**
     * Sinh mã phiếu bảo hành tiếp theo nối tiếp mã lớn nhất hiện có
     * Ví dụ: BH01, BH02, BH03 -> sinh BH04, BH05... (VARCHAR(20))
     */
    public static function generateWarrantyCode(int $offset = 1, ?PDO $pdo = null): string {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT so_phieu_bh FROM phieubaohanh WHERE so_phieu_bh LIKE 'BH%'");
        $maxNum = 0;

        while ($row = $stmt->fetch()) {
            $val = (string)$row['so_phieu_bh'];
            if (preg_match('/^BH(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + $offset;
        $code = 'BH' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);

        // Kiểm tra chắc chắn không trùng
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM phieubaohanh WHERE so_phieu_bh = :code");
        $stmtCheck->execute([':code' => $code]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $code = 'BH' . date('ymd') . strtoupper(substr(uniqid(), -4));
        }

        return substr($code, 0, 20);
    }

    /**
     * Tạo một phiếu bảo hành cho một chiếc đồng hồ
     */
    public static function createWarranty(
        string $soPhieuBh, 
        string $soHoaDon, 
        string $maDh, 
        int $thoiGianBhMonths, 
        string $maNvLap, 
        ?PDO $pdo = null
    ): bool {
        $db = self::getPdo($pdo);

        if ($thoiGianBhMonths <= 0) {
            $thoiGianBhMonths = 12; // Mặc định 12 tháng nếu chưa thiết lập
        }

        $sql = "INSERT INTO phieubaohanh (so_phieu_bh, ngay_bat_dau, ngay_ket_thuc, so_hoa_don, ma_dh, ma_nv_lap)
                VALUES (:so_pbh, CURDATE(), DATE_ADD(CURDATE(), INTERVAL :months MONTH), :so_hd, :ma_dh, :ma_nv)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':so_pbh'  => $soPhieuBh,
            ':months'  => $thoiGianBhMonths,
            ':so_hd'   => $soHoaDon,
            ':ma_dh'   => $maDh,
            ':ma_nv'   => $maNvLap
        ]);
    }
}

if (!class_exists('PhieuBaoHanh', false)) {
    class_alias('PhieuBaoHanhModel', 'PhieuBaoHanh');
}
