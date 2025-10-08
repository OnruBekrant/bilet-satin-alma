<?php

// Veritabanı dosyasının tam yolu.
// __DIR__ bu dosyanın (database.php) bulunduğu klasörü (config) verir.
// ../ diyerek bir üst dizine (bilet-satin-alma) çıkıyoruz
// ve oradan database/bilet_platformu.sqlite dosyasına ulaşıyoruz.
$db_path = __DIR__ . '/../database/bilet_platformu.sqlite';

try {
    // PDO (PHP Data Objects) ile SQLite veritabanına bağlanıyoruz.
    $pdo = new PDO('sqlite:' . $db_path);

    // Hata modunu ayarlıyoruz.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sonuçların ilişkilendirilebilir bir dizi olarak gelmesini sağlıyoruz.
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Eğer bağlantı sırasında bir hata oluşursa, programı durdur.
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}