<?php
/**
 * app/Models/HoaDonModel.php
 * Model thao tác với bảng 'hoadon' (Hóa đơn bán hàng lẻ)
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/database.php';

class HoaDonModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ danh sách hóa đơn có tìm kiếm
     */
    public static function getAll(?string $search = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT hd.so_hoa_don, hd.ngay_lap_hd, hd.tong_tien, hd.ma_so_thue, hd.ma_pd, hd.ma_nv,
                       pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, kh.email AS kh_email,
                       COALESCE(NULLIF(TRIM(pd.ho_ten_nguoi_nhan), ''), NULLIF(TRIM(CONCAT(COALESCE(kh.ho, ''), ' ', COALESCE(kh.ten, ''))), ''), 'Khách hàng') AS ten_khach_hang,
                       COALESCE(NULLIF(TRIM(pd.ho_ten_nguoi_nhan), ''), NULLIF(TRIM(CONCAT(COALESCE(kh.ho, ''), ' ', COALESCE(kh.ten, ''))), ''), 'Khách hàng') AS ho_ten_kh,
                       COALESCE(NULLIF(TRIM(pd.sdt_nguoi_nhan), ''), kh.so_dien_thoai, '') AS sdt_khach_hang,
                       COALESCE(NULLIF(TRIM(pd.sdt_nguoi_nhan), ''), kh.so_dien_thoai, '') AS sdt_kh,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhan_vien,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhanvien,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhanvien_lap,
                       (SELECT COUNT(*) FROM phieubaohanh pbh WHERE pbh.so_hoa_don = hd.so_hoa_don) AS so_luong_bh
                FROM hoadon hd
                LEFT JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                LEFT JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv ON hd.ma_nv = nv.ma_nv
                LEFT JOIN nguoidung nd ON nv.nguoidung_id = nd.id
                WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $kw = '%' . trim($search) . '%';
            $sql .= " AND (hd.so_hoa_don LIKE :s1 
                       OR pd.ho_ten_nguoi_nhan LIKE :s2 
                       OR pd.sdt_nguoi_nhan LIKE :s3 
                       OR kh.so_dien_thoai LIKE :s4 
                       OR kh.ten LIKE :s5 
                       OR hd.ma_so_thue LIKE :s6 
                       OR CAST(hd.ma_pd AS CHAR) LIKE :s7
                       OR nd.hoten LIKE :s8
                       OR nv.ten LIKE :s9)";
            $params[':s1'] = $kw;
            $params[':s2'] = $kw;
            $params[':s3'] = $kw;
            $params[':s4'] = $kw;
            $params[':s5'] = $kw;
            $params[':s6'] = $kw;
            $params[':s7'] = $kw;
            $params[':s8'] = $kw;
            $params[':s9'] = $kw;
        }

        $sql .= " ORDER BY hd.ngay_lap_hd DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(?PDO $pdo = null): array {
        return self::getAll(null, $pdo);
    }

    /**
     * Tìm hóa đơn theo số hóa đơn
     */
    public static function findBySoHoaDon(string $soHoaDon, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT hd.so_hoa_don, hd.ngay_lap_hd, hd.tong_tien, hd.ma_so_thue, hd.ma_pd, hd.ma_nv,
                       pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan, pd.ngay_gio_giao,
                       pd.trang_thai AS trang_thai_pd,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, 
                       kh.email AS kh_email, kh.dia_chi AS kh_diachi, kh.ma_so_thue AS kh_mst,
                       COALESCE(NULLIF(TRIM(pd.ho_ten_nguoi_nhan), ''), NULLIF(TRIM(CONCAT(COALESCE(kh.ho, ''), ' ', COALESCE(kh.ten, ''))), ''), 'Khách hàng') AS ten_khach_hang,
                       COALESCE(NULLIF(TRIM(pd.ho_ten_nguoi_nhan), ''), NULLIF(TRIM(CONCAT(COALESCE(kh.ho, ''), ' ', COALESCE(kh.ten, ''))), ''), 'Khách hàng') AS ho_ten_kh,
                       COALESCE(NULLIF(TRIM(pd.sdt_nguoi_nhan), ''), kh.so_dien_thoai, '') AS sdt_khach_hang,
                       COALESCE(NULLIF(TRIM(pd.sdt_nguoi_nhan), ''), kh.so_dien_thoai, '') AS sdt_kh,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhan_vien,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhanvien,
                       COALESCE(NULLIF(TRIM(nd.hoten), ''), NULLIF(TRIM(CONCAT(COALESCE(nv.ho, ''), ' ', COALESCE(nv.ten, ''))), ''), hd.ma_nv, 'Hệ thống') AS ten_nhanvien_lap,
                       CONCAT(nv_giao.ho, ' ', nv_giao.ten) AS ten_nhanvien_giao
                FROM hoadon hd
                LEFT JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                LEFT JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv ON hd.ma_nv = nv.ma_nv
                LEFT JOIN nguoidung nd ON nv.nguoidung_id = nd.id
                LEFT JOIN nhanvien nv_giao ON pd.ma_nv_giao = nv_giao.ma_nv
                WHERE hd.so_hoa_don = :so_hd";
        $stmt = $db->prepare($sql);
        $stmt->execute([':so_hd' => $soHoaDon]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByCode(string $soHoaDon, ?PDO $pdo = null): ?array {
        return self::findBySoHoaDon($soHoaDon, $pdo);
    }

    /**
     * Tìm hóa đơn theo mã phiếu đặt
     */
    public static function findByMaPd(int $maPd, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT hd.so_hoa_don, hd.ngay_lap_hd, hd.tong_tien, hd.ma_so_thue, hd.ma_pd, hd.ma_nv,
                       CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien_lap
                FROM hoadon hd
                LEFT JOIN nhanvien nv ON hd.ma_nv = nv.ma_nv
                WHERE hd.ma_pd = :ma_pd";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pd' => $maPd]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy danh sách sản phẩm trong hóa đơn kèm danh sách mã phiếu bảo hành tương ứng
     */
    public static function getInvoiceItemsWithWarranties(string $soHoaDon, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        
        // 1. Lấy mã phiếu đặt từ hóa đơn
        $stmtHd = $db->prepare("SELECT ma_pd FROM hoadon WHERE so_hoa_don = :so_hd");
        $stmtHd->execute([':so_hd' => $soHoaDon]);
        $maPd = $stmtHd->fetchColumn();

        if (!$maPd) {
            return [];
        }

        // 2. Lấy các sản phẩm trong ct_phieudat
        $sqlItems = "SELECT ct.ma_pd, ct.ma_dh, ct.so_luong, ct.don_gia, (ct.so_luong * ct.don_gia) AS thanh_tien,
                            d.ten_dh, d.image, d.thoi_gian_bh,
                            h.ten_hang, l.ten_loai
                     FROM ct_phieudat ct
                     JOIN dongho d ON ct.ma_dh = d.ma_dh
                     JOIN hangdongho h ON d.ma_hang = h.ma_hang
                     JOIN loaidongho l ON d.ma_loai = l.ma_loai
                     WHERE ct.ma_pd = :ma_pd";
        $stmtItems = $db->prepare($sqlItems);
        $stmtItems->execute([':ma_pd' => $maPd]);
        $items = $stmtItems->fetchAll();

        // 3. Lấy toàn bộ phiếu bảo hành thuộc hóa đơn này
        $sqlBh = "SELECT so_phieu_bh, ngay_bat_dau, ngay_ket_thuc, ma_dh 
                  FROM phieubaohanh 
                  WHERE so_hoa_don = :so_hd 
                  ORDER BY ma_dh ASC, so_phieu_bh ASC";
        $stmtBh = $db->prepare($sqlBh);
        $stmtBh->execute([':so_hd' => $soHoaDon]);
        $allBh = $stmtBh->fetchAll();

        // Gom nhóm phiếu bảo hành theo ma_dh
        $bhByDh = [];
        foreach ($allBh as $bh) {
            $bhByDh[$bh['ma_dh']][] = $bh;
        }

        foreach ($items as &$item) {
            $item['danh_sach_bh'] = $bhByDh[$item['ma_dh']] ?? [];
        }
        unset($item);

        return $items;
    }

    /**
     * Sinh số hóa đơn tiếp theo duy nhất nối tiếp mã lớn nhất hiện có
     * Ví dụ: HD01, HD02 -> sinh HD03, HD04... (VARCHAR(20))
     */
    public static function generateNextInvoiceNumber(?PDO $pdo = null): string {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT so_hoa_don FROM hoadon WHERE so_hoa_don LIKE 'HD%'");
        $maxNum = 0;

        while ($row = $stmt->fetch()) {
            $val = (string)$row['so_hoa_don'];
            if (preg_match('/^HD(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $newCode = 'HD' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);

        // Đảm bảo không trùng lặp
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM hoadon WHERE so_hoa_don = :code");
        $stmtCheck->execute([':code' => $newCode]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $newCode = 'HD' . date('ymd') . strtoupper(substr(uniqid(), -4));
        }

        return substr($newCode, 0, 20);
    }

    /**
     * Tạo hóa đơn mới
     */
    public static function createInvoice(
        string $soHoaDon, 
        float $tongTien, 
        ?string $maSoThue, 
        int $maPd, 
        string $maNv, 
        ?PDO $pdo = null
    ): bool {
        $db = self::getPdo($pdo);
        $sql = "INSERT INTO hoadon (so_hoa_don, ngay_lap_hd, tong_tien, ma_so_thue, ma_pd, ma_nv)
                VALUES (:so_hd, NOW(), :tong_tien, :mst, :ma_pd, :ma_nv)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':so_hd'     => $soHoaDon,
            ':tong_tien' => $tongTien,
            ':mst'       => $maSoThue ?: null,
            ':ma_pd'     => $maPd,
            ':ma_nv'     => $maNv
        ]);
    }
}

if (!class_exists('HoaDon', false)) {
    class_alias('HoaDonModel', 'HoaDon');
}
