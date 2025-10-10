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
else if ($action == 'add_company_admin' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id'];
    $role = 'firma_admin'; // Rolü doğrudan 'firma_admin' olarak ayarlıyoruz.

    // Basit doğrulama
    if (empty($name) || empty($email) || empty($password) || empty($company_id)) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode("Lütfen tüm alanları doldurun."));
        exit();
    }

    // E-postanın zaten kayıtlı olup olmadığını kontrol et
    $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->execute([$email]);
    if ($stmt_check->fetch()) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode("Bu e-posta adresi zaten kayıtlı."));
        exit();
    }

    // Şifreyi hash'le
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Veritabanına yeni kullanıcıyı ekle
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hashed_password, $role, $company_id]);

        header("Location: /index.php?page=admin_panel&status=company_admin_added");
        exit();

    } catch (PDOException $e) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode("Veritabanı hatası: " . $e->getMessage()));
        exit();
    }
}
// Süper Admin için genel kupon ekleme eylemi
else if ($action == 'add_global_coupon' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = trim($_POST['code']);
    $discount_rate = filter_input(INPUT_POST, 'discount_rate', FILTER_VALIDATE_INT);
    $usage_limit = filter_input(INPUT_POST, 'usage_limit', FILTER_VALIDATE_INT);
    $expire_date = $_POST['expire_date'];

    // company_id'yi NULL olarak ayarlıyoruz, bu onun genel bir kupon olduğunu belirtir.
    $company_id = null; 

    if (empty($code) || !$discount_rate || !$usage_limit || empty($expire_date)) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode("Lütfen kupon için tüm alanları doldurun."));
        exit();
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO coupons (code, discount_rate, usage_limit, expire_date, company_id) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$code, $discount_rate, $usage_limit, $expire_date, $company_id]);

        header("Location: /index.php?page=admin_panel&status=global_coupon_added");
        exit();
    } catch (PDOException $e) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode("Veritabanı hatası veya bu kod zaten mevcut."));
        exit();
    }
}
else if ($action == 'delete_global_coupon' && isset($_GET['coupon_id'])) {
    $coupon_id = $_GET['coupon_id'];

    try {
        // GÜVENLİK KONTROLÜ: Silinmek istenen kuponun genel bir kupon olduğundan emin ol (company_id IS NULL).
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ? AND company_id IS NULL");
        $stmt->execute([$coupon_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Genel kupon bulunamadı veya silme yetkiniz yok.");
        }

        header("Location: /index.php?page=admin_panel&status=global_coupon_deleted");
        exit();

    } catch (Exception $e) {
        header("Location: /index.php?page=admin_panel&error=" . urlencode($e->getMessage()));
        exit();
    }
}
