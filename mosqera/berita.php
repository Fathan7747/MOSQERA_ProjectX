<?php
$title = 'Berita & Informasi';
$page = 'kegiatan';
require_once 'config.php';
require 'includes/header.php';
?>
<div class="page-container">
  <div class="page-header-row">
    <div>
      <span class="page-badge">Informasi Masjid</span>
      <h1 class="page-title">Berita & Informasi Masjid</h1>
      <p class="page-lead">Pengumuman penting dan warta kegiatan terkini.</p>
    </div>
  </div>

  <div class="card">
    <p style="color:var(--text-muted); margin:0;">
      Untuk jadwal shalat fardhu, agenda khatib sholat Jumat, dan kajian mingguan, silakan kunjungi halaman <a href="kegiatan.php" style="font-weight:700; color:var(--primary);">Jadwal & Informasi Kegiatan</a>.
    </p>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
