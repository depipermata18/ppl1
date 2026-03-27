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
      padding: 40px 50px;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 100%;
    }
    h2 {
      margin-bottom: 20px;
      color: #2b6cb0;
      text-align: center;
    }
    label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-weight: 500;
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
    .info {
      font-size: 13px;
      color: #666;
      text-align: center;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Lupa Password Peserta</h2>
    <p class="info">Masukkan <strong>email</strong> dan <strong>NIK</strong> yang terdaftar. Kami akan kirim kode OTP untuk reset password.</p>

    <!-- Ganti action ke skrip PHP Anda -->
    <form action="send_reset_email.php" method="POST">
      <label for="email">Email Terdaftar</label>
      <input type="email" name="email" id="email" required placeholder="contoh: nama@email.com">

      <label for="nik">NIK (Nomor Induk Kependudukan)</label>
      <input type="text" name="nik" id="nik" required placeholder="Masukkan NIK Anda" maxlength="16">

      <button type="submit">Kirim Kode OTP</button>
    </form>

    <a href="login.php">← Kembali ke Login</a>
  </div>
</body>
</html>