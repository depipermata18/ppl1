<?php
include "koneksi.php";

// Log supaya bisa tahu POST yang diterima
file_put_contents("log_submit.txt", date('Y-m-d H:i:s') . " | POST = " . print_r($_POST, true) . "\n", FILE_APPEND);

// Ambil data kiriman dari Android
$id_peserta   = $_POST['id_peserta']   ?? $_GET['id_peserta']   ?? '';
$total_benar  = $_POST['total_benar']  ?? $_GET['total_benar']  ?? '';
$total_salah  = $_POST['total_salah']  ?? $_GET['total_salah']  ?? '';
$total_poin   = $_POST['total_poin']   ?? $_GET['total_poin']   ?? '';


if ($id_peserta == '' || $total_benar === '' || $total_salah === '' || $total_poin === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Data POST tidak lengkap",
        "received" => $_POST
    ]);
    exit;
}

$tanggal_tes = date('Y-m-d');

// Perhatikan: NULL tanpa tanda kutip
$q = "INSERT INTO laporan (tanggal_tes, total_poin, total_benar, total_salah, id_admin, id_peserta)
      VALUES ('$tanggal_tes', '$total_poin', '$total_benar', '$total_salah', NULL, '$id_peserta')";

if (mysqli_query($koneksi, $q)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => mysqli_error($koneksi)]);
}
?>
