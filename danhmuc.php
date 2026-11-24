<?php
/**
 * admin/danhmuc.php
 * Quản lý Loại Đồng Hồ Pin (loaidongho) & Thương Hiệu Hãng (hangdongho)
 */
require_once __DIR__ . '/../model/index.php';

$pageTitle = 'Quản lý Loại Đồng Hồ Pin & Hãng Sản Xuất';
$db = Database::getConnection();

$act = $_REQUEST['act'] ?? 'list';

// 1. Thao tác Loại đồng hồ
if ($act === 'add_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenLoai = trim($_POST['ten_loai'] ?? '');
    if (!empty($tenLoai)) {
        try {
            LoaiDongHoModel::create($tenLoai);
            $_SESSION['flash_success'] = "Đã thêm loại đồng hồ '{$tenLoai}' thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi thêm loại: " . $e->getMessage();
        }
    }
    header('Location: index.php?action=danhmuc&tab=loai');
    exit;
}

if ($act === 'edit_category' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maLoai = (int)($_POST['ma_loai'] ?? 0);
    $tenLoai = trim($_POST['ten_loai'] ?? '');
    if ($maLoai > 0 && !empty($tenLoai)) {
        try {
            LoaiDongHoModel::update($maLoai, $tenLoai);
            $_SESSION['flash_success'] = "Cập nhật loại đồng hồ thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi cập nhật: " . $e->getMessage();
        }
    }
    header('Location: index.php?action=danhmuc&tab=loai');
    exit;
}

if ($act === 'delete_category') {
    $maLoai = (int)($_GET['id'] ?? 0);
    if ($maLoai > 0) {
        try {
            LoaiDongHoModel::delete($maLoai);
            $_SESSION['flash_success'] = "Đã xóa loại đồng hồ!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Không thể xóa: Có sản phẩm đang thuộc loại đồng hồ này.";
        }
    }
    header('Location: index.php?action=danhmuc&tab=loai');
    exit;
}

// 2. Thao tác Hãng đồng hồ
if ($act === 'add_brand' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenHang = trim($_POST['ten_hang'] ?? '');
    if (!empty($tenHang)) {
        try {
            HangDongHoModel::create($tenHang);
            $_SESSION['flash_success'] = "Đã thêm hãng '{$tenHang}' thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi thêm hãng: " . $e->getMessage();
        }
    }
    header('Location: index.php?action=danhmuc&tab=hang');
    exit;
}

if ($act === 'edit_brand' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $maHang = (int)($_POST['ma_hang'] ?? 0);
    $tenHang = trim($_POST['ten_hang'] ?? '');
    if ($maHang > 0 && !empty($tenHang)) {
        try {
            HangDongHoModel::update($maHang, $tenHang);
            $_SESSION['flash_success'] = "Cập nhật thương hiệu thành công!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Lỗi khi cập nhật hãng: " . $e->getMessage();
        }
    }
    header('Location: index.php?action=danhmuc&tab=hang');
    exit;
}

if ($act === 'delete_brand') {
    $maHang = (int)($_GET['id'] ?? 0);
    if ($maHang > 0) {
        try {
            HangDongHoModel::delete($maHang);
            $_SESSION['flash_success'] = "Đã xóa hãng đồng hồ!";
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Không thể xóa: Có sản phẩm đang thuộc thương hiệu này.";
        }
    }
    header('Location: index.php?action=danhmuc&tab=hang');
    exit;
}

$categories = LoaiDongHoModel::allWithProductCount($db);
$brands = HangDongHoModel::allWithProductCount($db);
$currentTab = $_GET['tab'] ?? 'loai';

require __DIR__ . '/inc/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark mb-0">
        <i class="bi bi-tag-fill text-warning me-2"></i> Danh Mục Đồng Hồ Pin & Thương Hiệu
    </h4>
</div>

<!-- Tabs chọn phân loại -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link <?= ($currentTab === 'loai') ? 'active bg-warning text-dark fw-bold' : 'bg-white text-secondary' ?>" href="index.php?action=danhmuc&tab=loai">
            <i class="bi bi-watch me-1"></i> Loại Đồng Hồ Pin (3 Loại Chuẩn)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($currentTab === 'hang') ? 'active bg-warning text-dark fw-bold' : 'bg-white text-secondary' ?>" href="index.php?action=danhmuc&tab=hang">
            <i class="bi bi-buildings-fill me-1"></i> Thương Hiệu / Hãng Sản Xuất
        </a>
    </li>
</ul>

