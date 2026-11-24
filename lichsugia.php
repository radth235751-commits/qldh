<?php
/**
 * app/Models/LichSuGiaModel.php
 * Thao tác với bảng 'lichsugia' (Lịch sử biến động giá đồng hồ)
 */

require_once __DIR__ . '/../data/database.php';

class LichSuGiaModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ lịch sử biến động giá của một mẫu đồng hồ (hoặc tất cả gần đây nếu $maDh rỗng)
     */
    public static function getByMaDh(?string $maDh = null, ?PDO $pdo = null): array {
        if (empty($maDh)) {
            return self::getAllRecent(100, $pdo);
        }
        $db = self::getPdo($pdo);
        $sql = "SELECT l.ma_dh, l.ngay_ap_dung, l.gia_moi, l.ma_nv, 
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       d.ten_dh, d.image
                FROM lichsugia l
                JOIN dongho d ON l.ma_dh = d.ma_dh
                LEFT JOIN nhanvien nv ON l.ma_nv = nv.ma_nv
                WHERE l.ma_dh = :ma_dh
                ORDER BY l.ngay_ap_dung DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_dh' => $maDh]);
        return $stmt->fetchAll();
    }

    /**
     * Alias method: getAll
     */
    public static function getAll(?string $maDh = null, ?PDO $pdo = null): array {
        return self::getByMaDh($maDh, $pdo);
    }

    /**
     * Lấy danh sách lịch sử đổi giá gần đây của toàn bộ hệ thống
     */
    public static function getAllRecent(int $limit = 50, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT l.ma_dh, l.ngay_ap_dung, l.gia_moi, l.ma_nv, 
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       d.ten_dh, d.image, d.gia AS gia_hien_tai,
                       h.ten_hang, ld.ten_loai
                FROM lichsugia l
                JOIN dongho d ON l.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho ld ON d.ma_loai = ld.ma_loai
                LEFT JOIN nhanvien nv ON l.ma_nv = nv.ma_nv
                ORDER BY l.ngay_ap_dung DESC
                LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Thêm bản ghi lịch sử giá mới
     */
    public static function addRecord(string $maDh, float $giaMoi, string $maNv, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "INSERT INTO lichsugia (ma_dh, ngay_ap_dung, gia_moi, ma_nv) 
                VALUES (:ma_dh, NOW(), :gia_moi, :ma_nv)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ma_dh'   => $maDh,
            ':gia_moi' => $giaMoi,
            ':ma_nv'   => $maNv
        ]);
    }

    /**
     * Đếm tổng số lần thay đổi giá trong hệ thống
     */
    public static function countAll(?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        return (int)$db->query("SELECT COUNT(*) FROM lichsugia")->fetchColumn();
    }
}

if (!class_exists('LichSuGia', false)) {
    class_alias('LichSuGiaModel', 'LichSuGia');
}
