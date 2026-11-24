<?php
/**
 * public/profile.php
 * Quản lý hồ sơ cá nhân, cập nhật ảnh đại diện và đổi mật khẩu
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem thông tin hồ sơ.';
    header('Location: index.php?action=login');
    exit;
}

$db = Database::getConnection();
$userId = (int)$currentUser['id'];

$user = NguoiDungModel::findById($userId);
if (!$user) {
    $_SESSION['flash_error'] = 'Không tìm thấy người dùng.';
    header('Location: index.php?action=logout');
    exit;
}

// Lấy thông tin khách hàng (cmnd, ma_so_thue) nếu loại = 3
$khachHang = null;
if ((int)$user['loai'] === 3) {
    $khachHang = KhachHangModel::findByNguoiDungId($userId);
}

$successMsg = null;
$errorMsg = null;

// ==========================================
// Xử lý cập nhật ẢNH ĐẠI DIỆN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        // Kiểm tra MIME type thực tế
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes, true)) {
            $errorMsg = 'Định dạng ảnh không hỗ trợ. Chỉ chấp nhận JPG, PNG, GIF, WebP.';
        } elseif ($file['size'] > $maxSize) {
            $errorMsg = 'Kích thước ảnh vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn.';
        } else {
            // Tạo tên file duy nhất
            $ext = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };
            $newFilename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $uploadDir = __DIR__ . '/../images/avatars/';
            $uploadPath = $uploadDir . $newFilename;
            $dbPath = 'images/avatars/' . $newFilename;

            // Đảm bảo thư mục tồn tại
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ nếu có (không xóa ảnh mặc định)
                $oldAvatar = $user['hinhanh'] ?? '';
                if (!empty($oldAvatar) && strpos($oldAvatar, 'images/avatars/') !== false) {
                    $oldPath = __DIR__ . '/../' . $oldAvatar;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // Lưu đường dẫn vào CSDL
                NguoiDungModel::updateAvatar($userId, $dbPath);

                // Cập nhật session
                $_SESSION['user']['hinhanh'] = $dbPath;
                $user['hinhanh'] = $dbPath;

                $successMsg = 'Cập nhật ảnh đại diện thành công!';
            } else {
                $errorMsg = 'Không thể lưu ảnh. Vui lòng thử lại.';
            }
        }
    } elseif (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
        // Xóa ảnh đại diện
        $oldAvatar = $user['hinhanh'] ?? '';
        if (!empty($oldAvatar) && strpos($oldAvatar, 'images/avatars/') !== false) {
            $oldPath = __DIR__ . '/../' . $oldAvatar;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
        NguoiDungModel::updateAvatar($userId, null);
        $_SESSION['user']['hinhanh'] = null;
        $user['hinhanh'] = null;
        $successMsg = 'Đã xóa ảnh đại diện.';
    } else {
        $errorMsg = 'Vui lòng chọn một ảnh để tải lên.';
    }
}

// ==========================================
// Cập nhật thông tin cá nhân
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['sodienthoai'] ?? '');
 

    if (empty($hoten)) {
        $errorMsg = 'Họ tên không được để trống.';
    } elseif (empty($phone) || !preg_match('/^[0-9]{9,11}$/', $phone)) {
        $errorMsg = 'Số điện thoại không hợp lệ.';
   
    } else {
        // Kiểm tra trùng lặp email/phone với tài khoản khác
        $stmtChk = $db->prepare("SELECT id FROM nguoidung WHERE (email = :email OR sodienthoai = :phone) AND id != :uid");
        $stmtChk->execute([':email' => $email, ':phone' => $phone, ':uid' => $userId]);
        if ($stmtChk->fetch()) {
            $errorMsg = 'Email hoặc Số điện thoại đã được tài khoản khác sử dụng.';
        } else {
            try {
                $stmtUpd = $db->prepare("UPDATE nguoidung SET hoten = :hoten, email = :email, sodienthoai = :phone WHERE id = :uid");
                $stmtUpd->execute([
                    ':hoten' => $hoten,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':uid'   => $userId
                ]);

                // Tự động đồng bộ họ tên sang bảng nhanvien nếu là Quản trị viên (1) hoặc Nhân viên (2)
                if (in_array((int)$user['loai'], [1, 2], true)) {
                    $parts = explode(' ', trim($hoten));
                    if (count($parts) > 1) {
                        $ten = array_pop($parts);
                        $ho = implode(' ', $parts);
                    } else {
                        $ho = '';
                        $ten = $parts[0] ?? '';
                    }
                    $stmtUpdNv = $db->prepare("UPDATE nhanvien SET ho = :ho, ten = :ten WHERE nguoidung_id = :uid");
                    $stmtUpdNv->execute([
                        ':ho'  => $ho,
                        ':ten' => $ten,
                        ':uid' => $userId
                    ]);
                }

                // Cập nhật luôn thông tin khachhang nếu là khách
                if ((int)$user['loai'] === 3 && $khachHang) {
                    $hoTenParts = explode(' ', $hoten);
                    $ho = $hoTenParts[0] ?? '';
                    $ten = end($hoTenParts) ?? '';
                

                    KhachHangModel::updateProfile(
                        (int)$khachHang['ma_kh'],
                        $ho,
                        $ten,   
                        $phone,
                        $email
                       
                    );
                }

                $_SESSION['user']['hoten'] = $hoten;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['sodienthoai'] = $phone;
                $user = NguoiDungModel::findById($userId);
                $khachHang = ((int)$user['loai'] === 3) ? KhachHangModel::findByNguoiDungId($userId) : null;
                $successMsg = 'Cập nhật thông tin cá nhân thành công!';
            } catch (Throwable $e) {
                $errorMsg = $e->getMessage();
            }
        }
    }
}

// ==========================================
// Đổi mật khẩu
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';

    if (empty($oldPass) || empty($newPass)) {
        $errorMsg = 'Vui lòng nhập đầy đủ mật khẩu cũ và mới.';
    } elseif (md5($oldPass) !== $user['matkhau']) {
        $errorMsg = 'Mật khẩu hiện tại không chính xác.';
    } elseif (strlen($newPass) < 3) {
        $errorMsg = 'Mật khẩu mới phải có ít nhất 3 ký tự.';
    } elseif ($newPass !== $confPass) {
        $errorMsg = 'Xác nhận mật khẩu mới không khớp.';
    } else {
        $stmtUpdPass = $db->prepare("UPDATE nguoidung SET matkhau = :pass WHERE id = :uid");
        $stmtUpdPass->execute([
            ':pass' => md5($newPass),
            ':uid'  => $userId
        ]);
        $successMsg = 'Đổi mật khẩu thành công!';
    }
}

// Xác định ảnh đại diện hiển thị
$avatarSrc = null;
if (!empty($user['hinhanh'])) {
    $avatarFile = __DIR__ . '/../' . $user['hinhanh'];
    if (file_exists($avatarFile)) {
        $avatarSrc = '../' . $user['hinhanh'];
    }
}

$pageTitle = 'Hồ sơ cá nhân - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<style>
    .avatar-wrapper {
        position: relative;
        width: 130px;
        height: 130px;
        margin: 0 auto;
    }
    .avatar-img {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fde68a;
        box-shadow: 0 8px 24px rgba(217, 119, 6, 0.18);
        transition: all 0.3s ease;
        background-color: #f8fafc;
    }
    .avatar-placeholder {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        border: 4px dashed #d97706;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #fffbeb, #fef3c7);
        color: #d97706;
        transition: all 0.3s ease;
    }
    .avatar-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.55);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.25s ease;
        cursor: pointer;
    }
    .avatar-wrapper:hover .avatar-overlay {
        opacity: 1;
    }
    .avatar-wrapper:hover .avatar-img,
    .avatar-wrapper:hover .avatar-placeholder {
        transform: scale(1.03);
    }
    .avatar-upload-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 10;
        border-radius: 50%;
    }
    .avatar-preview-modal .modal-body img {
        max-width: 100%;
        max-height: 350px;
        object-fit: contain;
        border-radius: 12px;
    }
</style>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar tài khoản -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center mb-4">
                <!-- Ảnh Đại Diện -->
                <form method="POST" action="index.php?action=profile" enctype="multipart/form-data" id="avatarForm">
                    <input type="hidden" name="update_avatar" value="1">
                    <div class="avatar-wrapper mb-3">
                        <?php if ($avatarSrc): ?>
                            <img src="<?= htmlspecialchars($avatarSrc) ?>" alt="Ảnh đại diện" class="avatar-img" id="avatarPreview">
                        <?php else: ?>
                            <div class="avatar-placeholder" id="avatarPlaceholder">
                                <i class="bi bi-person-fill display-3"></i>
                            </div>
                        <?php endif; ?>
                        <div class="avatar-overlay">
                            <i class="bi bi-camera-fill text-white fs-4 mb-1"></i>
                            <span class="text-white small fw-semibold">Đổi ảnh</span>
                        </div>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" 
                               class="avatar-upload-input" id="avatarInput" title="Chọn ảnh đại diện">
                    </div>
                </form>

                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['hoten']) ?></h5>
                <p class="text-secondary small mb-1">
                    <?= ((int)$user['loai'] === 1) ? 'Quản trị viên (Admin)' : (((int)$user['loai'] === 2) ? 'Nhân viên cửa hàng' : 'Khách hàng thành viên') ?>
                </p>
                <?php if (!empty($user['email'])): ?>
                    <p class="text-muted small mb-3"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($user['email']) ?></p>
                <?php endif; ?>

                <?php if ($avatarSrc): ?>
                    <form method="POST" action="index.php?action=profile" class="mb-3">
                        <input type="hidden" name="update_avatar" value="1">
                        <input type="hidden" name="remove_avatar" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                                onclick="return confirm('Bạn có chắc muốn xóa ảnh đại diện?')">
                            <i class="bi bi-trash3 me-1"></i> Xóa ảnh đại diện
                        </button>
                    </form>
                <?php endif; ?>

                <div class="list-group list-group-flush text-start small">
                    <a href="index.php?action=profile" class="list-group-item list-group-item-action active fw-bold">
                        <i class="bi bi-person-gear me-2"></i> Thông tin cá nhân & Mật khẩu
                    </a>
                    <a href="index.php?action=addresses" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2"></i> Sổ địa chỉ nhận hàng
                    </a>
                    <a href="index.php?action=orders" class="list-group-item list-group-item-action">
                        <i class="bi bi-receipt me-2"></i> Lịch sử đơn mua & Bảo hành
                    </a>
                    <?php if (in_array((int)$user['loai'], [1, 2], true)): ?>
                        <a href="../admin/index.php" class="list-group-item list-group-item-action text-primary fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i> Vào trang Quản trị (Admin)
                        </a>
                    <?php endif; ?>
                    <a href="index.php?action=logout" class="list-group-item list-group-item-action text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Form chỉnh sửa thông tin -->
        <div class="col-lg-8">
            <?php if ($successMsg): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($successMsg) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($errorMsg) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
                    <i class="bi bi-person-lines-fill text-warning me-2"></i> Cập Nhật Thông Tin Cá Nhân
                </h5>
                <form method="POST" action="index.php?action=profile">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên</label>
                        <input type="text" name="hoten" class="form-control" required value="<?= htmlspecialchars($user['hoten']) ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Số điện thoại</label>
                            <input type="tel" name="sodienthoai" class="form-control" required value="<?= htmlspecialchars($user['sodienthoai'] ?? '') ?>">
                        </div>
                    </div>

                  
                    
                    <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Cập Nhật

                    </button>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-3 mb-4">
                    <i class="bi bi-key-fill text-warning me-2"></i> Đổi Mật Khẩu
                </h5>
                <form method="POST" action="index.php?action=profile">
                    <input type="hidden" name="change_password" value="1">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
                        <input type="password" name="old_password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark rounded-pill fw-bold px-4">
                        <i class="bi bi-arrow-repeat me-1"></i> Đổi Mật Khẩu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem trước ảnh trước khi upload -->
<div class="modal fade avatar-preview-modal" id="avatarPreviewModal" tabindex="-1" aria-labelledby="avatarPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold" id="avatarPreviewModalLabel">
                    <i class="bi bi-image text-warning me-2"></i>Xem trước ảnh đại diện
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <img src="" alt="Xem trước" id="avatarModalImg" class="shadow-sm">
                <div class="mt-3">
                    <p class="text-muted small mb-0" id="avatarFileInfo"></p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal" id="avatarCancelBtn">
                    <i class="bi bi-x-lg me-1"></i> Hủy
                </button>
                <button type="button" class="btn btn-warning rounded-pill fw-bold text-dark px-4 shadow-sm" id="avatarConfirmBtn">
                    <i class="bi bi-cloud-upload me-1"></i> Tải lên ảnh này
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('avatarInput');
    const form = document.getElementById('avatarForm');
    const modalEl = document.getElementById('avatarPreviewModal');
    const modalImg = document.getElementById('avatarModalImg');
    const fileInfo = document.getElementById('avatarFileInfo');
    const confirmBtn = document.getElementById('avatarConfirmBtn');
    const cancelBtn = document.getElementById('avatarCancelBtn');

    if (!fileInput || !form || !modalEl) return;

    const modal = new bootstrap.Modal(modalEl);

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Validate phía client
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Định dạng ảnh không hỗ trợ. Chỉ chấp nhận JPG, PNG, GIF, WebP.');
            this.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Kích thước ảnh vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn.');
            this.value = '';
            return;
        }

        // Hiển thị modal xem trước
        const reader = new FileReader();
        reader.onload = function (e) {
            modalImg.src = e.target.result;
            const sizeKB = (file.size / 1024).toFixed(1);
            const sizeMB = (file.size / 1024 / 1024).toFixed(2);
            const sizeText = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
            fileInfo.textContent = file.name + ' — ' + sizeText;
            modal.show();
        };
        reader.readAsDataURL(file);
    });

    // Khi nhấn Tải lên → submit form
    confirmBtn.addEventListener('click', function () {
        modal.hide();
        form.submit();
    });

    // Khi hủy → reset input
    cancelBtn.addEventListener('click', function () {
        fileInput.value = '';
    });

    // Khi đóng modal bằng nút X hoặc bấm ngoài → cũng reset input
    modalEl.addEventListener('hidden.bs.modal', function () {
        // Chỉ reset nếu form chưa được submit
        if (fileInput.value) {
            fileInput.value = '';
        }
    });
});
</script>

<?php require __DIR__ . '/inc/bottom.php'; ?>
