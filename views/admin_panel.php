<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'admin' değilse, erişimi engelle.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Tüm firmaları veritabanından çek
$companies = $pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
// Mevcut firma adminlerini ve atandıkları firmaları listelemek için JOIN'li sorgu
$stmt = $pdo->query(
    "SELECT u.id, u.name, u.email, c.name as company_name 
     FROM users u 
     LEFT JOIN companies c ON u.company_id = c.id 
     WHERE u.role = 'firma_admin'
     ORDER BY u.name"
);
$company_admins = $stmt->fetchAll();
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
            <?php if (isset($_GET['status']) && $_GET['status'] == 'company_deleted'): ?>
    <div style="color: #155724; background-color: #d4edda; padding: 1rem; border-radius: .25rem; margin-bottom: 1rem;">
        Firma ve ilişkili tüm verileri başarıyla silindi!
    </div>
<?php endif; ?>
<?php if (isset($_GET['status']) && $_GET['status'] == 'company_admin_added'): // YENİ EKLENEN BLOK ?>
    <div style="color: #155724; background-color: #d4edda; padding: 1rem; border-radius: .25rem; margin-bottom: 1rem;">
        Yeni Firma Admin kullanıcısı başarıyla eklendi!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div style="color: #721c24; background-color: #f8d7da; padding: 1rem; border-radius: .25rem; margin-bottom: 1rem;">
        Hata: <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
<?php endif; ?>
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
                            <td><a href="/index.php?action=delete_company&company_id=<?php echo $company['id']; ?>" class="btn btn-danger" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz? Bu firmaya ait TÜM seferler ve biletler kalıcı olarak silinecektir!');">Sil</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <hr>
        </div>
        <hr>

        <div class="section">
            <h2>Firma Admin Yönetimi</h2>
            <form action="/index.php?action=add_company_admin" method="POST" class="form-inline" style="flex-wrap: wrap;">
                <input type="text" name="name" placeholder="Ad Soyad" required>
                <input type="email" name="email" placeholder="E-posta" required>
                <input type="password" name="password" placeholder="Şifre" required>

                <select name="company_id" required>
                    <option value="">Firma Seçin...</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>">
                            <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">Firma Admin Ekle</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad Soyad</th>
                        <th>E-posta</th>
                        <th>Atandığı Firma</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($company_admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['company_name'] ?? 'Atanmamış'); ?></td>
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
        </div>
</body>
</html>