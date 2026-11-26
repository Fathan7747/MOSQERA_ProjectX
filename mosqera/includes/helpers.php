<?php
/**
 * MOSQERA - Kumpulan Fungsi Helper Global
 * Berisi fungsi utilitas sanitasi data, format mata uang, jadwal sholat, kalkulasi kas, dan pengaturan.
 */

if (!function_exists('e')) {
    /**
     * Sanitasi output string untuk mencegah kerentanan XSS.
     */
    function e($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rupiah')) {
    /**
     * Format angka desimal ke standar format mata uang Rupiah (Rp).
     */
    function rupiah($angka) {
        return 'Rp ' . number_format((float)($angka ?? 0), 0, ',', '.');
    }
}

if (!function_exists('jadwal')) {
    /**
     * Mengambil daftar jadwal waktu sholat dari database, dengan sinkronisasi harian otomatis dan fallback.
     */
    function jadwal($db) {
        $fallback = [
            ['nama' => 'Subuh', 'waktu' => '04:49'],
            ['nama' => 'Terbit', 'waktu' => '06:03'],
            ['nama' => 'Dzuhur', 'waktu' => '12:15'],
            ['nama' => 'Ashar', 'waktu' => '15:36'],
            ['nama' => 'Maghrib', 'waktu' => '18:22'],
            ['nama' => 'Isya', 'waktu' => '19:33']
        ];
        try {
            if (function_exists('sinkron_jadwal_sholat')) {
                sinkron_jadwal_sholat($db, false);
            }
            $rows = $db->query("SELECT nama, TIME_FORMAT(waktu,'%H:%i') AS waktu FROM jadwal_sholat ORDER BY waktu")->fetchAll();
            if (!empty($rows) && count($rows) > 0) {
                return $rows;
            }
        } catch (Exception $e) {}
        return $fallback;
    }
}

if (!function_exists('ringkasan_kas')) {
    /**
     * Mengkalkulasi total uang masuk, total uang keluar, dan sisa saldo kas kumulatif.
     */
    function ringkasan_kas($db) {
        try {
            $masuk = $db->query("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='masuk'")->fetchColumn();
            $keluar = $db->query("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='keluar'")->fetchColumn();
            return [
                'masuk' => (float)$masuk,
                'keluar' => (float)$keluar,
                'saldo' => (float)($masuk - $keluar)
            ];
        } catch (Exception $e) {
            return ['masuk' => 0, 'keluar' => 0, 'saldo' => 0];
        }
    }
}

if (!function_exists('ringkasan_kas_bulan')) {
    /**
     * Mengkalkulasi mutasi uang masuk dan uang keluar pada bulan tertentu.
     */
    function ringkasan_kas_bulan($db, $bulan = null, $tahun = null) {
        if (!$bulan) $bulan = date('m');
        if (!$tahun) $tahun = date('Y');
        try {
            $stmtMasuk = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='masuk' AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
            $stmtMasuk->execute([$bulan, $tahun]);
            $masuk = (float)$stmtMasuk->fetchColumn();

            $stmtKeluar = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='keluar' AND MONTH(tanggal)=? AND YEAR(tanggal)=?");
            $stmtKeluar->execute([$bulan, $tahun]);
            $keluar = (float)$stmtKeluar->fetchColumn();

            // Saldo kumulatif hingga akhir bulan ini
            $stmtKumulatifMasuk = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='masuk' AND tanggal <= LAST_DAY(?)");
            $stmtKumulatifMasuk->execute(["$tahun-$bulan-01"]);
            $totMasuk = (float)$stmtKumulatifMasuk->fetchColumn();

            $stmtKumulatifKeluar = $db->prepare("SELECT COALESCE(SUM(nominal),0) FROM transaksi_kas WHERE jenis='keluar' AND tanggal <= LAST_DAY(?)");
            $stmtKumulatifKeluar->execute(["$tahun-$bulan-01"]);
            $totKeluar = (float)$stmtKumulatifKeluar->fetchColumn();

            return [
                'masuk_bulan' => $masuk,
                'keluar_bulan' => $keluar,
                'saldo_akhir' => $totMasuk - $totKeluar
            ];
        } catch (Exception $e) {
            return ['masuk_bulan' => 0, 'keluar_bulan' => 0, 'saldo_akhir' => 0];
        }
    }
}

if (!function_exists('pengaturan')) {
    /**
     * Mengambil nilai pengaturan masjid berdasarkan kata kunci (kunci).
     */
    function pengaturan($db, $kunci, $default = '') {
        try {
            $stmt = $db->prepare("SELECT nilai FROM pengaturan_masjid WHERE kunci=?");
            $stmt->execute([$kunci]);
            $v = $stmt->fetchColumn();
            return $v !== false ? $v : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('simpan_bukti_transaksi')) {
    /**
     * Memvalidasi dan menyimpan file bukti transaksi ke folder uploads.
     * Mendukung format JPG, JPEG, PNG, dan PDF dengan batas ukuran maksimal 5 MB.
     *
     * @param string $inputName Nama field input file di form ($_FILES[$inputName])
     * @param string $prefix Prefix penamaan file bukti di server
     * @return array ['success' => bool, 'filename' => ?string, 'error' => ?string]
     */
    function simpan_bukti_transaksi($inputName = 'bukti_file', $prefix = 'bukti_') {
        if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName])) {
            return ['success' => true, 'filename' => null, 'error' => null];
        }

        $file = $_FILES[$inputName];

        // Jika tidak ada file yang dipilih oleh user
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => null, 'error' => null];
        }

        // Cek error upload dasar dari PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = match($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas maksimal server (Maks. 5 MB).',
                UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian. Silakan coba lagi.',
                default => 'Gagal mengunggah file bukti (Kode error: ' . $file['error'] . ').'
            };
            return ['success' => false, 'filename' => null, 'error' => $msg];
        }

        // Validasi ukuran maksimal (5 MB = 5 * 1024 * 1024 bytes)
        $maxBytes = 5 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            return ['success' => false, 'filename' => null, 'error' => 'Ukuran file terlalu besar (' . format_bytes($file['size']) . '). Batas maksimal adalah 5 MB.'];
        }

        // Validasi ekstensi file
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowedExts, true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Format file .' . $ext . ' tidak didukung. Harap gunakan format JPG, PNG, atau PDF.'];
        }

        // Validasi MIME type berbasis konten biner file
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = [
                'image/jpeg',
                'image/pjpeg',
                'image/png',
                'image/x-png',
                'application/pdf',
                'application/x-pdf'
            ];

            if (!in_array($mime, $allowedMimes, true)) {
                return ['success' => false, 'filename' => null, 'error' => 'Tipe konten file tidak diizinkan (' . e($mime) . '). Harap unggah foto bukti (JPG/PNG) atau dokumen PDF.'];
            }
        }

        // Buat folder penyimpanan jika belum ada
        $uploadDir = __DIR__ . '/../assets/uploads/bukti/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // Pastikan .htaccess keamanan ada di folder uploads/bukti
        $htaccessPath = $uploadDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Prevent script execution\n<FilesMatch \"\.(php|phtml|php3|php4|php5|pl|py|cgi|sh|bash)$\">\n    Deny from all\n</FilesMatch>\nOptions -Indexes\n";
            @file_put_contents($htaccessPath, $htaccessContent);
        }

        // Generate nama file acak yang unik dan aman
        $randomHex = bin2hex(random_bytes(6));
        $filename = $prefix . date('Ymd_His') . '_' . $randomHex . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        // Pindahkan file ke direktori tujuan (mendukung HTTP Upload & CLI Testing)
        $saved = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $saved = move_uploaded_file($file['tmp_name'], $targetPath);
        } else {
            $saved = @rename($file['tmp_name'], $targetPath) || @copy($file['tmp_name'], $targetPath);
        }

        if (!$saved) {
            return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan file bukti ke penyimpanan server. Periksa hak akses folder.'];
        }

        return ['success' => true, 'filename' => $filename, 'error' => null];
    }
}

