<?php
/**
 * app/Models/ThongKeModel.php
 * Model tổng hợp số liệu thống kê doanh thu, tồn kho và các chỉ số KPI
 */

declare(strict_types=1);

require_once __DIR__ . '/../data/database.php';

class ThongKeModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy 4 Thẻ KPI tổng hợp và các chỉ số bổ trợ
     */
    public static function getKpiSummary(?string $startDate = null, ?string $endDate = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        // 1. Tổng doanh thu hóa đơn và số lượng hóa đơn
        $sqlHd = "SELECT COALESCE(SUM(tong_tien), 0) AS tong_doanh_thu,
                         COUNT(so_hoa_don) AS so_hoa_don
                  FROM hoadon
                  WHERE 1=1";
        $paramsHd = [];
        if (!empty($startDate)) {
            $sqlHd .= " AND DATE(ngay_lap_hd) >= :start_date";
            $paramsHd[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sqlHd .= " AND DATE(ngay_lap_hd) <= :end_date";
            $paramsHd[':end_date'] = $endDate;
        }

        $stmtHd = $db->prepare($sqlHd);
        $stmtHd->execute($paramsHd);
        $resHd = $stmtHd->fetch();

        $tongDoanhThu = (float)($resHd['tong_doanh_thu'] ?? 0);
        $soHoaDon = (int)($resHd['so_hoa_don'] ?? 0);

        // 2. Tổng giá trị hàng khách đã trả
        $sqlTra = "SELECT COALESCE(SUM(ctp.so_luong_tra * ctd.don_gia), 0) AS tong_gia_tri_tra,
                          COUNT(DISTINCT pth.ma_pth) AS so_phieu_tra
                   FROM ct_phieutrahang ctp
                   JOIN phieutrahang pth ON ctp.ma_pth = pth.ma_pth
                   JOIN hoadon hd ON pth.so_hoa_don = hd.so_hoa_don
                   JOIN ct_phieudat ctd ON hd.ma_pd = ctd.ma_pd AND ctp.ma_dh = ctd.ma_dh
                   WHERE 1=1";
        $paramsTra = [];
        if (!empty($startDate)) {
            $sqlTra .= " AND DATE(pth.ngay_tra) >= :start_date";
            $paramsTra[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sqlTra .= " AND DATE(pth.ngay_tra) <= :end_date";
            $paramsTra[':end_date'] = $endDate;
        }

        $stmtTra = $db->prepare($sqlTra);
        $stmtTra->execute($paramsTra);
        $resTra = $stmtTra->fetch();

        $tongGiaTriTra = (float)($resTra['tong_gia_tri_tra'] ?? 0);
        $soPhieuTra    = (int)($resTra['so_phieu_tra'] ?? 0);
        $doanhThuThucTe = max(0, $tongDoanhThu - $tongGiaTriTra);

        // 3. Đơn đặt hàng chờ duyệt
        $donChoDuyet = (int)$db->query("SELECT COUNT(*) FROM phieudat WHERE trang_thai = 'Chờ duyệt'")->fetchColumn();

        // 4. Cảnh báo sắp hết hàng (so_luong_ton <= 5 AND status = 1)
        $spSapHetHang = (int)$db->query("SELECT COUNT(*) FROM dongho WHERE so_luong_ton <= 5 AND status = 1")->fetchColumn();

        // 5. Đang xử lý bảo hành (ma_nv_nhan IS NOT NULL AND ma_nv_giao_tra IS NULL)
        $dangBaoHanh = (int)$db->query("SELECT COUNT(*) FROM phieubaohanh WHERE ma_nv_nhan IS NOT NULL AND ma_nv_giao_tra IS NULL")->fetchColumn();

        return [
            'tong_doanh_thu'    => $tongDoanhThu,
            'tong_gia_tri_tra'  => $tongGiaTriTra,
            'doanh_thu_thuc_te' => $doanhThuThucTe,
            'so_hoa_don'        => $soHoaDon,
            'don_cho_duyet'     => $donChoDuyet,
            'sp_sap_het_hang'   => $spSapHetHang,
            'so_phieu_tra'      => $soPhieuTra,
            'dang_bao_hanh'     => $dangBaoHanh,
        ];
    }

    /**
     * Thống kê doanh thu theo 12 tháng trong năm phục vụ Chart.js
     */
    public static function getRevenueByMonth(?int $year = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        if ($year === null || $year <= 0) {
            $year = (int)date('Y');
        }

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[$m] = [
                'thang'         => 'T' . $m,
                'doanh_thu'     => 0.0,
                'gia_tri_tra'   => 0.0,
                'thuc_thu'      => 0.0,
            ];
        }

        // Doanh thu theo hóa đơn
        $sqlHd = "SELECT MONTH(ngay_lap_hd) AS thang, SUM(tong_tien) AS doanh_thu 
                  FROM hoadon 
                  WHERE YEAR(ngay_lap_hd) = :nam 
                  GROUP BY MONTH(ngay_lap_hd)";
        $stmtHd = $db->prepare($sqlHd);
        $stmtHd->execute([':nam' => $year]);
        while ($r = $stmtHd->fetch()) {
            $m = (int)$r['thang'];
            if (isset($monthlyData[$m])) {
                $monthlyData[$m]['doanh_thu'] = (float)$r['doanh_thu'];
            }
        }

        // Giá trị trả hàng theo tháng
        $sqlTra = "SELECT MONTH(pth.ngay_tra) AS thang, 
                          SUM(ctp.so_luong_tra * ctd.don_gia) AS gia_tri_tra
                   FROM ct_phieutrahang ctp
                   JOIN phieutrahang pth ON ctp.ma_pth = pth.ma_pth
                   JOIN hoadon hd ON pth.so_hoa_don = hd.so_hoa_don
                   JOIN ct_phieudat ctd ON hd.ma_pd = ctd.ma_pd AND ctp.ma_dh = ctd.ma_dh
                   WHERE YEAR(pth.ngay_tra) = :nam
                   GROUP BY MONTH(pth.ngay_tra)";
        $stmtTra = $db->prepare($sqlTra);
        $stmtTra->execute([':nam' => $year]);
        while ($r = $stmtTra->fetch()) {
            $m = (int)$r['thang'];
            if (isset($monthlyData[$m])) {
                $monthlyData[$m]['gia_tri_tra'] = (float)$r['gia_tri_tra'];
            }
        }

        // Tính thực thu
        foreach ($monthlyData as &$md) {
            $md['thuc_thu'] = max(0, $md['doanh_thu'] - $md['gia_tri_tra']);
        }
        unset($md);

        return array_values($monthlyData);
    }

    /**
     * Thống kê cơ cấu doanh thu theo 3 loại đồng hồ Pin phục vụ Doughnut Chart
     */
    public static function getRevenueByCategory(?string $startDate = null, ?string $endDate = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT l.ma_loai, l.ten_loai,
                       COALESCE(SUM(ctd.so_luong), 0) AS so_luong_ban,
                       COALESCE(SUM(ctd.so_luong * ctd.don_gia), 0) AS tong_doanh_thu
                FROM loaidongho l
                LEFT JOIN dongho d ON l.ma_loai = d.ma_loai
                LEFT JOIN ct_phieudat ctd ON d.ma_dh = ctd.ma_dh
                LEFT JOIN hoadon hd ON ctd.ma_pd = hd.ma_pd
                WHERE 1=1";
        $params = [];

        if (!empty($startDate)) {
            $sql .= " AND (hd.ngay_lap_hd IS NULL OR DATE(hd.ngay_lap_hd) >= :start_date)";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND (hd.ngay_lap_hd IS NULL OR DATE(hd.ngay_lap_hd) <= :end_date)";
            $params[':end_date'] = $endDate;
        }

        $sql .= " GROUP BY l.ma_loai, l.ten_loai ORDER BY tong_doanh_thu DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Bảng Cảnh báo Đồng hồ sắp hết hàng (so_luong_ton <= 5)
     */
    public static function getLowStockWatches(int $threshold = 5, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, d.image, d.thoi_gian_bh,
                       h.ten_hang, l.ten_loai
                FROM dongho d
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE d.so_luong_ton <= :threshold AND d.status = 1
                ORDER BY d.so_luong_ton ASC, d.ma_dh ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':threshold' => $threshold]);
        return $stmt->fetchAll();
    }

    /**
     * Bảng Top 5 Đồng hồ Pin bán chạy nhất
     */
    public static function getTopSellingWatches(
        int $limit = 5, 
        ?string $startDate = null, 
        ?string $endDate = null, 
        ?PDO $pdo = null
    ): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT d.ma_dh, d.ten_dh, d.image, d.gia,
                       h.ten_hang, l.ten_loai,
                       SUM(ctd.so_luong) AS tong_so_luong_ban,
                       SUM(ctd.so_luong * ctd.don_gia) AS tong_doanh_thu
                FROM ct_phieudat ctd
                JOIN hoadon hd ON ctd.ma_pd = hd.ma_pd
                JOIN dongho d ON ctd.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE 1=1";
        $params = [];

        if (!empty($startDate)) {
            $sql .= " AND DATE(hd.ngay_lap_hd) >= :start_date";
            $params[':start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $sql .= " AND DATE(hd.ngay_lap_hd) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $sql .= " GROUP BY d.ma_dh, d.ten_dh, d.image, d.gia, h.ten_hang, l.ten_loai
                  ORDER BY tong_so_luong_ban DESC, tong_doanh_thu DESC
                  LIMIT " . (int)$limit;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy tổng quan nhanh Dashboard
     */
    public static function getOverviewStats(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $kpi = self::getKpiSummary(null, null, $db);
        $tongDonHang = (int)$db->query("SELECT COUNT(*) FROM phieudat")->fetchColumn();
        $tongTonKho = (int)$db->query("SELECT COALESCE(SUM(so_luong_ton), 0) FROM dongho")->fetchColumn();

        return [
            'tong_doanh_thu'  => $kpi['tong_doanh_thu'] ?? 0,
            'tong_don_hang'   => $tongDonHang,
            'tong_ton_kho'    => $tongTonKho,
            'sp_sap_het_hang' => $kpi['sp_sap_het_hang'] ?? 0,
            'don_cho_duyet'   => $kpi['don_cho_duyet'] ?? 0
        ];
    }

    /**
     * Lấy cơ cấu đơn hàng theo trạng thái
     */
    public static function getOrdersCountByStatus(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        return $db->query("
            SELECT trang_thai, COUNT(*) AS so_luong
            FROM phieudat
            GROUP BY trang_thai
            ORDER BY FIELD(trang_thai, 'Chờ duyệt', 'Đã duyệt', 'Đang giao hàng', 'Đã phân công nhân viên giao hàng', 'Hoàn tất')
        ")->fetchAll();
    }

    /**
     * Alias lấy doanh thu 12 tháng
     */
    public static function getMonthlyRevenue(?int $year = null, ?PDO $pdo = null): array {
        return self::getRevenueByMonth($year, $pdo);
    }
}

if (!class_exists('ThongKe', false)) {
    class_alias('ThongKeModel', 'ThongKe');
}
