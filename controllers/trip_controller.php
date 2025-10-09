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