-- Add bukti_file column to transaksi_kas
ALTER TABLE `transaksi_kas` ADD COLUMN IF NOT EXISTS `bukti_file` VARCHAR(255) NULL AFTER `keterangan`;

-- Add bukti_file column to donasi_masuk  
ALTER TABLE `donasi_masuk` ADD COLUMN IF NOT EXISTS `bukti_file` VARCHAR(255) NULL AFTER `catatan_doa`;
