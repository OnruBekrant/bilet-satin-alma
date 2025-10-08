<?php
require_once __DIR__ . '/../config/database.php';

// 1. Kullanıcı giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    // Yönlendirmeyi tam URL ile yapmak daha güvenilirdir.
    header('Location: /index.php?page=login&error=Bilet almak için giriş yapmalısınız.');
    exit();
}

// 2. Form verileri doğru geldi mi?
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['seats']) || !isset($_POST['trip_id'])) {
    die('Hatalı istek.');
}

$user_id = $_SESSION['user_id'];
$trip_id = $_POST['trip_id'];
$selected_seats = $_POST['seats'];

try {
    // 3. Veritabanı İşlemini (Transaction) Başlat
    $pdo->beginTransaction();

    // 4. Sefer fiyatını ve kullanıcının kredisini çek
    $stmt_trip = $pdo->prepare("SELECT price FROM trips WHERE id = ?");
    $stmt_trip->execute([$trip_id]);
    $trip = $stmt_trip->fetch();

    $stmt_user = $pdo->prepare("SELECT credit FROM users WHERE id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch();

    if (!$trip || !$user) {
        throw new Exception("Kullanıcı veya sefer bilgisi alınamadı.");
    }

    // 5. Kredi yeterli mi?
    $total_cost = $trip['price'] * count($selected_seats);
    if ($user['credit'] < $total_cost) {
        throw new Exception("Yetersiz bakiye!");
    }

    // GÜVENLİK KONTROLÜ: Seçilen koltuklar hala boş mu?
    // Kullanıcı ekranı açtıktan sonra başka biri o koltuğu almış olabilir.
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    $stmt_check_seats = $pdo->prepare("SELECT seat_number FROM tickets WHERE trip_id = ? AND seat_number IN ($placeholders)");
    $params = array_merge([$trip_id], $selected_seats);
    $stmt_check_seats->execute($params);
    if ($stmt_check_seats->fetch()) {
        throw new Exception("Seçtiğiniz koltuklardan bazıları siz işlem yaparken satın alındı. Lütfen tekrar deneyin.");
    }

    // 6. Kullanıcının kredisini güncelle
    $stmt_update_credit = $pdo->prepare("UPDATE users SET credit = credit - ? WHERE id = ?");
    $stmt_update_credit->execute([$total_cost, $user_id]);

    // 7. Her bir koltuk için bilet kaydı oluştur
    $stmt_insert_ticket = $pdo->prepare("INSERT INTO tickets (user_id, trip_id, seat_number) VALUES (?, ?, ?)");
    foreach ($selected_seats as $seat) {
        $stmt_insert_ticket->execute([$user_id, $trip_id, $seat]);
    }

    // 8. Her şey yolundaysa işlemi onayla
    $pdo->commit();

    // Kullanıcıyı biletlerim sayfasına yönlendir
    header('Location: /index.php?page=my-tickets&status=purchase_success');
    exit();

} catch (Exception $e) {
    // Herhangi bir hata olursa tüm işlemleri geri al
    $pdo->rollBack();
    // Hata mesajıyla birlikte kullanıcıyı bir önceki sayfaya yönlendir
    header('Location: /index.php?page=purchase&trip_id=' . $trip_id . '&error=' . urlencode($e->getMessage()));
    exit();
}