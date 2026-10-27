<?php
$title = 'Portal Pengurus & Admin';
$page = 'admin';
require_once 'config.php';

// Pastikan user telah login
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
if (strtolower($user['role'] ?? '') !== 'jamaah') {
    $user['role'] = 'Pengurus';
    $_SESSION['user']['role'] = 'Pengurus';
}
$tab = $_GET['tab'] ?? 'dasbor';
if ($tab === 'tpa') {
    header('Location: admin.php?tab=dasbor');
    exit;
}
$sub = $_GET['sub'] ?? '';

$msg = '';
$err = '';

// ==========================================
// 1. HANDLER AKSI BUKU KAS
// ==========================================
// Tambah Uang Masuk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_uang_masuk') {
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $kategori = trim($_POST['kategori'] ?? 'Tromol Jumat');
    $judul = trim($_POST['judul'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $pencatat = $user['nama'];

    if (empty($judul) || $nominal <= 0) {
        $err = 'Mohon isi uraian pemasukan dan nominal yang valid.';
    } else {
        $ins = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, dibuat_oleh) VALUES (?, 'masuk', ?, ?, ?, ?, ?)");
        $ins->execute([$tanggal, $kategori, $judul, $nominal, $keterangan, $pencatat]);
        $msg = 'Uang masuk sebesar ' . rupiah($nominal) . ' berhasil dicatat.';
    }
}

// Catat Uang Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'catat_uang_keluar') {
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $kategori = trim($_POST['kategori'] ?? 'Operasional Masjid');
    $judul = trim($_POST['judul'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $buktiNota = trim($_POST['bukti_nota'] ?? '');
    $pencatat = $user['nama'];

    if (empty($judul) || $nominal <= 0) {
        $err = 'Mohon isi uraian pengeluaran dan nominal yang valid.';
    } else {
        $ins = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, bukti_nota, dibuat_oleh) VALUES (?, 'keluar', ?, ?, ?, ?, ?, ?)");
        $ins->execute([$tanggal, $kategori, $judul, $nominal, $keterangan, $buktiNota, $pencatat]);
        $msg = 'Pengeluaran kas sebesar ' . rupiah($nominal) . ' berhasil dicatat.';
    }
}

// Hapus Transaksi Kas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_transaksi') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $del = $db->prepare("DELETE FROM transaksi_kas WHERE id=?");
        $del->execute([$id]);
        $msg = 'Transaksi kas berhasil dihapus dari pembukuan.';
    }
}

// ==========================================
// 2. HANDLER ZISWAF & KAMPANYE DONASI
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_kampanye') {
    $judul = trim($_POST['judul'] ?? '');
    $kategori = trim($_POST['kategori'] ?? 'Fasilitas Masjid');
    $targetDana = floatval($_POST['target_dana'] ?? 0);
    $batasWaktu = !empty($_POST['batas_waktu']) ? $_POST['batas_waktu'] : null;
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($judul) || $targetDana <= 0) {
        $err = 'Mohon isi nama kampanye dan target dana dengan benar.';
    } else {
        $ins = $db->prepare("INSERT INTO kampanye_donasi (judul, kategori, target_dana, batas_waktu, deskripsi, status) VALUES (?,?,?,?,?,'Aktif')");
        $ins->execute([$judul, $kategori, $targetDana, $batasWaktu, $deskripsi]);
        $msg = 'Program kampanye donasi baru berhasil dipublikasikan.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verifikasi_donasi') {
    $donasiId = intval($_POST['donasi_id'] ?? 0);
    $statusDonasi = trim($_POST['status'] ?? 'Terverifikasi');
    if ($donasiId > 0) {
        $upd = $db->prepare("UPDATE donasi_masuk SET status=? WHERE id=?");
        $upd->execute([$statusDonasi, $donasiId]);
        $msg = 'Status verifikasi donasi diperbarui.';
    }
}

