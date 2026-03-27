<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'config/database.php';

if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik_login = $_SESSION['nik'];

$sql = "SELECT nama_peserta, jenis_kelamin, tgl_lahir, alamat, foto_profil, id_jurusan, id_peserta 
        FROM peserta 
        WHERE nik = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nik_login);
$stmt->execute();
$result = $stmt->get_result();
$peserta = $result->fetch_assoc();

if ($peserta) {
    $nama = htmlspecialchars($peserta['nama_peserta']);
    $jenis_kelamin = htmlspecialchars($peserta['jenis_kelamin']);
    $tempat_tgl_lahir = htmlspecialchars($peserta['alamat'] . ", " . date('d F Y', strtotime($peserta['tgl_lahir'])));
    $foto = $peserta['foto_profil'] ?? null;
    $id_jurusan = (int)$peserta['id_jurusan'];
    $id_peserta = (int)$peserta['id_peserta'];
} else {
    $nama = "Tidak ditemukan";
    $jenis_kelamin = "-";
    $tempat_tgl_lahir = "-";
    $foto = null;
    $id_jurusan = 0;
    $id_peserta = 0;
}
$stmt->close();

$btn_status = 'disabled-gray';
$btn_text = 'Belum Waktunya';
$btn_href = '#';

if ($id_peserta > 0) {
    $stmt_lap = $conn->prepare("SELECT id_laporan FROM laporan WHERE id_peserta = ? LIMIT 1");
    $stmt_lap->bind_param("i", $id_peserta);
    $stmt_lap->execute();
    $laporan_exists = $stmt_lap->get_result()->num_rows > 0;
    $stmt_lap->close();

    if ($laporan_exists) {
        $btn_status = 'disabled-green';
        $btn_text = 'Ujian Selesai';
    } else {
        if ($id_jurusan > 0) {
            $stmt_seleksi = $conn->prepare("SELECT waktu_mulai, waktu_selesai FROM seleksi WHERE id_jurusan = ? LIMIT 1");
            $stmt_seleksi->bind_param("i", $id_jurusan);
            $stmt_seleksi->execute();
            $seleksi = $stmt_seleksi->get_result()->fetch_assoc();
            $stmt_seleksi->close();

            if ($seleksi) {
                $waktu_mulai = new DateTime($seleksi['waktu_mulai']);
                $waktu_selesai = new DateTime($seleksi['waktu_selesai']);
                $sekarang = new DateTime();

                if ($sekarang < $waktu_mulai) {
                    $btn_status = 'disabled-gray';
                    $btn_text = 'Belum Waktunya';
                } elseif ($sekarang >= $waktu_mulai && $sekarang <= $waktu_selesai) {
                    $btn_status = 'enabled-blue';
                    $btn_text = 'Mulai Ujian';
                    $btn_href = 'tes.php?id_peserta=' . $id_peserta;
                } else {
                    $btn_status = 'disabled-red';
                    $btn_text = 'Waktu Habis';
                }
            } else {
                $btn_status = 'disabled-gray';
                $btn_text = 'Jadwal Belum Tersedia';
            }
        } else {
            $btn_status = 'disabled-gray';
            $btn_text = 'Jurusan Tidak Valid';
        }
    }
} else {
    $btn_status = 'disabled-gray';
    $btn_text = 'Data Tidak Lengkap';
}

$timeline_sql = "SELECT nama_jadwal, waktu_mulai, waktu_selesai, keterangan
                 FROM jadwal
                 WHERE id_jurusan = ?
                 ORDER BY waktu_mulai ASC";
$stmt2 = $conn->prepare($timeline_sql);
$stmt2->bind_param("i", $id_jurusan);
$stmt2->execute();
$result2 = $stmt2->get_result();

