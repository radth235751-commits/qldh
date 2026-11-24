<?php
/**
 * app/Models/NguoiDungModel.php
 * Model thao tác với bảng 'nguoidung'
 */

require_once __DIR__ . '/../data/database.php';

class NguoiDungModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Tìm người dùng theo Email hoặc Số điện thoại
     */
    public static function findByLogin(string $login, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT id, email, sodienthoai, matkhau, hoten, loai, trangthai, hinhanh 
                FROM nguoidung 
                WHERE email = :login_email OR sodienthoai = :login_phone";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':login_email' => $login,
            ':login_phone' => $login
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm theo ID
     */
    public static function findById(int $id, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT id, email, sodienthoai, matkhau, hoten, loai, trangthai, hinhanh 
                FROM nguoidung 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra số điện thoại đã tồn tại chưa
     */
    public static function checkPhoneExists(string $phone, ?int $excludeId = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM nguoidung WHERE sodienthoai = :phone";
        $params = [':phone' => $phone];
        if ($excludeId !== null) {
            $sql .= " AND id <> :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra email đã tồn tại chưa (nếu email không rỗng)
     */
    public static function checkEmailExists(?string $email, ?int $excludeId = null, ?PDO $pdo = null): bool {
        if ($email === null || trim($email) === '') {
            return false;
        }
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM nguoidung WHERE email = :email";
        $params = [':email' => trim($email)];
        if ($excludeId !== null) {
            $sql .= " AND id <> :excludeId";
            $params[':excludeId'] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Tạo tài khoản người dùng mới
     * @return int ID của người dùng vừa chèn
     */
    public static function create(array $data, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        $sql = "INSERT INTO nguoidung (email, sodienthoai, matkhau, hoten, loai, trangthai, hinhanh) 
                VALUES (:email, :sodienthoai, :matkhau, :hoten, :loai, :trangthai, :hinhanh)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':email'       => !empty($data['email']) ? trim($data['email']) : null,
            ':sodienthoai' => trim($data['sodienthoai']),
            ':matkhau'     => md5($data['matkhau']), // Đối chiếu md5 theo quy chuẩn CSDL QLDH
            ':hoten'       => trim($data['hoten']),
            ':loai'        => $data['loai'] ?? 3, // Mặc định 3: Khách hàng
            ':trangthai'   => $data['trangthai'] ?? 1, // 1: Hoạt động
            ':hinhanh'     => $data['hinhanh'] ?? null
        ]);

        $id = (int)$db->lastInsertId();
        if ($id <= 0) {
            $id = (int)$db->query("SELECT SCOPE_IDENTITY() AS id")->fetchColumn();
        }
        return $id;
    }

    /**
     * Cập nhật thông tin cơ bản người dùng
     */
    public static function updateProfile(int $id, string $hoten, string $sodienthoai, ?string $email, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE nguoidung 
                SET hoten = :hoten, 
                    sodienthoai = :sodienthoai, 
                    email = :email 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $res = $stmt->execute([
            ':hoten'       => trim($hoten),
            ':sodienthoai' => trim($sodienthoai),
            ':email'       => !empty($email) ? trim($email) : null,
            ':id'          => $id
        ]);

        if ($res) {
            // Kiểm tra nếu là Quản trị viên (1) hoặc Nhân viên (2) thì tách chuỗi đồng bộ vào bảng nhanvien
            $stmtLoai = $db->prepare("SELECT loai FROM nguoidung WHERE id = :id");
            $stmtLoai->execute([':id' => $id]);
            $loai = (int)$stmtLoai->fetchColumn();

            if (in_array($loai, [1, 2], true)) {
                $parts = explode(' ', trim($hoten));
                if (count($parts) > 1) {
                    $ten = array_pop($parts);
                    $ho = implode(' ', $parts);
                } else {
                    $ho = '';
                    $ten = $parts[0] ?? '';
                }
                $stmtNv = $db->prepare("UPDATE nhanvien SET ho = :ho, ten = :ten WHERE nguoidung_id = :uid");
                $stmtNv->execute([
                    ':ho'  => $ho,
                    ':ten' => $ten,
                    ':uid' => $id
                ]);
            }

            // Đồng bộ session nếu đang sửa chính tài khoản đang đăng nhập
            if (isset($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $id) {
                $_SESSION['user']['hoten'] = trim($hoten);
                if (!empty($email)) $_SESSION['user']['email'] = trim($email);
                if (!empty($sodienthoai)) $_SESSION['user']['sodienthoai'] = trim($sodienthoai);
            }
        }

        return $res;
    }

    /**
     * Kiểm tra mật khẩu hiện tại có đúng không
     */
    public static function verifyPassword(int $id, string $plainPassword, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT matkhau FROM nguoidung WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $hash = $stmt->fetchColumn();
        if (!$hash) {
            return false;
        }
        return md5($plainPassword) === $hash;
    }

    /**
     * Đổi mật khẩu
     */
    public static function updatePassword(int $id, string $newPasswordPlain, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE nguoidung SET matkhau = :matkhau WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':matkhau' => md5($newPasswordPlain),
            ':id'      => $id
        ]);
    }

    /**
     * Cập nhật hình ảnh đại diện
     */
    public static function updateAvatar(int $id, ?string $hinhanh, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE nguoidung SET hinhanh = :hinhanh WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':hinhanh' => $hinhanh,
            ':id'      => $id
        ]);
    }
}

if (!class_exists('NguoiDung', false)) {
    class_alias('NguoiDungModel', 'NguoiDung');
}
