<?php
/**
 * public/index.php
 * Bộ điều phối trung tâm (Front Router) Phân hệ Khách hàng & Cửa hàng
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../model/index.php';

$action = $_GET['action'] ?? 'home';
$action = trim($action);

switch ($action) {
    case 'home':
        require __DIR__ . '/home.php';
        break;

    case 'about':
        require __DIR__ . '/about.php';
        break;

    case 'detail':
        require __DIR__ . '/detail.php';
        break;

    case 'cart':
        require __DIR__ . '/cart.php';
        break;

    case 'checkout':
        require __DIR__ . '/checkout.php';
        break;

    case 'login':
        require __DIR__ . '/login.php';
        break;

    case 'register':
        require __DIR__ . '/register.php';
        break;

    case 'logout':
        require __DIR__ . '/logout.php';
        break;

    case 'profile':
        require __DIR__ . '/profile.php';
        break;

    case 'addresses':
        require __DIR__ . '/addresses.php';
        break;

    case 'orders':
        require __DIR__ . '/orders.php';
        break;

    case 'payment_qr':
        require __DIR__ . '/payment_qr.php';
        break;
        
    case 'categories':
        require __DIR__ . '/categories.php';
        break;

    default:
        require __DIR__ . '/home.php';
        break;
    
}
