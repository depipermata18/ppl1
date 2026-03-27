<?php
session_start();

// --------------------------------------------------
// CEGAH MASUK KE HALAMAN LOGIN JIKA SUDAH LOGIN
// --------------------------------------------------
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin' && isset($_SESSION['id_admin'])) {
        header("Location: admin/dashboard_admin.html");
        exit;
    }
    if ($_SESSION['role'] === 'peserta' && isset($_SESSION['nik'])) {
        header("Location: dashboard_peserta.php");
        exit;
    }
}

// --------------------------------------------------
// KONEKSI DATABASE
// --------------------------------------------------
$conn = include "config/database.php";

$error_message = "";


// ================================================================
// =============== 1) LOGIN VIA API JSON (Android / JS) ============
// ================================================================
$is_json = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    if ($json !== null) {
        $is_json = true;

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST');
        header('Access-Control-Allow-Headers: Content-Type');

        $role = $json['role'] ?? '';
        $password = $json['password'] ?? '';

        // ========================================================
        // LOGIN ADMIN — API JSON
        // ========================================================
        if ($role === 'admin') {
            $username = $json['username'] ?? '';

            if (!$username || !$password) {
                echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi']);
                exit;
            }

            $stmt = $conn->prepare("SELECT id_admin, username, password, id_jurusan, nama_admin, foto_profil 
                                    FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                if (password_verify($password, $row['password']) || $password === $row['password']) {

                    if ($password === $row['password']) {
                        // auto hash
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE admin SET password=? WHERE username=?");
                        $up->bind_param("ss", $newHash, $username);
                        $up->execute();
                    }

                    $_SESSION['role'] = 'admin';
                    $_SESSION['id_admin'] = $row['id_admin'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['id_jurusan'] = $row['id_jurusan'];
                    $_SESSION['nama_admin'] = $row['nama_admin'];

                    $foto = $row['foto_profil'] ?: "profile.png";

                    echo json_encode([
                        'success' => true,
                        'role' => 'admin',
                        'username' => $row['username'],
                        'nama_admin' => $row['nama_admin'],
                        'foto_profil_path' => "images/$foto",
                        'id_jurusan' => $row['id_jurusan']
                    ]);
                    exit;
                }
            }

            echo json_encode(['success' => false, 'message' => 'Username atau password salah']);
            exit;
        }

      // ========================================================
// LOGIN PESERTA — API JSON
// ========================================================
elseif ($role === 'peserta') {
    $nik = $json['nik'] ?? '';

    if (!$nik || !$password) {
        echo json_encode(['success' => false, 'message' => 'NIK dan password wajib diisi']);
        exit;
    }

    // 🔒 CEK BLOKIR
    $block_info = isLoginBlocked($nik);
    if ($block_info['blocked']) {
        echo json_encode([
            'success' => false,
            'message' => 'Login diblokir karena terlalu banyak percobaan gagal. Coba lagi dalam ' . $block_info['seconds_left'] . ' detik.'
        ]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id_peserta, NIK, nama_peserta, password FROM peserta WHERE NIK = ?");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            if ($password === $row['password']) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE peserta SET password=? WHERE NIK=?");
                $up->bind_param("ss", $newHash, $nik);
                $up->execute();
            }

            // ✅ Sukses → hapus riwayat gagal
            clearLoginFail($nik);
            $_SESSION['role'] = 'peserta';
            $_SESSION['nik'] = $row['NIK'];
            $_SESSION['nama_peserta'] = $row['nama_peserta'];

            echo json_encode([
                'success' => true,
                'role' => 'peserta',
                'username' => $row['nama_peserta'],
                'nama_peserta' => $row['nama_peserta']
            ]);
            exit;
        } else {
            // ❌ Gagal
            recordLoginFail($nik);
            echo json_encode(['success' => false, 'message' => 'Password salah']);
            exit;
        }
    } else {
        // ❌ NIK tidak ditemukan
        recordLoginFail($nik);
        echo json_encode(['success' => false, 'message' => 'NIK tidak ditemukan']);
        exit;
    }
}

        echo json_encode(['success' => false, 'message' => 'Peran tidak valid']);
        exit;
    }
}



