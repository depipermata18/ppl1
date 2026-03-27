<?php
session_start();

// Cek login
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

// === Gunakan koneksi Hostinger ===
$koneksi = require __DIR__ . "/config/database.php";

$nik = $_SESSION['nik'];

// Validasi input
if (!isset($_POST['nama'], $_POST['alamat'], $_POST['nohp'])) {
    die("Data tidak lengkap!");
}

$nama   = trim($_POST['nama']);
$alamat = trim($_POST['alamat']);
$nohp   = trim($_POST['nohp']);

if ($nama == "" || $alamat == "" || $nohp == "") {
    die("Semua field harus diisi!");
}

// Update data dengan prepared statement
$sql = "UPDATE peserta SET nama_peserta = ?, alamat = ?, NO_HP = ? WHERE NIK = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ssss", $nama, $alamat, $nohp, $nik);

if ($stmt->execute()) {
    header("Location: profil_peserta.php?update=success");
    exit;
} else {
    echo "Gagal update data!";
}
?>
