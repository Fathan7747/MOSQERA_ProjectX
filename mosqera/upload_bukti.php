<?php
require_once 'config.php';

header('Content-Type: application/json');

// Jika diakses oleh admin atau jamaah
$res = simpan_bukti_transaksi('bukti_file', 'async_');

if (!$res['success']) {
    echo json_encode([
        'success' => false,
        'error' => $res['error'] ?: 'Gagal memproses file bukti.'
    ]);
    exit;
}

if (empty($res['filename'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Tidak ada file bukti yang diunggah.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'filename' => $res['filename'],
    'url' => url_bukti_transaksi($res['filename']),
    'is_image' => is_image_bukti($res['filename']),
    'is_pdf' => is_pdf_bukti($res['filename'])
]);
