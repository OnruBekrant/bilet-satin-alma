<?php
// Not: session_start() bu dosyadan kaldırıldı, çünkü ana giriş noktamız olan
// public/index.php dosyasında zaten çağrılıyor.

require_once __DIR__ . '/../config/database.php';

// Hangi eylemin istendiğini URL'den alıyoruz
$action = $_GET['action'] ?? '';

// --- Kullanıcı Kayıt İşlemi ---
if ($action == 'register' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        die("Lütfen tüm alanları doldurun.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Geçersiz e-posta formatı.");
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            die("Bu e-posta adresi zaten kayıtlı.");
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashed_password]);

        header("Location: /index.php?page=login&status=success");
        exit();
    } catch (PDOException $e) {
        die("Kayıt sırasında bir hata oluştu: " . $e->getMessage());
    }
}
// --- Kullanıcı Giriş İşlemi (GÜNCELLENMİŞ HALİ) ---
else if ($action == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: /index.php?page=login&error=Lütfen tüm alanları doldurun.");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Şifre doğru. Oturum (session) bilgilerini ayarla.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Eğer kullanıcı bir firma admini ise, firma ID'sini de session'a ekle.
            if ($user['role'] === 'firma_admin') {
                $_SESSION['company_id'] = $user['company_id'];
            }

            // ###############################################################
            // ### DEĞİŞİKLİK BURADA: 3 ROLÜ DE KONTROL EDEN YÖNLENDİRME ###
            // ###############################################################
            if ($user['role'] === 'admin') {
                header("Location: /index.php?page=admin_panel");
            } elseif ($user['role'] === 'firma_admin') {
                header("Location: /index.php?page=company_admin_panel");
            } else {
                header("Location: /index.php?page=home");
            }
            exit();

        } else {
            header("Location: /index.php?page=login&error=E-posta veya şifre hatalı!");
            exit();
        }
    } catch (PDOException $e) {
        die("Giriş sırasında bir veritabanı hatası oluştu: ". $e->getMessage());
    }
}
// --- Kullanıcı Çıkış İşlemi ---
else if ($action == 'logout') {
    // Tüm session değişkenlerini temizle
    $_SESSION = [];

    // Session'ı yok et
    session_destroy();

    // Kullanıcıyı giriş sayfasına yönlendir
    header("Location: /index.php?page=login&status=logout_success");
    exit();
}