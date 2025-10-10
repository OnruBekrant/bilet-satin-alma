<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'firma_admin' değilse, ana sayfaya yönlendir.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'firma_admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Giriş yapmış olan admin'in firma ID'sini session'dan al
$company_id = $_SESSION['company_id'];

// Sadece bu firmaya ait seferleri veritabanından çek
$stmt_trips = $pdo->prepare("SELECT * FROM trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt_trips->execute([$company_id]);
$company_trips = $stmt_trips->fetchAll();

// Sadece bu firmaya ait kuponları çek
$stmt_coupons = $pdo->prepare("SELECT * FROM coupons WHERE company_id = ? ORDER BY expire_date DESC");
$stmt_coupons->execute([$company_id]);
$company_coupons = $stmt_coupons->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Firma Admin Paneli</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 1140px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section { margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border: 1px solid #dee2e6; padding: 0.75rem; text-align: left; }
        th { background-color: #e9ecef; }
        .form-inline { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .form-inline input, .form-inline select { padding: .5rem; border: 1px solid #ced4da; border-radius: 4px; }
        .btn { text-decoration: none; padding: 0.375rem 0.75rem; border-radius: 4px; color: white; cursor: pointer; border: none; }
        .btn-primary { background-color: #007bff; }
        .btn-warning { background-color: #ffc107; }
        .btn-danger { background-color: #dc3545; }
        .alert { padding: 1rem; border-radius: .25rem; margin-bottom: 1rem; border: 1px solid transparent; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php?page=company_admin_panel" style="font-weight: bold;">Bilet Platformu - Admin</a>
        <div>
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container">
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'trip_added'): ?>
            <div class="alert alert-success">Yeni sefer başarıyla eklendi!</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'trip_deleted'): ?>
            <div class="alert alert-success">Sefer başarıyla silindi!</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'trip_updated'): ?>
            <div class="alert alert-success">Sefer başarıyla güncellendi!</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'coupon_added'): ?>
            <div class="alert alert-success">Yeni kupon başarıyla eklendi!</div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'coupon_deleted'): ?>
            <div class="alert alert-success">Kupon başarıyla silindi!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Hata: <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <div class="section">
            <h1>Sefer Yönetimi</h1>
            <a href="/index.php?page=add_trip" class="btn btn-primary">Yeni Sefer Ekle</a>
            <table>
                <thead>
                    <tr><th>ID</th><th>Kalkış</th><th>Varış</th><th>Kalkış Saati</th><th>Koltuk Sayısı</th><th>Fiyat</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($company_trips)): ?>
                        <tr><td colspan="7" style="text-align: center;">Henüz firmanıza ait bir sefer bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($company_trips as $trip): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trip['id']); ?></td>
                                <td><?php echo htmlspecialchars($trip['departure_location']); ?></td>
                                <td><?php echo htmlspecialchars($trip['arrival_location']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($trip['departure_time'])); ?></td>
                                <td><?php echo htmlspecialchars($trip['seat_count']); ?></td>
                                <td><?php echo htmlspecialchars($trip['price']); ?> TL</td>
                                <td>
                                    <a href="/index.php?page=edit_trip&trip_id=<?php echo $trip['id']; ?>" class="btn btn-warning">Düzenle</a>
                                    <a href="/index.php?action=delete_trip&trip_id=<?php echo $trip['id']; ?>" class="btn btn-danger" onclick="return confirm('Bu seferi ve ilişkili tüm biletleri kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">Sil</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <hr>

        <div class="section">
            <h1>Kupon Yönetimi</h1>
            <form action="/index.php?action=add_company_coupon" method="POST" class="form-inline">
                <input type="text" name="code" placeholder="Kupon Kodu" required>
                <input type="number" name="discount_rate" placeholder="İndirim Oranı (%)" min="1" max="100" required>
                <input type="number" name="usage_limit" placeholder="Kullanım Limiti" min="1" required>
                <input type="date" name="expire_date" required>
                <button type="submit" class="btn btn-primary">Kupon Ekle</button>
            </form>
            <table>
                <thead>
                    <tr><th>ID</th><th>Kod</th><th>İndirim (%)</th><th>Limit</th><th>Son Tarih</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($company_coupons)): ?>
                        <tr><td colspan="6" style="text-align: center;">Henüz firmanıza ait bir kupon bulunmamaktadır.</td></tr>
                    <?php else: ?>
                        <?php foreach ($company_coupons as $coupon): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($coupon['id']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['discount_rate']); ?></td>
                                <td><?php echo htmlspecialchars($coupon['usage_limit']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($coupon['expire_date'])); ?></td>
                                <td><a href="/index.php?action=delete_company_coupon&coupon_id=<?php echo $coupon['id']; ?>" class="btn btn-danger" onclick="return confirm('Bu kuponu kalıcı olarak silmek istediğinizden emin misiniz?');">Sil</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>