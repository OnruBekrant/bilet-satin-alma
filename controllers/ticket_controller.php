<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php?page=login');
    exit();
}

// İptal edilecek biletin ID'si geldi mi?
if (!isset($_GET['ticket_id'])) {
    die('Hatalı istek: Bilet ID bilgisi eksik.');
}

$ticket_id = $_GET['ticket_id'];
$user_id = $_SESSION['user_id'];

try {
    // Transaction başlat: Ya hep, ya hiç!
    $pdo->beginTransaction();

    // 1. İptal edilmek istenen biletin bilgilerini ve sefer saatini al.
    // GÜVENLİK: Biletin bu kullanıcıya ait olup olmadığını da kontrol et (user_id = ?)
    $stmt = $pdo->prepare(
        "SELECT 
            t.id, 
            tr.departure_time,
            tr.price
         FROM tickets as t
         JOIN trips as tr ON t.trip_id = tr.id
         WHERE t.id = ? AND t.user_id = ?"
    );
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch();

    // Bilet bulunamazsa veya başkasına aitse, işlemi durdur.
    if (!$ticket) {
        throw new Exception("İptal işlemi için geçersiz bilet veya yetkiniz yok.");
    }

    // 2. Zaman kontrolü yap. Sefer kalkış saatine 1 saatten az mı kalmış?
    $departure_timestamp = strtotime($ticket['departure_time']);
    $current_timestamp = time();
    $hours_until_departure = ($departure_timestamp - $current_timestamp) / 3600;

    if ($hours_until_departure < 1) {
        throw new Exception("Kalkış saatine 1 saatten az kaldığı için bilet iptal edilemez.");
    }

    // 3. Bilet ücretini kullanıcının kredisine geri ekle.
    $stmt_refund = $pdo->prepare("UPDATE users SET credit = credit + ? WHERE id = ?");
    $stmt_refund->execute([$ticket['price'], $user_id]);

    // 4. Bilet kaydını 'tickets' tablosundan sil.
    $stmt_delete = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt_delete->execute([$ticket_id]);

    // Her şey yolundaysa, değişiklikleri onayla.
    $pdo->commit();

    header('Location: /index.php?page=my-tickets&status=cancel_success');
    exit();

} catch (Exception $e) {
    // Bir hata oluşursa, tüm değişiklikleri geri al.
    $pdo->rollBack();
    header('Location: /index.php?page=my-tickets&error=' . urlencode($e->getMessage()));
    exit();
}