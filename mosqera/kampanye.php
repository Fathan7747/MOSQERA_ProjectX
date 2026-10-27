<?php
$title = 'Kampanye Kebaikan & Donasi Digital';
$page = 'kampanye';
require_once 'config.php';

$successMsg = '';
$errorMsg = '';

// Proses Kirim Donasi Online
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'donasi_online') {
    $kampanyeId = intval($_POST['kampanye_id'] ?? 0);
    $namaDonatur = trim($_POST['nama_donatur'] ?? '');
    $anonim = isset($_POST['anonim']) ? 1 : 0;
    if ($anonim || empty($namaDonatur)) {
        $namaDonatur = 'Hamba Allah';
    }
    $nominal = floatval($_POST['nominal'] ?? 0);
    $metode = trim($_POST['metode'] ?? 'QRIS');
    $catatanDoa = trim($_POST['catatan_doa'] ?? '');

    if ($nominal < 10000) {
        $errorMsg = 'Nominal donasi minimal Rp 10.000.';
    } else {
        $stmtIns = $db->prepare("INSERT INTO donasi_masuk (kampanye_id, nama_donatur, nominal, metode, catatan_doa, status, tanggal) VALUES (?,?,?,?,?,?,CURDATE())");
        $stmtIns->execute([$kampanyeId ?: null, $namaDonatur, $nominal, $metode, $catatanDoa, 'Terverifikasi']);

        // Update saldo terkumpul kampanye
        if ($kampanyeId > 0) {
            $upd = $db->prepare("UPDATE kampanye_donasi SET terkumpul = terkumpul + ? WHERE id=?");
            $upd->execute([$nominal, $kampanyeId]);
        }

        // Catat juga ke transaksi kas masuk
        $stmtKas = $db->prepare("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, dibuat_oleh) VALUES (CURDATE(), 'masuk', 'Donasi Transfer', ?, ?, ?, 'Donasi Online')");
        $judulKas = 'Donasi Online: ' . ($kampanyeId ? 'Proyek #' . $kampanyeId : 'Infaq Umum') . ' (' . $namaDonatur . ')';
        $stmtKas->execute([$judulKas, $nominal, $catatanDoa]);

        $successMsg = 'Jazakallahu khairan! Donasi Anda sebesar ' . rupiah($nominal) . ' berhasil tercatat. Semoga berkah dan menjadi amal jariyah.';
    }
}

// Ambil Daftar Kampanye
$kampanyeList = $db->query("SELECT * FROM kampanye_donasi ORDER BY (status='Aktif') DESC, id DESC")->fetchAll();

// Ambil Kampanye Terpilih jika ada
$idSelected = intval($_GET['id'] ?? 0);
$selectedKampanye = null;
if ($idSelected > 0) {
    foreach ($kampanyeList as $k) {
        if ($k['id'] == $idSelected) {
            $selectedKampanye = $k;
            break;
        }
    }
}
if (!$selectedKampanye && count($kampanyeList) > 0) {
    $selectedKampanye = $kampanyeList[0];
}

// Donasi Terkini untuk Transparansi
$donasiTerbaru = $db->query("SELECT d.*, k.judul as nama_proyek FROM donasi_masuk d LEFT JOIN kampanye_donasi k ON d.kampanye_id = k.id ORDER BY d.id DESC LIMIT 8")->fetchAll();

$bankInfo = pengaturan($db, 'bank_info', 'Bank Syariah Indonesia (BSI) No. Rekening: 7123-456-789 a.n Kas DKM Masjid Jami As-Salam');
$qrisLabel = pengaturan($db, 'qris_label', 'QRIS Standar Pembayaran Nasional (NMID: ID1029384756)');

require 'includes/header.php';
?>

