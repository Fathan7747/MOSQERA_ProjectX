CREATE DATABASE IF NOT EXISTS mosqera CHARACTER SET utf8mb4;
USE mosqera;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(30) DEFAULT 'jamaah',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS transaksi_kas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  jenis ENUM('masuk','keluar') NOT NULL,
  kategori VARCHAR(100) NOT NULL,
  judul VARCHAR(200) NOT NULL,
  nominal DECIMAL(15,2) NOT NULL,
  keterangan TEXT NULL,
  dibuat_oleh VARCHAR(100) DEFAULT 'Bendahara',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS program_agenda (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_program VARCHAR(200) NOT NULL,
  tanggal DATE NOT NULL,
  target_anggaran DECIMAL(15,2) NOT NULL,
  realisasi_anggaran DECIMAL(15,2) DEFAULT 0,
  status ENUM('Direncanakan','Berjalan','Selesai') DEFAULT 'Direncanakan',
  penanggung_jawab VARCHAR(100) NOT NULL,
  keterangan TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE berita (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(200) NOT NULL,
  isi TEXT NOT NULL,
  tanggal DATE NOT NULL
);
CREATE TABLE jadwal_sholat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(30) NOT NULL,
  waktu TIME NOT NULL
);
INSERT INTO jadwal_sholat (nama, waktu) VALUES
('Subuh','04:40'),('Dzuhur','12:00'),('Ashar','15:15'),('Maghrib','18:05'),('Isya','19:15');
INSERT INTO berita (judul, isi, tanggal) VALUES
('Kajian Rutin Ba''da Maghrib','Kajian tafsir setiap Selasa dan Kamis ba''da Maghrib. Terbuka untuk umum.', CURDATE()),
('Penggalangan Dana Renovasi Tempat Wudhu','Panitia membuka donasi renovasi tempat wudhu. Laporan penggunaan dana akan diumumkan tiap pekan.', CURDATE()),
('Jadwal Petugas Jumat Bulan Ini','Daftar khatib dan muadzin Jumat bulan ini sudah tersedia di papan pengumuman.', CURDATE());
