<?php
/**
 * public/login.php
 * Đăng nhập đa vai trò: Quản trị viên (loai = 1), Nhân viên (loai = 2), Khách hàng (loai = 3)
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập thì chuyển hướng
if (!empty($_SESSION['user'])) {
    if (in_array((int)$_SESSION['user']['loai'], [1, 2], true)) {
        header('Location: ../admin/index.php');
    } else {
        header('Location: index.php?action=home');
    }
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($login) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ Email/Số điện thoại và Mật khẩu.';
    } else {
        $user = NguoiDungModel::findByLogin($login);

        if (!$user) {
            $error = 'Tài khoản không tồn tại trong hệ thống.';
        } elseif ((int)$user['trangthai'] !== 1) {
            $error = 'Tài khoản của bạn đang bị tạm khóa hoặc chưa được kích hoạt.';
        } elseif (md5($password) !== $user['matkhau']) {
            $error = 'Mật khẩu đăng nhập không chính xác.';
        } else {
            // Xác thực thành công: Chuẩn bị session
            $userData = [
                'id'          => (int)$user['id'],
                'email'       => $user['email'],
                'sodienthoai' => $user['sodienthoai'],
                'hoten'       => $user['hoten'],
                'loai'        => (int)$user['loai'],
                'hinhanh'     => $user['hinhanh']
            ];

            // Ánh xạ vai trò
            if ((int)$user['loai'] === 1) {
                // Admin
                $userData['is_admin'] = true;
                $nv = NhanVienModel::findByNguoiDungId((int)$user['id']);
                $userData['ma_nv'] = $nv['ma_nv'] ?? 'ADMIN';
            } elseif ((int)$user['loai'] === 2) {
                // Nhân viên
                $nv = NhanVienModel::findByNguoiDungId((int)$user['id']);
                $userData['ma_nv'] = $nv['ma_nv'] ?? 'NV01';
            } elseif ((int)$user['loai'] === 3) {
                // Khách hàng
                $kh = KhachHangModel::findByNguoiDungId((int)$user['id']);
                $userData['ma_kh'] = $kh ? (int)$kh['ma_kh'] : 0;
            }

            $_SESSION['user'] = $userData;
            $_SESSION['flash_success'] = "Chào mừng trở lại, {$user['hoten']}!";

            $returnUrl = $_SESSION['return_url'] ?? null;
            unset($_SESSION['return_url']);

            if (!empty($returnUrl)) {
                header("Location: {$returnUrl}");
            } elseif (in_array((int)$user['loai'], [1, 2], true)) {
                header('Location: ../admin/index.php');
            } else {
                header('Location: index.php?action=home');
            }
            exit;
        }
    }
}

$pageTitle = 'Đăng nhập - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex p-3 mb-2">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Đăng Nhập Tài Khoản</h4>
                    <p class="text-secondary small">Hệ thống phân quyền Khách hàng & Quản trị</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=login">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email hoặc Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" name="login" class="form-control" placeholder="admin@watchstore.vn / 0901..." required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-pill text-dark shadow-sm mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> ĐĂNG NHẬP
                    </button>

                    <div class="text-center text-secondary small">
                        Chưa có tài khoản khách hàng? 
                        <a href="index.php?action=register" class="fw-bold text-warning text-decoration-none">Đăng ký ngay</a>
                    </div>
                </form>

               
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
