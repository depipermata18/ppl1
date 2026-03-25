
<?php
header("Content-Type: application/json");
include "koneksi.php";

$NIK = $_POST['NIK'] ?? '';
$password_baru = $_POST['password'] ?? '';

// Validasi input
if ($NIK == "" || $password_baru == "") {
    echo json_encode(["success" => false, "message" => "NIK dan Password baru wajib diisi"]);
    exit;
}

// Generate hash password
$hash = password_hash($password_baru, PASSWORD_BCRYPT);

// Update database
$sql = "UPDATE peserta SET password = ? WHERE NIK = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("ss", $hash, $NIK);
$stmt->execute();

// Cek apakah NIK ditemukan
if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Password berhasil diupdate (HASH tersimpan)!"]);
} else {
    echo json_encode(["success" => false, "message" => "NIK tidak ditemukan atau tidak ada perubahan"]);
}
?>
