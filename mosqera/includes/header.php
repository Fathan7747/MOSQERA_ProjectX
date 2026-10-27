<?php
require_once __DIR__ . '/../config.php';
$page = $page ?? 'beranda';
$namaMasjid = pengaturan($db, 'nama_masjid', "Masjid Jami' As-Salam");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Beranda') ?> · MOSQERA - <?= e($namaMasjid) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time() ?>">
</head>
<body>

<header class="admin-brand">
  <div class="nav-container">
    <a class="brand-logo" href="index.php">
      <span class="brand-title">MOSQERA</span>
      <span class="brand-tagline"><?= e($namaMasjid) ?></span>
    </a>

    <button class="nav-burger" aria-label="Buka Menu Navigasi" onclick="document.querySelector('.site-nav').classList.toggle('open')">
      Menu
    </button>

    <nav class="site-nav">
      <a href="index.php" class="nav-link <?= $page==='beranda' ? 'active' : '' ?>">Beranda</a>
      <a href="transparansi.php" class="nav-link <?= ($page==='transparansi' || $page==='kas') ? 'active' : '' ?>">Transparansi Kas</a>
      <a href="kampanye.php" class="nav-link <?= $page==='kampanye' ? 'active' : '' ?>">Kampanye Kebaikan</a>
      <a href="kegiatan.php" class="nav-link <?= in_array($page, ['kegiatan', 'jadwal']) ? 'active' : '' ?>">Jadwal & Kegiatan</a>
      <a href="tpa.php" class="nav-link <?= $page==='tpa' ? 'active' : '' ?>">Pendidikan TPA</a>

      <?php if (!empty($_SESSION['user'])): ?>
        <a href="admin.php" class="btn btn-primary" style="padding:8px 16px; font-size:0.88rem;">Panel Pengurus</a>
        <a href="logout.php" class="nav-link" style="color:var(--danger); font-size:0.88rem;">Keluar</a>
      <?php else: ?>
        <a href="login.php" class="btn-login-header">Portal Pengurus</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
