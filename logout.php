<?php
/**
 * public/logout.php
 * Đăng xuất khỏi hệ thống
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['user']);
$_SESSION['flash_success'] = 'Bạn đã đăng xuất khỏi hệ thống thành công.';
header('Location: index.php?action=login');
exit;
