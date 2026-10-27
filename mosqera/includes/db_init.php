<?php
// Otomatis inisialisasi tabel pendukung jika belum ada
try {
    // 1. Tabel users
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(150) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'Pengurus',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    // Akun default admin/pengurus jika tabel users masih kosong
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $pwDefault = password_hash('password123', PASSWORD_DEFAULT);
        $insUser = $db->prepare("INSERT INTO users (nama, email, password, role) VALUES (?,?,?,?)");
        $insUser->execute(['Pengurus DKM Masjid', 'pengurus@mosqera.id', $pwDefault, 'Pengurus']);
        $insUser->execute(['H. Ahmad Fauzi', 'bendahara@mosqera.id', $pwDefault, 'Pengurus']);
    }

    // 2. Tabel jadwal_sholat
    $db->exec("CREATE TABLE IF NOT EXISTS jadwal_sholat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(50) NOT NULL,
        waktu TIME NOT NULL
    ) CHARACTER SET utf8mb4");

    $countJadwal = $db->query("SELECT COUNT(*) FROM jadwal_sholat")->fetchColumn();
    if ($countJadwal == 0) {
        $db->exec("INSERT INTO jadwal_sholat (nama, waktu) VALUES
        ('Subuh', '04:42:00'),
        ('Terbit', '05:58:00'),
        ('Dzuhur', '12:03:00'),
        ('Ashar', '15:18:00'),
        ('Maghrib', '18:08:00'),
        ('Isya', '19:18:00')");
    }

    // 3. Tabel transaksi_kas
    $db->exec("CREATE TABLE IF NOT EXISTS transaksi_kas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATE NOT NULL,
        jenis ENUM('masuk','keluar') NOT NULL,
        kategori VARCHAR(100) NOT NULL,
        judul VARCHAR(200) NOT NULL,
        nominal DECIMAL(15,2) NOT NULL,
        keterangan TEXT NULL,
        bukti_nota VARCHAR(255) NULL,
        dibuat_oleh VARCHAR(100) DEFAULT 'Bendahara',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    $countKas = $db->query("SELECT COUNT(*) FROM transaksi_kas")->fetchColumn();
    if ($countKas == 0) {
        $db->exec("INSERT INTO transaksi_kas (tanggal, jenis, kategori, judul, nominal, keterangan, dibuat_oleh) VALUES
        (CURDATE() - INTERVAL 12 DAY, 'masuk', 'Tromol Jumat', 'Infaq Kotak Amal Sholat Jumat Pekan ke-1', 4250000, 'Perhitungan bersama pengurus dan saksi jamaah', 'Bendahara'),
        (CURDATE() - INTERVAL 10 DAY, 'masuk', 'Donasi Transfer', 'Donasi Pengadaan Fasilitas dari Jamaah Hamba Allah', 2500000, 'Transfer via rekening BSI Masjid', 'Bendahara'),
        (CURDATE() - INTERVAL 8 DAY, 'keluar', 'Operasional Masjid', 'Biaya Listrik PLN, Air PDAM & Internet Masjid', 1250000, 'Nota resmi PLN & PDAM terlampir di sekretariat', 'Bendahara'),
        (CURDATE() - INTERVAL 6 DAY, 'masuk', 'ZISWAF', 'Penerimaan Infaq & Sedekah Terikat Beasiswa', 3000000, 'Diterima melalui QRIS resmi masjid', 'Bendahara'),
        (CURDATE() - INTERVAL 5 DAY, 'keluar', 'Bantuan Sosial', 'Santunan Sembako & Biaya Hidup 15 Mustahik Lingkungan', 1500000, 'Daftar penerima ditandatangani Ketua RT', 'Bendahara'),
        (CURDATE() - INTERVAL 3 DAY, 'keluar', 'Operasional Masjid', 'Honor & Transport Pemateri Kajian Rutin Sabtu', 600000, 'Kajian tafsir Al-Qur\'an', 'Bendahara'),
        (CURDATE() - INTERVAL 1 DAY, 'masuk', 'Tromol Jumat', 'Infaq Kotak Amal Sholat Jumat Pekan ke-2', 4850000, 'Kotak tromol utama dan sayap akhwat', 'Bendahara')");
    }

    // 4. Tabel kampanye_donasi
    $db->exec("CREATE TABLE IF NOT EXISTS kampanye_donasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        judul VARCHAR(255) NOT NULL,
        kategori VARCHAR(100) DEFAULT 'Fasilitas',
        target_dana DECIMAL(15,2) NOT NULL,
        terkumpul DECIMAL(15,2) DEFAULT 0,
        batas_waktu DATE NULL,
        deskripsi TEXT NULL,
        status ENUM('Aktif', 'Selesai') DEFAULT 'Aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    $countKampanye = $db->query("SELECT COUNT(*) FROM kampanye_donasi")->fetchColumn();
    if ($countKampanye == 0) {
        $db->exec("INSERT INTO kampanye_donasi (judul, kategori, target_dana, terkumpul, batas_waktu, deskripsi, status) VALUES
        ('Patungan Pengadaan 4 Unit AC Ruang Utama', 'Fasilitas Masjid', 18000000, 13500000, CURDATE() + INTERVAL 25 DAY, 'Meningkatkan kenyamanan ibadah sholat berjamaah dan pengajian dengan penambahan pendingin ruangan inverter hemat daya.', 'Aktif'),
        ('Beasiswa & Pembinaan Santri Yatim / Dhuafa', 'Pendidikan & Sosial', 12000000, 8400000, CURDATE() + INTERVAL 40 DAY, 'Bantuan biaya SPP, perlengkapan belajar, dan seragam mengaji untuk 25 santri berprestasi dari keluarga prasejahtera.', 'Aktif'),
        ('Renovasi & Perluasan Tempat Wudhu Ikhwan-Akhwat', 'Infrastruktur', 25000000, 21800000, CURDATE() + INTERVAL 15 DAY, 'Perbaikan saluran pipa air, penambahan kran wudhu duduk ramah lansia, dan pemasangan lantai keramik antiselip.', 'Aktif')");
    }

    // 5. Tabel donasi_masuk
    $db->exec("CREATE TABLE IF NOT EXISTS donasi_masuk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kampanye_id INT NULL,
        nama_donatur VARCHAR(150) NOT NULL,
        nominal DECIMAL(15,2) NOT NULL,
        metode ENUM('QRIS', 'Transfer Bank', 'Tunai') DEFAULT 'QRIS',
        catatan_doa TEXT NULL,
        status ENUM('Terverifikasi', 'Menunggu') DEFAULT 'Terverifikasi',
        tanggal DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    $countDonasi = $db->query("SELECT COUNT(*) FROM donasi_masuk")->fetchColumn();
    if ($countDonasi == 0) {
        $db->exec("INSERT INTO donasi_masuk (kampanye_id, nama_donatur, nominal, metode, catatan_doa, status, tanggal) VALUES
        (1, 'Hamba Allah', 500000, 'QRIS', 'Semoga menjadi amal jariyah untuk keluarga', 'Terverifikasi', CURDATE() - INTERVAL 4 DAY),
        (1, 'Keluarga Bpk. Bambang Sutrisno', 2000000, 'Transfer Bank', 'Mohon doa kelancaran usaha dan kesehatan anak cucu', 'Terverifikasi', CURDATE() - INTERVAL 3 DAY),
        (2, 'Ibu Hj. Aminah', 1000000, 'Transfer Bank', 'Untuk beasiswa anak-anak penghafal Al-Qur\'an', 'Terverifikasi', CURDATE() - INTERVAL 2 DAY),
        (3, 'Jamaah Subuh', 1500000, 'QRIS', 'Bismillah semoga cepat terwujud wudhu yang nyaman', 'Terverifikasi', CURDATE() - INTERVAL 1 DAY)");
    }

    // 6. Tabel jadwal_kegiatan
    $db->exec("CREATE TABLE IF NOT EXISTS jadwal_kegiatan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jenis ENUM('Khatib Jumat', 'Kajian Mingguan', 'Kajian Tematik') NOT NULL,
        judul VARCHAR(255) NOT NULL,
        penceramah VARCHAR(150) NOT NULL,
        tanggal DATE NOT NULL,
        waktu VARCHAR(50) NOT NULL,
        lokasi VARCHAR(150) DEFAULT 'Ruang Utama Masjid',
        keterangan TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    $countKegiatan = $db->query("SELECT COUNT(*) FROM jadwal_kegiatan")->fetchColumn();
    if ($countKegiatan == 0) {
        $db->exec("INSERT INTO jadwal_kegiatan (jenis, judul, penceramah, tanggal, waktu, lokasi, keterangan) VALUES
        ('Khatib Jumat', 'Khutbah Sholat Jumat: Menjaga Integritas & Amanah Umat', 'K.H. Dr. Ahmad Ridwan, Lc., M.A.', CURDATE() + INTERVAL (5 - DAYOFWEEK(CURDATE()) + 7) % 7 DAY, '11:45 WIB - Selesai', 'Mimbar Utama', 'Muadzin: Ust. Fathurrahman | Sholat tepat waktu'),
        ('Kajian Mingguan', 'Kajian Rutin Tafsir Ayat Ahkam & Fiqih Muamalah', 'Ust. Muhammad Ziyad, M.Ag.', CURDATE() + INTERVAL 3 DAY, 'Ba''da Maghrib - Isya', 'Ruang Utama Masjid', 'Terbuka untuk ikhwan dan akhwat. Disertai sesi tanya jawab.'),
        ('Kajian Tematik', 'Tabligh Akbar: Membangun Keluarga Sakinah Berkarakter Qurani', 'Ust. Hilman Fauzi, S.E.I., M.Si.', CURDATE() + INTERVAL 8 DAY, '08:30 - 11:30 WIB', 'Halaman & Ruang Utama Masjid', 'Disediakan konsumsi dan tempat terpisah untuk akhwat.')");
    }

    // 7. Tabel pendaftaran_tpa
    $db->exec("CREATE TABLE IF NOT EXISTS pendaftaran_tpa (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_santri VARCHAR(150) NOT NULL,
        umur INT NOT NULL,
        jenis_kelamin ENUM('Laki-laki', 'Perempuan') NOT NULL,
        nama_wali VARCHAR(150) NOT NULL,
        no_kontak VARCHAR(50) NOT NULL,
        alamat TEXT NULL,
        kelas_pilihan VARCHAR(50) DEFAULT 'Iqro Awal',
        status ENUM('Menunggu Verifikasi', 'Diterima', 'Aktif') DEFAULT 'Menunggu Verifikasi',
        tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4");

    $countTPA = $db->query("SELECT COUNT(*) FROM pendaftaran_tpa")->fetchColumn();
    if ($countTPA == 0) {
        $db->exec("INSERT INTO pendaftaran_tpa (nama_santri, umur, jenis_kelamin, nama_wali, no_kontak, alamat, kelas_pilihan, status) VALUES
        ('Muhammad Al-Fatih', 7, 'Laki-laki', 'Fauzan Hidayat', '081298765432', 'Jl. Kenanga No. 12', 'Iqro Awal', 'Diterima'),
        ('Aisyah Nur Ramadhani', 8, 'Perempuan', 'Budi Santoso', '085712345678', 'Kompleks Baiturrahim Blok C No. 4', 'Al-Qur\'an Tajwid', 'Diterima'),
        ('Zaid bin Tsabit', 6, 'Laki-laki', 'Rudi Hermawan', '082188776655', 'Jl. Melati RT 03/05', 'Iqro Awal', 'Menunggu Verifikasi')");
    }

    // 8. Tabel pengaturan_masjid
    $db->exec("CREATE TABLE IF NOT EXISTS pengaturan_masjid (
        kunci VARCHAR(50) PRIMARY KEY,
        nilai TEXT NULL
    ) CHARACTER SET utf8mb4");

    $countPengaturan = $db->query("SELECT COUNT(*) FROM pengaturan_masjid")->fetchColumn();
    if ($countPengaturan == 0) {
        $db->exec("INSERT INTO pengaturan_masjid (kunci, nilai) VALUES
        ('nama_masjid', 'Masjid Baiturrahim'),
        ('alamat', 'Jl. Jend. Sudirman No.70, Rembiga, Kec. Selaparang, Kota Mataram'),
        ('telepon', '0812-3456-7890'),
        ('email', 'sekretariat@mosqera.id'),
        ('bank_info', 'Bank Syariah Indonesia (BSI) No. Rekening: 7123-456-789 a.n Kas DKM Masjid Baiturrahim'),
        ('qris_label', 'QRIS Standar Pembayaran Nasional (NMID: ID1029384756)'),
        ('kota_jadwal', 'Jakarta Selatan')");
    }

} catch (Exception $e) {
    // Penanganan senyap agar sistem tetap berjalan jika terjadi pembatasan hak akses
}
