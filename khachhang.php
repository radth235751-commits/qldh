<?php
/**
 * app/Models/KhachHangModel.php
 * Model thao tác với bảng 'khachhang'
 */

require_once __DIR__ . '/../data/database.php';

class KhachHangModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Tìm khách hàng theo nguoidung_id
     */
    public static function findByNguoiDungId(int $nguoidungId, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_kh, nguoidung_id, cmnd, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email, ma_so_thue 
                FROM khachhang 
                WHERE nguoidung_id = :nd_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':nd_id' => $nguoidungId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Tìm khách hàng theo mã khách hàng
     */
    public static function findByMaKH(int $maKh, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ma_kh, nguoidung_id, cmnd, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email, ma_so_thue 
                FROM khachhang 
                WHERE ma_kh = :ma_kh";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_kh' => $maKh]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra số CMND/CCCD đã tồn tại chưa
     */
    public static function checkCmndExists(string $cmnd, ?int $excludeMaKh = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM khachhang WHERE cmnd = :cmnd";
        $params = [':cmnd' => trim($cmnd)];
        if ($excludeMaKh !== null) {
            $sql .= " AND ma_kh <> :excludeMaKh";
            $params[':excludeMaKh'] = $excludeMaKh;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra số điện thoại khách hàng đã tồn tại chưa
     */
    public static function checkPhoneExists(string $phone, ?int $excludeMaKh = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "SELECT COUNT(*) FROM khachhang WHERE so_dien_thoai = :phone";
        $params = [':phone' => trim($phone)];
        if ($excludeMaKh !== null) {
            $sql .= " AND ma_kh <> :excludeMaKh";
            $params[':excludeMaKh'] = $excludeMaKh;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Thêm mới khách hàng
     * Lưu ý: cmnd, ma_so_thue, ngay_sinh nếu rỗng → truyền NULL để tránh lỗi UNIQUE trùng ''
     */
    public static function create(array $data, ?PDO $pdo = null): int {
        $db = self::getPdo($pdo);

        // Chuẩn hóa cmnd: rỗng → null; nếu có giá trị → kiểm tra trùng
        $cmnd = isset($data['cmnd']) ? trim($data['cmnd']) : null;
        if ($cmnd === '') $cmnd = null;
        if ($cmnd !== null) {
            if (self::checkCmndExists($cmnd, null, $db)) {
                throw new \Exception("Số CMND/CCCD '{$cmnd}' đã được đăng ký bởi khách hàng khác.");
            }
        }

        $sql = "INSERT INTO khachhang (nguoidung_id, cmnd, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email, ma_so_thue) 
                VALUES (:nguoidung_id, :cmnd, :ho, :ten, :gioi_tinh, :ngay_sinh, :dia_chi, :so_dien_thoai, :email, :ma_so_thue)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':nguoidung_id'  => $data['nguoidung_id'] ?? null,
            ':cmnd'          => $cmnd,
            ':ho'            => trim($data['ho'] ?? ''),
            ':ten'           => trim($data['ten'] ?? ''),
            ':gioi_tinh'     => $data['gioi_tinh'] ?? 'Nam',
            ':ngay_sinh'     => !empty($data['ngay_sinh']) ? $data['ngay_sinh'] : null,
            ':dia_chi'       => trim($data['dia_chi'] ?? ''),
            ':so_dien_thoai' => trim($data['so_dien_thoai'] ?? ''),
            ':email'         => !empty($data['email']) ? trim($data['email']) : null,
            ':ma_so_thue'    => !empty($data['ma_so_thue']) ? trim($data['ma_so_thue']) : null
        ]);

        $id = (int)$db->lastInsertId();
        if ($id <= 0) {
            $id = (int)$db->query("SELECT SCOPE_IDENTITY() AS id")->fetchColumn();
        }
        return $id;
    }

    /**
     * Cập nhật thông tin khách hàng
     * cmnd rỗng → null để tránh trùng UNIQUE
     */
    public static function updateProfile(
        int $maKh,
        string $ho,
        string $ten,
        ?string $cmnd,
        string $gioiTinh,
        ?string $ngaySinh,
        string $soDienThoai,
        ?string $email,
        ?string $maSoThue,
        ?PDO $pdo = null
    ): bool {
        $db = self::getPdo($pdo);

        // Chuẩn hóa cmnd: rỗng → null; nếu có → kiểm tra trùng
        $cmnd = ($cmnd !== null) ? trim($cmnd) : null;
        if ($cmnd === '') $cmnd = null;
        if ($cmnd !== null) {
            if (self::checkCmndExists($cmnd, $maKh, $db)) {
                throw new \Exception("Số CMND/CCCD '{$cmnd}' đã được đăng ký bởi khách hàng khác.");
            }
        }

        // Chuẩn hóa ma_so_thue: rỗng → null
        $maSoThue = ($maSoThue !== null) ? trim($maSoThue) : null;
        if ($maSoThue === '') $maSoThue = null;

        $sql = "UPDATE khachhang 
                SET ho = :ho, 
                    ten = :ten, 
                    cmnd = :cmnd, 
                    gioi_tinh = :gioi_tinh, 
                    ngay_sinh = :ngay_sinh, 
                    so_dien_thoai = :so_dien_thoai, 
                    email = :email, 
                    ma_so_thue = :ma_so_thue 
                WHERE ma_kh = :ma_kh";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ho'            => trim($ho),
            ':ten'           => trim($ten),
            ':cmnd'          => $cmnd,
            ':gioi_tinh'     => $gioiTinh,
            ':ngay_sinh'     => !empty($ngaySinh) ? $ngaySinh : null,
            ':so_dien_thoai' => trim($soDienThoai),
            ':email'         => !empty($email) ? trim($email) : null,
            ':ma_so_thue'    => $maSoThue,
            ':ma_kh'         => $maKh
        ]);
    }

    /**
     * Cập nhật địa chỉ mặc định của khách hàng
     */
    public static function updateDefaultAddress(int $nguoidungId, string $newAddress, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE khachhang SET dia_chi = :dia_chi WHERE nguoidung_id = :nd_id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':dia_chi' => trim($newAddress),
            ':nd_id'   => $nguoidungId
        ]);
    }

    /**
     * Đếm tổng số khách hàng
     */
    public static function countAll(?PDO $pdo = null): int {
        $db = self::getPdo($pdo);
        return (int)$db->query("SELECT COUNT(*) FROM khachhang")->fetchColumn();
    }
}

if (!class_exists('KhachHang', false)) {
    class_alias('KhachHangModel', 'KhachHang');
}
