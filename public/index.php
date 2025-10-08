<?php
// --- HATA AYIKLAMA KODU ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ---------------------------
session_start(); // Session yönetimini her zaman en başta başlat

// --- Eylem Kontrolcüsü (Action Controller) ---
// Eğer URL'de bir 'action' parametresi varsa, bu bir form gönderimidir.
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Hangi eylemin hangi controller dosyasını çalıştıracağını belirleyelim
    if (in_array($action, ['register', 'login', 'logout'])) {
        require_once __DIR__ . '/../controllers/auth_controller.php';
    } elseif ($action === 'purchase') {
        require_once __DIR__ . '/../controllers/purchase_controller.php';
    } elseif ($action === 'cancel_ticket') { // YENİ EYLEM
        require_once __DIR__ . '/../controllers/ticket_controller.php';
    }
}

// --- Sayfa Görüntüleyici (View Router) ---
// Eğer bir 'action' yoksa, bu bir sayfa görüntüleme isteğidir.
$page = $_GET['page'] ?? 'home';

// İzin verilen sayfalar listesi
$allowed_pages = ['home', 'register', 'login', 'purchase', 'my-tickets'];

if (in_array($page, $allowed_pages) && file_exists(__DIR__ . "/../views/{$page}.php")) {
    require_once __DIR__ . "/../views/{$page}.php";
} else {
    // Ana sayfa için özel bir durum
    if ($page === 'home') {
        // Kullanıcı giriş yapmış mı diye session'ı kontrol et
        if (isset($_SESSION['user_id'])) {
            // Giriş yapmışsa:
            echo "<h1>Ana Sayfa</h1>";
            echo "<p>Hoş geldiniz, " . htmlspecialchars($_SESSION['user_name']) . "!</p>";
            echo '<a href="/index.php?action=logout">Çıkış Yap</a>';
        } else {
            // Giriş yapmamışsa:
            echo "<h1>Ana Sayfa</h1>";
            echo "<p>Lütfen bilet aramak veya satın almak için giriş yapın.</p>";
            echo '<a href="/index.php?page=login">Giriş Yap</a> | <a href="/index.php?page=register">Kayıt Ol</a>';
        }
    } else {
        http_response_code(404);
        echo "<h1>404 Sayfa Bulunamadı</h1>";
    }
}