<?php
/**
 * admin/inc/header.php
 * Giao diện dùng chung phần đầu trang & Thanh bên (Sidebar) cho Phân hệ Quản trị
 * Yêu cầu đăng nhập tài khoản Quản trị viên (loai = 1) hoặc Nhân viên (loai = 2)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth_middleware.php';
requireAdminOrStaff();

$currentUser = $_SESSION['user'];
$isAdmin = isAdmin();
$currentAction = $_GET['action'] ?? 'dashboard';
$currentAct = $_GET['act'] ?? '';
$pageTitle = $pageTitle ?? 'Bảng điều khiển Quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Quản trị WatchStore</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            min-height: 100vh;
        }
        #sidebar {
            width: 260px;
            min-height: 100vh;
            background-color: #0f172a;
            color: #94a3b8;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
        }
        #sidebar .brand {
            padding: 20px 24px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            border-bottom: 1px solid #1e293b;
        }
        #sidebar .brand span {
            color: #d97706;
        }
        #sidebar .nav-section-title {
            padding: 14px 24px 6px;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            font-weight: 700;
        }
        #sidebar .nav-link {
            padding: 10px 24px;
            color: #cbd5e1;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        #sidebar .nav-link:hover {
            color: #ffffff;
            background-color: #1e293b;
        }
        #sidebar .nav-link.active {
            color: #ffffff;
            background-color: #1e293b;
            border-left-color: #d97706;
            font-weight: 600;
        }
        #content-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .admin-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
        }
        .admin-footer {
            margin-top: auto;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 16px 24px;
            color: #64748b;
            font-size: 0.875rem;
        }
        @media (max-width: 991.98px) {
            #sidebar {
                margin-left: -260px;
            }
            #sidebar.active {
                margin-left: 0;
            }
            #content-wrapper {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar Điều Hướng -->
    <nav id="sidebar">
        <div class="brand d-flex align-items-center justify-content-between">
            <a href="index.php?action=dashboard" class="text-white text-decoration-none">
                <i class="bi bi-watch text-warning me-2"></i> WATCH<span>STORE</span>
            </a>
        </div>

        <div class="py-3">
            <div class="nav-section-title">Tổng quan</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'dashboard') ? 'active' : '' ?>" href="index.php?action=dashboard">
                        <i class="bi bi-speedometer2 text-info"></i> Bảng điều khiển
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../public/index.php" target="_blank">
                        <i class="bi bi-box-arrow-up-right text-muted"></i> Xem cửa hàng (Web)
                    </a>
                </li>
            </ul>

            <!-- Nhóm Sản phẩm & Danh mục -->
            <div class="nav-section-title">Danh mục & Sản phẩm Pin</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'dongho' && $currentAct !== 'price_history') ? 'active' : '' ?>" href="index.php?action=dongho">
                        <i class="bi bi-watch text-warning"></i> Sản phẩm Đồng hồ Pin
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'danhmuc') ? 'active' : '' ?>" href="index.php?action=danhmuc">
                        <i class="bi bi-tag-fill text-info"></i> Loại Pin & Thương hiệu
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= (($currentAction === 'dongho' && $currentAct === 'price_history') || $currentAction === 'gia') ? 'active' : '' ?>" href="index.php?action=dongho&act=price_history">
                        <i class="bi bi-clock-history text-success"></i> Lịch sử biến động giá
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'khuyenmai') ? 'active' : '' ?>" href="index.php?action=khuyenmai">
                        <i class="bi bi-tags-fill text-danger"></i> Chương trình Khuyến mãi
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Nhập kho -->
            <div class="nav-section-title">Quản lý Nhập kho</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'nhaphang') ? 'active' : '' ?>" href="index.php?action=nhaphang">
                        <i class="bi bi-box-arrow-in-down text-info"></i> NCC & Phiếu nhập kho
                    </a>
                </li>
            </ul>

            <!-- Bán hàng & Hậu mãi -->
            <div class="nav-section-title">Bán hàng & Hậu mãi</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'donhang' && $currentAct !== 'invoices') ? 'active' : '' ?>" href="index.php?action=donhang">
                        <i class="bi bi-cart-check-fill text-warning"></i> Đơn đặt hàng online
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'donhang' && $currentAct === 'invoices') ? 'active' : '' ?>" href="index.php?action=donhang&act=invoices">
                        <i class="bi bi-receipt-cutoff text-success"></i> Danh sách Hóa đơn
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'baohanh') ? 'active' : '' ?>" href="index.php?action=baohanh">
                        <i class="bi bi-shield-check text-info"></i> Quản lý Bảo hành
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'trahang') ? 'active' : '' ?>" href="index.php?action=trahang">
                        <i class="bi bi-arrow-return-left text-danger"></i> Quản lý Trả hàng
                    </a>
                </li>
            </ul>

            <?php if (isAdmin()): ?>
            <!-- Hệ thống người dùng & Phân quyền -->
            <div class="nav-section-title">Quản trị & Phân quyền</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'nhanvien') ? 'active' : '' ?>" href="index.php?action=nhanvien">
                        <i class="bi bi-person-badge-fill text-warning"></i> Nhân sự & Ủy quyền
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentAction === 'nguoidung') ? 'active' : '' ?>" href="index.php?action=nguoidung">
                        <i class="bi bi-people-fill text-secondary"></i> Tài khoản Người dùng
                    </a>
                </li>
            </ul>
            <?php endif; ?>

            <!-- Đồ án & Báo cáo Học thuật -->
            <div class="nav-section-title"></div>
            <ul class="nav flex-column mb-3">
               
                    <a class="nav-link text-danger" href="../public/index.php?action=logout">
                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Content Wrapper -->
    <div id="content-wrapper">
        <!-- Topbar -->
        <header class="admin-topbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="fw-bold fs-5 text-dark">
                    <?= htmlspecialchars($pageTitle) ?>
                </div>
            </div>

            <!-- Nhân viên đang đăng nhập -->
            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold text-dark mb-0">
                        <?= htmlspecialchars($currentUser['hoten']) ?>
                        <?php if (!empty($currentUser['ma_nv'])): ?>
                            <span class="badge bg-secondary ms-1">Mã NV: <?= htmlspecialchars($currentUser['ma_nv']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted">
                        <?php if ($isAdmin): ?>
                            <span class="badge bg-danger">Quản trị viên (Admin)</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Nhân viên</span>
                        <?php endif; ?>
                        • <?= htmlspecialchars($currentUser['email'] ?? $currentUser['sodienthoai'] ?? '') ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="btn btn-light rounded-circle p-2 border" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill fs-5 text-secondary"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2">
                        <li><a class="dropdown-item" href="../public/index.php?action=profile"><i class="bi bi-person-gear me-2"></i> Hồ sơ cá nhân</a></li>
                        <li><a class="dropdown-item" href="../public/index.php" target="_blank"><i class="bi bi-shop me-2"></i> Xem trang bán hàng</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../public/index.php?action=logout"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="container-fluid px-4 pt-3">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>
        </div>

        <!-- Main Body Content -->
        <main class="container-fluid px-4 py-3 flex-grow-1">
