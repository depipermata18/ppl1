<?php
session_start();

// === Gunakan file koneksi Hostinger ===
$koneksi = require __DIR__ . '/config/database.php';

// Cek login
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];

// ===============================
// 1. UPDATE FOTO PROFIL
// ===============================
if (!empty($_FILES['foto']['name'])) {

    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $namaFile = "FOTO_" . $nik . "_" . time() . "." . $ext;

    // Folder penyimpanan
    $folder = __DIR__ . "/image/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $target = $folder . $namaFile;

    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target)) {

        // Update database
        $sql = "UPDATE peserta SET foto_profil = ? WHERE nik = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ss", $namaFile, $nik);
        $stmt->execute();
    } else {
        echo "Gagal upload foto! Pastikan folder /image/ bisa ditulis.";
        exit;
    }
}


// ===============================
// 2. UPDATE PASSWORD
// ===============================
if (!empty($_POST['password_lama']) && !empty($_POST['password_baru'])) {

    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];

    // Ambil password lama
    $sql = "SELECT password FROM peserta WHERE nik = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo "Data akun tidak ditemukan.";
        exit;
    }

    // Cocokkan password lama
    if (!password_verify($password_lama, $data['password'])) {
        echo "Password lama salah!";
        exit;
    }

    // Hash password baru
    $passwordBaruHash = password_hash($password_baru, PASSWORD_DEFAULT);

    // Update password
    $sql = "UPDATE peserta SET password = ? WHERE nik = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ss", $passwordBaruHash, $nik);
    $stmt->execute();
}

// Redirect kembali ke profil
header("Location: profil_peserta.php");
exit;
?>
