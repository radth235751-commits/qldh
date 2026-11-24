<?php
/**
 * app/Models/PhieuNhapModel.php
 * Thao tác với bảng 'phieunhap' và 'ct_phieunhap' (Phiếu nhập kho)
 * LƯU Ý QUAN TRỌNG: Trigger 'trg_cong_ton_khi_nhap' tự động cộng tồn kho khi INSERT ct_phieunhap.
 * KHÔNG viết thêm lệnh UPDATE dongho để tránh cộng trùng số lượng tồn kho!
 */

require_once __DIR__ . '/../data/database.php';

class PhieuNhapModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách toàn bộ phiếu nhập kho kèm thông tin nhà cung cấp và nhân viên nhập
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT pn.ma_pn, pn.ngay_nhap, pn.ma_ddh, pn.ma_nv,
                       ncc.ten_ncc, ddh.ngay_dat,
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       COALESCE(SUM(ct.so_luong_nhap), 0) AS tong_so_luong,
                       COALESCE(SUM(ct.so_luong_nhap), 0) AS tong_so_luong_nhap,
                       COALESCE(SUM(ct.so_luong_nhap * ct.don_gia_nhap), 0) AS tong_tien,
                       COALESCE(SUM(ct.so_luong_nhap * ct.don_gia_nhap), 0) AS tong_tien_nhap
                FROM phieunhap pn
                JOIN dondathang ddh ON pn.ma_ddh = ddh.ma_ddh
                JOIN nhacungcap ncc ON ddh.ma_ncc = ncc.ma_ncc
                LEFT JOIN nhanvien nv ON pn.ma_nv = nv.ma_nv
                LEFT JOIN ct_phieunhap ct ON pn.ma_pn = ct.ma_pn
                GROUP BY pn.ma_pn, pn.ngay_nhap, pn.ma_ddh, pn.ma_nv, 
                         ncc.ten_ncc, ddh.ngay_dat, nv.ho, nv.ten
                ORDER BY pn.ngay_nhap DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function allWithDetails(?PDO $pdo = null): array {
        return self::getAll($pdo);
    }

    /**
     * Tìm phiếu nhập theo mã
     */
    public static function findById(string $maPn, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT pn.ma_pn, pn.ngay_nhap, pn.ma_ddh, pn.ma_nv,
                       ddh.ngay_dat, ddh.ma_ncc, ncc.ten_ncc, ncc.dia_chi AS dia_chi_ncc, ncc.so_dien_thoai AS sdt_ncc,
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       COALESCE((SELECT SUM(ct.so_luong_nhap * ct.don_gia_nhap) FROM ct_phieunhap ct WHERE ct.ma_pn = pn.ma_pn), 0) AS tong_tien,
                       COALESCE((SELECT SUM(ct.so_luong_nhap) FROM ct_phieunhap ct WHERE ct.ma_pn = pn.ma_pn), 0) AS tong_so_luong
                FROM phieunhap pn
                JOIN dondathang ddh ON pn.ma_ddh = ddh.ma_ddh
                JOIN nhacungcap ncc ON ddh.ma_ncc = ncc.ma_ncc
                LEFT JOIN nhanvien nv ON pn.ma_nv = nv.ma_nv
                WHERE pn.ma_pn = :ma_pn";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pn' => $maPn]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tự động sinh mã phiếu nhập tiếp theo (ví dụ: PN01, PN02, ...)
     */
    public static function generateNextCode(?PDO $pdo = null): string {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT ma_pn FROM phieunhap WHERE ma_pn LIKE 'PN%'");
        $maxNum = 0;

        while ($row = $stmt->fetch()) {
            $val = (string)$row['ma_pn'];
            if (preg_match('/^PN(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $code = 'PN' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);

        // Kiểm tra chắc chắn không trùng
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM phieunhap WHERE ma_pn = :code");
        $stmtCheck->execute([':code' => $code]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $code = 'PN' . date('ymd') . strtoupper(substr(uniqid(), -4));
        }

        return substr($code, 0, 20);
    }

    /**
     * Kiểm tra mã phiếu nhập đã tồn tại chưa
     */
    public static function checkIdExists(string $maPn, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM phieunhap WHERE ma_pn = :ma_pn");
        $stmt->execute([':ma_pn' => trim($maPn)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra đơn đặt hàng này đã có phiếu nhập chưa (ràng buộc 1 đơn đặt - 1 phiếu nhập duy nhất)
     */
    public static function checkDdhImported(string $maDdh, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM phieunhap WHERE ma_ddh = :ma_ddh");
        $stmt->execute([':ma_ddh' => trim($maDdh)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Lấy chi tiết các sản phẩm trong phiếu nhập kho
     */
    public static function getDetails(string $maPn, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ct.ma_pn, ct.ma_dh, ct.so_luong_nhap, ct.don_gia_nhap,
                       (ct.so_luong_nhap * ct.don_gia_nhap) AS thanh_tien,
                       (ct.so_luong_nhap * ct.don_gia_nhap) AS thanh_tien_nhap,
                       d.ten_dh, d.image, d.so_luong_ton,
                       h.ten_hang, l.ten_loai
                FROM ct_phieunhap ct
                JOIN dongho d ON ct.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE ct.ma_pn = :ma_pn
                ORDER BY ct.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pn' => $maPn]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy chi tiết các sản phẩm đã nhập kho và đối chiếu với số lượng đặt hàng
     */
    public static function getDetailsWithComparison(string $maPn, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ct_pn.ma_pn, ct_pn.ma_dh, ct_pn.so_luong_nhap, ct_pn.don_gia_nhap,
                       (ct_pn.so_luong_nhap * ct_pn.don_gia_nhap) AS thanh_tien,
                       (ct_pn.so_luong_nhap * ct_pn.don_gia_nhap) AS thanh_tien_nhap,
                       COALESCE(ct_ddh.so_luong_dat, ct_pn.so_luong_nhap) AS so_luong_dat,
                       (COALESCE(ct_ddh.so_luong_dat, ct_pn.so_luong_nhap) - ct_pn.so_luong_nhap) AS so_luong_thieu,
                       d.ten_dh, d.image, d.so_luong_ton,
                       h.ten_hang, l.ten_loai
                FROM ct_phieunhap ct_pn
                JOIN phieunhap pn ON ct_pn.ma_pn = pn.ma_pn
                JOIN dongho d ON ct_pn.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                LEFT JOIN ct_dondathang ct_ddh ON pn.ma_ddh = ct_ddh.ma_ddh AND ct_pn.ma_dh = ct_ddh.ma_dh
                WHERE ct_pn.ma_pn = :ma_pn
                ORDER BY ct_pn.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pn' => $maPn]);
        return $stmt->fetchAll();
    }

    /**
     * Lập Phiếu nhập kho từ Đơn đặt hàng
     * CHÚ Ý: Bắt buộc dùng Transaction.
     * Trigger 'trg_cong_ton_khi_nhap' sẽ tự động cộng tồn kho khi INSERT ct_phieunhap!
     */
    public static function createReceipt(
        string $maPn, 
        string $ngayNhap, 
        string $maDdh, 
        string $maNv, 
        array $items
    ): bool {
        $db = Database::getConnection();
        $maPn  = strtoupper(trim($maPn));
        $maDdh = strtoupper(trim($maDdh));

        if (self::checkIdExists($maPn, $db)) {
            throw new Exception("Mã phiếu nhập '{$maPn}' đã tồn tại trong hệ thống.");
        }

        if (self::checkDdhImported($maDdh, $db)) {
            throw new Exception("Đơn đặt hàng '{$maDdh}' đã được lập phiếu nhập kho trước đó. Quy định: Mỗi đơn đặt hàng chỉ lập 1 phiếu nhập duy nhất!");
        }

        // Lấy danh sách số lượng đặt trong ct_dondathang để kiểm tra hợp lệ
        $stmtPoItems = $db->prepare("SELECT ma_dh, so_luong_dat, don_gia FROM ct_dondathang WHERE ma_ddh = :ma_ddh");
        $stmtPoItems->execute([':ma_ddh' => $maDdh]);
        $poItemsMap = [];
        foreach ($stmtPoItems->fetchAll() as $row) {
            $poItemsMap[$row['ma_dh']] = [
                'so_luong_dat' => (int)$row['so_luong_dat'],
                'don_gia'      => (float)$row['don_gia']
            ];
        }

        if (empty($poItemsMap)) {
            throw new Exception("Đơn đặt hàng '{$maDdh}' không có sản phẩm nào hoặc không tồn tại.");
        }

        // Kiểm tra ít nhất 1 sản phẩm có số lượng nhập > 0
        $validItemsToInsert = [];
        foreach ($items as $item) {
            $maDh         = strtoupper(trim($item['ma_dh']));
            $soLuongNhap  = (int)($item['so_luong_nhap'] ?? 0);
            $donGiaNhap   = isset($item['don_gia_nhap']) ? (float)$item['don_gia_nhap'] : ($poItemsMap[$maDh]['don_gia'] ?? 0);

            if (!isset($poItemsMap[$maDh])) {
                continue; // Bỏ qua sản phẩm không thuộc đơn đặt
            }

            $maxSoLuong = $poItemsMap[$maDh]['so_luong_dat'];
            if ($soLuongNhap < 0 || $soLuongNhap > $maxSoLuong) {
                throw new Exception("Số lượng thực nhận của đồng hồ '{$maDh}' không hợp lệ (phải từ 0 đến tối đa số lượng đặt: {$maxSoLuong}).");
            }

            if ($donGiaNhap < 0) {
                throw new Exception("Đơn giá nhập của '{$maDh}' không được nhỏ hơn 0.");
            }

            // Chỉ lưu vào ct_phieunhap các mặt hàng thực tế có nhập (> 0)
            if ($soLuongNhap > 0) {
                $validItemsToInsert[] = [
                    'ma_dh'         => $maDh,
                    'so_luong_nhap' => $soLuongNhap,
                    'don_gia_nhap'  => $donGiaNhap
                ];
            }
        }

        if (empty($validItemsToInsert)) {
            throw new Exception("Phiếu nhập kho phải có ít nhất một mẫu đồng hồ với số lượng thực nhận lớn hơn 0.");
        }

        $db->beginTransaction();
        try {
            // 1. Thêm vào bảng phieunhap
            $sqlPn = "INSERT INTO phieunhap (ma_pn, ngay_nhap, ma_ddh, ma_nv) 
                      VALUES (:ma_pn, :ngay_nhap, :ma_ddh, :ma_nv)";
            $stmtPn = $db->prepare($sqlPn);
            $stmtPn->execute([
                ':ma_pn'     => $maPn,
                ':ngay_nhap' => $ngayNhap,
                ':ma_ddh'    => $maDdh,
                ':ma_nv'     => $maNv
            ]);

            // 2. Thêm vào bảng ct_phieunhap
            // BẪY TRIGGER 'trg_cong_ton_khi_nhap' trong CSDL sẽ tự động tăng dongho.so_luong_ton
            $sqlCtPn = "INSERT INTO ct_phieunhap (ma_pn, ma_dh, so_luong_nhap, don_gia_nhap) 
                        VALUES (:ma_pn, :ma_dh, :so_luong_nhap, :don_gia_nhap)";
            $stmtCtPn = $db->prepare($sqlCtPn);

            foreach ($validItemsToInsert as $val) {
                $stmtCtPn->execute([
                    ':ma_pn'         => $maPn,
                    ':ma_dh'         => $val['ma_dh'],
                    ':so_luong_nhap' => $val['so_luong_nhap'],
                    ':don_gia_nhap'  => $val['don_gia_nhap']
                ]);
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

if (!class_exists('PhieuNhap', false)) {
    class_alias('PhieuNhapModel', 'PhieuNhap');
}
