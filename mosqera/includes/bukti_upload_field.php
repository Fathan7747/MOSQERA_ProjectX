<?php
/**
 * MOSQERA - Komponen Reusable Input Upload Bukti Transaksi
 * Parameter opsional:
 * - $fieldLabel: Label teks di atas form input
 * - $fieldDesc: Keterangan deskripsi tambahan
 * - $fieldName: Atribut 'name' pada file input (default: 'bukti_file')
 * - $fieldId: ID unik kontainer upload
 */
$fieldLabel = $fieldLabel ?? 'Bukti Transaksi (Nota / Struk / Foto Fisik)';
$fieldDesc = $fieldDesc ?? 'Foto kuitansi, nota belanja fisik, struk transfer, atau dokumen pembayaran resmi.';
$fieldName = $fieldName ?? 'bukti_file';
$fieldId = $fieldId ?? ('buktiContainer_' . bin2hex(random_bytes(3)));
$inputId = 'fileInput_' . $fieldId;
?>
<div class="form-group">
  <label class="form-label">
    <?= e($fieldLabel) ?>
    <span style="font-size:0.82rem; font-weight:normal; color:var(--text-muted);">(Opsional)</span>
  </label>
  
  <div class="bukti-upload-container" id="<?= e($fieldId) ?>">
    <!-- Dropzone berbasis <label> yang secara native membuka dialog file saat diklik di browser manapun -->
    <label class="bukti-dropzone" for="<?= e($inputId) ?>" style="cursor:pointer; display:flex;">
      <!-- Input File Terhubung secara Native -->
      <input type="file" 
             id="<?= e($inputId) ?>" 
             name="<?= e($fieldName) ?>" 
             class="bukti-file-input" 
             accept=".jpg,.jpeg,.png,.pdf" 
             style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; overflow:hidden;">

      <div class="bukti-dropzone-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
          <polyline points="17 8 12 3 7 8"></polyline>
          <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
      </div>
      <div class="bukti-dropzone-title">Klik di Sini untuk Pilih File Bukti</div>
      <div class="bukti-dropzone-desc"><?= e($fieldDesc) ?></div>
      
      <div style="margin:12px 0 6px;">
        <span class="btn btn-secondary" style="pointer-events:none; padding:7px 18px; font-size:0.88rem; display:inline-flex; align-items:center; gap:7px; background:#ffffff; border:1.5px solid var(--border);">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          <span style="font-weight:600; color:var(--primary);">Pilih File Foto / Dokumen</span>
        </span>
      </div>

      <div class="bukti-dropzone-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>Format: JPG, JPEG, PNG, PDF &bull; Ukuran Maksimal 5 MB</span>
      </div>
    </label>

    <!-- Preview Card Setelah File Dipilih -->
    <div class="bukti-preview-card" style="display:none;">
      <div class="bukti-preview-media"></div>
      <div class="bukti-preview-meta">
        <div class="bukti-preview-filename">-</div>
        <div class="bukti-preview-sub">
          <span class="bukti-badge-tag bukti-badge-img">File Terpilih</span>
          <span class="bukti-preview-size">0 KB</span>
        </div>
      </div>
      <div class="bukti-preview-actions">
        <button type="button" class="btn-bukti-action btn-bukti-ganti" title="Ganti dengan file lain">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
          <span>Ganti</span>
        </button>
        <button type="button" class="btn-bukti-action remove btn-bukti-hapus" title="Hapus file bukti ini">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          <span>Hapus</span>
        </button>
      </div>
    </div>

    <!-- Kotak Pesan Error Validasi -->
    <div class="bukti-error-box" style="display:none;"></div>
  </div>
</div>