if (!function_exists('hapus_bukti_transaksi')) {
    /**
     * Menghapus file bukti fisik dari folder uploads/bukti.
     *
     * @param string|null $filename Nama file yang tersimpan di database
     * @return bool True jika file berhasil dihapus atau memang tidak ada
     */
    function hapus_bukti_transaksi($filename) {
        if (empty($filename)) return true;
        $safeName = basename($filename);
        $filePath = __DIR__ . '/../assets/uploads/bukti/' . $safeName;
        if (file_exists($filePath) && is_file($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }
}

if (!function_exists('url_bukti_transaksi')) {
    /**
     * Mengembalikan URL publik untuk mengakses file bukti transaksi.
     *
     * @param string|null $filename
     * @return string
     */
    function url_bukti_transaksi($filename) {
        if (empty($filename)) return '';
        $safeName = basename($filename);
        return 'assets/uploads/bukti/' . rawurlencode($safeName);
    }
}

if (!function_exists('file_bukti_tersedia')) {
    /**
     * Mengecek apakah file bukti fisik benar-benar ada di server.
     *
     * @param string|null $filename
     * @return bool
     */
    function file_bukti_tersedia($filename) {
        if (empty($filename)) return false;
        $safeName = basename($filename);
        return is_file(__DIR__ . '/../assets/uploads/bukti/' . $safeName);
    }
}

if (!function_exists('is_image_bukti')) {
    /**
     * Cek apakah nama file adalah gambar (JPG/PNG).
     */
    function is_image_bukti($filename) {
        if (empty($filename)) return false;
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    }
}

if (!function_exists('is_pdf_bukti')) {
    /**
     * Cek apakah nama file adalah dokumen PDF.
     */
    function is_pdf_bukti($filename) {
        if (empty($filename)) return false;
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $ext === 'pdf';
    }
}

if (!function_exists('format_bytes')) {
    /**
     * Format ukuran byte menjadi format yang mudah dibaca manusia (KB, MB).
     */
    function format_bytes($bytes, $precision = 1) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('simpan_pengaturan')) {
    /**
     * Menyimpan atau memperbarui satu entri pengaturan masjid.
     */
    function simpan_pengaturan($db, $kunci, $nilai) {
        $stmt = $db->prepare("INSERT INTO pengaturan_masjid (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai)");
        return $stmt->execute([$kunci, $nilai]);
    }
}

if (!function_exists('zona_waktu_label')) {
    /**
     * Mengembalikan label singkatan zona waktu masjid (WIB, WITA, atau WIT).
     */
    function zona_waktu_label($db) {
        $z = strtoupper(trim(pengaturan($db, 'zona_waktu', 'WITA')));
        return in_array($z, ['WIB', 'WITA', 'WIT'], true) ? $z : 'WITA';
    }
}

if (!function_exists('zona_waktu_lengkap')) {
    /**
     * Mengembalikan nama lengkap zona waktu Indonesia berdasarkan kode singkat.
     */
    function zona_waktu_lengkap($zona) {
        return match(strtoupper(trim($zona))) {
            'WIB'  => 'Waktu Indonesia Barat',
            'WIT'  => 'Waktu Indonesia Timur',
            default => 'Waktu Indonesia Tengah',
        };
    }
}

if (!function_exists('bulan_indo')) {
    /**
     * Mengembalikan nama bulan dan tahun dalam Bahasa Indonesia (contoh: November 2026).
     */
    function bulan_indo($timestamp = null) {
        $ts = $timestamp ?? time();
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $m = (int)date('n', $ts);
        return ($bulan[$m] ?? date('F', $ts)) . ' ' . date('Y', $ts);
    }
}

if (!function_exists('tanggal_indo')) {
    /**
     * Memformat tanggal dalam Bahasa Indonesia (opsional dengan nama hari).
     */
    function tanggal_indo($timestamp = null, $withDay = false) {
        $ts = $timestamp ?? time();
        $hari = [
            'Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa',
            'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'
        ];
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $tgl = date('d', $ts) . ' ' . ($bulan[(int)date('n', $ts)] ?? date('F', $ts)) . ' ' . date('Y', $ts);
        if ($withDay) {
            return ($hari[date('D', $ts)] ?? date('l', $ts)) . ', ' . $tgl;
        }
        return $tgl;
    }
}

if (!function_exists('sinkron_jadwal_sholat')) {
    /**
     * Menyinkronkan jadwal sholat harian otomatis dari API Kemenag RI (Aladhan Method 20)
     * berdasarkan kota pada pengaturan masjid, dengan cache harian agar akses tetap cepat.
     */
    function sinkron_jadwal_sholat($db, $force = false) {
        $kota = trim(pengaturan($db, 'kota_jadwal', 'Kota Mataram'));
        if ($kota === '') $kota = 'Kota Mataram';

        $today = date('Y-m-d');
        $lastSyncDate = pengaturan($db, 'jadwal_sync_date', '');
        $lastSyncKota = pengaturan($db, 'jadwal_sync_kota', '');

        if (!$force && $lastSyncDate === $today && $lastSyncKota === $kota) {
            return ['success' => true, 'synced' => false, 'message' => 'Jadwal sholat sudah mutakhir untuk hari ini.'];
        }

        // Hindari mencoba ulang terus-menerus bila perangkat sedang offline
        if (!$force && !empty($_SESSION['jadwal_sync_fail_at']) && (time() - (int)$_SESSION['jadwal_sync_fail_at']) < 600) {
            return ['success' => false, 'synced' => false, 'message' => 'Menunggu koneksi untuk sinkronisasi otomatis.'];
        }

        $cityQuery = trim(preg_replace('/^(kota|kabupaten|kab\.?)\s+/i', '', $kota));
        if ($cityQuery === '') $cityQuery = 'Mataram';

        $url = 'https://api.aladhan.com/v1/timingsByCity?city=' . rawurlencode($cityQuery) . '&country=Indonesia&method=20';
        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'MOSQERA/1.0'
            ]);
            $res = @curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res !== false && $code >= 200 && $code < 300) {
                $raw = $res;
            }
        }

        if ($raw === false && ini_get('allow_url_fopen')) {
            $ctx = stream_context_create([
                'http' => ['timeout' => 3, 'user_agent' => 'MOSQERA/1.0'],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            $raw = @file_get_contents($url, false, $ctx);
        }

        if ($raw === false) {
            $_SESSION['jadwal_sync_fail_at'] = time();
            return ['success' => false, 'synced' => false, 'message' => 'Gagal terhubung ke layanan jadwal sholat. Menggunakan jadwal tersimpan di database.'];
        }

        $json = json_decode($raw, true);
        $timings = $json['data']['timings'] ?? null;
        if (!is_array($timings)) {
            $_SESSION['jadwal_sync_fail_at'] = time();
            return ['success' => false, 'synced' => false, 'message' => 'Format respons jadwal sholat tidak valid.'];
        }

        $map = [
            'Subuh'   => $timings['Fajr'] ?? '',
            'Terbit'  => $timings['Sunrise'] ?? '',
            'Dzuhur'  => $timings['Dhuhr'] ?? '',
            'Ashar'   => $timings['Asr'] ?? '',
            'Maghrib' => $timings['Maghrib'] ?? '',
            'Isya'    => $timings['Isha'] ?? '',
        ];

        $parsed = [];
        foreach ($map as $nama => $jam) {
            $hhmm = substr(trim((string)$jam), 0, 5);
            if (!preg_match('/^\d{2}:\d{2}$/', $hhmm)) {
                $_SESSION['jadwal_sync_fail_at'] = time();
                return ['success' => false, 'synced' => false, 'message' => 'Data jam sholat dari API tidak lengkap.'];
            }
            $parsed[$nama] = $hhmm . ':00';
        }

        $stmtCheck = $db->prepare("SELECT id FROM jadwal_sholat WHERE nama=? LIMIT 1");
        $stmtUpd = $db->prepare("UPDATE jadwal_sholat SET waktu=? WHERE id=?");
        $stmtIns = $db->prepare("INSERT INTO jadwal_sholat (nama, waktu) VALUES (?, ?)");

        foreach ($parsed as $nama => $waktuDb) {
            $stmtCheck->execute([$nama]);
            $rowId = $stmtCheck->fetchColumn();
            if ($rowId) {
                $stmtUpd->execute([$waktuDb, $rowId]);
            } else {
                $stmtIns->execute([$nama, $waktuDb]);
            }
        }

        unset($_SESSION['jadwal_sync_fail_at']);
        simpan_pengaturan($db, 'jadwal_sync_date', $today);
        simpan_pengaturan($db, 'jadwal_sync_kota', $kota);

        return ['success' => true, 'synced' => true, 'message' => 'Jadwal sholat untuk ' . $kota . ' berhasil disinkronkan (' . tanggal_indo() . ').'];
    }
}

