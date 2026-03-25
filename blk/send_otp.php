<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$nik = $_POST['nik'] ?? '';

if (empty($email) || empty($nik)) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan NIK wajib diisi']);
    exit;
}

// 🔹 Cek kombinasi email & NIK
$stmt = $koneksi->prepare("SELECT id_peserta FROM peserta WHERE email = ? AND NIK = ?");
$stmt->bind_param("ss", $email, $nik);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan NIK tidak cocok atau tidak terdaftar']);
    exit;
}

// 🔹 Generate kode OTP
$otp = rand(100000, 999999);
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// 🔹 Simpan OTP ke database
$update = $koneksi->prepare("UPDATE peserta SET otp_code = ?, otp_expired = ? WHERE email = ?");
$update->bind_param("sss", $otp, $expiry, $email);
$update->execute();

// 🔹 Kirim email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'lendrawahyupratama43@gmail.com';
    $mail->Password = 'ecfeooobjlqenaum'; // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('lendrawahyupratama43@gmail.com', 'E-BLK');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Kode OTP Reset Password';
    $mail->Body = "
            <p>Halo,</p>
        <p>Kode OTP Anda :></p>
        <p style='font-size: 22px; font-weight: bold;'>$otp</p>
        <p>Gunakan kode ini untuk mengatur ulang password. Berlaku selama 10 menit.</p>
    ";

    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'OTP berhasil dikirim ke email Anda']);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal mengirim email',
        'error_detail' => $mail->ErrorInfo
    ]);
}
?>
