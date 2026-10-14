<?php
$title = 'Dashboard Pengurus Masjid';
$page = 'pengurus';
require_once 'config.php';

// Pastikan user login dan memiliki role pengurus, bendahara, atau admin
if (empty($_SESSION['user'])) {
    header('Location: login.php?role=pengurus');
    exit;
}
if (!in_array($_SESSION['user']['role'] ?? '', ['pengurus', 'bendahara', 'admin'])) {
    header('Location: dashboard_jamaah.php');
    exit;
}

$msg = '';
$err = '';

// Proses Tambah Program / Agenda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_program') {
    $nama = trim($_POST['nama_program'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
    $target = floatval($_POST['target_anggaran'] ?? 0);
    $realisasi = floatval($_POST['realisasi_anggaran'] ?? 0);
    $status = trim($_POST['status'] ?? 'Direncanakan');
    $pj = trim($_POST['penanggung_jawab'] ?? ($_SESSION['user']['nama'] ?? 'Pengurus'));
    $ket = trim($_POST['keterangan'] ?? '');

    if ($nama === '' || $target <= 0) {
        $err = 'Mohon isi nama program dan estimasi anggaran dengan benar.';
    } else {
        $ins = $db->prepare("INSERT INTO program_agenda (nama_program, tanggal, target_anggaran, realisasi_anggaran, status, penanggung_jawab, keterangan) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$nama, $tanggal, $target, $realisasi, $status, $pj, $ket]);
        $msg = 'Program & agenda kegiatan masjid berhasil ditambahkan.';
    }
}

// Proses Update Status Program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $idProg = intval($_POST['id'] ?? 0);
    $statusProg = trim($_POST['status'] ?? 'Direncanakan');
    if ($idProg > 0) {
        $upd = $db->prepare("UPDATE program_agenda SET status=? WHERE id=?");
        $upd->execute([$statusProg, $idProg]);
        $msg = 'Status program agenda berhasil diperbarui.';
    }
}

// Proses Hapus Program
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_program') {
    $idHapus = intval($_POST['id'] ?? 0);
    if ($idHapus > 0) {
        $del = $db->prepare("DELETE FROM program_agenda WHERE id=?");
        $del->execute([$idHapus]);
        $msg = 'Program kegiatan berhasil dihapus.';
    }
}

// Data Keuangan Real-Time (Pantauan Saldo Kas)
$kas = ringkasan_kas($db);

// Data Program Agenda
$programList = $db->query("SELECT * FROM program_agenda ORDER BY tanggal ASC, id DESC")->fetchAll();

// Hitung total kebutuhan anggaran
$totalTarget = 0;
$totalRealisasi = 0;
foreach ($programList as $p) {
    $totalTarget += $p['target_anggaran'];
    $totalRealisasi += $p['realisasi_anggaran'];
}

require 'includes/header.php';
?>

