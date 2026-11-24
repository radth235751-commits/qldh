<?php
/**
 * public/about.php
 * Trang Giới thiệu hệ thống cửa hàng Đồng hồ Pin WATCHSTORE
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Giới thiệu - WATCHSTORE Đồng Hồ Pin Chính Hãng';

// Lấy danh sách loại & hãng thực tế từ CSDL để hiển thị đồng bộ
$loaiDongHoList = class_exists('LoaiDongHoModel') ? LoaiDongHoModel::getAll() : [];
$hangDongHoList = class_exists('HangDongHoModel') ? HangDongHoModel::getAll() : [];

require __DIR__ . '/inc/top.php';
?>

<!-- 1. HERO BANNER GIỚI THIỆU -->
<section class="bg-dark text-white py-4 py-md-5 mb-4 border-bottom border-warning border-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-2">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">
                    <i class="bi bi-shield-check me-1"></i> HỆ THỐNG PHÂN PHỐI ĐỒNG HỒ PIN CHÍNH HÃNG
                </span>
                <h1 class="display-5 fw-bold mb-3">
                    Chào mừng đến với <span class="text-warning">WATCHSTORE</span>
                </h1>
                <p class="lead text-light opacity-75 mb-4">
                    Chuyên cung cấp các dòng đồng hồ chạy máy Pin (Quartz) chính hãng từ Nhật Bản và Thụy Sĩ — 
                    độ chính xác vượt trội, thiết kế tinh tế, bảo hành minh bạch từng chiếc và đổi trả linh hoạt trong 7 ngày.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="index.php?action=home" class="btn btn-warning btn-lg fw-bold text-dark rounded-pill px-4 shadow-sm">
                        <i class="bi bi-watch me-2"></i>Khám Phá Bộ Sưu Tập
                    </a>
                    <a href="#cam-ket-dich-vu" class="btn btn-outline-light btn-lg rounded-pill px-4">
                        Chính Sách Bảo Hành
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card bg-secondary bg-opacity-10 border border-secondary rounded-4 p-4 text-white">
                    <h5 class="fw-bold text-warning mb-3">
                        <i class="bi bi-award-fill me-2"></i>Tiêu Chuẩn Chất Lượng Tại Cửa Hàng
                    </h5>
                    <ul class="list-unstyled mb-0 d-grid gap-3">
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-warning me-2 mt-1"></i>
                            <span><strong>Bộ máy Quartz chuẩn xác:</strong> Sai số cực thấp, vận hành ổn định, dễ dàng thay pin và bảo dưỡng.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-warning me-2 mt-1"></i>
                            <span><strong>3 phân khúc thiết kế chuyên biệt:</strong> Dây kim loại sang trọng, dây da thanh lịch và dòng thể thao bền bỉ.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-warning me-2 mt-1"></i>
                            <span><strong>Quản lý bảo hành điện tử:</strong> Cấp phiếu bảo hành riêng theo từng chiếc đồng hồ ngay khi xuất hóa đơn.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. CHUYÊN MÔN & DANH MỤC CHỦ LỰC -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <h6 class="text-warning fw-bold text-uppercase mb-2">Về Chúng Tôi</h6>
                <h2 class="fw-bold text-dark mb-3">Chuyên Sâu Về Đồng Hồ Pin (Quartz) Chính Hãng</h2>
                <p class="text-muted">
                    Tại <strong>WATCHSTORE</strong>, mỗi mẫu đồng hồ đều được tuyển chọn kỹ lưỡng từ các nhà cung cấp uy tín, 
                    kiểm định chất lượng trước khi nhập kho và niêm yết giá minh bạch. Hệ thống lưu vết đầy đủ lịch sử biến động giá, 
                    chương trình khuyến mãi theo từng mẫu và thời hạn bảo hành từ 12 đến 24 tháng.
                </p>
                <p class="text-muted mb-4">
                    Chúng tôi tập trung phân phối các thương hiệu đồng hồ Pin được ưa chuộng nhất hiện nay:
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($hangDongHoList)): ?>
                        <?php foreach ($hangDongHoList as $hang): ?>
                            <a href="index.php?action=home&ma_hang=<?= (int)$hang['ma_hang'] ?>" 
                               class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-semibold">
                                <?= htmlspecialchars($hang['ten_hang']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="badge bg-dark px-3 py-2">Casio</span>
                        <span class="badge bg-dark px-3 py-2">Citizen</span>
                        <span class="badge bg-dark px-3 py-2">Seiko</span>
                        <span class="badge bg-dark px-3 py-2">Orient</span>
                        <span class="badge bg-dark px-3 py-2">Tissot</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 bg-light p-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-grid-fill text-warning me-2"></i>3 Dòng Đồng Hồ Pin Chủ Lực
                    </h5>
                    <div class="d-grid gap-3">
                        <?php if (!empty($loaiDongHoList)): ?>
                            <?php foreach ($loaiDongHoList as $index => $loai): ?>
                                <a href="index.php?action=home&ma_loai=<?= (int)$loai['ma_loai'] ?>" 
                                   class="text-decoration-none">
                                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center shadow-sm">
                                        <div>
                                            <span class="badge bg-warning text-dark me-2">#<?= $index + 1 ?></span>
                                            <strong class="text-dark"><?= htmlspecialchars($loai['ten_loai']) ?></strong>
                                        </div>
                                        <span class="text-warning fw-semibold small">Xem mẫu <i class="bi bi-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 bg-white rounded-3 border fw-bold">1. Đồng hồ Pin dây kim loại</div>
                            <div class="p-3 bg-white rounded-3 border fw-bold">2. Đồng hồ Pin dây da</div>
                            <div class="p-3 bg-white rounded-3 border fw-bold">3. Đồng hồ Pin thể thao</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. 4 CAM KẾT VÀNG DÀNH CHO KHÁCH HÀNG -->
<section id="cam-ket-dich-vu" class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-warning fw-bold text-uppercase">Quyền Lợi Khách Hàng</h6>
            <h2 class="fw-bold text-dark">4 Cam Kết Dịch Vụ Tại WATCHSTORE</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-patch-check-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">100% Chính Hãng</h5>
                    <p class="text-muted small mb-0">
                        Nhập hàng trực tiếp từ nhà phân phối ủy quyền, đầy đủ hộp, sổ hướng dẫn và hóa đơn bán lẻ hợp lệ.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-tools fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Bảo Hành 3-12 Tháng</h5>
                    <p class="text-muted small mb-0">
                        Mỗi chiếc đồng hồ được cấp một mã phiếu bảo hành riêng biệt, theo dõi rõ tình trạng trước và sau bảo hành.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-arrow-repeat fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Đổi Trả Trong 7 Ngày</h5>
                    <p class="text-muted small mb-0">
                        Hỗ trợ tiếp nhận trả hàng linh hoạt trong vòng 7 ngày kể từ ngày xuất hóa đơn theo đúng quy định cửa hàng.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Giao Hàng Toàn Quốc</h5>
                    <p class="text-muted small mb-0">
                        Đóng gói chống sốc tiêu chuẩn, hỗ trợ thanh toán COD khi nhận hàng hoặc chuyển khoản mã QR tiện lợi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>




<?php require __DIR__ . '/inc/bottom.php'; ?>