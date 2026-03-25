<?php
include "koneksi.php";
$id_peserta = $_GET['id_peserta'];
$q1 = mysqli_query($koneksi, "SELECT id_jurusan FROM peserta WHERE id_peserta='$id_peserta'");
$r1 = mysqli_fetch_assoc($q1);
$id_jurusan = $r1['id_jurusan'];

$q2 = mysqli_query($koneksi, "SELECT * FROM seleksi WHERE id_jurusan='$id_jurusan' ORDER BY no_soal ASC");

$soal = [];
while($row = mysqli_fetch_assoc($q2)){
    $soal[] = $row;
}

echo json_encode([
    "status" => "success",
    "soal" => $soal
]);
?>
