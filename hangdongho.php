<?php
/**
 * app/Models/HangDongHoModel.php
 * Thao tác với bảng 'hangdongho' (Hãng sản xuất đồng hồ)
 */

require_once __DIR__ . '/../data/database.php';

class HangDongHoModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ danh sách hãng kèm số lượng đồng hồ thuộc hãng
     */
    public static function getAllWithCount(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT h.ma_hang, h.ten_hang, COUNT(d.ma_dh) AS so_luong_dh
                FROM hangdongho h
                LEFT JOIN dongho d ON h.ma_hang = d.ma_hang
                GROUP BY h.ma_hang, h.ten_hang
                ORDER BY h.ma_hang ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách hãng đơn giản (dùng cho dropdown)
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_hang, ten_hang FROM hangdongho ORDER BY ten_hang ASC";
        return $db->query($sql)->fetchAll();
    }

    /**
     * Tìm hãng theo mã
     */
    public static function findById(int $maHang, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT ma_hang, ten_hang FROM hangdongho WHERE ma_hang = :ma_hang");
        $stmt->execute([':ma_hang' => $maHang]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra tên hãng đã tồn tại chưa (phục vụ ràng buộc UNIQUE uk_ten_hang)
     */
    public static function checkNameExists(string $tenHang, ?int $excludeMaHang = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM hangdongho WHERE ten_hang = :ten_hang";
        $params = [':ten_hang' => trim($tenHang)];
        if ($excludeMaHang !== null) {
            $sql .= " AND ma_hang <> :exclude";
            $params[':exclude'] = $excludeMaHang;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Thêm mới hãng đồng hồ
     */
    public static function create(string|array $tenHang, ?PDO $pdo = null): int {
        if (is_array($tenHang)) {
            $tenHang = $tenHang['ten_hang'] ?? '';
        }
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("INSERT INTO hangdongho (ten_hang) VALUES (:ten_hang)");
        $stmt->execute([':ten_hang' => trim((string)$tenHang)]);
        return (int)$db->lastInsertId();
    }

    /**
     * Cập nhật tên hãng
     */
    public static function update(int $maHang, string|array $tenHang, ?PDO $pdo = null): bool {
        if (is_array($tenHang)) {
            $tenHang = $tenHang['ten_hang'] ?? '';
        }
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("UPDATE hangdongho SET ten_hang = :ten_hang WHERE ma_hang = :ma_hang");
        return $stmt->execute([
            ':ten_hang' => trim((string)$tenHang),
            ':ma_hang'  => $maHang
        ]);
    }

    /**
     * Đếm số lượng sản phẩm của hãng
     */
    public static function countProducts(int $maHang, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM dongho WHERE ma_hang = :ma_hang");
        $stmt->execute([':ma_hang' => $maHang]);
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
     * Xóa hãng đồng hồ (Chặn xóa nếu đang có sản phẩm)
     */
    public static function delete(int $maHang, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        if (self::countProducts($maHang, $db) > 0) {
            throw new Exception("Không thể xóa hãng đồng hồ đang có sản phẩm kinh doanh.");
        }
        $stmt = $db->prepare("DELETE FROM hangdongho WHERE ma_hang = :ma_hang");
        return $stmt->execute([':ma_hang' => $maHang]);
    }
}

if (!class_exists('HangDongHo', false)) {
    class_alias('HangDongHoModel', 'HangDongHo');
}
