<?php
/**
 * admin/inc/auth_middleware.php
 * Middleware kiểm soát quyền truy cập và phân quyền / ủy quyền giữa
 * Quản trị viên (Admin - loai = 1) và Nhân viên (Staff - loai = 2)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../model/index.php';

/**
 * Kiểm tra người dùng có phải là Quản trị viên (loai = 1) hoặc Nhân viên (loai = 2) đang hoạt động.
 * Đồng thời tự động cập nhật lại thông tin mới nhất từ CSDL vào $_SESSION['user'].
 */
function requireAdminOrStaff(): void {
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
        $_SESSION['flash_error'] = 'Vui lòng đăng nhập để truy cập khu vực Quản trị.';
        header('Location: ../public/index.php?action=login');
        exit;
    }

    $userId = (int)$_SESSION['user']['id'];

    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, email, sodienthoai, hoten, loai, trangthai, hinhanh FROM nguoidung WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $userDb = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra nếu tài khoản không tồn tại, bị khóa (trangthai = 0) hoặc bị hạ quyền (loai = 3)
        if (!$userDb || (int)$userDb['trangthai'] !== 1 || !in_array((int)$userDb['loai'], [1, 2], true)) {
            unset($_SESSION['user']);
            $_SESSION['flash_error'] = 'Tài khoản của bạn đã bị khóa hoặc không có quyền truy cập khu vực Quản trị.';
            header('Location: ../public/index.php?action=login');
            exit;
        }

        // Đồng bộ lại loai, hoten, hinhanh từ CSDL vào $_SESSION['user']
        $_SESSION['user']['loai'] = (int)$userDb['loai'];
        $_SESSION['user']['hoten'] = $userDb['hoten'];
        $_SESSION['user']['hinhanh'] = $userDb['hinhanh'];
        if (!empty($userDb['email'])) {
            $_SESSION['user']['email'] = $userDb['email'];
        }
        if (!empty($userDb['sodienthoai'])) {
            $_SESSION['user']['sodienthoai'] = $userDb['sodienthoai'];
        }

        // Đảm bảo ma_nv trong session luôn đồng bộ với bảng nhanvien
        if (empty($_SESSION['user']['ma_nv'])) {
            $stmtNv = $db->prepare("SELECT ma_nv FROM nhanvien WHERE nguoidung_id = :nd_id LIMIT 1");
            $stmtNv->execute([':nd_id' => $userId]);
            $maNv = $stmtNv->fetchColumn();
            if ($maNv) {
                $_SESSION['user']['ma_nv'] = (string)$maNv;
            }
        }
    } catch (Throwable $e) {
        // Nếu có sự cố kết nối, vẫn giữ phiên hiện tại nếu hợp lệ
        if (!isset($_SESSION['user']['loai']) || !in_array((int)$_SESSION['user']['loai'], [1, 2], true)) {
            unset($_SESSION['user']);
            $_SESSION['flash_error'] = 'Lỗi xác thực phiên làm việc: ' . $e->getMessage();
            header('Location: ../public/index.php?action=login');
            exit;
        }
    }
}

/**
 * Kiểm tra xem người dùng hiện tại có phải là Quản trị viên (Admin - loai = 1) hay không.
 */
function isAdmin(): bool {
    return isset($_SESSION['user']['loai']) && (int)$_SESSION['user']['loai'] === 1;
}

/**
 * Kiểm tra quyền truy cập vào chức năng (action) và thao tác cụ thể (act).
 * - Admin (loai = 1): Toàn quyền truy cập.
 * - Nhân viên (loai = 2):
 *   + Được ủy quyền: dashboard, danhmuc (xem/thêm/sửa), sanpham/dongho (xem/thêm/sửa), nhaphang, donhang, hoadon, baohanh, trahang, profile.
 *   + Chặn: các module quản trị cốt lõi ('nhanvien', 'nguoidung', 'gia', 'khuyenmai') và mọi thao tác xóa ($act bắt đầu bằng 'delete' hoặc 'xoa').
 */
function canAccessAction(string $action, string $act = ''): bool {
    if (isAdmin()) {
        return true;
    }

    $userLoai = (int)($_SESSION['user']['loai'] ?? 0);
    if ($userLoai !== 2) {
        return false;
    }

    $actionLower = strtolower(trim($action));
    $actLower = strtolower(trim($act));

    // 1. Chặn mọi thao tác xóa dữ liệu (bắt đầu bằng 'delete' hoặc 'xoa')
    if ($actLower !== '' && (strpos($actLower, 'delete') === 0 || strpos($actLower, 'xoa') === 0 || strpos($actLower, 'remove') === 0)) {
        return false;
    }

    // 2. Chặn các phân hệ quản trị cốt lõi
    $blockedActions = ['nhanvien', 'nguoidung', 'gia', 'khuyenmai'];
    if (in_array($actionLower, $blockedActions, true)) {
        return false;
    }

    // Chặn xem lịch sử bảng giá nếu action là dongho và act là price_history
    if (($actionLower === 'dongho' || $actionLower === 'sanpham') && $actLower === 'price_history') {
        return false;
    }

    // 3. Danh sách phân hệ được ủy quyền vận hành
    $allowedActions = [
        'dashboard',
        'danhmuc',
        'sanpham',
        'dongho',
        'nhaphang',
        'donhang',
        'hoadon',
        'baohanh',
        'trahang',
        'profile'
    ];

    return in_array($actionLower, $allowedActions, true);
}

/**
 * Bắt buộc kiểm tra quyền truy cập. Nếu không đủ quyền, hiển thị thông báo flash và chuyển hướng về dashboard.
 */
function enforcePermission(string $action, string $act = ''): void {
    requireAdminOrStaff();

    if (!canAccessAction($action, $act)) {
        $_SESSION['flash_error'] = 'Chức năng này chỉ dành cho Quản trị viên (Admin). Bạn chưa được ủy quyền thực hiện.';
        header('Location: index.php?action=dashboard');
        exit;
    }
}
