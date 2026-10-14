<?php require_once __DIR__ . '/../config.php'; $page = $page ?? 'beranda'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'MOSQERA') ?> · MOSQERA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time() ?>">
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
    <a href="kas.php" class="<?= $page=='kas'?'on':'' ?>">Transparansi Kas</a>
    <div class="dd">
      <button class="dd-btn <?= in_array($page,['berita','jadwal'])?'on':'' ?>" aria-expanded="false">Lainnya ▾</button>
      <div class="dd-list">
        <a href="berita.php">Berita</a>
        <a href="jadwal.php">Jadwal Sholat</a>
      </div>
    </div>
    <?php if (!empty($_SESSION['user'])): 
      $uRole = $_SESSION['user']['role'] ?? 'jamaah';
      $dashUrl = in_array($uRole, ['bendahara','admin']) ? 'dashboard_bendahara.php' : ($uRole==='pengurus' ? 'dashboard_pengurus.php' : 'dashboard_jamaah.php');
      $dashLabel = in_array($uRole, ['bendahara','admin']) ? 'Bendahara' : ($uRole==='pengurus' ? 'Pengurus' : 'Jamaah');
    ?>
      <div class="user-nav">
        <a href="<?= $dashUrl ?>" class="btn-dash" title="Buka Dashboard">Dashboard (<?= $dashLabel ?>)</a>
        <span class="hi">Halo, <?= e($_SESSION['user']['nama']) ?></span>
        <a class="btn-logout" href="logout.php">Keluar</a>
      </div>
    <?php else: ?>
      <a class="btn" href="login.php">Masuk / Login</a>
    <?php endif; ?>
  </nav>
</header>
