<?php
session_start();

// --- KONEKSI DATABASE HOSTINGER (PAKAI CONFIG) ---
$conn = require __DIR__ . '/config/database.php';

// Cek login
if (!isset($_SESSION['nik'])) {
    header("Location: login.php");
    exit;
}

$nik_login = $_SESSION['nik'];


// Ambil id_jurusan peserta
$stmt = $conn->prepare("SELECT id_jurusan, nama_peserta FROM peserta WHERE NIK = ?");
$stmt->bind_param("s", $nik_login);
$stmt->execute();
$res = $stmt->get_result();
$peserta = $res->fetch_assoc();
$stmt->close();

if (!$peserta || !$peserta['id_jurusan']) {
    die("<h3 style='text-align:center;color:red;'>⚠ ID jurusan peserta tidak ditemukan!</h3>");
}

$id_jurusan = (int)$peserta['id_jurusan'];
$nama_peserta = htmlspecialchars($peserta['nama_peserta']);

// Ambil soal
$stmt = $conn->prepare("SELECT * FROM seleksi WHERE id_jurusan = ? ORDER BY no_soal ASC");
$stmt->bind_param("i", $id_jurusan);
$stmt->execute();
$res = $stmt->get_result();

$soal = [];
while ($row = $res->fetch_assoc()) $soal[] = $row;
$total_soal = count($soal);

// ===== AMBIL WAKTU TIMER DARI DATABASE =====
$stmt = $conn->prepare("SELECT waktu_mulai, waktu_selesai FROM seleksi WHERE id_jurusan = ? LIMIT 1");
$stmt->bind_param("i", $id_jurusan);
$stmt->execute();
$w = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Konversi ke detik
$mulai = strtotime($w['waktu_mulai']);
$selesai = strtotime($w['waktu_selesai']);
$durasi_detik = $selesai - $mulai;

// Jika ada error → default 30 menit
if ($durasi_detik <= 0) $durasi_detik = 30 * 60;

$conn->close();
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tes Seleksi e-BLK</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
/* ===================== TETAP — CSS ORIGINAL ===================== */
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #ffffff, #ffffff);
}
.header {
    background:#4F80FF;
    height: 70px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:20px;
    margin:20px;
    padding:0 40px;
    color:white;
    font-weight:600;
    font-size:22px;
}
.timer-box {
    background:white;
    color:#4F80FF;
    font-weight:700;
    font-size:24px;
    padding:8px 20px;
    border-radius:15px;
}
.main {
    display:flex;
    justify-content:center;
    align-items:flex-start;
    gap:20px;
    margin:20px;
}
.soal-container {
    background:white;
    width:65%;
    padding:30px;
    border-radius:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}
