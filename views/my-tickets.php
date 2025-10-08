<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Şu anki kullanıcı ID'sini al
$user_id = $_SESSION['user_id'];

// Kullanıcının biletlerini, sefer detaylarıyla birlikte çekmek için JOIN'li sorgu
$stmt = $pdo->prepare(
    "SELECT 
        t.id as ticket_id,
        t.seat_number,
        t.purchase_time,
        tr.departure_location,
        tr.arrival_location,
        tr.departure_time,
        tr.price
    FROM tickets as t
    JOIN trips as tr ON t.trip_id = tr.id
    WHERE t.user_id = ?
    ORDER BY tr.departure_time DESC"
);
$stmt->execute([$user_id]);
$my_tickets = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Biletlerim</title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f8f9fa; }
        .navbar { background-color: #343a40; padding: 1rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; margin: 0 1rem; }
        .container { max-width: 960px; margin: 2rem auto; padding: 2rem; background-color: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 1rem; border: 1px solid transparent; border-radius: .25rem; margin-bottom: 1rem; }
        .ticket-card { border: 1px solid #dee2e6; border-radius: 4px; padding: 1.5rem; margin-bottom: 1rem; }
        .ticket-card h3 { margin-top: 0; }
        .ticket-details p { margin: 0.5rem 0; }
        .ticket-actions { margin-top: 1rem; }
        .ticket-actions .btn { text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; margin-right: 0.5rem; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/index.php" style="font-weight: bold;">Bilet Platformu</a>
        <div>
            <span>Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            <a href="/index.php?page=my-tickets">Biletlerim</a>
            <a href="/index.php?action=logout">Çıkış Yap</a>
        </div>
    </nav>
    <div class="container">
        <h1>Biletlerim</h1>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'cancel_success'): ?>
            <div class="alert-success">
                Biletiniz başarıyla iptal edildi ve ücreti hesabınıza iade edildi.
            </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert-danger" style="color: #721c24; background-color: #f8d7da; padding: 1rem; border-radius: .25rem; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'purchase_success'): ?>
                    <div class="alert-success">
                        Bilet satın alma işleminiz başarıyla tamamlandı!
                    </div>
                    <?php endif; ?>

        <?php if (empty($my_tickets)): ?>
            <p>Henüz satın alınmış biletiniz bulunmamaktadır.</p>
        <?php else: ?>
            <?php foreach ($my_tickets as $ticket): ?>
                <div class="ticket-card">
                    <h3><?php echo htmlspecialchars($ticket['departure_location']); ?> &rarr; <?php echo htmlspecialchars($ticket['arrival_location']); ?></h3>
                    <div class="ticket-details">
                        <p><strong>Kalkış Tarihi:</strong> <?php echo date('d M Y H:i', strtotime($ticket['departure_time'])); ?></p>
                        <p><strong>Koltuk No:</strong> <?php echo htmlspecialchars($ticket['seat_number']); ?></p>
                        <p><strong>Fiyat:</strong> <?php echo htmlspecialchars($ticket['price']); ?> TL</p>
                        <p><strong>Satın Alınma Tarihi:</strong> <?php echo date('d M Y H:i', strtotime($ticket['purchase_time'])); ?></p>
                    </div>
                    <div class="ticket-actions">
                        <a href="/index.php?action=cancel_ticket&ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-danger" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz? Ücreti hesabınıza iade edilecektir.');">İptal Et</a>
                        <a href="/index.php?action=download_pdf&ticket_id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-secondary" target="_blank">PDF İndir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>