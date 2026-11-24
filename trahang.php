<?php
/**
 * admin/trahang.php
 * Quản lý Phiếu Trả Hàng (phieutrahang & ct_phieutrahang):
 * Xử lý 2 Trigger CSDL:
 * 1. trg_kiem_tra_han_tra_hang: Chặn và ném SQLSTATE '45000' nếu quá hạn 7 ngày từ ngày lập hóa đơn.
 * 2. trg_cong_ton_khi_tra: Tự động cộng lại số lượng trả vào tồn kho đồng hồ (Không UPDATE thủ công).
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Quản lý Đổi Trả Hàng (Quy định 7 ngày)';
$db = Database::getConnection();
$currentUser = $_SESSION['user'];
$maNv = $currentUser['ma_nv'] ?? 'NV01';

$act = $_REQUEST['act'] ?? 'list';

// 1. Lập Phiếu Trả Hàng (POST)
if ($act === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $soHoaDon = trim($_POST['so_hoa_don'] ?? '');
    $ngayTra = $_POST['ngay_tra'] ?? date('Y-m-d H:i:s');
    $items = $_POST['items'] ?? [];

    $cleanItems = [];
    foreach ($items as $it) {
        $maDh = trim($it['ma_dh'] ?? '');
        $sl = (int)($it['so_luong_tra'] ?? 0);
        $lyDo = trim($it['ly_do_tra'] ?? $it['ly_do'] ?? 'Đổi trả theo yêu cầu khách');
        if (!empty($maDh) && $sl > 0) {
            $cleanItems[] = [
                'ma_dh'        => $maDh,
                'so_luong_tra' => $sl,
                'ly_do'        => $lyDo
            ];
        }
    }

    if (!empty($soHoaDon) && !empty($cleanItems)) {
        try {
            $maPth = PhieuTraHangModel::createReturnOrder($soHoaDon, $maNv, $ngayTra, $cleanItems, $db);
            $_SESSION['flash_success'] = "Đã lập phiếu trả hàng #{$maPth} thành công! Trigger CSDL đã tự động hoàn tồn kho cho sản phẩm.";
            header('Location: index.php?action=trahang');
            exit;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '45000') || str_contains($msg, '7 ngày')) {
                $_SESSION['flash_error'] = "CHẶN TRẢ HÀNG: Hóa đơn này đã quá thời hạn 7 ngày đổi trả quy định! (Trigger trg_kiem_tra_han_tra_hang kích hoạt ngăn chặn)";
            } else {
                $_SESSION['flash_error'] = "Lỗi khi lập phiếu trả hàng: " . $msg;
            }
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng chọn hóa đơn và ít nhất 1 sản phẩm cần trả lại.";
    }
    header('Location: index.php?action=trahang&act=create');
    exit;
}

// 2. Form Lập Phiếu Trả Hàng
if ($act === 'create') {
    $returnableInvoices = PhieuTraHangModel::getReturnableInvoices($db);
    $selectedHd = $_GET['hd'] ?? ($returnableInvoices[0]['so_hoa_don'] ?? '');
    $invoiceItems = [];
    if (!empty($selectedHd)) {
        $invoiceItems = PhieuTraHangModel::getInvoiceItemsForReturn($selectedHd, $db);
    }
    $nextCode = PhieuTraHangModel::generateNextReturnCode($db);

    require __DIR__ . '/inc/header.php';
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Lập Phiếu Đổi Trả Hàng Mới (phieutrahang)</h5>
                <small class="text-muted">Áp dụng quy tắc kiểm tra thời hạn 7 ngày đổi trả qua Trigger CSDL</small>
            </div>
            <a href="index.php?action=trahang" class="btn btn-outline-secondary btn-sm rounded-pill">
                &larr; Danh sách phiếu trả
            </a>
        </div>

        <div class="alert alert-warning py-2 small mb-4">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Ràng buộc Trigger CSDL:</strong>
            <ul class="mb-0 ps-3 mt-1">
                <li><code>trg_kiem_tra_han_tra_hang</code>: Sẽ tự động chặn và báo lỗi nếu ngày trả cách ngày lập hóa đơn > 7 ngày.</li>
                <li><code>trg_cong_ton_khi_tra</code>: Sẽ tự động cộng tồn kho vào bảng <code>dongho</code> ngay khi lưu.</li>
            </ul>
        </div>

        <form method="POST" action="index.php?action=trahang&act=store">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Mã Phiếu Trả Dự Kiến</label>
                    <input type="text" class="form-control bg-light fw-bold" readonly value="<?= htmlspecialchars($nextCode) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Hóa Đơn Mua Hàng <span class="text-danger">*</span></label>
                    <select name="so_hoa_don" id="selectInvoice" class="form-select" required onchange="window.location.href='index.php?action=trahang&act=create&hd=' + this.value">
                        <option value="">-- Chọn Hóa Đơn --</option>
                        <?php foreach ($returnableInvoices as $rhi): ?>
                            <option value="<?= htmlspecialchars($rhi['so_hoa_don']) ?>" <?= ($selectedHd === $rhi['so_hoa_don']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rhi['so_hoa_don']) ?> (Mua: <?= date('d/m/Y', strtotime($rhi['ngay_lap_hd'])) ?>) — <?= htmlspecialchars($rhi['ho_ten_kh'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ngày Trả Hàng</label>
                    <input type="datetime-local" name="ngay_tra" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </div>

            <?php if (!empty($invoiceItems)): ?>
                <h6 class="fw-bold text-dark mb-3">Chọn Sản Phẩm Cần Trả Lại Từ Hóa Đơn:</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Ảnh</th>
                                <th>Mã ĐH</th>
                                <th>Tên Đồng Hồ Pin</th>
                                <th class="text-center" style="width: 120px;">Đã mua</th>
                                <th class="text-center" style="width: 140px;">Số lượng trả <span class="text-danger">*</span></th>
                                <th style="min-width: 200px;">Lý do trả hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoiceItems as $idx => $it): ?>
                                <tr>
                                    <td>
                                        <img src="../<?= htmlspecialchars($it['image']) ?>" alt="" class="rounded border p-1" style="width: 40px; height: 40px; object-fit: contain;">
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($it['ma_dh']) ?></td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($it['ten_dh']) ?></strong>
                                    </td>
                                    <td class="text-center fw-bold fs-6"><?= (int)$it['so_luong_mua'] ?></td>
                                    <td>
                                        <input type="hidden" name="items[<?= $idx ?>][ma_dh]" value="<?= htmlspecialchars($it['ma_dh']) ?>">
                                        <input type="number" name="items[<?= $idx ?>][so_luong_tra]" class="form-control form-control-sm text-center fw-bold text-danger" min="0" max="<?= (int)$it['so_luong_mua'] ?>" value="1">
                                    </td>
                                    <td>
                                        <input type="text" name="items[<?= $idx ?>][ly_do_tra]" class="form-control form-control-sm" placeholder="Khách đổi mẫu, lỗi thẩm mỹ...">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-arrow-return-left me-1"></i> Xác Nhận Trả Hàng & Kích Hoạt Trigger Hoàn Tồn
                </button>
            <?php else: ?>
                <div class="alert alert-secondary py-3 text-center">
                    Vui lòng chọn một Hóa đơn ở trên để tải danh sách sản phẩm có thể trả.
                </div>
            <?php endif; ?>
        </form>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

// 3. Chi tiết Phiếu Trả Hàng
if ($act === 'detail') {
    $maPth = $_GET['id'] ?? '';
    $pth = PhieuTraHangModel::findById($maPth, $db);
    if (!$pth) {
        $_SESSION['flash_error'] = "Không tìm thấy phiếu trả hàng.";
        header('Location: index.php?action=trahang');
        exit;
    }
    $pthItems = PhieuTraHangModel::getDetails($maPth, $db);

    require __DIR__ . '/inc/header.php';
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Chi Tiết Phiếu Trả Hàng: #<?= htmlspecialchars($pth['ma_pth']) ?></h5>
                <small class="text-muted">Ngày lập phiếu trả: <?= date('d/m/Y H:i', strtotime($pth['ngay_tra'])) ?></small>
            </div>
            <a href="index.php?action=trahang" class="btn btn-outline-secondary btn-sm rounded-pill">
                &larr; Danh sách phiếu trả
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 small">
                    <strong>Hóa Đơn Mua Gốc:</strong> 
                    <span class="badge bg-success-subtle text-success fs-6"><?= htmlspecialchars($pth['so_hoa_don']) ?></span>
                    <div class="text-muted mt-1">Ngày mua: <?= date('d/m/Y', strtotime($pth['ngay_lap_hd'])) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 small">
                    <strong>Khách Hàng:</strong> <?= htmlspecialchars($pth['ho_ten_kh'] ?? '') ?>
                    <div class="text-muted mt-1">Điện thoại: <?= htmlspecialchars($pth['sdt_kh'] ?? '') ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 small">
                    <strong>Nhân Viên Lập Phiếu Trả:</strong> <?= htmlspecialchars($pth['ten_nhanvien'] ?? $pth['ma_nv']) ?> (<?= htmlspecialchars($pth['ma_nv']) ?>)
                </div>
            </div>
        </div>

        <h6 class="fw-bold text-dark mb-3">Sản Phẩm Đã Nhập Trả Lại Kho:</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Ảnh</th>
                        <th>Mã ĐH</th>
                        <th>Tên Đồng Hồ Pin</th>
                        <th>Hãng sản xuất</th>
                        <th class="text-center" style="width: 140px;">Số lượng trả</th>
                        <th>Lý do trả hàng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pthItems as $it): ?>
                        <tr>
                            <td>
                                <img src="../<?= htmlspecialchars($it['image']) ?>" alt="" class="rounded border p-1" style="width: 40px; height: 40px; object-fit: contain;">
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($it['ma_dh']) ?></td>
                            <td><strong class="text-dark"><?= htmlspecialchars($it['ten_dh']) ?></strong></td>
                            <td><?= htmlspecialchars($it['ten_hang']) ?></td>
                            <td class="text-center fw-bold text-danger fs-6"><?= (int)$it['so_luong_tra'] ?> chiếc</td>
                            <td><?= htmlspecialchars($it['ly_do'] ?? $it['ly_do_tra'] ?? 'Đổi trả theo yêu cầu') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

// 4. Danh sách Phiếu Trả Hàng (Default)
$search = trim($_GET['search'] ?? '');
$returns = PhieuTraHangModel::getAll($search, $db);

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-arrow-return-left text-danger me-2"></i> Quản Lý Đổi Trả Hàng</h4>
        <p class="text-secondary small mb-0">Ràng buộc 7 ngày qua Trigger trg_kiem_tra_han_tra_hang và tự động hoàn tồn qua trg_cong_ton_khi_tra</p>
    </div>
    <a href="index.php?action=trahang&act=create" class="btn btn-warning rounded-pill btn-sm fw-bold text-dark shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Lập Phiếu Trả Hàng Mới
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Mã Phiếu Trả</th>
                    <th>Ngày trả</th>
                    <th>Số Hóa Đơn</th>
                    <th>Khách hàng</th>
                    <th class="text-center">Số lượng trả</th>
                    <th>Nhân viên lập</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có phiếu trả hàng nào</td></tr>
                <?php else: ?>
                    <?php foreach ($returns as $ret): ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-light text-dark border">#<?= htmlspecialchars($ret['ma_pth']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($ret['ngay_tra'])) ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($ret['so_hoa_don']) ?></span>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($ret['ho_ten_kh'] ?? '') ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($ret['sdt_kh'] ?? '') ?></div>
                            </td>
                            <td class="text-center fw-bold text-danger fs-6"><?= (int)$ret['tong_so_luong_tra'] ?> chiếc</td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($ret['ma_nv']) ?></span>
                                <?= htmlspecialchars($ret['ten_nhanvien'] ?? '') ?>
                            </td>
                            <td class="text-end">
                                <a href="index.php?action=trahang&act=detail&id=<?= urlencode($ret['ma_pth']) ?>" class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2">
                                    Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
