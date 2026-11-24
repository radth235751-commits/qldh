<?php
/**
 * admin/nhaphang.php
 * Phân hệ Quản lý Nhập kho: Nhà cung cấp (nhacungcap), Đơn đặt hàng nhập (dondathang),
 * Phiếu nhập kho (phieunhap, ct_phieunhap).
 * Tự động cập nhật tồn kho qua Trigger trg_cong_ton_khi_nhap và kiểm soát UNIQUE uk_pn_ddh.
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Quản lý Nhập kho & Nhà cung cấp';
$db = Database::getConnection();
$currentUser = $_SESSION['user'];
$maNv = $currentUser['ma_nv'] ?? 'NV01';

$act = $_REQUEST['act'] ?? 'purchase_orders';

// 1. Thêm Nhà Cung Cấp
if ($act === 'add_supplier' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maNcc = strtoupper(trim($_POST['ma_ncc'] ?? ''));
    $tenNcc = trim($_POST['ten_ncc'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');
    $sdt = trim($_POST['so_dien_thoai'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!empty($maNcc) && !empty($tenNcc)) {
        try {
            NhaCungCapModel::create([
                'ma_ncc'        => $maNcc,
                'ten_ncc'       => $tenNcc,
                'dia_chi'       => $diaChi,
                'so_dien_thoai' => $sdt,
                'email'         => $email
            ]);
            $_SESSION['flash_success'] = "Đã thêm nhà cung cấp '{$tenNcc}' ({$maNcc}) thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi: " . $e->getMessage();
        }
    }
    header('Location: index.php?action=nhaphang&act=suppliers');
    exit;
}

// 2. Tạo Đơn Đặt Hàng Nhập (PO)
if ($act === 'store_po' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maDdh = strtoupper(trim($_POST['ma_ddh'] ?? ''));
    $maNcc = trim($_POST['ma_ncc'] ?? '');
    $ngayDat = $_POST['ngay_dat'] ?? date('Y-m-d H:i:s');
    $items = $_POST['items'] ?? [];

    $cleanItems = [];
    foreach ($items as $it) {
        $maDh = trim($it['ma_dh'] ?? '');
        $sl = (int)($it['so_luong'] ?? 0);
        $dg = (float)($it['don_gia'] ?? 0);
        if (!empty($maDh) && $sl > 0 && $dg > 0) {
            $cleanItems[] = [
                'ma_dh'        => $maDh,
                'so_luong_dat' => $sl,
                'don_gia'      => $dg
            ];
        }
    }

    if (!empty($maDdh) && !empty($maNcc) && !empty($cleanItems)) {
        try {
            DonDatHangModel::createOrder($maDdh, $ngayDat, $maNcc, $maNv, $cleanItems);
            $_SESSION['flash_success'] = "Đã tạo đơn đặt hàng nhập #{$maDdh} thành công!";
            header('Location: index.php?action=nhaphang&act=purchase_orders');
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi tạo đơn đặt hàng: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng nhập đầy đủ mã đơn, nhà cung cấp và ít nhất 1 sản phẩm đặt.";
    }
    header('Location: index.php?action=nhaphang&act=create_po');
    exit;
}

// 3. Lập Phiếu Nhập Kho (Trigger trg_cong_ton_khi_nhap tự động tăng tồn kho)
if ($act === 'store_receipt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maPn = strtoupper(trim($_POST['ma_pn'] ?? ''));
    $maDdh = strtoupper(trim($_POST['ma_ddh'] ?? ''));
    $ngayNhap = $_POST['ngay_nhap'] ?? date('Y-m-d H:i:s');
    $items = $_POST['items'] ?? [];

    $cleanItems = [];
    foreach ($items as $maDh => $data) {
        $sl = (int)($data['so_luong_nhap'] ?? 0);
        $dg = (float)($data['don_gia_nhap'] ?? 0);
        if ($sl > 0) {
            $cleanItems[] = [
                'ma_dh'         => $maDh,
                'so_luong_nhap' => $sl,
                'don_gia_nhap'  => $dg
            ];
        }
    }

    if (!empty($maPn) && !empty($maDdh) && !empty($cleanItems)) {
        try {
            PhieuNhapModel::createReceipt($maPn, $ngayNhap, $maDdh, $maNv, $cleanItems);
            $_SESSION['flash_success'] = "Đã lập phiếu nhập kho #{$maPn} thành công! Trigger CSDL đã tự động cộng số lượng nhập vào tồn kho đồng hồ.";
            header('Location: index.php?action=nhaphang&act=goods_receipts');
            exit;
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi lập phiếu nhập: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Vui lòng nhập mã phiếu nhập và ít nhất 1 sản phẩm có số lượng nhập > 0.";
    }
    header("Location: index.php?action=nhaphang&act=create_receipt&ddh={$maDdh}");
    exit;
}

// Giao diện Sub-action
require __DIR__ . '/inc/header.php';
?>

<!-- Tab chuyển đổi -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-box-arrow-in-down text-info me-2"></i> Quản Lý Nhập Kho Đồng Hồ Pin</h4>
        <p class="text-secondary small mb-0">Nhà cung cấp, Đơn đặt hàng nhập & Phiếu nhập kho (Cộng tồn kho tự động qua Trigger)</p>
    </div>
    <div class="btn-group rounded-pill shadow-sm" role="group">
        <a href="index.php?action=nhaphang&act=purchase_orders" class="btn btn-sm <?= ($act === 'purchase_orders' || $act === 'create_po' || $act === 'detail_po') ? 'btn-warning fw-bold text-dark' : 'btn-light' ?>">
            <i class="bi bi-cart-check me-1"></i> Đơn Đặt Hàng Nhập
        </a>
        <a href="index.php?action=nhaphang&act=goods_receipts" class="btn btn-sm <?= ($act === 'goods_receipts' || $act === 'create_receipt' || $act === 'detail_receipt') ? 'btn-warning fw-bold text-dark' : 'btn-light' ?>">
            <i class="bi bi-box-arrow-in-down me-1"></i> Phiếu Nhập Kho
        </a>
        <a href="index.php?action=nhaphang&act=suppliers" class="btn btn-sm <?= ($act === 'suppliers') ? 'btn-warning fw-bold text-dark' : 'btn-light' ?>">
            <i class="bi bi-truck me-1"></i> Nhà Cung Cấp
        </a>
    </div>
</div>

<?php if ($act === 'suppliers'): ?>
    <!-- 1. QUẢN LÝ NHÀ CUNG CẤP -->
    <?php
    $suppliers = NhaCungCapModel::all();
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thêm Nhà Cung Cấp</h5>
                <form method="POST" action="index.php?action=nhaphang&act=add_supplier">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã NCC <span class="text-danger">*</span></label>
                        <input type="text" name="ma_ncc" class="form-control text-uppercase" required placeholder="NCC03..." maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên Nhà Cung Cấp <span class="text-danger">*</span></label>
                        <input type="text" name="ten_ncc" class="form-control" required placeholder="Công ty TNHH Phân Phối Đồng Hồ...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại</label>
                        <input type="tel" name="so_dien_thoai" class="form-control" placeholder="028...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="ncc@domain.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ</label>
                        <input type="text" name="dia_chi" class="form-control" placeholder="Địa chỉ trụ sở...">
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill text-dark shadow-sm">
                        Cập Nhật
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Danh Sách Nhà Cung Cấp</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Mã NCC</th>
                                <th>Tên nhà cung cấp</th>
                                <th>Số điện thoại</th>
                                <th>Email</th>
                                <th>Địa chỉ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td class="fw-bold"><span class="badge bg-light text-dark border"><?= htmlspecialchars($s['ma_ncc']) ?></span></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($s['ten_ncc']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['so_dien_thoai'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($s['dia_chi'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($act === 'create_po'): ?>
    <!-- 2. FORM TẠO ĐƠN ĐẶT HÀNG NHẬP -->
    <?php
    $suppliers = NhaCungCapModel::all();
    $watches = DongHoModel::all();
    $nextPoCode = DonDatHangModel::generateNextCode($db);
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <h5 class="fw-bold text-dark mb-0">Lập Đơn Đặt Hàng Nhập Mới (PO)</h5>
            <a href="index.php?action=nhaphang&act=purchase_orders" class="btn btn-outline-secondary btn-sm rounded-pill">
                &larr; Quay lại danh sách PO
            </a>
        </div>

        <form method="POST" action="index.php?action=nhaphang&act=store_po">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Mã Đơn Đặt <span class="text-danger">*</span></label>
                    <input type="text" name="ma_ddh" class="form-control text-uppercase fw-bold" required value="<?= htmlspecialchars($nextPoCode) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nhà Cung Cấp <span class="text-danger">*</span></label>
                    <select name="ma_ncc" class="form-select" required>
                        <option value="">-- Chọn Nhà Cung Cấp --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= htmlspecialchars($s['ma_ncc']) ?>">
                                <?= htmlspecialchars($s['ma_ncc']) ?> - <?= htmlspecialchars($s['ten_ncc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ngày Đặt Hàng</label>
                    <input type="datetime-local" name="ngay_dat" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-3">Danh Sách Sản Phẩm Cần Nhập:</h6>
            <div class="table-responsive mb-3">
                <table class="table table-bordered align-middle small" id="tablePoItems">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 250px;">Đồng hồ Pin</th>
                            <th style="width: 140px;" class="text-center">Số lượng đặt</th>
                            <th style="width: 180px;" class="text-end">Đơn giá nhập (VNĐ)</th>
                            <th style="width: 60px;" class="text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="items[0][ma_dh]" class="form-select form-select-sm" required>
                                    <option value="">-- Chọn đồng hồ --</option>
                                    <?php foreach ($watches as $w): ?>
                                        <option value="<?= htmlspecialchars($w['ma_dh']) ?>">
                                            <?= htmlspecialchars($w['ma_dh']) ?> - <?= htmlspecialchars($w['ten_dh']) ?> (Giá bán: <?= number_format($w['gia'], 0, ',', '.') ?>đ)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][so_luong]" class="form-control form-control-sm text-center fw-bold" required min="1" value="10">
                            </td>
                            <td>
                                <input type="number" name="items[0][don_gia]" class="form-control form-control-sm text-end fw-bold" required min="10000" step="1000" placeholder="2000000">
                            </td>
                            <td class="text-center"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark shadow-sm">
                <i class="bi bi-save me-1"></i> Lưu & Phát Hành Đơn Đặt Hàng Nhập
            </button>
        </form>
    </div>

<?php elseif ($act === 'create_receipt'): ?>
    <!-- 3. FORM LẬP PHIẾU NHẬP KHO TỪ ĐƠN ĐẶT HÀNG NHẬP -->
    <?php
    $maDdh = $_GET['ddh'] ?? '';
    $po = DonDatHangModel::findById($maDdh, $db);
    if (!$po) {
        $_SESSION['flash_error'] = "Không tìm thấy đơn đặt hàng nhập.";
        header('Location: index.php?action=nhaphang&act=purchase_orders');
        exit;
    }
    if (!empty($po['ma_pn'])) {
        $_SESSION['flash_error'] = "Đơn đặt này đã có phiếu nhập kho #{$po['ma_pn']}. Mỗi đơn chỉ được lập 1 phiếu nhập duy nhất!";
        header('Location: index.php?action=nhaphang&act=purchase_orders');
        exit;
    }
    $poItems = DonDatHangModel::getDetails($maDdh, $db);
    $nextReceiptCode = PhieuNhapModel::generateNextCode($db);
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-1">Lập Phiếu Nhập Kho Từ Đơn Đặt Hàng #<?= htmlspecialchars($maDdh) ?></h5>
                <small class="text-muted">Nhà cung cấp: <strong><?= htmlspecialchars($po['ten_ncc']) ?></strong> | Ngày đặt: <?= date('d/m/Y', strtotime($po['ngay_dat'])) ?></small>
            </div>
            <a href="index.php?action=nhaphang&act=purchase_orders" class="btn btn-outline-secondary btn-sm rounded-pill">
                &larr; Quay lại
            </a>
        </div>

        <div class="alert alert-info py-2 small mb-4">
            <i class="bi bi-info-circle-fill me-1"></i> <strong>Lưu ý:</strong> Khi bạn lưu phiếu nhập kho, Trigger CSDL <code>trg_cong_ton_khi_nhap</code> sẽ tự động cộng số lượng nhập vào tồn kho đồng hồ.
        </div>

        <form method="POST" action="index.php?action=nhaphang&act=store_receipt">
            <input type="hidden" name="ma_ddh" value="<?= htmlspecialchars($maDdh) ?>">

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mã Phiếu Nhập <span class="text-danger">*</span></label>
                    <input type="text" name="ma_pn" class="form-control text-uppercase fw-bold" required value="<?= htmlspecialchars($nextReceiptCode) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Ngày Nhập Kho</label>
                    <input type="datetime-local" name="ngay_nhap" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nhân Viên Lập Phiếu</label>
                    <input type="text" class="form-control bg-light" readonly value="<?= htmlspecialchars($currentUser['hoten']) ?> (<?= htmlspecialchars($maNv) ?>)">
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-3">Kiểm Đếm Thực Tế & Nhập Hàng:</h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Tên đồng hồ Pin</th>
                            <th class="text-center" style="width: 140px;">Số lượng đặt</th>
                            <th class="text-center" style="width: 150px;">Số lượng nhập thực tế <span class="text-danger">*</span></th>
                            <th class="text-end" style="width: 180px;">Đơn giá nhập (VNĐ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($poItems as $it): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($it['ma_dh']) ?></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($it['ten_dh']) ?></div>
                                    <small class="text-muted">Tồn kho hiện tại: <?= (int)$it['so_luong_ton'] ?> chiếc</small>
                                </td>
                                <td class="text-center fw-bold fs-6">
                                    <?= (int)$it['so_luong_dat'] ?>
                                </td>
                                <td>
                                    <!-- Mặc định đề xuất số lượng đặt, cho phép nhập thiếu -->
                                    <input type="number" name="items[<?= htmlspecialchars($it['ma_dh']) ?>][so_luong_nhap]" class="form-control form-control-sm text-center fw-bold text-success fs-6" required min="0" max="<?= (int)$it['so_luong_dat'] ?>" value="<?= (int)$it['so_luong_dat'] ?>">
                                </td>
                                <td>
                                    <input type="number" name="items[<?= htmlspecialchars($it['ma_dh']) ?>][don_gia_nhap]" class="form-control form-control-sm text-end fw-bold" required min="0" step="1000" value="<?= (float)$it['don_gia'] ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-box-arrow-in-down me-1"></i> Xác Nhận Nhập Kho & Kích Hoạt Trigger Tồn Kho
            </button>
        </form>
    </div>

<?php elseif ($act === 'goods_receipts'): ?>
    <!-- 4. DANH SÁCH PHIẾU NHẬP KHO -->
    <?php
    $receipts = PhieuNhapModel::allWithDetails($db);
    ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Danh Sách Phiếu Nhập Kho (phieunhap)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Mã PN</th>
                        <th>Ngày nhập</th>
                        <th>Mã Đơn Đặt (PO)</th>
                        <th>Nhà cung cấp</th>
                        <th>Nhân viên lập</th>
                        <th class="text-center">Tổng chiếc</th>
                        <th class="text-end">Tổng tiền nhập</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($receipts)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có phiếu nhập kho nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($receipts as $r): ?>
                            <tr>
                                <td class="fw-bold"><span class="badge bg-light text-dark border"><?= htmlspecialchars($r['ma_pn']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($r['ngay_nhap'])) ?></td>
                                <td class="fw-semibold text-primary">#<?= htmlspecialchars($r['ma_ddh']) ?></td>
                                <td><?= htmlspecialchars($r['ten_ncc'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($r['ma_nv']) ?></span>
                                    <?= htmlspecialchars($r['ten_nhanvien'] ?? '') ?>
                                </td>
                                <td class="text-center fw-bold"><?= (int)($r['tong_so_luong'] ?? $r['tong_so_luong_nhap'] ?? 0) ?> chiếc</td>
                                <td class="text-end fw-bold text-danger"><?= number_format((float)($r['tong_tien'] ?? $r['tong_tien_nhap'] ?? 0), 0, ',', '.') ?>đ</td>
                                <td class="text-center">
                                    <a href="index.php?action=nhaphang&act=detail_receipt&id=<?= urlencode($r['ma_pn']) ?>" class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2">
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

<?php elseif ($act === 'detail_receipt'): ?>
    <!-- 5. CHI TIẾT PHIẾU NHẬP KHO -->
    <?php
    $maPn = $_GET['id'] ?? '';
    $pn = PhieuNhapModel::findById($maPn, $db);
    if (!$pn):
    ?>
        <div class="alert alert-danger">Không tìm thấy phiếu nhập kho.</div>
    <?php else:
        $pnItems = PhieuNhapModel::getDetails($maPn, $db);
    ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h5 class="fw-bold text-dark mb-1">Chi Tiết Phiếu Nhập Kho: #<?= htmlspecialchars($pn['ma_pn']) ?></h5>
                    <small class="text-muted">Ngày nhập: <?= date('d/m/Y H:i', strtotime($pn['ngay_nhap'])) ?> | Đơn đặt hàng gốc: <strong>#<?= htmlspecialchars($pn['ma_ddh']) ?></strong></small>
                </div>
                <a href="index.php?action=nhaphang&act=goods_receipts" class="btn btn-outline-secondary btn-sm rounded-pill">
                    &larr; Quay lại danh sách
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 small">
                        <strong>Nhà Cung Cấp:</strong> <?= htmlspecialchars($pn['ten_ncc']) ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 small">
                        <strong>Nhân Viên Lập Phiếu:</strong> <?= htmlspecialchars($pn['ten_nhanvien'] ?? $pn['ma_nv']) ?> (<?= htmlspecialchars($pn['ma_nv']) ?>)
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded-3 small text-end">
                        <strong class="text-danger fs-6">Tổng Giá Trị Nhập: <?= number_format($pn['tong_tien'], 0, ',', '.') ?>đ</strong>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-3">Chi Tiết Hàng Hóa Thực Nhập:</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th>Ảnh</th>
                            <th>Mã ĐH</th>
                            <th>Tên đồng hồ Pin</th>
                            <th>Hãng & Loại</th>
                            <th class="text-center">Số lượng nhập</th>
                            <th class="text-end">Đơn giá nhập</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pnItems as $item): ?>
                            <tr>
                                <td>
                                    <img src="../<?= htmlspecialchars($item['image']) ?>" alt="" class="rounded border p-1" style="width: 40px; height: 40px; object-fit: contain;">
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($item['ma_dh']) ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($item['ten_dh']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($item['ten_hang']) ?> • <?= htmlspecialchars($item['ten_loai']) ?></td>
                                <td class="text-center fw-bold text-success fs-6"><?= (int)$item['so_luong_nhap'] ?> chiếc</td>
                                <td class="text-end"><?= number_format($item['don_gia_nhap'], 0, ',', '.') ?>đ</td>
                                <td class="text-end fw-bold"><?= number_format($item['thanh_tien'], 0, ',', '.') ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- 6. DANH SÁCH ĐƠN ĐẶT HÀNG NHẬP (DEFAULT) -->
    <?php
    $purchaseOrders = DonDatHangModel::allWithDetails($db);
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-dark mb-0">Danh Sách Đơn Đặt Hàng Nhập (dondathang)</h5>
        <a href="index.php?action=nhaphang&act=create_po" class="btn btn-warning btn-sm rounded-pill fw-bold text-dark shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Tạo Đơn Đặt Nhập Mới
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Mã Đơn Đặt (PO)</th>
                        <th>Ngày đặt</th>
                        <th>Nhà cung cấp</th>
                        <th>Nhân viên đặt</th>
                        <th class="text-center">Tổng chiếc</th>
                        <th class="text-end">Tổng tiền dự kiến</th>
                        <th class="text-center">Trạng thái nhập kho</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($purchaseOrders)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có đơn đặt hàng nhập nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchaseOrders as $po): ?>
                            <tr>
                                <td class="fw-bold"><span class="badge bg-light text-dark border">#<?= htmlspecialchars($po['ma_ddh']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($po['ngay_dat'])) ?></td>
                                <td><strong class="text-dark"><?= htmlspecialchars($po['ten_ncc']) ?></strong></td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($po['ma_nv']) ?></span>
                                    <?= htmlspecialchars($po['ten_nhanvien'] ?? '') ?>
                                </td>
                                <td class="text-center fw-bold"><?= (int)($po['tong_so_luong'] ?? 0) ?> chiếc</td>
                                <td class="text-end fw-bold"><?= number_format((float)($po['tong_tien'] ?? $po['tong_tien_dat'] ?? 0), 0, ',', '.') ?>đ</td>
                                <td class="text-center">
                                    <?php if (!empty($po['ma_pn'])): ?>
                                        <span class="badge bg-success">Đã nhập (<?= htmlspecialchars($po['ma_pn']) ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Chờ nhập kho</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (empty($po['ma_pn'])): ?>
                                        <a href="index.php?action=nhaphang&act=create_receipt&ddh=<?= urlencode($po['ma_ddh']) ?>" class="btn btn-success btn-sm rounded-pill fw-bold">
                                            <i class="bi bi-box-arrow-in-down me-1"></i> Nhập kho
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?action=nhaphang&act=detail_receipt&id=<?= urlencode($po['ma_pn']) ?>" class="btn btn-outline-info btn-sm rounded-pill">
                                            Xem phiếu nhập
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
