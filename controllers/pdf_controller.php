<?php
// Composer kütüphanelerini yükleyen sihirli satır. HER ZAMAN EN BAŞTA OLMALI!
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

// PDF Oluşturma
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Türkçe karakter sorunu yaşamamak için iconv kullanalım
$company_name = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $ticket['company_name']);
$title = iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Yolcu Binis Karti');

$pdf->Cell(0, 10, $company_name, 0, 1, 'C');
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Yolcu Adi:'), 0, 0);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $ticket['user_name']), 0, 1);

$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Kalkis:'), 0, 0);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $ticket['departure_location']), 0, 1);

$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Varis:'), 0, 0);
$pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $ticket['arrival_location']), 0, 1);

$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Kalkis Saati:'), 0, 0);
$pdf->Cell(0, 10, date('d.m.Y H:i', strtotime($ticket['departure_time'])), 0, 1);

$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Koltuk No:'), 0, 0);
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(40, 10, iconv('UTF-8', 'ISO-8859-9//TRANSLIT', 'Fiyat:'), 0, 0);
$pdf->Cell(0, 10, $ticket['price'] . ' TL', 0, 1);

// PDF'i tarayıcıya gönder
$pdf->Output('D', 'bilet.pdf');
exit();