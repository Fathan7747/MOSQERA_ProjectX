<?php
// Otomatis inisialisasi tabel pendukung jika belum ada
try {
    // 1. Pastikan kolom role di tabel users mendukung bendahara & pengurus
    $db->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(30) DEFAULT 'jamaah'");
} catch (Exception $e) {
    // Abaikan jika sudah sesuai
}

try {
    // 2. Tabel transaksi kas masuk & keluar
    $db->exec("CREATE TABLE IF NOT EXISTS transaksi_kas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        jenis ENUM('masuk','keluar') NOT NULL,
        kategori VARCHAR(100) NOT NULL,
        judul VARCHAR(200) NOT NULL,
        nominal DECIMAL(15,2) NOT NULL,
        keterangan TEXT NULL,
        dibuat_oleh VARCHAR(100) DEFAULT 'Bendahara',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    // Isi sampel transaksi kas jika masih kosong
    $countKas = $db->query("SELECT COUNT(*) FROM transaksi_kas")->fetchColumn();
    if ($countKas == 0) {
        $db->exec("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, dibuat_oleh) VALUES
        (CURDATE() - INTERVAL 10 DAY, 'masuk', 'Infaq Jumat', 'Infaq Kotak Amal Sholat Jumat Pekan 1', 3750000, 'Kotak infaq terhitung bersama pengurus', 'Bendahara'),
        (CURDATE() - INTERVAL 8 DAY, 'masuk', 'Zakat & Sedekah', 'Donasi Sedekah Hamba Allah untuk Operasional', 1500000, 'Transfer donasi via rekening masjid', 'Bendahara'),
        (CURDATE() - INTERVAL 7 DAY, 'keluar', 'Operasional', 'Pembayaran Listrik & Kebersihan Masjid', 850000, 'Nota terlampir di sekretariat', 'Bendahara'),
        (CURDATE() - INTERVAL 4 DAY, 'masuk', 'Pembangunan', 'Wakaf Tunai Pembebasan Lahan & Wudhu', 5000000, 'Donasi langsung tunai dari jamaah', 'Bendahara'),
        (CURDATE() - INTERVAL 3 DAY, 'keluar', 'Sosial & Dakwah', 'Konsumsi & Honor Ustadz Kajian Rutin', 600000, 'Kegiatan kajian tafsir', 'Bendahara'),
        (CURDATE() - INTERVAL 1 DAY, 'masuk', 'Infaq Jumat', 'Infaq Kotak Amal Sholat Jumat Pekan 2', 4200000, 'Penghitungan terbuka ba''da jumat', 'Bendahara')");
    }

    // 3. Tabel program & agenda kegiatan masjid yang butuh anggaran
    $db->exec("CREATE TABLE IF NOT EXISTS program_agenda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_program VARCHAR(200) NOT NULL,
        tanggal DATE NOT NULL,
        target_anggaran DECIMAL(15,2) NOT NULL,
        realisasi_anggaran DECIMAL(15,2) DEFAULT 0,
        status ENUM('Direncanakan','Berjalan','Selesai') DEFAULT 'Direncanakan',
        penanggung_jawab VARCHAR(100) NOT NULL,
        keterangan TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    // Isi sampel program agenda jika masih kosong
    $countAgenda = $db->query("SELECT COUNT(*) FROM program_agenda")->fetchColumn();
    if ($countAgenda == 0) {
        $db->exec("INSERT INTO program_agenda (nama_program, tanggal, target_anggaran, realisasi_anggaran, status, penanggung_jawab, keterangan) VALUES
        ('Renovasi Tempat Wudhu & Kamar Mandi', CURDATE() + INTERVAL 14 DAY, 15000000, 5000000, 'Berjalan', 'Seksi Pembangunan', 'Peningkatan kenyamanan fasilitas wudhu ikhwan & akhwat.'),
        ('Peringatan Maulid Nabi & Tabligh Akbar', CURDATE() + INTERVAL 25 DAY, 8000000, 2000000, 'Direncanakan', 'Seksi Dakwah', 'Mengundang da''i kondang dan santunan 50 anak yatim.'),
        ('Pengadaan Sound System & Genset Cadangan', CURDATE() - INTERVAL 5 DAY, 6500000, 6500000, 'Selesai', 'Seksi Perlengkapan', 'Pemasangan mixer baru dan perawatan speaker utama masjid.')");
    }
} catch (Exception $e) {
    // Biarkan aplikasi tetap berjalan bila terjadi masalah koneksi khusus
}
