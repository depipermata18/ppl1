<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

include '../config/database.php';

$id_peserta = (int)($_POST['id_peserta'] ?? 0);
$status_baru = $_POST['status'] ?? '';

if ($id_peserta <= 0 || !in_array($status_baru, ['lulus', 'tidak_lulus'])) {
    echo json_encode(['error' => 'Data tidak valid.']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($status_baru === 'tidak_lulus') {
        // HAPUS dari detail_laporan → laporan → peserta (CASCADE aman, tapi kita hapus manual untuk kejelasan)
        $stmt1 = $conn->prepare("DELETE dl FROM detail_laporan dl JOIN laporan l ON dl.id_laporan = l.id_laporan WHERE l.id_peserta = ?");
        $stmt1->bind_param("i", $id_peserta);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("DELETE FROM laporan WHERE id_peserta = ?");
        $stmt2->bind_param("i", $id_peserta);
        $stmt2->execute();
        $stmt2->close();

        $stmt3 = $conn->prepare("DELETE FROM peserta WHERE id_peserta = ?");
        $stmt3->bind_param("i", $id_peserta);
        $stmt3->execute();
        $stmt3->close();

        $message = 'Data peserta berhasil dihapus karena tidak lulus.';
    } else {
        // Tetapkan status = 'lulus'
        $stmt = $conn->prepare("UPDATE peserta SET status = 'lulus' WHERE id_peserta = ?");
        $stmt->bind_param("i", $id_peserta);
        $stmt->execute();
        $stmt->close();
        $message = 'Status peserta berhasil diubah menjadi LULUS.';
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error tetapkan_status: " . $e->getMessage());
    echo json_encode(['error' => 'Terjadi kesalahan internal. Silakan coba lagi.']);
}

$conn->close();
exit;
?>