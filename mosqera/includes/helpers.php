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
