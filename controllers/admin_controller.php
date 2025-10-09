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