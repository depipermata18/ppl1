<?php
require_once 'webblk/config/database.php';

$daftar_jurusan = [];
if (isset($conn) && $conn) {
    $result = $conn->query("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $daftar_jurusan[] = $row;
        }
    }
}

// Data resmi dari silengkap.disnakertrans.jatimprov.go.id
$ikon_map = [
    'Plate Welder FCAW 3G-UP/PF' => [
        'icon' => '🔥',
        'desc' => 'Pelatihan & sertifikasi juru las bersertifikat BNSP. Fokus pada efisiensi, produktivitas, dan keselamatan kerja.'
    ],
    'Pipe Welder SMAW 6G-UP HIL/HLO 45' => [
        'icon' => '🔩',
        'desc' => 'Lanjutan las pipa tingkat ahli. Disertifikasi oleh LSP yang diakui BNSP.'
    ],
    'Tata Rias Pengantin Muslim Modifikasi' => [
        'icon' => '💄',
        'desc' => 'Menguasai 11 unit kompetensi rias pengantin muslim modern, dari rias wajah hingga manajemen usaha.'
    ],
    'Menjahit Pakaian dengan Mesin' => [
        'icon' => '🧵',
        'desc' => 'Membuat kemeja, celana, blouse, rok, dan hiasan busana sesuai SOP industri.'
    ],
    'Pengelola Administrasi Perkantoran' => [
        'icon' => '💼',
        'desc' => 'Menguasai 14 unit kompetensi administrasi perkantoran berbasis SKKNI, siap jadi frontliner atau tata usaha.'
    ]
];

