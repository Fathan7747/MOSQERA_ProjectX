<?php
$title = 'Transparansi Kas Masjid';
$page = 'kas';
require_once 'config.php';

$kas = ringkasan_kas($db);

// Filter
$kategoriFilter = $_GET['kategori'] ?? 'semua';
$jenisFilter = $_GET['jenis'] ?? 'semua';

$query = "SELECT * FROM transaksi_kas WHERE 1=1";
$params = [];

if ($jenisFilter !== 'semua' && in_array($jenisFilter, ['masuk', 'keluar'])) {
    $query .= " AND jenis = ?";
    $params[] = $jenisFilter;
}

$query .= " ORDER BY tanggal DESC, id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transaksi = $stmt->fetchAll();

require 'includes/header.php';
?>

<section class="sec page">
  <div class="dash-head">
    <div>
      <span class="role-badge badge-jamaah">Publik & Transparan</span>
      <h2>Laporan Keuangan Kas Masjid Real-Time</h2>
      <p class="sub">Seluruh transaksi infaq, sedekah, dan belanja kas disajikan secara terbuka demi akuntabilitas dan kepercayaan penuh jamaah.</p>
    </div>
    <div class="dash-actions">
      <button onclick="window.print()" class="btn ghost btn-print">🖨️ Cetak Rekapitulasi</button>
    </div>
  </div>

  <!-- Kartu Kas -->
  <div class="stat-grid">
    <div class="stat-card masuk">
      <div class="stat-icon">📥</div>
      <div class="stat-info">
        <span class="stat-label">Total Pemasukan</span>
        <strong class="stat-val"><?= rupiah($kas['masuk']) ?></strong>
        <small>Infaq, Sedekah & Donasi</small>
      </div>
    </div>
    <div class="stat-card keluar">
      <div class="stat-icon">📤</div>
      <div class="stat-info">
        <span class="stat-label">Total Pengeluaran</span>
        <strong class="stat-val"><?= rupiah($kas['keluar']) ?></strong>
        <small>Operasional & Pembangunan</small>
      </div>
    </div>
    <div class="stat-card saldo">
      <div class="stat-icon">💰</div>
      <div class="stat-info">
        <span class="stat-label">Saldo Kas Saat Ini</span>
        <strong class="stat-val"><?= rupiah($kas['saldo']) ?></strong>
        <small>Posisi saldo kas terbuka</small>
      </div>
    </div>
  </div>

  <div class="card-box" style="margin-top:24px;">
    <div class="box-head">
      <h3>📋 Rincian Transparansi Transaksi Kas</h3>
      <div class="filter-pills">
        <a href="kas.php?jenis=semua" class="pill <?= $jenisFilter==='semua'?'active':'' ?>">Semua</a>
        <a href="kas.php?jenis=masuk" class="pill <?= $jenisFilter==='masuk'?'active':'' ?>">Kas Masuk</a>
        <a href="kas.php?jenis=keluar" class="pill <?= $jenisFilter==='keluar'?'active':'' ?>">Kas Keluar</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="tbl-custom">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori & Deskripsi</th>
            <th>Jenis</th>
            <th style="text-align:right;">Nominal</th>
            <th>Pencatat</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$transaksi): ?>
            <tr><td colspan="5" style="text-align:center; padding:20px;">Belum ada data transaksi kas.</td></tr>
          <?php else: ?>
            <?php foreach ($transaksi as $t): ?>
              <tr>
                <td><small><?= date('d M Y', strtotime($t['tanggal'])) ?></small></td>
                <td>
                  <strong><?= e($t['judul']) ?></strong><br>
                  <small class="text-muted">Kategori: <?= e($t['kategori']) ?></small>
                  <?php if ($t['keterangan']): ?>
                    <br><small class="text-italic"><?= e($t['keterangan']) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge-type <?= $t['jenis']=='masuk'?'type-masuk':'type-keluar' ?>">
                    <?= $t['jenis']=='masuk' ? '↓ Masuk' : '↑ Keluar' ?>
                  </span>
                </td>
                <td style="text-align:right; font-weight:700;" class="<?= $t['jenis']=='masuk'?'text-success':'text-danger' ?>">
                  <?= $t['jenis']=='masuk' ? '+' : '-' ?> <?= rupiah($t['nominal']) ?>
                </td>
                <td><small><?= e($t['dibuat_oleh']) ?></small></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
