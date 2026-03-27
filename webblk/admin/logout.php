<?php
// ==========================================
// ANTI CACHE (Biar halaman tidak bisa diback)
// ==========================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// ==========================================
// CEK: Jika tidak ada sesi → usir langsung
// ==========================================
if (empty($_SESSION['role']) && empty($_SESSION['id_admin']) && empty($_SESSION['nik'])) {
    header("Location: ../index.php");
    exit;
}

// ==========================================
// PROSES LOGOUT SERVER-SIDE
// ==========================================
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Setelah session hancur → tampilkan halaman loading
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Keluar...</title>

<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
    body {
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #f3f6ff;
        font-family: Poppins, sans-serif;
    }
    .loading-box {
        text-align: center;
        padding: 30px 40px;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    }
    .loading-box i {
        font-size: 50px;
        color: #2F5FA9;
        animation: spin 1s linear infinite;
        display: block;
        margin-bottom: 15px;
    }
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }
</style>
</head>

<body>

<div class="loading-box">
    <i class="fas fa-spinner"></i>
    <h3>Keluar dari sesi...</h3>
    <p>Harap tunggu sebentar</p>
</div>

<script>
// ========================================================
// ANTI BACK PALING KUAT — MENINDAS HISTORY SETIAP MS
// ========================================================
setInterval(() => {
    history.pushState(null, "", location.href);
}, 0);

// Redirect otomatis ke halaman login
setTimeout(() => {
    window.location.href = "../index.php";
}, 100);
</script>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</body>
</html>