// =====================================================================
// ==================== 2) LOGIN WEB NORMAL (FORM HTML) =================
// =====================================================================
if (!$is_json && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $role = $_POST['role'] ?? 'peserta';
    $password = trim($_POST['password'] ?? '');

    // ========================================================
    // LOGIN PESERTA — FORM
    // ========================================================
    if ($role === 'peserta') {
        $nik = trim($_POST['nik'] ?? '');

        if ($nik === "" || $password === "") {
            $error_message = "❌ NIK dan Password harus diisi.";
        } else {
            $stmt = $conn->prepare("SELECT NIK, nama_peserta, password FROM peserta WHERE NIK=?");
            $stmt->bind_param("s", $nik);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                $match = false;

                if (password_verify($password, $row['password']) || $password === $row['password']) {
                    $match = true;

                    if ($password === $row['password']) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE peserta SET password=? WHERE NIK=?");
                        $up->bind_param("ss", $newHash, $nik);
                        $up->execute();
                    }
                }

                if ($match) {
                    $_SESSION['role'] = 'peserta';
                    $_SESSION['nik'] = $row['NIK'];
                    $_SESSION['nama_peserta'] = $row['nama_peserta'];

                    header("Location: dashboard_peserta.php");
                    exit;
                } else {
                    $error_message = "❌ Kata sandi salah.";
                }
            } else {
                $error_message = "❌ NIK tidak ditemukan.";
            }
        }
    }


    // ========================================================
    // LOGIN ADMIN — FORM
    // ========================================================
    if ($role === 'admin') {
        $username = trim($_POST['username'] ?? '');

        if ($username === "" || $password === "") {
            $error_message = "❌ Username dan Password harus diisi.";
        } else {
            $stmt = $conn->prepare("SELECT id_admin, nama_admin, password FROM admin WHERE username=?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {

                $match = false;

                if (password_verify($password, $row['password']) || $password === $row['password']) {
                    $match = true;

                    if ($password === $row['password']) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE admin SET password=? WHERE username=?");
                        $up->bind_param("ss", $newHash, $username);
                        $up->execute();
                    }
                }

                if ($match) {
                    $_SESSION['role'] = 'admin';
                    $_SESSION['id_admin'] = $row['id_admin'];
                    $_SESSION['nama_admin'] = $row['nama_admin'];

                    header("Location: admin/dashboard_admin.html");
                    exit;
                } else {
                    $error_message = "❌ Kata sandi salah.";
                }
            } else {
                $error_message = "❌ Username tidak ditemukan.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login e-BLK</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Quicksand  :wght@400;500;600&display=swap');

:root {
    --bg: #f0f7ff;
    --card: #ffffff;
    --accent: #6a9df9;
    --accent-hover: #5a8bec;
    --toggle-active: #8ab9ff;
    --text: #3a4b6c;
    --text-muted: #7b8fb5;
    --error: #ff6b8b;
    --radius: 20px;
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: 'Quicksand', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: var(--bg);
    position: relative;
    overflow-x: hidden;
}

/* Latar belakang gambar dengan opacity rendah */
body::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('webblk/image/blk.jpeg');
    background-size: cover;
    background-position: center;
    opacity: 0.12;
    z-index: -1;
}

.card {
    display: flex;
    width: 900px;
    max-width: 95%;
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: 0 12px 40px rgba(106, 157, 249, 0.2);
    overflow: hidden;
    transition: transform 0.6s ease-in-out;
    border: 1px solid rgba(138, 185, 255, 0.2);
}

.left, .right {
    flex: 1;
    padding: 60px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: transform 0.6s ease-in-out;
}

.right {
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #e8f2ff, #f5fbff);
}

.logo-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.logo-box img {
    width: 160px;
    height: 160px;
    object-fit: contain;
    border-radius: 16px;
    box-shadow: 0 6px 16px rgba(106, 157, 249, 0.25);
    background: white;
    padding: 12px;
}

.logo-box span {
    color: var(--text-muted);
    margin-top: 12px;
    font-weight: 500;
    font-size: 16px;
}

h1 {
    font-size: 26px;
    margin-bottom: 10px;
    color: var(--text);
    font-weight: 600;
}

p.lead {
    color: var(--text-muted);
    margin-bottom: 24px;
    line-height: 1.5;
}

.role-toggle {
    display: flex;
    gap: 6px;
    background: #eaf4ff;
    border-radius: 50px;
    padding: 6px;
    margin-bottom: 28px;
    width: fit-content;
    box-shadow: inset 0 2px 6px rgba(138, 185, 255, 0.1);
}

.role-toggle button {
    border: none;
    background: transparent;
    padding: 10px 24px;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 15px;
    color: var(--text-muted);
}

.role-toggle button.active {
    background: var(--toggle-active);
    color: white;
    box-shadow: 0 4px 12px rgba(106, 157, 249, 0.3);
}

form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

label {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 8px;
    font-weight: 500;
}

input[type=text],
input[type=password],
input[type=number] {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #dbe8ff;
    border-radius: 14px;
    font-size: 16px;
    outline: none;
    transition: all 0.25s ease;
    background: #f9fbff;
    color: var(--text);
    min-height: 52px; /* nyaman di-tap */
}

input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(106, 157, 249, 0.15);
}

