<?php
session_start();

// === include koneksi pusat ===
$koneksi = require __DIR__ . '/config/database.php';

if (!$koneksi || !($koneksi instanceof mysqli)) {
    http_response_code(500);
    die("Koneksi database tidak tersedia.");
}

if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];

// Ambil data peserta
$sql = "SELECT * FROM peserta WHERE NIK = ?";
$stmt = $koneksi->prepare($sql);
$stmt->bind_param("s", $nik);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) { die("Data peserta tidak ditemukan!"); }

$nama      = $data['nama_peserta'];
$alamat    = $data['alamat'];
$tgl_lahir = $data['tgl_lahir'];
$jk        = ($data['jenis_kelamin'] == 'L') ? "Laki-laki" : "Perempuan";
$nohp      = $data['NO_HP'];
$email     = $data['email'];

// Path foto benar
$foto = !empty($data['foto_profil']) ? $data['foto_profil'] : "user.png";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Peserta</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* === LOADING SCREEN === */
.page-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.3s, visibility 0.3s;
}
.page-loader .logo {
    width: 90px;
    margin-bottom: 12px;
    animation: pulseLogo 1s infinite alternate;
}
.page-loader .text {
    font-size: 16px;
    color: #4F80FF;
    font-weight: 600;
    letter-spacing: 0.5px;
}
@keyframes pulseLogo {
    from { transform: scale(1); opacity: 0.9; }
    to { transform: scale(1.05); opacity: 1; }
}

/* GLOBAL STYLES */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #ffffff, #ffffff);
    color: #333333;
    line-height: 1.6;
}

/* HEADER */
.header {
    position: fixed;
    top: 0;
    left: 0;
    height: 70px;
    width: 100%;
    padding: 0 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    box-sizing: border-box;
}

.logo img {
    height: 50px;
    object-fit: contain;
    max-width: 100%;
}

.nav {
    display: flex;
    align-items: center;
    gap: 30px;
    margin-right: 20px;
}

.nav a {
    color: #333333;
    text-decoration: none;
    font-size: 16px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav a:hover,
.nav a.active {
    color: #4F80FF;
}

/* Hamburger hanya untuk HP — default sembunyikan */
.menu-toggle {
    display: none !important;
}

/* MAIN CONTENT */
.main-content {
    margin-top: 90px;
    padding: 20px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

/* PROFILE CARD */
.profile-card {
    background: #4F80FF;
    border-radius: 20px;
    padding: 30px;
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.22);
    text-align: center;
    margin-bottom: 20px;
}

.photo-box {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
}

.photo-box img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 3px solid #ffffff;
    object-fit: cover;
    box-shadow: 0 10px 22px rgba(0, 0, 0, 0.35);
}

.name-text {
    font-size: 20px;
    font-weight: bold;
    color: white;
    margin: 10px 0;
}

.btn {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    font-size: 15px;
    transition: 0.25s;
    margin: 8px 0;
    text-align: center;
}

.btn-blue {
    background: #ffffff;
    color: #4F80FF;
}

.btn-blue:hover {
    transform: scale(1.05);
}

.btn-red {
    background: #d9534f;
    color: white;
}

.btn-blue2 {
    background: #4F80FF;
    color: #ffffff;
}

.btn-blue2:hover {
    transform: scale(1.05);
}

.data-box {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    padding: 20px;
    margin-top: 20px;
    text-align: left;
}

.data-item {
    margin-bottom: 15px;
}

.label {
    font-weight: 700;
    color: #000000;
    display: block;
    margin-bottom: 4px;
}

.value {
    font-weight: 500;
    color: #000000;
    word-wrap: break-word;
}

/* MODAL */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(5px);
}

