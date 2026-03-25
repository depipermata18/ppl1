<?php
header('Content-Type: application/json');
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_peserta = $_GET['id_peserta'] ?? '';

    if ($id_peserta == '') {
        echo json_encode([
            'success' => false,
            'message' => 'ID Peserta tidak dikirim'
        ]);
        exit;
    }

    $query = $koneksi->prepare("
        SELECT 
            p.nama_peserta,
            p.NIK,
            l.total_poin,
            l.tanggal_tes,
            l.total_benar,
            l.total_salah
        FROM laporan l
        JOIN peserta p ON p.id_peserta = l.id_peserta
        WHERE l.id_peserta = ?
        ORDER BY l.tanggal_tes DESC
        LIMIT 1
    ");
    $query->bind_param("s", $id_peserta);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'message' => 'Data riwayat ditemukan',
            'riwayat' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Belum ada data riwayat untuk peserta ini'
        ]);
    }

    $query->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak valid'
    ]);
}
?>
