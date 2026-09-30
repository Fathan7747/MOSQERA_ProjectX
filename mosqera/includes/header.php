<?php require_once __DIR__ . '/../config.php'; $page = $page ?? 'beranda'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'MOSQERA') ?> · MOSQERA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="nav">
  <a class="logo" href="index.php">MOSQERA</a>
  <button class="burger" aria-label="Menu" aria-expanded="false">Menu</button>
  <nav id="menu">
    <a href="index.php" class="<?= $page=='beranda'?'on':'' ?>">Beranda</a>
    <a href="index.php#tentang">Tentang</a>
    <a href="index.php#fitur">Fitur</a>
    <a href="index.php#faq">FAQ</a>
    <div class="dd">
      <button class="dd-btn <?= in_array($page,['berita','jadwal'])?'on':'' ?>" aria-expanded="false">Lainnya ▾</button>
      <div class="dd-list">
        <a href="berita.php">Berita</a>
        <a href="jadwal.php">Jadwal</a>
      </div>
    </div>
    <?php if (!empty($_SESSION['user'])): ?>
      <span class="hi">Halo, <?= e($_SESSION['user']['nama']) ?></span>
      <a class="btn" href="logout.php">Keluar</a>
    <?php else: ?>
      <a class="btn" href="login.php">Masuk / Login</a>
    <?php endif; ?>
  </nav>
</header>
