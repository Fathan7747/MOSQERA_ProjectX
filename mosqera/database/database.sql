-- ==========================================================
-- MOSQERA: Sistem Informasi & Transparansi Keuangan Masjid
-- Skema Basis Data MySQL (Database Schema)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `mosqera` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mosqera`;

-- 1. Tabel Pengguna (Admin & Pengurus DKM)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(30) DEFAULT 'Pengurus',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Transaksi Buku Kas Masjid
CREATE TABLE IF NOT EXISTS `transaksi_kas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `jenis` ENUM('masuk','keluar') NOT NULL,
  `kategori` VARCHAR(100) NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `nominal` DECIMAL(15,2) NOT NULL,
  `keterangan` TEXT NULL,
  `dibuat_oleh` VARCHAR(100) DEFAULT 'Bendahara',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Jadwal 5 Waktu Sholat Fardhu
CREATE TABLE IF NOT EXISTS `jadwal_sholat` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(30) NOT NULL,
  `waktu` TIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Jadwal Khatib Sholat Jumat & Agenda Kajian
CREATE TABLE IF NOT EXISTS `jadwal_kegiatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `jenis` ENUM('Khatib Jumat','Kajian Mingguan','Kajian Tematik') NOT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `penceramah` VARCHAR(120) NOT NULL,
  `tanggal` DATE NOT NULL,
  `waktu` VARCHAR(50) NOT NULL,
  `lokasi` VARCHAR(100) DEFAULT 'Ruang Utama Masjid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Kampanye ZISWAF & Donasi Digital
CREATE TABLE IF NOT EXISTS `kampanye_donasi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `judul` VARCHAR(200) NOT NULL,
  `kategori` VARCHAR(80) NOT NULL,
  `target_dana` DECIMAL(15,2) NOT NULL,
  `terkumpul` DECIMAL(15,2) DEFAULT 0,
  `deskripsi` TEXT NULL,
  `batas_waktu` DATE NULL,
  `status` ENUM('Aktif','Selesai') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Tabel Riwayat Donasi Masuk dari Jamaah
CREATE TABLE IF NOT EXISTS `donasi_masuk` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kampanye_id` INT NULL,
  `nama_donatur` VARCHAR(100) NOT NULL,
  `nominal` DECIMAL(15,2) NOT NULL,
  `metode` VARCHAR(50) NOT NULL,
  `catatan_doa` TEXT NULL,
  `tanggal` DATE NOT NULL,
  `status` ENUM('Menunggu Verifikasi','Terverifikasi','Ditolak') DEFAULT 'Menunggu Verifikasi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabel Pengaturan Sistem Masjid
CREATE TABLE IF NOT EXISTS `pengaturan_masjid` (
  `kunci` VARCHAR(50) PRIMARY KEY,
  `nilai` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Tabel Pendaftaran Santri TPA (Arsip)
CREATE TABLE IF NOT EXISTS `pendaftaran_tpa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_santri` VARCHAR(100) NOT NULL,
  `umur` INT NOT NULL,
  `jenis_kelamin` ENUM('Laki-laki','Perempuan') NOT NULL,
  `nama_wali` VARCHAR(100) NOT NULL,
  `no_kontak` VARCHAR(30) NOT NULL,
  `alamat` TEXT NOT NULL,
  `kelas_pilihan` VARCHAR(50) NOT NULL,
  `status` ENUM('Menunggu Verifikasi','Diterima','Ditolak') DEFAULT 'Menunggu Verifikasi',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
