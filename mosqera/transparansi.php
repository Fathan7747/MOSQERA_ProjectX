<?php
$title = 'Transparansi Kas Masjid';
$page = 'transparansi';
require_once 'config.php';

$kas = ringkasan_kas($db);

// Filter Jenis
$jenisFilter = $_GET['jenis'] ?? 'semua';
$kategoriFilter = $_GET['kategori'] ?? 'semua';

$query = "SELECT * FROM transaksi_kas WHERE 1=1";
$params = [];

if (in_array($jenisFilter, ['masuk', 'keluar'])) {
    $query .= " AND jenis = ?";
    $params[] = $jenisFilter;
}

if ($kategoriFilter !== 'semua' && $kategoriFilter !== '') {
    $query .= " AND kategori = ?";
    $params[] = $kategoriFilter;
}

$query .= " ORDER BY tanggal DESC, id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$daftarTransaksi = $stmt->fetchAll();

// Data untuk Grafik Arus Kas
$arusKategoriMasuk = $db->query("SELECT kategori, SUM(nominal) as total FROM transaksi_kas WHERE jenis='masuk' GROUP BY kategori")->fetchAll();
$arusKategoriKeluar = $db->query("SELECT kategori, SUM(nominal) as total FROM transaksi_kas WHERE jenis='keluar' GROUP BY kategori")->fetchAll();

$namaMasjid = pengaturan($db, 'nama_masjid', "MASJID BAITURRAHIM");
$alamatMasjid = pengaturan($db, 'alamat', "Jl. Jend. Sudirman No.70, Rembiga, Kec. Selaparang, Kota Mataram");

require 'includes/header.php';
?>

<!-- Header Khusus Cetak Mading / PDF -->
<div class="print-header">
  <h2 style="margin:0; font-size:1.6rem;"><?= e($namaMasjid) ?></h2>
  <p style="margin:4px 0 12px; font-size:0.9rem;"><?= e($alamatMasjid) ?></p>
  <h3 style="margin:0; font-size:1.2rem; text-decoration:underline;">LAPORAN TRANSPARANSI KEUANGAN KAS MASJID</h3>
  <small>Tanggal Cetak: <?= e(tanggal_indo()) ?> · Laporan Terbuka untuk Seluruh Jamaah</small>
</div>

