<?php
/**
 * public/addresses.php
 * Quản lý sổ địa chỉ giao hàng của khách hàng
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem sổ địa chỉ.';
    header('Location: index.php?action=login');
    exit;
}

$db = Database::getConnection();
$userId = (int)$currentUser['id'];

$act = $_REQUEST['act'] ?? 'list';

// Đặt làm mặc định
if ($act === 'default') {
    $addrId = (int)($_GET['id'] ?? 0);
    if ($addrId > 0) {
        try {
            DiaChiModel::setDefault($addrId, $userId);
            $_SESSION['flash_success'] = 'Đã đặt địa chỉ làm mặc định!';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    header('Location: index.php?action=addresses');
    exit;
}

// Xóa địa chỉ
if ($act === 'delete') {
    $addrId = (int)($_GET['id'] ?? 0);
    if ($addrId > 0) {
        try {
            DiaChiModel::deleteAddress($addrId, $userId);
            $_SESSION['flash_success'] = 'Đã xóa địa chỉ khỏi danh bạ!';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }
    header('Location: index.php?action=addresses');
    exit;
}

// Thêm địa chỉ mới
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $act === 'add') {
    $detail = trim($_POST['dia_chi_chi_tiet'] ?? $_POST['diachi'] ?? '');
    $city = trim($_POST['tinh_thanh'] ?? '');
    $isDefault = !empty($_POST['mac_dinh']) || !empty($_POST['macdinh']);

    $fullAddress = $detail;
    if (!empty($city) && !str_contains($detail, $city)) {
        $fullAddress .= ', ' . $city;
    }

    if (!empty($fullAddress)) {
        try {
            DiaChiModel::addAddress($userId, $fullAddress, (bool)$isDefault);
            $_SESSION['flash_success'] = 'Đã thêm địa chỉ mới thành công!';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = 'Vui lòng điền đầy đủ các thông tin địa chỉ.';
    }
    header('Location: index.php?action=addresses');
    exit;
}

$addresses = DiaChiModel::getByNguoiDungId($userId);

// Kiểm tra ảnh đại diện
$userAvatar = trim((string)($currentUser['hinhanh'] ?? ''));
$hasUserAvatar = ($userAvatar !== '' && file_exists(__DIR__ . '/../' . $userAvatar));

$pageTitle = 'Sổ địa chỉ nhận hàng - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar tài khoản đồng bộ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center mb-4">
                <?php if ($hasUserAvatar): ?>
                    <img src="../<?= htmlspecialchars($userAvatar) ?>" alt="Avatar" class="rounded-circle border border-warning shadow-sm mx-auto mb-3" style="width: 110px; height: 110px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex p-3 mx-auto mb-3" style="width: 90px; height: 90px; align-items: center; justify-content: center;">
                        <i class="bi bi-person-circle display-4"></i>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($currentUser['hoten']) ?></h5>
                <p class="text-secondary small mb-1">
                    <?= ((int)$currentUser['loai'] === 1) ? 'Quản trị viên (Admin)' : (((int)$currentUser['loai'] === 2) ? 'Nhân viên cửa hàng' : 'Khách hàng thành viên') ?>
                </p>
                <?php if (!empty($currentUser['email'])): ?>
                    <p class="text-muted small mb-3"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($currentUser['email']) ?></p>
                <?php else: ?>
                    <div class="mb-3"></div>
                <?php endif; ?>

                <div class="list-group list-group-flush text-start small">
                    <a href="index.php?action=profile" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-gear me-2"></i> Thông tin cá nhân & Mật khẩu
                    </a>
                    <a href="index.php?action=addresses" class="list-group-item list-group-item-action active fw-bold">
                        <i class="bi bi-geo-alt me-2"></i> Sổ địa chỉ nhận hàng
                    </a>
                    <a href="index.php?action=orders" class="list-group-item list-group-item-action">
                        <i class="bi bi-receipt me-2"></i> Lịch sử đơn mua & Bảo hành
                    </a>
                    <?php if (in_array((int)($currentUser['loai'] ?? 0), [1, 2], true)): ?>
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

        <!-- Danh sách địa chỉ -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark mb-0">Sổ Địa Chỉ Của Bạn</h4>
                <button type="button" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark" data-bs-toggle="collapse" data-bs-target="#addAddressForm">
                    <i class="bi bi-plus-lg me-1"></i> Thêm Địa Chỉ Mới
                </button>
            </div>

            <!-- Form thêm địa chỉ mới (collapsible) -->
            <div class="collapse mb-4" id="addAddressForm">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thêm Địa Chỉ Nhận Hàng Mới</h5>
                    <form method="POST" action="index.php?action=addresses&act=add">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Họ tên người nhận <span class="text-danger">*</span></label>
                                <input type="text" name="ho_ten_nguoi_nhan" class="form-control" required value="<?= htmlspecialchars($currentUser['hoten']) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" name="so_dien_thoai" class="form-control" required value="<?= htmlspecialchars($currentUser['sodienthoai'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Địa chỉ chi tiết (Số nhà, đường, phường, quận) <span class="text-danger">*</span></label>
                            <input type="text" name="dia_chi_chi_tiet" class="form-control" required placeholder="123 Lê Lợi, Phường Bến Nghé, Quận 1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tỉnh / Thành phố</label>
                            <input type="text" name="tinh_thanh" class="form-control" value="TP. Hồ Chí Minh">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="mac_dinh" id="checkDefault" value="1" checked>
                            <label class="form-check-label" for="checkDefault">Đặt làm địa chỉ nhận hàng mặc định</label>
                        </div>
                        <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark px-4">
                            Lưu Địa Chỉ
                        </button>
                    </form>
                </div>
            </div>

            <!-- Danh sách địa chỉ đã lưu -->
            <?php if (empty($addresses)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <i class="bi bi-geo-alt text-secondary opacity-50 display-3 mb-3"></i>
                    <h5 class="fw-bold text-dark">Chưa có địa chỉ nào trong sổ tay</h5>
                    <p class="text-secondary small">Vui lòng thêm địa chỉ nhận hàng để tiện cho việc đặt hàng nhanh chóng.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($addresses as $addr): 
                        $isDefault = !empty($addr['macdinh']) || !empty($addr['mac_dinh']);
                        $recipientName = !empty($addr['ho_ten_nguoi_nhan']) ? $addr['ho_ten_nguoi_nhan'] : ($currentUser['hoten'] ?? 'Người nhận');
                        $recipientPhone = !empty($addr['so_dien_thoai']) ? $addr['so_dien_thoai'] : ($currentUser['sodienthoai'] ?? '');
                        $addressText = $addr['diachi'] ?? $addr['dia_chi_chi_tiet'] ?? '';
                    ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white <?= $isDefault ? 'border border-2 border-warning' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($recipientName) ?></h6>
                                            <?php if (!empty($recipientPhone)): ?>
                                                <span class="text-muted">|</span>
                                                <span class="text-muted small"><?= htmlspecialchars($recipientPhone) ?></span>
                                            <?php endif; ?>
                                            <?php if ($isDefault): ?>
                                                <span class="badge bg-warning text-dark fw-bold">Mặc định</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-secondary mb-1 small"><?= htmlspecialchars($addressText) ?></p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if (!$isDefault): ?>
                                            <a href="index.php?action=addresses&act=default&id=<?= $addr['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                                                Đặt mặc định
                                            </a>
                                        <?php endif; ?>
                                        <a href="index.php?action=addresses&act=delete&id=<?= $addr['id'] ?>" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có chắc muốn xóa địa chỉ này?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
