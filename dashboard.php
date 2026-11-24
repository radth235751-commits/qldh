<?php
/**
 * admin/dashboard.php
 * Bảng điều khiển quản trị: Thống kê doanh thu, tồn kho, trạng thái đơn hàng & cảnh báo nhập kho
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Bảng điều khiển Quản trị';
require __DIR__ . '/inc/header.php';

$db = Database::getConnection();

// Lấy số liệu thống kê tổng quan
$stats = ThongKeModel::getOverviewStats($db);
$ordersByStatus = ThongKeModel::getOrdersCountByStatus($db);
$lowStockWatches = ThongKeModel::getLowStockWatches(5, $db);

// Lấy 5 đơn đặt hàng mới nhất
$stmtRecent = $db->query("
    SELECT pd.ma_pd, pd.ngay_dat, pd.ho_ten_nguoi_nhan, pd.sdt_nguoi_nhan, pd.trang_thai,
           COALESCE((SELECT SUM(so_luong * don_gia) FROM ct_phieudat WHERE ma_pd = pd.ma_pd), 0) AS tong_tien
    FROM phieudat pd
    ORDER BY pd.ngay_dat DESC
    LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();

$statusBadges = [
    'Chờ duyệt' => 'bg-warning text-dark',
    'Đã duyệt' => 'bg-info text-white',
    'Đang giao hàng' => 'bg-primary text-white',
    'Đã phân công nhân viên giao hàng' => 'bg-primary text-white',
    'Hoàn tất' => 'bg-success text-white',
    'Đã hủy' => 'bg-danger text-white'
];
?>

<!-- Hàng Thẻ Thống Kê Tổng Quan -->
<div class="row g-3 mb-4">
    <!-- Doanh thu -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Tổng Doanh Thu</span>
                <div class="rounded-circle p-2 bg-success-subtle text-success">
                    <i class="bi bi-currency-dollar fs-4"></i>
                </div>
            </div>
            <?php if (isAdmin()): ?>
                <h3 class="fw-bold text-dark mb-1">
                    <?= number_format((float)($stats['tong_doanh_thu'] ?? 0), 0, ',', '.') ?>đ
                </h3>
                <span class="text-success small"><i class="bi bi-graph-up-arrow me-1"></i>Từ các hóa đơn hợp lệ</span>
            <?php else: ?>
                <h4 class="fw-bold text-muted mb-1">Chỉ Admin xem</h4>
                <span class="text-secondary small"><i class="bi bi-shield-lock me-1"></i>Chưa được ủy quyền</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tổng đơn hàng -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Đơn Đặt Hàng</span>
                <div class="rounded-circle p-2 bg-warning-subtle text-warning">
                    <i class="bi bi-cart-check fs-4"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-1">
                <?= number_format((int)($stats['tong_don_hang'] ?? 0)) ?>
            </h3>
            <span class="text-muted small">Khách đặt trực tuyến</span>
        </div>
    </div>

    <!-- Tổng tồn kho -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Tổng Tồn Kho</span>
                <div class="rounded-circle p-2 bg-primary-subtle text-primary">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
            </div>
            <h3 class="fw-bold text-dark mb-1">
                <?= number_format((int)($stats['tong_ton_kho'] ?? 0)) ?> chiếc
            </h3>
            <span class="text-info small"><i class="bi bi-check2-circle me-1"></i>Tự động cập nhật qua Trigger</span>
        </div>
    </div>

    <!-- Cảnh báo sắp hết hàng -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-secondary small fw-bold text-uppercase">Cảnh Báo Hết Hàng</span>
                <div class="rounded-circle p-2 bg-danger-subtle text-danger">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
            </div>
            <h3 class="fw-bold text-danger mb-1">
                <?= count($lowStockWatches) ?> mẫu
            </h3>
            <span class="text-danger small">Tồn kho &le; 5 chiếc cần nhập thêm</span>
        </div>
    </div>
</div>

<!-- Hàng Trạng Thái Đơn Hàng -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-pie-chart-fill text-primary me-2"></i> Trạng Thái Đơn Hàng
                </h5>
                <a href="index.php?action=donhang" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    Quản lý toàn bộ đơn hàng &rarr;
                </a>
            </div>

            <?php if (empty($ordersByStatus)): ?>
                <div class="text-center text-muted small py-3">Chưa có dữ liệu đơn đặt hàng</div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($ordersByStatus as $st): ?>
                        <div class="col-sm-6 col-md-4 col-xl">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded-3 bg-light border h-100">
                                <span class="badge <?= $statusBadges[$st['trang_thai']] ?? 'bg-secondary' ?> px-3 py-2">
                                    <?= htmlspecialchars($st['trang_thai']) ?>
                                </span>
                                <strong class="text-dark fs-5 ms-2"><?= (int)$st['so_luong'] ?> đơn</strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hàng Bảng Dữ Liệu: Đơn Mới & Cảnh Báo Tồn Kho -->
<div class="row g-3">
    <!-- Đơn hàng gần đây -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">Đơn Đặt Hàng Trực Tuyến Gần Đây</h5>
                <a href="index.php?action=donhang" class="small text-decoration-none fw-bold text-warning">Xem tất cả</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th class="text-end">Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Chưa có đơn hàng nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr>
                                    <td class="fw-bold">#<?= (int)$ro['ma_pd'] ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($ro['ho_ten_nguoi_nhan']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($ro['sdt_nguoi_nhan']) ?></div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($ro['ngay_dat'])) ?></td>
                                    <td class="text-end fw-bold text-danger"><?= number_format((float)$ro['tong_tien'], 0, ',', '.') ?>đ</td>
                                    <td>
                                        <span class="badge <?= $statusBadges[$ro['trang_thai']] ?? 'bg-secondary' ?>">
                                            <?= htmlspecialchars($ro['trang_thai']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="index.php?action=donhang&act=detail&id=<?= (int)$ro['ma_pd'] ?>" class="btn btn-sm btn-outline-primary rounded-pill py-0 px-2">
                                            Xem
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Cảnh báo tồn kho -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-danger mb-0"><i class="bi bi-bell-fill me-1"></i> Đồng Hồ Sắp Hết Hàng</h5>
                <a href="index.php?action=nhaphang&act=create_po" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark">
                    + Tạo đơn đặt nhập
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Hãng</th>
                            <th class="text-center">Tồn kho</th>
                            <th class="text-end">Giá bán</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($lowStockWatches)): ?>
                            <tr><td colspan="4" class="text-center text-success py-3">Kho hàng dồi dào, không có mẫu nào dưới 5 chiếc!</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStockWatches as $lw): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($lw['ten_dh']) ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Mã: <?= htmlspecialchars($lw['ma_dh']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($lw['ten_hang']) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-danger rounded-pill px-2">
                                            <?= (int)$lw['so_luong_ton'] ?> chiếc
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold"><?= number_format((float)$lw['gia'], 0, ',', '.') ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>