<div class="page-container">
  
  <div class="page-header-row no-print">
    <div>
      <span class="page-badge">Portal Publik Jamaah</span>
      <h1 class="page-title">Transparansi Keuangan Kas Masjid</h1>
      <p class="page-lead">Laporan resmi keuangan masjid yang disajikan secara akuntabel, dapat diakses dan diperiksa oleh seluruh jamaah tanpa perlu login.</p>
    </div>
    <div style="display:flex; gap:10px;">
      <button onclick="window.print()" class="btn btn-secondary">Cetak Laporan</button>
    </div>
  </div>

  <!-- Widget Ringkasan Kas Utama -->
  <div class="cash-summary-grid" style="margin-bottom:32px;">
    <div class="cash-widget saldo">
      <span class="cash-widget-title">Sisa Saldo Kas Utama</span>
      <strong class="cash-widget-amount"><?= rupiah($kas['saldo']) ?></strong>
      <p class="cash-widget-desc">Posisi saldo kas riil saat ini yang tersimpan di rekening dan kas kecil.</p>
    </div>

    <div class="cash-widget masuk">
      <span class="cash-widget-title">Total Uang Masuk</span>
      <strong class="cash-widget-amount"><?= rupiah($kas['masuk']) ?></strong>
      <p class="cash-widget-desc">Dari tromol Jumat, donasi transfer, ZISWAF, dan infaq terikat.</p>
    </div>

    <div class="cash-widget keluar">
      <span class="cash-widget-title">Total Uang Keluar</span>
      <strong class="cash-widget-amount"><?= rupiah($kas['keluar']) ?></strong>
      <p class="cash-widget-desc">Alokasi operasional masjid, pemeliharaan, serta bantuan sosial.</p>
    </div>
  </div>

  <!-- Grafik Arus Kas Masjid (Visualisasi Bersih) -->
  <section class="card no-print">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.25rem;">Grafik Arus Kas Masjid</h3>
        <span style="font-size:0.85rem; color:var(--text-muted);">Visualisasi komposisi pemasukan dan pengeluaran dana kas.</span>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px;">
      <!-- Komposisi Uang Masuk -->
      <div>
        <h4 style="font-size:1rem; color:var(--success); margin-bottom:12px; font-family:inherit; font-weight:700;">Uang Masuk Berdasarkan Kategori</h4>
        <?php if (!$arusKategoriMasuk): ?>
          <p style="font-size:0.88rem; color:var(--text-muted);">Belum ada data uang masuk.</p>
        <?php else: ?>
          <?php foreach ($arusKategoriMasuk as $km): 
            $pctIn = $kas['masuk'] > 0 ? round(($km['total'] / $kas['masuk']) * 100) : 0;
          ?>
            <div style="margin-bottom:12px;">
              <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:4px;">
                <span><?= e($km['kategori']) ?></span>
                <strong><?= rupiah($km['total']) ?> (<?= $pctIn ?>%)</strong>
              </div>
              <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width:<?= $pctIn ?>%; background:var(--success);"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Komposisi Uang Keluar -->
      <div>
        <h4 style="font-size:1rem; color:var(--danger); margin-bottom:12px; font-family:inherit; font-weight:700;">Uang Keluar Berdasarkan Kategori</h4>
        <?php if (!$arusKategoriKeluar): ?>
          <p style="font-size:0.88rem; color:var(--text-muted);">Belum ada data uang keluar.</p>
        <?php else: ?>
          <?php foreach ($arusKategoriKeluar as $kk): 
            $pctOut = $kas['keluar'] > 0 ? round(($kk['total'] / $kas['keluar']) * 100) : 0;
          ?>
            <div style="margin-bottom:12px;">
              <div style="display:flex; justify-content:space-between; font-size:0.88rem; margin-bottom:4px;">
                <span><?= e($kk['kategori']) ?></span>
                <strong><?= rupiah($kk['total']) ?> (<?= $pctOut ?>%)</strong>
              </div>
              <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width:<?= $pctOut ?>%; background:var(--danger);"></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Laporan Mutasi Kas & Filter -->
  <section class="card">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.25rem;">Rincian Transparansi Transaksi Kas</h3>
        <span style="font-size:0.85rem; color:var(--text-muted);">Menampilkan catatan setiap uang masuk dan keluar secara kronologis.</span>
      </div>

      <div class="filter-tabs no-print">
        <a href="transparansi.php?jenis=semua" class="filter-tab <?= $jenisFilter==='semua' ? 'active' : '' ?>">Semua</a>
        <a href="transparansi.php?jenis=masuk" class="filter-tab <?= $jenisFilter==='masuk' ? 'active' : '' ?>">Uang Masuk</a>
        <a href="transparansi.php?jenis=keluar" class="filter-tab <?= $jenisFilter==='keluar' ? 'active' : '' ?>">Uang Keluar</a>
      </div>
    </div>

    <!-- Filter Kategori Cepat -->
    <div class="no-print" style="margin-bottom:18px; display:flex; gap:8px; flex-wrap:wrap; font-size:0.85rem; align-items:center;">
      <span style="color:var(--text-muted); font-weight:600;">Filter Kategori:</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=semua" style="color:<?= $kategoriFilter==='semua'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='semua'?'700':'400' ?>;">Semua</a>
      <span>·</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=Tromol+Jumat" style="color:<?= $kategoriFilter==='Tromol Jumat'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='Tromol Jumat'?'700':'400' ?>;">Tromol Jumat</a>
      <span>·</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=Donasi+Transfer" style="color:<?= $kategoriFilter==='Donasi Transfer'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='Donasi Transfer'?'700':'400' ?>;">Donasi Transfer</a>
      <span>·</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=ZISWAF" style="color:<?= $kategoriFilter==='ZISWAF'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='ZISWAF'?'700':'400' ?>;">ZISWAF</a>
      <span>·</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=Operasional+Masjid" style="color:<?= $kategoriFilter==='Operasional Masjid'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='Operasional Masjid'?'700':'400' ?>;">Operasional Masjid</a>
      <span>·</span>
      <a href="transparansi.php?jenis=<?= urlencode($jenisFilter) ?>&kategori=Bantuan+Sosial" style="color:<?= $kategoriFilter==='Bantuan Sosial'?'var(--primary)':'var(--text-muted)' ?>; font-weight:<?= $kategoriFilter==='Bantuan Sosial'?'700':'400' ?>;">Bantuan Sosial</a>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Keterangan / Uraian</th>
            <th>Jenis</th>
            <th style="text-align:right;">Nominal (Rp)</th>
            <th>Bukti Transaksi</th>
            <th class="no-print">Detail</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$daftarTransaksi): ?>
            <tr>
              <td colspan="7" style="text-align:center; padding:32px; color:var(--text-muted);">
                Tidak ada data transaksi kas yang sesuai dengan filter yang dipilih.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($daftarTransaksi as $t): 
              $buktiAda = !empty($t['bukti_file']) && file_bukti_tersedia($t['bukti_file']);
              $buktiUrl = $buktiAda ? url_bukti_transaksi($t['bukti_file']) : '';
              $isPdf = $buktiAda && is_pdf_bukti($t['bukti_file']);
              $transaksiJson = json_encode([
                'id' => $t['id'],
                'tanggal' => $t['tanggal'],
                'tanggal_format' => date('d F Y', strtotime($t['tanggal'])),
                'jenis' => $t['jenis'],
                'kategori' => $t['kategori'],
                'judul' => $t['judul'],
                'nominal' => (float)$t['nominal'],
                'keterangan' => $t['keterangan'] ?? '',
                'dibuat_oleh' => $t['dibuat_oleh'] ?? 'Bendahara',
                'created_at' => !empty($t['created_at']) ? date('d/m/Y H:i', strtotime($t['created_at'])) : '-',
                'bukti_file' => $t['bukti_file'] ?? '',
                'bukti_url' => $buktiUrl,
                'detail_url' => 'detail_transaksi.php?id=' . $t['id']
              ]);
            ?>
              <tr>
                <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                <td><span style="font-weight:600; color:var(--primary);"><?= e($t['kategori']) ?></span></td>
                <td>
                  <strong><?= e($t['judul']) ?></strong>
                  <?php if (!empty($t['keterangan'])): ?>
                    <div style="font-size:0.85rem; color:var(--text-muted); margin-top:2px;"><?= e($t['keterangan']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge-tag <?= $t['jenis']==='masuk' ? 'badge-masuk' : 'badge-keluar' ?>">
                    <?= $t['jenis']==='masuk' ? 'Uang Masuk' : 'Uang Keluar' ?>
                  </span>
                </td>
                <td style="text-align:right; font-weight:700; color:<?= $t['jenis']==='masuk' ? 'var(--success)' : 'var(--danger)' ?>;">
                  <?= $t['jenis']==='masuk' ? '+' : '-' ?> <?= rupiah($t['nominal']) ?>
                </td>
                <td>
                  <?php if ($buktiAda): ?>
                    <button type="button" class="btn-table-bukti js-btn-detail-transaksi" data-transaksi="<?= e($transaksiJson) ?>" title="Klik untuk memeriksa bukti transaksi">
                      <?php if ($isPdf): ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                        <span>PDF</span>
                      <?php else: ?>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                        <span>Bukti</span>
                      <?php endif; ?>
                    </button>
                  <?php else: ?>
                    <span class="table-bukti-none">Tanpa Bukti</span>
                  <?php endif; ?>
                </td>
                <td class="no-print">
                  <a href="detail_transaksi.php?id=<?= $t['id'] ?>" class="btn-table-detail" title="Lihat detail lengkap">
                    Detail &rarr;
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<?php require 'includes/footer.php'; ?>
