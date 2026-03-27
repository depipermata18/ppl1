<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Lupa Password Peserta | e-BLK</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background: #f5f7fb;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .container {
      background: white;
      padding: 40px 60px;
      border-radius: 12px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 100%;
    }
    h2 { margin-bottom: 20px; color: #2b6cb0; }
    label { display: block; margin-bottom: 8px; color: #555; }
    input[type=email] {
      width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px;
      margin-bottom: 20px;
    }
    button {
      background: #2b6cb0; color: white; border: none;
      padding: 10px 15px; border-radius: 8px; cursor: pointer; width: 100%;
    }
    button:hover { opacity: .9; }
    a { text-decoration: none; color: #2b6cb0; display: block; text-align: center; margin-top: 15px; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Lupa Password Peserta</h2>
    <form action="send_reset_email.php" method="POST">
      <label for="email">Masukkan Email Terdaftar</label>
      <input type="email" name="email" id="email" required placeholder="contoh: nama@email.com">
      <button type="submit">Kirim Link Reset</button>
    </form>
    <a href="login.php">← Kembali ke Login</a>
  </div>
</body>
</html>
