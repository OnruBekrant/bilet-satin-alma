<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'firma_admin' değilse, erişimi engelle.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'firma_admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Düzenlenecek seferin ID'sini URL'den al
$trip_id_to_edit = $_GET['trip_id'] ?? null;
if (!$trip_id_to_edit) {
    die('Düzenlenecek sefer belirtilmedi.');
}

// GÜVENLİK: Bu seferin, giriş yapmış olan admin'in firmasına ait olduğunu doğrula.
$company_id = $_SESSION['company_id'];
$stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id_to_edit, $company_id]);
$trip = $stmt->fetch();

if (!$trip) {
    die('Böyle bir sefer bulunamadı veya bu seferi düzenleme yetkiniz yok.');
}

// Veritabanındaki datetime formatını, formdaki 'date' ve 'time' inputlarına ayır.
$departure_datetime = new DateTime($trip['departure_time']);
$departure_date = $departure_datetime->format('Y-m-d');
$departure_time = $departure_datetime->format('H:i');

$arrival_datetime = new DateTime($trip['arrival_time']);
$arrival_date = $arrival_datetime->format('Y-m-d');
$arrival_time = $arrival_datetime->format('H:i');

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Seferi Düzenle</title>
    <link rel="stylesheet" href="path/to/your/styles.css"> <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 720px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: bold; }
        .form-group input { width: 100%; padding: .5rem; border: 1px solid #ced4da; border-radius: 4px; box-sizing: border-box; }
        .btn { text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; color: white; border: none; cursor: pointer; font-size: 1rem; }
        .btn-primary { background-color: #007bff; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php?page=company_admin_panel" style="font-weight: bold;">Firma Admin Paneli</a>
        <div>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>

    <div class="container">
        <h1>Seferi Düzenle (ID: <?php echo htmlspecialchars($trip['id']); ?>)</h1>
        <form action="/index.php?action=edit_trip" method="POST">
            <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip['id']); ?>">

            <div class="form-group">
                <label for="departure_location">Kalkış Yeri</label>
                <input type="text" id="departure_location" name="departure_location" value="<?php echo htmlspecialchars($trip['departure_location']); ?>" required>
            </div>
            <div class="form-group">
                <label for="arrival_location">Varış Yeri</label>
                <input type="text" id="arrival_location" name="arrival_location" value="<?php echo htmlspecialchars($trip['arrival_location']); ?>" required>
            </div>

            <div class="form-group">
                <label for="departure_date">Kalkış Tarihi</label>
                <input type="date" id="departure_date" name="departure_date" value="<?php echo $departure_date; ?>" required>
            </div>
            <div class="form-group">
                <label for="departure_time">Kalkış Saati</label>
                <input type="time" id="departure_time" name="departure_time" value="<?php echo $departure_time; ?>" required>
            </div>

            <div class="form-group">
                <label for="arrival_date">Varış Tarihi</label>
                <input type="date" id="arrival_date" name="arrival_date" value="<?php echo $arrival_date; ?>" required>
            </div>
            <div class="form-group">
                <label for="arrival_time">Varış Saati</label>
                <input type="time" id="arrival_time" name="arrival_time" value="<?php echo $arrival_time; ?>" required>
            </div>

            <div class="form-group">
                <label for="seat_count">Toplam Koltuk Sayısı</label>
                <input type="number" id="seat_count" name="seat_count" min="1" value="<?php echo htmlspecialchars($trip['seat_count']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Fiyat (TL)</label>
                <input type="number" step="0.01" id="price" name="price" min="0" value="<?php echo htmlspecialchars($trip['price']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
        </form>
    </div>
</body>
</html>