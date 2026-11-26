<?php
require_once __DIR__ . '/../config.php';

$results = [];

// Add bukti_file to transaksi_kas
try {
    $check = $db->query("SHOW COLUMNS FROM transaksi_kas LIKE 'bukti_file'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE transaksi_kas ADD COLUMN bukti_file VARCHAR(255) NULL AFTER keterangan");
        $results[] = "✅ Kolom bukti_file berhasil ditambahkan ke tabel transaksi_kas";
    } else {
        $results[] = "ℹ️ Kolom bukti_file sudah ada di tabel transaksi_kas";
    }
} catch (Exception $e) {
    $results[] = "❌ Error transaksi_kas: " . $e->getMessage();
}

// Add bukti_file to donasi_masuk
try {
    $check = $db->query("SHOW COLUMNS FROM donasi_masuk LIKE 'bukti_file'")->fetch();
    if (!$check) {
        $db->exec("ALTER TABLE donasi_masuk ADD COLUMN bukti_file VARCHAR(255) NULL AFTER catatan_doa");
        $results[] = "✅ Kolom bukti_file berhasil ditambahkan ke tabel donasi_masuk";
    } else {
        $results[] = "ℹ️ Kolom bukti_file sudah ada di tabel donasi_masuk";
    }
} catch (Exception $e) {
    $results[] = "❌ Error donasi_masuk: " . $e->getMessage();
}

echo "<pre>" . implode("\n", $results) . "\n\nMigrasi selesai. Silakan hapus file ini setelah selesai.</pre>";
