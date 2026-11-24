<?php
/**
 * app/Models/DiaChiModel.php
 * Model thao tác với bảng 'diachi' (Sổ địa chỉ người dùng)
 */

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/khachhang.php';

class DiaChiModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy danh sách địa chỉ của người dùng theo thứ tự: Mặc định lên đầu, sau đó theo ID giảm dần
     * Họ tên và SĐT người nhận mặc định lấy từ bảng khachhang (hoặc nguoidung)
     */
    public static function getByNguoiDungId(int $nguoidungId, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT dc.id, dc.nguoidung_id, dc.diachi, dc.macdinh,
                       COALESCE(CONCAT(kh.ho, ' ', kh.ten), nd.hoten) AS ho_ten_nguoi_nhan,
                       COALESCE(kh.so_dien_thoai, nd.sodienthoai) AS so_dien_thoai
                FROM diachi dc
                JOIN nguoidung nd ON dc.nguoidung_id = nd.id
                LEFT JOIN khachhang kh ON kh.nguoidung_id = nd.id
                WHERE dc.nguoidung_id = :nd_id 
                ORDER BY dc.macdinh DESC, dc.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nd_id' => $nguoidungId]);
        return $stmt->fetchAll();
    }

    /**
     * Tìm 1 địa chỉ theo ID và thuộc về người dùng
     */
    public static function findById(int $id, int $nguoidungId, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT id, nguoidung_id, diachi, macdinh 
                FROM diachi 
                WHERE id = :id AND nguoidung_id = :nd_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id, ':nd_id' => $nguoidungId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Đếm tổng số địa chỉ của một người dùng
     */
    public static function countByNguoiDungId(int $nguoidungId, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM diachi WHERE nguoidung_id = :nd_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nd_id' => $nguoidungId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Thêm địa chỉ mới
     * Xử lý Transaction: nếu chọn mặc định hoặc chưa có địa chỉ nào, đổi các địa chỉ cũ về 0 và đồng bộ khachhang.dia_chi
     */
    public static function addAddress(int $nguoidungId, string $diachi, bool $isDefault = false): int {
        $db = Database::getConnection();
        $diachiTrim = trim($diachi);

        if ($diachiTrim === '') {
            throw new InvalidArgumentException("Địa chỉ không được để trống.");
        }

        $totalCount = self::countByNguoiDungId($nguoidungId, $db);
        // Nếu người dùng chưa có địa chỉ nào thì bắt buộc địa chỉ đầu tiên là mặc định
        if ($totalCount === 0) {
            $isDefault = true;
        }

        $db->beginTransaction();
        try {
            if ($isDefault) {
                // Đặt tất cả địa chỉ cũ của user về 0
                $stmtReset = $db->prepare("UPDATE diachi SET macdinh = 0 WHERE nguoidung_id = :nd_id");
                $stmtReset->execute([':nd_id' => $nguoidungId]);
            }

            // Chèn địa chỉ mới
            $stmtInsert = $db->prepare("INSERT INTO diachi (nguoidung_id, diachi, macdinh) VALUES (:nd_id, :diachi, :macdinh)");
            $stmtInsert->execute([
                ':nd_id'   => $nguoidungId,
                ':diachi'  => $diachiTrim,
                ':macdinh' => $isDefault ? 1 : 0
            ]);

            $newId = (int)$db->lastInsertId();
            if ($newId <= 0) {
                $newId = (int)$db->query("SELECT SCOPE_IDENTITY() AS id")->fetchColumn();
            }

            // Đồng bộ sang bảng khachhang nếu là mặc định
            if ($isDefault) {
                KhachHangModel::updateDefaultAddress($nguoidungId, $diachiTrim, $db);
            }

            $db->commit();
            return $newId;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Thiết lập địa chỉ mặc định
     */
    public static function setDefault(int $addressId, int $nguoidungId): bool {
        $db = Database::getConnection();

        // Kiểm tra xem địa chỉ có thuộc về user không
        $addr = self::findById($addressId, $nguoidungId, $db);
        if (!$addr) {
            throw new Exception("Địa chỉ không tồn tại hoặc không thuộc quyền sở hữu của bạn.");
        }

        $db->beginTransaction();
        try {
            // Chuyển toàn bộ địa chỉ của user về 0
            $stmtReset = $db->prepare("UPDATE diachi SET macdinh = 0 WHERE nguoidung_id = :nd_id");
            $stmtReset->execute([':nd_id' => $nguoidungId]);

            // Đặt địa chỉ được chọn thành 1
            $stmtSet = $db->prepare("UPDATE diachi SET macdinh = 1 WHERE id = :id AND nguoidung_id = :nd_id");
            $stmtSet->execute([':id' => $addressId, ':nd_id' => $nguoidungId]);

            // Đồng bộ sang bảng khachhang
            KhachHangModel::updateDefaultAddress($nguoidungId, $addr['diachi'], $db);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Xóa địa chỉ
     * - Không cho phép xóa nếu đó là địa chỉ duy nhất
     * - Nếu xóa địa chỉ đang là mặc định (macdinh = 1), tự động chọn địa chỉ gần nhất còn lại làm mặc định
     */
    public static function deleteAddress(int $addressId, int $nguoidungId): bool {
        $db = Database::getConnection();

        $totalCount = self::countByNguoiDungId($nguoidungId, $db);
        if ($totalCount <= 1) {
            throw new Exception("Không thể xóa địa chỉ duy nhất. Bạn cần duy trì ít nhất một địa chỉ giao hàng!");
        }

        $addr = self::findById($addressId, $nguoidungId, $db);
        if (!$addr) {
            throw new Exception("Địa chỉ không tồn tại hoặc không thuộc quyền sở hữu của bạn.");
        }

        $wasDefault = ((int)$addr['macdinh'] === 1);

        $db->beginTransaction();
        try {
            // Xóa địa chỉ
            $stmtDel = $db->prepare("DELETE FROM diachi WHERE id = :id AND nguoidung_id = :nd_id");
            $stmtDel->execute([':id' => $addressId, ':nd_id' => $nguoidungId]);

            // Nếu vừa xóa địa chỉ mặc định, tự động lấy địa chỉ còn lại gần nhất (id lớn nhất) đặt làm mặc định
            if ($wasDefault) {
                // T-SQL: TOP 1
                $stmtNext = $db->prepare("SELECT id, diachi FROM diachi WHERE nguoidung_id = :nd_id ORDER BY id DESC LIMIT 1");
                $stmtNext->execute([':nd_id' => $nguoidungId]);
                $nextAddr = $stmtNext->fetch();

                if ($nextAddr) {
                    $stmtSetNext = $db->prepare("UPDATE diachi SET macdinh = 1 WHERE id = :id");
                    $stmtSetNext->execute([':id' => $nextAddr['id']]);

                    // Đồng bộ sang bảng khachhang
                    KhachHangModel::updateDefaultAddress($nguoidungId, $nextAddr['diachi'], $db);
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

if (!class_exists('DiaChi', false)) {
    class_alias('DiaChiModel', 'DiaChi');
}