/* === Show/Hide Password Style === */
.password-toggle {
    position: relative;
}
.password-toggle input {
    padding-right: 50px;
}
.toggle-visibility {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 18px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}
.toggle-visibility:hover {
    color: var(--accent);
}

/* === End Show/Hide === */

.btn {
    background: var(--accent);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 14px;
    cursor: pointer;
    font-size: 17px;
    margin-top: 8px;
    transition: all 0.3s ease;
    font-weight: 600;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 14px rgba(106, 157, 249, 0.3);
    min-height: 54px;
}

.btn:hover {
    background: var(--accent-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(106, 157, 249, 0.4);
}

.forgot {
    font-size: 14px;
    color: var(--accent);
    text-decoration: none;
    margin-top: 6px;
    display: inline-block;
    font-weight: 500;
    transition: color 0.2s;
}

.forgot:hover {
    color: var(--accent-hover);
    text-decoration: underline;
}

.note {
    font-size: 13px;
    color: var(--text-muted);
    margin-top: 16px;
    line-height: 1.5;
    text-align: center;
}

.error {
    color: var(--error);
    font-size: 14px;
    margin-bottom: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.error::before {
    content: "⚠️";
}

/* 📱 MOBILE OPTIMIZATION */
@media (max-width: 768px) {
    .card {
        flex-direction: column;
        width: 95%;
        max-width: 500px;
    }
    .left, .right {
        padding: 40px 24px;
    }
    .logo-box img {
        width: 120px;
        height: 120px;
        padding: 10px;
    }
    h1 {
        font-size: 24px;
    }
    p.lead {
        font-size: 15px;
        margin-bottom: 20px;
    }
    .role-toggle {
        margin-bottom: 24px;
    }
    .role-toggle button {
        padding: 8px 18px;
        font-size: 14px;
    }
    input[type=text],
    input[type=password],
    input[type=number] {
        font-size: 16px; /* penting untuk iOS agar tidak zoom */
        padding: 14px;
    }
    .btn {
        font-size: 16px;
        padding: 14px;
    }
    .note {
        font-size: 12px;
        padding: 0 10px;
    }
}

.card.swap .left {
    transform: translateX(100%);
}
.card.swap .right {
    transform: translateX(-100%);
}
.transitioning {
    transition: transform 0.6s ease-in-out;
}

#fields-wrapper {
    height: 180px;
    position: relative;
    overflow: hidden;
}
#fields {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    transition: transform 0.4s cubic-bezier(0.33, 1, 0.68, 1);
}

/* ✅ TAMBAHAN KHUSUS HP: ubah arah slide jadi vertikal */
@media (max-width: 768px) {
    .card.swap .left {
        transform: translateY(100%);
    }
    .card.swap .right {
        transform: translateY(-100%);
    }
}
/* ✅ Perbaikan: tampilkan logo full saat card geser di HP */
@media (max-width: 768px) {
    /* Nonaktifkan overflow yang memotong */
    .card {
        overflow: visible !important;
    }

    /* Pastikan bagian logo punya ruang cukup */
    .right {
        min-height: 280px; /* cukup untuk logo + teks */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 50px 24px !important;
    }

    .logo-box {
        width: 100%;
        max-width: 200px;
    }

    .logo-box img {
        width: 120px;
        height: 120px;
        padding: 12px;
        border-radius: 16px;
    }

    /* Geser lebih sedikit agar logo tidak "lewat" */
    .card.swap .left {
        transform: translateY(105%);
    }
    .card.swap .right {
        transform: translateY(-105%);
    }
}
</style>
</head>

