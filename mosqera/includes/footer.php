<?php
$alamatMasjid = pengaturan($db, 'alamat', "Jl. Silaturahmi Raya No. 45");
$kontakMasjid = pengaturan($db, 'telepon', "0812-3456-7890");
$namaMasjid = pengaturan($db, 'nama_masjid', "Masjid Jami' As-Salam");
?>
<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <strong style="color:var(--primary); font-size:1.05rem;"><?= e($namaMasjid) ?></strong>
      <p style="margin:4px 0 0;"><?= e($alamatMasjid) ?> · Telp/WA: <?= e($kontakMasjid) ?></p>
    </div>
    <div style="text-align:right;">
      <p style="margin:0;">Sistem Informasi & Tata Kelola Keuangan Masjid Transparan</p>
      <small style="color:var(--text-light);">&copy; <?= date('Y') ?> MOSQERA. Seluruh data kas disajikan secara akuntabel.</small>
    </div>
  </div>
</footer>

<script src="assets/js/app.js?v=<?= file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : time() ?>"></script>
</body>
</html>
