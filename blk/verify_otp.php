<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $otp = $_POST['otp'] ?? '';

    if (empty($email) || empty($otp)) {
        echo json_encode(['status' => 'error', 'message' => 'Email dan OTP wajib diisi']);
        exit;
    }

    $stmt = $koneksi->prepare("SELECT otp_code, otp_expired FROM peserta WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan']);
        exit;
    }

    // Cek OTP
    $now = date('Y-m-d H:i:s');
    if ($data['otp_code'] !== $otp) {
        echo json_encode(['status' => 'error', 'message' => 'Kode OTP salah']);
    } elseif ($now > $data['otp_expired']) {
        echo json_encode(['status' => 'error', 'message' => 'Kode OTP telah kedaluwarsa']);
    } else {
        // Tandai sudah verifikasi
        $stmt = $koneksi->prepare("UPDATE peserta SET is_verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Verifikasi berhasil']);
    }
}
?>