// ==========================================
// 3. HANDLER JADWAL & KAJIAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_kegiatan') {
    $jenis = trim($_POST['jenis'] ?? 'Khatib Jumat');
    $judul = trim($_POST['judul'] ?? '');
    $penceramah = trim($_POST['penceramah'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $waktu = trim($_POST['waktu'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? 'Ruang Utama Masjid');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if (empty($judul) || empty($penceramah)) {
        $err = 'Mohon isi judul kegiatan dan nama penceramah/khatib.';
    } else {
        $ins = $db->prepare("INSERT INTO jadwal_kegiatan (jenis, judul, penceramah, tanggal, waktu, lokasi, keterangan) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$jenis, $judul, $penceramah, $tanggal, $waktu, $lokasi, $keterangan]);
        $msg = 'Jadwal kegiatan masjid berhasil ditambahkan.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_kegiatan') {
    $idKeg = intval($_POST['id'] ?? 0);
    if ($idKeg > 0) {
        $del = $db->prepare("DELETE FROM jadwal_kegiatan WHERE id=?");
        $del->execute([$idKeg]);
        $msg = 'Jadwal kegiatan berhasil dihapus.';
    }
}

// ==========================================
// 4. HANDLER PENGATURAN SISTEM
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_pengaturan') {
    $keys = ['nama_masjid', 'alamat', 'telepon', 'email', 'bank_info', 'qris_label'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = trim($_POST[$k]);
            $stmtSet = $db->prepare("INSERT INTO pengaturan_masjid (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
            $stmtSet->execute([$k, $val]);
        }
    }
    $msg = 'Pengaturan informasi masjid berhasil disimpan.';
}

// ==========================================
// PENGAMBILAN DATA
// ==========================================
$kas = ringkasan_kas($db);
$kasBulan = ringkasan_kas_bulan($db);

$statDonasiTotal = (float)$db->query("SELECT COALESCE(SUM(nominal),0) FROM donasi_masuk WHERE status='Terverifikasi'")->fetchColumn();

$namaMasjid = pengaturan($db, 'nama_masjid', "Masjid Jami' As-Salam");
$alamatMasjid = pengaturan($db, 'alamat', "Jl. Silaturahmi Raya No. 45");
$teleponMasjid = pengaturan($db, 'telepon', "0812-3456-7890");
$emailMasjid = pengaturan($db, 'email', "sekretariat@mosqera.id");
$bankInfo = pengaturan($db, 'bank_info', "Bank Syariah Indonesia (BSI) No. Rekening: 7123-456-789 a.n Kas DKM Masjid Jami As-Salam");
$qrisLabel = pengaturan($db, 'qris_label', "QRIS Standar Pembayaran Nasional (NMID: ID1029384756)");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal Pengurus · MOSQERA - <?= e($namaMasjid) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time() ?>">
</head>
<body style="background:#f4f7f6;">

<!-- Header Portal Pengurus (Senior Friendly: Jelas & Kontras) -->
<header class="admin-navbar no-print">
  <div class="admin-nav-container">
    <div style="display:flex; align-items:center; gap:16px;">
      <a class="admin-brand" href="admin.php">MOSQERA</a>
      <span style="opacity:0.75; font-size:0.88rem;">| <?= e($namaMasjid) ?></span>
    </div>

    <div class="admin-user-info">
      <span><strong>Selamat Datang!</strong> <strong><?= e($user['nama']) ?></strong></span>
      <a href="logout.php" class="admin-btn-logout">Keluar</a>
    </div>
  </div>
</header>

<!-- Tab Navigasi Admin Sesuai Sitemap -->
<nav class="admin-nav-tabs no-print">
  <div class="admin-nav-tabs-inner">
    <a href="admin.php?tab=dasbor" class="admin-tab-item <?= $tab==='dasbor'?'active':'' ?>">Dasbor Utama</a>
    <a href="admin.php?tab=kas" class="admin-tab-item <?= $tab==='kas'?'active':'' ?>">Buku Kas Masjid</a>
    <a href="admin.php?tab=donasi" class="admin-tab-item <?= $tab==='donasi'?'active':'' ?>">Manajemen ZISWAF & Donasi</a>
    <a href="admin.php?tab=jadwal" class="admin-tab-item <?= $tab==='jadwal'?'active':'' ?>">Manajemen Jadwal & Kajian</a>
    <a href="admin.php?tab=pengaturan" class="admin-tab-item <?= $tab==='pengaturan'?'active':'' ?>">Pengaturan Sistem</a>
  </div>
</nav>

<!-- Header Print Khusus Laporan Mading -->
<div class="print-header">
  <h2 style="margin:0; font-size:1.6rem;"><?= e($namaMasjid) ?></h2>
  <p style="margin:4px 0 12px; font-size:0.9rem;"><?= e($alamatMasjid) ?></p>
  <h3 style="margin:0; font-size:1.2rem; text-decoration:underline;">PUBLIKASI MADING: LAPORAN KEUANGAN KAS MASJID</h3>
  <small>Tanggal Cetak: <?= date('d F Y') ?> · Disahkan oleh Pengurus DKM</small>
</div>

<main class="page-container" style="padding-top:24px;">

  <?php if ($msg): ?>
    <div class="alert alert-success no-print"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="alert alert-error no-print"><?= e($err) ?></div>
  <?php endif; ?>

  <!-- ====================================================================
       TAB 1: DASBOR UTAMA
       ==================================================================== -->
  <?php if ($tab === 'dasbor'): ?>
    
    <!-- PINTASAN CEPAT RAMAH LANSIA / SENIOR -->
    <div class="quick-action-grid no-print">
      <a href="admin.php?tab=kas&aksi=masuk#form-transaksi" class="btn-quick-in">
        + Tambah Uang Masuk
      </a>
      <a href="admin.php?tab=kas&aksi=keluar#form-transaksi" class="btn-quick-out">
        - Catat Uang Keluar
      </a>
    </div>

    <!-- WIDGET SALDO & STATISTIK UTAMA -->
    <div class="cash-summary-grid">
      <div class="cash-widget saldo">
        <span class="cash-widget-title">Widget Sisa Saldo Kas Utama</span>
        <strong class="cash-widget-amount"><?= rupiah($kas['saldo']) ?></strong>
        <p class="cash-widget-desc">Posisi saldo kas terbuka yang siap digunakan untuk operasional masjid.</p>
      </div>

      <div class="cash-widget masuk">
        <span class="cash-widget-title">Widget Statistik Donasi Masuk</span>
        <strong class="cash-widget-amount"><?= rupiah($statDonasiTotal) ?></strong>
        <p class="cash-widget-desc">Total dana kampanye dan sedekah digital terverifikasi.</p>
      </div>

      <div class="cash-widget keluar">
        <span class="cash-widget-title">Pengeluaran Kas (Bulan Ini)</span>
        <strong class="cash-widget-amount"><?= rupiah($kasBulan['keluar_bulan']) ?></strong>
        <p class="cash-widget-desc">Total realisasi belanja operasional dan bantuan bulan berjalan.</p>
      </div>
    </div>

    <!-- Ringkasan Mutasi Kas Terkini -->
    <div class="card" style="margin-top:28px;">
      <div class="card-header">
        <div>
          <h3 style="font-size:1.25rem;">Transaksi Kas Terkini</h3>
          <span style="font-size:0.85rem; color:var(--text-muted);">Menampilkan 5 catatan kas terbaru.</span>
        </div>
        <a href="admin.php?tab=kas" class="btn btn-secondary" style="font-size:0.85rem;">Buka Buku Kas Lengkap &rarr;</a>
      </div>

      <?php 
        $transaksiTerbaru = $db->query("SELECT * FROM transaksi_kas ORDER BY tanggal DESC, id DESC LIMIT 5")->fetchAll();
      ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Uraian Transaksi</th>
              <th>Jenis</th>
              <th style="text-align:right;">Nominal (Rp)</th>
              <th>Pencatat</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transaksiTerbaru as $t): ?>
              <tr>
                <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                <td><strong style="color:var(--primary);"><?= e($t['kategori']) ?></strong></td>
                <td><?= e($t['judul']) ?></td>
                <td>
                  <span class="badge-tag <?= $t['jenis']==='masuk'?'badge-masuk':'badge-keluar' ?>">
                    <?= $t['jenis']==='masuk' ? 'Uang Masuk' : 'Uang Keluar' ?>
                  </span>
                </td>
                <td style="text-align:right; font-weight:700; color:<?= $t['jenis']==='masuk'?'var(--success)':'var(--danger)' ?>;">
                  <?= $t['jenis']==='masuk'?'+':'-' ?> <?= rupiah($t['nominal']) ?>
                </td>
                <td style="color:var(--text-muted); font-size:0.85rem;"><?= e($t['dibuat_oleh']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  <!-- ====================================================================
       TAB 2: BUKU KAS MASJID (MANAJEMEN KEUANGAN)
       ==================================================================== -->
  <?php elseif ($tab === 'kas'): 
    $filterPeriode = $_GET['periode'] ?? 'bulanan';
    $jenisFilterKas = $_GET['filter_jenis'] ?? 'semua';

    $qKas = "SELECT * FROM transaksi_kas WHERE 1=1";
    $pKas = [];

    if ($jenisFilterKas === 'masuk' || $jenisFilterKas === 'keluar') {
        $qKas .= " AND jenis = ?";
        $pKas[] = $jenisFilterKas;
    }

    if ($filterPeriode === 'harian') {
        $qKas .= " AND tanggal = CURDATE()";
    } elseif ($filterPeriode === 'mingguan') {
        $qKas .= " AND tanggal >= CURDATE() - INTERVAL 7 DAY";
    } elseif ($filterPeriode === 'bulanan') {
        $qKas .= " AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
    }

    $qKas .= " ORDER BY tanggal DESC, id DESC";
    $stmtKas = $db->prepare($qKas);
    $stmtKas->execute($pKas);
    $listKas = $stmtKas->fetchAll();

    $aksiForm = $_GET['aksi'] ?? 'masuk';
  ?>

    <div class="page-header-row no-print">
      <div>
        <h2 style="font-size:1.8rem; margin:0 0 4px;">Buku Kas Masjid</h2>
        <p class="page-lead">Pencatatan kas masuk dan kas keluar dengan istilah sederhana tanpa pembukuan rumit.</p>
      </div>
      <div style="display:flex; gap:10px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak / Unduh Laporan</button>
        <a href="admin.php?tab=kas&sub=mading" class="btn btn-primary">Publikasi Lembar Mading & QR</a>
      </div>
    </div>

    <!-- TAMPILAN LEMBAR MADING DENGAN QR CODE (JIKA DIPILIH) -->
    <?php if ($sub === 'mading'): ?>
      <div class="card" style="border:2px solid var(--primary); padding:32px; background:#fff;">
        <div style="text-align:center; border-bottom:2px solid var(--border); padding-bottom:18px; margin-bottom:24px;">
          <h2 style="margin:0; font-size:1.8rem;"><?= e($namaMasjid) ?></h2>
          <p style="margin:4px 0 0; color:var(--text-muted);"><?= e($alamatMasjid) ?> · Telp: <?= e($teleponMasjid) ?></p>
          <h3 style="margin:16px 0 4px; font-size:1.35rem; color:var(--primary);">LEMBAR PUBLIKASI MADING MASJID</h3>
          <span style="font-size:0.9rem; color:var(--text-muted);">Periode: Bulan <?= date('F Y') ?></span>
        </div>

        <div class="cash-summary-grid" style="margin-bottom:24px;">
          <div class="cash-widget saldo">
            <span class="cash-widget-title">Sisa Saldo Kas Terbuka</span>
            <strong class="cash-widget-amount"><?= rupiah($kas['saldo']) ?></strong>
          </div>
          <div class="cash-widget masuk">
            <span class="cash-widget-title">Total Pemasukan Bulan Ini</span>
            <strong class="cash-widget-amount"><?= rupiah($kasBulan['masuk_bulan']) ?></strong>
          </div>
          <div class="cash-widget keluar">
            <span class="cash-widget-title">Total Pengeluaran Bulan Ini</span>
            <strong class="cash-widget-amount"><?= rupiah($kasBulan['keluar_bulan']) ?></strong>
          </div>
        </div>

        <!-- QR Code Scan Publik -->
        <div style="display:grid; grid-template-columns:1fr 200px; gap:24px; align-items:center; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); padding:20px;">
          <div>
            <h4 style="margin:0 0 6px; font-size:1.1rem; color:var(--primary); font-family:inherit;">Pindai QR Code untuk Cek Laporan Real-Time</h4>
            <p style="margin:0; font-size:0.9rem; color:var(--text-muted); line-height:1.6;">
              Jamaah dapat memindai QR Code di samping menggunakan kamera HP untuk melihat rincian setiap nota pengeluaran dan riwayat uang masuk secara lengkap di portal web MOSQERA.
            </p>
          </div>
          <div style="text-align:center;">
            <div style="width:140px; height:140px; margin:0 auto; background:#fff; border:2px dashed var(--primary); display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px;">
              <strong style="color:var(--primary); font-size:0.9rem;">QR CODE</strong>
              <small style="color:var(--text-muted); font-size:0.75rem;">MOSQERA KAS</small>
            </div>
            <small style="display:block; margin-top:6px; font-size:0.78rem; color:var(--text-muted);">Portal Transparansi</small>
          </div>
        </div>

        <div style="text-align:center; margin-top:24px;" class="no-print">
          <button onclick="window.print()" class="btn btn-primary" style="padding:12px 28px;">Cetak Lembar Mading Ini</button>
          <a href="admin.php?tab=kas" class="btn btn-secondary">Kembali ke Buku Kas</a>
        </div>
      </div>
    <?php endif; ?>

    <!-- FORMULIR INPUT UANG MASUK & UANG KELUAR -->
    <div id="form-transaksi" class="card no-print" style="margin-top:20px;">
      <div style="display:flex; gap:12px; margin-bottom:20px; border-bottom:1px solid var(--border-light); padding-bottom:14px;">
        <a href="admin.php?tab=kas&aksi=masuk#form-transaksi" class="btn <?= $aksiForm==='masuk'?'btn-primary':'btn-secondary' ?>" style="padding:10px 20px;">
          + Catat Uang Masuk
        </a>
        <a href="admin.php?tab=kas&aksi=keluar#form-transaksi" class="btn <?= $aksiForm==='keluar'?'btn-primary':'btn-secondary' ?>" style="padding:10px 20px;">
          - Catat Uang Keluar
        </a>
      </div>

      <?php if ($aksiForm === 'masuk'): ?>
        <!-- FORM CATAT UANG MASUK -->
        <h3 style="font-size:1.3rem; margin-bottom:16px; color:var(--success);">Catat Uang Masuk Baru</h3>
        <form method="post">
          <input type="hidden" name="action" value="tambah_uang_masuk">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Tanggal Masuk</label>
              <input type="date" name="tanggal" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Nominal Uang Masuk (Rp)</label>
              <input type="number" name="nominal" class="form-input" placeholder="Contoh: 1500000" min="1000" step="500" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Kategori Pemasukan</label>
              <select name="kategori" class="form-select" required>
                <option value="Tromol Jumat">Tromol Jumat (Kotak Amal Sholat Jumat)</option>
                <option value="Donasi Transfer">Donasi Transfer Bank / QRIS</option>
                <option value="ZISWAF">ZISWAF (Zakat, Infaq, Sedekah, Wakaf)</option>
                <option value="Infaq Tromol Harian">Infaq Kotak Amal Harian</option>
                <option value="Lainnya">Lain-lain</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Judul / Sumber Pemasukan</label>
              <input type="text" name="judul" class="form-input" placeholder="Contoh: Tromol Sholat Jumat Pekan ke-3" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Keterangan Tambahan (Opsional)</label>
            <input type="text" name="keterangan" class="form-input" placeholder="Catatan saksi perhitungan atau rincian donatur...">
          </div>

          <button type="submit" class="btn btn-primary" style="padding:14px 28px; font-size:1.05rem; background:#198754; border-color:#198754;">
            Simpan Uang Masuk
          </button>
        </form>

      <?php else: ?>
        <!-- FORM CATAT UANG KELUAR -->
        <h3 style="font-size:1.3rem; margin-bottom:16px; color:var(--danger);">Catat Uang Keluar Baru</h3>
        <form method="post">
          <input type="hidden" name="action" value="catat_uang_keluar">

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Tanggal Pengeluaran</label>
              <input type="date" name="tanggal" class="form-input" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Nominal Uang Keluar (Rp)</label>
              <input type="number" name="nominal" class="form-input" placeholder="Contoh: 350000" min="1000" step="500" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Kategori Pengeluaran</label>
              <select name="kategori" class="form-select" required>
                <option value="Operasional Masjid">Operasional Masjid (Listrik, Air, Kebersihan)</option>
                <option value="Bantuan Sosial">Bantuan Sosial & Santunan Kaum Dhuafa</option>
                <option value="Perawatan & Renovasi">Perawatan Fasilitas & Perlengkapan</option>
                <option value="Honor / Transport Ustaz">Honor / Transport Pemateri & Kajian</option>
                <option value="Lainnya">Pengeluaran Lain-lain</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Uraian Keperluan Belanja</label>
              <input type="text" name="judul" class="form-input" placeholder="Contoh: Pembayaran Token Listrik PLN Masjid" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Rincian Keterangan & Nomor Bukti Nota</label>
            <textarea name="keterangan" class="form-textarea" rows="2" placeholder="Nomor nota bukti belanja fisik yang disimpan di map bendahara..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="padding:14px 28px; font-size:1.05rem; background:#b02a37; border-color:#b02a37;">
            Simpan Uang Keluar
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- REKAPITULASI LAPORAN & FILTER HARIAN / MINGGUAN / BULANAN -->
    <div class="card" style="margin-top:24px;">
      <div class="card-header">
        <div>
          <h3 style="font-size:1.25rem;">Rekapitulasi Catatan Buku Kas</h3>
          <span style="font-size:0.85rem; color:var(--text-muted);">Laporan keuangan lengkap dengan filter periode.</span>
        </div>

        <div class="filter-tabs no-print">
          <a href="admin.php?tab=kas&periode=harian&filter_jenis=<?= urlencode($jenisFilterKas) ?>" class="filter-tab <?= $filterPeriode==='harian'?'active':'' ?>">Harian (Hari ini)</a>
          <a href="admin.php?tab=kas&periode=mingguan&filter_jenis=<?= urlencode($jenisFilterKas) ?>" class="filter-tab <?= $filterPeriode==='mingguan'?'active':'' ?>">Mingguan (7 Hari)</a>
          <a href="admin.php?tab=kas&periode=bulanan&filter_jenis=<?= urlencode($jenisFilterKas) ?>" class="filter-tab <?= $filterPeriode==='bulanan'?'active':'' ?>">Bulanan (Bulan Ini)</a>
          <a href="admin.php?tab=kas&periode=semua&filter_jenis=<?= urlencode($jenisFilterKas) ?>" class="filter-tab <?= $filterPeriode==='semua'?'active':'' ?>">Semua</a>
        </div>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Uraian / Keterangan</th>
              <th>Jenis</th>
              <th style="text-align:right;">Nominal</th>
              <th>Pencatat</th>
              <th class="no-print">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$listKas): ?>
              <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">Belum ada data pada periode ini.</td></tr>
            <?php else: ?>
              <?php foreach ($listKas as $row): ?>
                <tr>
                  <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                  <td><strong><?= e($row['kategori']) ?></strong></td>
                  <td>
                    <?= e($row['judul']) ?>
                    <?php if ($row['keterangan']): ?>
                      <div style="font-size:0.82rem; color:var(--text-muted);"><?= e($row['keterangan']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge-tag <?= $row['jenis']==='masuk'?'badge-masuk':'badge-keluar' ?>">
                      <?= $row['jenis']==='masuk' ? 'Uang Masuk' : 'Uang Keluar' ?>
                    </span>
                  </td>
                  <td style="text-align:right; font-weight:700; color:<?= $row['jenis']==='masuk'?'var(--success)':'var(--danger)' ?>;">
                    <?= $row['jenis']==='masuk'?'+':'-' ?> <?= rupiah($row['nominal']) ?>
                  </td>
                  <td style="font-size:0.82rem; color:var(--text-muted);"><?= e($row['dibuat_oleh']) ?></td>
                  <td class="no-print">
                    <form method="post" onsubmit="return confirm('Hapus catatan transaksi ini?');" style="display:inline;">
                      <input type="hidden" name="action" value="hapus_transaksi">
                      <input type="hidden" name="id" value="<?= $row['id'] ?>">
                      <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.85rem; font-weight:600; text-decoration:underline;">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <!-- ====================================================================
       TAB 3: MANAJEMEN ZISWAF & DONASI
       ==================================================================== -->
  <?php elseif ($tab === 'donasi'): 
    $listKamp = $db->query("SELECT * FROM kampanye_donasi ORDER BY id DESC")->fetchAll();
    $listDonatur = $db->query("SELECT d.*, k.judul as nama_kampanye FROM donasi_masuk d LEFT JOIN kampanye_donasi k ON d.kampanye_id=k.id ORDER BY d.id DESC")->fetchAll();
  ?>

    <div class="page-header-row">
      <div>
        <h2 style="font-size:1.8rem; margin:0 0 4px;">Manajemen ZISWAF & Donasi</h2>
        <p class="page-lead">Kelola program kampanye donasi kebaikan, daftar donatur, dan verifikasi dana masuk.</p>
      </div>
    </div>

    <!-- BUAT KAMPANYE BARU (MICRO-TARGETING) -->
    <div class="card">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Buat Kampanye Kebaikan Baru</h3>
      <form method="post">
        <input type="hidden" name="action" value="buat_kampanye">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nama Proyek / Kampanye</label>
            <input type="text" name="judul" class="form-input" placeholder="Contoh: Pengadaan Karpet Shaf Utama" required>
          </div>

          <div class="form-group">
            <label class="form-label">Kategori Program</label>
            <select name="kategori" class="form-select" required>
              <option value="Fasilitas Masjid">Fasilitas Masjid</option>
              <option value="Pendidikan & Santri">Pendidikan & Santri</option>
              <option value="Infrastruktur & Renovasi">Infrastruktur & Renovasi</option>
              <option value="Sosial & Kaum Dhuafa">Sosial & Kaum Dhuafa</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Target Dana (Rp)</label>
            <input type="number" name="target_dana" class="form-input" placeholder="Contoh: 15000000" min="100000" step="50000" required>
          </div>

          <div class="form-group">
            <label class="form-label">Batas Waktu Pengumpulan (Opsional)</label>
            <input type="date" name="batas_waktu" class="form-input">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Deskripsi Rencana & Alokasi Anggaran</label>
          <textarea name="deskripsi" class="form-textarea" rows="2" placeholder="Jelaskan kebutuhan pengadaan, estimasi biaya, dan urgensi bagi kenyamanan jamaah..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Terbitkan Kampanye Baru</button>
      </form>
    </div>

    <!-- DAFTAR PROGRAM KAMPANYE -->
    <div class="card" style="margin-top:24px;">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Daftar Kampanye Kebaikan Berjalan</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama Program</th>
              <th>Kategori</th>
              <th>Target Dana</th>
              <th>Terkumpul</th>
              <th>Progress</th>
              <th>Batas Waktu</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listKamp as $kp): 
              $p = $kp['target_dana'] > 0 ? round(($kp['terkumpul'] / $kp['target_dana']) * 100) : 0;
            ?>
              <tr>
                <td><strong><?= e($kp['judul']) ?></strong></td>
                <td><?= e($kp['kategori']) ?></td>
                <td><?= rupiah($kp['target_dana']) ?></td>
                <td style="font-weight:700; color:var(--success);"><?= rupiah($kp['terkumpul']) ?></td>
                <td style="width:140px;">
                  <div style="font-size:0.8rem; font-weight:700; margin-bottom:3px;"><?= $p ?>%</div>
                  <div class="progress-bar-track" style="margin:0;">
                    <div class="progress-bar-fill" style="width:<?= $p ?>%;"></div>
                  </div>
                </td>
                <td style="font-size:0.85rem; color:var(--text-muted);"><?= $kp['batas_waktu'] ? date('d/m/Y', strtotime($kp['batas_waktu'])) : 'Tanpa batas' ?></td>
                <td><span class="badge-tag <?= $kp['status']==='Aktif'?'badge-masuk':'badge-keluar' ?>"><?= e($kp['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- VERIFIKASI DANA MASUK & DAFTAR DONATUR -->
    <div class="card" style="margin-top:24px;">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Verifikasi Dana Masuk & Daftar Donatur</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Nama Donatur</th>
              <th>Kampanye Tujuan</th>
              <th>Metode</th>
              <th style="text-align:right;">Nominal</th>
              <th>Doa / Catatan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$listDonatur): ?>
              <tr><td colspan="8" style="text-align:center; padding:20px;">Belum ada catatan donatur masuk.</td></tr>
            <?php else: ?>
              <?php foreach ($listDonatur as $d): ?>
                <tr>
                  <td><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                  <td><strong><?= e($d['nama_donatur']) ?></strong></td>
                  <td><span style="font-size:0.85rem; color:var(--primary);"><?= e($d['nama_kampanye'] ?? 'Infaq Umum') ?></span></td>
                  <td><?= e($d['metode']) ?></td>
                  <td style="text-align:right; font-weight:700; color:var(--success);"><?= rupiah($d['nominal']) ?></td>
                  <td style="font-size:0.82rem; color:var(--text-muted); max-width:200px;"><?= e($d['catatan_doa'] ?: '-') ?></td>
                  <td>
                    <span class="badge-tag <?= $d['status']==='Terverifikasi'?'badge-masuk':'badge-keluar' ?>"><?= e($d['status']) ?></span>
                  </td>
                  <td>
                    <?php if ($d['status'] === 'Menunggu'): ?>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="verifikasi_donasi">
                        <input type="hidden" name="donasi_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="status" value="Terverifikasi">
                        <button type="submit" class="btn btn-primary" style="padding:4px 8px; font-size:0.78rem;">Verifikasi</button>
                      </form>
                    <?php else: ?>
                      <span style="font-size:0.82rem; color:var(--text-muted);">Selesai</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <!-- ====================================================================
       TAB 4: MANAJEMEN JADWAL & KAJIAN
       ==================================================================== -->
  <?php elseif ($tab === 'jadwal'): 
    $kegiatanAll = $db->query("SELECT * FROM jadwal_kegiatan ORDER BY tanggal ASC")->fetchAll();
  ?>

    <div class="page-header-row">
      <div>
        <h2 style="font-size:1.8rem; margin:0 0 4px;">Manajemen Jadwal & Kajian</h2>
        <p class="page-lead">Kelola data penceramah sholat Jumat, kajian mingguan, dan kalender kegiatan masjid.</p>
      </div>
    </div>

    <!-- FORM INPUT JADWAL KHATIB & KAJIAN -->
    <div class="card">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Input Jadwal Shalat Jumat / Kajian</h3>
      <form method="post">
        <input type="hidden" name="action" value="tambah_kegiatan">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Jenis Kegiatan</label>
            <select name="jenis" class="form-select" required>
              <option value="Khatib Jumat">Khatib Sholat Jumat</option>
              <option value="Kajian Mingguan">Kajian Rutin Mingguan</option>
              <option value="Kajian Tematik">Kajian Tematik / Tabligh Akbar</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Nama Ustaz / Khatib / Penceramah</label>
            <input type="text" name="penceramah" class="form-input" placeholder="Contoh: K.H. Dr. Ahmad Ridwan, Lc., M.A." required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tema Khutbah / Judul Kajian</label>
            <input type="text" name="judul" class="form-input" placeholder="Contoh: Menjaga Integritas & Amanah Umat" required>
          </div>

          <div class="form-group">
            <label class="form-label">Tanggal Pelaksanaan</label>
            <input type="date" name="tanggal" class="form-input" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Waktu Pelaksanaan</label>
            <input type="text" name="waktu" class="form-input" placeholder="Contoh: 11:45 WIB - Selesai atau Ba'da Maghrib" required>
          </div>

          <div class="form-group">
            <label class="form-label">Lokasi Ruang</label>
            <input type="text" name="lokasi" class="form-input" value="Ruang Utama Masjid">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Catatan Tambahan (Muadzin / Keterangan Jamaah)</label>
          <input type="text" name="keterangan" class="form-input" placeholder="Contoh: Muadzin: Ust. Fathurrahman | Disertai tanya jawab">
        </div>

        <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Simpan Jadwal Kegiatan</button>
      </form>
    </div>

    <!-- DAFTAR KALENDER JADWAL -->
    <div class="card" style="margin-top:24px;">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Kalender & Daftar Kegiatan Masjid</h3>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>Jenis</th>
              <th>Penceramah / Ustaz</th>
              <th>Tema / Judul</th>
              <th>Waktu & Tempat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$kegiatanAll): ?>
              <tr><td colspan="6" style="text-align:center; padding:20px;">Belum ada jadwal kegiatan terdaftar.</td></tr>
            <?php else: ?>
              <?php foreach ($kegiatanAll as $k): ?>
                <tr>
                  <td style="white-space:nowrap; font-weight:600;"><?= date('d/m/Y', strtotime($k['tanggal'])) ?></td>
                  <td><span class="badge-tag" style="background:var(--primary-subtle); color:var(--primary);"><?= e($k['jenis']) ?></span></td>
                  <td><strong style="color:var(--primary);"><?= e($k['penceramah']) ?></strong></td>
                  <td><?= e($k['judul']) ?></td>
                  <td><?= e($k['waktu']) ?> (<?= e($k['lokasi']) ?>)</td>
                  <td>
                    <form method="post" onsubmit="return confirm('Hapus jadwal ini?');" style="display:inline;">
                      <input type="hidden" name="action" value="hapus_kegiatan">
                      <input type="hidden" name="id" value="<?= $k['id'] ?>">
                      <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.85rem; text-decoration:underline;">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  <!-- ====================================================================
       TAB 5: PENGATURAN SISTEM
       ==================================================================== -->
  <?php elseif ($tab === 'pengaturan'): 
    $userList = $db->query("SELECT id, nama, email, role, created_at FROM users ORDER BY id ASC")->fetchAll();
  ?>

    <div class="page-header-row">
      <div>
        <h2 style="font-size:1.8rem; margin:0 0 4px;">Pengaturan Sistem</h2>
        <p class="page-lead">Kelola profil masjid, rekening & tampilan QRIS, serta akun pengurus masjid.</p>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:24px;" class="form-row">
      
      <!-- FORM PROFIL & QRIS MASJID -->
      <div class="card">
        <h3 style="font-size:1.25rem; margin-bottom:16px;">Profil & Kontak Masjid</h3>
        <form method="post">
          <input type="hidden" name="action" value="simpan_pengaturan">

          <div class="form-group">
            <label class="form-label">Nama Resmi Masjid</label>
            <input type="text" name="nama_masjid" class="form-input" value="<?= e($namaMasjid) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Alamat Lengkap Masjid</label>
            <textarea name="alamat" class="form-textarea" rows="2" required><?= e($alamatMasjid) ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">No. Telepon / WhatsApp</label>
              <input type="text" name="telepon" class="form-input" value="<?= e($teleponMasjid) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Alamat Email Sekretariat</label>
              <input type="email" name="email" class="form-input" value="<?= e($emailMasjid) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Informasi Rekening Bank Resmi (Untuk Donasi Transfer)</label>
            <textarea name="bank_info" class="form-textarea" rows="2" required><?= e($bankInfo) ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Label & Identitas QRIS Utama</label>
            <input type="text" name="qris_label" class="form-input" value="<?= e($qrisLabel) ?>" required>
          </div>

          <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Simpan Perubahan Profil</button>
        </form>
      </div>

      <!-- KELOLA AKUN PENGGUNA (ROLE: PENGURUS) -->
      <div class="card">
        <h3 style="font-size:1.25rem; margin-bottom:16px;">Akun Pengurus Masjid</h3>
        <p style="font-size:0.88rem; color:var(--text-muted); margin-bottom:14px;">
          Daftar pengurus yang berwenang mengakses buku kas dan manajemen sistem MOSQERA:
        </p>

        <div style="display:flex; flex-direction:column; gap:10px;">
          <?php foreach ($userList as $u): ?>
            <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px;">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <strong style="color:var(--primary); font-size:0.95rem;"><?= e($u['nama']) ?></strong>
                <span class="badge-tag" style="background:var(--primary-subtle); color:var(--primary);"><?= e($u['role']) ?></span>
              </div>
              <div style="font-size:0.82rem; color:var(--text-muted); margin-top:4px;">
                Email: <?= e($u['email']) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; padding-top:14px; border-top:1px dashed var(--border);">
          <a href="register.php" target="_blank" class="btn btn-secondary" style="width:100%; font-size:0.88rem;">
            + Tambah Akun Pengurus Baru
          </a>
        </div>
      </div>

    </div>

  <?php endif; ?>

</main>

<footer class="site-footer no-print">
  <div class="footer-inner">
    <div>
      <strong style="color:#ffffff; font-size:1.05rem;"><?= e($namaMasjid) ?> · Portal Pengurus</strong>
      <p style="margin:2px 0 0; color:rgba(255,255,255,0.85);"><?= e($alamatMasjid) ?></p>
    </div>
    <div style="text-align:right;">
      <span style="color:rgba(255,255,255,0.75);">MOSQERA - Tata Kelola Kas Masjid Terbuka & Ramah Lansia</span>
    </div>
  </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
