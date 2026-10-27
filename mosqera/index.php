<?php
$title = 'Portal Publik & Beranda Masjid';
$page = 'beranda';
require_once 'config.php';

$j = jadwal($db);
$kasBulan = ringkasan_kas_bulan($db);
$kasTotal = ringkasan_kas($db);

// Kampanye Kebaikan Terkini
$kampanyeList = $db->query("SELECT * FROM kampanye_donasi WHERE status='Aktif' ORDER BY id DESC LIMIT 3")->fetchAll();

// Jadwal Kegiatan & Sholat Jumat Terdekat
$kegiatanList = $db->query("SELECT * FROM jadwal_kegiatan WHERE tanggal >= CURDATE() - INTERVAL 1 DAY ORDER BY tanggal ASC LIMIT 2")->fetchAll();

// Hitung sholat berikutnya langsung di PHP agar langsung tampil tanpa menunggu JS
$currentTime = date('H:i');
$nextInitial = null;
foreach ($j as $sh) {
    if ($sh['waktu'] > $currentTime) {
        $nextInitial = $sh;
        break;
    }
}
if (!$nextInitial && !empty($j)) {
    $nextInitial = $j[0];
}
$initNama = $nextInitial['nama'] ?? 'Subuh';
$initWaktu = $nextInitial['waktu'] ?? '04:42';

$targetTimestamp = strtotime(date('Y-m-d ') . $initWaktu . ':00');
if ($targetTimestamp <= time()) {
    $targetTimestamp += 86400;
}
$diffInit = max(0, $targetTimestamp - time());
$initHours = str_pad(floor($diffInit / 3600), 2, '0', STR_PAD_LEFT);
$initMins = str_pad(floor(($diffInit % 3600) / 60), 2, '0', STR_PAD_LEFT);
$initSecs = str_pad($diffInit % 60, 2, '0', STR_PAD_LEFT);
$initCountdown = "$initHours:$initMins:$initSecs";

require 'includes/header.php';
?>

<!-- Hero Beranda Publik -->
<section class="hero-wrapper">
  <div class="hero-grid">
    <div class="hero-content">
      <span class="page-badge">Portal Publik Jamaah</span>
      <h1>Informasi Terbuka, Transparansi Dana, Kebersamaan Jamaah.</h1>
      <p>Aplikasi web MOSQERA menyajikan keterbukaan buku kas masjid, jadwal shalat harian, serta penghimpunan donasi digital yang amanah dan akuntabel.</p>
      
      <div class="hero-actions">
        <a href="transparansi.php" class="btn btn-primary">Lihat Transparansi Kas</a>
        <a href="kampanye.php" class="btn btn-secondary">Kampanye Kebaikan</a>
        <a href="kegiatan.php" class="btn btn-secondary">Jadwal Shalat & Kajian</a>
      </div>
    </div>

    <!-- Kotak Sholat Berikutnya -->
    <div>
      <div class="prayer-next-card">
        <span class="prayer-label">Sholat Fardhu Berikutnya</span>
        <h2 id="next-prayer-name" class="prayer-name"><?= e($initNama) ?></h2>
        <div id="next-prayer-time" class="prayer-time">Pukul <?= e($initWaktu) ?> WIB</div>
        <div id="next-prayer-count" class="prayer-countdown"><?= $initCountdown ?></div>
        <div class="prayer-countdown-label">Menuju adzan waktu sholat berikutnya</div>
      </div>
    </div>
  </div>
</section>

