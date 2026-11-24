<?php
/**
 * public/cart.php
 * Giỏ hàng mua sắm: Thêm, sửa số lượng, xóa sản phẩm, tính tổng tiền
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$act = $_REQUEST['act'] ?? 'view';

// 1. Thêm vào giỏ hàng
if ($act === 'add') {
    $maDh = $_REQUEST['id'] ?? '';
    $quantity = max(1, (int)($_REQUEST['quantity'] ?? 1));

    if (!empty($maDh)) {
        $dh = DongHoModel::findById($maDh);
        if ($dh && (int)$dh['status'] === 1) {
            $stock = (int)$dh['so_luong_ton'];
            if ($stock < 1) {
                $_SESSION['flash_error'] = "Sản phẩm '{$dh['ten_dh']}' hiện đã hết hàng.";
            } else {
                // Tính giá khuyến mãi nếu có
                $db = Database::getConnection();
                $stmtKm = $db->prepare("
                    SELECT ct.phan_tram_giam 
                    FROM ct_khuyenmai ct
                    JOIN khuyenmai km ON ct.ma_km = km.ma_km
                    WHERE ct.ma_dh = :ma_dh AND NOW() BETWEEN km.ngay_bat_dau AND km.ngay_ket_thuc
                    ORDER BY ct.phan_tram_giam DESC LIMIT 1
                ");
                $stmtKm->execute([':ma_dh' => $maDh]);
                $km = $stmtKm->fetch();

                $giaGoc = (float)$dh['gia'];
                $ptGiam = $km ? (int)$km['phan_tram_giam'] : 0;
                $donGia = ($ptGiam > 0) ? round($giaGoc * (1 - $ptGiam / 100), 2) : $giaGoc;

                $currentQtyInCart = isset($_SESSION['cart'][$maDh]) ? (int)$_SESSION['cart'][$maDh]['so_luong'] : 0;
                $newQty = $currentQtyInCart + $quantity;

                if ($newQty > $stock) {
                    $newQty = $stock;
                    $_SESSION['flash_error'] = "Chỉ có thể đặt tối đa {$stock} chiếc '{$dh['ten_dh']}'.";
                } else {
                    $_SESSION['flash_success'] = "Đã thêm '{$dh['ten_dh']}' vào giỏ hàng thành công!";
                }

                $_SESSION['cart'][$maDh] = [
                    'ma_dh'         => $dh['ma_dh'],
                    'ten_dh'        => $dh['ten_dh'],
                    'image'         => $dh['image'],
                    'thoi_gian_bh'  => $dh['thoi_gian_bh'],
                    'so_luong'      => $newQty,
                    'don_gia'       => $donGia,
                    'gia_goc'       => $giaGoc,
                    'phan_tram_giam'=> $ptGiam,
                    'so_luong_ton'  => $stock,
                ];
            }
        } else {
            $_SESSION['flash_error'] = "Sản phẩm không hợp lệ.";
        }
    }
    header('Location: index.php?action=cart');
    exit;
}

// 2. Cập nhật số lượng
if ($act === 'update') {
    $quantities = $_POST['quantities'] ?? [];
    foreach ($quantities as $maDh => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$maDh]);
        } elseif (isset($_SESSION['cart'][$maDh])) {
            $dh = DongHoModel::findById($maDh);
            $stock = $dh ? (int)$dh['so_luong_ton'] : 0;
            if ($qty > $stock) {
                $_SESSION['cart'][$maDh]['so_luong'] = $stock;
                $_SESSION['flash_error'] = "Sản phẩm '{$_SESSION['cart'][$maDh]['ten_dh']}' chỉ còn {$stock} chiếc trong kho.";
            } else {
                $_SESSION['cart'][$maDh]['so_luong'] = $qty;
            }
        }
    }
    if (empty($_SESSION['flash_error'])) {
        $_SESSION['flash_success'] = "Giỏ hàng đã được cập nhật thành công!";
    }
    header('Location: index.php?action=cart');
    exit;
}

// 3. Xóa sản phẩm khỏi giỏ
if ($act === 'delete') {
    $maDh = $_GET['id'] ?? '';
    if (!empty($maDh) && isset($_SESSION['cart'][$maDh])) {
        unset($_SESSION['cart'][$maDh]);
        $_SESSION['flash_success'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
    }
    header('Location: index.php?action=cart');
    exit;
}

// 4. Xóa trắng giỏ hàng
if ($act === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = "Đã làm trống giỏ hàng.";
    header('Location: index.php?action=cart');
    exit;
}

// Tính toán tổng tiền
$cartItems = $_SESSION['cart'];
$totalAmount = 0.0;
$totalCount = 0;
foreach ($cartItems as $item) {
    $totalAmount += ($item['don_gia'] * $item['so_luong']);
    $totalCount += $item['so_luong'];
}

$pageTitle = 'Giỏ hàng của bạn - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-4">
    <h3 class="fw-bold text-dark mb-4">
        <i class="bi bi-cart3 text-warning me-2"></i> Giỏ Hàng Mua Sắm
        <span class="fs-6 fw-normal text-muted">(<?= $totalCount ?> sản phẩm)</span>
    </h3>

    <?php if (empty($cartItems)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4 bg-white">
            <div class="mb-3">
                <i class="bi bi-cart-x text-secondary opacity-50 display-1"></i>
            </div>
            <h4 class="fw-bold text-dark">Giỏ hàng của bạn đang trống!</h4>
            <p class="text-secondary mb-4">Hãy chọn ngay những mẫu đồng hồ Pin chính hãng ưng ý nhất để nhận ưu đãi bảo hành 24 tháng.</p>
            <div>
                <a href="index.php?action=home" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Khám Phá Đồng Hồ Ngay
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Danh sách sản phẩm trong giỏ -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-3">
                    <form action="index.php?action=cart&act=update" method="POST">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="min-width: 260px;">Sản phẩm</th>
                                        <th scope="col" class="text-center">Đơn giá</th>
                                        <th scope="col" class="text-center" style="width: 140px;">Số lượng</th>
                                        <th scope="col" class="text-end">Thành tiền</th>
                                        <th scope="col" class="text-center" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $maDh => $item): ?>
                                        <?php $thanhTien = $item['don_gia'] * $item['so_luong']; ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['ten_dh']) ?>" class="rounded-3 border p-1 bg-white" style="width: 65px; height: 65px; object-fit: contain;">
                                                    <div>
                                                        <a href="index.php?action=detail&id=<?= urlencode($item['ma_dh']) ?>" class="fw-bold text-dark text-decoration-none">
                                                            <?= htmlspecialchars($item['ten_dh']) ?>
                                                        </a>
                                                        <div class="small text-muted">Mã: <?= htmlspecialchars($item['ma_dh']) ?> • BH <?= (int)$item['thoi_gian_bh'] ?>T</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="fw-semibold text-danger">
                                                    <?= number_format($item['don_gia'], 0, ',', '.') ?>đ
                                                </span>
                                                <?php if (!empty($item['phan_tram_giam']) && $item['phan_tram_giam'] > 0): ?>
                                                    <div class="text-muted text-decoration-line-through small">
                                                        <?= number_format($item['gia_goc'], 0, ',', '.') ?>đ
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="quantities[<?= htmlspecialchars($maDh) ?>]" class="form-control form-control-sm text-center fw-bold mx-auto" value="<?= (int)$item['so_luong'] ?>" min="1" max="<?= (int)$item['so_luong_ton'] ?>" style="width: 80px;">
                                                <small class="text-muted d-block mt-1">Còn <?= (int)$item['so_luong_ton'] ?></small>
                                            </td>
                                            <td class="text-end fw-bold text-dark">
                                                <?= number_format($thanhTien, 0, ',', '.') ?>đ
                                            </td>
                                            <td class="text-center">
                                                <a href="index.php?action=cart&act=delete&id=<?= urlencode($maDh) ?>" class="btn btn-outline-danger btn-sm border-0 rounded-circle" title="Xóa">
                                                    <i class="bi bi-trash3"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="index.php?action=home" class="btn btn-outline-secondary btn-sm rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Tiếp tục chọn mẫu
                            </a>
                            <div class="d-flex gap-2">
                                <a href="index.php?action=cart&act=clear" class="btn btn-outline-danger btn-sm rounded-pill" onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?');">
                                    <i class="bi bi-trash me-1"></i> Xóa hết
                                </a>
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Cập nhật giỏ hàng
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tóm tắt thanh toán -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3">Tóm Tắt Đơn Hàng</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tổng số lượng:</span>
                        <strong class="text-dark"><?= $totalCount ?> chiếc</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary">Tạm tính:</span>
                        <strong class="text-dark"><?= number_format($totalAmount, 0, ',', '.') ?>đ</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-secondary">Phí vận chuyển:</span>
                        <span class="badge bg-success-subtle text-success fw-bold">MIỄN PHÍ</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fw-bold text-dark fs-5">Tổng thanh toán:</span>
                        <span class="text-danger fw-bold fs-4"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="index.php?action=checkout" class="btn btn-warning btn-lg fw-bold rounded-pill text-dark shadow-sm">
                            <i class="bi bi-check2-circle me-1"></i> TIẾN HÀNH ĐẶT HÀNG
                        </a>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded-3 text-secondary small">
                        <i class="bi bi-shield-check text-warning me-1"></i> Thanh toán an toàn, bảo mật thông tin 100%. Khách hàng được kiểm tra hàng trước khi thanh toán (COD).
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/inc/bottom.php'; ?>
