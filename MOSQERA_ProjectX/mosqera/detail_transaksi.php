<?php
$title = 'Detail Transaksi Kas';
$page = 'transaksi_detail';
require_once 'config.php';

$id = intval($_GET['id'] ?? 0);
$transaksi = null;

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM transaksi_kas WHERE id = ?");
    $stmt->execute([$id]);
    $transaksi = $stmt->fetch();
}

require 'includes/header.php';
?>

<div class="page-container" style="max-width: 860px; margin: 32px auto;">

  <!-- Tombol Navigasi Kembali -->
  <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <div>
      <a href="javascript:history.back()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
        <span>Kembali</span>
      </a>
      <a href="transparansi.php" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem; margin-left: 6px;">
        Transparansi Kas
      </a>
      <?php if (!empty($_SESSION['user'])): ?>
        <a href="admin.php?tab=kas" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem; margin-left: 6px;">
          Buku Kas Admin
        </a>
      <?php endif; ?>
    </div>

    <div>
      <button onclick="window.print()" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Lembar Bukti</span>
      </button>
    </div>
  </div>

  <?php if (!$transaksi): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 12px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <h2 style="font-size: 1.5rem; margin-bottom: 8px;">Catatan Transaksi Tidak Ditemukan</h2>
      <p style="color: var(--text-muted); max-width: 480px; margin: 0 auto 20px;">Nomor ID transaksi yang Anda cari tidak tersedia dalam buku kas masjid atau mungkin telah diperbarui.</p>
      <a href="transparansi.php" class="btn btn-primary">Kembali ke Laporan Kas Masjid</a>
    </div>
  <?php else: 
    $isMasuk = $transaksi['jenis'] === 'masuk';
    $buktiFile = $transaksi['bukti_file'];
    $buktiAda = !empty($buktiFile) && file_bukti_tersedia($buktiFile);
    $buktiUrl = $buktiAda ? url_bukti_transaksi($buktiFile) : '';
    $isPdf = $buktiAda && is_pdf_bukti($buktiFile);
    $isImg = $buktiAda && is_image_bukti($buktiFile);
  ?>
    <div class="card" style="box-shadow: var(--shadow-md);">
      
      <!-- Header Kartu Detail -->
      <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 1px solid var(--border-light); margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
        <div>
          <span style="font-size: 0.85rem; font-weight: 700; color: var(--accent); letter-spacing: 0.08em; text-transform: uppercase;">
            LEMBAR TRANSAKSI KAS RESMI
          </span>
          <h1 style="font-size: 1.85rem; margin: 4px 0 2px; color: var(--primary);">
            #TRX-<?= str_pad($transaksi['id'], 5, '0', STR_PAD_LEFT) ?>
          </h1>
          <span style="font-size: 0.9rem; color: var(--text-muted);">
            Tanggal Transaksi: <strong><?= date('d F Y', strtotime($transaksi['tanggal'])) ?></strong>
          </span>
        </div>

        <div style="text-align: right;">
          <span class="badge-tag <?= $isMasuk ? 'badge-masuk' : 'badge-keluar' ?>" style="font-size: 0.95rem; padding: 6px 14px;">
            <?= $isMasuk ? 'Uang Masuk (Pemasukan)' : 'Uang Keluar (Pengeluaran)' ?>
          </span>
          <div style="margin-top: 8px; font-size: 1.6rem; font-weight: 800; color: <?= $isMasuk ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= $isMasuk ? '+' : '-' ?> <?= rupiah($transaksi['nominal']) ?>
          </div>
        </div>
      </div>

      <!-- Grid Rincian Informasi -->
      <div class="transaksi-detail-grid" style="margin-bottom: 28px;">
        <div class="transaksi-detail-item">
          <span class="transaksi-detail-label">Kategori Transaksi</span>
          <span class="transaksi-detail-value" style="color: var(--primary); font-size: 1.05rem;">
            <?= e($transaksi['kategori']) ?>
          </span>
        </div>

        <div class="transaksi-detail-item">
          <span class="transaksi-detail-label">Petugas / Sumber Pencatat</span>
          <span class="transaksi-detail-value">
            <?= e($transaksi['dibuat_oleh'] ?: 'Pengurus DKM') ?>
          </span>
        </div>

        <div class="transaksi-detail-item full">
          <span class="transaksi-detail-label">Uraian / Judul Keperluan Transaksi</span>
          <span class="transaksi-detail-value" style="font-size: 1.15rem; color: var(--text-main);">
            <?= e($transaksi['judul']) ?>
          </span>
        </div>

        <div class="transaksi-detail-item full">
          <span class="transaksi-detail-label">Rincian Keterangan & Catatan Tambahan</span>
          <div style="background: var(--bg); border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 14px 18px; color: var(--text-main); line-height: 1.6; font-size: 0.96rem;">
            <?= !empty($transaksi['keterangan']) ? nl2br(e($transaksi['keterangan'])) : '<em style="color:var(--text-muted);">Tidak ada catatan keterangan tambahan.</em>' ?>
          </div>
        </div>

        <div class="transaksi-detail-item">
          <span class="transaksi-detail-label">Waktu Pencatatan Sistem</span>
          <span class="transaksi-detail-value" style="font-size: 0.88rem; font-weight: 400; color: var(--text-muted);">
            <?= !empty($transaksi['created_at']) ? date('d/m/Y H:i:s', strtotime($transaksi['created_at'])) : '-' ?>
          </span>
        </div>

        <div class="transaksi-detail-item">
          <span class="transaksi-detail-label">Status Verifikasi</span>
          <span class="transaksi-detail-value" style="color: var(--success); font-size: 0.88rem;">
            &check; Sah & Tercatat dalam Buku Kas
          </span>
        </div>
      </div>

      <!-- Bagian Bukti Transaksi Fisik -->
      <div class="transaksi-bukti-section">
        <div class="transaksi-bukti-title">
          <span style="font-size: 1.05rem; font-weight: 700;">Bukti Transaksi Fisik / Digital</span>
          <?php if ($buktiAda): ?>
            <span class="bukti-badge-tag <?= $isPdf ? 'bukti-badge-pdf' : 'bukti-badge-img' ?>">
              <?= $isPdf ? 'DOKUMEN PDF RESMI' : 'FOTO BUKTI / NOTA FISIK' ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if ($buktiAda): ?>
          <div class="transaksi-bukti-preview-box" style="margin-top: 10px;">
            <?php if ($isImg): ?>
              <div style="text-align: center; padding: 12px 0;">
                <img src="<?= e($buktiUrl) ?>" alt="Bukti Transaksi <?= e($transaksi['judul']) ?>" class="transaksi-bukti-image js-btn-zoom-bukti" data-src="<?= e($buktiUrl) ?>" data-title="<?= e($transaksi['judul']) ?>" style="max-height: 480px; box-shadow: var(--shadow-sm);" title="Klik untuk melihat perbesaran resolusi penuh">
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 10px;">
                  Klik pada gambar untuk memperbesar resolusi penuh (Zoom Lightbox)
                </div>
              </div>
            <?php elseif ($isPdf): ?>
              <div style="padding: 32px 20px; text-align: center;">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--danger); margin-bottom: 12px;">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">
                  <?= e($buktiFile) ?>
                </div>
                <div style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
                  Dokumen lampiran bukti transaksi dalam format PDF
                </div>
                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                  <a href="<?= e($buktiUrl) ?>" target="_blank" class="btn btn-secondary" style="padding: 8px 18px; font-size: 0.9rem;">
                    Buka Dokumen PDF di Tab Baru &rarr;
                  </a>
                  <a href="<?= e($buktiUrl) ?>" download class="btn btn-primary" style="padding: 8px 18px; font-size: 0.9rem;">
                    Unduh File PDF
                  </a>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Aksi untuk File Gambar -->
          <?php if ($isImg): ?>
            <div class="transaksi-bukti-actions no-print">
              <button type="button" class="btn btn-secondary js-btn-zoom-bukti" data-src="<?= e($buktiUrl) ?>" data-title="<?= e($transaksi['judul']) ?>" style="font-size: 0.85rem; padding: 7px 14px;">
                Perbesar Gambar Penuh
              </button>
              <a href="<?= e($buktiUrl) ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.85rem; padding: 7px 14px;">
                Buka di Tab Baru
              </a>
              <a href="<?= e($buktiUrl) ?>" download class="btn btn-primary" style="font-size: 0.85rem; padding: 7px 14px;">
                Unduh File Bukti
              </a>
            </div>
          <?php endif; ?>

        <?php else: ?>
          <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px dashed var(--border); border-radius: var(--radius-sm); margin-top: 10px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="color: var(--text-muted); margin-bottom: 8px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
            <div style="font-weight: 600; color: var(--text-main); font-size: 0.98rem;">Tidak Ada Lampiran Bukti Digital</div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 6px auto 0; max-width: 480px;">
              Transaksi kas ini dicatat tanpa mengunggah file bukti digital. Bukti fisik berupa nota/struk belanja disimpan secara manual di map arsip bendahara masjid.
            </p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  <?php endif; ?>

</div>

<?php require 'includes/footer.php'; ?>
