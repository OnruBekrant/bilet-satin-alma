<?php
// Kullanıcı giriş yapmamışsa, bu sayfayı göremez.
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php?page=login&error=Bilet almak için lütfen giriş yapın.');
    exit();
}

$trip_id = $_GET['trip_id'] ?? null;

if (!$trip_id) {
    die("Hatalı istek: Sefer ID'si bulunamadı.");
}

require_once __DIR__ . '/../config/database.php';

// 1. Adım: Sefer bilgilerini çek.
$stmt_trip = $pdo->prepare("SELECT * FROM trips WHERE id = ?");
$stmt_trip->execute([$trip_id]);
$trip = $stmt_trip->fetch();

if (!$trip) {
    die("Hata: Belirtilen sefer bulunamadı.");
}

// 2. Adım: Bu sefere ait satılmış koltuk numaralarını çek.
$stmt_tickets = $pdo->prepare("SELECT seat_number FROM tickets WHERE trip_id = ?");
$stmt_tickets->execute([$trip_id]);
// fetchAll(PDO::FETCH_COLUMN) ile sonuçları [2, 15, 32] gibi basit bir diziye alıyoruz.
$sold_seats = $stmt_tickets->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bilet Satın Al - Koltuk Seçimi</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 960px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .seat-map { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; max-width: 300px; margin-top: 2rem; }
        .seat { width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; border: 1px solid #ccc; border-radius: 5px; cursor: pointer; }
        .seat-aisle { border: none; cursor: default; } /* Koridor boşluğu */
        .seat.sold { background-color: #dc3545; color: white; cursor: not-allowed; }
        .seat.available { background-color: #28a745; color: white; }
        .seat.available:hover { background-color: #218838; }
        .seat input[type="checkbox"] { display: none; } /* Checkbox'ı gizle */
        .seat.selected { box-shadow: 0 0 0 3px #007bff; }
        .purchase-btn { background-color: #007bff; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; font-size: 1.2rem; cursor: pointer; margin-top: 2rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php" style="font-weight: bold;">Bilet Platformu</a>
        <div>
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>
    <div class="container">
        <h1>Bilet Satın Alma</h1>
        <h2><?php echo htmlspecialchars($trip['departure_location']); ?> &rarr; <?php echo htmlspecialchars($trip['arrival_location']); ?></h2>
        <p>Sefer Tarihi: <?php echo date('d M Y H:i', strtotime($trip['departure_time'])); ?></p>
        <p>Fiyat: <strong><?php echo htmlspecialchars($trip['price']); ?> TL</strong> (Koltuk Başına)</p>
        
        <hr>
        <h3>Koltuk Seçimi</h3>
        <form action="/index.php?action=purchase" method="POST">
            <div class="seat-map">
                <?php for ($i = 1; $i <= $trip['seat_count']; $i++): ?>
                    
                    <?php
                        // 3. Adım: Koltuk dolu mu diye kontrol et.
                        $is_sold = in_array($i, $sold_seats);
                        $seat_class = $is_sold ? 'sold' : 'available';
                    ?>

                    <?php if ($i % 5 == 3): // Her 5 koltukta 3. sırayı koridor boşluğu yap ?>
                        <div class="seat-aisle"></div>
                    <?php else: ?>
                        <label class="seat <?php echo $seat_class; ?>" for="seat-<?php echo $i; ?>">
                            <?php if (!$is_sold): // Dolu değilse seçilebilir yap ?>
                                <input type="checkbox" name="seats[]" id="seat-<?php echo $i; ?>" value="<?php echo $i; ?>">
                            <?php endif; ?>
                            <?php echo $i; ?>
                        </label>
                    <?php endif; ?>

                <?php endfor; ?>
            </div>
            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
            <button type="submit" class="purchase-btn">Seçilen Koltukları Satın Al</button>
        </form>
    </div>
    <script>
        // Koltuk seçildiğinde görsel olarak belirtmek için basit bir Javascript
        document.querySelectorAll('.seat.available').forEach(seat => {
            seat.addEventListener('click', () => {
                const checkbox = seat.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    seat.classList.toggle('selected', checkbox.checked);
                }
            });
        });
    </script>
</body>
</html>