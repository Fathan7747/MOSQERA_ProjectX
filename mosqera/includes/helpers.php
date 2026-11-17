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
     * Mengambil daftar jadwal 5 waktu sholat dari database, dengan fallback default.
     */
    function jadwal($db) {
        $fallback = [
            ['nama' => 'Subuh', 'waktu' => '04:42'],
            ['nama' => 'Terbit', 'waktu' => '05:58'],
            ['nama' => 'Dzuhur', 'waktu' => '12:03'],
            ['nama' => 'Ashar', 'waktu' => '15:18'],
            ['nama' => 'Maghrib', 'waktu' => '18:08'],
            ['nama' => 'Isya', 'waktu' => '19:18']
        ];
        try {
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
