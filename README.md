# MOSQERA - Sistem Tata Kelola & Transparansi Keuangan Masjid

MOSQERA adalah platform sistem informasi dan tata kelola keuangan masjid terbuka, akuntabel, dan ramah lansia. Platform ini dirancang untuk memudahkan pengurus DKM (Dewan Kemakmuran Masjid) dalam pencatatan kas, jadwal ibadah/kajian, publikasi laporan mading dengan QR code, serta penerimaan infaq dan donasi digital (ZISWAF).

---

## 📁 Struktur Direktori & Arsitektur Proyek

```text
MOSQERA_ProjectX/
├── index.php                   # Gateway redirector root ke mosqera/index.php
├── README.md                   # Dokumentasi arsitektur & panduan sistem
└── mosqera/                    # Aplikasi Utama MOSQERA
    ├── config/                 # [Konfigurasi Sistem]
    │   ├── database.php        # Konfigurasi koneksi database PDO MySQL
    │   └── app.php             # Global bootstrap (session, timezone, auto-loader)
    ├── database/               # [Skema & Migrasi Database]
    │   ├── database.sql        # Skema DDL struktur tabel database
    │   └── db_init.php         # Inisialisasi otomatis tabel & data seeder bawaan
    ├── includes/               # [Komponen & Helper Core]
    │   ├── helpers.php         # Kumpulan fungsi pembantu (rupiah, sanitasi, kalkulasi kas)
    │   ├── header.php          # Komponen navigasi & header publik
    │   ├── footer.php          # Komponen footer publik & pemanggilan script
    │   └── db_init.php         # Bridge kompatibilitas ke database/db_init.php
    ├── assets/                 # [Aset Statis Antarmuka]
    │   ├── css/
    │   │   └── style.css       # Stylesheet terpadu (UI/UX, animasi reveal, modal, cetak)
    │   └── js/
    │       └── app.js          # Skrip interaktif (MosqeraUI, MosqeraScrollReveal, jadwal)
    ├── config.php              # Bridge loader kompatibilitas penuh ke config/app.php
    ├── index.php               # Halaman Beranda Publik (Hero, Jadwal Sholat, Saldo, Kampanye)
    ├── transparansi.php        # Halaman Transparansi Kas & Grafik Arus Kas Masjid
    ├── kampanye.php            # Halaman Program ZISWAF & Donasi Digital
    ├── kegiatan.php            # Halaman Jadwal 5 Waktu Sholat & Agenda Kajian/Khatib Jumat
    ├── admin.php               # Portal Pengurus Terpadu (Single-Page Portal dengan 5 Tab)
    ├── login.php               # Halaman Masuk Pengurus DKM
    ├── register.php            # Halaman Pendaftaran Akun Pengurus Baru
    ├── lupa_password.php       # Halaman Atur Ulang Kata Sandi Pengurus
    └── logout.php              # Handler Pembersihan Session & Keluar Pengurus
```

---

## 🛠️ Rincian Fungsi Setiap Folder

1. **`mosqera/config/`**
   - Mengisolasi konfigurasi sistem dari kode antarmuka.
   - `database.php`: Menangani koneksi PDO MySQL dengan error mode exception dan set karakter utf8mb4.
   - `app.php`: Mengatur session, zona waktu default, serta memuat database dan helper secara terpusat.

2. **`mosqera/database/`**
   - Menyimpan seluruh artefak basis data secara rapi.
   - `database.sql`: Berisi struktur tabel SQL murni untuk instalasi manual atau impor phpMyAdmin.
   - `db_init.php`: Otomasi pembuatan tabel dan data contoh (*seeder*) secara aman saat pertama kali aplikasi dijalankan.

3. **`mosqera/includes/`**
   - Berisi komponen tampilan yang dapat digunakan kembali (*reusable partials*) dan fungsi inti.
   - `helpers.php`: Fungsi global seperti `rupiah()`, sanitasi `e()`, `pengaturan()`, dan ringkasan kas.
   - `header.php` & `footer.php`: Kerangka antarmuka pengguna halaman publik.

4. **`mosqera/assets/`**
   - Folder aset frontend terpisah:
     - `css/style.css`: Palet warna hijau masjid (`#0f3a34`), tipografi ramah lansia, layout grid responsif, modal pop-up, notifikasi toast, serta animasi scroll reveal.
     - `js/app.js`: Logika interaktif modal konfirmasi, notifikasi, hitung mundur waktu sholat otomatis, dan pengamat scroll reveal (*IntersectionObserver*).

5. **Root Aplikasi (`mosqera/*.php`)**
   - Merupakan titik masuk (*entry point*) untuk setiap fitur utama website yang dapat diakses langsung melalui URL browser tanpa dependensi file usang.

---

## 🚀 Panduan Menjalankan Website

1. Pastikan modul **Apache** dan **MySQL** aktif pada XAMPP Control Panel.
2. Tempatkan folder proyek di dalam direktori `htdocs`, misal: `c:\xampp\htdocs\MOSQERA_ProjectX`.
3. Buka browser dan akses salah satu alamat berikut:
   - `http://localhost/MOSQERA_ProjectX/` (Otomatis diarahkan ke halaman utama)
   - `http://localhost/MOSQERA_ProjectX/mosqera/index.php`
4. Untuk masuk ke Portal Pengurus:
   - Akses `http://localhost/MOSQERA_ProjectX/mosqera/login.php`
   - Default Akun Pengurus:
     - **Email:** `pengurus@mosqera.id`
     - **Password:** `password123`