foreach ($daftar_jurusan as &$jur) {
    $nama = $jur['nama_jurusan'];
    if (isset($ikon_map[$nama])) {
        $jur['icon'] = $ikon_map[$nama]['icon'];
        $jur['deskripsi'] = $ikon_map[$nama]['desc'];
    } else {
        $jur['icon'] = '🔧';
        $jur['deskripsi'] = 'Pelatihan keterampilan dasar hingga lanjutan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Download Aplikasi Tes Ujian BLK</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --accent: #8db4eb;
        --card-bg: rgba(255,255,255,0.55);
        --glass: rgba(255,255,255,0.35);
        --text: #111;
        --icon-default: #666;
        --icon-hover: #0056b3;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: "Poppins", sans-serif;
        background: linear-gradient(135deg, #d9d9d9, #8db4eb);
        color: var(--text);
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* HEADER */
    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 24px;
        background: var(--glass);
        backdrop-filter: blur(10px);
        position: sticky;
        top: 0;
        z-index: 50;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.5);
    }
    header .brand h1 {
        font-size: 20px;
        font-weight: 700;
        background: linear-gradient(90deg, #0056b3, #8db4eb);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    nav {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    nav a {
        text-decoration: none;
        color: var(--text);
        font-weight: 600;
        padding: 8px 10px;
        border-radius: 8px;
        transition: all 0.25s ease;
        position: relative;
    }
    nav a::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        width: 0;
        height: 2px;
        background: var(--accent);
        transition: all 0.3s ease;
        transform: translateX(-50%);
    }
    nav a:hover::after { width: 80%; }
    nav a:hover { color: #0056b3; transform: translateY(-2px); }
.login-btn {
    padding: 10px 24px;
    background: var(--accent);
    color: #111;
    font-weight: 600;
    letter-spacing: 0.4px;
    border-radius: 999px;
    text-decoration: none;
    display: inline-block;
    position: relative;

    /* elegan depth */
    box-shadow: 
        0 4px 10px rgba(0,0,0,0.08),
        0 1px 2px rgba(0,0,0,0.06);

    transition: all 0.25s ease;
}

/* inner light biar keliatan “mahal” */
.login-btn::after {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
    pointer-events: none;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 8px 20px rgba(0,0,0,0.12),
        0 2px 4px rgba(0,0,0,0.08);
}

.login-btn:active {
    transform: translateY(0);
    box-shadow: 
        0 3px 8px rgba(0,0,0,0.1),
        inset 0 2px 4px rgba(0,0,0,0.1);
}

    /* MOBILE: sembunyikan nav */
    @media (max-width: 768px) {
        nav { display: none; }
        header { padding: 12px 16px; }
        header .brand h1 { font-size: 18px; }
    }

    /* ANIMASI */
    .animate {
        opacity: 0;
        transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .animate.slide-left { transform: translateX(-30px); }
    .animate.slide-right { transform: translateX(30px); }
    .animate.slide-up { transform: translateY(30px); }
    .animate.show {
        opacity: 1 !important;
        transform: translateX(0) !important;
        transform: translateY(0) !important;
    }

    /* HERO */
    .hero {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 48px 18px;
    }
    .hero-box {
        background: rgba(255, 255, 255, 0.42);
        backdrop-filter: blur(20px);
        padding: 36px;
        border-radius: 16px;
        width: 100%;
        max-width: 800px;
        text-align: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        border: 1px solid rgba(255,255,255,0.6);
    }
    .hero-text h2 {
        font-size: 32px;
        margin: 0 0 16px;
        font-weight: 800;
        background: linear-gradient(90deg, #0056b3, #1a75ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .hero-text p { font-size: 16px; margin: 0 0 16px; color: #222; }
    .download-btn {
    display: inline-block;
    background: linear-gradient(180deg, #f0f6ff, #dceaff);
    color: #004a99;
    padding: 16px 32px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #c6dcff;
    box-shadow: 
        0 6px 16px rgba(0, 86, 179, 0.15),
        inset 0 1px 0 rgba(255,255,255,0.7);
    transition: all 0.2s ease;
    position: relative;
}
    .download-btn:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 10px 22px rgba(0, 86, 179, 0.25),
        inset 0 1px 0 rgba(255,255,255,0.8);
}
    @keyframes wobble-bounce {
        0%,100% { transform: translateX(0) scale(1); }
        20% { transform: translateX(-6px) scale(1.03); }
        40% { transform: translateX(4px) scale(1.02); }
        60% { transform: translateX(-3px) scale(1.04); }
        80% { transform: translateX(2px) scale(1.03); }
    }
    .download-sub { display: block; font-size: 14px; margin-top: 6px; opacity: 0.9; }
    .download-note { display: block; font-size: 12px; margin-top: 8px; color: #d32f2f; font-style: italic; }

    /* SLIDESHOW */
    .slideshow-wrap {
        padding: 40px 18px;
        text-align: center;
    }
    .slideshow-container {
        position: relative;
        width: 100%;
        max-width: 900px;
        margin: auto;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(0,0,0,0.22);
        background: #fff;
        aspect-ratio: 16/9;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .slide-frame {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        opacity: 0; transition: opacity 0.6s ease-in-out;
        display: flex; align-items: center; justify-content: center; padding: 20px;
    }
    .slide-frame.active { opacity: 1; }
    .slide-frame img {
        width: 100%; height: 100%;
        object-fit: cover;
        border-radius: 14px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        border: 4px solid #fff;
        transition: transform 0.3s ease;
    }
    .slide-frame.active img { transform: scale(1.02); }
    .slider-btn {
        position: absolute; top: 50%; transform: translateY(-50%);
        background: rgba(255,255,255,0.85);
        border: none; width: 48px; height: 48px;
        border-radius: 50%; cursor: pointer;
        font-size: 22px; color: #111;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transition: all 0.25s ease;
    }
    .slider-btn:hover { background: #8db4eb; transform: scale(1.1) translateY(-50%); }
    #prev { left: 20px; }
    #next { right: 20px; }

    /* VIDEO */
    .video-section {
        padding: 40px 18px;
        text-align: center;
    }
    .video-wrap {
        max-width: 900px;
        margin: 0 auto;
        position: relative;
    }
    .video-iframe {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .video-iframe iframe {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%; border: 0;
    }
    .video-fallback {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: #f0f5ff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: var(--icon-hover);
        font-weight: 600;
        text-align: center;
        padding: 20px;
        border-radius: 16px;
    }

    /* TENTANG KAMI */
    .about-us {
        padding: 60px 18px;
        background: linear-gradient(135deg, #f0f7ff, #e6f0ff);
        margin: 30px auto;
        max-width: 1100px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,86,179,0.08);
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(141,180,235,0.3);
    }
    .about-header h2 {
        font-size: 32px;
        font-weight: 800;
        margin: 0 0 8px;
        background: linear-gradient(90deg, #0056b3, #1a75ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        text-align: center;
    }
    .subtitle { text-align: center; font-size: 18px; color: #0056b3; margin-bottom: 30px; }
    .about-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 16px;
        margin: 30px 0;
        text-align: center;
    }
    .stat-number { font-size: 28px; font-weight: 800; color: #0056b3; }
    .stat-label { font-size: 13px; color: #555; }
    .about-content {
        display: flex; gap: 24px; margin: 30px 0;
    }
    .about-text { flex: 2; font-size: 16px; line-height: 1.7; }
    .about-text p { margin: 0 0 16px; }
    .about-badge {
        flex: 1;
        background: #eef5ff;
        padding: 20px;
        border-radius: 14px;
        border-left: 4px solid #0056b3;
    }
    .badge-item {
        padding: 6px 0;
        font-size: 14px;
        font-weight: 600;
        color: #0056b3;
    }
    .about-mission {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px dashed #ddd;
    }
    .about-mission h3 {
        font-size: 22px;
        color: #0056b3;
        margin: 0 0 12px;
    }
    .about-mission p {
        font-size: 16px;
        color: #444;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* JURUSAN */
    .program-blk {
        padding: 40px 18px;
    }
    .info-blk {
        max-width: 1100px;
        margin: 0 auto;
        padding: 28px;
        background: var(--card-bg);
        border-radius: 14px;
        box-shadow: 0 10px 26px rgba(0,0,0,0.12);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.6);
    }
    .program-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        margin-top: 20px;
    }
    .jurusan-card {
        padding: 24px;
        background: #fff;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    .jurusan-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0,0,0,0.12);
    }
    .jurusan-icon {
        font-size: 48px;
        margin-bottom: 16px;
        color: var(--icon-default);
        transition: color 0.3s ease, transform 0.2s ease;
        display: block;
    }
    .jurusan-card:hover .jurusan-icon {
        color: var(--icon-hover);
        transform: scale(1.15);
    }
    .jurusan-card h3 {
        margin: 0 0 10px;
        font-size: 18px;
        color: #0056b3;
    }
    .jurusan-card p {
        font-size: 14px;
        color: #555;
        line-height: 1.5;
    }

    /* CTA & TATA CARA */
    .cta, #cara-download {
        padding: 20px 18px;
        text-align: center;
    }
    .cta a {
        display: inline-block;
        padding: 14px 28px;
        font-size: 18px;
        background: var(--accent);
        border-radius: 999px;
        text-decoration: none;
        color: #000;
        font-weight: 800;
        box-shadow: 0 10px 28px rgba(0,0,0,0.14);
        transition: transform 0.2s ease;
    }
    .cta a:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 34px rgba(0,0,0,0.2);
    }
    .steps-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin-top: 18px;
    }
    .step-card {
        padding: 22px;
        border-radius: 14px;
        background: #fff;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
    }
    .step-card:hover { transform: translateY(-4px); }

    /* KONTAK */
    .kontak-section {
        padding: 50px 18px;
        background: white;
        margin: 40px auto 0;
        max-width: 1100px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .kontak-section h2 {
        text-align: center;
        font-size: 26px;
        margin-bottom: 30px;
        color: #0056b3;
    }
    .kontak-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 24px;
        max-width: 800px;
        margin: 0 auto;
    }
    .kontak-item {
        text-align: center;
        padding: 20px;
        background: #f9fbff;
        border-radius: 14px;
    }
    .kontak-icon { font-size: 32px; color: #0056b3; margin-bottom: 10px; }
    .kontak-text { font-size: 15px; }
    .kontak-link {
        color: var(--accent);
        font-weight: 600;
        text-decoration: none;
    }
    .kontak-link:hover { text-decoration: underline; }

    /* FOOTER */
    footer {
        padding: 20px;
        text-align: center;
        margin: 30px auto 0;
        max-width: 1100px;
        background: var(--glass);
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.5);
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .hero { padding: 32px 16px; }
        .hero-box { padding: 24px; }
        .hero-text h2 { font-size: 24px; }
        .about-us, .program-blk, .kontak-section { padding: 32px 16px; margin: 20px 12px; }
        .about-content { flex-direction: column; }
        .kontak-grid { grid-template-columns: 1fr; }
        .info-blk { padding: 20px; }
        .jurusan-card, .step-card { padding: 20px; }
        .jurusan-icon { font-size: 36px; }
        footer { margin: 20px 12px 0; }
    }
    @keyframes zoomIn {
    from {
        transform: scale(0.5);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="brand">
        <h1>UPT BLK Nganjuk</h1>
    </div>
    <nav>
        <a href="#tentang">Tentang</a>
        <a href="#jurusan">Jurusan</a>
        <a href="#download">Download</a>
        <a href="#kontak">Kontak</a>
    </nav>
    <a href="webblk/login.php" class="login-btn">Login</a>
</header>

<!-- HERO -->
<section class="hero" id="download">
    <div class="hero-box">
        <div class="hero-text">
            <h2>Download Aplikasi Tes Ujian BLK</h2>
            <p>Unduh aplikasi resmi BLK untuk mengikuti tes ujian dengan mudah, cepat, dan aman.</p>
            <a href="https://e-blk.pbltifnganjuk.com/E%20BLK.apk" 
                class="download-btn" 
                onclick="showPopup()" 
                download>
                📥 Download Aplikasi (.APK)
                <span class="download-sub">Versi terbaru • Aman & Resmi</span>
                <span class="download-note">Jika gagal, hubungi admin via kontak di bawah.</span>
            </a>
        </div>
    </div>
</section>

<!-- SLIDESHOW -->
<section class="slideshow-wrap">
    <h2>Foto UPT BLK Nganjuk</h2>
    <div class="slideshow-container" role="region" aria-label="Galeri Foto BLK">
        <div class="slide-frame active">
            <img src="webblk/image/blk.jpeg" alt="Foto BLK 1">
        </div>
        <div class="slide-frame">
            <img src="webblk/image/blk_2.jpeg" alt="Foto BLK 2">
        </div>
        <div class="slide-frame">
            <img src="webblk/image/blk_3.jpeg" alt="Foto BLK 3">
        </div>
        <div class="slide-frame">
            <img src="webblk/image/blk_4.jpeg" alt="Foto BLK 4">
        </div>
        <button id="prev" class="slider-btn" aria-label="Sebelumnya">&#10094;</button>
        <button id="next" class="slider-btn" aria-label="Selanjutnya">&#10095;</button>
    </div>
</section>

<!-- VIDEO -->
<section class="video-section animate slide-up">
    <h2>Video Profil BLK Nganjuk</h2>
    <div class="video-wrap">
        <div class="video-iframe">
            <iframe 
                src="https://www.youtube.com/embed/ght0djspDiM" 
                title="Video Profil BLK Nganjuk"
                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy"></iframe>
            <div class="video-fallback">
                <div>🎥</div>
                <p>Video sedang dalam pembaruan.<br>Segera hadir kembali!</p>
            </div>
        </div>
        <p style="text-align:center; margin-top:14px; color:#666; font-size:14px;">
            <em>ℹ️ Jika video tidak muncul, berarti sedang dalam pembaruan oleh admin.</em>
        </p>
    </div>
</section>

<!-- TENTANG KAMI -->
<section class="about-us animate slide-right" id="tentang">
    <div style="max-width:900px; margin:0 auto;">
        <div class="about-header">
            <h2>Kami UPT BLK Nganjuk</h2>
            <p class="subtitle">Membangun SDM Unggul Sejak 1983</p>
        </div>
        <div class="about-stats">
            <div class="stat-item"><div class="stat-number">371</div><div class="stat-label">Alumni</div></div>
            <div class="stat-item"><div class="stat-number">242</div><div class="stat-label">Penempatan</div></div>
            <div class="stat-item"><div class="stat-number">13</div><div class="stat-label">Program</div></div>
            <div class="stat-item"><div class="stat-number">13</div><div class="stat-label">Perusahaan Mitra</div></div>
        </div>
        <div class="about-content">
            <div class="about-text">
                <p><strong>UPT BLK Nganjuk</strong> adalah Unit Pelaksana Teknis di bawah Disnakertrans Provinsi Jawa Timur yang berkomitmen mencetak tenaga kerja terampil, kompeten, dan siap kerja.</p>
                <p>Berdiri sejak <strong>7 Juli 1983</strong> dengan nama awal <em>KLK (Kursus Latihan Kerja)</em>, kami terus bertransformasi menjadi lembaga pelatihan berstandar nasional yang diakui BNSP.</p>
                <p>Semua lulusan mendapatkan <strong>Sertifikat Kompetensi resmi dari BNSP</strong>.</p>
            </div>
            <div class="about-badge">
                <div class="badge-item">🔖 Nomor VIN: 1903351802</div>
                <div class="badge-item">🎓 Sertifikasi BNSP</div>
                <div class="badge-item">🏢 Mitra 13 Perusahaan</div>
            </div>
        </div>
        <div class="about-mission">
            <h3>Visi Kami</h3>
            <p>Menjadi lembaga pelatihan unggulan yang menghasilkan SDM berdaya saing tinggi dan berakhlak mulia.</p>
        </div>
    </div>
</section>

<!-- JURUSAN -->
<section class="program-blk animate slide-left" id="jurusan">
    <div class="info-blk">
        <h2 style="text-align:center; margin:0 0 20px; font-size:26px;">Program Pelatihan BLK Nganjuk</h2>
        <div class="program-grid">
            <?php if (!empty($daftar_jurusan)): ?>
                <?php foreach ($daftar_jurusan as $jur): ?>
                    <div class="jurusan-card">
                        <span class="jurusan-icon"><?= htmlspecialchars($jur['icon']) ?></span>
                        <h3><?= htmlspecialchars($jur['nama_jurusan']) ?></h3>
                        <p><?= htmlspecialchars($jur['deskripsi']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="jurusan-card">
                    <span class="jurusan-icon">⚠️</span>
                    <h3>Belum Ada Program</h3>
                    <p>Data pelatihan sedang dalam pembaruan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA DAFTAR -->
<section class="cta animate slide-up">
    <a href="https://sinaker.disnakertrans.jatimprov.go.id/blk/859d44ff-cb2d-49a4-a598-f19683cf4746" target="_blank" rel="noopener">Daftar Pelatihan Sekarang</a>
</section>

<!-- TATA CARA -->
<section class="info-blk animate slide-up" id="cara-download">
    <h2 style="text-align:center;">Tata Cara Download Aplikasi</h2>
    <div class="steps-grid">
        <div class="step-card">
            <div style="font-size:36px;">📥</div>
            <h3>1. Klik Tombol Download</h3>
            <p>Klik tombol <b>Download Aplikasi (.APK)</b> di atas.</p>
        </div>
        <div class="step-card">
            <div style="font-size:36px;">⬇️</div>
            <h3>2. Tunggu Hingga Selesai</h3>
            <p>Pastikan koneksi internet stabil selama proses download.</p>
        </div>
        <div class="step-card">
            <div style="font-size:36px;">📂</div>
            <h3>3. Buka File APK</h3>
            <p>Buka <b>File Manager</b> → Folder <b>Download</b>.</p>
        </div>
        <div class="step-card">
            <div style="font-size:36px;">🔐</div>
            <h3>4. Izinkan Sumber Tidak Dikenal</h3>
            <p>Aktifkan di **Pengaturan Keamanan** HP Anda.</p>
        </div>
        <div class="step-card">
            <div style="font-size:36px;">✅</div>
            <h3>5. Install & Gunakan</h3>
            <p>Klik **Install** dan tunggu. Aplikasi siap digunakan!</p>
        </div>
    </div>
</section>

<!-- KONTAK -->
<section class="kontak-section animate slide-right" id="kontak">
    <h2>📞 Kontak UPT BLK Nganjuk</h2>
    <div class="kontak-grid">
        <div class="kontak-item">
            <div class="kontak-icon">📍</div>
            <div class="kontak-text">Jl. Kapten Kasihin HS No. 3<br>Cangkringan, Nganjuk</div>
        </div>
        <div class="kontak-item">
            <div class="kontak-icon">📞</div>
            <div class="kontak-text">0358 321048</div>
        </div>
        <div class="kontak-item">
            <div class="kontak-icon">💬</div>
            <div class="kontak-text">
                <a href="https://wa.me/6285713573000" class="kontak-link">0857 1357 3000</a>
            </div>
        </div>
        <div class="kontak-item">
            <div class="kontak-icon">✉️</div>
            <div class="kontak-text">
                <a href="mailto:blknganjuk@gmail.com" class="kontak-link">blknganjuk@gmail.com</a>
            </div>
        </div>
    </div>
</section>

<footer>
    © 2025 UPT BLK Nganjuk • Semua Hak Dilindungi
</footer>

<!-- SCRIPTS -->
<script>
(() => {
    const slides = Array.from(document.querySelectorAll('.slide-frame'));
    const prev = document.getElementById('prev');
    const next = document.getElementById('next');
    let idx = 0;
    const show = (i) => {
        slides.forEach(s => s.classList.remove('active'));
        slides[i]?.classList.add('active');
    };
    prev?.addEventListener('click', () => {
        idx = (idx - 1 + slides.length) % slides.length;
        show(idx);
    });
    next?.addEventListener('click', () => {
        idx = (idx + 1) % slides.length;
        show(idx);
    });
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Smooth scroll
    document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({ top: target.offsetTop - 70, behavior: 'smooth' });
            }
        });
    });

    // Animasi scroll
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('show');
            else entry.target.classList.remove('show');
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.animate').forEach(el => observer.observe(el));

    // Fallback video
    const iframe = document.querySelector('.video-iframe iframe');
    if (iframe) {
        iframe.onload = function() {
            setTimeout(() => {
                const fallback = iframe.closest('.video-iframe').querySelector('.video-fallback');
                if (fallback) fallback.style.display = 'flex';
            }, 4000);
        };
    }
});
<script>
function showPopup() {
    document.getElementById("popup").style.display = "flex";
}

function closePopup() {
    document.getElementById("popup").style.display = "none";
}
</script>

</script>

<div id="popup" style="
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:999;
">

    <div style="
    background:white;
    padding:30px;
    border-radius:15px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
    animation: zoomIn 0.3s ease;
    width:300px;
    ">

        <h2>🎉 Download Dimulai!</h2>
        <p>Aplikasi sedang didownload...</p>

        <button onclick="closePopup()" style="
        margin-top:15px;
        padding:10px 20px;
        border:none;
        background:#8db4eb;
        border-radius:10px;
        cursor:pointer;
        font-weight:bold;
        ">
        OK
        </button>

    </div>
</div>
</body>
</html>