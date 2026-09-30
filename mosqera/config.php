<?php
// Sesuaikan dengan pengaturan MySQL kamu
session_start();
$db = new PDO('mysql:host=localhost;dbname=mosqera;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
function e($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function jadwal($db){ return $db->query("SELECT nama, TIME_FORMAT(waktu,'%H:%i') AS waktu FROM jadwal_sholat ORDER BY waktu")->fetchAll(); }
