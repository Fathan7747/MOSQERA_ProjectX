<?php $title='Beranda'; $page='beranda'; require 'includes/header.php'; $j = jadwal($db); ?>
<section class="hero">
  <div class="hero-text">
    <h1>Masjid yang teratur, jamaah yang terhubung.</h1>
    <p>MOSQERA membantu pengurus menyampaikan jadwal sholat, berita, dan kegiatan masjid dalam satu tempat.</p>
    <div class="cta">
      <a class="btn" href="register.php">Daftar sebagai jamaah</a>
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

<section id="tentang" class="sec">
  <h2>Tentang MOSQERA</h2>
  <p class="lead">MOSQERA dibuat untuk masjid dan musholla yang ingin informasinya rapi tanpa harus repot. Pengurus cukup memperbarui data, jamaah tinggal membuka halaman.</p>
</section>

<section id="fitur" class="sec alt">
  <h2>Fitur</h2>
  <div class="grid">
    <article><h3>Jadwal sholat</h3><p>Waktu sholat harian dengan hitung mundur menuju waktu berikutnya.</p></article>
    <article><h3>Berita masjid</h3><p>Pengumuman kajian, kegiatan, dan kabar terbaru untuk jamaah.</p></article>
    <article><h3>Akun jamaah</h3><p>Daftar dan masuk dengan email agar jamaah dapat terhubung dengan masjid.</p></article>
  </div>
</section>

<section id="faq" class="sec">
  <h2>Pertanyaan umum</h2>
  <div class="faq">
    <details><summary>Apakah MOSQERA gratis?</summary><p>Ya, versi awal ini gratis dipakai oleh masjid dan jamaah.</p></details>
    <details><summary>Bagaimana cara mengubah jadwal sholat?</summary><p>Ubah data pada tabel <code>jadwal_sholat</code> di database. Halaman admin akan menyusul.</p></details>
    <details><summary>Apakah data akun saya aman?</summary><p>Kata sandi disimpan dalam bentuk hash, bukan teks biasa.</p></details>
  </div>
</section>
<script>window.JADWAL = <?= json_encode($j) ?>;</script>
<?php require 'includes/footer.php'; ?>
