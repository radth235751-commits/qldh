<?php
/**
 * app/Models/KhuyenMaiModel.php
 * Thao tác với bảng 'khuyenmai' và 'ct_khuyenmai' (Chương trình Khuyến mãi)
 */

require_once __DIR__ . '/../data/database.php';

class KhuyenMaiModel {

    private static function getPdo(?PDO $pdo = null): PDO {
        return $pdo ?? Database::getConnection();
    }

    /**
     * Lấy toàn bộ danh sách chương trình khuyến mãi kèm số lượng sản phẩm áp dụng và nhân viên tạo
     */
    public static function getAll(?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT km.ma_km, km.ly_do_km, km.ngay_bat_dau, km.ngay_ket_thuc, km.ma_nv, 
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       COUNT(ct.ma_dh) AS so_sp_km,
                       CASE 
                           WHEN NOW() < km.ngay_bat_dau THEN 'upcoming'
                           WHEN NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc THEN 'active'
                           ELSE 'expired'
                       END AS trang_thai_km
                FROM khuyenmai km
                LEFT JOIN nhanvien nv ON km.ma_nv = nv.ma_nv
                LEFT JOIN ct_khuyenmai ct ON km.ma_km = ct.ma_km
                GROUP BY km.ma_km, km.ly_do_km, km.ngay_bat_dau, km.ngay_ket_thuc, km.ma_nv, nv.ho, nv.ten
                ORDER BY km.ngay_bat_dau DESC";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Tìm chương trình khuyến mãi theo mã
     */
    public static function findById(string $maKm, ?PDO $pdo = null): ?array {
        $db = self::getPdo($pdo);
        $sql = "SELECT km.ma_km, km.ly_do_km, km.ngay_bat_dau, km.ngay_ket_thuc, km.ma_nv, 
                       nv.ho, nv.ten, CONCAT(nv.ho, ' ', nv.ten) AS ten_nhanvien,
                       CASE 
                           WHEN NOW() < km.ngay_bat_dau THEN 'upcoming'
                           WHEN NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc THEN 'active'
                           ELSE 'expired'
                       END AS trang_thai_km
                FROM khuyenmai km
                LEFT JOIN nhanvien nv ON km.ma_nv = nv.ma_nv
                WHERE km.ma_km = :ma_km";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_km' => $maKm]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Kiểm tra mã khuyến mãi đã tồn tại chưa
     */
    public static function checkIdExists(string $maKm, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("SELECT COUNT(*) FROM khuyenmai WHERE ma_km = :ma_km");
        $stmt->execute([':ma_km' => trim($maKm)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Xác thực và tìm ma_nv hợp lệ từ bảng nhanvien:
     * 1. Ưu tiên tìm theo $_SESSION['user']['ma_nv']
     * 2. Nếu không có/không hợp lệ, tìm theo nguoidung_id = $_SESSION['user']['id']
     * 3. Nếu là Admin (loai = 1) chưa có trong nhanvien, tự động tạo bản ghi nhân sự hoặc lấy ma_nv đầu tiên
     * 4. Cập nhật lại $_SESSION['user']['ma_nv'] bằng mã hợp lệ tìm được
     */
    public static function resolveEmployeeCode(?PDO $pdo = null): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = self::getPdo($pdo);
        $user = $_SESSION['user'] ?? [];
        $sessionMaNv = trim((string)($user['ma_nv'] ?? ''));
        $userId = isset($user['id']) ? (int)$user['id'] : 0;
        $userLoai = isset($user['loai']) ? (int)$user['loai'] : 0;

        // 1. Ưu tiên tìm theo $_SESSION['user']['ma_nv'] trong bảng nhanvien
        if ($sessionMaNv !== '') {
            $stmt = $db->prepare("SELECT ma_nv FROM nhanvien WHERE ma_nv = :ma_nv LIMIT 1");
            $stmt->execute([':ma_nv' => $sessionMaNv]);
            $foundMaNv = $stmt->fetchColumn();
            if ($foundMaNv) {
                if (!isset($_SESSION['user'])) {
                    $_SESSION['user'] = [];
                }
                $_SESSION['user']['ma_nv'] = (string)$foundMaNv;
                return (string)$foundMaNv;
            }
        }

        // 2. Tìm theo nguoidung_id = $_SESSION['user']['id']
        if ($userId > 0) {
            $stmt = $db->prepare("SELECT ma_nv FROM nhanvien WHERE nguoidung_id = :nd_id LIMIT 1");
            $stmt->execute([':nd_id' => $userId]);
            $foundMaNv = $stmt->fetchColumn();
            if ($foundMaNv) {
                if (!isset($_SESSION['user'])) {
                    $_SESSION['user'] = [];
                }
                $_SESSION['user']['ma_nv'] = (string)$foundMaNv;
                return (string)$foundMaNv;
            }
        }

        // 3. Nếu tài khoản Admin (loai = 1) chưa có dòng trong nhanvien:
        // Tự động tạo bản ghi nhân sự quản trị hoặc lấy ma_nv hợp lệ đầu tiên
        if ($userLoai === 1 && $userId > 0) {
            try {
                // Lấy thông tin tài khoản từ bảng nguoidung
                $stmtNd = $db->prepare("SELECT hoten, email, sodienthoai FROM nguoidung WHERE id = :id LIMIT 1");
                $stmtNd->execute([':id' => $userId]);
                $nd = $stmtNd->fetch(PDO::FETCH_ASSOC) ?: [];

                $hoten = trim($nd['hoten'] ?? ($user['hoten'] ?? 'Quản Trị Viên'));
                $parts = explode(' ', $hoten);
                $ten = array_pop($parts) ?: 'Admin';
                $ho = !empty($parts) ? implode(' ', $parts) : 'Quản Trị';

                // Tạo mã NV tự động (NV01, NV02, ...)
                $stmtCount = $db->query("SELECT COUNT(*) FROM nhanvien");
                $nextNum = ((int)$stmtCount->fetchColumn()) + 1;
                $newMaNv = 'NV' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);
                while ($db->query("SELECT COUNT(*) FROM nhanvien WHERE ma_nv = " . $db->quote($newMaNv))->fetchColumn() > 0) {
                    $nextNum++;
                    $newMaNv = 'NV' . str_pad((string)$nextNum, 2, '0', STR_PAD_LEFT);
                }

                $email = !empty($nd['email']) ? trim($nd['email']) : 'admin' . $userId . '@domain.local';
                $sdt = !empty($nd['sodienthoai']) ? trim($nd['sodienthoai']) : null;
                $diaChi = 'Hệ thống Quản trị';

                // Kiểm tra xem email hoặc sdt có bị trùng lặp trong nhanvien không
                if ($sdt) {
                    $checkPhone = $db->prepare("SELECT COUNT(*) FROM nhanvien WHERE so_dien_thoai = :sdt");
                    $checkPhone->execute([':sdt' => $sdt]);
                    if ($checkPhone->fetchColumn() > 0) {
                        $sdt = null;
                    }
                }
                $checkEmail = $db->prepare("SELECT COUNT(*) FROM nhanvien WHERE email = :email");
                $checkEmail->execute([':email' => $email]);
                if ($checkEmail->fetchColumn() > 0) {
                    $email = 'admin' . $userId . '_' . time() . '@domain.local';
                }

                $stmtInsert = $db->prepare("
                    INSERT INTO nhanvien (ma_nv, nguoidung_id, ho, ten, gioi_tinh, ngay_sinh, dia_chi, so_dien_thoai, email)
                    VALUES (:ma_nv, :nd_id, :ho, :ten, 'Nam', '1990-01-01', :dia_chi, :sdt, :email)
                ");
                $stmtInsert->execute([
                    ':ma_nv'    => $newMaNv,
                    ':nd_id'    => $userId,
                    ':ho'       => $ho,
                    ':ten'      => $ten,
                    ':dia_chi'  => $diaChi,
                    ':sdt'      => $sdt,
                    ':email'    => $email
                ]);

                if (!isset($_SESSION['user'])) {
                    $_SESSION['user'] = [];
                }
                $_SESSION['user']['ma_nv'] = $newMaNv;
                return $newMaNv;
            } catch (Throwable $t) {
                // Nếu tạo thất bại, sẽ rơi xuống bước 4 lấy ma_nv đầu tiên
            }
        }

        // 4. Lấy ma_nv hợp lệ đầu tiên trong bảng nhanvien
        $stmtFirst = $db->query("SELECT ma_nv FROM nhanvien ORDER BY ma_nv ASC LIMIT 1");
        $firstMaNv = $stmtFirst->fetchColumn();
        if ($firstMaNv) {
            if (!isset($_SESSION['user'])) {
                $_SESSION['user'] = [];
            }
            $_SESSION['user']['ma_nv'] = (string)$firstMaNv;
            return (string)$firstMaNv;
        }

        throw new Exception("Không tìm thấy mã nhân viên hợp lệ trong hệ thống.");
    }

    /**
     * Tạo mới đợt khuyến mãi
     */
    public static function create(array $data, ?string $maNv = null, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $maKm = strtoupper(trim($data['ma_km']));

        if (self::checkIdExists($maKm, $db)) {
            throw new Exception("Mã khuyến mãi '{$maKm}' đã tồn tại.");
        }

        // Xác thực ma_nv hợp lệ trong bảng nhanvien
        $validMaNv = null;
        if (!empty($maNv)) {
            $stmtChk = $db->prepare("SELECT ma_nv FROM nhanvien WHERE ma_nv = :ma_nv LIMIT 1");
            $stmtChk->execute([':ma_nv' => trim($maNv)]);
            $validMaNv = $stmtChk->fetchColumn();
        }

        if (!$validMaNv) {
            $validMaNv = self::resolveEmployeeCode($db);
        }

        $sql = "INSERT INTO khuyenmai (ma_km, ly_do_km, ngay_bat_dau, ngay_ket_thuc, ma_nv) 
                VALUES (:ma_km, :ly_do_km, :ngay_bat_dau, :ngay_ket_thuc, :ma_nv)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ma_km'         => $maKm,
            ':ly_do_km'      => trim($data['ly_do_km']),
            ':ngay_bat_dau'  => $data['ngay_bat_dau'],
            ':ngay_ket_thuc' => $data['ngay_ket_thuc'],
            ':ma_nv'         => $validMaNv
        ]);
    }

    /**
     * Cập nhật thông tin đợt khuyến mãi
     */
    public static function update(string $maKm, array $data, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $sql = "UPDATE khuyenmai 
                SET ly_do_km = :ly_do_km, 
                    ngay_bat_dau = :ngay_bat_dau, 
                    ngay_ket_thuc = :ngay_ket_thuc 
                WHERE ma_km = :ma_km";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':ly_do_km'      => trim($data['ly_do_km']),
            ':ngay_bat_dau'  => $data['ngay_bat_dau'],
            ':ngay_ket_thuc' => $data['ngay_ket_thuc'],
            ':ma_km'         => $maKm
        ]);
    }

