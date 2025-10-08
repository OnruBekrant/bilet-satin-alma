<?php
require_once __DIR__ . '/../config/database.php';

$trips = [];
$search_performed = false;

// İlk olarak, veritabanındaki tüm seferleri çekelim.
$all_trips_stmt = $pdo->query("SELECT * FROM trips");
$all_trips = $all_trips_stmt->fetchAll();

// Eğer kullanıcı arama formunu göndermişse...
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $search_performed = true;

    // Arama kriterlerini alıp güvenli bir şekilde küçük harfe çevirelim.
    $departure_search = mb_strtolower(trim($_POST['departure']), 'UTF-8');
    $arrival_search = mb_strtolower(trim($_POST['arrival']), 'UTF-8');

    // Şimdi PHP'nin array_filter fonksiyonunu kullanarak tüm seferler içinde arama yapalım.
    $trips = array_filter($all_trips, function ($trip) use ($departure_search, $arrival_search) {
        
        // Veritabanından gelen konumu da aynı şekilde küçük harfe çevirelim.
        $trip_departure = mb_strtolower(trim($trip['departure_location']), 'UTF-8');
        $trip_arrival = mb_strtolower(trim($trip['arrival_location']), 'UTF-8');

        // Eğer kullanıcının aradığı kelime, seferin konumunun içinde geçiyorsa (LIKE gibi)
        // ve her iki konum da eşleşiyorsa, bu seferi sonuçlara dahil et.
        $departure_match = str_contains($trip_departure, $departure_search);
        $arrival_match = str_contains($trip_arrival, $arrival_search);

        return $departure_match && $arrival_match;
    });
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa - Bilet Satın Alma Platformu</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 960px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .search-form { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .search-form input, .search-form button { padding: 0.75rem; border: 1px solid #ced4da; border-radius: 4px; font-size: 1rem; }
        .search-form button { background-color: #007bff; color: white; cursor: pointer; border-color: #007bff; }
        .trip-card { border: 1px solid #dee2e6; border-radius: 4px; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .trip-card h3 { margin-top: 0; }
        .trip-info { flex-grow: 1; }
        .trip-action .price { font-size: 1.5rem; font-weight: bold; color: #28a745; margin-bottom: 0.5rem; }
        .trip-action .btn { background-color: #28a745; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .no-results { text-align: center; color: #6c757d; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="/index.php" style="font-weight: bold;">Bilet Platformu</a>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <a href="/index.php?action=logout">Çıkış Yap</a>
            <?php else: ?>
                <a href="/index.php?page=login">Giriş Yap</a>
                <a href="/index.php?page=register">Kayıt Ol</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1>Otobüs Seferi Ara</h1>
        <form action="/index.php?page=home" method="POST" class="search-form">
            <input type="text" name="departure" placeholder="Kalkış Noktası" value="<?php echo isset($_POST['departure']) ? htmlspecialchars($_POST['departure']) : ''; ?>" required>
            <input type="text" name="arrival" placeholder="Varış Noktası" value="<?php echo isset($_POST['arrival']) ? htmlspecialchars($_POST['arrival']) : ''; ?>" required>
            <button type="submit">Sefer Bul</button>
        </form>

        <hr>

        <h2>Arama Sonuçları</h2>
        <div id="trip-results">
            <?php if ($search_performed): ?>
                <?php if (!empty($trips)): ?>
                    <?php foreach ($trips as $trip): ?>
                        <div class="trip-card">
                            <div class="trip-info">
                                <h3><?php echo htmlspecialchars($trip['departure_location']); ?> &rarr; <?php echo htmlspecialchars($trip['arrival_location']); ?></h3>
                                <p>
                                    <strong>Kalkış:</strong> <?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?> - 
                                    <strong>Varış:</strong> <?php echo date('d M Y H:i', strtotime($trip['arrival_time'])); ?>
                                </p>
                            </div>
                            <div class="trip-action">
                                <div class="price"><?php echo htmlspecialchars($trip['price']); ?> TL</div>
                                <a href="/index.php?page=purchase&trip_id=<?php echo $trip['id']; ?>" class="btn">Bilet Al</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">Aradığınız kriterlere uygun sefer bulunamadı.</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-results">Lütfen bir arama yapın.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>