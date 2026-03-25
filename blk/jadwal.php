<?php
include "koneksi.php";

$id_peserta = $_GET['id_peserta'];

// Ambil id_jurusan dari peserta
$q1 = mysqli_query($koneksi, "SELECT id_jurusan FROM peserta WHERE id_peserta='$id_peserta'");
$d1 = mysqli_fetch_assoc($q1);
$id_jurusan = $d1['id_jurusan'];

// Ambil semua jadwal sesuai jurusan
$q2 = mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id_jurusan='$id_jurusan' ORDER BY waktu_mulai ASC");
$jadwal = [];

while ($row = mysqli_fetch_assoc($q2)) {
    $jadwal[] = $row;
}

echo json_encode([
    "status" => "success",
    "jadwal" => $jadwal
]);
?>
