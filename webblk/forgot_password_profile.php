<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

// 🔥 Set timezone ke WIB
date_default_timezone_set('Asia/Jakarta');

// Variabel untuk pesan error
$error = '';

// Proses form hanya jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $nik = trim($_POST['nik'] ?? '');

    if (empty($email) || empty($nik)) {
        $error = 'Email dan NIK wajib diisi.';
    } else {
        // Cek kombinasi email & NIK
        $stmt = $conn->prepare("SELECT id_peserta FROM peserta WHERE email = ? AND NIK = ?");
        $stmt->bind_param("ss", $email, $nik);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = 'Email dan NIK tidak cocok atau tidak terdaftar.';
        } else {
            // Generate OTP
            $otp = rand(100000, 999999);
            $expiry_time = date('Y-m-d H:i:s', time() + 600); // +10 menit

            // Simpan ke database
            $update = $conn->prepare("UPDATE peserta SET otp_code = ?, otp_expired = ? WHERE email = ?");
            $update->bind_param("sss", $otp, $expiry_time, $email);
            $update->execute();

            // Kirim email
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'lendrawahyupratama43@gmail.com';
                $mail->Password = 'ecfeooobjlqenaum';
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

                // ✅ Sukses → redirect ke verifikasi OTP
                header("Location: verifikasi_otp_profile.php?email=" . urlencode($email));
                exit;

            } catch (Exception $e) {
                $error = 'Gagal mengirim email. Coba lagi nanti.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lupa Password Peserta | e-BLK</title>
  <style>
    body {
      background: #f5f7fb;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background: white;
      padding: 4.5vh 5vw;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 90%;
    }
    h2 {
      margin-bottom: 20px;
      color: #2b6cb0;
      text-align: center;
      font-size: 22px;
    }
    .info {
      font-size: 13px;
      color: #666;
      text-align: center;
      margin-bottom: 20px;
      line-height: 1.5;
    }
    .error {
      background: #ffe6e6;
      color: #b00020;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 14px;
    }
    label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
      font-size: 14px;
    }
    input[type=email],
    input[type=text] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      box-sizing: border-box;
    }
    button {
      background: #2b6cb0;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      font-size: 15px;
      transition: background 0.3s;
      font-weight: 600;
    }
    button:hover {
      background: #2d7be4;
    }
    a {
      text-decoration: none;
      color: #2b6cb0;
      display: block;
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Lupa Password Peserta</h2>
    
    <p class="info">Masukkan <strong>email</strong> dan <strong>NIK</strong> yang terdaftar. Kami akan kirim kode OTP untuk reset password.</p>

    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="email">Email Terdaftar</label>
      <input type="email" name="email" id="email" required placeholder="contoh: nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

      <label for="nik">NIK (Nomor Induk Kependudukan)</label>
      <input type="text" name="nik" id="nik" required placeholder="Masukkan NIK Anda" maxlength="16" value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>">

      <button type="submit">Kirim Kode OTP</button>
    </form>

    <a href="profil_peserta.php">← Kembali ke Profile</a>
  </div>
</body>
</html>