if (!function_exists('url_base_mosqera')) {
    /**
     * Membangun URL dasar aplikasi secara dinamis sesuai host dan direktori aktif.
     */
    function url_base_mosqera() {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $dir = rtrim($dir, '/');
        return $scheme . '://' . $host . $dir;
    }
}

if (!function_exists('simpan_gambar_qris')) {
    /**
     * Memvalidasi dan menyimpan gambar QRIS resmi masjid (JPG, PNG, WEBP, maks. 3 MB).
     */
    function simpan_gambar_qris($inputName = 'qris_file') {
        if (!isset($_FILES[$inputName]) || !is_array($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'filename' => null, 'error' => null];
        }

        $file = $_FILES[$inputName];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'filename' => null, 'error' => 'Gagal mengunggah gambar QRIS (Kode error: ' . $file['error'] . ').'];
        }

        if ($file['size'] > 3 * 1024 * 1024) {
            return ['success' => false, 'filename' => null, 'error' => 'Ukuran gambar QRIS terlalu besar. Maksimal 3 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['success' => false, 'filename' => null, 'error' => 'Format gambar QRIS harus JPG, PNG, atau WEBP.'];
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/webp'], true)) {
                return ['success' => false, 'filename' => null, 'error' => 'Tipe file gambar QRIS tidak valid.'];
            }
        }

        $qrisDir = __DIR__ . '/../assets/uploads/qris/';
        if (!is_dir($qrisDir)) {
            @mkdir($qrisDir, 0755, true);
        }

        $htaccessPath = $qrisDir . '.htaccess';
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "# Prevent script execution\n<FilesMatch \"\.(php|phtml|php3|php4|php5|pl|py|cgi|sh|bash)$\">\n    Deny from all\n</FilesMatch>\nOptions -Indexes\n");
        }

        $filename = 'qris_masjid_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $qrisDir . $filename;
        $saved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $targetPath)
            : (@rename($file['tmp_name'], $targetPath) || @copy($file['tmp_name'], $targetPath));

        if (!$saved) {
            return ['success' => false, 'filename' => null, 'error' => 'Gagal menyimpan file gambar QRIS ke server.'];
        }

        return ['success' => true, 'filename' => $filename, 'error' => null];
    }
}

if (!function_exists('file_qris_tersedia')) {
    function file_qris_tersedia($filename) {
        if (empty($filename)) return false;
        return is_file(__DIR__ . '/../assets/uploads/qris/' . basename($filename));
    }
}

if (!function_exists('url_gambar_qris')) {
    function url_gambar_qris($filename) {
        if (empty($filename)) return '';
        return 'assets/uploads/qris/' . rawurlencode(basename($filename));
    }
}

if (!function_exists('hapus_gambar_qris')) {
    function hapus_gambar_qris($filename) {
        if (empty($filename)) return true;
        $path = __DIR__ . '/../assets/uploads/qris/' . basename($filename);
        if (file_exists($path) && is_file($path)) {
            return @unlink($path);
        }
        return true;
    }
}
