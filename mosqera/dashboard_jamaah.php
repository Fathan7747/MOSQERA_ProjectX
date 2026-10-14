<?php
$title = 'Portal Jamaah';
$page = 'jamaah';
require_once 'config.php';

// Pastikan user login (semua jamaah terdaftar bisa akses)
if (empty($_SESSION['user'])) {
    header('Location: login.php?role=jamaah');
    exit;
}

$user = $_SESSION['user'];
$kas = ringkasan_kas($db);

// Ambil 6 transaksi kas terbaru untuk transparansi jamaah
$transaksiTerbaru = $db->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 6")->fetchAll();

// Ambil program agenda terdekat
$agendaTerdekat = $db->query("SELECT * FROM program_agenda WHERE status != 'Selesai' ORDER BY tanggal ASC LIMIT 4")->fetchAll();

// Ambil berita terbaru
$beritaList = $db->query("SELECT * FROM berita ORDER BY tanggal DESC LIMIT 3")->fetchAll();

$j = jadwal($db);

require 'includes/header.php';
?>

<section class="sec page">
  <div class="dash-head">
    <div>
      <span class="role-badge badge-jamaah">Portal Jamaah Masjid</span>
      <h2>Ahlan wa Sahlan, <?= e($user['nama']) ?></h2>
      <p class="sub">Pantau transparansi kas masjid, laporan keuangan real-time, dan agenda kegiatan masjid secara terbuka.</p>
    </div>
    <div class="dash-actions">
      <a href="kas.php" class="btn">📊 Transparansi Kas Lengkap</a>
      <a href="jadwal.php" class="btn ghost">⏰ Jadwal Sholat</a>
    </div>
  </div>

  <!-- Kartu Transparansi Kas untuk Jamaah -->
  <div class="stat-grid">
    <div class="stat-card saldo">
      <div class="stat-icon">🕌</div>
      <div class="stat-info">
        <span class="stat-label">Saldo Kas Masjid Terbuka</span>
        <strong class="stat-val"><?= rupiah($kas['saldo']) ?></strong>
        <small>Akuntabel & dapat ditelusuri jamaah</small>
      </div>
    </div>
    <div class="stat-card masuk">
      <div class="stat-icon">📥</div>
      <div class="stat-info">
        <span class="stat-label">Total Infaq & Donasi Masuk</span>
        <strong class="stat-val"><?= rupiah($kas['masuk']) ?></strong>
        <small>Dari infaq Jumat & donasi jamaah</small>
      </div>
    </div>
    <div class="stat-card keluar">
      <div class="stat-icon">📤</div>
      <div class="stat-info">
        <span class="stat-label">Penyaluran & Operasional</span>
        <strong class="stat-val"><?= rupiah($kas['keluar']) ?></strong>
        <small>Untuk dakwah, fasilitas & sosial</small>
      </div>
    </div>
  </div>

  <!-- Visualisasi Bar Perbandingan Keuangan -->
  <div class="card-box" style="margin-bottom:28px;">
    <h3>📊 Visualisasi Transparansi Keuangan Kas</h3>
    <p class="text-muted">Perbandingan alokasi dana masuk dan pengeluaran operasional masjid.</p>
    <?php
      $totalArus = ($kas['masuk'] + $kas['keluar']) ?: 1;
      $persenMasuk = round(($kas['masuk'] / $totalArus) * 100);
      $persenKeluar = round(($kas['keluar'] / $totalArus) * 100);
    ?>
    <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-weight:600; font-size:0.9rem;">
      <span style="color:#1f5c8a;">Pemasukan (<?= $persenMasuk ?>%)</span>
      <span style="color:#c0392b;">Pengeluaran (<?= $persenKeluar ?>%)</span>
    </div>
    <div style="height:24px; border-radius:12px; background:#e0ece9; overflow:hidden; display:flex;">
      <div style="width:<?= $persenMasuk ?>%; background:#1f5c8a;" title="Pemasukan: <?= rupiah($kas['masuk']) ?>"></div>
      <div style="width:<?= $persenKeluar ?>%; background:#e74c3c;" title="Pengeluaran: <?= rupiah($kas['keluar']) ?>"></div>
    </div>
  </div>

  <div class="dash-content-grid">
    <!-- Transparansi Transaksi Terbaru -->
    <div class="card-box">
      <div class="box-head">
        <h3>🔍 Transparansi Kas Terkini</h3>
        <a href="kas.php" style="font-size:0.85rem; font-weight:600;">Lihat Semua &rarr;</a>
      </div>
      <p class="text-muted">Setiap rupiah yang masuk dan dibelanjakan dapat dipantau oleh seluruh jamaah.</p>

      <div class="table-responsive">
        <table class="tbl-custom">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Transaksi</th>
              <th style="text-align:right;">Nominal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transaksiTerbaru as $t): ?>
              <tr>
                <td><small><?= date('d/m/Y', strtotime($t['tanggal'])) ?></small></td>
                <td>
                  <strong><?= e($t['judul']) ?></strong><br>
                  <small class="text-muted"><?= e($t['kategori']) ?></small>
                </td>
                <td style="text-align:right; font-weight:700;" class="<?= $t['jenis']=='masuk'?'text-success':'text-danger' ?>">
                  <?= $t['jenis']=='masuk' ? '+' : '-' ?> <?= rupiah($t['nominal']) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Agenda & Berita Masjid -->
    <div>
      <!-- Agenda Masjid -->
      <div class="card-box" style="margin-bottom:24px;">
        <div class="box-head">
          <h3>🗓️ Agenda Masjid Terdekat</h3>
        </div>
        <?php if (!$agendaTerdekat): ?>
          <p class="text-muted">Belum ada agenda kegiatan baru.</p>
        <?php else: ?>
          <div class="agenda-list-compact">
            <?php foreach ($agendaTerdekat as $ag): ?>
              <div class="agenda-compact-item">
                <div class="agenda-compact-date">
                  <strong><?= date('d', strtotime($ag['tanggal'])) ?></strong>
                  <small><?= date('M', strtotime($ag['tanggal'])) ?></small>
                </div>
                <div>
                  <h4 style="margin:0 0 2px;"><?= e($ag['nama_program']) ?></h4>
                  <small class="text-muted">Target: <?= rupiah($ag['target_anggaran']) ?> · Status: <?= e($ag['status']) ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Berita Masjid -->
      <div class="card-box">
        <div class="box-head">
          <h3>📢 Berita & Pengumuman</h3>
          <a href="berita.php" style="font-size:0.85rem; font-weight:600;">Semua Berita &rarr;</a>
        </div>
        <?php foreach ($beritaList as $b): ?>
          <div style="border-bottom:1px solid #e1ebe8; padding:10px 0;">
            <small style="color:#1f5c8a; font-weight:600;"><?= date('d M Y', strtotime($b['tanggal'])) ?></small>
            <h4 style="margin:4px 0 6px;"><?= e($b['judul']) ?></h4>
            <p style="margin:0; font-size:0.9rem; color:#555;"><?= mb_strimwidth(strip_tags($b['isi']), 0, 100, '...') ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
