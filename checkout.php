<?php
/**
 * public/checkout.php
 * Đặt hàng trực tuyến: Điền thông tin giao hàng, chọn sổ địa chỉ, tạo phiếu đặt (phieudat)
 */
require_once __DIR__ . '/../model/index.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? null;

// Chặn tuyệt đối Admin (1) và Nhân viên (2) thực hiện đặt hàng
if ($currentUser && in_array((int)($currentUser['loai'] ?? 0), [1, 2], true)) {
    unset($_SESSION['cart']);
    $_SESSION['flash_error'] = 'Tài khoản Quản trị viên / Nhân viên không được phép thực hiện chức năng mua hàng.';
    header('Location: index.php?action=home');
    exit;
}

$cartItems = $_SESSION['cart'] ?? [];
if (empty($cartItems)) {
    $_SESSION['flash_error'] = 'Giỏ hàng của bạn đang trống. Vui lòng chọn sản phẩm trước khi thanh toán.';
    header('Location: index.php?action=home');
    exit;
}

// Bắt buộc đăng nhập tài khoản Khách hàng
if (!$currentUser) {
    $_SESSION['flash_error'] = 'Vui lòng đăng nhập hoặc đăng ký tài khoản để tiến hành đặt hàng.';
    $_SESSION['return_url'] = 'index.php?action=checkout';
    header('Location: index.php?action=login');
    exit;
}

$db = Database::getConnection();
$userId = (int)$currentUser['id'];
$maKh = (int)($currentUser['ma_kh'] ?? 0);

// Liên kết hoặc khởi tạo bản ghi khachhang cho tài khoản khách (loai = 3)
if ($maKh === 0) {
    $stmtKh = $db->prepare("SELECT ma_kh FROM khachhang WHERE nguoidung_id = :uid");
    $stmtKh->execute([':uid' => $userId]);
    $maKh = (int)$stmtKh->fetchColumn();
    if ($maKh === 0) {
        $hoTenParts = explode(' ', trim($currentUser['hoten'] ?? 'Khách Hàng'));
        $ho = $hoTenParts[0] ?? 'Khách';
        $ten = count($hoTenParts) > 1 ? implode(' ', array_slice($hoTenParts, 1)) : 'Hàng';
        $stmtInsKh = $db->prepare("INSERT INTO khachhang (ho, ten, dia_chi, so_dien_thoai, email, nguoidung_id) VALUES (:ho, :ten, '', :sdt, :email, :uid)");
        $stmtInsKh->execute([
            ':ho'    => $ho,
            ':ten'   => $ten,
            ':sdt'   => $currentUser['sodienthoai'] ?? '',
            ':email' => $currentUser['email'] ?? '',
            ':uid'   => $userId
        ]);
        $maKh = (int)$db->lastInsertId();
    }
    $_SESSION['user']['ma_kh'] = $maKh;
}

// Lấy thông tin họ tên đầy đủ từ bảng khachhang
$stmtKhInfo = $db->prepare("SELECT ho, ten, so_dien_thoai FROM khachhang WHERE ma_kh = :ma_kh");
$stmtKhInfo->execute([':ma_kh' => $maKh]);
$khInfo = $stmtKhInfo->fetch();
$customerFullName = trim(($khInfo['ho'] ?? '') . ' ' . ($khInfo['ten'] ?? ''));
if (empty($customerFullName)) {
    $customerFullName = trim($currentUser['hoten'] ?? 'Khách Hàng');
}

// Lấy danh sách sổ địa chỉ của người dùng
$savedAddresses = DiaChiModel::getByNguoiDungId($userId);

// Tính toán tổng tiền
$totalAmount = 0.0;
$totalCount = 0;
foreach ($cartItems as $item) {
    $totalAmount += ((float)$item['don_gia'] * (int)$item['so_luong']);
    $totalCount += (int)$item['so_luong'];
}

$errors = [];

