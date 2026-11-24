<?php
/**
 * app/Models/DonDatHangModel.php
 * Thao tác với bảng 'dondathang' và 'ct_dondathang' (Đơn đặt hàng nhập từ NCC)
 */

require_once __DIR__ . '/../data/database.php';

class DonDatHangModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách toàn bộ đơn đặt hàng nhập kèm trạng thái nhập kho và nhân viên lập
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ddh.ma_ddh, ddh.ngay_dat, ddh.ma_ncc, ddh.ma_nv,
                       ncc.ten_ncc, ncc.so_dien_thoai AS sdt_ncc,
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       pn.ma_pn, pn.ngay_nhap,
                       COALESCE(SUM(ct.so_luong_dat), 0) AS tong_so_luong,
                       COALESCE(SUM(ct.so_luong_dat * ct.don_gia), 0) AS tong_tien,
                       COALESCE(SUM(ct.so_luong_dat * ct.don_gia), 0) AS tong_tien_dat
                FROM dondathang ddh
                JOIN nhacungcap ncc ON ddh.ma_ncc = ncc.ma_ncc
                LEFT JOIN nhanvien nv ON ddh.ma_nv = nv.ma_nv
                LEFT JOIN phieunhap pn ON ddh.ma_ddh = pn.ma_ddh
                LEFT JOIN ct_dondathang ct ON ddh.ma_ddh = ct.ma_ddh
                GROUP BY ddh.ma_ddh, ddh.ngay_dat, ddh.ma_ncc, ddh.ma_nv, 
                         ncc.ten_ncc, ncc.so_dien_thoai, nv.ho, nv.ten, pn.ma_pn, pn.ngay_nhap
                ORDER BY ddh.ngay_dat DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function allWithDetails(?PDO $pdo = null): array {
        return self::getAll($pdo);
    }

    /**
     * Lấy danh sách các đơn đặt hàng chưa từng lập phiếu nhập kho (dùng cho form lập phiếu nhập)
     */
    public static function getUnimportedOrders(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ddh.ma_ddh, ddh.ngay_dat, ddh.ma_ncc, ncc.ten_ncc,
                       COALESCE(SUM(ct.so_luong_dat), 0) AS tong_so_luong,
                       COALESCE(SUM(ct.so_luong_dat * ct.don_gia), 0) AS tong_tien_dat
                FROM dondathang ddh
                JOIN nhacungcap ncc ON ddh.ma_ncc = ncc.ma_ncc
                LEFT JOIN ct_dondathang ct ON ddh.ma_ddh = ct.ma_ddh
                WHERE ddh.ma_ddh NOT IN (SELECT ma_ddh FROM phieunhap)
                GROUP BY ddh.ma_ddh, ddh.ngay_dat, ddh.ma_ncc, ncc.ten_ncc
                ORDER BY ddh.ngay_dat DESC";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Tìm đơn đặt hàng theo mã
     */
    public static function findById(string $maDdh, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ddh.ma_ddh, ddh.ngay_dat, ddh.ma_ncc, ddh.ma_nv,
                       ncc.ten_ncc, ncc.dia_chi AS dia_chi_ncc, ncc.so_dien_thoai AS sdt_ncc, ncc.email AS email_ncc,
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       pn.ma_pn, pn.ngay_nhap
                FROM dondathang ddh
                JOIN nhacungcap ncc ON ddh.ma_ncc = ncc.ma_ncc
                LEFT JOIN nhanvien nv ON ddh.ma_nv = nv.ma_nv
                LEFT JOIN phieunhap pn ON ddh.ma_ddh = pn.ma_ddh
                WHERE ddh.ma_ddh = :ma_ddh";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_ddh' => $maDdh]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy chi tiết các sản phẩm trong đơn đặt hàng
     */
    public static function getDetails(string $maDdh, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ct.ma_ddh, ct.ma_dh, ct.so_luong_dat, ct.don_gia,
                       (ct.so_luong_dat * ct.don_gia) AS thanh_tien,
                       d.ten_dh, d.image, d.so_luong_ton,
                       h.ten_hang, l.ten_loai
                FROM ct_dondathang ct
                JOIN dongho d ON ct.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE ct.ma_ddh = :ma_ddh
                ORDER BY ct.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_ddh' => $maDdh]);
        return $stmt->fetchAll();
    }

    /**
     * Tự động sinh mã đơn đặt hàng tiếp theo (ví dụ: DDH01, DDH02, ...)
     */
    public static function generateNextCode(?PDO $pdo = null): string {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT ma_ddh FROM dondathang WHERE ma_ddh LIKE 'DDH%'");
        $maxNum = 0;

        while ($row = $stmt->fetch()) {
            $val = (string)$row['ma_ddh'];
            if (preg_match('/^DDH(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $code = 'DDH' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);

        // Kiểm tra chắc chắn không trùng
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM dondathang WHERE ma_ddh = :code");
        $stmtCheck->execute([':code' => $code]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $code = 'DDH' . date('ymd') . strtoupper(substr(uniqid(), -4));
        }

        return substr($code, 0, 20);
    }

    /**
     * Kiểm tra mã đơn đặt hàng đã tồn tại chưa
     */
    public static function checkIdExists(string $maDdh, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM dondathang WHERE ma_ddh = :ma_ddh");
        $stmt->execute([':ma_ddh' => trim($maDdh)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Tạo đơn đặt hàng mới kèm danh sách chi tiết sản phẩm trong Transaction
     */
    public static function createOrder(string $maDdh, string $ngayDat, string $maNcc, string $maNv, array $items, ?PDO $pdo = null): bool {
        return self::createWithItems([
            'ma_ddh'   => $maDdh,
            'ngay_dat' => $ngayDat,
            'ma_ncc'   => $maNcc
        ], $items, $maNv);
    }

    public static function createWithItems(array $poData, array $items, string $maNv): bool {
        $db = Database::getConnection();
        $maDdh   = strtoupper(trim($poData['ma_ddh']));
        $ngayDat = $poData['ngay_dat'] ?? date('Y-m-d H:i:s');
        $maNcc   = trim($poData['ma_ncc']);

        if (self::checkIdExists($maDdh, $db)) {
            throw new Exception("Mã đơn đặt hàng '{$maDdh}' đã tồn tại trong hệ thống.");
        }

        if (empty($items)) {
            throw new Exception("Đơn đặt hàng phải có ít nhất một mẫu đồng hồ.");
        }

        $db->beginTransaction();
        try {
            // 1. Thêm vào bảng dondathang
            $sqlPo = "INSERT INTO dondathang (ma_ddh, ngay_dat, ma_ncc, ma_nv) 
                      VALUES (:ma_ddh, :ngay_dat, :ma_ncc, :ma_nv)";
            $stmtPo = $db->prepare($sqlPo);
            $stmtPo->execute([
                ':ma_ddh'   => $maDdh,
                ':ngay_dat' => $ngayDat,
                ':ma_ncc'   => $maNcc,
                ':ma_nv'    => $maNv
            ]);

            // 2. Thêm vào bảng ct_dondathang
            $sqlItem = "INSERT INTO ct_dondathang (ma_ddh, ma_dh, so_luong_dat, don_gia) 
                        VALUES (:ma_ddh, :ma_dh, :so_luong_dat, :don_gia)";
            $stmtItem = $db->prepare($sqlItem);

            $seenWatches = [];
            foreach ($items as $item) {
                $maDh       = strtoupper(trim($item['ma_dh']));
                $soLuongDat = (int)$item['so_luong_dat'];
                $donGia     = (float)$item['don_gia'];

                if ($maDh === '' || $soLuongDat <= 0 || $donGia < 0) {
                    throw new Exception("Thông tin sản phẩm '{$maDh}' trong đơn đặt hàng không hợp lệ (Số lượng > 0, Đơn giá >= 0).");
                }

                if (in_array($maDh, $seenWatches, true)) {
                    throw new Exception("Mẫu đồng hồ '{$maDh}' bị chọn trùng lặp nhiều lần trong đơn đặt hàng.");
                }
                $seenWatches[] = $maDh;

                $stmtItem->execute([
                    ':ma_ddh'       => $maDdh,
                    ':ma_dh'        => $maDh,
                    ':so_luong_dat' => $soLuongDat,
                    ':don_gia'      => $donGia
                ]);
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa đơn đặt hàng (chỉ cho phép khi chưa lập phiếu nhập kho)
     */
    public static function delete(string $maDdh): bool {
        $db = Database::getConnection();

        // Kiểm tra đã lập phiếu nhập chưa
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM phieunhap WHERE ma_ddh = :ma_ddh");
        $stmtCheck->execute([':ma_ddh' => $maDdh]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            throw new Exception("Không thể xóa đơn đặt hàng '{$maDdh}' vì đã được lập phiếu nhập kho.");
        }

        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM ct_dondathang WHERE ma_ddh = :ma_ddh")->execute([':ma_ddh' => $maDdh]);
            $stmt = $db->prepare("DELETE FROM dondathang WHERE ma_ddh = :ma_ddh");
            $stmt->execute([':ma_ddh' => $maDdh]);
            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

if (!class_exists('DonDatHang', false)) {
    class_alias('DonDatHangModel', 'DonDatHang');
}
