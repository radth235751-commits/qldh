<?php
/**
 * public/payment_qr.php
 * Trang hiển thị mã QR thanh toán chuyển khoản ngân hàng cho đơn hàng
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập để xem thông tin thanh toán.';
    header('Location: index.php?action=login');
    exit;
}

$maPd = (int)($_GET['ma_pd'] ?? 0);
if ($maPd <= 0) {
    $_SESSION['flash_error'] = 'Mã đơn hàng không hợp lệ.';
    header('Location: index.php?action=orders');
    exit;
}

$db = Database::getConnection();
$userId = (int)$currentUser['id'];
$maKh = (int)($currentUser['ma_kh'] ?? 0);
if ($maKh === 0) {
    $stmtKh = $db->prepare("SELECT ma_kh FROM khachhang WHERE nguoidung_id = :uid");
    $stmtKh->execute([':uid' => $userId]);
    $maKh = (int)$stmtKh->fetchColumn();
}

// Lấy thông tin đơn hàng và kiểm tra quyền sở hữu
$order = PhieuDatModel::findById($maPd, $db);
if (!$order) {
    $_SESSION['flash_error'] = 'Không tìm thấy đơn hàng #PD' . $maPd;
    header('Location: index.php?action=orders');
    exit;
}

// Kiểm tra đơn hàng thuộc về khách hàng đang đăng nhập
if ((int)$order['ma_kh'] !== $maKh) {
    $_SESSION['flash_error'] = 'Bạn không có quyền xem thông tin thanh toán của đơn hàng này.';
    header('Location: index.php?action=orders');
    exit;
}

// Thông tin chuyển khoản cửa hàng (cấu hình cố định)
$bankInfo = [
    'bank_name'    => 'Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank)',
    'bank_short'   => 'Vietcombank',
    'account_no'   => '9921472765',
    'account_name' => 'NEANG RA',
    'branch'       => 'Chi nhánh Long Xuyên - An Giang',
];

$tongTien = (float)$order['tong_tien'];
$noiDungCK = "THANHTOAN PD{$maPd}";

// Chuỗi QR theo chuẩn VietQR (đơn giản: thông tin thanh toán dạng text)
$qrContent = "Ngan hang: {$bankInfo['bank_short']}\n"
           . "STK: {$bankInfo['account_no']}\n"
           . "Chu TK: {$bankInfo['account_name']}\n"
           . "So tien: " . number_format($tongTien, 0, '', '') . "\n"
           . "Noi dung: {$noiDungCK}";

$pageTitle = "Thanh toán QR - Đơn #PD{$maPd} - WatchStore";
require __DIR__ . '/inc/top.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?action=home" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?action=orders" class="text-decoration-none">Đơn hàng</a></li>
            <li class="breadcrumb-item active">Thanh toán QR #PD<?= $maPd ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Thông tin chuyển khoản -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 mx-auto mb-3">
                        <i class="bi bi-bank display-4"></i>
                    </div>
                    <h4 class="fw-bold text-dark">Chuyển Khoản Ngân Hàng</h4>
                    <p class="text-secondary small">Đơn hàng <strong class="text-dark">#PD<?= $maPd ?></strong> — Vui lòng chuyển khoản chính xác thông tin bên dưới</p>
                </div>

                <div class="bg-light rounded-4 p-4 mb-4">
                    <table class="table table-borderless mb-0 small">
                        <tbody>
                            <tr>
                                <td class="text-muted fw-semibold" style="width: 140px;">Ngân hàng:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($bankInfo['bank_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Số tài khoản:</td>
                                <td>
                                    <span class="fw-bold text-primary fs-5" id="accountNo"><?= htmlspecialchars($bankInfo['account_no']) ?></span>
                                    <button type="button" class="btn btn-outline-primary btn-sm ms-2 rounded-pill copy-btn" data-copy="<?= htmlspecialchars($bankInfo['account_no']) ?>" title="Sao chép STK">
                                        <i class="bi bi-clipboard"></i> Sao chép
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Chủ tài khoản:</td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($bankInfo['account_name']) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Chi nhánh:</td>
                                <td class="text-dark"><?= htmlspecialchars($bankInfo['branch']) ?></td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted fw-semibold pt-3">Số tiền:</td>
                                <td class="pt-3">
                                    <span class="fw-bold text-danger fs-4" id="transferAmount"><?= number_format($tongTien, 0, ',', '.') ?>đ</span>
                                    <button type="button" class="btn btn-outline-danger btn-sm ms-2 rounded-pill copy-btn" data-copy="<?= number_format($tongTien, 0, '', '') ?>" title="Sao chép số tiền">
                                        <i class="bi bi-clipboard"></i> Sao chép
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted fw-semibold">Nội dung CK:</td>
                                <td>
                                    <span class="fw-bold text-success fs-5" id="transferContent"><?= htmlspecialchars($noiDungCK) ?></span>
                                    <button type="button" class="btn btn-outline-success btn-sm ms-2 rounded-pill copy-btn" data-copy="<?= htmlspecialchars($noiDungCK) ?>" title="Sao chép nội dung CK">
                                        <i class="bi bi-clipboard"></i> Sao chép
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-warning shadow-sm rounded-3 small mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Lưu ý quan trọng:</strong> Vui lòng ghi đúng nội dung chuyển khoản <strong class="text-danger"><?= htmlspecialchars($noiDungCK) ?></strong> để hệ thống tự động xác nhận thanh toán. Nhân viên sẽ liên hệ xác nhận sau khi nhận được khoản chuyển.
                </div>
            </div>
        </div>

        <!-- Mã QR -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-center">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="bi bi-qr-code text-warning me-1"></i> Mã QR Thanh Toán
                </h5>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <p class="text-muted small mb-3">Quét mã QR bằng ứng dụng ngân hàng để chuyển khoản nhanh</p>

                <!-- Thông tin đơn hàng tóm tắt -->
                <div class="bg-light rounded-3 p-3 text-start small">
                    <p class="mb-1"><strong>Đơn hàng:</strong> <span class="text-primary fw-bold">#PD<?= $maPd ?></span></p>
                    <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($order['ho_ten_nguoi_nhan']) ?></p>
                    <p class="mb-1"><strong>SĐT:</strong> <?= htmlspecialchars($order['sdt_nguoi_nhan']) ?></p>
                    <p class="mb-1"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['dia_chi_nguoi_nhan']) ?></p>
                    <p class="mb-1"><strong>Số sản phẩm:</strong> <?= (int)$order['tong_so_luong'] ?> món</p>
                    <p class="mb-0"><strong>Trạng thái:</strong>
                        <span class="badge bg-warning text-dark"><?= htmlspecialchars($order['trang_thai']) ?></span>
                    </p>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <a href="index.php?action=orders" class="btn btn-outline-dark rounded-pill fw-bold">
                        <i class="bi bi-arrow-left me-1"></i> Quay lại Đơn hàng
                    </a>
                    <a href="index.php?action=home" class="btn btn-warning rounded-pill fw-bold text-dark">
                        <i class="bi bi-bag-heart me-1"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code JS (nội bộ) -->
<script src="assets/js/qrcode.min.js"></script>
<script>
// Vẽ mã QR
document.addEventListener('DOMContentLoaded', function() {
    var qrContainer = document.getElementById('qrcode');
    if (qrContainer) {
        new QRCode(qrContainer, {
            text: <?= json_encode($qrContent, JSON_UNESCAPED_UNICODE) ?>,
            width: 220,
            height: 220,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    }
});

// Sao chép nhanh
document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = this.getAttribute('data-copy');
        navigator.clipboard.writeText(text).then(function() {
            var original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Đã sao chép!';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-primary', 'btn-outline-danger', 'btn-outline-success');
            setTimeout(function() {
                btn.innerHTML = original;
                btn.classList.remove('btn-success');
                // Phục hồi class cũ
                if (btn.closest('tr') && btn.getAttribute('data-copy').match(/^\d{10,}$/)) {
                    btn.classList.add('btn-outline-primary');
                }
            }, 2000);
        }).catch(function() {
            // Fallback cho trình duyệt cũ
            var input = document.createElement('textarea');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Đã sao chép!';
            setTimeout(function() { location.reload(); }, 1500);
        });
    });
});
</script>

<?php require __DIR__ . '/inc/bottom.php'; ?>
