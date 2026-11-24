<?php
/**
 * app/Models/NhaCungCapModel.php
 * Thao tác với bảng 'nhacungcap' (Nhà cung cấp đồng hồ)
 */

require_once __DIR__ . '/../data/database.php';

class NhaCungCapModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách nhà cung cấp có tìm kiếm theo mã, tên, số điện thoại kèm số đơn đặt
     */
    public static function getAllFiltered(?string $search = null, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ncc.ma_ncc, ncc.ten_ncc, ncc.dia_chi, ncc.email, ncc.so_dien_thoai,
                       COUNT(ddh.ma_ddh) AS so_don_dat
                FROM nhacungcap ncc
                LEFT JOIN dondathang ddh ON ncc.ma_ncc = ddh.ma_ncc
                WHERE 1=1";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $kw = '%' . trim($search) . '%';
            $sql .= " AND (ncc.ma_ncc LIKE :s1 OR ncc.ten_ncc LIKE :s2 OR ncc.so_dien_thoai LIKE :s3 OR ncc.email LIKE :s4)";
            $params[':s1'] = $kw;
            $params[':s2'] = $kw;
            $params[':s3'] = $kw;
            $params[':s4'] = $kw;
        }

        $sql .= " GROUP BY ncc.ma_ncc, ncc.ten_ncc, ncc.dia_chi, ncc.email, ncc.so_dien_thoai
                  ORDER BY ncc.ma_ncc ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy danh sách đơn giản cho dropdown
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_ncc, ten_ncc, so_dien_thoai FROM nhacungcap ORDER BY ten_ncc ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function all(?PDO $pdo = null): array {
        return self::getAll($pdo);
    }

    /**
     * Tìm nhà cung cấp theo mã
     */
    public static function findById(string $maNcc, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT ma_ncc, ten_ncc, dia_chi, email, so_dien_thoai FROM nhacungcap WHERE ma_ncc = :ma_ncc");
        $stmt->execute([':ma_ncc' => $maNcc]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra mã nhà cung cấp đã tồn tại chưa
     */
    public static function checkIdExists(string $maNcc, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM nhacungcap WHERE ma_ncc = :ma_ncc");
        $stmt->execute([':ma_ncc' => trim($maNcc)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Thêm mới nhà cung cấp
     */
    public static function create(array $data, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $maNcc = strtoupper(trim($data['ma_ncc']));

        if (self::checkIdExists($maNcc, $db)) {
            throw new Exception("Mã nhà cung cấp '{$maNcc}' đã tồn tại trong hệ thống.");
        }

        $sql = "INSERT INTO nhacungcap (ma_ncc, ten_ncc, dia_chi, email, so_dien_thoai) 
                VALUES (:ma_ncc, :ten_ncc, :dia_chi, :email, :so_dien_thoai)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ma_ncc'        => $maNcc,
            ':ten_ncc'       => trim($data['ten_ncc']),
            ':dia_chi'       => trim($data['dia_chi']),
            ':email'         => trim($data['email']),
            ':so_dien_thoai' => trim($data['so_dien_thoai'])
        ]);
    }

    /**
     * Cập nhật thông tin nhà cung cấp
     */
    public static function update(string $maNcc, array $data, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE nhacungcap 
                SET ten_ncc = :ten_ncc, 
                    dia_chi = :dia_chi, 
                    email = :email, 
                    so_dien_thoai = :so_dien_thoai 
                WHERE ma_ncc = :ma_ncc";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ten_ncc'       => trim($data['ten_ncc']),
            ':dia_chi'       => trim($data['dia_chi']),
            ':email'         => trim($data['email']),
            ':so_dien_thoai' => trim($data['so_dien_thoai']),
            ':ma_ncc'        => $maNcc
        ]);
    }

    /**
     * Đếm số đơn đặt hàng của nhà cung cấp này
     */
    public static function countOrders(string $maNcc, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM dondathang WHERE ma_ncc = :ma_ncc");
        $stmt->execute([':ma_ncc' => $maNcc]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Xóa nhà cung cấp (chặn nếu đã có đơn đặt hàng)
     */
    public static function delete(string $maNcc, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        if (self::countOrders($maNcc, $db) > 0) {
            throw new Exception("Không thể xóa nhà cung cấp '{$maNcc}' vì đã có đơn đặt hàng trong hệ thống.");
        }

        $stmt = $db->prepare("DELETE FROM nhacungcap WHERE ma_ncc = :ma_ncc");
        return $stmt->execute([':ma_ncc' => $maNcc]);
    }
}

if (!class_exists('NhaCungCap', false)) {
    class_alias('NhaCungCapModel', 'NhaCungCap');
}
