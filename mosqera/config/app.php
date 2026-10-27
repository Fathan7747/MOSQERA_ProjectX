<?php
/**
 * MOSQERA - Konfigurasi Utama & Bootstrap Aplikasi
 * Menginisialisasi session, koneksi database, helper global, dan database seeder.
 */

// 1. Inisialisasi Session PHP Aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Pengaturan Zona Waktu Standar Indonesia
date_default_timezone_set('Asia/Makassar');

// 3. Muat Konfigurasi Koneksi Database PDO
require_once __DIR__ . '/database.php';

// 4. Muat Kumpulan Fungsi Helper Global
require_once __DIR__ . '/../includes/helpers.php';

// 5. Muat Inisialisasi Otomatis Tabel & Seeder Database
require_once __DIR__ . '/../database/db_init.php';
