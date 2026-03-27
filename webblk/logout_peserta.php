<?php
// Mulai sesi
session_start();

// Cek apakah ini permintaan AJAX untuk logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    // Hapus semua data sesi
    $_SESSION = array();

    // Hapus cookie sesi jika digunakan
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Hancurkan sesi
    session_destroy();

    // Beri respons JSON sukses
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* === LOADING SCREEN === */
        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(4px);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        #loading .logo {
            width: 90px;
            margin-bottom: 12px;
            animation: pulseLogo 1s infinite alternate;
        }
        #loading .text {
            font-size: 16px;
            color: #4F80FF;
            font-weight: 600;
        }
        @keyframes pulseLogo {
            from { transform: scale(1); opacity: 0.9; }
            to { transform: scale(1.05); opacity: 1; }
        }

        /* === POPUP MODAL === */
        .modal {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 400px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            font-family: 'Poppins', sans-serif;
        }

        .modal-content h2 {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .modal-content p {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .btn-yes {
            background: #e53935;
            color: white;
            margin-right: 10px;
        }

        .btn-yes:hover {
            background: #c62828;
            transform: scale(1.03);
        }

        .btn-no {
            background: #4F80FF;
            color: white;
        }

        .btn-no:hover {
            background: #3a6bc9;
            transform: scale(1.03);
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        @media (max-width: 480px) {
            .modal-content { padding: 25px; width: 95%; }
            .btn { padding: 10px 20px; font-size: 15px; }
        }
    </style>
</head>
<body>

<!-- LOADING SCREEN -->
<div id="loading">
    <img src="image/logo.png" alt="Logo" class="logo">
    <div class="text">Logout...</div>
</div>

<!-- POPUP KONFIRMASI -->
<div class="modal">
    <div class="modal-content">
        <h2>Yakin ingin logout?</h2>
        <p>Apa Anda yakin ingin keluar dari akun Anda? Anda harus login lagi untuk mengakses dashboard.</p>
        <div class="btn-group">
            <button class="btn btn-yes" onclick="confirmLogout()">Ya, Logout</button>
            <button class="btn btn-no" onclick="cancelLogout()">Batal</button>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    // Tampilkan loading
    document.getElementById('loading').style.display = 'flex';

    // Kirim permintaan POST ke diri sendiri
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Redirect setelah loading
                setTimeout(() => {
                    window.location.href = 'https://e-blk.pbltifnganjuk.com/index.php';
                }, 500);
            }
        }
    };
    xhr.send("confirm_logout=1");
}

function cancelLogout() {
    window.history.back();
}
</script>

</body>
</html>