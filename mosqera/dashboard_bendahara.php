<?php
$title = 'Dashboard Bendahara';
$page = 'bendahara';
require_once 'config.php';

// Pastikan user login dan memiliki role bendahara atau admin
if (empty($_SESSION['user'])) {
    header('Location: login.php?role=bendahara');
    exit;
}
if (!in_array($_SESSION['user']['role'] ?? '', ['bendahara', 'admin'])) {
    // Jika bukan bendahara, alihkan ke dashboard yang sesuai
    if ($_SESSION['user']['role'] === 'pengurus') {
        header('Location: dashboard_pengurus.php');
        exit;
    } else {
        header('Location: dashboard_jamaah.php');
        exit;
    }
}

$msg = '';
$err = '';

// Proses Tambah Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_transaksi') {
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $jenis = trim($_POST['jenis'] ?? 'masuk');
    $kategori = trim($_POST['kategori'] ?? 'Infaq');
    $judul = trim($_POST['judul'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $dibuat_oleh = $_SESSION['user']['nama'] ?? 'Bendahara';

    if ($judul === '' || $nominal <= 0) {
        $err = 'Mohon isi judul transaksi dan nominal yang valid.';
    } else {
        $ins = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, dibuat_oleh) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$tanggal, $jenis, $kategori, $judul, $nominal, $keterangan, $dibuat_oleh]);
        $msg = 'Transaksi kas berhasil dicatat ke sistem.';
    }
}

// Proses Hapus Transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_transaksi') {
    $idHapus = intval($_POST['id'] ?? 0);
    if ($idHapus > 0) {
        $del = $db->prepare("DELETE FROM transaksi_kas WHERE id=?");
        $del->execute([$idHapus]);
        $msg = 'Transaksi kas berhasil dihapus.';
    }
}

// Ambil Ringkasan Keuangan
$kas = ringkasan_kas($db);

// Filter list transaksi
$filterJenis = $_GET['filter'] ?? 'semua';
if ($filterJenis === 'masuk') {
    $stmt = $db->prepare("SELECT * FROM transaksi_kas WHERE jenis='masuk' ORDER BY tanggal DESC, id DESC");
    $stmt->execute();
} elseif ($filterJenis === 'keluar') {
    $stmt = $db->prepare("SELECT * FROM transaksi_kas WHERE jenis='keluar' ORDER BY tanggal DESC, id DESC");
    $stmt->execute();
} else {
    $stmt = $db->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC");
}
$listTransaksi = $stmt->fetchAll();

require 'includes/header.php';
?>

