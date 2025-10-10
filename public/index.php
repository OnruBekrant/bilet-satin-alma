<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Europe/Istanbul');

session_start();

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if (in_array($action, ['register', 'login', 'logout'])) {
        require_once __DIR__ . '/../controllers/auth_controller.php';
    } elseif ($action === 'purchase') {
        require_once __DIR__ . '/../controllers/purchase_controller.php';
    } elseif ($action === 'cancel_ticket') {
        require_once __DIR__ . '/../controllers/ticket_controller.php';
    } elseif ($action === 'download_pdf') {
        require_once __DIR__ . '/../controllers/pdf_controller.php';
    } elseif (in_array($action, ['add_trip', 'delete_trip', 'edit_trip', 'add_company_coupon', 'delete_company_coupon'])) {
        require_once __DIR__ . '/../controllers/trip_controller.php';
    } elseif (in_array($action, ['add_company', 'delete_company', 'add_company_admin', 'add_global_coupon', 'delete_global_coupon'])) {
    require_once __DIR__ . '/../controllers/admin_controller.php';
}
}

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'register', 'login', 'purchase', 'my-tickets', 'company_admin_panel', 'add_trip', 'edit_trip', 'admin_panel'];

if (in_array($page, $allowed_pages) && file_exists(__DIR__ . "/../views/{$page}.php")) {
    require_once __DIR__ . "/../views/{$page}.php";
} elseif ($page === 'home' && file_exists(__DIR__ . "/../views/home.php")) {
    require_once __DIR__ . "/../views/home.php";
} else {
    if (!isset($_GET['action'])) {
        http_response_code(404);
        echo "<h1>404 Sayfa Bulunamadı</h1>";
    }
}