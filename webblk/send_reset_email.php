<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'config/database.php';
require __DIR__ . '/vendor/autoload.php';

// 🔥 Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

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
$stmt = $conn->prepare("SELECT id_peserta FROM peserta WHERE email = ? AND NIK = ?");
$stmt->bind_param("ss", $email, $nik);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan NIK tidak cocok atau tidak terdaftar']);
    exit;
}

// 🔹 Generate kode OTP
$otp = rand(100000, 999999);

// 🔥 Simpan waktu kadaluarsa dalam format WIB
$expiry_time = date('Y-m-d H:i:s', time() + 600); // +10 menit

$update = $conn->prepare("UPDATE peserta SET otp_code = ?, otp_expired = ? WHERE email = ?");
$update->bind_param("sss", $otp, $expiry_time, $email);
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
        <p>Kode OTP Anda :</p>
        <p style='font-size: 22px; font-weight: bold;'>$otp</p>
        <p>Gunakan kode ini untuk mengatur ulang password. Berlaku selama 10 menit.</p>
    ";

    $mail->send();
    header("Location: verifikasi_otp.php?email=" . urlencode($email));
    exit;

} catch (Exception $e) {
    die("<script>alert('Gagal mengirim email. Coba lagi nanti.'); window.location='forgot_password.php';</script>");
}

?>
