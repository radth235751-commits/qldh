<?php
/**
 * public/categories.php
 * Trang Danh mục sản phẩm toàn màn hình (3 dòng đồng hồ Pin, Thương hiệu & Bộ lọc trực quan)
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Danh mục sản phẩm - WatchStore Đồng Hồ Pin Chính Hãng';

// Nhận tham số lọc (nếu người dùng bấm chọn một loại hoặc hãng cụ thể)
$selectedLoai = isset($_GET['ma_loai']) && (int)$_GET['ma_loai'] > 0 ? (int)$_GET['ma_loai'] : null;
$selectedHang = isset($_GET['ma_hang']) && (int)$_GET['ma_hang'] > 0 ? (int)$_GET['ma_hang'] : null;
$sort         = trim((string)($_GET['sort'] ?? 'newest'));

// Lấy dữ liệu thực tế từ CSDL
$categories = class_exists('LoaiDongHoModel') ? LoaiDongHoModel::getAllWithCount() : [];
$brands     = class_exists('HangDongHoModel') ? HangDongHoModel::getAllWithCount() : [];
$watches    = class_exists('DongHoModel')
    ? DongHoModel::getStorefrontWatches($selectedLoai, $selectedHang, null, null, null, $sort)
    : [];

$categoryMeta = [
    1 => [
        'icon'  => 'bi-gem',
        'desc'  => 'Thiết kế dây thép không gỉ sang trọng, bền bỉ theo thời gian, phù hợp môi trường công sở và sự kiện.',
        'badge' => 'Thanh lịch & Đẳng cấp',
        'color' => '#d97706',
        'bg'    => '#fef3c7'
    ],
    2 => [
        'icon'  => 'bi-clock-history',
        'desc'  => 'Dây da mềm mại ôm sát cổ tay, phong cách tối giản cổ điển, nhẹ nhàng và tinh tế mỗi ngày.',
        'badge' => 'Cổ điển & Tinh tế',
        'color' => '#dc2626',
        'bg'    => '#fee2e2'
    ],
    3 => [
        'icon'  => 'bi-speedometer2',
        'desc'  => 'Khả năng chống sốc vượt trội, kháng nước cao, tích hợp bấm giờ thể thao cho người năng động.',
        'badge' => 'Bền bỉ & Năng động',
        'color' => '#0284c7',
        'bg'    => '#e0f2fe'
    ]
];

$brandOrigins = [
    'Casio'   => 'Nhật Bản',
    'Citizen' => 'Nhật Bản',
    'Seiko'   => 'Nhật Bản',
    'Orient'  => 'Nhật Bản',
    'Tissot'  => 'Thụy Sĩ'
];

require __DIR__ . '/inc/top.php';
?>

<!-- 1. BANNER DANH MỤC SẢN PHẨM -->
<section class="bg-dark text-white py-4 py-md-5 mb-4 border-bottom border-warning border-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-2">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-grid-fill me-1"></i> DANH MỤC ĐỒNG HỒ PIN (QUARTZ) CHÍNH HÃNG
                </span>
                <h1 class="display-5 fw-bold mb-3">
                    Khám Phá Toàn Bộ <span class="text-warning">Danh Mục Sản Phẩm</span>
                </h1>
                <p class="lead text-light opacity-75 mb-0">
                    Lựa chọn nhanh theo 3 dòng đồng hồ Pin chủ lực hoặc các thương hiệu nổi tiếng từ Nhật Bản và Thụy Sĩ.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-inline-flex gap-3 bg-secondary bg-opacity-25 border border-secondary rounded-4 p-3 text-start">
                    <div>
                        <div class="fs-3 fw-extrabold text-warning"><?= count($categories) ?></div>
                        <div class="small text-light opacity-75">Dòng đồng hồ Pin</div>
                    </div>
                    <div class="vr bg-secondary"></div>
                    <div>
                        <div class="fs-3 fw-extrabold text-warning"><?= count($brands) ?></div>
                        <div class="small text-light opacity-75">Thương hiệu</div>
                    </div>
                    <div class="vr bg-secondary"></div>
                    <div>
                        <div class="fs-3 fw-extrabold text-warning"><?= count($watches) ?></div>
                        <div class="small text-light opacity-75">Mẫu đang hiển thị</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. PHÂN LOẠI THEO 3 DÒNG ĐỒNG HỒ PIN -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-2">
            <div>
                <h6 class="text-warning fw-bold text-uppercase mb-1">Phân loại theo thiết kế</h6>
                <h2 class="fw-bold text-dark mb-0">3 Dòng Đồng Hồ Pin Chủ Lực</h2>
            </div>
            <?php if ($selectedLoai !== null || $selectedHang !== null): ?>
                <a href="index.php?action=categories" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Xem tất cả danh mục
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($categories as $cat):
                $cId = (int)$cat['ma_loai'];
                $countSp = (int)($cat['so_luong_dh'] ?? $cat['so_luong_sp'] ?? 0);
                $meta = $categoryMeta[$cId] ?? [
                    'icon'  => 'bi-watch',
                    'desc'  => 'Bộ sưu tập đồng hồ chạy máy Pin chính hãng, độ bền cao và bảo hành đầy đủ.',
                    'badge' => 'Chính hãng',
                    'color' => '#d97706',
                    'bg'    => '#fef3c7'
                ];
                $isSelected = ($selectedLoai === $cId);
            ?>
                <div class="col-md-4">
                    <div class="card h-100 rounded-4 p-4 shadow-sm border-2 <?= $isSelected ? 'border-warning bg-warning-subtle bg-opacity-10' : 'border-light bg-white' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="rounded-4 d-flex align-items-center justify-content-center"
                                 style="width: 58px; height: 58px; background-color: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; font-size: 1.7rem;">
                                <i class="bi <?= $meta['icon'] ?>"></i>
                            </div>
                            <span class="badge bg-dark text-warning rounded-pill px-3 py-2 fs-6">
                                <?= $countSp ?> mẫu
                            </span>
                        </div>
                        <span class="small fw-bold text-uppercase mb-1" style="color: <?= $meta['color'] ?>;">
                            <?= htmlspecialchars($meta['badge']) ?>
                        </span>
                        <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($cat['ten_loai']) ?></h4>
                        <p class="text-muted small flex-grow-1 mb-4"><?= htmlspecialchars($meta['desc']) ?></p>
                        <div class="d-flex gap-2">
                            <a href="index.php?action=categories&ma_loai=<?= $cId ?>#ket-qua-danh-muc"
                               class="btn <?= $isSelected ? 'btn-warning text-dark fw-bold' : 'btn-outline-dark' ?> rounded-pill px-4 w-100">
                                <?= $isSelected ? 'Đang lọc dòng này' : 'Lọc theo dòng này' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. PHÂN LOẠI THEO THƯƠNG HIỆU / HÃNG SẢN XUẤT -->
<section class="py-4 bg-light border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-patch-check-fill text-warning me-2"></i>Thương Hiệu Đồng Hồ Chính Hãng
            </h5>
            <span class="text-muted small">Chọn thương hiệu để lọc nhanh các mẫu tương ứng</span>
        </div>

        <div class="row g-3">
            <?php foreach ($brands as $b):
                $bId = (int)$b['ma_hang'];
                $bName = $b['ten_hang'];
                $bCount = (int)($b['so_luong_dh'] ?? $b['so_luong_sp'] ?? 0);
                $origin = $brandOrigins[$bName] ?? 'Chính hãng';
                $isBrandSelected = ($selectedHang === $bId);
            ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg">
                    <a href="index.php?action=categories&ma_hang=<?= $bId ?><?= $selectedLoai ? '&ma_loai=' . $selectedLoai : '' ?>#ket-qua-danh-muc"
                       class="text-decoration-none">
                        <div class="card border-2 rounded-4 p-3 h-100 shadow-sm <?= $isBrandSelected ? 'border-warning bg-warning text-dark' : 'border-white bg-white text-dark' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold fs-6"><?= htmlspecialchars($bName) ?></span>
                                <span class="badge <?= $isBrandSelected ? 'bg-dark text-warning' : 'bg-light text-dark border' ?> rounded-pill">
                                    <?= $bCount ?> mẫu
                                </span>
                            </div>
                            <small class="<?= $isBrandSelected ? 'text-dark opacity-75' : 'text-muted' ?>">
                                <i class="bi bi-globe2 me-1"></i><?= htmlspecialchars($origin) ?>
                            </small>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. DANH SÁCH ĐỒNG HỒ THEO DANH MỤC ĐANG CHỌN -->
<section id="ket-qua-danh-muc" class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-3 rounded-4 border shadow-sm">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-bold text-dark"><i class="bi bi-funnel-fill text-warning me-1"></i> Đang hiển thị:</span>
                <span class="badge bg-dark text-warning rounded-pill px-3 py-2"><?= count($watches) ?> sản phẩm</span>
                <?php if ($selectedLoai !== null): ?>
                    <a href="index.php?action=categories<?= $selectedHang ? '&ma_hang=' . $selectedHang : '' ?>#ket-qua-danh-muc"
                       class="badge bg-warning text-dark text-decoration-none rounded-pill px-3 py-2">
                        Loại #<?= $selectedLoai ?> <i class="bi bi-x-circle ms-1"></i>
                    </a>
                <?php endif; ?>
                <?php if ($selectedHang !== null): ?>
                    <a href="index.php?action=categories<?= $selectedLoai ? '&ma_loai=' . $selectedLoai : '' ?>#ket-qua-danh-muc"
                       class="badge bg-warning text-dark text-decoration-none rounded-pill px-3 py-2">
                        Hãng #<?= $selectedHang ?> <i class="bi bi-x-circle ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>

            <form method="GET" action="index.php" class="d-flex align-items-center gap-2">
                <input type="hidden" name="action" value="categories">
                <?php if ($selectedLoai !== null): ?>
                    <input type="hidden" name="ma_loai" value="<?= $selectedLoai ?>">
                <?php endif; ?>
                <?php if ($selectedHang !== null): ?>
                    <input type="hidden" name="ma_hang" value="<?= $selectedHang ?>">
                <?php endif; ?>
                <label class="small text-muted fw-semibold text-nowrap">Sắp xếp:</label>
                <select name="sort" class="form-select form-select-sm rounded-pill" onchange="this.form.submit()">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá thấp đến cao</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá cao đến thấp</option>
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Tên A - Z</option>
                </select>
            </form>
        </div>

        <?php if (empty($watches)): ?>
            <div class="text-center py-5 bg-white rounded-4 border">
                <i class="bi bi-watch display-4 text-muted"></i>
                <h5 class="fw-bold mt-3">Không tìm thấy đồng hồ phù hợp</h5>
                <p class="text-muted">Vui lòng thử bỏ bớt điều kiện lọc hoặc xem toàn bộ danh mục.</p>
                <a href="index.php?action=categories" class="btn btn-warning fw-bold text-dark rounded-pill px-4">
                    Xem tất cả sản phẩm
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                <?php foreach ($watches as $w):
                    $imgName = trim((string)($w['image'] ?? ''));
                    $imgUrl = '';
                    if ($imgName !== '') {
                        if (file_exists(__DIR__ . '/../images/products/' . $imgName)) {
                            $imgUrl = '../images/products/' . $imgName;
                        } elseif (file_exists(__DIR__ . '/../images/' . $imgName)) {
                            $imgUrl = '../images/' . $imgName;
                        } elseif (file_exists(__DIR__ . '/../' . $imgName)) {
                            $imgUrl = '../' . $imgName;
                        }
                    }
                    $tonKho = (int)$w['so_luong_ton'];
                    $giam = (int)$w['phan_tram_giam'];
                ?>
                    <div class="col">
                        <div class="card h-100 product-card position-relative">
                            <?php if ($giam > 0): ?>
                                <span class="badge-discount">-<?= $giam ?>%</span>
                            <?php endif; ?>
                            <span class="badge-pin"><?= htmlspecialchars($w['ten_hang']) ?></span>

                            <!-- Ảnh sản phẩm có zoom hover -->
                            <a href="index.php?action=detail&id=<?= urlencode($w['ma_dh']) ?>" class="product-img-wrapper text-center d-block pt-3 bg-white">
                                <?php if ($imgUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($w['ten_dh']) ?>" class="card-img-top p-3" style="height: 200px; object-fit: contain;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center p-3" style="height: 200px;">
                                        <i class="bi bi-watch text-secondary" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </a>

                            <div class="card-body d-flex flex-column p-3">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-tag-fill text-warning me-1"></i><?= htmlspecialchars($w['ten_loai']) ?> • Mã: <?= htmlspecialchars($w['ma_dh']) ?>
                                </div>
                                <h6 class="card-title fw-bold text-dark mb-2" style="min-height: 42px; font-size: 0.95rem;">
                                    <a href="index.php?action=detail&id=<?= urlencode($w['ma_dh']) ?>" class="text-dark text-decoration-none">
                                        <?= htmlspecialchars($w['ten_dh']) ?>
                                    </a>
                                </h6>

                                <div class="mt-auto pt-2">
                                    <div class="d-flex align-items-baseline gap-2 mb-2">
                                        <span class="text-danger fw-bold fs-5">
                                            <?= number_format((float)$w['gia_ban'], 0, ',', '.') ?>đ
                                        </span>
                                        <?php if ($giam > 0): ?>
                                            <span class="text-muted text-decoration-line-through small">
                                                <?= number_format((float)$w['gia_goc'], 0, ',', '.') ?>đ
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3 small">
                                        <?php if ($tonKho > 0): ?>
                                            <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Còn <?= $tonKho ?> chiếc</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Tạm hết hàng</span>
                                        <?php endif; ?>
                                        <span class="text-muted"><i class="bi bi-shield-check me-1"></i>BH <?= (int)$w['thoi_gian_bh'] ?>T</span>
                                    </div>

                                    <!-- Hành động -->
                                    <div class="d-grid gap-2">
                                        <a href="index.php?action=detail&id=<?= urlencode($w['ma_dh']) ?>" class="btn btn-outline-dark btn-sm rounded-pill fw-semibold">
                                            Xem chi tiết
                                        </a>
                                        <?php if (!empty($isStaffOrAdmin)): ?>
                                            <span class="badge bg-secondary-subtle text-secondary py-2 rounded-pill small">Chế độ Quản trị</span>
                                        <?php elseif ($tonKho > 0): ?>
                                            <a href="index.php?action=cart&act=add&id=<?= urlencode($w['ma_dh']) ?>" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark">
                                                <i class="bi bi-cart-plus me-1"></i> Thêm vào giỏ
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm rounded-pill" disabled>Tạm hết hàng</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/inc/bottom.php'; ?>