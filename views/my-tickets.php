<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php?page=login');
    exit();
}
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

        <?php if (isset($_GET['status']) && $_GET['status'] == 'purchase_success'): ?>
            <div class="alert-success">
                Bilet satın alma işleminiz başarıyla tamamlandı!
            </div>
        <?php endif; ?>

        <p>(Satın alınan biletler burada listelenecek...)</p>
    </div>
</body>
</html>