.modal-content {
    background: rgba(255, 255, 255, 0.2);
    width: 90%;
    max-width: 420px;
    padding: 25px;
    border-radius: 18px;
    backdrop-filter: blur(10px);
    color: white;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.input-box {
    margin-bottom: 15px;
}

.input-box input {
    width: 100%;
    padding: 12px;
    border-radius: 10px;
    border: 2px solid rgba(255, 255, 255, 0.5);
    background: rgba(0, 0, 0, 0.15);
    color: white;
}

.input-box input::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

/* === RESPONSIVE — HP ONLY === */
@media (max-width: 768px) {
    .header {
        padding: 0 15px;
        height: 60px;
    }
    .logo img { height: 40px; }

    .nav {
        display: none;
        position: absolute;
        top: 60px;
        left: 0;
        width: 100%;
        background: white;
        flex-direction: column;
        padding: 12px 0;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        border-radius: 0 0 16px 16px;
        z-index: 999;
        gap: 0;
    }
    .nav.active {
        display: flex !important;
    }
    .nav a {
        padding: 12px 20px;
        font-size: 15px;
        border-bottom: 1px solid #eee;
        margin: 0;
        transition: all 0.2s ease;
    }
    .nav a:last-child {
        border-bottom: none;
    }
    .nav a:hover {
        background: #f0f7ff;
        transform: translateX(4px);
    }

    /* TAMPILKAN HAMBURGER HANYA DI HP */
    .menu-toggle {
        display: flex !important;
        background: none;
        border: none;
        font-size: 26px;
        color: #4F80FF;
        cursor: pointer;
        margin-left: auto;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        transition: background 0.3s;
    }
    .menu-toggle:hover {
        background: #f0f7ff;
    }

    .main-content {
        margin-top: 80px;
        padding: 15px;
    }

    .profile-card {
        padding: 20px;
    }

    .photo-box img {
        width: 120px;
        height: 120px;
    }

    .name-text {
        font-size: 18px;
    }

    .btn {
        font-size: 14px;
        padding: 10px;
    }

    .data-box {
        padding: 15px;
    }

    .label { font-size: 15px; }
    .value { font-size: 15px; }
}

@media (max-width: 480px) {
    .header { height: 55px; }
    .logo img { height: 35px; }
    .photo-box img { width: 100px; height: 100px; }
    .name-text { font-size: 16px; }
    .btn { font-size: 13px; padding: 8px; }
    .modal-content { width: 95%; padding: 20px; }
}
</style>
</head>

<body>

<!-- LOADING SCREEN -->
<div class="page-loader" id="pageLoader">
    <img src="image/logo.png" alt="Logo" class="logo">
    <div class="text">Memuat...</div>
</div>

<!-- HEADER -->
<div class="header">
    <div class="logo">
        <img src="image/logo.png" alt="Logo BLK">
    </div>

    <!-- Hanya tampilkan hamburger di HP -->
    <script>
    if (window.innerWidth <= 768) {
        document.write('<button class="menu-toggle" onclick="toggleMenu()">☰</button>');
    }
    </script>

    <div class="nav">
        <a href="dashboard_peserta.php">HOME</a>
        <a href="jadwal.php">JADWAL</a>
        <a href="riwayat.php">RIWAYAT</a>
        <a href="profil_peserta.php" class="active">PROFIL</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="profile-card">
        <div class="photo-box">
            <img src="image/<?= htmlspecialchars($foto) ?>?v=<?= time() ?>" alt="Foto Profil">
        </div>
        <div class="name-text"><?= htmlspecialchars($nama) ?></div>
        <button class="btn btn-blue" onclick="openModal('modalFoto')">Edit</button>
    </div>

    <div class="data-box">
        <div class="label">NIK</div><div class="value"><?= htmlspecialchars($nik) ?></div>
        <div class="label">Nama</div><div class="value"><?= htmlspecialchars($nama) ?></div>
        <div class="label">Tanggal Lahir</div><div class="value"><?= date("d-m-Y", strtotime($tgl_lahir)) ?></div>
        <div class="label">Jenis Kelamin</div><div class="value"><?= htmlspecialchars($jk) ?></div>
        <div class="label">Alamat</div><div class="value"><?= htmlspecialchars($alamat) ?></div>
        <div class="label">No. HP</div><div class="value"><?= htmlspecialchars($nohp) ?></div>
        <div class="label">Email</div><div class="value"><?= htmlspecialchars($email) ?></div>
    </div>

    <button class="btn btn-blue2" onclick="window.location='forgot_password_profile.php'">Edit Password</button>
    <button class="btn btn-red" onclick="window.location='logout_peserta.php'">Logout</button>
</div>

<!-- MODAL UPLOAD FOTO -->
<div class="modal" id="modalFoto">
    <div class="modal-content">
        <h3>Upload Foto Profil</h3>
        <form action="update_foto.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="foto" required>
            <button class="btn btn-blue" style="width:100%;margin-top:15px;">Upload</button>
        </form>
        <button class="btn btn-red" onclick="closeModal('modalFoto')" style="width:100%;margin-top:10px;">Tutup</button>
    </div>
</div>


<script>
function toggleMenu() {
    document.querySelector('.nav').classList.toggle('active');
}

function openModal(id) { document.getElementById(id).style.display = "flex"; }
function closeModal(id) { document.getElementById(id).style.display = "none"; }

window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
}

// Loading screen
document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById('pageLoader');
    setTimeout(() => {
        loader.style.opacity = '0';
        setTimeout(() => {
            if (loader.parentNode) loader.remove();
        }, 300);
    }, 500);
});
</script>

</body>
</html>