<section class="sec page">
  <div class="dash-head">
    <div>
      <span class="role-badge badge-pengurus">Peran: Pengurus Masjid</span>
      <h2>Dashboard Pemantauan Saldo & Program Masjid</h2>
      <p class="sub">Pantau saldo kas real-time, kelola program & agenda kegiatan masjid yang membutuhkan alokasi anggaran.</p>
    </div>
    <div class="dash-actions">
      <a href="kas.php" class="btn ghost">📊 Lihat Laporan Publik</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="alert-success"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>

  <!-- Pantauan Saldo & Alokasi Anggaran -->
  <div class="stat-grid">
    <div class="stat-card saldo">
      <div class="stat-icon">🏦</div>
      <div class="stat-info">
        <span class="stat-label">Saldo Kas Masjid Real-Time</span>
        <strong class="stat-val"><?= rupiah($kas['saldo']) ?></strong>
        <small>Total kas tercatat oleh Bendahara</small>
      </div>
    </div>
    <div class="stat-card masuk">
      <div class="stat-icon">🎯</div>
      <div class="stat-info">
        <span class="stat-label">Total Kebutuhan Anggaran</span>
        <strong class="stat-val"><?= rupiah($totalTarget) ?></strong>
        <small>Dari <?= count($programList) ?> agenda/program terdaftar</small>
      </div>
    </div>
    <div class="stat-card keluar">
      <div class="stat-icon">⚡</div>
      <div class="stat-info">
        <span class="stat-label">Dana Program Terpakai</span>
        <strong class="stat-val"><?= rupiah($totalRealisasi) ?></strong>
        <small>Realisasi serapan dana saat ini</small>
      </div>
    </div>
  </div>

  <div class="dash-content-grid">
    <!-- Form Tambah Program Agenda -->
    <div class="card-box">
      <h3>📅 Buat Program & Agenda Baru</h3>
      <p class="text-muted">Daftarkan rencana kegiatan masjid, estimasi dana, dan penanggung jawabnya.</p>
      <form method="post" class="form-compact">
        <input type="hidden" name="action" value="tambah_program">

        <label>Nama Program / Kegiatan
          <input type="text" name="nama_program" placeholder="Contoh: Santunan Anak Yatim & Dhuafa Ramadhan" required>
        </label>

        <div class="form-row">
          <label>Tanggal Pelaksanaan
            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
          </label>
          <label>Status Pelaksanaan
            <select name="status">
              <option value="Direncanakan">Direncanakan</option>
              <option value="Berjalan">Berjalan</option>
              <option value="Selesai">Selesai</option>
            </select>
          </label>
        </div>

        <div class="form-row">
          <label>Estimasi Anggaran (Rp)
            <input type="number" name="target_anggaran" min="1000" placeholder="Target kebutuhan dana..." required>
          </label>
          <label>Dana Terpakai Saat Ini (Rp)
            <input type="number" name="realisasi_anggaran" min="0" value="0" placeholder="0">
          </label>
        </div>

        <label>Penanggung Jawab / Seksi
          <input type="text" name="penanggung_jawab" placeholder="Contoh: Seksi Dakwah / Remaja Masjid" required>
        </label>

        <label>Keterangan & Target Kegiatan
          <textarea name="keterangan" rows="2" placeholder="Tujuan kegiatan, rincian biaya, atau jadwal detail..."></textarea>
        </label>

        <button class="btn" style="width:100%; margin-top:8px;">📌 Simpan Agenda Program</button>
      </form>
    </div>

    <!-- Daftar Program & Agenda -->
    <div class="card-box">
      <div class="box-head">
        <h3>📋 Agenda & Program Masjid Aktif</h3>
        <span class="badge-total"><?= count($programList) ?> Program</span>
      </div>

      <?php if (!$programList): ?>
        <p class="text-muted" style="padding:24px 0; text-align:center;">Belum ada program agenda terdaftar.</p>
      <?php else: ?>
        <div class="agenda-list">
          <?php foreach ($programList as $p): 
            $persen = $p['target_anggaran'] > 0 ? min(100, round(($p['realisasi_anggaran'] / $p['target_anggaran']) * 100)) : 0;
            $statusClass = $p['status'] === 'Selesai' ? 'badge-selesai' : ($p['status'] === 'Berjalan' ? 'badge-berjalan' : 'badge-rencana');
          ?>
            <div class="agenda-item">
              <div class="agenda-item-header">
                <div>
                  <h4 style="margin:0 0 4px;"><?= e($p['nama_program']) ?></h4>
                  <small class="text-muted">🗓️ <?= date('d M Y', strtotime($p['tanggal'])) ?> · PJ: <?= e($p['penanggung_jawab']) ?></small>
                </div>
                <div class="agenda-status-ctrl">
                  <span class="badge-status <?= $statusClass ?>"><?= e($p['status']) ?></span>
                </div>
              </div>

              <?php if ($p['keterangan']): ?>
                <p class="agenda-desc"><?= e($p['keterangan']) ?></p>
              <?php endif; ?>

              <!-- Progress Anggaran -->
              <div class="progress-box">
                <div class="progress-labels">
                  <span>Realisasi: <strong><?= rupiah($p['realisasi_anggaran']) ?></strong></span>
                  <span>Target: <strong><?= rupiah($p['target_anggaran']) ?></strong> (<?= $persen ?>%)</span>
                </div>
                <div class="progress-bar-bg">
                  <div class="progress-bar-fill" style="width: <?= $persen ?>%;"></div>
                </div>
              </div>

              <!-- Action Status & Hapus -->
              <div class="agenda-footer">
                <form method="post" style="display:flex; gap:6px; align-items:center;">
                  <input type="hidden" name="action" value="update_status">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <small>Ubah status:</small>
                  <select name="status" onchange="this.form.submit()" style="padding:4px 8px; font-size:0.8rem; border-radius:4px;">
                    <option value="Direncanakan" <?= $p['status']=='Direncanakan'?'selected':'' ?>>Direncanakan</option>
                    <option value="Berjalan" <?= $p['status']=='Berjalan'?'selected':'' ?>>Berjalan</option>
                    <option value="Selesai" <?= $p['status']=='Selesai'?'selected':'' ?>>Selesai</option>
                  </select>
                </form>

                <form method="post" onsubmit="return confirm('Hapus program ini?');">
                  <input type="hidden" name="action" value="hapus_program">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button class="btn-del" title="Hapus Program">🗑️</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
