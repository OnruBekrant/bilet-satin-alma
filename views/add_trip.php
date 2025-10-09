<?php
// Güvenlik: Kullanıcı giriş yapmamışsa veya rolü 'firma_admin' değilse, erişimi engelle.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'firma_admin') {
    header('Location: /index.php?error=unauthorized');
    exit();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yeni Sefer Ekle</title>
    <style>
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
        <h1>Yeni Sefer Ekle</h1>
        <form action="/index.php?action=add_trip" method="POST">
    <div class="form-group">
        <label for="departure_location">Kalkış Yeri</label>
        <input type="text" id="departure_location" name="departure_location" required>
    </div>
    <div class="form-group">
        <label for="arrival_location">Varış Yeri</label>
        <input type="text" id="arrival_location" name="arrival_location" required>
    </div>
    
    <div class="form-group">
        <label for="departure_date">Kalkış Tarihi</label>
        <input type="date" id="departure_date" name="departure_date" required>
    </div>
    <div class="form-group">
        <label for="departure_time">Kalkış Saati</label>
        <input type="time" id="departure_time" name="departure_time" required>
    </div>

    <div class="form-group">
        <label for="arrival_date">Varış Tarihi</label>
        <input type="date" id="arrival_date" name="arrival_date" required>
    </div>
    <div class="form-group">
        <label for="arrival_time">Varış Saati</label>
        <input type="time" id="arrival_time" name="arrival_time" required>
    </div>

    <div class="form-group">
        <label for="seat_count">Toplam Koltuk Sayısı</label>
        <input type="number" id="seat_count" name="seat_count" min="1" required>
    </div>
    <div class="form-group">
        <label for="price">Fiyat (TL)</label>
        <input type="number" step="0.01" id="price" name="price" min="0" required>
    </div>
    <button type="submit" class="btn btn-primary">Seferi Kaydet</button>
</form>
    </div>
</body>
</html>