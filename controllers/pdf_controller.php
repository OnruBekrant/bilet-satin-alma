<?php
// Composer kütüphanelerini yükleyen sihirli satır.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die('PDF oluşturmak için giriş yapmalısınız.');
}
if (!isset($_GET['ticket_id'])) {
    die('Hatalı istek: Bilet ID bilgisi eksik.');
}

$ticket_id = $_GET['ticket_id'];
$user_id = $_SESSION['user_id'];

// PDF'te görünecek tüm bilgileri tek bir sorguyla alalım
$stmt = $pdo->prepare(
    "SELECT 
        u.name as user_name,
        t.seat_number,
        t.purchase_time,
        tr.departure_location,
        tr.arrival_location,
        tr.departure_time,
        tr.price,
        c.name as company_name
    FROM tickets as t
    JOIN users as u ON t.user_id = u.id
    JOIN trips as tr ON t.trip_id = tr.id
    JOIN companies as c ON tr.company_id = c.id
    WHERE t.id = ? AND t.user_id = ?"
);
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.");
}

// PDF Oluşturma (tFPDF ile)
$pdf = new tFPDF();
$pdf->AddPage();

// Türkçe karakterleri destekleyen bir font ekliyoruz.
// tFPDF kütüphanesiyle gelen DejaVu fontunu kullanacağız.
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);

// Eklediğimiz fontu aktif hale getiriyoruz.
$pdf->SetFont('DejaVu', 'B', 16);

// EN BÜYÜK DEĞİŞİKLİK: Artık iconv() fonksiyonuna ihtiyacımız kalmadı!
// Metinleri doğrudan UTF-8 olarak gönderebiliriz.
$pdf->Cell(0, 10, $ticket['company_name'], 0, 1, 'C');
$pdf->Cell(0, 10, 'Yolcu Biniş Kartı', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(40, 10, 'Yolcu Adı:', 0, 0);
$pdf->Cell(0, 10, $ticket['user_name'], 0, 1);

$pdf->Cell(40, 10, 'Kalkış:', 0, 0);
$pdf->Cell(0, 10, $ticket['departure_location'], 0, 1);

$pdf->Cell(40, 10, 'Varış:', 0, 0);
$pdf->Cell(0, 10, $ticket['arrival_location'], 0, 1);

$pdf->Cell(40, 10, 'Kalkış Saati:', 0, 0);
$pdf->Cell(0, 10, date('d.m.Y H:i', strtotime($ticket['departure_time'])), 0, 1);

$pdf->Cell(40, 10, 'Koltuk No:', 0, 0);
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(40, 10, 'Fiyat:', 0, 0);
$pdf->Cell(0, 10, $ticket['price'] . ' TL', 0, 1);

// PDF'i tarayıcıya gönder
$pdf->Output('D', 'bilet.pdf');
exit();