// Xử lý khi bấm nút "Xác nhận đặt hàng"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTenNhan  = $customerFullName;
    $sdtNhan    = trim($_POST['sdt_nguoi_nhan'] ?? '');
    $diaChiNhan = trim($_POST['dia_chi_nguoi_nhan'] ?? '');
    $ghiChu     = trim($_POST['ghi_chu'] ?? '');
    $paymentMethod = trim($_POST['payment_method'] ?? 'COD');

    if (!in_array($paymentMethod, ['COD', 'BANK'], true)) {
        $paymentMethod = 'COD';
    }

    if (empty($hoTenNhan)) {
        $errors[] = 'Họ tên người nhận không được để trống.';
    }
    if (empty($sdtNhan) || !preg_match('/^[0-9]{9,11}$/', $sdtNhan)) {
        $errors[] = 'Số điện thoại người nhận không hợp lệ (9 - 11 chữ số).';
    }
    if (empty($diaChiNhan)) {
        $errors[] = 'Địa chỉ giao hàng không được để trống.';
    }

    if (empty($errors)) {
        try {
            $ptLabel = ($paymentMethod === 'BANK') ? 'Chuyển khoản QR' : 'COD';
            $diaChiDayDu = $diaChiNhan . " [TT: {$ptLabel}]" . ($ghiChu !== '' ? " (Ghi chú: {$ghiChu})" : "");

            $orderData = [
                'ho_ten_nguoi_nhan'  => $customerFullName,
                'dia_chi_nguoi_nhan' => $diaChiDayDu,
                'sdt_nguoi_nhan'     => $sdtNhan,
                'ma_kh'              => $maKh
            ];

            // Tạo phiếu đặt hàng và chi tiết phiếu đặt trong Transaction
            $maPd = PhieuDatModel::createOrder($orderData, array_values($cartItems));

            // Làm trống giỏ hàng sau khi đặt thành công
            unset($_SESSION['cart']);

            if ($paymentMethod === 'BANK') {
                $_SESSION['flash_success'] = "Đặt hàng thành công! Mã đơn #PD{$maPd}. Vui lòng quét mã QR hoặc chuyển khoản theo thông tin bên dưới.";
                header("Location: index.php?action=payment_qr&ma_pd={$maPd}");
                exit;
            } else {
                $_SESSION['flash_success'] = "Đặt hàng thành công! Mã đơn đặt hàng của bạn là #PD{$maPd}. Nhân viên sẽ sớm liên hệ xác nhận.";
                header('Location: index.php?action=orders');
                exit;
            }
        } catch (Throwable $e) {
            $errors[] = "Lỗi khi xử lý đơn hàng: " . $e->getMessage();
        }
    }
}

$defaultPhone = $khInfo['so_dien_thoai'] ?? $currentUser['sodienthoai'] ?? '';
$defaultAddress = '';
if (!empty($savedAddresses)) {
    foreach ($savedAddresses as $addr) {
        if (!empty($addr['macdinh']) || !empty($addr['mac_dinh'])) {
            $defaultAddress = $addr['diachi'] ?? $addr['dia_chi_chi_tiet'] ?? '';
            break;
        }
    }
    if (empty($defaultAddress)) {
        $defaultAddress = $savedAddresses[0]['diachi'] ?? $savedAddresses[0]['dia_chi_chi_tiet'] ?? '';
    }
}

