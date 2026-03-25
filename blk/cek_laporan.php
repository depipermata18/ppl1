<?php
include "koneksi.php";
$id_peserta = $_GET['id_peserta'] ?? '';

$q = mysqli_query($koneksi, "SELECT id_laporan FROM laporan WHERE id_peserta='$id_peserta' LIMIT 1");

if (mysqli_num_rows($q) > 0) {
    echo json_encode(["status" => "ada"]);
} else {
    echo json_encode(["status" => "kosong"]);
}
?>
