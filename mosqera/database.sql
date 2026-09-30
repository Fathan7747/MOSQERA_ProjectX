CREATE DATABASE IF NOT EXISTS mosqera CHARACTER SET utf8mb4;
USE mosqera;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','jamaah') DEFAULT 'jamaah',
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