<body>
<div class="card" id="card">
    <div class="left">
        <h1>Masuk ke e-BLK</h1>
        <p class="lead">Silakan pilih peran dan masukkan data login Anda.</p>

        <div class="role-toggle">
            <button id="btn-peserta" class="active">Peserta</button>
            <button id="btn-admin">Admin</button>
        </div>

        <?php if (!empty($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="role" id="role" value="peserta">

            <div id="fields-wrapper">
                <div id="fields" class="transitioning">
                    <div class="field">
                        <label for="nik">NIK</label>
                        <input id="nik" name="nik" type="text" placeholder="Masukkan NIK" required />
                    </div>

                    <div class="field">
                        <label for="password">Kata Sandi</label>
                        <div class="password-toggle">
                            <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required />
                            <button type="button" class="toggle-visibility" id="togglePassword" 
                                onclick="togglePasswordVisibility('password', 'togglePassword')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn">Masuk</button>

            <a id="forgotLink" class="forgot" href="forgot_password.php">
                Lupa kata sandi?
            </a>

            <p class="note">
                Belum punya akun? Hubungi admin BLK untuk pendaftaran.
            </p>
        </form>
    </div>

   <!-- Bagian kanan (logo) — tetap sama, tapi pastikan src benar -->
<div class="right">
    <div class="logo-box">
        <img 
            id="logoImg"
            src="webblk/image/logo.jpeg"
            onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22120%22 height=%22120%22 viewBox=%220 0 120 120%22><circle cx=%2260%22 cy=%2260%22 r=%2254%22 fill=%22%238ab9ff%22/><text x=%2250%25%22 y=%2255%25%22 font-family=%22Quicksand,sans-serif%22 text-anchor=%22middle%22 font-size=%2224%22 fill=%22white%22>e-BLK</text></svg>'"
            alt="Logo e-BLK">
        <span id="logoText">Balai Latihan Kerja</span>
    </div>
</div>
</div>

<script>
const btnPeserta = document.getElementById('btn-peserta');
const btnAdmin = document.getElementById('btn-admin');
const card = document.getElementById('card');
const roleInput = document.getElementById('role');
const forgot = document.getElementById('forgotLink');
const fields = document.getElementById('fields');
const logoText = document.getElementById('logoText');

btnPeserta.onclick = () => switchRole('peserta');
btnAdmin.onclick = () => switchRole('admin');

function switchRole(role) {
    if (role === 'admin') {
        roleInput.value = 'admin';
        btnAdmin.classList.add('active');
        btnPeserta.classList.remove('active');
        card.classList.add('swap');

        fields.style.transform = 'translateY(-160px)';

        setTimeout(() => {
            fields.innerHTML = `
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" placeholder="Masukkan username" required />
                </div>
                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <div class="password-toggle">
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required />
                        <button type="button" class="toggle-visibility" id="togglePassword" 
                            onclick="togglePasswordVisibility('password', 'togglePassword')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            fields.style.transform = 'translateY(0)';
        }, 300);

        forgot.style.display = 'none';
        logoText.textContent = 'Mode Admin';
    } else {
        roleInput.value = 'peserta';
        btnPeserta.classList.add('active');
        btnAdmin.classList.remove('active');
        card.classList.remove('swap');

        fields.style.transform = 'translateY(-160px)';

        setTimeout(() => {
            fields.innerHTML = `
                <div class="field">
                    <label for="nik">NIK</label>
                    <input id="nik" name="nik" type="text" placeholder="Masukkan NIK" required />
                </div>
                <div class="field">
                    <label for="password">Kata Sandi</label>
                    <div class="password-toggle">
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required />
                        <button type="button" class="toggle-visibility" id="togglePassword" 
                            onclick="togglePasswordVisibility('password', 'togglePassword')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            fields.style.transform = 'translateY(0)';
        }, 300);

        forgot.style.display = 'inline';
        logoText.textContent = 'Balai Latihan Kerja';
    }
}

function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
    button.innerHTML = type === 'password' 
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
}

// Initialize as peserta
switchRole('peserta');
</script>
</body>
</html>