<div class="page-container">
  
  <div class="page-header-row">
    <div>
      <span class="page-badge">Portal Publik Jamaah</span>
      <h1 class="page-title">Kampanye Kebaikan & Donasi Digital</h1>
      <p class="page-lead">Wadah sedekah jariyah dan infaq terprogram untuk pemeliharaan fasilitas ibadah, pendidikan santri yatim, dan kemakmuran masjid.</p>
    </div>
  </div>

  <?php if ($successMsg): ?>
    <div class="alert alert-success"><?= e($successMsg) ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-error"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <!-- Daftar Proyek Donasi -->
  <div class="campaign-grid">
    <?php foreach ($kampanyeList as $k): 
      $persen = $k['target_dana'] > 0 ? min(100, round(($k['terkumpul'] / $k['target_dana']) * 100)) : 0;
      $sisaHari = null;
      if ($k['batas_waktu']) {
          $diff = (strtotime($k['batas_waktu']) - time()) / (60 * 60 * 24);
          $sisaHari = max(0, ceil($diff));
      }
    ?>
      <div class="campaign-card" style="border:<?= ($selectedKampanye && $selectedKampanye['id']==$k['id']) ? '2px solid var(--primary)' : '1px solid var(--border)' ?>;">
        <span class="campaign-cat"><?= e($k['kategori']) ?> · <?= e($k['status']) ?></span>
        <h3 class="campaign-title"><?= e($k['judul']) ?></h3>
        <p class="campaign-desc"><?= e($k['deskripsi']) ?></p>

        <div class="progress-container">
          <div class="progress-bar-track">
            <div class="progress-bar-fill" style="width: <?= $persen ?>%;"></div>
          </div>
          <div class="progress-metrics">
            <span>Terkumpul: <strong><?= rupiah($k['terkumpul']) ?></strong></span>
            <span><strong><?= $persen ?>%</strong> dari <?= rupiah($k['target_dana']) ?></span>
          </div>
        </div>

        <?php if ($sisaHari !== null): ?>
          <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:14px;">
            Waktu tersisa: <strong><?= $sisaHari ?> hari lagi</strong>
          </div>
        <?php endif; ?>

        <a href="kampanye.php?id=<?= $k['id'] ?>#donasi-form" class="btn btn-primary" style="margin-top:auto; width:100%;">
          Donasi Proyek Ini
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Formulir Donasi Online & Instruksi Pembayaran (QRIS & Transfer) -->
  <section id="donasi-form" class="card" style="margin-top:36px;">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.35rem;">Formulir Donasi Online</h3>
        <span style="font-size:0.88rem; color:var(--text-muted);">
          Menyalurkan untuk: <strong><?= $selectedKampanye ? e($selectedKampanye['judul']) : 'Infaq Kas Umum Masjid' ?></strong>
        </span>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:36px;" class="form-row">
      
      <!-- Sisi Kiri: Form Input -->
      <form method="post">
        <input type="hidden" name="action" value="donasi_online">
        <input type="hidden" name="kampanye_id" value="<?= $selectedKampanye ? $selectedKampanye['id'] : 0 ?>">

        <div class="form-group">
          <label class="form-label">Pilihan Nominal Donasi (Rp)</label>
          <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:8px; margin-bottom:10px;">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('input-nominal').value='50000'" style="padding:8px;">Rp 50.000</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('input-nominal').value='100000'" style="padding:8px;">Rp 100.000</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('input-nominal').value='250000'" style="padding:8px;">Rp 250.000</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('input-nominal').value='500000'" style="padding:8px;">Rp 500.000</button>
          </div>
          <input type="number" id="input-nominal" name="nominal" class="form-input" placeholder="Atau ketik nominal sendiri, contoh: 200000" min="10000" step="5000" required>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Donatur</label>
          <input type="text" name="nama_donatur" id="input-nama" class="form-input" placeholder="Nama lengkap Anda">
          <label style="display:flex; align-items:center; gap:8px; font-size:0.88rem; margin-top:6px; cursor:pointer;">
            <input type="checkbox" name="anonim" value="1" onchange="document.getElementById('input-nama').disabled = this.checked;">
            Sembunyikan nama saya (Tampilkan sebagai Hamba Allah)
          </label>
        </div>

        <div class="form-group">
          <label class="form-label">Metode Pembayaran</label>
          <select name="metode" class="form-select" required>
            <option value="QRIS">QRIS (GoPay, OVO, Dana, LinkAja, BCA, Mandiri, BSI, dll.)</option>
            <option value="Transfer Bank">Transfer Bank Syariah Indonesia (BSI)</option>
            <option value="Tunai">Penyerahan Tunai Langsung di Sekretariat DKM</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Doa / Pesan Kebaikan (Opsional)</label>
          <textarea name="catatan_doa" class="form-textarea" rows="2" placeholder="Tuliskan doa atau niat sedekah..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1.05rem;">
          Lanjutkan Donasi Sekarang
        </button>
      </form>

      <!-- Sisi Kanan: Panduan QRIS & Rekening Resmi -->
      <div style="background:var(--bg); border:1px solid var(--border); border-radius:var(--radius-lg); padding:24px;">
        <h4 style="margin:0 0 12px; font-size:1.1rem; color:var(--primary); font-family:inherit; font-weight:700;">Instruksi Rekening & QRIS Resmi</h4>
        
        <!-- Simulasi Kotak QRIS Bersih -->
        <div style="background:#ffffff; border:1px solid var(--border); border-radius:var(--radius-md); padding:16px; text-align:center; margin-bottom:16px;">
          <div style="font-size:0.82rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:8px;">Scan QRIS Semua Bank & E-Wallet</div>
          <div style="width:160px; height:160px; margin:0 auto; background:#fafafa; border:2px dashed var(--border); display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:8px;">
            <span style="font-weight:700; color:var(--primary); font-size:1.1rem;">QRIS RESMI</span>
            <span style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;">NMID: ID1029384756</span>
          </div>
          <div style="font-size:0.8rem; color:var(--text-muted); margin-top:8px;"><?= e($qrisLabel) ?></div>
        </div>

        <div>
          <div style="font-size:0.82rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; margin-bottom:4px;">Transfer Rekening Bank</div>
          <div style="background:#ffffff; border:1px solid var(--border); border-radius:var(--radius-sm); padding:12px; font-size:0.9rem;">
            <?= nl2br(e($bankInfo)) ?>
          </div>
        </div>

        <p style="font-size:0.82rem; color:var(--text-muted); margin-top:14px;">
          Setiap donasi yang masuk akan langsung tercatat secara otomatis pada transparansi kas masjid dan diteruskan untuk program kebaikan.
        </p>
      </div>

    </div>
  </section>

  <!-- Daftar Donatur Kebaikan Terkini (Transparansi Terbuka) -->
  <section class="card" style="margin-top:36px;">
    <div class="card-header">
      <div>
        <h3 style="font-size:1.25rem;">Donatur & Sahabat Kebaikan Terkini</h3>
        <span style="font-size:0.85rem; color:var(--text-muted);">Riwayat infaq dan sedekah yang masuk secara akuntabel.</span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nama Donatur</th>
            <th>Untuk Proyek</th>
            <th>Metode</th>
            <th>Doa / Pesan</th>
            <th style="text-align:right;">Nominal (Rp)</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$donasiTerbaru): ?>
            <tr><td colspan="6" style="text-align:center; padding:24px;">Belum ada donasi online tercatat.</td></tr>
          <?php else: ?>
            <?php foreach ($donasiTerbaru as $d): ?>
              <tr>
                <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                <td><strong><?= e($d['nama_donatur']) ?></strong></td>
                <td><span style="font-size:0.85rem; color:var(--primary); font-weight:600;"><?= e($d['nama_proyek'] ?? 'Infaq Umum') ?></span></td>
                <td><span class="badge-tag" style="background:var(--primary-subtle); color:var(--primary);"><?= e($d['metode']) ?></span></td>
                <td style="font-size:0.85rem; color:var(--text-muted); font-style:italic;"><?= e($d['catatan_doa'] ?: '-') ?></td>
                <td style="text-align:right; font-weight:700; color:var(--success);"><?= rupiah($d['nominal']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<?php require 'includes/footer.php'; ?>
