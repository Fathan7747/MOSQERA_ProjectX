<?php 
$title = 'Beranda'; 
$page = 'beranda'; 
require 'includes/header.php'; 
$j = jadwal($db); 
$kas = ringkasan_kas($db);
?>
<section class="hero">
  <div class="hero-text">
    <h1>Masjid yang teratur, jamaah yang terhubung.</h1>
    <p>MOSQERA membantu pengurus menyampaikan jadwal sholat, berita, dan transparansi keuangan kas masjid dalam satu tempat.</p>
    <div class="cta">
      <!-- Dropdown Daftar Sebagai 3 Peran -->
      <div class="hero-dd">
        <button class="btn hero-dd-btn" type="button" aria-expanded="false">Daftar Sebagai ▾</button>
        <div class="hero-dd-menu" style="display:none;">
          <a href="register.php?role=bendahara" class="hero-dd-item">
            <span class="role-bubble" style="background:#0e2a2f;">1</span>
            <div>
              <strong>Admin / Bendahara</strong>
              <small>Input kas, nota, kelola dana & terbitkan laporan</small>
            </div>
          </a>
          <a href="register.php?role=pengurus" class="hero-dd-item">
            <span class="role-bubble" style="background:#c99a2e;">2</span>
            <div>
              <strong>Pengurus Masjid</strong>
              <small>Pantau saldo kas & kelola program kegiatan</small>
            </div>
          </a>
          <a href="register.php?role=jamaah" class="hero-dd-item">
            <span class="role-bubble" style="background:#0e2a2f;">3</span>
            <div>
              <strong>Jamaah (Publik)</strong>
              <small>Pantau transparansi kas, jadwal & berita</small>
            </div>
          </a>
        </div>
      </div>
      <a class="btn ghost" href="kas.php">📊 Transparansi Kas</a>
      <a class="btn ghost" href="jadwal.php">Lihat jadwal sholat</a>
    </div>
  </div>
  <div class="arch" aria-live="polite">
    <span>Sholat berikutnya</span>
    <strong id="next-name">—</strong>
    <div id="next-count">--:--:--</div>
    <small id="next-time"></small>
  </div>
</section>

<!-- 3 Peran Utama MOSQERA Sesuai Desain Slide -->
<section id="peran" class="sec">
  <div style="text-align:center; margin-bottom: 32px;">
    <h2>3 Peran Utama Pengelolaan MOSQERA</h2>
    <p class="lead" style="margin:auto;">Sistem aplikasi web yang menghubungkan tiga peran utama demi transparansi, akuntabilitas, dan efisiensi dana masjid.</p>
  </div>

  <div class="role-cards-grid">
    <!-- 1. Admin / Bendahara -->
    <div class="role-card">
      <div class="role-circle" style="background:#0e2a2f;">1</div>
      <h3>Admin / Bendahara</h3>
      <p>Input transaksi, verifikasi, kelola kategori dana, terbitkan laporan resmi.</p>
      <div class="role-card-actions">
        <a href="register.php?role=bendahara" class="btn">Daftar</a>
        <a href="login.php?role=bendahara" class="btn-card-login">Masuk</a>
      </div>
    </div>

    <!-- 2. Pengurus Masjid -->
    <div class="role-card">
      <div class="role-circle" style="background:#c99a2e;">2</div>
      <h3>Pengurus Masjid</h3>
      <p>Memantau saldo, mengelola program & agenda masjid yang butuh anggaran.</p>
      <div class="role-card-actions">
        <a href="register.php?role=pengurus" class="btn">Daftar</a>
        <a href="login.php?role=pengurus" class="btn-card-login">Masuk</a>
      </div>
    </div>

    <!-- 3. Jamaah (Publik) -->
    <div class="role-card">
      <div class="role-circle" style="background:#0e2a2f;">3</div>
      <h3>Jamaah (Publik)</h3>
      <p>Melihat laporan keuangan real-time tanpa perlu login — mendorong transparansi penuh.</p>
      <div class="role-card-actions">
        <a href="register.php?role=jamaah" class="btn">Daftar</a>
        <a href="login.php?role=jamaah" class="btn-card-login">Masuk</a>
      </div>
    </div>
  </div>
</section>

