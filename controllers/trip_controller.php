<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'firma_admin' değilse, işlemi durdur.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'firma_admin') {
    die('Bu işlemi yapma yetkiniz yok.');
}

$action = $_GET['action'] ?? '';

// Yeni sefer ekleme eylemi
if ($action == 'add_trip' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $departure_location = trim($_POST['departure_location']);
    $arrival_location = trim($_POST['arrival_location']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_date = $_POST['arrival_date'];
    $arrival_time = $_POST['arrival_time'];
    $seat_count = filter_input(INPUT_POST, 'seat_count', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    // Giriş yapmış olan admin'in firma ID'sini session'dan al
    $company_id = $_SESSION['company_id'];

    // Ayrılmış tarih ve saat verilerini birleştirerek veritabanı formatına uygun hale getir
    $departure_datetime = $departure_date . ' ' . $departure_time;
    $arrival_datetime = $arrival_date . ' ' . $arrival_time;

    // Basit doğrulama
    if (empty($departure_location) || empty($arrival_location) || !$seat_count || !$price) {
        die('Lütfen tüm alanları doğru bir şekilde doldurun.');
    }

    try {
        // Veritabanına yeni seferi ekle
        $stmt = $pdo->prepare(
            "INSERT INTO trips (company_id, departure_location, arrival_location, departure_time, arrival_time, seat_count, price)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $company_id,
            $departure_location,
            $arrival_location,
            $departure_datetime,
            $arrival_datetime,
            $seat_count,
            $price
        ]);

        // Başarılı ekleme sonrası admin paneline yönlendir
        header("Location: /index.php?page=company_admin_panel&status=trip_added");
        exit();

    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
}
// Yeni sefer silme eylemi
else if ($action == 'delete_trip' && isset($_GET['trip_id'])) {
    $trip_id = $_GET['trip_id'];
    $company_id = $_SESSION['company_id'];

    try {
        // Transaction başlat
        $pdo->beginTransaction();

        // GÜVENLİK KONTROLÜ: Admin'in silmeye çalıştığı sefer gerçekten kendi firmasına mı ait?
        $stmt_check = $pdo->prepare("SELECT id FROM trips WHERE id = ? AND company_id = ?");
        $stmt_check->execute([$trip_id, $company_id]);
        if ($stmt_check->fetch() === false) {
            // Eğer sefer bu firmaya ait değilse, yetkisiz işlem hatası ver.
            throw new Exception("Bu seferi silme yetkiniz yok.");
        }

        // Önce bu sefere ait satılmış biletleri (varsa) 'tickets' tablosundan sil.
        // Bu, veritabanında "yetim" kayıt kalmasını önler.
        $stmt_delete_tickets = $pdo->prepare("DELETE FROM tickets WHERE trip_id = ?");
        $stmt_delete_tickets->execute([$trip_id]);

        // Şimdi seferin kendisini 'trips' tablosundan sil.
        $stmt_delete_trip = $pdo->prepare("DELETE FROM trips WHERE id = ?");
        $stmt_delete_trip->execute([$trip_id]);

        // Her şey yolundaysa, değişiklikleri onayla.
        $pdo->commit();

        header("Location: /index.php?page=company_admin_panel&status=trip_deleted");
        exit();

    } catch (Exception $e) {
        // Herhangi bir hata olursa, tüm işlemleri geri al.
        $pdo->rollBack();
        header("Location: /index.php?page=company_admin_panel&error=" . urlencode($e->getMessage()));
        exit();
    }
}
// Sefer güncelleme eylemi
else if ($action == 'edit_trip' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $trip_id = $_POST['trip_id'];
    $departure_location = trim($_POST['departure_location']);
    $arrival_location = trim($_POST['arrival_location']);
    $departure_date = $_POST['departure_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_date = $_POST['arrival_date'];
    $arrival_time = $_POST['arrival_time'];
    $seat_count = filter_input(INPUT_POST, 'seat_count', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    $company_id = $_SESSION['company_id'];

    // Tarih ve saat verilerini birleştir
    $departure_datetime = $departure_date . ' ' . $departure_time;
    $arrival_datetime = $arrival_date . ' ' . $arrival_time;

    // Basit doğrulama
    if (empty($departure_location) || empty($arrival_location) || !$seat_count || !$price || !$trip_id) {
        die('Lütfen tüm alanları doğru bir şekilde doldurun.');
    }

    try {
        // GÜVENLİK KONTROLÜ: Admin'in güncellemeye çalıştığı sefer gerçekten kendi firmasına mı ait?
        // Bu kontrolü UPDATE sorgusunun WHERE kısmına company_id ekleyerek de yapabiliriz.

        $stmt = $pdo->prepare(
            "UPDATE trips SET 
                departure_location = ?,
                arrival_location = ?,
                departure_time = ?,
                arrival_time = ?,
                seat_count = ?,
                price = ?
            WHERE id = ? AND company_id = ?"
        );
        $stmt->execute([
            $departure_location,
            $arrival_location,
            $departure_datetime,
            $arrival_datetime,
            $seat_count,
            $price,
            $trip_id,
            $company_id // Güvenlik için company_id kontrolü
        ]);

        // Başarılı güncelleme sonrası admin paneline yönlendir
        header("Location: /index.php?page=company_admin_panel&status=trip_updated");
        exit();

    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
}