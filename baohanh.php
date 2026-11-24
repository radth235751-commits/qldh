<?php
/**
 * admin/baohanh.php
 * Quản lý Phiếu Bảo Hành:
 * Vòng đời bảo hành: Lập phiếu -> Tiếp nhận bảo hành -> Hoàn thành & Giao trả máy.
 * Lưu vết đầy đủ cả 3 nhân viên (ma_nv_lap, ma_nv_nhan, ma_nv_giao_tra).
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Quản lý Dịch vụ Bảo hành Đồng hồ Pin';
$db = Database::getConnection();
$currentUser = $_SESSION['user'];
$maNv = $currentUser['ma_nv'] ?? 'NV01';

$act = $_REQUEST['act'] ?? 'list';

// 1. Tiếp nhận bảo hành
if ($act === 'receive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $soPhieuBh = trim($_POST['so_phieu_bh'] ?? '');
    $tinhTrang = trim($_POST['trang_thai_truoc_bh'] ?? '');

    if (!empty($soPhieuBh) && !empty($tinhTrang)) {
        try {
            PhieuBaoHanhModel::receiveWarranty($soPhieuBh, $tinhTrang, $maNv);
            $_SESSION['flash_success'] = "Đã tiếp nhận bảo hành cho phiếu #{$soPhieuBh} thành công! (Ghi nhận nhân viên tiếp nhận: {$maNv})";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi tiếp nhận bảo hành: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng mô tả tình trạng hư hỏng trước bảo hành.";
    }
    header("Location: index.php?action=baohanh");
    exit;
}

// 2. Hoàn thành sửa chữa & Giao trả khách
if ($act === 'complete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $soPhieuBh = trim($_POST['so_phieu_bh'] ?? '');
    $ketQua = trim($_POST['trang_thai_sau_bh'] ?? '');

    if (!empty($soPhieuBh) && !empty($ketQua)) {
        try {
            PhieuBaoHanhModel::completeAndReturnWarranty($soPhieuBh, $ketQua, $maNv);
            $_SESSION['flash_success'] = "Đã hoàn tất bảo hành và bàn giao trả đồng hồ cho khách (Phiếu #{$soPhieuBh})! (Ghi nhận nhân viên giao trả: {$maNv})";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi bàn giao: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng mô tả kết quả sửa chữa sau bảo hành.";
    }
    header("Location: index.php?action=baohanh");
    exit;
}

// 3. Danh sách Phiếu bảo hành (Default)
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? null;
if ($statusFilter === '') $statusFilter = null;

$warranties = PhieuBaoHanhModel::getAllFiltered($search, $statusFilter, null, $db);

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-info me-2"></i> Quản Lý Dịch Vụ Bảo Hành </h4>
        <p class="text-secondary small mb-0">Tiếp nhận bảo hành, ghi nhận tình trạng máy, sửa chữa và lưu vết 3 nhân viên</p>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
    <form method="GET" action="index.php" class="row g-2 align-items-center">
        <input type="hidden" name="action" value="baohanh">
        <div class="col-md-5">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Tìm theo số phiếu, tên khách, số HĐ hoặc tên đồng hồ..." value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- Tất cả tiến độ bảo hành --</option>
                <option value="chua_bh" <?= ($statusFilter === 'chua_bh') ? 'selected' : '' ?>>Chưa bảo hành (Máy đang ở khách)</option>
                <option value="dang_bh" <?= ($statusFilter === 'dang_bh') ? 'selected' : '' ?>>Đang bảo hành (Đã tiếp nhận máy)</option>
                <option value="da_tra" <?= ($statusFilter === 'da_tra') ? 'selected' : '' ?>>Đã hoàn thành & Trả máy</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-dark btn-sm rounded-pill w-100 fw-bold">Tìm kiếm</button>
            <a href="index.php?action=baohanh" class="btn btn-outline-secondary btn-sm rounded-pill" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<!-- Bảng Phiếu Bảo Hành -->
<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Số Phiếu BH</th>
                    <th>Đồng hồ Pin</th>
                    <th>Hóa đơn / Khách hàng</th>
                    <th>Thời hạn bảo hành</th>
                    <th class="text-center">Trạng thái hạn</th>
                    <th class="text-center">Tiến độ tiếp nhận</th>
                    <th>Nhân viên lưu vết (Lập / Nhận / Trả)</th>
                    <th class="text-end">Xử lý</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warranties)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Chưa có phiếu bảo hành nào phù hợp</td></tr>
                <?php else: ?>
                    <?php foreach ($warranties as $w): ?>
                        <?php
                        $conHan = !empty($w['con_han_bh']);
                        $tienDo = $w['tien_do_bh'] ?? 'Chưa bảo hành';
                        ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-light text-dark border"><?= htmlspecialchars($w['so_phieu_bh']) ?></span></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($w['ten_dh']) ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;">Mã: <?= htmlspecialchars($w['ma_dh']) ?> • <?= htmlspecialchars($w['ten_hang']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary">HĐ: <?= htmlspecialchars($w['so_hoa_don']) ?></div>
                                <div class="text-dark"><?= htmlspecialchars($w['ho_ten_kh'] ?? '') ?></div>
                                <small class="text-muted"><?= htmlspecialchars($w['sdt_kh'] ?? '') ?></small>
                            </td>
                            <td>
                                <div>Từ: <?= date('d/m/Y', strtotime($w['ngay_bat_dau'])) ?></div>
                                <div>Đến: <?= date('d/m/Y', strtotime($w['ngay_ket_thuc'])) ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($conHan): ?>
                                    <span class="badge bg-success">Còn Hạn BH</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Hết Hạn BH</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tienDo === 'Chưa bảo hành'): ?>
                                    <span class="badge bg-secondary">Chưa bảo hành</span>
                                <?php elseif ($tienDo === 'Đang bảo hành'): ?>
                                    <span class="badge bg-warning text-dark">Đang sửa chữa</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Đã hoàn thành</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 0.75rem;">
                                    <div><strong>Lập:</strong> <?= htmlspecialchars($w['ma_nv_lap'] ?? '—') ?></div>
                                    <div><strong>Nhận:</strong> <?= htmlspecialchars($w['ma_nv_nhan'] ?? '—') ?></div>
                                    <div><strong>Trả:</strong> <?= htmlspecialchars($w['ma_nv_giao_tra'] ?? '—') ?></div>
                                </div>
                            </td>
                            <td class="text-end">
                                <?php if (empty($w['ma_nv_nhan'])): ?>
                                    <!-- Chưa tiếp nhận: Nút tiếp nhận máy -->
                                    <button class="btn btn-warning btn-sm rounded-pill fw-bold text-dark py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalReceive<?= $w['so_phieu_bh'] ?>">
                                        Tiếp nhận
                                    </button>
                                <?php elseif (empty($w['ma_nv_giao_tra'])): ?>
                                    <!-- Đang sửa: Nút giao trả máy -->
                                    <button class="btn btn-success btn-sm rounded-pill fw-bold py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalComplete<?= $w['so_phieu_bh'] ?>">
                                        Giao trả
                                    </button>
                                <?php else: ?>
                                    <!-- Đã xong: Nút xem chi tiết -->
                                    <button class="btn btn-outline-secondary btn-sm rounded-pill py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalView<?= $w['so_phieu_bh'] ?>">
                                        Xem
                                    </button>
                                <?php endif; ?>

                                <!-- Modal Tiếp Nhận Bảo Hành -->
                                <div class="modal fade text-start" id="modalReceive<?= $w['so_phieu_bh'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Tiếp Nhận Bảo Hành: <?= htmlspecialchars($w['so_phieu_bh']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="index.php?action=baohanh&act=receive">
                                                <input type="hidden" name="so_phieu_bh" value="<?= htmlspecialchars($w['so_phieu_bh']) ?>">
                                                <div class="modal-body">
                                                    <p class="mb-2"><strong>Đồng hồ:</strong> <?= htmlspecialchars($w['ten_dh']) ?></p>
                                                    <p class="mb-3"><strong>Khách hàng:</strong> <?= htmlspecialchars($w['ho_ten_kh'] ?? '') ?> (<?= htmlspecialchars($w['sdt_kh'] ?? '') ?>)</p>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Tình trạng máy trước khi nhận sửa <span class="text-danger">*</span></label>
                                                        <textarea name="trang_thai_truoc_bh" class="form-control" rows="3" required placeholder="Ví dụ: Đồng hồ chạy chậm 5 phút/ngày, kim giây bị kẹt, máy hết pin..."></textarea>
                                                    </div>
                                                    <small class="text-muted d-block">* Nhân viên tiếp nhận sẽ ghi nhận tự động là: <strong><?= htmlspecialchars($maNv) ?></strong></small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark px-4">Xác Nhận Tiếp Nhận</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Hoàn Thành & Giao Trả -->
                                <div class="modal fade text-start" id="modalComplete<?= $w['so_phieu_bh'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Hoàn Tất & Giao Trả Máy: <?= htmlspecialchars($w['so_phieu_bh']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="index.php?action=baohanh&act=complete">
                                                <input type="hidden" name="so_phieu_bh" value="<?= htmlspecialchars($w['so_phieu_bh']) ?>">
                                                <div class="modal-body">
                                                    <div class="p-2 border rounded-3 bg-light mb-3">
                                                        <small class="text-muted d-block">Tình trạng khi nhận:</small>
                                                        <strong><?= htmlspecialchars($w['trang_thai_truoc_bh'] ?? 'Chưa ghi chú') ?></strong>
                                                        <div class="text-muted mt-1" style="font-size: 0.72rem;">Nhân viên tiếp nhận: <?= htmlspecialchars($w['ma_nv_nhan'] ?? '') ?></div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Kết quả sửa chữa & Tình trạng bàn giao <span class="text-danger">*</span></label>
                                                        <textarea name="trang_thai_sau_bh" class="form-control" rows="3" required placeholder="Ví dụ: Đã thay pin Maxell chính hãng, căn chỉnh kim giây, kiểm tra độ kín nước 10ATM hoạt động tốt..."></textarea>
                                                    </div>
                                                    <small class="text-muted d-block">* Nhân viên giao trả máy sẽ ghi nhận tự động là: <strong><?= htmlspecialchars($maNv) ?></strong></small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Hoàn Tất & Bàn Giao</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Xem Chi Tiết -->
                                <div class="modal fade text-start" id="modalView<?= $w['so_phieu_bh'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Lịch Sử Bảo Hành: <?= htmlspecialchars($w['so_phieu_bh']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-1"><strong>Sản phẩm:</strong> <?= htmlspecialchars($w['ten_dh']) ?></p>
                                                <p class="mb-2"><strong>Khách hàng:</strong> <?= htmlspecialchars($w['ho_ten_kh'] ?? '') ?></p>
                                                <hr>
                                                <p class="mb-1 text-danger"><strong>Tình trạng khi tiếp nhận:</strong></p>
                                                <div class="p-2 bg-light rounded-3 mb-2"><?= htmlspecialchars($w['trang_thai_truoc_bh'] ?? '—') ?></div>
                                                <small class="text-muted d-block mb-3">Nhân viên tiếp nhận: <strong><?= htmlspecialchars($w['ma_nv_nhan'] ?? '—') ?></strong></small>

                                                <p class="mb-1 text-success"><strong>Kết quả sửa chữa & Trả máy:</strong></p>
                                                <div class="p-2 bg-light rounded-3 mb-2"><?= htmlspecialchars($w['trang_thai_sau_bh'] ?? '—') ?></div>
                                                <small class="text-muted d-block">Nhân viên bàn giao: <strong><?= htmlspecialchars($w['ma_nv_giao_tra'] ?? '—') ?></strong></small>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Đóng</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