<!-- Sekilas Transparansi Kas Terkini -->
<section class="sec alt">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:20px;">
    <div>
      <h2 style="margin:0;">Transparansi Kas Masjid Terkini</h2>
      <p class="text-muted" style="margin:4px 0 0;">Laporan keuangan kas real-time yang dapat diakses seluruh jamaah.</p>
    </div>
    <a href="kas.php" class="btn">Lihat Laporan Lengkap &rarr;</a>
  </div>
  
  <div class="stat-grid">
    <div class="stat-card masuk">
      <div class="stat-icon">📥</div>
      <div class="stat-info">
        <span class="stat-label">Total Kas Masuk</span>
        <strong class="stat-val"><?= rupiah($kas['masuk']) ?></strong>
        <small>Infaq, Sedekah & Donasi</small>
      </div>
    </div>
    <div class="stat-card keluar">
      <div class="stat-icon">📤</div>
      <div class="stat-info">
        <span class="stat-label">Total Kas Keluar</span>
        <strong class="stat-val"><?= rupiah($kas['keluar']) ?></strong>
        <small>Operasional & Kegiatan</small>
      </div>
    </div>
    <div class="stat-card saldo">
      <div class="stat-icon">💰</div>
      <div class="stat-info">
        <span class="stat-label">Saldo Kas Saat Ini</span>
        <strong class="stat-val"><?= rupiah($kas['saldo']) ?></strong>
        <small>Akuntabel & Terbuka</small>
      </div>
    </div>
  </div>
</section>

<section id="tentang" class="sec">
  <h2>Tentang MOSQERA</h2>
  <p class="lead">MOSQERA dibuat untuk masjid dan musholla yang ingin informasinya rapi tanpa harus repot.
Pengurus cukup memperbarui data kas masuk dan keluar, sementara jamaah tinggal membuka halaman publik secara terbuka tanpa perlu login.
Platform ini mengotomatiskan pengelompokan pos anggaran, penyimpanan foto bukti nota, hingga penyusunan rekapitulasi laporan keuangan secara instan.
Tidak ada lagi pencatatan manual yang berisiko tercecer atau penyusunan buku kas yang memakan banyak waktu dan tenaga pengurus.
Hadir berbasis web yang praktis, MOSQERA mengakselerasi transparansi kas demi memperkuat akuntabilitas dan kepercayaan penuh seluruh jamaah.</p>
</section>

<section id="fitur" class="sec alt">
  <h2>Fitur Utama</h2>
  <div class="grid">
    <article><h3>Manajemen Transaksi & Kas</h3><p>Pencatatan kas masuk & keluar, pengelompokan kategori dana, serta pemantauan saldo real-time.</p></article>
    <article><h3>Program & Agenda Masjid</h3><p>Pengurus dapat mengelola rencana kegiatan masjid dan memantau serapan kebutuhan anggaran.</p></article>
    <article><h3>Transparansi & Pelaporan</h3><p>Dashboard publik transparansi, laporan keuangan instan, dan visualisasi grafik yang mudah dipahami.</p></article>
    <article><h3>Jadwal Sholat & Berita</h3><p>Jadwal sholat 5 waktu otomatis dengan hitung mundur waktu berikutnya dan pengumuman kajian.</p></article>
  </div>
</section>

<section id="faq" class="sec">
  <h2>Pertanyaan umum</h2>
  <div class="faq">
    <details><summary>Apakah MOSQERA gratis?</summary><p>Ya, versi awal ini gratis dipakai oleh masjid dan jamaah.</p></details>
    <details><summary>Siapa saja yang bisa menggunakan MOSQERA?</summary><p>Ada 3 peran: Admin/Bendahara untuk mencatat kas, Pengurus Masjid untuk mengelola program agenda, dan Jamaah/Publik untuk melihat laporan transparansi.</p></details>
    <details><summary>Apakah jamaah harus login untuk melihat kas masjid?</summary><p>Tidak. Jamaah dan masyarakat umum dapat melihat laporan keuangan kas masjid secara transparan tanpa perlu login.</p></details>
    <details><summary>Apakah data akun saya aman?</summary><p>Kata sandi disimpan dalam bentuk hash terenkripsi (BCrypt), bukan teks biasa.</p></details>
  </div>
</section>

<script>window.JADWAL = <?= json_encode($j) ?>;</script>
<?php require 'includes/footer.php'; ?>
