<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include 'config/database.php';

// Ambil email dari URL
$email = trim($_GET['email'] ?? '');
if (empty($email)) {
    die("<script>alert('Email tidak ditemukan. Silakan ulangi proses reset password.'); 
    window.location='forgot_password.php';</script>");
}

// Status alur
$otp_verified = false;
$error = "";

// ========== TAHAP 1: Verifikasi OTP ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $otp = trim($_POST['otp']);

    $stmt = $conn->prepare("SELECT otp_code, otp_expired FROM peserta WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = "Email tidak ditemukan.";
    } else {
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $expired = new DateTime($user['otp_expired'], new DateTimeZone('Asia/Jakarta'));

        if ($user['otp_code'] !== $otp) {
            $error = "Kode OTP salah.";
        } elseif ($now > $expired) {
            $error = "Kode OTP telah kedaluwarsa.";
        } else {
            // ✅ OTP valid → simpan di sesi
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_verified'] = true;
            $otp_verified = true;

            // Hapus OTP dari database
            $update = $conn->prepare("UPDATE peserta SET otp_code = NULL, otp_expired = NULL WHERE email = ?");
            $update->bind_param("s", $email);
            $update->execute();
        }
    }
}

// ========== TAHAP 2: Update Password ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || $_SESSION['reset_email'] !== $email) {
        die("<script>alert('Akses tidak valid!'); window.location='forgot_password.php';</script>");
    }

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi tidak cocok!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE peserta SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();

       if ($stmt) {
    // 🔒 Hapus sesi (logout otomatis)
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }

    // ✅ Tampilkan alert, lalu redirect ke halaman utama e-BLK
    echo "<script>
        alert('Password berhasil diperbarui! Silakan login.');
        setTimeout(() => {
            window.location.href = 'https://e-blk.pbltifnganjuk.com/index.php';
        }, 500);
    </script>";
    exit;
}
    }
}

// Cek sesi untuk status verifikasi
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true && $_SESSION['reset_email'] === $email) {
    $otp_verified = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Reset Password | e-BLK</title>
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
      padding: 40px 50px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    h2 {
      color: #2b6cb0;
      font-size: 22px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    .input-group {
      position: relative;
      margin-bottom: 20px;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      outline: none;
      transition: border-color 0.2s;
    }
    input:focus {
      border-color: #4F80FF;
      box-shadow: 0 0 0 2px rgba(79, 128, 255, 0.2);
    }
    button {
      background: #2b6cb0;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      font-size: 16px;
      font-weight: 600;
      transition: background 0.3s, transform 0.2s;
    }
    button:hover {
      background: #1a4e8a;
      transform: translateY(-1px);
    }
    .error {
      color: #b00020;
      background: #ffe6e6;
      padding: 10px;
      border-radius: 8px;
      margin: 10px 0;
      text-align: center;
      line-height: 1.4;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <?php if (!$otp_verified): ?>
      <!-- Tahap 1: Masukkan OTP -->
      <h2>Verifikasi Kode OTP</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="text" name="otp" placeholder="Masukkan Kode OTP" required maxlength="6">
        <button type="submit">Verifikasi OTP</button>
      </form>

    <?php else: ?>
      <!-- Tahap 2: Ganti Password -->
      <h2>Atur Password Baru</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <input type="password" name="password" placeholder="Password Baru" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi Password" required>
        <button type="submit">Perbarui Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>