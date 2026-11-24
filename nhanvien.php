<?php
/**
 * app/Models/NhanVienModel.php
 * Model thao tác với bảng 'nhanvien'
 */

require_once __DIR__ . '/../data/database.php';

class NhanVienModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Tìm nhân viên theo nguoidung_id
     */
    public static function findByNguoiDungId(int $nguoidungId, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_nv, nguoidung_id, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email 
                FROM nhanvien 
                WHERE nguoidung_id = :nd_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nd_id' => $nguoidungId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm nhân viên theo mã nhân viên
     */
    public static function findByMaNV(string $maNv, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_nv, nguoidung_id, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email 
                FROM nhanvien 
                WHERE ma_nv = :ma_nv";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_nv' => $maNv]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cập nhật thông tin hồ sơ nhân viên đồng bộ với bảng nguoidung
     */
    public static function updateProfile(
        string $maNv, 
        string $ho, 
        string $ten, 
        string $gioiTinh, 
        string $ngaySinh, 
        string $diaChi, 
        string $soDienThoai, 
        string $email, 
        ?PDO $pdo = null
    ): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE nhanvien 
                SET ho = :ho, 
                    ten = :ten, 
                    gioi_tinh = :gioi_tinh, 
                    ngay_sinh = :ngay_sinh, 
                    dia_chi = :dia_chi, 
                    so_dien_thoai = :so_dien_thoai, 
                    email = :email 
                WHERE ma_nv = :ma_nv";
        $stmt = $db->prepare($sql);
        $res = $stmt->execute([
            ':ho'            => trim($ho),
            ':ten'           => trim($ten),
            ':gioi_tinh'     => $gioiTinh,
            ':ngay_sinh'     => $ngaySinh,
            ':dia_chi'       => trim($diaChi),
            ':so_dien_thoai' => trim($soDienThoai),
            ':email'         => trim($email),
            ':ma_nv'         => $maNv
        ]);

        if ($res) {
            // Tự động đồng bộ sang bảng nguoidung.hoten = CONCAT(ho, ' ', ten)
            $hoten = trim("{$ho} {$ten}");
            $stmtUid = $db->prepare("SELECT nguoidung_id FROM nhanvien WHERE ma_nv = :ma_nv LIMIT 1");
            $stmtUid->execute([':ma_nv' => $maNv]);
            $uid = (int)$stmtUid->fetchColumn();

            if ($uid > 0) {
                $stmtUpdNd = $db->prepare("UPDATE nguoidung SET hoten = :hoten WHERE id = :uid");
                $stmtUpdNd->execute([':hoten' => $hoten, ':uid' => $uid]);

                if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $uid) {
                    $_SESSION['user']['hoten'] = $hoten;
                }
            }
        }

        return $res;
    }

    /**
     * Lấy toàn bộ danh sách nhân viên
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT nv.ma_nv, nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ho_ten, 
                       nv.so_dien_thoai, nv.email, nv.dia_chi 
                FROM nhanvien nv 
                ORDER BY nv.ten ASC, nv.ho ASC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    public static function all(?PDO $pdo = null): array {
        return self::getAll($pdo);
    }

    /**
     * Đếm tổng số nhân viên đang hoạt động
     */
    public static function countAll(?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        return (int)$db->query("SELECT COUNT(*) FROM nhanvien")->fetchColumn();
    }
}

if (!class_exists('NhanVien', false)) {
    class_alias('NhanVienModel', 'NhanVien');
}
