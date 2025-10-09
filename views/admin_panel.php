<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'admin' değilse, erişimi engelle.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Tüm firmaları veritabanından çek
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Süper Admin Paneli</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #c82333; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 1140px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section { margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border: 1px solid #dee2e6; padding: 0.75rem; text-align: left; }
        th { background-color: #e9ecef; }
        .form-inline { display: flex; gap: 1rem; align-items: center; }
        .form-inline input { padding: .5rem; border: 1px solid #ced4da; border-radius: 4px; }
        .btn { text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; color: white; border: none; cursor: pointer; }
        .btn-primary { background-color: #007bff; }
        .btn-danger { background-color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php?page=admin_panel" style="font-weight: bold;">Süper Admin Paneli</a>
        <div>
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h2>Firma Yönetimi</h2>
            <form action="/index.php?action=add_company" method="POST" class="form-inline">
                <input type="text" name="company_name" placeholder="Yeni Firma Adı" required>
                <button type="submit" class="btn btn-primary">Firma Ekle</button>
            </form>

            <table>
                <thead><tr><th>ID</th><th>Firma Adı</th><th>İşlemler</th></tr></thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['id']); ?></td>
                            <td><?php echo htmlspecialchars($company['name']); ?></td>
                            <td><a href="#" class="btn btn-danger">Sil</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <hr>
        </div>
</body>
</html>