<?php
$title = 'Pendidikan TPA & Pendaftaran Santri';
$page = 'tpa';
require_once 'config.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'daftar_tpa') {
    $namaSantri = trim($_POST['nama_santri'] ?? '');
    $umur = intval($_POST['umur'] ?? 0);
    $jenisKelamin = trim($_POST['jenis_kelamin'] ?? 'Laki-laki');
    $namaWali = trim($_POST['nama_wali'] ?? '');
    $noKontak = trim($_POST['no_kontak'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $kelas = trim($_POST['kelas_pilihan'] ?? 'Iqro Awal');

    if (empty($namaSantri) || empty($namaWali) || empty($noKontak) || $umur <= 0) {
        $errorMsg = 'Mohon lengkapi seluruh kolom formulir dengan data yang valid.';
    } else {
        $stmtIns = $db->prepare("INSERT INTO pendaftaran_tpa (nama_santri, umur, jenis_kelamin, nama_wali, no_kontak, alamat, kelas_pilihan, status) VALUES (?,?,?,?,?,?,?,'Menunggu Verifikasi')");
        $stmtIns->execute([$namaSantri, $umur, $jenisKelamin, $namaWali, $noKontak, $alamat, $kelas]);
        $successMsg = 'Alhamdulillah, pendaftaran calon santri ananda ' . e($namaSantri) . ' berhasil dikirim. Pengurus TPA akan segera menghubungi nomor WhatsApp Anda.';
    }
}

$namaMasjid = pengaturan($db, 'nama_masjid', "Masjid Jami' As-Salam");

require 'includes/header.php';
?>

<div class="page-container">
  
  <div class="page-header-row">
    <div>
      <span class="page-badge">Portal Publik Jamaah</span>
      <h1 class="page-title">Pendidikan Taman Pendidikan Al-Qur'an (TPA)</h1>
      <p class="page-lead">Membina generasi qurani berkarakter mulia dengan bimbingan asatidz berpengalaman di lingkungan masjid.</p>
    </div>
  </div>

  <?php if ($successMsg): ?>
    <div class="alert alert-success"><?= $successMsg ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Informasi Program & Pendaftaran -->
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px; margin-bottom:36px;">
    
    <div class="card">
      <h3 style="font-size:1.25rem; margin-bottom:12px;">Kurikulum & Jenjang Kelas</h3>
      <p style="font-size:0.92rem; color:var(--text-muted); margin-bottom:14px;">Pembelajaran santri dibagi sesuai tingkatan kemampuan membaca Al-Qur'an:</p>
      
      <div style="display:flex; flex-direction:column; gap:10px; font-size:0.9rem;">
        <div style="padding:10px; background:var(--bg); border-radius:var(--radius-sm); border:1px solid var(--border-light);">
          <strong style="color:var(--primary); display:block;">1. Kelas Iqro Awal (Jilid 1 - 3)</strong>
          <span style="color:var(--text-muted); font-size:0.85rem;">Pengenalan huruf hijaiyah, makhorijul huruf dasar, dan doa harian.</span>
        </div>
        <div style="padding:10px; background:var(--bg); border-radius:var(--radius-sm); border:1px solid var(--border-light);">
          <strong style="color:var(--primary); display:block;">2. Kelas Iqro Lanjutan (Jilid 4 - 6)</strong>
          <span style="color:var(--text-muted); font-size:0.85rem;">Penyambungan huruf, panjang-pendek bacaan, dan hafalan surah pendek.</span>
        </div>
        <div style="padding:10px; background:var(--bg); border-radius:var(--radius-sm); border:1px solid var(--border-light);">
          <strong style="color:var(--primary); display:block;">3. Kelas Al-Qur'an & Tajwid</strong>
          <span style="color:var(--text-muted); font-size:0.85rem;">Kaidah hukum tajwid, praktek tartil, dan pengenalan adab Islam.</span>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="font-size:1.25rem; margin-bottom:12px;">Waktu & Ketentuan Belajar</h3>
      <div style="font-size:0.92rem; color:var(--text-muted); line-height:1.7;">
        <p><strong>Hari Belajar:</strong> Senin s/d Kamis (4 kali sepekan)</p>
        <p><strong>Waktu Belajar:</strong> Ba'da Ashar (Pukul 16.00 – 17.30 WIB)</p>
        <p><strong>Persyaratan Santri:</strong> Usia 5 s/d 14 tahun, siap mengikuti tata tertib TPA Masjid.</p>
        <p><strong>Biaya Pendidikan:</strong> Disediakan program beasiswa penuh (gratis) bagi anak yatim dan keluarga dhuafa yang didukung dari kas infaq donatur.</p>
      </div>

      <div style="margin-top:20px; padding:14px; background:var(--primary-subtle); border-radius:var(--radius-md); border:1px solid #cce5e0;">
        <strong style="color:var(--primary); font-size:0.95rem; display:block; margin-bottom:4px;">Butuh Konsultasi Pendaftaran?</strong>
        <span style="font-size:0.85rem; color:var(--text-muted);">Silakan hubungi koordinator TPA melalui nomor kontak sekretariat masjid.</span>
      </div>
    </div>

  </div>

  <!-- Formulir Pendaftaran TPA Online -->
  <section id="daftar" class="card">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.35rem;">Formulir Pendaftaran Santri Baru Online</h3>
        <span style="font-size:0.88rem; color:var(--text-muted);">Orang tua / wali calon santri dapat mengisi formulir resmi di bawah ini:</span>
      </div>
    </div>

    <form method="post" style="max-width:780px;">
      <input type="hidden" name="action" value="daftar_tpa">

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Lengkap Calon Santri</label>
          <input type="text" name="nama_santri" class="form-input" placeholder="Nama lengkap ananda" required>
        </div>

        <div class="form-group">
          <label class="form-label">Usia Santri (Tahun)</label>
          <input type="number" name="umur" class="form-input" placeholder="Contoh: 7" min="4" max="17" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Jenis Kelamin</label>
          <select name="jenis_kelamin" class="form-select" required>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Pilihan Jenjang Awal</label>
          <select name="kelas_pilihan" class="form-select" required>
            <option value="Iqro Awal">Iqro Awal (Belum lancar mengenal huruf)</option>
            <option value="Iqro Lanjutan">Iqro Lanjutan (Sudah jilid 3 ke atas)</option>
            <option value="Al-Qur'an Tajwid">Kelas Al-Qur'an & Tajwid</option>
            <option value="Tahfidz Anak">Tahfidz Juz 'Amma</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nama Orang Tua / Wali</label>
          <input type="text" name="nama_wali" class="form-input" placeholder="Nama ayah / ibu / wali" required>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor WhatsApp / HP Aktif</label>
          <input type="tel" name="no_kontak" class="form-input" placeholder="Contoh: 081234567890" required>
          <span class="form-help">Pengurus akan mengirim konfirmasi jadwal kelas melalui WhatsApp ini.</span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Alamat Domisili / Tempat Tinggal</label>
        <textarea name="alamat" class="form-textarea" rows="2" placeholder="Alamat rumah tempat tinggal calon santri..." required></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="padding:14px 28px; font-size:1.05rem;">
        Kirim Pendaftaran Santri Baru
      </button>
    </form>
  </section>

</div>

<?php require 'includes/footer.php'; ?>
