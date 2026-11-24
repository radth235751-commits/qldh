<?php
/**
 * app/Models/PhieuTraHangModel.php
 * Model thao tác với bảng 'phieutrahang' và 'ct_phieutrahang' (Quản lý đổi trả hàng trong 7 ngày)
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/database.php';

class PhieuTraHangModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách phiếu trả hàng có tìm kiếm
     */
    public static function getAll(?string $search = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT pth.ma_pth, pth.ngay_tra, pth.so_hoa_don, pth.ma_nv,
                       hd.ngay_lap_hd, hd.ma_pd,
                       pd.ho_ten_nguoi_nhan, pd.sdt_nguoi_nhan,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt,
                       CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien_nhan,
                       COALESCE((SELECT SUM(ctp.so_luong_tra) FROM ct_phieutrahang ctp WHERE ctp.ma_pth = pth.ma_pth), 0) AS tong_so_luong_tra,
                       COALESCE((
                           SELECT SUM(ctp.so_luong_tra * ctd.don_gia) 
                           FROM ct_phieutrahang ctp 
                           JOIN hoadon hd2 ON pth.so_hoa_don = hd2.so_hoa_don 
                           JOIN ct_phieudat ctd ON hd2.ma_pd = ctd.ma_pd AND ctp.ma_dh = ctd.ma_dh 
                           WHERE ctp.ma_pth = pth.ma_pth
                       ), 0) AS tong_gia_tri_tra
                FROM phieutrahang pth
                JOIN hoadon hd ON pth.so_hoa_don = hd.so_hoa_don
                JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv ON pth.ma_nv = nv.ma_nv
                WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $s = '%' . trim($search) . '%';
            $sql .= " AND (pth.ma_pth LIKE :s1 
                       OR pth.so_hoa_don LIKE :s2 
                       OR kh.so_dien_thoai LIKE :s3 
                       OR pd.sdt_nguoi_nhan LIKE :s4 
                       OR kh.ten LIKE :s5 
                       OR pd.ho_ten_nguoi_nhan LIKE :s6)";
            $params[':s1'] = $s;
            $params[':s2'] = $s;
            $params[':s3'] = $s;
            $params[':s4'] = $s;
            $params[':s5'] = $s;
            $params[':s6'] = $s;
        }

        $sql .= " ORDER BY pth.ngay_tra DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tìm phiếu trả hàng theo mã phiếu (ma_pth)
     */
    public static function findById(string $maPth, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);

        $sql = "SELECT pth.ma_pth, pth.ngay_tra, pth.so_hoa_don, pth.ma_nv,
                       hd.ngay_lap_hd, hd.ma_pd, hd.tong_tien AS hd_tong_tien, hd.ma_so_thue,
                       pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan,
                       kh.ma_kh, kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, kh.email AS kh_email,
                       CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien_nhan,
                       COALESCE((SELECT SUM(ctp.so_luong_tra) FROM ct_phieutrahang ctp WHERE ctp.ma_pth = pth.ma_pth), 0) AS tong_so_luong_tra,
                       COALESCE((
                           SELECT SUM(ctp.so_luong_tra * ctd.don_gia) 
                           FROM ct_phieutrahang ctp 
                           JOIN hoadon hd2 ON pth.so_hoa_don = hd2.so_hoa_don 
                           JOIN ct_phieudat ctd ON hd2.ma_pd = ctd.ma_pd AND ctp.ma_dh = ctd.ma_dh 
                           WHERE ctp.ma_pth = pth.ma_pth
                       ), 0) AS tong_gia_tri_tra
                FROM phieutrahang pth
                JOIN hoadon hd ON pth.so_hoa_don = hd.so_hoa_don
                JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv ON pth.ma_nv = nv.ma_nv
                WHERE pth.ma_pth = :code";

        $stmt = $db->prepare($sql);
        $stmt->execute([':code' => trim($maPth)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy chi tiết các đồng hồ được trả trong phiếu trả hàng
     */
    public static function getDetails(string $maPth, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT ctp.ma_pth, ctp.ma_dh, ctp.so_luong_tra, ctp.ly_do,
                       d.ten_dh, d.image, d.thoi_gian_bh,
                       h.ten_hang, l.ten_loai,
                       ctd.don_gia,
                       (ctp.so_luong_tra * ctd.don_gia) AS thanh_tien
                FROM ct_phieutrahang ctp
                JOIN phieutrahang pth ON ctp.ma_pth = pth.ma_pth
                JOIN hoadon hd ON pth.so_hoa_don = hd.so_hoa_don
                JOIN ct_phieudat ctd ON hd.ma_pd = ctd.ma_pd AND ctp.ma_dh = ctd.ma_dh
                JOIN dongho d ON ctp.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE ctp.ma_pth = :code
                ORDER BY ctp.ma_dh ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':code' => trim($maPth)]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách các hóa đơn hợp lệ có thể trả hàng
     * Điều kiện: Hóa đơn được lập trong vòng 7 ngày (DATEDIFF(NOW(), ngay_lap_hd) <= 7)
     */
    public static function getReturnableInvoices(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT hd.so_hoa_don, hd.ngay_lap_hd, hd.tong_tien, hd.ma_pd,
                       DATEDIFF(NOW(), hd.ngay_lap_hd) AS so_ngay_da_mua,
                       kh.ma_kh, CONCAT(kh.ho, ' ', kh.ten) AS ten_khach_hang, kh.so_dien_thoai AS kh_sdt,
                       pd.ho_ten_nguoi_nhan, pd.sdt_nguoi_nhan
                FROM hoadon hd
                JOIN phieudat pd ON hd.ma_pd = pd.ma_pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                WHERE DATEDIFF(NOW(), hd.ngay_lap_hd) <= 7
                ORDER BY hd.ngay_lap_hd DESC";

        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách sản phẩm trong hóa đơn kèm số lượng đã mua, đã trả và số lượng còn lại được phép trả
     * Hỗ trợ nghiệp vụ trả nhiều lần trên cùng 1 Hóa đơn
     */
    public static function getInvoiceItemsForReturn(string $soHoaDon, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT ctd.ma_pd, ctd.ma_dh, ctd.so_luong AS so_luong_mua, ctd.don_gia,
                       (ctd.so_luong * ctd.don_gia) AS tong_tien_mua,
                       d.ten_dh, d.image, d.so_luong_ton,
                       h.ten_hang, l.ten_loai,
                       COALESCE((
                           SELECT SUM(ctp.so_luong_tra) 
                           FROM ct_phieutrahang ctp 
                           JOIN phieutrahang pth ON ctp.ma_pth = pth.ma_pth 
                           WHERE pth.so_hoa_don = hd.so_hoa_don AND ctp.ma_dh = ctd.ma_dh
                       ), 0) AS so_luong_da_tra
                FROM hoadon hd
                JOIN ct_phieudat ctd ON hd.ma_pd = ctd.ma_pd
                JOIN dongho d ON ctd.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE hd.so_hoa_don = :so_hd
                ORDER BY ctd.ma_dh ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':so_hd' => trim($soHoaDon)]);
        $items = $stmt->fetchAll();

        foreach ($items as &$it) {
            $it['so_luong_mua']    = (int)$it['so_luong_mua'];
            $it['so_luong_da_tra'] = (int)$it['so_luong_da_tra'];
            $it['so_luong_con_lai'] = max(0, $it['so_luong_mua'] - $it['so_luong_da_tra']);
            $it['don_gia']         = (float)$it['don_gia'];
        }
        unset($it);

        return $items;
    }

    /**
     * Sinh mã phiếu trả hàng tiếp theo nối tiếp mã lớn nhất hiện có
     * Ví dụ: PTH01 -> PTH02, PTH03... (VARCHAR(20))
     */
    public static function generateNextReturnCode(?PDO $pdo = null): string {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT ma_pth FROM phieutrahang WHERE ma_pth LIKE 'PTH%'");
        $maxNum = 0;

        while ($row = $stmt->fetch()) {
            $val = (string)$row['ma_pth'];
            if (preg_match('/^PTH(\d+)$/i', $val, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $code = 'PTH' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);

        // Kiểm tra chắc chắn không trùng
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM phieutrahang WHERE ma_pth = :code");
        $stmtCheck->execute([':code' => $code]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            $code = 'PTH' . date('ymd') . strtoupper(substr(uniqid(), -4));
        }

        return substr($code, 0, 20);
    }

    /**
     * Lập phiếu trả hàng trong Transaction
     * LƯU Ý VỀ TRIGGER:
     * 1. Trigger trg_kiem_tra_han_tra_hang sẽ chặn nếu DATEDIFF(ngay_tra, ngay_lap_hd) > 7
     * 2. Trigger trg_cong_ton_khi_tra sẽ tự động cộng so_luong_tra vào dongho.so_luong_ton
     *    -> KHÔNG viết lệnh UPDATE dongho SET so_luong_ton = ... trong PHP!
     */
    public static function createReturnOrder(
        string $soHoaDon, 
        array $returnItems, 
        string $maNv
    ): string {
        $db = Database::getConnection();

        // 1. Kiểm tra hóa đơn tồn tại và hạn 7 ngày
        $stmtHd = $db->prepare("SELECT so_hoa_don, ngay_lap_hd, DATEDIFF(NOW(), ngay_lap_hd) AS so_ngay 
                                FROM hoadon WHERE so_hoa_don = :so_hd");
        $stmtHd->execute([':so_hd' => trim($soHoaDon)]);
        $hd = $stmtHd->fetch();

        if (!$hd) {
            throw new Exception("Hóa đơn '{$soHoaDon}' không tồn tại trong hệ thống.");
        }

        if ((int)$hd['so_ngay'] > 7) {
            throw new Exception("Hóa đơn '{$soHoaDon}' đã quá thời hạn 7 ngày kể từ ngày mua hàng (" . date('d/m/Y', strtotime($hd['ngay_lap_hd'])) . "). Không thể lập phiếu trả!");
        }

        // 2. Kiểm tra số lượng hợp lệ
        $availableItems = self::getInvoiceItemsForReturn($soHoaDon, $db);
        $availableMap = [];
        foreach ($availableItems as $ai) {
            $availableMap[$ai['ma_dh']] = $ai;
        }

        $validReturns = [];
        foreach ($returnItems as $item) {
            $maDh = trim($item['ma_dh'] ?? '');
            $qty  = (int)($item['so_luong_tra'] ?? 0);
            $lyDo = trim($item['ly_do'] ?? 'Khách yêu cầu trả hàng');

            if ($qty <= 0) {
                continue;
            }

            if (!isset($availableMap[$maDh])) {
                throw new Exception("Sản phẩm mã '{$maDh}' không thuộc hóa đơn '{$soHoaDon}'.");
            }

            $maxAllowed = (int)$availableMap[$maDh]['so_luong_con_lai'];
            if ($qty > $maxAllowed) {
                throw new Exception("Số lượng trả của mẫu '{$availableMap[$maDh]['ten_dh']}' ({$qty} chiếc) vượt quá số lượng còn lại được phép trả ({$maxAllowed} chiếc).");
            }

            $validReturns[] = [
                'ma_dh'         => $maDh,
                'so_luong_tra'  => $qty,
                'ly_do'         => $lyDo
            ];
        }

        if (empty($validReturns)) {
            throw new InvalidArgumentException("Vui lòng nhập số lượng trả lớn hơn 0 cho ít nhất một mẫu đồng hồ.");
        }

        $db->beginTransaction();
        try {
            // Sinh mã phiếu trả hàng tiếp theo
            $maPth = self::generateNextReturnCode($db);

            // Chèn vào bảng phieutrahang (Trigger trg_kiem_tra_han_tra_hang sẽ kiểm tra tại đây)
            $sqlPth = "INSERT INTO phieutrahang (ma_pth, ngay_tra, so_hoa_don, ma_nv)
                       VALUES (:ma_pth, NOW(), :so_hd, :ma_nv)";
            $stmtPth = $db->prepare($sqlPth);
            $stmtPth->execute([
                ':ma_pth' => $maPth,
                ':so_hd'  => $soHoaDon,
                ':ma_nv'  => $maNv
            ]);

            // Chèn từng dòng vào ct_phieutrahang
            // Trigger trg_cong_ton_khi_tra sẽ tự động tăng tồn kho dongho.so_luong_ton
            $sqlCt = "INSERT INTO ct_phieutrahang (ma_pth, ma_dh, so_luong_tra, ly_do)
                      VALUES (:ma_pth, :ma_dh, :so_luong, :ly_do)";
            $stmtCt = $db->prepare($sqlCt);

            foreach ($validReturns as $vr) {
                $stmtCt->execute([
                    ':ma_pth'   => $maPth,
                    ':ma_dh'    => $vr['ma_dh'],
                    ':so_luong' => $vr['so_luong_tra'],
                    ':ly_do'    => $vr['ly_do']
                ]);
            }

            $db->commit();
            return $maPth;

        } catch (PDOException $e) {
            $db->rollBack();
            // Nếu lỗi từ Trigger 45000
            if ($e->getCode() == '45000' || str_contains($e->getMessage(), '45000')) {
                throw new Exception("Lỗi từ CSDL: " . ($e->errorInfo[2] ?? $e->getMessage()));
            }
            throw $e;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

if (!class_exists('PhieuTraHang', false)) {
    class_alias('PhieuTraHangModel', 'PhieuTraHang');
}
