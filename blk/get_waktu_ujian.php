<?php
include "koneksi.php";
header('Content-Type: application/json; charset=utf-8');

// Set timezone server agar waktu server benar (WIB)
date_default_timezone_set('Asia/Jakarta');

$id_peserta = $_GET['id_peserta'] ?? '';

if ($id_peserta == '') {
    echo json_encode([
        "status" => "error",
        "message" => "id_peserta kosong"
    ]);
    exit;
}

// Ambil id_jurusan berdasarkan id_peserta
$q1 = mysqli_query($koneksi, "SELECT id_jurusan FROM peserta WHERE id_peserta='$id_peserta' LIMIT 1");

if (!$q1 || mysqli_num_rows($q1) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Peserta tidak ditemukan"
    ]);
    exit;
}

$r1 = mysqli_fetch_assoc($q1);
$id_jurusan = $r1['id_jurusan'];

// Ambil waktu ujian berdasarkan id_jurusan
$q2 = mysqli_query($koneksi, "
    SELECT waktu_mulai, waktu_selesai 
    FROM seleksi 
    WHERE id_jurusan = '$id_jurusan' 
    LIMIT 1
");

if (!$q2 || mysqli_num_rows($q2) == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Data waktu ujian tidak ditemukan untuk jurusan ini"
    ]);  
    exit;
}

$r2 = mysqli_fetch_assoc($q2);

// ===========================
// TIDAK PERLU TAMPILKAN INFO IP / LOKASI
// ===========================

// OUTPUT KE ANDROID
echo json_encode([
    "status" => "success",
    "waktu_mulai" => $r2['waktu_mulai'],
    "waktu_selesai" => $r2['waktu_selesai'],
    "server_time" => date('Y-m-d H:i:s')
]);
?>