    /**
     * Xóa đợt khuyến mãi (xóa cascade ct_khuyenmai trước)
     */
    public static function delete(string $maKm): bool {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM ct_khuyenmai WHERE ma_km = :ma_km")->execute([':ma_km' => $maKm]);
            $stmt = $db->prepare("DELETE FROM khuyenmai WHERE ma_km = :ma_km");
            $stmt->execute([':ma_km' => $maKm]);
            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // =========================================================================
    // THAO TÁC VỚI BẢNG CHI TIẾT KHUYẾN MÃI (ct_khuyenmai)
    // =========================================================================

    /**
     * Lấy danh sách sản phẩm trong một đợt khuyến mãi kèm giá gốc và giá sau giảm
     */
    public static function getDetails(string $maKm, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT ct.ma_km, ct.ma_dh, ct.phan_tram_giam,
                       d.ten_dh, d.gia AS gia_goc, d.image, d.so_luong_ton,
                       ROUND(d.gia * (100 - ct.phan_tram_giam) / 100, 2) AS gia_sau_giam,
                       h.ten_hang, l.ten_loai
                FROM ct_khuyenmai ct
                JOIN dongho d ON ct.ma_dh = d.ma_dh
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE ct.ma_km = :ma_km
                ORDER BY ct.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_km' => $maKm]);
        return $stmt->fetchAll();
    }

    /**
     * Thêm sản phẩm vào đợt khuyến mãi (nếu đã có thì cập nhật mức % giảm mới)
     */
    public static function addOrUpdateProduct(string $maKm, string $maDh, float $phanTramGiam, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $maDh = strtoupper(trim($maDh));

        if ($phanTramGiam <= 0 || $phanTramGiam > 100) {
            throw new InvalidArgumentException("Phần trăm giảm giá phải lớn hơn 0% và không vượt quá 100%.");
        }

        // Kiểm tra đã có trong ct_khuyenmai chưa
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM ct_khuyenmai WHERE ma_km = :ma_km AND ma_dh = :ma_dh");
        $stmtCheck->execute([':ma_km' => $maKm, ':ma_dh' => $maDh]);
        $exists = ((int)$stmtCheck->fetchColumn() > 0);

        if ($exists) {
            $sql = "UPDATE ct_khuyenmai SET phan_tram_giam = :phan_tram WHERE ma_km = :ma_km AND ma_dh = :ma_dh";
        } else {
            $sql = "INSERT INTO ct_khuyenmai (ma_km, ma_dh, phan_tram_giam) VALUES (:ma_km, :ma_dh, :phan_tram)";
        }

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':phan_tram' => $phanTramGiam,
            ':ma_km'     => $maKm,
            ':ma_dh'     => $maDh
        ]);
    }

    /**
     * Xóa sản phẩm khỏi đợt khuyến mãi
     */
    public static function removeProduct(string $maKm, string $maDh, ?PDO $pdo = null): bool {
        $db = self::getPdo($pdo);
        $stmt = $db->prepare("DELETE FROM ct_khuyenmai WHERE ma_km = :ma_km AND ma_dh = :ma_dh");
        return $stmt->execute([
            ':ma_km' => $maKm,
            ':ma_dh' => $maDh
        ]);
    }

    /**
     * Lấy danh sách các mẫu đồng hồ chưa được thêm vào đợt khuyến mãi này (phục vụ chọn thêm)
     */
    public static function getAvailableWatches(string $maKm, ?PDO $pdo = null): array {
        $db = self::getPdo($pdo);
        $sql = "SELECT d.ma_dh, d.ten_dh, d.gia, d.so_luong_ton, h.ten_hang, l.ten_loai
                FROM dongho d
                JOIN hangdongho h ON d.ma_hang = h.ma_hang
                JOIN loaidongho l ON d.ma_loai = l.ma_loai
                WHERE d.ma_dh NOT IN (SELECT ma_dh FROM ct_khuyenmai WHERE ma_km = :ma_km)
                  AND d.status = 1
                ORDER BY d.ma_dh ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ma_km' => $maKm]);
        return $stmt->fetchAll();
    }
}

if (!class_exists('KhuyenMai', false)) {
    class_alias('KhuyenMaiModel', 'KhuyenMai');
}
