<?php
/**
 * public/home.php
 * Trang chủ Storefront: Hiển thị danh sách đồng hồ Pin, bộ lọc 3 loại Pin, Hãng, Giá, Tìm kiếm
 */
require_once __DIR__ . '/../model/index.php';

// Nhận các tham số lọc từ GET (hỗ trợ cả ma_loai/loai và ma_hang/hang)
$maLoai = !empty($_GET['ma_loai']) ? (int)$_GET['ma_loai'] : (!empty($_GET['loai']) ? (int)$_GET['loai'] : null);
$maHang = !empty($_GET['ma_hang']) ? (int)$_GET['ma_hang'] : (!empty($_GET['hang']) ? (int)$_GET['hang'] : null);
$search = !empty($_GET['keyword']) ? trim($_GET['keyword']) : null;
$priceRange = $_GET['price_range'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$minPrice = null;
$maxPrice = null;
if ($priceRange === 'under_3m') {
    $maxPrice = 3000000;
} elseif ($priceRange === '3m_to_5m') {
    $minPrice = 3000000;
    $maxPrice = 5000000;
} elseif ($priceRange === 'above_5m') {
    $minPrice = 5000000;
}

// Lấy danh sách danh mục & hãng kèm số lượng để hiển thị bộ lọc
$categories = class_exists('LoaiDongHoModel') ? LoaiDongHoModel::getAllWithCount() : [];
$brands = class_exists('HangDongHoModel') ? HangDongHoModel::getAllWithCount() : [];

$totalAllWatches = 0;
$categoryMap = [];
foreach ($categories as $c) {
    $cId = (int)$c['ma_loai'];
    $categoryMap[$cId] = $c;
    $totalAllWatches += (int)($c['so_luong_dh'] ?? 0);
}

// Xác định tên loại và tên hãng đang được chọn
$selectedCategoryName = null;
if ($maLoai !== null && isset($categoryMap[$maLoai])) {
    $selectedCategoryName = $categoryMap[$maLoai]['ten_loai'];
}

$selectedBrandName = null;
if ($maHang !== null) {
    foreach ($brands as $b) {
        if ((int)$b['ma_hang'] === $maHang) {
            $selectedBrandName = $b['ten_hang'];
            break;
        }
    }
}

$hasAnyFilter = ($maLoai !== null || $maHang !== null || !empty($priceRange) || !empty($search));

// Lấy danh sách sản phẩm theo bộ lọc
$products = DongHoModel::getStorefrontWatches($maLoai, $maHang, $minPrice, $maxPrice, $search, $sort);

$pageTitle = 'Trang chủ - Cửa hàng Đồng hồ Pin Chính hãng';
require __DIR__ . '/inc/top.php';
?>

<style>
    .cat-quick-card {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        border: 2px solid transparent !important;
    }
    .cat-quick-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -6px rgba(15, 23, 42, 0.12) !important;
        border-color: #f59e0b !important;
    }
    .cat-quick-card.active-cat {
        border-color: #d97706 !important;
        background: linear-gradient(180deg, #fffbeb 0%, #ffffff 100%) !important;
        box-shadow: 0 8px 20px -4px rgba(217, 119, 6, 0.2) !important;
    }
    .product-img-wrapper {
        overflow: hidden;
        border-radius: 12px 12px 0 0;
    }
    .product-img-wrapper img {
        transition: transform 0.35s ease;
    }
    .product-card:hover .product-img-wrapper img {
        transform: scale(1.07);
    }
</style>

<!-- Hero Banner -->
<div class="bg-primary text-white py-4 py-md-5 mb-4 border-bottom border-warning border-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-2">
        <div class="row align-items-center">
            <div class="col-lg-7 text-center text-lg-start">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">
                    <i class="bi bi-lightning-charge-fill me-1"></i> BỘ SƯU TẬP ĐỒNG HỒ PIN 2026
                </span>
                <h1 class="display-5 fw-bold mb-3 text-white">Đỉnh Cao Chính Xác & Độ Bền Vượt Trội</h1>
                <p class="lead text-light mb-4" style="font-size: 1.05rem;">
                    Khám phá hơn 100+ mẫu đồng hồ Pin Quartz từ các thương hiệu huyền thoại Nhật Bản & Thụy Sĩ: Casio, Seiko, Citizen, Orient, Tissot. Bảo hành máy 12 tháng, thay pin miễn phí trọn đời!
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                    <a href="index.php?action=home#danh-sach-san-pham" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-grid-fill me-1"></i> Tất cả danh mục
                    </a>
                    <a href="index.php?action=home&loai=1#danh-sach-san-pham" class="btn btn-outline-light fw-bold px-4 py-2 rounded-pill">
                        Dây Kim Loại
                    </a>
                    <a href="index.php?action=home&loai=2#danh-sach-san-pham" class="btn btn-outline-light fw-bold px-4 py-2 rounded-pill">
                        Dây Da
                    </a>
                    <a href="index.php?action=home&loai=3#danh-sach-san-pham" class="btn btn-outline-warning fw-bold px-4 py-2 rounded-pill">
                        Thể Thao
                    </a>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <img src="../images/products/dh1.jpg" alt="Đồng hồ Pin" class="img-fluid rounded-4 shadow-lg p-3 bg-white" style="max-height: 280px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <!-- 4 Khối Danh Mục Sản Phẩm (Tất cả + 3 Dòng Pin) -->
    <div class="row g-3 mb-4">
        <!-- Khối 1: Tất Cả Đồng Hồ Pin -->
        <div class="col-6 col-lg-3">
            <a href="index.php?action=home#danh-sach-san-pham" class="card cat-quick-card text-decoration-none shadow-sm p-3 rounded-4 h-100 d-flex flex-column justify-content-between <?= ($maLoai === null) ? 'active-cat' : 'bg-white' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="rounded-circle p-2 bg-dark text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-grid-3x3-gap-fill fs-5"></i>
                    </div>
                    <span class="badge <?= ($maLoai === null) ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill fw-bold">
                        <?= $totalAllWatches ?> mẫu
                    </span>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Tất Cả Danh Mục</h6>
                    <small class="text-secondary d-block" style="font-size: 0.78rem;">Toàn bộ mẫu Pin chính hãng</small>
                </div>
            </a>
        </div>

        <!-- Khối 2: Pin Dây Kim Loại -->
        <?php $cat1 = $categoryMap[1] ?? ['ten_loai' => 'Dây Kim Loại', 'so_luong_dh' => 0]; ?>
        <div class="col-6 col-lg-3">
            <a href="index.php?action=home&loai=1#danh-sach-san-pham" class="card cat-quick-card text-decoration-none shadow-sm p-3 rounded-4 h-100 d-flex flex-column justify-content-between <?= ($maLoai === 1) ? 'active-cat' : 'bg-white' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="rounded-circle p-2 bg-warning-subtle text-warning" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-gem fs-5"></i>
                    </div>
                    <span class="badge <?= ($maLoai === 1) ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill fw-bold">
                        <?= (int)$cat1['so_luong_dh'] ?> mẫu
                    </span>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Pin Dây Kim Loại</h6>
                    <small class="text-secondary d-block" style="font-size: 0.78rem;">Sang trọng, bền bỉ, chuẩn công sở</small>
                </div>
            </a>
        </div>

        <!-- Khối 3: Pin Dây Da -->
        <?php $cat2 = $categoryMap[2] ?? ['ten_loai' => 'Dây Da', 'so_luong_dh' => 0]; ?>
        <div class="col-6 col-lg-3">
            <a href="index.php?action=home&loai=2#danh-sach-san-pham" class="card cat-quick-card text-decoration-none shadow-sm p-3 rounded-4 h-100 d-flex flex-column justify-content-between <?= ($maLoai === 2) ? 'active-cat' : 'bg-white' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="rounded-circle p-2 bg-danger-subtle text-danger" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <span class="badge <?= ($maLoai === 2) ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill fw-bold">
                        <?= (int)$cat2['so_luong_dh'] ?> mẫu
                    </span>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Pin Dây Da</h6>
                    <small class="text-secondary d-block" style="font-size: 0.78rem;">Cổ điển, thanh lịch & êm ái cổ tay</small>
                </div>
            </a>
        </div>

        <!-- Khối 4: Pin Thể Thao -->
        <?php $cat3 = $categoryMap[3] ?? ['ten_loai' => 'Thể Thao', 'so_luong_dh' => 0]; ?>
        <div class="col-6 col-lg-3">
            <a href="index.php?action=home&loai=3#danh-sach-san-pham" class="card cat-quick-card text-decoration-none shadow-sm p-3 rounded-4 h-100 d-flex flex-column justify-content-between <?= ($maLoai === 3) ? 'active-cat' : 'bg-white' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="rounded-circle p-2 bg-info-subtle text-info" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-speedometer2 fs-5"></i>
                    </div>
                    <span class="badge <?= ($maLoai === 3) ? 'bg-warning text-dark' : 'bg-light text-muted border' ?> rounded-pill fw-bold">
                        <?= (int)$cat3['so_luong_dh'] ?> mẫu
                    </span>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Pin Thể Thao</h6>
                    <small class="text-secondary d-block" style="font-size: 0.78rem;">Chống sốc, chống nước 20ATM</small>
                </div>
            </a>
        </div>
    </div>

    <!-- Thanh Chọn Nhanh Thương Hiệu (Brand Quick Pills) -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark text-warning p-2 rounded-3"><i class="bi bi-patch-check-fill"></i></span>
                <span class="fw-bold small text-uppercase text-secondary">Chọn nhanh hãng:</span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="index.php?action=home<?= $maLoai ? '&loai='.$maLoai : '' ?>#danh-sach-san-pham" 
                   class="btn btn-sm rounded-pill px-3 fw-bold <?= ($maHang === null) ? 'btn-warning text-dark shadow-sm' : 'btn-outline-secondary' ?>">
                    Tất cả thương hiệu (<?= $totalAllWatches ?>)
                </a>
                <?php foreach ($brands as $b): 
                    $isBActive = ($maHang === (int)$b['ma_hang']);
                ?>
                    <a href="index.php?action=home<?= $maLoai ? '&loai='.$maLoai : '' ?>&hang=<?= $b['ma_hang'] ?>#danh-sach-san-pham" 
                       class="btn btn-sm rounded-pill px-3 fw-bold <?= $isBActive ? 'btn-warning text-dark shadow-sm' : 'btn-outline-secondary' ?>">
                        <?= htmlspecialchars($b['ten_hang']) ?> 
                        <span class="badge <?= $isBActive ? 'bg-dark text-white' : 'bg-light text-muted border' ?> rounded-pill ms-1">
                            <?= (int)$b['so_luong_dh'] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Bộ Lọc -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-funnel-fill text-warning me-1"></i> Bộ Lọc Tìm Kiếm</h6>
                    <a href="index.php?action=home" class="badge text-bg-light text-decoration-none border">Xóa lọc</a>
                </div>

                <form method="GET" action="index.php">
                    <input type="hidden" name="action" value="home">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="keyword" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>

                    <!-- Loại đồng hồ -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Loại Đồng Hồ Pin</label>
                        <select name="loai" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Tất cả 3 loại Pin --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['ma_loai'] ?>" <?= ($maLoai === (int)$cat['ma_loai']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['ten_loai']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hãng sản xuất -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Thương Hiệu</label>
                        <select name="hang" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Tất cả thương hiệu --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['ma_hang'] ?>" <?= ($maHang === (int)$b['ma_hang']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['ten_hang']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Mức giá -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Khoảng Giá</label>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="price_range" id="price_all" value="" <?= empty($priceRange) ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="price_all">Tất cả mức giá</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="price_range" id="price_under_3m" value="under_3m" <?= ($priceRange === 'under_3m') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="price_under_3m">Dưới 3.000.000đ</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="price_range" id="price_3m_5m" value="3m_to_5m" <?= ($priceRange === '3m_to_5m') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="price_3m_5m">Từ 3.000.000đ - 5.000.000đ</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="price_range" id="price_above_5m" value="above_5m" <?= ($priceRange === 'above_5m') ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label small" for="price_above_5m">Trên 5.000.000đ</label>
                        </div>
                    </div>

                    <!-- Sắp xếp -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Sắp Xếp</label>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="newest" <?= ($sort === 'newest') ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Giá giảm dần</option>
                            <option value="name_asc" <?= ($sort === 'name_asc') ? 'selected' : '' ?>>Tên A-Z</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold btn-sm rounded-pill">
                        <i class="bi bi-check-lg me-1"></i> Áp Dụng Lọc
                    </button>
                </form>
            </div>
        </div>

        <!-- Lưới Danh Sách Đồng Hồ -->
        <div class="col-lg-9" id="danh-sach-san-pham">
            <?php
            $titlePrefix = 'Tất Cả Đồng Hồ Pin';
            if ($selectedCategoryName && $selectedBrandName) {
                $titlePrefix = $selectedCategoryName . ' — ' . $selectedBrandName;
            } elseif ($selectedCategoryName) {
                $titlePrefix = $selectedCategoryName;
            } elseif ($selectedBrandName) {
                $titlePrefix = 'Đồng Hồ Pin ' . $selectedBrandName;
            }
            ?>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-grid-fill text-warning me-1"></i> <?= htmlspecialchars($titlePrefix) ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill ms-2 fw-semibold fs-6"><?= count($products) ?> mẫu</span>
                    </h5>
                    <?php if (!empty($search)): ?>
                        <small class="text-muted">Kết quả tìm kiếm cho: "<strong><?= htmlspecialchars($search) ?></strong>"</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Thanh Filter Chips khi có bộ lọc hoạt động -->
            <?php if ($hasAnyFilter): ?>
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3 p-2 bg-light rounded-3 border small">
                    <span class="text-muted fw-bold me-1"><i class="bi bi-funnel me-1 text-warning"></i>Đang lọc:</span>
                    <?php if ($selectedCategoryName): ?>
                        <span class="badge bg-white text-dark border rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs">
                            Loại: <?= htmlspecialchars($selectedCategoryName) ?>
                            <a href="index.php?action=home<?= $maHang ? '&hang='.$maHang : '' ?><?= !empty($priceRange) ? '&price_range='.$priceRange : '' ?><?= !empty($search) ? '&keyword='.urlencode($search) : '' ?>#danh-sach-san-pham" class="text-secondary text-decoration-none ms-1 fw-bold">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if ($selectedBrandName): ?>
                        <span class="badge bg-white text-dark border rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs">
                            Hãng: <?= htmlspecialchars($selectedBrandName) ?>
                            <a href="index.php?action=home<?= $maLoai ? '&loai='.$maLoai : '' ?><?= !empty($priceRange) ? '&price_range='.$priceRange : '' ?><?= !empty($search) ? '&keyword='.urlencode($search) : '' ?>#danh-sach-san-pham" class="text-secondary text-decoration-none ms-1 fw-bold">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($priceRange)): 
                        $priceText = ($priceRange === 'under_3m') ? '< 3 triệu' : (($priceRange === '3m_to_5m') ? '3 - 5 triệu' : '> 5 triệu');
                    ?>
                        <span class="badge bg-white text-dark border rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs">
                            Giá: <?= $priceText ?>
                            <a href="index.php?action=home<?= $maLoai ? '&loai='.$maLoai : '' ?><?= $maHang ? '&hang='.$maHang : '' ?><?= !empty($search) ? '&keyword='.urlencode($search) : '' ?>#danh-sach-san-pham" class="text-secondary text-decoration-none ms-1 fw-bold">&times;</a>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($search)): ?>
                        <span class="badge bg-white text-dark border rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1 shadow-xs">
                            Từ khóa: "<?= htmlspecialchars($search) ?>"
                            <a href="index.php?action=home<?= $maLoai ? '&loai='.$maLoai : '' ?><?= $maHang ? '&hang='.$maHang : '' ?><?= !empty($priceRange) ? '&price_range='.$priceRange : '' ?>#danh-sach-san-pham" class="text-secondary text-decoration-none ms-1 fw-bold">&times;</a>
                        </span>
                    <?php endif; ?>
                    <a href="index.php?action=home#danh-sach-san-pham" class="btn btn-link btn-sm text-danger text-decoration-none p-0 ms-auto fw-semibold">
                        <i class="bi bi-x-circle me-1"></i>Xóa tất cả bộ lọc
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($products)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
                    <i class="bi bi-search display-3 text-secondary opacity-50 mb-3"></i>
                    <h5 class="fw-bold text-dark">Không tìm thấy sản phẩm phù hợp</h5>
                    <p class="text-secondary mb-3">Vui lòng thử điều chỉnh lại bộ lọc hoặc từ khóa tìm kiếm.</p>
                    <div>
                        <a href="index.php?action=home" class="btn btn-warning rounded-pill px-4 fw-bold">Xem tất cả sản phẩm</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3">
                    <?php foreach ($products as $dh): ?>
                        <div class="col">
                            <div class="card h-100 product-card position-relative">
                                <!-- Khuyến mãi badge -->
                                <?php if (!empty($dh['phan_tram_giam']) && $dh['phan_tram_giam'] > 0): ?>
                                    <span class="badge-discount">-<?= (int)$dh['phan_tram_giam'] ?>%</span>
                                <?php endif; ?>

                                <!-- Loại Pin badge -->
                                <span class="badge-pin"><?= htmlspecialchars($dh['ten_loai'] ?? 'Pin') ?></span>

                                <!-- Ảnh sản phẩm có zoom hover -->
                                <a href="index.php?action=detail&id=<?= urlencode($dh['ma_dh']) ?>" class="product-img-wrapper text-center d-block pt-3 bg-white">
                                    <img src="../<?= htmlspecialchars($dh['image']) ?>" alt="<?= htmlspecialchars($dh['ten_dh']) ?>" class="card-img-top p-3" style="height: 200px; object-fit: contain;">
                                </a>

                                <div class="card-body d-flex flex-column p-3">
                                    <div class="text-muted small mb-1">
                                        <i class="bi bi-tag-fill text-warning me-1"></i><?= htmlspecialchars($dh['ten_hang']) ?> • Mã: <?= htmlspecialchars($dh['ma_dh']) ?>
                                    </div>
                                    <h6 class="card-title fw-bold text-dark mb-2" style="min-height: 40px; font-size: 0.95rem;">
                                        <a href="index.php?action=detail&id=<?= urlencode($dh['ma_dh']) ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($dh['ten_dh']) ?>
                                        </a>
                                    </h6>

                                    <!-- Giá bán -->
                                    <div class="mt-auto pt-2">
                                        <div class="d-flex align-items-baseline gap-2 mb-2">
                                            <span class="text-danger fw-bold fs-5">
                                                <?= number_format($dh['gia_ban'], 0, ',', '.') ?>đ
                                            </span>
                                            <?php if (!empty($dh['phan_tram_giam']) && $dh['phan_tram_giam'] > 0): ?>
                                                <span class="text-muted text-decoration-line-through small">
                                                    <?= number_format($dh['gia_goc'], 0, ',', '.') ?>đ
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3 small">
                                            <?php if ((int)$dh['so_luong_ton'] > 0): ?>
                                                <span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Còn <?= (int)$dh['so_luong_ton'] ?> chiếc</span>
                                            <?php else: ?>
                                                <span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>Tạm hết hàng</span>
                                            <?php endif; ?>
                                            <span class="text-muted"><i class="bi bi-shield-check me-1"></i>BH <?= (int)$dh['thoi_gian_bh'] ?>T</span>
                                        </div>

                                        <!-- Hành động -->
                                        <div class="d-grid gap-2">
                                            <a href="index.php?action=detail&id=<?= urlencode($dh['ma_dh']) ?>" class="btn btn-outline-dark btn-sm rounded-pill fw-semibold">
                                                Xem chi tiết
                                            </a>
                                            <?php if ((int)$dh['so_luong_ton'] > 0): ?>
                                                <a href="index.php?action=cart&act=add&id=<?= urlencode($dh['ma_dh']) ?>" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark">
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
    </div>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
