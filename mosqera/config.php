<?php
// Sesuaikan dengan pengaturan MySQL kamu
session_start();
$db = new PDO('mysql:host=localhost;dbname=mosqera;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function rupiah($angka){ return 'Rp ' . number_format($angka ?? 0, 0, ',', '.'); }
function jadwal($db){ return $db->query("SELECT nama, TIME_FORMAT(waktu,'%H:%i') AS waktu FROM jadwal_sholat ORDER BY waktu")->fetchAll(); }
function ringkasan_kas($db){
    $masuk = $db->query("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='masuk'")->fetchColumn();
    $keluar = $db->query("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='keluar'")->fetchColumn();
    return [
        'masuk' => (float)$masuk,
        'keluar' => (float)$keluar,
        'saldo' => (float)($masuk - $keluar)
    ];
}
require_once __DIR__ . '/includes/db_init.php';
