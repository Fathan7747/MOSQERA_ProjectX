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

    $uploadRes = simpan_bukti_transaksi('bukti_file', 'kas_masuk_');
    if (!$uploadRes['success']) {
        $err = $uploadRes['error'];
    } elseif (empty($judul) || $nominal <= 0) {
        $err = 'Mohon isi uraian pemasukan dan nominal yang valid.';
    } else {
        $buktiFile = $uploadRes['filename'];
        $ins = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, bukti_file, dibuat_oleh) VALUES (?, 'masuk', ?, ?, ?, ?, ?, ?)");
        $ins->execute([$tanggal, $kategori, $judul, $nominal, $keterangan, $buktiFile ?: null, $pencatat]);
        $msg = 'Uang masuk sebesar ' . rupiah($nominal) . ' berhasil dicatat' . ($buktiFile ? ' beserta bukti transaksi.' : '.');
    }
}

// Catat Uang Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'catat_uang_keluar') {
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $kategori = trim($_POST['kategori'] ?? 'Operasional Masjid');
    $judul = trim($_POST['judul'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $pencatat = $user['nama'];

    $uploadRes = simpan_bukti_transaksi('bukti_file', 'kas_keluar_');
    if (!$uploadRes['success']) {
        $err = $uploadRes['error'];
    } elseif (empty($judul) || $nominal <= 0) {
        $err = 'Mohon isi uraian pengeluaran dan nominal yang valid.';
    } else {
        $buktiFile = $uploadRes['filename'];
        $ins = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, bukti_file, dibuat_oleh) VALUES (?, 'keluar', ?, ?, ?, ?, ?, ?)");
        $ins->execute([$tanggal, $kategori, $judul, $nominal, $keterangan, $buktiFile ?: null, $pencatat]);
        $msg = 'Pengeluaran kas sebesar ' . rupiah($nominal) . ' berhasil dicatat' . ($buktiFile ? ' beserta lampiran bukti nota.' : '.');
    }
}

// Hapus Transaksi Kas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_transaksi') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        // Ambil data bukti sebelum transaksi dihapus
        $rowDel = $db->prepare("SELECT bukti_file FROM transaksi_kas WHERE id=?");
        $rowDel->execute([$id]);
        $rowData = $rowDel->fetch();
        $del = $db->prepare("DELETE FROM transaksi_kas WHERE id=?");
        $del->execute([$id]);
        // Hapus file fisik jika ada
        if (!empty($rowData['bukti_file'])) {
            hapus_bukti_transaksi($rowData['bukti_file']);
        }
        $msg = 'Transaksi kas berhasil dihapus dari pembukuan.';
    }
}

