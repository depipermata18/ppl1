<?php
// Ambil jurusan dari database
require_once 'config/database.php';

$daftar_jurusan = [];
if (isset($conn) && $conn) {
    $result = $conn->query("SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $daftar_jurusan[] = $row;
        }
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
    :root{
        --accent:#8db4eb;
        --card-bg: rgba(255,255,255,0.55);
        --glass: rgba(255,255,255,0.35);
        --text:#111;
    }

    *{box-sizing:border-box; margin:0; padding:0;}
    body {
        font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        background: linear-gradient(135deg, #ffffff, #8db4eb);
        color: var(--text);
        -webkit-font-smoothing:antialiased;
        -moz-osx-font-smoothing:grayscale;
        line-height:1.6;
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
        gap:12px;
    }

    header .brand h1 {
        font-size: 20px;
        font-weight:700;
    }

    nav {
        display:flex;
        gap:12px;
        align-items:center;
    }

    nav a {
        text-decoration: none;
        color: var(--text);
        font-weight: 600;
        padding: 8px 10px;
        border-radius:8px;
        transition: all 0.25s ease;
    }
    nav a:hover {
        background: rgba(0,0,0,0.08);
        color: #0056b3;
        transform: translateY(-2px);
    }

    .login-btn {
        padding: 8px 16px;
        background: var(--accent);
        color: #000;
        font-weight: 700;
        border-radius: 30px;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        transition: transform .2s ease;
    }
    .login-btn:hover{ transform:translateY(-3px); }

    /* Scroll Reveal - Section */
    .fade-scroll {
        opacity: 0;
        transform: translateY(30px);
        transition: opacity 0.8s ease, transform 0.8s ease;
    }
    .fade-scroll.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Scroll Reveal - Per Kotak */
    .fade-scroll-item {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .fade-scroll-item.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* HERO (tanpa gambar) */
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
    }

    .hero-text h2 {
        font-size: 32px;
        margin: 0 0 16px 0;
        font-weight: 800;
    }

    .hero-text p {
        font-size: 16px;
        margin: 0 0 24px 0;
        color: #222;
    }

    /* Download Button - Animasi Lucu */
    .download-btn {
        display: inline-block;
        background: linear-gradient(180deg, #57a4f2, #7fbcf0);
        padding: 16px 32px;
        border-radius: 999px;
        font-size: 18px;
        color: #000;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 8px 22px rgba(0,0,0,0.12);
        transition: all 0.3s ease;
    }

    @keyframes wobble-bounce {
        0%, 100% { transform: translateX(0) scale(1); }
        20% { transform: translateX(-6px) scale(1.03); }
        40% { transform: translateX(4px) scale(1.02); }
        60% { transform: translateX(-3px) scale(1.04); }
        80% { transform: translateX(2px) scale(1.03); }
    }

    .download-btn:hover {
        animation: wobble-bounce 0.7s ease-in-out;
        background: linear-gradient(180deg, #6ab2f5, #8ccdf2);
        box-shadow: 0 12px 28px rgba(0,0,0,0.25);
    }

    .download-sub {
        display: block;
        font-size: 14px;
        margin-top: 8px;
        color: #333;
        font-weight: 600;
        opacity: 0.9;
    }

    /* SLIDESHOW */
    .slideshow-wrap {
        padding: 40px 18px;
        text-align: center;
    }

    .slideshow-container {
        position: relative;
        width: 100%;
        max-width: 1100px;
        margin: auto;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(0,0,0,0.18);
        background: #fff;
    }

    .slide-img {
        width: 100%;
        display: none;
        height: auto;
        object-fit: cover;
    }
    .slide-img.active { display: block; }

    .slider-controls {
        position: absolute;
        top: 50%;
        width: 100%;
        transform: translateY(-50%);
        display: flex;
        justify-content: space-between;
        pointer-events: none;
    }
    .slider-btn {
        pointer-events: auto;
        background: rgba(0,0,0,0.45);
        color: #fff;
        border: none;
        font-size: 20px;
        padding: 10px 14px;
        margin: 8px;
        border-radius: 50%;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.12s ease;
    }
    .slider-btn:hover {
        background: rgba(0,0,0,0.6);
        transform: scale(1.05);
    }

    /* JURUSAN */
    .program-blk {
        padding: 26px 18px;
    }

    .program-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .card {
        padding: 20px;
        background: #fff;
        border-radius: 14px;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .card h3 {
        margin: 0 0 8px 0;
        font-size: 18px;
    }
    .card p {
        margin: 0;
        color: #444;
        font-size: 14px;
    }

    /* SECTIONS */
    .info-blk, .contact, #cara-download {
        padding: 28px 18px;
        margin: 22px auto;
        max-width: 1100px;
        border-radius: 14px;
        background: var(--card-bg);
        box-shadow: 0 10px 26px rgba(0,0,0,0.12);
    }

    .cta {
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
    }

    .steps-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }
    .step-card {
        padding: 22px;
        border-radius: 14px;
        background: #fff;
        text-align: center;
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }

    footer {
        padding: 20px;
        text-align: center;
        margin: 28px auto 0;
        max-width: 1100px;
        background: var(--glass);
        backdrop-filter: blur(8px);
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        font-weight: 600;
    }

    @media (max-width: 900px) {
        .hero-box { padding: 28px; }
        .hero-text h2 { font-size: 26px; }
        .hero-text p { font-size: 15px; }
        .download-btn { padding: 14px 26px; font-size: 16px; }
    }

    @media (max-width: 480px) {
        header { padding: 12px; gap: 8px; }
        nav { display: none; }
        .hero { padding: 28px 12px; }
        .hero-text h2 { font-size: 22px; }
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
        <a href="#jurusan">Jurusan</a>
        <a href="#download">Download</a>
        <a href="#cara-download">Tata Cara</a>
        <a href="#kontak">Kontak</a>
    </nav>
    <a href="login.php" class="login-btn">Login</a>
</header>

<!-- HERO -->
<section class="hero fade-scroll">
    <div class="hero-box">
        <div class="hero-text">
            <h2>Download Aplikasi Tes Ujian BLK</h2>
            <p>Unduh aplikasi resmi BLK untuk mengikuti tes ujian dengan mudah, cepat, dan aman.</p>
            <a id="download" href="https://e-blk.pbltifnganjuk.com/E%20BLK.apk" class="download-btn" download>
                📥 Download Aplikasi (.APK)
                <span class="download-sub">Versi terbaru • Aman & Resmi</span>
            </a>
        </div>
    </div>
</section>

<!-- SLIDESHOW -->
<section class="slideshow-wrap fade-scroll">
    <h2 style="text-align:center; font-size:28px; margin-bottom:18px;">Foto UPT BLK Nganjuk</h2>
    <div class="slideshow-container" role="region" aria-label="Galeri Foto BLK">
        <img class="slide-img active" src="image/blk.jpeg" alt="Foto BLK 1">
        <img class="slide-img" src="image/blk_2.jpeg" alt="Foto BLK 2">
        <img class="slide-img" src="image/blk_3.jpeg" alt="Foto BLK 3">
        <img class="slide-img" src="image/blk_4.jpeg" alt="Foto BLK 4">
        <div class="slider-controls" aria-hidden="false">
            <button id="prev" class="slider-btn" aria-label="Sebelumnya">&#10094;</button>
            <button id="next" class="slider-btn" aria-label="Selanjutnya">&#10095;</button>
        </div>
    </div>
</section>

<!-- JURUSAN -->
<section class="program-blk fade-scroll" id="jurusan">
    <div class="info-blk">
        <h2 style="text-align:center; margin:0 0 16px 0; font-size:26px;">Program Pelatihan BLK Nganjuk</h2>
        <div class="program-grid" style="margin-top:18px;">
            <?php if (!empty($daftar_jurusan)): ?>
                <?php foreach ($daftar_jurusan as $jur): ?>
                    <div class="card fade-scroll-item">
                        <h3><?= htmlspecialchars($jur['nama_jurusan']) ?></h3>
                        <p>Pelatihan keterampilan dasar hingga lanjutan.</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card fade-scroll-item">
                    <h3>Belum Ada Program</h3>
                    <p>Data pelatihan sedang dalam pembaruan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA DAFTAR -->
<section class="cta fade-scroll">
    <a href="https://sinaker.disnakertrans.jatimprov.go.id/blk/859d44ff-cb2d-49a4-a598-f19683cf4746" target="_blank" rel="noopener">Daftar Pelatihan Sekarang</a>
</section>

<!-- INFORMASI -->
<section class="info-blk fade-scroll">
    <h2>UPT BLK Nganjuk</h2>
    <p style="font-weight:600;">Nomor VIN: 1903351802</p>
    <p style="margin-top:12px; line-height:1.7;">
        UPT BLK Nganjuk didirikan pada tanggal 7 Juli 1983 dengan nama <b>KLK (Kursus Latihan Kerja)</b>.
        Pada tahun 1985 berubah menjadi <b>BLKIP</b>, kemudian menjadi <b>LLK-UKM</b>.
        Pada era otonomi daerah tahun 2000 menjadi <b>BLKUKM Disnaker Provinsi Jawa Timur</b>.
        Berdasarkan Perda No. 35/2008 berganti nama menjadi <b>Unit Pelatihan Teknis Pelatihan Kerja</b>.
        Berdasarkan Pergub No. 62/2018 berganti nama menjadi <b>Unit Pelaksana Teknis Balai Latihan Kerja (BLK) Nganjuk</b> pada Disnakertrans Provinsi Jawa Timur.
    </p>
</section>

<!-- KONTAK -->
<section class="contact fade-scroll" id="kontak">
    <div class="info-blk">
        <h2 style="text-align:center;">Kontak UPT BLK Nganjuk</h2>
        <div style="max-width:900px; margin:auto; font-size:16px; line-height:1.8; text-align:center;">
            <p><b>Alamat:</b><br>Jl. Kapten Kasihin HS No. 3 Cangkringan, Kec. Nganjuk, Jawa Timur</p>
            <p><b>Telepon:</b> 0358 321048</p>
            <p><b>WhatsApp:</b> 0857 1357 3000</p>
            <p><b>Email:</b> blknganjuk@gmail.com</p>
            <p><b>Website:</b><br><a href="https://silengkap.disnakertrans.jatimprov.go.id" style="color:#0056b3; font-weight:600;">silengkap.disnakertrans.jatimprov.go.id</a></p>
            <p><b>Instagram:</b><br>@uptblknganjuk • @blknganjukofficial</p>
        </div>
    </div>
</section>

<!-- TATA CARA -->
<section id="cara-download" class="fade-scroll">
    <div class="info-blk">
        <h2 style="text-align:center;">Tata Cara Download Aplikasi</h2>
        <div class="steps-grid" style="margin-top:18px;">
            <div class="step-card fade-scroll-item">
                <div style="font-size:36px;">📥</div>
                <h3>1. Klik Tombol Download</h3>
                <p>Klik tombol <b>Download Aplikasi</b> di atas untuk memulai unduhan.</p>
            </div>
            <div class="step-card fade-scroll-item">
                <div style="font-size:36px;">⬇️</div>
                <h3>2. Tunggu Hingga Selesai</h3>
                <p>Pastikan koneksi internet stabil selama proses download.</p>
            </div>
            <div class="step-card fade-scroll-item">
                <div style="font-size:36px;">📂</div>
                <h3>3. Buka File APK</h3>
                <p>Buka <b>File Manager</b> → Folder <b>Download</b> → pilih file APK.</p>
            </div>
            <div class="step-card fade-scroll-item">
                <div style="font-size:36px;">🔐</div>
                <h3>4. Izinkan Sumber Tidak Dikenal</h3>
                <p>Di pengaturan keamanan, aktifkan <b>Sumber Tidak Dikenal</b>.</p>
            </div>
            <div class="step-card fade-scroll-item">
                <div style="font-size:36px;">✅</div>
                <h3>5. Install & Gunakan</h3>
                <p>Klik <b>Install</b> dan tunggu. Aplikasi siap digunakan!</p>
            </div>
        </div>
    </div>
</section>

<footer>
    © 2025 UPT BLK Nganjuk • Semua Hak Dilindungi
</footer>

<!-- SLIDER SCRIPT -->
<script>
(() => {
    const slides = Array.from(document.querySelectorAll('.slide-img'));
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

    let startX = 0;
    const container = document.querySelector('.slideshow-container');
    if (container) {
        container.addEventListener('touchstart', e => startX = e.touches[0].clientX);
        container.addEventListener('touchend', e => {
            const endX = e.changedTouches[0].clientX;
            if (startX - endX > 50) { idx = (idx + 1) % slides.length; show(idx); }
            else if (endX - startX > 50) { idx = (idx - 1 + slides.length) % slides.length; show(idx); }
        });
    }
})();
</script>

<!-- SCROLL REVEAL & SMOOTH NAVIGATION -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Smooth scroll saat klik navigasi header
    document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Section animation
    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-scroll').forEach(el => sectionObserver.observe(el));

    // Per-kotak animasi (jurusan & tata cara)
    const itemObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const parent = entry.target.parentNode;
                const index = Array.from(parent.children).indexOf(entry.target);
                entry.target.style.transitionDelay = `${index * 0.12}s`;
                entry.target.classList.add('show');
                itemObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.fade-scroll-item').forEach(el => itemObserver.observe(el));
});
</script>

</body>
</html>