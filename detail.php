<?php
/**
 * public/detail.php
 * Chi tiết sản phẩm Đồng hồ Pin: Thông số kỹ thuật, bảo hành, hình ảnh, thêm vào giỏ
 */
require_once __DIR__ . '/../model/index.php';

$maDh = $_GET['id'] ?? '';
if (empty($maDh)) {
    $_SESSION['flash_error'] = 'Mã đồng hồ không hợp lệ!';
    header('Location: index.php?action=home');
    exit;
}

$dh = DongHoModel::findById($maDh);
if (!$dh || (int)$dh['status'] !== 1) {
    $_SESSION['flash_error'] = 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.';
    header('Location: index.php?action=home');
    exit;
}

// Kiểm tra khuyến mãi hiện tại của sản phẩm
$db = Database::getConnection();
$stmtKm = $db->prepare("
    SELECT km.ma_km, km.ly_do_km, ct.phan_tram_giam, km.ngay_bat_dau, km.ngay_ket_thuc
    FROM ct_khuyenmai ct
    JOIN khuyenmai km ON ct.ma_km = km.ma_km
    WHERE ct.ma_dh = :ma_dh AND NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc
    ORDER BY ct.phan_tram_giam DESC
    LIMIT 1
");
$stmtKm->execute([':ma_dh' => $maDh]);
$activePromo = $stmtKm->fetch();

$giaGoc = (float)$dh['gia'];
$phanTramGiam = $activePromo ? (int)$activePromo['phan_tram_giam'] : 0;
$giaBan = ($phanTramGiam > 0) ? round($giaGoc * (1 - $phanTramGiam / 100), 2) : $giaGoc;

// Lấy sản phẩm tương tự cùng hãng hoặc loại
$stmtRelated = $db->prepare("
    SELECT d.ma_dh, d.ten_dh, d.gia, d.image, d.so_luong_ton, l.ten_loai, h.ten_hang
    FROM dongho d
    JOIN loaidongho l ON d.ma_loai = l.ma_loai
    JOIN hangdongho h ON d.ma_hang = h.ma_hang
    WHERE d.status = 1 AND d.ma_dh != :ma_dh AND (d.ma_hang = :ma_hang OR d.ma_loai = :ma_loai)
    LIMIT 4
");
$stmtRelated->execute([
    ':ma_dh'   => $maDh,
    ':ma_hang' => $dh['ma_hang'],
    ':ma_loai' => $dh['ma_loai']
]);
$relatedWatches = $stmtRelated->fetchAll();

$pageTitle = $dh['ten_dh'] . ' - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?action=home" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?action=home&loai=<?= $dh['ma_loai'] ?>" class="text-decoration-none"><?= htmlspecialchars($dh['ten_loai']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($dh['ten_dh']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 mb-5">
        <!-- Ảnh Sản Phẩm -->
        <div class="col-lg-5 text-center">
            <div class="card border-0 shadow-sm rounded-4 p-4 position-relative bg-white">
                <?php if ($phanTramGiam > 0): ?>
                    <span class="badge-discount fs-6">-<?= $phanTramGiam ?>%</span>
                <?php endif; ?>
                <span class="badge-pin"><?= htmlspecialchars($dh['ten_loai']) ?></span>

                <img src="../<?= htmlspecialchars($dh['image']) ?>" alt="<?= htmlspecialchars($dh['ten_dh']) ?>" class="img-fluid mx-auto p-3" style="max-height: 400px; object-fit: contain;">
            </div>
        </div>

        <!-- Thông Tin Sản Phẩm & Đặt Mua -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-dark"><?= htmlspecialchars($dh['ten_hang']) ?></span>
                    <span class="badge bg-secondary">Mã: <?= htmlspecialchars($dh['ma_dh']) ?></span>
                    <span class="badge bg-info text-white">Máy Pin Quartz</span>
                </div>

                <h3 class="fw-bold text-dark mb-3"><?= htmlspecialchars($dh['ten_dh']) ?></h3>

                <!-- Giá bán & Khuyến mãi -->
                <div class="p-3 bg-light rounded-3 mb-4">
                    <div class="d-flex align-items-baseline gap-3">
                        <span class="text-danger fw-bold fs-2">
                            <?= number_format($giaBan, 0, ',', '.') ?>đ
                        </span>
                        <?php if ($phanTramGiam > 0): ?>
                            <span class="text-muted text-decoration-line-through fs-5">
                                <?= number_format($giaGoc, 0, ',', '.') ?>đ
                            </span>
                            <span class="badge bg-danger">Tiết kiệm <?= number_format($giaGoc - $giaBan, 0, ',', '.') ?>đ</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($activePromo): ?>
                        <div class="small text-danger mt-1">
                            <i class="bi bi-tag-fill me-1"></i> <strong>Chương trình:</strong> <?= htmlspecialchars($activePromo['ly_do_km']) ?> (Đang diễn ra)
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tình trạng & Thời gian bảo hành -->
                <div class="row g-2 mb-4">
                    <div class="col-sm-6">
                        <div class="p-2 border rounded-3 text-center">
                            <small class="text-muted d-block">Tồn Kho Hiện Tại</small>
                            <?php if ((int)$dh['so_luong_ton'] > 0): ?>
                                <strong class="text-success fs-5">Còn <?= (int)$dh['so_luong_ton'] ?> chiếc</strong>
                            <?php else: ?>
                                <strong class="text-danger fs-5">Tạm hết hàng</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 border rounded-3 text-center">
                            <small class="text-muted d-block">Chính Sách Bảo Hành</small>
                            <strong class="text-primary fs-5"><?= (int)$dh['thoi_gian_bh'] ?> Tháng Chính Hãng</strong>
                        </div>
                    </div>
                </div>

                <!-- Form Thêm Vào Giỏ Hàng -->
                <?php if ((int)$dh['so_luong_ton'] > 0): ?>
                    <form action="index.php" method="GET" class="mb-4">
                        <input type="hidden" name="action" value="cart">
                        <input type="hidden" name="act" value="add">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($dh['ma_dh']) ?>">

                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-auto">
                                <label for="quantity" class="fw-bold small">Số lượng mua:</label>
                            </div>
                            <div class="col-auto" style="width: 120px;">
                                <input type="number" name="quantity" id="quantity" class="form-control text-center fw-bold" value="1" min="1" max="<?= (int)$dh['so_luong_ton'] ?>">
                            </div>
                            <div class="col-auto text-muted small">
                                (Tối đa <?= (int)$dh['so_luong_ton'] ?> chiếc)
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-warning btn-lg px-4 fw-bold rounded-pill text-dark shadow-sm flex-grow-1">
                                <i class="bi bi-cart-plus me-2"></i> THÊM VÀO GIỎ HÀNG
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-1"></i> Sản phẩm này hiện đang tạm hết hàng. Quý khách vui lòng chọn mẫu khác hoặc liên hệ hotline 0358554605 để đặt trước.
                    </div>
                <?php endif; ?>

                <!-- Cam kết nhanh -->
                <div class="border-top pt-3 text-secondary small">
                    <div class="row g-2">
                        <div class="col-6"><i class="bi bi-check2-circle text-success me-1"></i> Đổi mới 7 ngày nếu lỗi</div>
                        <div class="col-6"><i class="bi bi-check2-circle text-success me-1"></i> Miễn phí thay pin trọn đời</div>
                        <div class="col-6"><i class="bi bi-check2-circle text-success me-1"></i> Hóa đơn & Thẻ bảo hành điện tử</div>
                        <div class="col-6"><i class="bi bi-check2-circle text-success me-1"></i> Giao hàng hỏa tốc toàn quốc</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chi tiết mô tả sản phẩm -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
        <h4 class="fw-bold text-dark border-bottom pb-3 mb-4">
            <i class="bi bi-file-text-fill text-warning me-2"></i> Đặc Điểm Nổi Bật & Mô Tả Chi Tiết
        </h4>
        <div class="product-description lead fs-6">
            <?= !empty($dh['mo_ta']) ? $dh['mo_ta'] : '<p class="text-muted">Chưa có mô tả chi tiết cho sản phẩm này.</p>' ?>
        </div>
    </div>

    <!-- Sản phẩm liên quan -->
    <?php if (!empty($relatedWatches)): ?>
        <div class="mb-5">
            <h4 class="fw-bold text-dark mb-4">
                <i class="bi bi-stars text-warning me-2"></i> Sản Phẩm Tương Tự
            </h4>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
                <?php foreach ($relatedWatches as $rw): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <a href="index.php?action=detail&id=<?= urlencode($rw['ma_dh']) ?>" class="p-3 text-center d-block bg-white">
                                <img src="../<?= htmlspecialchars($rw['image']) ?>" alt="<?= htmlspecialchars($rw['ten_dh']) ?>" class="img-fluid" style="height: 160px; object-fit: contain;">
                            </a>
                            <div class="card-body p-3 d-flex flex-column">
                                <small class="text-muted"><?= htmlspecialchars($rw['ten_hang']) ?></small>
                                <h6 class="fw-bold text-dark mb-2" style="font-size: 0.9rem;">
                                    <a href="index.php?action=detail&id=<?= urlencode($rw['ma_dh']) ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($rw['ten_dh']) ?>
                                    </a>
                                </h6>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="text-danger fw-bold"><?= number_format($rw['gia'], 0, ',', '.') ?>đ</span>
                                    <a href="index.php?action=detail&id=<?= urlencode($rw['ma_dh']) ?>" class="btn btn-sm btn-outline-dark rounded-pill">Xem</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