<div class="page-container">

  <!-- 1. Ringkasan Saldo Kas Masjid (Bulan Berjalan) -->
  <section class="section-cash-summary">
    <div class="page-header-row" style="margin-bottom:16px;">
      <div>
        <h2 style="margin:0 0 4px; font-size:1.75rem;">Transparansi Keuangan Bulan <?= strftime('%B %Y') ? date('F Y') : date('M Y') ?></h2>
        <p class="page-lead">Laporan posisi kas berjalan yang dapat dipantau oleh seluruh jamaah secara terbuka tanpa login.</p>
      </div>
      <a href="transparansi.php" class="btn btn-secondary" style="font-size:0.9rem;">Buka Laporan Rinci &rarr;</a>
    </div>

    <div class="cash-summary-grid">
      <div class="cash-widget saldo">
        <span class="cash-widget-title">Sisa Saldo Kas Utama</span>
        <strong class="cash-widget-amount"><?= rupiah($kasTotal['saldo']) ?></strong>
        <p class="cash-widget-desc">Posisi saldo kas riil masjid saat ini.</p>
      </div>

      <div class="cash-widget masuk">
        <span class="cash-widget-title">Uang Masuk (Bulan Ini)</span>
        <strong class="cash-widget-amount"><?= rupiah($kasBulan['masuk_bulan']) ?></strong>
        <p class="cash-widget-desc">Infaq tromol Jumat, transfer donatur & ZISWAF.</p>
      </div>

      <div class="cash-widget keluar">
        <span class="cash-widget-title">Uang Keluar (Bulan Ini)</span>
        <strong class="cash-widget-amount"><?= rupiah($kasBulan['keluar_bulan']) ?></strong>
        <p class="cash-widget-desc">Biaya listrik, operasional & bantuan sosial.</p>
      </div>
    </div>
  </section>

  <!-- 2. Jadwal Shalat Harian -->
  <section class="card" style="margin-top:36px;">
    <div class="card-header">
      <div>
        <h3 style="margin-bottom:4px;">Jadwal Shalat Hari Ini</h3>
        <span style="font-size:0.9rem; color:var(--text-muted);"><?= strftime('%A, %d %B %Y') ? date('l, d F Y') : date('d M Y') ?> (Waktu Indonesia Barat)</span>
      </div>
      <a href="kegiatan.php" class="btn btn-secondary" style="padding:6px 14px; font-size:0.85rem;">Jadwal Lengkap & Kajian</a>
    </div>

    <div class="prayer-times-grid">
      <?php foreach ($j as $sholat): ?>
        <div class="prayer-time-box" data-name="<?= e($sholat['nama']) ?>">
          <div class="p-name"><?= e($sholat['nama']) ?></div>
          <div class="p-clock"><?= e($sholat['waktu']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- 3. Kampanye Kebaikan (Donasi Digital) -->
  <section style="margin-top:48px;">
    <div class="page-header-row">
      <div>
        <h2 style="margin:0 0 4px; font-size:1.75rem;">Kampanye Kebaikan Masjid</h2>
        <p class="page-lead">Salurkan sedekah jariyah dan infaq terikat secara digital melalui transfer bank atau QRIS resmi.</p>
      </div>
      <a href="kampanye.php" class="btn btn-secondary" style="font-size:0.9rem;">Lihat Semua Program &rarr;</a>
    </div>

    <div class="campaign-grid">
      <?php foreach ($kampanyeList as $k): 
        $persen = $k['target_dana'] > 0 ? min(100, round(($k['terkumpul'] / $k['target_dana']) * 100)) : 0;
      ?>
        <div class="campaign-card">
          <span class="campaign-cat"><?= e($k['kategori']) ?></span>
          <h3 class="campaign-title"><?= e($k['judul']) ?></h3>
          <p class="campaign-desc"><?= e($k['deskripsi']) ?></p>

          <div class="progress-container">
            <div class="progress-bar-track">
              <div class="progress-bar-fill" style="width: <?= $persen ?>%;"></div>
            </div>
            <div class="progress-metrics">
              <span>Terkumpul: <strong><?= rupiah($k['terkumpul']) ?></strong></span>
              <span><strong><?= $persen ?>%</strong> dari <?= rupiah($k['target_dana']) ?></span>
            </div>
          </div>

          <a href="kampanye.php?id=<?= $k['id'] ?>" class="btn btn-primary" style="margin-top:auto; width:100%;">Salurkan Donasi</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- 4. Sorotan Kegiatan & Pendidikan TPA -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; margin-top:24px;">
    <!-- Kegiatan & Sholat Jumat -->
    <div class="card">
      <div class="card-header">
        <h3 style="font-size:1.25rem;">Agenda Khatib & Kajian</h3>
        <a href="kegiatan.php" style="font-size:0.85rem; font-weight:600;">Lihat Kalender &rarr;</a>
      </div>
      
      <?php if (!$kegiatanList): ?>
        <p style="color:var(--text-muted); font-size:0.9rem;">Belum ada jadwal kegiatan terdaftar pekan ini.</p>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
          <?php foreach ($kegiatanList as $keg): ?>
            <div style="border-left:3px solid var(--accent); padding-left:14px;">
              <span class="badge-tag" style="background:var(--primary-subtle); color:var(--primary); margin-bottom:4px;"><?= e($keg['jenis']) ?></span>
              <strong style="display:block; font-size:1.02rem; color:var(--text-main); margin-bottom:2px;"><?= e($keg['judul']) ?></strong>
              <small style="display:block; color:var(--primary); font-weight:600;"><?= e($keg['penceramah']) ?></small>
              <small style="color:var(--text-muted);"><?= date('d M Y', strtotime($keg['tanggal'])) ?> · <?= e($keg['waktu']) ?> · <?= e($keg['lokasi']) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pendidikan Santri TPA -->
    <div class="card">
      <div class="card-header">
        <h3 style="font-size:1.25rem;">Pendidikan Santri TPA</h3>
        <a href="tpa.php" style="font-size:0.85rem; font-weight:600;">Info Lengkap &rarr;</a>
      </div>
      <p style="font-size:0.92rem; color:var(--text-muted); margin-bottom:18px;">
        TPA Masjid membuka pendaftaran santri baru untuk kelas Iqro awal, tahsin, dan hafalan juz 'amma. Belajar dibimbing asatidz berkompeten dalam lingkungan masjid yang ramah anak.
      </p>
      <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); padding:16px; margin-bottom:18px;">
        <span style="font-size:0.85rem; font-weight:700; color:var(--primary); display:block; margin-bottom:4px;">Pendaftaran Santri Baru Dibuka</span>
        <span style="font-size:0.82rem; color:var(--text-muted);">Pendaftaran dapat dilakukan secara mandiri oleh orang tua/wali santri secara online.</span>
      </div>
      <a href="tpa.php#daftar" class="btn btn-secondary" style="width:100%;">Daftar Santri Baru Online</a>
    </div>
  </div>

</div>

<script>window.JADWAL = <?= json_encode($j) ?>;</script>
<?php require 'includes/footer.php'; ?>
