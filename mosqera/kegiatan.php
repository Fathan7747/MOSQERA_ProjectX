<?php
$title = 'Jadwal Shalat & Kegiatan Masjid';
$page = 'kegiatan';
require_once 'config.php';

$j = jadwal($db);

// Ambil Jadwal Khatib Jumat
$khatibList = $db->query("SELECT * FROM jadwal_kegiatan WHERE jenis='Khatib Jumat' ORDER BY tanggal ASC")->fetchAll();

// Ambil Jadwal Kajian (Mingguan & Tematik)
$kajianList = $db->query("SELECT * FROM jadwal_kegiatan WHERE jenis != 'Khatib Jumat' ORDER BY tanggal ASC")->fetchAll();

require 'includes/header.php';
?>

<div class="page-container">
  
  <div class="page-header-row">
    <div>
      <span class="page-badge">Portal Publik Jamaah</span>
      <h1 class="page-title">Jadwal Shalat & Informasi Kegiatan</h1>
      <p class="page-lead">Informasi waktu sholat fardhu, jadwal khatib sholat Jumat, dan kajian keislaman rutin di lingkungan masjid.</p>
    </div>
  </div>

  <!-- 1. Jadwal Shalat Harian -->
  <section class="card" style="margin-bottom:36px;">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.35rem;">Jadwal Shalat Harian</h3>
        <span style="font-size:0.88rem; color:var(--text-muted);"><?= date('l, d F Y') ?> (WIB)</span>
      </div>
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

  <!-- 2. Jadwal Penceramah / Khatib Shalat Jumat -->
  <section class="card" style="margin-bottom:36px;">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.35rem;">Jadwal Khatib & Imam Shalat Jumat</h3>
        <span style="font-size:0.88rem; color:var(--text-muted);">Agenda penceramah sholat Jumat bulan berjalan.</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Khatib / Penceramah</th>
            <th>Tema Khutbah</th>
            <th>Waktu & Lokasi</th>
            <th>Catatan Petugas</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$khatibList): ?>
            <tr><td colspan="5" style="text-align:center; padding:24px;">Belum ada jadwal khatib terdaftar.</td></tr>
          <?php else: ?>
            <?php foreach ($khatibList as $k): ?>
              <tr>
                <td style="white-space:nowrap; font-weight:600;"><?= date('d M Y', strtotime($k['tanggal'])) ?></td>
                <td><strong style="color:var(--primary); font-size:1rem;"><?= e($k['penceramah']) ?></strong></td>
                <td><?= e($k['judul']) ?></td>
                <td><?= e($k['waktu']) ?><br><small style="color:var(--text-muted);"><?= e($k['lokasi']) ?></small></td>
                <td style="font-size:0.85rem; color:var(--text-muted);"><?= e($k['keterangan'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- 3. Jadwal Kajian Mingguan & Tematik -->
  <section class="card">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.35rem;">Kajian Mingguan & Kajian Tematik</h3>
        <span style="font-size:0.88rem; color:var(--text-muted);">Majelis ilmu terbuka untuk umum (ikhwan & akhwat).</span>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:20px;">
      <?php if (!$kajianList): ?>
        <p style="color:var(--text-muted); padding:16px;">Belum ada agenda kajian terdaftar.</p>
      <?php else: ?>
        <?php foreach ($kajianList as $kajian): ?>
          <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); padding:20px; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <span class="badge-tag" style="background:var(--primary-subtle); color:var(--primary);"><?= e($kajian['jenis']) ?></span>
              <span style="font-size:0.82rem; color:var(--text-muted); font-weight:600;"><?= date('d M Y', strtotime($kajian['tanggal'])) ?></span>
            </div>
            
            <h4 style="font-size:1.15rem; margin-bottom:8px; color:var(--primary); font-family:inherit; font-weight:700;"><?= e($kajian['judul']) ?></h4>
            <p style="font-size:0.92rem; color:var(--text-main); margin-bottom:8px;">
              Pemateri: <strong><?= e($kajian['penceramah']) ?></strong>
            </p>
            <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:12px;">
              Waktu: <?= e($kajian['waktu']) ?> · Lokasi: <?= e($kajian['lokasi']) ?>
            </div>
            <?php if ($kajian['keterangan']): ?>
              <p style="font-size:0.85rem; color:var(--text-muted); margin-top:auto; padding-top:10px; border-top:1px dashed var(--border);">
                <?= e($kajian['keterangan']) ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

</div>

<script>window.JADWAL = <?= json_encode($j) ?>;</script>
<?php require 'includes/footer.php'; ?>