// Catat Penerimaan Donasi Langsung Pengurus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'catat_donasi_admin') {
    $namaDonatur = trim($_POST['nama_donatur'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $kampanyeId = intval($_POST['kampanye_id'] ?? 0);
    $metode = trim($_POST['metode'] ?? 'Tunai');
    $catatanDoa = trim($_POST['catatan_doa'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $catatKeKas = isset($_POST['catat_ke_kas']) ? 1 : 0;

    $uploadRes = simpan_bukti_transaksi('bukti_file', 'donasi_admin_');
    if (!$uploadRes['success']) {
        $err = $uploadRes['error'];
    } elseif (empty($namaDonatur) || $nominal <= 0) {
        $err = 'Mohon lengkapi nama donatur dan nominal donasi dengan benar.';
    } else {
        $buktiFile = $uploadRes['filename'];
        $stmtDonasi = $db->prepare("INSERT INTO donasi_masuk (kampanye_id, nama_donatur, nominal, metode, catatan_doa, bukti_file, status, tanggal) VALUES (?, ?, ?, ?, ?, ?, 'Terverifikasi', ?)");
        $stmtDonasi->execute([$kampanyeId ?: null, $namaDonatur, $nominal, $metode, $catatanDoa, $buktiFile ?: null, $tanggal]);

        if ($kampanyeId > 0) {
            $upd = $db->prepare("UPDATE kampanye_donasi SET terkumpul = terkumpul + ? WHERE id=?");
            $upd->execute([$nominal, $kampanyeId]);
        }

        if ($catatKeKas) {
            $judulKas = 'Penerimaan Donasi: ' . ($kampanyeId ? 'Kampanye #' . $kampanyeId : 'Infaq Kas Umum') . ' (' . $namaDonatur . ')';
            $stmtKas = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, bukti_file, dibuat_oleh) VALUES (?, 'masuk', 'Donasi Transfer', ?, ?, ?, ?, ?)");
            $stmtKas->execute([$tanggal, $judulKas, $nominal, $catatanDoa ?: 'Diterima langsung oleh ' . $user['nama'], $buktiFile ?: null, $user['nama']]);
        }

        $msg = 'Penerimaan donasi dari ' . $namaDonatur . ' sebesar ' . rupiah($nominal) . ' berhasil dicatat' . ($buktiFile ? ' dengan bukti pembayaran.' : '.');
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
    if ($donasiId > 0 && in_array($statusDonasi, ['Terverifikasi', 'Menunggu', 'Ditolak'], true)) {
        $upd = $db->prepare("UPDATE donasi_masuk SET status=? WHERE id=?");
        $upd->execute([$statusDonasi, $donasiId]);
        $msg = 'Status verifikasi donasi diperbarui menjadi ' . e($statusDonasi) . '.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status_kampanye') {
    $kId = intval($_POST['id'] ?? 0);
    $newStatus = ($_POST['status'] ?? '') === 'Selesai' ? 'Selesai' : 'Aktif';
    if ($kId > 0) {
        $updK = $db->prepare("UPDATE kampanye_donasi SET status=? WHERE id=?");
        $updK->execute([$newStatus, $kId]);
        $msg = 'Status program kampanye berhasil diubah menjadi ' . $newStatus . '.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_kampanye') {
    $kId = intval($_POST['id'] ?? 0);
    if ($kId > 0) {
        $delK = $db->prepare("DELETE FROM kampanye_donasi WHERE id=?");
        $delK->execute([$kId]);
        $msg = 'Program kampanye donasi berhasil dihapus.';
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
    $keys = ['nama_masjid', 'alamat', 'telepon', 'email', 'bank_info', 'qris_label', 'kota_jadwal', 'zona_waktu'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $val = trim($_POST[$k]);
            $stmtSet = $db->prepare("INSERT INTO pengaturan_masjid (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
            $stmtSet->execute([$k, $val]);
        }
    }

    // Tangani Unggah Gambar QRIS Resmi Masjid
    if (isset($_FILES['qris_file']) && $_FILES['qris_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $qrisRes = simpan_gambar_qris('qris_file');
        if (!$qrisRes['success']) {
            $err = $qrisRes['error'];
        } else {
            $oldQris = pengaturan($db, 'qris_image', '');
            if ($oldQris) {
                hapus_gambar_qris($oldQris);
            }
            simpan_pengaturan($db, 'qris_image', $qrisRes['filename']);
        }
    }

    // Opsi Hapus Gambar QRIS
    if (isset($_POST['hapus_qris_image']) && $_POST['hapus_qris_image'] == '1') {
        $oldQris = pengaturan($db, 'qris_image', '');
        if ($oldQris) {
            hapus_gambar_qris($oldQris);
            simpan_pengaturan($db, 'qris_image', '');
        }
    }

    if (!$err) {
        // Otomatis sinkronkan jadwal sholat dengan kota yang dipilih
        if (function_exists('sinkron_jadwal_sholat')) {
            sinkron_jadwal_sholat($db, true);
        }
        $msg = 'Pengaturan profil masjid dan jadwal sholat berhasil diperbarui.';
    }
}

// Handler Sinkronisasi Manual Jadwal Sholat dari API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sinkron_jadwal_api') {
    if (function_exists('sinkron_jadwal_sholat')) {
        $resSync = sinkron_jadwal_sholat($db, true);
        if ($resSync['success']) {
            $msg = $resSync['message'];
        } else {
            $err = $resSync['message'];
        }
    }
}

// ==========================================
// PENGAMBILAN DATA
// ==========================================
$kas = ringkasan_kas($db);
$kasBulan = ringkasan_kas_bulan($db);

$statDonasiTotal = (float)$db->query("SELECT COALESCE(SUM(nominal),0) FROM donasi_masuk WHERE status='Terverifikasi'")->fetchColumn();

$namaMasjid = pengaturan($db, 'nama_masjid', "MASJID BAITURRAHIM");
$alamatMasjid = pengaturan($db, 'alamat', "Jl. Jend. Sudirman No.70, Rembiga, Kec. Selaparang, Kota Mataram");
$teleponMasjid = pengaturan($db, 'telepon', "0812-3456-7890");
$emailMasjid = pengaturan($db, 'email', "sekretariat@mosqera.id");
$bankInfo = pengaturan($db, 'bank_info', "Bank Syariah Indonesia (BSI) No. Rekening: 7123-456-789 a.n Kas DKM MASJID BAITURRAHIM");
$qrisLabel = pengaturan($db, 'qris_label', "QRIS Standar Pembayaran Nasional (NMID: ID1029384756)");
$kotaJadwal = pengaturan($db, 'kota_jadwal', "Kota Mataram");
$zonaWaktu = zona_waktu_label($db);
$qrisImage = pengaturan($db, 'qris_image', "");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal Pengurus · MOSQERA - <?= e($namaMasjid) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time() ?>">
</head>
<body style="background:#f4f7f6;">

<!-- Header Portal Pengurus (Senior Friendly: Jelas & Kontras) -->
<header class="admin-navbar no-print">
  <div class="admin-nav-container">
    <div class="admin-brand-box">
      <a class="admin-brand" href="admin.php">MOSQERA</a>
      <span class="admin-brand-divider"></span>
      <span class="admin-brand-masjid"><?= e($namaMasjid) ?></span>
    </div>

    <div class="admin-user-info">
      <span style="opacity:0.92;">Selamat Datang, <strong><?= e($user['nama']) ?></strong></span>
      <a href="index.php" style="color:#ffffff; opacity:0.85; font-size:0.85rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border:1px solid rgba(255,255,255,0.25); border-radius:var(--radius-sm); transition:all 0.15s ease;" title="Buka Halaman Depan Masjid">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
        <span>Lihat Web</span>
      </a>
      <a href="logout.php" class="admin-btn-logout">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Keluar</span>
      </a>
    </div>
  </div>
</header>

<!-- Tab Navigasi Admin Sesuai Sitemap -->
<nav class="admin-nav-tabs no-print">
  <div class="admin-nav-tabs-inner">
    <a href="admin.php?tab=dasbor" class="admin-tab-item <?= $tab==='dasbor'?'active':'' ?>">
      <svg class="admin-tab-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span>Dasbor Utama</span>
    </a>
    <a href="admin.php?tab=kas" class="admin-tab-item <?= $tab==='kas'?'active':'' ?>">
      <svg class="admin-tab-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg>
      <span>Buku Kas Masjid</span>
    </a>
    <a href="admin.php?tab=donasi" class="admin-tab-item <?= $tab==='donasi'?'active':'' ?>">
      <svg class="admin-tab-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
      <span>Manajemen ZISWAF & Donasi</span>
    </a>
    <a href="admin.php?tab=jadwal" class="admin-tab-item <?= $tab==='jadwal'?'active':'' ?>">
      <svg class="admin-tab-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
      <span>Manajemen Jadwal & Kajian</span>
    </a>
    <a href="admin.php?tab=pengaturan" class="admin-tab-item <?= $tab==='pengaturan'?'active':'' ?>">
      <svg class="admin-tab-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
      <span>Pengaturan Sistem</span>
    </a>
  </div>
</nav>

<!-- Header Print Khusus Laporan Mading -->
<div class="print-header">
  <h2 style="margin:0; font-size:1.6rem;"><?= e($namaMasjid) ?></h2>
  <p style="margin:4px 0 12px; font-size:0.9rem;"><?= e($alamatMasjid) ?></p>
  <h3 style="margin:0; font-size:1.2rem; text-decoration:underline;">PUBLIKASI MADING: LAPORAN KEUANGAN KAS MASJID</h3>
  <small>Tanggal Cetak: <?= e(tanggal_indo()) ?> · Disahkan oleh Pengurus DKM</small>
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
        <?php
          $transparansiUrl = url_base_mosqera() . '/transparansi.php';
          $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($transparansiUrl);
        ?>
        <div style="display:grid; grid-template-columns:1fr 200px; gap:24px; align-items:center; background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); padding:20px;">
          <div>
            <h4 style="margin:0 0 6px; font-size:1.1rem; color:var(--primary); font-family:inherit;">Pindai QR Code untuk Cek Laporan Real-Time</h4>
            <p style="margin:0; font-size:0.9rem; color:var(--text-muted); line-height:1.6;">
              Jamaah dapat memindai QR Code di samping menggunakan kamera HP untuk melihat rincian setiap nota pengeluaran dan riwayat uang masuk secara lengkap di portal web MOSQERA.
            </p>
            <div style="margin-top:6px; font-size:0.8rem; color:var(--primary); font-weight:600; word-break:break-all;">
              <?= e($transparansiUrl) ?>
            </div>
          </div>
          <div style="text-align:center;">
            <div style="width:140px; height:140px; margin:0 auto; background:#fff; border:1px solid var(--border); display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px; overflow:hidden; padding:4px;">
              <img src="<?= e($qrApiUrl) ?>" alt="QR Code Transparansi Kas" style="width:100%; height:100%; object-fit:contain;" onerror="this.parentElement.innerHTML='<strong style=\'color:var(--primary); font-size:0.85rem;\'>SCAN WEB</strong><small style=\'color:var(--text-muted); font-size:0.75rem;\'>transparansi.php</small>'">
            </div>
            <small style="display:block; margin-top:6px; font-size:0.78rem; color:var(--text-muted);">Portal Transparansi Kas</small>
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
        <form method="post" enctype="multipart/form-data" data-confirm="Simpan catatan uang masuk ini ke dalam buku kas masjid?" data-confirm-title="Simpan Uang Masuk" data-confirm-btn="Simpan" data-confirm-type="success">
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

          <!-- UPLOAD BUKTI TRANSAKSI UANG MASUK -->
          <?php
            $fieldLabel = 'Bukti Transaksi Pemasukan (Foto / Struk)';
            $fieldDesc = 'Unggah foto struk transfer, kuitansi infaq, atau tanda terima donatur.';
            $fieldName = 'bukti_file';
            $fieldId = 'buktiUploadMasuk';
            include __DIR__ . '/includes/bukti_upload_field.php';
          ?>

          <button type="submit" class="btn btn-primary" style="padding:14px 28px; font-size:1.05rem; background:#198754; border-color:#198754;">
            Simpan Uang Masuk
          </button>
        </form>

      <?php else: ?>
        <!-- FORM CATAT UANG KELUAR -->
        <h3 style="font-size:1.3rem; margin-bottom:16px; color:var(--danger);">Catat Uang Keluar Baru</h3>
        <form method="post" enctype="multipart/form-data" data-confirm="Catat pengeluaran kas ini? Pastikan bukti belanja fisik sudah diverifikasi." data-confirm-title="Catat Uang Keluar" data-confirm-btn="Simpan" data-confirm-type="danger">
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

          <!-- UPLOAD BUKTI TRANSAKSI UANG KELUAR -->
          <?php
            $fieldLabel = 'Unggah Bukti Nota / Kuitansi Belanja';
            $fieldDesc = 'Foto nota toko, kuitansi resmi, struk belanja kas, atau invoice operasional.';
            $fieldName = 'bukti_file';
            $fieldId = 'buktiUploadKeluar';
            include __DIR__ . '/includes/bukti_upload_field.php';
          ?>

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
              <th>Bukti</th>
              <th>Pencatat</th>
              <th class="no-print">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$listKas): ?>
              <tr><td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">Belum ada data pada periode ini.</td></tr>
            <?php else: ?>
              <?php foreach ($listKas as $row): 
                $buktiAda = !empty($row['bukti_file']) && file_bukti_tersedia($row['bukti_file']);
                $buktiUrl = $buktiAda ? url_bukti_transaksi($row['bukti_file']) : '';
                $isPdf = $buktiAda && is_pdf_bukti($row['bukti_file']);
                $dataJson = json_encode([
                  'id' => $row['id'],
                  'tanggal' => $row['tanggal'],
                  'tanggal_format' => date('d F Y', strtotime($row['tanggal'])),
                  'jenis' => $row['jenis'],
                  'kategori' => $row['kategori'],
                  'judul' => $row['judul'],
                  'nominal' => (float)$row['nominal'],
                  'keterangan' => $row['keterangan'] ?? '',
                  'dibuat_oleh' => $row['dibuat_oleh'] ?? 'Bendahara',
                  'created_at' => !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-',
                  'bukti_file' => $row['bukti_file'] ?? '',
                  'bukti_url' => $buktiUrl,
                  'detail_url' => 'detail_transaksi.php?id=' . $row['id']
                ]);
              ?>
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
                  <td>
                    <?php if ($buktiAda): ?>
                      <button type="button" class="btn-table-bukti js-btn-detail-transaksi" data-transaksi="<?= e($dataJson) ?>" title="Klik untuk melihat bukti transaksi">
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
                  <td style="font-size:0.82rem; color:var(--text-muted);"><?= e($row['dibuat_oleh']) ?></td>
                  <td class="no-print" style="white-space:nowrap;">
                    <button type="button" class="btn-table-detail js-btn-detail-transaksi" data-transaksi="<?= e($dataJson) ?>" title="Lihat rincian lengkap transaksi">
                      Detail
                    </button>
                    <form method="post" style="display:inline; margin-left:4px;" data-confirm="Apakah Anda yakin ingin menghapus catatan transaksi ini? Data dan file bukti yang dihapus tidak dapat dipulihkan." data-confirm-title="Hapus Catatan Kas" data-confirm-btn="Hapus" data-confirm-type="danger">
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
      <form method="post" data-confirm="Terbitkan program kampanye donasi baru ini ke publik?" data-confirm-title="Terbitkan Kampanye" data-confirm-btn="Simpan" data-confirm-type="primary">
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
              <th class="no-print">Aksi</th>
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
                <td class="no-print" style="white-space:nowrap;">
                  <form method="post" style="display:inline;" data-confirm="Ubah status kampanye ini menjadi <?= $kp['status']==='Aktif'?'Selesai':'Aktif' ?>?" data-confirm-title="Ubah Status Kampanye" data-confirm-btn="Ubah" data-confirm-type="primary">
                    <input type="hidden" name="action" value="toggle_status_kampanye">
                    <input type="hidden" name="id" value="<?= $kp['id'] ?>">
                    <input type="hidden" name="status" value="<?= $kp['status']==='Aktif'?'Selesai':'Aktif' ?>">
                    <button type="submit" style="background:none; border:none; color:var(--primary); cursor:pointer; font-size:0.82rem; font-weight:600; text-decoration:underline;">
                      <?= $kp['status']==='Aktif'?'Selesaikan':'Aktifkan' ?>
                    </button>
                  </form>
                  <form method="post" style="display:inline; margin-left:6px;" data-confirm="Hapus program kampanye ini? Donasi yang telah masuk tetap tercatat." data-confirm-title="Hapus Kampanye" data-confirm-btn="Hapus" data-confirm-type="danger">
                    <input type="hidden" name="action" value="hapus_kampanye">
                    <input type="hidden" name="id" value="<?= $kp['id'] ?>">
                    <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.82rem; font-weight:600; text-decoration:underline;">Hapus</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- FORM CATAT PENERIMAAN DONASI LANGSUNG -->
    <div class="card" style="margin-top:24px;">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Catat Penerimaan Donasi Langsung (Offline / Donatur Datang)</h3>
      <form method="post" enctype="multipart/form-data" data-confirm="Simpan catatan penerimaan donasi kebaikan ini?" data-confirm-title="Simpan Penerimaan Donasi" data-confirm-btn="Simpan" data-confirm-type="primary">
        <input type="hidden" name="action" value="catat_donasi_admin">

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tanggal Penerimaan</label>
            <input type="date" name="tanggal" class="form-input" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Nominal Donasi (Rp)</label>
            <input type="number" name="nominal" class="form-input" placeholder="Contoh: 500000" min="10000" step="5000" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nama Donatur / Keluarga</label>
            <input type="text" name="nama_donatur" class="form-input" placeholder="Contoh: Hamba Allah / Bpk. Sutrisno" required>
          </div>

          <div class="form-group">
            <label class="form-label">Alokasi Program Kampanye</label>
            <select name="kampanye_id" class="form-select">
              <option value="0">Infaq Kas Umum Masjid</option>
              <?php foreach ($listKamp as $kp): ?>
                <option value="<?= $kp['id'] ?>"><?= e($kp['judul']) ?> (<?= e($kp['kategori']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Metode Pembayaran</label>
            <select name="metode" class="form-select" required>
              <option value="Tunai">Tunai / Penyerahan Langsung</option>
              <option value="Transfer Bank">Transfer Rekening Bank BSI</option>
              <option value="QRIS">QRIS Standar Nasional</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Doa / Niat Kebaikan (Opsional)</label>
            <input type="text" name="catatan_doa" class="form-input" placeholder="Niat sedekah jariyah atau doa keluarga...">
          </div>
        </div>

        <!-- UPLOAD BUKTI DONASI -->
        <?php
          $fieldLabel = 'Unggah Bukti Transaksi Donasi (Kuitansi / Struk Transfer)';
          $fieldDesc = 'Foto kuitansi penyerahan tunai, bukti transfer bank, atau struk pembayaran donatur.';
          $fieldName = 'bukti_file';
          $fieldId = 'buktiUploadDonasiAdmin';
          include __DIR__ . '/includes/bukti_upload_field.php';
        ?>

        <div class="form-group">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.92rem;">
            <input type="checkbox" name="catat_ke_kas" value="1" checked>
            <span>Otomatis catat juga sebagai uang masuk ke Buku Kas Masjid</span>
          </label>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:12px 24px;">Simpan Catatan Donasi</button>
      </form>
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
              <th>Bukti Transfer</th>
              <th>Doa / Catatan</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$listDonatur): ?>
              <tr><td colspan="9" style="text-align:center; padding:20px;">Belum ada catatan donatur masuk.</td></tr>
            <?php else: ?>
              <?php foreach ($listDonatur as $d): 
                $buktiAda = !empty($d['bukti_file']) && file_bukti_tersedia($d['bukti_file']);
                $buktiUrl = $buktiAda ? url_bukti_transaksi($d['bukti_file']) : '';
                $isPdf = $buktiAda && is_pdf_bukti($d['bukti_file']);
                $donasiDataJson = json_encode([
                  'id' => $d['id'],
                  'tanggal' => $d['tanggal'],
                  'tanggal_format' => date('d F Y', strtotime($d['tanggal'])),
                  'jenis' => 'masuk',
                  'kategori' => 'Donasi / ' . ($d['nama_kampanye'] ?? 'Infaq Umum'),
                  'judul' => 'Donasi dari ' . $d['nama_donatur'] . ' (' . $d['metode'] . ')',
                  'nominal' => (float)$d['nominal'],
                  'keterangan' => $d['catatan_doa'] ?? '',
                  'dibuat_oleh' => 'Donatur (' . $d['nama_donatur'] . ')',
                  'created_at' => !empty($d['created_at']) ? date('d/m/Y H:i', strtotime($d['created_at'])) : '-',
                  'bukti_file' => $d['bukti_file'] ?? '',
                  'bukti_url' => $buktiUrl,
                  'detail_url' => '#'
                ]);
              ?>
                <tr>
                  <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                  <td><strong><?= e($d['nama_donatur']) ?></strong></td>
                  <td><span style="font-size:0.85rem; color:var(--primary);"><?= e($d['nama_kampanye'] ?? 'Infaq Umum') ?></span></td>
                  <td><?= e($d['metode']) ?></td>
                  <td style="text-align:right; font-weight:700; color:var(--success);"><?= rupiah($d['nominal']) ?></td>
                  <td>
                    <?php if ($buktiAda): ?>
                      <button type="button" class="btn-table-bukti js-btn-detail-transaksi" data-transaksi="<?= e($donasiDataJson) ?>" title="Lihat bukti transfer donatur">
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
                  <td style="font-size:0.82rem; color:var(--text-muted); max-width:200px;"><?= e($d['catatan_doa'] ?: '-') ?></td>
                  <td>
                    <?php 
                      $badgeClass = match($d['status']) {
                        'Terverifikasi' => 'badge-masuk',
                        'Ditolak' => 'badge-keluar',
                        default => 'badge-tag'
                      };
                      $badgeStyle = ($d['status'] === 'Menunggu') ? 'background:#fff3cd; color:#856404; border:1px solid #ffeeba;' : '';
                    ?>
                    <span class="badge-tag <?= $badgeClass ?>" style="<?= $badgeStyle ?>"><?= e($d['status']) ?></span>
                  </td>
                  <td style="white-space:nowrap;">
                    <?php if ($d['status'] === 'Menunggu'): ?>
                      <form method="post" style="display:inline;" data-confirm="Verifikasi penerimaan dana donasi sebesar <?= rupiah($d['nominal']) ?> ini?" data-confirm-title="Verifikasi Donasi" data-confirm-btn="Verifikasi" data-confirm-type="primary">
                        <input type="hidden" name="action" value="verifikasi_donasi">
                        <input type="hidden" name="donasi_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="status" value="Terverifikasi">
                        <button type="submit" class="btn btn-primary" style="padding:4px 8px; font-size:0.78rem;">Verifikasi</button>
                      </form>
                      <form method="post" style="display:inline; margin-left:4px;" data-confirm="Tolak donasi ini? Status akan ditandai sebagai Ditolak." data-confirm-title="Tolak Donasi" data-confirm-btn="Tolak" data-confirm-type="danger">
                        <input type="hidden" name="action" value="verifikasi_donasi">
                        <input type="hidden" name="donasi_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="status" value="Ditolak">
                        <button type="submit" style="background:none; border:none; color:var(--danger); cursor:pointer; font-size:0.78rem; text-decoration:underline;">Tolak</button>
                      </form>
                    <?php elseif ($d['status'] === 'Ditolak'): ?>
                      <form method="post" style="display:inline;" data-confirm="Pulihkan dan verifikasi donasi ini?" data-confirm-title="Verifikasi Donasi" data-confirm-btn="Verifikasi" data-confirm-type="primary">
                        <input type="hidden" name="action" value="verifikasi_donasi">
                        <input type="hidden" name="donasi_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="status" value="Terverifikasi">
                        <button type="submit" style="background:none; border:none; color:var(--primary); cursor:pointer; font-size:0.78rem; text-decoration:underline;">Pulihkan</button>
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
        <p class="page-lead">Kelola data penceramah sholat Jumat, kajian mingguan, dan kalender kegiatan masjid (Lokasi: <?= e($kotaJadwal) ?> · <?= e($zonaWaktu) ?>).</p>
      </div>
      <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="sinkron_jadwal_api">
        <button type="submit" class="btn btn-secondary" style="font-size:0.88rem; display:inline-flex; align-items:center; gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          <span>Sinkron Jadwal Sholat API</span>
        </button>
      </form>
    </div>

    <!-- FORM INPUT JADWAL KHATIB & KAJIAN -->
    <div class="card">
      <h3 style="font-size:1.25rem; margin-bottom:16px;">Input Jadwal Shalat Jumat / Kajian</h3>
      <form method="post" data-confirm="Simpan dan publikasikan jadwal kegiatan masjid ini?" data-confirm-title="Simpan Jadwal Kegiatan" data-confirm-btn="Simpan" data-confirm-type="primary">
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
                    <form method="post" style="display:inline;" data-confirm="Apakah Anda yakin ingin menghapus jadwal kegiatan ini dari agenda masjid?" data-confirm-title="Hapus Jadwal Kegiatan" data-confirm-btn="Hapus" data-confirm-type="danger">
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
        <form method="post" enctype="multipart/form-data" data-confirm="Simpan perubahan profil dan informasi rekening resmi masjid?" data-confirm-title="Simpan Pengaturan Masjid" data-confirm-btn="Simpan" data-confirm-type="primary">
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

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Kota / Lokasi Jadwal Sholat</label>
              <input type="text" name="kota_jadwal" class="form-input" value="<?= e($kotaJadwal) ?>" placeholder="Contoh: Kota Mataram, Surabaya, Jakarta" required>
              <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:3px;">Sinkronisasi otomatis harian dengan API Kemenag RI</small>
            </div>

            <div class="form-group">
              <label class="form-label">Zona Waktu Masjid</label>
              <select name="zona_waktu" class="form-select">
                <option value="WITA" <?= $zonaWaktu==='WITA'?'selected':'' ?>>WITA (Waktu Indonesia Tengah - Mataram / Bali / Makassar)</option>
                <option value="WIB" <?= $zonaWaktu==='WIB'?'selected':'' ?>>WIB (Waktu Indonesia Barat - Jakarta / Surabaya / Medan)</option>
                <option value="WIT" <?= $zonaWaktu==='WIT'?'selected':'' ?>>WIT (Waktu Indonesia Timur - Jayapura / Ambon)</option>
              </select>
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

          <div class="form-group">
            <label class="form-label">Foto / Barcode QRIS Resmi Masjid (Opsional)</label>
            <?php if (!empty($qrisImage) && file_qris_tersedia($qrisImage)): ?>
              <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-md); padding:14px; margin-bottom:10px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <img src="<?= e(url_gambar_qris($qrisImage)) ?>" alt="QRIS Resmi" style="width:72px; height:72px; object-fit:contain; background:#fff; border-radius:6px; border:1px solid var(--border); padding:4px;">
                <div style="flex:1; min-width:180px;">
                  <strong style="display:block; font-size:0.9rem; color:var(--text-main);">QRIS Resmi Terpasang</strong>
                  <span style="font-size:0.8rem; color:var(--text-muted);"><?= e($qrisImage) ?></span>
                </div>
                <label style="display:inline-flex; align-items:center; gap:6px; font-size:0.85rem; color:var(--danger); cursor:pointer;">
                  <input type="checkbox" name="hapus_qris_image" value="1">
                  <span>Hapus QRIS saat disimpan</span>
                </label>
              </div>
            <?php endif; ?>
            <input type="file" name="qris_file" accept=".jpg,.jpeg,.png,.webp" class="form-input" style="padding:8px 12px; font-size:0.9rem;">
            <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:3px;">Unggah gambar scan barcode QRIS resmi masjid Anda (JPG, PNG, atau WEBP, maks. 3 MB).</small>
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

<script src="assets/js/app.js?v=<?= file_exists(__DIR__ . '/assets/js/app.js') ? filemtime(__DIR__ . '/assets/js/app.js') : time() ?>"></script>
</body>
</html>
