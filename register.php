<?php
/**
 * public/register.php
 * Đăng ký tài khoản Khách hàng mới (loai = 3)
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user'])) {
    header('Location: index.php?action=home');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho = trim($_POST['ho'] ?? '');
    $ten = trim($_POST['ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['sodienthoai'] ?? '');
    $password = $_POST['matkhau'] ?? '';
    $confirmPassword = $_POST['matkhau_xacnhan'] ?? '';
    $diaChi = trim($_POST['dia_chi'] ?? '');

    if (empty($ho) || empty($ten)) {
        $errors[] = 'Họ và tên không được để trống.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (empty($phone) || !preg_match('/^[0-9]{9,11}$/', $phone)) {
        $errors[] = 'Số điện thoại không hợp lệ (9 - 11 số).';
    }
    if (strlen($password) < 3) {
        $errors[] = 'Mật khẩu phải có ít nhất 3 ký tự.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Mật khẩu xác nhận không khớp.';
    }

    if (empty($errors)) {
        $db = Database::getConnection();

        // Kiểm tra email hoặc phone đã tồn tại
        $stmtCheck = $db->prepare("SELECT id FROM nguoidung WHERE email = :email OR sodienthoai = :phone");
        $stmtCheck->execute([':email' => $email, ':phone' => $phone]);
        if ($stmtCheck->fetch()) {
            $errors[] = 'Email hoặc Số điện thoại này đã được đăng ký trên hệ thống.';
        } else {
            $db->beginTransaction();
            try {
                $hoTen = trim("{$ho} {$ten}");
                $hashedPass = md5($password);

                // 1. Thêm vào nguoidung
                $stmtUser = $db->prepare("
                    INSERT INTO nguoidung (email, sodienthoai, matkhau, hoten, loai, trangthai)
                    VALUES (:email, :phone, :pass, :hoten, 3, 1)
                ");
                $stmtUser->execute([
                    ':email' => $email,
                    ':phone' => $phone,
                    ':pass'  => $hashedPass,
                    ':hoten' => $hoTen
                ]);
                $userId = (int)$db->lastInsertId();

                // 2. Thêm vào khachhang (cmnd = NULL khi đăng ký, bổ sung sau ở Hồ sơ)
                $stmtKh = $db->prepare("
                    INSERT INTO khachhang (ho, ten, cmnd, dia_chi, so_dien_thoai, email, nguoidung_id)
                    VALUES (:ho, :ten, NULL, :diachi, :phone, :email, :uid)
                ");
                $stmtKh->execute([
                    ':ho'     => $ho,
                    ':ten'    => $ten,
                    ':diachi' => $diaChi,
                    ':phone'  => $phone,
                    ':email'  => $email,
                    ':uid'    => $userId
                ]);
                $maKh = (int)$db->lastInsertId();

                // 3. Nếu có địa chỉ, thêm vào diachi mặc định
                if (!empty($diaChi)) {
                    $stmtAddr = $db->prepare("
                        INSERT INTO diachi (nguoidung_id, diachi, macdinh)
                        VALUES (:uid, :diachi, 1)
                    ");
                    $stmtAddr->execute([
                        ':uid'    => $userId,
                        ':diachi' => $diaChi
                    ]);
                }

                $db->commit();

                // Tự động đăng nhập
                $_SESSION['user'] = [
                    'id'          => $userId,
                    'email'       => $email,
                    'sodienthoai' => $phone,
                    'hoten'       => $hoTen,
                    'loai'        => 3,
                    'ma_kh'       => $maKh,
                    'hinhanh'     => null
                ];

                $_SESSION['flash_success'] = "Đăng ký tài khoản thành công! Chào mừng bạn gia nhập WatchStore.";
                header('Location: index.php?action=home');
                exit;
            } catch (Throwable $e) {
                $db->rollBack();
                $errors[] = 'Lỗi hệ thống khi đăng ký: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Đăng ký tài khoản - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                <div class="text-center mb-4">
                    <h4 class="fw-bold text-dark">Đăng Ký Tài Khoản Khách Hàng</h4>
                    <p class="text-secondary small">Trở thành thành viên để nhận bảo hành 24 tháng và ưu đãi giảm giá</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?action=register">
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Họ đệm <span class="text-danger">*</span></label>
                            <input type="text" name="ho" class="form-control" placeholder="Nguyễn" required value="<?= htmlspecialchars($_POST['ho'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Tên <span class="text-danger">*</span></label>
                            <input type="text" name="ten" class="form-control" placeholder="Văn A" required value="<?= htmlspecialchars($_POST['ten'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                        <input type="tel" name="sodienthoai" class="form-control" placeholder="0901234567" required value="<?= htmlspecialchars($_POST['sodienthoai'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ giao hàng mặc định</label>
                        <input type="text" name="dia_chi" class="form-control" placeholder="Số 123 Đường ABC, Quận X, TP.HCM" value="<?= htmlspecialchars($_POST['dia_chi'] ?? '') ?>">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="matkhau" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="matkhau_xacnhan" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2 rounded-pill text-dark shadow-sm mb-3">
                        <i class="bi bi-person-plus me-1"></i> HOÀN TẤT ĐĂNG KÝ
                    </button>

                    <div class="text-center text-secondary small">
                        Đã có tài khoản? 
                        <a href="index.php?action=login" class="fw-bold text-warning text-decoration-none">Đăng nhập</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