.soal-text {
    background:#f2f6ff;
    border-radius:15px;
    padding:20px;
    font-size:17px;
    font-weight:500;
    color:#4F80FF;
    margin-bottom:20px;
    min-height:100px;
}
.opsi { display:flex; flex-direction:column; gap:10px; }
.opsi label {
    background:#f7f9ff;
    border:1px solid #cdd8ff;
    border-radius:10px;
    padding:10px 15px;
    cursor:pointer;
    transition:0.3s;
}
.opsi input { margin-right:8px; }
.opsi label:hover { background:#d9e5ff; }

.btn-area { display:flex; justify-content:space-between; margin-top:30px; }
.btn-prev, .btn-next, .btn-submit {
    border:none; border-radius:25px;
    font-size:16px; padding:10px 25px;
    font-weight:600; cursor:pointer; color:white;
}
.btn-prev { background:#ff3c3c; }
.btn-next { background:#4F80FF; }
.btn-submit { background:#009944; width:100%; margin-top:25px; }

.nav-container {
    background:white; width:30%; padding:25px; border-radius:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
}
.nav-container h3 { text-align:center; color:#4F80FF; margin-bottom:15px; }
.nav-grid {
    display:grid; grid-template-columns:repeat(5,1fr);
    gap:10px; justify-items:center;
}
.nav-grid button {
    width:55px; height:55px; border:none;
    background:#e1e8ff; border-radius:12px;
    font-weight:600; cursor:pointer; transition:0.2s;
}
.nav-grid button.active { background:#4F80FF; color:white; }
.nav-grid button.answered { background:#4CAF50; color:white; }
.nav-grid button:hover { background:#9bb7ff; }

/* ===================== TAMBAHAN MODERN ===================== */
.timer-box {
    box-shadow: 0 0 15px rgba(255,255,255,.8);
    animation: glow 2s infinite alternate;
}
@keyframes glow {
    from { box-shadow: 0 0 10px rgba(255,255,255,0.4); }
    to   { box-shadow: 0 0 20px rgba(255,255,255,1); }
}

.soal-container, .nav-container {
    transition: transform .2s ease;
}
.soal-container:hover, .nav-container:hover {
    transform: scale(1.01);
}

.nav-grid button {
    transition: transform .15s ease, background .25s;
}
.nav-grid button:active {
    transform: scale(0.85);
}

.btn-prev:hover, .btn-next:hover, .btn-submit:hover {
    opacity: .8;
    transform: scale(1.03);
    transition: .2s;
}
</style>
</head>
<body>

<div class="header">
    <div>🧠 TES SELEKSI E-BLK</div>
    <div class="timer-box" id="timer">00:00</div>
</div>

<form id="formTes" method="post" action="simpan_tes.php">
<input type="hidden" name="id_jurusan" value="<?= $id_jurusan ?>">
<input type="hidden" name="nik" value="<?= $nik_login ?>">

<div class="main">
    <div class="soal-container">
        <?php if ($total_soal > 0): ?>
            <?php foreach ($soal as $index => $s): ?>
                <div class="soal-item" id="soal-<?= $index ?>" style="<?= $index === 0 ? '' : 'display:none;' ?>">
                    <div class="soal-text">
                        <?= $s['no_soal'] . ". " . htmlspecialchars($s['soal']); ?>
                    </div>
                    <div class="opsi">
                        <label><input type="radio" name="jawaban[<?= $s['id_seleksi'] ?>]" value="a" onchange="markAnswered(<?= $index ?>)"> <?= htmlspecialchars($s['opsi_a']); ?></label>
                        <label><input type="radio" name="jawaban[<?= $s['id_seleksi'] ?>]" value="b" onchange="markAnswered(<?= $index ?>)"> <?= htmlspecialchars($s['opsi_b']); ?></label>
                        <label><input type="radio" name="jawaban[<?= $s['id_seleksi'] ?>]" value="c" onchange="markAnswered(<?= $index ?>)"> <?= htmlspecialchars($s['opsi_c']); ?></label>
                        <label><input type="radio" name="jawaban[<?= $s['id_seleksi'] ?>]" value="d" onchange="markAnswered(<?= $index ?>)"> <?= htmlspecialchars($s['opsi_d']); ?></label>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="btn-area">
                <button type="button" class="btn-prev" onclick="prevSoal()"><< Sebelumnya</button>
                <button type="button" class="btn-next" onclick="nextSoal()">Selanjutnya >></button>
            </div>

            <button type="button" class="btn-submit" onclick="submitTes()">Kirim Jawaban</button>

        <?php else: ?>
            <h3 style="color:red;text-align:center;">Tidak ada soal untuk jurusan ini!</h3>
        <?php endif; ?>
    </div>

    <?php if ($total_soal > 0): ?>
    <div class="nav-container">
        <h3>Navigasi Soal</h3>
        <div class="nav-grid">
            <?php for ($i = 0; $i < $total_soal; $i++): ?>
                <button type="button" id="nav-<?= $i ?>" onclick="gotoSoal(<?= $i ?>)"><?= $i+1 ?></button>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</form>

<script>
/* ========= NAVIGASI SOAL ========= */
let currentIndex = 0;
const total = <?= $total_soal ?>;
const btnNav = [...Array(total)].map((_, i) => document.getElementById("nav-" + i));

function showSoal(i) {
    for (let j = 0; j < total; j++) {
        document.getElementById("soal-" + j).style.display = "none";
        btnNav[j].classList.remove("active");
    }
    document.getElementById("soal-" + i).style.display = "block";
    btnNav[i].classList.add("active");
    currentIndex = i;
}

function nextSoal() { if (currentIndex < total - 1) showSoal(currentIndex + 1); }
function prevSoal() { if (currentIndex > 0) showSoal(currentIndex - 1); }
function gotoSoal(i) { showSoal(i); }
function markAnswered(i) { btnNav[i].classList.add("answered"); }

showSoal(0);

/* ========= TIMER ANTI REFRESH ========= */
let durasi = <?= $durasi_detik ?>;
const KEY = "timer_<?= $nik_login ?>_<?= $id_jurusan ?>";
let sisa = localStorage.getItem(KEY);

sisa = sisa === null ? durasi : parseInt(sisa);

const timerDisplay = document.getElementById("timer");

let x = setInterval(() => {

    let m = Math.floor(sisa / 60);
    let d = sisa % 60;

    timerDisplay.textContent =
        `${String(m).padStart(2,'0')}:${String(d).padStart(2,'0')}`;

    sisa--;
    localStorage.setItem(KEY, sisa);

    if (sisa < 0) {
        clearInterval(x);
        localStorage.removeItem(KEY);
        alert("⏰ Waktu habis! Jawaban dikirim otomatis.");
        document.getElementById("formTes").submit();
    }
}, 1000);

document.getElementById("formTes").addEventListener("submit", () => {
    localStorage.removeItem(KEY);
});

/* ========= VALIDASI SUBMIT ========= */
function submitTes() {
    if (confirm("Yakin ingin mengirim jawaban?")) {
        localStorage.removeItem(KEY);
        document.getElementById("formTes").submit();
    }
}
</script>

</body>
</html>
