<?php
/**
 * app/Models/PhieuDatModel.php
 * Model thao tác với bảng 'phieudat' và 'ct_phieudat' (Đơn đặt hàng trực tuyến)
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/hoadon.php';
require_once __DIR__ . '/phieubaohanh.php';

class PhieuDatModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ đơn đặt hàng có bộ lọc trạng thái và tìm kiếm
     */
    public static function getAllFiltered(?string $status = null, ?string $search = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT pd.ma_pd, pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan, 
                       pd.ngay_gio_giao, pd.trang_thai, pd.ma_kh, pd.ma_nv_duyet, pd.ma_nv_giao,
                       kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, kh.email AS kh_email,
                       CONCAT(nv_d.ho, ' ', nv_d.ten) AS ten_nv_duyet,
                       CONCAT(nv_g.ho, ' ', nv_g.ten) AS ten_nv_giao,
                       hd.so_hoa_don, hd.ngay_lap_hd,
                       COALESCE((SELECT SUM(ct.so_luong * ct.don_gia) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_tien,
                       COALESCE((SELECT SUM(ct.so_luong) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_so_luong
                FROM phieudat pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv_d ON pd.ma_nv_duyet = nv_d.ma_nv
                LEFT JOIN nhanvien nv_g ON pd.ma_nv_giao = nv_g.ma_nv
                LEFT JOIN hoadon hd ON pd.ma_pd = hd.ma_pd
                WHERE 1=1";
        $params = [];

        if ($status !== null && trim($status) !== '') {
            $sql .= " AND pd.trang_thai = :status";
            $params[':status'] = trim($status);
        }

        if ($search !== null && trim($search) !== '') {
            $kw = '%' . trim($search) . '%';
            $sql .= " AND (CAST(pd.ma_pd AS CHAR) LIKE :s1 
                       OR pd.ho_ten_nguoi_nhan LIKE :s2 
                       OR pd.sdt_nguoi_nhan LIKE :s3 
                       OR kh.so_dien_thoai LIKE :s4 
                       OR kh.ten LIKE :s5 
                       OR hd.so_hoa_don LIKE :s6)";
            $params[':s1'] = $kw;
            $params[':s2'] = $kw;
            $params[':s3'] = $kw;
            $params[':s4'] = $kw;
            $params[':s5'] = $kw;
            $params[':s6'] = $kw;
        }

        $sql .= " ORDER BY pd.ngay_dat DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function allWithDetails(?PDO $pdo = null): array {
        return self::getAllFiltered(null, null, $pdo);
    }

    /**
     * Tìm phiếu đặt theo ID kèm thông tin khách hàng, nhân viên, hóa đơn
     */
    public static function findById(int $maPd, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);

        $sql = "SELECT pd.ma_pd, pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan, 
                       pd.ngay_gio_giao, pd.trang_thai, pd.ma_kh, pd.ma_nv_duyet, pd.ma_nv_giao,
                       kh.ho AS kh_ho, kh.ten AS kh_ten, kh.so_dien_thoai AS kh_sdt, kh.email AS kh_email, 
                       kh.dia_chi AS kh_diachi, kh.ma_so_thue AS kh_mst,
                       CONCAT(nv_d.ho, ' ', nv_d.ten) AS ten_nv_duyet,
                       CONCAT(nv_g.ho, ' ', nv_g.ten) AS ten_nv_giao,
                       hd.so_hoa_don, hd.ngay_lap_hd, hd.tong_tien AS hd_tong_tien,
                       COALESCE((SELECT SUM(ct.so_luong * ct.don_gia) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_tien,
                       COALESCE((SELECT SUM(ct.so_luong) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_so_luong
                FROM phieudat pd
                JOIN khachhang kh ON pd.ma_kh = kh.ma_kh
                LEFT JOIN nhanvien nv_d ON pd.ma_nv_duyet = nv_d.ma_nv
                LEFT JOIN nhanvien nv_g ON pd.ma_nv_giao = nv_g.ma_nv
                LEFT JOIN hoadon hd ON pd.ma_pd = hd.ma_pd
                WHERE pd.ma_pd = :ma_pd";

        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pd' => $maPd]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lấy chi tiết các sản phẩm trong phiếu đặt
     */
    public static function getDetails(int $maPd, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ct.ma_pd, ct.ma_dh, ct.so_luong, ct.don_gia, (ct.so_luong * ct.don_gia) AS thanh_tien,
                       d.ten_dh, d.image, d.thoi_gian_bh, d.so_luong_ton,
                       h.ten_hang, l.ten_loai
                FROM ct_phieudat ct
                JOIN dongho d ON ct.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE ct.ma_pd = :ma_pd
                ORDER BY ct.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_pd' => $maPd]);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách phiếu đặt của một khách hàng
     */
    public static function getByCustomerId(int $maKh, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT pd.ma_pd, pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.dia_chi_nguoi_nhan, pd.sdt_nguoi_nhan, 
                       pd.ngay_gio_giao, pd.trang_thai, pd.ma_nv_duyet, pd.ma_nv_giao,
                       hd.so_hoa_don, hd.ngay_lap_hd,
                       COALESCE((SELECT SUM(ct.so_luong * ct.don_gia) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_tien,
                       COALESCE((SELECT SUM(ct.so_luong) FROM ct_phieudat ct WHERE ct.ma_pd = pd.ma_pd), 0) AS tong_so_luong
                FROM phieudat pd
                LEFT JOIN hoadon hd ON pd.ma_pd = hd.ma_pd
                WHERE pd.ma_kh = :ma_kh
                ORDER BY pd.ngay_dat DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_kh' => $maKh]);
        return $stmt->fetchAll();
    }

    /**
     * Khách hàng đặt hàng trực tuyến (Transaction)
     * Kiểm tra tồn kho trước khi tạo đơn
     */
    public static function createOrder(array $orderData, array $cartItems): int {
        $db = Database::getConnection();

        if (empty($cartItems)) {
            throw new InvalidArgumentException("Giỏ hàng đang trống, không thể tạo đơn hàng.");
        }

        $db->beginTransaction();
        try {
            // 1. Kiểm tra tồn kho từng sản phẩm trong giỏ
            foreach ($cartItems as $item) {
                $maDh = $item['ma_dh'];
                $soLuongMua = (int)$item['so_luong'];

                $stmtStock = $db->prepare("SELECT ten_dh, so_luong_ton, status FROM dongho WHERE ma_dh = :ma_dh FOR UPDATE");
                $stmtStock->execute([':ma_dh' => $maDh]);
                $dh = $stmtStock->fetch();

                if (!$dh) {
                    throw new Exception("Sản phẩm mã {$maDh} không tồn tại trong hệ thống.");
                }
                if ((int)$dh['status'] !== 1) {
                    throw new Exception("Sản phẩm '{$dh['ten_dh']}' ({$maDh}) đã ngừng kinh doanh.");
                }
                if ((int)$dh['so_luong_ton'] < $soLuongMua) {
                    throw new Exception("Sản phẩm '{$dh['ten_dh']}' chỉ còn {$dh['so_luong_ton']} sản phẩm trong kho (bạn đặt {$soLuongMua}). Vui lòng cập nhật lại giỏ hàng.");
                }
            }

            // 2. Thêm mới phiếu đặt
            // Lấy họ tên khách hàng từ bảng khachhang để bảo đảm ho_ten_nguoi_nhan luôn chuẩn xác
            $stmtKhName = $db->prepare("SELECT CONCAT(ho, ' ', ten) AS ho_ten_kh FROM khachhang WHERE ma_kh = :ma_kh");
            $stmtKhName->execute([':ma_kh' => (int)$orderData['ma_kh']]);
            $khFullName = trim((string)$stmtKhName->fetchColumn());
            $hoTenNhan = !empty($khFullName) ? $khFullName : trim((string)($orderData['ho_ten_nguoi_nhan'] ?? ''));

            $sqlPd = "INSERT INTO phieudat (ngay_dat, ho_ten_nguoi_nhan, dia_chi_nguoi_nhan, sdt_nguoi_nhan, trang_thai, ma_kh)
                      VALUES (NOW(), :ho_ten, :dia_chi, :sdt, 'Chờ duyệt', :ma_kh)";
            $stmtPd = $db->prepare($sqlPd);
            $stmtPd->execute([
                ':ho_ten'  => $hoTenNhan,
                ':dia_chi' => trim($orderData['dia_chi_nguoi_nhan']),
                ':sdt'     => trim($orderData['sdt_nguoi_nhan']),
                ':ma_kh'   => (int)$orderData['ma_kh']
            ]);

            $maPd = (int)$db->lastInsertId();

            // 3. Thêm các dòng chi tiết ct_phieudat
            $sqlCt = "INSERT INTO ct_phieudat (ma_pd, ma_dh, so_luong, don_gia)
                      VALUES (:ma_pd, :ma_dh, :so_luong, :don_gia)";
            $stmtCt = $db->prepare($sqlCt);

            foreach ($cartItems as $item) {
                $stmtCt->execute([
                    ':ma_pd'    => $maPd,
                    ':ma_dh'    => $item['ma_dh'],
                    ':so_luong' => (int)$item['so_luong'],
                    ':don_gia'  => (float)$item['don_gia']
                ]);
            }

            $db->commit();
            return $maPd;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Bước 1: Nhân viên / Admin duyệt đơn hàng ('Chờ duyệt' -> 'Đã duyệt')
     */
    public static function approveOrder(int $maPd, string $maNvDuyet): bool {
        $db = Database::getConnection();

        $order = self::findById($maPd, $db);
        if (!$order) {
            throw new Exception("Không tìm thấy đơn đặt hàng #{$maPd}.");
        }
        if ($order['trang_thai'] !== 'Chờ duyệt') {
            throw new Exception("Chỉ có thể duyệt đơn hàng đang ở trạng thái 'Chờ duyệt'. Trạng thái hiện tại: {$order['trang_thai']}.");
        }

        $stmt = $db->prepare("UPDATE phieudat SET trang_thai = 'Đã duyệt', ma_nv_duyet = :ma_nv WHERE ma_pd = :ma_pd");
        return $stmt->execute([
            ':ma_nv' => $maNvDuyet,
            ':ma_pd' => $maPd
        ]);
    }

    /**
     * Bước 2: Giao hàng & Tự động xuất Hóa đơn + Phiếu bảo hành (Transaction)
     * Trừ tồn kho đồng hồ thực tế, chuyển trạng thái sang 'Đang giao hàng', ma_nv_giao = NULL
     */
    public static function shipAndGenerateInvoice(int $maPd, string $maNvThucHien): string {
        $db = Database::getConnection();

        $order = self::findById($maPd, $db);
        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng #{$maPd}.");
        }

        if (!in_array($order['trang_thai'], ['Chờ duyệt', 'Đã duyệt'], true)) {
            throw new Exception("Chỉ có thể giao hàng cho đơn ở trạng thái 'Chờ duyệt' hoặc 'Đã duyệt'. Trạng thái hiện tại: {$order['trang_thai']}.");
        }

        // Kiểm tra chắc chắn chưa xuất hóa đơn
        if (!empty($order['so_hoa_don'])) {
            throw new Exception("Đơn hàng này đã có hóa đơn ({$order['so_hoa_don']}). Không thể xuất thêm hóa đơn.");
        }

        $items = self::getDetails($maPd, $db);
        if (empty($items)) {
            throw new Exception("Đơn hàng #{$maPd} không có chi tiết sản phẩm.");
        }

        $db->beginTransaction();
        try {
            // A. Cập nhật phiếu đặt: trạng thái 'Đang giao hàng', ma_nv_giao = NULL, ngay_gio_giao = NOW()
            $sqlPd = "UPDATE phieudat 
                      SET trang_thai = 'Đang giao hàng',
                          ma_nv_giao = NULL,
                          ngay_gio_giao = NOW(),
                          ma_nv_duyet = COALESCE(ma_nv_duyet, :ma_nv_duyet)
                      WHERE ma_pd = :ma_pd";
            $stmtPd = $db->prepare($sqlPd);
            $stmtPd->execute([
                ':ma_nv_duyet' => $maNvThucHien,
                ':ma_pd'       => $maPd
            ]);

            // B. Trừ tồn kho đồng hồ
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $maDh = $item['ma_dh'];
                $soLuongCanTru = (int)$item['so_luong'];
                $totalAmount += ($soLuongCanTru * (float)$item['don_gia']);

                // Kiểm tra tồn kho trước khi trừ
                $stmtCheck = $db->prepare("SELECT so_luong_ton, ten_dh FROM dongho WHERE ma_dh = :ma_dh FOR UPDATE");
                $stmtCheck->execute([':ma_dh' => $maDh]);
                $dh = $stmtCheck->fetch();

                if (!$dh || (int)$dh['so_luong_ton'] < $soLuongCanTru) {
                    $tenDh = $dh ? $dh['ten_dh'] : $maDh;
                    $tonHienCo = $dh ? $dh['so_luong_ton'] : 0;
                    throw new Exception("Tồn kho sản phẩm '{$tenDh}' không đủ để xuất hàng (Tồn hiện tại: {$tonHienCo}, cần xuất: {$soLuongCanTru}). Vui lòng nhập hàng thêm.");
                }

                $stmtUpdateStock = $db->prepare("UPDATE dongho SET so_luong_ton = so_luong_ton - :so_luong WHERE ma_dh = :ma_dh");
                $stmtUpdateStock->execute([
                    ':so_luong' => $soLuongCanTru,
                    ':ma_dh'    => $maDh
                ]);
            }

            // C. Tự động lập Hóa đơn bán lẻ duy nhất (hoadon)
            $soHoaDon = HoaDonModel::generateNextInvoiceNumber($db);
            $maSoThue = $order['kh_mst'] ?? null;

            HoaDonModel::createInvoice($soHoaDon, $totalAmount, $maSoThue, $maPd, $maNvThucHien, $db);

            // D. Tự động sinh Phiếu bảo hành riêng cho từng chiếc đồng hồ (phieubaohanh)
            $seq = 1;
            foreach ($items as $item) {
                $maDh = $item['ma_dh'];
                $thoiGianBh = (int)($item['thoi_gian_bh'] ?? 12);
                $soLuong = (int)$item['so_luong'];

                for ($i = 1; $i <= $soLuong; $i++) {
                    $soPhieuBh = PhieuBaoHanhModel::generateWarrantyCode($seq++, $db);
                    PhieuBaoHanhModel::createWarranty($soPhieuBh, $soHoaDon, $maDh, $thoiGianBh, $maNvThucHien, $db);
                }
            }

            $db->commit();
            return $soHoaDon;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Tương thích ngược: Phân công giao hàng / Xuất hóa đơn
     */
    public static function assignDeliveryAndGenerateInvoice(
        int $maPd, 
        ?string $maNvGiao = null, 
        ?string $ngayGioGiao = null, 
        ?string $maNvThucHien = null
    ): string {
        return self::shipAndGenerateInvoice($maPd, $maNvThucHien ?? 'NV01');
    }

    /**
     * Bước 3: Xác nhận giao thành công ('Đang giao hàng' -> 'Hoàn tất')
     */
    public static function completeOrder(int $maPd): bool {
        $db = Database::getConnection();

        $order = self::findById($maPd, $db);
        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng #{$maPd}.");
        }
        if (!in_array($order['trang_thai'], ['Đang giao hàng', 'Đã phân công nhân viên giao hàng'], true)) {
            throw new Exception("Chỉ có thể hoàn tất đơn hàng đang ở trạng thái 'Đang giao hàng'. Trạng thái hiện tại: {$order['trang_thai']}.");
        }

        $stmt = $db->prepare("UPDATE phieudat SET trang_thai = 'Hoàn tất' WHERE ma_pd = :ma_pd");
        return $stmt->execute([':ma_pd' => $maPd]);
    }

    /**
     * Bước 4: Hủy đơn hàng ('Đã hủy')
     * - Khách hàng: chỉ được hủy khi đơn ở 'Chờ duyệt'
     * - Nhân viên/Admin: được hủy khi đơn ở 'Chờ duyệt' hoặc 'Đã duyệt'
     */
    public static function cancelOrder(int $maPd, ?int $maKh = null): bool {
        $db = Database::getConnection();

        $order = self::findById($maPd, $db);
        if (!$order) {
            throw new Exception("Không tìm thấy đơn hàng #{$maPd}.");
        }

        if ($maKh !== null) {
            // Khách hàng tự hủy
            if ((int)$order['ma_kh'] !== $maKh) {
                throw new Exception("Bạn không có quyền thao tác trên đơn hàng này.");
            }
            if ($order['trang_thai'] !== 'Chờ duyệt') {
                throw new Exception("Bạn chỉ có thể hủy đơn hàng khi đơn đang ở trạng thái 'Chờ duyệt'. Vui lòng liên hệ nhân viên cửa hàng để được hỗ trợ.");
            }
        } else {
            // Nhân viên / Admin hủy
            if (!in_array($order['trang_thai'], ['Chờ duyệt', 'Đã duyệt'], true)) {
                throw new Exception("Không thể hủy đơn hàng đã phân công giao hoặc đã hoàn tất.");
            }
        }

        $stmt = $db->prepare("UPDATE phieudat SET trang_thai = 'Đã hủy' WHERE ma_pd = :ma_pd");
        return $stmt->execute([':ma_pd' => $maPd]);
    }
}

if (!class_exists('PhieuDat', false)) {
    class_alias('PhieuDatModel', 'PhieuDat');
}
