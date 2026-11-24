<?php
/**
 * app/Models/LoaiDongHoModel.php
 * Thao tác với bảng 'loaidongho' (Loại đồng hồ Pin)
 */

require_once __DIR__ . '/../data/database.php';

class LoaiDongHoModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ danh sách loại đồng hồ kèm số lượng sản phẩm thuộc mỗi loại
     */
    public static function getAllWithCount(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT l.ma_loai, l.ten_loai, COUNT(d.ma_dh) AS so_luong_dh
                FROM loaidongho l
                LEFT JOIN dongho d ON l.ma_loai = d.ma_loai
                GROUP BY l.ma_loai, l.ten_loai
                ORDER BY l.ma_loai ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách loại đồng hồ đơn giản (dùng cho dropdown)
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_loai, ten_loai FROM loaidongho ORDER BY ten_loai ASC";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Tìm loại đồng hồ theo mã
     */
    public static function findById(int $maLoai, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT ma_loai, ten_loai FROM loaidongho WHERE ma_loai = :ma_loai");
        $stmt->execute([':ma_loai' => $maLoai]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra tên loại đã tồn tại chưa (phục vụ ràng buộc UNIQUE uk_ten_loai)
     */
    public static function checkNameExists(string $tenLoai, ?int $excludeMaLoai = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM loaidongho WHERE ten_loai = :ten_loai";
        $params = [':ten_loai' => trim($tenLoai)];
        if ($excludeMaLoai !== null) {
            $sql .= " AND ma_loai <> :exclude";
            $params[':exclude'] = $excludeMaLoai;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Thêm mới loại đồng hồ
     */
    public static function create(string|array $tenLoai, ?PDO $pdo = null): int {
        if (is_array($tenLoai)) {
            $tenLoai = $tenLoai['ten_loai'] ?? '';
        }
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("INSERT INTO loaidongho (ten_loai) VALUES (:ten_loai)");
        $stmt->execute([':ten_loai' => trim((string)$tenLoai)]);
        return (int)$db->lastInsertId();
    }

    /**
     * Cập nhật tên loại đồng hồ
     */
    public static function update(int $maLoai, string|array $tenLoai, ?PDO $pdo = null): bool {
        if (is_array($tenLoai)) {
            $tenLoai = $tenLoai['ten_loai'] ?? '';
        }
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("UPDATE loaidongho SET ten_loai = :ten_loai WHERE ma_loai = :ma_loai");
        return $stmt->execute([
            ':ten_loai' => trim((string)$tenLoai),
            ':ma_loai'  => $maLoai
        ]);
    }

    /**
     * Đếm số lượng đồng hồ thuộc loại này
     */
    public static function countProducts(int $maLoai, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM dongho WHERE ma_loai = :ma_loai");
        $stmt->execute([':ma_loai' => $maLoai]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Alias method: all()
     */
    public static function all(?PDO $pdo = null): array {
        return self::getAll($pdo);
    }

    /**
     * Alias method: allWithProductCount()
     */
    public static function allWithProductCount(?PDO $pdo = null): array {
        return self::getAllWithCount($pdo);
    }

    /**
     * Xóa loại đồng hồ (Chặn xóa nếu đang có sản phẩm)
     */
    public static function delete(int $maLoai, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        if (self::countProducts($maLoai, $db) > 0) {
            throw new Exception("Không thể xóa loại đồng hồ đang có sản phẩm kinh doanh.");
        }
        $stmt = $db->prepare("DELETE FROM loaidongho WHERE ma_loai = :ma_loai");
        return $stmt->execute([':ma_loai' => $maLoai]);
    }
}

if (!class_exists('LoaiDongHo', false)) {
    class_alias('LoaiDongHoModel', 'LoaiDongHo');
}