$timeline_events = [];
while ($row = $result2->fetch_assoc()) {
    $timeline_events[] = [
        "title" => $row['nama_jadwal'],
        "date" => date('d M Y', strtotime($row['waktu_mulai'])) . " - " . date('d M Y', strtotime($row['waktu_selesai'])),
        "desc" => $row['keterangan']
    ];
}
$stmt2->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* === LOADING SCREEN (PER HALAMAN) === */
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

        /* === SISA STYLE TETAP SAMA === */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #fff8ff, #ffffff);
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

        .nav a:hover {
            color: #4F80FF;
        }

        .logout-btn {
            background: #c70000;
            padding: 10px 25px;
            color: white;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
            margin-left: auto;
            min-width: 80px;
            text-align: center;
        }

        .logout-btn:hover {
            background: #a50000;
            transform: scale(1.05);
        }

        .hero-banner {
            position: relative;
            width: 100%;
            height: 550px;
            background: url('image/blk.jpeg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            margin-top: 70px;
            z-index: 999;
            overflow: hidden;
        }

        .hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(79, 128, 255, 0.6);
            z-index: -1;
        }

        .hero-content {
            max-width: 800px;
            padding: 20px;
            z-index: 1;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .hero-banner.loaded .hero-content {
            opacity: 1;
            transform: translateY(0);
        }

        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0 0 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            font-weight: 400;
            margin: 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        }

        .cta-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            color: white;
            font-size: 26px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 25px;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .cta-btn.disabled-gray {
            background: #9e9e9e !important;
            cursor: not-allowed !important;
            pointer-events: none;
            animation: none;
        }

        .cta-btn.disabled-red {
            background: #e53935 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            animation: none;
        }

        .cta-btn.disabled-green {
            background: #4caf50 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            animation: none;
        }

        .cta-btn.enabled-blue {
            background: #4F80FF !important;
            cursor: pointer !important;
            pointer-events: auto;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .hint-text {
            font-size: 0.9rem;
            font-weight: 400;
            color: #ffffff;
            margin-top: 10px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            text-align: center;
        }

        .profile-box {
            background: #ffffff;
            border-radius: 20px;
            margin: 40px auto;
            width: 95%;
            max-width: 1300px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            padding: 40px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        main.loaded .profile-box {
            opacity: 1;
            transform: translateY(0);
        }

        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 30px;
            background: #4F80FF;
            color: #ffffff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .photo {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(145deg, #dbe6ff, #f3f7ff);
            border: 2px solid #8db4eb;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .photo:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            object-position: center;
        }

        .info {
            flex: 1;
            margin-left: 40px;
        }

        .info h3 {
            margin: 0 0 8px;
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
        }

        .info p {
            margin: 0 0 16px;
            font-size: 16px;
            color: #e0e7ff;
        }

        .timeline-container {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 40px;
        }

        .timeline {
            position: relative;
            width: 100%;
            max-width: 600px;
            padding: 30px 0;
            margin: 0 auto;
        }

        .timeline::before {
            content: "";
            position: absolute;
            left: 50%;
            width: 4px;
            background: linear-gradient(to bottom, #4F80FF, #8db4eb);
            top: 0;
            bottom: 0;
            transform: translateX(-50%);
            border-radius: 10px;
            z-index: 1;
        }

        .timeline-item {
            position: relative;
            width: 42%;
            background: #f8fbff;
            padding: 22px 28px;
            margin: 35px auto;
            border-radius: 20px;
            box-shadow: 0 6px 15px rgba(79, 128, 255, 0.1);
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.5s ease;
            z-index: 2;
        }

        .timeline-item.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .timeline-item:hover {
            box-shadow: 0 8px 20px rgba(79, 128, 255, 0.18);
        }

        .timeline-dot {
            position: absolute;
            top: 50%;
            width: 24px;
            height: 24px;
            background: #4F80FF;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 10px rgba(79, 128, 255, 0.4);
            transform: translateY(-50%);
            z-index: 3;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .timeline-item:nth-child(odd) {
            margin-right: auto;
            margin-left: 0;
            transform: translateX(-20px);
        }
        .timeline-item:nth-child(odd) .timeline-dot {
            right: -12px;
        }

        .timeline-item:nth-child(even) {
            margin-left: auto;
            margin-right: 0;
            transform: translateX(20px);
        }
        .timeline-item:nth-child(even) .timeline-dot {
            left: -12px;
        }

        .timeline-item:hover .timeline-dot {
            transform: translateY(-50%) scale(1.2);
            box-shadow: 0 6px 15px rgba(79, 128, 255, 0.6);
        }

        .timeline-item:nth-child(odd).visible {
            transform: translateX(0);
        }
        .timeline-item:nth-child(even).visible {
            transform: translateX(0);
        }

        /* === HP: DOT PAS DI GARIS === */
        @media (max-width: 768px) {
            .header { padding: 0 15px; height: 60px; }
            .logo img { height: 40px; }
            .menu-toggle {
                display: block;
                background: none;
                border: none;
                font-size: 26px;
                color: #4F80FF;
                cursor: pointer;
                margin-left: auto;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background 0.3s;
            }
            .menu-toggle:hover { background: #f0f7ff; }
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
            .nav.active { display: flex !important; }
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
                color: #4F80FF;
            }

            .hero-banner { height: 280px; margin-top: 60px; padding: 0 15px; }
            .hero-content { padding: 15px; }
            .hero-content h1 { font-size: 1.6rem; }
            .hero-content p { font-size: 0.95rem; }
            .cta-btn { font-size: 18px; padding: 10px 22px; margin-top: 15px; }
            .hint-text { font-size: 0.8rem; }

            .top-section {
                flex-direction: column;
                text-align: center;
                gap: 18px;
                padding: 18px;
            }
            .photo { width: 110px; height: 110px; }
            .info { margin-left: 0; }
            .info h3, .info p { text-align: center; font-size: 15px; }

            /* TIMELINE HP — DOT PAS DI GARIS */
            .timeline {
                padding: 25px 0;
                position: relative;
            }
            .timeline::before {
                left: 20px;
                width: 3px;
                transform: none;
            }
            .timeline-item {
                width: calc(100% - 40px);
                margin: 20px 0 20px 40px !important;
                padding: 16px;
                font-size: 14px;
                transform: none !important;
                opacity: 1 !important;
            }
            .timeline-dot {
                left: -20px !important;
                right: auto !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                width: 20px !important;
                height: 20px !important;
                border-width: 3px !important;
            }
        }

        @media (min-width: 769px) {
            .menu-toggle { display: none !important; }
        }

        @media (max-width: 480px) {
            .hero-banner { height: 220px; }
            .hero-content h1 { font-size: 1.4rem; }
            .hero-content p { font-size: 0.9rem; }
            .cta-btn { font-size: 16px; padding: 9px 20px; }
            .timeline-item { margin-left: 36px !important; padding: 14px; }
        }
    </style>
</head>
<body>

<!-- LOADING SCREEN SETIAP HALAMAN -->
<div class="page-loader" id="pageLoader">
    <img src="image/logo.png" alt="Logo" class="logo">
    <div class="text">Memuat...</div>
</div>

<div class="header">
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
    <div class="nav">
        <a href="dashboard_peserta.php">HOME</a>
        <a href="jadwal.php">JADWAL</a>
        <a href="riwayat.php">RIWAYAT</a>
        <a href="profil_peserta.php">PROFIL</a>
    </div>
</div>

<main>
    <div class="tampungan">
        <div class="hero-banner">
            <div class="hero-content">
                <h1>Mulai Perjalanan Karir Anda di BLK Nganjuk</h1>
                <p>Bersiaplah untuk Masa Depan Cerah dengan Pelatihan dan Sertifikasi Terbaik.</p>
                <a href="<?= htmlspecialchars($btn_href); ?>" class="cta-btn <?= $btn_status; ?>">
                    <?= htmlspecialchars($btn_text); ?>
                </a>
                <p class="hint-text">Klik tombol di atas untuk memulai ujian Anda.</p>
            </div>
        </div>

        <div class="profile-box">
            <div class="top-section">
                <div class="photo">
                    <?php if ($foto): ?>
                        <img src="image/<?= htmlspecialchars($foto); ?>" alt="Foto Profil">
                    <?php else: ?>
                        FOTO
                    <?php endif; ?>
                </div>
                <div class="info">
                    <h3>Nama</h3>
                    <p><?= htmlspecialchars($nama); ?></p>
                    <h3>Jenis Kelamin</h3>
                    <p><?= htmlspecialchars($jenis_kelamin); ?></p>
                    <h3>Tempat, Tanggal Lahir</h3>
                    <p><?= htmlspecialchars($tempat_tgl_lahir); ?></p>
                </div>
            </div>

            <hr style="margin:30px 0;border:1px solid #d2d8ff;">

            <div class="timeline-container">
                <div class="timeline">
                    <?php if (empty($timeline_events)): ?>
                        <p style="text-align:center;color:#001a70;">Belum ada jadwal untuk jurusan Anda.</p>
                    <?php else: ?>
                        <?php foreach ($timeline_events as $index => $ev): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <strong><?= htmlspecialchars($ev["title"]); ?></strong>
                                <p><?= htmlspecialchars($ev["date"]); ?></p>
                                <p><?= htmlspecialchars($ev["desc"]); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleMenu() {
    document.querySelector('.nav').classList.toggle('active');
}

document.addEventListener("DOMContentLoaded", function () {
    // TAMPILKAN LOADING SETIAP HALAMAN DIMUAT
    const loader = document.getElementById('pageLoader');
    setTimeout(() => {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.remove();
        }, 300);
    }, 500); // 0.5 detik

    // Trigger animasi konten
    const hero = document.querySelector('.hero-banner');
    const main = document.querySelector('main');
    if (hero) hero.classList.add('loaded');
    if (main) main.classList.add('loaded');

    // Scroll reveal timeline
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.timeline-item').forEach(item => observer.observe(item));
});
</script>

</body>
</html>