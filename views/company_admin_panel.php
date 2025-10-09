<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'firma_admin' değilse, ana sayfaya yönlendir.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'firma_admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Giriş yapmış olan admin'in firma ID'sini session'dan al
$company_id = $_SESSION['company_id']; // Bu session'ı birazdan oluşturacağız.

// Sadece bu firmaya ait seferleri veritabanından çek
$stmt = $pdo->prepare("SELECT * FROM trips WHERE company_id = ? ORDER BY departure_time DESC");
$stmt->execute([$company_id]);
$company_trips = $stmt->fetchAll();
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
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { border: 1px solid #dee2e6; padding: 0.75rem; text-align: left; }
        th { background-color: #e9ecef; }
        .btn { text-decoration: none; padding: 0.375rem 0.75rem; border-radius: 4px; color: white; }
        .btn-primary { background-color: #007bff; }
        .btn-warning { background-color: #ffc107; }
        .btn-danger { background-color: #dc3545; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php" style="font-weight: bold;">Bilet Platformu - Admin</a>
        <div>
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container">
        <h1>Sefer Yönetimi</h1>
        <a href="/index.php?page=add_trip" class="btn btn-primary">Yeni Sefer Ekle</a>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kalkış</th>
                    <th>Varış</th>
                    <th>Kalkış Saati</th>
                    <th>Koltuk Sayısı</th>
                    <th>Fiyat</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($company_trips)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Henüz firmanıza ait bir sefer bulunmamaktadır.</td>
                    </tr>
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
                                <a href="#" class="btn btn-warning">Düzenle</a>
                                <a href="#" class="btn btn-danger">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>