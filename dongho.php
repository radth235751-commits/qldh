<?php
/**
 * model/dongho.php
 * Thao tác với bảng 'dongho' (Sản phẩm Đồng hồ Pin) & liên kết 'lichsugia'
 */

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/lichsugia.php';

class DongHoModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách đồng hồ có bộ lọc (tìm kiếm mã/tên, lọc theo loại, hãng, trạng thái)
     */
    public static function getAllFiltered(
        ?string $search = null, 
        ?int $maLoai = null, 
        ?int $maHang = null, 
        ?int $status = null, 
        ?PDO $pdo = null
    ): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, d.mo_ta, d.status, d.image, d.thoi_gian_bh, 
                       d.ma_loai, d.ma_hang, l.ten_loai, h.ten_hang,
                       (SELECT COUNT(*) FROM lichsugia ls WHERE ls.ma_dh = d.ma_dh) AS so_lan_doi_gia
                FROM dongho d
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $kw = '%' . trim($search) . '%';
            $sql .= " AND (d.ma_dh LIKE :search_ma OR d.ten_dh LIKE :search_ten)";
            $params[':search_ma']  = $kw;
            $params[':search_ten'] = $kw;
        }

        if ($maLoai !== null && $maLoai > 0) {
            $sql .= " AND d.ma_loai = :ma_loai";
            $params[':ma_loai'] = $maLoai;
        }

        if ($maHang !== null && $maHang > 0) {
            $sql .= " AND d.ma_hang = :ma_hang";
            $params[':ma_hang'] = $maHang;
        }

        if ($status !== null && in_array($status, [0, 1], true)) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY d.ma_dh ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Tìm đồng hồ theo mã sản phẩm (ma_dh)
     */
    public static function findById(string $maDh, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, d.mo_ta, d.status, d.image, d.thoi_gian_bh, 
                       d.ma_loai, d.ma_hang, l.ten_loai, h.ten_hang
                FROM dongho d
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                WHERE d.ma_dh = :ma_dh";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_dh' => $maDh]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra mã đồng hồ đã tồn tại chưa
     */
    public static function checkIdExists(string $maDh, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM dongho WHERE ma_dh = :ma_dh");
        $stmt->execute([':ma_dh' => trim($maDh)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Thêm mới đồng hồ và tự động ghi nhận mức giá khởi điểm vào 'lichsugia'
     * Thực thi trong 1 Transaction
     */
    public static function createWithInitialPrice(array $data, string $maNv): bool {
        return self::createWithInitialPriceImpl($data, $maNv);
    }

    public static function create(array $data, string $maNv): bool {
        return self::createWithInitialPriceImpl($data, $maNv);
    }

    private static function createWithInitialPriceImpl(array $data, string $maNv): bool {
        $db = Database::getConnection();

        $maDh       = strtoupper(trim($data['ma_dh']));
        $tenDh      = trim($data['ten_dh']);
        $gia        = (float)$data['gia'];
        $soLuongTon = (int)($data['so_luong_ton'] ?? 0);
        $moTa       = $data['mo_ta'] ?? null;
        $status     = (int)($data['status'] ?? 1);
        $image      = !empty($data['image']) ? trim($data['image']) : null;
        $thoiGianBh = (int)($data['thoi_gian_bh'] ?? 12);
        $maLoai     = (int)$data['ma_loai'];
        $maHang     = (int)$data['ma_hang'];

        if (self::checkIdExists($maDh, $db)) {
            throw new Exception("Mã đồng hồ '{$maDh}' đã tồn tại trong hệ thống.");
        }

        $db->beginTransaction();
        try {
            // 1. Thêm vào bảng dongho
            $sqlDongHo = "INSERT INTO dongho (ma_dh, ten_dh, gia, so_luong_ton, mo_ta, status, image, thoi_gian_bh, ma_loai, ma_hang) 
                          VALUES (:ma_dh, :ten_dh, :gia, :so_luong_ton, :mo_ta, :status, :image, :thoi_gian_bh, :ma_loai, :ma_hang)";
            $stmt1 = $db->prepare($sqlDongHo);
            $stmt1->execute([
                ':ma_dh'        => $maDh,
                ':ten_dh'       => $tenDh,
                ':gia'          => $gia,
                ':so_luong_ton' => $soLuongTon,
                ':mo_ta'        => $moTa,
                ':status'       => $status,
                ':image'        => $image,
                ':thoi_gian_bh' => $thoiGianBh,
                ':ma_loai'      => $maLoai,
                ':ma_hang'      => $maHang
            ]);

            // 2. Ghi nhận giá khởi điểm vào bảng lichsugia
            $sqlGia = "INSERT INTO lichsugia (ma_dh, ngay_ap_dung, gia_moi, ma_nv) 
                       VALUES (:ma_dh, NOW(), :gia_moi, :ma_nv)";
            $stmt2 = $db->prepare($sqlGia);
            $stmt2->execute([
                ':ma_dh'   => $maDh,
                ':gia_moi' => $gia,
                ':ma_nv'   => $maNv
            ]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật thông tin đồng hồ
     * Nếu giá mới khác giá cũ trong CSDL: Chạy Transaction cập nhật dongho và thêm bản ghi vào lichsugia
     */
    public static function update(string $maDh, array $data, string $maNv): bool {
        return self::updateWithPriceCheck($maDh, $data, $maNv);
    }

    public static function updateWithPriceCheck(string $maDh, array $data, string $maNv): bool {
        $db = Database::getConnection();

        $current = self::findById($maDh, $db);
        if (!$current) {
            throw new Exception("Không tìm thấy sản phẩm có mã '{$maDh}'.");
        }

        $tenDh      = trim($data['ten_dh']);
        $newPrice   = (float)$data['gia'];
        $oldPrice   = (float)$current['gia'];
        $soLuongTon = (int)$data['so_luong_ton'];
        $moTa       = $data['mo_ta'] ?? null;
        $status     = (int)$data['status'];
        $image      = !empty($data['image']) ? trim($data['image']) : $current['image'];
        $thoiGianBh = (int)($data['thoi_gian_bh'] ?? 12);
        $maLoai     = (int)$data['ma_loai'];
        $maHang     = (int)$data['ma_hang'];

        // Kiểm tra biến động giá
        $priceChanged = (abs($newPrice - $oldPrice) > 0.001);

        $db->beginTransaction();
        try {
            // Cập nhật bảng dongho
            $sql = "UPDATE dongho 
                    SET ten_dh = :ten_dh, 
                        gia = :gia, 
                        so_luong_ton = :so_luong_ton, 
                        mo_ta = :mo_ta, 
                        status = :status, 
                        image = :image, 
                        thoi_gian_bh = :thoi_gian_bh, 
                        ma_loai = :ma_loai, 
                        ma_hang = :ma_hang 
                    WHERE ma_dh = :ma_dh";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':ten_dh'       => $tenDh,
                ':gia'          => $newPrice,
                ':so_luong_ton' => $soLuongTon,
                ':mo_ta'        => $moTa,
                ':status'       => $status,
                ':image'        => $image,
                ':thoi_gian_bh' => $thoiGianBh,
                ':ma_loai'      => $maLoai,
                ':ma_hang'      => $maHang,
                ':ma_dh'        => $maDh
            ]);

            // Nếu giá thay đổi, ghi nhận vào lichsugia
            if ($priceChanged) {
                $sqlGia = "INSERT INTO lichsugia (ma_dh, ngay_ap_dung, gia_moi, ma_nv) 
                           VALUES (:ma_dh, NOW(), :gia_moi, :ma_nv)";
                $stmtGia = $db->prepare($sqlGia);
                $stmtGia->execute([
                    ':ma_dh'   => $maDh,
                    ':gia_moi' => $newPrice,
                    ':ma_nv'   => $maNv
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
     * Xóa đồng hồ (Kiểm tra ràng buộc các bảng chi tiết phiếu đặt, đơn đặt, nhập, trả, khuyến mãi)
     */
    public static function delete(string $maDh, ?PDO $pdo = null): bool {
        $db = Database::getConnection();

        $checks = [
            'ct_phieudat'     => 'đơn đặt hàng của khách',
            'ct_dondathang'   => 'đơn đặt hàng nhà cung cấp',
            'ct_phieunhap'    => 'phiếu nhập hàng',
            'ct_phieutrahang' => 'phiếu trả hàng',
        ];

        foreach ($checks as $tbl => $label) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$tbl} WHERE ma_dh = :ma_dh");
            $stmt->execute([':ma_dh' => $maDh]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception("Không thể xóa đồng hồ '{$maDh}' vì đã phát sinh dữ liệu trong {$label}. Hãy chuyển trạng thái sản phẩm sang 'Ngừng kinh doanh'.");
            }
        }

        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM ct_khuyenmai WHERE ma_dh = :ma_dh")->execute([':ma_dh' => $maDh]);
            $db->prepare("DELETE FROM lichsugia WHERE ma_dh = :ma_dh")->execute([':ma_dh' => $maDh]);

            $stmtDel = $db->prepare("DELETE FROM dongho WHERE ma_dh = :ma_dh");
            $stmtDel->execute([':ma_dh' => $maDh]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Lấy danh sách đồng hồ Pin đang kinh doanh (status = 1) phục vụ Storefront
     * Hỗ trợ tìm kiếm, lọc theo loại, hãng, khoảng giá, và tự động tính % khuyến mãi
     */
    public static function getStorefrontWatches(
        ?int $maLoai = null,
        ?int $maHang = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?string $search = null,
        string $sort = 'newest',
        ?PDO $pdo = null
    ): array {
        $db = self::getPdo($pdo);

        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, d.mo_ta, d.image, d.thoi_gian_bh,
                       d.ma_loai, d.ma_hang, l.ten_loai, h.ten_hang,
                       COALESCE(
                           (SELECT MAX(ct.phan_tram_giam)
                            FROM ct_khuyenmai ct
                            JOIN khuyenmai km ON ct.ma_km = km.ma_km
                            WHERE ct.ma_dh = d.ma_dh 
                              AND NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc),
                           0
                       ) AS phan_tram_giam
                FROM dongho d
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                WHERE d.status = 1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $kw = '%' . trim($search) . '%';
            $sql .= " AND (d.ma_dh LIKE :search_ma OR d.ten_dh LIKE :search_ten OR h.ten_hang LIKE :search_hang)";
            $params[':search_ma']   = $kw;
            $params[':search_ten']  = $kw;
            $params[':search_hang'] = $kw;
        }

        if ($maLoai !== null && $maLoai > 0) {
            $sql .= " AND d.ma_loai = :ma_loai";
            $params[':ma_loai'] = $maLoai;
        }

        if ($maHang !== null && $maHang > 0) {
            $sql .= " AND d.ma_hang = :ma_hang";
            $params[':ma_hang'] = $maHang;
        }

        if ($minPrice !== null && $minPrice > 0) {
            $sql .= " AND d.gia >= :min_price";
            $params[':min_price'] = $minPrice;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $sql .= " AND d.gia <= :max_price";
            $params[':max_price'] = $maxPrice;
        }

        // Sắp xếp
        switch ($sort) {
            case 'price_asc':
                $sql .= " ORDER BY d.gia ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY d.gia DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY d.ten_dh ASC";
                break;
            case 'newest':
            default:
                $sql .= " ORDER BY d.ma_dh DESC";
                break;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $watches = $stmt->fetchAll();

        // Tính đơn giá bán thực tế sau khuyến mãi
        foreach ($watches as &$w) {
            $w['gia_goc'] = (float)$w['gia'];
            $w['phan_tram_giam'] = (int)$w['phan_tram_giam'];
            if ($w['phan_tram_giam'] > 0) {
                $w['gia_ban'] = round($w['gia_goc'] * (1 - $w['phan_tram_giam'] / 100), 2);
            } else {
                $w['gia_ban'] = $w['gia_goc'];
            }
        }
        unset($w);

        return $watches;
    }

    /**
     * Lấy chi tiết đồng hồ Pin kèm khuyến mãi đang hiệu lực
     */
    public static function getStorefrontDetail(string $maDh, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);

        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, d.mo_ta, d.image, d.thoi_gian_bh, d.status,
                       d.ma_loai, d.ma_hang, l.ten_loai, h.ten_hang,
                       COALESCE(
                           (SELECT MAX(ct.phan_tram_giam)
                            FROM ct_khuyenmai ct
                            JOIN khuyenmai km ON ct.ma_km = km.ma_km
                            WHERE ct.ma_dh = d.ma_dh 
                              AND NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc),
                           0
                       ) AS phan_tram_giam,
                       (SELECT km.ly_do_km
                        FROM ct_khuyenmai ct
                        JOIN khuyenmai km ON ct.ma_km = km.ma_km
                        WHERE ct.ma_dh = d.ma_dh 
                          AND NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc
                        ORDER BY ct.phan_tram_giam DESC
                        LIMIT 1) AS ten_khuyenmai
                FROM dongho d
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                WHERE d.ma_dh = :ma_dh";

        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_dh' => $maDh]);
        $watch = $stmt->fetch();

        if (!$watch) {
            return null;
        }

        $watch['gia_goc'] = (float)$watch['gia'];
        $watch['phan_tram_giam'] = (int)$watch['phan_tram_giam'];
        if ($watch['phan_tram_giam'] > 0) {
            $watch['gia_ban'] = round($watch['gia_goc'] * (1 - $watch['phan_tram_giam'] / 100), 2);
        } else {
            $watch['gia_ban'] = $watch['gia_goc'];
        }

        return $watch;
    }

    /**
     * Lấy toàn bộ danh sách đồng hồ (mã, tên) cho select box
     */
    public static function all(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $stmt = $db->query("SELECT ma_dh, ten_dh, gia, so_luong_ton, status FROM dongho ORDER BY ten_dh ASC");
        return $stmt->fetchAll();
    }

    /**
     * Trừ tồn kho đồng hồ khi phân công giao hàng
     */
    public static function decreaseStock(string $maDh, int $soLuong, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("UPDATE dongho 
                              SET so_luong_ton = so_luong_ton - :so_luong 
                              WHERE ma_dh = :ma_dh AND so_luong_ton >= :so_luong_check");
        $stmt->execute([
            ':so_luong'       => $soLuong,
            ':ma_dh'          => $maDh,
            ':so_luong_check' => $soLuong
        ]);
        return $stmt->rowCount() > 0;
    }
}

if (!class_exists('DongHo', false)) {
    class_alias('DongHoModel', 'DongHo');
}