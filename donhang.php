<?php
/**
 * admin/donhang.php
 * Quản lý Đơn Đặt Hàng Trực Tuyến (phieudat) & Hóa Đơn Bán Lẻ (hoadon).
 * Quy trình: Khách đặt -> Nhân viên duyệt -> Phân công giao hàng ->
 * Tự động xuất 1 Hóa đơn duy nhất (uk_hd_pd), trừ tồn kho, tự động sinh Phiếu bảo hành cho từng chiếc.
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Quản lý Đơn Đặt Hàng & Hóa Đơn Bán Lẻ';
$db = Database::getConnection();
$currentUser = $_SESSION['user'];
$maNv = $currentUser['ma_nv'] ?? 'NV01';

$act = $_REQUEST['act'] ?? 'list';

// 1. Duyệt đơn hàng (Bước 1: Chờ duyệt -> Đã duyệt)
if ($act === 'approve') {
    $maPd = (int)($_GET['id'] ?? 0);
    if ($maPd > 0) {
        try {
            PhieuDatModel::approveOrder($maPd, $maNv);
            $_SESSION['flash_success'] = "Đã duyệt đơn hàng #{$maPd} thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi duyệt đơn: " . $e->getMessage();
        }
    }
    header("Location: index.php?action=donhang&act=detail&id={$maPd}");
    exit;
}

// 2. Giao hàng & Tự động xuất Hóa đơn + Phiếu bảo hành (Đã duyệt -> Đang giao hàng)
if (($act === 'ship' || $act === 'assign_delivery') && ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id']))) {
    $maPd = (int)($_POST['ma_pd'] ?? $_GET['id'] ?? 0);
    if ($maPd > 0) {
        try {
            $soHoaDon = PhieuDatModel::shipAndGenerateInvoice($maPd, $maNv);
            $_SESSION['flash_success'] = "Đã chuyển đơn hàng #{$maPd} sang trạng thái 'Đang giao hàng' và tự động xuất Hóa đơn #{$soHoaDon} kèm Phiếu bảo hành điện tử thành công! Tồn kho đã được trừ tương ứng.";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi xử lý: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Mã đơn hàng không hợp lệ.";
    }
    header("Location: index.php?action=donhang&act=detail&id={$maPd}");
    exit;
}

// 3. Hoàn tất đơn hàng ('Đang giao hàng' -> 'Hoàn tất')
if ($act === 'complete') {
    $maPd = (int)($_GET['id'] ?? $_POST['ma_pd'] ?? 0);
    if ($maPd > 0) {
        try {
            PhieuDatModel::completeOrder($maPd);
            $_SESSION['flash_success'] = "Đơn hàng #{$maPd} đã được xác nhận hoàn tất thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi hoàn tất đơn hàng: " . $e->getMessage();
        }
    }
    header("Location: index.php?action=donhang&act=detail&id={$maPd}");
    exit;
}

// 3. Danh sách Hóa đơn bán lẻ
if ($act === 'invoices') {
    $invoices = HoaDonModel::allWithDetails($db);
    require __DIR__ . '/inc/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-receipt-cutoff text-success me-2"></i> Danh Sách Hóa Đơn Bán Lẻ</h4>
            <p class="text-secondary small mb-0">Theo dõi toàn bộ hóa đơn bán hàng trực tuyến đã xuất và thông tin bảo hành kèm theo</p>
        </div>
        <a href="index.php?action=donhang" class="btn btn-outline-secondary btn-sm rounded-pill">
            &larr; Quản lý đơn đặt hàng
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Số Hóa Đơn</th>
                        <th>Ngày lập</th>
                        <th>Mã Đơn Đặt</th>
                        <th>Khách hàng</th>
                        <th>Nhân viên lập</th>
                        <th class="text-end">Tổng tiền thanh toán</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Chưa có hóa đơn nào</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $hd): ?>
                            <tr>
                                <td class="fw-bold"><span class="badge bg-success-subtle text-success fs-6"><?= htmlspecialchars($hd['so_hoa_don']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($hd['ngay_lap_hd'])) ?></td>
                                <td class="fw-semibold text-primary">#<?= $hd['ma_pd'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <i class="bi bi-person text-secondary me-1"></i><?= htmlspecialchars($hd['ten_khach_hang'] ?: ($hd['ho_ten_kh'] ?? 'Khách hàng')) ?>
                                    </div>
                                    <?php if (!empty($hd['sdt_khach_hang']) || !empty($hd['sdt_kh'])): ?>
                                        <div class="text-muted small"><i class="bi bi-telephone text-secondary me-1"></i><?= htmlspecialchars($hd['sdt_khach_hang'] ?: $hd['sdt_kh']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($hd['ma_nv'])): ?>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($hd['ma_nv']) ?></span>
                                    <?php endif; ?>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($hd['ten_nhan_vien'] ?: ($hd['ten_nhanvien'] ?? 'Hệ thống')) ?></span>
                                </td>
                                <td class="text-end fw-bold text-danger fs-6"><?= number_format($hd['tong_tien'], 0, ',', '.') ?>đ</td>
                                <td class="text-center">
                                    <a href="index.php?action=donhang&act=invoice_detail&id=<?= urlencode($hd['so_hoa_don']) ?>" class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2">
                                        Xem hóa đơn
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

// 4. Chi tiết Hóa đơn bán lẻ & in
if ($act === 'invoice_detail') {
    $soHd = $_GET['id'] ?? '';
    $hd = HoaDonModel::findByCode($soHd, $db);
    if (!$hd) {
        $_SESSION['flash_error'] = "Không tìm thấy hóa đơn.";
        header('Location: index.php?action=donhang&act=invoices');
        exit;
    }
    $warranties = PhieuBaoHanhModel::getBySoHoaDon($soHd, $db);
    $orderItems = PhieuDatModel::getDetails((int)$hd['ma_pd'], $db);

    require __DIR__ . '/inc/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-receipt text-success me-2"></i> Hóa Đơn Bán Lẻ: <?= htmlspecialchars($hd['so_hoa_don']) ?></h4>
            <span class="text-muted small">Ngày lập: <?= date('d/m/Y H:i', strtotime($hd['ngay_lap_hd'])) ?> | Đơn đặt hàng gốc: #<?= $hd['ma_pd'] ?></span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm rounded-pill">
                <i class="bi bi-printer me-1"></i> In Hóa Đơn
            </button>
            <a href="index.php?action=donhang&act=invoices" class="btn btn-outline-secondary btn-sm rounded-pill">
                &larr; Quay lại
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="row g-3 mb-4 pb-3 border-bottom">
            <div class="col-md-6">
                <h6 class="fw-bold text-dark mb-2">ĐƠN VỊ BÁN HÀNG:</h6>
                <p class="mb-1 fw-bold text-warning">WATCHSTORE — HỆ THỐNG ĐỒNG HỒ PIN CHÍNH HÃNG</p>
                <p class="text-secondary small mb-1">Địa chỉ: 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh</p>
                <p class="text-secondary small mb-0">Hotline: 1900 6868 | Nhân viên xuất đơn: <strong><?= htmlspecialchars($hd['ten_nhan_vien'] ?: ($hd['ten_nhanvien'] ?? $hd['ma_nv'])) ?> (<?= htmlspecialchars($hd['ma_nv']) ?>)</strong></p>
            </div>
            <div class="col-md-6 text-md-end">
                <h6 class="fw-bold text-dark mb-2">KHÁCH HÀNG:</h6>
                <p class="mb-1 fw-bold text-dark"><?= htmlspecialchars($hd['ten_khach_hang'] ?: ($hd['ho_ten_kh'] ?? 'Khách hàng')) ?></p>
                <p class="text-secondary small mb-1">Điện thoại: <?= htmlspecialchars($hd['sdt_khach_hang'] ?: ($hd['sdt_kh'] ?? '—')) ?></p>
                <p class="text-secondary small mb-0">Địa chỉ giao: <?= htmlspecialchars($hd['dia_chi_nguoi_nhan'] ?? $hd['dia_chi_giao'] ?? '—') ?></p>
            </div>
        </div>

        <h6 class="fw-bold text-dark mb-3">DANH SÁCH SẢN PHẨM:</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Mã ĐH</th>
                        <th>Tên Đồng Hồ Pin</th>
                        <th>Bảo hành</th>
                        <th class="text-center" style="width: 90px;">SL</th>
                        <th class="text-end" style="width: 140px;">Đơn giá</th>
                        <th class="text-end" style="width: 150px;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $stt = 1; foreach ($orderItems as $it): ?>
                        <tr>
                            <td class="text-center"><?= $stt++ ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($it['ma_dh']) ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($it['ten_dh']) ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($it['ten_hang']) ?> • <?= htmlspecialchars($it['ten_loai']) ?></div>
                            </td>
                            <td><?= (int)$it['thoi_gian_bh'] ?> Tháng</td>
                            <td class="text-center fw-bold"><?= (int)$it['so_luong'] ?></td>
                            <td class="text-end"><?= number_format($it['don_gia'], 0, ',', '.') ?>đ</td>
                            <td class="text-end fw-bold"><?= number_format($it['thanh_tien'], 0, ',', '.') ?>đ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end fs-6">TỔNG CỘNG TIỀN THANH TOÁN:</th>
                        <th class="text-end text-danger fs-5"><?= number_format($hd['tong_tien'], 0, ',', '.') ?>đ</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Phiếu bảo hành sinh tự động -->
        <h6 class="fw-bold text-primary mb-3">
            <i class="bi bi-shield-check me-1"></i> PHIẾU BẢO HÀNH ĐIỆN TỬ ĐÃ SINH TỰ ĐỘNG (<?= count($warranties) ?> CHIẾC):
        </h6>
        <div class="row row-cols-1 row-cols-md-3 g-2">
            <?php foreach ($warranties as $w): ?>
                <div class="col">
                    <div class="p-3 border rounded-3 bg-light small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge bg-secondary"><?= htmlspecialchars($w['so_phieu_bh']) ?></span>
                            <span class="badge bg-success">Còn BH 24T</span>
                        </div>
                        <strong class="text-dark d-block text-truncate"><?= htmlspecialchars($w['ten_dh']) ?></strong>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">
                            Bắt đầu: <?= date('d/m/Y', strtotime($w['ngay_bat_dau'])) ?> &rarr; Hết hạn: <?= date('d/m/Y', strtotime($w['ngay_ket_thuc'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

// 5. Chi tiết Đơn đặt hàng (act = detail)
if ($act === 'detail') {
    $maPd = (int)($_GET['id'] ?? 0);
    $order = PhieuDatModel::findById($maPd, $db);
    if (!$order) {
        $_SESSION['flash_error'] = "Không tìm thấy đơn hàng #{$maPd}.";
        header('Location: index.php?action=donhang');
        exit;
    }
    $orderItems = PhieuDatModel::getDetails($maPd, $db);
    $staffList = NhanVienModel::all($db);

    require __DIR__ . '/inc/header.php';
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                Chi Tiết Đơn Đặt Hàng: #<?= $order['ma_pd'] ?>
            </h4>
            <?php
            $detailBadge = match ($order['trang_thai']) {
                'Chờ duyệt' => 'bg-warning text-dark',
                'Đã duyệt'  => 'bg-info text-white',
                'Đang giao hàng' => 'bg-primary text-white',
                'Đã phân công nhân viên giao hàng' => 'bg-primary text-white',
                'Hoàn tất'  => 'bg-success text-white',
                default     => 'bg-secondary text-white'
            };
            ?>
            <span class="badge <?= $detailBadge ?> me-2"><?= htmlspecialchars($order['trang_thai']) ?></span>
            <span class="text-muted small">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></span>
        </div>
        <a href="index.php?action=donhang" class="btn btn-outline-secondary btn-sm rounded-pill">
            &larr; Danh sách đơn hàng
        </a>
    </div>

    <div class="row g-4 mb-4">
        <!-- Thông tin khách & người nhận -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thông Tin Người Nhận</h5>
                <?php
                $customerFullName = trim(($order['kh_ho'] ?? '') . ' ' . ($order['kh_ten'] ?? ''));
                $recipientName = !empty($customerFullName) ? $customerFullName : ($order['ho_ten_nguoi_nhan'] ?? '');
                ?>
                <p class="mb-1"><strong>Họ tên người nhận:</strong> <span class="text-dark fw-bold"><?= htmlspecialchars($recipientName) ?></span></p>
                <p class="mb-1"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['sdt_nguoi_nhan']) ?></p>
                <p class="mb-1"><strong>Địa chỉ giao:</strong> <?= htmlspecialchars($order['dia_chi_nguoi_nhan']) ?></p>
                <p class="mb-0"><strong>Tài khoản khách:</strong> <?= htmlspecialchars($customerFullName ?: 'Khách hàng') ?> (<?= htmlspecialchars($order['kh_email']) ?>)</p>
            </div>
        </div>

        <!-- Trạng thái xử lý & Giao hàng -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Tiến Độ Xử Lý Nghiệp Vụ</h5>

                <!-- Bước 1: Duyệt đơn -->
                <div class="mb-3">
                    <strong>1. Nhân viên duyệt đơn:</strong>
                    <?php if (!empty($order['ma_nv_duyet'])): ?>
                        <span class="text-success fw-bold ms-1"><i class="bi bi-check-circle-fill"></i> Đã duyệt bởi <?= htmlspecialchars($order['ten_nv_duyet'] ?? $order['ma_nv_duyet']) ?></span>
                    <?php else: ?>
                        <span class="text-warning fw-bold ms-1"><i class="bi bi-hourglass-split"></i> Chưa duyệt</span>
                        <a href="index.php?action=donhang&act=approve&id=<?= $order['ma_pd'] ?>" class="btn btn-sm btn-warning rounded-pill fw-bold ms-2" onclick="return confirm('Xác nhận duyệt đơn hàng này?');">
                            Duyệt đơn ngay
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Bước 2: Giao hàng & Xuất Hóa Đơn -->
                <div>
                    <strong>2. Giao hàng & Xuất Hóa Đơn:</strong>
                    <?php if (!empty($order['so_hoa_don'])): ?>
                        <div class="text-success fw-bold mt-1">
                            <i class="bi bi-receipt me-1"></i> Đã xuất hóa đơn: 
                            <a href="index.php?action=donhang&act=invoice_detail&id=<?= urlencode($order['so_hoa_don']) ?>" class="badge bg-success text-decoration-none">
                                <?= htmlspecialchars($order['so_hoa_don']) ?>
                            </a>
                            <div class="small fw-normal text-muted mt-1">
                                Trạng thái: <span class="badge bg-primary"><?= htmlspecialchars($order['trang_thai']) ?></span>
                                <?php if (!empty($order['ngay_gio_giao'])): ?>
                                    | Ngày xuất kho: <?= date('d/m/Y H:i', strtotime($order['ngay_gio_giao'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($order['trang_thai'] === 'Đang giao hàng' || $order['trang_thai'] === 'Đã phân công nhân viên giao hàng'): ?>
                            <div class="mt-3">
                                <a href="index.php?action=donhang&act=complete&id=<?= $order['ma_pd'] ?>" 
                                   class="btn btn-success btn-sm rounded-pill fw-bold"
                                   onclick="return confirm('Xác nhận khách đã nhận hàng thành công và chuyển đơn hàng #<?= $order['ma_pd'] ?> sang Hoàn tất?');">
                                    <i class="bi bi-check-circle-fill me-1"></i> Hoàn Tất Đơn Hàng
                                </a>
                            </div>
                        <?php elseif ($order['trang_thai'] === 'Hoàn tất'): ?>
                            <div class="mt-2 text-success small">
                                <i class="bi bi-patch-check-fill me-1"></i> Đơn hàng đã hoàn tất thành công.
                            </div>
                        <?php endif; ?>
                    <?php elseif ($order['trang_thai'] === 'Đã duyệt'): ?>
                        <div class="mt-2">
                            <form method="POST" action="index.php?action=donhang&act=ship" onsubmit="return confirm('Xác nhận xuất kho giao hàng và tự động xuất Hóa đơn + Phiếu bảo hành cho đơn #<?= $order['ma_pd'] ?>? Tồn kho sẽ được trừ tương ứng.');">
                                <input type="hidden" name="ma_pd" value="<?= $order['ma_pd'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-bold">
                                    <i class="bi bi-box-seam me-1"></i> Giao Hàng & Xuất Hóa Đơn
                                </button>
                            </form>
                        </div>
                    <?php elseif ($order['trang_thai'] === 'Chờ duyệt'): ?>
                        <div class="text-muted small mt-1">Vui lòng bấm <strong>Duyệt đơn ngay</strong> ở Bước 1 trước khi giao hàng & xuất hóa đơn.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Chi Tiết Sản Phẩm Đặt Mua</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Ảnh</th>
                        <th>Mã ĐH</th>
                        <th>Tên Đồng Hồ Pin</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Thành tiền</th>
                        <th class="text-center">Tồn kho hiện tại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $it): ?>
                        <tr>
                            <td>
                                <img src="../<?= htmlspecialchars($it['image']) ?>" alt="" class="rounded border p-1" style="width: 45px; height: 45px; object-fit: contain;">
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($it['ma_dh']) ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($it['ten_dh']) ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($it['ten_hang']) ?> • <?= htmlspecialchars($it['ten_loai']) ?></div>
                            </td>
                            <td class="text-center fw-bold fs-6"><?= (int)$it['so_luong'] ?></td>
                            <td class="text-end"><?= number_format($it['don_gia'], 0, ',', '.') ?>đ</td>
                            <td class="text-end fw-bold"><?= number_format($it['thanh_tien'], 0, ',', '.') ?>đ</td>
                            <td class="text-center">
                                <span class="badge <?= ((int)$it['so_luong_ton'] >= (int)$it['so_luong']) ? 'bg-success' : 'bg-danger' ?>">
                                    Còn <?= (int)$it['so_luong_ton'] ?> chiếc
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end fs-6">TỔNG TIỀN ĐƠN HÀNG:</th>
                        <th class="text-end text-danger fs-5"><?= number_format($order['tong_tien'], 0, ',', '.') ?>đ</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php
    require __DIR__ . '/inc/footer.php';
    exit;
}

// 6. Danh sách Đơn Đặt Hàng Trực Tuyến (Default)
$orders = PhieuDatModel::allWithDetails($db);
require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1"><i class="bi bi-cart-check-fill text-warning me-2"></i> Đơn Đặt Hàng Trực Tuyến (phieudat)</h4>
        <p class="text-secondary small mb-0">Tiếp nhận đơn, duyệt đơn và giao hàng & xuất hóa đơn tự động</p>
    </div>
    <a href="index.php?action=donhang&act=invoices" class="btn btn-outline-success btn-sm rounded-pill fw-bold">
        <i class="bi bi-receipt me-1"></i> Xem Danh Sách Hóa Đơn
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Mã Đơn</th>
                    <th>Ngày đặt</th>
                    <th>Khách hàng</th>
                    <th>Địa chỉ nhận hàng</th>
                    <th class="text-center">Số lượng</th>
                    <th class="text-end">Tổng tiền</th>
                    <th class="text-center">Trạng thái</th>
                    <th class="text-center">Hóa đơn</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Chưa có đơn hàng trực tuyến nào</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $od): ?>
                        <?php
                        $stBadge = match ($od['trang_thai']) {
                            'Chờ duyệt' => 'bg-warning text-dark',
                            'Đã duyệt'  => 'bg-info text-white',
                            'Đang giao hàng' => 'bg-primary text-white',
                            'Đã phân công nhân viên giao hàng' => 'bg-primary text-white',
                            'Hoàn tất'  => 'bg-success text-white',
                            default     => 'bg-secondary text-white'
                        };
                        $khName = trim(($od['kh_ho'] ?? '') . ' ' . ($od['kh_ten'] ?? ''));
                        $displayRecipient = !empty($khName) ? $khName : $od['ho_ten_nguoi_nhan'];
                        ?>
                        <tr>
                            <td class="fw-bold"><span class="badge bg-light text-dark border">#<?= $od['ma_pd'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($od['ngay_dat'])) ?></td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($displayRecipient) ?></strong>
                                <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($od['sdt_nguoi_nhan']) ?></div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($od['dia_chi_nguoi_nhan']) ?>">
                                    <?= htmlspecialchars($od['dia_chi_nguoi_nhan']) ?>
                                </div>
                            </td>
                            <td class="text-center fw-bold"><?= (int)$od['tong_so_luong'] ?> chiếc</td>
                            <td class="text-end fw-bold text-danger"><?= number_format($od['tong_tien'], 0, ',', '.') ?>đ</td>
                            <td class="text-center">
                                <span class="badge <?= $stBadge ?>"><?= htmlspecialchars($od['trang_thai']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($od['so_hoa_don'])): ?>
                                    <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($od['so_hoa_don']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="index.php?action=donhang&act=detail&id=<?= $od['ma_pd'] ?>" class="btn btn-outline-primary btn-sm rounded-pill py-0 px-2">
                                    Chi tiết & Duyệt
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