<section class="sec page">
  <div class="dash-head">
    <div>
      <span class="role-badge badge-bendahara">Peran: Admin / Bendahara</span>
      <h2>Dashboard Keuangan & Transaksi Kas</h2>
      <p class="sub">Input transaksi, verifikasi nota bukti, kelola kategori dana, dan terbitkan laporan resmi masjid.</p>
    </div>
    <div class="dash-actions">
      <button onclick="window.print()" class="btn ghost btn-print">🖨️ Cetak / Ekspor Laporan</button>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>

  <!-- Ringkasan Kartu Kas -->
  <div class="stat-grid">
    <div class="stat-card masuk">
      <div class="stat-icon">📥</div>
      <div class="stat-info">
        <span class="stat-label">Total Kas Masuk</span>
        <strong class="stat-val"><?= rupiah($kas['masuk']) ?></strong>
        <small>Infaq, Zakat, Donasi & Wakaf</small>
      </div>
    </div>
    <div class="stat-card keluar">
      <div class="stat-icon">📤</div>
      <div class="stat-info">
        <span class="stat-label">Total Kas Keluar</span>
        <strong class="stat-val"><?= rupiah($kas['keluar']) ?></strong>
        <small>Operasional, Kegiatan & Renovasi</small>
      </div>
    </div>
    <div class="stat-card saldo">
      <div class="stat-icon">💰</div>
      <div class="stat-info">
        <span class="stat-label">Saldo Kas Bersih</span>
        <strong class="stat-val"><?= rupiah($kas['saldo']) ?></strong>
        <small>Kas tersedia di rekening & brankas</small>
      </div>
    </div>
  </div>

  <div class="dash-content-grid">
    <!-- Form Input Transaksi -->
    <div class="card-box">
      <h3>➕ Input Transaksi Kas Baru</h3>
      <p class="text-muted">Catat setiap uang masuk atau pengeluaran dana masjid secara akuntabel.</p>
      <form method="post" class="form-compact">
        <input type="hidden" name="action" value="tambah_transaksi">
        
        <div class="form-row">
          <label>Tanggal Transaksi
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
          </label>
          <label>Jenis Transaksi
            <select name="jenis" required>
              <option value="masuk">Pemasukan (Kas Masuk)</option>
              <option value="keluar">Pengeluaran (Kas Keluar)</option>
            </select>
          </label>
        </div>

        <div class="form-row">
          <label>Kategori Dana
            <select name="kategori" required>
              <option value="Infaq Jumat">Infaq Jumat</option>
              <option value="Zakat & Sedekah">Zakat & Sedekah</option>
              <option value="Pembangunan">Pembangunan Masjid</option>
              <option value="Operasional">Operasional & Listrik</option>
              <option value="Sosial & Dakwah">Sosial & Dakwah</option>
              <option value="Lainnya">Lain-lain</option>
            </select>
          </label>
          <label>Nominal (Rp)
            <input type="number" name="nominal" min="1000" step="500" placeholder="Contoh: 500000" required>
          </label>
        </div>

        <label>Judul Transaksi
          <input type="text" name="judul" placeholder="Contoh: Infaq Kotak Amal Sholat Jumat Pekan ini" required>
        </label>

        <label>Keterangan / Rincian Nota
          <textarea name="keterangan" rows="2" placeholder="Catatan bukti transaksi, nota fisik disimpan di bendahara..."></textarea>
        </label>

        <button class="btn" style="width:100%; margin-top:8px;">💾 Simpan Transaksi Kas</button>
      </form>
    </div>

    <!-- Riwayat Transaksi -->
    <div class="card-box">
      <div class="box-head">
        <h3>📋 Riwayat Pembukuan Kas</h3>
        <div class="filter-pills">
          <a href="dashboard_bendahara.php?filter=semua" class="pill <?= $filterJenis==='semua'?'active':'' ?>">Semua</a>
          <a href="dashboard_bendahara.php?filter=masuk" class="pill <?= $filterJenis==='masuk'?'active':'' ?>">Kas Masuk</a>
          <a href="dashboard_bendahara.php?filter=keluar" class="pill <?= $filterJenis==='keluar'?'active':'' ?>">Kas Keluar</a>
        </div>
      </div>

      <?php if (!$listTransaksi): ?>
        <p class="text-muted" style="padding:24px 0; text-align:center;">Belum ada riwayat transaksi pada filter ini.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="tbl-custom">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Kategori & Judul</th>
                <th>Jenis</th>
                <th style="text-align:right;">Nominal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($listTransaksi as $t): ?>
                <tr>
                  <td><small><?= date('d/m/Y', strtotime($t['tanggal'])) ?></small></td>
                  <td>
                    <strong><?= e($t['judul']) ?></strong><br>
                    <small class="text-muted">Kategori: <?= e($t['kategori']) ?> · Oleh: <?= e($t['dibuat_oleh']) ?></small>
                    <?php if ($t['keterangan']): ?>
                      <br><small class="text-italic">Ket: <?= e($t['keterangan']) ?></small>
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
                  <td>
                    <form method="post" onsubmit="return confirm('Yakin ingin menghapus transaksi ini?');" style="display:inline;">
                      <input type="hidden" name="action" value="hapus_transaksi">
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button class="btn-del" title="Hapus Transaksi">🗑️</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