<?php if ($currentTab === 'loai'): ?>
    <!-- TAB 1: LOẠI ĐỒNG HỒ PIN -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thêm Loại Đồng Hồ Pin</h5>
                <form method="POST" action="index.php?action=danhmuc&act=add_category">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên Loại Pin <span class="text-danger">*</span></label>
                        <input type="text" name="ten_loai" class="form-control" required placeholder="Ví dụ: Đồng hồ Pin dây kim loại">
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill text-dark shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Thêm Loại Mới
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Danh Sách Loại Đồng Hồ Pin</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 80px;">Mã loại</th>
                                <th>Tên loại đồng hồ Pin</th>
                                <th class="text-center">Số lượng mẫu</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <?php $soLuongMauLoai = (int)($cat['so_luong_sp'] ?? $cat['so_luong_mau'] ?? $cat['so_luong_dh'] ?? 0); ?>
                                <tr>
                                    <td class="fw-bold">#<?= $cat['ma_loai'] ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($cat['ten_loai']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $soLuongMauLoai > 0 ? 'bg-primary' : 'bg-secondary' ?> rounded-pill px-3">
                                            <?= $soLuongMauLoai ?> sản phẩm
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEditCat<?= $cat['ma_loai'] ?>">
                                            <i class="bi bi-pencil-square"></i> Sửa
                                        </button>
                                        <a href="index.php?action=danhmuc&act=delete_category&id=<?= $cat['ma_loai'] ?>" class="btn btn-outline-danger btn-sm rounded-pill ms-1" onclick="return confirm('Bạn chắc chắn muốn xóa loại này?');">
                                            <i class="bi bi-trash"></i>
                                        </a>

                                        <!-- Modal Sửa Loại -->
                                        <div class="modal fade text-start" id="modalEditCat<?= $cat['ma_loai'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content rounded-4 border-0">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Sửa Loại Đồng Hồ Pin #<?= $cat['ma_loai'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="index.php?action=danhmuc&act=edit_category">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="ma_loai" value="<?= $cat['ma_loai'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Tên loại:</label>
                                                                <input type="text" name="ten_loai" class="form-control" value="<?= htmlspecialchars($cat['ten_loai']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark">Lưu Thay Đổi</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- TAB 2: THƯƠNG HIỆU / HÃNG -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Thêm Thương Hiệu</h5>
                <form method="POST" action="index.php?action=danhmuc&act=add_brand">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên Thương Hiệu <span class="text-danger">*</span></label>
                        <input type="text" name="ten_hang" class="form-control" required placeholder="Ví dụ: Casio, Seiko, Citizen...">
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold rounded-pill text-dark shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Thêm Hãng
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Danh Sách Thương Hiệu</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 80px;">Mã hãng</th>
                                <th>Tên thương hiệu</th>
                                <th class="text-center">Số lượng mẫu</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brands as $b): ?>
                                <?php $soLuongMauHang = (int)($b['so_luong_sp'] ?? $b['so_luong_mau'] ?? $b['so_luong_dh'] ?? 0); ?>
                                <tr>
                                    <td class="fw-bold">#<?= $b['ma_hang'] ?></td>
                                    <td>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($b['ten_hang']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $soLuongMauHang > 0 ? 'bg-primary' : 'bg-secondary' ?> rounded-pill px-3">
                                            <?= $soLuongMauHang ?> sản phẩm
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-outline-primary btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#modalEditBrand<?= $b['ma_hang'] ?>">
                                            <i class="bi bi-pencil-square"></i> Sửa
                                        </button>
                                        <a href="index.php?action=danhmuc&act=delete_brand&id=<?= $b['ma_hang'] ?>" class="btn btn-outline-danger btn-sm rounded-pill ms-1" onclick="return confirm('Bạn chắc chắn muốn xóa hãng này?');">
                                            <i class="bi bi-trash"></i>
                                        </a>

                                        <!-- Modal Sửa Hãng -->
                                        <div class="modal fade text-start" id="modalEditBrand<?= $b['ma_hang'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content rounded-4 border-0">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Sửa Thương Hiệu #<?= $b['ma_hang'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="index.php?action=danhmuc&act=edit_brand">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="ma_hang" value="<?= $b['ma_hang'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Tên thương hiệu:</label>
                                                                <input type="text" name="ten_hang" class="form-control" value="<?= htmlspecialchars($b['ten_hang']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Hủy</button>
                                                            <button type="submit" class="btn btn-warning rounded-pill fw-bold text-dark">Cập Nhật</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>