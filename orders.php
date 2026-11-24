<?php
/**
 * public/orders.php
 * Lịch sử đơn đặt hàng của khách hàng & Tra cứu Phiếu bảo hành điện tử
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem lịch sử đơn hàng.';
    header('Location: index.php?action=login');
    exit;
}

$db = Database::getConnection();
$userId = (int)$currentUser['id'];

// Lấy ma_kh
$maKh = (int)($currentUser['ma_kh'] ?? 0);
if ($maKh === 0) {
    $stmtKh = $db->prepare("SELECT ma_kh FROM khachhang WHERE nguoidung_id = :uid");
    $stmtKh->execute([':uid' => $userId]);
    $maKh = (int)$stmtKh->fetchColumn();
}

$orders = [];
if ($maKh > 0) {
    $orders = PhieuDatModel::getByCustomerId($maKh, $db);
}

// Kiểm tra ảnh đại diện
$userAvatar = trim((string)($currentUser['hinhanh'] ?? ''));
$hasUserAvatar = ($userAvatar !== '' && file_exists(__DIR__ . '/../' . $userAvatar));

$pageTitle = 'Lịch sử đơn mua của bạn - WatchStore';
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
                    <a href="index.php?action=addresses" class="list-group-item list-group-item-action">
                        <i class="bi bi-geo-alt me-2"></i> Sổ địa chỉ nhận hàng
                    </a>
                    <a href="index.php?action=orders" class="list-group-item list-group-item-action active fw-bold">
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

        <!-- Danh sách đơn hàng -->
        <div class="col-lg-8">
            <h4 class="fw-bold text-dark mb-4">
                <i class="bi bi-receipt-cutoff text-warning me-2"></i> Đơn Hàng Của Bạn (<?= count($orders) ?> đơn)
            </h4>

            <?php if (empty($orders)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                    <i class="bi bi-cart-x text-secondary opacity-50 display-3 mb-3"></i>
                    <h5 class="fw-bold text-dark">Bạn chưa có đơn đặt hàng nào</h5>
                    <p class="text-secondary small mb-4">Khám phá ngay các mẫu đồng hồ Pin chính hãng được ưa chuộng nhất hiện nay.</p>
                    <div>
                        <a href="index.php?action=home" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">
                            Mua sắm ngay
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $items = PhieuDatModel::getDetails((int)$order['ma_pd'], $db);
                        $status = $order['trang_thai'];
                        $badgeClass = match ($status) {
                            'Chờ duyệt' => 'bg-warning text-dark',
                            'Đã duyệt'  => 'bg-info text-white',
                            'Đang giao hàng' => 'bg-primary text-white',
                            'Đã phân công nhân viên giao hàng' => 'bg-primary text-white',
                            'Hoàn tất'  => 'bg-success text-white',
                            default     => 'bg-secondary text-white'
                        };
                        ?>
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <!-- Header đơn hàng -->
                            <div class="card-header bg-light border-0 py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="fw-bold text-dark me-2">Đơn hàng #<?= $order['ma_pd'] ?></span>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                                    <?php if ($status === 'Chờ duyệt'): ?>
                                        <a href="index.php?action=payment_qr&ma_pd=<?= $order['ma_pd'] ?>" class="btn btn-outline-primary btn-sm rounded-pill ms-2" title="Thanh toán chuyển khoản QR">
                                            <i class="bi bi-qr-code me-1"></i> Xem mã QR thanh toán
                                        </a>
                                    <?php endif; ?>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-clock me-1"></i> Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="text-secondary small">Tổng thanh toán:</span>
                                    <span class="text-danger fw-bold fs-5 d-block">
                                        <?= number_format($order['tong_tien'], 0, ',', '.') ?>đ
                                    </span>
                                </div>
                            </div>

                            <!-- Thân đơn hàng: Sản phẩm -->
                            <div class="card-body p-4">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-7">
                                        <h6 class="fw-bold text-dark mb-3">Sản phẩm đã đặt:</h6>
                                        <div class="d-flex flex-column gap-2">
                                            <?php foreach ($items as $item): ?>
                                                <div class="d-flex align-items-center justify-content-between p-2 border rounded-3 bg-light">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['ten_dh']) ?>" class="rounded border p-1 bg-white" style="width: 50px; height: 50px; object-fit: contain;">
                                                        <div>
                                                            <div class="fw-bold text-dark small"><?= htmlspecialchars($item['ten_dh']) ?></div>
                                                            <small class="text-muted">Đơn giá: <?= number_format($item['don_gia'], 0, ',', '.') ?>đ × <?= $item['so_luong'] ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="fw-bold text-dark small">
                                                        <?= number_format($item['thanh_tien'], 0, ',', '.') ?>đ
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <h6 class="fw-bold text-dark mb-3">Thông tin nhận hàng:</h6>
                                        <div class="p-3 border rounded-3 small bg-light">
                                            <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($order['ho_ten_nguoi_nhan']) ?></p>
                                            <p class="mb-1"><strong>Điện thoại:</strong> <?= htmlspecialchars($order['sdt_nguoi_nhan']) ?></p>
                                            <p class="mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['dia_chi_nguoi_nhan']) ?></p>
                                            <?php if (!empty($order['so_hoa_don'])): ?>
                                                <p class="mb-0 text-success fw-bold">
                                                    <i class="bi bi-receipt me-1"></i> Số Hóa đơn: <?= htmlspecialchars($order['so_hoa_don']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tra cứu Phiếu bảo hành nếu đã có Hóa đơn -->
                                <?php if (!empty($order['so_hoa_don'])): ?>
                                    <?php
                                    $warranties = PhieuBaoHanhModel::getBySoHoaDon($order['so_hoa_don'], $db);
                                    ?>
                                    <?php if (!empty($warranties)): ?>
                                        <div class="mt-3 pt-3 border-top">
                                            <h6 class="fw-bold text-primary mb-2">
                                                <i class="bi bi-shield-check me-1"></i> Phiếu Bảo Hành Điện Tử (<?= count($warranties) ?> chiếc)
                                            </h6>
                                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                                <?php foreach ($warranties as $w): ?>
                                                    <div class="col">
                                                        <div class="p-2 border rounded-3 bg-white d-flex align-items-center justify-content-between small">
                                                            <div>
                                                                <span class="badge bg-secondary"><?= htmlspecialchars($w['so_phieu_bh']) ?></span>
                                                                <span class="fw-bold text-dark ms-1"><?= htmlspecialchars($w['ten_dh']) ?></span>
                                                                <div class="text-muted mt-1" style="font-size: 0.78rem;">
                                                                    Từ: <?= date('d/m/Y', strtotime($w['ngay_bat_dau'])) ?> &rarr; Đến: <?= date('d/m/Y', strtotime($w['ngay_ket_thuc'])) ?>
                                                                </div>
                                                            </div>
                                                            <div class="text-end">
                                                                <?php if (!empty($w['con_han_bh'])): ?>
                                                                    <span class="badge bg-success">Còn BH</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Hết hạn</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
