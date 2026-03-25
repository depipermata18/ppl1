<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password_baru = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || empty($password_baru)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email dan password wajib diisi'
        ]);
        exit;
    }

    // Cek apakah email ada & sudah diverifikasi
    $stmt = $koneksi->prepare("SELECT is_verified FROM peserta WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Email tidak ditemukan'
        ]);
        exit;
    }

    if ($data['is_verified'] != 1) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses tidak diizinkan. Verifikasi OTP terlebih dahulu.'
        ]);
        exit;
    }

    // 🔥 HASH password baru (paling penting)
    $hash_password = password_hash($password_baru, PASSWORD_DEFAULT);

    // Update ke database
    $stmt = $koneksi->prepare("UPDATE peserta SET password = ?, is_verified = 0, otp_code = NULL, otp_expired = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hash_password, $email);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Password berhasil diubah'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal memperbarui password'
        ]);
    }

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gunakan metode POST'
    ]);
}
?>
