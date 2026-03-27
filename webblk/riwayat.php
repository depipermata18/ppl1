<?php
session_start();
include 'config/database.php';

if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];

$pes = $conn->prepare("SELECT id_peserta, nama_peserta FROM peserta WHERE nik=?");
$pes->bind_param("s", $nik);
$pes->execute();
$d_pes = $pes->get_result()->fetch_assoc();

$id_peserta = $d_pes['id_peserta'];
$nama = $d_pes['nama_peserta'];

$q = $conn->prepare("SELECT * FROM laporan WHERE id_peserta=? ORDER BY id_laporan DESC");
$q->bind_param("i", $id_peserta);
$q->execute();
$riwayat = $q->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Tes</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
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

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #ffffff, #ffffff);
    color: #333333;
    line-height: 1.6;
}

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

/* Hamburger hanya untuk HP — sembunyikan di desktop */
.menu-toggle {
    display: none !important;
}

.main-content {
    margin-top: 90px;
    padding: 20px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.card-container {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.title { font-size: 22px; font-weight: bold; color: #000; margin-bottom: 10px; }
.subtitle { font-size: 16px; color: #000; margin-bottom: 15px; }
.divider { width: 60px; height: 3px; background: #4F80FF; margin-bottom: 20px; }

.history-item {
    background: #4F80FF;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.item-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.icon {
    width: 35px;
    height: 35px;
    margin-right: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f0f5ff;
    color: #4F80FF;
    font-size: 20px;
}

.text { font-size: 18px; color: #333; }

.illustration {
    width: 100%;
    max-width: 550px;
    margin: 20px auto;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.message {
    font-size: 16px;
    color: #E97E7E;
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 10px;
}

.finish-btn {
    display: inline-block;
    padding: 10px 30px;
    background: #197bff;
    color: white;
    border-radius: 20px;
    font-size: 14px;
    text-decoration: none;
    transition: background 0.3s, transform 0.2s;
}

.finish-btn:hover {
    background: #0d65d0;
    transform: scale(1.03);
}

/* === HP ONLY === */
@media (max-width: 768px) {
    .header {
        padding: 0 15px;
        height: 60px;
    }
    .logo img { height: 40px; }
    
    /* Sembunyikan menu default */
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
    .nav a:last-child { border-bottom: none; }
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

    .main-content { margin-top: 80px; }
    .card-container { padding: 20px; }
    .title { font-size: 20px; }
    .subtitle { font-size: 14px; }
    .item-row { font-size: 16px; }
    .icon { width: 30px; height: 30px; font-size: 18px; }
    .finish-btn { font-size: 13px; padding: 9px 25px; }
}

@media (max-width: 480px) {
    .header { height: 55px; }
    .logo img { height: 35px; }
    .title { font-size: 18px; }
    .subtitle { font-size: 12px; }
    .item-row { font-size: 14px; }
    .icon { width: 28px; height: 28px; font-size: 16px; }
    .finish-btn { font-size: 12px; padding: 8px 22px; }
}
</style>
</head>

<body>

<div class="page-loader" id="pageLoader">
    <img src="image/logo.png" alt="Logo" class="logo">
    <div class="text">Memuat...</div>
</div>

<div class="header">
    <div class="logo">
        <img src="image/logo.png" alt="Logo BLK">
    </div>
    
    <!-- Hanya tampilkan hamburger jika di HP -->
    <script>
    // Sisipkan hamburger hanya di HP
    if (window.innerWidth <= 768) {
        document.write('<button class="menu-toggle" onclick="toggleMenu()">☰</button>');
    }
    </script>
    
    <div class="nav">
        <a href="dashboard_peserta.php">HOME</a>
        <a href="jadwal.php">JADWAL</a>
        <a href="riwayat.php" class="active">RIWAYAT</a>
        <a href="profil_peserta.php">PROFIL</a>
    </div>
</div>

<div class="main-content">
    <div class="card-container">
        <div class="title">Riwayat Ujian Peserta</div>
        <div class="subtitle"><?= htmlspecialchars($nama) ?></div>
        <div class="divider"></div>

        <?php if ($riwayat->num_rows > 0): ?>
            <?php 
            $last = $riwayat->fetch_assoc();
            ?>
            <div class="history-item">
                <div class="item-row">
                    <div class="icon">📅</div>
                    <div class="text"><?= htmlspecialchars($last['tanggal_tes']) ?></div>
                </div>
                <div class="item-row">
                    <div class="icon">⭐</div>
                    <div class="text"><?= htmlspecialchars($last['total_poin']) ?> Poin</div>
                </div>
                <div class="item-row">
                    <div class="icon">✅</div>
                    <div class="text"><?= htmlspecialchars($last['total_benar']) ?> Benar</div>
                </div>
                <div class="item-row">
                    <div class="icon">❌</div>
                    <div class="text"><?= htmlspecialchars($last['total_salah']) ?> Salah</div>
                </div>
            </div>

            <img src="image/lp.png" alt="Ilustrasi Hasil Ujian" class="illustration">

            <div class="message">
                Untuk pengumuman lihat di instagram resmi uptblknganjuk
            </div>

        <?php else: ?>
            <p>Belum ada riwayat tes.</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard_peserta.php" class="finish-btn">Selesai &gt;&gt;</a>
        </div>
    </div>
</div>

<script>
function toggleMenu() {
    document.querySelector('.nav').classList.toggle('active');
}

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