$selectedPayment = $_POST['payment_method'] ?? 'COD';
$pageTitle = 'Tiến hành Đặt hàng - WatchStore';
require __DIR__ . '/inc/top.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?action=home" class="text-decoration-none">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="index.php?action=cart" class="text-decoration-none">Giỏ hàng</a></li>
            <li class="breadcrumb-item active">Thanh toán & Đặt hàng</li>
        </ol>
    </nav>

    <h3 class="fw-bold text-dark mb-4">
        <i class="bi bi-credit-card-2-front text-warning me-2"></i> Xác Nhận & Đặt Hàng
    </h3>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Có lỗi xảy ra:</h6>
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?action=checkout">
        <div class="row g-4">
            <!-- Thông tin giao hàng -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3">
                        <i class="bi bi-geo-alt-fill text-warning me-1"></i> Thông Tin Người Nhận & Địa Chỉ Giao
                    </h5>

                    <?php if (!empty($savedAddresses)): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Chọn từ sổ địa chỉ đã lưu:</label>
                            <select id="selectSavedAddress" class="form-select form-select-sm">
                                <option value="">-- Nhập địa chỉ mới bên dưới --</option>
                                <?php foreach ($savedAddresses as $a): 
                                    $aText = $a['diachi'] ?? $a['dia_chi_chi_tiet'] ?? '';
                                    $aPhone = $a['so_dien_thoai'] ?? $defaultPhone;
                                    $isDef = !empty($a['macdinh']) || !empty($a['mac_dinh']);
                                ?>
                                    <option value="<?= htmlspecialchars($aText) ?>" data-phone="<?= htmlspecialchars($aPhone) ?>">
                                        <?= htmlspecialchars($aText) ?> <?= $isDef ? '[Mặc định]' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Họ và tên người nhận <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten_nguoi_nhan" id="inputName" class="form-control bg-light" required readonly value="<?= htmlspecialchars($customerFullName) ?>">
                        <small class="text-muted"><i class="bi bi-person-check-fill text-success me-1"></i>Tự động lấy theo họ tên tài khoản: <strong><?= htmlspecialchars($customerFullName) ?></strong></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Số điện thoại liên hệ <span class="text-danger">*</span></label>
                        <input type="tel" name="sdt_nguoi_nhan" id="inputPhone" class="form-control" required value="<?= htmlspecialchars($_POST['sdt_nguoi_nhan'] ?? $defaultPhone) ?>" placeholder="0901234567">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Địa chỉ nhận hàng chi tiết <span class="text-danger">*</span></label>
                        <textarea name="dia_chi_nguoi_nhan" id="inputAddress" rows="3" class="form-control" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($_POST['dia_chi_nguoi_nhan'] ?? $defaultAddress) ?></textarea>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold">Ghi chú giao hàng (nếu có)</label>
                        <input type="text" name="ghi_chu" class="form-control" value="<?= htmlspecialchars($_POST['ghi_chu'] ?? '') ?>" placeholder="Giao hàng giờ hành chính, gọi trước khi giao...">
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3">
                        <i class="bi bi-wallet2 text-warning me-1"></i> Phương Thức Thanh Toán
                    </h5>

                    <label for="pay_cod" id="box_cod" class="d-block p-3 border rounded-3 mb-3 <?= $selectedPayment === 'COD' ? 'border-warning bg-warning-subtle bg-opacity-25' : 'bg-white' ?>" style="cursor: pointer;">
                        <div class="form-check mb-0">
                            <input class="form-check-input me-2" type="radio" name="payment_method" id="pay_cod" value="COD" <?= $selectedPayment === 'COD' ? 'checked' : '' ?>>
                            <span class="fw-bold text-dark">
                                <i class="bi bi-cash-coin text-success fs-5 me-2"></i> Thanh toán tiền mặt khi nhận hàng (COD)
                            </span>
                            <div class="small fw-normal text-muted mt-1 ps-4">Được kiểm tra đồng hồ và thử máy trước khi thanh toán.</div>
                        </div>
                    </label>

                    <label for="pay_bank" id="box_bank" class="d-block p-3 border rounded-3 <?= $selectedPayment === 'BANK' ? 'border-warning bg-warning-subtle bg-opacity-25' : 'bg-white' ?>" style="cursor: pointer;">
                        <div class="form-check mb-0">
                            <input class="form-check-input me-2" type="radio" name="payment_method" id="pay_bank" value="BANK" <?= $selectedPayment === 'BANK' ? 'checked' : '' ?>>
                            <span class="fw-bold text-dark">
                                <i class="bi bi-qr-code-scan text-primary fs-5 me-2"></i> Chuyển khoản ngân hàng (QR Code)
                            </span>
                            <div class="small fw-normal text-muted mt-1 ps-4">Hệ thống cung cấp mã QR tự động điền số tiền và nội dung ngay sau khi xác nhận đơn.</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Xem lại đơn hàng -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white sticky-top" style="top: 90px;">
                    <h5 class="fw-bold text-dark border-bottom pb-3 mb-3">Đơn Hàng Của Bạn (<?= $totalCount ?> món)</h5>

                    <div class="mb-3" style="max-height: 280px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item): 
                            $imgRaw = trim((string)($item['image'] ?? ''));
                            $imgSrc = '';
                            if ($imgRaw !== '') {
                                if (is_file(__DIR__ . '/../' . $imgRaw)) {
                                    $imgSrc = '../' . $imgRaw;
                                } elseif (is_file(__DIR__ . '/../images/products/' . $imgRaw)) {
                                    $imgSrc = '../images/products/' . $imgRaw;
                                } elseif (is_file(__DIR__ . '/../images/' . $imgRaw)) {
                                    $imgSrc = '../images/' . $imgRaw;
                                }
                            }
                        ?>
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($imgSrc !== ''): ?>
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['ten_dh']) ?>" class="rounded border p-1" style="width: 48px; height: 48px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="rounded border d-flex align-items-center justify-content-center bg-light" style="width: 48px; height: 48px;">
                                            <i class="bi bi-watch text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark small" style="max-width: 185px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($item['ten_dh']) ?>
                                        </div>
                                        <small class="text-muted"><?= number_format((float)$item['don_gia'], 0, ',', '.') ?>đ × <?= (int)$item['so_luong'] ?></small>
                                    </div>
                                </div>
                                <span class="fw-bold text-dark small">
                                    <?= number_format((float)$item['don_gia'] * (int)$item['so_luong'], 0, ',', '.') ?>đ
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Tạm tính:</span>
                        <strong class="text-dark"><?= number_format($totalAmount, 0, ',', '.') ?>đ</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Phí giao hàng:</span>
                        <span class="badge bg-success-subtle text-success fw-bold">MIỄN PHÍ</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-baseline mb-4">
                        <span class="fw-bold text-dark fs-5">Tổng cần trả:</span>
                        <span class="text-danger fw-bold fs-3"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                    </div>

                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill text-dark shadow-sm">
                        <i class="bi bi-shield-check me-1"></i> XÁC NHẬN ĐẶT HÀNG
                    </button>

                    <div class="mt-3 text-center">
                        <a href="index.php?action=cart" class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Quay lại chỉnh sửa giỏ hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('selectSavedAddress')?.addEventListener('change', function() {
    if (this.value) {
        document.getElementById('inputAddress').value = this.value;
        const selectedOpt = this.options[this.selectedIndex];
        const phone = selectedOpt.getAttribute('data-phone');
        if (phone) document.getElementById('inputPhone').value = phone;
    }
});

function updatePaymentHighlight() {
    const isBank = document.getElementById('pay_bank')?.checked;
    const boxCod = document.getElementById('box_cod');
    const boxBank = document.getElementById('box_bank');
    if (boxCod && boxBank) {
        boxCod.classList.toggle('border-warning', !isBank);
        boxCod.classList.toggle('bg-warning-subtle', !isBank);
        boxCod.classList.toggle('bg-opacity-25', !isBank);
        boxBank.classList.toggle('border-warning', isBank);
        boxBank.classList.toggle('bg-warning-subtle', isBank);
        boxBank.classList.toggle('bg-opacity-25', isBank);
    }
}
document.getElementById('pay_cod')?.addEventListener('change', updatePaymentHighlight);
document.getElementById('pay_bank')?.addEventListener('change', updatePaymentHighlight);
</script>

<?php require __DIR__ . '/inc/bottom.php'; ?>