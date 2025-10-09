<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Bu işlemi yapma yetkiniz yok.');
}

$action = $_GET['action'] ?? '';

if ($action == 'add_company' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = trim($_POST['company_name']);

    if (!empty($company_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO companies (name) VALUES (?)");
            $stmt->execute([$company_name]);
        } catch (PDOException $e) {
            // Hata yönetimi eklenebilir (örn: aynı isimde firma varsa)
        }
    }
    header("Location: /index.php?page=admin_panel");
    exit();
}
else if ($action == 'delete_company' && isset($_GET['company_id'])) {
    $company_id_to_delete = $_GET['company_id'];

    try {
        $pdo->beginTransaction();

        // 1. Adım: Silinecek firmaya ait tüm seferlerin ID'lerini bul.
        $stmt_find_trips = $pdo->prepare("SELECT id FROM trips WHERE company_id = ?");
        $stmt_find_trips->execute([$company_id_to_delete]);
        $trip_ids = $stmt_find_trips->fetchAll(PDO::FETCH_COLUMN);

        // 2. Adım: Eğer bu firmaya ait seferler varsa, o seferlere ait tüm biletleri sil.
        if (!empty($trip_ids)) {
            $placeholders = implode(',', array_fill(0, count($trip_ids), '?'));
            $stmt_delete_tickets = $pdo->prepare("DELETE FROM tickets WHERE trip_id IN ($placeholders)");
            $stmt_delete_tickets->execute($trip_ids);
        }

        // 3. Adım: Firmaya ait tüm seferleri sil.
        $stmt_delete_trips = $pdo->prepare("DELETE FROM trips WHERE company_id = ?");
        $stmt_delete_trips->execute([$company_id_to_delete]);

        // 4. Adım: Son olarak firmanın kendisini sil.
        $stmt_delete_company = $pdo->prepare("DELETE FROM companies WHERE id = ?");
        $stmt_delete_company->execute([$company_id_to_delete]);

        // Her şey yolundaysa, değişiklikleri onayla.
        $pdo->commit();

        header("Location: /index.php?page=admin_panel&status=company_deleted");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: /index.php?page=admin_panel&error=" . urlencode($e->getMessage()));
        exit();
    }
}